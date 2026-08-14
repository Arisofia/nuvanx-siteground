const fs = require('node:fs');
const path = require('node:path');
const { createGscClient, getGscDateRanges, queryGsc } = require('./gsc-client');

async function runFullGscAnalysis() {
  const property = 'https://nuvanx.com/';
  const sc = createGscClient();
  const dates = getGscDateRanges();

  const queryErrors = {};
  let totalQueries = 0;

  const safeQ = async (name, body) => {
    totalQueries += 1;
    try {
      return await queryGsc(sc, property, body);
    } catch (err) {
      const errMsg = err?.message || String(err);
      console.warn(`[WARN] GSC query "${name}" failed:`, errMsg);
      queryErrors[name] = errMsg;
      return [];
    }
  };

  const [
    topQueries,
    topPages,
    deviceBreakdown,
    countryBreakdown,
    last7,
    prev7,
    queryPage,
    queriesMobile,
    queriesDesktop,
  ] = await Promise.all([
    // Full query list (últimos 30 días)
    safeQ('topQueries', { startDate: dates.startDate30, endDate: dates.endDate, dimensions: ['query'], rowLimit: 25 }),
    // Top pages con CTR real
    safeQ('topPages', { startDate: dates.startDate30, endDate: dates.endDate, dimensions: ['page'], rowLimit: 20 }),
    // Breakdown por dispositivo
    safeQ('deviceBreakdown', { startDate: dates.startDate30, endDate: dates.endDate, dimensions: ['device'] }),
    // Breakdown por país/región
    safeQ('countryBreakdown', { startDate: dates.startDate30, endDate: dates.endDate, dimensions: ['country'], rowLimit: 10 }),
    // Últimos 7 días (para tendencia)
    safeQ('last7', { startDate: dates.startDate7, endDate: dates.endDate, dimensions: ['date'] }),
    // 7 días previos (para comparativa)
    safeQ('prev7', { startDate: dates.prev7Start, endDate: dates.prev7End, dimensions: ['date'] }),
    // Query + Page combos (para detectar canibalización y oportunidades)
    safeQ('queryPage', { startDate: dates.startDate30, endDate: dates.endDate, dimensions: ['query', 'page'], rowLimit: 50 }),
    // Queries en móvil
    safeQ('queriesMobile', { startDate: dates.startDate30, endDate: dates.endDate, dimensions: ['query'], rowLimit: 15, dimensionFilterGroups: [{ filters: [{ dimension: 'device', expression: 'MOBILE' }] }] }),
    // Queries en desktop
    safeQ('queriesDesktop', { startDate: dates.startDate30, endDate: dates.endDate, dimensions: ['query'], rowLimit: 15, dimensionFilterGroups: [{ filters: [{ dimension: 'device', expression: 'DESKTOP' }] }] }),
  ]);

  const results = {
    dateRanges: dates,
    topQueries,
    topPages,
    deviceBreakdown,
    countryBreakdown,
    last7,
    prev7,
    queryPage,
    queriesMobile,
    queriesDesktop,
    ...(Object.keys(queryErrors).length > 0 ? { errors: queryErrors } : {})
  };
  fs.mkdirSync(path.join(__dirname, 'artifacts'), { recursive: true });
  fs.writeFileSync(path.join(__dirname, 'artifacts', 'gsc-full-analysis.json'), JSON.stringify(results, null, 2));
  console.log(JSON.stringify(results, null, 2));

  if (Object.keys(queryErrors).length > 0) {
    if (Object.keys(queryErrors).length === totalQueries) {
      console.error('\n[ERROR] All GSC queries failed. Check credentials and property permissions.');
    } else {
      console.warn(`\n[WARN] ${Object.keys(queryErrors).length}/${totalQueries} GSC queries failed.`);
    }
    process.exitCode = 1;
  }
}

runFullGscAnalysis().catch(err => { console.error('GSC Error:', err.message); process.exit(1); });
