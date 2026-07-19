#!/usr/bin/env node

import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const jsPath = path.join(root, 'wp-content/themes/nuvanx-medical/assets/js/nvx-conversion-events.js');
const phpPath = path.join(root, 'wp-content/themes/nuvanx-medical/inc/nvx-conversion-events.php');
const integrationsPath = path.join(root, 'wp-content/themes/nuvanx-medical/inc/nvx-integrations.php');

const js = fs.readFileSync(jsPath, 'utf8');
const php = fs.readFileSync(phpPath, 'utf8');
const integrations = fs.readFileSync(integrationsPath, 'utf8');

function requireText(source, text, message) {
  if (!source.includes(text)) throw new Error(message);
}

for (const eventName of ['reserve_click', 'whatsapp_click', 'phone_click', 'generate_lead']) {
  requireText(js, `'${eventName}'`, `missing event ${eventName}`);
  if (eventName.length > 40) throw new Error(`GA4 event name exceeds 40 characters: ${eventName}`);
}

requireText(js, "hs-form-event:on-submission:success", 'HubSpot updated-form success listener is missing');
requireText(js, "data.type !== 'hsFormCallback'", 'HubSpot legacy callback listener is missing');
requireText(js, "data.eventName !== 'onFormSubmitted'", 'legacy listener does not wait for persisted submission');
requireText(js, 'submissionWindowMs', 'submission deduplication window is missing');
requireText(js, 'recentSubmissions', 'submission deduplication state is missing');
requireText(js, "event: signalName", 'diagnostic dataLayer signal is missing');
requireText(js, "window.gtag('event', normalizedName, params)", 'direct GA4 event dispatch is missing');
requireText(js, "document.addEventListener('click', trackClick, true)", 'delegated click listener is missing');
requireText(js, 'isAllowedHubSpotOrigin', 'HubSpot message origin validation is missing');

const forbiddenDataAccess = [
  'submissionValues',
  'getFieldValue',
  'getFormFieldValues',
  "['email']",
  '.email',
  "['phone']",
  '.phone',
  'firstname',
  'lastname',
];
for (const fragment of forbiddenDataAccess) {
  if (js.includes(fragment)) throw new Error(`conversion layer must not read submitted personal data: ${fragment}`);
}

requireText(php, "'nvx-conversion-events'", 'WordPress script handle is missing');
requireText(php, 'wp_add_inline_script', 'form context configuration is not registered before the script');
requireText(php, "false\n\t);", 'conversion listener must load in the document head before deferred HubSpot embeds');
requireText(integrations, "require_once __DIR__ . '/nvx-conversion-events.php';", 'conversion module is not loaded by the theme');

console.log('NUVANX conversion event contracts passed.');
