#!/usr/bin/env node

/**
 * Search Console verification script
 * Checks Googlebot access, indexing status, and coverage
 */

const { google } = require('googleapis');
const fs = require('fs');
const path = require('path');

// Parse command line arguments
const args = process.argv.slice(2);
let property = '';
let url = '';

for (let i = 0; i < args.length; i++) {
  if (args[i] === '--property' && args[i + 1]) {
    property = args[i + 1];
    i++;
  } else if (args[i] === '--url' && args[i + 1]) {
    url = args[i + 1];
    i++;
  }
}

if (!property || !url) {
  console.error('Error: --property and --url are required');
  console.error('Usage: node search-console-check.js --property <property> --url <url>');
  process.exit(1);
}

async function checkSearchConsole() {
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
      scopes: ['https://www.googleapis.com/auth/webmasters.readonly']
    });

    const searchconsole = google.searchconsole({ version: 'v1', auth });

    console.log(`Checking Search Console for property: ${property}`);
    console.log(`Target URL: ${url}`);

    // Check URL inspection
    try {
      const inspection = await searchconsole.urlInspection.index.inspect({
        requestBody: {
          inspectionUrl: url,
          siteUrl: property,
          languageCode: 'es'
        }
      });

      const result = inspection.data.inspectionResult;

      console.log('INDEXED_STATUS=' + (result.indexStatusResult?.lastCrawlTime || 'NOT_INDEXED'));
      console.log('COVERAGE=' + (result.indexStatusResult?.coverageState || 'UNKNOWN'));
      console.log('ROBOTS_TXT=' + (result.indexStatusResult?.robotsTxtState || 'UNKNOWN'));
      console.log('GOOGLEBOT_ACCESS=' + (result.indexStatusResult?.googlebotState || 'UNKNOWN'));
      console.log('CRAWL_ERRORS=' + (result.indexStatusResult?.crawlingState || 'NONE'));
      console.log('INDEXING_ALLOWED=' + (result.indexStatusResult?.indexingState || 'UNKNOWN'));
      console.log('LAST_CRAWL=' + (result.indexStatusResult?.lastCrawlTime || 'NEVER'));
      console.log('CANONICAL=' + (result.indexStatusResult?.canonical || 'NONE'));
      console.log('REFERRING_URLS=' + (result.pageInspectionResult?.referringUrls?.length || 0));

      // Check if Googlebot is blocked
      const googlebotState = result.indexStatusResult?.googlebotState;
      if (googlebotState === 'ALLOWED') {
        console.log('GOOGLEBOT_BLOCKED=false');
      } else {
        console.log('GOOGLEBOT_BLOCKED=true');
        console.log('WARNING=Googlebot may be blocked');
      }

      console.log('SEARCH_CONSOLE_CHECK=true');
      console.log('STATUS=SUCCESS');

    } catch (error) {
      console.error('Error during URL inspection:', error.message);
      console.log('SEARCH_CONSOLE_CHECK=false');
      console.log('STATUS=ERROR');
      console.log('ERROR=' + error.message);
      process.exit(1);
    }

  } catch (error) {
    console.error('Fatal error:', error.message);
    console.log('SEARCH_CONSOLE_CHECK=false');
    console.log('STATUS=FATAL');
    console.log('ERROR=' + error.message);
    process.exit(1);
  }
}

checkSearchConsole();
