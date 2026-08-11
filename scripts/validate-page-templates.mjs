#!/usr/bin/env node

/**
 * WordPress Page Template + Publication Topology Validator
 *
 * Validates that:
 * - the authenticated WordPress publication inventory matches the canonical
 *   version-controlled manifest;
 * - published pages with custom templates reference files that exist;
 * - the required canonical template files exist in the current theme.
 *
 * Optional env:
 * - WORDPRESS_PAGES_FILE: trusted JSON snapshot from authenticated WP-CLI.
 * - WORDPRESS_URL: REST fallback when no snapshot is supplied.
 */

import { readFileSync, existsSync } from 'fs';
import { join, dirname } from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);
const THEME_ROOT = join(__dirname, '..', 'wp-content', 'themes', 'nuvanx-medical');
const TEMPLATES_DIR = join(THEME_ROOT, 'templates');
const MANIFEST_FILE = join(__dirname, 'staging2', 'published-pages-manifest.json');

const VALID_TEMPLATES = [
  'page-contacto.php',
  'page-landing-valoracion.php',
  'page-sede.php',
  'page-soluciones-medicas.php',
];

function parsePagesJson(raw, source) {
  let pages;
  try {
    pages = JSON.parse(raw);
  } catch (error) {
    throw new Error(`Invalid page JSON from ${source}: ${error.message}`);
  }
  if (!Array.isArray(pages)) {
    throw new TypeError(`Invalid page payload from ${source}: expected an array`);
  }
  return pages;
}

function loadManifest() {
  if (!existsSync(MANIFEST_FILE)) {
    throw new Error(`Canonical published-page manifest is missing: ${MANIFEST_FILE}`);
  }
  const manifest = parsePagesJson(readFileSync(MANIFEST_FILE, 'utf8'), MANIFEST_FILE);
  if (manifest.length === 0) {
    throw new Error('Canonical published-page manifest must not be empty');
  }

  const ids = manifest.map((page) => Number(page.id));
  if (new Set(ids).size !== manifest.length || ids.some((id) => !Number.isInteger(id) || id <= 0)) {
    throw new Error('Canonical published-page manifest contains invalid or duplicate IDs');
  }

  const paths = manifest.map((page) => String(page.path || ''));
  if (new Set(paths).size !== manifest.length || paths.some((path) => !path.startsWith('/'))) {
    throw new Error('Canonical published-page manifest contains invalid or duplicate paths');
  }

  return manifest;
}

async function fetchPublishedPages() {
  const pagesFile = process.env.WORDPRESS_PAGES_FILE;
  if (pagesFile) {
    if (!existsSync(pagesFile)) {
      throw new Error(`WORDPRESS_PAGES_FILE does not exist: ${pagesFile}`);
    }
    return parsePagesJson(readFileSync(pagesFile, 'utf8'), pagesFile);
  }

  const baseUrl = process.env.WORDPRESS_URL || 'https://nuvanx.com';
  const endpoint = `${baseUrl}/wp-json/wp/v2/pages?per_page=100&status=publish&_fields=id,slug,template`;
  const response = await fetch(endpoint, { headers: { Accept: 'application/json' } });
  const body = await response.text();
  if (!response.ok) {
    throw new Error(`Failed to fetch pages: ${response.status} ${response.statusText}`);
  }
  const contentType = response.headers.get('content-type') || '';
  if (!contentType.toLowerCase().includes('application/json')) {
    throw new Error(`Expected JSON from ${endpoint}; got ${contentType || 'unknown content-type'}`);
  }
  return parsePagesJson(body, endpoint);
}

function validatePublicationTopology(pages, manifest) {
  const errors = [];
  const actualById = new Map(pages.map((page) => [Number(page.id), page]));
  const expectedIds = new Set(manifest.map((page) => Number(page.id)));

  for (const expected of manifest) {
    const id = Number(expected.id);
    const actual = actualById.get(id);
    if (!actual) {
      errors.push(`Manifest page ${id} (${expected.path}) is not published in WordPress`);
      continue;
    }

    const expectedSlug = String(expected.slug || '').trim();
    if (expectedSlug && String(actual.slug || '') !== expectedSlug) {
      errors.push(`Page ${id} slug drift: WordPress=${actual.slug || '(empty)'}, manifest=${expectedSlug}`);
    }
  }

  const extraPages = pages.filter((page) => !expectedIds.has(Number(page.id)));
  if (extraPages.length > 0) {
    console.log(`ℹ️ ${extraPages.length} additional published page(s) present in WordPress (IDs: ${extraPages.map((p) => p.id).join(', ')})`);
  }

  return errors;
}

function templateExists(templatePath) {
  if (!templatePath || templatePath === '' || templatePath === 'default') return true;
  if (existsSync(join(TEMPLATES_DIR, templatePath))) return true;
  if (existsSync(join(THEME_ROOT, templatePath))) return true;
  return false;
}

async function validateTemplates() {
  console.log('🔍 Validating WordPress publication topology and page templates...\n');

  try {
    const manifest = loadManifest();
    const pages = await fetchPublishedPages();
    console.log(`📄 Published page inventory loaded: ${pages.length} pages`);
    console.log(`📋 Canonical manifest loaded: ${manifest.length} pages\n`);

    const topologyErrors = validatePublicationTopology(pages, manifest);
    const templateErrors = [];

    for (const page of pages) {
      const template = page.template || '';
      if (template && !templateExists(template)) {
        templateErrors.push(`Page ${page.id} (${page.slug}): template file missing: ${template}`);
      } else if (template) {
        console.log(`✅ Page ${page.id} (${page.slug}): templates/${template} exists`);
      }
    }

    console.log('\n🔍 Verifying expected template files exist...\n');
    for (const templateName of VALID_TEMPLATES) {
      const templatePath = join(TEMPLATES_DIR, templateName);
      if (!existsSync(templatePath)) {
        templateErrors.push(`Required template file missing: templates/${templateName}`);
      } else {
        console.log(`✅ ${templateName} exists`);
      }
    }

    const allErrors = [...topologyErrors, ...templateErrors];

    console.log('\n' + '='.repeat(60));
    if (allErrors.length > 0) {
      console.error(`\n❌ VALIDATION FAILED: ${allErrors.length} publication/template issue(s)`);
      for (const err of allErrors) {
        console.error(`  - ${err}`);
      }
      process.exit(1);
    } else {
      console.log('\n✅ ALL TEMPLATES AND PUBLICATION TOPOLOGY VALIDATED');
      process.exit(0);
    }
  } catch (error) {
    console.error(`\n❌ FATAL ERROR: ${error.message}`);
    process.exit(1);
  }
}

validateTemplates();
