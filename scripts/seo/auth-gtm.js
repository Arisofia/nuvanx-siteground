#!/usr/bin/env node
const { google } = require('googleapis');
const readline = require('node:readline');
const fs = require('node:fs');

if (process.env.CI === 'true' || process.env.GITHUB_ACTIONS === 'true' || !process.stdin.isTTY || !process.stdout.isTTY) {
  console.error('REFRESH_TOKEN_HELPER=REFUSED: this interactive credential helper may only run in a private local TTY.');
  process.exit(2);
}

const envPath = '.env.local';

function loadEnvVars(filePath) {
  const envVars = {};
  if (fs.existsSync(filePath)) {
    const envContent = fs.readFileSync(filePath, 'utf8');
    const envLineRegex = /^(?:export\s+)?(\w+)=['"]?(.*?)['"]?$/;
    envContent.split('\n').forEach((line) => {
      const match = envLineRegex.exec(line.trim());
      if (match) envVars[match[1]] = match[2];
    });
  }
  for (const [k, v] of Object.entries(process.env)) {
    if (v !== undefined && v !== '') envVars[k] = v;
  }
  return envVars;
}

const envVars = loadEnvVars(envPath);
const clientId = envVars['GTM_CLIENT_ID'] || envVars['GOOGLE_ADS_CLIENT_ID'];
const clientSecret = envVars['GTM_CLIENT_SECRET'] || envVars['GOOGLE_ADS_CLIENT_SECRET'];

if (!clientId || !clientSecret) {
  console.error('REFRESH_TOKEN_HELPER=REFUSED: set GTM_CLIENT_ID/GOOGLE_ADS_CLIENT_ID and GTM_CLIENT_SECRET/GOOGLE_ADS_CLIENT_SECRET in .env.local or environment.');
  process.exit(2);
}

const oauth2Client = new google.auth.OAuth2(
  clientId,
  clientSecret,
  'http://localhost'
);

const url = oauth2Client.generateAuthUrl({
  access_type: 'offline',
  scope: [
    'https://www.googleapis.com/auth/tagmanager.edit.containers',
    'https://www.googleapis.com/auth/tagmanager.publish'
  ],
  prompt: 'consent'
});

function printInstructions(authUrl) {
  console.log('\n======================================================');
  console.log('Falta autorizar tu sesión para Google Tag Manager.');
  console.log('Como gcloud no está instalado, usaremos este script directo.\n');
  console.log('1. Haz clic o copia este enlace y ábrelo en tu navegador:');
  console.log('\n' + authUrl + '\n');
  console.log('2. Inicia sesión con nuvanx@gmail.com y acepta los permisos.');
  console.log('3. Te redirigirá a una página de error o a localhost.');
  console.log('   Copia la URL completa donde has acabado (debería tener un ?code=...)');
  console.log('======================================================\n');
}

function extractAuthCode(codeUrl) {
  const raw = String(codeUrl || '').trim();
  if (!raw.includes('code=')) return raw;
  try {
    const urlObj = new URL(raw, 'http://localhost');
    return urlObj.searchParams.get('code') || raw;
  } catch {
    const match = /code=([^&]+)/.exec(raw);
    return match ? decodeURIComponent(match[1]) : raw;
  }
}

function persistRefreshToken(filePath, refreshToken) {
  const newExportLine = `export GTM_REFRESH_TOKEN='${refreshToken}'`;
  let currentContent = '';

  if (fs.existsSync(filePath)) {
    currentContent = fs.readFileSync(filePath, 'utf8');
    const lines = currentContent.split('\n');
    const existingIndex = lines.findIndex((line) =>
      /^(?:export\s+)?GTM_REFRESH_TOKEN=/.test(line.trim())
    );

    if (existingIndex !== -1) {
      lines[existingIndex] = newExportLine;
      currentContent = lines.join('\n');
      console.log('ℹ️ GTM_REFRESH_TOKEN ya existía en .env.local; se ha actualizado su valor.');
    } else {
      currentContent += (currentContent.endsWith('\n') ? '' : '\n') + newExportLine + '\n';
    }
  } else {
    currentContent = `${newExportLine}\n`;
  }

  fs.writeFileSync(filePath, currentContent);
  console.log(`✅ Token guardado automáticamente en ${filePath}`);
}

printInstructions(url);

const rl = readline.createInterface({
  input: process.stdin,
  output: process.stdout
});

rl.question('Pega aquí la URL completa a la que fuiste redirigido: ', async (codeUrl) => {
  const code = extractAuthCode(codeUrl);
  if (!code) {
    console.error('❌ No se encontró un código de autorización válido.');
    process.exit(1);
  }

  try {
    console.log('\nIntercambiando código por tokens...');
    const { tokens } = await oauth2Client.getToken(code);
    
    if (tokens.refresh_token) {
      console.log('✅ Refresh Token obtenido exitosamente.');
      persistRefreshToken(envPath, tokens.refresh_token);
      console.log('\n¡Todo listo! Ahora ejecuta tu script original:');
      console.log('source .env.local && node scripts/seo/setup-gtm-conversion-trigger.js\n');
    } else {
      console.error('❌ Google no devolvió un refresh_token (quizás no forzó el consentimiento).');
      process.exitCode = 1;
    }
  } catch (error) {
    console.error('❌ Error intercambiando tokens:', error.message);
    if (error.message.includes('redirect_uri_mismatch')) {
      console.error('\n⚠️ El OAuth Client configurado no permite redirecciones a http://localhost.');
      console.error('Solución: En Google Cloud Console > APIs & Services > Credentials, añade "http://localhost" como Authorized Redirect URI en tu OAuth 2.0 Client ID.');
    }
    process.exitCode = 1;
  } finally {
    rl.close();
  }
});
