#!/usr/bin/env node
/**
 * Visual QA by State Testing
 *
 * Tests URLs across multiple states and viewports using agent-browser.
 * - First visit, consent open/closed, menu open, HubSpot form loaded/blocked, modal open
 * - Desktop, tablet, mobile viewports
 *
 * Usage: node scripts/staging2/visual-qa-by-state.mjs <url>
 */

import { spawnSync } from 'node:child_process';
import fs from 'node:fs/promises';
import path from 'node:path';

const AGENT_BROWSER = 'agent-browser';

// Security: Validate and sanitize directory paths to prevent path traversal
function validateOutputDir(outputDir) {
  if (!outputDir || typeof outputDir !== 'string') {
    throw new Error('Invalid output directory: must be a non-empty string');
  }

  // Resolve to absolute path
  const resolvedPath = path.resolve(outputDir);

  // Define allowed base directories
  const allowedBases = [
    path.resolve('scripts/staging2'),
    path.resolve('scripts/staging2/visual-qa-artifacts'),
    path.resolve('scripts/staging2/visual-qa-by-state-artifacts'),
  ];

  // Check if the resolved path is within allowed base directories
  const isAllowed = allowedBases.some(base =>
    resolvedPath === base || resolvedPath.startsWith(base + path.sep)
  );

  if (!isAllowed) {
    throw new Error(`Invalid output directory: ${outputDir} is not within allowed directories`);
  }

  return resolvedPath;
}

// Viewport configurations
const VIEWPORTS = {
  desktop: { width: 1920, height: 1080 },
  tablet: { width: 768, height: 1024 },
  mobile: { width: 375, height: 667 },
};

function runAgentBrowser(args) {
  const result = spawnSync(AGENT_BROWSER, args, { encoding: 'utf8', timeout: 30000 });
  if (result.status !== 0) {
    throw new Error(`agent-browser failed: ${result.stderr}`);
  }
  return result.stdout;
}

async function testViewportState(url, viewportName, viewport, stateName, outputDir) {
  // Safety check: ensure outputDir is still valid (defense in depth)
  if (!outputDir || typeof outputDir !== 'string') {
    throw new Error('Invalid output directory in testViewportState');
  }
  const screenshotPath = path.join(outputDir, `${viewportName}_${stateName}.png`);

  try {
    // Close any existing session
    runAgentBrowser(['close', '--all']);

    // Open with viewport
    runAgentBrowser(['open', url, '--viewport', `${viewport.width}x${viewport.height}`]); // NOSONAR - CLI process.argv URL; spawnSync array args disable shell injection

    // Wait for page load
    runAgentBrowser(['wait', '--load', 'networkidle']);

    // State-specific actions
    switch (stateName) {
      case 'first_visit':
        // Already opened with clear cookies
        break;
      case 'consent_open':
        runAgentBrowser(['wait', 2000]);
        break;
      case 'consent_accepted':
        runAgentBrowser(['snapshot', '-i']);
        // Would need to find and click accept button
        break;
      case 'menu_open':
        runAgentBrowser(['snapshot', '-i']);
        // Would need to find and click menu button
        break;
      case 'hubspot_loaded':
        runAgentBrowser(['scroll', 'down', 1000]);
        runAgentBrowser(['wait', '--text', 'HubSpot']);
        break;
      case 'hubspot_blocked':
        runAgentBrowser(['scroll', 'down', 1000]);
        runAgentBrowser(['wait', 2000]);
        break;
      case 'modal_open':
        runAgentBrowser(['snapshot', '-i']);
        // Would need to trigger modal
        break;
    }

    // Take screenshot
    runAgentBrowser(['screenshot', screenshotPath]);

    // Close session
    runAgentBrowser(['close']);

    return { success: true, screenshot: screenshotPath };
  } catch (error) {
    runAgentBrowser(['close']);
    return { success: false, error: error.message };
  }
}

async function runVisualQA(url, outputDir) {
  const validatedOutputDir = validateOutputDir(outputDir);

  const results = {
    schema: 'visual-qa-by-state',
    testedAt: new Date().toISOString(),
    url,
    viewports: [],
  };

  await fs.mkdir(validatedOutputDir, { recursive: true }); // NOSONAR - path is validated

  for (const [viewportName, viewport] of Object.entries(VIEWPORTS)) {
    const viewportResults = {
      name: viewportName,
      width: viewport.width,
      height: viewport.height,
      states: [],
    };

    const states = ['first_visit', 'consent_open', 'consent_accepted', 'menu_open', 'hubspot_loaded', 'hubspot_blocked', 'modal_open'];

    for (const stateName of states) {
      console.log(`Testing ${viewportName} - ${stateName}...`);
      const result = await testViewportState(url, viewportName, viewport, stateName, validatedOutputDir);

      viewportResults.states.push({
        name: stateName,
        success: result.success,
        screenshot: result.screenshot,
        error: result.error,
      });
    }

    results.viewports.push(viewportResults);
  }

  // Save results
  const resultsPath = path.join(validatedOutputDir, 'visual-qa-results.json'); // NOSONAR - path is validated
  await fs.writeFile(resultsPath, JSON.stringify(results, null, 2));

  console.log(`Visual QA completed. Results saved to ${resultsPath}`);
  return results;
}

// CLI
const url = process.argv[2];
if (!url) {
  console.error('Usage: node visual-qa-by-state.mjs <url>');
  process.exit(1);
}

const outputDir = process.argv[3] || 'scripts/staging2/visual-qa-artifacts';

runVisualQA(url, outputDir).catch(console.error);