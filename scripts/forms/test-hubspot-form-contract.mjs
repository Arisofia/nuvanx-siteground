#!/usr/bin/env node

import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const read = (relative) => fs.readFileSync(path.join(root, relative), 'utf8');
const requireText = (source, text, message) => {
  if (!source.includes(text)) throw new Error(message);
};
const requireMatch = (source, pattern, message) => {
  if (!pattern.test(source)) throw new Error(message);
};

const contacto = read('wp-content/mu-plugins/nuvanx-contacto-hubspot-form.php');
const valoracion = read('wp-content/mu-plugins/nuvanx-valoracion-native-hubspot-form.php');
const contactTemplate = read('wp-content/themes/nuvanx-medical/templates/template-contact.php');
const conversionPhp = read('wp-content/themes/nuvanx-medical/inc/nvx-conversion-events.php');
const conversionJs = read('wp-content/themes/nuvanx-medical/assets/js/nvx-conversion-events.js');
const stagingWorkflow = read('.github/workflows/deploy-theme-staging2.yml');
const conversionWorkflow = read('.github/workflows/conversion-events-gate.yml');
const runtimeVerifier = read('scripts/forms/verify-hubspot-runtime-browser.mjs');

const assessmentFormId = '5042522a-0bc5-4381-ac3e-5aee8649b69c';

requireText(valoracion, assessmentFormId, 'canonical valoración form ID is missing');
requireText(contacto, 'NVX_CONTACTO_HS_FORM_ID', 'contact form must be supplied by environment configuration');
requireText(contacto, `if ( '${assessmentFormId}' === $form_id )`, 'contact renderer must reject the valoración form ID');
requireMatch(contacto, /\^\[0-9a-f\]\{8\}-\[0-9a-f\]\{4\}-\[1-5\]\[0-9a-f\]\{3\}-\[89ab\]\[0-9a-f\]\{3\}-\[0-9a-f\]\{12\}\$/, 'contact form UUID validation is missing');
requireText(contacto, 'El formulario de contacto no está disponible temporalmente.', 'contact form requires an explicit safe fallback');
requireText(contacto, 'Política de privacidad', 'contact form privacy notice is missing');
requireText(valoracion, 'Política de privacidad', 'valoración form privacy notice is missing');
requireText(contactTemplate, "function_exists( 'nvx_contacto_hubspot_form_markup' )", 'contact template does not invoke the dedicated renderer');
requireText(contactTemplate, 'id="nvx-contacto-hubspot-form"', 'contact mount container is missing');

requireText(conversionPhp, "'valoracion' => defined( 'NVX_VALORACION_HS_FRAME_FORM_ID' )", 'conversion config does not expose valoración form context');
requireText(conversionPhp, "'contacto'   => defined( 'NVX_CONTACTO_HS_FORM_ID' )", 'conversion config does not expose contacto form context');
requireText(conversionJs, "emit('generate_lead'", 'successful HubSpot submissions do not emit generate_lead');
requireText(conversionJs, 'form_context: formContext(formId)', 'lead event does not classify the form context');
requireText(conversionJs, "data.eventName !== 'onFormSubmitted'", 'legacy HubSpot listener fires before persisted submission');
requireText(conversionJs, 'hs-form-event:on-submission:success', 'current HubSpot success event listener is missing');

requireText(stagingWorkflow, 'nuvanx-contacto-hubspot-form.php', 'staging deploy does not synchronize the contact MU plugin');
requireText(stagingWorkflow, 'nuvanx-valoracion-native-hubspot-form.php', 'staging deploy does not synchronize the valoración MU plugin');

for (const required of [
  "{ path: '/contacto/', context: 'contacto', mount: '#nvx-contacto-hubspot-form' }",
  "{ path: '/madrid/valoracion/', context: 'valoracion', mount: '#nvx-hubspot-native-form'",
  "const frame = mount.locator('.hs-form-frame[data-form-id][data-portal-id][data-region]')",
  'contacto and valoración must use different form IDs',
  'hs-form-event:on-submission:success',
  "probe.signals[0].nvx_event_name === 'generate_lead'",
  'primary form privacy link is missing or duplicated',
  'HubSpot did not initialize an iframe',
]) {
  requireText(runtimeVerifier, required, `rendered HubSpot verifier is missing: ${required}`);
}
requireText(conversionWorkflow, 'node --check scripts/forms/verify-hubspot-runtime-browser.mjs', 'runtime verifier is not syntax-checked');
requireText(conversionWorkflow, 'node scripts/forms/verify-hubspot-runtime-browser.mjs 2>&1 | tee -a analytics-staging.log', 'runtime verifier is not executed in staging evidence');
requireText(conversionWorkflow, 'EXPECTED_DEPLOY_SHA: ${{ steps.deploy.outputs.sha }}', 'runtime verifier does not inherit the audited deploy SHA');
requireText(conversionWorkflow, 'analytics_status=$?', 'analytics verifier status is not preserved');
requireText(conversionWorkflow, 'forms_status=$?', 'forms verifier status is not preserved');
requireText(conversionWorkflow, 'analytics_status != 0 || forms_status != 0', 'combined rendered failure is not enforced');

const forbidden = [
  /define\(\s*'NVX_CONTACTO_HS_FORM_ID'\s*,\s*'5042522a-0bc5-4381-ac3e-5aee8649b69c'/,
  /submissionValues/,
  /getFormFieldValues/,
];
for (const pattern of forbidden) {
  if (pattern.test(contacto) || pattern.test(conversionJs) || pattern.test(runtimeVerifier)) {
    throw new Error(`forbidden form contract fragment detected: ${pattern}`);
  }
}

console.log('PASS: HubSpot form source and rendered-runtime contracts');
