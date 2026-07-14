const { test, expect } = require('@playwright/test');
const fs = require('fs');
const path = require('path');

if (!process.env.STAGING_BASE_URL || !process.env.STAGING_BASIC_USER || !process.env.STAGING_BASIC_PASSWORD) {
	console.error('CRITICAL ERROR: Missing STAGING environment variables.');
	process.exit(1);
}

const BASE_URL = process.env.STAGING_BASE_URL.replace(/\/$/, '');

const VIEWPORTS = [
	{ width: 1440, height: 900, name: '1440x900' },
	{ width: 1024, height: 768, name: '1024x768' },
	{ width: 390, height: 844, name: '390x844' },
];

test.describe('Ticket 43 V3 Home Visual Validation', () => {
	const metrics = {};

	test.afterAll(async () => {
		const resultsDir = path.join(__dirname, 'results');
		fs.mkdirSync(resultsDir, { recursive: true });
		fs.writeFileSync(
			path.join(resultsDir, 'chromium-v3-home-metrics.json'),
			JSON.stringify(metrics, null, 2)
		);
	});

	for (const vp of VIEWPORTS) {
		test(`Home V3 - ${vp.name}`, async ({ page }) => {
			const failedRequests = [];
			const httpErrors = [];

			page.on('requestfailed', (request) => failedRequests.push(request.url()));
			page.on('response', (response) => {
				if (response.status() >= 400) {
					httpErrors.push(`${response.status()} ${response.url()}`);
				}
			});

			const response = await page.goto('/?nvxqa=ticket43-v3', {
				waitUntil: 'networkidle',
			});

			expect(response).not.toBeNull();
			expect(response.status()).toBe(200);
			expect(await page.locator('main').count()).toBe(1);
			expect(await page.locator('#nvx-home-main.nvx-editorial-home-v3').count()).toBe(1);

			await page.waitForFunction(() => document.fonts.status === 'loaded');

			const h1Text = await page.locator('#nvx-home-h1').innerText();
			expect(h1Text).toContain('EXPERIENCIA NUVANX:');
			expect(h1Text).toContain('Excelencia en Medicina Estética Láser en Madrid');
			expect(h1Text).toContain('Resultados naturales, sin cirugía, guiados por criterio médico.');

			await expect(page.getByRole('link', { name: 'Solicitar valoración médica personalizada' }).first()).toBeVisible();
			await expect(page.getByRole('link', { name: 'Explorar tratamientos exclusivos' })).toBeVisible();
			await expect(page.locator('#nvx-home-hero-video')).toBeVisible();
			await expect(page.locator('.nvx-home-organic-wave')).toBeVisible();

			const pageText = await page.locator('body').innerText();
			expect(pageText).not.toMatch(/sin ruido/i);
			expect(pageText).not.toMatch(/Nuestro manifiesto/i);

			const screenshotDir = path.join(__dirname, 'screenshots');
			fs.mkdirSync(screenshotDir, { recursive: true });
			await page.screenshot({
				path: path.join(screenshotDir, `chromium_home_${vp.name}.png`),
				fullPage: true,
			});

			const layout = await page.evaluate(() => {
				const heroContent = document.querySelector('.nvx-home-hero-content');
				const videoFeature = document.querySelector('.nvx-home-video-feature');
				const h1 = document.querySelector('#nvx-home-h1');
				const box = (el) => (el ? el.getBoundingClientRect() : null);
				const style = (el, prop) => (el ? window.getComputedStyle(el)[prop] : null);

				return {
					scrollWidth: document.documentElement.scrollWidth,
					viewportWidth: window.innerWidth,
					heroContentBox: box(heroContent),
					videoBox: box(videoFeature),
					videoPosition: style(videoFeature, 'position'),
					h1FontSize: style(h1, 'font-size'),
				};
			});

			metrics[vp.name] = layout;

			expect(failedRequests.length, failedRequests.join(', ')).toBe(0);
			expect(httpErrors.length, httpErrors.join(', ')).toBe(0);
			expect(layout.scrollWidth).toBeLessThanOrEqual(layout.viewportWidth + 1);
			expect(layout.videoPosition).not.toBe('absolute');

			if (vp.width <= 680 && layout.h1FontSize) {
				expect(parseFloat(layout.h1FontSize)).toBeLessThanOrEqual(44.5);
			}
		});
	}
});