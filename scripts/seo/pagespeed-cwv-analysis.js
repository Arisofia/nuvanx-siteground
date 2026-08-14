const { google } = require('googleapis');
const fs = require('fs');
const path = require('path');

async function runPagespeedAnalysis() {
  const apiKey = process.env.GOOGLE_PAGESPEED_API_KEY;
  const urls = [
    'https://nuvanx.com/',
    'https://nuvanx.com/clinicas-de-medicina-estetica-nuvanx/medicina-estetica-goya-barrio-salamanca/',
    'https://nuvanx.com/endolift-facial-papada-mandibula/',
    'https://nuvanx.com/medicina-estetica/',
    'https://nuvanx.com/madrid/valoracion/'
  ];

  const strategies = ['mobile', 'desktop'];
  const results = {};

  for (const url of urls) {
    results[url] = {};
    for (const strategy of strategies) {
      try {
        const endpoint = `https://www.googleapis.com/pagespeedonline/v5/runPagespeed?url=${encodeURIComponent(url)}&strategy=${strategy}&key=${apiKey}&category=performance&category=seo&category=accessibility&category=best-practices`;
        const res = await fetch(endpoint);
        const data = await res.json();

        const lcp = data.lighthouseResult?.audits?.['largest-contentful-paint']?.numericValue;
        const cls = data.lighthouseResult?.audits?.['cumulative-layout-shift']?.numericValue;
        const tbt = data.lighthouseResult?.audits?.['total-blocking-time']?.numericValue;
        const fcp = data.lighthouseResult?.audits?.['first-contentful-paint']?.numericValue;
        const ttfb = data.lighthouseResult?.audits?.['server-response-time']?.numericValue;
        const perf = data.lighthouseResult?.categories?.performance?.score;
        const seo = data.lighthouseResult?.categories?.seo?.score;
        const a11y = data.lighthouseResult?.categories?.accessibility?.score;
        const bp = data.lighthouseResult?.categories?.['best-practices']?.score;

        results[url][strategy] = { lcp: Math.round(lcp), cls: cls?.toFixed(3), tbt: Math.round(tbt), fcp: Math.round(fcp), ttfb: Math.round(ttfb), perf: Math.round(perf * 100), seo: Math.round(seo * 100), a11y: Math.round(a11y * 100), bp: Math.round(bp * 100) };
        console.log(`[${strategy.toUpperCase()}] ${url}`);
        console.log(`  Perf: ${Math.round(perf*100)} | SEO: ${Math.round(seo*100)} | A11y: ${Math.round(a11y*100)} | LCP: ${Math.round(lcp)}ms | CLS: ${cls?.toFixed(3)} | TBT: ${Math.round(tbt)}ms | TTFB: ${Math.round(ttfb)}ms`);
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
