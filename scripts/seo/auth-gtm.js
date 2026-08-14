const { google } = require('googleapis');
const readline = require('node:readline');
const fs = require('node:fs');
const path = require('node:path');

const REFUSAL_MARKER = 'GTM_AUTH_HELPER=REFUSED';
const RELEVANT_ENV_KEYS = [
  'GTM_CLIENT_ID',
  'GTM_CLIENT_SECRET',
  'GOOGLE_ADS_CLIENT_ID',
  'GOOGLE_ADS_CLIENT_SECRET',
];

if (process.env.CI === 'true' || process.env.GITHUB_ACTIONS === 'true' || !process.stdin.isTTY || !process.stdout.isTTY) {
  console.error(`${REFUSAL_MARKER}: this interactive credential helper may only run in a private local TTY.`);
  process.exit(2);
}

const envPath = path.resolve(__dirname, '../../.env.local');

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

  for (const key of RELEVANT_ENV_KEYS) {
    const value = process.env[key];
    if (value !== undefined && value !== '') envVars[key] = value;
  }
  return envVars;
}

const envVars = loadEnvVars(envPath);
const clientId = envVars.GTM_CLIENT_ID || envVars.GOOGLE_ADS_CLIENT_ID;
const clientSecret = envVars.GTM_CLIENT_SECRET || envVars.GOOGLE_ADS_CLIENT_SECRET;

if (!clientId || !clientSecret) {
  console.error(`${REFUSAL_MARKER}: set GTM_CLIENT_ID/GOOGLE_ADS_CLIENT_ID and GTM_CLIENT_SECRET/GOOGLE_ADS_CLIENT_SECRET in .env.local or environment.`);
  process.exit(2);
}

const oauth2Client = new google.auth.OAuth2(clientId, clientSecret, 'http://localhost');

const url = oauth2Client.generateAuthUrl({
  access_type: 'offline',
  scope: [
    'https://www.googleapis.com/auth/tagmanager.edit.containers',
    'https://www.googleapis.com/auth/tagmanager.edit.containerversions',
    'https://www.googleapis.com/auth/tagmanager.publish',
  ],
  prompt: 'consent',
});

function printInstructions(authUrl) {
  console.log('\n======================================================');
  console.log('Falta autorizar tu sesión para Google Tag Manager.');
  console.log('Este helper solo debe ejecutarse en un terminal privado local.\n');
  console.log('1. Haz clic o copia este enlace y ábrelo en tu navegador:');
  console.log('\n' + authUrl + '\n');
  console.log('2. Inicia sesión con la cuenta autorizada para NUVANX y acepta los permisos.');
  console.log('3. Te redirigirá a localhost. Copia la URL completa con ?code=...');
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

function shellSingleQuote(value) {
  const escaped = String(value).replaceAll("'", "'\"'\"'");
  return `'${escaped}'`;
}

function persistRefreshToken(filePath, refreshToken) {
  const token = String(refreshToken || '');
  if (!token || /[\r\n\0]/.test(token)) {
    throw new Error('OAuth refresh token is empty or contains a line-breaking/NUL character; refusing to serialize it.');
  }

  const newExportLine = `export GTM_REFRESH_TOKEN=${shellSingleQuote(token)}`;
  let lines = [];

  if (fs.existsSync(filePath)) {
    lines = fs.readFileSync(filePath, 'utf8').split('\n');
  }

  const hadExisting = lines.some((line) => /^(?:export\s+)?GTM_REFRESH_TOKEN=/.test(line.trim()));
  lines = lines.filter((line) => !/^(?:export\s+)?GTM_REFRESH_TOKEN=/.test(line.trim()));
  while (lines.length > 0 && lines[lines.length - 1] === '') lines.pop();
  lines.push(newExportLine, '');

  fs.writeFileSync(filePath, lines.join('\n'), { mode: 0o600 });
  try {
    fs.chmodSync(filePath, 0o600);
  } catch {
    console.warn('⚠️ No se pudo reforzar chmod 0600 en esta plataforma; verifica manualmente los permisos de .env.local.');
  }

  console.log(hadExisting
    ? 'ℹ️ Se eliminaron asignaciones GTM_REFRESH_TOKEN anteriores y se escribió una única asignación canónica.'
    : 'ℹ️ Se añadió una única asignación GTM_REFRESH_TOKEN canónica.');
  console.log(`✅ Token guardado en ${filePath} con permisos 0600.`);
}

function sanitizeOAuthError(error) {
  const status = String(error?.code || error?.response?.status || 'UNKNOWN').replace(/[^a-zA-Z0-9_.-]/g, '').slice(0, 80);
  const reason = String(error?.response?.data?.error || error?.name || 'OAUTH_ERROR').replace(/[^a-zA-Z0-9_.-]/g, '').slice(0, 80);
  return `status=${status || 'UNKNOWN'} reason=${reason || 'OAUTH_ERROR'}`;
}

printInstructions(url);

const rl = readline.createInterface({ input: process.stdin, output: process.stdout });

rl.question('Pega aquí la URL completa a la que fuiste redirigido: ', async (codeUrl) => {
  const code = extractAuthCode(codeUrl);
  if (!code) {
    console.error('❌ No se encontró un código de autorización válido.');
    process.exitCode = 1;
    rl.close();
    return;
  }

  try {
    console.log('\nIntercambiando código por tokens...');
    const { tokens } = await oauth2Client.getToken(code);

    if (tokens.refresh_token) {
      console.log('✅ Refresh Token obtenido exitosamente.');
      persistRefreshToken(envPath, tokens.refresh_token);
      console.log('\n¡Todo listo! Ahora configura explícitamente los IDs objetivo y ejecuta:');
      console.log('source .env.local && GTM_CONFIRM_PUBLISH=yes node scripts/seo/setup-gtm-conversion-trigger.js\n');
    } else {
      console.error('❌ Google no devolvió un refresh_token.');
      process.exitCode = 1;
    }
  } catch (error) {
    console.error(`❌ Error intercambiando tokens: ${sanitizeOAuthError(error)}`);
    process.exitCode = 1;
  } finally {
    rl.close();
  }
});
