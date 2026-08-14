#!/usr/bin/env node
/**
 * Test script for WCAG 2.1 / 2.2 AA Contrast Compliance (Success Criterion 1.4.3)
 * on the Home Hero section with dynamic video background.
 *
 * Requirements:
 * - H1 Title (Large text >= 24px): Minimum contrast ratio >= 3.0:1.
 * - Lead text (Normal text < 24px): Minimum contrast ratio >= 4.5:1.
 * - Button text (Normal text): Minimum contrast ratio >= 4.5:1.
 * - Dynamic video worst-case: Contrast must be maintained across all gradient bands (0% to 100%)
 *   even against pure white video frames (L_v = 1.0, RGB = [255, 255, 255]).
 */

import assert from 'node:assert/strict';
import fs from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const tokensPath = path.join(__dirname, '../../wp-content/themes/nuvanx-medical/assets/css/nvx-tokens.css');
const tokensContent = await fs.readFile(tokensPath, 'utf8');

// Parse --nvx-media-overlay stops
const mediaOverlayMatch = tokensContent.match(/--nvx-media-overlay:\s*linear-gradient\(([\s\S]*?)\);/);
if (!mediaOverlayMatch) {
  throw new Error('Could not find --nvx-media-overlay in nvx-tokens.css');
}

const overlayGradient = mediaOverlayMatch[1];
const stopRegex = /rgba\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*,\s*([\d.]+)\s*\)\s*([\d.]+)%/g;
const stops = [];
let match;
while ((match = stopRegex.exec(overlayGradient)) !== null) {
  stops.push({
    r: Number.parseInt(match[1], 10),
    g: Number.parseInt(match[2], 10),
    b: Number.parseInt(match[3], 10),
    alpha: Number.parseFloat(match[4]),
    pos: Number.parseFloat(match[5]) / 100,
  });
}

assert.ok(stops.length >= 2, 'Overlay gradient must have at least 2 color stops');

function getOverlayAlphaAt(pos) {
  if (pos <= stops[0].pos) return stops[0].alpha;
  if (pos >= stops[stops.length - 1].pos) return stops[stops.length - 1].alpha;
  for (let i = 0; i < stops.length - 1; i++) {
    const s1 = stops[i];
    const s2 = stops[i + 1];
    if (pos >= s1.pos && pos <= s2.pos) {
      const t = (pos - s1.pos) / (s2.pos - s1.pos);
      return s1.alpha + t * (s2.alpha - s1.alpha);
    }
  }
  return stops[0].alpha;
}

function sRGBtoLinear(c) {
  const v = c / 255;
  return v <= 0.04045 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4);
}

function relativeLuminance(r, g, b) {
  return 0.2126 * sRGBtoLinear(r) + 0.7152 * sRGBtoLinear(g) + 0.0722 * sRGBtoLinear(b);
}

function contrastRatio(l1, l2) {
  const lighter = Math.max(l1, l2);
  const darker = Math.min(l1, l2);
  return (lighter + 0.05) / (darker + 0.05);
}

// Compute worst-case background over pure white video (255, 255, 255)
function computeBlendedWorstCase(alpha, overlayR = 26, overlayG = 26, overlayB = 26) {
  const bgR = Math.round(overlayR * alpha + 255 * (1 - alpha));
  const bgG = Math.round(overlayG * alpha + 255 * (1 - alpha));
  const bgB = Math.round(overlayB * alpha + 255 * (1 - alpha));
  return { bgR, bgG, bgB, lum: relativeLuminance(bgR, bgG, bgB) };
}

const whiteTextLum = relativeLuminance(255, 255, 255); // 1.0

console.log('Testing WCAG 2.1 / 2.2 AA Contrast Compliance on Hero Video Overlay...\n');

// 1. Test across the gradient positions from top (0.0) to bottom (1.0)
const samplePositions = [0.0, 0.15, 0.28, 0.40, 0.58, 0.75, 1.0];
let minContrast = Infinity;

for (const pos of samplePositions) {
  const alpha = getOverlayAlphaAt(pos);
  const blended = computeBlendedWorstCase(alpha);
  const cr = contrastRatio(whiteTextLum, blended.lum);
  minContrast = Math.min(minContrast, cr);

  console.log(
    `  Stop at ${(pos * 100).toFixed(0).padStart(3)}%: alpha=${alpha.toFixed(2)}, blended RGB=(${blended.bgR}, ${blended.bgG}, ${blended.bgB}), Worst-Case CR=${cr.toFixed(2)}:1`
  );

  // Position 0.0 to 0.40 (where H1 title sits): must be >= 3.0:1 (large text)
  if (pos <= 0.40) {
    assert.ok(cr >= 3.0, `Contrast ratio at position ${pos} (${cr.toFixed(2)}:1) must meet WCAG Large Text >= 3.0:1`);
  }

  // Position 0.28 to 1.0 (where Lead and body text sits): must be >= 4.5:1 (normal text)
  if (pos >= 0.28) {
    assert.ok(cr >= 4.5, `Contrast ratio at position ${pos} (${cr.toFixed(2)}:1) must meet WCAG Normal Text >= 4.5:1`);
  }
}

// 2. Test top-of-hero minimum contrast (even at absolute position 0.0)
const topAlpha = getOverlayAlphaAt(0.0);
const topBlended = computeBlendedWorstCase(topAlpha);
const topCR = contrastRatio(whiteTextLum, topBlended.lum);
assert.ok(
  topCR >= 4.5,
  `Top overlay contrast ratio (${topCR.toFixed(2)}:1) must guarantee >= 4.5:1 for complete AA safety`
);

// 3. Test primary button text contrast: #1A1A1A text on #F7F7F5 button
const btnBgLum = relativeLuminance(247, 247, 245); // #F7F7F5
const btnTextLum = relativeLuminance(26, 26, 26);   // #1A1A1A
const btnCR = contrastRatio(btnBgLum, btnTextLum);
console.log(`\n  Primary CTA Button Contrast: ${btnCR.toFixed(2)}:1 (Text #1A1A1A on #F7F7F5)`);
assert.ok(btnCR >= 4.5, `CTA button contrast (${btnCR.toFixed(2)}:1) must be >= 4.5:1`);

console.log('\nHERO_WCAG_CONTRAST_TEST=PASS');
