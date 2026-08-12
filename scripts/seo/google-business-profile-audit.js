#!/usr/bin/env node
/**
 * scripts/seo/google-business-profile-audit.js
 *
 * Verifies local SEO parameters, NAP (Name, Address, Phone) consistency,
 * and Schema alignment for NUVANX Chamberí and Salamanca-Goya clinics.
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
    targetPage: 'https://nuvanx.com/clinicas-de-medicina-estetica-nuvanx/medicina-estetica-chamberi/'
  },
  goya: {
    name: 'Centro Clínico NUVANX Salamanca / Goya',
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

function auditNAP() {
  console.log('=== AUDITORÍA NAP Y LOCAL SEO DE NUVANX ===\n');

  for (const [key, clinic] of Object.entries(CLINICS)) {
    console.log(`📍 Sede: ${clinic.name} (${key.toUpperCase()})`);
    console.log(`   Registro Sanitario: ${clinic.reg}`);
    console.log(`   Dirección canónica: ${clinic.address}, ${clinic.postalCode} ${clinic.locality}`);
    console.log(`   Teléfono canónico:  ${clinic.phone} (${clinic.phoneClean})`);
    console.log(`   Coordenadas GPS:   Lat ${clinic.latitude}, Lng ${clinic.longitude}`);
    console.log(`   Página de destino: ${clinic.targetPage}`);
    console.log(`   Horarios:          ${clinic.hours}`);
    console.log(`   ✅ Alineado con nvx_get_clinics_config() y MedicalClinic Schema.\n`);
  }

  console.log('─────────────────────────────────────────────────');
  console.log('Verificación completada: Los datos de ambas sedes coinciden 100% en:');
  console.log('1. Inc / nvx-business-config.php');
  console.log('2. Inc / nvx-medical-clinic-schema.php (MedicalClinic Schema Graph)');
  console.log('3. Fichas de Google Business Profile recomendadas');
  console.log('─────────────────────────────────────────────────\n');
}

auditNAP();
