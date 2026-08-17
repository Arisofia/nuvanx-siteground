#!/usr/bin/env node
/** Regression contract for the Endoláser approval gate. */
import assert from 'node:assert/strict';
import {
  ENDOLASER_APPROVAL_SCHEMA,
  ENDOLASER_PATHS,
  ENDOLASER_REFERENCED_TARIFF_KEYS,
  approvalMatchesEvaluatedChange,
  evaluateEndolaserChanges,
  hasCompleteEndolaserApproval,
} from './test-endolaser-claim-approval.mjs';

const clone = (value) => JSON.parse(JSON.stringify(value));
const json = (value) => `${JSON.stringify(value, null, 2)}\n`;

const routes = {
  '/endolaser-corporal-grasa-localizada/': {
    seo_id: 'endolaser',
    schema_id: 'endolaser_corporal',
    schema_type: 'MedicalProcedure',
    post_id: 0,
  },
  '/co2-fraccionado/': { seo_id: 'co2', schema_id: 'laser_co2', schema_type: 'MedicalProcedure', post_id: 0 },
};

const seo = {
  endolaser: { title: 'Endoláser corporal', description: 'Texto vigente.' },
  co2: { title: 'CO₂', description: 'Texto vigente.' },
};

const tariffs = {
  endolift: {
    abdomen: { label: 'Endolift® zona abdomen', pvp: 1694, group: 'corporal' },
    flancos: { label: 'Endolift® flancos', pvp: 1573, group: 'corporal' },
  },
  endolift_combo: {
    abdomen_flancos: { label: 'Abdomen y flancos', pvp: 2395.8, group: 'corporal' },
  },
  exion: { facial: { label: 'EXION facial', pvp: 900, group: 'facial' } },
};

const structuredData = `<?php
if ( ! defined( 'NVX_SD_ID_MEDICAL_PROCEDURE' ) ) {
  define( 'NVX_SD_ENDOLASER_CORPORAL', 'Endoláser corporal' );
}

function nvx_schema_faq_catalog() {
  $catalog = array();
  $catalog['endolift_facial'] = nvx_schema_faq_load_single_page( 'endolift-page.json' );
  $catalog['endolaser_corporal'] = nvx_schema_faq_load_single_page( 'endolaser-page.json' );

  if ( empty( $catalog['endolift_facial'] ) ) {
    $catalog['endolift_facial'] = array(
      array( 'q' => '¿Cuánto cuesta Endolift?', 'a' => 'Respuesta Endolift vigente.' ),
    );
  }

  if ( empty( $catalog['post-maternity'] ) ) {
    $catalog['post-maternity'] = array(
      array( 'q' => '¿Cuándo valorar?', 'a' => 'Respuesta postparto.' ),
    );
  }

  if ( empty( $catalog['endolaser_corporal'] ) ) {
    $catalog['endolaser_corporal'] = array(
      array( 'q' => '¿Cuántas sesiones?', 'a' => 'Respuesta Endoláser.' ),
    );
  }

  return $catalog;
}

function nvx_endolaser_procedure_name() {
  return 'Endoláser corporal';
}

function nvx_schema_treatment_node_laser( $key ) {
  if ( 'endolaser_corporal' === $key ) {
    return array( '@type' => array( 'MedicalProcedure', 'Service' ), 'name' => nvx_endolaser_procedure_name() );
  }
  if ( 'laser_co2' === $key ) {
    return array( '@type' => array( 'MedicalProcedure', 'Service' ), 'name' => 'CO₂ fraccionado' );
  }
}

function nvx_schema_offer_catalog() {
  $catalog_defs = array(
    'endolaser_corporal' => array(
      'label' => NVX_SD_ENDOLASER_CORPORAL,
      'price' => null,
    ),
    'laser_co2' => array(
      'label' => 'Láser CO₂ fraccionado',
      'price' => 330,
    ),
  );
  return $catalog_defs;
}

function nvx_schema_enrich_organization() {
  return array(
    'knowsAbout' => array(
      NVX_SD_ENDOLASER_CORPORAL,
      'Láser CO₂ fraccionado',
    ),
  );
}
`;

