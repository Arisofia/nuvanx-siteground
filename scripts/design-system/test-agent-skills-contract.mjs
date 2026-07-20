#!/usr/bin/env node
// Contract test for the new .agents/skills/*/SKILL.md documents:
//   - .agents/skills/design-critique/SKILL.md
//   - .agents/skills/design-system/SKILL.md
//   - .agents/skills/nuvanx-theme-factory/SKILL.md
//
// These files are agent-facing instructions (not executable code), so this
// test validates their YAML frontmatter contract and cross-checks their
// stated rules against the rest of the design-system source of truth
// (tokens, docs, showcase fixture) so the skills cannot silently drift from
// the artifacts they describe.
import fs from 'node:fs';
import path from 'node:path';
import process from 'node:process';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const read = (relative) => fs.readFileSync(path.join(root, relative), 'utf8');
const exists = (relative) => fs.existsSync(path.join(root, relative));
const failures = [];

/**
 * Records a validation failure when the source does not match a pattern.
 * @param {string} source - The content to validate.
 * @param {RegExp} pattern - The pattern that the content must match.
 * @param {string} message - The failure message to record.
 */
function requireMatch(source, pattern, message) {
  if (!pattern.test(source)) failures.push(message);
}

const SKILLS = ['design-critique', 'design-system', 'nuvanx-theme-factory'];

/**
 * Parses top-level fields from a YAML frontmatter block.
 * @param {string} source - The source text containing the frontmatter block.
 * @param {string} label - The label used in the missing-frontmatter failure message.
 * @return {Object.<string, string>} The parsed frontmatter fields, or an empty object when the block is missing.
 */
function parseFrontmatter(source, label) {
  const match = source.match(/^---\r?\n([\s\S]*?)\r?\n---/);
  if (!match) {
    failures.push(`${label}: missing YAML frontmatter block ("---" ... "---")`);
    return {};
  }
  const block = match[1];
  const fields = {};
  for (const line of block.split(/\r?\n/)) {
    const fieldMatch = line.match(/^([a-zA-Z0-9_-]+):\s*(.*)$/);
    if (fieldMatch) fields[fieldMatch[1]] = fieldMatch[2].trim();
  }
  return fields;
}

const skillSources = {};

for (const skill of SKILLS) {
  const relative = `.agents/skills/${skill}/SKILL.md`;
  if (!exists(relative)) {
    failures.push(`missing skill file: ${relative}`);
    continue;
  }
  const source = read(relative);
  skillSources[skill] = source;

  const frontmatter = parseFrontmatter(source, relative);
  if (frontmatter.name !== skill) {
    failures.push(`${relative}: frontmatter "name" must equal directory name "${skill}" (got "${frontmatter.name}")`);
  }
  if (!frontmatter.description || frontmatter.description.length < 10) {
    failures.push(`${relative}: frontmatter "description" must be a non-trivial, non-empty string`);
  }
  requireMatch(frontmatter.description || '', /NUVANX/i, `${relative}: description must scope the skill to NUVANX`);
}

// --- design-critique/SKILL.md ------------------------------------------------

if (skillSources['design-critique']) {
  const source = skillSources['design-critique'];
  requireMatch(source, /Quiet Luxury/, 'design-critique must reference the Quiet Luxury visual contract');
  requireMatch(
    source,
    /Playfair Display[\s\S]*Manrope/,
    'design-critique must describe the canonical Playfair Display + Manrope pairing',
  );
  requireMatch(source, /nvx-tokens\.css/, 'design-critique must anchor evaluations to nvx-tokens.css');
  requireMatch(source, /48px/, 'design-critique must enforce the 48px minimum touch target guideline');
  requireMatch(source, /WCAG 2\.1 AA/, 'design-critique must state the WCAG 2.1 AA accessibility bar');
  requireMatch(source, /4\.5:1/, 'design-critique must state the 4.5:1 minimum text contrast ratio');
  requireMatch(source, /3:1/, 'design-critique must state the 3:1 minimum large-text contrast ratio');
  requireMatch(
    source,
    /ni `!important`/,
    'design-critique must explicitly ban !important, not merely avoid mentioning it',
  );
  requireMatch(source, /Focus indicators visibles obligatorios/i, 'design-critique must require mandatory visible focus');
  requireMatch(source, /Usar `aria-label` únicamente/i, 'design-critique must require conditional aria-label usage');
}

// --- design-system/SKILL.md ---------------------------------------------------

if (skillSources['design-system']) {
  const source = skillSources['design-system'];
  requireMatch(source, /Zero New Tokens/i, 'design-system must state the "Zero New Tokens" extension rule');
  requireMatch(source, /nvx-tokens\.css/, 'design-system must anchor handoff specs to nvx-tokens.css');
  requireMatch(source, /ACTIVE_STACK/, 'design-system must reference the audit-css.mjs ACTIVE_STACK mechanism');
  requireMatch(
    source,
    /scripts\/design-system\/audit-css\.mjs/,
    'design-system must reference the concrete audit-css.mjs script path',
  );
  requireMatch(source, /Sin `!important`/i, 'design-system must explicitly call out the ban on !important (not just mention the term)');
  requireMatch(source, /aplicable a componentes runtime web/i, 'design-system must validate the web runtime clause');
  requireMatch(source, /excepciones de marketing/i, 'design-system must validate the local marketing exception');
  requireMatch(source, /aria-label|aria-expanded/, 'design-system handoff specs must require ARIA attributes');
}

