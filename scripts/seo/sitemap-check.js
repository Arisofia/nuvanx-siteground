#!/usr/bin/env node

/**
 * Check sitemap status via Search Console API
 * Returns: last read, status, URLs discovered, errors
 */

const { google } = require('googleapis');
const fs = require('fs');
const path = require('path');

// Parse command line arguments
const args = process.argv.slice(2);
let property = '';
let sitemapUrl = '';

for (let i = 0; i < args.length; i++) {
  if (args[i] === '--property' && args[i + 1]) {
    property = args[i + 1];
    i++;
  } else if (args[i] === '--sitemap-url' && args[i + 1]) {
    sitemapUrl = args[i + 1];
    i++;
  }
}

if (!property || !sitemapUrl) {
  console.error('Error: --property and --sitemap-url are required');
  console.error('Usage: node sitemap-check.js --property <property> --sitemap-url <url>');
  process.exit(1);
}

async function checkSitemapStatus() {
  try {
    // Load credentials
    const credentialsPath = path.join(__dirname, 'credentials.json');
    if (!fs.existsSync(credentialsPath)) {
      throw new Error('credentials.json not found');
    }

    const credentials = JSON.parse(fs.readFileSync(credentialsPath, 'utf8'));

    // Authenticate
    const auth = new google.auth.GoogleAuth({
      credentials: credentials,
      scopes: ['https://www.googleapis.com/auth/webmasters']
    });

    const searchconsole = google.searchconsole({ version: 'v1', auth });

    console.log(`Checking sitemap status for: ${sitemapUrl}`);
    console.log(`Property: ${property}`);

    // Get sitemap data
    const response = await searchconsole.sitemaps.get({
      siteUrl: property,
      feedpath: sitemapUrl
    });

    const sitemapData = response.data;

    console.log('\n=== Sitemap Status ===');
    console.log(`Last Read: ${sitemapData.lastDownloaded || 'N/A'}`);
    console.log(`Status: ${sitemapData.isPending ? 'Pending' : 'Success'}`);
    console.log(`Warnings: ${sitemapData.warnings || 0}`);
    console.log(`Errors: ${sitemapData.errors || 0}`);
    console.log(`Contents: ${sitemapData.contents ? JSON.stringify(sitemapData.contents, null, 2) : 'N/A'}`);

    // Get sitemap contents if available
    if (sitemapData.contents) {
      let totalUrls = 0;
      let indexedUrls = 0;
      
      if (Array.isArray(sitemapData.contents)) {
        sitemapData.contents.forEach(content => {
          totalUrls += content.submitted || 0;
          indexedUrls += content.indexed || 0;
        });
      }

      console.log(`\nTotal URLs Submitted: ${totalUrls}`);
      console.log(`Total URLs Indexed: ${indexedUrls}`);
      console.log(`Index Rate: ${totalUrls > 0 ? ((indexedUrls / totalUrls) * 100).toFixed(2) : 0}%`);
    }

    console.log('\nSITEMAP_CHECK_COMPLETED=true');
    console.log(`LAST_READ=${sitemapData.lastDownloaded || 'N/A'}`);
    console.log(`STATUS=${sitemapData.isPending ? 'Pending' : 'Success'}`);
    console.log(`WARNINGS=${sitemapData.warnings || 0}`);
    console.log(`ERRORS=${sitemapData.errors || 0}`);

    // Save results to file
    const resultsPath = path.join(__dirname, 'artifacts', 'sitemap-status.json');
    fs.mkdirSync(path.dirname(resultsPath), { recursive: true });
    fs.writeFileSync(resultsPath, JSON.stringify(sitemapData, null, 2));
    console.log(`\nResults saved to: ${resultsPath}`);

  } catch (error) {
    console.error('Fatal error:', error.message);
    console.log('SITEMAP_CHECK_COMPLETED=false');
    console.log(`ERROR=${error.message}`);
    process.exit(1);
  }
}

checkSitemapStatus();
