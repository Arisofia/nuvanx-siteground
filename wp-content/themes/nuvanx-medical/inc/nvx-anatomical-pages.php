<?php
/**
 * Anatomical zone pages (Phase 2).
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once __DIR__ . '/nvx-13-point-renderer.php';

if ( ! defined( 'NVX_KICKER_ROSTRO' ) ) {
    define( 'NVX_KICKER_ROSTRO', 'Soluciones Médicas: Rostro' );
}
if ( ! defined( 'NVX_KICKER_CUERPO' ) ) {
    define( 'NVX_KICKER_CUERPO', 'Soluciones Médicas: Cuerpo' );
}

/**
 * Helper to construct a structured anatomical page entry.
 *
 * @param string $slug Route slug.
 * @param string $seo_title Document title.
 * @param string $description Meta description.
 * @param string $kicker Eyebrow kicker.
 * @param string $h1 Main heading.
 * @param string $lead Lead text.
 * @param string $diagnosis Clinical diagnosis text.
 * @param string $mechanism Therapeutic mechanism text.
 * @param string[] $indications List of indications.
 * @param string[] $precautions List of precautions.
 * @param string[] $process List of process steps.
 * @param array<int, array{q:string,a:string}> $faqs FAQ list.
 * @return array<string, mixed>
 */
function nvx_anatomical_entry(
    string $slug,
    string $seo_title,
    string $description,
    string $kicker,
    string $h1,
    string $lead,
    string $diagnosis,
    string $mechanism,
    array $indications,
    array $precautions,
    array $process,
    array $faqs
): array {
    return array(
        'slug'          => $slug,
        'seo_title'     => $seo_title,
        'description'   => $description,
        'kicker'        => $kicker,
        'h1'            => $h1,
        'lead'          => $lead,
        'diagnosis'     => $diagnosis,
        'mechanism'     => $mechanism,
        'indications'   => $indications,
        'precautions'   => $precautions,
        'process'       => $process,
        'faqs'          => $faqs,
        'review_status' => 'approved_for_publication',
    );
}

/**
 * Catalogue for Facial Anatomical Zone Pages.
 *
 * @return array<string, array<string, mixed>>
 */
