import { chromium } from 'playwright';

const baseUrl = process.env.BASE_URL || 'https://staging2.nuvanx.com';
const expectedHost = process.env.EXPECTED_HOST || 'staging2.nuvanx.com';
let base;
try {
  base = new URL(baseUrl);
} catch {
  throw new Error(`BASE_URL must be an absolute URL; received=${JSON.stringify(baseUrl)}`);
}
if (base.protocol !== 'https:' || base.hostname !== expectedHost || base.username || base.password) {
  throw new Error(`Refusing unexpected staging target: base=${base.origin} expected_host=${expectedHost}`);
}

const criticalUrls = [
  '/',
  '/madrid/valoracion/',
  '/endolift-facial-papada-mandibula/',
  '/contacto/',
  '/clinicas-de-medicina-estetica-nuvanx/',
  '/tratamientos/',
  '/blog/',
];

const browser = await chromium.launch({ headless: true, args: ['--no-sandbox'] });
const results = [];

function crossHostNavigationUrls(response, finalUrl) {
  const urls = [finalUrl];
  let request = response?.request();
  while (request) {
    urls.push(request.url());
    request = request.redirectedFrom();
  }

  return [...new Set(urls)].filter((url) => {
    try {
      return new URL(url).hostname !== expectedHost;
    } catch {
      return true;
    }
  });
}

for (const path of criticalUrls) {
  const url = new URL(path, base).href;
  const context = await browser.newContext({
    viewport: { width: 1440, height: 1100 },
    ignoreHTTPSErrors: true,
  });
  const page = await context.newPage();
  
  try {
    const response = await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 30000 });
    const status = response?.status() || 0;
    const crossHostUrls = crossHostNavigationUrls(response, page.url());
    
    // Check for critical elements
    const hasHeader = await page.locator('header, .nvx-header, .nvx-site-header').count() > 0;
    const hasContent = await page.locator('.entry-content, .nvx-page__content, main').count() > 0;
    const hasFooter = await page.locator('footer, .nvx-footer').count() > 0;
    
    const issues = [];
    if (status !== 200) issues.push(`HTTP ${status}`);
    if (crossHostUrls.length > 0) issues.push(`Navigation left ${expectedHost}: ${crossHostUrls.join(', ')}`);
    if (!hasHeader) issues.push('Missing header');
    if (!hasContent) issues.push('Missing content area');
    if (!hasFooter) issues.push('Missing footer');
    
    results.push({ url, status, issues, pass: issues.length === 0 });
    console.log(`${issues.length === 0 ? 'PASS' : 'FAIL'} ${url} ${status}`);
    if (issues.length > 0) issues.forEach(i => console.error(`  ${i}`));
  } catch (error) {
    results.push({ url, status: 0, issues: [error.message], pass: false });
    console.log(`FAIL ${url} ${error.message}`);
  }
  
  await context.close();
}

await browser.close();

const failed = results.filter(r => !r.pass);
if (failed.length > 0) {
  console.log(`\n${failed.length} pages failed`);
  process.exit(1);
}
console.log('\nAll critical pages passed');
