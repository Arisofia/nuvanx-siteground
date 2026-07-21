#!/usr/bin/env node
import fs from 'node:fs';
import path from 'node:path';
import process from 'node:process';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const read = (relative) => fs.readFileSync(path.join(root, relative), 'utf8');
const failures = [];

/**
 * Records a failure message when the source does not contain the required text.
 * @param {string} source - The content to inspect.
 * @param {string} text - The text that must be present.
 * @param {string} message - The failure message to record.
 */
function requireText(source, text, message) {
  if (!source.includes(text)) failures.push(message);
}

/**
 * Records a failure when the source contains prohibited text.
 * @param {string} source - The content to inspect.
 * @param {string} text - The text that must not appear.
 * @param {string} message - The failure message to record.
 */
function forbidText(source, text, message) {
  if (source.includes(text)) failures.push(message);
}

/**
 * Records a failure when a regular expression does not match the source text.
 * @param {string} source - The text to inspect.
 * @param {RegExp} pattern - The pattern to match against the source text.
 * @param {string} message - The failure message to record.
 */
function requirePattern(source, pattern, message) {
  if (!pattern.test(source)) failures.push(message);
}

/**
 * Records a failure when a forbidden regular expression matches the source text.
 * @param {string} source - The text to inspect.
 * @param {RegExp} pattern - The forbidden pattern.
 * @param {string} message - The failure message to record.
 */
function forbidPattern(source, pattern, message) {
  if (pattern.test(source)) failures.push(message);
}

const navigation = read('wp-content/themes/nuvanx-medical/inc/nvx-navigation-filters.php');
const javascript = read('wp-content/themes/nuvanx-medical/assets/js/nvx-main.js');
const css = read('wp-content/themes/nuvanx-medical/assets/css/nvx-header.css');
const tokens = read('wp-content/themes/nuvanx-medical/assets/css/nvx-tokens.css');
const header = read('wp-content/themes/nuvanx-medical/header.php');
const documentation = read('docs/navigation/PRIMARY-MENU-ARCHITECTURE.md');

forbidText(
  navigation,
  'nvx_add_exion_to_tratamientos_menu',
  'legacy dynamic treatment injection must be removed',
);
requireText(
  navigation,
  'function nvx_navigation_primary_blueprint(): array',
  'published-route-aware definitive fallback blueprint is missing',
);
requireText(
  navigation,
  "$args['fallback_cb'] = 'nvx_navigation_primary_fallback';",
  'desktop and mobile primary renders must share the safe fallback',
);
requireText(
  navigation,
  "$args['depth']       = 3;",
  'primary navigation must support exactly three levels',
);
requireText(
  navigation,
  'function nvx_navigation_prune_unpublished_items',
  'unpublished page and descendant pruning is missing',
);
requireText(
  navigation,
  "array( 'soluciones', 'protocolos-signature', 'tecnologia' )",
  'automatic mega-menu role detection is missing',
);
requireText(
  navigation,
  "add_filter( 'nav_menu_css_class', 'nvx_navigation_item_classes', 20, 4 );",
  'menu item class filter is missing',
);
requireText(
  navigation,
  "add_filter( 'nav_menu_link_attributes', 'nvx_navigation_link_attributes', 20, 4 );",
  'menu link attribute filter is missing',
);

requireText(javascript, 'function initMobileAccordions()', 'mobile accordion initializer is missing');
requireText(javascript, "button.setAttribute('aria-expanded', 'false');", 'accordion button aria-expanded contract is missing');
requireText(javascript, "button.setAttribute('aria-controls', submenuId);", 'accordion aria-controls contract is missing');
requireText(javascript, "submenu.setAttribute('aria-hidden', 'true');", 'submenu aria-hidden contract is missing');
requireText(javascript, "event.key === 'Escape'", 'Escape must close the mobile drawer');
requireText(javascript, "window.matchMedia('(prefers-reduced-motion: reduce)')", 'reduced-motion support is missing');
requireText(javascript, 'candidate.item.parentElement === entry.item.parentElement', 'sibling accordion collapse is missing');
requireText(javascript, 'setMobileNavOpen(false, false);', 'desktop breakpoint/modal must close the mobile drawer safely');