function nvx_anatomical_facial_catalog_upper_mid(): array {
    return array(
        'tercio-superior' => nvx_anatomical_entry(
            'soluciones-medicas/rostro/tercio-superior',
            'Tratamientos Tercio Superior: Frente y Entrecejo | NUVANX',
            'Valoración médica para líneas de expresión en frente y entrecejo. Relajamos la musculatura sin congelar tu mirada.',
            NVX_KICKER_ROSTRO,
            'Las arrugas frontales son consecuencia directa de la dinámica gesticular repetida.',
            'La cosmética tópica tiene un alcance limitado frente a la contracción muscular. Evaluamos la dinámica de gesticulación para atenuar la tensión muscular conservando la expresión natural del rostro.',
            'Congelar la frente por sistema hace que las cejas caigan o que la expresión se vea plástica. Médicamente, diferenciamos entre arrugas dinámicas (causadas por contracción muscular) y estáticas (cuando la piel ya se ha fracturado) para decidir el enfoque.',
            'Si el problema es dinámico, utilizamos neuromoduladores en dosis ultra precisas para "educar" al músculo. Si ya existe una fractura en la piel, combinamos terapias de redensificación para rellenar la huella sin aportar volumen artificial.',
            array(
                'Líneas horizontales en la frente por hiperactividad muscular.',
                'Surcos en el entrecejo (líneas del ceño fruncido) que dan aspecto de enfado.',
                'Arrugas finas y pérdida de soporte en la zona lateral de los ojos (patas de gallo).',
            ),
            array(
                'Infección o inflamación activa en la zona de inyección.',
                'Antecedentes de patologías neuromusculares exigen valoración exhaustiva.',
                'Expectativa de "congelación total" de la mirada (no realizamos tratamientos que borren la expresividad).',
            ),
            array(
                'Estudio de la dinámica muscular (te pediremos que gesticules, te enfades y te sorprendas).',
                'Diseño del mapa de inyección adaptado a tu asimetría natural.',
                'Intervención ambulatoria y revisión médica a los 15 días para evaluar la relajación y hacer reajustes si es necesario.',
            ),
            array(
                array(
                    'q' => '¿Perderé la expresividad en la mirada?',
                    'a' => 'No. Nuestra filosofía es la "intervención mínima". Tratamos el músculo justo para que dejes de fracturar la piel, pero manteniendo la capacidad de expresar emociones.',
                ),
                array(
                    'q' => '¿Cuánto tiempo duran los resultados?',
                    'a' => 'La relajación muscular suele durar entre 3 y 5 meses, dependiendo de la fuerza de tu músculo y tu metabolismo.',
                ),
            )
        ),
        'mirada' => nvx_anatomical_entry(
            'soluciones-medicas/rostro/mirada',
            'Tratamientos para la Mirada: Ojeras y Párpados | NUVANX',
            'Diagnóstico de la región periocular. Diferenciamos hundimiento, bolsas y pigmentación para proponer el tratamiento médico adecuado.',
            NVX_KICKER_ROSTRO,
            'El aspecto de cansancio crónico puede no resolverse con descanso si el origen es anatómico.',
            'Generalmente está determinado por la incidencia de la luz sobre un déficit de soporte óseo o una menor densidad cutánea. El problema suele ser cómo la luz incide sobre tus ojos debido a un déficit de soporte óseo o calidad de piel.',
            'Rechazamos los rellenos indiscriminados. Diferenciamos estrictamente entre un surco lagrimal hundido (falta de hueso/grasa), componente vascular, pigmentación y bolsas grasas reales. Tratar una bolsa inyectando ácido hialurónico es un error médico que empeora el aspecto.',
            'Si falta soporte, usamos inyectables estructurales profundos. Si el problema es de calidad cutánea, empleamos tecnología de redensificación. Si hay bolsas severas, te derivaremos a cirugía.',
            array(
                'Hundimiento del surco lagrimal que genera sombras (falsas ojeras).',
                'Piel fina y apergaminada en el párpado inferior.',
                'Pigmentación o exceso vascular (ojeras marrones o moradas).',
            ),
            array(
                'Presencia de bolsas grasas reales severas (requieren valoración para blefaroplastia).',
                'Retención de líquidos o edema malar (el ácido hialurónico agravará el problema).',
                'Expectativas poco realistas sobre la pigmentación congénita oscura.',
            ),
            array(
                'Análisis óseo y estructural de la transición párpado-mejilla.',
                'Evaluación de la laxitud cutánea y la presencia de edema.',
                'Diseño del plan (soporte inyectable, tecnología o derivación quirúrgica).',
            ),
            array(
                array(
                    'q' => '¿Se puede quitar una ojera oscura con ácido hialurónico?',
                    'a' => 'No. El ácido hialurónico da volumen y corrige hundimientos. Si el problema es puro color (pigmentación), se requieren despigmentantes o tecnología.',
                ),
            )
        ),
        'tercio-medio' => nvx_anatomical_entry(
            'soluciones-medicas/rostro/tercio-medio',
            'Tratamientos Tercio Medio: Pómulos y Surcos | NUVANX',
            'Soporte facial sin exceso de volumen. Tratamos la flacidez del tercio medio devolviendo la estructura natural al rostro.',
            NVX_KICKER_ROSTRO,
            'Tratar el surco nasogeniano sin restituir el soporte de los pómulos es un abordaje incompleto que no aborda la causa estructural.',
            'Ante la pérdida de firmeza o surcos marcados, el instinto inicial puede ser rellenar la arruga. Sin embargo, este pliegue suele ser consecuencia de la pérdida de soporte en el pómulo y la mejilla, provocando el descenso del tejido.',
            'Diferenciamos la pérdida de volumen profundo (grasa o hueso que desaparece con la edad) de la laxitud cutánea superficial. Añadir volumen cuando el problema es laxitud solo consigue una "cara de globo" antinatural.',
            'En lugar de rellenar la arruga, aplicamos inyectables estructurales en vectores ascendentes para dar soporte a los ligamentos del pómulo, o utilizamos bioestimuladores si lo que necesitas es generar colágeno nuevo.',
            array(
                'Pérdida de proyección y soporte en la zona de los pómulos.',
                'Aplanamiento de las mejillas y descolgamiento del tercio medio.',
                'Surcos nasogenianos marcados por gravedad (caída del tejido).',
            ),
            array(
                'Acúmulo de grasa malar (exceso de peso en la mejilla).',
                'Laxitud extrema que requiere lifting quirúrgico.',
                'Solicitudes de volumen excessive que desvirtúen la anatomía original.',
            ),
            array(
                'Palpación de los ligamentos de retención facial.',
                'Planificación de los vectores de tracción o reposicionamiento.',
                'Infiltración profunda supra-perióstica para máximo soporte sin dar aspecto de cara ancha.',
            ),
            array(
                array(
                    'q' => '¿Me cambiará la forma de la cara?',
                    'a' => 'Al revés. Buscamos devolverte la estructura que tenías hace años, no crearte unos pómulos que no son tuyos.',
                ),
            )
        ),
    );
}

