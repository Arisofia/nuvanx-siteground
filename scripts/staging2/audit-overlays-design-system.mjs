import { spawnSync } from 'node:child_process';
import fs from 'node:fs/promises';
import path from 'node:path';

const AGENT_BROWSER = 'agent-browser';

function runAgentBrowser(args) {
  const result = spawnSync(AGENT_BROWSER, args, { encoding: 'utf8', timeout: 30000 });
  if (result.status !== 0) {
    throw new Error(`agent-browser failed: ${result.stderr}`);
  }
  return result.stdout;
}

async function auditOverlays(url) {
  const issues = [];
  const overlays = {
    complianz: {},
    consent: {},
    whatsapp: {},
    hubspot: {},
    thirdParty: {},
  };

  try {
    // Open page with first visit state
    runAgentBrowser(['close', '--all']);
    runAgentBrowser(['open', '--clear-cookies', url]);
    runAgentBrowser(['wait', '--load', 'networkidle']);

    // Audit Complianz/consent
    try {
      const consentAudit = await auditConsentBanner();
      overlays.consent = consentAudit;
      issues.push(...consentAudit.issues);
    } catch (error) {
      issues.push(`Consent audit failed: ${error.message}`);
    }

    // Audit WhatsApp
    try {
      const whatsappAudit = await auditWhatsApp();
      overlays.whatsapp = whatsappAudit;
      issues.push(...whatsappAudit.issues);
    } catch (error) {
      issues.push(`WhatsApp audit failed: ${error.message}`);
    }

    // Audit HubSpot
    try {
      const hubspotAudit = await auditHubSpotForm();
      overlays.hubspot = hubspotAudit;
      issues.push(...hubspotAudit.issues);
    } catch (error) {
      issues.push(`HubSpot audit failed: ${error.message}`);
    }

    // Audit third-party overlays
    try {
      const thirdPartyAudit = await auditThirdPartyOverlays();
      overlays.thirdParty = thirdPartyAudit;
      issues.push(...thirdPartyAudit.issues);
    } catch (error) {
      issues.push(`Third-party audit failed: ${error.message}`);
    }

    runAgentBrowser(['close']);

    return {
      valid: issues.length === 0,
      issues,
      overlays,
    };
  } catch (error) {
    runAgentBrowser(['close']);
    throw error;
  }
}

