#!/usr/bin/env node

import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const header = fs.readFileSync(path.join(root, 'wp-content/themes/nuvanx-medical/header.php'), 'utf8');
const workflow = fs.readFileSync(path.join(root, '.github/workflows/navigation-route-gate.yml'), 'utf8');
const requireText = (source, text, message) => { if (!source.includes(text)) throw new Error(message); };

requireText(header, 'aria-label="NUVANX MEDICINA ESTÉTICA LÁSER — Inicio"', 'header logo accessible name is incomplete');
requireText(header, '<nav class="nvx-nav" aria-label="Menú principal">', 'primary navigation lacks a native labelled nav element');
requireText(header, `'items_wrap'     => '<ul class="%2$s">%3$s</ul>'`, 'primary menu must render as a native list');
requireText(header, 'role="dialog" aria-modal="true" aria-label="Menú móvil"', 'mobile navigation dialog lacks an accessible name');
requireText(header, 'aria-controls="nvx-mobile-nav"', 'hamburger control relationship is missing');

for (const forbidden of [
  'role="menubar"',
  'role="menu"',
  'role="menuitem"',
  '<nav class="nvx-nav" role="navigation"',
]) {
  if (header.includes(forbidden)) throw new Error(`application-menu semantics must not be used for site navigation: ${forbidden}`);
}

for (const required of [
  'wp-content/themes/nuvanx-medical/header.php',
  'scripts/navigation/test-header-accessibility-contract.mjs',
  'node scripts/navigation/test-header-accessibility-contract.mjs',
]) {
  requireText(workflow, required, `navigation workflow is missing header accessibility contract: ${required}`);
}

console.log('PASS: native header navigation and accessible-name contract');
