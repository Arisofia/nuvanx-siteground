#!/usr/bin/env node

/**
 * WordPress Page Template Integrity Validator
 *
 * Validates that all published WordPress pages with custom templates
 * reference template files that actually exist in the current theme.
 *
 * Usage: node scripts/validate-page-templates.mjs
 *
 * Optional env:
 * - WORDPRESS_PAGES_FILE: JSON snapshot of [{ id, slug, template }].
 * - WORDPRESS_URL: REST fallback when no snapshot is supplied.
 *
 * Fails if any published page references a non-existent template file.
 */

import { readFileSync, existsSync } from 'fs';
import { join, dirname } from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

// Theme root directory (wp-content/themes/nuvanx-medical)
const THEME_ROOT = join(__dirname, '..', 'wp-content', 'themes', 'nuvanx-medical');

// Templates directory
const TEMPLATES_DIR = join(THEME_ROOT, 'templates');

// Valid template files that must exist
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

/**
 * Fetch published pages either from a trusted local snapshot or WordPress REST.
 */
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
  const response = await fetch(endpoint, {
    headers: { Accept: 'application/json' },
  });

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

/**
 * Check if a template file exists in the theme.
 */
function templateExists(templatePath) {
  // Empty template means default - always valid
  if (!templatePath || templatePath === '' || templatePath === 'default') {
    return true;
  }

  // Check if template is in templates/ directory
  const fullPath = join(TEMPLATES_DIR, templatePath);
  if (existsSync(fullPath)) {
    return true;
  }

  // Check if template is in theme root (legacy)
  const rootPath = join(THEME_ROOT, templatePath);
  if (existsSync(rootPath)) {
    return true;
  }

  return false;
}

/**
 * Validates published page template references and expected template files.
 * Exits with status `1` when validation or operational errors occur, or status `0` when validation passes.
 */
async function validateTemplates() {
  console.log('🔍 Validating WordPress page template references...\n');

  try {
    const pages = await fetchPublishedPages();
    console.log('📄 Published page inventory loaded\n');

    const errors = [];
    const warnings = [];

    for (const page of pages) {
      const template = page.template || '';

      // Skip pages with default template
      if (!template) {
        continue;
      }

      const exists = templateExists(template);

      if (!exists) {
        errors.push({
          id: page.id,
          slug: page.slug,
          template,
          message: `Page ${page.id} (${page.slug}) references missing template: ${template}`,
        });
        console.error(`❌ ${errors[errors.length - 1].message}`);
      } else {
        console.log(`✅ Page ${page.id} (${page.slug}): ${template} exists`);
      }
    }

    // Verify all expected templates actually exist
    console.log('\n🔍 Verifying expected template files exist...\n');
    for (const template of VALID_TEMPLATES) {
      const fullPath = join(TEMPLATES_DIR, template);
      if (existsSync(fullPath)) {
        console.log(`✅ ${template} exists`);
      } else {
        warnings.push(`⚠️  Expected template ${template} not found`);
        console.warn(warnings[warnings.length - 1]);
      }
    }

    console.log('\n' + '='.repeat(60));

    if (errors.length > 0) {
      console.error(`\n❌ VALIDATION FAILED: ${errors.length} page(s) reference missing template(s)`);
      console.error('\nTo fix:');
      for (const error of errors) {
        console.error(`  - wp post meta update ${error.id} _wp_page_template default`);
      }
      process.exit(1);
    }

    if (warnings.length > 0) {
      console.warn(`\n⚠️  VALIDATION PASSED with ${warnings.length} warning(s)`);
      process.exit(0);
    }

    console.log('\n✅ VALIDATION PASSED: All page template references are valid');
    process.exit(0);
  } catch (error) {
    console.error('\n❌ Validation error:', error.message);
    process.exit(1);
  }
}

validateTemplates();
