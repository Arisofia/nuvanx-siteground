#!/usr/bin/env node

import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const footer = fs.readFileSync(path.join(root, 'wp-content/themes/nuvanx-medical/footer.php'), 'utf8');
const requireText = (source, text, message) => { if (!source.includes(text)) throw new Error(message); };

requireText(footer, "function_exists( 'nvx_navigation_published_treatments' )", 'footer does not guard catalogue availability');
requireText(footer, '? nvx_navigation_published_treatments()', 'footer does not use the published treatment catalogue');
requireText(footer, 'foreach ( $nvx_footer_published_treatments as $treatment )', 'footer does not render the published catalogue');
requireText(footer, "home_url( '/exion-btl/' )", 'published EXION BTL hub link is missing');
requireText(footer, 'NUVANX MEDICINA ESTÉTICA LÁSER — Inicio', 'footer logo accessible name does not include the visible label');
requireText(footer, '<span class="nvx-logo__wordmark">NUVANX</span>', 'visible wordmark is missing');
requireText(footer, '<span class="nvx-logo__tagline">MEDICINA ESTÉTICA LÁSER</span>', 'visible logo tagline is missing');

for (const forbidden of [
  "home_url( '/exion-face/' )",
  "home_url( '/exion-body/' )",
  "home_url( '/exion-fractional/' )",
  "home_url( '/emfusion/' )",
]) {
  if (footer.includes(forbidden)) throw new Error(`footer exposes speculative route: ${forbidden}`);
}

console.log('PASS: footer published-route and accessible-name contract');
