#!/usr/bin/env node

/**
 * WordPress Page Template + Publication Topology Validator
 *
 * Validates that:
 * - the authenticated WordPress publication inventory matches the canonical
 *   version-controlled 52-page manifest;
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
  if (manifest.length !== 52) {
    throw new Error(`Canonical published-page manifest must contain exactly 52 pages; got ${manifest.length}`);
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
  if (pages.length !== manifest.length) {
    errors.push(`Published page count drift: WordPress=${pages.length}, manifest=${manifest.length}`);
  }

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

  for (const actual of pages) {
    const id = Number(actual.id);
    if (!expectedIds.has(id)) {
      errors.push(`Unexpected published WordPress page ${id} (${actual.slug || 'no-slug'}) is absent from manifest`);
    }
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

    const errors = validatePublicationTopology(pages, manifest);
    const warnings = [];

    for (const page of pages) {
      const template = page.template || '';
      if (!template) continue;

      if (!templateExists(template)) {
        const message = `Page ${page.id} (${page.slug}) references missing template: ${template}`;
        errors.push(message);
        console.error(`❌ ${message}`);
      } else {
        console.log(`✅ Page ${page.id} (${page.slug}): ${template} exists`);
      }
    }

    console.log('\n🔍 Verifying expected template files exist...\n');
    for (const template of VALID_TEMPLATES) {
      const fullPath = join(TEMPLATES_DIR, template);
      if (existsSync(fullPath)) {
        console.log(`✅ ${template} exists`);
      } else {
        const warning = `Expected template ${template} not found`;
        warnings.push(warning);
        console.warn(`⚠️  ${warning}`);
      }
    }

    console.log('\n' + '='.repeat(60));

    if (errors.length > 0) {
      console.error(`\n❌ VALIDATION FAILED: ${errors.length} publication/template issue(s)`);
      for (const error of errors) console.error(`  - ${error}`);
      process.exit(1);
    }

    console.log('\n✅ PUBLICATION_TOPOLOGY=PASS pages=52');
    if (warnings.length > 0) {
      console.warn(`⚠️  VALIDATION PASSED with ${warnings.length} warning(s)`);
    } else {
      console.log('✅ VALIDATION PASSED: publication topology and page templates are valid');
    }
    process.exit(0);
  } catch (error) {
    console.error('\n❌ Validation error:', error.message);
    process.exit(1);
  }
}

validateTemplates();
