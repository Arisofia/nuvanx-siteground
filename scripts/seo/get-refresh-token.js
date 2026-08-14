const { OAuth2Client } = require('google-auth-library');
const readline = require('node:readline');

if (process.env.CI === 'true' || process.env.GITHUB_ACTIONS === 'true' || !process.stdin.isTTY || !process.stdout.isTTY) {
  console.error('REFRESH_TOKEN_HELPER=REFUSED: this interactive credential helper may only run in a private local TTY.');
  process.exit(2);
}

const CLIENT_ID = String(process.env.GOOGLE_ADS_CLIENT_ID || '').trim();
const CLIENT_SECRET = String(process.env.GOOGLE_ADS_CLIENT_SECRET || '').trim();
const REDIRECT_URI = 'http://localhost';

if (!CLIENT_ID || !CLIENT_SECRET) {
  console.error('REFRESH_TOKEN_HELPER=REFUSED: set GOOGLE_ADS_CLIENT_ID and GOOGLE_ADS_CLIENT_SECRET in the local shell.');
  process.exit(2);
}

const oAuth2Client = new OAuth2Client(CLIENT_ID, CLIENT_SECRET, REDIRECT_URI);
const authUrl = oAuth2Client.generateAuthUrl({
  access_type: 'offline',
  prompt: 'consent',
  scope: ['https://www.googleapis.com/auth/adwords'],
});

console.log('1. Abre esta URL en un navegador privado/local:');
console.log('\n', authUrl, '\n');

const rl = readline.createInterface({
  input: process.stdin,
  output: process.stdout,
});

rl.question('2. Pega el código de autorización: ', async (code) => {
  try {
    const { tokens } = await oAuth2Client.getToken(decodeURIComponent(String(code || '').trim()));
    const refreshToken = String(tokens.refresh_token || '');
    if (!refreshToken) {
      console.error('No se recibió refresh_token. Revoca el consentimiento anterior o repite con prompt=consent.');
      process.exitCode = 1;
      return;
    }
    console.log('\nRefresh token generado. Se mostrará una sola vez en este TTY privado:\n');
    console.log(refreshToken);
    console.log('\nGuárdalo inmediatamente en el gestor de secretos correspondiente; no lo pegues en issues, PRs ni logs compartidos.\n');
  } catch (error) {
    console.error('Error obteniendo el token:', error?.name || 'OAuthError');
    process.exitCode = 1;
  } finally {
    rl.close();
  }
});
