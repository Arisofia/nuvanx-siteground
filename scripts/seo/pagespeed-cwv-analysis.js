const fs = require('fs');
const path = require('path');
const https = require('https');

function fetchJson(url, timeoutMs = 30000) {
  return new Promise((resolve, reject) => {
    const req = https.get(url, (res) => {
      res.setEncoding('utf8');
      let body = '';
      res.on('data', (chunk) => { body += chunk; });
      res.on('end', () => {
        if (res.statusCode && (res.statusCode < 200 || res.statusCode >= 300)) {
          return reject(new Error(`HTTP ${res.statusCode}: ${body.slice(0, 200)}`));
        }
        try {
          resolve(JSON.parse(body));
        } catch (parseErr) {
          reject(parseErr);
        }
      });
    });

    req.setTimeout(timeoutMs, () => {
      req.destroy(new Error(`Request timed out after ${timeoutMs}ms: ${url}`));
    });

    req.on('error', reject);
  });
}

async function runPagespeedAnalysis() {
  const apiKey = process.env.GOOGLE_PAGESPEED_API_KEY || process.env.PAGESPEED_API_KEY;
  if (!apiKey) {
    console.error('ERROR: GOOGLE_PAGESPEED_API_KEY is required to run PageSpeed analysis.');
    process.exit(1);
  }

  const urls = [
    'https://nuvanx.com/',
    'https://nuvanx.com/clinicas-de-medicina-estetica-nuvanx/medicina-estetica-goya-barrio-salamanca/',
    'https://nuvanx.com/endolift-facial-papada-mandibula/',
    'https://nuvanx.com/medicina-estetica/',
    'https://nuvanx.com/madrid/valoracion/'
  ];

  const strategies = ['mobile', 'desktop'];
  const results = {};

  const parseScore = (val) => typeof val === 'number' && !isNaN(val) ? Math.round(val * 100) : null;
  const parseNum   = (val) => typeof val === 'number' && !isNaN(val) ? Math.round(val) : null;
  const parseCls   = (val) => typeof val === 'number' && !isNaN(val) ? Number(val.toFixed(3)) : null;

  for (const url of urls) {
    results[url] = {};
    for (const strategy of strategies) {
      try {
        const endpoint = `https://www.googleapis.com/pagespeedonline/v5/runPagespeed?url=${encodeURIComponent(url)}&strategy=${strategy}&key=${apiKey}&category=performance&category=seo&category=accessibility&category=best-practices`;
        const data = await fetchJson(endpoint);

        const lcpVal  = data.lighthouseResult?.audits?.['largest-contentful-paint']?.numericValue;
        const clsVal  = data.lighthouseResult?.audits?.['cumulative-layout-shift']?.numericValue;
        const tbtVal  = data.lighthouseResult?.audits?.['total-blocking-time']?.numericValue;
        const fcpVal  = data.lighthouseResult?.audits?.['first-contentful-paint']?.numericValue;
        const ttfbVal = data.lighthouseResult?.audits?.['server-response-time']?.numericValue;
        const perfVal = data.lighthouseResult?.categories?.performance?.score;
        const seoVal  = data.lighthouseResult?.categories?.seo?.score;
        const a11yVal = data.lighthouseResult?.categories?.accessibility?.score;
        const bpVal   = data.lighthouseResult?.categories?.['best-practices']?.score;

        const metrics = {
          lcp:  parseNum(lcpVal),
          cls:  parseCls(clsVal),
          tbt:  parseNum(tbtVal),
          fcp:  parseNum(fcpVal),
          ttfb: parseNum(ttfbVal),
          perf: parseScore(perfVal),
          seo:  parseScore(seoVal),
          a11y: parseScore(a11yVal),
          bp:   parseScore(bpVal)
        };

        results[url][strategy] = metrics;
        console.log(`[${strategy.toUpperCase()}] ${url}`);
        console.log(`  Perf: ${metrics.perf ?? 'N/A'} | SEO: ${metrics.seo ?? 'N/A'} | A11y: ${metrics.a11y ?? 'N/A'} | LCP: ${metrics.lcp ?? 'N/A'}ms | CLS: ${metrics.cls ?? 'N/A'} | TBT: ${metrics.tbt ?? 'N/A'}ms | TTFB: ${metrics.ttfb ?? 'N/A'}ms`);
      } catch (err) {
        results[url][strategy] = { error: err.message };
        console.error(`Error [${strategy}] ${url}:`, err.message);
      }
    }
  }

  fs.mkdirSync(path.join(__dirname, 'artifacts'), { recursive: true });
  fs.writeFileSync(path.join(__dirname, 'artifacts', 'pagespeed-results.json'), JSON.stringify(results, null, 2));
  console.log('\nSaved PageSpeed results to artifacts/pagespeed-results.json');
}

runPagespeedAnalysis().catch(err => { console.error('PageSpeed Error:', err.message); process.exit(1); });
