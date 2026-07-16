<?php
/**
 * Medicina Estética hub — high-authority editorial rebuild.
 *
 * Wire-frame: Hero → Diagnóstico 3 columnas → Catálogo facial → Regeneración
 *             → FAQ reológicas AEO → Action banner.
 * Pattern-based (medicina-estetica markers). Excludes láser hub and detail pages.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Singular page context for aesthetic hub rewrite.
 */
function nvx_aesthetic_is_singular_context(): bool {
	if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return false;
	}

	return is_singular( 'page' ) || is_page();
}

/**
 * Lookup a published page URL by path (null if missing). Request-static cache.
 *
 * @param string $path Relative path without domain.
 * @return string|null Permalink or null when not found / not published.
 */
function nvx_aesthetic_lookup_published_url( string $path ): ?string {
	static $cache = array();

	$path = trim( $path, '/' );
	if ( array_key_exists( $path, $cache ) ) {
		return $cache[ $path ];
	}

	$page = get_page_by_path( $path );
	if ( $page instanceof WP_Post && 'publish' === $page->post_status ) {
		$url = get_permalink( $page );
		if ( is_string( $url ) && '' !== $url ) {
			$cache[ $path ] = $url;
			return $url;
		}
	}

	$cache[ $path ] = null;
	return null;
}

/**
 * Resolve a public page URL by path, with home_url fallback.
 *
 * @param string $path Relative path without domain.
 */
function nvx_aesthetic_page_url( string $path ): string {
	$path = trim( $path, '/' );
	$found = nvx_aesthetic_lookup_published_url( $path );
	return null !== $found ? $found : home_url( '/' . $path . '/' );
}

/**
 * Detect Medicina Estética hub (injectables / regenerativa), not láser hub.
 */
function nvx_content_is_aesthetic_medicine_page( string $content ): bool {
	$is_hub = false;

	if ( false === strpos( $content, 'nvx-aesthetic-editorial' )
		&& nvx_aesthetic_is_singular_context()
		&& ! preg_match(
			'/nvx-brand-page--laser|nvx-laser-editorial|nvx-laser-hero|id=["\']nvx-laser-h1["\']|nvx-endolift-editorial|nvx-endolift-hero|aria-label=["\']Medicina estética láser NUVANX["\']/iu',
			$content
		)
	) {
		// Stable structural markers for /medicina-estetica/.
		$is_hub = (bool) preg_match(
			'/class=["\'][^"\']*nvx-brand-page--medicina-estetica|id=["\']nvx-med-h1["\']|aria-label=["\']Medicina estética NUVANX["\']/iu',
			$content
		);
	}

	return $is_hub;
}

/**
 * Linear premium icons — Champagne Bronce stroke 1.5px, 32×32.
 *
 * @param string $name Icon key.
 */
function nvx_aesthetic_icon( string $name ): string {
	$icons = array(
		'support'  => '<svg class="nvx-aes-icon" viewBox="0 0 32 32" width="32" height="32" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M8 24V12l8-6 8 6v12" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M12 24v-8h8v8" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg>',
		'express'  => '<svg class="nvx-aes-icon" viewBox="0 0 32 32" width="32" height="32" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><circle cx="16" cy="16" r="10" stroke="currentColor" stroke-width="1.5"/><path d="M11 14h.01M21 14h.01M12 20c1.5 2 6.5 2 8 0" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>',
		'rheology' => '<svg class="nvx-aes-icon" viewBox="0 0 32 32" width="32" height="32" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M10 8h12v4c0 4-2.5 6-6 8-3.5-2-6-4-6-8V8Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M12 24h8M14 28h4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>',
		'lips'     => '<svg class="nvx-aes-icon" viewBox="0 0 32 32" width="32" height="32" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M6 16c2-4 5-6 10-6s8 2 10 6c-2 4-5 6-10 6s-8-2-10-6Z" stroke="currentColor" stroke-width="1.5"/><path d="M8 16h16" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>',
		'nose'     => '<svg class="nvx-aes-icon" viewBox="0 0 32 32" width="32" height="32" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M12 6h8l-1 14c0 3-2 6-3 6s-3-3-3-6L12 6Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M14 24h4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>',
		'eye'      => '<svg class="nvx-aes-icon" viewBox="0 0 32 32" width="32" height="32" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M4 16c3-6 7-9 12-9s9 3 12 9c-3 6-7 9-12 9s-9-3-12-9Z" stroke="currentColor" stroke-width="1.5"/><circle cx="16" cy="16" r="3.5" stroke="currentColor" stroke-width="1.5"/></svg>',
		'regen'    => '<svg class="nvx-aes-icon" viewBox="0 0 32 32" width="32" height="32" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M16 28V14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><path d="M16 24c-6 0-10-3.5-10-8.5 6 0 10 3.5 10 8.5Z" stroke="currentColor" stroke-width="1.5"/><path d="M16 21c6 0 10-3.5 10-8.5-6 0-10 3.5-10 8.5Z" stroke="currentColor" stroke-width="1.5"/><path d="M11 10c3-3 6-4.5 9-4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>',
	);

	return $icons[ $name ] ?? $icons['support'];
}

