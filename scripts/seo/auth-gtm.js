#!/usr/bin/env node
const { google } = require('googleapis');
const readline = require('readline');
const fs = require('fs');

const envPath = '.env.local';

let envContent = '';
if (fs.existsSync(envPath)) {
  envContent = fs.readFileSync(envPath, 'utf8');
}
const envVars = {};
envContent.split('\n').forEach(line => {
  const match = line.match(/^export\s+([^=]+)=['"]?(.*?)['"]?$/);
  if (match) envVars[match[1]] = match[2];
});

const clientId = envVars['GTM_CLIENT_ID'] || envVars['GOOGLE_ADS_CLIENT_ID'];
const clientSecret = envVars['GTM_CLIENT_SECRET'] || envVars['GOOGLE_ADS_CLIENT_SECRET'];

if (!clientId || !clientSecret) {
  console.error("❌ No se encontró GOOGLE_ADS_CLIENT_ID ni GOOGLE_ADS_CLIENT_SECRET en .env.local");
  process.exit(1);
}

const oauth2Client = new google.auth.OAuth2(
  clientId,
  clientSecret,
  'http://localhost'
);

const url = oauth2Client.generateAuthUrl({
  access_type: 'offline',
  scope: ['https://www.googleapis.com/auth/tagmanager.edit.containers', 'https://www.googleapis.com/auth/tagmanager.publish'],
  prompt: 'consent'
});

console.log('\n======================================================');
console.log('Falta autorizar tu sesión para Google Tag Manager.');
console.log('Como gcloud no está instalado, usaremos este script directo.\n');
console.log('1. Haz clic o copia este enlace y ábrelo en tu navegador:');
console.log('\n' + url + '\n');
console.log('2. Inicia sesión con nuvanx@gmail.com y acepta los permisos.');
console.log('3. Te redirigirá a una página de error o a localhost.');
console.log('   Copia la URL completa donde has acabado (debería tener un ?code=...)');
console.log('======================================================\n');

const rl = readline.createInterface({
  input: process.stdin,
  output: process.stdout
});

rl.question('Pega aquí la URL completa a la que fuiste redirigido: ', async (codeUrl) => {
  let code = codeUrl;
  
  if (codeUrl.includes('code=')) {
    const urlObj = new URL(codeUrl);
    code = urlObj.searchParams.get('code');
  }

  if (!code) {
    console.error('❌ No se encontró un código de autorización válido.');
    process.exit(1);
  }

  try {
    console.log('\nIntercambiando código por tokens...');
    const { tokens } = await oauth2Client.getToken(code);
    
    if (tokens.refresh_token) {
      console.log('✅ Refresh Token obtenido exitosamente.');

      const newExportLine = `export GTM_REFRESH_TOKEN='${tokens.refresh_token}'`;
      let currentContent = '';

      if (fs.existsSync(envPath)) {
        currentContent = fs.readFileSync(envPath, 'utf8');
        const lines = currentContent.split('\n');
        const existingIndex = lines.findIndex((line) =>
          line.trim().startsWith('export GTM_REFRESH_TOKEN=')
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

      fs.writeFileSync(envPath, currentContent);
      console.log(`✅ Token guardado automáticamente en ${envPath}`);
      console.log('\n¡Todo listo! Ahora ejecuta tu script original:');
      console.log('source .env.local && node scripts/seo/setup-gtm-conversion-trigger.js\n');
    } else {
      console.error('❌ Google no devolvió un refresh_token (quizás no forzó el consentimiento).');
    }
  } catch (error) {
    console.error('❌ Error intercambiando tokens:', error.message);
    if (error.message.includes('redirect_uri_mismatch')) {
      console.error('\n⚠️ El OAuth Client configurado no permite redirecciones a http://localhost.');
      console.error('Solución: En Google Cloud Console > APIs & Services > Credentials, añade "http://localhost" como Authorized Redirect URI en tu OAuth 2.0 Client ID.');
    }
  }
  
  rl.close();
});
