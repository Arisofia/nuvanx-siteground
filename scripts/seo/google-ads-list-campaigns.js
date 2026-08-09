const { GoogleAdsApi } = require('google-ads-api');
const fs = require('fs');

function loadJsonCredentials() {
  const credentialsPath = process.env.GOOGLE_ADS_JSON || './google-ads.json';
  if (fs.existsSync(credentialsPath)) {
    try {
      return JSON.parse(fs.readFileSync(credentialsPath, 'utf8'));
    } catch {
      console.log('Credentials path is not a valid JSON file');
    }
  } else if (process.env.GOOGLE_ADS_JSON) {
    try {
      return JSON.parse(process.env.GOOGLE_ADS_JSON);
    } catch {
      console.log('GOOGLE_ADS_JSON env is not valid JSON string');
    }
  }
  return {};
}

async function main() {
  const json = loadJsonCredentials();
  if (!json || typeof json !== 'object' || Array.isArray(json)) {
    throw new Error('Google Ads credential JSON must be an object');
  }

  const oauth = json.installed || json.web || json.credentials || json.oauth || json;

  const clientId = oauth.client_id || oauth.clientId || process.env.GOOGLE_ADS_CLIENT_ID || process.env.CLIENT_ID || '';
  const clientSecret = oauth.client_secret || oauth.clientSecret || process.env.GOOGLE_ADS_CLIENT_SECRET || process.env.CLIENT_SECRET || '';
  const devToken = oauth.developer_token || oauth.developerToken || process.env.GOOGLE_ADS_DEVELOPER_TOKEN || process.env.DEVELOPER_TOKEN || '';
  const refreshToken = oauth.refresh_token || oauth.refreshToken || process.env.GOOGLE_ADS_REFRESH_TOKEN || process.env.REFRESH_TOKEN || '';
  const customerId = (oauth.customer_id || oauth.customerId || process.env.GOOGLE_ADS_CUSTOMER_ID || process.env.CUSTOMER_ID || '').replace(/-/g, '');

  const maskSuffix = (str) => (str && str.length > 4 ? `...${str.slice(-4)}` : '(none)');

  const hasClientId = Boolean(oauth.client_id || oauth.clientId || process.env.GOOGLE_ADS_CLIENT_ID || process.env.CLIENT_ID);
  const hasClientSecret = Boolean(oauth.client_secret || oauth.clientSecret || process.env.GOOGLE_ADS_CLIENT_SECRET || process.env.CLIENT_SECRET);
  const hasDevToken = Boolean(oauth.developer_token || oauth.developerToken || process.env.GOOGLE_ADS_DEVELOPER_TOKEN || process.env.DEVELOPER_TOKEN);
  const hasRefreshToken = Boolean(oauth.refresh_token || oauth.refreshToken || process.env.GOOGLE_ADS_REFRESH_TOKEN || process.env.REFRESH_TOKEN);
  const hasCustomerId = Boolean(oauth.customer_id || oauth.customerId || process.env.GOOGLE_ADS_CUSTOMER_ID || process.env.CUSTOMER_ID);

  console.log('CREDENTIAL_DIAGNOSTICS:');
  console.log(`- client_id_fingerprint=${maskSuffix(clientId)} (source: ${hasClientId ? 'RESOLVED' : 'MISSING'})`);
  console.log(`- client_secret_fingerprint=${maskSuffix(clientSecret)} (source: ${hasClientSecret ? 'RESOLVED' : 'MISSING'})`);
  console.log(`- developer_token_fingerprint=${maskSuffix(devToken)} (source: ${hasDevToken ? 'RESOLVED' : 'MISSING'})`);
  console.log(`- refresh_token_fingerprint=${maskSuffix(refreshToken)} (source: ${hasRefreshToken ? 'RESOLVED' : 'MISSING'})`);
  console.log(`- customer_id_fingerprint=${maskSuffix(customerId)} (source: ${hasCustomerId ? 'RESOLVED' : 'MISSING'})`);

  if (!clientId || !clientSecret || !devToken || !refreshToken || !customerId) {
    const missing = [];
    if (!clientId) missing.push('client_id');
    if (!clientSecret) missing.push('client_secret');
    if (!devToken) missing.push('developer_token');
    if (!refreshToken) missing.push('refresh_token');
    if (!customerId) missing.push('customer_id');

    console.log('JSON_TOP_LEVEL_KEYS:', Object.keys(json));
    console.log('OAUTH_KEYS:', Object.keys(oauth));
    throw new Error(`Missing required Google Ads credential parameters: ${missing.join(', ')}`);
  }

  const GoogleAds = new GoogleAdsApi({
    client_id: clientId,
    client_secret: clientSecret,
    developer_token: devToken,
  });

  const customer = GoogleAds.Customer({
    customer_id: customerId,
    refresh_token: refreshToken,
  });

  console.log('Attempting to list campaigns...');
  const campaigns = await customer.campaigns.list();
  console.log('Campaigns found:', campaigns.length);
  campaigns.forEach(c => console.log('-', c.id, c.name, c.status));
  console.log('SUCCESS: Google Ads credentials are valid for read-only access');
}

main().catch(err => {
  console.error('Error listing campaigns:', err.message);
  console.log('GOOGLE_ADS_READ_ONLY=FAIL', err.message);
  process.exit(1);
});
