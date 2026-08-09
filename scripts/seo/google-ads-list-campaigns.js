const { GoogleAdsApi } = require('google-ads-api');
const fs = require('fs');

// Load credentials from environment or file
const credentialsPath = process.env.GOOGLE_ADS_JSON || './google-ads.json';
let credentials = {};

try {
  if (fs.existsSync(credentialsPath)) {
    try {
      credentials = JSON.parse(fs.readFileSync(credentialsPath, 'utf8'));
      console.log('Credentials loaded from file');
    } catch {
      console.log('Credentials path is not a valid JSON file');
    }
  } else if (process.env.GOOGLE_ADS_JSON) {
    try {
      credentials = JSON.parse(process.env.GOOGLE_ADS_JSON);
      console.log('Credentials loaded from GOOGLE_ADS_JSON env');
    } catch {
      console.log('GOOGLE_ADS_JSON env is not valid JSON string');
    }
  }

  const clientId = credentials.client_id || process.env.GOOGLE_ADS_CLIENT_ID || '';
  const clientSecret = credentials.client_secret || process.env.GOOGLE_ADS_CLIENT_SECRET || '';
  const devToken = credentials.developer_token || process.env.GOOGLE_ADS_DEVELOPER_TOKEN || '';
  const refreshToken = credentials.refresh_token || process.env.GOOGLE_ADS_REFRESH_TOKEN || '';
  const customerId = (credentials.customer_id || process.env.GOOGLE_ADS_CUSTOMER_ID || '').replace(/-/g, '');

  const maskSuffix = (str) => (str && str.length > 4 ? `...${str.slice(-4)}` : '(none)');

  console.log('CREDENTIAL_DIAGNOSTICS:');
  console.log(`- client_id_fingerprint=${maskSuffix(clientId)} (source: ${credentials.client_id ? 'JSON' : process.env.GOOGLE_ADS_CLIENT_ID ? 'ENV' : 'MISSING'})`);
  console.log(`- client_secret_fingerprint=${maskSuffix(clientSecret)} (source: ${credentials.client_secret ? 'JSON' : process.env.GOOGLE_ADS_CLIENT_SECRET ? 'ENV' : 'MISSING'})`);
  console.log(`- developer_token_fingerprint=${maskSuffix(devToken)} (source: ${credentials.developer_token ? 'JSON' : process.env.GOOGLE_ADS_DEVELOPER_TOKEN ? 'ENV' : 'MISSING'})`);
  console.log(`- refresh_token_fingerprint=${maskSuffix(refreshToken)} (source: ${credentials.refresh_token ? 'JSON' : process.env.GOOGLE_ADS_REFRESH_TOKEN ? 'ENV' : 'MISSING'})`);
  console.log(`- customer_id_fingerprint=${maskSuffix(customerId)} (source: ${credentials.customer_id ? 'JSON' : process.env.GOOGLE_ADS_CUSTOMER_ID ? 'ENV' : 'MISSING'})`);

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
  customer.campaigns.list()
    .then(campaigns => {
      console.log('Campaigns found:', campaigns.length);
      campaigns.forEach(c => console.log('-', c.id, c.name, c.status));
      console.log('SUCCESS: Google Ads credentials are valid for read-only access');
    })
    .catch(err => {
      console.error('Error listing campaigns:', err.message);
      console.log('GOOGLE_ADS_READ_ONLY=FAIL', err.message);
      process.exit(1);
    });
} catch (error) {
  console.error('Error:', error.message);
  process.exit(1);
}