/**
 * Hero dual CTA: valoración + WhatsApp (proposal).
 */
function nvx_aesthetic_hero_ctas_markup(): string {
	$valoracion = function_exists( 'nvx_cta_valoracion_url' )
		? nvx_cta_valoracion_url()
		: home_url( '/madrid/valoracion/' );

	$html  = '<div class="nvx-cta-pair nvx-aes-hero-ctas">';
	$html .= sprintf(
		'<a class="nvx-brand-btn nvx-brand-btn--primary nvx-aes-btn--primary" href="%1$s">%2$s</a>',
		esc_url( $valoracion ),
		esc_html__( 'Reservar valoración gratuita', 'nuvanx-medical' )
	);

	if ( function_exists( 'nvx_cta_whatsapp_markup' ) ) {
		$html .= nvx_cta_whatsapp_markup( 'nvx-brand-btn nvx-brand-btn--secondary nvx-aes-btn--secondary' );
	} else {
		$html .= sprintf(
			'<a class="nvx-brand-btn nvx-brand-btn--secondary nvx-aes-btn--secondary" href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
			esc_url( 'https://wa.me/34669319836' ),
			esc_html__( 'Contactar por WhatsApp', 'nuvanx-medical' )
		);
	}

	$html .= '</div>';

	return $html;
}

/**
 * Hero copy.
 */
function nvx_aesthetic_hero_copy_markup(): string {
	$html  = '<div class="nvx-brand-hero__copy nvx-aes-hero-copy">';
	$html .= '<p class="nvx-brand-kicker">' . esc_html__( 'NUVANX · Madrid', 'nuvanx-medical' ) . '</p>';
	$html .= '<h1 class="nvx-brand-hero__title" id="nvx-med-h1">' . esc_html__( 'Medicina Estética Avanzada con Criterio Clínico', 'nuvanx-medical' ) . '</h1>';
	$html .= '<p class="nvx-brand-hero__lead">' . esc_html__( 'Restauramos el soporte estructural, la turgencia y la armonía del rostro mediante procedimientos médicos inyectables y regenerativos de alta precisión. Sin alterar tu identidad y guiados exclusivamente por el diagnóstico personalizado de nuestro equipo médico.', 'nuvanx-medical' ) . '</p>';
	$html .= nvx_aesthetic_hero_ctas_markup();
	$html .= '<p class="nvx-brand-meta">' . esc_html__( 'Chamberí (CS20144) · Salamanca–Goya (CS20073) · Preservación anatómica', 'nuvanx-medical' ) . '</p>';
	$html .= '</div>';

	return $html;
}

/**
 * Action banner — dark conversion (valoración + WhatsApp; presencial).
 */
