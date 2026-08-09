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

  // Environment variables take precedence over OAuth JSON credentials to allow runtime overrides in CI/CD.
  let clientId = pick(process.env.GOOGLE_ADS_CLIENT_ID, process.env.CLIENT_ID, oauth.client_id, oauth.clientId);
  let clientSecret = pick(process.env.GOOGLE_ADS_CLIENT_SECRET, process.env.CLIENT_SECRET, oauth.client_secret, oauth.clientSecret);
  let devToken = pick(process.env.GOOGLE_ADS_DEVELOPER_TOKEN, process.env.DEVELOPER_TOKEN, oauth.developer_token, oauth.developerToken);
  let refreshToken = pick(process.env.GOOGLE_ADS_REFRESH_TOKEN, process.env.REFRESH_TOKEN, oauth.refresh_token, oauth.refreshToken);
  let rawCustomerId = pick(process.env.GOOGLE_ADS_CUSTOMER_ID, process.env.CUSTOMER_ID, oauth.customer_id, oauth.customerId);
  let rawLoginCustomerId = pick(process.env.GOOGLE_ADS_LOGIN_CUSTOMER_ID, process.env.LOGIN_CUSTOMER_ID, oauth.login_customer_id, oauth.loginCustomerId);

  // Auto-heal swapped client_secret vs customer_id environment variables if customer_id starts with GOCSPX-
  if (rawCustomerId && rawCustomerId.startsWith('GOCSPX-') && (!clientSecret || !clientSecret.startsWith('GOCSPX-'))) {
    const tempSecret = rawCustomerId;
    rawCustomerId = clientSecret && !clientSecret.startsWith('GOCSPX-') ? clientSecret : (rawLoginCustomerId || '');
    clientSecret = tempSecret;
  }

  const customerId = rawCustomerId.replace(/-/g, '');
  const loginCustomerId = rawLoginCustomerId.replace(/-/g, '');

  const maskSuffix = (str) => (str && str.length > 4 ? `...${str.slice(-4)}` : '(none)');

  console.log('CREDENTIAL_DIAGNOSTICS:');
  console.log(`- client_id_fingerprint=${maskSuffix(clientId)} (source: ${oauth.client_id || oauth.clientId ? 'JSON' : process.env.GOOGLE_ADS_CLIENT_ID ? 'ENV' : 'MISSING'})`);
  console.log(`- client_secret_status=${clientSecret ? 'SET' : 'MISSING'} (source: ${oauth.client_secret || oauth.clientSecret ? 'JSON' : process.env.GOOGLE_ADS_CLIENT_SECRET ? 'ENV' : 'MISSING'})`);
  console.log(`- developer_token_fingerprint=${maskSuffix(devToken)} (source: ${oauth.developer_token || oauth.developerToken ? 'JSON' : process.env.GOOGLE_ADS_DEVELOPER_TOKEN ? 'ENV' : 'MISSING'})`);
  console.log(`- refresh_token_status=${refreshToken ? 'SET' : 'MISSING'} (source: ${oauth.refresh_token || oauth.refreshToken ? 'JSON' : process.env.GOOGLE_ADS_REFRESH_TOKEN ? 'ENV' : 'MISSING'})`);
  console.log(`- customer_id_fingerprint=${maskSuffix(customerId)} (source: ${oauth.customer_id || oauth.customerId ? 'JSON' : process.env.GOOGLE_ADS_CUSTOMER_ID ? 'ENV' : 'MISSING'})`);
  console.log(`- login_customer_id_fingerprint=${maskSuffix(loginCustomerId)} (source: ${oauth.login_customer_id || oauth.loginCustomerId ? 'JSON' : process.env.GOOGLE_ADS_LOGIN_CUSTOMER_ID ? 'ENV' : 'OPTIONAL_MISSING'})`);

  if (!clientId || !clientSecret || !devToken || !refreshToken || !customerId) {
    const missing = [];
    if (!clientId) missing.push('client_id');
    if (!clientSecret) missing.push('client_secret');
    if (!devToken) missing.push('developer_token');
    if (!refreshToken) missing.push('refresh_token');
    if (!customerId) missing.push('customer_id');

    throw new Error(`Missing required Google Ads credential parameters: ${missing.join(', ')}`);
  }

  const GoogleAds = new GoogleAdsApi({
    client_id: clientId,
    client_secret: clientSecret,
    developer_token: devToken,
  });

  const customerOptions = {
    customer_id: customerId,
    refresh_token: refreshToken,
  };
  if (loginCustomerId) {
    customerOptions.login_customer_id = loginCustomerId;
  }

  const customer = GoogleAds.Customer(customerOptions);

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
  const sanitizeMessage = (msg) => (typeof msg === 'string' ? msg.replace(/(GOCSPX-[A-Za-z0-9_-]+|1\/\/[A-Za-z0-9_-]+)/g, '[REDACTED]') : msg);

  console.error('Error listing campaigns:', sanitizeMessage(err && err.message ? err.message : String(err)));
  if (err && Array.isArray(err.errors)) {
    const sanitizedErrors = err.errors.map((e) => ({
      error_code: e.error_code || e.errorCode,
      message: sanitizeMessage(e.message),
    }));
    console.error('Details:', JSON.stringify(sanitizedErrors, null, 2));
  }
  console.log('GOOGLE_ADS_READ_ONLY=FAIL', sanitizeMessage(err && err.message ? err.message : 'unknown error'));
  process.exit(1);
});