function nvx_anatomical_facial_catalog_lower(): array {
    return array(
        'labios' => nvx_anatomical_entry(
            'soluciones-medicas/rostro/labios',
            'Tratamientos de Labios y Zona Perioral | NUVANX',
            'Hidratación, perfilado y recuperación del labio. Armonizamos la zona perioral sin volúmenes artificiales.',
            NVX_KICKER_ROSTRO,
            'Un resultado estético óptimo en el labio prioriza su integración armónica con las proporciones del resto del rostro.',
            'Rechazamos los volúmenes desproporcionados. El objetivo clínico es hidratar, corregir asimetrías o restituir la estructura labial afectada por el envejecimiento.',
            'Evaluamos si necesitas volumen, si solo buscas hidratación profunda sin cambiar la forma, o si el problema principal está en la zona de alrededor (código de barras, sonrisa gingival o comisuras caídas).',
            'Empleamos ácidos hialurónicos dinámicos, diseñados específicamente para integrarse en el tejido muscular del labio y moverse contigo cuando hablas o sonríes, evitando bultos y rigidez.',
            array(
                'Pérdida de hidratación y afinamiento del tejido labial.',
                'Asimetrías o falta de definición en el arco de Cupido y contornos.',
                'Arrugas periorales (código de barras) y comisuras caídas.',
            ),
            array(
                'Infecciones activas o herpes labial agudo (debe tratarse previamente).',
                'Anatomía que no admite más producto (si hay migración de rellenos anteriores, primero disolvemos).',
                'Peticiones de volúmenes desproporcionados respecto a la base ósea y dental.',
            ),
            array(
                'Análisis dinámico: te observamos hablando y sonriendo.',
                'Diseño del tratamiento: elección de la densidad del ácido hialurónico.',
                'Inyección conservadora y masaje de integración.',
            ),
            array(
                array(
                    'q' => '¿Qué pasa si ya llevo relleno de otro sitio y no me gusta?',
                    'a' => 'Si el producto anterior está mal posicionado o migrado, lo honesto es disolverlo con hialuronidasa, dejar que el tejido se recupere, y empezar de cero.',
                ),
            )
        ),
        'tercio-inferior' => nvx_anatomical_entry(
            'soluciones-medicas/rostro/tercio-inferior',
            'Tratamientos Tercio Inferior: Mandíbula y Cuello | NUVANX',
            'Definición del óvalo facial y tratamiento de papada y cuello sin cirugía. Diagnóstico estructural avanzado.',
            NVX_KICKER_ROSTRO,
            'En ocasiones, el volumen submentoniano no obedece a un exceso graso, sino a un mentón hipoplásico o retraído que no proporciona el soporte adecuado a los tejidos blandos.',
            'El tercio inferior delata el paso del tiempo por la pérdida de definición en la mandíbula y la flacidez del cuello. Antes de intentar quemar grasa o estirar la piel, medimos tus proporciones óseas.',
            'Separamos claramente la grasa submentoniana (papada), la flacidez cutánea, la hipertrofia del músculo masetero y el déficit óseo (micrognatia). Un mal diagnóstico aquí deriva en caras cuadradas o tratamientos inútiles.',
            'Proporcionamos soporte con inyectables de alta densidad en mentón y ángulo mandibular, relajamos músculos depresores con neuromoduladores, o eliminamos grasa y tensamos piel mediante tecnología láser (Endoláser o radiofrecuencia).',
            array(
                'Pérdida de definición en la línea mandibular ("jowls" o "caritas de bulldog").',
                'Papada por acúmulo graso o laxitud cutánea.',
                'Mentón retraído que desequilibra el perfil.',
            ),
            array(
                'Flacidez severa ("cuello de pavo") con clara indicación de lifting quirúrgico.',
                'Micrognatia severa (falta de hueso extrema) tributaria de cirugía ortognática.',
                'Infiltrar ácido hialurónico en pacientes con rostros muy pesados (añade más anchura).',
            ),
            array(
                'Análisis del perfil, proyección del mentón y calidad de la piel cervical.',
                'Valoración de los paquetes grasos y la mordida.',
                'Propuesta terapéutica (tecnología, inyectables o combinación).',
            ),
            array(
                array(
                    'q' => '¿Se puede marcar la mandíbula solo con ácido hialurónico?',
                    'a' => 'Si hay grasa superpuesta, inyectar relleno solo ensanchará el rostro. Primero hay que tratar la grasa, y luego definir la estructura.',
                ),
            )
        ),
    );
}

