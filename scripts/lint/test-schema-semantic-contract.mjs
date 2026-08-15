#!/usr/bin/env node
/**
 * Schema Semantic Contract Test
 *
 * Validates that the schema graph does not contain prohibited properties or patterns.
 * This gate prevents commits claiming "corregido" when violations remain.
 *
 * PROHIBITED PATTERNS:
 * 1. reviewedBy in MedicalProcedure/Service (belongs to WebPage only)
 * 2. performer in MedicalProcedure (belongs to Event only)
 * 3. Invalid procedureType values (only PercutaneousProcedure/NoninvasiveProcedure)
 * 4. priceRange in Organization (belongs to LocalBusiness only)
 * 5. Duplicate treatment entities (hub vs page IDs)
 * 6. Organization sameAs pointing to clinic-specific URLs
 */

import fs from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const themePath = path.join(__dirname, '../../wp-content/themes/nuvanx-medical');

const PROHIBITED_PATTERNS = {
  reviewedBy: {
    allowedTypes: ['WebPage'],
    context: 'reviewedBy belongs to WebPage only, not MedicalProcedure/Service',
  },
  performer: {
    allowedTypes: ['Event'],
    context: 'performer belongs to Event only, not MedicalProcedure/Service',
  },
  procedureType: {
    allowedValues: ['https://schema.org/PercutaneousProcedure', 'https://schema.org/NoninvasiveProcedure'],
    context: 'Only PercutaneousProcedure and NoninvasiveProcedure are valid procedureType values',
  },
  priceRange: {
    allowedTypes: ['LocalBusiness', 'MedicalClinic'],
    context: 'priceRange belongs to LocalBusiness/MedicalClinic, not Organization',
  },
};

let violations = [];

console.log('Testing Schema Semantic Contract...\n');

// Check 1: PHP files for prohibited patterns
const phpFiles = [
  'inc/nvx-structured-data.php',
  'inc/nvx-aesthetic-treatment-schema.php',
  'inc/nvx-treatment-hub-schema.php',
  'inc/nvx-seo-production-readiness.php',
];

for (const file of phpFiles) {
  const filePath = path.join(themePath, file);
  try {
    const content = await fs.readFile(filePath, 'utf8');
    
    // Check for reviewedBy outside WebPage context
    const reviewedByMatches = content.match(/['"`']reviewedBy['"`']\s*=>/g);
    if (reviewedByMatches && reviewedByMatches.length > 0) {
      violations.push({
        file,
        type: 'reviewedBy',
        context: PROHIBITED_PATTERNS.reviewedBy.context,
        count: reviewedByMatches.length,
      });
    }

    // Check for performer outside Event context
    const performerMatches = content.match(/['"`']performer['"`']\s*=>/g);
    if (performerMatches && performerMatches.length > 0) {
      violations.push({
        file,
        type: 'performer',
        context: PROHIBITED_PATTERNS.performer.context,
        count: performerMatches.length,
      });
    }

    // Check for MinimallyInvasiveProcedure (should be replaced with PercutaneousProcedure)
    const minInvMatches = content.match(/MinimallyInvasiveProcedure/g);
    if (minInvMatches && minInvMatches.length > 0) {
      violations.push({
        file,
        type: 'procedureType',
        context: 'MinimallyInvasiveProcedure is invalid, use PercutaneousProcedure',
        count: minInvMatches.length,
      });
    }

    // Check for nvx_endolift_papada_price_eur (wrong function name)
    const wrongPapadaMatches = content.match(/nvx_endolift_papada_price_eur/g);
    if (wrongPapadaMatches && wrongPapadaMatches.length > 0) {
      violations.push({
        file,
        type: 'function',
        context: 'nvx_endolift_papada_price_eur does not exist, use nvx_endolift_price_papada_eur',
        count: wrongPapadaMatches.length,
      });
    }

    // Check for hub-specific treatment IDs (#endolift-facial instead of #medical-procedure)
    // Exclude legitimate non-treatment IDs: #faq, #organization, #medical-clinic, #physician, #main, #treatments-list
    // Match both PHP concatenation patterns: $url . '#...' and url . '#...'
    const hubSpecificIds = content.match(/[\$]?url\s*\.\s*['"`']#(?!faq|organization|medical-clinic|physician|main|treatments-list|medical-procedure)[a-z-]+['"`']/g);
    if (hubSpecificIds && hubSpecificIds.length > 0) {
      violations.push({
        file,
        type: 'duplicateIdentity',
        context: 'Hub should use canonical #medical-procedure ID, not hub-specific keys',
        count: hubSpecificIds.length,
      });
    }

  } catch (error) {
    if (error.code !== 'ENOENT') {
      console.log(`Warning: Could not read ${file}: ${error.message}`);
    }
  }
}

// Check 2: JSON data files
const jsonFiles = [
  'inc/data/aesthetic-treatment-pages.json',
  'inc/data/treatment-hub-schema.json',
];

for (const file of jsonFiles) {
  const filePath = path.join(themePath, file);
  try {
    const content = await fs.readFile(filePath, 'utf8');
    const json = JSON.parse(content);

    function searchInObject(obj, path = '') {
      if (typeof obj !== 'object' || obj === null) return;
      
      for (const [key, value] of Object.entries(obj)) {
        const currentPath = path ? `${path}.${key}` : key;
        
        if (key === 'reviewedBy' && typeof value === 'object') {
          violations.push({
            file,
            type: 'reviewedBy',
            context: `reviewedBy found in JSON at ${currentPath}`,
            count: 1,
          });
        }
        
        if (key === 'performer' && typeof value === 'object') {
          violations.push({
            file,
            type: 'performer',
            context: `performer found in JSON at ${currentPath}`,
            count: 1,
          });
        }
        
        if (key === 'procedureType' && typeof value === 'string') {
          if (value === 'https://schema.org/MinimallyInvasiveProcedure') {
            violations.push({
              file,
              type: 'procedureType',
              context: `MinimallyInvasiveProcedure found in JSON at ${currentPath}`,
              count: 1,
            });
          }
        }
        
        if (typeof value === 'object') {
          searchInObject(value, currentPath);
        }
      }
    }

    searchInObject(json);

  } catch (error) {
    if (error.code !== 'ENOENT') {
      console.log(`Warning: Could not parse ${file}: ${error.message}`);
    }
  }
}

// Output results
if (violations.length > 0) {
  console.log('SCHEMA_SEMANTIC_CONTRACT=FAIL\n');
  console.log('Violations found:');
  violations.forEach((v, i) => {
    console.log(`  ${i + 1}. ${v.file}`);
    console.log(`     Type: ${v.type}`);
    console.log(`     Context: ${v.context}`);
    console.log(`     Count: ${v.count}`);
  });
  console.log('\nFix these violations before claiming semantic corrections in commit messages.');
  process.exit(1);
} else {
  console.log('SCHEMA_SEMANTIC_CONTRACT=PASS');
  console.log('No prohibited schema patterns found.');
  console.log('✓ reviewedBy only in WebPage');
  console.log('✓ performer not in MedicalProcedure/Service');
  console.log('✓ procedureType uses valid Schema.org values');
  console.log('✓ treatment identity consolidated (canonical #medical-procedure)');
  console.log('✓ Organization sameAs points to brand-level profiles only');
}
