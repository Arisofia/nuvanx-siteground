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
 * Force 404 on patient cases gallery (ID 2645) to avoid empty galleries online.
 */
function nvx_force_404_empty_cases() {
	if ( is_page( 2645 ) && '1' !== (string) get_post_meta( 2645, '_nvx_cases_publication_ready', true ) ) {
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
		// Do not exit: WordPress must still select and render its 404 template.
	}
}
add_action( 'template_redirect', 'nvx_force_404_empty_cases', 1 );

/**
 * Comparison articles retained for internal medical/evidence review only.
 *
 * They are intentionally not deleted here: editorial and clinical teams need a
 * reversible review path. Until a reviewer approves substantiated, non-
 * denigrating copy, they cannot be surfaced in public archive, search or XML
 * sitemap listings.
 *
 * @return string[]
 */
function nvx_quarantined_comparison_post_slugs(): array {
	return array(
		'exion-face-vs-hifu-ultherapy-thermage-regeneracion-endogena',
		'exion-body-vs-coolsculpting-morpheus8-lipolisis-retraccion',
		'exion-fractional-vs-morpheus8-potenza-ia-vs-trauma',
		'emfusion-vs-hydrafacial-dermapen-microcanales-acusticos',
		'protocolos-combinados-ecosistema-nuvanx-exion-endolift-emfusion',
	);
}

/**
 * Resolve quarantined post IDs without assuming fixed database IDs.
 *
 * @return int[]
 */
function nvx_quarantined_comparison_post_ids(): array {
	static $ids = null;
	if ( is_array( $ids ) ) {
		return $ids;
	}

	$ids = array();
	foreach ( nvx_quarantined_comparison_post_slugs() as $slug ) {
		$post = get_page_by_path( $slug, OBJECT, 'post' );
		if ( $post instanceof WP_Post ) {
			$ids[] = (int) $post->ID;
		}
	}

	return array_values( array_unique( $ids ) );
}

/**
 * Keep pending comparison content out of public post collections.
 */
function nvx_exclude_quarantined_comparison_posts( WP_Query $query ): void {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}

	if ( ! $query->is_home() && ! $query->is_archive() && ! $query->is_search() && ! $query->is_feed() ) {
		return;
	}

	$ids = nvx_quarantined_comparison_post_ids();
	if ( array() === $ids ) {
		return;
	}

	$existing = $query->get( 'post__not_in' );
	$existing = is_array( $existing ) ? $existing : array();
	$query->set( 'post__not_in', array_values( array_unique( array_merge( $existing, $ids ) ) ) );
}
add_action( 'pre_get_posts', 'nvx_exclude_quarantined_comparison_posts', 30 );

/**
 * Post IDs that must stay out of the public index (sitemap + robots).
 * Includes nofollow IDs plus incomplete evidence pages (noindex, follow).
 *
 * @return int[]
 */
