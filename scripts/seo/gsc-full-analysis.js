const fs = require('node:fs');
const path = require('node:path');
const { createGscClient, getGscDateRanges, queryGsc } = require('./gsc-client');

async function runFullGscAnalysis() {
  const property = 'https://nuvanx.com/';
  const sc = createGscClient();
  const dates = getGscDateRanges();

  const safeQ = async (name, body) => {
    try {
      return await queryGsc(sc, property, body);
    } catch (err) {
      console.warn(`[WARN] GSC query "${name}" failed:`, err?.message || err);
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
  };
  fs.mkdirSync(path.join(__dirname, 'artifacts'), { recursive: true });
  fs.writeFileSync(path.join(__dirname, 'artifacts', 'gsc-full-analysis.json'), JSON.stringify(results, null, 2));
  console.log(JSON.stringify(results, null, 2));
}

runFullGscAnalysis().catch(err => { console.error('GSC Full Analysis Error:', err?.message); process.exit(1); });
