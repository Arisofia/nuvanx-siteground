#!/usr/bin/env node
/**
 * Governed staging2 browser visual QA entrypoint.
 *
 * Orchestration (load, wait-for-SHA, viewport audit, Chrome lifecycle) lives in
 * visual-qa-common.mjs so this file stays a thin contract runner.
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

const { baseUrl, expectedSha, evidenceDir } = resolveVisualQaConfig();
assertVisualQaRuntime({ baseUrl, expectedSha });
fs.mkdirSync(evidenceDir, { recursive: true });

const { report, findings, fail } = createVisualQaReport({ baseUrl, expectedSha });
const loadPage = createGovernedLoadPage({ expectedSha });

const { chromePath, runtimeError } = await withHeadlessChrome(async (port) => {
  await auditGovernedPages({
    port,
    loadPage,
    baseUrl,
    expectedSha,
    evidenceDir,
    fail,
    report,
  });
  await auditDesktopNavigation(port, loadPage, closeSession, report, fail);
  await auditMobileNavigation(port, loadPage, closeSession, report, fail);
});

report.chrome = chromePath;
report.runtime_error = runtimeError;
if (runtimeError) fail('visual QA runtime', runtimeError);

writeVisualQaReport(evidenceDir, report);
exitVisualQa(report, findings, expectedSha);
