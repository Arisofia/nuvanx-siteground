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
  rows.push({
    file,
    url: report.finalDisplayedUrl || report.finalUrl || report.requestedUrl || '',
    performance: Math.round((categories.performance?.score || 0) * 100),
    accessibility: Math.round((categories.accessibility?.score || 0) * 100),
    bestPractices: Math.round((categories['best-practices']?.score || 0) * 100),
    seo: Math.round((categories.seo?.score || 0) * 100),
    fcp: audits['first-contentful-paint']?.numericValue || null,
    lcp: audits['largest-contentful-paint']?.numericValue || null,
    cls: audits['cumulative-layout-shift']?.numericValue || null,
    tbt: audits['total-blocking-time']?.numericValue || null,
    speedIndex: audits['speed-index']?.numericValue || null,
  });
}

const markdown = [
  '# NUVANX Lighthouse evidence',
  '',
  '| Report | URL | Performance | Accessibility | Best practices | SEO | FCP ms | LCP ms | CLS | TBT ms |',
  '|---|---|---:|---:|---:|---:|---:|---:|---:|---:|',
  ...rows.map((row) => `| ${row.file} | ${row.url} | ${row.performance} | ${row.accessibility} | ${row.bestPractices} | ${row.seo} | ${Math.round(row.fcp || 0)} | ${Math.round(row.lcp || 0)} | ${(row.cls || 0).toFixed(3)} | ${Math.round(row.tbt || 0)} |`),
  '',
].join('\n');

await fs.writeFile(path.join(inputDir, 'summary.json'), `${JSON.stringify(rows, null, 2)}\n`);
await fs.writeFile(path.join(inputDir, 'summary.md'), markdown);
console.log(markdown);