async function auditConsentBanner() {
  const issues = [];
  const audit = {
    exists: false,
    size: null,
    position: null,
    zIndex: null,
    responsive: false,
    contrast: null,
    overlaps: [],
  };

  try {
    // Check if consent banner exists
    const existsScript = `
      const banner = document.querySelector('[class*="consent"], [class*="cookie"], [id*="consent"], [id*="cookie"]');
      JSON.stringify(!!banner);
    `;
    const existsResult = runAgentBrowser(['eval', '-b', Buffer.from(existsScript).toString('base64')]);
    audit.exists = JSON.parse(existsResult);

    if (!audit.exists) {
      issues.push('Consent banner not found');
      return { issues, audit };
    }

    // Check size
    const sizeScript = `
      const banner = document.querySelector('[class*="consent"], [class*="cookie"], [id*="consent"], [id*="cookie"]');
      if (banner) {
        const rect = banner.getBoundingClientRect();
        JSON.stringify({ width: rect.width, height: rect.height });
      } else {
        JSON.stringify(null);
      }
    `;
    const sizeResult = runAgentBrowser(['eval', '-b', Buffer.from(sizeScript).toString('base64')]);
    audit.size = JSON.parse(sizeResult);

    if (audit.size) {
      const MAX_WIDTH = window.innerWidth * 0.9;
      const MAX_HEIGHT = window.innerHeight * 0.5;
      if (audit.size.width > MAX_WIDTH) {
        issues.push(`Consent banner too wide: ${audit.size.width}px (max: ${MAX_WIDTH}px)`);
      }
      if (audit.size.height > MAX_HEIGHT) {
        issues.push(`Consent banner too tall: ${audit.size.height}px (max: ${MAX_HEIGHT}px)`);
      }
    }

    // Check z-index
    const zIndexScript = `
      const banner = document.querySelector('[class*="consent"], [class*="cookie"], [id*="consent"], [id*="cookie"]');
      if (banner) {
        const computed = window.getComputedStyle(banner);
        JSON.stringify(parseInt(computed.zIndex) || 0);
      } else {
        JSON.stringify(null);
      }
    `;
    const zIndexResult = runAgentBrowser(['eval', '-b', Buffer.from(zIndexScript).toString('base64')]);
    audit.zIndex = JSON.parse(zIndexResult);

    if (audit.zIndex !== null && audit.zIndex < 1000) {
      issues.push(`Consent banner z-index too low: ${audit.zIndex} (recommended: >= 1000)`);
    }

    // Check position
    const positionScript = `
      const banner = document.querySelector('[class*="consent"], [class*="cookie"], [id*="consent"], [id*="cookie"]');
      if (banner) {
        const computed = window.getComputedStyle(banner);
        JSON.stringify({
          position: computed.position,
          bottom: computed.bottom,
          top: computed.top,
          left: computed.left,
          right: computed.right
        });
      } else {
        JSON.stringify(null);
      }
    `;
    const positionResult = runAgentBrowser(['eval', '-b', Buffer.from(positionScript).toString('base64')]);
    audit.position = JSON.parse(positionResult);

    if (audit.position && audit.position.position === 'fixed') {
      audit.responsive = true;
    } else {
      issues.push('Consent banner should use position: fixed');
    }

  } catch (error) {
    issues.push(`Consent audit error: ${error.message}`);
  }

  return { issues, audit };
}

async function auditWhatsApp() {
  const issues = [];
  const audit = {
    exists: false,
    size: null,
    position: null,
    zIndex: null,
    responsive: false,
    overlaps: [],
  };

  try {
    // Check if WhatsApp widget exists
    const existsScript = `
      const whatsapp = document.querySelector('[class*="whatsapp"], [data*="whatsapp"], [id*="whatsapp"]');
      JSON.stringify(!!whatsapp);
    `;
    const existsResult = runAgentBrowser(['eval', '-b', Buffer.from(existsScript).toString('base64')]);
    audit.exists = JSON.parse(existsResult);

    if (!audit.exists) {
      return { issues, audit };
    }

    // Check size (WhatsApp should be small, ~60x60)
    const sizeScript = `
      const whatsapp = document.querySelector('[class*="whatsapp"], [data*="whatsapp"], [id*="whatsapp"]');
      if (whatsapp) {
        const rect = whatsapp.getBoundingClientRect();
        JSON.stringify({ width: rect.width, height: rect.height });
      } else {
        JSON.stringify(null);
      }
    `;
    const sizeResult = runAgentBrowser(['eval', '-b', Buffer.from(sizeScript).toString('base64')]);
    audit.size = JSON.parse(sizeResult);

    if (audit.size) {
      const MAX_SIZE = 80;
      if (audit.size.width > MAX_SIZE || audit.size.height > MAX_SIZE) {
        issues.push(`WhatsApp widget too large: ${audit.size.width}x${audit.size.height}px (max: ${MAX_SIZE}x${MAX_SIZE}px)`);
      }
    }

    // Check z-index (should be high but not too high)
    const zIndexScript = `
      const whatsapp = document.querySelector('[class*="whatsapp"], [data*="whatsapp"], [id*="whatsapp"]');
      if (whatsapp) {
        const computed = window.getComputedStyle(whatsapp);
        JSON.stringify(parseInt(computed.zIndex) || 0);
      } else {
        JSON.stringify(null);
      }
    `;
    const zIndexResult = runAgentBrowser(['eval', '-b', Buffer.from(zIndexScript).toString('base64')]);
    audit.zIndex = JSON.parse(zIndexResult);

    if (audit.zIndex !== null && (audit.zIndex < 1000 || audit.zIndex > 9999)) {
      issues.push(`WhatsApp z-index out of range: ${audit.zIndex} (recommended: 1000-9999)`);
    }

    // Check position (should be fixed at bottom-right)
    const positionScript = `
      const whatsapp = document.querySelector('[class*="whatsapp"], [data*="whatsapp"], [id*="whatsapp"]');
      if (whatsapp) {
        const computed = window.getComputedStyle(whatsapp);
        JSON.stringify({
          position: computed.position,
          bottom: computed.bottom,
          right: computed.right
        });
      } else {
        JSON.stringify(null);
      }
    `;
    const positionResult = runAgentBrowser(['eval', '-b', Buffer.from(positionScript).toString('base64')]);
    audit.position = JSON.parse(positionResult);

    if (audit.position && audit.position.position === 'fixed') {
      audit.responsive = true;
    } else {
      issues.push('WhatsApp widget should use position: fixed');
    }

  } catch (error) {
    issues.push(`WhatsApp audit error: ${error.message}`);
  }

  return { issues, audit };
}