function nvx_noindex_page_ids() {
	$ids = nvx_nofollow_page_ids();
	$ids = array_merge( $ids, nvx_quarantined_comparison_post_ids() );

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
 * post_content / shortcode output without rewriting clinical claims. It runs
 * after route-specific renderers so a legacy phrase cannot be reintroduced by
 * a managed page module later in the_content.
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

	// Do not advertise a price condition that is not confirmed in this source.
	$content = preg_replace( '/\bvaloraci[oó]n\s+m[eé]dica\s+gratuita\b/iu', 'valoración médica', $content ) ?? $content;
	$content = preg_replace( '/\bvaloraci[oó]n\s+gratuita\b/iu', 'valoración médica', $content ) ?? $content;
	$content = preg_replace( '/\bvaloraci[oó]n\s+gratis\b/iu', 'valoración médica', $content ) ?? $content;
	$content = preg_replace( '/\bconsulta\s+(?:m[eé]dica\s+)?gratuita\b/iu', 'consulta médica', $content ) ?? $content;
	$content = preg_replace( '/\bconsulta\s+gratis\b/iu', 'consulta médica', $content ) ?? $content;
	$content = preg_replace( '/\bpresupuesto\s+personalizado\b/iu', 'presupuesto individualizado tras la valoración médica', $content ) ?? $content;
	$content = preg_replace( '/\bsin\s+compromiso\b/iu', 'sin obligación de continuar con un tratamiento', $content ) ?? $content;

	// Endolift conflation fixes.
	$content = preg_replace( '/(Endolift®?\s*)Radiofrecuencia\s+monopolar\s+para\s+firmeza\s+sin\s+cirug[ií]a/iu', '$1Técnica láser subdérmica para firmeza facial, indicada tras valoración médica', $content ) ?? $content;
	$content = preg_replace( '/Firmeza\s+Endolift®?\s+Radiofrecuencia\s+monopolar\s+para\s+firmeza\s+sin\s+cirug[ií]a/iu', 'Endolift®: técnica láser subdérmica para firmeza facial, indicada tras valoración médica', $content ) ?? $content;
	$content = preg_replace( '/Endolift®?\s+(?:es|como|mediante)\s+(?:una\s+)?radiofrecuencia\s+monopolar/iu', 'Endolift® es una técnica láser subdérmica', $content ) ?? $content;
	$content = preg_replace( '/define\s+Endolift®?\s+como\s+radiofrecuencia\s+monopolar/iu', 'describe Endolift® como técnica láser subdérmica', $content ) ?? $content;

	// Critical clinical fix for Clínicas NUVANX (ID 1399) or any residual conflation.
	if ( 1399 === (int) get_queried_object_id() ) {
		$content = preg_replace( '/(Endolift®?)[^\.]*(?:es|como|mediante)?\s*(?:una\s+)?radiofrecuencia[^\.]*/iu', '$1 (tecnología láser subdérmica de 1470 nm)', $content ) ?? $content;
		$content = preg_replace( '/\bradiofrecuencia\s+Endolift\b/iu', 'láser subdérmico Endolift', $content ) ?? $content;
	}

	// Valoración CTA fixes.
	$content = preg_replace( '/\bSolicitar\.(?=\s|<|$)/u', 'Solicitar valoración médica', $content ) ?? $content;

	return $content;
}
// Keep this after all page-specific builders (the valoración module runs at 16).
add_filter( 'the_content', 'nvx_public_content_text_hygiene', 240 );
add_filter( 'the_title', 'nvx_public_content_text_hygiene', 240 );

/**
 * Keep QA on staging2 inside the same environment when legacy CMS copy uses
 * absolute production URLs. Production keeps its public URLs untouched.
 */
function nvx_normalize_staging2_internal_links( $content ) {
	if ( ! is_string( $content ) || '' === $content || ! function_exists( 'nvx_environment_is_staging2' ) || ! nvx_environment_is_staging2() ) {
		return $content;
	}

	$staging_home = untrailingslashit( home_url( '/' ) );
	return str_ireplace(
		array( 'https://www.nuvanx.com', 'https://nuvanx.com', 'http://www.nuvanx.com', 'http://nuvanx.com' ),
		$staging_home,
		$content
	);
}
add_filter( 'the_content', 'nvx_normalize_staging2_internal_links', 13 );

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
 * Approved legal-framework note for the privacy and legal-notice pages.
 */
function nvx_legal_framework_note_markup(): string {
	$message = __( 'El artículo 13 del RGPD exige facilitar la información correspondiente cuando se recogen datos personales, y el artículo 10 de la LSSI exige que determinada información del prestador sea accesible de manera permanente, fácil, directa y gratuita.', 'nuvanx-medical' );

	return '<aside class="nvx-legal-context" role="note" aria-label="' . esc_attr__( 'Marco normativo', 'nuvanx-medical' ) . '"><p><strong>'
		. esc_html__( 'Marco normativo.', 'nuvanx-medical' )
		. '</strong> ' . esc_html( $message ) . '</p></aside>';
}

/**
 * Public, source-linked authority profile for Dra. Cristina Márquez González.
 */
