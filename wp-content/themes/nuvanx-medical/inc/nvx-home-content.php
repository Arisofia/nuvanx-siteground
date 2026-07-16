<?php
/**
 * Home content enhancements (front page only).
 * Structured values, unified CTAs, E-E-A-T director block, GEO treatment copy,
 * method columns, and FAQ framing.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** ICOMEM — public professional registry (Doctoralia / clinic communications). */
if ( ! defined( 'NVX_DIRECTOR_COLEGIADO' ) ) {
	define( 'NVX_DIRECTOR_COLEGIADO', '282864786' );
}

/**
 * @return string
 */
function nvx_home_valoracion_url(): string {
	return home_url( '/madrid/valoracion/' );
}

/**
 * @return string
 */
function nvx_home_whatsapp_url(): string {
	return 'https://wa.me/34669319836';
}

/**
 * Primary CTA markup.
 */
function nvx_home_cta_primary( string $class = 'nvx-brand-btn nvx-brand-btn--primary' ): string {
	return sprintf(
		'<a class="%1$s" href="%2$s">%3$s</a>',
		esc_attr( $class ),
		esc_url( nvx_home_valoracion_url() ),
		esc_html__( 'Reservar valoración gratuita', 'nuvanx-medical' )
	);
}

/**
 * Secondary WhatsApp CTA markup.
 */
function nvx_home_cta_whatsapp( string $class = 'nvx-brand-btn nvx-brand-btn--secondary' ): string {
	return sprintf(
		'<a class="%1$s" href="%2$s" target="_blank" rel="noopener noreferrer">%3$s</a>',
		esc_attr( $class ),
		esc_url( nvx_home_whatsapp_url() ),
		esc_html__( 'Contactar por WhatsApp', 'nuvanx-medical' )
	);
}

/**
 * Minimal inline icons (stroke, currentColor).
 *
 * @param string $name shield|laser|nature|scan|precision|follow
 */
function nvx_home_icon_svg( string $name ): string {
	$icons = array(
		'shield'    => '<svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M24 6 10 12v10c0 10.5 5.8 16.8 14 20 8.2-3.2 14-9.5 14-20V12L24 6Z" stroke="currentColor" stroke-width="1.6"/><path d="M24 16v14M18 23h12" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>',
		'laser'     => '<svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><circle cx="24" cy="24" r="5" stroke="currentColor" stroke-width="1.6"/><path d="M24 6v8M24 34v8M6 24h8M34 24h8M11 11l5.5 5.5M31.5 31.5 37 37M37 11l-5.5 5.5M16.5 31.5 11 37" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>',
		'nature'    => '<svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M24 40V22" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><path d="M24 34c-8 0-14-5-14-12 8 0 14 5 14 12Z" stroke="currentColor" stroke-width="1.6"/><path d="M24 30c8 0 14-5 14-12-8 0-14 5-14 12Z" stroke="currentColor" stroke-width="1.6"/><path d="M16 14c4-4 8-6 12-6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>',
		'scan'      => '<svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M10 16V10h6M32 10h6v6M38 32v6h-6M16 38H10v-6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><circle cx="24" cy="24" r="7" stroke="currentColor" stroke-width="1.6"/><path d="M8 24h32" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>',
		'precision' => '<svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M8 30 24 8l16 22" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M14 30h20" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><path d="M18 36h12M21 42h6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>',
		'follow'    => '<svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M8 32c6-10 10-14 16-14s10 4 16 14" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><path d="M12 20c3-2 6-3 12-3s9 1 12 3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><circle cx="24" cy="28" r="3" stroke="currentColor" stroke-width="1.6"/></svg>',
	);

	return $icons[ $name ] ?? $icons['shield'];
}

/**
 * PART 1 — structured clinical values (replaces intro prose).
 */
