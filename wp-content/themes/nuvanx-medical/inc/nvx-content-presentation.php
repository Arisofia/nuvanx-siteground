<?php
/**
 * Global content presentation layer.
 *
 * Applies by content pattern (classes / known blocks), not by page ID or home-only rules:
 * - dual CTAs (valoración + WhatsApp)
 * - clinical values pillars
 * - method columns
 * - GEO treatment card densification
 * - director E-E-A-T (colegiado)
 * - FAQ framing (EXION vs Morpheus8)
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'NVX_DIRECTOR_COLEGIADO' ) ) {
	define( 'NVX_DIRECTOR_COLEGIADO', '282864786' );
}

/**
 * @return string
 */
function nvx_cta_valoracion_url(): string {
	return home_url( '/madrid/valoracion/' );
}

/**
 * @return string
 */
function nvx_cta_whatsapp_url(): string {
	return 'https://wa.me/34669319836';
}

/**
 * Primary conversion CTA.
 */
function nvx_cta_primary_markup( string $class = 'nvx-brand-btn nvx-brand-btn--primary' ): string {
	return sprintf(
		'<a class="%1$s" href="%2$s">%3$s</a>',
		esc_attr( $class ),
		esc_url( nvx_cta_valoracion_url() ),
		esc_html__( 'Reservar valoración gratuita', 'nuvanx-medical' )
	);
}

/**
 * Secondary WhatsApp CTA.
 */
function nvx_cta_whatsapp_markup( string $class = 'nvx-brand-btn nvx-brand-btn--secondary' ): string {
	return sprintf(
		'<a class="%1$s" href="%2$s" target="_blank" rel="noopener noreferrer">%3$s</a>',
		esc_attr( $class ),
		esc_url( nvx_cta_whatsapp_url() ),
		esc_html__( 'Contactar por WhatsApp', 'nuvanx-medical' )
	);
}

/**
 * Dual CTA cluster.
 */
function nvx_cta_pair_markup( string $extra_class = '' ): string {
	$class = trim( 'nvx-cta-pair ' . $extra_class );
	return '<div class="' . esc_attr( $class ) . '">'
		. nvx_cta_primary_markup()
		. nvx_cta_whatsapp_markup()
		. '</div>';
}

/**
 * Minimal stroke icons (currentColor).
 *
 * @param string $name Icon key.
 */
