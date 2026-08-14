const { GoogleAdsApi } = require('google-ads-api');
const fs = require('fs');
const path = require('path');

async function runFullAdsAnalysis() {
  const client = new GoogleAdsApi({
    client_id: process.env.GOOGLE_ADS_CLIENT_ID,
    client_secret: process.env.GOOGLE_ADS_CLIENT_SECRET,
    developer_token: process.env.GOOGLE_ADS_DEVELOPER_TOKEN,
  });

  const customer = client.Customer({
    customer_id: (process.env.GOOGLE_ADS_CUSTOMER_ID || '').replace(/-/g, ''),
    refresh_token: process.env.GOOGLE_ADS_REFRESH_TOKEN,
  });

  const [campaigns, assetGroups, demographics, geo] = await Promise.all([
    // Campaign metrics
    customer.query(`
      SELECT
        campaign.id, campaign.name, campaign.status, campaign.advertising_channel_type,
        campaign_budget.amount_micros,
        metrics.impressions, metrics.clicks, metrics.ctr,
        metrics.cost_micros, metrics.conversions, metrics.conversions_value,
        metrics.average_cpc, metrics.average_cpm,
        metrics.all_conversions, metrics.all_conversions_value
      FROM campaign
    `),

    // Asset group details
    customer.query(`
      SELECT
        asset_group.id, asset_group.name, asset_group.status, asset_group.ad_strength,
        campaign.id,
        metrics.impressions, metrics.clicks, metrics.ctr, metrics.cost_micros
      FROM asset_group
    `),

    // Demographics breakdown (age/gender/device)
    customer.query(`
      SELECT
        age_range_view.resource_name,
        ad_group_criterion.age_range.type,
        metrics.impressions, metrics.clicks, metrics.cost_micros, metrics.ctr
      FROM age_range_view
      LIMIT 10
    `),

    // Geographic performance
    customer.query(`
      SELECT
        geographic_view.country_criterion_id, geographic_view.location_type,
        metrics.impressions, metrics.clicks, metrics.ctr, metrics.cost_micros
      FROM geographic_view
      WHERE geographic_view.location_type = 'LOCATION_OF_PRESENCE'
      LIMIT 10
    `)
  ]);

  const results = { campaigns, assetGroups, demographics, geo };
  fs.mkdirSync(path.join(__dirname, 'artifacts'), { recursive: true });
  fs.writeFileSync(path.join(__dirname, 'artifacts', 'ads-full-analysis.json'), JSON.stringify(results, null, 2));
  console.log(JSON.stringify(results, null, 2));
}

runFullAdsAnalysis().catch(err => { console.error('Ads Full Analysis Error:', err?.message || err); process.exit(1); });
