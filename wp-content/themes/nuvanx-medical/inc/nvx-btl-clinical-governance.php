<?php
/**
 * Clinical governance for BTL detail pages.
 *
 * This layer removes deterministic high-risk comparative and quantitative
 * statements from the rendered BTL pages until each claim has a primary,
 * page-specific evidence record approved by the medical director.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return the supported BTL detail slugs.
 *
 * @return string[]
 */
function nvx_btl_governed_slugs(): array {
	return array( 'exion-face', 'exion-body', 'exion-fractional', 'emfusion' );
}

/**
 * Whether the current request is a governed BTL detail page.
 */
function nvx_btl_is_governed_request(): bool {
	if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return false;
	}

	if ( ! is_page() ) {
		return false;
	}

	$slug = (string) get_post_field( 'post_name', get_queried_object_id() );
	return in_array( $slug, nvx_btl_governed_slugs(), true );
}

/**
 * Replace high-risk copy with clinically neutral wording.
 *
 * The source module remains visible to reviewers so unsupported wording cannot
 * disappear from code review. This filter prevents that wording from reaching
 * patients while the source registry is rewritten and medically signed off.
 *
 * @param string $content Rendered page content.
 * @return string
 */
function nvx_btl_govern_rendered_content( string $content ): string {
	if ( ! nvx_btl_is_governed_request() || '' === $content ) {
		return $content;
	}

	$replacements = array(
		'EXION® Face en Madrid: regeneración endógena facial sin daño térmico agresivo' => 'EXION® Face en Madrid: protocolo médico para calidad cutánea',
		'Radiofrecuencia monopolar y ultrasonido terapéutico (TUS) a microtemperaturas controladas para redensificar, hidratar y mejorar calidad de piel — con criterio médico en Chamberí y Goya.' => 'Radiofrecuencia monopolar y ultrasonido terapéutico dentro de protocolos individualizados de calidad cutánea, tras valoración médica en Chamberí o Goya.',
		'Chamberí · Goya · Alternativa a HIFU / RF de alto pico térmico' => 'Chamberí · Goya · Indicación individualizada',
		'EXION Face combina radiofrecuencia monopolar y ultrasonido terapéutico en un protocolo orientado a estimular fibroblastos y matriz extracelular, no a necrosar tejido con picos térmicos de 60–70 °C.' => 'EXION Face combina radiofrecuencia monopolar y ultrasonido terapéutico en un protocolo orientado a la calidad cutánea. Los parámetros se adaptan al tejido, la zona y el objetivo clínico.',
		'El rango de trabajo habitual (~40–42 °C en dermis) busca hipertermia controlada y reversible: activación de vías de estrés adaptativo (p. ej. HSPs) y señalización de síntesis de matriz, con ultrasonido como mecanoestimulación complementaria.' => 'La plataforma combina energía térmica y ultrasonido. La respuesta depende de la dosificación, la anatomía y el protocolo aplicado; no se comunica una temperatura universal como garantía de resultado.',
		'Documentación del fabricante describe, en modelos evaluados, incrementos de marcadores de ácido hialurónico endógeno del orden del 224% a ~4 semanas; en consulta se presentan como potencial de estimulación, no como promesa personalizada.' => 'La documentación técnica describe cambios experimentales en componentes de la matriz extracelular. Esos datos no equivalen a una magnitud clínica garantizada en humanos y deben interpretarse con cautela.',
		'Historial de plataformas de “quemadura controlada” con pérdida de volumen o textura rígida: se reevalúa anatomía y se prioriza regeneración sin agravar atrofia.' => 'Ante tratamientos energéticos previos se revisan anatomía, volumen, sensibilidad y calidad cutánea antes de indicar un nuevo procedimiento.',
		'HIFU y RF volumétrica de alto pico buscan contracción por desnaturalización intensa. EXION Face prioriza regeneración a temperaturas más fisiológicas, con mejor tolerancia y menor downtime. La comparativa clínica ampliada está en el blog médico.' => 'HIFU, radiofrecuencia y EXION Face utilizan mecanismos y profundidades diferentes. La elección depende del diagnóstico, la recuperación aceptable y la evidencia aplicable a cada indicación.',
		'Protocolo habitual: 3 sesiones (~4 semanas). Casos avanzados: 4–5 o combinación (p. ej. EMFUSION®).' => 'El número y el intervalo de sesiones se definen después de valorar la zona, la respuesta inicial y el objetivo clínico.',
		'Sesión ~30 min. Downtime mínimo o nulo en la mayoría de pacientes.' => 'La duración y la recuperación dependen de la zona, los parámetros y la respuesta individual.',
		'La mayoría describe calor tolerable (0–2/10). No es comparable al dolor habitual de HIFU de alta energía sin anestesia.' => 'La percepción de calor o molestia varía. El equipo explica el protocolo de confort y ajusta la energía según tolerancia y objetivo.',
		'Al trabajar con picos más fisiológicos, el riesgo de PIH suele ser inferior al de daño térmico agresivo; la indicación la marca el médico.' => 'El riesgo pigmentario depende de fototipo, inflamación, parámetros y antecedentes. La indicación y los cuidados se individualizan.',
		'EXION® Body en Madrid: lipólisis y retracción cutánea en un solo protocolo' => 'EXION® Body en Madrid: protocolo corporal para grasa localizada y calidad cutánea',
		'Radiofrecuencia monopolar con refrigeración activa para adiposidad localizada y laxitud asociada — sin el downtime de microagujas corporales ni la flacidez residual típica de solo congelar grasa.' => 'Radiofrecuencia monopolar con refrigeración activa para protocolos corporales seleccionados, después de valorar adiposidad localizada, laxitud y calidad cutánea.',
		'En rangos de ~40–45 °C en tejido adiposo se busca apoptosis programada de adipocitos y, en paralelo, contracción y remodelado de colágeno — la “ecuación” grasa + piel que la criolipólisis sola no resuelve.' => 'La plataforma entrega energía térmica con refrigeración superficial. El efecto y la profundidad dependen de parámetros, anatomía y protocolo, y no se expresan como una temperatura o respuesta universal.',
		'Documentación BTL comunica órdenes de magnitud del tipo −22% adiposidad y mejoras relevantes de laxitud en series evaluadas; en NUVANX se individualiza por espesor graso, zona y calidad de piel.' => 'La documentación técnica presenta resultados de series seleccionadas. No se trasladan porcentajes fijos a la expectativa individual sin revisar metodología, población y protocolo.',
		'Zonas donde perder grasa sin tensar deja piel “suelta”; se valora RF corporal vs láser intersticial según espesor.' => 'En brazos y muslos internos se valoran conjuntamente grasa localizada, laxitud y espesor cutáneo antes de elegir tecnología.',
		'Pacientes con grasa reducida pero laxitud residual: se reorienta el plan a retracción y calidad, no a más frío a ciegas.' => 'Después de otros tratamientos corporales se reevalúan tejido, volumen y laxitud antes de proponer una intervención adicional.',
		'En adiposidad >~4–5 cm puede proponerse laserlipólisis y, en fase posterior, EXION Body para consolidar tensado.' => 'La combinación con procedimientos invasivos solo se plantea después de exploración médica y con una secuencia clínica documentada.',
		'La criolipólisis reduce grasa pero no tensa. Las microagujas corporales tensan con más trauma y downtime. EXION Body busca ambos efectos con mejor tolerancia. Detalle comparativo en el blog.' => 'Criolipólisis, radiofrecuencia con microagujas y EXION Body tienen mecanismos, recuperación y limitaciones diferentes. La elección depende de la anatomía y del objetivo clínico.',
		'Leve: 2–3 sesiones. Moderada: 3–4. Severa: valorar endoláser + EXION en secuencia.' => 'El número de sesiones y la posible combinación con otros procedimientos se definen tras valoración y reevaluación de la respuesta.',
		'Sesión 45–60 min según áreas. Downtime habitual nulo o mínimo.' => 'La duración y la recuperación dependen del área, la intensidad y la respuesta individual.',
		'No es un sistema de perforación con agujas largas; el eritema, si aparece, suele resolverse en horas.' => 'No utiliza microagujas. Puede aparecer eritema, sensibilidad u otros cambios transitorios cuya duración depende del protocolo y del paciente.',
		'Microagujas más cortas y gradiente térmico extendido con feedback de tejido — textura, poros y cicatrices con menos pasadas y downtime más predecible que la RF fraccionada “a ciegas”.' => 'Radiofrecuencia fraccionada con microagujas y control de entrega energética para protocolos de textura, poro y cicatrices según valoración médica.',
		'Agujas más cortas con proyección térmica extendida permiten alcanzar profundidad de trabajo relevante reduciendo trauma mecánico superficial respecto a protocolos de aguja larga multipasada.' => 'La geometría de las microagujas y la entrega energética forman parte del protocolo. La profundidad, el número de pases y la recuperación se individualizan.',
		'Pacientes que no toleran multipasada agresiva' => 'Pacientes con antecedentes de baja tolerancia',
		'Historial de RF fraccionada con hematomas prolongados o abandono por dolor: se reevalúa energía y número de pases.' => 'Ante tratamientos previos con recuperación difícil se revisan parámetros, analgesia, número de pases y alternativas.',
		'Downtime típico: eritema 12–48 h según energía; se explica antes de firmar el plan.' => 'La recuperación puede incluir eritema, edema o sensibilidad. Su intensidad y duración dependen de parámetros y respuesta individual.',
		'El diseño single-pass reduce pasadas innecesarias cuando el feedback de tejido es adecuado; el médico puede modular según zona.' => 'El número de pases se define según el sistema, la zona, la respuesta del tejido y el objetivo clínico.',
		'EMFUSION® en Madrid: infusión cutánea y restauración de barrera sin succión agresiva' => 'EMFUSION® en Madrid: protocolo de hidratación y apoyo a la barrera cutánea',
		'Tecnología DYNAMiQ™ de microcanales acústicos para favorecer la penetración de activos y apoyar la homeostasis epidérmica — alternativa a vórtices de succión y microneedling cuando la barrera está comprometida.' => 'Tecnología DYNAMiQ™ utilizada en protocolos de aplicación de activos y apoyo a la hidratación y barrera cutánea, según evaluación profesional.',
	);

	$governed = strtr( $content, $replacements );

	$notice = '<aside class="nvx-clinical-note nvx-btl-evidence-note" role="note"><h2 class="nvx-clinical-note__title">Información clínica revisable</h2><p class="nvx-clinical-note__text">Las comparaciones, parámetros y resultados dependen del equipo, el protocolo y el paciente. Esta página no sustituye una valoración médica ni garantiza una magnitud concreta de respuesta.</p></aside>';

	if ( false === strpos( $governed, 'nvx-btl-evidence-note' ) ) {
		$governed .= $notice;
	}

	return $governed;
}
add_filter( 'the_content', 'nvx_btl_govern_rendered_content', 99 );

