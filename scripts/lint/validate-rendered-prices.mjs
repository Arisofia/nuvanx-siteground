#!/usr/bin/env node
/**
 * Lint script to validate rendered prices against tariff-catalog.json SSOT.
 *
 * This script:
 * 1. Loads the canonical tariff catalog from tariff-catalog.json
 * 2. Scans PHP files for hardcoded price patterns in post_content
 * 3. Reports orphaned prices that don't match the SSOT
 *
 * Violations block deployment in CI.
 */

import fs from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { scanDirectory } from './file-scan-utils.mjs';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const THEME_DIR = path.join(__dirname, '../../wp-content/themes/nuvanx-medical');
const TARIFF_CATALOG_PATH = path.join(THEME_DIR, 'inc/data/tariff-catalog.json');

// Price patterns to detect in PHP files (hardcoded euro prices)
const PRICE_PATTERNS = [
  // Euro amounts: 330 €, 1.064,80 €, 498,20 €, etc.
  /\b\d{1,3}(?:[.,]\d{3})*(?:[.,]\d{2})?\s*€\b/g,
  // Alternative format: €330, €1.064,80, etc.
  /€\s*\d{1,3}(?:[.,]\d{3})*(?:[.,]\d{2})?\b/g,
];

/**
 * Load and parse tariff-catalog.json
 */
async function loadTariffCatalog() {
  try {
    const content = await fs.readFile(TARIFF_CATALOG_PATH, 'utf8');
    return JSON.parse(content);
  } catch (error) {
    console.error(`❌ Failed to load tariff-catalog.json: ${error.message}`);
    process.exit(1);
  }
}

/**
 * Extract all canonical prices from tariff-catalog.json
 */
function extractCanonicalPrices(catalog) {
  const prices = new Set();

  for (const group of Object.keys(catalog)) {
    const groupData = catalog[group];
    if (!groupData || typeof groupData !== 'object') continue;

    for (const key of Object.keys(groupData)) {
      const item = groupData[key];
      if (item && typeof item === 'object' && typeof item.pvp === 'number') {
        // Add both common formats: "330 €" and "330,00 €"
        const pvp = item.pvp;
        const formatted = pvp.toFixed(2).replace('.', ',');
        prices.add(`${formatted} €`);
        prices.add(`${Math.round(pvp)} €`);
      }
    }
  }

  return prices;
}

/**
 * Normalize a price string for comparison
 */
function normalizePrice(priceStr) {
  return priceStr
    .replace(/\s/g, '')
    .replace(',', '.')
    .replace('€', '')
    .trim();
}

/**
 * Check if a price matches any canonical price
 */
function isCanonicalPrice(priceStr, canonicalPrices) {
  const normalized = normalizePrice(priceStr);

  for (const canonical of canonicalPrices) {
    const canonicalNormalized = normalizePrice(canonical);
    if (normalized === canonicalNormalized) {
      return true;
    }
  }

  return false;
}

/**
 * Scan a PHP file for hardcoded price violations
 */
async function scanFile(filePath, canonicalPrices) {
  const content = await fs.readFile(filePath, 'utf8');
  const lines = content.split('\n');
  const violations = [];

  for (let i = 0; i < lines.length; i++) {
    const line = lines[i];
    const lineNum = i + 1;

    // Skip comments
    if (line.trim().startsWith('//') || line.trim().startsWith('/*')) continue;

    // Skip if line contains shortcode (that's the correct way)
    if (line.includes('[nvx_tariff')) continue;

    for (const pattern of PRICE_PATTERNS) {
      const matches = line.matchAll(pattern);
      for (const match of matches) {
        const matchedText = match[0];

        // Check if this price exists in the canonical catalog
        if (!isCanonicalPrice(matchedText, canonicalPrices)) {
          violations.push({
            line: lineNum,
            file: path.relative(THEME_DIR, filePath),
            match: matchedText,
            context: line.trim()
          });
        }
      }
    }
  }

  return violations;
}

/**
 * Main validation function
 */
async function main() {
  console.log('💰 Validating rendered prices against tariff-catalog.json SSOT...');

  const catalog = await loadTariffCatalog();
  const canonicalPrices = extractCanonicalPrices(catalog);

  console.log(`📋 Loaded ${canonicalPrices.size} canonical prices from tariff-catalog.json`);

  // Scan PHP files for hardcoded prices (excluding data files which are the source)
  const phpDir = THEME_DIR;
  const violations = await scanDirectory(phpDir, ['.php'], (filePath) => {
    // Skip data directory (that's where tariff-catalog.json lives)
    if (filePath.includes('/inc/data/')) return [];
    // Skip the shortcode file itself
    if (filePath.includes('nvx-tariff-shortcode.php')) return [];
    return scanFile(filePath, canonicalPrices);
  });

  if (violations.length === 0) {
    console.log('✅ No orphaned hardcoded prices found');
    console.log('💡 All prices should use [nvx_tariff key="group.subkey"] shortcode');
    process.exit(0);
  }

  console.log(`\n❌ Found ${violations.length} orphaned hardcoded price violations:\n`);

  // Group by file
  const byFile = {};
  violations.forEach(v => {
    if (!byFile[v.file]) byFile[v.file] = [];
    byFile[v.file].push(v);
  });

  for (const [file, fileViolations] of Object.entries(byFile)) {
    console.log(`📄 ${file}:`);
    fileViolations.forEach(v => {
      console.log(`   Line ${v.line}: ${v.match}`);
      console.log(`   Context: ${v.context.substring(0, 80)}...`);
    });
    console.log('');
  }

  console.log('💡 REMEDY: Replace hardcoded prices with [nvx_tariff] shortcode');
  console.log('   Example: 330 € → [nvx_tariff key="laser_co2.facial"]');
  console.log('   Example: 1.064,80 € → [nvx_tariff key="endolift.papada"]');
  console.log('   See docs/operations/tariff-shortcode-usage.md for usage guide');

  console.log('\n❌ Orphaned prices detected: blocking deployment');
  process.exit(1);
}

main().catch(err => {
  console.error('❌ Error:', err);
  process.exit(1);
});
