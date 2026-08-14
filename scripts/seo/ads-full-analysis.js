const { GoogleAdsApi } = require('google-ads-api');
const fs = require('node:fs');
const path = require('node:path');

async function runFullAdsAnalysis() {
  const client = new GoogleAdsApi({
    client_id: process.env.GOOGLE_ADS_CLIENT_ID,
    client_secret: process.env.GOOGLE_ADS_CLIENT_SECRET,
    developer_token: process.env.GOOGLE_ADS_DEVELOPER_TOKEN,
  });

  const customerOptions = {
    customer_id: (process.env.GOOGLE_ADS_CUSTOMER_ID || '').replaceAll('-', ''),
    refresh_token: process.env.GOOGLE_ADS_REFRESH_TOKEN,
  };
  const loginCustomerId = (process.env.GOOGLE_ADS_LOGIN_CUSTOMER_ID || process.env.GOOGLE_ADS_MANAGER_ID || '').replaceAll('-', '');
  if (loginCustomerId) {
    customerOptions.login_customer_id = loginCustomerId;
  }

  const customer = client.Customer(customerOptions);

  async function safeQuery(queryName, gaql) {
    try {
      return await customer.query(gaql);
    } catch (err) {
      console.warn(`[WARN] Query "${queryName}" failed: ${sanitizeAdsError(err)}`);
      return [];
    }
  }

  const [campaigns, assetGroups, demographics, geo] = await Promise.all([
    // Campaign metrics
    safeQuery('campaigns', `
      SELECT
        campaign.id, campaign.name, campaign.status, campaign.advertising_channel_type,
        campaign_budget.amount_micros,
        metrics.impressions, metrics.clicks, metrics.ctr,
        metrics.cost_micros, metrics.conversions, metrics.conversions_value,
        metrics.average_cpc, metrics.average_cpm,
        metrics.all_conversions, metrics.all_conversions_value
      FROM campaign
      WHERE segments.date DURING LAST_30_DAYS
    `),

    // Asset group details
    safeQuery('assetGroups', `
      SELECT
        asset_group.id, asset_group.name, asset_group.status, asset_group.ad_strength,
        campaign.id,
        metrics.impressions, metrics.clicks, metrics.ctr, metrics.cost_micros
      FROM asset_group
      WHERE segments.date DURING LAST_30_DAYS
    `),

    // Demographics breakdown (age/gender/device)
    safeQuery('demographics', `
      SELECT
        age_range_view.resource_name,
        ad_group_criterion.age_range.type,
        metrics.impressions, metrics.clicks, metrics.cost_micros, metrics.ctr
      FROM age_range_view
      WHERE segments.date DURING LAST_30_DAYS
      LIMIT 10
    `),

    // Geographic performance
    safeQuery('geo', `
      SELECT
        geographic_view.country_criterion_id, geographic_view.location_type,
        metrics.impressions, metrics.clicks, metrics.ctr, metrics.cost_micros
      FROM geographic_view
      WHERE geographic_view.location_type = LOCATION_OF_PRESENCE
        AND segments.date DURING LAST_30_DAYS
      LIMIT 10
    `)
  ]);

  const results = { dateRange: 'LAST_30_DAYS', campaigns, assetGroups, demographics, geo };
  fs.mkdirSync(path.join(__dirname, 'artifacts'), { recursive: true });
  fs.writeFileSync(path.join(__dirname, 'artifacts', 'ads-full-analysis.json'), JSON.stringify(results, null, 2));
  console.log(JSON.stringify(results, null, 2));

  if (campaigns.length === 0 && assetGroups.length === 0 && demographics.length === 0 && geo.length === 0) {
    console.warn('\n[WARN] All Google Ads queries returned 0 results or failed. Check credentials and account status.');
  }
}

function sanitizeAdsError(err) {
  if (!err) return 'UNKNOWN_ERROR';
  const code = err.code || err.status || err.name || 'GOOGLE_ADS_API_ERROR';
  let errorDetails;
  if (Array.isArray(err.failure?.errors)) {
    errorDetails = err.failure.errors
      .map((e) => {
        const errCodeObj = e.error_code || {};
        return Object.keys(errCodeObj).map((k) => `${k}.${errCodeObj[k]}`).join(',') || 'ERROR';
      })
      .filter(Boolean)
      .slice(0, 3)
      .join('; ');
  } else {
    errorDetails = err.failure?.message || err.message || 'GENERIC_FAILURE';
  }

  return `code=${code} details=[${String(errorDetails).slice(0, 150)}]`;
}

runFullAdsAnalysis().catch((err) => {
  console.error('ADS_FULL_ANALYSIS=FAIL', sanitizeAdsError(err));
  process.exit(1);
});
