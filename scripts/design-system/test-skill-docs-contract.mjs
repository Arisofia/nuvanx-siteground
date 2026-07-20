#!/usr/bin/env node
import fs from 'node:fs';
import path from 'node:path';
import process from 'node:process';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const read = (relative) => fs.readFileSync(path.join(root, relative), 'utf8');
const exists = (relative) => fs.existsSync(path.join(root, relative));
const failures = [];

function requireMatch(source, pattern, message) {
  if (!pattern.test(source)) failures.push(message);
}

function parseFrontmatter(source, file) {
  const match = source.match(/^---\n([\s\S]*?)\n---\n/);
  if (!match) {
    failures.push(`${file} is missing a YAML frontmatter block`);
    return {};
  }
  const fields = {};
  for (const line of match[1].split('\n')) {
    const fieldMatch = line.match(/^([a-zA-Z0-9_]+):\s*(.*)$/);
    if (fieldMatch) fields[fieldMatch[1]] = fieldMatch[2].trim();
  }
  return fields;
}

const skills = [
  { dir: 'design-critique', name: 'design-critique' },
  { dir: 'design-system', name: 'design-system' },
  { dir: 'nuvanx-theme-factory', name: 'nuvanx-theme-factory' },
];

const sources = {};
for (const skill of skills) {
  const relative = `.agents/skills/${skill.dir}/SKILL.md`;
  if (!exists(relative)) {
    failures.push(`missing skill file: ${relative}`);
    continue;
  }
  const source = read(relative);
  sources[skill.dir] = source;

  const frontmatter = parseFrontmatter(source, relative);
  if (frontmatter.name !== skill.name) {
    failures.push(`${relative} frontmatter "name" must equal "${skill.name}" (found "${frontmatter.name}")`);
  }
  if (!frontmatter.description || frontmatter.description.length < 20) {
    failures.push(`${relative} frontmatter "description" must be a non-trivial summary`);
  }
}

// --- design-critique: 5-pillar evaluation framework ---------------------
const critique = sources['design-critique'];
if (critique) {
  requireMatch(critique, /Quiet Luxury/, 'design-critique must reference the Quiet Luxury contract');
  requireMatch(critique, /Playfair Display[\s\S]*Manrope/, 'design-critique must require the canonical font pair');
  requireMatch(critique, /nvx-tokens\.css/, 'design-critique must point to nvx-tokens.css as the source of truth');
  requireMatch(critique, /WCAG 2\.1 AA/, 'design-critique must require WCAG 2.1 AA evaluation');
  requireMatch(critique, /4\.5:1[\s\S]*3:1/, 'design-critique must state the minimum contrast ratios');
  for (const pillar of [
    'First Impression',
    'Usability',
    'Visual Hierarchy',
    'Consistency',
    'Accessibility',
  ]) {
    requireMatch(critique, new RegExp(pillar), `design-critique is missing the "${pillar}" pillar`);
  }
}

// --- design-system: handoff spec generation rules ------------------------
const designSystem = sources['design-system'];
if (designSystem) {
  requireMatch(designSystem, /Zero New Tokens/, 'design-system must state the zero-new-tokens rule');
  requireMatch(designSystem, /Sin `!important`/, 'design-system must forbid !important');
  requireMatch(designSystem, /scripts\/design-system\/audit-css\.mjs/, 'design-system must reference the audit-css.mjs ACTIVE_STACK');
  requireMatch(designSystem, /nvx-tokens\.css/, 'design-system must reference nvx-tokens.css as the live source of truth');
  requireMatch(designSystem, /aria-label/, 'design-system handoff spec must require ARIA attributes');
}

// --- nuvanx-theme-factory: marketing-only variance rules -----------------
const themeFactory = sources['nuvanx-theme-factory'];
if (themeFactory) {
  requireMatch(themeFactory, /Metal Pulido es la única identidad visual de producción/, 'theme-factory must assert Metal Pulido is the sole production identity');
  requireMatch(themeFactory, /Playfair Display/, 'theme-factory must name Playfair Display');
  requireMatch(themeFactory, /Manrope/, 'theme-factory must name Manrope');
  requireMatch(themeFactory, /No utilizar Bodoni Moda, Cormorant Garamond, Inter, Source Sans, Pinyon Script/, 'theme-factory must explicitly forbid decorative/alternate fonts');

  for (const forbiddenEdit of [
    'nvx-tokens\\.css',
    'hojas CSS cargadas por WordPress',
    'plantillas PHP del tema',
    'configuración de plugins',
    'identidad del logotipo NUVANX',
  ]) {
    requireMatch(themeFactory, new RegExp(forbiddenEdit), `theme-factory must forbid editing: ${forbiddenEdit}`);
  }

  const accentBlocks = [
    ['Zafiro', '#27435F', '#1D334A', '#E8EEF3'],
    ['Oro Editorial', '#9A7A42', '#755A2D', '#F1E8D8'],
  ];
  for (const [name, primary, hover, tint] of accentBlocks) {
    requireMatch(themeFactory, new RegExp(name), `theme-factory must document the ${name} accent variant`);
    requireMatch(themeFactory, new RegExp(primary), `theme-factory ${name} primary hex is missing or changed`);
    requireMatch(themeFactory, new RegExp(hover), `theme-factory ${name} hover hex is missing or changed`);
    requireMatch(themeFactory, new RegExp(tint), `theme-factory ${name} tint hex is missing or changed`);
  }

  requireMatch(themeFactory, /Elegir \*\*una sola variante\*\* por artefacto/, 'theme-factory must limit artifacts to a single accent variant');
  requireMatch(themeFactory, /8-12% de la superficie visual/, 'theme-factory must cap accent coverage to 8-12%');
  requireMatch(themeFactory, /CONCEPTO - NO PRODUCCIÓN/, 'theme-factory must require the concept-only label for prototypes');
  requireMatch(themeFactory, /No modifica el sistema visual web de producción/, 'theme-factory handoff must include the non-modification declaration');
}

if (failures.length) {
  console.error('Agent skill documentation contract failed:');
  for (const failure of failures) console.error(`- ${failure}`);
  process.exit(1);
}

console.log('PASS: design-critique, design-system and nuvanx-theme-factory skills document the Metal Pulido contract');