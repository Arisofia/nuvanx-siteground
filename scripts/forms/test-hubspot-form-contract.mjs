#!/usr/bin/env node

import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const read = (relative) => fs.readFileSync(path.join(root, relative), 'utf8');
const requireText = (source, text, message) => {
  if (!source.includes(text)) throw new Error(message);
};
const forbidText = (source, text, message) => {
  if (source.includes(text)) throw new Error(message);
};

const valoracion = read('wp-content/mu-plugins/nuvanx-valoracion-native-hubspot-form.php');
const contactTemplate = read('wp-content/themes/nuvanx-medical/templates/template-contact.php');
const modal = read('wp-content/themes/nuvanx-medical/inc/nvx-valoracion-modal.php');
const conversionPhp = read('wp-content/themes/nuvanx-medical/inc/nvx-conversion-events.php');
const conversionJs = read('wp-content/themes/nuvanx-medical/assets/js/nvx-conversion-events.js');
const stagingWorkflow = read('.github/workflows/deploy-theme-staging2.yml');
const conversionWorkflow = read('.github/workflows/conversion-events-gate.yml');
const runtimeVerifier = read('scripts/forms/verify-hubspot-runtime-browser.mjs');

const assessmentFormId = '5042522a-0bc5-4381-ac3e-5aee8649b69c';

requireText(valoracion, assessmentFormId, 'canonical valoración form ID is missing');
requireText(valoracion, 'nvx_valoracion_native_hubspot_enforce_single_mount', 'valoración final-document normalizer is missing');
requireText(valoracion, 'nvx_valoracion_balanced_div_range', 'balanced mount parser is missing');
requireText(valoracion, "nvx_valoracion_remove_divs_by_class( $html, 'hs-form-frame' )", 'competing modern frames are not removed');
requireText(valoracion, "nvx_valoracion_remove_divs_by_class( $html, 'hbspt-form' )", 'legacy HubSpot forms are not removed');
requireText(valoracion, 'nvx-hubspot-privacy', 'canonical valoración privacy note is missing');

forbidText(contactTemplate, 'nvx_contacto_hubspot_form_markup', 'contact template still invokes HubSpot');
forbidText(contactTemplate, 'nvx-contacto-hubspot-form', 'contact template still exposes the legacy mount');
forbidText(contactTemplate, 'hs-form-frame', 'contact template still contains a HubSpot frame');
forbidText(contactTemplate, 'nvx-open-valoracion-modal', 'contact template still opts into the modal');
requireText(contactTemplate, '/madrid/valoracion/', 'contact template lacks a direct valoración route');
requireText(contactTemplate, 'nvx-clinic-card__map', 'contact maps are missing');

requireText(modal, "is_page( 14 )", 'valoración modal is not disabled by contacto page ID');
requireText(modal, "is_page( 'contacto' )", 'valoración modal is not disabled by contacto slug');

requireText(conversionPhp, "'valoracion' => defined( 'NVX_VALORACION_HS_FRAME_FORM_ID' )", 'conversion config does not expose valoración context');
forbidText(conversionPhp, "'contacto'", 'obsolete contacto form context remains in PHP config');
forbidText(conversionJs, 'forms.contacto', 'obsolete contacto form mapping remains in JavaScript');
forbidText(conversionJs, "pagePath().indexOf('/contacto/')", 'contact path still classifies successful form submissions');

requireText(stagingWorkflow, 'nuvanx-valoracion-native-hubspot-form.php', 'staging deploy does not synchronize the valoración MU plugin');

for (const required of [
  "open(page, '/contacto/')",
  "open(page, '/madrid/valoracion/')",
  'HubSpot form container must be absent',
  'valoración modal must not be rendered',
  "const mount = page.locator('#nvx-hubspot-native-form')",
  'expected one canonical embed script',
  'HubSpot did not initialize one iframe',
  "probe.signals[0].nvx_event_name === 'generate_lead'",
]) {
  requireText(runtimeVerifier, required, `rendered HubSpot verifier is missing: ${required}`);
}

for (const requiredPath of [
  "'wp-content/themes/nuvanx-medical/templates/template-contact.php'",
  "'wp-content/themes/nuvanx-medical/inc/nvx-valoracion-modal.php'",
  "'wp-content/mu-plugins/nuvanx-valoracion-native-hubspot-form.php'",
]) {
  requireText(conversionWorkflow, requiredPath, `conversion gate does not trigger for ${requiredPath}`);
}

requireText(conversionWorkflow, 'node --check scripts/forms/verify-hubspot-runtime-browser.mjs', 'runtime verifier is not syntax-checked');
requireText(conversionWorkflow, 'php scripts/forms/test-valoracion-single-mount.php', 'single-mount PHP fixture is not executed');
requireText(conversionWorkflow, 'node scripts/forms/verify-hubspot-runtime-browser.mjs 2>&1 | tee -a analytics-staging.log', 'runtime verifier is not executed');
requireText(conversionWorkflow, 'EXPECTED_DEPLOY_SHA: ${{ steps.deploy.outputs.sha }}', 'runtime verifier does not inherit the deploy SHA');

for (const pattern of [/submissionValues/, /getFormFieldValues/]) {
  if (pattern.test(conversionJs) || pattern.test(runtimeVerifier)) {
    throw new Error(`forbidden form contract fragment detected: ${pattern}`);
  }
}

console.log('PASS: form-free contacto and single-form valoración contracts');