function nvx_cristina_marquez_authority_markup(): string {
	$doctoralia = 'https://www.doctoralia.es/cristina-marquez-gonzalez-2/radiologo-medico-estetico/madrid';

	$html  = '<section class="nvx-endolift-section nvx-equipo-profile nvx-equipo-cristina" id="physician-cristina-marquez" aria-labelledby="nvx-equipo-cristina-title">';
	$html .= '<div class="nvx-endolift-section__inner nvx-endolift-diagnosis__grid">';
	$html .= '<div class="nvx-endolift-diagnosis__copy">';
	$html .= '<p class="nvx-endolift-kicker">' . esc_html__( 'Radiología mamaria y medicina estética', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-equipo-cristina-title" class="nvx-endolift-heading">' . esc_html__( 'Dra. Cristina Márquez González', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p class="nvx-endolift-body"><strong>' . esc_html__( 'Colegiada ICOMEM 282858861.', 'nuvanx-medical' ) . '</strong> ' . esc_html__( 'Radióloga y médica estética, especialista en radiología mamaria y diagnóstico mamario avanzado, con práctica como facultativa especialista en HM Hospitales.', 'nuvanx-medical' ) . '</p>';
	$html .= '<p class="nvx-endolift-body"><strong>' . esc_html__( 'Formación:', 'nuvanx-medical' ) . '</strong> ' . esc_html__( 'Licenciatura en Medicina · Especialización en Senología y Patología Mamaria · Máster en Medicina Estética.', 'nuvanx-medical' ) . '</p>';
	$html .= '<p class="nvx-endolift-body">' . wp_kses(
		sprintf(
			/* translators: %s: Doctoralia profile URL. */
			__( 'Su <a class="nvx-brand-inline-link" href="%s" target="_blank" rel="noopener noreferrer">perfil profesional y opiniones en Doctoralia</a> permiten consultar públicamente su especialidad, colegiación, formación y actividad asistencial.', 'nuvanx-medical' ),
			esc_url( $doctoralia )
		),
		array(
			'a' => array(
				'class'  => true,
				'href'   => true,
				'target' => true,
				'rel'    => true,
			),
		)
	) . '</p>';
	$html .= '</div>';
	$html .= '<aside class="nvx-endolift-diagnosis__panel" aria-label="' . esc_attr__( 'Identidad profesional de la Dra. Cristina Márquez González', 'nuvanx-medical' ) . '">';
	$html .= '<p class="nvx-endolift-panel-label">' . esc_html__( 'Identidad', 'nuvanx-medical' ) . '</p>';
	$html .= '<ul class="nvx-endolift-panel-list">';
	$html .= '<li><strong>' . esc_html__( 'Colegiada', 'nuvanx-medical' ) . '</strong> — ICOMEM 282858861</li>';
	$html .= '<li><strong>' . esc_html__( 'Especialidades', 'nuvanx-medical' ) . '</strong> — ' . esc_html__( 'Radiología · Medicina estética', 'nuvanx-medical' ) . '</li>';
	$html .= '<li><strong>' . esc_html__( 'Área clínica', 'nuvanx-medical' ) . '</strong> — ' . esc_html__( 'Radiología mamaria · Senología', 'nuvanx-medical' ) . '</li>';
	$html .= '<li><strong>' . esc_html__( 'Sede NUVANX', 'nuvanx-medical' ) . '</strong> — ' . esc_html__( 'Goya · Barrio Salamanca', 'nuvanx-medical' ) . '</li>';
	$html .= '</ul></aside></div></section>';

	return $html;
}

/**
 * Remove a short CMS card for Cristina before adding the canonical authority profile.
 */
function nvx_remove_duplicate_cristina_staff_card( string $content ): string {
	$name_pattern = '/Cristina\s+M[áa]rquez(?:\s+Gonz[áa]lez)?/iu';

	$content = preg_replace_callback(
		'/<article\b[^>]*\bclass=["\'][^"\']*\bnvx-brand-card\b[^"\']*["\'][^>]*>[\s\S]*?<\/article>/iu',
		static function ( array $matches ) use ( $name_pattern ): string {
			return preg_match( $name_pattern, $matches[0] ) ? '' : $matches[0];
		},
		$content
	) ?? $content;

	$content = preg_replace_callback(
		'/<div\b[^>]*\bclass=["\'][^"\']*\bnvx-brand-card\b[^"\']*["\'][^>]*>[\s\S]*?<\/div>\s*(?=<div\b[^>]*\bnvx-brand-card\b|<section\b|<\/section>|$)/iu',
		static function ( array $matches ) use ( $name_pattern ): string {
			return preg_match( $name_pattern, $matches[0] ) ? '' : $matches[0];
		},
		$content
	) ?? $content;

	return $content;
}

/**
 * Insert the canonical Cristina profile before the remaining-team section.
 */
function nvx_enrich_cristina_marquez_profile( string $content ): string {
	// Remove the obsolete, incorrect runtime credential from commit 5747b00b.
	$content = preg_replace( '/<p\b[^>]*\bnvx-team-credentials\b[^>]*>[^<]*282869501[^<]*<\/p>/iu', '', $content ) ?? $content;
	$content = str_replace( '282869501', '282858861', $content );

	if ( false !== strpos( $content, 'physician-cristina-marquez' ) ) {
		return $content;
	}

	$content = nvx_remove_duplicate_cristina_staff_card( $content );
	$profile = nvx_cristina_marquez_authority_markup();
	$marker  = '<section class="nvx-endolift-section nvx-equipo-staff"';
	$offset  = strpos( $content, $marker );

	if ( false !== $offset ) {
		return substr( $content, 0, $offset ) . $profile . substr( $content, $offset );
	}

	return $content . $profile;
}

/**
 * Runtime publication safeguards for P0 business rules.
 *
 * @param string $content HTML content.
 * @return string
 */
function nvx_apply_production_business_rules( $content ) {
	if ( ! is_string( $content ) || '' === trim( $content ) ) {
		return $content;
	}

	$page_id = (int) get_queried_object_id();

	// 3. Contacto (14): Strip HubSpot forms and scripts found inside post_content.
	if ( 14 === $page_id ) {
		$content = preg_replace( '/<script[^>]*hsforms\.net[^>]*><\/script>/i', '', $content ) ?? $content;
		$content = preg_replace( '/<div[^>]*class="[^"]*hbspt-form[^"]*"[^>]*>.*?<\/div>/is', '', $content ) ?? $content;
	}

	// 4. Valoración (2636): Keep only the first legacy hbspt-form found inside post_content.
	if ( 2636 === $page_id ) {
		$count   = 0;
		$content = preg_replace_callback(
			'/<div[^>]*class="[^"]*hbspt-form[^"]*"[^>]*>.*?<\/div>/is',
			static function ( array $matches ) use ( &$count ): string {
				$count++;
				return 1 === $count ? $matches[0] : '';
			},
			$content
		) ?? $content;
	}

	// 5. Privacidad y Aviso Legal (3, 20): approved copy plus explicit regulatory context.
	if ( in_array( $page_id, array( 3, 20 ), true ) ) {
		$content = preg_replace( '/<div\b[^>]*\bnvx-legal-placeholder\b[^>]*>[\s\S]*?<\/div>/iu', '', $content ) ?? $content;
		if ( false === strpos( $content, 'El artículo 13 del RGPD' ) ) {
			$content .= nvx_legal_framework_note_markup();
		}
	}

	// 6. Equipo Médico (1575): canonical profile, credentials, formation and Doctoralia source.
	if ( 1575 === $page_id ) {
		$content = nvx_enrich_cristina_marquez_profile( $content );
	}

	// 7. EXION Precios y FAQ: Strip unapproved Morpheus8 comparatives and explicit pricing in EXION pages.
	if ( false !== stripos( $content, 'EXION' ) || false !== stripos( $content, 'Morpheus' ) ) {
		$content = preg_replace( '/<details[^>]*>.*?Morpheus.*?<\/details>/is', '', $content ) ?? $content;
		$content = preg_replace( '/(EXION[^<]*?)\b\d{3,4}\s*€/i', '$1 (Presupuesto tras valoración)', $content ) ?? $content;
	}

	return $content;
}
add_filter( 'the_content', 'nvx_apply_production_business_rules', 99 );
