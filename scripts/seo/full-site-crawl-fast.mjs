#!/usr/bin/env node

import fs from 'node:fs/promises';
import path from 'node:path';
import { chromium } from '@playwright/test';

const base = new URL(process.env.BASE_URL || 'https://nuvanx.com/');
const outDir = process.env.OUTPUT_DIR || 'artifacts/seo-evidence';
const maxUrls = Number(process.env.CRAWL_MAX_URLS || 180);
const timeout = Number(process.env.FETCH_TIMEOUT_MS || 25000);
const knownRoutes = [
  '/', '/contacto/', '/blog/', '/medicina-estetica-chamberi/',
  '/clinicas-de-medicina-estetica-nuvanx/medicina-estetica-goya-barrio-salamanca/',
  '/endolift-facial-papada-mandibula/', '/endolaser-corporal-grasa-localizada/',
  '/laser-co2-fraccionado-madrid-textura-cicatrices-poro/', '/exion-btl/',
];

const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));
const decode = (s = '') => s.replace(/&amp;/gi, '&').replace(/&quot;/gi, '"').replace(/&#39;|&apos;/gi, "'").replace(/&lt;/gi, '<').replace(/&gt;/gi, '>');
const textOnly = (s = '') => decode(s.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim());
const normalise = (value) => {
  try { const u = new URL(value, base); u.hash = ''; return u.href; } catch { return ''; }
};
const isEdge = (status, html, url) => status === 202 || /sgcaptcha|robot challenge|access denied|cf-chl-/i.test(`${html} ${url}`);
const eligible = (value) => {
  try {
    const u = new URL(value, base);
    if (u.origin !== base.origin || u.search || u.hash) return false;
    if (/\/(?:wp-admin|wp-login|wp-json)(?:\/|$)/i.test(u.pathname)) return false;
    if (/\.(?:avif|css|csv|docx?|gif|ico|jpe?g|js|json|mp3|mp4|pdf|png|svg|webm|webp|woff2?|xml|zip)$/i.test(u.pathname)) return false;
    return true;
  } catch { return false; }
};
const meta = (html, attr, key, output = 'content') => {
  for (const m of html.matchAll(/<(?:meta|link)\b[^>]*>/gi)) {
    const tag = m[0];
    const a = tag.match(new RegExp(`\\b${attr}\\s*=\\s*(["'])(.*?)\\1`, 'i'))?.[2] || '';
    if (a.toLowerCase() !== key.toLowerCase()) continue;
    return decode(tag.match(new RegExp(`\\b${output}\\s*=\\s*(["'])(.*?)\\1`, 'i'))?.[2] || '');
  }
  return '';
};

async function navigate(page, url, attempts = 3) {
  let last = { url, status: 0, finalUrl: url, html: '', contentType: '', edge: true };
  for (let i = 1; i <= attempts; i += 1) {
    const response = await page.goto(url, { waitUntil: 'domcontentloaded', timeout }).catch(() => null);
    const status = response?.status() || 0;
    const headers = response ? await response.allHeaders().catch(() => response.headers()) : {};
    const contentType = headers['content-type'] || '';
    const finalUrl = page.url();
    const html = response && /xml|json/i.test(contentType) ? await response.text().catch(() => '') : await page.content().catch(() => '');
    last = { url, status, finalUrl, html, contentType, edge: isEdge(status, html, finalUrl) };
    if (!last.edge && status >= 200 && status < 400) return last;
    if (i < attempts) await sleep(700 * i);
  }
  return last;
}

function locs(xml, parent) {
  const found = [];
  const pattern = new RegExp(`<${parent}\\b[^>]*>([\\s\\S]*?)<\\/${parent}>`, 'gi');
  for (const block of xml.matchAll(pattern)) {
    const value = block[1].match(/<loc\b[^>]*>([\s\S]*?)<\/loc>/i)?.[1];
    if (value) found.push(normalise(decode(value.trim())));
  }
  return found.filter(Boolean);
}

async function discover(page) {
  const urls = new Set();
  const infrastructure = [];
  const maps = [];
  const queue = [new URL('/sitemap_index.xml', base).href];
  const seenMaps = new Set();

  while (queue.length) {
    const map = queue.shift();
    if (!map || seenMaps.has(map)) continue;
    seenMaps.add(map); maps.push(map);
    const r = await navigate(page, map, 2);
    if (r.status !== 200 || r.edge) { infrastructure.push({ type: 'sitemap_edge', url: map, status: r.status }); continue; }
    if (/<sitemapindex\b/i.test(r.html)) queue.push(...locs(r.html, 'sitemap'));
    else locs(r.html, 'url').forEach((u) => { if (eligible(u)) urls.add(u); });
  }

  if (urls.size < 5) {
    const endpoints = ['pages', 'posts', 'categories', 'tags'];
    for (const type of endpoints) {
      const endpoint = new URL(`/wp-json/wp/v2/${type}?per_page=100&${type === 'categories' || type === 'tags' ? 'hide_empty=true&' : 'status=publish&'}_fields=link`, base).href;
      const r = await navigate(page, endpoint, 2);
      if (r.status !== 200 || r.edge) { infrastructure.push({ type: 'rest_edge', url: endpoint, status: r.status }); continue; }
      try { JSON.parse(r.html).forEach((item) => { if (eligible(item?.link)) urls.add(normalise(item.link)); }); }
      catch { infrastructure.push({ type: 'rest_parse', url: endpoint, status: r.status }); }
    }
  }

  knownRoutes.forEach((route) => urls.add(new URL(route, base).href));
  return { urls, maps, infrastructure, source: urls.size > knownRoutes.length ? 'sitemap-or-rest' : 'known-routes-link-discovery' };
}

function parsePage(result) {
  const { html, url, status, finalUrl, edge } = result;
  const title = textOnly(html.match(/<title\b[^>]*>([\s\S]*?)<\/title>/i)?.[1] || '');
  const description = meta(html, 'name', 'description');
  const robots = meta(html, 'name', 'robots').toLowerCase();
  const canonical = normalise(meta(html, 'rel', 'canonical', 'href'));
  const h1s = [...html.matchAll(/<h1\b[^>]*>([\s\S]*?)<\/h1>/gi)].map((m) => textOnly(m[1])).filter(Boolean);
  const scripts = [...html.matchAll(/<script\b(?=[^>]*\btype\s*=\s*(["'])application\/ld\+json\1)[^>]*>([\s\S]*?)<\/script>/gi)];
  const images = [...html.matchAll(/<img\b[^>]*>/gi)].map((m) => m[0]);
  const links = new Set();
  for (const m of html.matchAll(/<a\b[^>]*\bhref\s*=\s*(["'])(.*?)\1[^>]*>/gi)) {
    const u = normalise(decode(m[2]));
    if (u && eligible(u)) links.add(u);
  }
  return {
    url, status, finalUrl, edge, title, titleLength: [...title].length,
    description, descriptionLength: [...description].length, robots,
    noindex: robots.includes('noindex'), canonical, h1Count: h1s.length, h1s,
    ogImage: meta(html, 'property', 'og:image'), jsonLdCount: scripts.length,
    yoastGraphCount: scripts.filter((m) => /yoast-schema-graph/i.test(m[0])).length,
    imageCount: images.length,
    missingAltAttribute: images.filter((tag) => !/\balt\s*=/i.test(tag)).length,
    emptyAlt: images.filter((tag) => /\balt\s*=\s*(["'])\s*\1/i.test(tag)).length,
    internalLinks: [...links].sort(),
  };
}

function assess(pages, infrastructure) {
  const issues = [...infrastructure.map((i) => ({ severity: 'infrastructure', ...i }))];
  const byUrl = new Map(pages.map((p) => [p.url, p]));
  const inbound = new Map(pages.map((p) => [p.url, 0]));
  for (const page of pages) for (const link of page.internalLinks || []) if (inbound.has(link) && link !== page.url) inbound.set(link, inbound.get(link) + 1);
  for (const p of pages) {
    if (p.edge) { issues.push({ severity: 'infrastructure', type: 'page_edge', url: p.url, value: p.status }); continue; }
    if (p.status !== 200) { issues.push({ severity: 'critical', type: p.status >= 300 && p.status < 400 ? 'redirect' : 'http_status', url: p.url, value: `${p.status} -> ${p.finalUrl}` }); continue; }
    if (!p.title) issues.push({ severity: 'critical', type: 'missing_title', url: p.url });
    else if (p.titleLength > 65) issues.push({ severity: 'warning', type: 'title_too_long', url: p.url, value: p.titleLength });
    if (!p.description) issues.push({ severity: 'warning', type: 'missing_description', url: p.url });
    else if (p.descriptionLength < 110 || p.descriptionLength > 170) issues.push({ severity: 'warning', type: 'description_length', url: p.url, value: p.descriptionLength });
    if (!p.canonical) issues.push({ severity: 'critical', type: 'missing_canonical', url: p.url });
    else if (p.canonical !== p.url) issues.push({ severity: 'critical', type: 'canonical_mismatch', url: p.url, value: p.canonical });
    if (p.noindex) issues.push({ severity: 'critical', type: 'production_noindex', url: p.url, value: p.robots });
    if (p.h1Count !== 1) issues.push({ severity: 'critical', type: 'h1_count', url: p.url, value: p.h1Count });
    if (p.jsonLdCount > 1) issues.push({ severity: 'warning', type: 'duplicate_jsonld', url: p.url, value: p.jsonLdCount });
    if (p.yoastGraphCount !== 1) issues.push({ severity: 'warning', type: 'yoast_graph_count', url: p.url, value: p.yoastGraphCount });
    if (p.missingAltAttribute) issues.push({ severity: 'warning', type: 'images_missing_alt_attribute', url: p.url, value: p.missingAltAttribute });
    if (p.url !== base.href && (inbound.get(p.url) || 0) === 0) issues.push({ severity: 'warning', type: 'orphan_candidate', url: p.url });
    for (const link of p.internalLinks) {
      const target = byUrl.get(link);
      if (target && !target.edge && target.status >= 400) issues.push({ severity: 'critical', type: 'broken_internal_link', url: link, value: target.status });
    }
  }
  return { issues, inbound: Object.fromEntries(inbound) };
}

function markdown(report) {
  const lines = [
    '# NUVANX full-site SEO evidence', '',
    `- Generated: ${report.generatedAt}`,
    `- Discovery: ${report.discoverySource}`,
    `- Pages crawled: ${report.summary.pages}`,
    `- Critical: ${report.summary.critical}`,
    `- Warnings: ${report.summary.warnings}`,
    `- Infrastructure: ${report.summary.infrastructure}`,
    '', '| Severity | Type | URL | Value |', '|---|---|---|---|',
    ...report.issues.map((i) => `| ${i.severity} | ${i.type} | ${i.url || ''} | ${String(i.value ?? i.status ?? '').replace(/\|/g, '\\|')} |`),
  ];
  if (!report.issues.length) lines.push('| — | No findings | — | — |');
  return `${lines.join('\n')}\n`;
}

await fs.mkdir(outDir, { recursive: true });
const browser = await chromium.launch({ headless: true });
const context = await browser.newContext({ locale: 'es-ES', timezoneId: 'Europe/Madrid', userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/126 Safari/537.36' });
await context.route('**/*', async (route) => {
  if (['image', 'media', 'font'].includes(route.request().resourceType())) await route.abort();
  else await route.continue();
});
const page = await context.newPage();
const discovery = await discover(page);
const queue = [...discovery.urls];
const seen = new Set();
const pages = [];
while (queue.length && seen.size < maxUrls) {
  const url = queue.shift();
  if (!url || seen.has(url) || !eligible(url)) continue;
  seen.add(url);
  const result = await navigate(page, url, 2);
  const parsed = parsePage(result);
  pages.push(parsed);
  for (const link of parsed.internalLinks) if (!seen.has(link) && queue.length + seen.size < maxUrls) queue.push(link);
}
await context.close(); await browser.close();
const assessed = assess(pages, discovery.infrastructure);
const report = {
  generatedAt: new Date().toISOString(), baseUrl: base.href, discoverySource: discovery.source,
  sitemaps: discovery.maps, summary: {
    pages: pages.length,
    critical: assessed.issues.filter((i) => i.severity === 'critical').length,
    warnings: assessed.issues.filter((i) => i.severity === 'warning').length,
    infrastructure: assessed.issues.filter((i) => i.severity === 'infrastructure').length,
  }, issues: assessed.issues, pages, inbound: assessed.inbound,
};
await fs.writeFile(path.join(outDir, 'full-site-seo-report.json'), `${JSON.stringify(report, null, 2)}\n`);
await fs.writeFile(path.join(outDir, 'full-site-seo-report.md'), markdown(report));
console.log(JSON.stringify(report.summary));
