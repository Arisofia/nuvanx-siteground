<?php
/**
 * Clinical governance for BTL detail pages.
 *
 * Keeps manufacturer-supported device information visible while preventing
 * those data from being presented as universal patient outcomes. Comparative,
 * quantitative, pain and recovery statements remain qualified until evidence
 * sign-off.
 *
 * High-risk product strings live in nvx_btl_claim_library() so registry copy
 * and late rewrites share one source of truth.
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

	$slug = function_exists( 'nvx_theme_current_page_slug' )
		? nvx_theme_current_page_slug()
		: (string) get_post_field( 'post_name', get_queried_object_id() );

	return in_array( $slug, nvx_btl_governed_slugs(), true );
}

/**
 * Shared claim library: source text for page registry + governed public rewrite.
 *
 * Source strings are retained only to neutralize legacy cached HTML. New public
 * registry output must use the governed version of each claim.
 *
 * @return array<string, array{source:string, governed:string}>
 */
function nvx_btl_claim_library(): array {
	static $library = null;
	if ( null !== $library ) {
		return $library;
	}

	$library = array(
		// EXION Face — product depth (authoritative for registry + gate).
		'exion_face_mech_intro'  => array(
			'source'   => 'EXION Face combina radiofrecuencia monopolar y ultrasonido terapéutico orientados a estimular fibroblastos y matriz extracelular, sin basarse en picos térmicos de 60–70 °C típicos de algunas plataformas de contracción intensa.',
			'governed' => 'EXION Face combina radiofrecuencia monopolar y ultrasonido terapéutico orientados a estimular fibroblastos y matriz extracelular. La comparación con plataformas de mayor pico térmico depende del aplicador, los parámetros y la indicación.',
		),
		'exion_face_ha_224'      => array(
			'source'   => 'La documentación del fabricante comunica, en modelos evaluados, un aumento de hasta ~224% en marcadores de ácido hialurónico endógeno a ~4 semanas. Ese dato es de laboratorio/protocolo evaluado y no equivale a un resultado individual garantizado.',
			'governed' => 'La documentación del fabricante describe cambios en marcadores de matriz cutánea en modelos evaluados. La evidencia aplicable, la indicación y la respuesta clínica deben valorarse de forma individual; no se comunica como porcentaje ni como resultado garantizado.',
		),
		'exion_face_compare'     => array(
			'source'   => 'HIFU y RF volumétrica de alto pico buscan contracción por desnaturalización intensa (picos frecuentemente citados ~60–70 °C). EXION Face prioriza regeneración a microtemperaturas más fisiológicas (~40–42 °C en protocolos evaluados), con perfil de tolerancia y downtime habitualmente más favorables. Temperatura, dolor, atrofia y “porcentajes de HA” no son transferibles 1:1 entre pacientes ni entre estudios. La comparativa clínica ampliada está en el Journal.',
			'governed' => 'Las tecnologías energéticas se seleccionan por mecanismo, zona, fototipo, antecedentes, objetivo y período de recuperación aceptable. La indicación no se establece por una comparación comercial entre marcas.',
		),
		// EXION Body — product depth.
		'exion_body_btl_22'      => array(
			'source'   => 'BTL comunica, en series evaluadas, órdenes de magnitud del tipo hasta −22% de adiposidad y mejoras relevantes de laxitud. Son datos de condiciones de estudio; en NUVANX se individualizan por espesor, zona y calidad de piel.',
			'governed' => 'La documentación técnica describe cambios en adiposidad en series evaluadas. No se publica un porcentaje porque depende de población, zona, protocolo y evaluación clínica, y no constituye un resultado individual garantizado.',
		),
		'exion_body_compare'     => array(
			'source'   => 'Criolipólisis reduce grasa localizada pero no tensa. Microagujas corporales tensan con más trauma y downtime. EXION Body busca grasa + calidad cutánea con mejor tolerancia en muchos protocolos. Frente a liposucción quirúrgica: menos invasivo, menos downtime, pero tampoco sustituye una cirugía mayor cuando el exceso es muy importante. Detalle y matices en el Journal y en la página de endoláser.',
			'governed' => 'Los procedimientos para contorno corporal tienen mecanismos, límites y períodos de recuperación distintos. La elección se realiza tras explorar grasa localizada, calidad cutánea, exceso de piel y expectativas; una tecnología no sustituye una cirugía cuando ésta está indicada.',
		),
		// Neutral surface-cooling description (no unverified “lower burn risk” claim).
		'exion_body_cooling'     => array(
			'source'   => 'Protege la epidermis mientras la RF trabaja en planos más profundos. El control de superficie forma parte del diseño del aplicador; el perfil de seguridad depende de parámetros, zona y técnica, y no elimina por sí solo el riesgo de efectos térmicos.',
			'governed' => 'Protege la epidermis mientras la RF trabaja en planos más profundos. El control de superficie forma parte del diseño del aplicador; el perfil de seguridad depende de parámetros, zona y técnica, y no elimina por sí solo el riesgo de efectos térmicos.',
		),
		// Legacy / residual strings (rewrite-only if older HTML remains).
		'legacy_face_mech'       => array(
			'source'   => 'EXION Face combina radiofrecuencia monopolar y ultrasonido terapéutico en un protocolo orientado a estimular fibroblastos y matriz extracelular, no a necrosar tejido con picos térmicos de 60–70 °C.',
			'governed' => 'EXION Face combina radiofrecuencia monopolar y ultrasonido terapéutico en un protocolo orientado a estimular fibroblastos y matriz extracelular sin recurrir a un daño térmico agresivo. La comparación con otras plataformas depende del aplicador, los parámetros y la indicación.',
		),
		'legacy_face_ha'         => array(
			'source'   => 'Documentación del fabricante describe, en modelos evaluados, incrementos de marcadores de ácido hialurónico endógeno del orden del 224% a ~4 semanas; en consulta se presentan como potencial de estimulación, no como promesa personalizada.',
			'governed' => 'La documentación del fabricante describe cambios en marcadores de matriz cutánea en modelos evaluados. La evidencia aplicable y la respuesta clínica deben valorarse de forma individual; no se comunica como porcentaje ni como resultado garantizado.',
		),
		'legacy_face_compare'    => array(
			'source'   => 'HIFU y RF volumétrica de alto pico buscan contracción por desnaturalización intensa. EXION Face prioriza regeneración a temperaturas más fisiológicas, con mejor tolerancia y menor downtime. La comparativa clínica ampliada está en el blog médico.',
			'governed' => 'Las tecnologías energéticas se seleccionan por mecanismo, zona, fototipo, antecedentes, objetivo y período de recuperación aceptable. La indicación no se establece por una comparación comercial entre marcas.',
		),
		'legacy_body_22'         => array(
			'source'   => 'Documentación BTL comunica órdenes de magnitud del tipo −22% adiposidad y mejoras relevantes de laxitud en series evaluadas; en NUVANX se individualiza por espesor graso, zona y calidad de piel.',
			'governed' => 'La documentación técnica describe cambios en adiposidad en series evaluadas. No se publica un porcentaje porque depende de población, zona, protocolo y evaluación clínica, y no constituye un resultado individual garantizado.',
		),
		'legacy_body_compare'    => array(
			'source'   => 'La criolipólisis reduce grasa pero no tensa. Las microagujas corporales tensan con más trauma y downtime. EXION Body busca ambos efectos con mejor tolerancia. Detalle comparativo en el blog.',
			'governed' => 'Criolipólisis, radiofrecuencia con microagujas y EXION Body tienen mecanismos y perfiles de recuperación diferentes. EXION Body puede ofrecer mejor tolerancia o menor recuperación en determinados protocolos, sin convertir esa diferencia en superioridad universal.',
		),
		'legacy_body_needles'    => array(
			'source'   => 'No es un sistema de perforación con agujas largas; el eritema, si aparece, suele resolverse en horas.',
			'governed' => 'No utiliza microagujas. El eritema, cuando aparece, puede resolverse en horas según materiales del fabricante y experiencia clínica, pero su intensidad y duración varían.',
		),
		// Soften residual risk phrasing if older cached HTML still emits it.
		'legacy_body_burn_risk'  => array(
			'source'   => 'Protege la epidermis mientras la RF trabaja en planos más profundos. Reduce el riesgo de quemadura superficial respecto a RF sin control de superficie adecuado.',
			'governed' => 'Protege la epidermis mientras la RF trabaja en planos más profundos. El control de superficie forma parte del diseño del aplicador; el perfil de seguridad depende de parámetros, zona y técnica, y no elimina por sí solo el riesgo de efectos térmicos.',
		),
		'frac_lead_legacy'       => array(
			'source'   => 'Microagujas más cortas y gradiente térmico extendido con feedback de tejido — textura, poros y cicatrices con menos pasadas y downtime más predecible que la RF fraccionada “a ciegas”.',
			'governed' => 'EXION Fractional RF utiliza microagujas, control de impedancia y feedback tisular. Esa información permite explicar diferencias frente a sistemas sin retroalimentación en tiempo real, sin afirmar de forma universal menos dolor, menos pases o mejor recuperación.',
		),
		'frac_needles_legacy'    => array(
			'source'   => 'Agujas más cortas con proyección térmica extendida permiten alcanzar profundidad de trabajo relevante reduciendo trauma mecánico superficial respecto a protocolos de aguja larga multipasada.',
			'governed' => 'La geometría de las microagujas y la proyección térmica pueden reducir el componente mecánico frente a determinados protocolos multipasada. La magnitud de esa diferencia depende del dispositivo, los parámetros y la técnica.',
		),
		'frac_tolerate_title'    => array(
			'source'   => 'Pacientes que no toleran multipasada agresiva',
			'governed' => 'Pacientes con antecedentes de baja tolerancia a protocolos multipasada',
		),
		'frac_hematoma_legacy'   => array(
			'source'   => 'Historial de RF fraccionada con hematomas prolongados o abandono por dolor: se reevalúa energía y número de pases.',
			'governed' => 'Ante tratamientos previos con dolor, hematomas o recuperación prolongada se revisan energía, analgesia, número de pases y alternativas.',
		),
		'frac_downtime_legacy'   => array(
			'source'   => 'Downtime típico: eritema 12–48 h según energía; se explica antes de firmar el plan.',
			'governed' => 'Puede comunicarse un rango orientativo de eritema de 12–48 horas cuando corresponda al protocolo utilizado. No constituye una recuperación garantizada y puede variar con parámetros y respuesta individual.',
		),
		'frac_single_pass'       => array(
			'source'   => 'El diseño single-pass reduce pasadas innecesarias cuando el feedback de tejido es adecuado; el médico puede modular según zona.',
			'governed' => 'El diseño single-pass y el feedback de impedancia pueden reducir pasadas adicionales en protocolos seleccionados. El profesional decide el número de pases según zona, respuesta y objetivo.',
		),
		'emfusion_h1_legacy'     => array(
			'source'   => 'EMFUSION® en Madrid: infusión cutánea y restauración de barrera sin succión agresiva',
			'governed' => 'EMFUSION® en Madrid: infusión cutánea y apoyo a la barrera sin sistemas de succión',
		),
		'emfusion_lead_legacy'   => array(
			'source'   => 'Tecnología DYNAMiQ™ de microcanales acústicos para favorecer la penetración de activos y apoyar la homeostasis epidérmica — alternativa a vórtices de succión y microneedling cuando la barrera está comprometida.',
			'governed' => 'Tecnología DYNAMiQ™ de microcanales acústicos para favorecer la aplicación de activos y apoyar hidratación y barrera cutánea. Puede compararse con sistemas de succión o microneedling por mecanismo y tolerancia, sin calificarlos de forma denigratoria.',
		),
	);

	return $library;
}