function nvx_aesthetic_action_banner_markup(): string {
	$valoracion = function_exists( 'nvx_cta_valoracion_url' )
		? nvx_cta_valoracion_url()
		: home_url( '/madrid/valoracion/' );
	$whatsapp = function_exists( 'nvx_cta_whatsapp_url' )
		? nvx_cta_whatsapp_url()
		: 'https://wa.me/34669319836';

	$html  = '<section class="nvx-aes-action" aria-label="' . esc_attr__( 'Reservar valoración de medicina estética', 'nuvanx-medical' ) . '">';
	$html .= '<div class="nvx-aes-action__shell">';
	$html .= '<div class="nvx-aes-action__card">';
	$html .= '<div class="nvx-aes-action__copy">';
	$html .= '<h2 class="nvx-aes-action__title">' . esc_html__( 'Inicia tu diagnóstico médico de precisión', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p class="nvx-aes-action__text">' . wp_kses(
		__( 'Agenda tu valoración médica personalizada de 15 a 30 minutos. Disponible de forma presencial en nuestras clínicas autorizadas de <strong>Chamberí</strong> (Registro Sanitario CS20144) o <strong>Salamanca–Goya</strong> (Registro Sanitario CS20073).', 'nuvanx-medical' ),
		array( 'strong' => array() )
	) . '</p>';
	$html .= '</div>';
	$html .= '<div class="nvx-aes-action__ctas">';
	$html .= sprintf(
		'<a class="nvx-aes-action__primary" href="%1$s">%2$s</a>',
		esc_url( $valoracion ),
		esc_html__( 'Reservar valoración gratuita', 'nuvanx-medical' )
	);
	$html .= sprintf(
		'<a class="nvx-aes-action__secondary" href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
		esc_url( $whatsapp ),
		esc_html__( 'Contactar por WhatsApp', 'nuvanx-medical' )
	);
	$html .= '</div></div></div></section>';

	return $html;
}

/**
 * Diagnosis pillars section.
 */
