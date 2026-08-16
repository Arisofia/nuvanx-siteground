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
      pages.set(route, { pageId: result.pageId, route, statuses: {} });
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

export function renderBlockCEvidence(results, {
  expectedSha,
  recoverySummary = null,
  recoverySectionTitle = 'Public browser recovery',
} = {}) {
  const matrixRows = buildMatrix(results);
  const passCount = results.filter((item) => item.status === 'PASS').length;
  const fixCount = results.filter((item) => item.status === 'FIX').length;
  const blockedCount = results.filter((item) => item.status === 'BLOCKED').length;
  const originRows = results
    .filter((item) => item.originVerified && item.externalInconclusive === true)
    .map((item) => `| ${item.pageId} | \`${item.route}\` | ${item.viewport?.label || 'unknown'} | ${item.edgeHttpStatus || 0} | ${item.originStatus || 0} | \`${item.originDeploySha || ''}\` | ${item.visualValidation || ''} |`);
  const pageCount = new Set(results.map((item) => item.route)).size;
  const viewportLabels = [...new Set(results.map((item) => item.viewport?.label).filter(Boolean))];
  const findings = results.filter((item) => item.status !== 'PASS');

  const summary = [
    '# NUVANX Staging2 — Block C Visual QA',
    '',
    `Expected staging SHA: \`${expectedSha || ''}\``,
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
    ...(recoverySummary ? ['', `## ${recoverySectionTitle}`, '', ...recoverySummary, ''] : []),
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

async function prepareEvidenceEntry(filePath, content, token) {
  const resolvedFilePath = path.resolve(filePath);
  const fileName = path.basename(resolvedFilePath);
  const tmpPath = `${resolvedFilePath}.tmp-${token}`;
  const backupPath = `${resolvedFilePath}.bak-${token}`;
  let hadOriginal = true;

  try {
    await fs.writeFile(tmpPath, content, 'utf8');
    try {
      await fs.copyFile(resolvedFilePath, backupPath);
    } catch (error) {
      if (error?.code === 'ENOENT') hadOriginal = false;
      else throw error;
    }
    return {
      filePath: resolvedFilePath,
      fileName,
      tmpPath,
      backupPath,
      hadOriginal,
    };
  } catch (error) {
    await fs.rm(tmpPath, { force: true }).catch(() => {});
    await fs.rm(backupPath, { force: true }).catch(() => {});
    throw error;
  }
}

export async function writeEvidenceBundle(entries) {
  const staged = [];
  const committed = [];
  const preservedBackupPaths = new Set();
  const token = `${process.pid}-${Date.now()}`;
  const artifactsDir = entries.length > 0 ? path.dirname(path.resolve(entries[0][0])) : process.cwd();
  const inconsistentMarkerPath = path.join(artifactsDir, 'block-c-evidence-inconsistent.json');

  try {
    await fs.rm(inconsistentMarkerPath, { force: true }).catch(() => {});

    for (const [filePath, content] of entries) {
      staged.push(await prepareEvidenceEntry(filePath, content, token));
    }

    for (const entry of staged) {
      await fs.rename(entry.tmpPath, entry.filePath);
      committed.push(entry);
    }
  } catch (error) {
    const rollbackFailures = [];
    for (const entry of [...committed].reverse()) {
      try {
        if (entry.hadOriginal) await fs.copyFile(entry.backupPath, entry.filePath);
        else await fs.rm(entry.filePath, { force: true });
      } catch (rollbackError) {
        rollbackFailures.push({
          entry,
          filePath: entry.filePath,
          fileName: entry.fileName,
          message: `${entry.filePath}:${rollbackError?.message || rollbackError}`,
        });
      }
    }

    const removedInvalidFiles = [];
    const cleanupFailures = [];
    let markerWriteError = '';
    if (rollbackFailures.length > 0) {
      for (const { entry, filePath, fileName } of rollbackFailures) {
        if (entry.hadOriginal) preservedBackupPaths.add(entry.backupPath);
        try {
          await fs.rm(filePath, { force: true });
          removedInvalidFiles.push(filePath);
        } catch (cleanupError) {
          cleanupFailures.push({
            filePath,
            fileName,
            message: `${filePath}:${cleanupError?.message || cleanupError}`,
          });
        }
      }

      const failedPaths = new Set(rollbackFailures.map(({ filePath }) => filePath));
      const marker = {
        schema: 5,
        status: 'inconsistent-evidence-bundle',
        writeError: error?.message || String(error),
        rollbackErrors: rollbackFailures.map(({ message }) => message),
        removedInvalidFiles,
        remainingInvalidFiles: cleanupFailures.map(({ filePath }) => filePath),
        cleanupErrors: cleanupFailures.map(({ message }) => message),
        retainedPriorFiles: staged
          .filter((entry) => entry.hadOriginal && !failedPaths.has(entry.filePath))
          .map((entry) => entry.filePath),
        absentPriorFiles: staged
          .filter((entry) => !entry.hadOriginal)
          .map((entry) => entry.filePath),
        preservedBackupFiles: rollbackFailures
          .filter(({ entry }) => entry.hadOriginal)
          .map(({ entry }) => entry.backupPath),
        generatedAt: new Date().toISOString(),
      };

      try {
        await fs.writeFile(inconsistentMarkerPath, `${JSON.stringify(marker, null, 2)}\n`, 'utf8');
      } catch (markerError) {
        markerWriteError = markerError?.message || String(markerError);
      }
    }

    const details = [
      rollbackFailures.length ? `rollback_errors=${rollbackFailures.map(({ message }) => message).join('|')}` : '',
      cleanupFailures.length ? `cleanup_errors=${cleanupFailures.map(({ message }) => message).join('|')}` : '',
      preservedBackupPaths.size ? `preserved_backups=${[...preservedBackupPaths].join('|')}` : '',
      markerWriteError ? `marker_write_error=${markerWriteError}` : '',
    ].filter(Boolean);
    const detail = details.length ? ` ${details.join(' ')}` : '';
    throw new Error(`Block C evidence bundle commit failed: ${error?.message || error}.${detail}`, { cause: error });
  } finally {
    await Promise.all(staged.flatMap(({ tmpPath, backupPath }) => [
      fs.rm(tmpPath, { force: true }).catch(() => {}),
      preservedBackupPaths.has(backupPath)
        ? Promise.resolve()
        : fs.rm(backupPath, { force: true }).catch(() => {}),
    ]));
  }
}
