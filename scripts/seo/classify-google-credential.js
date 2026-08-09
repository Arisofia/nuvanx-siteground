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
console.log('SECRET_TOP_LEVEL_KEYS=' + Object.keys(data).sort().join(','));
const ads = ['client_id','client_secret','developer_token','customer_id','refresh_token'];
console.log('GOOGLE_ADS_REQUIRED_KEYS_PRESENT=' + ads.filter(k => Boolean(data[k])).sort().join(','));
const serviceAccount = ['type','project_id','private_key_id','private_key','client_email','client_id','auth_uri','token_uri'];
console.log('SERVICE_ACCOUNT_SIGNATURE=' + (serviceAccount.filter(k => Boolean(data[k])).length >= 5 ? 'true' : 'false'));