requireText(css, '.nvx-nav__list > .nvx-nav__item--mega > .sub-menu', 'desktop mega-menu selector is missing');
requireText(css, 'grid-template-columns: repeat(3, minmax(0, 1fr));', 'desktop mega-menu must expose three editorial columns');
requireText(css, '.nvx-mobile-nav__toggle[aria-expanded="true"]', 'mobile plus/minus state styling is missing');
requireText(css, '.nvx-mobile-nav__list .sub-menu[hidden]', 'collapsed mobile submenu styling is missing');
requireText(css, '@media (max-width: 80em)', 'protective navigation breakpoint is missing');
requireText(css, '@media (prefers-reduced-motion: reduce)', 'CSS reduced-motion contract is missing');
forbidText(css, '!important', 'navigation CSS must not use !important');

const definedTokens = new Set(
  [...tokens.matchAll(/(--nvx-[a-z0-9-]+)\s*:/gi)].map((match) => match[1]),
);
const usedTokens = new Set(
  [...css.matchAll(/var\(\s*(--nvx-[a-z0-9-]+)/gi)].map((match) => match[1]),
);
for (const token of usedTokens) {
  if (!definedTokens.has(token)) failures.push(`navigation CSS uses undefined token ${token}`);
}
requirePattern(
  tokens,
  /--nvx-border-focus\s*:\s*[^;]+;/,
  'canonical focus-border token must remain defined',
);
requirePattern(
  css,
  /outline\s*:\s*var\(\s*--nvx-border-focus\s*\)\s+solid\s+var\(\s*--nvx-accent-muted\s*\)\s*;/,
  'navigation focus-visible controls must consume the canonical focus-border token',
);

const menuCalls = [...header.matchAll(/wp_nav_menu\(\s*array\(([^]*?)\)\s*\);/g)].map((match) => match[1]);
const desktopMenuCall = menuCalls.find((call) => /['"]menu_class['"]\s*=>\s*['"]nvx-nav__list['"]/.test(call)) || '';
const mobileMenuCall = menuCalls.find((call) => /['"]menu_class['"]\s*=>\s*['"]nvx-mobile-nav__list['"]/.test(call)) || '';

requirePattern(
  desktopMenuCall,
  /['"]theme_location['"]\s*=>\s*['"]primary['"]/,
  'desktop header must continue to render the WordPress primary menu',
);
requirePattern(
  mobileMenuCall,
  /['"]theme_location['"]\s*=>\s*['"]primary['"]/,
  'mobile drawer must continue to render the same WordPress primary menu',
);
forbidPattern(
  header,
  /['"]fallback_cb['"]\s*=>/,
  'header must not override the centralized primary-menu fallback',
);
forbidText(
  header,
  'nvx_primary_menu_fallback',
  'header must not reference the removed legacy fallback',
);
requireText(
  header,
  'nvx_cta_whatsapp_url()',
  'mobile WhatsApp CTA must use the centralized helper',
);
forbidText(
  header,
  'https://wa.me/34669319836',
  'mobile WhatsApp CTA must not hardcode the phone URL',
);

for (const label of [
  'SOLUCIONES',
  'PROTOCOLOS SIGNATURE',
  'TECNOLOGÍA',
  'CASOS CLÍNICOS',
  'EQUIPO MÉDICO',
  'CLÍNICAS',
  'JOURNAL',
]) {
  requireText(documentation, label, `documentation is missing the ${label} pillar`);
}
requireText(
  documentation,
  'No debe crearse un enlace personalizado hacia una ruta futura.',
  'documentation must forbid premature custom links to future routes',
);

if (failures.length) {
  console.error('Navigation architecture contract failed:');
  for (const failure of failures) console.error(`- ${failure}`);
  process.exit(1);
}

console.log('PASS: definitive WordPress-managed navigation, mega-menu and mobile accordion contract');
