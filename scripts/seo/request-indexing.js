#!/usr/bin/env node

/**
 * Request indexing of specific URLs using Google Indexing API v3.
 *
 * NOTE ON GOOGLE POLICY & SCOPE:
 * According to Google's official documentation, the Indexing API is intended
 * for pages containing JobPosting or BroadcastEvent structured data.
 * For general medical treatment pages, submitting/updating XML sitemaps
 * via Search Console API or GSC UI is the recommended mechanism for organic indexing.
 *
 * Authenticates via Google OAuth 2.0 / ADC / Service Account.
 */

const { google } = require('googleapis');
const fs = require('fs');
const path = require('path');

const args = process.argv.slice(2);
let urls = [];

for (let i = 0; i < args.length; i++) {
  if (args[i] === '--urls' && args[i + 1]) {
    urls = args[++i].split(',').map(u => u.trim());
  }
}

if (urls.length === 0) {
  urls = [
    'https://nuvanx.com/tratamiento-postparto-abdomen-contorno-corporal-madrid/',
    'https://nuvanx.com/rinomodelacion-sin-cirugia-madrid-guia/'
  ];
}

async function publishUrlNotification(indexing, targetUrl) {
  const response = await indexing.urlNotifications.publish({
    requestBody: {
      url: targetUrl,
      type: 'URL_UPDATED'
    }
  });
  return response.data;
}

async function main() {
  console.log('Autenticando con Google Indexing API (ADC / Service Account)...');
  console.log('ℹ️ Nota: La API de Indexación de Google está orientada formalmente a JobPosting/BroadcastEvent.');
  console.log('   Para páginas de tratamiento web, las sitemaps XML y GSC son la vía canonical.\n');

  const credentialsPath = path.join(__dirname, 'credentials.json');
  let auth;
  if (process.env.GOOGLE_ACCESS_TOKEN) {
    const { OAuth2Client } = require('google-auth-library');
    auth = new OAuth2Client();
    auth.setCredentials({ access_token: process.env.GOOGLE_ACCESS_TOKEN });
  } else if (fs.existsSync(credentialsPath)) {
    const credentials = JSON.parse(fs.readFileSync(credentialsPath, 'utf8'));
    auth = new google.auth.GoogleAuth({
      credentials,
      scopes: ['https://www.googleapis.com/auth/indexing']
    });
  } else {
    auth = new google.auth.GoogleAuth({
      scopes: ['https://www.googleapis.com/auth/indexing']
    });
  }

  const indexing = google.indexing({ version: 'v3', auth });

  console.log(`Enviando ${urls.length} URLs a Google Indexing API...\n`);

  for (const url of urls) {
    try {
      console.log(`--> Solicitando notificación de actualización para: ${url}`);
      const result = await publishUrlNotification(indexing, url);
      console.log(`✅ Notificación procesada por Google Indexing API.`);
      console.log(`   notifyTime: ${result.urlNotificationMetadata?.latestUpdate?.notifyTime || 'N/A'}\n`);
    } catch (error) {
      console.error(`❌ Error en ${url}:`, error.message);
      if (error.response?.data) {
        console.error('   Detalles:', JSON.stringify(error.response.data, null, 2));
      }
    }
  }
}

main().catch(err => {
  console.error('Fatal error en Indexing API:', err);
  process.exit(1);
});
