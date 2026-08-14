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

async function runFullGscAnalysis() {
  const property = process.env.GSC_SITE_URL || process.env.WORDPRESS_URL || 'https://nuvanx.com/';
  const sc = createGscClient();
  const dates = getGscDateRanges();

  const queryErrors = {};
  let totalQueries = 0;

  const safeQ = async (name, body) => {
    totalQueries += 1;
    try {
      return await queryGsc(sc, property, body);
    } catch (err) {
      const sanitized = sanitizeGscError(err);
      console.warn(`[WARN] GSC query "${name}" failed: ${sanitized}`);
      queryErrors[name] = sanitized;
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

  console.log('\nSearch Console Analysis complete:');
  console.log(`  Device breakdown rows: ${deviceBreakdown.length}`);
  console.log(`  Country breakdown rows: ${countryBreakdown.length}`);
  console.log(`  Last 7 days rows: ${last7.length}`);
  console.log(`  Previous 7 days rows: ${prev7.length}`);
  console.log(`  Query/Page rows: ${queryPage.length}`);
  console.log(`  Mobile queries: ${queriesMobile.length}`);
  console.log(`  Desktop queries: ${queriesDesktop.length}`);
  console.log('  Full results saved to scripts/seo/artifacts/gsc-full-analysis.json');

  if (Object.keys(queryErrors).length > 0) {
    if (Object.keys(queryErrors).length === totalQueries) {
      console.error('\n[ERROR] All GSC queries failed. Check credentials and property permissions.');
    } else {
      console.warn(`\n[WARN] ${Object.keys(queryErrors).length}/${totalQueries} GSC queries failed.`);
    }
    process.exitCode = 1;
  }
}

runFullGscAnalysis().catch(err => {
  console.error('GSC_FULL_ANALYSIS=FAIL', sanitizeGscError(err));
  process.exit(1);
});
