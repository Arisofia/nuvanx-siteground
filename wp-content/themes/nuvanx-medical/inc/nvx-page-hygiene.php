<?php
/**
 * NUVANX canonical page hygiene for staging/production indexing.
 *
 * - Redirect superseded cookie documents to the Complianz EU statement.
 * - Keep transactional / incomplete-evidence pages out of search results.
 * - Does not print schema or CSS.
 *
 * @package NUVANX_Medical
 */

defined( 'ABSPATH' ) || exit;

/**
 * Redirect superseded cookie documents to the Complianz EU statement (page 577).
 */
function nvx_redirect_superseded_legal_pages() {
	if ( ! is_page() ) {
		return;
	}

	$page_id = (int) get_queried_object_id();

	if ( in_array( $page_id, array( 18, 31 ), true ) ) {
		$target = get_permalink( 577 );

		if ( is_string( $target ) && '' !== $target ) {
			wp_safe_redirect( $target, 301, 'NUVANX' );
			exit;
		}
	}
}
add_action( 'template_redirect', 'nvx_redirect_superseded_legal_pages', 1 );

/**
 * Transactional pages that must not pass PageRank via links (noindex + nofollow).
 *
 * @return int[]
 */
function nvx_nofollow_page_ids() {
	$ids = array( 78 ); // Solicitud recibida — thank-you / transactional.

	/**
	 * Filter page IDs that receive noindex, nofollow.
	 *
	 * @param int[] $ids Page IDs.
	 */
	return array_values( array_unique( array_map( 'intval', apply_filters( 'nvx_nofollow_page_ids', $ids ) ) ) );
}

/**
 * Post IDs that must stay out of the public index (sitemap + robots).
 * Includes nofollow IDs plus incomplete evidence pages (noindex, follow).
 *
 * @return int[]
 */
function nvx_noindex_page_ids() {
	$ids = nvx_nofollow_page_ids();

	// Casos de pacientes: only index after explicit editorial meta.
	if ( '1' !== (string) get_post_meta( 2645, '_nvx_cases_publication_ready', true ) ) {
		$ids[] = 2645;
	}

	/**
	 * Filter page IDs forced to noindex (sitemap exclusion + robots).
	 *
	 * @param int[] $ids Page IDs.
	 */
	return array_values( array_unique( array_map( 'intval', apply_filters( 'nvx_noindex_page_ids', $ids ) ) ) );
}

/**
 * Keep transactional and incomplete evidence pages out of search results.
 *
 * Page 78 (thank-you): noindex, nofollow — do not follow outbound links.
 * Other noindex IDs (e.g. casos until ready): noindex, follow.
 *
 * @param string $robots Existing Yoast robots directive.
 * @return string
 */
function nvx_sensitive_page_robots( $robots ) {
	$page_id = (int) get_queried_object_id();

	if ( in_array( $page_id, nvx_nofollow_page_ids(), true ) ) {
		return 'noindex, nofollow';
	}

	if ( in_array( $page_id, nvx_noindex_page_ids(), true ) ) {
		return 'noindex, follow';
	}

	return $robots;
}
add_filter( 'wpseo_robots', 'nvx_sensitive_page_robots', 20 );

/**
 * Exclude sensitive pages from the Yoast XML sitemap by post ID list.
 *
 * @param int[] $excluded_ids Existing excluded IDs.
 * @return int[]
 */
function nvx_exclude_sensitive_pages_from_sitemap_ids( $excluded_ids ) {
	$excluded_ids = is_array( $excluded_ids ) ? $excluded_ids : array();

	return array_values( array_unique( array_merge( $excluded_ids, nvx_noindex_page_ids() ) ) );
}
add_filter( 'wpseo_exclude_from_sitemap_by_post_ids', 'nvx_exclude_sensitive_pages_from_sitemap_ids' );

/**
 * Belt-and-suspenders: drop sitemap entries for sensitive pages.
 *
 * @param array|false $url  Sitemap URL array or false to exclude.
 * @param string      $type Object type.
 * @param WP_Post     $post Post object.
 * @return array|false
 */
function nvx_filter_sitemap_entry_sensitive_pages( $url, $type, $post ) {
	if ( ! is_object( $post ) || ! isset( $post->ID ) ) {
		return $url;
	}

	if ( in_array( (int) $post->ID, nvx_noindex_page_ids(), true ) ) {
		return false;
	}

	return $url;
}
add_filter( 'wpseo_sitemap_entry', 'nvx_filter_sitemap_entry_sensitive_pages', 20, 3 );

/**
 * Lightweight public HTML hygiene: typos and clichés in inherited CMS content.
 *
 * Theme-rendered pages already use clean strings; this catches residual
 * post_content / shortcode output without rewriting clinical claims.
 *
 * @param string $content HTML content.
 * @return string
 */
