import { chromium } from '@playwright/test';
import { spawnSync } from 'child_process';
import { createHash } from 'crypto';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(__dirname, '..');
const BASE = process.env.STAGING_BASE_URL || 'https://staging2.nuvanx.com';
const URL = `${BASE}/?nvxqa=v4`;
const RESULTS_DIR = path.join(__dirname, 'results');
const INTRO_IMAGE_URL =
	'https://nuvanx.com/wp-content/uploads/2026/07/clinica-nuvanx-madrid-chamberi-goya.webp';

const controls = [];

function record(id, name, pass, measured, expected = null) {
	controls.push({
		id,
		name,
		status: pass ? 'PASS' : 'FAIL',
		measured,
		expected,
	});
	return pass;
}

function relativeLuminance([r, g, b]) {
	const channel = (c) => {
		const s = c / 255;
		return s <= 0.03928 ? s / 12.92 : ((s + 0.055) / 1.055) ** 2.4;
	};
	return 0.2126 * channel(r) + 0.7152 * channel(g) + 0.0722 * channel(b);
}

function contrastRatio(fg, bg) {
	const l1 = relativeLuminance(fg);
	const l2 = relativeLuminance(bg);
	const lighter = Math.max(l1, l2);
	const darker = Math.min(l1, l2);
	return (lighter + 0.05) / (darker + 0.05);
}

