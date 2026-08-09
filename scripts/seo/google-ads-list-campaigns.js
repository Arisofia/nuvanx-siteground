const { GoogleAdsApi } = require('google-ads-api');
const fs = require('fs');

// Load credentials from environment or file
const credentialsPath = process.env.GOOGLE_ADS_JSON || './google-ads.json';
let credentials;

try {
  if (fs.existsSync(credentialsPath)) {
    credentials = JSON.parse(fs.readFileSync(credentialsPath, 'utf8'));
    console.log('Credentials loaded from file');
  } else {
    console.log('Credentials file not found, trying environment');
    const envCreds = process.env.GOOGLE_ADS_JSON;
    if (envCreds) {
      credentials = JSON.parse(envCreds);
      console.log('Credentials loaded from environment');
    }
  }
  
  if (!credentials) {
    console.error('No credentials found');
    process.exit(1);
  }

  const GoogleAds = new GoogleAdsApi({
    client_id: credentials.client_id,
    client_secret: credentials.client_secret,
    developer_token: credentials.developer_token,
  });

  const customer = GoogleAds.Customer({
    customer_id: credentials.customer_id,
    refresh_token: credentials.refresh_token,
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
      process.exit(1);
    });
} catch (error) {
  console.error('Error:', error.message);
  process.exit(1);
}
