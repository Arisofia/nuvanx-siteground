#!/usr/bin/env node

/**
 * Request indexing of specific URLs using Google Indexing API.
 * Requires GOOGLE_INDEXING_API_KEY environment variable.
 */

const https = require('https');

const args = process.argv.slice(2);
let urls = [];
let apiKey = process.env.GOOGLE_INDEXING_API_KEY || '';

for (let i = 0; i < args.length; i++) {
  if (args[i] === '--api-key' && args[i + 1]) apiKey = args[++i];
  else if (args[i] === '--urls' && args[i + 1]) {
    urls = args[++i].split(',').map(u => u.trim());
  }
}

if (!apiKey) {
  console.error('Error: GOOGLE_INDEXING_API_KEY environment variable or --api-key is required');
  process.exit(1);
}

if (urls.length === 0) {
  console.error('Error: --urls with comma-separated URLs is required');
  process.exit(1);
}

async function requestIndexing(url) {
  return new Promise((resolve, reject) => {
    const data = JSON.stringify({
      url: url,
      type: 'URL_UPDATED'
    });

    const options = {
      hostname: 'indexing.googleapis.com',
      port: 443,
      path: `/v3/urlNotifications:publish?key=${apiKey}`,
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Content-Length': Buffer.byteLength(data)
      }
    };

    const req = https.request(options, (res) => {
      let body = '';
      res.on('data', (chunk) => body += chunk);
      res.on('end', () => {
        if (res.statusCode === 200) {
          resolve(JSON.parse(body));
        } else {
          reject(new Error(`HTTP ${res.statusCode}: ${body}`));
        }
      });
    });

    req.on('error', reject);
    req.write(data);
    req.end();
  });
}

async function main() {
  console.log(`Requesting indexing for ${urls.length} URLs...`);
  
  for (const url of urls) {
    try {
      console.log(`Requesting: ${url}`);
      const result = await requestIndexing(url);
      console.log(`✓ Success: ${url}`);
      console.log(`  Response: ${JSON.stringify(result)}`);
    } catch (error) {
      console.error(`✗ Failed: ${url}`);
      console.error(`  Error: ${error.message}`);
    }
  }
}

main().catch(console.error);
