const { google } = require('googleapis');
const fs = require('fs');
const path = require('path');

async function runGscAnalytics() {
  const property = 'https://nuvanx.com/';
  console.log('Consultando Google Search Console Search Analytics API para:', property);

  const auth = new google.auth.GoogleAuth({
    scopes: ['https://www.googleapis.com/auth/webmasters.readonly']
  });

  const searchconsole = google.searchconsole({ version: 'v1', auth });

  // 1. Top Search Queries (Últimos 30 días)
  const queriesRes = await searchconsole.searchanalytics.query({
    siteUrl: property,
    requestBody: {
      startDate: '2026-07-01',
      endDate: '2026-08-11',
      dimensions: ['query'],
      rowLimit: 15
    }
  });

  // 2. Top Pages by Traffic
  const pagesRes = await searchconsole.searchanalytics.query({
    siteUrl: property,
    requestBody: {
      startDate: '2026-07-01',
      endDate: '2026-08-11',
      dimensions: ['page'],
      rowLimit: 15
    }
  });

  // 3. Overall Performance Summary
  const summaryRes = await searchconsole.searchanalytics.query({
    siteUrl: property,
    requestBody: {
      startDate: '2026-07-01',
      endDate: '2026-08-11'
    }
  });

  const results = {
    summary: summaryRes.data.rows || [],
    topQueries: queriesRes.data.rows || [],
    topPages: pagesRes.data.rows || []
  };

  console.log('\n=== GSC ANALYTICS RESULTS ===');
  console.log(JSON.stringify(results, null, 2));

  const artifactPath = path.join(__dirname, 'artifacts', 'gsc-analytics-results.json');
  fs.mkdirSync(path.join(__dirname, 'artifacts'), { recursive: true });
  fs.writeFileSync(artifactPath, JSON.stringify(results, null, 2));
  console.log('Saved GSC Analytics results to:', artifactPath);
}

runGscAnalytics().catch(err => {
  console.error('GSC Analytics Error:', err?.message || err);
  process.exit(1);
});