/**
 * Keep search snippets neutral while source claims are being reviewed.
 *
 * @param string $description Existing Yoast description.
 * @return string
 */
function nvx_btl_govern_metadescription( string $description ): string {
	if ( ! nvx_btl_is_governed_request() ) {
		return $description;
	}

	$slug = (string) get_post_field( 'post_name', get_queried_object_id() );
	$descriptions = array(
		'exion-face'       => 'EXION® Face en NUVANX Madrid: radiofrecuencia y ultrasonido dentro de protocolos individualizados de calidad cutánea. Valoración médica en Chamberí y Goya.',
		'exion-body'       => 'EXION® Body en NUVANX Madrid: protocolo corporal con radiofrecuencia y refrigeración activa, indicado tras valorar grasa localizada, laxitud y calidad cutánea.',
		'exion-fractional' => 'EXION® Fractional RF en NUVANX Madrid: radiofrecuencia fraccionada para textura, poro y cicatrices según diagnóstico, fototipo y recuperación prevista.',
		'emfusion'         => 'EMFUSION® en NUVANX Madrid: protocolo de hidratación y apoyo a la barrera cutánea con tecnología DYNAMiQ™, indicado tras evaluación profesional.',
	);

	return $descriptions[ $slug ] ?? $description;
}
add_filter( 'wpseo_metadesc', 'nvx_btl_govern_metadescription', 99 );