/**
 * Translate a claim library string (literals live in nvx_btl_claim_library for extraction).
 *
 * phpcs:disable WordPress.WP.I18n.NonSingularStringLiteralText -- msgids are centralized claim literals.
 */
function nvx_btl_claim_translate( string $text ): string {
	if ( '' === $text ) {
		return '';
	}
	return __( $text, 'nuvanx-medical' );
}

/**
 * Source (registry) text for a claim id — localized.
 */
function nvx_btl_claim_source( string $id ): string {
	$library = nvx_btl_claim_library();
	$raw     = isset( $library[ $id ]['source'] ) ? (string) $library[ $id ]['source'] : '';
	return nvx_btl_claim_translate( $raw );
}

/** Return approved public wording for a governed claim. */
function nvx_btl_claim_governed( string $id ): string {
	$library = nvx_btl_claim_library();
	$raw     = isset( $library[ $id ]['governed'] ) ? (string) $library[ $id ]['governed'] : '';
	return nvx_btl_claim_translate( $raw );
}

/**
 * Safe claim lookup for registry builders (empty when id missing or helper unavailable).
 */
function nvx_btl_claim( string $id ): string {
	if ( ! function_exists( 'nvx_btl_claim_governed' ) ) {
		return '';
	}
	return nvx_btl_claim_governed( $id );
}

