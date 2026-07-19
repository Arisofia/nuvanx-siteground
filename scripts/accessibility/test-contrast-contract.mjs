#!/usr/bin/env node

import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const cssRoot = path.join(root, 'wp-content/themes/nuvanx-medical/assets/css');
const tokens = fs.readFileSync(path.join(cssRoot, 'nvx-tokens.css'), 'utf8');
const components = fs.readFileSync(path.join(cssRoot, 'nvx-components.css'), 'utf8');
const home = fs.readFileSync(path.join(cssRoot, 'nvx-brand-home.css'), 'utf8');
const workflow = fs.readFileSync(path.join(root, '.github/workflows/accessibility-contrast-gate.yml'), 'utf8');

function token(name) {
  const match = tokens.match(new RegExp(`--${name}:\\s*([^;]+);`));
  if (!match) throw new Error(`missing token --${name}`);
  return match[1].trim();
}

function rgb(value) {
  const hex = value.match(/^#([0-9a-f]{6})$/i);
  if (hex) return [0, 2, 4].map((offset) => parseInt(hex[1].slice(offset, offset + 2), 16));
  const rgba = value.match(/^rgba\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*,\s*(0(?:\.\d+)?|1(?:\.0+)?)\s*\)$/i);
  if (rgba) return [Number(rgba[1]), Number(rgba[2]), Number(rgba[3]), Number(rgba[4])];
  throw new Error(`unsupported color value: ${value}`);
}

function luminance(color) {
  const channels = color.slice(0, 3).map((value) => {
    const normalized = value / 255;
    return normalized <= 0.04045 ? normalized / 12.92 : ((normalized + 0.055) / 1.055) ** 2.4;
  });
  return 0.2126 * channels[0] + 0.7152 * channels[1] + 0.0722 * channels[2];
}

function ratio(foreground, background) {
  const high = Math.max(luminance(foreground), luminance(background));
  const low = Math.min(luminance(foreground), luminance(background));
  return (high + 0.05) / (low + 0.05);
}

function composite(foreground, background) {
  const alpha = foreground[3];
  return foreground.slice(0, 3).map((value, index) => value * alpha + background[index] * (1 - alpha));
}

function requireRatio(label, foreground, background, minimum = 4.5) {
  const value = ratio(foreground, background);
  if (value < minimum) throw new Error(`${label}: ${value.toFixed(3)} < ${minimum}`);
  console.log(`${label}: ${value.toFixed(3)}`);
}

const accent = rgb(token('nvx-accent-muted'));
const surface = rgb(token('nvx-surface-base'));
const light = rgb(token('nvx-light'));
const ink = rgb(token('nvx-ink'));
const charcoal = rgb(token('nvx-charcoal'));
const textBody = rgb(token('nvx-text-body'));
const onDark = rgb(token('nvx-text-on-dark-72'));

requireRatio('accent on surface base', accent, surface);
requireRatio('accent on light', accent, light);
requireRatio('Complianz light text on body background', light, textBody);
requireRatio('72% light text on ink', composite(onDark, ink), ink);
requireRatio('72% light text on charcoal', composite(onDark, charcoal), charcoal);

if (tokens.includes('#77736d')) throw new Error('legacy low-contrast accent remains');
for (const fragment of [
  '.nvx-home-action-banner',
  '.nvx-home-cta-final-band',
  '.nvx-cta-banner',
  '.nvx-catalog-close',
  '.nvx-endolift-action',
  '.nvx-laser-action',
  '.nvx-aes-action',
  'color: var(--nvx-text-on-dark-72);',
  'html body button.cmplz-blocked-content-notice',
  'background-color: var(--nvx-text-body);',
]) {
  if (!components.includes(fragment)) throw new Error(`missing contrast context: ${fragment}`);
}
if (!home.includes('.nvx-home-action-banner__kicker') || !home.includes('color:var(--nvx-text-on-dark-72);')) {
  throw new Error('home dark action kicker is not explicitly protected');
}
for (const fragment of [
  'node scripts/accessibility/test-contrast-contract.mjs',
  'nvx-tokens.css',
  'nvx-components.css',
  'nvx-brand-home.css',
]) {
  if (!workflow.includes(fragment)) throw new Error(`accessibility workflow is missing: ${fragment}`);
}

console.log('PASS: WCAG contrast token and context contract');
