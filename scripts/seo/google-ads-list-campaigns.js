const { GoogleAdsApi } = require('google-ads-api');
const fs = require('fs');

function parseJsonFile(filePath) {
  if (fs.existsSync(filePath)) {
    try {
      return JSON.parse(fs.readFileSync(filePath, 'utf8'));
    } catch {
      console.log(`Credentials file ${filePath} is not valid JSON`);
    }
  }
  return null;
}

function loadJsonCredentials() {
  const credentialsPath = process.env.GOOGLE_ADS_JSON || './google-ads.json';

  // Check if credentialsPath is a file
  const fileParsed = parseJsonFile(credentialsPath);
  if (fileParsed) {
    console.log('Credentials loaded from file');
    return fileParsed;
  }

  // Check direct JSON string in GOOGLE_ADS_JSON env
  const rawEnv = process.env.GOOGLE_ADS_JSON;
  if (rawEnv) {
    try {
      const envParsed = JSON.parse(rawEnv);
      console.log('Credentials loaded from GOOGLE_ADS_JSON env');
      return envParsed;
    } catch {
      console.log('GOOGLE_ADS_JSON env is not a valid JSON string');
    }
  }

  return {};
}

function pick(...values) {
  for (const v of values) {
    if (v !== undefined && v !== null && v !== '') {
      return String(v).trim();
    }
  }
  return '';
}

async function main() {
  const json = loadJsonCredentials();

  // Guard: reject JSON that is not a plain object (null, primitive, array)
  if (!json || typeof json !== 'object' || Array.isArray(json)) {
    throw new Error('Google Ads credential JSON must be an object');
  }

  const oauth = json.installed || json.web || json.credentials || json.oauth || json;

  const clientId = pick(oauth.client_id, oauth.clientId, process.env.GOOGLE_ADS_CLIENT_ID, process.env.CLIENT_ID);
  const clientSecret = pick(oauth.client_secret, oauth.clientSecret, process.env.GOOGLE_ADS_CLIENT_SECRET, process.env.CLIENT_SECRET);
  const devToken = pick(oauth.developer_token, oauth.developerToken, process.env.GOOGLE_ADS_DEVELOPER_TOKEN, process.env.DEVELOPER_TOKEN);
  const refreshToken = pick(oauth.refresh_token, oauth.refreshToken, process.env.GOOGLE_ADS_REFRESH_TOKEN, process.env.REFRESH_TOKEN);
  const rawCustomerId = pick(oauth.customer_id, oauth.customerId, process.env.GOOGLE_ADS_CUSTOMER_ID, process.env.CUSTOMER_ID);
  const customerId = rawCustomerId.replace(/-/g, '');

  const maskSuffix = (str) => (str && str.length > 4 ? `...${str.slice(-4)}` : '(none)');

  console.log('CREDENTIAL_DIAGNOSTICS:');
  console.log(`- client_id_fingerprint=${maskSuffix(clientId)} (source: ${oauth.client_id || oauth.clientId ? 'JSON' : process.env.GOOGLE_ADS_CLIENT_ID ? 'ENV' : 'MISSING'})`);
  console.log(`- client_secret_fingerprint=${maskSuffix(clientSecret)} (source: ${oauth.client_secret || oauth.clientSecret ? 'JSON' : process.env.GOOGLE_ADS_CLIENT_SECRET ? 'ENV' : 'MISSING'})`);
  console.log(`- developer_token_fingerprint=${maskSuffix(devToken)} (source: ${oauth.developer_token || oauth.developerToken ? 'JSON' : process.env.GOOGLE_ADS_DEVELOPER_TOKEN ? 'ENV' : 'MISSING'})`);
  console.log(`- refresh_token_fingerprint=${maskSuffix(refreshToken)} (source: ${oauth.refresh_token || oauth.refreshToken ? 'JSON' : process.env.GOOGLE_ADS_REFRESH_TOKEN ? 'ENV' : 'MISSING'})`);
  console.log(`- customer_id_fingerprint=${maskSuffix(customerId)} (source: ${oauth.customer_id || oauth.customerId ? 'JSON' : process.env.GOOGLE_ADS_CUSTOMER_ID ? 'ENV' : 'MISSING'})`);

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

  console.log('Attempting read-only GAQL campaign query...');
  const campaigns = await customer.query(`
    SELECT campaign.id, campaign.name, campaign.status
    FROM campaign
    LIMIT 10
  `);

  console.log('Campaigns found:', campaigns.length);
  campaigns.forEach((c) => console.log('-', c.campaign.id, c.campaign.name, c.campaign.status));
  console.log('SUCCESS: Google Ads credentials are valid for read-only access');
}

main().catch((err) => {
  console.error('Error listing campaigns:', err.message);
  console.log('GOOGLE_ADS_READ_ONLY=FAIL', err.message);
  process.exit(1);
});
