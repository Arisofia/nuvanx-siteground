const { google } = require('googleapis');
const readline = require('node:readline');
const fs = require('node:fs');
const path = require('node:path');

if (process.env.CI === 'true' || process.env.GITHUB_ACTIONS === 'true' || !process.stdin.isTTY || !process.stdout.isTTY) {
  console.error('REFRESH_TOKEN_HELPER=REFUSED: this interactive credential helper may only run in a private local TTY.');
  process.exit(2);
}

const envPath = path.resolve(__dirname, '../../.env.local');

function loadEnvVars(filePath) {
  const envVars = {};
  if (fs.existsSync(filePath)) {
    const envContent = fs.readFileSync(filePath, 'utf8');
    envContent.split('\n').forEach((line) => {
      const trimmed = line.trim();
      if (!trimmed || trimmed.startsWith('#')) return;
      const match = /^(?:export\s+)?([A-Za-z_][A-Za-z0-9_]*)=(.*)$/.exec(trimmed);
      if (!match) return;
      const key = match[1];
      let val = match[2].trim();
      if ((val.startsWith("'") && val.endsWith("'")) || (val.startsWith('"') && val.endsWith('"'))) {
        val = val.slice(1, -1);
      }
      envVars[key] = val;
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
    'https://www.googleapis.com/auth/tagmanager.edit.containerversions',
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

const { persistRefreshToken, sanitizeGtmError } = require('./gtm-utils');

printInstructions(url);

const rl = readline.createInterface({
  input: process.stdin,
  output: process.stdout
});

rl.question('Pega aquí la URL completa a la que fuiste redirigido: ', async (codeUrl) => {
  const code = extractAuthCode(codeUrl);
  if (!code) {
    console.error('❌ No se encontró un código de autorización válido.');
    process.exitCode = 1;
    rl.close();
    return;
  }

  let tokens;
  try {
    console.log('\nIntercambiando código por tokens...');
    const res = await oauth2Client.getToken(code);
    tokens = res.tokens;
  } catch (error) {
    console.error('❌ Error intercambiando tokens:', sanitizeGtmError(error));
    if (error.message && error.message.includes('redirect_uri_mismatch')) {
      console.error('\n⚠️ El OAuth Client configurado no permite redirecciones a http://localhost.');
      console.error('Solución: En Google Cloud Console > APIs & Services > Credentials, añade "http://localhost" como Authorized Redirect URI en tu OAuth 2.0 Client ID.');
    }
    process.exitCode = 1;
    rl.close();
    return;
  }

  if (!tokens?.refresh_token) {
    console.error('❌ Google no devolvió un refresh_token (quizás no forzó el consentimiento con prompt=consent).');
    process.exitCode = 1;
    rl.close();
    return;
  }

  console.log('✅ Refresh Token obtenido exitosamente de Google OAuth.');

  try {
    persistRefreshToken(envPath, tokens.refresh_token);
    console.log('\n¡Todo listo! Ahora ejecuta tu script de configuración:');
    console.log('source .env.local && GTM_CONFIRM_PUBLISH=yes node scripts/seo/setup-gtm-conversion-trigger.js\n');
  } catch (persistErr) {
    console.error(`\n⚠️ No se pudo guardar automáticamente el token en ${envPath}: ${persistErr.message}`);
    console.log('\nGuarda el refresh token manualmente en .env.local mediante un canal seguro.');
    console.log('El valor no se imprimirá en la consola.');
  } finally {
    rl.close();
  }
});