const baseFiles = {
  [ENDOLASER_PATHS.content]: '{"page":"endolaser"}\n',
  [ENDOLASER_PATHS.emitter]: '<?php function nvx_endolaser_editorial_body_markup() { return ""; }\n',
  [ENDOLASER_PATHS.routes]: json(routes),
  [ENDOLASER_PATHS.seo]: json(seo),
  [ENDOLASER_PATHS.tariffs]: json(tariffs),
  [ENDOLASER_PATHS.structuredData]: structuredData,
};

function decisionFor(path, nextSource) {
  return evaluateEndolaserChanges({
    changedPaths: [path],
    baseFiles,
    headFiles: { ...baseFiles, [path]: nextSource },
  });
}

function assertPass(label, decision) {
  assert.equal(decision.protected, false, `${label} must remain outside the Endoláser approval gate: ${decision.signals.join(',')}`);
  console.log(`${label}=PASS`);
}

function assertExpectedFailure(label, decision) {
  assert.equal(decision.protected, true, `${label} must be Endoláser-protected`);
  console.log(`${label}=FAIL_EXPECTED`);
}

const unrelatedTariff = clone(tariffs);
unrelatedTariff.exion.facial.pvp = 901;
assertPass('ENDOLASER_APPROVAL_UNRELATED_TARIFF', decisionFor(ENDOLASER_PATHS.tariffs, json(unrelatedTariff)));

const unrelatedRoute = clone(routes);
unrelatedRoute['/co2-fraccionado/'].post_id = 42;
assertPass('ENDOLASER_APPROVAL_UNRELATED_ROUTE', decisionFor(ENDOLASER_PATHS.routes, json(unrelatedRoute)));

const unrelatedSeo = clone(seo);
unrelatedSeo.co2.title = 'CO₂ fraccionado Madrid';
assertPass('ENDOLASER_APPROVAL_UNRELATED_SEO', decisionFor(ENDOLASER_PATHS.seo, json(unrelatedSeo)));

const unrelatedSchema = structuredData.replace("'CO₂ fraccionado'", "'CO₂ fraccionado facial'");
assertPass('ENDOLASER_APPROVAL_UNRELATED_SCHEMA', decisionFor(ENDOLASER_PATHS.structuredData, unrelatedSchema));

const unrelatedFaq = structuredData.replace('Respuesta postparto.', 'Respuesta postparto actualizada.');
assertPass('ENDOLASER_APPROVAL_UNRELATED_FAQ', decisionFor(ENDOLASER_PATHS.structuredData, unrelatedFaq));

const unrelatedEndoliftFaq = structuredData.replace('Respuesta Endolift vigente.', 'Respuesta Endolift actualizada.');
assertPass('ENDOLASER_APPROVAL_UNRELATED_ENDOLIFT_FAQ', decisionFor(ENDOLASER_PATHS.structuredData, unrelatedEndoliftFaq));

assertExpectedFailure(
  'ENDOLASER_APPROVAL_CONTENT_CHANGE_WITHOUT_APPROVAL',
  decisionFor(ENDOLASER_PATHS.content, '{"page":"endolaser","changed":true}\n'),
);

assertExpectedFailure(
  'ENDOLASER_APPROVAL_EMITTER_COMMENT_CHANGE_WITHOUT_APPROVAL',
  decisionFor(ENDOLASER_PATHS.emitter, `${baseFiles[ENDOLASER_PATHS.emitter]}// governance note\n`),
);

const changedRoute = clone(routes);
changedRoute['/endolaser-corporal-grasa-localizada/'].schema_type = 'Service';
assertExpectedFailure(
  'ENDOLASER_APPROVAL_ROUTE_CHANGE_WITHOUT_APPROVAL',
  decisionFor(ENDOLASER_PATHS.routes, json(changedRoute)),
);

