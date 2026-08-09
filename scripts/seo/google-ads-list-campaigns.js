const { GoogleAdsApi } = require('google-ads-api');
const fs = require('fs');

function loadJsonCredentials() {
  const raw = process.env.GOOGLE_ADS_JSON || '';
  const explicitPath = process.env.GOOGLE_ADS_JSON_PATH || '';
  const defaultPath = './google-ads.json';

  for (const candidate of [explicitPath, defaultPath]) {
    if (candidate && fs.existsSync(candidate)) {
      console.log('OAuth client JSON loaded from file');
      return JSON.parse(fs.readFileSync(candidate, 'utf8'));
    }
  }

  if (raw) {
    if (fs.existsSync(raw)) {
      console.log('OAuth client JSON loaded from file');
      return JSON.parse(fs.readFileSync(raw, 'utf8'));
    }
    console.log('OAuth client JSON loaded from environment');
    return JSON.parse(raw);
  }

  return {};
}

function normalizeCustomerId(value) {
  return String(value || '').replace(/-/g, '').trim();
}

async function main() {
  const json = loadJsonCredentials();
  const oauth = json.installed || json.web || json;

  const credentials = {
    client_id: process.env.GOOGLE_ADS_CLIENT_ID || oauth.client_id || '',
    client_secret: process.env.GOOGLE_ADS_CLIENT_SECRET || oauth.client_secret || '',
    developer_token: process.env.GOOGLE_ADS_DEVELOPER_TOKEN || json.developer_token || '',
    customer_id: process.env.GOOGLE_ADS_CUSTOMER_ID || json.customer_id || '',
    login_customer_id: process.env.GOOGLE_ADS_LOGIN_CUSTOMER_ID || json.login_customer_id || '',
    refresh_token: process.env.GOOGLE_ADS_REFRESH_TOKEN || json.refresh_token || '',
  };

  const required = ['client_id', 'client_secret', 'developer_token', 'customer_id', 'refresh_token'];
  const missing = required.filter((key) => !credentials[key]);
  if (missing.length) {
    throw new Error(`Missing Google Ads credential fields: ${missing.join(', ')}`);
  }

  const client = new GoogleAdsApi({
    client_id: credentials.client_id,
    client_secret: credentials.client_secret,
    developer_token: credentials.developer_token,
  });

  const customerOptions = {
    customer_id: normalizeCustomerId(credentials.customer_id),
    refresh_token: credentials.refresh_token,
  };
  if (credentials.login_customer_id) {
    customerOptions.login_customer_id = normalizeCustomerId(credentials.login_customer_id);
  }

  const customer = client.Customer(customerOptions);
  console.log('Attempting read-only GAQL campaign query...');

  const campaigns = await customer.query(`
    SELECT
      campaign.id,
      campaign.name,
      campaign.status,
      campaign.advertising_channel_type
    FROM campaign
    ORDER BY campaign.id
  `);

  console.log(`Campaigns found: ${campaigns.length}`);
  for (const row of campaigns) {
    const campaign = row.campaign || {};
    console.log(`CAMPAIGN id=${campaign.id || ''} status=${campaign.status || ''} channel=${campaign.advertising_channel_type || ''} name=${campaign.name || ''}`);
  }
  console.log('GOOGLE_ADS_READ_ONLY=PASS');
}

main().catch((error) => {
  console.error(`GOOGLE_ADS_READ_ONLY=FAIL ${error.message}`);
  process.exit(1);
});