// --- nuvanx-theme-factory/SKILL.md --------------------------------------------

if (skillSources['nuvanx-theme-factory']) {
  const source = skillSources['nuvanx-theme-factory'];

  requireMatch(source, /Metal Pulido es la única identidad visual de producción/, 'must assert Metal Pulido is the sole production identity');
  requireMatch(source, /4\.5:1 para texto normal/i, 'must state the 4.5:1 normal text contrast ratio threshold');
  requireMatch(source, /3:1 para títulos y textos grandes/i, 'must state the 3:1 large text contrast ratio threshold');
  requireMatch(source, /precedencia absoluta/i, 'must assert the absolute precedence of the web runtime');
  requireMatch(source, /Playfair Display/, 'must name Playfair Display as the editorial typeface');
  requireMatch(source, /Manrope/, 'must name Manrope as the functional typeface');
  requireMatch(
    source,
    /No utilizar[\s\S]*?fuentes decorativas adicionales\./,
    'must enumerate the banned/decorative font list',
  );
  for (const bannedFont of ['Bodoni Moda', 'Cormorant Garamond', 'Inter', 'Source Sans', 'Pinyon Script']) {
    requireMatch(source, new RegExp(bannedFont.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')), `must explicitly ban the font "${bannedFont}"`);
  }

  // Runtime files must never be listed as editable by marketing artifacts.
  requireMatch(
    source,
    /wp-content\/themes\/nuvanx-medical\/assets\/css\/nvx-tokens\.css/,
    'must explicitly forbid editing the canonical nvx-tokens.css for commercial pieces',
  );
  requireMatch(source, /CONCEPTO - NO PRODUCCIÓN/, 'must require the concept/non-production label for prototypes');
  requireMatch(
    source,
    /No modifica el sistema visual web de producción/,
    'handoff flow must require the explicit "does not modify production" declaration',
  );
  requireMatch(source, /Una sola variante/i, 'must limit artifacts to a single accent variant');
  requireMatch(source, /8-12%/, 'must cap accent usage to 8-12% of the visual surface');

  // Pin down the two documented accent variants and their exact hex values,
  // since these are the values other artifacts (e.g. the marketing showcase)
  // are expected to reuse verbatim.
  const documentedAccents = {
    Zafiro: { primary: '#27435F', hover: '#1D334A', tint: '#E8EEF3' },
    'Oro Editorial': { primary: '#9A7A42', hover: '#755A2D', tint: '#F1E8D8' },
  };
  for (const [name, hexes] of Object.entries(documentedAccents)) {
    requireMatch(source, new RegExp(`Acento ${name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}`), `must document the "${name}" accent variant`);
    for (const hex of Object.values(hexes)) {
      requireMatch(source, new RegExp(hex), `${name} accent must document ${hex}`);
    }
  }

  // --- Regression: cross-check the documented accent hexes against the
  // marketing showcase fixture that is supposed to demonstrate them.
  const showcaseRelative = 'docs/design-system/theme-showcase.html';
  if (exists(showcaseRelative)) {
    const showcase = read(showcaseRelative);
    const sapphireMatch = showcase.match(/--showcase-sapphire:\s*(#[0-9a-f]{6});/i);
    const goldMatch = showcase.match(/--showcase-gold:\s*(#[0-9a-f]{6});/i);

    if (!sapphireMatch) {
      failures.push(`${showcaseRelative}: could not find --nvx-accent-sapphire to cross-check against the skill's documented Zafiro primary`);
    } else if (sapphireMatch[1].toUpperCase() !== documentedAccents.Zafiro.primary.toUpperCase()) {
      failures.push(
        `known contract drift: nuvanx-theme-factory/SKILL.md documents the Zafiro accent as `
        + `${documentedAccents.Zafiro.primary}, but ${showcaseRelative} uses --nvx-accent-sapphire: `
        + `${sapphireMatch[1]}. The marketing showcase and the skill spec disagree on the approved hex value.`,
      );
    }

    if (!goldMatch) {
      failures.push(`${showcaseRelative}: could not find --nvx-accent-gold to cross-check against the skill's documented Oro Editorial primary`);
    } else if (goldMatch[1].toUpperCase() !== documentedAccents['Oro Editorial'].primary.toUpperCase()) {
      failures.push(
        `known contract drift: nuvanx-theme-factory/SKILL.md documents the Oro Editorial accent as `
        + `${documentedAccents['Oro Editorial'].primary}, but ${showcaseRelative} uses --nvx-accent-gold: `
        + `${goldMatch[1]}. The marketing showcase and the skill spec disagree on the approved hex value.`,
      );
    }
  }
}

if (failures.length) {
  console.error('Agent skills contract failed:');
  for (const failure of failures) console.error(`- ${failure}`);
  process.exit(1);
}

console.log('PASS: design-critique, design-system and nuvanx-theme-factory skills contract');