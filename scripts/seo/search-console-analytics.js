const fs = require('fs');
const path = require('path');
const { createGscClient, getGscDateRanges, queryGsc } = require('./gsc-client');

async function runGscAnalytics() {
  const property = 'https://nuvanx.com/';
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
