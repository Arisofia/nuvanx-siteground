import { VIEWPORTS } from './published-pages-contract.mjs';

export const BLOCK_C_BROWSER_CONFIG = Object.freeze({
  maxAttempts: 5,
  navigationTimeoutMs: 40000,
  navigationErrorBackoffBaseMs: 2500,
  transientBackoffBaseMs: 3000,
  visualRetryBackoffBaseMs: 1800,
  consentVisibleTimeoutMs: 350,
  consentClickTimeoutMs: 1500,
  consentPostClickMs: 150,
  fontSettleMs: 400,
  networkIdleTimeoutMs: 3000,
  lazyScrollMinStepPx: 360,
  lazyScrollViewportFactor: 0.8,
  lazyScrollStepDelayMs: 45,
  lazyBottomSettleMs: 120,
  lazyPostSettleMs: 120,
  menuClickTimeoutMs: 2500,
  menuSettleMs: 220,
  screenshotQuality: 72,
  layoutTolerancePx: 2,
  minimumMainTextChars: 80,
  minimumSemanticSections: 2,
  minimumSectionFallbackTextChars: 400,
  minimumVideoDimensionPx: 100,
  diagnosticLimit: 12,
  ctaPreviewLimit: 10,
  imagePreviewLimit: 12,
  errorPreviewLimit: 8,
});

export const BLOCK_C_BROWSER_UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36';

export const BLOCK_C_VIEWPORTS = Object.freeze(
  VIEWPORTS.map((viewport) => Object.freeze({ ...viewport }))
);

export const BLOCK_C_RECOVERY_TARGETS = Object.freeze({
  homeMobile: Object.freeze({
    route: '/',
    viewportKey: 'mobile-390x844',
    screenshotStem: 'home--mobile-390x844--public-recovery',
  }),
});

export function getCanonicalViewport(viewportKey) {
  const viewport = BLOCK_C_VIEWPORTS.find((candidate) => candidate.key === viewportKey);
  if (!viewport) {
    throw new Error(`Unknown canonical Block C viewport: ${viewportKey}`);
  }
  return viewport;
}
