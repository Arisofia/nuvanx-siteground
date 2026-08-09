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
  // Align with credential classifiers: reject JSON that is not a plain
  // object (null, primitive, array) with a clear error instead of an opaque
  // "Cannot read properties of null" further down.
  if (!json || typeof json !== 'object' || Array.isArray(json)) {
    throw new Error('Google Ads credential JSON must be an object');
  }
  const oauth = json.installed || json.web || json;

  const clientId = oauth.client_id || process.env.GOOGLE_ADS_CLIENT_ID || '';
  const clientSecret = oauth.client_secret || process.env.GOOGLE_ADS_CLIENT_SECRET || '';
  const devToken = oauth.developer_token || process.env.GOOGLE_ADS_DEVELOPER_TOKEN || '';
  const refreshToken = oauth.refresh_token || process.env.GOOGLE_ADS_REFRESH_TOKEN || '';
  const customerId = (oauth.customer_id || process.env.GOOGLE_ADS_CUSTOMER_ID || '').replace(/-/g, '');

  const maskSuffix = (str) => (str && str.length > 4 ? `...${str.slice(-4)}` : '(none)');

  console.log('CREDENTIAL_DIAGNOSTICS:');
  console.log(`- client_id_fingerprint=${maskSuffix(clientId)} (source: ${oauth.client_id ? 'JSON' : process.env.GOOGLE_ADS_CLIENT_ID ? 'ENV' : 'MISSING'})`);
  console.log(`- client_secret_fingerprint=${maskSuffix(clientSecret)} (source: ${oauth.client_secret ? 'JSON' : process.env.GOOGLE_ADS_CLIENT_SECRET ? 'ENV' : 'MISSING'})`);
  console.log(`- developer_token_fingerprint=${maskSuffix(devToken)} (source: ${oauth.developer_token ? 'JSON' : process.env.GOOGLE_ADS_DEVELOPER_TOKEN ? 'ENV' : 'MISSING'})`);
  console.log(`- refresh_token_fingerprint=${maskSuffix(refreshToken)} (source: ${oauth.refresh_token ? 'JSON' : process.env.GOOGLE_ADS_REFRESH_TOKEN ? 'ENV' : 'MISSING'})`);
  console.log(`- customer_id_fingerprint=${maskSuffix(customerId)} (source: ${oauth.customer_id ? 'JSON' : process.env.GOOGLE_ADS_CUSTOMER_ID ? 'ENV' : 'MISSING'})`);

  if (!clientId || !clientSecret || !devToken || !refreshToken || !customerId) {
    console.error('Error: Missing required Google Ads credential parameters.');
    process.exit(1);
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
