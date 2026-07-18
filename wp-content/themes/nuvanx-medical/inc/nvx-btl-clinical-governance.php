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
		// EXION Face — product depth (2026-07)
		'EXION Face combina radiofrecuencia monopolar y ultrasonido terapéutico orientados a estimular fibroblastos y matriz extracelular, sin basarse en picos térmicos de 60–70 °C típicos de algunas plataformas de contracción intensa.' => 'EXION Face combina radiofrecuencia monopolar y ultrasonido terapéutico orientados a estimular fibroblastos y matriz extracelular. La comparación con plataformas de mayor pico térmico depende del aplicador, los parámetros y la indicación.',
		'La documentación del fabricante comunica, en modelos evaluados, un aumento de hasta ~224% en marcadores de ácido hialurónico endógeno a ~4 semanas. Ese dato es de laboratorio/protocolo evaluado y no equivale a un resultado individual garantizado.' => 'La documentación del fabricante comunica un aumento de hasta ~224% en marcadores de ácido hialurónico endógeno en modelos evaluados a ~4 semanas. El dato debe interpretarse según diseño, población y seguimiento del estudio, y no equivale a una mejora clínica garantizada para cada paciente.',
		'HIFU y RF volumétrica de alto pico buscan contracción por desnaturalización intensa (picos frecuentemente citados ~60–70 °C). EXION Face prioriza regeneración a microtemperaturas más fisiológicas (~40–42 °C en protocolos evaluados), con perfil de tolerancia y downtime habitualmente más favorables. Temperatura, dolor, atrofia y “porcentajes de HA” no son transferibles 1:1 entre pacientes ni entre estudios. La comparativa clínica ampliada está en el Journal.' => 'HIFU, radiofrecuencia volumétrica y EXION Face utilizan mecanismos, profundidades y perfiles de recuperación distintos. EXION Face puede ofrecer mejor tolerancia o menor recuperación en determinados protocolos; esa comparación no es universal y debe sostenerse en evidencia aplicable a cada indicación.',
		// EXION Body — product depth (2026-07)
		'BTL comunica, en series evaluadas, órdenes de magnitud del tipo hasta −22% de adiposidad y mejoras relevantes de laxitud. Son datos de condiciones de estudio; en NUVANX se individualizan por espesor, zona y calidad de piel.' => 'BTL comunica hasta −22% de reducción de adiposidad en series evaluadas con EXION Body. El porcentaje pertenece a condiciones de estudio concretas y no debe trasladarse como resultado individual garantizado.',
		'Criolipólisis reduce grasa localizada pero no tensa. Microagujas corporales tensan con más trauma y downtime. EXION Body busca grasa + calidad cutánea con mejor tolerancia en muchos protocolos. Frente a liposucción quirúrgica: menos invasivo, menos downtime, pero tampoco sustituye una cirugía mayor cuando el exceso es muy importante. Detalle y matices en el Journal y en la página de endoláser.' => 'Criolipólisis, radiofrecuencia con microagujas y EXION Body tienen mecanismos y perfiles de recuperación diferentes. EXION Body puede ofrecer mejor tolerancia o menor recuperación en determinados protocolos, sin convertir esa diferencia en superioridad universal ni sustituir una cirugía mayor cuando el exceso lo exige.',
		// Legacy strings (kept if older markup remains cached).
		'EXION Face combina radiofrecuencia monopolar y ultrasonido terapéutico en un protocolo orientado a estimular fibroblastos y matriz extracelular, no a necrosar tejido con picos térmicos de 60–70 °C.' => 'EXION Face combina radiofrecuencia monopolar y ultrasonido terapéutico en un protocolo orientado a estimular fibroblastos y matriz extracelular sin recurrir a un daño térmico agresivo. La comparación con otras plataformas depende del aplicador, los parámetros y la indicación.',
		'Documentación del fabricante describe, en modelos evaluados, incrementos de marcadores de ácido hialurónico endógeno del orden del 224% a ~4 semanas; en consulta se presentan como potencial de estimulación, no como promesa personalizada.' => 'La documentación del fabricante comunica un aumento de hasta 224% en marcadores de ácido hialurónico endógeno en modelos evaluados. El dato debe interpretarse según el diseño, la población y el seguimiento del estudio, y no equivale a una mejora clínica garantizada para cada paciente.',
		'HIFU y RF volumétrica de alto pico buscan contracción por desnaturalización intensa. EXION Face prioriza regeneración a temperaturas más fisiológicas, con mejor tolerancia y menor downtime. La comparativa clínica ampliada está en el blog médico.' => 'HIFU, radiofrecuencia volumétrica y EXION Face utilizan mecanismos, profundidades y perfiles de recuperación distintos. EXION Face puede ofrecer mejor tolerancia o menor recuperación en determinados protocolos, pero esa comparación no es universal y debe sostenerse en evidencia aplicable a cada indicación.',
		'Documentación BTL comunica órdenes de magnitud del tipo −22% adiposidad y mejoras relevantes de laxitud en series evaluadas; en NUVANX se individualiza por espesor graso, zona y calidad de piel.' => 'BTL comunica hasta −22% de reducción de adiposidad en series evaluadas con EXION Body. El porcentaje pertenece a condiciones de estudio concretas y no debe trasladarse como resultado individual garantizado.',
		'La criolipólisis reduce grasa pero no tensa. Las microagujas corporales tensan con más trauma y downtime. EXION Body busca ambos efectos con mejor tolerancia. Detalle comparativo en el blog.' => 'Criolipólisis, radiofrecuencia con microagujas y EXION Body tienen mecanismos y perfiles de recuperación diferentes. EXION Body puede ofrecer mejor tolerancia o menor recuperación en determinados protocolos, sin convertir esa diferencia en superioridad universal.',
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
