const { google } = require('googleapis');
const fs = require('fs');
const path = require('path');

async function runFullGscAnalysis() {
  const property = 'https://nuvanx.com/';

  const auth = new google.auth.GoogleAuth({
    scopes: ['https://www.googleapis.com/auth/webmasters.readonly']
  });

  const sc = google.searchconsole({ version: 'v1', auth });

  async function q(body) {
    const r = await sc.searchanalytics.query({ siteUrl: property, requestBody: body });
    return r.data.rows || [];
  }

  const [
    topQueries,
    topPages,
    deviceBreakdown,
    countryBreakdown,
    last7,
    prev7,
    queryPage,
    queriesMobile,
    queriesDesktop
  ] = await Promise.all([
    // Full query list (últimos 42 días)
    q({ startDate: '2026-07-01', endDate: '2026-08-11', dimensions: ['query'], rowLimit: 25 }),
    // Top pages con CTR real
    q({ startDate: '2026-07-01', endDate: '2026-08-11', dimensions: ['page'], rowLimit: 20 }),
    // Breakdown por dispositivo
    q({ startDate: '2026-07-01', endDate: '2026-08-11', dimensions: ['device'] }),
    // Breakdown por país/región
    q({ startDate: '2026-07-01', endDate: '2026-08-11', dimensions: ['country'], rowLimit: 10 }),
    // Últimos 7 días (para tendencia)
    q({ startDate: '2026-08-05', endDate: '2026-08-11', dimensions: ['date'] }),
    // 7 días previos (para comparativa)
    q({ startDate: '2026-07-29', endDate: '2026-08-04', dimensions: ['date'] }),
    // Query + Page combos (para detectar canibalización y oportunidades)
    q({ startDate: '2026-07-01', endDate: '2026-08-11', dimensions: ['query', 'page'], rowLimit: 30, dimensionFilterGroups: [{ filters: [{ dimension: 'query', expression: 'madrid', operator: 'contains' }] }] }),
    // Queries en móvil
    q({ startDate: '2026-07-01', endDate: '2026-08-11', dimensions: ['query'], rowLimit: 15, dimensionFilterGroups: [{ filters: [{ dimension: 'device', expression: 'MOBILE' }] }] }),
    // Queries en desktop
    q({ startDate: '2026-07-01', endDate: '2026-08-11', dimensions: ['query'], rowLimit: 15, dimensionFilterGroups: [{ filters: [{ dimension: 'device', expression: 'DESKTOP' }] }] }),
  ]);

  const results = { topQueries, topPages, deviceBreakdown, countryBreakdown, last7, prev7, queryPage, queriesMobile, queriesDesktop };
  fs.mkdirSync(path.join(__dirname, 'artifacts'), { recursive: true });
  fs.writeFileSync(path.join(__dirname, 'artifacts', 'gsc-full-analysis.json'), JSON.stringify(results, null, 2));
  console.log(JSON.stringify(results, null, 2));
}

runFullGscAnalysis().catch(err => { console.error('GSC Full Analysis Error:', err?.message); process.exit(1); });
