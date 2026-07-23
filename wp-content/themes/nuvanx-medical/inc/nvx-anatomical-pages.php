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
 * Catalogue for Facial Anatomical Zone Pages.
 *
 * @return array<string, array<string, mixed>>
 */
function nvx_anatomical_facial_catalog(): array {
	return array(
		'tercio-superior' => array(
			'slug'          => 'soluciones-medicas/rostro/tercio-superior',
			'seo_title'     => 'Tratamientos Tercio Superior: Frente y Entrecejo | NUVANX',
			'description'   => 'Valoración médica para líneas de expresión en frente y entrecejo. Relajamos la musculatura sin congelar tu mirada.',
			'kicker'        => NVX_KICKER_ROSTRO,
			'h1'            => 'Las arrugas de la frente no son el problema, son el síntoma de cómo gesticulas.',
			'lead'          => 'Si te molestan esas líneas que se quedan marcadas incluso cuando estás seria, la mejor crema no servirá de nada porque el problema está en el músculo que hay debajo. Estudiamos tu forma de gesticular para relajar la zona sin que pierdas tu expresión natural.',
			'diagnosis'     => 'Congelar la frente por sistema hace que las cejas caigan o que la expresión se vea plástica. Médicamente, diferenciamos entre arrugas dinámicas (causadas por contracción muscular) y estáticas (cuando la piel ya se ha fracturado) para decidir el enfoque.',
			'mechanism'     => 'Si el problema es dinámico, utilizamos neuromoduladores en dosis ultra precisas para "educar" al músculo. Si ya existe una fractura en la piel, combinamos terapias de redensificación para rellenar la huella sin aportar volumen artificial.',
			'indications'   => array(
				'Líneas horizontales en la frente por hiperactividad muscular.',
				'Surcos en el entrecejo (líneas del ceño fruncido) que dan aspecto de enfado.',
				'Arrugas finas y pérdida de soporte en la zona lateral de los ojos (patas de gallo).',
			),
			'precautions'   => array(
				'Infección o inflamación activa en la zona de inyección.',
				'Antecedentes de patologías neuromusculares exigen valoración exhaustiva.',
				'Expectativa de "congelación total" de la mirada (no realizamos tratamientos que borren la expresividad).',
			),
			'process'       => array(
				'Estudio de la dinámica muscular (te pediremos que gesticules, te enfades y te sorprendas).',
				'Diseño del mapa de inyección adaptado a tu asimetría natural.',
				'Intervención ambulatoria y revisión médica a los 15 días para evaluar la relajación y hacer reajustes si es necesario.',
			),
			'faqs'          => array(
				array(
					'q' => '¿Perderé la expresividad en la mirada?',
					'a' => 'No. Nuestra filosofía es la "intervención mínima". Tratamos el músculo justo para que dejes de fracturar la piel, pero manteniendo la capacidad de expresar emociones.',
				),
				array(
					'q' => '¿Cuánto tiempo duran los resultados?',
					'a' => 'La relajación muscular suele durar entre 3 y 5 meses, dependiendo de la fuerza de tu músculo y tu metabolismo.',
				),
			),
			'review_status' => 'approved_for_publication',
		),
		'mirada' => array(
			'slug'          => 'soluciones-medicas/rostro/mirada',
			'seo_title'     => 'Tratamientos para la Mirada: Ojeras y Párpados | NUVANX',
			'description'   => 'Diagnóstico de la región periocular. Diferenciamos hundimiento, bolsas y pigmentación para proponer el tratamiento médico adecuado.',
			'kicker'        => NVX_KICKER_ROSTRO,
			'h1'            => 'Tu mirada no siempre refleja lo cansada que estás; a veces es solo la anatomía.',
			'lead'          => 'Tener "cara de cansada" durmiendo ocho horas no se arregla con más sueño ni con correctores mágicos. El problema suele ser cómo la luz incide sobre tus ojos debido a un déficit de soporte óseo o calidad de piel.',
			'diagnosis'     => 'Rechazamos los rellenos indiscriminados. Diferenciamos estrictamente entre un surco lagrimal hundido (falta de hueso/grasa), componente vascular, pigmentación y bolsas grasas reales. Tratar una bolsa inyectando ácido hialurónico es un error médico que empeora el aspecto.',
			'mechanism'     => 'Si falta soporte, usamos inyectables estructurales profundos. Si el problema es de calidad cutánea, empleamos tecnología de redensificación. Si hay bolsas severas, te derivaremos a cirugía.',
			'indications'   => array(
				'Hundimiento del surco lagrimal que genera sombras (falsas ojeras).',
				'Piel fina y apergaminada en el párpado inferior.',
				'Pigmentación o exceso vascular (ojeras marrones o moradas).',
			),
			'precautions'   => array(
				'Presencia de bolsas grasas reales severas (requieren valoración para blefaroplastia).',
				'Retención de líquidos o edema malar (el ácido hialurónico agravará el problema).',
				'Expectativas poco realistas sobre la pigmentación congénita oscura.',
			),
			'process'       => array(
				'Análisis óseo y estructural de la transición párpado-mejilla.',
				'Evaluación de la laxitud cutánea y la presencia de edema.',
				'Diseño del plan (soporte inyectable, tecnología o derivación quirúrgica).',
			),
			'faqs'          => array(
				array(
					'q' => '¿Se puede quitar una ojera oscura con ácido hialurónico?',
					'a' => 'No. El ácido hialurónico da volumen y corrige hundimientos. Si el problema es puro color (pigmentación), se requieren despigmentantes o tecnología.',
				),
			),
			'review_status' => 'approved_for_publication',
		),
		'tercio-medio' => array(
			'slug'          => 'soluciones-medicas/rostro/tercio-medio',
			'seo_title'     => 'Tratamientos Tercio Medio: Pómulos y Surcos | NUVANX',
			'description'   => 'Soporte facial sin exceso de volumen. Tratamos la flacidez del tercio medio devolviendo la estructura natural al rostro.',
			'kicker'        => NVX_KICKER_ROSTRO,
			'h1'            => 'Rellenar los surcos nasogenianos sin dar soporte a los pómulos es como tapar una grieta sin arreglar los cimientos.',
			'lead'          => 'Si te ves la cara caída o los surcos muy marcados, la tentación es rellenar la arruga directamente. Pero esa arruga suele formarse porque el pómulo y la mejilla han perdido soporte y la piel "cae".',
			'diagnosis'     => 'Diferenciamos la pérdida de volumen profundo (grasa o hueso que desaparece con la edad) de la laxitud cutánea superficial. Añadir volumen cuando el problema es laxitud solo consigue una "cara de globo" antinatural.',
			'mechanism'     => 'En lugar de rellenar la arruga, aplicamos inyectables estructurales en vectores ascendentes para dar soporte a los ligamentos del pómulo, o utilizamos bioestimuladores si lo que necesitas es generar colágeno nuevo.',
			'indications'   => array(
				'Pérdida de proyección y soporte en la zona de los pómulos.',
				'Aplanamiento de las mejillas y descolgamiento del tercio medio.',
				'Surcos nasogenianos marcados por gravedad (caída del tejido).',
			),
			'precautions'   => array(
				'Acúmulo de grasa malar (exceso de peso en la mejilla).',
				'Laxitud extrema que requiere lifting quirúrgico.',
				'Solicitudes de volumen excesivo que desvirtúen la anatomía original.',
			),
			'process'       => array(
				'Palpación de los ligamentos de retención facial.',
				'Planificación de los vectores de tracción o reposicionamiento.',
				'Infiltración profunda supra-perióstica para máximo soporte sin dar aspecto de cara ancha.',
			),
			'faqs'          => array(
				array(
					'q' => '¿Me cambiará la forma de la cara?',
					'a' => 'Al revés. Buscamos devolverte la estructura que tenías hace años, no crearte unos pómulos que no son tuyos.',
				),
			),
			'review_status' => 'approved_for_publication',
		),
		'labios' => array(
			'slug'          => 'soluciones-medicas/rostro/labios',
			'seo_title'     => 'Tratamientos de Labios y Zona Perioral | NUVANX',
			'description'   => 'Hidratación, perfilado y recuperación del labio. Armonizamos la zona perioral sin volúmenes artificiales.',
			'kicker'        => NVX_KICKER_ROSTRO,
			'h1'            => 'Un labio bonito no es el que más resalta, es el que mejor encaja en tus proporciones.',
			'lead'          => 'Huimos de los volúmenes exagerados y los perfiles plásticos. Nuestro objetivo es hidratar, corregir asimetrías o recuperar la estructura que los labios pierden con el tiempo.',
			'diagnosis'     => 'Evaluamos si necesitas volumen, si solo buscas hidratación profunda sin cambiar la forma, o si el problema principal está en la zona de alrededor (código de barras, sonrisa gingival o comisuras caídas).',
			'mechanism'     => 'Empleamos ácidos hialurónicos dinámicos, diseñados específicamente para integrarse en el tejido muscular del labio y moverse contigo cuando hablas o sonríes, evitando bultos y rigidez.',
			'indications'   => array(
				'Pérdida de hidratación y afinamiento del tejido labial.',
				'Asimetrías o falta de definición en el arco de Cupido y contornos.',
				'Arrugas periorales (código de barras) y comisuras caídas.',
			),
			'precautions'   => array(
				'Infecciones activas o herpes labial agudo (debe tratarse previamente).',
				'Anatomía que no admite más producto (si hay migración de rellenos anteriores, primero disolvemos).',
				'Peticiones de volúmenes desproporcionados respecto a la base ósea y dental.',
			),
			'process'       => array(
				'Análisis dinámico: te observamos hablando y sonriendo.',
				'Diseño del tratamiento: elección de la densidad del ácido hialurónico.',
				'Inyección conservadora y masaje de integración.',
			),
			'faqs'          => array(
				array(
					'q' => '¿Qué pasa si ya llevo relleno de otro sitio y no me gusta?',
					'a' => 'Si el producto anterior está mal posicionado o migrado, lo honesto es disolverlo con hialuronidasa, dejar que el tejido se recupere, y empezar de cero.',
				),
			),
			'review_status' => 'approved_for_publication',
		),
		'tercio-inferior' => array(
			'slug'          => 'soluciones-medicas/rostro/tercio-inferior',
			'seo_title'     => 'Tratamientos Tercio Inferior: Mandíbula y Cuello | NUVANX',
			'description'   => 'Definición del óvalo facial y tratamiento de papada y cuello sin cirugía. Diagnóstico estructural avanzado.',
			'kicker'        => NVX_KICKER_ROSTRO,
			'h1'            => 'A veces no hay papada real: lo que pasa es que el mentón es pequeño y la piel cuelga por falta de soporte.',
			'lead'          => 'El tercio inferior delata el paso del tiempo por la pérdida de definición en la mandíbula y la flacidez del cuello. Antes de intentar quemar grasa o estirar la piel, medimos tus proporciones óseas.',
			'diagnosis'     => 'Separamos claramente la grasa submentoniana (papada), la flacidez cutánea, la hipertrofia del músculo masetero y el déficit óseo (micrognatia). Un mal diagnóstico aquí deriva en caras cuadradas o tratamientos inútiles.',
			'mechanism'     => 'Proporcionamos soporte con inyectables de alta densidad en mentón y ángulo mandibular, relajamos músculos depresores con neuromoduladores, o eliminamos grasa y tensamos piel mediante tecnología láser (Endoláser o radiofrecuencia).',
			'indications'   => array(
				'Pérdida de definición en la línea mandibular ("jowls" o "caritas de bulldog").',
				'Papada por acúmulo graso o laxitud cutánea.',
				'Mentón retraído que desequilibra el perfil.',
			),
			'precautions'   => array(
				'Flacidez severa ("cuello de pavo") con clara indicación de lifting quirúrgico.',
				'Micrognatia severa (falta de hueso extrema) tributaria de cirugía ortognática.',
				'Infiltrar ácido hialurónico en pacientes con rostros muy pesados (añade más anchura).',
			),
			'process'       => array(
				'Análisis del perfil, proyección del mentón y calidad de la piel cervical.',
				'Valoración de los paquetes grasos y la mordida.',
				'Propuesta terapéutica (tecnología, inyectables o combinación).',
			),
			'faqs'          => array(
				array(
					'q' => '¿Se puede marcar la mandíbula solo con ácido hialurónico?',
					'a' => 'Si hay grasa superpuesta, inyectar relleno solo ensanchará el rostro. Primero hay que tratar la grasa, y luego definir la estructura.',
				),
			),
			'review_status' => 'approved_for_publication',
		),
	);
}

