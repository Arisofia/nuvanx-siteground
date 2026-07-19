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

const forbidden = [
  /define\(\s*'NVX_CONTACTO_HS_FORM_ID'\s*,\s*'5042522a-0bc5-4381-ac3e-5aee8649b69c'/,
  /submissionValues/,
  /getFormFieldValues/,
];
for (const pattern of forbidden) {
  if (pattern.test(contacto) || pattern.test(conversionJs)) {
    throw new Error(`forbidden form contract fragment detected: ${pattern}`);
  }
}

console.log('PASS: HubSpot form source contracts');
