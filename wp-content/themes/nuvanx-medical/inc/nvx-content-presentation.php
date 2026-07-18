<?php
/**
 * Global content presentation layer.
 *
 * Applies by content pattern (classes / known blocks), not by page ID or home-only rules:
 * - dual CTAs (valoración + WhatsApp)
 * - clinical values pillars
 * - method columns
 * - treatment card blurbs
 * - home specialized protocols block (with orientative “desde €” when tariff known)
 * - homepage team strip + well-aging pillar
 * - EXION hub investment transparency (presupuesto tras valoración)
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
if ( ! defined( 'NVX_IVON_COLEGIADO' ) ) {
	define( 'NVX_IVON_COLEGIADO', '284621525' );
}
if ( ! defined( 'NVX_FABIO_COLEGIADO' ) ) {
	define( 'NVX_FABIO_COLEGIADO', '282877543' );
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
 * Canonical site-wide closing conversion band (pre-footer).
 * One markup, one copy, used by footer.php on every non-conversion page.
 */
function nvx_site_closing_cta_markup(): string {
	$valoracion = nvx_cta_valoracion_url();
	$whatsapp   = nvx_cta_whatsapp_url();

	// Already on the valoración form page: primary CTA targets the form anchor.
	if ( function_exists( 'nvx_theme_is_valoracion_form_page' ) && nvx_theme_is_valoracion_form_page() ) {
		$valoracion = trailingslashit( get_permalink() ) . '#nvx-hubspot-form';
	}

	$html  = '<section class="nvx-cta-banner" id="nvx-site-closing-cta" aria-label="' . esc_attr__( 'Solicitar valoración médica', 'nuvanx-medical' ) . '">';
	$html .= '<div class="nvx-cta-banner__inner">';
	$html .= '<div>';
	$html .= '<h2 class="nvx-cta-banner__title">' . esc_html__( 'Reserva 15–30 min de valoración médica', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p class="nvx-cta-banner__sub">' . esc_html__( 'Indicación, plan A/B y presupuesto orientativo — sin compromiso de tratamiento el mismo día. Presencial en Chamberí o Goya.', 'nuvanx-medical' ) . '</p>';
	$html .= '</div>';
	$html .= '<div class="nvx-cta-pair nvx-cta-banner__actions">';
	$html .= sprintf(
		'<a class="nvx-btn nvx-btn--light" id="nvx-footer-cta" href="%1$s">%2$s</a>',
		esc_url( $valoracion ),
		esc_html__( 'Reservar valoración gratuita', 'nuvanx-medical' )
	);
	$html .= sprintf(
		'<a class="nvx-btn nvx-btn--secondary-on-dark" href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
		esc_url( $whatsapp ),
		esc_html__( 'Contactar por WhatsApp', 'nuvanx-medical' )
	);
	$html .= '</div></div></section>';

	return $html;
}

/**
 * Strip page-level closing conversion bands so only the site footer CTA remains.
 *
 * Patterns are intentionally narrow:
 * - known page-module closing class tokens
 * - legacy soft CTAs only when they carry conversion copy/chrome
 * - in-content duplicates of the site closing banner (id / footer-cta hook)
 *
 * @param string $content HTML.
 * @return string
 */
function nvx_content_strip_page_closing_ctas( string $content ): string {
	// Exact module closers (page-local class tokens, not generic sections).
	$patterns = array(
		'/<section\b[^>]*\bclass=["\'][^"\']*\bnvx-endolift-action\b[^"\']*["\'][^>]*>[\s\S]*?<\/section>/iu',
		'/<section\b[^>]*\bclass=["\'][^"\']*\bnvx-catalog-close\b[^"\']*["\'][^>]*>[\s\S]*?<\/section>/iu',
		'/<section\b[^>]*\bclass=["\'][^"\']*\bnvx-laser-action\b[^"\']*["\'][^>]*>[\s\S]*?<\/section>/iu',
		'/<section\b[^>]*\bclass=["\'][^"\']*\bnvx-aes-action\b[^"\']*["\'][^>]*>[\s\S]*?<\/section>/iu',
		'/<section\b[^>]*\bclass=["\'][^"\']*\bnvx-home-cta-final-band\b[^"\']*["\'][^>]*>[\s\S]*?<\/section>/iu',
		'/<div\b[^>]*\bclass=["\'][^"\']*\bnvx-home-cta-final-band\b[^"\']*["\'][^>]*>[\s\S]*?<\/div>/iu',
		'/<section\b[^>]*\bclass=["\'][^"\']*\bnvx-home-cta-final\b[^"\']*["\'][^>]*>[\s\S]*?<\/section>/iu',
		// Explicit in-content copy of the site-wide closing band.
		'/<section\b[^>]*\bid=["\']nvx-site-closing-cta["\'][^>]*>[\s\S]{0,4000}?<\/section>/iu',
		// Duplicate pre-footer banner only when it carries the footer CTA hook.
		'/<section\b[^>]*\bclass=["\'][^"\']*\bnvx-cta-banner\b[^"\']*["\'][^>]*>[\s\S]{0,4000}?\bid=["\']nvx-footer-cta["\'][\s\S]{0,2000}?<\/section>/iu',
		// Legacy CMS soft CTA: require conversion signal inside a bounded block.
		'/<section\b[^>]*\bclass=["\'][^"\']*\bnvx-brand-section--cta\b[^"\']*["\'][^>]*>[\s\S]{0,4000}?(?:valoraci[oó]n|Reservar|consulta m[eé]dica|nvx-brand-btn|nvx-btn)[\s\S]{0,4000}?<\/section>/iu',
	);

	foreach ( $patterns as $pattern ) {
		$content = preg_replace( $pattern, '', $content ) ?? $content;
	}

	return $content;
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
 * Premium action banner immediately after the clinical values columns.
 * Uses design-system CTAs (pill radius) — not square marketing blocks.
 */
function nvx_home_action_banner_markup(): string {
	$valoracion = nvx_cta_valoracion_url();
	$whatsapp   = nvx_cta_whatsapp_url();

	// Stable structural id + data attribute for safe strip/replace (no broad markup regex).
	$html  = '<div id="nvx-post-values-action-banner" class="nvx-home-action-banner-shell" data-nvx-action-banner="post-values">';
	$html .= '<section class="nvx-home-action-banner" aria-labelledby="nvx-home-action-banner-title">';
	$html .= '<div class="nvx-home-action-banner__copy">';
	$html .= '<p class="nvx-brand-kicker nvx-home-action-banner__kicker">' . esc_html__( 'Valoración médica', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-home-action-banner-title" class="nvx-home-action-banner__title">' . esc_html__( '15–30 minutos para saber si existe indicación', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p class="nvx-home-action-banner__text">' . wp_kses(
		__( 'Evaluamos tu caso, explicamos las opciones disponibles y documentamos el presupuesto antes de cualquier decisión. Presencial en <strong>Chamberí</strong> o <strong>Salamanca–Goya</strong>.', 'nuvanx-medical' ),
		array( 'strong' => array() )
	) . '</p>';
	$html .= '</div>';
	$html .= '<div class="nvx-home-action-banner__actions">';
	// Pill CTAs from the design system (radius 999px) — never square blocks.
	$html .= sprintf(
		'<a class="nvx-button nvx-button--light nvx-home-action-banner__cta" href="%1$s">%2$s</a>',
		esc_url( $valoracion ),
		esc_html__( 'Reservar valoración gratuita', 'nuvanx-medical' )
	);
	$html .= sprintf(
		'<a class="nvx-button nvx-button--secondary-on-dark nvx-home-action-banner__cta" href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
		esc_url( $whatsapp ),
		esc_html__( 'Contactar por WhatsApp', 'nuvanx-medical' )
	);
	$html .= '</div></section></div>';

	return $html;
}

/**
 * Clinical values pillars (structured presentation of intro/criterio blocks).
 * Conversion CTAs move to the post-values action banner — clean UI, no inline links.
 */
function nvx_values_section_markup(): string {
	$items = array(
		array(
			'icon'  => 'shield',
			'title' => '1. Diagnóstico antes de tecnología',
			'body'  => 'Cada protocolo comienza con una valoración médica de 15 a 30 minutos: calidad de piel, historial, objetivos y contraindicaciones. Solo se indica un tratamiento cuando existe una razón clínica para hacerlo.',
		),
		array(
			'icon'  => 'laser',
			'title' => '2. Equipamiento médico certificado',
			'body'  => 'Trabajamos con plataformas médicas con marcado CE como DEKA Motus AZ+, Láser CO₂ fraccionado y EXION® BTL. La tecnología y sus parámetros se seleccionan según la anatomía y el objetivo de cada paciente.',
		),
		array(
			'icon'  => 'nature',
			'title' => '3. Resultados naturales y expectativa realista',
			'body'  => 'El objetivo es mejorar firmeza, textura y definición respetando la expresión y la identidad del rostro. Antes de tratar, explicamos qué puede mejorar, qué límites existen y qué recuperación requiere cada protocolo.',
		),
	);

	$html  = '<section class="nvx-brand-section nvx-brand-section--tight nvx-values-section" aria-label="Por qué NUVANX">';
	$html .= '<div class="nvx-shell nvx-brand-section__inner">';
	$html .= '<p class="nvx-brand-kicker">' . esc_html__( 'Por qué NUVANX', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 class="nvx-brand-title">' . esc_html__( 'Medicina estética donde el diagnóstico decide la tecnología', 'nuvanx-medical' ) . '</h2>';
	$html .= '<div class="nvx-values">';

	foreach ( $items as $item ) {
		$html .= '<article class="nvx-value">';
		$html .= '<div class="nvx-value__icon" aria-hidden="true">' . nvx_content_icon_svg( $item['icon'] ) . '</div>';
		$html .= '<h3 class="nvx-value__title">' . esc_html( $item['title'] ) . '</h3>';
		$html .= '<p class="nvx-value__body">' . esc_html( $item['body'] ) . '</p>';
		$html .= '</article>';
	}

	$html .= '</div>';
	// No CTAs inside the pillars — conversion lives in the action banner below.
	$html .= '</div></section>';
	$html .= nvx_home_action_banner_markup();

	return $html;
}

/**
 * Method as three icon columns (distinct from numbered treatment grids).
 */
function nvx_method_section_markup(): string {
	$items = array(
		array(
			'icon'  => 'scan',
			'title' => 'Evaluación individual',
			'body'  => 'Revisamos piel, anatomía, historial, objetivos y contraindicaciones antes de proponer un procedimiento.',
		),
		array(
			'icon'  => 'precision',
			'title' => 'Indicación y parámetros',
			'body'  => 'Definimos tecnología, energía, profundidad y número de sesiones según el caso, no mediante configuraciones estándar.',
		),
		array(
			'icon'  => 'follow',
			'title' => 'Control de evolución',
			'body'  => 'Programamos seguimiento según el tratamiento para valorar respuesta, recuperación y necesidad de ajustes.',
		),
	);

	$html  = '<section class="nvx-brand-section nvx-method-section" aria-label="Cómo trabajamos NUVANX">';
	$html .= '<div class="nvx-shell nvx-brand-section__inner">';
	$html .= '<p class="nvx-brand-kicker">' . esc_html__( 'Cómo trabajamos', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 class="nvx-brand-title">' . esc_html__( 'Un protocolo médico en tres decisiones', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p class="nvx-brand-body nvx-method-lead">' . esc_html__( 'La evaluación, la indicación y el seguimiento forman un único proceso clínico.', 'nuvanx-medical' ) . '</p>';
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
	// Already transformed: keep pillars, ensure post-values action banner exists.
	if ( false !== strpos( $content, 'nvx-values-section' ) || false !== strpos( $content, 'class="nvx-values"' ) ) {
		return nvx_content_ensure_post_values_action_banner( $content );
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

	return nvx_content_ensure_post_values_action_banner( $content );
}

/**
 * Safe preg_replace: never wipe content when the regex engine fails (returns null).
 *
 * @param string   $pattern  Pattern.
 * @param string   $replace  Replacement.
 * @param string   $subject  Subject HTML.
 * @param int      $limit    Limit (-1 = all).
 * @param int|null $count    Optional match count out-param.
 */
function nvx_content_preg_replace_keep( string $pattern, string $replace, string $subject, int $limit = -1, ?int &$count = null ): string {
	$result = preg_replace( $pattern, $replace, $subject, $limit, $count );
	return is_string( $result ) ? $result : $subject;
}

/**
 * Pattern: canonical post-values banner shell (id + data attribute + section child).
 */
function nvx_content_post_values_banner_pattern_with_id(): string {
	return '/<div\b[^>]*\bid=["\']nvx-post-values-action-banner["\'][^>]*\bdata-nvx-action-banner=["\']post-values["\'][^>]*>\s*<section\b[^>]*\bclass=["\'][^"\']*\bnvx-home-action-banner\b[^"\']*["\'][^>]*>[\s\S]*?<\/section>\s*<\/div>/iu';
}

/**
 * Pattern: legacy shell with data attribute only (same rigid shape).
 */
function nvx_content_post_values_banner_pattern_legacy(): string {
	return '/<div\b[^>]*\bdata-nvx-action-banner=["\']post-values["\'][^>]*>\s*<section\b[^>]*\bclass=["\'][^"\']*\bnvx-home-action-banner\b[^"\']*["\'][^>]*>[\s\S]*?<\/section>\s*<\/div>/iu';
}

/**
 * Pattern: legacy values dual-CTA pair only (not other .nvx-cta-pair blocks).
 */
function nvx_content_values_legacy_cta_pattern(): string {
	return '/\s*<div class="nvx-cta-pair nvx-values__cta"[^>]*>[\s\S]*?<\/div>/iu';
}

/**
 * Pattern: values section open…close (structural class only).
 */
function nvx_content_values_section_pattern(): string {
	return '/(<section\b[^>]*\bclass=["\'][^"\']*\bnvx-values-section\b[^"\']*["\'][^>]*>[\s\S]*?<\/section>)/iu';
}

/**
 * Remove the canonical post-values action banner only (stable id + data attribute).
 */
function nvx_content_strip_post_values_action_banner( string $content ): string {
	$content = nvx_content_preg_replace_keep( nvx_content_post_values_banner_pattern_with_id(), '', $content );
	return nvx_content_preg_replace_keep( nvx_content_post_values_banner_pattern_legacy(), '', $content );
}

/**
 * Whether the canonical post-values banner markup is already present.
 */
function nvx_content_has_post_values_action_banner( string $content ): bool {
	return false !== strpos( $content, 'id="nvx-post-values-action-banner"' )
		|| false !== strpos( $content, "id='nvx-post-values-action-banner'" )
		|| false !== strpos( $content, 'data-nvx-action-banner="post-values"' )
		|| false !== strpos( $content, "data-nvx-action-banner='post-values'" );
}

/**
 * Insert / refresh premium action banner right after the values section.
 * Patterns are named helpers scoped to known ids/classes only.
 */
function nvx_content_ensure_post_values_action_banner( string $content ): string {
	// Legacy dual CTA under values pillars only.
	$content = nvx_content_preg_replace_keep( nvx_content_values_legacy_cta_pattern(), '', $content, 1 );

	// Refresh: drop previous canonical banner then re-insert current markup.
	$content = nvx_content_strip_post_values_action_banner( $content );

	// If strip failed partially and marker remains, do not insert a second copy.
	if ( nvx_content_has_post_values_action_banner( $content ) ) {
		return $content;
	}

	$banner = nvx_home_action_banner_markup();
	$count  = 0;
	$updated = nvx_content_preg_replace_keep(
		nvx_content_values_section_pattern(),
		'$1' . $banner,
		$content,
		1,
		$count
	);
	if ( $count > 0 ) {
		return $updated;
	}

	// Fallback: values grid close inside its parent section (still structural).
	$count   = 0;
	$updated = nvx_content_preg_replace_keep(
		'/(<div class="nvx-values">[\s\S]*?<\/div>\s*<\/div>\s*<\/section>)/iu',
		'$1' . $banner,
		$content,
		1,
		$count
	);

	return $count > 0 ? $updated : $content;
}

/**
 * Legacy / CMS method section patterns (pre-transform markup).
 *
 * @return string[]
 */
function nvx_content_method_legacy_patterns(): array {
	return array(
		'/<section\b[^>]*class="[^"]*nvx-v3-metodo[^"]*"[^>]*>[\s\S]*?<\/section>/i',
		'/<section\b[^>]*class="[^"]*nvx-home-metodo[^"]*"[^>]*>[\s\S]*?<\/section>/i',
		// CMS copies that use aria-label Cómo trabajamos but are not yet our columns markup.
		'/<section\b(?![^>]*\bnvx-method-section\b)[^>]*aria-label="[^"]*Cómo trabajamos[^"]*"[^>]*>[\s\S]*?<\/section>/iu',
	);
}

/**
 * Strip leftover method sections after one canonical block is present.
 */
function nvx_content_strip_extra_method_sections( string $content ): string {
	// Drop any remaining legacy CMS method blocks.
	foreach ( nvx_content_method_legacy_patterns() as $pattern ) {
		$content = nvx_content_preg_replace_keep( $pattern, '', $content );
	}

	// Keep only the first canonical method section; remove further copies.
	$seen = 0;
	$updated = preg_replace_callback(
		'/<section\b[^>]*\bclass=["\'][^"\']*\bnvx-method-section\b[^"\']*["\'][^>]*>[\s\S]*?<\/section>/iu',
		static function ( array $m ) use ( &$seen ): string {
			$seen++;
			return ( 1 === $seen ) ? $m[0] : '';
		},
		$content
	);

	return is_string( $updated ) ? $updated : $content;
}

/**
 * Replace numbered method lists with icon columns — at most one block on the page.
 */
function nvx_content_replace_method_sections( string $content ): string {
	// Already transformed: still dedupe if CMS + filter left two copies.
	if ( false !== strpos( $content, 'nvx-method-section' ) || false !== strpos( $content, 'nvx-method-columns' ) ) {
		return nvx_content_strip_extra_method_sections( $content );
	}

	$replacement = nvx_method_section_markup();
	$replaced    = false;

	// One replacement only (first match wins), then strip siblings.
	foreach ( nvx_content_method_legacy_patterns() as $pattern ) {
		$count   = 0;
		$updated = preg_replace( $pattern, $replacement, $content, 1, $count );
		if ( is_string( $updated ) && $count > 0 ) {
			$content  = $updated;
			$replaced = true;
			break;
		}
	}

	if ( $replaced ) {
		$content = nvx_content_strip_extra_method_sections( $content );
	}

	return $content;
}

/**
 * Treatment card blurbs sitewide — clinical only (no prices on home cards).
 */
function nvx_content_enrich_treatment_cards( string $content ): string {
	// Never inject tariffs into cards (home or elsewhere). Prices live on treatment pages.
	$endolift_new = 'Tensado del óvalo, mandíbula y papada con microfibra láser subdérmica tras valoración. Indicado en flacidez leve–moderada y grasa submentoniana seleccionada.';

	$exion_new = 'Plataforma con aplicadores Fractional RF, Face y Body. La elección y el número de sesiones dependen del diagnóstico; no sustituye rellenos ni valoración médica.';

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

	// Strip residual “desde X € / PVP” price fragments that may remain in CMS card bodies on front.
	if ( is_front_page() ) {
		$content = preg_replace(
			'/\s*(?:Tarifa[s]?\s+de\s+referencia\s+)?desde\s+[\d.,]+\s*€[^.<]*(?:\.|$)/iu',
			'',
			$content
		) ?? $content;
		$content = preg_replace(
			'/\s*Papada\s*\/\s*marcación mandibular:\s*[\d.,]+\s*€[^.<]*(?:\.|$)/iu',
			'',
			$content
		) ?? $content;
		$content = preg_replace(
			'/\s*\(PVP[^)]*\)\.?/iu',
			'',
			$content
		) ?? $content;
	}

	return is_string( $content ) ? $content : '';
}

/**
 * Home “Protocolos médicos especializados” — clinical copy + orientative investment when known.
 *
 * @return array<int, array{title:string,lead:string,facts:array<string,string>,url:string}>
 */
function nvx_home_protocols_data(): array {
	$endolift_from = function_exists( 'nvx_format_price_eur' ) && function_exists( 'nvx_endolift_price_from_eur' )
		? nvx_format_price_eur( nvx_endolift_price_from_eur() )
		: '798,60';
	$co2_from      = function_exists( 'nvx_format_price_eur' ) && function_exists( 'nvx_tariff_catalog' )
		? nvx_format_price_eur( nvx_tariff_catalog()['laser_co2']['facial']['pvp'] )
		: '330,00';

	return array(
		array(
			'title' => 'Endolift® facial: papada, mandíbula y cuello',
			'lead'  => 'Microfibra láser bajo la piel para tensar tejido y, cuando hay indicación, reducir grasa local. No sustituye un lifting quirúrgico en todos los casos.',
			'facts' => array(
				'Inversión orientativa' => sprintf( 'Desde %s € (zona ojeras, IVA incl.). Tabla completa en la ficha.', $endolift_from ),
				'Recuperación estimada' => 'Inflamación o hematomas leves habitualmente 3 a 7 días según el caso.',
			),
			'url'   => home_url( '/endolift-facial-papada-mandibula/' ),
		),
		array(
			'title' => 'Endoláser corporal: grasa localizada y contorno',
			'lead'  => 'Protocolo láser ambulatorio para focos de grasa con flacidez leve–moderada. No es tratamiento de obesidad ni liposucción.',
			'facts' => array(
				'Zonas habituales' => 'Abdomen, flancos, muslos, rodillas, brazos y otras áreas seleccionadas.',
				'Inversión'        => 'Presupuesto por zonas tras valoración médica.',
			),
			'url'   => home_url( '/endolaser-corporal-grasa-localizada/' ),
		),
		array(
			'title' => 'Láser CO₂ fraccionado: textura y cicatrices',
			'lead'  => 'Resurfacing fraccionado para cicatrices de acné, poros y fotodaño, con downtime realista según profundidad.',
			'facts' => array(
				'Inversión orientativa' => sprintf( 'Desde %s € sesión facial (IVA incl.).', $co2_from ),
				'Recuperación'          => 'Habitualmente 4 a 7 días de eritema y descamación según protocolo.',
			),
			'url'   => home_url( '/laser-co2-fraccionado-madrid-textura-cicatrices-poro/' ),
		),
	);
}

/**
 * Markup for home specialized protocols section.
 */
function nvx_home_protocols_markup(): string {
	$html  = '<section class="nvx-brand-section nvx-home-protocols" id="nvx-home-protocols" aria-labelledby="nvx-home-protocols-title">';
	$html .= '<div class="nvx-shell nvx-brand-section__inner">';
	$html .= '<p class="nvx-brand-kicker">' . esc_html__( 'Protocolos', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-home-protocols-title" class="nvx-brand-title">' . esc_html__( 'Nuestros Protocolos Médicos Especializados', 'nuvanx-medical' ) . '</h2>';
	$html .= '<div class="nvx-home-protocols__list">';

	foreach ( nvx_home_protocols_data() as $item ) {
		$html .= '<article class="nvx-home-protocol">';
		$html .= '<h3 class="nvx-home-protocol__title">' . esc_html( $item['title'] ) . '</h3>';
		$html .= '<p class="nvx-home-protocol__lead">' . esc_html( $item['lead'] ) . '</p>';
		if ( ! empty( $item['facts'] ) ) {
			$html .= '<dl class="nvx-home-protocol__facts">';
			foreach ( $item['facts'] as $label => $value ) {
				$html .= '<div class="nvx-home-protocol__fact">';
				$html .= '<dt>' . esc_html( $label ) . '</dt>';
				$html .= '<dd>' . esc_html( $value ) . '</dd>';
				$html .= '</div>';
			}
			$html .= '</dl>';
		}
		$html .= '<p class="nvx-home-protocol__more"><a class="nvx-brand-inline-link" href="' . esc_url( $item['url'] ) . '">' . esc_html__( 'Ver protocolo', 'nuvanx-medical' ) . '</a></p>';
		$html .= '</article>';
	}

	$html .= '</div></div></section>';

	return $html;
}

/**
 * Ensure front page has one protocols block after Cómo trabajamos (or after values banner).
 */
function nvx_content_ensure_home_protocols( string $content ): string {
	if ( ! is_front_page() ) {
		return $content;
	}

	// Already present once: drop extras.
	if ( false !== strpos( $content, 'nvx-home-protocols' ) || false !== strpos( $content, 'id="nvx-home-protocols"' ) ) {
		$seen    = 0;
		$updated = preg_replace_callback(
			'/<section\b[^>]*\bnvx-home-protocols\b[^>]*>[\s\S]*?<\/section>/iu',
			static function ( array $m ) use ( &$seen ): string {
				$seen++;
				// Refresh first copy with current markup; drop further copies.
				return ( 1 === $seen ) ? nvx_home_protocols_markup() : '';
			},
			$content
		);
		return is_string( $updated ) ? $updated : $content;
	}

	$block = nvx_home_protocols_markup();

	// Prefer after single Cómo trabajamos section.
	$count   = 0;
	$updated = preg_replace(
		'/(<section\b[^>]*\bnvx-method-section\b[^>]*>[\s\S]*?<\/section>)/iu',
		'$1' . $block,
		$content,
		1,
		$count
	);
	if ( is_string( $updated ) && $count > 0 ) {
		return $updated;
	}

	// After post-values action banner.
	$count   = 0;
	$updated = preg_replace(
		'/(id=["\']nvx-post-values-action-banner["\'][\s\S]*?<\/div>\s*<\/div>)/iu',
		'$1' . $block,
		$content,
		1,
		$count
	);
	if ( is_string( $updated ) && $count > 0 ) {
		return $updated;
	}

	// Fallback: append before last CTA-ish section or at end of content.
	return $content . $block;
}

/**
 * Homepage team strip — surfaces the 3-physician hospital team (audit v2 differentiator).
 */
function nvx_home_team_strip_markup(): string {
	$equipo  = home_url( '/equipo-medico/' );
	$director = defined( 'NVX_DIRECTOR_COLEGIADO' ) ? NVX_DIRECTOR_COLEGIADO : '282864786';
	$ivon     = defined( 'NVX_IVON_COLEGIADO' ) ? NVX_IVON_COLEGIADO : '284621525';
	$fabio    = defined( 'NVX_FABIO_COLEGIADO' ) ? NVX_FABIO_COLEGIADO : '282877543';
	$lead     = sprintf(
		/* translators: 1: director ICOMEM, 2: Dra. Ivon ICOMEM, 3: Dr. Fabio ICOMEM */
		__( 'NUVANX está liderada por el Dr. José Javier Rivera Tejeda (ICOMEM %1$s), Director Médico especialista en Endolift® y láser CO₂. La Dra. Ivon Yamileth Rivera Deras (ICOMEM %2$s), FEA del Hospital La Paz, aporta well-aging y geriatría preventiva. El Dr. Fabio Augusto Quiñónez Bareiro (ICOMEM %3$s), PhD (UAM) e investigador CIBERFES, integra fisiología del envejecimiento y paciente complejo.', 'nuvanx-medical' ),
		$director,
		$ivon,
		$fabio
	);

	$html  = '<section class="nvx-brand-section nvx-home-team-strip" id="nvx-home-team" aria-labelledby="nvx-home-team-title" data-nvx-home-block="team">';
	$html .= '<div class="nvx-shell nvx-brand-section__inner">';
	$html .= '<p class="nvx-brand-kicker">' . esc_html__( 'Equipo médico', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-home-team-title" class="nvx-brand-title">' . esc_html__( 'Tres médicos colegiados. Investigación hospitalaria. Un solo objetivo.', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p class="nvx-brand-lead">' . esc_html( $lead ) . '</p>';
	$html .= '<p class="nvx-brand-lead">' . esc_html__(
		'El criterio médico en NUVANX no es un claim de marketing: es el resultado de un equipo con experiencia hospitalaria real.',
		'nuvanx-medical'
	) . '</p>';
	$html .= '<p class="nvx-home-team-strip__cta"><a class="nvx-brand-btn nvx-brand-btn--secondary" href="' . esc_url( $equipo ) . '">' . esc_html__( 'Conocer al equipo médico', 'nuvanx-medical' ) . '</a></p>';
	$html .= '</div></section>';
	return $html;
}

/**
 * Homepage well-aging pillar (unique vs pure aesthetic competitors).
 */
function nvx_home_wellaging_strip_markup(): string {
	$html  = '<section class="nvx-brand-section nvx-home-wellaging" id="nvx-home-wellaging" aria-labelledby="nvx-home-wellaging-title" data-nvx-home-block="wellaging">';
	$html .= '<div class="nvx-shell nvx-brand-section__inner">';
	$html .= '<p class="nvx-brand-kicker">' . esc_html__( 'Well-aging', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-home-wellaging-title" class="nvx-brand-title">' . esc_html__( 'Más allá de la estética: medicina del envejecimiento saludable', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p class="nvx-brand-lead">' . esc_html__(
		'Integramos well-aging con base en geriatría preventiva y longevidad — un enfoque que los centros solo cosméticos no pueden ofrecer. Tratar la piel es también tratar el tejido que envejece, con médicos formados en fisiología del envejecimiento, no solo en aparatología.',
		'nuvanx-medical'
	) . '</p>';
	$html .= '<p class="nvx-brand-lead">' . esc_html__(
		'Láser, inductores de colágeno y protocolos regenerativos se diseñan con esa visión: resultados naturales hoy y tejido más saludable a largo plazo, siempre tras indicación médica.',
		'nuvanx-medical'
	) . '</p>';
	$html .= '</div></section>';
	return $html;
}

/**
 * Ensure home has team + well-aging strips after protocols (or after method).
 */
function nvx_content_ensure_home_team_wellaging( string $content ): string {
	if ( ! is_front_page() ) {
		return $content;
	}

	$team = nvx_home_team_strip_markup();
	$well = nvx_home_wellaging_strip_markup();

	// Refresh existing blocks.
	if ( false !== strpos( $content, 'id="nvx-home-team"' ) || false !== strpos( $content, "id='nvx-home-team'" ) ) {
		$content = preg_replace(
			'/<section\b[^>]*\bid=["\']nvx-home-team["\'][^>]*>[\s\S]*?<\/section>/iu',
			$team,
			$content,
			1
		) ?? $content;
	}
	if ( false !== strpos( $content, 'id="nvx-home-wellaging"' ) || false !== strpos( $content, "id='nvx-home-wellaging'" ) ) {
		$content = preg_replace(
			'/<section\b[^>]*\bid=["\']nvx-home-wellaging["\'][^>]*>[\s\S]*?<\/section>/iu',
			$well,
			$content,
			1
		) ?? $content;
	}

	$need_team = false === strpos( $content, 'id="nvx-home-team"' ) && false === strpos( $content, "id='nvx-home-team'" );
	$need_well = false === strpos( $content, 'id="nvx-home-wellaging"' ) && false === strpos( $content, "id='nvx-home-wellaging'" );
	if ( ! $need_team && ! $need_well ) {
		return $content;
	}

	$insert = ( $need_team ? $team : '' ) . ( $need_well ? $well : '' );

	// After protocols.
	$count   = 0;
	$updated = preg_replace(
		'/(<section\b[^>]*\bid=["\']nvx-home-protocols["\'][^>]*>[\s\S]*?<\/section>)/iu',
		'$1' . $insert,
		$content,
		1,
		$count
	);
	if ( is_string( $updated ) && $count > 0 ) {
		return $updated;
	}

	// After method section.
	$count   = 0;
	$updated = preg_replace(
		'/(<section\b[^>]*\bnvx-method-section\b[^>]*>[\s\S]*?<\/section>)/iu',
		'$1' . $insert,
		$content,
		1,
		$count
	);
	if ( is_string( $updated ) && $count > 0 ) {
		return $updated;
	}

	return $content . $insert;
}
add_filter( 'the_content', 'nvx_content_ensure_home_team_wellaging', 125 );

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
	$body = __( 'Especialista en Endolift®, láser CO₂ y medicina estética facial. La valoración, la indicación y el seguimiento se realizan con criterio médico. Martes y jueves: Chamberí. Miércoles: Salamanca–Goya.', 'nuvanx-medical' );

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
		__( 'La dirección médica de NUVANX corresponde al Dr. José Javier Rivera Tejeda (Colegiado ICOMEM Nº %s). El equipo clínico realiza valoración, indicación y seguimiento en ambas sedes con un protocolo individual.', 'nuvanx-medical' ),
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
 * FAQ: EXION vs Morpheus8 — clinical comparison (long-tail GEO), not brand superiority ads.
 */
function nvx_content_rewrite_morpheus_faq( string $content ): string {
	$answer  = '<p>' . esc_html__( 'Sí, puede serlo según el diagnóstico. Ambos actúan con radiofrecuencia fraccionada y microagujas; EXION® Fractional RF añade control de impedancia y feedback tisular que permite dosificar la energía de forma más predecible en dermis profunda.', 'nuvanx-medical' ) . '</p>';
	$answer .= '<p>' . esc_html__( 'No hay un ranking comercial universal: la elección depende del objetivo (textura, flacidez, cicatrices), la calidad de piel, el fototipo y la recuperación aceptable. En NUVANX la indicación se define en valoración médica, no por marca.', 'nuvanx-medical' ) . '</p>';
	$answer .= '<p><a class="nvx-brand-inline-link" href="' . esc_url( home_url( '/exion-fractional/' ) ) . '">' . esc_html__( 'Ver EXION® Fractional RF', 'nuvanx-medical' ) . '</a></p>';

	$updated = preg_replace(
		'/(<summary><span>¿EXION® Fractional RF es una alternativa a Morpheus8\?<\/span><\/summary>\s*<div class="nvx-brand-faq-content">)([\s\S]*?)(<\/div>\s*<\/details>)/u',
		'$1' . $answer . '$3',
		$content
	);

	return is_string( $updated ) ? $updated : $content;
}

/**
 * Whether current request is the EXION® BTL hub page.
 */
function nvx_content_is_exion_hub(): bool {
	if ( function_exists( 'nvx_schema_path_matches' ) && function_exists( 'nvx_schema_current_path' ) ) {
		$path = nvx_schema_current_path( (int) get_queried_object_id() );
		if ( nvx_schema_path_matches( $path, '/exion-btl/' ) ) {
			return true;
		}
	}
	if ( is_singular( 'page' ) ) {
		$slug = get_post_field( 'post_name', get_queried_object_id() );
		return is_string( $slug ) && 'exion-btl' === $slug;
	}
	return false;
}

/**
 * EXION hub investment transparency — no invented retail PVP (tariff sheet not yet locked).
 */
function nvx_exion_investment_markup(): string {
	$html  = '<section class="nvx-brand-section nvx-exion-investment" id="inversion-exion" aria-labelledby="nvx-exion-investment-title" data-nvx-block="exion-investment">';
	$html .= '<div class="nvx-shell nvx-brand-section__inner">';
	$html .= '<p class="nvx-brand-kicker">' . esc_html__( 'Inversión', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-exion-investment-title" class="nvx-brand-title">' . esc_html__( 'Precio de EXION® BTL en NUVANX', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p class="nvx-brand-lead">' . esc_html__(
		'El PVP de EXION® no se publica como tarifa fija online porque depende del aplicador (Face, Body o Fractional RF), de la zona, del número de sesiones y de si se combina con otros protocolos. El presupuesto se documenta por escrito tras la valoración médica gratuita.',
		'nuvanx-medical'
	) . '</p>';
	$html .= '<ul class="nvx-brand-list nvx-exion-investment__factors">';
	$html .= '<li>' . esc_html__( 'Aplicador y profundidad del protocolo (Face / Body / Fractional RF).', 'nuvanx-medical' ) . '</li>';
	$html .= '<li>' . esc_html__( 'Zona o superficie tratada y objetivo clínico (textura, firmeza, contorno).', 'nuvanx-medical' ) . '</li>';
	$html .= '<li>' . esc_html__( 'Plan de sesiones habitual (a menudo 2–4) y posibles combinaciones médicas.', 'nuvanx-medical' ) . '</li>';
	$html .= '</ul>';
	$html .= '<p class="nvx-brand-lead">' . esc_html__(
		'Si buscas “EXION BTL precio Madrid”, la respuesta honesta es: sin exploración no hay cifra fiable. En la consulta cerramos indicación, plan y PVP con IVA incluido antes de cualquier decisión.',
		'nuvanx-medical'
	) . '</p>';
	$html .= '<p class="nvx-exion-investment__cta">' . nvx_cta_pair_markup( 'nvx-exion-investment__actions' ) . '</p>';
	$html .= '</div></section>';
	return $html;
}

/**
 * Inject / refresh EXION investment block on the hub page.
 */
function nvx_content_ensure_exion_investment( string $content ): string {
	if ( ! nvx_content_is_exion_hub() ) {
		return $content;
	}

	$block = nvx_exion_investment_markup();

	if ( false !== strpos( $content, 'id="inversion-exion"' ) || false !== strpos( $content, "id='inversion-exion'" ) ) {
		$updated = preg_replace(
			'/<section\b[^>]*\bid=["\']inversion-exion["\'][^>]*>[\s\S]*?<\/section>/iu',
			$block,
			$content,
			1
		);
		return is_string( $updated ) ? $updated : $content;
	}

	// After first FAQ accordion or before last CTA cluster; fallback append.
	$count   = 0;
	$updated = preg_replace(
		'/(<section\b[^>]*\b(?:nvx-brand-faq|nvx-faq|nvx-home-faq)[^>]*>)/iu',
		$block . '$1',
		$content,
		1,
		$count
	);
	if ( is_string( $updated ) && $count > 0 ) {
		return $updated;
	}

	return $content . $block;
}
add_filter( 'the_content', 'nvx_content_ensure_exion_investment', 126 );

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
			$attrs = preg_replace( '/\s*href=["\'][^"\']*["\']/i', '', $attrs ) ?? $attrs;
			return '<a' . $attrs . ' href="' . esc_url( $valoracion_url ) . '">' . $m[2] . '</a>';
		},
		$content
	);

	// WhatsApp anchors → wa.me (preserve classes).
	$content = preg_replace_callback(
		'/<a\b([^>]*)>(\s*Contactar por WhatsApp\s*)<\/a>/iu',
		static function ( array $m ) use ( $whatsapp_url ): string {
			$attrs = $m[1];
			$attrs = preg_replace( '/\s*href=["\'][^"\']*["\']/i', '', $attrs ) ?? $attrs;
			$attrs = preg_replace( '/\s*target=["\'][^"\']*["\']/i', '', $attrs ) ?? $attrs;
			$attrs = preg_replace( '/\s*rel=["\'][^"\']*["\']/i', '', $attrs ) ?? $attrs;
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
 * Strip inline style attributes from hero containers so cascade wins without
 * the important flag (CSS Gate).
 */
function nvx_content_strip_hero_inline_styles( string $content ): string {
	// Opening tags for hero stages / copy that may carry legacy inline layout.
	$hero_bits = 'nvx-brand-hero|nvx-editorial-hero|nvx-page-hero|nvx-hero|nvx-home-hero-stage';
	$copy_bits = 'nvx-brand-hero__copy|nvx-hero__copy|nvx-page-hero__copy|nvx-editorial-hero__copy';
	$inner_bits = 'nvx-brand-hero__inner|nvx-hero__inner|nvx-page-hero__inner';
	$pattern    = '/(<(?:section|div)\b[^>]*\bclass="[^"]*\b(?:' . $hero_bits . '|' . $copy_bits . '|' . $inner_bits . ')\b[^"]*"[^>]*)\s+style="[^"]*"/iu';
	$updated    = preg_replace( $pattern, '$1', $content );
	return is_string( $updated ) ? $updated : $content;
}

/**
 * Append a CSS class token to an HTML attribute string.
 */
function nvx_html_attrs_add_class( string $attrs, string $class_token ): string {
	if ( preg_match( '/\bclass=(["\'])([^"\']*)\1/i', $attrs, $cm ) ) {
		if ( false !== strpos( $cm[2], $class_token ) ) {
			return $attrs;
		}
		$updated = preg_replace(
			'/\bclass=(["\'])/',
			'class=$1' . $class_token . ' ',
			$attrs,
			1
		);
		return is_string( $updated ) ? $updated : $attrs;
	}

	return $attrs . ' class="' . esc_attr( $class_token ) . '"';
}

/**
 * Normalize body figures/images so every page shares the same media rules.
 * Heroes are left untouched (full-bleed stage) — extracted before body tagging.
 */
function nvx_content_normalize_body_media( string $content ): string {
	// Protect hero media blocks so imgs inside never get nvx-media--body (was cutting heroes with a gray band).
	$hero_slots = array();
	$protected  = preg_replace_callback(
		'/<((?:figure|div))\b([^>]*\bclass=["\'][^"\']*\bnvx-(?:brand|editorial|page)?-?hero__media\b[^"\']*["\'][^>]*)>([\s\S]*?)<\/\1>/iu',
		static function ( array $m ) use ( &$hero_slots ): string {
			$key                = '<!--NVX_HERO_MEDIA_' . count( $hero_slots ) . '-->';
			$hero_slots[ $key ] = $m[0];
			return $key;
		},
		$content
	);
	$content = is_string( $protected ) ? $protected : $content;

	// Portraits + formula stages must not get body-figure margins / height:auto.
	$skip_figure = 'nvx-content-figure|nvx-endolift-formula|nvx-laser-formula|nvx-aes-formula|nvx-equipo-portrait|nvx-brand-card__media|nvx-brand-card__media--portrait';

	$updated = preg_replace_callback(
		'/<figure\b([^>]*)>/iu',
		static function ( array $m ) use ( $skip_figure ): string {
			$attrs = $m[1];
			if ( preg_match( '/' . $skip_figure . '/i', $attrs ) ) {
				return '<figure' . $attrs . '>';
			}
			return '<figure' . nvx_html_attrs_add_class( $attrs, 'nvx-content-figure' ) . '>';
		},
		$content
	);
	$content = is_string( $updated ) ? $updated : $content;

	// Protect team / card portrait frames (doctor role, not body landscape crop).
	$team_slots = array();
	$protected  = preg_replace_callback(
		'/<figure\b([^>]*\bclass=["\'][^"\']*\b(?:nvx-brand-card__media|nvx-equipo-portrait)\b[^"\']*["\'][^>]*)>([\s\S]*?)<\/figure>/iu',
		static function ( array $m ) use ( &$team_slots ): string {
			$attrs = $m[1];
			// Only card media gets the portrait media class; authority figures keep nvx-equipo-portrait.
			if ( false !== stripos( $attrs, 'nvx-brand-card__media' ) ) {
				$attrs = nvx_html_attrs_add_class( $attrs, 'nvx-brand-card__media--portrait' );
			}
			$inner = $m[2];
			$inner = preg_replace( '/\bnvx-media--body\b/i', 'nvx-media--doctor', $inner ) ?? $inner;
			$inner = preg_replace_callback(
				'/<img\b([^>]*)>/iu',
				static function ( array $im ): string {
					$a = $im[1];
					if ( preg_match( '/nvx-logo|nvx-media--hero/i', $a ) ) {
						return '<img' . $a . '>';
					}
					$a = preg_replace( '/\s+style=["\'][^"\']*["\']/i', '', $a ) ?? $a;
					$a = preg_replace( '/\s*nvx-media--body\s*/i', ' ', $a ) ?? $a;
					$a = nvx_html_attrs_add_class( $a, 'nvx-media' );
					$a = nvx_html_attrs_add_class( $a, 'nvx-media--doctor' );
					return '<img' . $a . '>';
				},
				$inner
			);
			$key                = '<!--NVX_TEAM_MEDIA_' . count( $team_slots ) . '-->';
			$team_slots[ $key ] = '<figure' . $attrs . '>' . ( is_string( $inner ) ? $inner : $m[2] ) . '</figure>';
			return $key;
		},
		$content
	);
	$content = is_string( $protected ) ? $protected : $content;

	$updated = preg_replace_callback(
		'/<img\b([^>]*)>/iu',
		static function ( array $m ): string {
			$attrs = $m[1];
			if ( preg_match( '/nvx-logo|nvx-home-hero|nvx-media--hero|nvx-media--doctor/i', $attrs ) ) {
				return '<img' . $attrs . '>';
			}

			$attrs = preg_replace( '/\s+style=["\'][^"\']*["\']/i', '', $attrs ) ?? $attrs;
			// Strip accidental body role if ever re-processed on a hero path.
			$attrs = preg_replace( '/\s*nvx-media--body\s*/i', ' ', $attrs ) ?? $attrs;
			$attrs = nvx_html_attrs_add_class( $attrs, 'nvx-media' );
			$attrs = nvx_html_attrs_add_class( $attrs, 'nvx-media--body' );

			return '<img' . $attrs . '>';
		},
		$content
	);
	$content = is_string( $updated ) ? $updated : $content;

	// Restore protected media untouched.
	if ( ! empty( $team_slots ) ) {
		$content = str_replace( array_keys( $team_slots ), array_values( $team_slots ), $content );
	}
	// Restore hero media untouched (no body classes, full-bleed cover intact).
	if ( ! empty( $hero_slots ) ) {
		$content = str_replace( array_keys( $hero_slots ), array_values( $hero_slots ), $content );
	}

	return $content;
}

/**
 * Remove body façade block when the page hero already shows the clinic photo.
 *
 * @param string $content HTML.
 * @return string
 */
function nvx_content_strip_duplicate_fachada( string $content ): string {
	if ( ! preg_match( '/nvx-(?:brand|page|editorial)-hero__media/i', $content ) ) {
		return $content;
	}

	$updated = preg_replace(
		'/\s*<section\b[^>]*\bnvx-brand-section--fachada\b[^>]*>[\s\S]*?<\/section>/iu',
		'',
		$content,
		1
	);

	return is_string( $updated ) ? $updated : $content;
}

/**
 * Rewrite CMS versioned class tokens to canonical names (no v3/v4 layers).
 *
 * @param string $content HTML.
 * @return string
 */
function nvx_content_strip_versioned_class_tokens( string $content ): string {
	$map = array(
		'nvx-editorial-home-v4' => '',
		'nvx-v3-shell'          => 'nvx-shell',
		'nvx-v3-intro'          => '',
		'nvx-v3-metodo'         => '',
		'nvx-v3-tratamientos'   => 'nvx-home-tratamientos',
		'nvx-v3-direccion'      => 'nvx-home-direccion',
		'nvx-v3-cta-final'      => 'nvx-home-cta-final',
		'nvx-v3-faq'            => '',
	);

	foreach ( $map as $from => $to ) {
		// Whole class token only (not substrings of longer BEM names).
		$pattern = '/(?<=[\s"\'])' . preg_quote( $from, '/' ) . '(?=[\s"\'])/u';
		$content = preg_replace( $pattern, $to, $content ) ?? $content;
	}

	// Collapse leftover double spaces inside class attributes.
	$content = preg_replace_callback(
		'/\bclass=(["\'])([^"\']*)\1/u',
		static function ( array $m ): string {
			$q     = $m[1];
			$clean = preg_replace( '/\s+/u', ' ', trim( $m[2] ) ) ?? $m[2];
			return 'class=' . $q . $clean . $q;
		},
		$content
	) ?? $content;

	return $content;
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

	$content = nvx_content_strip_hero_inline_styles( $content );
	$content = nvx_content_strip_duplicate_fachada( $content );
	$content = nvx_content_normalize_body_media( $content );
	$content = nvx_content_replace_values_sections( $content );
	$content = nvx_content_replace_method_sections( $content );
	$content = nvx_content_ensure_home_protocols( $content );
	$content = nvx_content_enrich_treatment_cards( $content );
	$content = nvx_content_enhance_director_blocks( $content );
	$content = nvx_content_rewrite_morpheus_faq( $content );
	$content = nvx_content_unify_ctas( $content );
	$content = nvx_content_strip_versioned_class_tokens( $content );
	// Closing CTA strip runs once at priority 99 (after page modules at ~19 rebuild content).

	return $content;
}
add_filter( 'the_content', 'nvx_content_presentation_enhance', 20 );

/**
 * Single late strip of page-local closing CTAs after modules rebuild the_content.
 * Only footer.php nvx-cta-banner remains as the site-wide closing band.
 */
function nvx_content_strip_page_closing_ctas_late( string $content ): string {
	if ( is_admin() || is_feed() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return $content;
	}
	return nvx_content_strip_page_closing_ctas( $content );
}
add_filter( 'the_content', 'nvx_content_strip_page_closing_ctas_late', 99 );

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
