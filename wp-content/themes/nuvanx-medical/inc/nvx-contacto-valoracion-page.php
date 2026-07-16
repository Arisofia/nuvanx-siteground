<?php
/**
 * Contacto (NAP) + Valoración (diagnóstico + form) content layer.
 *
 * Funnel split:
 * - /madrid/valoracion/ → clinical intro + triple validation + form primary
 * - /contacto/ → clinics NAP + phones + GDPR note + link to valoración (no form)
 *
 * No videoconsulta CTA (not operational as marketed). Preliminary photo
 * orientation is only mentioned under GDPR disclaimer, not as a booking product.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether this is the valoración landing (form funnel).
 */
function nvx_is_valoracion_page_request(): bool {
	if ( function_exists( 'nvx_theme_is_valoracion_landing' ) && nvx_theme_is_valoracion_landing() ) {
		return true;
	}

	if ( ! is_singular( 'page' ) ) {
		return false;
	}

	$path = function_exists( 'nvx_schema_current_path' )
		? nvx_schema_current_path( (int) get_queried_object_id() )
		: '';

	return is_string( $path ) && (
		false !== strpos( $path, '/valoracion/' )
		|| false !== strpos( $path, 'madrid/valoracion' )
	);
}

/**
 * Whether this is the contacto / NAP page.
 */
function nvx_is_contacto_page_request(): bool {
	if ( ! is_singular( 'page' ) || is_front_page() ) {
		return false;
	}

	if ( 'templates/page-contacto.php' === (string) get_page_template_slug() ) {
		return true;
	}

	$slug = (string) get_post_field( 'post_name', get_queried_object_id() );
	if ( 'contacto' === $slug || 'contact' === $slug ) {
		return true;
	}

	$path = function_exists( 'nvx_schema_current_path' )
		? nvx_schema_current_path( (int) get_queried_object_id() )
		: '';

	return is_string( $path ) && (
		nvx_schema_path_matches( $path, '/contacto/' )
		|| false !== strpos( $path, '/contacto/' )
	);
}

/**
 * Clinic NAP rows (shared with schema clinics when available).
 *
 * @return array<int, array{name:string,reg:string,address:string,phone:string,phone_href:string,days:string}>
 */
function nvx_contact_clinics_nap(): array {
	return array(
		array(
			'name'       => 'Centro Clínico NUVANX Chamberí',
			'reg'        => 'CS20144',
			'address'    => 'Calle de Fernández de la Hoz, 4, Bajo Derecha, 28010, Madrid',
			'phone'      => '669 319 836',
			'phone_href' => '+34669319836',
			'days'       => 'Martes y jueves',
		),
		array(
			'name'       => 'Centro Clínico NUVANX Salamanca / Goya',
			'reg'        => 'CS20073',
			'address'    => 'Calle de Fernán González, 26, 28009, Madrid',
			'phone'      => '647 505 107',
			'phone_href' => '+34647505107',
			'days'       => 'Miércoles',
		),
	);
}

/**
 * Triple validation steps for valoración.
 *
 * @return array<int, array{title:string,body:string}>
 */
function nvx_valoracion_process_steps(): array {
	return array(
		array(
			'title' => __( 'Escucha activa', 'nuvanx-medical' ),
			'body'  => __( 'Análisis de historial clínico, cirugías previas y expectativas estéticas reales.', 'nuvanx-medical' ),
		),
		array(
			'title' => __( 'Evaluación anatómica in situ', 'nuvanx-medical' ),
			'body'  => __( 'Exploración de calidad dérmica, grado de ptosis y distribución de grasa localizada.', 'nuvanx-medical' ),
		),
		array(
			'title' => __( 'Planificación médica y presupuestaria', 'nuvanx-medical' ),
			'body'  => __( 'Protocolo individualizado y presupuesto cerrado, transparente, sin costes ocultos.', 'nuvanx-medical' ),
		),
	);
}

/**
 * GDPR / photo disclaimer (no definitive remote diagnosis).
 */
function nvx_contact_privacy_disclaimer_markup(): string {
	return '<p class="nvx-contact-disclaimer"><em>' . esc_html__(
		'Privacidad: si adjunta material fotográfico para una orientación preliminar, se trata bajo protocolos de confidencialidad clínica (GDPR). Ningún diagnóstico definitivo se emite solo a partir de una evaluación fotográfica; la indicación se confirma en valoración presencial.',
		'nuvanx-medical'
	) . '</em></p>';
}

/**
 * Clinics NAP cards markup.
 */
