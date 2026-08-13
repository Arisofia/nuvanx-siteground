#!/usr/bin/env node
/**
 * scripts/seo/google-business-profile-audit.js
 *
 * Static NAP / local-SEO reference for the two NUVANX clinics.
 *
 * IMPORTANT: this script does NOT call the Google Business Profile API and must
 * never be used as evidence that the live GBP listings are correct. It records
 * the canonical website-side identity expected for each clinic so a separate
 * live GBP/API audit can compare Google against the same contract.
 */

'use strict';

const CLINICS = {
  chamberi: {
    name: 'Centro Clínico NUVANX Chamberí',
    reg: 'CS20144',
    address: 'Calle de Fernández de la Hoz, 4, Bajo Derecha',
    postalCode: '28010',
    locality: 'Madrid',
    phone: '+34 669 319 836',
    phoneClean: '+34669319836',
    hours: 'lunes a viernes, 12:00–20:00; sábados, 10:00–18:00',
    days: 'Martes y jueves',
    latitude: 40.431204,
    longitude: -3.693425,
    targetPage: 'https://nuvanx.com/medicina-estetica-chamberi/'
  },
  goya: {
    name: 'Centro Clínico NUVANX Salamanca–Goya',
    reg: 'CS20073',
    address: 'Calle de Fernán González, 26',
    postalCode: '28009',
    locality: 'Madrid',
    phone: '+34 647 505 107',
    phoneClean: '+34647505107',
    hours: 'lunes a viernes, 11:00–20:00',
    days: 'Miércoles',
    latitude: 40.423912,
    longitude: -3.675648,
    targetPage: 'https://nuvanx.com/clinicas-de-medicina-estetica-nuvanx/medicina-estetica-goya-barrio-salamanca/'
  }
};

const NEXT_CHECK = 'Compare GBP name, primary/secondary categories, address, phone, hours, website URL, appointment URL, services and review profile against this reference.';

function buildReference() {
  return {
    evidenceScope: 'WEBSITE_CANONICAL_REFERENCE',
    gbpLiveApiCheck: 'NOT_PERFORMED',
    gbpLiveStatus: 'UNKNOWN',
    staticNapReference: 'PRINTED',
    liveGbpAuditRequired: true,
    clinics: CLINICS,
    nextCheck: NEXT_CHECK
  };
}

function printJsonReference() {
  console.log(JSON.stringify(buildReference(), null, 2));
}

function printHumanReference() {
  console.log('=== REFERENCIA NAP / LOCAL SEO NUVANX (NO GBP LIVE) ===\n');
  console.log('EVIDENCE_SCOPE=WEBSITE_CANONICAL_REFERENCE');
  console.log('GBP_LIVE_API_CHECK=NOT_PERFORMED');
  console.log('GBP_LIVE_STATUS=UNKNOWN\n');

  for (const [key, clinic] of Object.entries(CLINICS)) {
    console.log(`📍 Sede: ${clinic.name} (${key.toUpperCase()})`);
    console.log(`   Registro sanitario: ${clinic.reg}`);
    console.log(`   Dirección canónica: ${clinic.address}, ${clinic.postalCode} ${clinic.locality}`);
    console.log(`   Teléfono canónico:  ${clinic.phone} (${clinic.phoneClean})`);
    console.log(`   Coordenadas web:   Lat ${clinic.latitude}, Lng ${clinic.longitude}`);
    console.log(`   Landing canónica:  ${clinic.targetPage}`);
    console.log(`   Horario de referencia: ${clinic.hours}`);
    console.log(`   Días de consulta médica: ${clinic.days}\n`);
  }

  console.log(`STATIC_NAP_REFERENCE=PRINTED clinics=${Object.keys(CLINICS).length}`);
  console.log('LIVE_GBP_AUDIT_REQUIRED=true');
  console.log(`NEXT_CHECK=${NEXT_CHECK}`);
}

function printStaticReference() {
  if (process.argv.includes('--json')) {
    printJsonReference();
    return;
  }

  printHumanReference();
}

printStaticReference();
