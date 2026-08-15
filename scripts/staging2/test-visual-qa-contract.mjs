import fs from 'node:fs/promises';
import path from 'node:path';
import { spawnSync } from 'node:child_process';

const AGENT_BROWSER = 'agent-browser';

export async function runVisualQAContract(options = {}) {
  const url = options.url || process.env.STAGING_URL || 'https://staging2.nuvanx.com';
  const outputDir = path.resolve(options.outputDir || 'scripts/staging2/visual-qa-artifacts');
  
  await fs.mkdir(outputDir, { recursive: true });

  // Check if agent-browser is installed
  const checkResult = spawnSync(AGENT_BROWSER, ['--version'], { encoding: 'utf8' });
  if (checkResult.status !== 0) {
    const report = {
      schema: 'visual-qa-contract',
      checkedAt: new Date().toISOString(),
      url,
      validation: 'SKIP',
      reason: 'agent-browser not installed',
    };
    await fs.writeFile(path.join(outputDir, 'visual-qa-contract.json'), `${JSON.stringify(report, null, 2)}\n`, 'utf8');
    console.log('VISUAL_QA_CONTRACT=SKIP reason=agent-browser_not_installed');
    return report;
  }

  // Run visual QA script
  const visualQAResult = spawnSync(
    'node',
    ['scripts/staging2/visual-qa-by-state.mjs', url, outputDir],
    { encoding: 'utf8', timeout: 300000, cwd: process.cwd() }
  );

  if (visualQAResult.status !== 0) {
    const report = {
      schema: 'visual-qa-contract',
      checkedAt: new Date().toISOString(),
      url,
      validation: 'FAIL',
      error: visualQAResult.stderr || visualQAResult.stdout,
    };
    await fs.writeFile(path.join(outputDir, 'visual-qa-contract.json'), `${JSON.stringify(report, null, 2)}\n`, 'utf8');
    console.error('VISUAL_QA_CONTRACT=FAIL');
    throw new Error(`Visual QA contract failed: ${visualQAResult.stderr}`);
  }

  // Read results
  const resultsPath = path.join(outputDir, 'visual-qa-results.json');
  let results = {};
  try {
    const resultsContent = await fs.readFile(resultsPath, 'utf8');
    results = JSON.parse(resultsContent);
  } catch (error) {
    // Results file may not exist yet
  }

  const report = {
    schema: 'visual-qa-contract',
    checkedAt: new Date().toISOString(),
    url,
    validation: 'PASS',
    viewportsTested: results.viewports?.length || 0,
    statesTested: results.viewports?.[0]?.states?.length || 0,
  };

  await fs.writeFile(path.join(outputDir, 'visual-qa-contract.json'), `${JSON.stringify(report, null, 2)}\n`, 'utf8');
  
  console.log(`VISUAL_QA_CONTRACT=PASS url=${url} viewports=${report.viewportsTested} states=${report.statesTested}`);
  return report;
}