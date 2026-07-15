import { chromium } from '@playwright/test';
import { execSync } from 'child_process';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const BASE = process.env.STAGING_BASE_URL || 'https://staging2.nuvanx.com';
const USER = process.env.STAGING_BASIC_USER || '';
const PASS = process.env.STAGING_BASIC_PASSWORD || '';
const OUT = path.join(__dirname, 'screenshots');
const VIDEO_TIME = Number(process.env.NVX_HERO_VIDEO_TIME || '2.4');

if (!USER || !PASS) {
	console.error('STAGING_BASIC_USER and STAGING_BASIC_PASSWORD are required');
	process.exit(1);
}

const VIEWPORTS = [
	{ width: 1440, height: 900, tag: '1440' },
	{ width: 1024, height: 768, tag: '1024' },
	{ width: 390, height: 844, tag: '390' },
];

const COOKIE_SELECTORS = [
	'#CybotCookiebotDialogBodyLevelButtonLevelOptinAllowAll',
	'#cookie_action_close_header',
	'button:has-text("Aceptar")',
	'button:has-text("Accept")',
	'.cmplz-btn.cmplz-accept',
];

fs.mkdirSync(OUT, { recursive: true });

const browser = await chromium.launch({
	headless: true,
	args: ['--disable-blink-features=AutomationControlled'],
});
const context = await browser.newContext({
	deviceScaleFactor: 1,
	httpCredentials: { username: USER, password: PASS },
	userAgent:
		'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
});

async function acceptCookies(page) {
	for (const sel of COOKIE_SELECTORS) {
		const btn = page.locator(sel).first();
		if (await btn.isVisible().catch(() => false)) {
			await btn.click({ timeout: 3000 }).catch(() => {});
			await page.waitForTimeout(400);
			break;
		}
	}

	await page.evaluate(() => {
		document
			.querySelectorAll('.cmplz-cookiebanner, #cookie-law-info-bar, #CybotCookiebotDialog')
			.forEach((el) => {
				el.style.setProperty('display', 'none', 'important');
			});
	});
}

async function waitForAssets(page) {
	await page.evaluate(async () => {
		if (document.fonts?.ready) await document.fonts.ready;
	});

	await page.evaluate(async (t) => {
		const video = document.querySelector('#nvx-home-hero-video');
		if (!video) return;
		video.pause();
		await new Promise((resolve) => {
			if (video.readyState >= 2) return resolve();
			video.addEventListener('loadeddata', resolve, { once: true });
			setTimeout(resolve, 4000);
		});
		try {
			video.currentTime = t;
		} catch (_) {}
		await new Promise((r) => setTimeout(r, 250));
		video.pause();
	}, VIDEO_TIME);

	await page.evaluate(async () => {
		const imgs = Array.from(document.querySelectorAll('#nvx-home-main img'));
		await Promise.all(
			imgs.map(
				(img) =>
					new Promise((resolve) => {
						if (img.complete && img.naturalWidth > 0) return resolve();
						img.addEventListener('load', resolve, { once: true });
						img.addEventListener('error', resolve, { once: true });
						setTimeout(resolve, 5000);
					})
			)
		);
	});

	await page.waitForTimeout(1200);
}

async function preparePage(page, vp) {
	await page.setViewportSize({ width: vp.width, height: vp.height });
	const response = await page.goto(`${BASE}/?nvxqa=v4`, {
		waitUntil: 'domcontentloaded',
		timeout: 90000,
	});
	if (!response || response.status() >= 400) {
		throw new Error(`Page load failed @${vp.tag}: status=${response?.status() ?? 'none'}`);
	}
	await page.waitForTimeout(800);
	await acceptCookies(page);
	await waitForAssets(page);
	await page.evaluate(() => window.scrollTo(0, 0));
	await page.waitForTimeout(300);

	const scrollY = await page.evaluate(() => window.scrollY);
	if (scrollY !== 0) {
		throw new Error(`scrollY !== 0 @${vp.tag}: ${scrollY}`);
	}
}

async function captureViewport(page, vp) {
	const viewportPath = path.join(OUT, `chromium_home_${vp.width}x${vp.height}_viewport.png`);
	const heroPath = path.join(OUT, `hero_${vp.tag}.png`);
	const introPath = path.join(OUT, `intro_${vp.tag}.png`);

	await preparePage(page, vp);

	await page.screenshot({ path: viewportPath, fullPage: false });

	const hero = page.locator('.nvx-home-hero-stage').first();
	const intro = page.locator('.nvx-v3-intro').first();

	if ((await hero.count()) === 0) {
		throw new Error(`Missing .nvx-home-hero-stage @${vp.tag}`);
	}
	if ((await intro.count()) === 0) {
		throw new Error(`Missing .nvx-v3-intro @${vp.tag}`);
	}

	await hero.screenshot({ path: heroPath });
	await intro.screenshot({ path: introPath });

	for (const file of [viewportPath, heroPath, introPath]) {
		if (!fs.existsSync(file) || fs.statSync(file).size === 0) {
			throw new Error(`Empty or missing screenshot: ${file}`);
		}
	}

	try {
		const dim = execSync(`file "${viewportPath}"`, { encoding: 'utf8' }).trim();
		console.log('Captured', vp.tag, dim);
	} catch (_) {
		console.log('Captured', vp.tag);
	}
}

async function captureFullpage(vp) {
	const page = await context.newPage();
	const fullpagePath = path.join(OUT, `chromium_home_${vp.width}x${vp.height}_fullpage.png`);

	try {
		await preparePage(page, vp);
		await page.screenshot({ path: fullpagePath, fullPage: true });
		if (!fs.existsSync(fullpagePath) || fs.statSync(fullpagePath).size === 0) {
			throw new Error(`Empty or missing fullpage screenshot: ${fullpagePath}`);
		}
		console.log('Captured fullpage', vp.tag);
	} finally {
		await page.close();
	}
}

for (const vp of VIEWPORTS) {
	const page = await context.newPage();
	try {
		await captureViewport(page, vp);
	} finally {
		await page.close();
	}
}

await captureFullpage({ width: 1440, height: 900, tag: '1440' });
await captureFullpage({ width: 390, height: 844, tag: '390' });

await context.close();
await browser.close();