const changedSchema = structuredData.replace("return 'Endoláser corporal';", "return 'Endoláser corporal actualizado';");
assertExpectedFailure(
  'ENDOLASER_APPROVAL_SCHEMA_CHANGE_WITHOUT_APPROVAL',
  decisionFor(ENDOLASER_PATHS.structuredData, changedSchema),
);

assertExpectedFailure(
  'ENDOLASER_APPROVAL_SHARED_HELPER_WITHOUT_APPROVAL',
  decisionFor(ENDOLASER_PATHS.structuredData, structuredData.replace("return 'Endoláser corporal';", "return 'Endoláser corporal vía helper';")),
);

const changedEndolaserFaq = structuredData.replace('Respuesta Endoláser.', 'Respuesta Endoláser actualizada.');
assertExpectedFailure(
  'ENDOLASER_APPROVAL_ENDOLASER_FAQ_WITHOUT_APPROVAL',
  decisionFor(ENDOLASER_PATHS.structuredData, changedEndolaserFaq),
);

const changedTariff = clone(tariffs);
changedTariff.endolift.abdomen.pvp = 1695;
assertExpectedFailure(
  'ENDOLASER_APPROVAL_TARIFF_CHANGE_WITHOUT_APPROVAL',
  decisionFor(ENDOLASER_PATHS.tariffs, json(changedTariff)),
);

const introducedEndolaserTariff = clone(tariffs);
introducedEndolaserTariff.endolaser = { abdomen: { label: 'Endoláser abdomen', pvp: 1700, group: 'corporal' } };
assertExpectedFailure(
  'ENDOLASER_APPROVAL_ENDOLASER_NAMESPACE_WITHOUT_APPROVAL',
  decisionFor(ENDOLASER_PATHS.tariffs, json(introducedEndolaserTariff)),
);

assert.equal(ENDOLASER_REFERENCED_TARIFF_KEYS.includes('endolift.abdomen'), true, 'The explicit Endoláser tariff contract must include the consumed abdomen price.');
assert.equal(ENDOLASER_REFERENCED_TARIFF_KEYS.includes('endolift_combo.abdomen_flancos'), true, 'The explicit Endoláser tariff contract must include the consumed combination price.');

const pendingApproval = {
  status: 'PENDING',
  equipment: {}, technique: {}, claims: {}, identity: {}, tariff: {}, taxonomy: {},
};
assert.equal(hasCompleteEndolaserApproval(pendingApproval).complete, false, 'PENDING approval cannot unlock a protected change.');

const approvedBlock = {
  approved_by: 'Evidence owner',
  approved_at: '2026-08-17',
  evidence_references: ['private-evidence-reference'],
};
const completeApproval = {
  schema: ENDOLASER_APPROVAL_SCHEMA,
  status: 'APPROVED',
  approved_change: { base: 'base-sha', head: 'head-sha', fingerprint: 'protected-fingerprint' },
  equipment: approvedBlock,
  technique: approvedBlock,
  claims: approvedBlock,
  identity: approvedBlock,
  tariff: approvedBlock,
  taxonomy: approvedBlock,
};
assert.equal(hasCompleteEndolaserApproval(completeApproval).complete, true, 'All six required approval domains must unlock a protected change.');
assert.equal(
  hasCompleteEndolaserApproval({ ...completeApproval, approved_change: { base: '', head: '', fingerprint: '' } }).complete,
  false,
  'An APPROVED record without a bound change cannot unlock a protected edit.',
);
assert.equal(
  approvalMatchesEvaluatedChange(completeApproval, { base: 'base-sha', head: 'head-sha', fingerprint: 'protected-fingerprint' }),
  true,
);
assert.equal(
  approvalMatchesEvaluatedChange(completeApproval, { base: 'base-sha', head: 'other-head', fingerprint: 'protected-fingerprint' }),
  false,
  'A reused approval must not bind a different head.',
);
console.log('ENDOLASER_APPROVAL_PROTECTED_CHANGE_WITH_COMPLETE_APPROVAL=PASS');
console.log('ENDOLASER_APPROVAL_SEMANTIC_CONTRACT=PASS');
