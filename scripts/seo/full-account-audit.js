const fs = require('fs');
const path = require('path');

// 1. Load credentials from .env.local
const envContent = fs.readFileSync('/Users/MARIA/Desktop/nuvanx-siteground/.env.local', 'utf8');
envContent.split('\n').forEach(line => {
  const m = line.match(/^export\s+([A-Z0-9_]+)=['\"]?(.*?)['\"]?$/);
  if (m) process.env[m[1]] = m[2];
});

const { GoogleAdsApi } = require('./node_modules/google-ads-api');

const client = new GoogleAdsApi({
  client_id: process.env.GOOGLE_ADS_CLIENT_ID,
  client_secret: process.env.GOOGLE_ADS_CLIENT_SECRET,
  developer_token: process.env.GOOGLE_ADS_DEVELOPER_TOKEN,
});

const customerId = process.env.GOOGLE_ADS_CUSTOMER_ID.replace(/-/g, '');
const customer = client.Customer({
  customer_id: customerId,
  refresh_token: process.env.GOOGLE_ADS_REFRESH_TOKEN,
});

async function audit() {
  console.log('=== STARTING FULL GOOGLE ADS AUDIT ===');
  console.log('Customer ID:', customerId);

  // 1. Customer General Info
  const customerInfo = await customer.query(`
    SELECT
      customer.id,
      customer.descriptive_name,
      customer.currency_code,
      customer.time_zone,
      customer.status
    FROM customer
    LIMIT 1
  `);

  // 2. Conversion Actions
  const conversionActions = await customer.query(`
    SELECT
      conversion_action.id,
      conversion_action.name,
      conversion_action.status,
      conversion_action.type,
      conversion_action.category,
      conversion_action.primary_for_goal,
      conversion_action.include_in_conversions_metric,
      conversion_action.counting_type,
      conversion_action.attribution_model_settings.attribution_model,
      conversion_action.tag_snippets
    FROM conversion_action
  `);

  // 3. Campaigns (Last 30 Days)
  const campaigns30d = await customer.query(`
    SELECT
      campaign.id,
      campaign.name,
      campaign.status,
      campaign.advertising_channel_type,
      campaign.bidding_strategy_type,
      campaign_budget.amount_micros,
      metrics.impressions,
      metrics.clicks,
      metrics.ctr,
      metrics.average_cpc,
      metrics.cost_micros,
      metrics.conversions,
      metrics.cost_per_conversion,
      metrics.conversions_from_interactions_rate,
      metrics.search_impression_share,
      metrics.search_budget_lost_impression_share,
      metrics.search_rank_lost_impression_share
    FROM campaign
    WHERE segments.date DURING LAST_30_DAYS
  `);

  // 4. All Campaigns (All Time / Status)
  const allCampaigns = await customer.query(`
    SELECT
      campaign.id,
      campaign.name,
      campaign.status,
      campaign.advertising_channel_type,
      campaign.bidding_strategy_type,
      campaign_budget.amount_micros,
      metrics.impressions,
      metrics.clicks,
      metrics.cost_micros,
      metrics.conversions
    FROM campaign
  `);

  // 5. Ad Groups (Last 30 Days)
  const adGroups30d = await customer.query(`
    SELECT
      ad_group.id,
      ad_group.name,
      ad_group.status,
      ad_group.type,
      campaign.id,
      campaign.name,
      metrics.impressions,
      metrics.clicks,
      metrics.ctr,
      metrics.cost_micros,
      metrics.conversions
    FROM ad_group
    WHERE segments.date DURING LAST_30_DAYS
  `);

  // 6. Keywords & Quality Scores
  let keywords = [];
  try {
    keywords = await customer.query(`
      SELECT
        ad_group_criterion.criterion_id,
        ad_group_criterion.keyword.text,
        ad_group_criterion.keyword.match_type,
        ad_group_criterion.status,
        ad_group_criterion.quality_info.quality_score,
        ad_group_criterion.quality_info.search_predicted_ctr,
        ad_group_criterion.quality_info.creative_quality_score,
        ad_group_criterion.quality_info.post_click_quality_score,
        ad_group.name,
        campaign.name,
        metrics.impressions,
        metrics.clicks,
        metrics.cost_micros,
        metrics.conversions
      FROM keyword_view
      WHERE segments.date DURING LAST_30_DAYS
    `);
  } catch (e) {
    console.log('Keyword View Query note:', e.message);
  }

  // 7. Search Terms (Actual User Queries)
  let searchTerms = [];
  try {
    searchTerms = await customer.query(`
      SELECT
        search_term_view.search_term,
        search_term_view.status,
        campaign.name,
        ad_group.name,
        metrics.impressions,
        metrics.clicks,
        metrics.ctr,
        metrics.average_cpc,
        metrics.cost_micros,
        metrics.conversions
      FROM search_term_view
      WHERE segments.date DURING LAST_30_DAYS
      ORDER BY metrics.cost_micros DESC
      LIMIT 150
    `);
  } catch (e) {
    console.log('Search Term View Query note:', e.message);
  }

  // 8. Negative Keywords (Campaign Level)
  let campaignNegatives = [];
  try {
    campaignNegatives = await customer.query(`
      SELECT
        campaign_criterion.campaign,
        campaign_criterion.criterion_id,
        campaign_criterion.keyword.text,
        campaign_criterion.keyword.match_type,
        campaign_criterion.negative
      FROM campaign_criterion
      WHERE campaign_criterion.type = 'KEYWORD' AND campaign_criterion.negative = TRUE
    `);
  } catch (e) {
    console.log('Campaign Negatives query note:', e.message);
  }

  // 9. Ads / RSA Content
  let ads = [];
  try {
    ads = await customer.query(`
      SELECT
        ad_group_ad.ad.id,
        ad_group_ad.ad.name,
        ad_group_ad.ad.type,
        ad_group_ad.ad.final_urls,
        ad_group_ad.ad.responsive_search_ad.headlines,
        ad_group_ad.ad.responsive_search_ad.descriptions,
        ad_group_ad.ad.responsive_search_ad.path1,
        ad_group_ad.ad.responsive_search_ad.path2,
        ad_group_ad.ad_strength,
        ad_group_ad.status,
        ad_group.name,
        campaign.name,
        metrics.impressions,
        metrics.clicks,
        metrics.cost_micros,
        metrics.conversions
      FROM ad_group_ad
      WHERE segments.date DURING LAST_30_DAYS
    `);
  } catch (e) {
    console.log('Ad Group Ad query note:', e.message);
  }

  // 10. Devices Breakdown
  let devices = [];
  try {
    devices = await customer.query(`
      SELECT
        segments.device,
        metrics.impressions,
        metrics.clicks,
        metrics.ctr,
        metrics.cost_micros,
        metrics.conversions,
        metrics.cost_per_conversion
      FROM campaign
      WHERE segments.date DURING LAST_30_DAYS
    `);
  } catch (e) {
    console.log('Devices query note:', e.message);
  }

  // 11. Geo Locations
  let geoLocations = [];
  try {
    geoLocations = await customer.query(`
      SELECT
        geographic_view.country_criterion_id,
        geographic_view.location_type,
        metrics.impressions,
        metrics.clicks,
        metrics.cost_micros,
        metrics.conversions
      FROM geographic_view
      WHERE segments.date DURING LAST_30_DAYS
      ORDER BY metrics.cost_micros DESC
      LIMIT 50
    `);
  } catch (e) {
    console.log('Geo query note:', e.message);
  }

  const dump = {
    customerInfo,
    conversionActions,
    campaigns30d,
    allCampaigns,
    adGroups30d,
    keywords,
    searchTerms,
    campaignNegatives,
    ads,
    devices,
    geoLocations,
  };

  const outPath = path.join(__dirname, 'raw-ads-audit-data.json');
  fs.writeFileSync(outPath, JSON.stringify(dump, null, 2));
  console.log('SUCCESS: Full audit data extracted and saved to', outPath);
}

audit().catch(err => {
  console.error('Audit Error:', err);
  process.exit(1);
});
