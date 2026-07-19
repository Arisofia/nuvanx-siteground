#!/usr/bin/env node

import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const verifier = fs.readFileSync(path.join(root, 'scripts/runtime/verify-third-party-health.mjs'), 'utf8');
const mediaVerifier = fs.readFileSync(path.join(root, 'scripts/runtime/verify-home-video-performance.mjs'), 'utf8');
const known = JSON.parse(fs.readFileSync(path.join(root, 'qa/runtime/known-third-party-findings.json'), 'utf8'));
const workflow = fs.readFileSync(path.join(root, '.github/workflows/third-party-runtime-gate.yml'), 'utf8');
const requireText = (source, text, message) => { if (!source.includes(text)) throw new Error(message); };

for (const route of [
  '/contacto/',
  '/madrid/valoracion/',
  '/endolift-facial-papada-mandibula/',
  '/endolaser-corporal-grasa-localizada/',
  '/laser-co2-fraccionado-madrid-textura-cicatrices-poro/',
  '/exion-btl/',
]) {
  requireText(verifier, route, `missing critical runtime route: ${route}`);
}
for (const fragment of [
  'page.on(\'pageerror\'',
  'page.on(\'console\'',
  'page.on(\'response\'',
  'resetCapture();',
  'firstPartyFailures',
  'multiple_meta_pixel_owners',
  'meta_request_before_marketing_consent',
  'siteground-optimizer-assets/facebook-signal',
  'googletagmanager',
  'known runtime exception expired',
  'EXPECTED_DEPLOY_SHA',
]) {
  requireText(verifier, fragment, `runtime verifier is missing: ${fragment}`);
}
for (const fragment of [
  "page.locator('#nvx-home-hero-video')",
  'idCount === 1',
  "canonicalShape.tagName === 'VIDEO'",
  "element.classList.contains('nvx-home-hero-video')",
  "attributes.preload === 'metadata'",
  "attributes.fetchpriority.toLowerCase() !== 'high'",
  "source[type=\"video/mp4\"]",
  'attributes.autoplay && attributes.muted && attributes.loop && attributes.playsInline',
  'home video poster is missing',
  'home poster must be first-party',
  'home MP4 must be first-party',
  'home video did not decode metadata',
  'posterContentType',
  'mp4ContentType',
  '/^image\\//i.test(posterContentType)',
  '/^video\\/mp4(?:;|$)/i.test(mp4ContentType)',
  'report.fatal.push(failure.message)',
  'fs.writeFileSync(outputPath',
  'EXPECTED_DEPLOY_SHA',
]) {
  requireText(mediaVerifier, fragment, `home media verifier is missing: ${fragment}`);
}
if (known.issue !== 166) throw new Error('known runtime debt must reference issue #166');
if (known.allowedPageError !== 'FacebookSignal is not defined') throw new Error('known page error must remain exact');
if (known.maxPerRoute !== 1) throw new Error('known page error ceiling must remain exactly one per route');
if (!Array.isArray(known.allowedSignals) || known.allowedSignals.length !== 2) throw new Error('known signal baseline changed unexpectedly');
const expiry = new Date(`${known.expiresOn}T23:59:59Z`).getTime();
const created = new Date('2026-07-19T00:00:00Z').getTime();
if (!Number.isFinite(expiry) || expiry <= created || expiry - created > 14 * 86400000) {
  throw new Error('known runtime exception must expire within 14 days');
}
for (const fragment of [
  'node --check scripts/runtime/verify-third-party-health.mjs',
  'node --check scripts/runtime/verify-home-video-performance.mjs',
  'node scripts/runtime/test-third-party-health-contract.mjs',
  'node scripts/runtime/verify-home-video-performance.mjs',
  'https://nuvanx.com',
  'https://staging2.nuvanx.com',
  'runtime-health-production.json',
  'runtime-health-staging.json',
  'home-media-production.json',
  'home-media-staging.json',
  'resolve-staging-deploy-sha.mjs',
  'runtime_status != 0 || media_status != 0',
]) {
  requireText(workflow, fragment, `runtime workflow is missing: ${fragment}`);
}
for (const forbidden of ['password=', 'api_key', 'access_token', 'pixelId:']) {
  if (verifier.includes(forbidden) || mediaVerifier.includes(forbidden) || workflow.includes(forbidden)) {
    throw new Error(`secret-like fragment is forbidden: ${forbidden}`);
  }
}
console.log('PASS: third-party and home media runtime contracts');
