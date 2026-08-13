import { chromium } from 'playwright';
import fs from 'node:fs/promises';

const base = (process.env.BASE_URL || 'https://staging2.nuvanx.com').replace(/\/$/, '');
const prod = 'https://nuvanx.com';
const sha = (process.env.EXPECTED_SHA || '').trim();
if (!/^[0-9a-f]{40}$/.test(sha)) throw new Error('EXPECTED_SHA must be a full SHA');

const catalog = JSON.parse(await fs.readFile('wp-content/themes/nuvanx-medical/inc/data/seo-blog-post-metadata.json', 'utf8'));
const norm = (value) => `${String(value).split(/[?#]/, 1)[0].replace(/\/$/, '')}/`;
const browser = await chromium.launch({ headless: true });
const context = await browser.newContext({ ignoreHTTPSErrors: true });
const rest = await context.newPage();
await rest.goto(`${base}/wp-json/wp/v2/posts?per_page=100&status=publish&_fields=id,slug`, { waitUntil: 'domcontentloaded', timeout: 40000 });
const posts = JSON.parse(await rest.locator('body').innerText());
await rest.close();

const governed = posts.filter((post) => Object.hasOwn(catalog, post.slug));
if (governed.length === 0) throw new Error('No published governed journal posts found on Staging2');

let failures = 0;
for (const post of governed) {
  const page = await context.newPage();
  const expected = `${base}/${post.slug}/`;
  const response = await page.goto(expected, { waitUntil: 'domcontentloaded', timeout: 40000 });
  const title = (await page.title()).trim();
  const canonical = await page.locator('link[rel="canonical"]').evaluateAll((nodes) => nodes.map((node) => node.href));
  const og = await page.locator('meta[property="og:url"]').evaluateAll((nodes) => nodes.map((node) => node.content));
  const deploy = (await page.locator('meta[name="nvx-deploy-sha"]').getAttribute('content').catch(() => '')) || '';
  const robots = ((await page.locator('meta[name="robots"]').getAttribute('content').catch(() => '')) || '').toLowerCase();
  const expectedTitle = String(catalog[post.slug].title || '').trim();
  const issues = [];
  if (!response || response.status() !== 200) issues.push(`http=${response?.status() || 0}`);
  if (title !== expectedTitle) issues.push(`title=${title}`);
  if (canonical.length !== 1 || norm(canonical[0] || '') !== norm(expected)) issues.push(`canonical=${canonical.join(',')}`);
  if (og.length !== 1 || norm(og[0] || '') !== norm(`${prod}/${post.slug}/`)) issues.push(`og=${og.join(',')}`);
  if (deploy !== sha) issues.push(`sha=${deploy || 'missing'}`);
  if (!robots.includes('noindex')) issues.push('noindex-missing');
  if (issues.length) failures += 1;
  console.log(`${issues.length ? 'FAIL' : 'PASS'} GOVERNED_BLOG_HEAD slug=${post.slug}${issues.length ? ` ${issues.join(' | ')}` : ''}`);
  await page.close();
}

await browser.close();
console.log(`GOVERNED_BLOG_HEAD_TOTAL=${governed.length}`);
console.log(`GOVERNED_BLOG_HEAD_FAIL=${failures}`);
if (failures) throw new Error(`Governed blog head contract failed for ${failures} post(s)`);
console.log('GOVERNED_BLOG_HEAD_CONTRACT=PASS');
