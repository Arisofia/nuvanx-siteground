<?php
/**
 * Clinical governance for BTL detail pages.
 *
 * This layer keeps manufacturer-supported device information visible while
 * preventing those data from being presented as universal patient outcomes.
 * Comparative, quantitative, pain and recovery statements remain qualified
 * until each page has a primary evidence record approved by the medical
 * director.
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
 * Retain sourced device data and qualify the interpretation presented to users.
 *
 * @param string $content Rendered page content.
 * @return string
 */
function nvx_btl_govern_rendered_content( string $content ): string {
	if ( ! nvx_btl_is_governed_request() || '' === $content ) {
		return $content;
	}

	$replacements = array(
		'EXION Face combina radiofrecuencia monopolar y ultrasonido terapéutico en un protocolo orientado a estimular fibroblastos y matriz extracelular, no a necrosar tejido con picos térmicos de 60–70 °C.' => 'EXION Face combina radiofrecuencia monopolar y ultrasonido terapéutico en un protocolo orientado a estimular fibroblastos y matriz extracelular sin recurrir a un daño térmico agresivo. La comparación con otras plataformas depende del aplicador, los parámetros y la indicación.',
		'El rango de trabajo habitual (~40–42 °C en dermis) busca hipertermia controlada y reversible: activación de vías de estrés adaptativo (p. ej. HSPs) y señalización de síntesis de matriz, con ultrasonido como mecanoestimulación complementaria.' => 'La documentación técnica de EXION Face describe microtemperaturas controladas y un rango dérmico aproximado de 40–42 °C en protocolos evaluados. Ese rango orienta la dosificación del equipo, pero no constituye una temperatura universal ni garantiza una respuesta individual.',
		'Documentación del fabricante describe, en modelos evaluados, incrementos de marcadores de ácido hialurónico endógeno del orden del 224% a ~4 semanas; en consulta se presentan como potencial de estimulación, no como promesa personalizada.' => 'La documentación del fabricante comunica un aumento de hasta 224% en marcadores de ácido hialurónico endógeno en modelos evaluados. El dato debe interpretarse según el diseño, la población y el seguimiento del estudio, y no equivale a una mejora clínica garantizada para cada paciente.',
		'Historial de plataformas de “quemadura controlada” con pérdida de volumen o textura rígida: se reevalúa anatomía y se prioriza regeneración sin agravar atrofia.' => 'Ante tratamientos energéticos previos se revisan anatomía, volumen, sensibilidad y calidad cutánea antes de indicar un nuevo procedimiento. La expresión “daño térmico agresivo” se reserva para explicar diferencias de dosificación, no para atribuir complicaciones universales a otra plataforma.',
		'HIFU y RF volumétrica de alto pico buscan contracción por desnaturalización intensa. EXION Face prioriza regeneración a temperaturas más fisiológicas, con mejor tolerancia y menor downtime. La comparativa clínica ampliada está en el blog médico.' => 'HIFU, radiofrecuencia volumétrica y EXION Face utilizan mecanismos, profundidades y perfiles de recuperación distintos. EXION Face puede ofrecer mejor tolerancia o menor recuperación en determinados protocolos, pero esa comparación no es universal y debe sostenerse en evidencia aplicable a cada indicación.',
		'Protocolo habitual: 3 sesiones (~4 semanas). Casos avanzados: 4–5 o combinación (p. ej. EMFUSION®).' => 'La documentación y los protocolos clínicos suelen organizar EXION Face en series de sesiones, con frecuencia alrededor de tres y separadas aproximadamente cuatro semanas. El número final se individualiza tras valorar respuesta y objetivo.',
		'Sesión ~30 min. Downtime mínimo o nulo en la mayoría de pacientes.' => 'La sesión puede situarse alrededor de 30 minutos según zona y protocolo. El fabricante describe una recuperación mínima o nula para el aplicador no invasivo, aunque pueden aparecer eritema, sensibilidad u otros cambios transitorios.',
		'La mayoría describe calor tolerable (0–2/10). No es comparable al dolor habitual de HIFU de alta energía sin anestesia.' => 'En protocolos publicados por centros y materiales del fabricante se comunican puntuaciones bajas de molestia, incluidas referencias de 0–2/10. La percepción es individual y no debe presentarse como garantía ni como comparación absoluta frente a HIFU.',
		'Al trabajar con picos más fisiológicos, el riesgo de PIH suele ser inferior al de daño térmico agresivo; la indicación la marca el médico.' => 'Al emplear microtemperaturas controladas, el perfil inflamatorio puede diferir del de plataformas con mayor pico térmico. El riesgo de hiperpigmentación depende de fototipo, parámetros, antecedentes y cuidados, por lo que no se comunica como inferior de forma universal.',
		'En rangos de ~40–45 °C en tejido adiposo se busca apoptosis programada de adipocitos y, en paralelo, contracción y remodelado de colágeno — la “ecuación” grasa + piel que la criolipólisis sola no resuelve.' => 'La documentación técnica de EXION Body describe rangos aproximados de 40–45 °C en tejido objetivo dentro de protocolos evaluados. El propósito es combinar acción sobre adiposidad localizada y calidad cutánea, pero el rango y la respuesta dependen del aplicador, la zona y la dosificación.',
		'Documentación BTL comunica órdenes de magnitud del tipo −22% adiposidad y mejoras relevantes de laxitud en series evaluadas; en NUVANX se individualiza por espesor graso, zona y calidad de piel.' => 'BTL comunica hasta −22% de reducción de adiposidad en series evaluadas con EXION Body. El porcentaje pertenece a condiciones de estudio concretas y no debe trasladarse como resultado individual garantizado.',
		'Zonas donde perder grasa sin tensar deja piel “suelta”; se valora RF corporal vs láser intersticial según espesor.' => 'En brazos y muslos internos se valoran conjuntamente grasa localizada, laxitud y espesor cutáneo. La reducción de volumen puede dejar laxitud residual cuando la piel no se adapta, pero no se presenta como desenlace inevitable.',
		'Pacientes con grasa reducida pero laxitud residual: se reorienta el plan a retracción y calidad, no a más frío a ciegas.' => 'Después de criolipólisis u otros tratamientos corporales se reevalúan tejido, volumen y laxitud. La expresión “a ciegas” se limita a describir sistemas sin feedback tisular en tiempo real y no a desacreditar una tecnología completa.',
		'En adiposidad >~4–5 cm puede proponerse laserlipólisis y, en fase posterior, EXION Body para consolidar tensado.' => 'En espesores adiposos mayores puede valorarse una secuencia con laserlipólisis y EXION Body. El umbral, el intervalo y el objetivo deben confirmarse mediante exploración y protocolo médico.',
		'La criolipólisis reduce grasa pero no tensa. Las microagujas corporales tensan con más trauma y downtime. EXION Body busca ambos efectos con mejor tolerancia. Detalle comparativo en el blog.' => 'Criolipólisis, radiofrecuencia con microagujas y EXION Body tienen mecanismos y perfiles de recuperación diferentes. EXION Body puede ofrecer mejor tolerancia o menor recuperación en determinados protocolos, sin convertir esa diferencia en superioridad universal.',
		'Leve: 2–3 sesiones. Moderada: 3–4. Severa: valorar endoláser + EXION en secuencia.' => 'Los protocolos pueden organizarse en rangos orientativos de 2–4 sesiones según severidad, zona y respuesta. El número no se presenta como fijo ni suficiente para todos los pacientes.',
		'Sesión 45–60 min según áreas. Downtime habitual nulo o mínimo.' => 'La sesión puede durar aproximadamente 45–60 minutos según las áreas tratadas. El fabricante describe recuperación nula o mínima para el aplicador no invasivo, aunque la respuesta individual puede incluir cambios transitorios.',
		'No es un sistema de perforación con agujas largas; el eritema, si aparece, suele resolverse en horas.' => 'No utiliza microagujas. El eritema, cuando aparece, puede resolverse en horas según materiales del fabricante y experiencia clínica, pero su intensidad y duración varían.',
		'Microagujas más cortas y gradiente térmico extendido con feedback de tejido — textura, poros y cicatrices con menos pasadas y downtime más predecible que la RF fraccionada “a ciegas”.' => 'EXION Fractional RF utiliza microagujas, control de impedancia y feedback tisular. Esa información permite explicar diferencias frente a sistemas sin retroalimentación en tiempo real, sin afirmar de forma universal menos dolor, menos pases o mejor recuperación.',
		'Agujas más cortas con proyección térmica extendida permiten alcanzar profundidad de trabajo relevante reduciendo trauma mecánico superficial respecto a protocolos de aguja larga multipasada.' => 'La geometría de las microagujas y la proyección térmica pueden reducir el componente mecánico frente a determinados protocolos multipasada. La magnitud de esa diferencia depende del dispositivo, los parámetros y la técnica.',
		'Pacientes que no toleran multipasada agresiva' => 'Pacientes con antecedentes de baja tolerancia a protocolos multipasada',
		'Historial de RF fraccionada con hematomas prolongados o abandono por dolor: se reevalúa energía y número de pases.' => 'Ante tratamientos previos con dolor, hematomas o recuperación prolongada se revisan energía, analgesia, número de pases y alternativas.',
		'Downtime típico: eritema 12–48 h según energía; se explica antes de firmar el plan.' => 'Puede comunicarse un rango orientativo de eritema de 12–48 horas cuando corresponda al protocolo utilizado. No constituye una recuperación garantizada y puede variar con parámetros y respuesta individual.',
		'El diseño single-pass reduce pasadas innecesarias cuando el feedback de tejido es adecuado; el médico puede modular según zona.' => 'El diseño single-pass y el feedback de impedancia pueden reducir pasadas adicionales en protocolos seleccionados. El profesional decide el número de pases según zona, respuesta y objetivo.',
		'EMFUSION® en Madrid: infusión cutánea y restauración de barrera sin succión agresiva' => 'EMFUSION® en Madrid: infusión cutánea y apoyo a la barrera sin sistemas de succión',
		'Tecnología DYNAMiQ™ de microcanales acústicos para favorecer la penetración de activos y apoyar la homeostasis epidérmica — alternativa a vórtices de succión y microneedling cuando la barrera está comprometida.' => 'Tecnología DYNAMiQ™ de microcanales acústicos para favorecer la aplicación de activos y apoyar hidratación y barrera cutánea. Puede compararse con sistemas de succión o microneedling por mecanismo y tolerancia, sin calificarlos de forma denigratoria.',
	);

	$governed = strtr( $content, $replacements );

	$notice = '<aside class="nvx-clinical-note nvx-btl-evidence-note" role="note"><h2 class="nvx-clinical-note__title">Datos técnicos y variabilidad clínica</h2><p class="nvx-clinical-note__text">Los porcentajes, temperaturas, sesiones, dolor y recuperación citados proceden de documentación técnica o protocolos evaluados. No representan una garantía individual. La indicación, los parámetros y la respuesta dependen del equipo, el aplicador, la zona y el paciente.</p></aside>';

	if ( false === strpos( $governed, 'nvx-btl-evidence-note' ) ) {
		$governed .= $notice;
	}

	return $governed;
}
add_filter( 'the_content', 'nvx_btl_govern_rendered_content', 99 );

/**
 * Keep search snippets precise while source claims are being reviewed.
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
		'exion-face'       => 'EXION® Face en NUVANX Madrid: RF y ultrasonido a microtemperaturas controladas para calidad cutánea. Valoración médica en Chamberí y Goya.',
		'exion-body'       => 'EXION® Body en NUVANX Madrid: radiofrecuencia con refrigeración activa para grasa localizada y calidad cutánea, según valoración médica.',
		'exion-fractional' => 'EXION® Fractional RF en Madrid: microagujas con control de impedancia para textura, poro y cicatrices según diagnóstico y fototipo.',
		'emfusion'         => 'EMFUSION® en NUVANX Madrid: microcanales acústicos DYNAMiQ™ para hidratación y apoyo a la barrera cutánea, sin sistemas de succión.',
	);

	return $descriptions[ $slug ] ?? $description;
}
add_filter( 'wpseo_metadesc', 'nvx_btl_govern_metadescription', 99 );
