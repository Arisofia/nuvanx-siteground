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
 */
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const baseUrl = (process.env.BASE_URL || 'https://staging2.nuvanx.com').replace(/\/$/, '');
const evidenceDir = process.env.EVIDENCE_DIR || 'staging2-deployment-evidence/entity-graph-audit';

const routes = [
  { path: '/', expect: { clinics: 2, physicians: 3, catalog: true, faq: true } },
  { path: '/equipo-medico/', expect: { clinics: 2, physicians: 3 } },
  { path: '/clinicas-de-medicina-estetica-nuvanx/', expect: { clinics: 2 } },
  { path: '/medicina-estetica-chamberi/', expect: { clinics: 1 } },
  { path: '/endolift-facial-papada-mandibula/', expect: { procedure: true, physician: true } },
  { path: '/endolaser-corporal-grasa-localizada/', expect: { procedure: true, physician: true } },
  { path: '/laser-co2-fraccionado-madrid-textura-cicatrices-poro/', expect: { procedure: true, physician: true } },
  { path: '/exion-btl/', expect: { procedure: true, physician: true } },
  { path: '/exion-face/', expect: { procedure: true, physician: true, faq: true } },
  { path: '/exion-body/', expect: { procedure: true, physician: true, faq: true } },
  { path: '/exion-fractional/', expect: { procedure: true, physician: true, faq: true } },
  { path: '/emfusion/', expect: { procedure: true, physician: true, faq: true } },
  { path: '/btl-exilite-ipl-madrid/', expect: { procedure: true, physician: true } },
  { path: '/contacto/', expect: { clinics: 2 } },
];

function typesOf(node) {
  const t = node?.['@type'];
  if (Array.isArray(t)) return t;
  return t ? [t] : [];
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
    for (const [k, v] of Object.entries(obj)) {
      if (k === '@id') continue;
      collectRefIds(v, acc);
    }
  }
  return acc;
}

function parseGraphs(html) {
  const graphs = [];
  const re = /<script\b[^>]*type=["']application\/ld\+json["'][^>]*>([\s\S]*?)<\/script>/gi;
  let match;
  while ((match = re.exec(html)) !== null) {
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
  const res = await fetch(url, {
    redirect: 'follow',
    headers: { 'User-Agent': 'NUVANX-Entity-Graph-Audit/1.0' },
  });
  const html = await res.text();
  return { url: res.url, status: res.status, html };
}

function analyze(route, response) {
  const issues = [];
  const graphs = parseGraphs(response.html);
  const schemaGraphs = graphs.filter((g) => {
    if (g.error) return true;
    const blob = JSON.stringify(g.data);
    return /schema\.org|@graph|"@type"/i.test(blob);
  });

  if (schemaGraphs.length !== 1) {
    issues.push(`expected 1 Schema.org ld+json block, got ${schemaGraphs.length}`);
  }

  const nodes = schemaGraphs.flatMap((g) => g.nodes || []);
  const idSet = new Set();
  const idCounts = new Map();
  const typeCounts = {};

  for (const node of nodes) {
    for (const t of typesOf(node)) {
      typeCounts[t] = (typeCounts[t] || 0) + 1;
    }
    if (node['@id']) {
      idSet.add(node['@id']);
      idCounts.set(node['@id'], (idCounts.get(node['@id']) || 0) + 1);
    }
  }

  const dups = [...idCounts.entries()].filter(([, c]) => c > 1).map(([id]) => id);
  if (dups.length) issues.push(`duplicate @id: ${dups.slice(0, 5).join(', ')}`);

  const refs = [];
  for (const node of nodes) collectRefIds(node, refs);
  const dangling = [...new Set(refs)].filter(
    (id) => !idSet.has(id) && !String(id).startsWith('https://schema.org/'),
  );
  if (dangling.length) issues.push(`dangling refs: ${dangling.slice(0, 8).join(', ')}`);

  const hasOrg = (typeCounts.Organization || 0) > 0 || (typeCounts.MedicalOrganization || 0) > 0;
  if (!hasOrg) issues.push('missing Organization/MedicalOrganization');

  const expect = route.expect || {};
  if (expect.clinics != null && (typeCounts.MedicalClinic || 0) < expect.clinics) {
    issues.push(`expected >=${expect.clinics} MedicalClinic, got ${typeCounts.MedicalClinic || 0}`);
  }
  if (expect.physicians != null && (typeCounts.Physician || 0) < expect.physicians) {
    issues.push(`expected >=${expect.physicians} Physician, got ${typeCounts.Physician || 0}`);
  }
  if (expect.physician && (typeCounts.Physician || 0) < 1) {
    issues.push('expected Physician');
  }
  if (expect.procedure) {
    const proc = (typeCounts.MedicalProcedure || 0) + (typeCounts.Service || 0);
    if (proc < 1) issues.push('expected MedicalProcedure or Service');
  }
  if (expect.catalog && (typeCounts.OfferCatalog || 0) < 1) {
    issues.push('expected OfferCatalog on home');
  }
  if (expect.faq && (typeCounts.FAQPage || 0) < 1) {
    issues.push('expected FAQPage');
  }

  return {
    path: route.path,
    finalUrl: response.url,
    status: response.status,
    schemaBlocks: schemaGraphs.length,
    nodeCount: nodes.length,
    typeCounts,
    issues,
    ok: issues.length === 0 && response.status >= 200 && response.status < 400,
  };
}

async function main() {
  fs.mkdirSync(evidenceDir, { recursive: true });
  const results = [];
  for (const route of routes) {
    try {
      const response = await fetchHtml(route.path);
      results.push(analyze(route, response));
    } catch (err) {
      results.push({
        path: route.path,
        ok: false,
        issues: [`fetch failed: ${err.message}`],
      });
    }
  }

  const report = {
    baseUrl,
    generatedAt: new Date().toISOString(),
    summary: {
      total: results.length,
      passed: results.filter((r) => r.ok).length,
      failed: results.filter((r) => !r.ok).length,
    },
    results,
  };

  const outPath = path.join(evidenceDir, 'entity-graph-report.json');
  fs.writeFileSync(outPath, JSON.stringify(report, null, 2));

  console.log(`Entity graph audit: ${report.summary.passed}/${report.summary.total} passed`);
  console.log(`Evidence: ${outPath}`);
  for (const r of results) {
    const mark = r.ok ? 'OK ' : 'FAIL';
    console.log(`${mark} ${r.path}${r.issues?.length ? ' — ' + r.issues.join('; ') : ''}`);
  }

  if (report.summary.failed > 0) process.exit(1);
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});
