const fs = require('node:fs');
const path = require('node:path');
const { createGscClient, getGscDateRanges, queryGsc } = require('./gsc-client');

function sanitizeGscError(err) {
  if (!err) return 'UNKNOWN_ERROR';
  const code = String(err.code || err.status || 'GSC_API_ERROR').replace(/[^a-zA-Z0-9_]/g, '');
  const reason = err.errors?.[0]?.reason || err.response?.data?.error?.status || '';
  const cleanReason = String(reason).replace(/[^a-zA-Z0-9_]/g, '');
  return `code=${code}${cleanReason ? ` reason=${cleanReason}` : ''}`;
}

async function runGscAnalytics() {
  const property = process.env.GSC_SITE_URL || process.env.WORDPRESS_URL || 'https://nuvanx.com/';
  console.log('Consultando Google Search Console Search Analytics API para:', property);

  const searchconsole = createGscClient();
  const dates = getGscDateRanges();

  // 1. Top Search Queries (Últimos 30 días)
  const topQueries = await queryGsc(searchconsole, property, {
    startDate: dates.startDate30,
    endDate: dates.endDate,
    dimensions: ['query'],
    rowLimit: 15,
  });

  // 2. Top Pages by Traffic
  const topPages = await queryGsc(searchconsole, property, {
    startDate: dates.startDate30,
    endDate: dates.endDate,
    dimensions: ['page'],
    rowLimit: 15,
  });

  // 3. Overall Performance Summary
  const summary = await queryGsc(searchconsole, property, {
    startDate: dates.startDate30,
    endDate: dates.endDate,
  });

  const results = {
    dateRange: { startDate: dates.startDate30, endDate: dates.endDate },
    summary,
    topQueries,
    topPages,
  };

  console.log('\n=== GSC ANALYTICS SUMMARY ===');
  console.log(`  Top queries retrieved: ${topQueries.length}`);
  console.log(`  Top pages retrieved: ${topPages.length}`);
  console.log(`  Summary rows: ${summary.length}`);

  const artifactPath = path.join(__dirname, 'artifacts', 'gsc-analytics-results.json');
  fs.mkdirSync(path.join(__dirname, 'artifacts'), { recursive: true });
  fs.writeFileSync(artifactPath, JSON.stringify(results, null, 2));
  console.log('Saved GSC Analytics results to:', artifactPath);
}

runGscAnalytics().catch(err => {
  console.error('GSC_ANALYTICS=FAIL', sanitizeGscError(err));
  process.exit(1);
});
