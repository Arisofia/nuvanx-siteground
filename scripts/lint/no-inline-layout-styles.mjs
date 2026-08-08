#!/usr/bin/env node
/**
 * Lint script to detect dangerous inline styles in PHP files.
 * 
 * This script scans PHP files for:
 * - Inline style attributes with layout properties (margin, padding, font-size, color)
 * - style="..." patterns that should be in CSS classes instead
 * 
 * Violations are reported and the script exits with error code 1.
 */

import fs from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { scanDirectory } from './file-scan-utils.mjs';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const THEME_DIR = path.join(__dirname, '../../wp-content/themes/nuvanx-medical');

// Dangerous CSS properties that should not be inline
const DANGEROUS_PROPERTIES = [
  'margin',
  'padding',
  'font-size',
  'color',
  'background',
  'width',
  'height',
  'display',
  'position',
  'top',
  'left',
  'right',
  'bottom',
];

// Pattern for inline style attributes with dangerous properties
const INLINE_STYLE_PATTERN = /style\s*=\s*["']([^"']*(?:margin|padding|font-size|color)\s*:[^"']*)["']/gi;

/**
 * Scans a PHP file for prohibited inline CSS properties.
 * @param {string} filePath - The path to the file to scan.
 * @return {Array<Object>} The detected violations with their line numbers, file paths, matched styles, properties, and line context.
 */
async function scanFile(filePath) {
  const content = await fs.readFile(filePath, 'utf-8');
  const lines = content.split('\n');
  const violations = [];

  for (let i = 0; i < lines.length; i++) {
    const line = lines[i];
    const lineNum = i + 1;

    // Skip comments
    if (line.trim().startsWith('//') || line.trim().startsWith('/*')) continue;

    // Skip explicit allow marker
    if (line.includes('// nvx-allow-inline-style')) continue;

    // Skip SVG fallback for hidden attribute (legitimate use case)
    if (line.includes('hidden') && line.includes('display:none')) continue;

    const matches = line.matchAll(INLINE_STYLE_PATTERN);
    for (const match of matches) {
      const styleContent = match[1];
      
      // Extract which dangerous properties are present
      const foundProperties = DANGEROUS_PROPERTIES.filter(prop => 
        styleContent.toLowerCase().includes(prop)
      );

      violations.push({
        line: lineNum,
        file: path.relative(THEME_DIR, filePath),
        match: match[0],
        properties: foundProperties,
        context: line.trim()
      });
    }
  }

  return violations;
}

/**
 * Scans the theme's PHP files for dangerous inline styles and reports any violations.
 *
 * Exits with code `0` when no violations are found and code `1` when violations or scanning errors occur.
 */
async function main() {
  console.log('🚫 Scanning PHP files for dangerous inline styles...');
  console.log(`📁 Directory: ${THEME_DIR}`);

  const violations = await scanDirectory(THEME_DIR, ['.php'], scanFile);

  if (violations.length === 0) {
    console.log('✅ No dangerous inline styles found');
    process.exit(0);
  }

  console.log(`\n❌ Found ${violations.length} dangerous inline style violations:\n`);

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
      console.log(`   Dangerous properties: ${v.properties.join(', ')}`);
      console.log(`   Context: ${v.context.substring(0, 80)}...`);
    });
    console.log('');
  }

  console.log('💡 REMEDY: Move inline styles to CSS classes');
  console.log('   Replace: style="margin: 10px; padding: 20px;"');
  console.log('   With: class="my-spacing-class"');
  console.log('   And define the class in your CSS file');

  console.log('\n❌ Dangerous inline styles found: exiting with error code');
  process.exit(1);
}

main().catch(err => {
  console.error('❌ Error:', err);
  process.exit(1);
});