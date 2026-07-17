#!/usr/bin/env node

import { readFile, writeFile } from 'node:fs/promises';

const target = 'wp-content/themes/nuvanx-medical/inc/nvx-content-presentation.php';

const replacements = [
  ['Recupera la armonía de tu piel', '15–30 minutos para saber si existe indicación'],
  [
    'Agenda tu valoración médica personalizada hoy mismo. Disponible de forma presencial en nuestras clínicas de <strong>Chamberí</strong> o <strong>Salamanca–Goya</strong>.',
    'Evaluamos tu caso, explicamos las opciones disponibles y documentamos el presupuesto antes de cualquier decisión. Presencial en <strong>Chamberí</strong> o <strong>Salamanca–Goya</strong>.',
  ],
  ['La base de nuestro criterio clínico', 'Por qué NUVANX'],
  [
    'Medicina estética láser con diagnóstico, tecnología certificada y resultados naturales',
    'Medicina estética donde el diagnóstico decide la tecnología',
  ],
  ['1. Diagnóstico médico de precisión', '1. Diagnóstico antes de tecnología'],
  [
    'No creemos en soluciones estandarizadas ni en la aplicación automática de tecnología. Bajo la dirección del Dr. José Javier Rivera Tejeda, cada protocolo se inicia con una valoración exhaustiva de 15 a 30 minutos. Analizamos la calidad de tu dermis, el grado de elastosis y tu historial clínico para diseñar un plan de tratamiento exclusivo y seguro, garantizando que el criterio médico prevalezca siempre sobre la aparatología.',
    'Cada protocolo comienza con una valoración médica de 15 a 30 minutos: calidad de piel, historial, objetivos y contraindicaciones. Solo se indica un tratamiento cuando existe una razón clínica para hacerlo.',
  ],
  ['2. Tecnología láser de vanguardia certificada', '2. Equipamiento médico certificado'],
  [
    'Equipamos nuestras clínicas en Madrid con plataformas médicas originales con marcado CE y autorizadas por la Comunidad de Madrid. Calibramos de forma milimétrica la energía de sistemas de referencia internacional como DEKA Motus AZ+, Láser CO₂ fraccionado y la plataforma EXION® de BTL. Esto nos permite actuar en las capas más profundas de los tejidos de forma indolora y con máxima exactitud, eliminando la flacidez y renovando la piel sin tiempos de baja prolongados.',
    'Trabajamos con plataformas médicas con marcado CE como DEKA Motus AZ+, Láser CO₂ fraccionado y EXION® BTL. La tecnología y sus parámetros se seleccionan según la anatomía y el objetivo de cada paciente.',
  ],
  ['3. Resultados naturales sin quirófano', '3. Resultados naturales y expectativa realista'],
  [
    'Nuestra prioridad es devolver la turgencia y definición al óvalo facial, la mandíbula y el cuello respetando la expresividad y la armonía natural de tu rostro. Mediante procedimientos mínimamente invasivos de última generación —como el Endolift® facial con microfibras ópticas subdérmicas y EXION® Fractional RF— estimulamos la neocolagénesis y la producción natural de ácido hialurónico, ofreciendo una alternativa real, segura y progresiva al lifting quirúrgico tradicional.',
    'El objetivo es mejorar firmeza, textura y definición respetando la expresión y la identidad del rostro. Antes de tratar, explicamos qué puede mejorar, qué límites existen y qué recuperación requiere cada protocolo.',
  ],
  ['Método', 'Cómo trabajamos'],
  ['El criterio médico antes que la tecnología', 'Un protocolo médico en tres decisiones'],
  [
    'En NUVANX, la experiencia y el criterio médico son el pilar de cada tratamiento. La aparatología se pone al servicio del diagnóstico, nunca al revés.',
    'La evaluación, la indicación y el seguimiento forman un único proceso clínico.',
  ],
  ['Diagnóstico médico integral', 'Evaluación individual'],
  [
    'Evaluamos historial clínico, calidad de piel, objetivos y contraindicaciones. Solo entonces se define si hay indicación y qué protocolo tiene sentido.',
    'Revisamos piel, anatomía, historial, objetivos y contraindicaciones antes de proponer un procedimiento.',
  ],
  ['Tecnología de precisión', 'Indicación y parámetros'],
  [
    'Seleccionamos plataforma y parámetros con exactitud milimétrica —láser, Endolift® o EXION®— según la anatomía y el resultado esperado, no por catálogo.',
    'Definimos tecnología, energía, profundidad y número de sesiones según el caso, no mediante configuraciones estándar.',
  ],
  ['Seguimiento continuo', 'Control de evolución'],
  [
    'Acompañamiento médico con calendario de control según el tratamiento y tu evolución, para consolidar resultados con seguridad.',
    'Programamos seguimiento según el tratamiento para valorar respuesta, recuperación y necesidad de ajustes.',
  ],
  ['Endolift® Facial: Retracción Subdérmica y Definición Mandibular', 'Endolift® Facial: retracción subdérmica y definición mandibular'],
  [
    'Considerado el estándar de oro actual en lifting biológico no quirúrgico. A través de la inserción intersticial de una microfibra óptica de 200 a 300 micras bajo la piel, canalizamos energía láser directa al tejido subcutáneo. Este proceso genera una lipólisis selectiva de la grasa en la papada y provoca una retracción térmica inmediata que redefine el óvalo facial y tensa el cuello sin incisiones.',
    'Procedimiento médico mínimamente invasivo con microfibra óptica de 200 a 300 micras. La energía láser intersticial actúa en tejido subcutáneo para favorecer lipólisis selectiva y retracción térmica en papada, contorno mandibular y cuello, cuando existe indicación anatómica.',
  ],
  ['Flacidez leve a moderada y pérdida de definición del contorno mandibular.', 'Flacidez leve a moderada y grasa submentoniana seleccionada.'],
  ['De 3 a 7 días de inflamación controlada.', 'Inflamación, tirantez o hematomas leves durante 3 a 7 días según el caso.'],
  ['Endoláser Corporal: Lipólisis Láser Selectiva', 'Endoláser Corporal: lipólisis láser selectiva'],
  [
    'El abordaje médico definitivo para la adiposidad localizada que se resiste a la dieta y al deporte. El calor controlado emitido por la fibra láser destruye las membranas de los adipocitos, mientras que, simultáneamente, la retracción térmica inducida previene el descolgamiento de la piel. Este doble mecanismo supera ampliamente las limitaciones estructurales de tratamientos basados en frío como la criolipólisis.',
    'El calor controlado de la fibra láser actúa sobre adiposidad localizada y produce un estímulo térmico de retracción cutánea. La indicación depende de la zona, la calidad de la piel, el volumen de grasa y la expectativa de resultado.',
  ],
  ['Zonas anatómicas de alta respuesta', 'Zonas que pueden valorarse'],
  ['Abdomen inferior, flancos laterales, cara interna del muslo y cara posterior de los brazos.', 'Abdomen, flancos, cara interna de muslos, rodillas, brazos y otras áreas seleccionadas.'],
  ['Láser CO₂ Fraccionado: Renovación Epidérmica Profunda', 'Láser CO₂ Fraccionado: renovación cutánea controlada'],
  [
    'El resurfacing cutáneo en su máxima expresión. Utilizamos tecnología de vaporización térmica controlada para el tratamiento intensivo de cicatrices atróficas de acné, poros dilatados crónicos y fotodaño severo. Este procedimiento no es un tratamiento cosmético superficial; representa una intervención dermatológica de alto impacto que exige una planificación médica rigurosa.',
    'El láser CO₂ crea microcolumnas de ablación fraccionada para tratar cicatrices atróficas de acné, poros, textura irregular y fotodaño. La profundidad y la densidad se ajustan al fototipo, la indicación y el período de recuperación aceptable.',
  ],
  ['Mejora radical de la textura epidérmica y síntesis masiva de nuevas fibras de colágeno.', 'Mejora progresiva de textura y estímulo de remodelación de colágeno.'],
  ['Variable entre 4 y 7 días según la profundidad ablativa del protocolo.', 'Habitualmente de 4 a 7 días, según la profundidad del protocolo.'],
  [
    'Especialista en Endolift®, láser CO₂ y medicina estética facial. Miembro de las principales sociedades científicas del sector. Martes y jueves: Sede Chamberí. Miércoles: Sede Goya.',
    'Especialista en Endolift®, láser CO₂ y medicina estética facial. La valoración, la indicación y el seguimiento se realizan con criterio médico. Martes y jueves: Chamberí. Miércoles: Salamanca–Goya.',
  ],
  [
    'Nuestro equipo médico, liderado por el Dr. José Javier Rivera Tejeda (Colegiado ICOMEM Nº %s), supervisa cada valoración, indicación y seguimiento en ambas sedes. Su trabajo se basa en el diagnóstico individual, la indicación médica responsable y el seguimiento personalizado de cada tratamiento.',
    'La dirección médica de NUVANX corresponde al Dr. José Javier Rivera Tejeda (Colegiado ICOMEM Nº %s). El equipo clínico realiza valoración, indicación y seguimiento en ambas sedes con un protocolo individual.',
  ],
  ['la comodidad y el downtime dependen del protocolo', 'la comodidad y el período de recuperación dependen del protocolo'],
];

let content = await readFile(target, 'utf8');
let changed = 0;
const unresolved = [];

for (const [legacy, canonical] of replacements) {
  if (content.includes(legacy)) {
    content = content.split(legacy).join(canonical);
    changed += 1;
  } else if (!content.includes(canonical)) {
    unresolved.push(legacy.slice(0, 90));
  }
}

if (unresolved.length) {
  console.error('Could not locate legacy or canonical forms for:');
  unresolved.forEach((item) => console.error(`- ${item}`));
  process.exit(1);
}

await writeFile(target, content);
console.log(`Normalized ${changed} legacy clinical copy fragment(s).`);
