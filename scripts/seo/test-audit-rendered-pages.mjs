#!/usr/bin/env node

import assert from 'node:assert/strict';
import {
  analyseHtml,
  collectSchemaTypes,
  getCanonical,
  getMeta,
  isEdgeInterstitialResponse,
  parseAttributes,
  selectBlockingFindings,
} from './audit-rendered-pages.mjs';

const fixture = `<!doctype html>
<html lang="es">
<head>
  <title>Medicina Estética Láser Madrid | NUVANX</title>
  <meta name="description" content="Clínica médica de láser estético en Madrid.">
  <meta name="robots" content="index, follow">
  <meta property="og:url" content="https://nuvanx.com/">
  <meta property="og:image" content="https://nuvanx.com/media/hero.webp">
  <link rel="canonical" href="https://nuvanx.com/">
  <script type="application/ld+json">{
    "@context": "https://schema.org",
    "@graph": [
      {"@type": ["Organization", "MedicalOrganization"]},
      {"@type": "MedicalClinic", "name": "NUVANX Chamberí"},
      {"@type": ["Person", "Physician"], "name": "Dr. Rivera"}
    ]
  }</script>
</head>
<body>
  <h1>Medicina estética láser en Madrid</h1>
  <p>Centros sanitarios autorizados CS20144 y CS20073.</p>
</body>
</html>`;

assert.deepEqual(parseAttributes('<meta property="og:url" content="https://nuvanx.com/">'), {
  property: 'og:url',
  content: 'https://nuvanx.com/',
});
assert.equal(getMeta(fixture, 'description'), 'Clínica médica de láser estético en Madrid.');
assert.equal(getMeta(fixture, 'og:url', 'property'), 'https://nuvanx.com/');
assert.equal(getCanonical(fixture), 'https://nuvanx.com/');
assert.deepEqual(
  collectSchemaTypes(fixture).types,
  ['MedicalClinic', 'MedicalOrganization', 'Organization', 'Person', 'Physician'],
);

const production = analyseHtml({
  html: fixture,
  status: 200,
  headers: {},
  finalUrl: 'https://nuvanx.com/',
  environment: 'production',
  route: { path: '/', role: 'home', expectedTypes: ['MedicalClinic', 'Physician'] },
});
assert.equal(production.issues.filter((finding) => finding.severity === 'critical').length, 0);
assert.equal(production.h1Count, 1);
assert.equal(production.noindex, false);

const stagingHtml = fixture
  .replace('content="index, follow"', 'content="noindex, nofollow"')
  .replaceAll('https://nuvanx.com/', 'https://staging2.nuvanx.com/');
const staging = analyseHtml({
  html: stagingHtml,
  status: 200,
  headers: {},
  finalUrl: 'https://staging2.nuvanx.com/',
  environment: 'staging',
  route: { path: '/', role: 'home', expectedTypes: ['MedicalClinic', 'Physician'] },
});
assert.equal(staging.noindex, true);
assert.equal(staging.issues.some((finding) => finding.code === 'STAGING_INDEXABLE'), false);

const contaminated = analyseHtml({
  html: fixture.replace('</head>', '<meta property="og:image" content="https://staging2.nuvanx.com/hero.webp"></head>'),
  status: 200,
  headers: {},
  finalUrl: 'https://nuvanx.com/',
  environment: 'production',
  route: { path: '/', role: 'home', expectedTypes: [] },
});
assert.equal(contaminated.issues.some((finding) => finding.code === 'PRODUCTION_STAGING_REFERENCE'), true);

const indexableStaging = analyseHtml({
  html: fixture,
  status: 200,
  headers: {},
  finalUrl: 'https://staging2.nuvanx.com/',
  environment: 'staging',
  route: { path: '/', role: 'home', expectedTypes: [] },
});
assert.equal(indexableStaging.issues.some((finding) => finding.code === 'STAGING_INDEXABLE'), true);

assert.equal(
  isEdgeInterstitialResponse({
    status: 200,
    contentType: 'text/html',
    title: 'Robot Challenge Screen',
    bodyTextLength: 400,
    finalUrl: 'https://nuvanx.com/.well-known/sgcaptcha/?r=%2F&y=ipr:1.2.3.4:123',
    html: '<html><head><title>Robot Challenge Screen</title><meta name="robots" content="NOINDEX, NOFOLLOW"></head><body><h1>nuvanx.com</h1></body></html>',
  }),
  true,
);

const captchaPage = analyseHtml({
  html: '<html><head><title>Robot Challenge Screen</title><meta name="robots" content="NOINDEX, NOFOLLOW"></head><body><h1>nuvanx.com</h1><p>Checking your browser</p></body></html>',
  status: 200,
  headers: { 'content-type': 'text/html', 'x-robots-tag': 'noindex' },
  finalUrl: 'https://nuvanx.com/.well-known/sgcaptcha/?r=%2F',
  environment: 'production',
  route: { path: '/', role: 'home', expectedTypes: ['MedicalClinic', 'Physician'] },
});
assert.equal(captchaPage.issues.some((finding) => finding.code === 'EDGE_INTERSTITIAL'), true);
assert.equal(captchaPage.issues.some((finding) => finding.code === 'PRODUCTION_NOINDEX'), false);
assert.equal(selectBlockingFindings(captchaPage.issues, 'critical').length, 0);
assert.equal(selectBlockingFindings(captchaPage.issues, 'all').length, 1);

const noindexPage = analyseHtml({
  html: fixture.replace('content="index, follow"', 'content="noindex, follow"'),
  status: 200,
  headers: { 'content-type': 'text/html' },
  finalUrl: 'https://nuvanx.com/',
  environment: 'production',
  route: { path: '/', role: 'home', expectedTypes: [] },
});
assert.equal(noindexPage.issues.some((finding) => finding.code === 'PRODUCTION_NOINDEX'), true);
assert.equal(selectBlockingFindings(noindexPage.issues, 'critical').some((f) => f.code === 'PRODUCTION_NOINDEX'), true);

console.log('SEO/GEO rendered audit tests passed.');
