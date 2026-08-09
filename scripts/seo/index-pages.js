#!/usr/bin/env node

/**
 * Inspect nuvanx.com sitemap URLs through the Google Search Console URL Inspection API.
 *
 * Important: Search Console does not provide a general-purpose "submit URL for indexing"
 * method. This script inspects the indexed state only. Google's separate Indexing API is
 * restricted to JobPosting and BroadcastEvent pages and is not appropriate for NUVANX
 * treatment/clinic pages.
 */

const { google } = require('googleapis');
const fs = require('fs');
const path = require('path');

const args = process.argv.slice(2);
let property = '';
let baseUrl = '';
let maxUrls = 200;

for (let i = 0; i < args.length; i++) {
  if (args[i] === '--property' && args[i + 1]) {
    property = args[++i];
  } else if (args[i] === '--url' && args[i + 1]) {
    baseUrl = args[++i];
  } else if (args[i] === '--max-urls' && args[i + 1]) {
    maxUrls = Number.parseInt(args[++i], 10);
  }
}

if (!property || !baseUrl) {
  console.error('Error: --property and --url are required');
  console.error('Usage: node index-pages.js --property <property> --url <url> [--max-urls 200]');
  process.exit(1);
}

if (!Number.isFinite(maxUrls) || maxUrls < 1 || maxUrls > 500) {
  console.error('Error: --max-urls must be between 1 and 500');
  process.exit(1);
}

const normalizedBase = baseUrl.replace(/\/$/, '');
const sitemapIndexUrl = `${normalizedBase}/sitemap_index.xml`;
const sleep = ms => new Promise(resolve => setTimeout(resolve, ms));