function nvx_content_icon_svg( string $name ): string {
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
 * Clinical values pillars (structured presentation of intro/criterio blocks).
 */
function nvx_values_section_markup(): string {
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

	$html  = '<section class="nvx-brand-section nvx-brand-section--tight nvx-values-section" aria-label="La base de nuestro criterio clínico">';
	$html .= '<div class="nvx-v3-shell nvx-brand-section__inner">';
	$html .= '<p class="nvx-brand-kicker">' . esc_html__( 'La base de nuestro criterio clínico', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 class="nvx-brand-title">' . esc_html__( 'Medicina estética láser con diagnóstico, tecnología certificada y resultados naturales', 'nuvanx-medical' ) . '</h2>';
	$html .= '<div class="nvx-values">';

	foreach ( $items as $item ) {
		$html .= '<article class="nvx-value">';
		$html .= '<div class="nvx-value__icon" aria-hidden="true">' . nvx_content_icon_svg( $item['icon'] ) . '</div>';
		$html .= '<h3 class="nvx-value__title">' . esc_html( $item['title'] ) . '</h3>';
		$html .= '<p class="nvx-value__body">' . esc_html( $item['body'] ) . '</p>';
		$html .= '</article>';
	}

	$html .= '</div>';
	$html .= nvx_cta_pair_markup( 'nvx-values__cta' );
	$html .= '</div></section>';

	return $html;
}

/**
 * Method as three icon columns (distinct from numbered treatment grids).
 */
function nvx_method_section_markup(): string {
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

	$html  = '<section class="nvx-brand-section nvx-method-section" aria-label="Método NUVANX">';
	$html .= '<div class="nvx-v3-shell nvx-brand-section__inner">';
	$html .= '<p class="nvx-brand-kicker">' . esc_html__( 'Método', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 class="nvx-brand-title">' . esc_html__( 'El criterio médico antes que la tecnología', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p class="nvx-brand-body nvx-method-lead">' . esc_html__( 'En NUVANX, la experiencia y el criterio médico son el pilar de cada tratamiento. La aparatología se pone al servicio del diagnóstico, nunca al revés.', 'nuvanx-medical' ) . '</p>';
	$html .= '<div class="nvx-method-columns">';

	foreach ( $items as $item ) {
		$html .= '<article class="nvx-method-col">';
		$html .= '<div class="nvx-method-col__icon" aria-hidden="true">' . nvx_content_icon_svg( $item['icon'] ) . '</div>';
		$html .= '<h3 class="nvx-method-col__title">' . esc_html( $item['title'] ) . '</h3>';
		$html .= '<p class="nvx-method-col__body">' . esc_html( $item['body'] ) . '</p>';
		$html .= '</article>';
	}

	$html .= '</div>';
	$html .= nvx_cta_pair_markup( 'nvx-method__cta' );
	$html .= '</div></section>';

	return $html;
}

/**
 * Replace intro/editorial prose blocks with structured values.
 * Matches structural patterns, not page slugs.
 */
function nvx_content_replace_values_sections( string $content ): string {
	// Already transformed.
	if ( false !== strpos( $content, 'nvx-values-section' ) || false !== strpos( $content, 'class="nvx-values"' ) ) {
		return $content;
	}

	$replacement = nvx_values_section_markup();
	$patterns    = array(
		// Legacy home editorial intro.
		'/<section\b[^>]*class="[^"]*nvx-home-editorial[^"]*"[^>]*>[\s\S]*?<\/section>/i',
		// Generic v3 intro blocks with continuous prose (same role).
		'/<section\b[^>]*class="[^"]*nvx-v3-intro[^"]*"[^>]*>[\s\S]*?<\/section>/i',
	);

	foreach ( $patterns as $pattern ) {
		$updated = preg_replace( $pattern, $replacement, $content, 1, $count );
		if ( is_string( $updated ) && $count > 0 ) {
			$content = $updated;
		}
	}

	return $content;
}

/**
 * Replace numbered method lists with icon columns.
 */
function nvx_content_replace_method_sections( string $content ): string {
	if ( false !== strpos( $content, 'nvx-method-section' ) || false !== strpos( $content, 'nvx-method-columns' ) ) {
		return $content;
	}

	$replacement = nvx_method_section_markup();
	$patterns    = array(
		'/<section\b[^>]*class="[^"]*nvx-v3-metodo[^"]*"[^>]*>[\s\S]*?<\/section>/i',
		'/<section\b[^>]*class="[^"]*nvx-home-metodo[^"]*"[^>]*>[\s\S]*?<\/section>/i',
		'/<section\b[^>]*aria-label="[^"]*Método[^"]*"[^>]*>[\s\S]*?<\/section>/iu',
	);

	foreach ( $patterns as $pattern ) {
		$updated = preg_replace( $pattern, $replacement, $content, -1, $count );
		if ( is_string( $updated ) && $count > 0 ) {
			$content = $updated;
		}
	}

	return $content;
}

/**
 * GEO densification for Endolift / EXION cards sitewide.
 */
function nvx_content_enrich_treatment_cards( string $content ): string {
	$endolift_new = 'Tensado progresivo del óvalo facial, mandíbula y papada mediante el uso de microfibras ópticas estériles monouso de entre 200 y 300 micras introducidas bajo la piel para retraer el tejido conectivo (SMAS) y eliminar la grasa submentoniana de forma selectiva. Técnica mínimamente invasiva, siempre tras valoración médica.';

	$exion_new = 'Plataforma médica que combina radiofrecuencia monopolar y ultrasonido dirigido (aplicadores Fractional RF, Face y Body). Consigue aumentar de forma natural hasta un 224% la producción de ácido hialurónico endógeno sin necesidad de infiltrar rellenos, además de mejorar textura y firmeza según protocolo personalizado tras valoración médica.';

	// Any brand-card titled Endolift® Facial…
	$content = preg_replace(
		'/(<h3 class="nvx-brand-card__title">\s*Endolift® Facial[\s\S]*?<\/h3>\s*<p class="nvx-brand-card__body">)([\s\S]*?)(<\/p>)/u',
		'$1' . esc_html( $endolift_new ) . '$3',
		$content
	);

	// Any brand-card titled EXION®…
	$content = preg_replace(
		'/(<h3 class="nvx-brand-card__title">\s*EXION®[\s\S]*?<\/h3>\s*<p class="nvx-brand-card__body">)([\s\S]*?)(<\/p>)/u',
		'$1' . esc_html( $exion_new ) . '$3',
		$content
	);

	return is_string( $content ) ? $content : '';
}

/**
 * Director E-E-A-T wherever the Rivera card / leadership copy appears.
 */
function nvx_content_enhance_director_blocks( string $content ): string {
	$colegiado = NVX_DIRECTOR_COLEGIADO;
	$role      = sprintf(
		/* translators: %s: medical license number */
		__( 'Director Médico · Colegiado Nº %s', 'nuvanx-medical' ),
		$colegiado
	);
	$body = __( 'Especialista en Endolift®, láser CO₂ y medicina estética facial. Miembro de las principales sociedades científicas del sector. Martes y jueves: Sede Chamberí. Miércoles: Sede Goya.', 'nuvanx-medical' );

	$content = preg_replace(
		'/(class="nvx-brand-card__kicker">\s*Dr\.\s*José Javier Rivera Tejeda\s*<\/p>\s*<h3 class="nvx-brand-card__title">)([\s\S]*?)(<\/h3>\s*<p class="nvx-brand-card__body">)([\s\S]*?)(<\/p>)/u',
		'$1' . esc_html( $role ) . '$3' . esc_html( $body ) . '$5',
		$content
	);

	// Alternate: title holds the name.
	$content = preg_replace(
		'/(class="nvx-brand-card__title">\s*Dr\.\s*José Javier Rivera Tejeda\s*)(Director Médico[^<]*)?(<\/h3>\s*<p class="nvx-brand-card__body">)([\s\S]*?)(<\/p>)/u',
		'$1' . esc_html( $role ) . '$3' . esc_html( $body ) . '$5',
		$content
	);

	$lead = sprintf(
		/* translators: %s: medical license number */
		__( 'Nuestro equipo médico, liderado por el Dr. José Javier Rivera Tejeda (Colegiado ICOMEM Nº %s), supervisa cada valoración, indicación y seguimiento en ambas sedes. Su trabajo se basa en el diagnóstico individual, la indicación médica responsable y el seguimiento personalizado de cada tratamiento.', 'nuvanx-medical' ),
		$colegiado
	);

	$content = preg_replace(
		'/(Nuestro equipo médico, liderado por el Dr\.\s*José Javier Rivera Tejeda)([^<]*)(<\/p>)/u',
		esc_html( $lead ) . '$3',
		$content
	);

	return is_string( $content ) ? $content : '';
}

/**
 * FAQ: EXION vs Morpheus8 — superiority framing, any page.
 */
function nvx_content_rewrite_morpheus_faq( string $content ): string {
	$answer  = '<p>' . esc_html__( 'Sí, y representa una evolución en tratamientos de radiofrecuencia fraccionada con microagujas. Mientras que otros sistemas tradicionales pueden resultar altamente dolorosos, EXION® incorpora una tecnología de control térmico inteligente que optimiza la entrega de energía en la dermis profunda de forma cómoda y controlada.', 'nuvanx-medical' ) . '</p>';
	$answer .= '<p>' . esc_html__( 'Esto nos permite maximizar el tensado de la piel, la producción de colágeno y la remodelación tisular con un nivel de molestia mínimo y sin tiempo de baja.', 'nuvanx-medical' ) . '</p>';
	$answer .= '<p><a class="nvx-brand-inline-link" href="' . esc_url( home_url( '/exion-btl/' ) ) . '">' . esc_html__( 'Ver EXION® Fractional RF', 'nuvanx-medical' ) . '</a></p>';

	$updated = preg_replace(
		'/(<summary><span>¿EXION® Fractional RF es una alternativa a Morpheus8\?<\/span><\/summary>\s*<div class="nvx-brand-faq-content">)([\s\S]*?)(<\/div>\s*<\/details>)/u',
		'$1' . $answer . '$3',
		$content
	);

	return is_string( $updated ) ? $updated : $content;
}

/**
 * Unify conversion CTAs globally in post content.
 */
function nvx_content_unify_ctas( string $content ): string {
	$primary_label  = 'Reservar valoración gratuita';
	$whatsapp_label = 'Contactar por WhatsApp';
	$valoracion_url = nvx_cta_valoracion_url();
	$whatsapp_url   = nvx_cta_whatsapp_url();

	// Paired hero / brand action clusters: primary + secondary.
	$content = preg_replace(
		'/(class="(?:nvx-home-hero-ctas|nvx-brand-actions|nvx-page__cta|nvx-cta-pair)"[^>]*>[\s\S]*?<a class="[^"]*(?:brand-btn--primary|button--primary|btn--primary)[^"]*")[^>]*>[\s\S]*?<\/a>/u',
		'$1 href="' . esc_url( $valoracion_url ) . '">' . esc_html( $primary_label ) . '</a>',
		$content
	);
	$content = preg_replace(
		'/(class="(?:nvx-home-hero-ctas|nvx-brand-actions|nvx-page__cta|nvx-cta-pair)"[^>]*>[\s\S]*?<a class="[^"]*(?:brand-btn--secondary|button--secondary|btn--secondary)[^"]*")[^>]*>[\s\S]*?<\/a>/u',
		'$1 href="' . esc_url( $whatsapp_url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $whatsapp_label ) . '</a>',
		$content
	);

	// Label normalization for remaining anchors.
	$label_map = array(
		'Solicitar valoración médica personalizada' => $primary_label,
		'Solicitar valoración médica gratuita'      => $primary_label,
		'Solicitar consulta médica personalizada'   => $primary_label,
		'Solicitar consulta médica'                 => $primary_label,
		'Solicitar consulta'                        => $primary_label,
		'Agenda tu Valoración médica personalizada' => $primary_label,
		'Pedir cita'                                => $primary_label,
		'Reservar cita'                             => $primary_label,
		'Valoración gratuita'                       => $primary_label,
		'Cita online'                               => $primary_label,
		'RESERVAR CITA'                             => $primary_label,
		'Explorar tratamientos exclusivos'          => $whatsapp_label,
	);

	foreach ( $label_map as $from => $to ) {
		$content = str_ireplace( '>' . $from . '<', '>' . $to . '<', $content );
	}

	// Primary conversion anchors → valoración URL (preserve classes).
	$content = preg_replace_callback(
		'/<a\b([^>]*)>(\s*Reservar valoración gratuita\s*)<\/a>/iu',
		static function ( array $m ) use ( $valoracion_url ): string {
			$attrs = $m[1];
			$attrs = preg_replace( '/\s*href=("|\')[^"\']*("|\')/i', '', $attrs ) ?? $attrs;
			return '<a' . $attrs . ' href="' . esc_url( $valoracion_url ) . '">' . $m[2] . '</a>';
		},
		$content
	);

	// WhatsApp anchors → wa.me (preserve classes).
	$content = preg_replace_callback(
		'/<a\b([^>]*)>(\s*Contactar por WhatsApp\s*)<\/a>/iu',
		static function ( array $m ) use ( $whatsapp_url ): string {
			$attrs = $m[1];
			$attrs = preg_replace( '/\s*href=("|\')[^"\']*("|\')/i', '', $attrs ) ?? $attrs;
			$attrs = preg_replace( '/\s*target=("|\')[^"\']*("|\')/i', '', $attrs ) ?? $attrs;
			$attrs = preg_replace( '/\s*rel=("|\')[^"\']*("|\')/i', '', $attrs ) ?? $attrs;
			return '<a' . $attrs . ' href="' . esc_url( $whatsapp_url ) . '" target="_blank" rel="noopener noreferrer">' . $m[2] . '</a>';
		},
		$content
	);

	// Invitation free-text blocks → dual CTA pair.
	$content = preg_replace(
		'/<div class="nvx-home-invitation">[\s\S]*?<\/div>/u',
		nvx_cta_pair_markup( 'nvx-home-invitation' ),
		$content
	);

	// Final band CTAs.
	$content = preg_replace(
		'/(class="[^"]*nvx-home-cta-final-band[^"]*"[\s\S]*?<a[^>]*href=")[^"]*("[^>]*>)([^<]*)(<\/a>)/u',
		'$1' . esc_url( $valoracion_url ) . '$2' . esc_html( $primary_label ) . '$4',
		$content
	);

	return is_string( $content ) ? $content : '';
}

/**
 * Global content presentation pipeline (all singular + front content).
 *
 * @param string $content HTML.
 * @return string
 */
function nvx_content_presentation_enhance( string $content ): string {
	if ( is_admin() || '' === trim( $content ) ) {
		return $content;
	}

	// Feeds / REST: keep raw.
	if ( is_feed() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return $content;
	}

	$content = nvx_content_replace_values_sections( $content );
	$content = nvx_content_replace_method_sections( $content );
	$content = nvx_content_enrich_treatment_cards( $content );
	$content = nvx_content_enhance_director_blocks( $content );
	$content = nvx_content_rewrite_morpheus_faq( $content );
	$content = nvx_content_unify_ctas( $content );

	return $content;
}
add_filter( 'the_content', 'nvx_content_presentation_enhance', 20 );

// Backward-compatible aliases (older home helpers).
if ( ! function_exists( 'nvx_home_valoracion_url' ) ) {
	/**
	 * @return string
	 */
	function nvx_home_valoracion_url(): string {
		return nvx_cta_valoracion_url();
	}
}
if ( ! function_exists( 'nvx_home_whatsapp_url' ) ) {
	/**
	 * @return string
	 */
	function nvx_home_whatsapp_url(): string {
		return nvx_cta_whatsapp_url();
	}
}
