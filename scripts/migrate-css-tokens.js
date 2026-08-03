#!/usr/bin/env node
/**
 * CSS Token Migration Script
 * Converts hardcoded CSS values to CSS custom properties (tokens)
 */

import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const CSS_DIR = path.join(__dirname, '../wp-content/themes/nuvanx-medical/assets/css');
const BACKUP_DIR = path.join(__dirname, '../css-backups');

// Migration rules based on analysis
const MIGRATION_RULES = [
  {
    // Priority HIGH: nvx-home-v3.css line 14
    file: 'nvx-home-v3.css',
    pattern: /min-height:\s*600px;/g,
    replacement: 'min-height: calc(var(--nvx-hero-h) * 0.6);',
    description: 'hero min-height → token-based calculation'
  },
  {
    // Priority HIGH: nvx-accessibility-governance.css line 96
    file: 'nvx-accessibility-governance.css',
    pattern: /top:\s*-60px;/g,
    replacement: 'top: calc(var(--nvx-header-height) * -0.75);',
    description: 'skip-link top position → token-based calculation'
  },
  {
    // Priority MEDIUM: nvx-components.css line 996
    file: 'nvx-components.css',
    pattern: /min-height:\s*120px;/g,
    replacement: 'min-height: calc(var(--nvx-space-6) * 2.5);',
    description: 'modal min-height → token-based calculation'
  },
  {
    // Priority MEDIUM: nvx-portfolio-hub.css line 109
    file: 'nvx-portfolio-hub.css',
    pattern: /max-width:\s*900px;/g,
    replacement: 'max-width: var(--nvx-catalog-width);',
    description: 'catalog max-width → new token'
  },
  {
    // Priority MEDIUM: nvx-home-v3.css line 455
    file: 'nvx-home-v3.css',
    pattern: /max-width:\s*800px;/g,
    replacement: 'max-width: var(--nvx-section-tight-width);',
    description: 'section max-width → new token'
  },
  {
    // Priority LOW: outline-offset (multiple files)
    pattern: /outline-offset:\s*2px;/g,
    replacement: 'outline-offset: var(--nvx-outline-offset);',
    description: 'outline-offset → new token',
    files: ['nvx-components.css', 'nvx-accessibility-governance.css']
  },
  {
    // Priority LOW: 1px values
    pattern: /(text-decoration-thickness|gap|width|height):\s*1px;/g,
    replacement: '$1: var(--nvx-border-hairline);',
    description: '1px values → existing border-hairline token',
    files: ['nvx-components.css', 'nvx-posts.css', 'nvx-patterns-editorial.css']
  }
];

// New tokens to add to nvx-tokens.css
const NEW_TOKENS = `
/* Additional design tokens added during migration */
--nvx-outline-offset: 2px;
--nvx-catalog-width: 900px;
--nvx-section-tight-width: 800px;
--nvx-modal-min-height: 120px;
`;

function createBackupDir() {
  if (!fs.existsSync(BACKUP_DIR)) {
    fs.mkdirSync(BACKUP_DIR, { recursive: true });
    console.log(`✅ Created backup directory: ${BACKUP_DIR}`);
  }
}

function backupFile(filePath) {
  const backupPath = path.join(BACKUP_DIR, path.basename(filePath));
  fs.copyFileSync(filePath, backupPath);
  console.log(`📦 Backed up: ${path.basename(filePath)}`);
}

function migrateFile(filePath, rules) {
  let content = fs.readFileSync(filePath, 'utf8');
  let changes = 0;

  rules.forEach(rule => {
    const matches = content.match(rule.pattern);
    if (matches) {
      content = content.replace(rule.pattern, rule.replacement);
      changes += matches.length;
      console.log(`  ✏️  ${rule.description} (${matches.length} occurrence(s))`);
    }
  });

  if (changes > 0) {
    fs.writeFileSync(filePath, content, 'utf8');
    console.log(`💾 Saved ${changes} change(s) to ${path.basename(filePath)}`);
  }

  return changes;
}

function addNewTokens(tokensPath) {
  let content = fs.readFileSync(tokensPath, 'utf8');
  
  // Check if tokens already exist
  if (content.includes('--nvx-outline-offset')) {
    console.log('⚠️  New tokens already exist in nvx-tokens.css');
    return false;
  }

  // Add tokens before the closing brace
  content = content.replace(/}\s*$/, NEW_TOKENS + '\n}');
  fs.writeFileSync(tokensPath, content, 'utf8');
  console.log('✅ Added new tokens to nvx-tokens.css');
  return true;
}

function main() {
  console.log('🎨 CSS Token Migration Script');
  console.log('================================\n');

  createBackupDir();

  let totalChanges = 0;
  let filesModified = 0;

  // Process specific file rules
  MIGRATION_RULES.forEach(rule => {
    if (rule.file) {
      const filePath = path.join(CSS_DIR, rule.file);
      if (fs.existsSync(filePath)) {
        console.log(`\n📄 Processing ${rule.file}:`);
        backupFile(filePath);
        const changes = migrateFile(filePath, [rule]);
        if (changes > 0) {
          totalChanges += changes;
          filesModified++;
        }
      } else {
        console.log(`⚠️  File not found: ${rule.file}`);
      }
    } else if (rule.files) {
      // Process multiple files for pattern rules
      console.log(`\n📄 Processing pattern "${rule.description}":`);
      rule.files.forEach(fileName => {
        const filePath = path.join(CSS_DIR, fileName);
        if (fs.existsSync(filePath)) {
          backupFile(filePath);
          const changes = migrateFile(filePath, [rule]);
          if (changes > 0) {
            totalChanges += changes;
            filesModified++;
          }
        }
      });
    }
  });

  // Add new tokens to nvx-tokens.css
  const tokensPath = path.join(CSS_DIR, 'nvx-tokens.css');
  if (fs.existsSync(tokensPath)) {
    console.log('\n📄 Processing nvx-tokens.css:');
    backupFile(tokensPath);
    addNewTokens(tokensPath);
  }

  console.log('\n================================');
  console.log('📊 Migration Summary');
  console.log('================================');
  console.log(`Files modified: ${filesModified}`);
  console.log(`Total changes: ${totalChanges}`);
  console.log(`Backups saved to: ${BACKUP_DIR}`);
  console.log('\n✅ Migration completed successfully!');
  console.log('💡 Tip: Review the changes in the CSS files before committing.');
}

main();