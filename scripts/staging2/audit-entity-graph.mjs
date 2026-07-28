#!/usr/bin/env node
/**
 * Live entity-graph audit against staging2 (or BASE_URL).
 *
 * Checks per route:
 * - exactly one Schema.org ld+json block (Yoast)
 * - Organization / MedicalOrganization present
 * - no dangling @id refs (except schema.org vocabulary URLs)
 * - no duplicate @id
 * - treatment routes expose Service or MedicalProcedure
 * - home / equipo expose MedicalClinic + Physician
 * - redirects cannot silently substitute another audited route
 */
import fs from 'node:fs';
import path from 'node:path';

const baseUrl = (process.env.BASE_URL || 'https://staging2.nuvanx.com').replace(/\/$/, '');
const evidenceDir = process.env.EVIDENCE_DIR || 'staging2-deployment-evidence/entity-graph-audit';

// Clinic and physician counts are minimum thresholds, not exact inventory locks.
const routes = [
  { path: '/', expect: { minClinics: 2, minPhysicians: 3, catalog: true, faq: true } },
  { path: '/equipo-medico/', expect: { minClinics: 2, minPhysicians: 3 } },
  { path: '/clinicas-de-medicina-estetica-nuvanx/', expect: { minClinics: 2 } },
  { path: '/medicina-estetica-chamberi/', expect: { minClinics: 1 } },
  {
    path: '/clinicas-de-medicina-estetica-nuvanx/medicina-estetica-goya-barrio-salamanca/',
    expect: { minClinics: 1 },
  },
  { path: '/endolift-facial-papada-mandibula/', expect: { procedure: true, physician: true } },
  { path: '/endolaser-corporal-grasa-localizada/', expect: { procedure: true, physician: true } },
  { path: '/laser-co2-fraccionado-madrid-textura-cicatrices-poro/', expect: { procedure: true, physician: true } },
  { path: '/exion-btl/', expect: { procedure: true, physician: true } },
  { path: '/exion-face/', expect: { procedure: true, physician: true, faq: true } },
  { path: '/exion-body/', expect: { procedure: true, physician: true, faq: true } },
  { path: '/exion-fractional/', expect: { procedure: true, physician: true, faq: true } },
  { path: '/emfusion/', expect: { procedure: true, physician: true, faq: true } },
  { path: '/btl-exilite-ipl-madrid/', expect: { procedure: true, physician: true } },
  { path: '/contacto/', expect: { minClinics: 2 } },
];

function typesOf(node) {
  const type = node?.['@type'];
  if (Array.isArray(type)) return type;
  return type ? [type] : [];
}

function collectRefIds(obj, acc = []) {
  if (Array.isArray(obj)) {
    for (const item of obj) collectRefIds(item, acc);
    return acc;
  }
  if (obj && typeof obj === 'object') {
    const keys = Object.keys(obj);
    if (keys.length === 1 && keys[0] === '@id' && typeof obj['@id'] === 'string') {
      acc.push(obj['@id']);
      return acc;
    }
    for (const [key, value] of Object.entries(obj)) {
      if (key === '@id') continue;
      collectRefIds(value, acc);
    }
  }
  return acc;
}

function parseGraphs(html) {
  const graphs = [];
  const scriptPattern = /<script\b(?=[^>]*\stype\s*=\s*(?:"application\/ld\+json"|'application\/ld\+json'|application\/ld\+json(?=\s|>)))[^>]*>([\s\S]*?)<\/script>/gi;
  let match;
  while ((match = scriptPattern.exec(html)) !== null) {
    const raw = match[1].trim();
    try {
      const data = JSON.parse(raw);
      if (data && typeof data === 'object' && Array.isArray(data['@graph'])) {
        graphs.push({ raw, data, nodes: data['@graph'] });
      } else if (Array.isArray(data)) {
        graphs.push({ raw, data, nodes: data });
      } else if (data && typeof data === 'object') {
        graphs.push({ raw, data, nodes: [data] });
      }
    } catch {
      graphs.push({ raw, error: 'parse_error', nodes: [] });
    }
  }
  return graphs;
}

async function fetchHtml(routePath) {
  const url = `${baseUrl}${routePath}`;
  const response = await fetch(url, {
    redirect: 'follow',
    headers: { 'User-Agent': 'NUVANX-Entity-Graph-Audit/1.0' },
    signal: AbortSignal.timeout(30000),
  });
  const html = await response.text();
  return { url: response.url, status: response.status, html };
}

function normalizePath(value) {
  const pathname = /^https?:\/\//i.test(value) ? new URL(value).pathname : String(value).split('?')[0];
  const normalized = `/${pathname.replace(/^\/+|\/+$/g, '')}/`;
  return normalized === '//' ? '/' : normalized;
}

function buildTypeAndIdIndex(nodes) {
  const idSet = new Set();
  const idCounts = new Map();
  const typeCounts = {};

  for (const node of nodes) {
    for (const type of typesOf(node)) {
      typeCounts[type] = (typeCounts[type] || 0) + 1;
    }
    if (node['@id']) {
      idSet.add(node['@id']);
      idCounts.set(node['@id'], (idCounts.get(node['@id']) || 0) + 1);
    }
  }

  return { idSet, idCounts, typeCounts };
}