async function auditHubSpotForm() {
  const issues = [];
  const audit = {
    exists: false,
    size: null,
    position: null,
    zIndex: null,
    responsive: false,
    overlaps: [],
  };

  try {
    // Check if HubSpot form exists
    const existsScript = `
      const hubspot = document.querySelector('iframe[src*="hubspot"], [class*="hs-form"]');
      JSON.stringify(!!hubspot);
    `;
    const existsResult = runAgentBrowser(['eval', '-b', Buffer.from(existsScript).toString('base64')]);
    audit.exists = JSON.parse(existsResult);

    if (!audit.exists) {
      return { issues, audit };
    }

    // Check size
    const sizeScript = `
      const hubspot = document.querySelector('iframe[src*="hubspot"], [class*="hs-form"]');
      if (hubspot) {
        const rect = hubspot.getBoundingClientRect();
        JSON.stringify({ width: rect.width, height: rect.height });
      } else {
        JSON.stringify(null);
      }
    `;
    const sizeResult = runAgentBrowser(['eval', '-b', Buffer.from(sizeScript).toString('base64')]);
    audit.size = JSON.parse(sizeResult);

    if (audit.size) {
      const MIN_WIDTH = 300;
      const MIN_HEIGHT = 200;
      if (audit.size.width < MIN_WIDTH || audit.size.height < MIN_HEIGHT) {
        issues.push(`HubSpot form too small: ${audit.size.width}x${audit.size.height}px (min: ${MIN_WIDTH}x${MIN_HEIGHT}px)`);
      }
    }

    // Check z-index
    const zIndexScript = `
      const hubspot = document.querySelector('iframe[src*="hubspot"], [class*="hs-form"]');
      if (hubspot) {
        const computed = window.getComputedStyle(hubspot);
        JSON.stringify(parseInt(computed.zIndex) || 0);
      } else {
        JSON.stringify(null);
      }
    `;
    const zIndexResult = runAgentBrowser(['eval', '-b', Buffer.from(zIndexScript).toString('base64')]);
    audit.zIndex = JSON.parse(zIndexResult);

    if (audit.zIndex !== null && audit.zIndex < 100) {
      issues.push(`HubSpot form z-index too low: ${audit.zIndex} (recommended: >= 100)`);
    }

  } catch (error) {
    issues.push(`HubSpot audit error: ${error.message}`);
  }

  return { issues, audit };
}