function nvx_home_values_section_markup(): string {
	$items = array(
		array(
			'icon'  => 'shield',
			'title' => '1. Diagnóstico médico de precisión',
			'body'  => 'No creemos en soluciones estandarizadas ni en la aplicación automática de tecnología. Bajo la dirección del Dr. José Javier Rivera Tejeda, cada protocolo se inicia con una valoración exhaustiva de 15 a 30 minutos (presencial o por videoconsulta). Analizamos la calidad de tu dermis, el grado de elastosis y tu historial clínico para diseñar un plan de tratamiento exclusivo y seguro, garantizando que el criterio médico prevalezca siempre sobre la aparatología.',
		),
		array(
			'icon'  => 'laser',
			'title' => '2. Tecnología láser de vanguardia certificada',
			'body'  => 'Equipamos nuestras clínicas en Madrid con plataformas médicas originales con marcado CE y autorizadas por la Comunidad de Madrid. Calibramos de forma milimétrica la energía de sistemas de referencia internacional como DEKA Motus AZ+, Láser CO₂ fraccionado y la plataforma EXION® de BTL. Esto nos permite actuar en las capas más profundas de los tejidos de forma indolora y con máxima exactitud, eliminando la flacidez y renovando la piel sin tiempos de baja prolongados.',
		),
		array(
			'icon'  => 'nature',
			'title' => '3. Resultados naturales sin quirófano',
			'body'  => 'Nuestra prioridad es devolver la turgencia y definición al óvalo facial, la mandíbula y el cuello respetando la expresividad y la armonía natural de tu rostro. Mediante procedimientos mínimamente invasivos de última generación —como el Endolift® facial con microfibras ópticas subdérmicas y EXION® Fractional RF— estimulamos la neocolagénesis y la producción natural de ácido hialurónico, ofreciendo una alternativa real, segura y progresiva al lifting quirúrgico tradicional.',
		),
	);

	$html  = '<section class="nvx-brand-section nvx-brand-section--tight nvx-home-editorial nvx-v3-intro nvx-home-values-section" aria-label="La base de nuestro criterio clínico">';
	$html .= '<div class="nvx-v3-shell nvx-brand-section__inner">';
	$html .= '<p class="nvx-brand-kicker">' . esc_html__( 'La base de nuestro criterio clínico', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 class="nvx-brand-title">' . esc_html__( 'Medicina estética láser con diagnóstico, tecnología certificada y resultados naturales', 'nuvanx-medical' ) . '</h2>';
	$html .= '<div class="nvx-home-values">';

	foreach ( $items as $item ) {
		$html .= '<article class="nvx-home-value">';
		$html .= '<div class="nvx-home-value__icon" aria-hidden="true">' . nvx_home_icon_svg( $item['icon'] ) . '</div>';
		$html .= '<h3 class="nvx-home-value__title">' . esc_html( $item['title'] ) . '</h3>';
		$html .= '<p class="nvx-home-value__body">' . esc_html( $item['body'] ) . '</p>';
		$html .= '</article>';
	}

	$html .= '</div>';
	$html .= '<div class="nvx-home-values__cta nvx-home-hero-ctas">';
	$html .= nvx_home_cta_primary();
	$html .= nvx_home_cta_whatsapp();
	$html .= '</div>';
	$html .= '</div></section>';

	return $html;
}

/**
 * Método as 3 horizontal columns (not another 01/02/03 treatment list).
 */
function nvx_home_metodo_section_markup(): string {
	$items = array(
		array(
			'icon'  => 'scan',
			'title' => 'Diagnóstico médico integral',
			'body'  => 'Evaluamos historial clínico, calidad de piel, objetivos y contraindicaciones. Solo entonces se define si hay indicación y qué protocolo tiene sentido.',
		),
		array(
			'icon'  => 'precision',
			'title' => 'Tecnología de precisión',
			'body'  => 'Seleccionamos plataforma y parámetros con exactitud milimétrica —láser, Endolift® o EXION®— según la anatomía y el resultado esperado, no por catálogo.',
		),
		array(
			'icon'  => 'follow',
			'title' => 'Seguimiento continuo',
			'body'  => 'Acompañamiento médico con calendario de control según el tratamiento y tu evolución, para consolidar resultados con seguridad.',
		),
	);

	$html  = '<section class="nvx-brand-section nvx-v3-metodo nvx-home-metodo-columns-section" aria-label="Método NUVANX">';
	$html .= '<div class="nvx-v3-shell nvx-brand-section__inner">';
	$html .= '<p class="nvx-brand-kicker">' . esc_html__( 'Método', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 class="nvx-brand-title">' . esc_html__( 'El criterio médico antes que la tecnología', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p class="nvx-brand-body nvx-home-metodo-lead">' . esc_html__( 'En NUVANX, la experiencia y el criterio médico son el pilar de cada tratamiento. La aparatología se pone al servicio del diagnóstico, nunca al revés.', 'nuvanx-medical' ) . '</p>';
	$html .= '<div class="nvx-home-metodo-columns">';

	foreach ( $items as $item ) {
		$html .= '<article class="nvx-home-metodo-col">';
		$html .= '<div class="nvx-home-metodo-col__icon" aria-hidden="true">' . nvx_home_icon_svg( $item['icon'] ) . '</div>';
		$html .= '<h3 class="nvx-home-metodo-col__title">' . esc_html( $item['title'] ) . '</h3>';
		$html .= '<p class="nvx-home-metodo-col__body">' . esc_html( $item['body'] ) . '</p>';
		$html .= '</article>';
	}

	$html .= '</div>';
	$html .= '<div class="nvx-home-metodo-columns__cta nvx-home-hero-ctas">';
	$html .= nvx_home_cta_primary();
	$html .= nvx_home_cta_whatsapp();
	$html .= '</div>';
	$html .= '</div></section>';

	return $html;
}

/**
 * @param string $content Post content HTML.
 */
function nvx_home_replace_intro_section( string $content ): string {
	$replacement = nvx_home_values_section_markup();
	$updated     = preg_replace(
		'/<section\b[^>]*class="[^"]*nvx-home-editorial[^"]*"[^>]*>[\s\S]*?<\/section>/i',
		$replacement,
		$content,
		1,
		$count
	);
	return ( is_string( $updated ) && $count > 0 ) ? $updated : $content;
}

/**
 * @param string $content Post content HTML.
 */
function nvx_home_replace_metodo_section( string $content ): string {
	$replacement = nvx_home_metodo_section_markup();
	$updated     = preg_replace(
		'/<section\b[^>]*class="[^"]*nvx-v3-metodo[^"]*"[^>]*>[\s\S]*?<\/section>/i',
		$replacement,
		$content,
		1,
		$count
	);
	return ( is_string( $updated ) && $count > 0 ) ? $updated : $content;
}

/**
 * GEO denser treatment cards (Endolift + EXION).
 *
 * @param string $content Post content HTML.
 */
function nvx_home_enrich_treatment_cards( string $content ): string {
	$endolift_new = 'Tensado progresivo del óvalo facial, mandíbula y papada mediante el uso de microfibras ópticas estériles monouso de entre 200 y 300 micras introducidas bajo la piel para retraer el tejido conectivo (SMAS) y eliminar la grasa submentoniana de forma selectiva. Técnica mínimamente invasiva, siempre tras valoración médica.';

	$exion_new = 'Plataforma médica que combina radiofrecuencia monopolar y ultrasonido dirigido (aplicadores Fractional RF, Face y Body). Consigue aumentar de forma natural hasta un 224% la producción de ácido hialurónico endógeno sin necesidad de infiltrar rellenos, además de mejorar textura y firmeza según protocolo personalizado tras valoración médica.';

	$content = preg_replace(
		'/(<h3 class="nvx-brand-card__title">\s*Endolift® Facial[\s\S]*?<\/h3>\s*<p class="nvx-brand-card__body">)([\s\S]*?)(<\/p>)/u',
		'$1' . esc_html( $endolift_new ) . '$3',
		$content,
		1
	);

	$content = preg_replace(
		'/(<h3 class="nvx-brand-card__title">\s*EXION®[\s\S]*?<\/h3>\s*<p class="nvx-brand-card__body">)([\s\S]*?)(<\/p>)/u',
		'$1' . esc_html( $exion_new ) . '$3',
		$content,
		1
	);

	return $content;
}

/**
 * E-E-A-T director medical block.
 *
 * @param string $content Post content HTML.
 */
function nvx_home_enhance_director_block( string $content ): string {
	$colegiado = NVX_DIRECTOR_COLEGIADO;
	$role      = sprintf(
		/* translators: %s: medical license number */
		__( 'Director Médico · Colegiado Nº %s', 'nuvanx-medical' ),
		$colegiado
	);
	$body = __( 'Especialista en Endolift®, láser CO₂ y medicina estética facial. Miembro de las principales sociedades científicas del sector. Martes y jueves: Sede Chamberí. Miércoles: Sede Goya.', 'nuvanx-medical' );

	// Card: name (kicker) · role + colegiado (title) · credentials (body).
	$content = preg_replace(
		'/(class="nvx-brand-card__kicker">\s*Dr\.\s*José Javier Rivera Tejeda\s*<\/p>\s*<h3 class="nvx-brand-card__title">)([\s\S]*?)(<\/h3>\s*<p class="nvx-brand-card__body">)([\s\S]*?)(<\/p>)/u',
		'$1' . esc_html( $role ) . '$3' . esc_html( $body ) . '$5',
		$content,
		1
	);

	// Intro paragraph under Dirección Médica — keep leadership sentence + colegiado cue.
	$lead = sprintf(
		/* translators: %s: medical license number */
		__( 'Nuestro equipo médico, liderado por el Dr. José Javier Rivera Tejeda (Colegiado ICOMEM Nº %s), supervisa cada valoración, indicación y seguimiento en ambas sedes. Su trabajo se basa en el diagnóstico individual, la indicación médica responsable y el seguimiento personalizado de cada tratamiento.', 'nuvanx-medical' ),
		$colegiado
	);

	$content = preg_replace(
		'/(Nuestro equipo médico, liderado por el Dr\.\s*José Javier Rivera Tejeda)([\s\S]*?)(<\/p>)/u',
		esc_html( $lead ) . '$3',
		$content,
		1
	);

	return $content;
}

/**
 * FAQ: EXION vs Morpheus8 — superiority framing (not defensive).
 *
 * @param string $content Post content HTML.
 */
function nvx_home_rewrite_morpheus_faq( string $content ): string {
	$answer  = '<p>' . esc_html__( 'Sí, y representa una evolución en tratamientos de radiofrecuencia fraccionada con microagujas. Mientras que otros sistemas tradicionales pueden resultar altamente dolorosos, EXION® incorpora una tecnología de control térmico inteligente que optimiza la entrega de energía en la dermis profunda de forma cómoda y controlada.', 'nuvanx-medical' ) . '</p>';
	$answer .= '<p>' . esc_html__( 'Esto nos permite maximizar el tensado de la piel, la producción de colágeno y la remodelación tisular con un nivel de molestia mínimo y sin tiempo de baja.', 'nuvanx-medical' ) . '</p>';
	$answer .= '<p><a class="nvx-brand-inline-link" href="' . esc_url( home_url( '/exion-btl/' ) ) . '">' . esc_html__( 'Ver EXION® Fractional RF', 'nuvanx-medical' ) . '</a></p>';

	$updated = preg_replace(
		'/(<summary><span>¿EXION® Fractional RF es una alternativa a Morpheus8\?<\/span><\/summary>\s*<div class="nvx-brand-faq-content">)([\s\S]*?)(<\/div>\s*<\/details>)/u',
		'$1' . $answer . '$3',
		$content,
		1,
		$count
	);

	return ( is_string( $updated ) && $count > 0 ) ? $updated : $content;
}

/**
 * Unify CTAs on the home to primary valoración + secondary WhatsApp.
 *
 * @param string $content Post content HTML.
 */
function nvx_home_unify_ctas( string $content ): string {
	$primary_label  = 'Reservar valoración gratuita';
	$whatsapp_label = 'Contactar por WhatsApp';
	$valoracion_url = nvx_home_valoracion_url();
	$whatsapp_url   = nvx_home_whatsapp_url();

	// Hero CTAs: primary → valoración, secondary → WhatsApp.
	$content = preg_replace(
		'/(class="nvx-home-hero-ctas">[\s\S]*?<a class="nvx-brand-btn nvx-brand-btn--primary")[^>]*>[\s\S]*?<\/a>/u',
		'$1 href="' . esc_url( $valoracion_url ) . '">' . esc_html( $primary_label ) . '</a>',
		$content,
		1
	);
	$content = preg_replace(
		'/(class="nvx-home-hero-ctas">[\s\S]*?<a class="nvx-brand-btn nvx-brand-btn--secondary")[^>]*>[\s\S]*?<\/a>/u',
		'$1 href="' . esc_url( $whatsapp_url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $whatsapp_label ) . '</a>',
		$content,
		1
	);

	// Generic label replacements for primary conversion language.
	$label_map = array(
		'Solicitar valoración médica personalizada' => $primary_label,
		'Solicitar consulta médica personalizada'   => $primary_label,
		'Solicitar consulta médica'                 => $primary_label,
		'Solicitar consulta'                        => $primary_label,
		'Agenda tu Valoración médica personalizada' => $primary_label,
		'Pedir cita'                                => $primary_label,
		'Reservar cita'                             => $primary_label,
		'Valoración gratuita'                       => $primary_label,
		'Cita online'                               => $primary_label,
		'Explorar tratamientos exclusivos'          => $whatsapp_label,
	);

	foreach ( $label_map as $from => $to ) {
		$content = str_ireplace( '>' . $from . '<', '>' . $to . '<', $content );
	}

	// Final CTA band → valoración URL + primary label.
	$content = preg_replace(
		'/(class="[^"]*nvx-home-cta-final-band[^"]*"[\s\S]*?<a[^>]*href=")[^"]*("[^>]*>)([^<]*)(<\/a>)/u',
		'$1' . esc_url( $valoracion_url ) . '$2' . esc_html( $primary_label ) . '$4',
		$content,
		1
	);

	// Remaining invitation free-text (if values section not yet covering CTAs).
	$content = preg_replace(
		'/<div class="nvx-home-invitation">[\s\S]*?<\/div>/u',
		'<div class="nvx-home-invitation nvx-home-hero-ctas">' . nvx_home_cta_primary() . nvx_home_cta_whatsapp() . '</div>',
		$content,
		1
	);

	return $content;
}

/**
 * Front-page content pipeline.
 *
 * @param string $content Post content.
 * @return string
 */
function nvx_home_content_enhance( string $content ): string {
	if ( is_admin() || ! is_front_page() ) {
		return $content;
	}

	$content = nvx_home_replace_intro_section( $content );
	$content = nvx_home_replace_metodo_section( $content );
	$content = nvx_home_enrich_treatment_cards( $content );
	$content = nvx_home_enhance_director_block( $content );
	$content = nvx_home_rewrite_morpheus_faq( $content );
	$content = nvx_home_unify_ctas( $content );

	return $content;
}
add_filter( 'the_content', 'nvx_home_content_enhance', 20 );
