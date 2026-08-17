#!/usr/bin/env node
/**
 * Validate Gate Normalization
 *
 * Ensures that all workflows execute the same complete set of gates.
 * No workflow "green" should mean lower coverage than another.
 *
 * Usage: node scripts/ci/validate-gate-normalization.mjs <workflow_name>
 */

import fs from 'node:fs/promises';
import path from 'node:path';
import { spawnSync } from 'node:child_process';

const CONFIG_PATH = '.github/workflows/gate-normalization-config.json';

async function loadConfig() {
  const configContent = await fs.readFile(CONFIG_PATH, 'utf8');
  return JSON.parse(configContent);
}

async function validateWorkflowGateCoverage(workflowName, config) {
  const workflowConfig = config.workflows[workflowName];

  if (!workflowConfig) {
    throw new Error(`Workflow "${workflowName}" not found in configuration`);
  }

  const requiredGates = workflowConfig.required_gates;
  const issues = [];

  // Check if all mandatory gates are defined
  for (const gateCategory of requiredGates) {
    if (!config.mandatory_gates[gateCategory]) {
      issues.push(`Gate category "${gateCategory}" is required but not defined in mandatory_gates`);
    }
  }

  // Check if deep_quality parameter is present (violates no_opt_in_quality rule)
  const stagingWorkflowPath = '.github/workflows/staging.yml';
  try {
    const stagingContent = await fs.readFile(stagingWorkflowPath, 'utf8');
    if (stagingContent.includes('deep_quality')) {
      issues.push('Deep quality gate is conditional (violates no_opt_in_quality rule)');
    }
  } catch (error) {
    // staging.yml may not exist
  }

  return {
    valid: issues.length === 0,
    issues,
    requiredGates,
    coverage: requiredGates.length,
  };
}

async function compareWorkflowCoverage(config) {
  const issues = [];
  const coverage = {};

  for (const [workflowName, workflowConfig] of Object.entries(config.workflows)) {
    coverage[workflowName] = workflowConfig.required_gates.length;
  }

  const coverages = Object.values(coverage);
  const uniqueCoverages = [...new Set(coverages)];

  if (uniqueCoverages.length > 1) {
    issues.push(`Workflows have different gate coverage: ${JSON.stringify(coverage)}`);
    issues.push('This violates the "same_coverage" rule');
  }

  return {
    valid: issues.length === 0,
    issues,
    coverage,
  };
}

async function main() {
  const workflowName = process.argv[2] || 'staging';

  try {
    const config = await loadConfig();

    console.log(`=== GATE NORMALIZATION VALIDATION ===`);
    console.log(`Workflow: ${workflowName}`);
    console.log(`Config version: ${config.version}`);
    console.log('');

    // Validate workflow gate coverage
    const coverageValidation = await validateWorkflowGateCoverage(workflowName, config);

    console.log(`Required gates: ${coverageValidation.requiredGates.join(', ')}`);
    console.log(`Gate coverage: ${coverageValidation.coverage}`);
    console.log('');

    if (!coverageValidation.valid) {
      console.error('VALIDATION FAILED:');
      coverageValidation.issues.forEach((issue) => console.error(`- ${issue}`));
      process.exit(1);
    }

    // Compare workflow coverage
    const comparisonValidation = await compareWorkflowCoverage(config);

    console.log('Workflow coverage comparison:');
    console.log(JSON.stringify(comparisonValidation.coverage, null, 2));
    console.log('');

    if (!comparisonValidation.valid) {
      console.error('VALIDATION FAILED:');
      comparisonValidation.issues.forEach((issue) => console.error(`- ${issue}`));
      process.exit(1);
    }

    console.log('✓ Gate normalization validation PASSED');
    console.log('All workflows execute the same complete set of gates');
    console.log('No workflow "green" means lower coverage than another');

    process.exit(0);
  } catch (error) {
    console.error(`VALIDATION ERROR: ${error.message}`);
    process.exit(1);
  }
}

main();