function findDuplicateIds(idCounts) {
  return [...idCounts.entries()].filter(([, count]) => count > 1).map(([id]) => id);
}

function findDanglingRefs(nodes, idSet) {
  const refs = [];
  for (const node of nodes) collectRefIds(node, refs);
  return [...new Set(refs)].filter(
    (id) => !idSet.has(id) && !String(id).startsWith('https://schema.org/'),
  );
}

function checkResponse(route, response, issues) {
  if (response.status < 200 || response.status >= 400) {
    issues.push(`unexpected HTTP status ${response.status}`);
  }

  const expectedPath = normalizePath(route.path);
  const finalPath = normalizePath(response.url);
  if (finalPath !== expectedPath) {
    issues.push(`unexpected redirect: ${expectedPath} -> ${finalPath}`);
  }
}

function checkExpectations(route, typeCounts, issues) {
  const hasOrg = (typeCounts.Organization || 0) > 0 || (typeCounts.MedicalOrganization || 0) > 0;
  if (!hasOrg) issues.push('missing Organization/MedicalOrganization');

  const expect = route.expect || {};
  if (expect.minClinics != null && (typeCounts.MedicalClinic || 0) < expect.minClinics) {
    issues.push(`expected >=${expect.minClinics} MedicalClinic, got ${typeCounts.MedicalClinic || 0}`);
  }
  if (expect.minPhysicians != null && (typeCounts.Physician || 0) < expect.minPhysicians) {
    issues.push(`expected >=${expect.minPhysicians} Physician, got ${typeCounts.Physician || 0}`);
  }
  if (expect.physician && (typeCounts.Physician || 0) < 1) {
    issues.push('expected Physician');
  }
  if (expect.procedure) {
    const procedures = (typeCounts.MedicalProcedure || 0) + (typeCounts.Service || 0);
    if (procedures < 1) issues.push('expected MedicalProcedure or Service');
  }
  if (expect.catalog && (typeCounts.OfferCatalog || 0) < 1) {
    issues.push('expected OfferCatalog on home');
  }
  if (expect.faq && (typeCounts.FAQPage || 0) < 1) {
    issues.push('expected FAQPage');
  }
}

function analyze(route, response) {
  const issues = [];
  checkResponse(route, response, issues);

  const graphs = parseGraphs(response.html);
  const schemaGraphs = graphs.filter((graph) => {
    const testContent = graph.error ? graph.raw : JSON.stringify(graph.data);
    return /schema\.org|@graph|"@type"/i.test(testContent);
  });

  if (schemaGraphs.length !== 1) {
    issues.push(`expected 1 Schema.org ld+json block, got ${schemaGraphs.length}`);
  }

  const nodes = schemaGraphs.flatMap((graph) => graph.nodes || []);
  const { idSet, idCounts, typeCounts } = buildTypeAndIdIndex(nodes);

  const duplicateIds = findDuplicateIds(idCounts);
  if (duplicateIds.length) issues.push(`duplicate @id: ${duplicateIds.slice(0, 5).join(', ')}`);

  const danglingRefs = findDanglingRefs(nodes, idSet);
  if (danglingRefs.length) issues.push(`dangling refs: ${danglingRefs.slice(0, 8).join(', ')}`);

  checkExpectations(route, typeCounts, issues);

  return {
    path: route.path,
    finalUrl: response.url,
    status: response.status,
    schemaBlocks: schemaGraphs.length,
    nodeCount: nodes.length,
    typeCounts,
    issues,
    ok: issues.length === 0,
  };
}

async function main() {
  fs.mkdirSync(evidenceDir, { recursive: true });
  const results = [];
  for (const route of routes) {
    try {
      const response = await fetchHtml(route.path);
      results.push(analyze(route, response));
    } catch (error) {
      const message = error instanceof Error ? error.message : String(error);
      results.push({
        path: route.path,
        ok: false,
        issues: [`fetch failed: ${message}`],
      });
    }
  }

  const report = {
    baseUrl,
    generatedAt: new Date().toISOString(),
    summary: {
      total: results.length,
      passed: results.filter((result) => result.ok).length,
      failed: results.filter((result) => !result.ok).length,
    },
    results,
  };

  const outPath = path.join(evidenceDir, 'entity-graph-report.json');
  fs.writeFileSync(outPath, JSON.stringify(report, null, 2));

  console.log(`Entity graph audit: ${report.summary.passed}/${report.summary.total} passed`);
  console.log(`Evidence: ${outPath}`);
  for (const result of results) {
    const mark = result.ok ? 'OK ' : 'FAIL';
    console.log(`${mark} ${result.path}${result.issues?.length ? ` — ${result.issues.join('; ')}` : ''}`);
  }

  if (report.summary.failed > 0) process.exit(1);
}

try {
  await main();
} catch (error) {
  console.error(error);
  process.exit(1);
}
