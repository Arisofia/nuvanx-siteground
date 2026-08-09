#!/usr/bin/env node

/**
 * Index all nuvanx.com pages via Search Console API
 * Fetches sitemap and requests indexing for all URLs
 */

const { google } = require('googleapis');
const fs = require('fs');
const path = require('path');

// Parse command line arguments
const args = process.argv.slice(2);
let property = '';
let baseUrl = '';

for (let i = 0; i < args.length; i++) {
  if (args[i] === '--property' && args[i + 1]) {
    property = args[i + 1];
    i++;
  } else if (args[i] === '--url' && args[i + 1]) {
    baseUrl = args[i + 1];
    i++;
  }
}

if (!property || !baseUrl) {
  console.error('Error: --property and --url are required');
  console.error('Usage: node index-pages.js --property <property> --url <url>');
  process.exit(1);
}

async function indexAllPages() {
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

    console.log(`Indexing pages for property: ${property}`);
    console.log(`Base URL: ${baseUrl}`);

    // Fetch sitemap
    const sitemapUrl = `${baseUrl}/sitemap.xml`;
    console.log(`Fetching sitemap: ${sitemapUrl}`);
    
    const sitemapResponse = await fetch(sitemapUrl);
    if (!sitemapResponse.ok) {
      throw new Error(`Failed to fetch sitemap: ${sitemapResponse.status}`);
    }

    const sitemapText = await sitemapResponse.text();
    
    // Parse sitemap XML to extract URLs
    const urlRegex = /<loc>(.*?)<\/loc>/g;
    const urls = [];
    let match;
    
    while ((match = urlRegex.exec(sitemapText)) !== null) {
      urls.push(match[1]);
    }

    console.log(`Found ${urls.length} URLs in sitemap`);

    if (urls.length === 0) {
      console.log('No URLs found in sitemap');
      process.exit(0);
    }

    // Request indexing for each URL
    let successCount = 0;
    let errorCount = 0;
    const results = [];

    for (const url of urls) {
      try {
        console.log(`Requesting indexing for: ${url}`);
        
        const response = await searchconsole.urlInspection.index.inspectAndSubmitUrl({
          requestBody: {
            inspectionUrl: url,
            siteUrl: property,
            languageCode: 'es'
          }
        });

        const result = response.data.inspectionResult;
        
        if (result.indexStatusResult?.lastCrawlTime) {
          console.log(`✓ Indexed: ${url}`);
          successCount++;
          results.push({ url, status: 'indexed', lastCrawl: result.indexStatusResult.lastCrawlTime });
        } else {
          console.log(`⏳ Pending: ${url}`);
          successCount++;
          results.push({ url, status: 'pending', coverage: result.indexStatusResult?.coverageState });
        }

        // Rate limiting: wait 1 second between requests
        await new Promise(resolve => setTimeout(resolve, 1000));

      } catch (error) {
        console.error(`✗ Error for ${url}: ${error.message}`);
        errorCount++;
        results.push({ url, status: 'error', error: error.message });
      }
    }

    console.log('\n=== Indexing Summary ===');
    console.log(`Total URLs: ${urls.length}`);
    console.log(`Success: ${successCount}`);
    console.log(`Errors: ${errorCount}`);
    console.log(`INDEXING_COMPLETED=true`);
    console.log(`TOTAL_URLS=${urls.length}`);
    console.log(`SUCCESS_COUNT=${successCount}`);
    console.log(`ERROR_COUNT=${errorCount}`);

    // Save results to file
    const resultsPath = path.join(__dirname, 'artifacts', 'indexing-results.json');
    fs.mkdirSync(path.dirname(resultsPath), { recursive: true });
    fs.writeFileSync(resultsPath, JSON.stringify(results, null, 2));
    console.log(`Results saved to: ${resultsPath}`);

  } catch (error) {
    console.error('Fatal error:', error.message);
    console.log(`INDEXING_COMPLETED=false`);
    console.log(`ERROR=${error.message}`);
    process.exit(1);
  }
}

indexAllPages();
