'use strict';

const fs = require('node:fs');

/**
 * Sanitizes GTM and OAuth API error objects to prevent leaking tokens or query secrets.
 *
 * @param {Error|object} err
 * @returns {string}
 */
function sanitizeGtmError(err) {
  if (!err) return 'UNKNOWN_ERROR';
  const status = err.code || err.response?.status || 'UNKNOWN';
  const rawMsg = err.response?.data?.error?.message || err.message || 'Fatal error';
  const cleanMsg = String(rawMsg).replace(/(key|secret|token|credential)=([^\s&]+)/gi, '$1=[REDACTED]');
  return `HTTP ${status}: ${cleanMsg}`;
}

/**
 * Validates and safely persists an OAuth refresh token to a shell-sourced file.
 *
 * @param {string} filePath - Target file (e.g., .env.local)
 * @param {string} refreshToken - OAuth refresh token
 * @param {string} [varName='GTM_REFRESH_TOKEN'] - Environment variable name to export
 */
function persistRefreshToken(filePath, refreshToken, varName = 'GTM_REFRESH_TOKEN') {
  const token = String(refreshToken || '').trim();
  if (!token || /[\r\n'\\]/.test(token)) {
    throw new Error('OAuth refresh token contains invalid characters (newlines, quotes, or backslashes) for shell export.');
  }
  const newExportLine = `export ${varName}='${token}'`;
  let currentContent = '';

  if (fs.existsSync(filePath)) {
    currentContent = fs.readFileSync(filePath, 'utf8');
    const lines = currentContent.split('\n');
    const regex = new RegExp(`^(?:export\\s+)?${varName}=`);
    const existingIndex = lines.findIndex((line) => regex.test(line.trim()));

    if (existingIndex !== -1) {
      lines[existingIndex] = newExportLine;
      currentContent = lines.join('\n');
      console.log(`ℹ️ ${varName} ya existía en ${filePath}; se ha actualizado su valor.`);
    } else {
      currentContent += (currentContent.endsWith('\n') ? '' : '\n') + newExportLine + '\n';
    }
  } else {
    currentContent = `${newExportLine}\n`;
  }

  fs.writeFileSync(filePath, currentContent, { mode: 0o600 });
  try {
    fs.chmodSync(filePath, 0o600);
  } catch {
    // Non-POSIX platforms / unsupported filesystems
  }
  console.log(`✅ Token guardado automáticamente en ${filePath}`);
}

module.exports = {
  sanitizeGtmError,
  persistRefreshToken,
};
