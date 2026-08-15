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

async function validateHubSpotIframe(url) {
  const issues = [];
  const checks = {
    mounts: 0,
    iframes: 0,
    visibleControls: 0,
    iframeBoundingBox: null,
    formIntersectsViewport: false,
    oversizedBlankContainer: false,
    duplicateLoader: false,
  };

  try {
    // Open page
    runAgentBrowser(['close', '--all']);
    runAgentBrowser(['open', url]);
    runAgentBrowser(['wait', '--load', 'networkidle']);

    // Get snapshot for interactive elements
    const snapshot = runAgentBrowser(['snapshot', '-i']);
    
    // Get HTML for detailed analysis
    const html = runAgentBrowser(['read']);

    // Count mounts (HubSpot mount points)
    const mountRegex = /data-hs-cos|hubspot-form|hs-form/gi;
    const mountMatches = html.match(mountRegex);
    checks.mounts = mountMatches ? mountMatches.length : 0;

    // Count iframes from snapshot
    const iframeRegex = /\[iframe\]/gi;
    const iframeMatches = snapshot.match(iframeRegex);
    checks.iframes = iframeMatches ? iframeMatches.length : 0;

    // Count visible controls from snapshot
    const controlRegex = /\[input\]|\[button\]|\[select\]|\[textarea\]/gi;
    const controlMatches = snapshot.match(controlRegex);
    checks.visibleControls = controlMatches ? controlMatches.length : 0;

    // Check iframe bounding box (using eval)
    try {
      const bboxScript = `
        const iframe = document.querySelector('iframe[src*="hubspot"]');
        if (iframe) {
          const rect = iframe.getBoundingClientRect();
          JSON.stringify({
            width: rect.width,
            height: rect.height,
            top: rect.top,
            left: rect.left,
            bottom: rect.bottom,
            right: rect.right
          });
        } else {
          JSON.stringify(null);
        }
      `;
      const bboxResult = runAgentBrowser(['eval', '-b', Buffer.from(bboxScript).toString('base64')]);
      checks.iframeBoundingBox = JSON.parse(bboxResult);
      
      if (checks.iframeBoundingBox) {
        const MIN_WIDTH = 300;
        const MIN_HEIGHT = 200;
        if (checks.iframeBoundingBox.width < MIN_WIDTH || checks.iframeBoundingBox.height < MIN_HEIGHT) {
          issues.push(`Iframe bounding box too small: ${checks.iframeBoundingBox.width}x${checks.iframeBoundingBox.height} (minimum: ${MIN_WIDTH}x${MIN_HEIGHT})`);
        }
      }
    } catch (bboxError) {
      issues.push('Failed to check iframe bounding box');
    }

    // Check if form intersects viewport
    try {
      const viewportScript = `
        const iframe = document.querySelector('iframe[src*="hubspot"]');
        if (iframe) {
          const rect = iframe.getBoundingClientRect();
          const viewport = { width: window.innerWidth, height: window.innerHeight };
          const intersects = rect.top < viewport.height && rect.bottom > 0 && rect.left < viewport.width && rect.right > 0;
          JSON.stringify(intersects);
        } else {
          JSON.stringify(false);
        }
      `;
      const viewportResult = runAgentBrowser(['eval', '-b', Buffer.from(viewportScript).toString('base64')]);
      checks.formIntersectsViewport = JSON.parse(viewportResult);
      
      if (!checks.formIntersectsViewport) {
        issues.push('HubSpot form does not intersect viewport');
      }
    } catch (viewportError) {
      issues.push('Failed to check viewport intersection');
    }

    // Check for duplicate loaders
    const loaderRegex = /loading|loader|spinner/gi;
    const loaderMatches = html.match(loaderRegex);
    checks.duplicateLoader = loaderMatches && loaderMatches.length > 1;

    // Check for oversized blank containers
    const emptyDivRegex = /<div[^>]*>\s*<\/div>/gi;
    const emptyDivMatches = html.match(emptyDivRegex);
    checks.oversizedBlankContainer = emptyDivMatches && emptyDivMatches.length > 5; // Allow up to 5

    // Validate conjoint conditions
    if (checks.mounts !== 1) {
      issues.push(`HubSpot mounts: expected 1, found ${checks.mounts}`);
    }

    if (checks.iframes !== 1) {
      issues.push(`HubSpot iframes: expected 1, found ${checks.iframes}`);
    }

    if (checks.visibleControls === 0) {
      issues.push('No visible form controls found');
    }

    if (checks.duplicateLoader) {
      issues.push('Duplicate loaders detected');
    }

    if (checks.oversizedBlankContainer) {
      issues.push('Oversized blank containers detected');
    }

    // Close browser
    runAgentBrowser(['close']);

    return {
      valid: issues.length === 0,
      issues,
      checks,
    };
  } catch (error) {
    runAgentBrowser(['close']);
    throw error;
  }
}

export async function runHubSpotSpecificGate(options = {}) {
  const url = options.url || process.env.STAGING_URL || 'https://staging2.nuvanx.com';
  const outputDir = path.resolve(options.outputDir || 'scripts/staging2/artifacts');
  
  await fs.mkdir(outputDir, { recursive: true });

  // Check if agent-browser is installed
  const checkResult = spawnSync(AGENT_BROWSER, ['--version'], { encoding: 'utf8' });
  if (checkResult.status !== 0) {
    const report = {
      schema: 'hubspot-specific-gate',
      checkedAt: new Date().toISOString(),
      url,
      validation: 'SKIP',
      reason: 'agent-browser not installed',
    };
    await fs.writeFile(path.join(outputDir, 'hubspot-specific-gate.json'), `${JSON.stringify(report, null, 2)}\n`, 'utf8');
    console.log('HUBSPOT_SPECIFIC_GATE=SKIP reason=agent-browser_not_installed');
    return report;
  }

  try {
    const validation = await validateHubSpotIframe(url);

    const report = {
      schema: 'hubspot-specific-gate',
      checkedAt: new Date().toISOString(),
      url,
      validation: validation.valid ? 'PASS' : 'FAIL',
      issues: validation.issues,
      checks: validation.checks,
    };

    await fs.writeFile(path.join(outputDir, 'hubspot-specific-gate.json'), `${JSON.stringify(report, null, 2)}\n`, 'utf8');

    if (!validation.valid) {
      console.error('HUBSPOT_SPECIFIC_GATE=FAIL');
      validation.issues.forEach((issue) => console.error(`- ${issue}`));
      throw new Error(`HubSpot specific gate failed with ${validation.issues.length} issue(s). iframeExists=true does not guarantee PASS.`);
    }

    console.log(`HUBSPOT_SPECIFIC_GATE=PASS url=${url} mounts=${validation.checks.mounts} iframes=${validation.checks.iframes} controls=${validation.checks.visibleControls}`);
    return report;
  } catch (error) {
    const report = {
      schema: 'hubspot-specific-gate',
      checkedAt: new Date().toISOString(),
      url,
      validation: 'FAIL',
      error: error.message,
    };
    await fs.writeFile(path.join(outputDir, 'hubspot-specific-gate.json'), `${JSON.stringify(report, null, 2)}\n`, 'utf8');
    console.error('HUBSPOT_SPECIFIC_GATE=FAIL');
    throw error;
  }
}