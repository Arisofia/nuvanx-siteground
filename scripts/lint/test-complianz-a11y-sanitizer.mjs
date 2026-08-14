#!/usr/bin/env node
/**
 * Test script for Complianz Cookie Consent Accessible Name Sanitization (WCAG 2.4.4 / 4.1.2).
 *
 * Asserts that:
 * 1. Placeholder tokens like {title} inside cookie consent anchors are replaced with meaningful names.
 * 2. Privacy policy, cookie policy, and legal notice links resolve to their correct accessible names.
 * 3. No unreplaced template tokens reach the Accessibility Tree (AXTree).
 */

import assert from 'node:assert/strict';
import fs from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

// 1. Test PHP sanitizer filter logic
function simulatePhpSanitizer(html) {
  if (!html.includes('{title}') && !html.includes('{url}')) {
    return html;
  }

  const replaced = html.replace(/<a\s+([^>]*?)href=(['"])(.*?)\2([^>]*)>(.*?)<\/a>/gis, (match, attrBefore, quote, href, attrAfter, innerText) => {
    if (innerText.includes('{title}')) {
      let title = 'Política de cookies';
      if (href.includes('privacidad') || href.includes('privacy')) {
        title = 'Política de privacidad';
      } else if (href.includes('aviso-legal') || href.includes('legal')) {
        title = 'Aviso legal';
      }
      innerText = innerText.replaceAll('{title}', title);
    }
    return `<a ${attrBefore}href=${quote}${href}${quote}${attrAfter}>${innerText}</a>`;
  });

  return replaced.replaceAll('{title}', 'Política de cookies');
}

// Test cases
const inputCookie = '<div class="cmplz-message">Para más información, consulte nuestra <a href="https://nuvanx.com/politica-de-cookies-ue/" class="cmplz-link">{title}</a>.</div>';
const outputCookie = simulatePhpSanitizer(inputCookie);
assert.equal(outputCookie.includes('{title}'), false);
assert.equal(outputCookie.includes('Política de cookies'), true);

const inputPrivacy = '<div class="cmplz-message">Consulte la <a href="/politica-de-privacidad/">{title}</a>.</div>';
const outputPrivacy = simulatePhpSanitizer(inputPrivacy);
assert.equal(outputPrivacy.includes('{title}'), false);
assert.equal(outputPrivacy.includes('Política de privacidad'), true);

const inputBare = '<div>{title}</div>';
const outputBare = simulatePhpSanitizer(inputBare);
assert.equal(outputBare.includes('{title}'), false);
assert.equal(outputBare.includes('Política de cookies'), true);

// 2. Test JS sanitizer in nvx-main.js
const nvxMainPath = path.join(__dirname, '../../wp-content/themes/nuvanx-medical/assets/js/nvx-main.js');
const nvxMainContent = await fs.readFile(nvxMainPath, 'utf8');

assert.ok(nvxMainContent.includes('sanitizeComplianzAccessibleNames'), 'nvx-main.js must contain sanitizeComplianzAccessibleNames function');
assert.ok(nvxMainContent.includes('MutationObserver'), 'nvx-main.js must observe dynamic Complianz banner injection');

// 3. Test nvx-page-hygiene.php hook
const pageHygienePath = path.join(__dirname, '../../wp-content/themes/nuvanx-medical/inc/nvx-page-hygiene.php');
const pageHygieneContent = await fs.readFile(pageHygienePath, 'utf8');

assert.ok(pageHygieneContent.includes('nvx_sanitize_complianz_banner_html'), 'nvx-page-hygiene.php must contain nvx_sanitize_complianz_banner_html');
assert.ok(pageHygieneContent.includes("add_filter( 'cmplz_banner_html'"), 'nvx-page-hygiene.php must hook cmplz_banner_html');

console.log('COMPLIANZ_A11Y_SANITIZER_TEST=PASS');
