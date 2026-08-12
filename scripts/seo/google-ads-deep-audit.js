const { GoogleAdsApi } = require('google-ads-api');
const fs = require('fs');
const path = require('path');

async function runDeepAudit() {
  const clientId = process.env.GOOGLE_ADS_CLIENT_ID;
  const clientSecret = process.env.GOOGLE_ADS_CLIENT_SECRET;
  const devToken = process.env.GOOGLE_ADS_DEVELOPER_TOKEN;
  const refreshToken = process.env.GOOGLE_ADS_REFRESH_TOKEN;
  const customerId = (process.env.GOOGLE_ADS_CUSTOMER_ID || '').replace(/-/g, '');

  if (!clientId || !clientSecret || !devToken || !refreshToken || !customerId) {
    console.error('Missing required Google Ads credential parameters');
    process.exit(1);
  }

  const client = new GoogleAdsApi({
    client_id: clientId,
    client_secret: clientSecret,
    developer_token: devToken,
  });

  const customer = client.Customer({
    customer_id: customerId,
    refresh_token: refreshToken,
  });

  console.log('Fetching Google Ads details for Customer ID:', customerId);

  // 1. Fetch Campaigns with performance metrics
  const campaigns = await customer.query(`
    SELECT
      campaign.id,
      campaign.name,
      campaign.status,
      campaign.advertising_channel_type,
      campaign_budget.amount_micros,
      metrics.impressions,
      metrics.clicks,
      metrics.ctr,
      metrics.cost_micros,
      metrics.conversions
    FROM campaign
  `);

  // 2. Fetch Performance Max Asset Groups
  const assetGroups = await customer.query(`
    SELECT
      asset_group.id,
      asset_group.name,
      asset_group.status,
      campaign.id,
      metrics.impressions,
      metrics.clicks,
      metrics.ctr,
      metrics.cost_micros
    FROM asset_group
  `);

  const results = { campaigns, assetGroups };
  console.log('\n=== GOOGLE ADS AUDIT RESULTS ===');
  console.log(JSON.stringify(results, null, 2));

  const artifactPath = path.join(__dirname, 'artifacts', 'ads-audit-results.json');
  fs.mkdirSync(path.join(__dirname, 'artifacts'), { recursive: true });
  fs.writeFileSync(artifactPath, JSON.stringify(results, null, 2));
  console.log('\nSaved Ads Audit results to:', artifactPath);
}

runDeepAudit().catch(err => {
  console.error('Deep Audit Error:', err?.message || err);
  process.exit(1);
});