function nvx_contact_clinics_markup(): string {
	$html  = '<div class="nvx-contact-clinics">';
	foreach ( nvx_contact_clinics_nap() as $clinic ) {
		$html .= '<article class="nvx-contact-clinic">';
		$html .= '<h3 class="nvx-contact-clinic__name">' . esc_html( $clinic['name'] ) . '</h3>';
		$html .= '<p class="nvx-contact-clinic__reg"><strong>' . esc_html__( 'Registro sanitario', 'nuvanx-medical' ) . ':</strong> ' . esc_html( $clinic['reg'] ) . '</p>';
		$html .= '<p class="nvx-contact-clinic__addr">' . esc_html( $clinic['address'] ) . '</p>';
		$html .= '<p class="nvx-contact-clinic__phone"><strong>' . esc_html__( 'Teléfono / WhatsApp', 'nuvanx-medical' ) . ':</strong> ';
		$html .= '<a class="nvx-brand-inline-link" href="' . esc_url( 'tel:' . $clinic['phone_href'] ) . '">' . esc_html( $clinic['phone'] ) . '</a></p>';
		$html .= '<p class="nvx-contact-clinic__days"><strong>' . esc_html__( 'Consulta médica directa', 'nuvanx-medical' ) . ':</strong> ' . esc_html( $clinic['days'] ) . '</p>';
		$html .= '</article>';
	}
	$html .= '</div>';

	return $html;
}

/**
 * Valoración clinical intro (form stays separate / primary via form-first filter).
 */