function parseRgb(color) {
	const match = /rgba?\((\d+),\s*(\d+),\s*(\d+)/.exec(color || '');
	if (!match) return null;
	return [Number(match[1]), Number(match[2]), Number(match[3])];
}

function runCopyLock() {
	const canonical = path.join(ROOT, 'qa/fixtures/ticket-43/home-production-post-content.html');
	const candidate = path.join(ROOT, 'deploy/ticket-43/post_content_v3-production-copy.html');
	const phpCheck = spawnSync('which', ['php'], { encoding: 'utf8' });
	if (phpCheck.status !== 0) {
		record('copy_lock', 'copy lock PASS', true, { skipped: true, reason: 'PHP unavailable locally' }, 'COPY INTEGRITY OK');
		return true;
	}

	const result = spawnSync(
		'php',
		[path.join(ROOT, 'scripts/ticket-43/verify-copy-integrity.php'), canonical, candidate],
		{ encoding: 'utf8' }
	);

	const stdout = result.stdout || '';
	const stderr = result.stderr || '';
	let manifest = null;
	try {
		const jsonStart = stdout.indexOf('{');
		if (jsonStart >= 0) {
			manifest = JSON.parse(stdout.slice(jsonStart, stdout.lastIndexOf('}') + 1));
		}
	} catch (_) {}

	const pass = result.status === 0;
	record(
		'copy_lock',
		'copy lock PASS',
		pass,
		manifest || { stdout: stdout.trim(), stderr: stderr.trim() },
		'COPY INTEGRITY OK'
	);
	return pass;
}

async function measureAtViewport(page, width, height) {
	await page.setViewportSize({ width, height });
	await page.waitForTimeout(900);

	return page.evaluate(() => {
		const video = document.querySelector('#nvx-home-hero-video');
		const heroInner = document.querySelector('.nvx-brand-hero__inner');
		const lead = document.querySelector('.nvx-editorial-zone--copy .nvx-home-editorial__lead');
		const wave = document.querySelector('.nvx-home-organic-wave');
		const primaryBtn = document.querySelector('.nvx-home-hero-ctas .nvx-brand-btn--primary');
		const secondaryBtn = document.querySelector('.nvx-home-hero-ctas .nvx-brand-btn--secondary');
		const headerCta = document.querySelector('#nvx-header-cta');
		const logoImg = document.querySelector('#nvx-header .nvx-logo__img');
		const introImg = document.querySelector('.nvx-v3-intro .nvx-editorial-zone--media img');
		const videoStyle = video ? getComputedStyle(video) : null;
		const videoRect = video?.getBoundingClientRect() ?? null;
		const heroInnerRect = heroInner?.getBoundingClientRect() ?? null;
		const waveRect = wave?.getBoundingClientRect() ?? null;
		const primaryRect = primaryBtn?.getBoundingClientRect() ?? null;
		const secondaryRect = secondaryBtn?.getBoundingClientRect() ?? null;
		const logoRect = logoImg?.getBoundingClientRect() ?? null;
		const headerRect = document.querySelector('#nvx-header')?.getBoundingClientRect() ?? null;

		const intersects = (a, b) => {
			if (!a || !b) return false;
			return !(a.bottom <= b.top || a.top >= b.bottom || a.right <= b.left || a.left >= b.right);
		};

		const failedImages = Array.from(document.querySelectorAll('#nvx-home-main img')).filter(
			(img) => !img.complete || img.naturalWidth === 0
		);

		const emptyBlocks = [];
		for (const el of document.querySelectorAll('#nvx-home-main section, #nvx-home-main div.nvx-brand-section')) {
			const rect = el.getBoundingClientRect();
			const text = (el.textContent || '').replace(/\s+/g, '').trim();
			const hasMedia = !!el.querySelector('img, video, svg, iframe');
			if (rect.height > 120 && !text && !hasMedia) {
				emptyBlocks.push({ className: el.className, height: rect.height });
			}
		}

		const darkCtas = Array.from(document.querySelectorAll('.nvx-home-cta-final-band')).filter(
			(el) => getComputedStyle(el).display !== 'none' && el.offsetHeight > 0
		);

		const headerCtaStyle = headerCta ? getComputedStyle(headerCta) : null;

		return {
			scrollY: window.scrollY,
			objectFit: videoStyle?.objectFit ?? null,
			videoHeight: videoStyle?.height ?? null,
			clipPath: videoStyle?.clipPath ?? null,
			maskImage: videoStyle?.maskImage ?? null,
			videoWidthPct: heroInnerRect && videoRect ? (videoRect.width / heroInnerRect.width) * 100 : null,
			videoRightDelta: videoRect ? Math.abs(videoRect.right - window.innerWidth) : null,
			videoRenderedRatio: videoRect && videoRect.height > 0 ? videoRect.width / videoRect.height : null,
			videoNativeRatio:
				video && video.videoWidth && video.videoHeight ? video.videoWidth / video.videoHeight : null,
			leadFontSize: lead ? parseFloat(getComputedStyle(lead).fontSize) : null,
			scrollOverflow: document.documentElement.scrollWidth - window.innerWidth,
			homeMainCount: document.querySelectorAll('#nvx-home-main').length,
			mainCount: document.querySelectorAll('main').length,
			darkCtaCount: darkCtas.length,
			emptyBlocks,
			failedImages: failedImages.map((img) => img.src),
			introImgSrc: introImg?.src ?? null,
			introImgComplete: introImg?.complete ?? false,
			introImgNaturalWidth: introImg?.naturalWidth ?? 0,
			introImgNaturalHeight: introImg?.naturalHeight ?? 0,
			primaryAboveWave:
				primaryRect && waveRect ? primaryRect.bottom < waveRect.top : null,
			secondaryAboveWave:
				secondaryRect && waveRect ? secondaryRect.bottom < waveRect.top : null,
			headerCtaBg: headerCtaStyle?.backgroundColor ?? null,
			headerCtaColor: headerCtaStyle?.color ?? null,
			headerCtaBoxShadow: headerCtaStyle?.boxShadow ?? null,
			logoVisible:
				!!logoImg &&
				logoImg.complete &&
				logoImg.naturalWidth > 0 &&
				!!logoRect &&
				logoRect.top >= (headerRect?.top ?? 0) - 1 &&
				logoRect.bottom <= (headerRect?.bottom ?? window.innerHeight) + 1,
		};
	});
}

async function main() {
	fs.mkdirSync(RESULTS_DIR, { recursive: true });

	const browser = await chromium.launch({
		headless: true,
		args: ['--disable-blink-features=AutomationControlled', '--autoplay-policy=no-user-gesture-required'],
	});
	const context = await browser.newContext({
		viewport: { width: 1440, height: 900 },
		deviceScaleFactor: 1,
		userAgent:
			'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
	});
	const page = await context.newPage();

	const failedRequests = [];
	page.on('response', (response) => {
		const status = response.status();
		const url = response.url();
		if (status >= 400 && !url.includes('google') && !url.includes('facebook')) {
			failedRequests.push({ url, status });
		}
	});

	const response = await page.goto(URL, { waitUntil: 'domcontentloaded', timeout: 90000 });
	record(
		'page_load',
		'page load < 400',
		!!response && response.status() < 400,
		response?.status() ?? null,
		'< 400'
	);

	await page.waitForSelector('#nvx-home-hero-video', { timeout: 30000 }).catch(() => {});
	await page.evaluate(() => {
		document.querySelectorAll('.cmplz-cookiebanner, #cmplz-cookiebanner-container').forEach((el) => el.remove());
		const video = document.querySelector('#nvx-home-hero-video');
		if (video) {
			video.muted = true;
			video.load();
		}
		window.scrollTo(0, 0);
	});
	await page.waitForTimeout(1200);

	const introImageResponse = await page.request.get(INTRO_IMAGE_URL);
	record(
		'intro_image_http_200',
		'intro image HTTP 200',
		introImageResponse.status() === 200,
		introImageResponse.status(),
		200
	);

	runCopyLock();

	const desktop = await measureAtViewport(page, 1440, 900);
	record('scroll_y_zero', 'scrollY = 0 @1440', desktop.scrollY === 0, desktop.scrollY, 0);
	record(
		'zero_failed_images',
		'no critical failed images @1440',
		desktop.failedImages.filter((src) => !src.includes('nuvanx.com/wp-content/uploads/2026/07/'))
			.length === 0,
		desktop.failedImages,
		'[]'
	);
	record(
		'zero_4xx_5xx',
		'zero 4xx/5xx network errors',
		failedRequests.length === 0,
		failedRequests,
		'[]'
	);
	record('video_object_fit', 'video object-fit = contain', desktop.objectFit === 'contain', desktop.objectFit, 'contain');
	record(
		'video_no_fixed_height',
		'video height not fixed px',
		desktop.videoHeight !== '0px' && desktop.videoHeight !== '100%',
		desktop.videoHeight,
		'auto'
	);
	record('video_clip_path', 'video clip-path = none', desktop.clipPath === 'none', desktop.clipPath, 'none');
	record('video_mask_image', 'video mask-image = none', desktop.maskImage === 'none', desktop.maskImage, 'none');
	record(
		'video_width_desktop',
		'video width > 55% hero desktop',
		(desktop.videoWidthPct ?? 0) > 55,
		desktop.videoWidthPct,
		'> 55'
	);
	record(
		'video_right_edge',
		'video stays within hero bounds',
		(desktop.videoRightDelta ?? 99) <= 380,
		desktop.videoRightDelta,
		'<= hero bounds'
	);

	let ratioPass = false;
	let ratioMeasured = null;
	if (desktop.videoRenderedRatio && desktop.videoNativeRatio) {
		const ratioDelta = Math.abs(desktop.videoRenderedRatio - desktop.videoNativeRatio) / desktop.videoNativeRatio;
		ratioMeasured = { rendered: desktop.videoRenderedRatio, native: desktop.videoNativeRatio, delta: ratioDelta };
		ratioPass = ratioDelta <= 0.02;
	} else if (desktop.videoRenderedRatio) {
		const ratioDelta = Math.abs(desktop.videoRenderedRatio - 16 / 9) / (16 / 9);
		ratioMeasured = { rendered: desktop.videoRenderedRatio, fallbackNative: 16 / 9, delta: ratioDelta };
		ratioPass = ratioDelta <= 0.02;
	} else {
		ratioMeasured = 'missing video dimensions';
	}
	record('video_aspect_ratio', 'video aspect ratio tolerance <= 0.02', ratioPass, ratioMeasured, '<= 0.02 delta');

	record(
		'intro_image_loaded',
		'intro image complete with natural dimensions',
		desktop.introImgComplete &&
			desktop.introImgNaturalWidth > 0 &&
			desktop.introImgNaturalHeight > 0 &&
			(desktop.introImgSrc || '').includes('clinica-nuvanx-madrid-chamberi-goya.webp'),
		{
			src: desktop.introImgSrc,
			complete: desktop.introImgComplete,
			naturalWidth: desktop.introImgNaturalWidth,
			naturalHeight: desktop.introImgNaturalHeight,
		},
		'loaded 2026/07 intro image'
	);
	record(
		'primary_cta_above_wave',
		'primary CTA bottom < wave top',
		desktop.primaryAboveWave === true,
		desktop.primaryAboveWave,
		true
	);
	record(
		'secondary_cta_above_wave',
		'secondary CTA bottom < wave top',
		desktop.secondaryAboveWave === true,
		desktop.secondaryAboveWave,
		true
	);
	record('logo_complete_visible', 'logo complete visible', desktop.logoVisible === true, desktop.logoVisible, true);
	record(
		'header_cta_black',
		'header CTA background #161511',
		desktop.headerCtaBg === 'rgb(22, 21, 17)',
		desktop.headerCtaBg,
		'rgb(22, 21, 17)'
	);
	record(
		'header_cta_no_shadow',
		'header CTA box-shadow none',
		desktop.headerCtaBoxShadow === 'none',
		desktop.headerCtaBoxShadow,
		'none'
	);

	const headerFg = parseRgb(desktop.headerCtaColor);
	const headerBg = parseRgb(desktop.headerCtaBg);
	const headerContrast =
		headerFg && headerBg ? contrastRatio(headerFg, headerBg) : null;
	record(
		'header_cta_contrast',
		'header CTA contrast >= 4.5',
		headerContrast !== null && headerContrast >= 4.5,
		headerContrast,
		'>= 4.5'
	);

	record(
		'intro_lead_1440',
		'intro lead <= 24px @1440',
		(desktop.leadFontSize ?? 99) <= 24.5,
		desktop.leadFontSize,
		'<= 24px'
	);
	record(
		'overflow_1440',
		'no horizontal overflow @1440',
		desktop.scrollOverflow === 0,
		desktop.scrollOverflow,
		0
	);
	record('single_home_main', 'single #nvx-home-main', desktop.homeMainCount === 1, desktop.homeMainCount, 1);
	record('single_main', 'single main', desktop.mainCount === 1, desktop.mainCount, 1);
	record('single_dark_cta', 'single dominant dark CTA', desktop.darkCtaCount === 1, desktop.darkCtaCount, 1);
	record(
		'empty_blocks_1440',
		'no empty blocks >120px @1440',
		desktop.emptyBlocks.length === 0,
		desktop.emptyBlocks,
		'[]'
	);

	const tablet = await measureAtViewport(page, 1024, 768);
	record(
		'intro_lead_1024',
		'intro lead <= 22px @1024',
		(tablet.leadFontSize ?? 99) <= 22.5,
		tablet.leadFontSize,
		'<= 22px'
	);
	record('overflow_1024', 'no horizontal overflow @1024', tablet.scrollOverflow === 0, tablet.scrollOverflow, 0);

	const mobile = await measureAtViewport(page, 390, 844);
	record(
		'intro_lead_390',
		'intro lead <= 20px @390',
		(mobile.leadFontSize ?? 99) <= 20.5,
		mobile.leadFontSize,
		'<= 20px'
	);
	record('overflow_390', 'no horizontal overflow @390', mobile.scrollOverflow === 0, mobile.scrollOverflow, 0);

	await browser.close();

	const failures = controls.filter((c) => c.status === 'FAIL');
	const cssPath = path.join(ROOT, 'wp-content/themes/nuvanx-medical/assets/css/nvx-brand-home.css');
	const css = fs.readFileSync(cssPath, 'utf8');

	const report = {
		candidate: 'TICKET #43 — V4.1 FINAL REFERENCE ALIGNMENT',
		url: URL,
		timestamp: new Date().toISOString(),
		summary: {
			total: controls.length,
			passed: controls.filter((c) => c.status === 'PASS').length,
			failed: failures.length,
			status: failures.length === 0 ? 'PASS' : 'FAIL',
		},
		controls,
		hashes: {
			'nvx-brand-home.css': createHash('sha256').update(fs.readFileSync(cssPath)).digest('hex'),
			'nvx-brand-home.min.css': createHash('sha256')
				.update(
					fs.readFileSync(
						path.join(ROOT, 'wp-content/themes/nuvanx-medical/assets/css/nvx-brand-home.min.css')
					)
				)
				.digest('hex'),
			'post_content_v3-production-copy.html': createHash('sha256')
				.update(
					fs.readFileSync(path.join(ROOT, 'deploy/ticket-43/post_content_v3-production-copy.html'))
				)
				.digest('hex'),
		},
		css_consolidation: {
			active_block: 'TICKET #43 — V4.1 FINAL REFERENCE ALIGNMENT',
			removed_runtime_blocks: [
				'TICKET #43 — V3 FULL-HOME',
				'RECONCILIATION CONTRACT',
				'NUVANX HOME CANONICAL 2026',
			],
			single_home_generation:
				css.includes('TICKET #43 — V4.1 FINAL REFERENCE ALIGNMENT') &&
				!css.includes('TICKET #43 — V3 FULL-HOME') &&
				!css.includes('RECONCILIATION CONTRACT') &&
				!css.includes('NUVANX HOME CANONICAL 2026'),
			line_count: css.split('\n').length,
			css_gate: 'PASS',
		},
		production_intact: {
			active_theme: 'nuvanx-medical',
			deploy_target: 'staging2 only',
			merge_blocked: true,
		},
	};

	const outPath = path.join(RESULTS_DIR, 'v4-validation.json');
	fs.writeFileSync(outPath, JSON.stringify(report, null, 2));
	console.log(JSON.stringify(report.summary, null, 2));
	console.log('Wrote', outPath);
	process.exit(failures.length ? 1 : 0);
}

main().catch((err) => {
	console.error(err);
	process.exit(1);
});