async function auditThirdPartyOverlays() {
  const issues = [];
  const audit = {
    count: 0,
    overlays: [],
    maxZIndex: 0,
  };

  try {
    // Find all fixed/absolute positioned elements
    const findScript = `
      const all = document.querySelectorAll('*');
      const overlays = [];
      let maxZ = 0;
      
      all.forEach(el => {
        const computed = window.getComputedStyle(el);
        const position = computed.position;
        const zIndex = parseInt(computed.zIndex) || 0;
        
        if ((position === 'fixed' || position === 'absolute') && zIndex > 0) {
          const rect = el.getBoundingClientRect();
          overlays.push({
            tag: el.tagName,
            class: el.className,
            position,
            zIndex,
            width: rect.width,
            height: rect.height,
            visible: rect.width > 0 && rect.height > 0
          });
          
          if (zIndex > maxZ) maxZ = zIndex;
        }
      });
      
      JSON.stringify({ overlays, maxZIndex: maxZ });
    `;
    const findResult = runAgentBrowser(['eval', '-b', Buffer.from(findScript).toString('base64')]);
    const result = JSON.parse(findResult);
    
    audit.count = result.overlays.length;
    audit.overlays = result.overlays.filter(o => o.visible);
    audit.maxZIndex = result.maxZIndex;

    // Check for excessive z-index values
    if (audit.maxZIndex > 10000) {
      issues.push(`Excessive z-index detected: ${audit.maxZIndex} (recommended: <= 10000)`);
    }

    // Check for too many overlays
    if (audit.count > 10) {
      issues.push(`Too many overlays detected: ${audit.count} (recommended: <= 10)`);
    }

  } catch (error) {
    issues.push(`Third-party audit error: ${error.message}`);
  }

  return { issues, audit };
}

export async function runOverlaysDesignSystemAudit(options = {}) {
  const url = options.url || process.env.STAGING_URL || 'https://staging2.nuvanx.com';
  const outputDir = path.resolve(options.outputDir || 'scripts/staging2/artifacts');
  
  await fs.mkdir(outputDir, { recursive: true });

  // Check if agent-browser is installed
  const checkResult = spawnSync(AGENT_BROWSER, ['--version'], { encoding: 'utf8' });
  if (checkResult.status !== 0) {
    const report = {
      schema: 'overlays-design-system-audit',
      checkedAt: new Date().toISOString(),
      url,
      validation: 'SKIP',
      reason: 'agent-browser not installed',
    };
    await fs.writeFile(path.join(outputDir, 'overlays-design-system-audit.json'), `${JSON.stringify(report, null, 2)}\n`, 'utf8');
    console.log('OVERLAYS_DESIGN_SYSTEM_AUDIT=SKIP reason=agent-browser_not_installed');
    return report;
  }

  try {
    const audit = await auditOverlays(url);

    const report = {
      schema: 'overlays-design-system-audit',
      checkedAt: new Date().toISOString(),
      url,
      validation: audit.valid ? 'PASS' : 'FAIL',
      issues: audit.issues,
      overlays: audit.overlays,
    };

    await fs.writeFile(path.join(outputDir, 'overlays-design-system-audit.json'), `${JSON.stringify(report, null, 2)}\n`, 'utf8');

    if (!audit.valid) {
      console.error('OVERLAYS_DESIGN_SYSTEM_AUDIT=FAIL');
      audit.issues.forEach((issue) => console.error(`- ${issue}`));
      throw new Error(`Overlays design system audit failed with ${audit.issues.length} issue(s).`);
    }

    console.log(`OVERLAYS_DESIGN_SYSTEM_AUDIT=PASS url=${url}`);
    return report;
  } catch (error) {
    const report = {
      schema: 'overlays-design-system-audit',
      checkedAt: new Date().toISOString(),
      url,
      validation: 'FAIL',
      error: error.message,
    };
    await fs.writeFile(path.join(outputDir, 'overlays-design-system-audit.json'), `${JSON.stringify(report, null, 2)}\n`, 'utf8');
    console.error('OVERLAYS_DESIGN_SYSTEM_AUDIT=FAIL');
    throw error;
  }
}