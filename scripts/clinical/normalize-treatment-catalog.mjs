#!/usr/bin/env node

// One-shot, exact-source normalizer. Removed after the canonical PHP diff lands.
import { readFile, writeFile } from 'node:fs/promises';

const target = 'wp-content/themes/nuvanx-medical/inc/nvx-treatments-catalog.php';
const replacements = [
  [
    'Visión de la plataforma y criterio de elección entre aplicadores. La indicación definitiva se confirma en valoración.',
    'Plataforma médica con aplicadores Fractional RF, Face y Body. Cada modalidad tiene mecanismo, profundidad, recuperación y objetivos distintos; la indicación se define por diagnóstico.',
  ],
  [
    'Regeneración endógena facial con RF monopolar + ultrasonido a microtemperaturas controladas. Alternativa a HIFU de alto pico cuando el diagnóstico lo indica.',
    'Aplicador no invasivo de radiofrecuencia y ultrasonido para protocolos de calidad cutánea. Los parámetros y el número de sesiones se definen según diagnóstico y tolerancia.',
  ],
  [
    'Adiposidad localizada y retracción cutánea con refrigeración activa. Para flancos, abdomen y zonas de grasa + flacidez leve–moderada.',
    'Aplicador corporal no invasivo para protocolos de firmeza, textura y contorno. No sustituye procedimientos de reducción de grasa ni trata obesidad.',
  ],
  [
    'Textura, poro y cicatrices superficiales con RF fraccionada y control de tejido. Plan de sesiones y downtime realistas.',
    'Radiofrecuencia fraccionada con microagujas para textura, poro y cicatrices seleccionadas. Profundidad, anestesia, cuidados y período de recuperación dependen del protocolo.',
  ],
  [
    'Infusión cutánea y soporte de barrera con microcanales acústicos DYNAMiQ™. Útil en piel sensible y fases post-procedimiento.',
    'Aplicador orientado al soporte de barrera y a la infusión cutánea según protocolo. No sustituye procedimientos médicos de energía ni tratamientos inyectables.',
  ],
  [
    'Fotorejuvenecimiento y mejora de manchas, rojeces y calidad cutánea con parámetros ajustados al fototipo y la indicación.',
    'Luz pulsada intensa para indicaciones pigmentarias, vasculares y calidad cutánea seleccionadas tras diagnóstico, fototipo y ajuste de parámetros.',
  ],
];

let content = await readFile(target, 'utf8');
let changed = 0;
const unresolved = [];

for (const [legacy, canonical] of replacements) {
  if (content.includes(legacy)) {
    content = content.split(legacy).join(canonical);
    changed += 1;
  } else if (!content.includes(canonical)) {
    unresolved.push(legacy);
  }
}

if (unresolved.length) {
  console.error('Catalog normalizer could not locate expected legacy or canonical copy:');
  unresolved.forEach((text) => console.error(`- ${text}`));
  process.exit(1);
}

for (const prohibited of ['Alternativa a HIFU', 'downtime', 'microtemperaturas controladas']) {
  if (content.includes(prohibited)) {
    console.error(`Prohibited catalog wording remains: ${prohibited}`);
    process.exit(1);
  }
}

await writeFile(target, content);
console.log(`Normalized ${changed} treatment catalog item(s).`);
