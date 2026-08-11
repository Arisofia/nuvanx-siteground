const { GoogleAdsApi, errors } = require('google-ads-api');
const fs = require('fs');

const MAX_PROJECTED_ERRORS = 20;
const SAFE_ERROR_FAMILIES = new Set([
  'authentication_error',
  'authenticationError',
  'authorization_error',
  'authorizationError',
  'query_error',
  'queryError',
  'quota_error',
  'quotaError',
  'request_error',
  'requestError',
  'resource_access_denied_error',
  'resourceAccessDeniedError',
]);
const SAFE_ERROR_ENUMS = new Set([
  'UNSPECIFIED',
  'UNKNOWN',
  'AUTHENTICATION_ERROR',
  'CLIENT_CUSTOMER_ID_INVALID',
  'CUSTOMER_NOT_FOUND',
  'NOT_ADS_USER',
  'OAUTH_TOKEN_INVALID',
  'ORGANIZATION_NOT_RECOGNIZED',
  'USER_PERMISSION_DENIED',
  'PROJECT_DISABLED',
  'CUSTOMER_NOT_ENABLED',
  'MISSING_TOS',
  'DEVELOPER_TOKEN_NOT_APPROVED',
  'DEVELOPER_TOKEN_PROHIBITED',
  'RESOURCE_EXHAUSTED',
  'RESOURCE_TEMPORARILY_EXHAUSTED',
  'BAD_RESOURCE_ID',
  'INVALID_CUSTOMER_ID',
  'INVALID_QUERY',
  'MALFORMED_QUERY',
  'PROHIBITED_FIELD_COMBINATION',
  'REQUEST_ERROR',
  'QUOTA_ERROR',
]);
const SAFE_RUNTIME_CODES = new Set(['ENOTFOUND', 'ETIMEDOUT', 'ECONNRESET', 'EAI_AGAIN', 'ECONNREFUSED']);

class LocalConfigError extends Error {
  constructor(code, message) {
    super(message);
    this.name = 'LocalConfigError';
    this.code = code;
  }
}

function parseJsonFile(filePath) {
  if (!fs.existsSync(filePath)) return null;
  try {
    return JSON.parse(fs.readFileSync(filePath, 'utf8'));
  } catch {
    console.log(`Credentials file ${filePath} is not valid JSON`);
    return null;
  }
}

function loadJsonCredentials() {
  const credentialsPath = process.env.GOOGLE_ADS_JSON_PATH || process.env.GOOGLE_ADS_JSON || './google-ads.json';

  // Preserve the historic GOOGLE_ADS_JSON-as-path contract while supporting an
  // explicit path variable for callers that keep inline JSON in GOOGLE_ADS_JSON.
  const fileParsed = parseJsonFile(credentialsPath);
  if (fileParsed) {
    console.log('Credentials loaded from file');
    return fileParsed;
  }

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
    if (value) return { value, source: 'ENV' };
  }
  for (const jsonVal of jsonCandidates) {
    const value = jsonVal == null ? '' : String(jsonVal).trim();
    if (value) return { value, source: 'JSON' };
  }
  return { value: '', source: isOptional ? 'OPTIONAL_MISSING' : 'MISSING' };
}

function classifyMessage(message) {
  const value = String(message || '').toLowerCase();
  const labels = [];
  if (/invalid[_ -]?grant/.test(value)) labels.push('invalid_grant');
  if (/invalid[_ -]?client/.test(value)) labels.push('invalid_client');
  if (/developer[_ -]?token/.test(value)) labels.push('developer_token_error');
  if (/quota|resource[_ -]?exhausted|rate[_ -]?limit/.test(value)) labels.push('quota_or_rate_limit');
  if (/permission[_ -]?denied/.test(value)) labels.push('permission_denied');
  if (/unauthenticated|authentication/.test(value)) labels.push('authentication_error');
  return labels.length ? labels.join('+') : 'google_ads_api_error';
}

function safeEnum(value) {
  if (!['string', 'number'].includes(typeof value)) return undefined;
  const normalized = String(value);
  return SAFE_ERROR_ENUMS.has(normalized) ? normalized : undefined;
}