function nvx_public_content_text_hygiene( $content ) {
	if ( is_admin() || ! is_string( $content ) || '' === $content ) {
		return $content;
	}

	$replacements = array(
		// Brand / product typo seen in legacy CMS titles.
		'EXILITET' => 'EXILITE™',
		'Exilitet' => 'EXILITE™',
		// Empty brand slogans.
		'Tu mejor versión empieza aquí.' => 'Reserva 15–30 min de valoración médica.',
		'Tu mejor versión empieza aquí'  => 'Reserva 15–30 min de valoración médica',
		// Vague sede framing.
		'enfoque médico premium'                             => 'misma dirección médica que Chamberí',
		'Medicina estética en Goya con enfoque médico premium' => 'Medicina estética láser en Goya–Barrio de Salamanca (CS20073)',
	);

	$content = str_replace( array_keys( $replacements ), array_values( $replacements ), $content );

	// Endolift conflation fixes
	$content = preg_replace('/(Endolift®?\s*)Radiofrecuencia\s+monopolar\s+para\s+firmeza\s+sin\s+cirug[ií]a/iu', '$1Técnica láser subdérmica para firmeza facial, indicada tras valoración médica', $content) ?? $content;
	$content = preg_replace('/Firmeza\s+Endolift®?\s+Radiofrecuencia\s+monopolar\s+para\s+firmeza\s+sin\s+cirug[ií]a/iu', 'Endolift®: técnica láser subdérmica para firmeza facial, indicada tras valoración médica', $content) ?? $content;
	$content = preg_replace('/Endolift®?\s+(?:es|como|mediante)\s+(?:una\s+)?radiofrecuencia\s+monopolar/iu', 'Endolift® es una técnica láser subdérmica', $content) ?? $content;
	$content = preg_replace('/define\s+Endolift®?\s+como\s+radiofrecuencia\s+monopolar/iu', 'describe Endolift® como técnica láser subdérmica', $content) ?? $content;

	// Valoración CTA fixes
	$content = preg_replace( '/\bSolicitar\.(?=\s|<|$)/u', 'Solicitar valoración médica', $content ) ?? $content;

	return $content;
}
add_filter( 'the_content', 'nvx_public_content_text_hygiene', 12 );
add_filter( 'the_title', 'nvx_public_content_text_hygiene', 12 );

/**
 * Remove sensitive pages (e.g., Casos de pacientes ID 2645) from all navigation menus automatically.
 * 
 * @param array $items Array of menu items.
 * @return array
 */
function nvx_exclude_sensitive_pages_from_menus( $items ) {
	if ( ! is_array( $items ) ) {
		return $items;
	}
	$noindex_ids = nvx_noindex_page_ids();
	foreach ( $items as $key => $item ) {
		if ( 'post_type' === $item->type && in_array( (int) $item->object_id, $noindex_ids, true ) ) {
			unset( $items[ $key ] );
		}
	}
	return $items;
}
add_filter( 'wp_get_nav_menu_items', 'nvx_exclude_sensitive_pages_from_menus', 20 );

/**
 * Automate the production business rules to bypass manual DB editing from P0-FINISH-RUNBOOK.md.
 * 
 * @param string $content HTML content.
 * @return string
 */
function nvx_apply_production_business_rules( $content ) {
	if ( ! is_string( $content ) || '' === trim( $content ) ) {
		return $content;
	}

	$page_id = (int) get_queried_object_id();

	// 3. Contacto (14): Strip all HubSpot forms and scripts.
	if ( 14 === $page_id ) {
		$content = preg_replace( '/<script[^>]*hsforms\.net[^>]*><\/script>/i', '', $content ) ?? $content;
		$content = preg_replace( '/<div[^>]*class="[^"]*hbspt-form[^"]*"[^>]*>.*?<\/div>/is', '', $content ) ?? $content;
	}

	// 4. Valoración (2636): Keep only the primary HubSpot form CTA.
	if ( 2636 === $page_id ) {
		$count = 0;
		$content = preg_replace_callback( '/<div[^>]*class="[^"]*hbspt-form[^"]*"[^>]*>.*?<\/div>/is', function( $matches ) use ( &$count ) {
			$count++;
			return $count === 1 ? $matches[0] : '';
		}, $content ) ?? $content;
	}

	// 5. Privacidad y Aviso Legal (3, 20): Add legal placeholder if content is too short (empty).
	if ( in_array( $page_id, array( 3, 20 ), true ) ) {
		if ( strlen( strip_tags( $content ) ) < 200 ) {
			$placeholder = '<div class="nvx-legal-placeholder" style="padding:40px; background:var(--nvx-surface-subtle,#f4f3ef); color:var(--nvx-text-base,#4a4542); font-family:var(--nvx-font-base); font-weight:500; text-align:center; border: 1px dashed var(--nvx-accent-muted);">[Texto legal en revisión por asesoría jurídica (Counsel). Publicación pendiente.]</div>';
			$content = $placeholder . $content;
		}
	}

	// 6. Equipo Médico (1575): Inject verifiable credentials for Cristina Marquez.
	if ( 1575 === $page_id && strpos( $content, 'Cristina Marquez' ) !== false ) {
		if ( strpos( $content, 'Colegiada Nº 282869501' ) === false ) {
			$cred_html = '<p class="nvx-team-credentials" style="font-size:0.875rem; margin-top:0.5rem; color:var(--nvx-text-muted);">Colegiada Nº 282869501 · Máster en Medicina Estética y Antienvejecimiento · Especialista en láser</p>';
			$content = preg_replace( '/(Cristina Marquez.*?<\/h[2-6]>)/i', '$1' . $cred_html, $content ) ?? $content;
		}
	}

	// 7. EXION Precios y FAQ: Strip unapproved Morpheus8 comparatives and explicit pricing in EXION pages.
	if ( stripos( $content, 'EXION' ) !== false || stripos( $content, 'Morpheus' ) !== false ) {
		// Strip comparative Morpheus8 FAQs
		$content = preg_replace( '/<details[^>]*>.*?Morpheus.*?<\/details>/is', '', $content ) ?? $content;
		// Ensure no direct hardcoded pricing for EXION is displayed (replaces digit+€ next to EXION with generic).
		$content = preg_replace( '/(EXION[^<]*?)\b\d{3,4}\s*€/i', '$1 (Presupuesto tras valoración)', $content ) ?? $content;
	}

	return $content;
}
add_filter( 'the_content', 'nvx_apply_production_business_rules', 99 );
