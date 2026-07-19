#!/usr/bin/env node

import fs from 'node:fs/promises';
import path from 'node:path';
import { chromium } from '@playwright/test';

const baseUrl = new URL(process.env.BASE_URL || 'https://nuvanx.com/');
const outputDir = process.env.OUTPUT_DIR || 'artifacts/seo-evidence';
const timeout = Number(process.env.FETCH_TIMEOUT_MS || 90000);
const delayMs = Number(process.env.CRAWL_DELAY_MS || 350);
const browserUA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36';

const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));
const decode = (value = '') => value
  .replace(/&amp;/gi, '&').replace(/&quot;/gi, '"').replace(/&#39;|&apos;/gi, "'")
  .replace(/&lt;/gi, '<').replace(/&gt;/gi, '>')
  .replace(/&#(\d+);/g, (_, code) => String.fromCodePoint(Number(code)));
const textOnly = (value = '') => decode(value.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim());
const normalise = (value) => {
  try { const url = new URL(value, baseUrl); url.hash = ''; return url.href; } catch { return ''; }
};
const sameOrigin = (value) => { try { return new URL(value).origin === baseUrl.origin; } catch { return false; } };
const edge = ({ status = 0, body = '', url = '', contentType = '' }) => status === 202
  || /sgcaptcha|robot challenge|access denied|cf-chl-/i.test(`${body} ${url}`)
  || (contentType && !/text\/html|application\/xhtml|xml|application\/json/i.test(contentType));

function extractLocs(xml, tag) {
  const out = [];
  const pattern = new RegExp(`<${tag}\\b[^>]*>([\\s\\S]*?)<\\/${tag}>`, 'gi');
  for (const match of xml.matchAll(pattern)) {
    const loc = match[1].match(/<loc\b[^>]*>([\s\S]*?)<\/loc>/i)?.[1];
    if (loc) out.push(normalise(decode(loc.trim())));
  }
  return out.filter(Boolean);
}

function attrTag(html, tagName, attribute, value, outputAttribute) {
  for (const match of html.matchAll(new RegExp(`<${tagName}\\b[^>]*>`, 'gi'))) {
    const tag = match[0];
    const expected = tag.match(new RegExp(`\\b${attribute}\\s*=\\s*(["'])(.*?)\\1`, 'i'))?.[2] || '';
    if (expected.toLowerCase() !== value.toLowerCase()) continue;
    return decode(tag.match(new RegExp(`\\b${outputAttribute}\\s*=\\s*(["'])(.*?)\\1`, 'i'))?.[2] || '');
  }
  return '';
}

function parsePage(url, status, html) {
  const title = textOnly(html.match(/<title\b[^>]*>([\s\S]*?)<\/title>/i)?.[1] || '');
  const description = attrTag(html, 'meta', 'name', 'description', 'content');
  const robots = attrTag(html, 'meta', 'name', 'robots', 'content').toLowerCase();
  const canonical = normalise(attrTag(html, 'link', 'rel', 'canonical', 'href'));
  const ogImage = attrTag(html, 'meta', 'property', 'og:image', 'content');
  const h1s = [...html.matchAll(/<h1\b[^>]*>([\s\S]*?)<\/h1>/gi)].map((m) => textOnly(m[1])).filter(Boolean);
  const jsonLd = [...html.matchAll(/<script\b(?=[^>]*\btype\s*=\s*(["'])application\/ld\+json\1)[^>]*>([\s\S]*?)<\/script>/gi)];
  const images = [...html.matchAll(/<img\b[^>]*>/gi)].map((m) => m[0]);
  const internalLinks = new Set();
  for (const match of html.matchAll(/<a\b[^>]*\bhref\s*=\s*(["'])(.*?)\1[^>]*>/gi)) {
    const href = decode(match[2].trim());
    if (!href || /^(?:#|mailto:|tel:|javascript:|data:)/i.test(href)) continue;
    const absolute = normalise(href);
    if (absolute && sameOrigin(absolute)) internalLinks.add(absolute);
  }
  return {
    url, status, title, titleLength: [...title].length, description,
    descriptionLength: [...description].length, canonical, robots,
    noindex: robots.includes('noindex'), h1Count: h1s.length, h1s, ogImage,
    jsonLdCount: jsonLd.length,
    yoastGraphCount: jsonLd.filter((item) => /yoast-schema-graph/i.test(item[0])).length,
    imageCount: images.length,
    missingAltAttribute: images.filter((tag) => !/\balt\s*=/i.test(tag)).length,
    emptyAlt: images.filter((tag) => /\balt\s*=\s*(["'])\s*\1/i.test(tag)).length,
    internalLinks: [...internalLinks].sort(),
  };
}

async function navigate(page, url, attempts = 4) {
  let last = { status: 0, html: '', finalUrl: url, contentType: '', edge: true };
  for (let attempt = 1; attempt <= attempts; attempt += 1) {
    const response = await page.goto(url, { waitUntil: 'domcontentloaded', timeout }).catch(() => null);
    await page.waitForLoadState('networkidle', { timeout: 8000 }).catch(() => {});
    const status = response?.status() || 0;
    const headers = response ? await response.allHeaders().catch(() => response.headers()) : {};
    const contentType = headers['content-type'] || '';
    const finalUrl = page.url();
    const html = response && /xml|application\/json/i.test(contentType)
      ? await response.text().catch(() => page.content())
      : await page.content().catch(() => '');
    last = { status, html, finalUrl, contentType, edge: edge({ status, body: html, url: finalUrl, contentType }) };
    if (!last.edge && status >= 200 && status < 400) return last;
    if (attempt < attempts) await sleep(1200 * attempt);
  }
  return last;
}

async function discoverRestUrls(page) {
  const endpoints = [
    '/wp-json/wp/v2/pages?per_page=100&status=publish&_fields=link',
    '/wp-json/wp/v2/posts?per_page=100&status=publish&_fields=link',
    '/wp-json/wp/v2/categories?per_page=100&hide_empty=true&_fields=link',
    '/wp-json/wp/v2/tags?per_page=100&hide_empty=true&_fields=link',
  ];
  const urls = new Set([baseUrl.href]);
  const errors = [];
  for (const endpoint of endpoints) {
    const requestUrl = new URL(endpoint, baseUrl).href;
    const result = await navigate(page, requestUrl);
    if (result.status !== 200 || result.edge) {
      errors.push({ url: requestUrl, status: result.status, edge: result.edge, finalUrl: result.finalUrl, source: 'rest' });
      continue;
    }
    try {
      const items = JSON.parse(result.html);
      if (Array.isArray(items)) {
        for (const item of items) {
          const link = normalise(item?.link || '');
          if (link && sameOrigin(link)) urls.add(link);
        }
      }
    } catch (errorValue) {
      errors.push({ url: requestUrl, status: result.status, source: 'rest-parse', error: String(errorValue) });
    }
    await sleep(delayMs);
  }
  return { urls: [...urls].sort(), errors };
}

async function discoverSitemap(page) {
  const queue = [new URL('/sitemap_index.xml', baseUrl).href];
  const sitemaps = new Set();
  const urls = new Set();
  const errors = [];
  const mediaIssues = [];
  let source = 'sitemap';
  while (queue.length) {
    const sitemap = queue.shift();
    if (!sitemap || sitemaps.has(sitemap)) continue;
    sitemaps.add(sitemap);
    const result = await navigate(page, sitemap);
    if (result.status !== 200 || result.edge) {
      errors.push({ url: sitemap, status: result.status, edge: result.edge, finalUrl: result.finalUrl, source: 'sitemap' });
      continue;
    }
    if (/<sitemapindex\b/i.test(result.html)) queue.push(...extractLocs(result.html, 'sitemap'));
    if (!/<sitemapindex\b/i.test(result.html)) {
      for (const loc of extractLocs(result.html, 'url')) urls.add(loc);
      for (const loc of [...result.html.matchAll(/<image:loc\b[^>]*>([\s\S]*?)<\/image:loc>/gi)].map((m) => decode(m[1].trim()))) {
        if (!/\.(?:avif|gif|jpe?g|png|svg|webp)(?:[?#]|$)/i.test(loc)) mediaIssues.push({ sitemap, loc });
      }
    }
    await sleep(delayMs);
  }
  if (urls.size === 0) {
    source = 'wordpress-rest-fallback';
    const fallback = await discoverRestUrls(page);
    fallback.urls.forEach((url) => urls.add(url));
    errors.push(...fallback.errors);
  }
  return { urls: [...urls].sort(), sitemaps: [...sitemaps], errors, mediaIssues, source };
}

function evaluate(pages, inventory, linkChecks) {
  const issues = [];
  const inventorySet = new Set(inventory.urls.map(normalise));
  const inbound = new Map([...inventorySet].map((url) => [url, 0]));
  for (const page of pages) for (const link of page.internalLinks || []) {
    const target = normalise(link);
    if (inbound.has(target) && target !== normalise(page.url)) inbound.set(target, inbound.get(target) + 1);
  }
  for (const page of pages) {
    const url = normalise(page.url);
    if (page.status !== 200 && !page.edge) issues.push({ severity: 'critical', type: 'inventory_status', url, value: page.status });
    if (page.edge) issues.push({ severity: 'infrastructure', type: 'edge_interstitial', url, value: page.status });
    if (page.status !== 200 || page.edge) continue;
    if (!page.title) issues.push({ severity: 'critical', type: 'missing_title', url });
    else if (page.titleLength > 65) issues.push({ severity: 'warning', type: 'title_too_long', url, value: page.titleLength });
    if (!page.description) issues.push({ severity: 'warning', type: 'missing_description', url });
    else if (page.descriptionLength < 110 || page.descriptionLength > 170) issues.push({ severity: 'warning', type: 'description_length', url, value: page.descriptionLength });
    if (!page.canonical) issues.push({ severity: 'critical', type: 'missing_canonical', url });
    else if (page.canonical !== url) issues.push({ severity: 'critical', type: 'canonical_mismatch', url, value: page.canonical });
    if (page.noindex) issues.push({ severity: 'critical', type: 'inventory_noindex', url, value: page.robots });
    if (page.h1Count !== 1) issues.push({ severity: 'critical', type: 'h1_count', url, value: page.h1Count });
    if (page.jsonLdCount > 1) issues.push({ severity: 'warning', type: 'duplicate_jsonld_blocks', url, value: page.jsonLdCount });
    if (page.yoastGraphCount !== 1) issues.push({ severity: 'warning', type: 'yoast_graph_count', url, value: page.yoastGraphCount });
    if (page.missingAltAttribute) issues.push({ severity: 'warning', type: 'images_missing_alt_attribute', url, value: page.missingAltAttribute });
    if (url !== baseUrl.href && (inbound.get(url) || 0) === 0) issues.push({ severity: 'warning', type: 'orphan_inventory_url', url });
  }
  for (const check of linkChecks) {
    if (check.edge) issues.push({ severity: 'infrastructure', type: 'link_edge_interstitial', url: check.url, value: check.status });
    else if (check.status >= 400 || check.status === 0) issues.push({ severity: 'critical', type: 'broken_internal_link', url: check.url, value: check.status });
    else if (check.status >= 300) issues.push({ severity: 'warning', type: 'redirecting_internal_link', url: check.url, value: `${check.status} -> ${check.finalUrl}` });
  }
  for (const item of inventory.mediaIssues) issues.push({ severity: 'warning', type: 'sitemap_media_type', url: item.loc, value: item.sitemap });
  for (const item of inventory.errors) issues.push({ severity: 'infrastructure', type: `${item.source || 'inventory'}_fetch_error`, url: item.url, value: item.status || item.error });
  return { issues, inbound: Object.fromEntries(inbound) };
}

function markdown(report) {
  const lines = [
    '# NUVANX full-site SEO evidence', '',
    `- Generated: ${report.generatedAt}`,
    `- Base URL: ${report.baseUrl}`,
    `- Discovery source: ${report.discoverySource}`,
    `- Inventory URLs: ${report.summary.inventoryUrls}`,
    `- Pages crawled: ${report.summary.pagesCrawled}`,
    `- Internal links checked: ${report.summary.internalLinksChecked}`,
    `- Critical: ${report.summary.critical}`,
    `- Warnings: ${report.summary.warnings}`,
    `- Infrastructure: ${report.summary.infrastructure}`,
    '', '## Findings', '',
    '| Severity | Type | URL | Value |', '|---|---|---|---|',
    ...report.issues.map((item) => `| ${item.severity} | ${item.type} | ${item.url || ''} | ${String(item.value ?? '').replace(/\|/g, '\\|')} |`),
  ];
  if (!report.issues.length) lines.push('| — | No findings | — | — |');
  return `${lines.join('\n')}\n`;
}

async function main() {
  await fs.mkdir(outputDir, { recursive: true });
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({ locale: 'es-ES', timezoneId: 'Europe/Madrid', userAgent: browserUA, viewport: { width: 1440, height: 1000 } });
  await context.route('**/*', async (route) => {
    if (['image', 'media', 'font'].includes(route.request().resourceType())) await route.abort();
    else await route.continue();
  });
  const page = await context.newPage();
  const inventory = await discoverSitemap(page);
  const pages = [];
  for (const url of inventory.urls) {
    const result = await navigate(page, url);
    pages.push(result.status === 200 && !result.edge ? parsePage(url, result.status, result.html) : { url, ...result });
    await sleep(delayMs);
  }
  const internalLinks = [...new Set(pages.flatMap((item) => item.internalLinks || []))].sort();
  const linkChecks = [];
  for (const url of internalLinks) {
    const result = await navigate(page, url, 2);
    linkChecks.push({ url, status: result.status, finalUrl: result.finalUrl, edge: result.edge });
    await sleep(Math.min(delayMs, 200));
  }
  await context.close();
  await browser.close();
  const { issues, inbound } = evaluate(pages, inventory, linkChecks);
  const report = {
    generatedAt: new Date().toISOString(), baseUrl: baseUrl.href,
    discoverySource: inventory.source, sitemaps: inventory.sitemaps,
    summary: {
      inventoryUrls: inventory.urls.length, pagesCrawled: pages.length, internalLinksChecked: linkChecks.length,
      critical: issues.filter((item) => item.severity === 'critical').length,
      warnings: issues.filter((item) => item.severity === 'warning').length,
      infrastructure: issues.filter((item) => item.severity === 'infrastructure').length,
    }, issues, pages, linkChecks, inbound,
  };
  await fs.writeFile(path.join(outputDir, 'full-site-seo-report.json'), `${JSON.stringify(report, null, 2)}\n`);
  await fs.writeFile(path.join(outputDir, 'full-site-seo-report.md'), markdown(report));
  console.log(JSON.stringify(report.summary));
}

main().catch((error) => { console.error(error); process.exitCode = 1; });
