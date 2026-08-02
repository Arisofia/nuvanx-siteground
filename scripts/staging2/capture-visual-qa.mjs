#!/usr/bin/env node
/**
 * Staging2 visual QA entrypoint.
 * Runtime policy and CDP helpers live in visual-qa-common.mjs.
 */
import fs from 'node:fs';

import {
  resolveVisualQaConfig,
  assertVisualQaRuntime,
  createVisualQaReport,
  createGovernedLoadPage,
  auditGovernedPages,
  auditDesktopNavigation,
  auditMobileNavigation,
  withHeadlessChrome,
  writeVisualQaReport,
  exitVisualQa,
  closeSession,
} from './visual-qa-common.mjs';

const config = resolveVisualQaConfig();
assertVisualQaRuntime(config);
process.env.EVIDENCE_DIR = config.evidenceDir;
fs.mkdirSync(config.evidenceDir, { recursive: true });

const { report, findings, fail } = createVisualQaReport(config);
const loadPage = createGovernedLoadPage({ expectedSha: config.expectedSha });

const { chromePath, runtimeError } = await withHeadlessChrome(async (port) => {
  await auditGovernedPages({
    port,
    loadPage,
    baseUrl: config.baseUrl,
    expectedSha: config.expectedSha,
    evidenceDir: config.evidenceDir,
    fail,
    report,
  });
  await auditDesktopNavigation(port, loadPage, closeSession, report, fail);
  await auditMobileNavigation(port, loadPage, closeSession, report, fail);
});

report.chrome = chromePath;
if (runtimeError) {
  fail('visual QA runtime', runtimeError);
}

writeVisualQaReport(config.evidenceDir, report);
exitVisualQa(report, findings, config.expectedSha);
