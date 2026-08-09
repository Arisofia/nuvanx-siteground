const raw = process.env.GOOGLE_ADS_JSON || '';
if (!raw) {
  console.log('GOOGLE_ADS_CREDENTIALS=MISSING');
  process.exit(3);
}
let data;
try {
  data = JSON.parse(raw);
} catch {
  console.log('SECRET_JSON_VALID=false');
  process.exit(4);
}
console.log('SECRET_JSON_VALID=true');
// Guard against valid JSON that is not an object (null, primitive, array), so
// the subsequent key inspection stays deterministic instead of throwing.
if (!data || typeof data !== 'object' || Array.isArray(data)) {
  console.log('SECRET_JSON_IS_OBJECT=false');
  process.exit(5);
}
// Do not echo raw secret key names; report only the top-level key count.
console.log('SECRET_TOP_LEVEL_KEY_COUNT=' + Object.keys(data).length);
// OAuth client JSON may nest credentials under installed/web/credentials/oauth,
// so inspect the same wrappers the validator uses to keep diagnostics in sync.
const oauth = data.installed || data.web || data.credentials || data.oauth || data;

const camelMap = {
  client_id: 'clientId',
  client_secret: 'clientSecret',
  developer_token: 'developerToken',
  customer_id: 'customerId',
  refresh_token: 'refreshToken',
};

const envMap = {
  client_id: ['GOOGLE_ADS_CLIENT_ID', 'CLIENT_ID'],
  client_secret: ['GOOGLE_ADS_CLIENT_SECRET', 'CLIENT_SECRET'],
  developer_token: ['GOOGLE_ADS_DEVELOPER_TOKEN', 'DEVELOPER_TOKEN'],
  customer_id: ['GOOGLE_ADS_CUSTOMER_ID', 'CUSTOMER_ID'],
  refresh_token: ['GOOGLE_ADS_REFRESH_TOKEN', 'REFRESH_TOKEN'],
};

const has = (k) => {
  const camelKey = camelMap[k];
  const envKeys = envMap[k] || [];
  const inJson = Boolean(data[k] || oauth[k] || (camelKey && (data[camelKey] || oauth[camelKey])));
  const inEnv = envKeys.some((envKey) => Boolean(process.env[envKey]));
  return inJson || inEnv;
};

const ads = ['client_id','client_secret','developer_token','customer_id','refresh_token'];
console.log('GOOGLE_ADS_REQUIRED_KEYS_PRESENT=' + ads.filter(has).sort((a, b) => a.localeCompare(b)).join(','));
const serviceAccount = ['type','project_id','private_key_id','private_key','client_email','client_id','auth_uri','token_uri'];
console.log('SERVICE_ACCOUNT_SIGNATURE=' + (serviceAccount.filter((k) => Boolean(data[k])).length >= 5 ? 'true' : 'false'));