function projectNestedEnums(value) {
  if (!value || typeof value !== 'object' || Array.isArray(value)) return null;
  const nested = Object.create(null);
  for (const [key, nestedValue] of Object.entries(value).slice(0, 8)) {
    if (!/^[A-Za-z]\w{0,63}$/.test(key)) continue;
    const enumValue = safeEnum(nestedValue);
    if (enumValue !== undefined) nested[key] = enumValue;
  }
  return Object.keys(nested).length ? nested : null;
}

function projectErrorFamilyValue(value) {
  const direct = safeEnum(value);
  if (direct !== undefined) return direct;
  return projectNestedEnums(value) || true;
}

function projectErrorCode(raw) {
  if (!raw || typeof raw !== 'object' || Array.isArray(raw)) return undefined;
  const projected = Object.create(null);
  for (const [key, value] of Object.entries(raw).slice(0, 8)) {
    if (!SAFE_ERROR_FAMILIES.has(key)) continue;
    projected[key] = projectErrorFamilyValue(value);
  }
  return Object.keys(projected).length ? projected : undefined;
}

function projectError(error) {
  if (!error || typeof error !== 'object') {
    return { message_class: classifyMessage(error) };
  }

  const projected = { message_class: classifyMessage(error.message) };
  const snakeCode = projectErrorCode(error.error_code);
  const camelCode = projectErrorCode(error.errorCode);
  if (snakeCode !== undefined) projected.error_code = snakeCode;
  if (camelCode !== undefined) projected.errorCode = camelCode;
  return projected;
}

function googleAdsFailureClass(projectedErrors) {
  const classes = [...new Set(projectedErrors.map((entry) => entry.message_class).filter(Boolean))];
  return classes.length ? classes.join('+') : 'google_ads_api_error';
}

function reportGoogleAdsFailure(error) {
  const projectedErrors = (Array.isArray(error.errors) ? error.errors : [])
    .slice(0, MAX_PROJECTED_ERRORS)
    .map(projectError);
  const failureClass = googleAdsFailureClass(projectedErrors);
  console.error('Error listing campaigns:', failureClass);
  if (projectedErrors.length) console.error('Details:', JSON.stringify(projectedErrors, null, 2));
  console.log('GOOGLE_ADS_READ_ONLY=FAIL', failureClass);
}

function runtimeDiagnostic(error) {
  const rawClass = String(error?.constructor?.name || '');
  const runtimeClass = /^[A-Za-z][\w$]{0,63}$/.test(rawClass) ? rawClass : 'runtime_error';
  const rawCode = typeof error?.code === 'string' ? error.code : '';
  return SAFE_RUNTIME_CODES.has(rawCode) ? `${runtimeClass}:${rawCode}` : runtimeClass;
}

