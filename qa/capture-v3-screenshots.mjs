import { chromium } from '@playwright/test';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const BASE = process.env.STAGING_BASE_URL || 'https://staging2.nuvanx.com';
const USER = process.env.STAGING_BASIC_USER || 'nuvanx-qa';
const PASS = process.env.STAGING_BASIC_PASSWORD || '';

if (!PASS) {
	console.error('STAGING_BASIC_PASSWORD required');
	process.exit(1);
}

const VIEWPORTS = [
	{ width: 1440, height: 900, name: '1440x900' },
	{ width: 1024, height: 768, name: '1024x768' },
	{ width: 390, height: 844, name: '390x844' },
];

const outDir = path.join(__dirname, 'screenshots');
fs.mkdirSync(outDir, { recursive: true });

const browser = await chromium.launch();
const context = await browser.newContext({
	httpCredentials: { username: USER, password: PASS },
});
const page = await context.newPage();

for (const vp of VIEWPORTS) {
	await page.setViewportSize({ width: vp.width, height: vp.height });
	await page.goto(`${BASE}/?nvxqa=ticket43-v3-full`, { waitUntil: 'networkidle' });
	await page.waitForTimeout(1500);
	const file = path.join(outDir, `chromium_home_${vp.name}.png`);
	await page.screenshot({ path: file, fullPage: true });
	console.log('Wrote', file);
}

await browser.close();