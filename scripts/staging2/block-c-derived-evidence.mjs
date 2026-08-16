import fs from 'node:fs/promises';
import path from 'node:path';
import { BLOCK_C_VIEWPORTS } from './block-c-browser-config.mjs';

function buildMatrix(results) {
  const pageOrder = [];
  const pages = new Map();
  const viewportOrder = BLOCK_C_VIEWPORTS.map((viewport) => viewport.key);

  for (const result of results) {
    const route = String(result.route || '');
    if (!pages.has(route)) {
      pages.set(route, {
        pageId: result.pageId,
        route,
        statuses: {},
      });
      pageOrder.push(route);
    }
    pages.get(route).statuses[result.viewport?.key || 'unknown'] = result.status;
  }

  const viewportHeaders = BLOCK_C_VIEWPORTS.map((viewport) => `${viewport.width}×${viewport.height}`);
  const rows = [
    `| # | WP ID | URL | ${viewportHeaders.join(' | ')} |`,
    `|---:|---:|---|${viewportHeaders.map(() => '---:').join('|')}|`,
  ];
  pageOrder.forEach((route, index) => {
    const page = pages.get(route);
    rows.push(`| ${index + 1} | ${page.pageId} | \`${route}\` | ${viewportOrder.map((key) => page.statuses[key] || '—').join(' | ')} |`);
  });
  return rows;
}

export function renderBlockCDerivedEvidence(results, expectedSha) {
  const matrixRows = buildMatrix(results);
  const passCount = results.filter((item) => item.status === 'PASS').length;
  const fixCount = results.filter((item) => item.status === 'FIX').length;
  const blockedCount = results.filter((item) => item.status === 'BLOCKED').length;
  const originRows = results
    .filter((item) => item.originVerified && item.externalInconclusive === true)
    .map((item) => `| ${item.pageId} | \`${item.route}\` | ${item.viewport?.label || 'unknown'} | ${item.edgeHttpStatus || 0} | ${item.originStatus || 0} | \`${item.originDeploySha || ''}\` | ${item.visualValidation || ''} |`);
  const browserRecoveries = results.filter((item) => item.recoveredFromExternalInconclusive === true && item.visualValidation === 'complete-public-browser-recovery');
  const pageCount = new Set(results.map((item) => item.route)).size;
  const viewportLabels = [...new Set(results.map((item) => item.viewport?.label).filter(Boolean))];
  const findings = results.filter((item) => item.status !== 'PASS');

  const summary = [
    '# NUVANX Staging2 — Block C Visual QA',
    '',
    `Expected staging SHA: \`${expectedSha}\``,
    `Published WordPress pages: ${pageCount}`,
    `Viewports: ${viewportLabels.join(', ')}`,
    `Total cases: ${results.length}`,
    `PASS: ${passCount}`,
    `FIX: ${fixCount}`,
    `BLOCKED: ${blockedCount}`,
    `Origin-verified edge-inconclusive cases: ${originRows.length}`,
    'Published WordPress pages must remain addressable; editorial readiness is governed by robots/sitemap policy.',
    'Origin fallback may certify HTTP 200, exact deploy SHA and staging noindex/nofollow when SiteGround Antibot blocks the edge browser; it does not certify geometry, H1 visibility, responsive layout or images for cases that remain edge-inconclusive.',
    '',
    '## Matrix',
    '',
    ...matrixRows,
    '',
    '## Findings',
    '',
    '| WP ID | URL | Viewport | Status | Finding | Screenshot |',
    '|---:|---|---|---|---|---|',
    ...(findings.length
      ? findings.map((item) => `| ${item.pageId} | \`${item.route}\` | ${item.viewport?.label || 'unknown'} | ${item.status} | ${[...(item.blockers || []), ...(item.issues || [])].join('; ').replaceAll('|', '\\|')} | \`${item.screenshot || ''}\` |`)
      : ['| — | — | — | PASS | No findings | — |']),
    '',
    '## SiteGround origin fallback evidence',
    '',
    '| WP ID | URL | Viewport | Edge HTTP | Origin HTTP | Origin SHA | Visual state |',
    '|---:|---|---|---:|---:|---|---|',
    ...(originRows.length ? originRows : ['| — | — | — | — | — | — | No edge-inconclusive origin fallback remains |']),
    ...(browserRecoveries.length ? [
      '',
      '## Public browser recovery',
      '',
      ...browserRecoveries.map((item) => `- \`${item.route}\` · ${item.viewport?.label || 'unknown'}: public browser completed the visual contract with exact deploy SHA \`${expectedSha}\`.`),
    ] : []),
    '',
  ].join('\n');

  const csvHeader = ['wp_id', 'title', 'route', 'viewport', 'width', 'height', 'status', 'expected_http_status', 'http_status', 'edge_http_status', 'final_url', 'meta_sha', 'external_inconclusive', 'origin_verified', 'origin_status', 'origin_sha', 'origin_robots', 'visual_validation', 'horizontal_overflow_px', 'h1', 'issues', 'notes', 'screenshot'];
  const csvEscape = (value) => `"${String(value ?? '').replaceAll('"', '""').replaceAll('\n', ' ')}"`;
  const csvRows = [csvHeader.map(csvEscape).join(',')];
  for (const item of results) {
    csvRows.push([
      item.pageId,
      item.title,
      item.route,
      item.viewport?.label || '',
      item.viewport?.width || '',
      item.viewport?.height || '',
      item.status,
      item.expectedHttpStatus,
      item.httpStatus,
      item.edgeHttpStatus,
      item.finalUrl,
      item.metaSha,
      item.externalInconclusive,
      item.originVerified,
      item.originStatus ?? '',
      item.originDeploySha,
      item.originRobots,
      item.visualValidation,
      item.geometry?.horizontalOverflowPx ?? '',
      item.geometry?.h1Text ?? '',
      [...(item.blockers || []), ...(item.issues || [])].join('; '),
      (item.notes || []).join('; '),
      item.screenshot,
    ].map(csvEscape).join(','));
  }

  return {
    matrix: `${matrixRows.join('\n')}\n`,
    summary: `${summary}\n`,
    csv: `${csvRows.join('\n')}\n`,
  };
}