/**
 * Build strtr map from the claim library (skips identical source/governed pairs).
 * Cached per locale so switch_to_locale() / mid-request locale changes stay correct.
 *
 * @return array<string, string>
 */
function nvx_btl_claim_replacement_map(): array {
	static $maps = array();

	$locale = function_exists( 'determine_locale' )
		? determine_locale()
		: ( function_exists( 'get_locale' ) ? get_locale() : 'default' );
	if ( ! is_string( $locale ) || '' === $locale ) {
		$locale = 'default';
	}

	if ( isset( $maps[ $locale ] ) ) {
		return $maps[ $locale ];
	}

	$map = array();
	foreach ( nvx_btl_claim_library() as $pair ) {
		$from_raw = (string) ( $pair['source'] ?? '' );
		$to_raw   = (string) ( $pair['governed'] ?? '' );
		if ( '' === $from_raw || '' === $to_raw || $from_raw === $to_raw ) {
			continue;
		}
		// Match both raw and translated source (registry uses localized source).
		$from_l10n = nvx_btl_claim_translate( $from_raw );
		$to_l10n   = nvx_btl_claim_translate( $to_raw );
		$map[ $from_raw ] = $to_l10n;
		if ( $from_l10n !== $from_raw ) {
			$map[ $from_l10n ] = $to_l10n;
		}
	}

	$maps[ $locale ] = $map;
	return $map;
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

	$governed = strtr( $content, nvx_btl_claim_replacement_map() );

	$governed = preg_replace_callback(
		'/<details\b[^>]*>[\s\S]*?<\/details>/iu',
		static function ( array $matches ): string {
			return preg_match( '/\b(?:Morpheus8|Potenza|CoolSculpting|HIFU|Thermage|Ultherapy|Hydrafacial|Dermapen)\b/iu', $matches[0] ) ? '' : $matches[0];
		},
		$governed
	) ?? $governed;

	$notice = '<aside class="nvx-clinical-note nvx-btl-evidence-note" role="note"><h2 class="nvx-clinical-note__title">Datos técnicos y variabilidad clínica</h2><p class="nvx-clinical-note__text">Los datos técnicos requieren contexto clínico y no equivalen a un resultado individual. La indicación, los parámetros y la respuesta dependen del equipo, el aplicador, la zona y el paciente.</p></aside>';

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

	$slug = function_exists( 'nvx_theme_current_page_slug' )
		? nvx_theme_current_page_slug()
		: (string) get_post_field( 'post_name', get_queried_object_id() );

	$descriptions = array(
		'exion-face'       => 'EXION® Face en NUVANX Madrid: RF y ultrasonido a microtemperaturas controladas para calidad cutánea. Valoración médica en Chamberí y Goya.',
		'exion-body'       => 'EXION® Body en NUVANX Madrid: radiofrecuencia con refrigeración activa para grasa localizada y calidad cutánea, según valoración médica.',
		'exion-fractional' => 'EXION® Fractional RF en Madrid: microagujas con control de impedancia para textura, poro y cicatrices según diagnóstico y fototipo.',
		'emfusion'         => 'EMFUSION® en NUVANX Madrid: microcanales acústicos DYNAMiQ™ para hidratación y apoyo a la barrera cutánea, sin sistemas de succión.',
	);

	return $descriptions[ $slug ] ?? $description;
}
add_filter( 'wpseo_metadesc', 'nvx_btl_govern_metadescription', 99 );
