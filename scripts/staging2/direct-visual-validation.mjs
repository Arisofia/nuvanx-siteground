import { chromium } from 'playwright';
import fs from 'node:fs/promises';
import path from 'node:path';

const baseUrl = 'https://staging2.nuvanx.com';
const outDir = path.resolve('scripts/staging2/visual-validation-results');
await fs.rm(outDir, { recursive: true, force: true });
await fs.mkdir(outDir, { recursive: true });

const browser = await chromium.launch({ headless: true, args: ['--no-sandbox'] });

async function validatePage(urlPath, pageName) {
  console.log(`\n========================================`);
  console.log(`Validating: ${baseUrl}${urlPath}`);
  console.log(`========================================`);

  const viewports = [
    { name: 'desktop', width: 1440, height: 1100 },
    { name: 'tablet', width: 1024, height: 768 },
    { name: 'mobile', width: 390, height: 844 },
  ];

  for (const vp of viewports) {
    const context = await browser.newContext({
      viewport: { width: vp.width, height: vp.height },
      userAgent: 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
    });
    const page = await context.newPage();

    let response;
    for (let attempt = 1; attempt <= 4; attempt++) {
      try {
        response = await page.goto(`${baseUrl}${urlPath}`, { waitUntil: 'networkidle', timeout: 30000 });
        if (response && response.status() === 200) break;
      } catch (e) {
        console.log(`[Attempt ${attempt}] Navigation retry for ${vp.name}...`);
      }
      await page.waitForTimeout(2000);
    }

    const status = response ? response.status() : 'NO_RESPONSE';
    console.log(`\n[${vp.name} (${vp.width}x${vp.height})] HTTP Status: ${status}`);

    // Wait a brief moment for dynamic layout
    await page.waitForTimeout(1000);

    // Evaluate metrics
    const metrics = await page.evaluate(() => {
      const results = {};

      // 1. Navigation items
      const navLinks = Array.from(document.querySelectorAll('.nvx-nav__link'));
      if (navLinks.length > 0) {
        const firstLink = navLinks[0];
        const cs = window.getComputedStyle(firstLink);
        const rect = firstLink.getBoundingClientRect();
        results.nav = {
          count: navLinks.length,
          fontSize: cs.fontSize,
          computedHeight: rect.height,
          paddingInline: cs.paddingLeft + ' ' + cs.paddingRight,
        };

        const navList = document.querySelector('.nvx-nav__list');
        if (navList) {
          const listRect = navList.getBoundingClientRect();
          results.nav.listHeight = listRect.height;
          results.nav.isSingleRow = listRect.height < 60;
        }
      }

      // 2. Action links & touch targets
      const touchTargets = [];
      const linksToCheck = [
        ...Array.from(document.querySelectorAll('.nvx-home-portfolio__link')),
        ...Array.from(document.querySelectorAll('.nvx-home-location__link')),
        ...Array.from(document.querySelectorAll('.nvx-home-seo__list .nvx-text-link')),
        ...Array.from(document.querySelectorAll('.nvx-brand-inline-link')),
        ...Array.from(document.querySelectorAll('.nvx-valoracion-direct-contact a')),
      ];

      for (const link of linksToCheck.slice(0, 10)) {
        const rect = link.getBoundingClientRect();
        touchTargets.push({
          text: link.innerText.trim().slice(0, 30),
          width: Math.round(rect.width),
          height: Math.round(rect.height),
          passMin44: rect.height >= 43.5,
        });
      }
      results.touchTargets = touchTargets;

      // 3. HubSpot Form & Stage dimensions
      const formStage = document.querySelector('.nvx-form-stage, #nvx-hubspot-form');
      if (formStage) {
        const stageRect = formStage.getBoundingClientRect();
        results.formStage = {
          height: Math.round(stageRect.height),
          width: Math.round(stageRect.width),
        };
      }

      const directContact = document.querySelector('.nvx-valoracion-direct-contact');
      if (directContact) {
        const dcRect = directContact.getBoundingClientRect();
        results.directContact = {
          visible: dcRect.height > 0 && dcRect.width > 0,
          height: Math.round(dcRect.height),
        };
      }

      // 4. Protocols / Features Grid
      const standardGrid = document.querySelector('.nvx-home-standard__grid');
      if (standardGrid) {
        const cs = window.getComputedStyle(standardGrid);
        results.standardGrid = {
          gridTemplateColumns: cs.gridTemplateColumns,
        };
      }

      // 5. Floating chat widgets
      const hsMessages = document.querySelector('#hubspot-messages-iframe-container, .hs-messages-widget-open, iframe[id^="hubspot-messages-iframe"]');
      const joinChat = document.querySelector('.joinchat, .joinchat__button');
      results.floatingWidgets = {
        hubspotMessagesHiddenOrAbsent: !hsMessages || window.getComputedStyle(hsMessages).display === 'none',
        joinChatPresent: !!joinChat,
      };

      // 6. Cookie banner
      const cookieBanner = document.querySelector('.cmplz-cookiebanner, #cmplz-cookiebanner-container, .cmplz-banner');
      if (cookieBanner) {
        const cbRect = cookieBanner.getBoundingClientRect();
        results.cookieBanner = {
          present: true,
          height: Math.round(cbRect.height),
          bottom: Math.round(window.innerHeight - cbRect.bottom),
        };
      }

      return results;
    });

    console.log(JSON.stringify(metrics, null, 2));

    // Take screenshot
    const screenshotPath = path.join(outDir, `${pageName}-${vp.name}.png`);
    await page.screenshot({ path: screenshotPath, fullPage: false });
    console.log(`📸 Saved screenshot: ${screenshotPath}`);

    await context.close();
  }
}

try {
  await validatePage('/', 'home');
  await validatePage('/madrid/valoracion/', 'valoracion');
  await validatePage('/tratamientos/endolift-facial-papada-mandibula/', 'endolift');
} finally {
  await browser.close();
}