async function stageEntry(filePath, content, token) {
  const tmpPath = `${filePath}.tmp-${token}`;
  const backupPath = `${filePath}.bak-${token}`;
  let existed = true;
  await fs.writeFile(tmpPath, content, 'utf8');
  try {
    await fs.copyFile(filePath, backupPath);
  } catch (error) {
    if (error?.code === 'ENOENT') existed = false;
    else throw error;
  }
  return { filePath, tmpPath, backupPath, existed, replaced: false };
}

export async function writeBlockCEvidenceBundle({ results, expectedSha, artifactsDir, extraEntries = [] }) {
  const derived = renderBlockCDerivedEvidence(results, expectedSha);
  const token = `${process.pid}-${Date.now()}`;
  const entries = [
    [path.join(artifactsDir, 'block-c-matrix.md'), derived.matrix],
    [path.join(artifactsDir, 'block-c-summary.md'), derived.summary],
    [path.join(artifactsDir, 'block-c-results.csv'), derived.csv],
    [path.join(artifactsDir, 'block-c-results.json'), `${JSON.stringify(results, null, 2)}\n`],
    ...extraEntries,
  ];
  const staged = [];

  try {
    for (const [filePath, content] of entries) {
      staged.push(await stageEntry(filePath, content, token));
    }
    for (const entry of staged) {
      await fs.rename(entry.tmpPath, entry.filePath);
      entry.replaced = true;
    }
  } catch (error) {
    const rollbackFailures = [];
    for (const entry of [...staged].reverse()) {
      if (!entry.replaced) continue;
      try {
        if (entry.existed) await fs.rename(entry.backupPath, entry.filePath);
        else await fs.rm(entry.filePath, { force: true });
      } catch (rollbackError) {
        rollbackFailures.push(`${path.basename(entry.filePath)}:${rollbackError instanceof Error ? rollbackError.message : String(rollbackError)}`);
      }
    }
    const suffix = rollbackFailures.length ? ` rollback_failures=${rollbackFailures.join('|')}` : '';
    throw new Error(`Block C evidence bundle commit failed after ${staged.filter((entry) => entry.replaced).length} replacement(s): ${error instanceof Error ? error.message : String(error)}${suffix}`, { cause: error });
  } finally {
    await Promise.all(staged.flatMap((entry) => [
      fs.rm(entry.tmpPath, { force: true }).catch(() => {}),
      fs.rm(entry.backupPath, { force: true }).catch(() => {}),
    ]));
  }
}
