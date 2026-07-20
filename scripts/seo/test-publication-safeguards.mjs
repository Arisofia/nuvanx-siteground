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

const integrations = read('wp-content/themes/nuvanx-medical/inc/nvx-integrations.php');
const review = read('wp-content/themes/nuvanx-medical/inc/nvx-medical-review.php');
const safeguards = read('wp-content/themes/nuvanx-medical/inc/nvx-publication-safeguards.php');
const modal = read('wp-content/themes/nuvanx-medical/inc/nvx-valoracion-modal.php');
const homeCss = read('wp-content/themes/nuvanx-medical/assets/css/nvx-brand-home.css');

requireText(integrations, "require_once __DIR__ . '/nvx-medical-review.php';", 'Medical review governance is not loaded.');
requireText(integrations, "require_once __DIR__ . '/nvx-publication-safeguards.php';", 'Publication safeguards are not loaded.');
requireText(integrations, "'/politica-de-privacidad/' === $norm", 'Legacy privacy route is not detected.');
requireText(integrations, "home_url( '/politica-privacidad/' )", 'Privacy redirect does not target the canonical P0 route.');

for (const metaKey of [
  '_nvx_medical_review_status',
  '_nvx_medical_reviewer',
  '_nvx_medical_review_date',
]) {
  requireText(review, metaKey, `Medical review contract is missing ${metaKey}.`);
}
requireText(review, "'approved' !== $status", 'Medical review does not fail closed on approval status.');
requireText(review, 'nvx_medical_review_strip_legacy_bylines', 'Legacy unconditional bylines are not removed.');
requireText(review, 'data-nvx-medical-review="approved"', 'Canonical approved review marker is missing.');
requireText(review, "add_filter( 'wpseo_schema_graph'", 'reviewedBy schema governance is missing.');

requireText(safeguards, 'nvx_publication_protect_generic_send_links', 'Generic Enviar links are not protected.');
requireText(safeguards, '__NVX_PRESERVE_SEND__', 'Generic Enviar preservation token is missing.');
requireText(safeguards, 'siguiente día laborable', 'Moderated response-time copy is missing.');
requireText(safeguards, 'EMFUSION® en Madrid: hidratación y luminosidad cutánea', 'Governed EMFUSION heading is missing.');
requireText(safeguards, 'El diagnóstico y las alternativas deben explicarse antes de decidir.', 'Medical authority wording was not moderated.');

requireText(modal, "home_url( '/politica-privacidad/' )", 'Modal privacy link is not canonical.');
requireText(modal, 'siguiente día laborable', 'Modal still lacks moderated response-time wording.');
forbidText(modal, 'plazo máximo de 24 horas', 'Modal retains an absolute response-time SLA.');

requireText(homeCss, 'box-shadow: var(--nvx-shadow-medium)', 'Portrait shadow is not tokenized in the final cascade.');
requireText(homeCss, 'border-color: var(--nvx-light)', 'Portrait border is not tokenized in the final cascade.');
requireText(homeCss, 'border-top-color: var(--nvx-color-line)', 'Authority-page rule is not tokenized in the final cascade.');

console.log('PASS: publication safeguard contract');
