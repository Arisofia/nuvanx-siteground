const fs = require('fs');
const path = require('path');
const { createGscClient, getGscDateRanges, queryGsc } = require('./gsc-client');

async function runFullGscAnalysis() {
  const property = 'https://nuvanx.com/';
  const sc = createGscClient();
  const dates = getGscDateRanges();

  const q = (body) => queryGsc(sc, property, body);

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
    q({ startDate: dates.startDate30, endDate: dates.endDate, dimensions: ['query'], rowLimit: 25 }),
    // Top pages con CTR real
    q({ startDate: dates.startDate30, endDate: dates.endDate, dimensions: ['page'], rowLimit: 20 }),
    // Breakdown por dispositivo
    q({ startDate: dates.startDate30, endDate: dates.endDate, dimensions: ['device'] }),
    // Breakdown por país/región
    q({ startDate: dates.startDate30, endDate: dates.endDate, dimensions: ['country'], rowLimit: 10 }),
    // Últimos 7 días (para tendencia)
    q({ startDate: dates.startDate7, endDate: dates.endDate, dimensions: ['date'] }),
    // 7 días previos (para comparativa)
    q({ startDate: dates.prev7Start, endDate: dates.prev7End, dimensions: ['date'] }),
    // Query + Page combos (para detectar canibalización y oportunidades)
    q({ startDate: dates.startDate30, endDate: dates.endDate, dimensions: ['query', 'page'], rowLimit: 30, dimensionFilterGroups: [{ filters: [{ dimension: 'query', expression: 'madrid', operator: 'contains' }] }] }),
    // Queries en móvil
    q({ startDate: dates.startDate30, endDate: dates.endDate, dimensions: ['query'], rowLimit: 15, dimensionFilterGroups: [{ filters: [{ dimension: 'device', expression: 'MOBILE' }] }] }),
    // Queries en desktop
    q({ startDate: dates.startDate30, endDate: dates.endDate, dimensions: ['query'], rowLimit: 15, dimensionFilterGroups: [{ filters: [{ dimension: 'device', expression: 'DESKTOP' }] }] }),
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