function nvx_valoracion_intro_markup(): string {
	$html  = '<section class="nvx-endolift-section nvx-valoracion-intro" id="nvx-valoracion-intro" aria-labelledby="nvx-valoracion-intro-title">';
	$html .= '<div class="nvx-endolift-section__inner">';
	$html .= '<p class="nvx-endolift-kicker">' . esc_html__( 'Primer paso', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-valoracion-intro-title" class="nvx-endolift-heading">' . esc_html__( 'Diagnóstico tisular gratuito y sin compromiso', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p class="nvx-endolift-body nvx-endolift-body--measure">' . esc_html__( 'La excelencia en medicina láser exige un diagnóstico diferencial preciso, no una transacción comercial. NUVANX ofrece una valoración médica estética gratuita de 15 a 30 minutos, de forma presencial en Chamberí o Goya · Barrio Salamanca.', 'nuvanx-medical' ) . '</p>';
	$html .= '<p class="nvx-endolift-body nvx-endolift-body--measure">' . esc_html__( 'El equipo clínico, bajo la dirección del Dr. Rivera Tejeda, aplica un proceso de triple validación:', 'nuvanx-medical' ) . '</p>';
	$html .= '<ol class="nvx-co2-timeline nvx-valoracion-steps">';
	$n = 1;
	foreach ( nvx_valoracion_process_steps() as $step ) {
		$html .= '<li class="nvx-co2-timeline__item">';
		$html .= '<span class="nvx-co2-timeline__n">' . esc_html( sprintf( '%02d', $n ) ) . '</span>';
		$html .= '<h3 class="nvx-co2-timeline__title">' . esc_html( $step['title'] ) . '</h3>';
		$html .= '<p class="nvx-endolift-body">' . esc_html( $step['body'] ) . '</p>';
		$html .= '</li>';
		$n++;
	}
	$html .= '</ol>';
	$html .= nvx_contact_privacy_disclaimer_markup();
	$html .= '</div></section>';

	// Compact NAP under process (phones secondary; form is primary CTA).
	$html .= '<section class="nvx-endolift-section nvx-valoracion-locations" aria-labelledby="nvx-valoracion-loc-title">';
	$html .= '<div class="nvx-endolift-section__inner">';
	$html .= '<p class="nvx-endolift-kicker">' . esc_html__( 'Sedes', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-valoracion-loc-title" class="nvx-endolift-heading">' . esc_html__( 'Ubicaciones autorizadas por Sanidad', 'nuvanx-medical' ) . '</h2>';
	$html .= nvx_contact_clinics_markup();
	$html .= '</div></section>';

	return $html;
}

/**
 * Contacto page: NAP + link to valoración (no clinical form dump).
 */
function nvx_contacto_page_markup(): string {
	$valoracion = function_exists( 'nvx_cta_valoracion_url' )
		? nvx_cta_valoracion_url()
		: home_url( '/madrid/valoracion/' );

	$html  = '<div class="nvx-contacto-editorial nvx-endolift-editorial">';
	$html .= '<section class="nvx-endolift-section" aria-labelledby="nvx-contacto-h2">';
	$html .= '<div class="nvx-endolift-section__inner">';
	$html .= '<p class="nvx-endolift-kicker">' . esc_html__( 'Contacto', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-contacto-h2" class="nvx-endolift-heading">' . esc_html__( 'Contacto directo y ubicaciones autorizadas por Sanidad', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p class="nvx-endolift-body nvx-endolift-body--measure">' . esc_html__( 'Para diagnóstico y plan de tratamiento, reserve la valoración médica gratuita (15–30 min) en Chamberí o Goya. Esta página es el directorio NAP de sedes y teléfonos.', 'nuvanx-medical' ) . '</p>';
	$html .= nvx_contact_clinics_markup();
	$html .= nvx_contact_privacy_disclaimer_markup();
	$html .= '<p class="nvx-contacto-cta"><a class="nvx-brand-btn nvx-brand-btn--primary" href="' . esc_url( $valoracion ) . '">' . esc_html__( 'Ir a valoración médica gratuita', 'nuvanx-medical' ) . '</a></p>';
	$html .= '</div></section></div>';

	return $html;
}

/**
 * Inject valoración intro without removing the HubSpot form.
 */
function nvx_content_enhance_valoracion_page( string $content ): string {
	if ( is_admin() || ! nvx_is_valoracion_page_request() ) {
		return $content;
	}

	if ( false !== strpos( $content, 'nvx-valoracion-intro' ) || false !== strpos( $content, 'id="nvx-valoracion-intro"' ) ) {
		return $content;
	}

	$intro = nvx_valoracion_intro_markup();

	// After hero section if present.
	if ( preg_match( '/(<section\b[^>]*class=["\'][^"\']*nvx-(?:hero|page-hero|brand-hero)[^"\']*["\'][^>]*>[\s\S]*?<\/section>)/iu', $content, $m, PREG_OFFSET_CAPTURE ) ) {
		$end = (int) $m[0][1] + strlen( $m[0][0] );
		return substr( $content, 0, $end ) . $intro . substr( $content, $end );
	}

	// Before form section.
	if ( preg_match( '/<section\b[^>]*(?:\bid=["\']nvx-hubspot-form["\']|nvx-hubspot-form-section|nvx-form-stage)[^>]*>/iu', $content, $m, PREG_OFFSET_CAPTURE ) ) {
		$pos = (int) $m[0][1];
		return substr( $content, 0, $pos ) . $intro . substr( $content, $pos );
	}

	return $intro . $content;
}
add_filter( 'the_content', 'nvx_content_enhance_valoracion_page', 16 );

/**
 * Contacto: NAP editorial; strip embedded HubSpot forms (form lives on valoración).
 */
function nvx_content_restructure_contacto_page( string $content ): string {
	if ( is_admin() || ! nvx_is_contacto_page_request() ) {
		return $content;
	}

	if ( false !== strpos( $content, 'nvx-contacto-editorial' ) ) {
		return $content;
	}

	// Drop forms / shortcodes that belong on valoración.
	$content = preg_replace(
		'/<section\b[^>]*(?:nvx-hubspot-form|hubspot)[^>]*>[\s\S]*?<\/section>/iu',
		'',
		$content
	) ?? $content;
	$content = preg_replace( '/\[hubspot[^\]]*\]/iu', '', $content ) ?? $content;

	$block = nvx_contacto_page_markup();

	if ( preg_match( '/(<section\b[^>]*class=["\'][^"\']*nvx-(?:hero|page-hero|brand-hero)[^"\']*["\'][^>]*>[\s\S]*?<\/section>)/iu', $content, $m, PREG_OFFSET_CAPTURE ) ) {
		$end = (int) $m[0][1] + strlen( $m[0][0] );
		return substr( $content, 0, $end ) . $block;
	}

	if ( preg_match( '/(<div class="nvx-brand-page[^"]*"[^>]*>)/iu', $content, $wrap ) ) {
		return $wrap[1] . $block . '</div>';
	}

	return $block;
}
add_filter( 'the_content', 'nvx_content_restructure_contacto_page', 17 );

/**
 * Yoast title for valoración.
 *
 * @param string $title Title.
 * @return string
 */
function nvx_filter_valoracion_document_title( $title ) {
	if ( ! nvx_is_valoracion_page_request() ) {
		return $title;
	}

	return 'Valoración Médica Gratuita en Madrid | Diagnóstico y Presupuesto | NUVANX';
}
add_filter( 'wpseo_title', 'nvx_filter_valoracion_document_title', 21 );

/**
 * @param string $desc Description.
 * @return string
 */
function nvx_filter_valoracion_metadesc( $desc ) {
	if ( ! nvx_is_valoracion_page_request() ) {
		return $desc;
	}

	return 'Valoración médica estética gratuita 15–30 min en Chamberí o Goya. Diagnóstico diferencial, plan y presupuesto cerrado. Sin compromiso. NUVANX Madrid.';
}
add_filter( 'wpseo_metadesc', 'nvx_filter_valoracion_metadesc', 21 );

/**
 * Yoast title for contacto.
 *
 * @param string $title Title.
 * @return string
 */
function nvx_filter_contacto_document_title( $title ) {
	if ( ! nvx_is_contacto_page_request() ) {
		return $title;
	}

	return 'Contacto NUVANX Madrid | Chamberí y Goya · Teléfonos y Direcciones';
}
add_filter( 'wpseo_title', 'nvx_filter_contacto_document_title', 21 );

/**
 * @param string $desc Description.
 * @return string
 */
function nvx_filter_contacto_metadesc( $desc ) {
	if ( ! nvx_is_contacto_page_request() ) {
		return $desc;
	}

	return 'Contacto NUVANX: Chamberí CS20144 (669 319 836) y Goya CS20073 (647 505 107). Valoración médica en /madrid/valoracion/.';
}
add_filter( 'wpseo_metadesc', 'nvx_filter_contacto_metadesc', 21 );
