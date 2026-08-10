const { OAuth2Client } = require('google-auth-library');
const readline = require('readline');

// Asegúrate de que las variables de entorno están cargadas o configúralas manualmente aquí
const CLIENT_ID = process.env.GOOGLE_ADS_CLIENT_ID || 'INTRODUCE_TU_CLIENT_ID_AQUI';
const CLIENT_SECRET = process.env.GOOGLE_ADS_CLIENT_SECRET || 'INTRODUCE_TU_CLIENT_SECRET_AQUI';
const REDIRECT_URI = 'http://localhost'; // El redirect estándar para apps de escritorio

const oAuth2Client = new OAuth2Client(CLIENT_ID, CLIENT_SECRET, REDIRECT_URI);

const authUrl = oAuth2Client.generateAuthUrl({
  access_type: 'offline',
  prompt: 'consent', // Fuerza que devuelva un refresh token
  scope: ['https://www.googleapis.com/auth/adwords'],
});

console.log('1. Abre esta URL en tu navegador:');
console.log('\n', authUrl, '\n');

const rl = readline.createInterface({
  input: process.stdin,
  output: process.stdout,
});

rl.question('2. Ingresa el código de autorización que te da la página (en la URL después de code= o en la pantalla si da error de localhost): ', async (code) => {
  try {
    const { tokens } = await oAuth2Client.getToken(decodeURIComponent(code));
    console.log('\n¡Éxito! Aquí tienes tu nuevo Refresh Token:\n');
    console.log(tokens.refresh_token);
    console.log('\nCopia este valor y ponlo en el GitHub Secret GOOGLE_ADS_REFRESH_TOKEN.\n');
  } catch (error) {
    console.error('Error obteniendo el token:', error.message);
  }
  rl.close();
});