function decodeXml(value) {
  return value
    .replace(/&lt;/g, '<')
    .replace(/&gt;/g, '>')
    .replace(/&quot;/g, '"')
    .replace(/&#39;/g, "'")
    .replace(/&amp;/g, '&');
}

function extractLocs(xml) {
  return [...xml.matchAll(/<loc>\s*([^<]+?)\s*<\/loc>/gi)].map(match => decodeXml(match[1].trim()));
}

async function fetchTextWithRetry(url, attempts = 6) {
  let lastStatus = 0;
  let lastError = null;

  for (let attempt = 1; attempt <= attempts; attempt++) {
    try {
      const response = await fetch(url, {
        headers: {
          'user-agent': 'Mozilla/5.0 (compatible; NUVANX-SEO-Audit/1.0)'
        },
        redirect: 'follow'
      });
      lastStatus = response.status;

      if (response.status === 200) {
        return await response.text();
      }

      if (response.status === 202 || response.status === 429 || response.status >= 500) {
        await sleep(attempt * 2000);
        continue;
      }

      const fatal = new Error(`HTTP ${response.status} for ${url}`);
      fatal.nonRetryable = true;
      throw fatal;
    } catch (error) {
      if (error?.nonRetryable) {
        throw error;
      }
      lastError = error;
      if (attempt < attempts) {
        await sleep(attempt * 2000);
      }
    }
  }

  throw new Error(lastError?.message || `Unable to fetch ${url}; last HTTP status ${lastStatus}`);
}

async function discoverCanonicalUrls() {
  console.log(`Fetching sitemap index: ${sitemapIndexUrl}`);
  const indexXml = await fetchTextWithRetry(sitemapIndexUrl);
  const indexEntries = extractLocs(indexXml);
  const childSitemapPattern = /\.xml(?:\.gz)?(?:$|\?)/i;
  const childSitemaps = indexEntries.filter(url => childSitemapPattern.test(url));

  for (const entry of indexEntries) {
    if (!childSitemapPattern.test(entry)) {
      console.warn(`Skipping non-sitemap index entry: ${entry}`);
    }
  }

  if (childSitemaps.length === 0) {
    throw new Error(`No child sitemaps found in sitemap_index.xml (index contained ${indexEntries.length} entries)`);
  }

  console.log(`Child sitemaps: ${childSitemaps.length}`);
  const urls = new Set();

  for (const sitemapUrl of childSitemaps) {
    const xml = await fetchTextWithRetry(sitemapUrl);
    const locs = extractLocs(xml);
    console.log(`  ${sitemapUrl}: ${locs.length} URLs`);

    for (const candidate of locs) {
      try {
        const parsed = new URL(candidate);
        if (parsed.origin === new URL(normalizedBase).origin) {
          urls.add(parsed.href);
        }
      } catch {
        console.warn(`Ignoring invalid sitemap URL: ${candidate}`);
      }
    }
  }

  const discovered = [...urls].slice(0, maxUrls);
  if (discovered.length === 0) {
    throw new Error('Sitemap discovery returned zero canonical URLs');
  }

  return discovered;
}

function simplifyInspection(url, inspectionResult) {
  const status = inspectionResult?.indexStatusResult || {};
  return {
    url,
    verdict: status.verdict || 'VERDICT_UNSPECIFIED',
    coverageState: status.coverageState || '',
    indexingState: status.indexingState || '',
    robotsTxtState: status.robotsTxtState || '',
    pageFetchState: status.pageFetchState || '',
    lastCrawlTime: status.lastCrawlTime || '',
    crawledAs: status.crawledAs || '',
    userCanonical: status.userCanonical || '',
    googleCanonical: status.googleCanonical || '',
    referringUrls: status.referringUrls || [],
    sitemap: status.sitemap || []
  };
}

async function inspectAllPages() {
  const credentialsPath = path.join(__dirname, 'credentials.json');
  if (!fs.existsSync(credentialsPath)) {
    throw new Error('credentials.json not found');
  }

  const credentials = JSON.parse(fs.readFileSync(credentialsPath, 'utf8'));
  const auth = new google.auth.GoogleAuth({
    credentials,
    scopes: ['https://www.googleapis.com/auth/webmasters.readonly']
  });

  const searchconsole = google.searchconsole({ version: 'v1', auth });
  const urls = await discoverCanonicalUrls();

  console.log(`Search Console property: ${property}`);
  console.log(`Canonical URLs discovered: ${urls.length}`);

  const results = [];
  let apiErrors = 0;
  let pass = 0;
  let notIndexed = 0;
  let warnings = 0;

  for (const url of urls) {
    try {
      const response = await searchconsole.urlInspection.index.inspect({
        requestBody: {
          inspectionUrl: url,
          siteUrl: property,
          languageCode: 'es-ES'
        }
      });

      const row = simplifyInspection(url, response.data.inspectionResult);
      const indexed = row.verdict === 'PASS';
      const blocked = row.indexingState === 'BLOCKED_BY_META_TAG' || row.indexingState === 'BLOCKED_BY_HTTP_HEADER' || row.robotsTxtState === 'DISALLOWED';
      const canonicalMismatch = row.googleCanonical && row.userCanonical && row.googleCanonical !== row.userCanonical;

      if (indexed && !blocked && !canonicalMismatch) {
        pass++;
      } else if (!indexed) {
        notIndexed++;
      }
      if (blocked || canonicalMismatch) {
        warnings++;
      }

      results.push({ ...row, apiStatus: 'ok', blocked, canonicalMismatch });
      console.log(`${indexed ? 'PASS' : 'NOT_INDEXED'} ${url} coverage="${row.coverageState}" crawl="${row.lastCrawlTime || 'none'}"`);
    } catch (error) {
      apiErrors++;
      results.push({ url, apiStatus: 'error', error: error.message });
      console.error(`API_ERROR ${url}: ${error.message}`);
    }

    await sleep(150);
  }

  const artifactsDir = path.join(__dirname, 'artifacts');
  fs.mkdirSync(artifactsDir, { recursive: true });
  fs.writeFileSync(
    path.join(artifactsDir, 'indexing-results.json'),
    JSON.stringify({
      generatedAt: new Date().toISOString(),
      property,
      sitemapIndexUrl,
      totals: {
        urls: urls.length,
        pass,
        notIndexed,
        warnings,
        apiErrors
      },
      results
    }, null, 2)
  );

  const errorRatio = urls.length > 0 ? apiErrors / urls.length : 0;
  const maxErrorRatio = 0.2;

  console.log('\n=== Search Console URL Inspection Summary ===');
  console.log(`TOTAL_URLS=${urls.length}`);
  console.log(`INDEXED_PASS=${pass}`);
  console.log(`NOT_INDEXED=${notIndexed}`);
  console.log(`INDEX_WARNINGS=${warnings}`);
  console.log(`API_ERRORS=${apiErrors}`);
  console.log(`API_ERROR_RATIO=${errorRatio.toFixed(3)}`);
  console.log('INSPECTION_COMPLETED=true');

  // Tolerate a small ratio of transient API errors (quota/5xx). Only fail the run
  // when the error ratio exceeds the threshold, which signals a systemic problem.
  if (apiErrors > 0 && errorRatio > maxErrorRatio) {
    console.error(`::error::API error ratio ${errorRatio.toFixed(3)} exceeds threshold ${maxErrorRatio}`);
    process.exitCode = 2;
  }
}

inspectAllPages().catch(error => {
  console.error(`Fatal error: ${error.message}`);
  console.log('INSPECTION_COMPLETED=false');
  process.exit(1);
});