/**
 * Catalogue for Facial Anatomical Zone Pages.
 *
 * @return array<string, array<string, mixed>>
 */
function nvx_anatomical_facial_catalog(): array {
    return array_merge( nvx_anatomical_facial_catalog_upper_mid(), nvx_anatomical_facial_catalog_lower() );
}

/**
 * Catalogue for Body Anatomical Zone Pages.
 *
 * @return array<string, array<string, mixed>>
 */
function nvx_anatomical_body_catalog(): array {
    return array(
        'abdomen-y-flancos' => nvx_anatomical_entry(
            'soluciones-medicas/cuerpo/abdomen-y-flancos',
            'Tratamientos de Abdomen y Flancos | NUVANX',
            'Remodelación de abdomen y cintura. Diferenciamos grasa, laxitud y diástasis para ofrecer un resultado clínico real.',
            NVX_KICKER_CUERPO,
            'El abordaje de la región abdominal requiere un diagnóstico preciso de los tejidos involucrados.',
            'El volumen abdominal resistente a dieta puede deberse a adiposidad localizada, laxitud cutánea tras fluctuaciones de peso o diástasis muscular. Tratar estas distintas condiciones anatómicas con un único enfoque es ineficaz.',
            'Realizamos una exploración médica para separar la grasa subcutánea (tratable con láser o radiofrecuencia) de la grasa visceral (interna), la diástasis de rectos y la laxitud de la piel.',
            'Destruimos los adipocitos y tensamos la piel en la misma sesión con Endoláser, o mejoramos la tonicidad muscular y redensificamos el tejido con plataformas electromagnéticas y radiofrecuencia fraccionada.',
            array(
                'Grasa localizada rebelde en abdomen y flancos ("michelines").',
                'Flacidez cutánea tras pérdidas de peso o embarazos.',
                'Pérdida de definición de la cintura.',
            ),
            array(
                'Grasa visceral predominante (requiere dieta y ejercicio).',
                'Diástasis severa o hernias no tratadas.',
                'Faldón abdominal masivo (indicación de abdominoplastia).',
            ),
            array(
                'Palpación y ecografía de la pared abdominal.',
                'Evaluación del grado de laxitud cutánea (pinch test).',
                'Diseño de la topografía del tratamiento y selección de aparatología.',
            ),
            array(
                array(
                    'q' => '¿El Endoláser duele?',
                    'a' => 'Se realiza bajo anestesia local, por lo que el procedimiento en sí no es doloroso. Notarás inflamación las semanas posteriores.',
                ),
            )
        ),
        'brazos-y-espalda' => nvx_anatomical_entry(
            'soluciones-medicas/cuerpo/brazos-y-espalda',
            'Tratamientos para Flacidez en Brazos y Espalda | NUVANX',
            'Tratamiento del descolgamiento en brazos y rollitos de la espalda. Tensamos el tejido desde dentro.',
            NVX_KICKER_CUERPO,
            'La laxitud en la cara interna de los brazos refleja la disminución de colágeno, pero es posible inducir su síntesis.',
            'El descolgamiento de la cara interna del brazo ("alas de murciélago") y los pliegues de la espalda bajo el sujetador son problemas mecánicos del tejido. Necesitan tensión estructural.',
            'Determinamos si el volumen se debe a grasa localizada pesada, a pura laxitud de la piel, o a falta de masa muscular en el tríceps. Esto dicta si debemos "vaciar", "tensar", o ambas.',
            'Combinamos láser subdérmico para retraer el tejido de forma interna y radiofrecuencia potente para generar colágeno nuevo. A veces integramos inductores de colágeno inyectables para engrosar la dermis fina del brazo.',
            array(
                'Flacidez leve a moderada en la cara interna de los brazos.',
                'Acúmulos grasos en la axila y la línea del sujetador en la espalda.',
                'Pérdida de firmeza y piel apergaminada.',
            ),
            array(
                'Descolgamientos masivos tras cirugías bariátricas (tributarios de braquioplastia quirúrgica).',
                'Piel con estrías extremas sin capacidad elástica residual.',
            ),
            array(
                'Marcaje en bipedestación (de pie) y con el brazo a 90 grados.',
                'Aplicación del tratamiento tensado (láser o aparatología no invasiva).',
                'Seguimiento y prescripción de presoterapia o prendas de compresión suaves si procede.',
            ),
            array(
                array(
                    'q' => '¿Me quedará cicatriz?',
                    'a' => 'Nuestras opciones son mínimamente invasivas o no invasivas. En el caso del láser subdérmico, la incisión es del tamaño de una aguja, por lo que no deja cicatriz quirúrgica visible.',
                ),
            )
        ),
        'tren-inferior' => nvx_anatomical_entry(
            'soluciones-medicas/cuerpo/tren-inferior',
            'Tratamientos Tren Inferior: Muslos, Glúteos y Rodillas | NUVANX',
            'Remodelación de celulitis, grasa y flacidez en el tren inferior. Diagnóstico clínico de la piel.',
            NVX_KICKER_CUERPO,
            'La celulitis es una entidad multifactorial. Evaluamos y abordamos la fibrosis, la retención de líquidos y el componente graso de forma individualizada.',
            'El tren inferior concentra celulitis, grasa localizada (cartucheras) y flacidez (cara interna del muslo, rodillas). Usar la misma máquina de masajes para todo es la razón por la que no ves resultados duraderos.',
            'Diferenciamos el tipo de celulitis (edematosa, fibrosa o adiposa) y evaluamos la laxitud de la rodilla y el muslo. Si hay hoyuelos profundos (fibrosis), romperlos es el único camino.',
            'Liberamos los tractos fibrosos (hoyuelos) de forma manual o con tecnología avanzada (subcisión), mejoramos la calidad de la piel con radiofrecuencia y tratamos la grasa focalizada con láser subdérmico.',
            array(
                'Celulitis en cualquiera de sus fases (con especial éxito en celulitis fibrótica).',
                'Grasa localizada en trocánteres (cartucheras) y cara interna de rodillas.',
                'Flacidez en la región subglútea (banana roll) y cara interna de muslos.',
            ),
            array(
                'Problemas vasculares o linfáticos severos (requieren abordaje médico específico).',
                'Lipodistrofias o linfedemas que escapan al tratamiento puramente estético.',
                'Falta de compromiso con los hábitos de vida (la celulitis requiere un enfoque 360).',
            ),
            array(
                'Exploración del tejido, test de pellizco y marcaje de hoyuelos fibróticos.',
                'Selección de tecnología: liberación de septos, tensado o lipólisis.',
                'Pautas de actividad física, hidratación y drenaje linfático complementario.',
            ),
            array(
                array(
                    'q' => '¿La celulitis se quita para siempre?',
                    'a' => 'La celulitis es una condición crónica del tejido conectivo femenino. Podemos mejorar drásticamente su aspecto, alisar los hoyuelos y tensar la piel, pero requerirá mantenimiento y buenos hábitos a largo plazo.',
                ),
            )
        ),
    );
}

/**
 * Catalogue for Anatomical Zone Pages.
 *
 * @return array<string, array<string, mixed>>
 */
function nvx_anatomical_pages_catalog(): array {
    return array_merge( nvx_anatomical_facial_catalog(), nvx_anatomical_body_catalog() );
}

nvx_register_catalog_content_filter( 'nvx_anatomical_pages_catalog', 22 );
