import fs from 'node:fs/promises';

const manifestUrl = new URL('./published-pages-manifest.json', import.meta.url);

export const MIN_MANIFEST_ENTRIES = 40;

function normalizePath(value) {
  const path = String(value || '').split(/[?#]/, 1)[0] || '/';
  return path.endsWith('/') ? path : `${path}/`;
}

export async function loadPublishedPagesManifest() {
  const manifest = JSON.parse(await fs.readFile(manifestUrl, 'utf8'));
  if (!Array.isArray(manifest) || manifest.length === 0) {
    throw new Error('Canonical published-page manifest must be a non-empty array');
  }
  if (manifest.length < MIN_MANIFEST_ENTRIES) {
    throw new Error(`Canonical published-page manifest has only ${manifest.length} entries; minimum ${MIN_MANIFEST_ENTRIES} required to prevent accidental truncation`);
  }

  for (const page of manifest) {
    if (!page || typeof page.path !== 'string' || page.path.trim() === '') {
      throw new Error('Canonical published-page manifest contains entry with missing or empty path');
    }
  }

  const paths = manifest.map((page) => normalizePath(page.path));
  if (paths.some((path) => !path.startsWith('/')) || new Set(paths).size !== paths.length) {
    throw new Error('Canonical published-page manifest contains invalid or duplicate paths');
  }

  return manifest;
}

export function assertCanonicalPublishedPaths(actualPaths, manifest, sourceLabel) {
  const actual = new Set([...actualPaths].map(normalizePath));
  const missing = manifest.map((page) => normalizePath(page.path)).filter((path) => !actual.has(path));
  if (missing.length > 0) {
    throw new Error(`${sourceLabel} is missing canonical published paths: ${missing.join(', ')}`);
  }
}
