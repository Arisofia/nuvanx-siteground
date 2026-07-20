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

function requireAbsent(source, pattern, message) {
  if (pattern.test(source)) failures.push(message);
}

function parseFrontmatter(source, label) {
  const match = source.match(/^---\n([\s\S]*?)\n---\n/);
  if (!match) {
    failures.push(`${label}: missing YAML frontmatter block`);
    return {};
  }
  const fields = {};
  for (const line of match[1].split('\n')) {
    const fieldMatch = line.match(/^([a-zA-Z]+):\s*(.+)$/);
    if (fieldMatch) fields[fieldMatch[1]] = fieldMatch[2];
  }
  return fields;
}

// ---------------------------------------------------------------------------
// Skill: design-critique
// ---------------------------------------------------------------------------
const criticalSkillPath = '.agents/skills/design-critique/SKILL.md';
if (!exists(criticalSkillPath)) {
  failures.push(`missing skill file: ${criticalSkillPath}`);
} else {
  const critique = read(criticalSkillPath);
  const frontmatter = parseFrontmatter(critique, 'design-critique');
  if (frontmatter.name !== 'design-critique') {
    failures.push(`design-critique frontmatter name must equal "design-critique", got "${frontmatter.name}"`);
  }
  if (!frontmatter.description || !/Quiet Luxury/i.test(frontmatter.description)) {
    failures.push('design-critique frontmatter description must reference "Quiet Luxury"');
  }
  requireMatch(critique, /Playfair Display[\s\S]*Manrope/, 'design-critique must require the canonical Playfair Display + Manrope pair');
  requireMatch(critique, /nvx-tokens\.css/, 'design-critique must reference nvx-tokens.css as the source of truth');
  requireMatch(critique, /## Framework de Evaluaci[oó]n/, 'design-critique must define an evaluation framework section');
  requireMatch(critique, /4\.5:1/, 'design-critique must state the 4.5:1 minimum contrast ratio for normal text');
  requireMatch(critique, /3:1/, 'design-critique must state the 3:1 minimum contrast ratio for large text');
  requireMatch(critique, />=\s*48px/, 'design-critique must require touch targets of at least 48px');
  requireMatch(critique, /WCAG 2\.1 AA/, 'design-critique must reference the WCAG 2.1 AA standard');
  requireMatch(critique, /ni `!important`/, 'design-critique must explicitly forbid designs that introduce !important');
}

// ---------------------------------------------------------------------------
// Skill: design-system
// ---------------------------------------------------------------------------
const designSystemSkillPath = '.agents/skills/design-system/SKILL.md';
if (!exists(designSystemSkillPath)) {
  failures.push(`missing skill file: ${designSystemSkillPath}`);
} else {
  const designSystem = read(designSystemSkillPath);
  const frontmatter = parseFrontmatter(designSystem, 'design-system');
  if (frontmatter.name !== 'design-system') {
    failures.push(`design-system frontmatter name must equal "design-system", got "${frontmatter.name}"`);
  }
  if (!frontmatter.description || !/Metal Pulido/i.test(frontmatter.description)) {
    failures.push('design-system frontmatter description must reference "Metal Pulido"');
  }
  requireMatch(designSystem, /Handoff Spec/, 'design-system must define Handoff Spec generation');
  requireMatch(designSystem, /Zero New Tokens/, 'design-system must enforce the "Zero New Tokens" rule');
  requireMatch(designSystem, /Sin `!important`/, 'design-system must forbid !important');
  requireMatch(designSystem, /nvx-tokens\.css/, 'design-system must reference nvx-tokens.css');
  requireMatch(designSystem, /audit-css\.mjs/, 'design-system must reference the audit-css.mjs specificity contract');
  requireMatch(designSystem, /aria-label/, 'design-system handoff spec must require ARIA attributes such as aria-label');
}

// ---------------------------------------------------------------------------
// Skill: nuvanx-theme-factory
// ---------------------------------------------------------------------------
const themeFactorySkillPath = '.agents/skills/nuvanx-theme-factory/SKILL.md';
if (!exists(themeFactorySkillPath)) {
  failures.push(`missing skill file: ${themeFactorySkillPath}`);
} else {
  const factory = read(themeFactorySkillPath);
  const frontmatter = parseFrontmatter(factory, 'nuvanx-theme-factory');
  if (frontmatter.name !== 'nuvanx-theme-factory') {
    failures.push(`nuvanx-theme-factory frontmatter name must equal "nuvanx-theme-factory", got "${frontmatter.name}"`);
  }
  if (!frontmatter.description || !/Metal Pulido/i.test(frontmatter.description)) {
    failures.push('nuvanx-theme-factory frontmatter description must reference "Metal Pulido"');
  }

  requireMatch(
    factory,
    /Metal Pulido es la [uú]nica identidad visual de producci[oó]n/,
    'nuvanx-theme-factory must state Metal Pulido is the only production identity',
  );
  requireMatch(
    factory,
    /wp-content\/themes\/nuvanx-medical\/assets\/css\/nvx-tokens\.css/,
    'nuvanx-theme-factory must forbid editing the canonical token stylesheet',
  );
  requireMatch(factory, /hojas CSS cargadas por WordPress/, 'nuvanx-theme-factory must forbid editing WordPress-loaded stylesheets');
  requireMatch(factory, /plantillas PHP del tema/, 'nuvanx-theme-factory must forbid editing theme PHP templates');
  requireMatch(factory, /CONCEPTO - NO PRODUCCI[OÓ]N/, 'nuvanx-theme-factory must require the concept/no-production label for prototypes');

  requireMatch(factory, /Playfair Display/, 'nuvanx-theme-factory must specify Playfair Display for editorial typography');
  requireMatch(factory, /Manrope/, 'nuvanx-theme-factory must specify Manrope for functional typography');
  requireMatch(
    factory,
    /No utilizar Bodoni Moda, Cormorant Garamond, Inter, Source Sans, Pinyon Script/,
    'nuvanx-theme-factory must explicitly ban the retired decorative fonts',
  );

  const paletteHexValues = ['#FCFBF8', '#F8F7F4', '#1A1A1A', '#2B2926', '#ECEAE6', '#D4D1CC', '#756F69'];
  for (const hex of paletteHexValues) {
    requireMatch(factory, new RegExp(hex.replace('#', '\\#')), `nuvanx-theme-factory must document the Metal Pulido color ${hex}`);
  }

  requireMatch(factory, /Acento Zafiro/, 'nuvanx-theme-factory must define the Zafiro accent variant');
  requireMatch(factory, /#27435F/, 'nuvanx-theme-factory must document the Zafiro primary color');
  requireMatch(factory, /Acento Oro Editorial/, 'nuvanx-theme-factory must define the Oro Editorial accent variant');
  requireMatch(factory, /#9A7A42/, 'nuvanx-theme-factory must document the Oro Editorial primary color');

  requireMatch(factory, /Elegir \*\*una sola variante\*\* por artefacto/, 'nuvanx-theme-factory must limit artifacts to a single accent variant');
  requireMatch(factory, /8-12%/, 'nuvanx-theme-factory must cap accent coverage between 8-12% of the visual surface');
  requireMatch(factory, /WCAG AA/, 'nuvanx-theme-factory must require WCAG AA contrast for functional text');

  requireMatch(factory, /## Flujo de handoff/, 'nuvanx-theme-factory must define a handoff flow section');
  requireMatch(
    factory,
    /No modifica el sistema visual web de producci[oó]n/,
    'nuvanx-theme-factory handoff flow must require an explicit non-production declaration',
  );
  requireMatch(factory, /## Control de calidad/, 'nuvanx-theme-factory must define a quality-control checklist section');
  requireMatch(factory, /Renderizar PDF y revisar todas las p[aá]ginas como im[aá]genes/, 'nuvanx-theme-factory QA checklist must require rendering and reviewing the PDF');
}

if (failures.length) {
  console.error('Skill documentation contract failed:');
  for (const failure of failures) console.error(`- ${failure}`);
  process.exit(1);
}

console.log('PASS: design-critique, design-system and nuvanx-theme-factory skills follow the NUVANX visual contract');