async function main() {
  const json = loadJsonCredentials();
  if (!json || typeof json !== 'object' || Array.isArray(json)) {
    throw new LocalConfigError('invalid_credentials_json', 'Google Ads credential JSON must be an object');
  }

  const oauthCandidate = json.installed || json.web || json.credentials || json.oauth || json;
  if (!oauthCandidate || typeof oauthCandidate !== 'object' || Array.isArray(oauthCandidate)) {
    throw new LocalConfigError('invalid_credentials_json', 'Google Ads OAuth credentials must be an object');
  }
  const oauth = oauthCandidate;

  const clientIdRes = pickWithValueAndSource([process.env.GOOGLE_ADS_CLIENT_ID, process.env.CLIENT_ID], [oauth.client_id, oauth.clientId]);
  let clientSecretRes = pickWithValueAndSource([process.env.GOOGLE_ADS_CLIENT_SECRET, process.env.CLIENT_SECRET], [oauth.client_secret, oauth.clientSecret]);
  const devTokenRes = pickWithValueAndSource([process.env.GOOGLE_ADS_DEVELOPER_TOKEN, process.env.DEVELOPER_TOKEN], [oauth.developer_token, oauth.developerToken]);
  const refreshTokenRes = pickWithValueAndSource([process.env.GOOGLE_ADS_REFRESH_TOKEN, process.env.REFRESH_TOKEN], [oauth.refresh_token, oauth.refreshToken]);
  let customerIdRes = pickWithValueAndSource([process.env.GOOGLE_ADS_CUSTOMER_ID, process.env.CUSTOMER_ID], [oauth.customer_id, oauth.customerId]);
  const loginCustomerIdRes = pickWithValueAndSource([process.env.GOOGLE_ADS_LOGIN_CUSTOMER_ID, process.env.LOGIN_CUSTOMER_ID], [oauth.login_customer_id, oauth.loginCustomerId], true);

  const clientId = clientIdRes.value;
  let clientSecret = clientSecretRes.value;
  const devToken = devTokenRes.value;
  const refreshToken = refreshTokenRes.value;
  let rawCustomerId = customerIdRes.value;
  const rawLoginCustomerId = loginCustomerIdRes.value;

  const looksLikeSecret = (value) => String(value || '').startsWith('GOCSPX-');
  const looksLikeCustomerId = (value) => {
    const candidate = String(value || '').trim();
    return /^\d{10}$/.test(candidate) || /^\d{3}-\d{3}-\d{4}$/.test(candidate);
  };

  // Heal only a verifiable one-to-one swap. login_customer_id is manager
  // context and must never be promoted to the target customer_id as a guess.
  if (looksLikeSecret(rawCustomerId) && looksLikeCustomerId(clientSecret)) {
    const swappedSecret = rawCustomerId;
    const swappedCustomerId = clientSecret;
    const swappedSecretSource = customerIdRes.source;
    const swappedCustomerSource = clientSecretRes.source;
    rawCustomerId = swappedCustomerId;
    customerIdRes = { value: swappedCustomerId, source: swappedCustomerSource };
    clientSecret = swappedSecret;
    clientSecretRes = { value: swappedSecret, source: swappedSecretSource };
  } else if (looksLikeSecret(rawCustomerId)) {
    throw new LocalConfigError(
      'credential_swap_suspected',
      'Google Ads customer_id contains a client-secret-shaped value; refusing to infer customer_id from login_customer_id'
    );
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

  if (!/^\d{10}$/.test(customerId)) {
    throw new LocalConfigError('invalid_customer_id', 'Google Ads customer_id must be exactly 10 digits (UI dashes are allowed and removed before use)');
  }
  if (loginCustomerId && !/^\d{10}$/.test(loginCustomerId)) {
    throw new LocalConfigError('invalid_login_customer_id', 'Google Ads login_customer_id must be exactly 10 digits when provided (UI dashes are allowed and removed before use)');
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
  if (loginCustomerId) customerOptions.login_customer_id = loginCustomerId;

  const customer = GoogleAds.Customer(customerOptions);
  console.log('Attempting read-only GAQL campaign query...');
  const campaigns = await customer.query(`
    SELECT campaign.id, campaign.name, campaign.status
    FROM campaign
    LIMIT 10
  `);

  console.log('Campaigns found:', campaigns.length);
  campaigns.forEach((campaign) => console.log('-', campaign.campaign.id, campaign.campaign.name, campaign.campaign.status));
  console.log('SUCCESS: Google Ads credentials are valid for read-only access');
}

main().catch((error) => {
  if (error instanceof LocalConfigError) {
    console.error('Google Ads configuration error:', error.message);
    console.log('GOOGLE_ADS_READ_ONLY=FAIL', error.code);
    process.exit(1);
  }

  const isGoogleAdsFailure = Boolean(
    (errors?.GoogleAdsFailure && error instanceof errors.GoogleAdsFailure) ||
    Array.isArray(error?.errors)
  );
  if (isGoogleAdsFailure) {
    reportGoogleAdsFailure(error);
    process.exit(1);
  }

  const diagnostic = runtimeDiagnostic(error);
  console.error('Error listing campaigns:', diagnostic);
  console.log('GOOGLE_ADS_READ_ONLY=FAIL', diagnostic);
  process.exit(1);
});
