const { GoogleAdsApi } = require('google-ads-api');
const fs = require('fs');

class LocalConfigError extends Error {
  constructor(code, message) {
    super(message);
    this.name = 'LocalConfigError';
    this.code = code;
  }
}

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

function pickWithValueAndSource(envCandidates, jsonCandidates, isOptional = false) {
  for (const envVal of envCandidates) {
    const value = envVal == null ? '' : String(envVal).trim();
    if (value) {
      return { value, source: 'ENV' };
    }
  }
  for (const jsonVal of jsonCandidates) {
    const value = jsonVal == null ? '' : String(jsonVal).trim();
    if (value) {
      return { value, source: 'JSON' };
    }
  }
  return { value: '', source: isOptional ? 'OPTIONAL_MISSING' : 'MISSING' };
}

async function main() {
  const json = loadJsonCredentials();

  // Guard: reject JSON that is not a plain object (null, primitive, array)
  if (!json || typeof json !== 'object' || Array.isArray(json)) {
    throw new LocalConfigError('invalid_credentials_json', 'Google Ads credential JSON must be an object');
  }

  const oauth = json.installed || json.web || json.credentials || json.oauth || json;

  // Environment variables take precedence over OAuth JSON credentials to allow runtime overrides in CI/CD.
  const clientIdRes = pickWithValueAndSource([process.env.GOOGLE_ADS_CLIENT_ID, process.env.CLIENT_ID], [oauth.client_id, oauth.clientId]);
  let clientSecretRes = pickWithValueAndSource([process.env.GOOGLE_ADS_CLIENT_SECRET, process.env.CLIENT_SECRET], [oauth.client_secret, oauth.clientSecret]);
  const devTokenRes = pickWithValueAndSource([process.env.GOOGLE_ADS_DEVELOPER_TOKEN, process.env.DEVELOPER_TOKEN], [oauth.developer_token, oauth.developerToken]);
  const refreshTokenRes = pickWithValueAndSource([process.env.GOOGLE_ADS_REFRESH_TOKEN, process.env.REFRESH_TOKEN], [oauth.refresh_token, oauth.refreshToken]);
  let customerIdRes = pickWithValueAndSource([process.env.GOOGLE_ADS_CUSTOMER_ID, process.env.CUSTOMER_ID], [oauth.customer_id, oauth.customerId]);
  let loginCustomerIdRes = pickWithValueAndSource([process.env.GOOGLE_ADS_LOGIN_CUSTOMER_ID, process.env.LOGIN_CUSTOMER_ID], [oauth.login_customer_id, oauth.loginCustomerId], true);

  let clientId = clientIdRes.value;
  let clientSecret = clientSecretRes.value;
  let devToken = devTokenRes.value;
  let refreshToken = refreshTokenRes.value;
  let rawCustomerId = customerIdRes.value;
  let rawLoginCustomerId = loginCustomerIdRes.value;

  // Auto-heal swapped client_secret vs customer_id environment variables if customer_id starts with GOCSPX-
  if (rawCustomerId?.startsWith('GOCSPX-') && !clientSecret?.startsWith('GOCSPX-')) {
    const tempSecret = rawCustomerId;
    const previousCustomerSource = customerIdRes.source;
    rawCustomerId = (clientSecret && !clientSecret.startsWith('GOCSPX-')) ? clientSecret : (rawLoginCustomerId || '');
    customerIdRes = { value: rawCustomerId, source: (clientSecret && !clientSecret.startsWith('GOCSPX-')) ? clientSecretRes.source : loginCustomerIdRes.source };
    clientSecret = tempSecret;
    clientSecretRes = { value: clientSecret, source: previousCustomerSource };
  }

  const customerId = rawCustomerId.replace(/-/g, '');
  const loginCustomerId = rawLoginCustomerId.replace(/-/g, '');

  console.log('CREDENTIAL_DIAGNOSTICS:');
  console.log(`- client_id_status=${clientId ? 'SET' : 'MISSING'} (source: ${clientIdRes.source})`);
  console.log(`- client_secret_status=${clientSecret ? 'SET' : 'MISSING'} (source: ${clientSecretRes.source})`);
  console.log(`- developer_token_status=${devToken ? 'SET' : 'MISSING'} (source: ${devTokenRes.source})`);
  console.log(`- refresh_token_status=${refreshToken ? 'SET' : 'MISSING'} (source: ${refreshTokenRes.source})`);
  console.log(`- customer_id_status=${customerId ? 'SET' : 'MISSING'} (source: ${customerIdRes.source})`);
  console.log(`- login_customer_id_status=${loginCustomerId ? 'SET' : 'MISSING'} (source: ${loginCustomerIdRes.source})`);

  if (!clientId || !clientSecret || !devToken || !refreshToken || !customerId) {
    const missing = [];
    if (!clientId) missing.push('client_id');
    if (!clientSecret) missing.push('client_secret');
    if (!devToken) missing.push('developer_token');
    if (!refreshToken) missing.push('refresh_token');
    if (!customerId) missing.push('customer_id');

    throw new LocalConfigError('missing_credentials', `Missing required Google Ads credential parameters: ${missing.join(', ')}`);
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
  const classifyMessage = (msg) => {
    const value = String(msg || '').toLowerCase();
    if (/invalid[_ -]?grant/.test(value)) return 'invalid_grant';
    if (/invalid[_ -]?client/.test(value)) return 'invalid_client';
    if (/developer[_ -]?token/.test(value)) return 'developer_token_error';
    if (/quota|resource[_ -]?exhausted|rate[_ -]?limit/.test(value)) return 'quota_or_rate_limit';
    if (/permission[_ -]?denied/.test(value)) return 'permission_denied';
    if (/unauthenticated|authentication/.test(value)) return 'authentication_error';
    return 'google_ads_api_error';
  };

  const projectErrorCode = (raw) => {
    if (raw === undefined || raw === null) return undefined;
    if (typeof raw !== 'object' || Array.isArray(raw)) {
      return String(raw).replace(/[^A-Za-z0-9_.:-]/g, '_').slice(0, 120);
    }

    const projected = {};
    for (const [key, value] of Object.entries(raw).slice(0, 8)) {
      if (!/^[A-Za-z][\w-]{0,63}$/.test(key)) continue;
      if (['string', 'number', 'boolean'].includes(typeof value)) {
        projected[key] = String(value).replace(/[^A-Za-z0-9_.:-]/g, '_').slice(0, 120);
      } else {
        // Preserve the bounded oneof key without serializing nested request/error payloads.
        projected[key] = true;
      }
    }
    return Object.keys(projected).length ? projected : undefined;
  };

  // CI logs are public-facing operational evidence. Never serialize free-form
  // Google Ads API messages or request metadata; emit bounded enum-like diagnostics only.
  const DIAGNOSTIC_FIELDS = ['error_code', 'errorCode'];
  const projectError = (e) => {
    const projected = {};
    if (e && typeof e === 'object') {
      for (const field of DIAGNOSTIC_FIELDS) {
        const code = projectErrorCode(e[field]);
        if (code !== undefined) projected[field] = code;
      }
      projected.message_class = classifyMessage(e.message);
      return projected;
    }
    return { message_class: classifyMessage(e) };
  };

  if (err instanceof LocalConfigError) {
    console.error('Google Ads configuration error:', err.message);
    console.log('GOOGLE_ADS_READ_ONLY=FAIL', err.code);
    process.exit(1);
  }

  const topLevelClass = classifyMessage(err?.message || err);
  console.error('Error listing campaigns:', topLevelClass);
  if (Array.isArray(err?.errors)) {
    const projectedErrors = err.errors.map((e) => projectError(e));
    console.error('Details:', JSON.stringify(projectedErrors, null, 2));
  }
  console.log('GOOGLE_ADS_READ_ONLY=FAIL', topLevelClass);
  process.exit(1);
});
