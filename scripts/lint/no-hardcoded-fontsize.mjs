#!/usr/bin/env node
/**
 * Lint script to detect hardcoded font-size values in CSS files.
 * 
 * This script scans CSS files for:
 * - Hardcoded px values in font-size properties
 * - Exceptions: var() declarations, tokens, comments
 * 
 * Violations are reported and the script exits with error code 1.
 */

import fs from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const THEME_DIR = path.join(__dirname, '../../wp-content/themes/nuvanx-medical');

// Pattern for hardcoded font-size in px
const FONTSIZE_PATTERN = /font-size:\s*\d+px/gi;

async function scanFile(filePath) {
  const content = await fs.readFile(filePath, 'utf-8');
  const lines = content.split('\n');
  const violations = [];

  // Skip nvx-tokens.css entirely (that's where tokens are defined)
  if (filePath.includes('nvx-tokens.css')) {
    return violations;
  }

  for (let i = 0; i < lines.length; i++) {
    const line = lines[i];
    const lineNum = i + 1;

    // Skip comments
    if (line.trim().startsWith('//') || line.trim().startsWith('/*')) continue;

    // Skip if line contains var() declaration (CSS token)
    if (/var\(--[\w-]+\)/.test(line)) continue;

    // Skip if line has explicit allow marker
    if (line.includes('/* nvx-allow-font-px */')) continue;

    // Skip if line is a comment block
    if (line.includes('/*') && line.includes('*/')) continue;

    const matches = line.matchAll(FONTSIZE_PATTERN);
    for (const match of matches) {
      violations.push({
        line: lineNum,
        file: path.relative(THEME_DIR, filePath),
        match: match[0],
        context: line.trim()
      });
    }
  }

  return violations;
}

async function scanDirectory(dir, extensions = ['.css']) {
  const violations = [];
  const files = [];

  async function scanDir(currentDir) {
    const entries = await fs.readdir(currentDir, { withFileTypes: true });
    
    for (const entry of entries) {
      const fullPath = path.join(currentDir, entry.name);
      
      if (entry.isDirectory()) {
        // Skip node_modules and vendor directories
        if (entry.name !== 'node_modules' && entry.name !== 'vendor') {
          await scanDir(fullPath);
        }
      } else if (extensions.includes(path.extname(entry.name))) {
        files.push(fullPath);
      }
    }
  }

  await scanDir(dir);

  for (const file of files) {
    const fileViolations = await scanFile(file);
    violations.push(...fileViolations);
  }

  return violations;
}

async function main() {
  const cssDir = path.join(THEME_DIR, 'assets/css');

  console.log('📏 Scanning CSS files for hardcoded font-size values...');
  console.log(`📁 Directory: ${cssDir}`);

  const violations = await scanDirectory(cssDir);

  if (violations.length === 0) {
    console.log('✅ No hardcoded font-size values found');
    process.exit(0);
  }

  console.log(`\n❌ Found ${violations.length} hardcoded font-size violations:\n`);

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
      console.log(`   Context: ${v.context.substring(0, 60)}...`);
    });
    console.log('');
  }

  console.log('💡 REMEDY: Replace hardcoded font-size with CSS tokens from nvx-tokens.css');
  console.log('   Example: font-size: 16px → font-size: var(--nvx-type-body)');
  console.log('   Example: font-size: 24px → font-size: var(--nvx-type-h3)');

  console.log('\n❌ Hardcoded font-size values found: exiting with error code');
  process.exit(1);
}

main().catch(err => {
  console.error('❌ Error:', err);
  process.exit(1);
});