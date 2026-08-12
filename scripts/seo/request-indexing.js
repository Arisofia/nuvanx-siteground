#!/usr/bin/env node

/**
 * Request indexing of specific URLs using Google Indexing API v3.
 * Autentica mediante Google OAuth 2.0 / ADC / Service Account (gsc-sitemap-reader@nuvanx.iam.gserviceaccount.com).
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

  console.log(`Enviando ${urls.length} URLs a Googlebot para indexación inmediata...\n`);

  for (const url of urls) {
    try {
      console.log(`--> Solicitando indexación para: ${url}`);
      const result = await publishUrlNotification(indexing, url);
      console.log(`✅ ¡ÉXITO! En enviado a Google Indexing API.`);
      console.log(`   Notificación: notifyTime=${result.urlNotificationMetadata?.latestUpdate?.notifyTime || 'N/A'}\n`);
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
