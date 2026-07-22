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
	$class = trim( 'nvx-cta-cluster ' . $extra_class );
	return '<div class="' . esc_attr( $class ) . '">
		<button class="nvx-button nvx-button--primary nvx-open-valoracion-modal" data-nvx-valoracion-modal="1" aria-haspopup="dialog" data-gtag="click-reserve">
			<span>Solicitar valoración médica</span>
		</button>
		<a href="' . esc_url( nvx_cta_whatsapp_url() ) . '" class="nvx-button nvx-button--secondary" target="_blank" rel="noopener noreferrer" data-gtag="click-whatsapp">
			<svg class="icon-whatsapp" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 0 0-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z"/></svg>
			Contactar por WhatsApp
		</a>
	</div>';
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
	$html .= '<p class="nvx-cta-banner__kicker">Medicina estética con criterio clínico</p>';
	$html .= '<h2 class="nvx-cta-banner__title">Da el siguiente paso con una valoración médica personalizada.</h2>';
	$html .= '<p class="nvx-cta-banner__sub">Plan individualizado &bull; Precisión clínica &bull; Recuperación según tu caso</p>';
	$html .= '</div>';
	$html .= '<div class="nvx-cta-pair nvx-cta-banner__actions">';
	$html .= sprintf(
		'<a class="nvx-btn nvx-btn--light nvx-open-valoracion-modal" id="nvx-footer-cta" href="%1$s" data-nvx-valoracion-modal="1" aria-haspopup="dialog">%2$s</a>',
		esc_url( $valoracion ),
		esc_html__( 'Iniciar mi valoración médica', 'nuvanx-medical' )
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
		$content = nvx_content_preg_replace_keep( $pattern, '', $content );
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
	$html .= '<p class="nvx-eyebrow nvx-home-action-banner__kicker">' . esc_html__( 'Valoración médica', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-home-action-banner-title" class="nvx-home-action-banner__title">' . esc_html__( '15–30 minutos para saber si existe indicación', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p class="nvx-home-action-banner__text">' . wp_kses(
		__( 'Evaluamos tu caso, explicamos las opciones disponibles y documentamos el presupuesto antes de cualquier decisión. Presencial en <strong>Chamberí</strong> o <strong>Salamanca–Goya</strong>.', 'nuvanx-medical' ),
		array( 'strong' => array() )
	) . '</p>';
	$html .= '</div>';
	$html .= '<div class="nvx-home-action-banner__actions">';
	// Pill CTAs from the design system (radius 999px) — never square blocks.
	$html .= sprintf(
		'<a class="nvx-button nvx-button--light nvx-home-action-banner__cta nvx-open-valoracion-modal" href="%1$s" data-nvx-valoracion-modal="1" aria-haspopup="dialog">%2$s</a>',
		esc_url( $valoracion ),
			esc_html__( 'Solicitar valoración médica', 'nuvanx-medical' )
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
	$html .= '<p class="nvx-eyebrow">' . esc_html__( 'Por qué NUVANX', 'nuvanx-medical' ) . '</p>';
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
	$html .= '<p class="nvx-eyebrow">' . esc_html__( 'Cómo trabajamos', 'nuvanx-medical' ) . '</p>';
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
		$updated = nvx_content_preg_replace_keep( $pattern, $replacement, $content, 1, $count );
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
	$result = nvx_content_preg_replace_keep( $pattern, $replace, $subject, $limit, $count );
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
	$updated = nvx_content_preg_replace_keep(
		'/<section\b[^>]*\bclass=["\'][^"\']*\bnvx-method-section\b[^"\']*["\'][^>]*>[\s\S]*?<\/section>/iu',
		static function ( array $m ) use ( &$seen ): string {
			$seen++;
			return ( 1 === $seen ) ? $m[0] : '';
		},
		$content
	);

	return $updated;
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
		$updated = nvx_content_preg_replace_keep( $pattern, $replacement, $content, 1, $count );
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
	$content = nvx_content_preg_replace_keep(
		'/(<h3 class="nvx-brand-card__title">\s*Endolift® Facial[\s\S]*?<\/h3>\s*<p class="nvx-brand-card__body">)([\s\S]*?)(<\/p>)/u',
		'$1' . esc_html( $endolift_new ) . '$3',
		$content
	);

	// Any brand-card titled EXION®…
	$content = nvx_content_preg_replace_keep(
		'/(<h3 class="nvx-brand-card__title">\s*EXION®[\s\S]*?<\/h3>\s*<p class="nvx-brand-card__body">)([\s\S]*?)(<\/p>)/u',
		'$1' . esc_html( $exion_new ) . '$3',
		$content
	);

	// Strip residual “desde X € / PVP” price fragments that may remain in CMS card bodies on front.
	if ( is_front_page() ) {
		$content = nvx_content_preg_replace_keep(
			'/\s*(?:Tarifa[s]?\s+de\s+referencia\s+)?desde\s+[\d.,]+\s*€[^.<]*(?:\.|$)/iu',
			'',
			$content
		) ?? $content;
		$content = nvx_content_preg_replace_keep(
			'/\s*Papada\s*\/\s*marcación mandibular:\s*[\d.,]+\s*€[^.<]*(?:\.|$)/iu',
			'',
			$content
		) ?? $content;
		$content = nvx_content_preg_replace_keep(
			'/\s*\(PVP[^)]*\)\.?/iu',
			'',
			$content
		) ?? $content;
	}

	return is_string( $content ) ? $content : $content; /* fixed */
}



/**
 * Ensure front page has one protocols block after Cómo trabajamos (or after values banner).
 */
function nvx_content_ensure_home_protocols( string $content ): string {
	if ( ! is_front_page() ) {
		return $content;
	}

	// The protocols section has been removed from the homepage.
	// Strip any existing canonical or legacy protocols blocks.
	$content = nvx_content_preg_replace_keep( '/<section\b[^>]*\bnvx-home-protocols\b[^>]*>[\s\S]*?<\/section>/iu', '', $content );
	$content = nvx_content_preg_replace_keep( '/<section\b[^>]*>(?:(?!<\/section>)[\s\S])*?Protocolos Médicos Especializados(?:(?!<\/section>)[\s\S])*?<\/section>/iu', '', $content ) ?? $content;

	return $content;
}

/**
 * Homepage team strip — surfaces the 3-physician hospital team (audit v2 differentiator).
 */
function nvx_home_team_strip_markup(): string {
	$equipo  = home_url( '/equipo-medico/' );
	$director = defined( 'NVX_DIRECTOR_COLEGIADO' ) ? NVX_DIRECTOR_COLEGIADO : '282864786';
	$ivon     = defined( 'NVX_IVON_COLEGIADO' ) ? NVX_IVON_COLEGIADO : '284621525';
	$fabio    = defined( 'NVX_FABIO_COLEGIADO' ) ? NVX_FABIO_COLEGIADO : '282877543';
	$dr_jose  = sprintf( __( 'Dr. José Javier Rivera Tejeda (ICOMEM %s): Director Médico especialista en Endolift® y tratamientos con láser CO₂.', 'nuvanx-medical' ), $director );
	$dra_ivon = sprintf( __( 'Dra. Ivon Yamileth Rivera Deras (ICOMEM %s): Médico Especialista (FEA) en el Hospital La Paz, experta en well-aging y geriatría preventiva.', 'nuvanx-medical' ), $ivon );
	$dr_fabio = sprintf( __( 'Dr. Fabio Augusto Quiñónez Bareiro (ICOMEM %s): Doctor por la UAM e investigador en el CIBERFES, especializado en la fisiología del envejecimiento y el paciente complejo.', 'nuvanx-medical' ), $fabio );

	$html  = '<section class="nvx-brand-section nvx-home-team-strip" id="nvx-home-team" aria-labelledby="nvx-home-team-title" data-nvx-home-block="team">';
	$html .= '<div class="nvx-shell nvx-brand-section__inner">';
	$html .= '<p class="nvx-eyebrow">' . esc_html__( 'Equipo médico', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-home-team-title" class="nvx-brand-title">' . esc_html__( 'Experiencia clínica hospitalaria aplicada a la estética', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p class="nvx-brand-lead">' . esc_html__( 'En NUVANX, la excelencia no es solo una promesa; es el resultado de un equipo médico con trayectoria directa en el entorno hospitalario.', 'nuvanx-medical' ) . '</p>';
	$html .= '<p class="nvx-brand-lead">' . esc_html( $dr_jose ) . '<br><br>' . esc_html( $dra_ivon ) . '<br><br>' . esc_html( $dr_fabio ) . '</p>';
	$html .= '<p class="nvx-brand-lead">' . esc_html__( 'Abordamos el cuidado de la piel con exploración clínica, expectativas realistas y seguimiento médico cuando está indicado.', 'nuvanx-medical' ) . '</p>';
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
	$html .= '<p class="nvx-eyebrow">' . esc_html__( 'Well-aging', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-home-wellaging-title" class="nvx-brand-title">' . esc_html__( 'Medicina del envejecimiento: salud desde el interior', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p class="nvx-brand-lead">' . esc_html__(
		'No nos limitamos a la apariencia superficial. Unimos el cuidado de la piel con la geriatría preventiva y el estudio de la longevidad, un enfoque clínico global que va mucho más allá de la estética convencional. Entendemos que mejorar la piel implica tratar el tejido profundo que envejece. Por eso, nuestro equipo está formado en la fisiología médica del cuerpo humano, no solo en el manejo de máquinas.',
		'nuvanx-medical'
	) . '</p>';
	$html .= '<p class="nvx-brand-lead">' . esc_html__(
			'Diseñamos cada tratamiento de láser, inductores de colágeno y medicina regenerativa con criterio médico. La indicación, los límites y el seguimiento se explican de forma individual antes de decidir.',
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

	// Remove legacy duplicated CMS blocks that are replaced by the canonical team strip.
	$content = nvx_content_preg_replace_keep( '/<section\b[^>]*>(?:(?!<\/section>)[\s\S])*?Liderazgo y Experiencia(?:(?!<\/section>)[\s\S])*?<\/section>/iu', '', $content ) ?? $content;
	$content = nvx_content_preg_replace_keep( '/<section\b[^>]*>(?:(?!<\/section>)[\s\S])*?Registro sanitario(?:(?!<\/section>)[\s\S])*?<\/section>/iu', '', $content ) ?? $content;

	$team = nvx_home_team_strip_markup();
	$well = nvx_home_wellaging_strip_markup();

	// Refresh existing blocks.
	if ( false !== strpos( $content, 'id="nvx-home-team"' ) || false !== strpos( $content, "id='nvx-home-team'" ) ) {
		$content = nvx_content_preg_replace_keep(
			'/<section\b[^>]*\bid=["\']nvx-home-team["\'][^>]*>[\s\S]*?<\/section>/iu',
			$team,
			$content,
			1
		);
	}
	if ( false !== strpos( $content, 'id="nvx-home-wellaging"' ) || false !== strpos( $content, "id='nvx-home-wellaging'" ) ) {
		$content = nvx_content_preg_replace_keep(
			'/<section\b[^>]*\bid=["\']nvx-home-wellaging["\'][^>]*>[\s\S]*?<\/section>/iu',
			$well,
			$content,
			1
		);
	}

	$need_team = false === strpos( $content, 'id="nvx-home-team"' ) && false === strpos( $content, "id='nvx-home-team'" );
	$need_well = false === strpos( $content, 'id="nvx-home-wellaging"' ) && false === strpos( $content, "id='nvx-home-wellaging'" );
	if ( ! $need_team && ! $need_well ) {
		return $content;
	}

	$insert = ( $need_team ? $team : '' ) . ( $need_well ? $well : '' );

	// After protocols.
	$count   = 0;
	$updated = nvx_content_preg_replace_keep(
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
	$updated = nvx_content_preg_replace_keep(
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

	$content = nvx_content_preg_replace_keep(
		'/(class="nvx-brand-card__kicker">\s*Dr\.\s*José Javier Rivera Tejeda\s*<\/p>\s*<h3 class="nvx-brand-card__title">)([\s\S]*?)(<\/h3>\s*<p class="nvx-brand-card__body">)([\s\S]*?)(<\/p>)/u',
		'$1' . esc_html( $role ) . '$3' . esc_html( $body ) . '$5',
		$content
	);

	// Alternate: title holds the name.
	$content = nvx_content_preg_replace_keep(
		'/(class="nvx-brand-card__title">\s*Dr\.\s*José Javier Rivera Tejeda\s*)(Director Médico[^<]*)?(<\/h3>\s*<p class="nvx-brand-card__body">)([\s\S]*?)(<\/p>)/u',
		'$1' . esc_html( $role ) . '$3' . esc_html( $body ) . '$5',
		$content
	);

	$lead = sprintf(
		/* translators: %s: medical license number */
		__( 'La dirección médica de NUVANX corresponde al Dr. José Javier Rivera Tejeda (Colegiado ICOMEM Nº %s). El equipo clínico realiza valoración, indicación y seguimiento en ambas sedes con un protocolo individual.', 'nuvanx-medical' ),
		$colegiado
	);

	$content = nvx_content_preg_replace_keep(
		'/(Nuestro equipo médico, liderado por el Dr\.\s*José Javier Rivera Tejeda)([^<]*)(<\/p>)/u',
		esc_html( $lead ) . '$3',
		$content
	);

	return is_string( $content ) ? $content : $content; /* fixed */
}

/**
 * Remove legacy branded-comparison FAQs until their evidence and legal review
 * are completed. Product pages should answer patient questions, not attack
 * alternatives by name.
 */
function nvx_content_rewrite_morpheus_faq( string $content ): string {
	$updated = nvx_content_preg_replace_keep(
		'/<details\b[^>]*>[\s\S]*?<\/details>/iu',
		static function ( array $matches ): string {
			return preg_match( '/\bMorpheus8\b/iu', $matches[0] ) ? '' : $matches[0];
		},
		$content
	);

	return $updated;
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
	$html .= '<p class="nvx-eyebrow">' . esc_html__( 'Inversión', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-exion-investment-title" class="nvx-brand-title">' . esc_html__( 'Precio de EXION® BTL en NUVANX', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p class="nvx-brand-lead">' . esc_html__(
			'El PVP de EXION® no se publica como tarifa fija online porque depende del aplicador (Face, Body o Fractional RF), de la zona, del número de sesiones y de si se combina con otros protocolos. El presupuesto se documenta por escrito tras la valoración médica.',
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
		$updated = nvx_content_preg_replace_keep(
			'/<section\b[^>]*\bid=["\']inversion-exion["\'][^>]*>[\s\S]*?<\/section>/iu',
			$block,
			$content,
			1
		);
		return $updated;
	}

	// After first FAQ accordion or before last CTA cluster; fallback append.
	$count   = 0;
	$updated = nvx_content_preg_replace_keep(
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
	$primary_label  = 'Iniciar mi valoración médica';
	$whatsapp_label = 'Contactar por WhatsApp';
	$valoracion_url = nvx_cta_valoracion_url();
	$whatsapp_url   = nvx_cta_whatsapp_url();

	// Paired hero / brand action clusters: primary + secondary.
	// Since we are upgrading to a new HTML structure (hero-cta-group with a <button>),
	// we completely replace the inner contents of these wrappers.
	$content = nvx_content_preg_replace_keep(
		'/<div\s+class="([^"]*(?:nvx-home-hero-ctas|nvx-brand-actions|nvx-page__cta|nvx-cta-pair)[^"]*)">[\s\S]*?<\/div>/u',
		static function ( array $m ): string {
			// Do not recursively nest if it already has nvx-cta-cluster.
			if ( strpos( $m[1], 'nvx-cta-cluster' ) !== false ) {
				return $m[0];
			}
			return nvx_cta_pair_markup( $m[1] );
		},
		$content
	);

	// Label normalization for remaining anchors.
	$label_map = array(
		'Solicitar valoración médica personalizada' => $primary_label,
		'Solicitar valoración médica'               => $primary_label,
		'Solicitar valoración médica gratuita'      => $primary_label,
		'Solicitar consulta médica personalizada'   => $primary_label,
		'Solicitar consulta médica'                 => $primary_label,
		'Solicitar consulta'                        => $primary_label,
		'Solicitar información'                     => $primary_label,
		'Agenda tu Valoración médica personalizada' => $primary_label,
		'Pedir cita'                                => $primary_label,
		'Reservar cita'                             => $primary_label,
		'Valoración gratuita'                       => $primary_label,
		'Cita online'                               => $primary_label,
		'Enviar'                                    => $primary_label,
		'RESERVAR CITA'                             => $primary_label,
		'Explorar tratamientos exclusivos'          => $whatsapp_label,
	);

	foreach ( $label_map as $from => $to ) {
		$content = str_ireplace( '>' . $from . '<', '>' . $to . '<', $content );
	}

	// Primary conversion anchors → valoración URL (preserve classes).
	$content = nvx_content_preg_replace_keep(
		'/<a\b([^>]*)>(\s*Iniciar mi valoración médica\s*)<\/a>/iu',
		static function ( array $m ) use ( $valoracion_url ): string {
			$attrs = $m[1];
			$attrs = nvx_content_preg_replace_keep( '/\s*href=["\'][^"\']*["\']/i', '', $attrs );
			return '<a' . $attrs . ' href="' . esc_url( $valoracion_url ) . '">' . $m[2] . '</a>';
		},
		$content
	);

	// WhatsApp anchors → wa.me (preserve classes).
	$content = nvx_content_preg_replace_keep(
		'/<a\b([^>]*)>(\s*Contactar por WhatsApp\s*)<\/a>/iu',
		static function ( array $m ) use ( $whatsapp_url ): string {
			$attrs = $m[1];
			$attrs = nvx_content_preg_replace_keep( '/\s*href=["\'][^"\']*["\']/i', '', $attrs );
			$attrs = nvx_content_preg_replace_keep( '/\s*target=["\'][^"\']*["\']/i', '', $attrs );
			$attrs = nvx_content_preg_replace_keep( '/\s*rel=["\'][^"\']*["\']/i', '', $attrs );
			return '<a' . $attrs . ' href="' . esc_url( $whatsapp_url ) . '" target="_blank" rel="noopener noreferrer">' . $m[2] . '</a>';
		},
		$content
	);

	// Invitation free-text blocks → dual CTA pair.
	$content = nvx_content_preg_replace_keep(
		'/<div class="nvx-home-invitation">[\s\S]*?<\/div>/u',
		nvx_cta_pair_markup( 'nvx-home-invitation' ),
		$content
	);

	// Final band CTAs.
	$content = nvx_content_preg_replace_keep(
		'/(class="[^"]*nvx-home-cta-final-band[^"]*"[\s\S]*?<a[^>]*href=")[^"]*("[^>]*>)([^<]*)(<\/a>)/u',
		'$1' . esc_url( $valoracion_url ) . '$2' . esc_html( $primary_label ) . '$4',
		$content
	);

	return is_string( $content ) ? $content : $content; /* fixed */
}

/**
 * Strip inline style attributes from hero containers so cascade wins without
 * the important flag (CSS Gate).
 */
function nvx_content_strip_hero_inline_styles( string $content ): string {
	// Opening tags for hero stages / copy that may carry legacy inline layout.
	$hero_bits = 'nvx-brand-hero|nvx-editorial-hero|nvx-page-hero|nvx-hero|nvx-home-hero-stage';
	$copy_bits = 'nvx-editorial-hero__copy|nvx-hero__copy|nvx-page-hero__copy|nvx-editorial-hero__copy';
	$inner_bits = 'nvx-brand-hero__inner|nvx-hero__inner|nvx-page-hero__inner';
	$pattern    = '/(<(?:section|div)\b[^>]*\bclass="[^"]*\b(?:' . $hero_bits . '|' . $copy_bits . '|' . $inner_bits . ')\b[^"]*"[^>]*)\s+style="[^"]*"/iu';
	$updated    = nvx_content_preg_replace_keep( $pattern, '$1', $content );
	return $updated;
}

/**
 * Append a CSS class token to an HTML attribute string.
 */
function nvx_html_attrs_add_class( string $attrs, string $class_token ): string {
	if ( preg_match( '/\bclass=(["\'])([^"\']*)\1/i', $attrs, $cm ) ) {
		if ( false !== strpos( $cm[2], $class_token ) ) {
			return $attrs;
		}
		$updated = nvx_content_preg_replace_keep(
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
	$protected  = nvx_content_preg_replace_keep(
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
	$skip_figure = 'nvx-content-figure|nvx-endolift-formula|nvx-laser-formula|nvx-equipo-portrait|nvx-brand-card__media|nvx-brand-card__media--portrait';

	$updated = nvx_content_preg_replace_keep(
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
	$protected  = nvx_content_preg_replace_keep(
		'/<figure\b([^>]*\bclass=["\'][^"\']*\b(?:nvx-brand-card__media|nvx-equipo-portrait)\b[^"\']*["\'][^>]*)>([\s\S]*?)<\/figure>/iu',
		static function ( array $m ) use ( &$team_slots ): string {
			$attrs = $m[1];
			// Only card media gets the portrait media class; authority figures keep nvx-equipo-portrait.
			if ( false !== stripos( $attrs, 'nvx-brand-card__media' ) ) {
				$attrs = nvx_html_attrs_add_class( $attrs, 'nvx-brand-card__media--portrait' );
			}
			$inner = $m[2];
			$inner = nvx_content_preg_replace_keep( '/\bnvx-media--body\b/i', 'nvx-media--doctor', $inner );
			$inner = nvx_content_preg_replace_keep(
				'/<img\b([^>]*)>/iu',
				static function ( array $im ): string {
					$a = $im[1];
					if ( preg_match( '/nvx-logo|nvx-media--hero/i', $a ) ) {
						return '<img' . $a . '>';
					}
					$a = nvx_content_preg_replace_keep( '/\s+style=["\'][^"\']*["\']/i', '', $a );
					$a = nvx_content_preg_replace_keep( '/\s*nvx-media--body\s*/i', ' ', $a );
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

	$updated = nvx_content_preg_replace_keep(
		'/<img\b([^>]*)>/iu',
		static function ( array $m ): string {
			$attrs = $m[1];
			if ( preg_match( '/nvx-logo|nvx-home-hero|nvx-media--hero|nvx-media--doctor/i', $attrs ) ) {
				return '<img' . $attrs . '>';
			}

			$attrs = nvx_content_preg_replace_keep( '/\s+style=["\'][^"\']*["\']/i', '', $attrs );
			// Strip accidental body role if ever re-processed on a hero path.
			$attrs = nvx_content_preg_replace_keep( '/\s*nvx-media--body\s*/i', ' ', $attrs );
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

	$updated = nvx_content_preg_replace_keep(
		'/\s*<section\b[^>]*\bnvx-brand-section--fachada\b[^>]*>[\s\S]*?<\/section>/iu',
		'',
		$content,
		1
	);

	return $updated;
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
		$content = nvx_content_preg_replace_keep( $pattern, $to, $content );
	}

	// Collapse leftover double spaces inside class attributes.
	$content = nvx_content_preg_replace_keep(
		'/\bclass=(["\'])([^"\']*)\1/u',
		static function ( array $m ): string {
			$q     = $m[1];
			$clean = nvx_content_preg_replace_keep( '/\s+/u', ' ', trim( $m[2] ) ) ?? $m[2];
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


/**
 * Global Before/After Teaser markup (sitewide).
 */
function nvx_before_after_teaser_markup(): string {
	$cases_page_id = 2645;
	if ( function_exists( 'nvx_noindex_page_ids' ) && in_array( $cases_page_id, nvx_noindex_page_ids(), true ) ) {
		return '';
	}

	$url = get_permalink( $cases_page_id );
	if ( ! is_string( $url ) || '' === $url ) {
		return '';
	}
	$html  = '<section class="nvx-ba-teaser" aria-label="' . esc_attr__( 'Resultados clínicos', 'nuvanx-medical' ) . '">';
	$html .= '<div class="nvx-ba-teaser__inner">';
	$html .= '<div class="nvx-ba-teaser__copy">';
	$html .= '<p class="nvx-ba-teaser__kicker">' . esc_html__( 'Evidencia clínica', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 class="nvx-ba-teaser__title">' . esc_html__( 'Resultados reales, sin filtros', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p class="nvx-ba-teaser__body">' . esc_html__( 'Explora nuestra galería de casos clínicos documentados. Evolución real de pacientes NUVANX sometidos a protocolos láser y médico-estéticos.', 'nuvanx-medical' ) . '</p>';
	$html .= '</div>';
	$html .= '<div class="nvx-ba-teaser__cta">';
	$html .= sprintf(
		'<a href="%1$s" class="nvx-btn nvx-btn--light">%2$s</a>',
		esc_url( $url ),
		esc_html__( 'Ver galería de resultados', 'nuvanx-medical' )
	);
	$html .= '</div></div></section>';
	return $html;
}

/**
 * Global Treatment Process markup (generic).
 */
function nvx_treatment_process_markup(): string {
	$html  = '<section class="nvx-treatment-process" aria-label="' . esc_attr__( 'Proceso clínico', 'nuvanx-medical' ) . '">';
	$html .= '<div class="nvx-treatment-process__inner">';
	$html .= '<p class="nvx-treatment-process__kicker">' . esc_html__( 'El procedimiento', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 class="nvx-treatment-process__title">' . esc_html__( 'Cómo funciona tu tratamiento', 'nuvanx-medical' ) . '</h2>';
	$html .= '<blockquote class="nvx-equipo-blockquote"><p>' . esc_html__( 'El día más importante de tu protocolo no es el de la sesión. Es el seguimiento.', 'nuvanx-medical' ) . '</p></blockquote>';
	$html .= '<ol class="nvx-treatment-process__steps">';
	$steps = array(
		array( 'Valoración médica', 'Evaluación presencial de tu anatomía y calidad cutánea para confirmar la indicación exacta del tratamiento.' ),
		array( 'Procedimiento', 'Intervención ambulatoria de alta precisión, respetando tiempos biológicos y maximizando el confort.' ),
		array( 'Seguimiento', 'Pautas de cuidado domiciliario y revisión en consulta para documentar y asegurar la correcta evolución.' ),
	);
	foreach ( $steps as $step ) {
		$html .= '<li class="nvx-treatment-process__step">';
		$html .= '<h3 class="nvx-treatment-process__step-title">' . esc_html( $step[0] ) . '</h3>';
		$html .= '<p class="nvx-treatment-process__step-body">' . esc_html( $step[1] ) . '</p>';
		$html .= '</li>';
	}
	$html .= '</ol></div></section>';
	return $html;
}

/**
 * Generic FAQ markup for pages missing them.
 */
function nvx_generic_faq_markup(): string {
	$html  = '<section class="nvx-brand-section nvx-faq-section" aria-labelledby="nvx-generic-faq-title">';
	$html .= '<div class="nvx-shell nvx-brand-section__inner">';
	$html .= '<h2 class="nvx-brand-title" id="nvx-generic-faq-title">' . esc_html__( 'Preguntas Frecuentes', 'nuvanx-medical' ) . '</h2>';
	$html .= '<div class="nvx-faq nvx-generic-faq-list">';

	$faqs = array(
		array( '¿Duele el procedimiento?', 'La percepción varía según el umbral personal y la zona. Según el protocolo pueden usarse anestesia local, frío o cremas tópicas para mejorar el confort; la experiencia se valora de forma individual en consulta.' ),
		array( '¿Cuánta recuperación necesito?', 'Depende del tratamiento, la intensidad del protocolo y tu respuesta individual. Algunos procedimientos permiten retomar la actividad habitual con rapidez; otros (por ejemplo, láser ablativo) implican eritema, descamación o varios días de curación. La pauta exacta se define en la valoración y el consentimiento.' ),
		array( '¿Cuándo se notan los resultados?', 'Depende del mecanismo del tratamiento. Algunos cambios se aprecian en los primeros días; cuando el objetivo es estimular colágeno, la evolución se valora a lo largo de semanas. No hay un calendario único para todos los protocolos.' ),
		array( '¿Es para mí?', 'La candidatura clínica solo puede determinarse de forma responsable mediante una valoración médica presencial. Estudiamos tu historial, la viabilidad de los tejidos y tus objetivos para trazar el plan adecuado.' ),
	);

	foreach ( $faqs as $i => $faq ) {
		$open = ( 0 === $i ) ? ' open' : '';
		$html .= '<details class="nvx-brand-faq-item"' . $open . '>';
		$html .= '<summary><span>' . esc_html( $faq[0] ) . '</span><span class="nvx-brand-faq-icon"></span></summary>';
		$html .= '<div class="nvx-brand-faq-item__body"><p>' . esc_html( $faq[1] ) . '</p></div>';
		$html .= '</details>';
	}

	$html .= '</div></div></section>';
	return $html;
}

/**
 * Determines whether the content belongs to a treatment detail or hub page eligible for shared section injection.
 *
 * @param string $content The page content to inspect for treatment and non-treatment markers.
 * @return bool `true` if the content identifies a treatment page, `false` otherwise.
 */
function nvx_content_is_treatment_injection_target( string $content ): bool {
	// Explicit non-treatment shells that share layout classes with treatments.
	if (
		preg_match(
			'/nvx-equipo-editorial|nvx-equipo-hero|nvx-brand-page--nosotros|nvx-brand-page--equipo|id=["\']nvx-nosotros-h1["\']|id=["\']nvx-equipo-h1["\']|aria-label=["\']Equipo médico NUVANX["\']|aria-label=["\']Sobre Nosotros NUVANX["\']/iu',
			$content
		)
	) {
		return false;
	}

	// Canonical treatment routes from the schema page registry.
	if ( function_exists( 'nvx_schema_resolve_treatment_key' ) ) {
		$key = nvx_schema_resolve_treatment_key( (int) get_queried_object_id() );
		if ( null !== $key && '' !== (string) $key ) {
			return true;
		}
	}

	// Explicit treatment identifiers only (no bare nvx-editorial-page / nvx-brand-page--*).
	$treatment_markers = array(
		'nvx-endolaser-editorial',
		'nvx-endolaser-hero',
		'nvx-co2-editorial',
		'nvx-co2-hero',
		'nvx-btl-editorial',
		'nvx-aesthetic-editorial',
		'nvx-laser-editorial',
		'nvx-laser-hero',
		'nvx-brand-page--laser',
		'nvx-brand-page--medicina-estetica',
		'nvx-brand-page--exion',
		'id="nvx-endolift-h1"',
		"id='nvx-endolift-h1'",
		'id="nvx-endolaser-h1"',
		"id='nvx-endolaser-h1'",
		'id="nvx-co2-h1"',
		"id='nvx-co2-h1'",
		'id="nvx-laser-h1"',
		"id='nvx-laser-h1'",
		'id="nvx-med-h1"',
		"id='nvx-med-h1'",
		'aria-label="Endolift facial NUVANX"',
		"aria-label='Endolift facial NUVANX'",
		'aria-label="Medicina estética láser NUVANX"',
		"aria-label='Medicina estética láser NUVANX'",
	);

	foreach ( $treatment_markers as $marker ) {
		if ( false !== stripos( $content, $marker ) ) {
			return true;
		}
	}

	return false;
}


/**
 * Auto-inject shared treatment sections into real treatment pages that lack them.
 */
function nvx_content_inject_global_treatment_sections( string $content ): string {
	if ( is_admin() || is_feed() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return $content;
	}
	if ( ! is_singular( 'page' ) && ! is_page() ) {
		return $content;
	}

	if ( ! nvx_content_is_treatment_injection_target( $content ) ) {
		return $content;
	}

	$injections = '';

	// 1. Before/After teaser (promotional gallery link — no numeric claims).
	if ( false === strpos( $content, 'nvx-ba-teaser' ) ) {
		$injections .= nvx_before_after_teaser_markup();
	}

	// 2. Trust badges intentionally omitted until claims-register approved figures exist.

	// 3. How It Works / Process — skip when the page already documents process/downtime.
	$has_process = preg_match(
		'/nvx-method-section|nvx-endolift-process|nvx-co2-downtime|nvx-co2-timeline|nvx-treatment-process|Procedimiento, sesiones y cuidados/iu',
		$content
	);
	if ( ! $has_process ) {
		$injections .= nvx_treatment_process_markup();
	}

	// 4. FAQ — only if the page has none. Skip CO₂: recovery is protocol-specific and
	// already described on-page (do not inject generic “immediate return” answers).
	$has_faq = preg_match( '/nvx-brand-faq-item|nvx-faq|nvx-generic-faq-list/iu', $content );
	$is_co2  = (
		false !== strpos( $content, 'nvx-co2-editorial' )
		|| false !== strpos( $content, 'nvx-co2-hero' )
		|| false !== strpos( $content, 'nvx-co2-downtime' )
		|| ( function_exists( 'nvx_schema_resolve_treatment_key' )
			&& 'laser_co2' === nvx_schema_resolve_treatment_key( (int) get_queried_object_id() ) )
	);
	if ( ! $has_faq && ! $is_co2 ) {
		$injections .= nvx_generic_faq_markup();
	}

	if ( '' === $injections ) {
		return $content;
	}

	if ( preg_match( '/<\/div>\s*$/i', $content ) ) {
		$replaced = nvx_content_preg_replace_keep( '/(<\/div>\s*)$/i', $injections . '$1', $content );
		return is_string( $replaced ) ? $replaced : $content . $injections;
	}

	return $content . $injections;
}
add_filter( 'the_content', 'nvx_content_inject_global_treatment_sections', 21 );

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
