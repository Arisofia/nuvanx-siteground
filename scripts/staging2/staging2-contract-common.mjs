import fs from 'node:fs';

export function getStaging2Config(evidenceSubdir) {
  const envUrl = (process.env.BASE_URL || 'https://staging2.nuvanx.com').trim().replace(/\/$/, '');
  const shaToken = (process.env.EXPECTED_SHA || '').trim();
  const dirPath = (process.env.EVIDENCE_DIR || `staging2-deployment-evidence/${evidenceSubdir}`).trim();

  if (envUrl !== 'https://staging2.nuvanx.com') throw new Error(`refusing unexpected BASE_URL: ${envUrl}`);
  if (!/^[0-9a-f]{40}$/.test(shaToken)) throw new Error('EXPECTED_SHA must be a full lowercase 40-character SHA.');
  fs.mkdirSync(dirPath, { recursive: true });
  return { baseUrl: envUrl, expectedSha: shaToken, evidenceDir: dirPath };
}

export const phasePageDefinitions = [
  ['/papada-definicion-mandibular-madrid/', 'Papada y definición mandibular Madrid | NUVANX', 'Valoración médica de papada, cuello y mandíbula en Madrid para diferenciar grasa, laxitud y soporte antes de indicar Endolift® u otra opción.', 'Tratamiento médico de papada y definición mandibular en Madrid.'],
  ['/calidad-piel-firmeza-luminosidad-madrid/', 'Calidad y firmeza de la piel Madrid | NUVANX', 'Tratamiento médico para calidad, firmeza y luminosidad de la piel en Madrid con tecnología seleccionada tras diagnóstico, fototipo y valoración.', 'Tratamiento médico para firmeza, densidad y calidad cutánea.'],
  ['/cicatrices-acne-poros-textura-madrid/', 'Cicatrices de acné, poros y textura Madrid | NUVANX', 'Tratamiento de cicatrices de acné, poros y textura en Madrid con CO₂ o Fractional RF según morfología, fototipo y valoración médica.', 'Tratamiento médico de cicatrices, poros dilatados y textura cutánea.'],
  ['/manchas-rojeces-fotorejuvenecimiento-ipl-madrid/', 'Manchas, rojeces y fotodaño Madrid | NUVANX', 'Tratamiento de manchas, rojeces y fotodaño en Madrid con IPL seleccionada según diagnóstico, fototipo y valoración médica.', 'Tratamiento médico de manchas, rojeces y daño solar.'],
  ['/grasa-localizada-abdomen-flancos-madrid/', 'Grasa localizada abdomen y flancos Madrid | NUVANX', 'Valoración de grasa localizada, laxitud y pared abdominal en abdomen y flancos en Madrid dentro de NUVANX Contour Architecture™.', 'Esa grasa del abdomen que no se va ni a dieta ni a gimnasio.'],
  ['/flacidez-grasa-localizada-brazos-madrid/', 'Flacidez y grasa localizada brazos Madrid | NUVANX', 'Tratamiento de flacidez y grasa localizada en brazos en Madrid con valoración de brazo, axila y torso antes de seleccionar tecnología.', 'Para que la manga caiga bien — sin que la piel quede colgando después.'],
  ['/grasa-espalda-zona-sujetador-madrid/', 'Grasa espalda y zona del sujetador Madrid | NUVANX', 'Valoración de grasa y laxitud en espalda y zona del sujetador en Madrid, considerando continuidad con brazos y flancos.', 'El pliegue que marca la ropa, aunque tu peso esté bien.'],
  ['/flacidez-muslos-internos-subgluteo-madrid/', 'Flacidez muslos internos y subglúteo Madrid | NUVANX', 'Valoración de flacidez, grasa y continuidad en muslos internos y región subglútea en Madrid dentro de Contour Architecture™.', 'La piel más delicada del cuerpo merece el abordaje más cuidadoso.'],
  ['/tratamiento-rodillas-grasa-flacidez-madrid/', 'Grasa localizada y flacidez rodillas Madrid | NUVANX', 'Valoración de grasa localizada y flacidez en rodillas en Madrid, diferenciando tejido estético de causas articulares, vasculares o edema.', 'Una zona pequeña que cambia toda la línea de la pierna.'],
  ['/contorno-corporal-masculino-madrid/', 'Contorno corporal masculino Madrid | NUVANX', 'Contorno corporal masculino en Madrid para abdomen, cintura, espalda o perfil, con diagnóstico y tecnología seleccionada tras valoración.', 'Pensado para el cuerpo de un hombre, no adaptado del de una mujer.'],
];

/**
 * Laser / platform technology pages historically hit by SiteGround Dynamic Cache
 * fragmentation. Titles/descriptions match nvx_seo_metadata_catalog() so the
 * acceptance gate fails when stale HTML lacks the current deploy SHA or copy.
 *
 * Each entry: [path, title, description, h1, markers[]]
 */
export const technologyPageDefinitions = [
  [
    '/endolift-facial-papada-mandibula/',
    'Endolift® Facial Madrid — Papada, Mandíbula y Cuello | NUVANX',
    'Endolift® facial en Madrid con plataforma Eufoton original. Indicación médica previa. Desde 798,60 € (ojeras). NUVANX Chamberí y Goya · Barrio Salamanca.',
    'Endolift® en Madrid: papada, mandíbula y cuello sin quirófano',
    [
      'Cuándo el Endolift® es una opción razonable',
      '1470 nm: deposición térmica controlada',
      'Estructura de precios Endolift® en NUVANX Madrid',
    ],
  ],
  [
    '/endolaser-corporal-grasa-localizada/',
    'Endoláser Corporal Madrid | Firmeza que la Dieta No Logra',
    'La dieta no pega la piel al músculo. El Endoláser Corporal en NUVANX Madrid destruye la grasa localizada y retrae la flacidez severa. Criterio médico.',
    'Endoláser corporal en Madrid: grasa localizada y mejor contorno',
    [
      'Cómo actúa: grasa localizada y soporte de la piel',
      'Zonas anatómicas de alta respuesta',
      'Criterios de exclusión y alternativas',
    ],
  ],
  [
    '/laser-co2-fraccionado-madrid-textura-cicatrices-poro/',
    'Láser CO2 Fraccionado Madrid | Borra Cicatrices sin Cremas Inútiles',
    'Las cremas cosméticas no quitan las cicatrices. El Láser CO2 médico de NUVANX renueva la piel dañada de raíz. Pide valoración con el Dr. Rivera Tejeda.',
    'Láser CO₂ fraccionado en Madrid: textura, poros y cicatrices de acné',
    [
      'La ciencia de la ablación fraccionada',
      'Indicaciones terapéuticas principales',
      'Cronología real de la recuperación',
    ],
  ],
  [
    '/exion-btl/',
    'Radiofrecuencia EXION Madrid | Firmeza Facial y Corporal',
    'La aparatología estética barata no funciona. EXION BTL con IA en NUVANX Madrid ofrece regeneración de ácido hialurónico y tensión cutánea demostrada.',
    'EXION® BTL en Madrid',
    [
      'Una plataforma, distintas indicaciones médicas',
      'Fractional RF, Face y Body',
      '¿Cuándo puede tener sentido EXION®?',
    ],
  ],
];
