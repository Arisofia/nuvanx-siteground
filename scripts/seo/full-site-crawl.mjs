#!/usr/bin/env node

import fs from 'node:fs/promises';
import path from 'node:path';

const baseUrl = new URL(process.env.BASE_URL || 'https://nuvanx.com/');
const outputDir = process.env.OUTPUT_DIR || 'artifacts/seo-evidence';
const concurrency = Math.max(1, Number(process.env.CRAWL_CONCURRENCY || 6));
const userAgent = 'NUVANX-SEO-Evidence/1.0 (+https://nuvanx.com/)';
const criticalOnly = process.env.CRITICAL_ONLY !== '0';

const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));
const decodeEntities = (value = '') => value
  .replace(/&amp;/gi, '&')
  .replace(/&quot;/gi, '"')
  .replace(/&#39;|&apos;/gi, "'")
  .replace(/&lt;/gi, '<')
  .replace(/&gt;/gi, '>')
  .replace(/&#(\d+);/g, (_, code) => String.fromCodePoint(Number(code)));
const stripTags = (value = '') => decodeEntities(value.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim());
const normaliseUrl = (value) => {
  try {
    const url = new URL(value, baseUrl);
    url.hash = '';
    return url.href;
  } catch {
    return '';
  }
};
const sameOrigin = (value) => {
  try { return new URL(value).origin === baseUrl.origin; } catch { return false; }
};
const isHtmlLike = (response) => (response.headers.get('content-type') || '').toLowerCase().includes('text/html');
const isEdgeInterstitial = (body = '') => /sgcaptcha|robot challenge|access denied|cf-chl-/i.test(body);

async function fetchWithRetry(url, options = {}, attempts = 3) {
  let lastError;
  for (let attempt = 1; attempt <= attempts; attempt += 1) {
    try {
      const response = await fetch(url, {
        redirect: options.redirect || 'manual',
        signal: AbortSignal.timeout(Number(process.env.FETCH_TIMEOUT_MS || 25000)),
        headers: { 'user-agent': userAgent, accept: options.accept || '*/*' },
        ...options,
      });
      const text = options.method === 'HEAD' ? '' : await response.text();
      if ((response.status === 429 || response.status >= 500 || isEdgeInterstitial(text)) && attempt < attempts) {
        await sleep(750 * attempt);
        continue;
      }
      return { response, text, edgeInterstitial: isEdgeInterstitial(text) };
    } catch (error) {
      lastError = error;
      if (attempt < attempts) await sleep(750 * attempt);
    }
  }
  throw lastError || new Error(`Unable to fetch ${url}`);
}

function extractLocs(xml, parentTag) {
  const blockPattern = new RegExp(`<${parentTag}\\b[^>]*>([\\s\\S]*?)<\\/${parentTag}>`, 'gi');
  const results = [];
  for (const match of xml.matchAll(blockPattern)) {
    const locMatch = match[1].match(/<loc\b[^>]*>([\s\S]*?)<\/loc>/i);
    if (locMatch) results.push(decodeEntities(locMatch[1].trim()));
  }
  return results;
}

async function collectSitemapUrls() {
  const queue = [new URL('/sitemap_index.xml', baseUrl).href];
  const visitedMaps = new Set();
  const urls = new Set();
  const mediaIssues = [];
  const sitemapErrors = [];

  while (queue.length) {
    const sitemapUrl = queue.shift();
    if (!sitemapUrl || visitedMaps.has(sitemapUrl)) continue;
    visitedMaps.add(sitemapUrl);
    try {
      const { response, text, edgeInterstitial } = await fetchWithRetry(sitemapUrl, { redirect: 'follow', accept: 'application/xml,text/xml,*/*' });
      if (!response.ok || edgeInterstitial) {
        sitemapErrors.push({ url: sitemapUrl, status: response.status, edgeInterstitial });
        continue;
      }
      if (/<sitemapindex\b/i.test(text)) {
        for (const loc of extractLocs(text, 'sitemap')) queue.push(normaliseUrl(loc));
        continue;
      }
      for (const loc of extractLocs(text, 'url')) urls.add(normaliseUrl(loc));
      for (const imageLoc of [...text.matchAll(/<image:loc\b[^>]*>([\s\S]*?)<\/image:loc>/gi)].map((m) => decodeEntities(m[1].trim()))) {
        if (!/\.(?:avif|gif|jpe?g|png|svg|webp)(?:[?#]|$)/i.test(imageLoc)) {
          mediaIssues.push({ sitemap: sitemapUrl, loc: imageLoc, issue: 'non-image URL in image sitemap entry' });
        }
      }
    } catch (error) {
      sitemapErrors.push({ url: sitemapUrl, status: 'error', error: String(error) });
    }
  }

  return { urls: [...urls].filter(Boolean).sort(), sitemaps: [...visitedMaps], mediaIssues, sitemapErrors };
}

function metaContent(html, key, attribute = 'name') {
  const escaped = key.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  const patterns = [
    new RegExp(`<meta\\b(?=[^>]*\\b${attribute}\\s*=\\s*(["'])${escaped}\\1)[^>]*\\bcontent\\s*=\\s*(["'])([\\s\\S]*?)\\2[^>]*>`, 'i'),
    new RegExp(`<meta\\b(?=[^>]*\\bcontent\\s*=\\s*(["'])([\\s\\S]*?)\\1)[^>]*\\b${attribute}\\s*=\\s*(["'])${escaped}\\3[^>]*>`, 'i'),
  ];
  for (const pattern of patterns) {
    const match = html.match(pattern);
    if (match) return decodeEntities(match[3] || match[2] || '').trim();
  }
  return '';
}

function linkHref(html, rel) {
  const escaped = rel.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  const match = html.match(new RegExp(`<link\\b(?=[^>]*\\brel\\s*=\\s*(["'])${escaped}\\1)[^>]*\\bhref\\s*=\\s*(["'])([\\s\\S]*?)\\2[^>]*>`, 'i'));
  return match ? decodeEntities(match[3]).trim() : '';
}

function extractPage(url, response, html) {
  const title = stripTags(html.match(/<title\b[^>]*>([\s\S]*?)<\/title>/i)?.[1] || '');
  const h1s = [...html.matchAll(/<h1\b[^>]*>([\s\S]*?)<\/h1>/gi)].map((m) => stripTags(m[1])).filter(Boolean);
  const images = [...html.matchAll(/<img\b[^>]*>/gi)].map((m) => m[0]);
  const missingAltAttribute = images.filter((tag) => !/\balt\s*=/i.test(tag)).length;
  const emptyAlt = images.filter((tag) => /\balt\s*=\s*(["'])\s*\1/i.test(tag)).length;
  const jsonLd = [...html.matchAll(/<script\b(?=[^>]*\btype\s*=\s*(["'])application\/ld\+json\1)[^>]*>([\s\S]*?)<\/script>/gi)];
  const yoastGraphs = jsonLd.filter((m) => /yoast-schema-graph/i.test(m[0])).length;
  const internalLinks = new Set();
  for (const match of html.matchAll(/<a\b[^>]*\bhref\s*=\s*(["'])([\s\S]*?)\1[^>]*>/gi)) {
    const href = decodeEntities(match[2].trim());
    if (!href || /^(?:#|mailto:|tel:|javascript:|data:)/i.test(href)) continue;
    const absolute = normaliseUrl(href);
    if (absolute && sameOrigin(absolute)) internalLinks.add(absolute);
  }
  const description = metaContent(html, 'description');
  const robots = metaContent(html, 'robots').toLowerCase();
  return {
    url,
    status: response.status,
    finalUrl: response.url || url,
    title,
    titleLength: [...title].length,
    description,
    descriptionLength: [...description].length,
    canonical: normaliseUrl(linkHref(html, 'canonical')),
    robots,
    noindex: robots.includes('noindex'),
    h1Count: h1s.length,
    h1s,
    ogImage: metaContent(html, 'og:image', 'property'),
    jsonLdCount: jsonLd.length,
    yoastGraphCount: yoastGraphs,
    imageCount: images.length,
    missingAltAttribute,
    emptyAlt,
    internalLinks: [...internalLinks].sort(),
  };
}

async function mapLimit(items, limit, mapper) {
  const results = new Array(items.length);
  let cursor = 0;
  async function worker() {
    while (cursor < items.length) {
      const index = cursor++;
      results[index] = await mapper(items[index], index);
    }
  }
  await Promise.all(Array.from({ length: Math.min(limit, items.length || 1) }, worker));
  return results;
}

async function crawlPages(urls) {
  return mapLimit(urls, concurrency, async (url) => {
    try {
      const { response, text, edgeInterstitial } = await fetchWithRetry(url, { redirect: 'manual', accept: 'text/html,application/xhtml+xml' });
      const location = response.headers.get('location') ? normaliseUrl(response.headers.get('location')) : '';
      if (response.status >= 300 && response.status < 400) return { url, status: response.status, location };
      if (edgeInterstitial) return { url, status: response.status, edgeInterstitial: true };
      if (!isHtmlLike(response)) return { url, status: response.status, contentType: response.headers.get('content-type') || '' };
      return extractPage(url, response, text);
    } catch (error) {
      return { url, status: 'error', error: String(error) };
    }
  });
}

async function checkInternalLinks(links) {
  return mapLimit(links, concurrency, async (url) => {
    try {
      let result = await fetchWithRetry(url, { method: 'HEAD', redirect: 'manual' }, 2);
      if (result.response.status === 405 || result.response.status === 403) {
        result = await fetchWithRetry(url, { method: 'GET', redirect: 'manual', accept: 'text/html,*/*' }, 2);
      }
      return {
        url,
        status: result.response.status,
        location: result.response.headers.get('location') ? normaliseUrl(result.response.headers.get('location')) : '',
        edgeInterstitial: result.edgeInterstitial,
      };
    } catch (error) {
      return { url, status: 'error', error: String(error) };
    }
  });
}

function buildIssues(pages, sitemapUrls, linkChecks, mediaIssues, sitemapErrors) {
  const issues = [];
  const sitemapSet = new Set(sitemapUrls.map(normaliseUrl));
  const inbound = new Map([...sitemapSet].map((url) => [url, 0]));
  for (const page of pages) {
    for (const link of page.internalLinks || []) {
      const target = normaliseUrl(link);
      if (inbound.has(target) && target !== normaliseUrl(page.url)) inbound.set(target, inbound.get(target) + 1);
    }
  }
  for (const page of pages) {
    const url = normaliseUrl(page.url);
    if (page.status !== 200) issues.push({ severity: 'critical', type: 'sitemap_status', url, value: page.status });
    if (page.edgeInterstitial) issues.push({ severity: 'warning', type: 'edge_interstitial', url });
    if (page.status !== 200 || !page.title) {
      if (page.status === 200) issues.push({ severity: 'critical', type: 'missing_title', url });
      continue;
    }
    if (page.titleLength > 65) issues.push({ severity: 'warning', type: 'title_too_long', url, value: page.titleLength });
    if (!page.description) issues.push({ severity: 'warning', type: 'missing_description', url });
    else if (page.descriptionLength < 110 || page.descriptionLength > 170) issues.push({ severity: 'warning', type: 'description_length', url, value: page.descriptionLength });
    if (!page.canonical) issues.push({ severity: 'critical', type: 'missing_canonical', url });
    else if (normaliseUrl(page.canonical) !== url) issues.push({ severity: 'critical', type: 'canonical_mismatch', url, value: page.canonical });
    if (page.noindex) issues.push({ severity: 'critical', type: 'sitemap_noindex', url, value: page.robots });
    if (page.h1Count !== 1) issues.push({ severity: 'critical', type: 'h1_count', url, value: page.h1Count });
    if (page.jsonLdCount > 1) issues.push({ severity: 'warning', type: 'duplicate_jsonld_blocks', url, value: page.jsonLdCount });
    if (page.yoastGraphCount !== 1) issues.push({ severity: 'warning', type: 'yoast_graph_count', url, value: page.yoastGraphCount });
    if (page.missingAltAttribute > 0) issues.push({ severity: 'warning', type: 'images_missing_alt_attribute', url, value: page.missingAltAttribute });
    if (url !== baseUrl.href && sitemapSet.has(url) && (inbound.get(url) || 0) === 0) issues.push({ severity: 'warning', type: 'orphan_sitemap_url', url });
  }
  for (const check of linkChecks) {
    if (check.edgeInterstitial) issues.push({ severity: 'warning', type: 'link_edge_interstitial', url: check.url });
    else if (check.status === 'error' || Number(check.status) >= 400) issues.push({ severity: 'critical', type: 'broken_internal_link', url: check.url, value: check.status });
    else if (Number(check.status) >= 300) issues.push({ severity: 'warning', type: 'redirecting_internal_link', url: check.url, value: `${check.status} -> ${check.location || ''}` });
  }
  for (const issue of mediaIssues) issues.push({ severity: 'warning', type: 'sitemap_media_type', url: issue.loc, value: issue.sitemap });
  for (const error of sitemapErrors) issues.push({ severity: 'critical', type: 'sitemap_fetch_error', url: error.url, value: error.status });
  return { issues, inbound: Object.fromEntries(inbound) };
}

function markdownReport(report) {
  const lines = [
    '# NUVANX full-site SEO evidence',
    '',
    `- Generated: ${report.generatedAt}`,
    `- Base URL: ${report.baseUrl}`,
    `- Sitemap URLs: ${report.summary.sitemapUrls}`,
    `- Pages crawled: ${report.summary.pagesCrawled}`,
    `- Internal links checked: ${report.summary.internalLinksChecked}`,
    `- Critical findings: ${report.summary.critical}`,
    `- Warnings: ${report.summary.warnings}`,
    '',
    '## Findings',
    '',
    '| Severity | Type | URL | Value |',
    '|---|---|---|---|',
  ];
  for (const issue of report.issues) {
    lines.push(`| ${issue.severity} | ${issue.type} | ${issue.url || ''} | ${String(issue.value ?? '').replace(/\|/g, '\\|')} |`);
  }
  if (!report.issues.length) lines.push('| — | No findings | — | — |');
  lines.push('', '## Page inventory', '', '| Status | URL | Title | H1 | Canonical | JSON-LD | Missing alt attribute |', '|---:|---|---|---:|---|---:|---:|');
  for (const page of report.pages) {
    lines.push(`| ${page.status} | ${page.url} | ${(page.title || '').replace(/\|/g, '\\|')} | ${page.h1Count ?? ''} | ${page.canonical || ''} | ${page.jsonLdCount ?? ''} | ${page.missingAltAttribute ?? ''} |`);
  }
  return `${lines.join('\n')}\n`;
}

async function main() {
  await fs.mkdir(outputDir, { recursive: true });
  const sitemap = await collectSitemapUrls();
  const pages = await crawlPages(sitemap.urls);
  const allInternalLinks = [...new Set(pages.flatMap((page) => page.internalLinks || []))].sort();
  const linkChecks = await checkInternalLinks(allInternalLinks);
  const { issues, inbound } = buildIssues(pages, sitemap.urls, linkChecks, sitemap.mediaIssues, sitemap.sitemapErrors);
  const report = {
    generatedAt: new Date().toISOString(),
    baseUrl: baseUrl.href,
    sitemaps: sitemap.sitemaps,
    summary: {
      sitemapUrls: sitemap.urls.length,
      pagesCrawled: pages.length,
      internalLinksChecked: linkChecks.length,
      critical: issues.filter((issue) => issue.severity === 'critical').length,
      warnings: issues.filter((issue) => issue.severity === 'warning').length,
    },
    issues,
    pages,
    linkChecks,
    inbound,
  };
  await fs.writeFile(path.join(outputDir, 'full-site-seo-report.json'), `${JSON.stringify(report, null, 2)}\n`);
  await fs.writeFile(path.join(outputDir, 'full-site-seo-report.md'), markdownReport(report));
  console.log(JSON.stringify(report.summary));
  if (criticalOnly && report.summary.critical > 0) process.exitCode = 1;
}

main().catch((error) => {
  console.error(error);
  process.exitCode = 1;
});
