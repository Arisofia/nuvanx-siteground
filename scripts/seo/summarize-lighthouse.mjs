#!/usr/bin/env node

import fs from 'node:fs/promises';
import path from 'node:path';

const inputDir = process.env.LIGHTHOUSE_DIR || 'artifacts/lighthouse';
const files = (await fs.readdir(inputDir)).filter((file) => file.endsWith('.json')).sort();
const rows = [];

for (const file of files) {
  const report = JSON.parse(await fs.readFile(path.join(inputDir, file), 'utf8'));
  const categories = report.categories || {};
  const audits = report.audits || {};
  const requestedUrl = report.requestedUrl || '';
  const finalUrl = report.finalDisplayedUrl || report.finalUrl || requestedUrl;
  const edgeInterstitial = /\/\.well-known\/sgcaptcha\//i.test(finalUrl)
    || /robot challenge|access denied/i.test(report.finalDisplayedUrl || '');
  const validPage = !edgeInterstitial;

  rows.push({
    file,
    requestedUrl,
    url: finalUrl,
    validPage,
    invalidReason: edgeInterstitial ? 'SiteGround edge interstitial' : '',
    performance: validPage ? Math.round((categories.performance?.score || 0) * 100) : null,
    accessibility: validPage ? Math.round((categories.accessibility?.score || 0) * 100) : null,
    bestPractices: validPage ? Math.round((categories['best-practices']?.score || 0) * 100) : null,
    seo: validPage ? Math.round((categories.seo?.score || 0) * 100) : null,
    fcp: validPage ? audits['first-contentful-paint']?.numericValue || null : null,
    lcp: validPage ? audits['largest-contentful-paint']?.numericValue || null : null,
    cls: validPage ? audits['cumulative-layout-shift']?.numericValue || null : null,
    tbt: validPage ? audits['total-blocking-time']?.numericValue || null : null,
    speedIndex: validPage ? audits['speed-index']?.numericValue || null : null,
  });
}

const show = (value, digits = 0) => {
  if (value === null || value === undefined) return '—';
  return digits ? Number(value).toFixed(digits) : String(Math.round(Number(value)));
};

const markdown = [
  '# NUVANX Lighthouse evidence',
  '',
  `Valid page reports: **${rows.filter((row) => row.validPage).length}**`,
  `Infrastructure-blocked reports: **${rows.filter((row) => !row.validPage).length}**`,
  '',
  '| Report | Status | Final URL | Performance | Accessibility | Best practices | SEO | FCP ms | LCP ms | CLS | TBT ms |',
  '|---|---|---|---:|---:|---:|---:|---:|---:|---:|---:|',
  ...rows.map((row) => `| ${row.file} | ${row.validPage ? 'VALID' : 'EDGE_BLOCKED'} | ${row.url} | ${show(row.performance)} | ${show(row.accessibility)} | ${show(row.bestPractices)} | ${show(row.seo)} | ${show(row.fcp)} | ${show(row.lcp)} | ${show(row.cls, 3)} | ${show(row.tbt)} |`),
  '',
  '> EDGE_BLOCKED rows are SiteGround challenge pages and must not be interpreted as performance scores for NUVANX.',
  '',
].join('\n');

await fs.writeFile(path.join(inputDir, 'summary.json'), `${JSON.stringify(rows, null, 2)}\n`);
await fs.writeFile(path.join(inputDir, 'summary.md'), markdown);
console.log(markdown);