function nvx_aesthetic_diagnosis_section_markup(): string {
	$pillars = array(
		array(
			'icon'  => 'support',
			'title' => __( '1. Pérdida de soporte estructural', 'nuvanx-medical' ),
			'body'  => __( 'Con el paso de los años, la reabsorción ósea y el desplazamiento de los compartimentos grasos profundos provocan la caída de los tejidos. Tratar una arruga de forma aislada sin restaurar este soporte óseo subyacente genera volúmenes artificiales y rostros pesados.', 'nuvanx-medical' ),
		),
		array(
			'icon'  => 'express',
			'title' => __( '2. Modulación de la expresión', 'nuvanx-medical' ),
			'body'  => __( 'Estudiamos tu rostro en movimiento (estática y dinámica gesticular). La colocación de un inyectable debe respetar la contracción natural de la musculatura mímica facial, evitando congelar la mirada o alterar la sonrisa.', 'nuvanx-medical' ),
		),
		array(
			'icon'  => 'rheology',
			'title' => __( '3. Densidad y reología cutánea', 'nuvanx-medical' ),
			'body'  => __( 'Analizamos el espesor dermoepidérmico y el nivel de elastosis. Esto determina la reología y el módulo de elasticidad del producto médico a inyectar, garantizando que sea imperceptible tanto a la vista como al tacto.', 'nuvanx-medical' ),
		),
	);

	$html  = '<section class="nvx-aes-section nvx-aes-diagnosis" aria-labelledby="nvx-aes-diagnosis-title">';
	$html .= '<div class="nvx-aes-section__inner">';
	$html .= '<p class="nvx-aes-kicker">' . esc_html__( 'El diagnóstico', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-aes-diagnosis-title" class="nvx-aes-heading">' . esc_html__( 'El diagnóstico antes del tratamiento', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p class="nvx-aes-body nvx-aes-body--lead">' . esc_html__( 'En NUVANX, la indicación de un inyectable no parte de un menú estandarizado, sino de una lectura clínica profunda de los vectores de envejecimiento del rostro. Evaluamos tres parámetros críticos:', 'nuvanx-medical' ) . '</p>';
	$html .= '<div class="nvx-aes-focus-grid">';

	foreach ( $pillars as $pillar ) {
		$html .= '<article class="nvx-aes-pillar">';
		$html .= nvx_aesthetic_icon( $pillar['icon'] );
		$html .= '<h3 class="nvx-aes-pillar__title">' . esc_html( $pillar['title'] ) . '</h3>';
		$html .= '<p class="nvx-aes-body">' . esc_html( $pillar['body'] ) . '</p>';
		$html .= '</article>';
	}

	$html .= '</div></div></section>';
	return $html;
}

/**
 * Resolve published page URL by primary path or alternate slugs (single cached lookup chain).
 *
 * @param string             $primary Primary path slug.
 * @param array<int, string> $alts    Alternate path slugs.
 */
function nvx_aesthetic_resolve_treatment_url( string $primary, array $alts = array() ): string {
	static $resolved = array();

	$key = $primary . '|' . implode( ',', $alts );
	if ( isset( $resolved[ $key ] ) ) {
		return $resolved[ $key ];
	}

	foreach ( array_merge( array( $primary ), $alts ) as $slug ) {
		$slug = trim( (string) $slug, '/' );
		if ( '' === $slug ) {
			continue;
		}
		$found = nvx_aesthetic_lookup_published_url( $slug );
		if ( null !== $found ) {
			$resolved[ $key ] = $found;
			return $found;
		}
	}

	$resolved[ $key ] = home_url( '/' . trim( $primary, '/' ) . '/' );
	return $resolved[ $key ];
}

/**
 * Facial catalog cards.
 */
function nvx_aesthetic_catalog_section_markup(): string {
	$treatments = array(
		array(
			'n'     => '01',
			'icon'  => 'lips',
			'title' => __( 'Labios de proporción natural · Perfilado e hidratación', 'nuvanx-medical' ),
			'body'  => __( 'Reestablecemos la definición del arco de Cupido, las columnas del filtrum y el volumen del bermellón respetando la anatomía original del paciente. Seleccionamos geles de ácido hialurónico con alta cohesividad y elasticidad adaptada para que el labio se mueva de forma natural con el habla y la sonrisa.', 'nuvanx-medical' ),
			'price' => __( 'Desde 290 €', 'nuvanx-medical' ),
			'core'  => __( 'Labios delgados, pérdida de volumen por envejecimiento o asimetrías severas.', 'nuvanx-medical' ),
			'url'   => nvx_aesthetic_resolve_treatment_url( 'labios-acido-hialuronico-madrid', array( 'labios', 'acido-hialuronico-labios', 'tratamiento-labios' ) ),
		),
		array(
			'n'     => '02',
			'icon'  => 'nose',
			'title' => __( 'Rinomodelación sin cirugía · Armonización del perfil', 'nuvanx-medical' ),
			'body'  => __( 'Corrección de irregularidades en el dorso nasal (caballete) y elevación sutil de la punta mediante la infiltración precisa de ácido hialurónico de alta densidad en el plano supraperiosteal. Un procedimiento de alta precisión que armoniza el perfil sin los tiempos de baja de una cirugía.', 'nuvanx-medical' ),
			'price' => __( 'Desde 380 €', 'nuvanx-medical' ),
			'core'  => __( 'Desviaciones leves del dorso nasal o puntas caídas. No sustituye a la rinoplastia quirúrgica.', 'nuvanx-medical' ),
			'url'   => nvx_aesthetic_resolve_treatment_url( 'rinomodelacion-sin-cirugia-madrid', array( 'rinomodelacion', 'rinomodelacion-sin-cirugia' ) ),
		),
		array(
			'n'     => '03',
			'icon'  => 'eye',
			'title' => __( 'Rejuvenecimiento de la mirada · Corrección del surco lagrimal', 'nuvanx-medical' ),
			'body'  => __( 'Tratamiento estructural del hundimiento de la ojera mediante la infiltración profunda de ácido hialurónico específico para la zona periocular. El objetivo es eliminar el aspecto de cansancio visual de forma segura, reduciendo la sombra de la ojera y proyectando la luz en el tercio medio.', 'nuvanx-medical' ),
			'price' => __( 'Tras valoración médica personalizada', 'nuvanx-medical' ),
			'core'  => __( 'Ojeras hundidas o surco lagrimal marcado. Requiere dermis de calidad y ausencia de bolsas grasas.', 'nuvanx-medical' ),
			'url'   => nvx_aesthetic_resolve_treatment_url( 'ojeras-surco-lagrimal-madrid', array( 'ojeras', 'tratamiento-ojeras', 'surco-lagrimal' ) ),
		),
		array(
			'n'     => '04',
			'icon'  => 'regen',
			'title' => __( 'Bioestimulación dérmica · Firmeza sin volumen', 'nuvanx-medical' ),
			'body'  => __( 'Protocolos inductores de colágeno mediante la infiltración de ácido poliláctico (Sculptra®) o hidroxiapatita de calcio (Radiesse®). Estos principios activos desencadenan una respuesta celular en la dermis profunda que estimula a los fibroblastos a producir nuevas fibras elásticas, tensando el tejido sin añadir volumen artificial al rostro.', 'nuvanx-medical' ),
			'price' => __( 'Estimuladores de colágeno desde 490 €', 'nuvanx-medical' ),
			'core'  => __( 'Flacidez moderada, pérdida de elasticidad y piel desvitalizada.', 'nuvanx-medical' ),
			'url'   => nvx_aesthetic_resolve_treatment_url( 'bioestimuladores-colageno-madrid', array( 'bioestimulacion', 'bioestimuladores', 'sculptra', 'radiesse' ) ),
		),
	);

	$html  = '<section class="nvx-aes-section nvx-aes-catalog" aria-labelledby="nvx-aes-catalog-title">';
	$html .= '<div class="nvx-aes-section__inner">';
	$html .= '<p class="nvx-aes-kicker">' . esc_html__( 'Catálogo facial', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-aes-catalog-title" class="nvx-aes-heading">' . esc_html__( 'Procedimientos médico-estéticos faciales', 'nuvanx-medical' ) . '</h2>';
	$html .= '<div class="nvx-aes-card-grid">';

	foreach ( $treatments as $treatment ) {
		$html .= '<article class="nvx-aes-card">';
		$html .= '<div class="nvx-aes-card__head">';
		$html .= nvx_aesthetic_icon( $treatment['icon'] );
		$html .= '<span class="nvx-aes-card__n">' . esc_html( $treatment['n'] ) . '</span>';
		$html .= '</div>';
		$html .= '<h3 class="nvx-aes-card__title">' . esc_html( $treatment['title'] ) . '</h3>';
		$html .= '<p class="nvx-aes-body">' . esc_html( $treatment['body'] ) . '</p>';
		// Valid description list: dt/dd are direct children of dl (no wrapping divs).
		$html .= '<dl class="nvx-aes-card__meta">';
		$html .= '<dt>' . esc_html__( 'Tarifa', 'nuvanx-medical' ) . '</dt>';
		$html .= '<dd>' . esc_html( $treatment['price'] ) . '</dd>';
		$html .= '<dt>' . esc_html__( 'Indicación core', 'nuvanx-medical' ) . '</dt>';
		$html .= '<dd>' . esc_html( $treatment['core'] ) . '</dd>';
		$html .= '</dl>';
		$html .= '<p class="nvx-aes-card__link-wrap"><a class="nvx-aes-card__link" href="' . esc_url( $treatment['url'] ) . '">' . esc_html__( 'Ver protocolo', 'nuvanx-medical' ) . '</a></p>';
		$html .= '</article>';
	}

	$html .= '</div></div></section>';
	return $html;
}

/**
 * Regeneration callout.
 */
function nvx_aesthetic_regen_section_markup(): string {
	$html  = '<section class="nvx-aes-section nvx-aes-regen" aria-labelledby="nvx-aes-regen-title">';
	$html .= '<div class="nvx-aes-section__inner nvx-aes-regen__grid">';
	$html .= '<div>';
	$html .= '<p class="nvx-aes-kicker">' . esc_html__( 'Regeneración', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-aes-regen-title" class="nvx-aes-heading">' . esc_html__( 'El estímulo biológico: firmeza sin volumen', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p class="nvx-aes-body">' . esc_html__( 'Los bioestimuladores (Sculptra®, Radiesse® y protocolos con PDRN) no rellenan: inducen una respuesta celular controlada en la dermis profunda. Los fibroblastos aumentan la síntesis de colágeno y matriz extracelular, densificando la piel y mejorando la turgencia con un resultado progresivo y natural.', 'nuvanx-medical' ) . '</p>';
	$html .= '</div>';
	$html .= '<aside class="nvx-aes-regen__panel" aria-label="' . esc_attr__( 'Criterio regenerativo', 'nuvanx-medical' ) . '">';
	$html .= '<p class="nvx-aes-meta-label">' . esc_html__( 'Criterio clínico', 'nuvanx-medical' ) . '</p>';
	$html .= '<ul class="nvx-aes-panel-list">';
	$html .= '<li><strong>' . esc_html__( 'Sin volumen artificial', 'nuvanx-medical' ) . '</strong> — ' . esc_html__( 'Tensado por neocolagénesis, no por relleno masivo.', 'nuvanx-medical' ) . '</li>';
	$html .= '<li><strong>' . esc_html__( 'Resultado bifásico', 'nuvanx-medical' ) . '</strong> — ' . esc_html__( 'Mejora progresiva entre semanas y meses según el protocolo.', 'nuvanx-medical' ) . '</li>';
	$html .= '<li><strong>' . esc_html__( 'Indicación médica', 'nuvanx-medical' ) . '</strong> — ' . esc_html__( 'Fototipo, elastosis y calidad dérmica definen el plan.', 'nuvanx-medical' ) . '</li>';
	$html .= '</ul></aside></div></section>';
	return $html;
}

/**
 * Clinical FAQs (AEO).
 */
function nvx_aesthetic_faq_section_markup(): string {
	$html  = '<section class="nvx-aes-section nvx-aes-faq" aria-labelledby="nvx-aes-faq-title">';
	$html .= '<div class="nvx-aes-section__inner">';
	$html .= '<p class="nvx-aes-kicker">' . esc_html__( 'Preguntas clínicas', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-aes-faq-title" class="nvx-aes-heading">' . esc_html__( 'Rigor científico sobre inyectables y regeneración', 'nuvanx-medical' ) . '</h2>';
	$html .= '<div class="nvx-faq nvx-aes-faq-list">';

	$html .= '<details class="nvx-brand-faq-item" open>';
	$html .= '<summary><span>' . esc_html__( '¿Cómo influye la reología del ácido hialurónico en el éxito de una armonización facial y cómo se elige el producto adecuado?', 'nuvanx-medical' ) . '</span></summary>';
	$html .= '<div class="nvx-brand-faq-content">';
	$html .= '<p>' . esc_html__( 'La reología es el estudio de la deformación y el flujo de la materia. En medicina estética, las propiedades viscoelásticas de un gel de ácido hialurónico determinan su capacidad para proyectar tejidos o integrarse en zonas móviles. El comportamiento del gel bajo un esfuerzo mecánico se define mediante el módulo de elasticidad complejo (G*), compuesto por el módulo de almacenamiento elástico (G′) y el módulo de pérdida viscoso (G″):', 'nuvanx-medical' ) . '</p>';
	$html .= '<figure class="nvx-aes-formula" aria-label="' . esc_attr__( 'Módulo de almacenamiento elástico G′', 'nuvanx-medical' ) . '">';
	$html .= '<p class="nvx-aes-formula__eq" role="math"><span class="nvx-aes-formula__g">G′</span> = <span class="nvx-aes-formula__frac"><span class="nvx-aes-formula__num">σ<sub>0</sub></span><span class="nvx-aes-formula__den">γ<sub>0</sub></span></span> cos(δ)</p>';
	$html .= '<figcaption class="nvx-aes-formula__cap">' . esc_html__( 'Donde σ₀ representa la amplitud del esfuerzo mecánico aplicado, γ₀ es la amplitud de la deformación resultante, y δ corresponde al ángulo de fase del gel. Un gel con alto G′ ofrece gran resistencia a la deformación y capacidad de elevación: lo indicamos en planos profundos y supraperiosteales (mandíbula, pómulos). En labios u ojeras seleccionamos G′ bajo y alta cohesividad para integración imperceptible sin migración.', 'nuvanx-medical' ) . '</figcaption>';
	$html .= '</figure></div></details>';

	$faqs = array(
		array(
			'q' => __( '¿Qué es la bioestimulación con PDRN (ADN de salmón) y en qué se diferencia de los rellenos de ácido hialurónico?', 'nuvanx-medical' ),
			'a' => __( 'El Polidesoxirribonucleótido (PDRN), comúnmente conocido como ADN de salmón, actúa a nivel celular profundo. A diferencia de los rellenos de ácido hialurónico, cuya función es mecánica (aportar volumen y captar agua), el PDRN se une de forma selectiva a los receptores de adenosina A2A de los fibroblastos, acelerando la síntesis de colágeno, promoviendo la angiogénesis y reparando el ADN dañado por la radiación ultravioleta. Es un tratamiento regenerativo para densificar la piel desde dentro, sin aportar volumen volumétrico.', 'nuvanx-medical' ),
		),
		array(
			'q' => __( '¿Qué es el efecto Tyndall en el tratamiento de ojeras y cómo lo previene el equipo médico de NUVANX?', 'nuvanx-medical' ),
			'a' => __( 'El efecto Tyndall es una complicación estética menor que ocurre cuando la luz incide sobre un depósito de ácido hialurónico colocado demasiado superficial en la piel ultrafina de la ojera, provocando una coloración azulada o grisácea. En Chamberí y Goya lo prevenimos depositando el producto en plano profundo, inmediatamente por encima del periostio, con microcánulas romas, y seleccionando geles con nula capacidad de retención de agua y bajísima dispersión de luz, para un resultado invisible y natural.', 'nuvanx-medical' ),
		),
	);

	foreach ( $faqs as $faq ) {
		$html .= '<details class="nvx-brand-faq-item">';
		$html .= '<summary><span>' . esc_html( $faq['q'] ) . '</span></summary>';
		$html .= '<div class="nvx-brand-faq-content"><p>' . esc_html( $faq['a'] ) . '</p></div>';
		$html .= '</details>';
	}

	$html .= '</div></div></section>';
	return $html;
}

/**
 * Full editorial body after hero.
 */
function nvx_aesthetic_editorial_body_markup(): string {
	return '<div class="nvx-aesthetic-editorial">'
		. nvx_aesthetic_diagnosis_section_markup()
		. nvx_aesthetic_catalog_section_markup()
		. nvx_aesthetic_regen_section_markup()
		. nvx_aesthetic_faq_section_markup()
		. nvx_aesthetic_action_banner_markup()
		. '</div>';
}

/**
 * Extract existing hero media markup when present.
 */
function nvx_aesthetic_extract_hero_media( string $content ): string {
	if ( preg_match( '/<(?:figure|div) class="nvx-brand-hero__media"[\s\S]*?<\/(?:figure|div)>/iu', $content, $m ) ) {
		return $m[0];
	}
	return '';
}

/**
 * Rebuild Medicina Estética hub page.
 */
function nvx_content_restructure_aesthetic_medicine_page( string $content ): string {
	if ( ! nvx_content_is_aesthetic_medicine_page( $content ) ) {
		return $content;
	}

	$media = nvx_aesthetic_extract_hero_media( $content );

	$hero  = '<section class="nvx-brand-hero nvx-brand-hero--medical nvx-aes-hero" aria-labelledby="nvx-med-h1" aria-label="' . esc_attr__( 'Medicina estética NUVANX', 'nuvanx-medical' ) . '">';
	$hero .= '<div class="nvx-brand-hero__inner">';
	$hero .= nvx_aesthetic_hero_copy_markup();
	$hero .= $media;
	$hero .= '</div></section>';

	$body = nvx_aesthetic_editorial_body_markup();
	$out  = $hero . $body;

	if ( preg_match( '/(<div class="nvx-brand-page[^"]*"[^>]*>)/iu', $content, $wrap ) ) {
		$out = $wrap[1] . $out . '</div>';
	} else {
		$out = '<div class="nvx-brand-page nvx-brand-page--medicina-estetica">' . $out . '</div>';
	}

	return $out;
}
add_filter( 'the_content', 'nvx_content_restructure_aesthetic_medicine_page', 19 );