/**
 * Catalogue for Body Anatomical Zone Pages.
 *
 * @return array<string, array<string, mixed>>
 */
function nvx_anatomical_body_catalog(): array {
	return array(
		'abdomen-y-flancos' => array(
			'slug'          => 'soluciones-medicas/cuerpo/abdomen-y-flancos',
			'seo_title'     => 'Tratamientos de Abdomen y Flancos | NUVANX',
			'description'   => 'Remodelación de abdomen y cintura. Diferenciamos grasa, laxitud y diástasis para ofrecer un resultado clínico real.',
			'kicker'        => NVX_KICKER_CUERPO,
			'h1'            => 'El abdomen no se arregla haciendo abdominales a ciegas. Medimos qué falla antes de actuar.',
			'lead'          => 'La "tripita" rebelde que no se va con dieta puede ser grasa localizada, puede ser piel estirada tras fluctuaciones de peso, o puede ser una pared muscular débil. Tratar todo como si fuera grasa es perder el tiempo.',
			'diagnosis'     => 'Realizamos una exploración médica para separar la grasa subcutánea (tratable con láser o radiofrecuencia) de la grasa visceral (interna), la diástasis de rectos y la laxitud de la piel.',
			'mechanism'     => 'Destruimos los adipocitos y tensamos la piel en la misma sesión con Endoláser, o mejoramos la tonicidad muscular y redensificamos el tejido con plataformas electromagnéticas y radiofrecuencia fraccionada.',
			'indications'   => array(
				'Grasa localizada rebelde en abdomen y flancos ("michelines").',
				'Flacidez cutánea tras pérdidas de peso o embarazos.',
				'Pérdida de definición de la cintura.',
			),
			'precautions'   => array(
				'Grasa visceral predominante (requiere dieta y ejercicio).',
				'Diástasis severa o hernias no tratadas.',
				'Faldón abdominal masivo (indicación de abdominoplastia).',
			),
			'process'       => array(
				'Palpación y ecografía de la pared abdominal.',
				'Evaluación del grado de laxitud cutánea (pinch test).',
				'Diseño de la topografía del tratamiento y selección de aparatología.',
			),
			'faqs'          => array(
				array(
					'q' => '¿El Endoláser duele?',
					'a' => 'Se realiza bajo anestesia local, por lo que el procedimiento en sí no es doloroso. Notarás inflamación las semanas posteriores.',
				),
			),
			'review_status' => 'approved_for_publication',
		),
		'brazos-y-espalda' => array(
			'slug'          => 'soluciones-medicas/cuerpo/brazos-y-espalda',
			'seo_title'     => 'Tratamientos para Flacidez en Brazos y Espalda | NUVANX',
			'description'   => 'Tratamiento del descolgamiento en brazos y rollitos de la espalda. Tensamos el tejido desde dentro.',
			'kicker'        => NVX_KICKER_CUERPO,
			'h1'            => 'La piel de los brazos delata la pérdida de colágeno, pero podemos forzar su creación.',
			'lead'          => 'El descolgamiento de la cara interna del brazo ("alas de murciélago") y los pliegues de la espalda bajo el sujetador son problemas mecánicos del tejido. Necesitan tensión estructural.',
			'diagnosis'     => 'Determinamos si el volumen se debe a grasa localizada pesada, a pura laxitud de la piel, o a falta de masa muscular en el tríceps. Esto dicta si debemos "vaciar", "tensar", o ambas.',
			'mechanism'     => 'Combinamos láser subdérmico para retraer el tejido de forma interna y radiofrecuencia potente para generar colágeno nuevo. A veces integramos inductores de colágeno inyectables para engrosar la dermis fina del brazo.',
			'indications'   => array(
				'Flacidez leve a moderada en la cara interna de los brazos.',
				'Acúmulos grasos en la axila y la línea del sujetador en la espalda.',
				'Pérdida de firmeza y piel apergaminada.',
			),
			'precautions'   => array(
				'Descolgamientos masivos tras cirugías bariátricas (tributarios de braquioplastia quirúrgica).',
				'Piel con estrías extremas sin capacidad elástica residual.',
			),
			'process'       => array(
				'Marcaje en bipedestación (de pie) y con el brazo a 90 grados.',
				'Aplicación del tratamiento tensado (láser o aparatología no invasiva).',
				'Seguimiento y prescripción de presoterapia o prendas de compresión suaves si procede.',
			),
			'faqs'          => array(
				array(
					'q' => '¿Me quedará cicatriz?',
					'a' => 'Nuestras opciones son mínimamente invasivas o no invasivas. En el caso del láser subdérmico, la incisión es del tamaño de una aguja, por lo que no deja cicatriz quirúrgica visible.',
				),
			),
			'review_status' => 'approved_for_publication',
		),
		'tren-inferior' => array(
			'slug'          => 'soluciones-medicas/cuerpo/tren-inferior',
			'seo_title'     => 'Tratamientos Tren Inferior: Muslos, Glúteos y Rodillas | NUVANX',
			'description'   => 'Remodelación de celulitis, grasa y flacidez en el tren inferior. Diagnóstico clínico de la piel.',
			'kicker'        => NVX_KICKER_CUERPO,
			'h1'            => 'No existe una sola "celulitis". Tratamos la fibrosis, la retención y la grasa de forma independiente.',
			'lead'          => 'El tren inferior concentra celulitis, grasa localizada (cartucheras) y flacidez (cara interna del muslo, rodillas). Usar la misma máquina de masajes para todo es la razón por la que no ves resultados duraderos.',
			'diagnosis'     => 'Diferenciamos el tipo de celulitis (edematosa, fibrosa o adiposa) y evaluamos la laxitud de la rodilla y el muslo. Si hay hoyuelos profundos (fibrosis), romperlos es el único camino.',
			'mechanism'     => 'Liberamos los tractos fibrosos (hoyuelos) de forma manual o con tecnología avanzada (subcisión), mejoramos la calidad de la piel con radiofrecuencia y tratamos la grasa focalizada con láser subdérmico.',
			'indications'   => array(
				'Celulitis en cualquiera de sus fases (con especial éxito en celulitis fibrótica).',
				'Grasa localizada en trocánteres (cartucheras) y cara interna de rodillas.',
				'Flacidez en la región subglútea (banana roll) y cara interna de muslos.',
			),
			'precautions'   => array(
				'Problemas vasculares o linfáticos severos (requieren abordaje médico específico).',
				'Lipodistrofias o linfedemas que escapan al tratamiento puramente estético.',
				'Falta de compromiso con los hábitos de vida (la celulitis requiere un enfoque 360).',
			),
			'process'       => array(
				'Exploración del tejido, test de pellizco y marcaje de hoyuelos fibróticos.',
				'Selección de tecnología: liberación de septos, tensado o lipólisis.',
				'Pautas de actividad física, hidratación y drenaje linfático complementario.',
			),
			'faqs'          => array(
				array(
					'q' => '¿La celulitis se quita para siempre?',
					'a' => 'La celulitis es una condición crónica del tejido conectivo femenino. Podemos mejorar drásticamente su aspecto, alisar los hoyuelos y tensar la piel, pero requerirá mantenimiento y buenos hábitos a largo plazo.',
				),
			),
			'review_status' => 'approved_for_publication',
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
