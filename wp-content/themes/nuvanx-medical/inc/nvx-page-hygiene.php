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
 * Ensure legal CMS pages expose a single document H1 for accessibility/crawl contracts.
 *
 * When the body has no H1, promote the first H2 (typical legal title pattern) to H1.
 *
 * @param string $content Filtered post content.
 * @return string
 */
function nvx_legal_ensure_document_h1( string $content ): string {
	if ( '' === trim( $content ) || (bool) preg_match( '/<h1\b/i', $content ) ) {
		return $content;
	}

	$promoted = preg_replace( '/<h2(\b[^>]*)>/i', '<h1$1>', $content, 1 );
	if ( ! is_string( $promoted ) || $promoted === $content ) {
		return $content;
	}

	$closed = preg_replace( '/<\/h2>/i', '</h1>', $promoted, 1 );
	return is_string( $closed ) ? $closed : $promoted;
}

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
 * Resolved by slug so IDs may differ across environments.
 *
 * @return int[]
 */
function nvx_nofollow_page_ids() {
	$ids = array();
	$thank_you = function_exists( 'nvx_page_id_by_slug' )
		? nvx_page_id_by_slug( 'gracias' )
		: 0;
	if ( $thank_you > 0 ) {
		$ids[] = $thank_you;
	}

	/**
	 * Filter page IDs that receive noindex, nofollow.
	 *
	 * @param int[] $ids Page IDs.
	 */
	return array_values( array_unique( array_map( 'intval', apply_filters( 'nvx_nofollow_page_ids', $ids ) ) ) );
}

/**
 * Force 404 on patient cases gallery until explicitly marked publication-ready.
 */
function nvx_force_404_empty_cases() {
	if ( ! is_page() ) {
		return;
	}
	$cases_id = function_exists( 'nvx_page_id_by_slug' )
		? nvx_page_id_by_slug( 'casos-de-pacientes' )
		: 0;
	if ( $cases_id <= 0 || (int) get_queried_object_id() !== $cases_id ) {
		return;
	}
	if ( '1' === (string) get_post_meta( $cases_id, '_nvx_cases_publication_ready', true ) ) {
		return;
	}

	global $wp_query;
	$wp_query->set_404();
	status_header( 404 );
	nocache_headers();
	// Do not exit: WordPress must still select and render its 404 template.
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

	// Working protocol names are review artefacts, never discoverable treatment pages.
	if ( function_exists( 'nvx_strategy_pending_page_ids' ) ) {
		$ids = array_merge( $ids, nvx_strategy_pending_page_ids() );
	}

	// Casos de pacientes: only index after explicit editorial meta.
	$cases_id = function_exists( 'nvx_page_id_by_slug' )
		? nvx_page_id_by_slug( 'casos-de-pacientes' )
		: 0;
	if ( $cases_id > 0 && '1' !== (string) get_post_meta( $cases_id, '_nvx_cases_publication_ready', true ) ) {
		$ids[] = $cases_id;
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
 * Exclude sensitive pages from the WordPress Core XML sitemap.
 *
 * @param array  $args      Query arguments for the sitemap posts query.
 * @param string $post_type Post type name.
 * @return array
 */
function nvx_exclude_sensitive_pages_from_core_sitemap( $args, $post_type ) {
	unset( $post_type );
	$excluded = nvx_noindex_page_ids();
	if ( ! empty( $excluded ) ) {
		$args['post__not_in'] = isset( $args['post__not_in'] ) ? array_merge( (array) $args['post__not_in'], $excluded ) : $excluded;
	}
	return $args;
}
add_filter( 'wp_sitemaps_posts_query_args', 'nvx_exclude_sensitive_pages_from_core_sitemap', 10, 2 );

/**
 * Belt-and-suspenders: drop sitemap entries for sensitive pages.
 *
 * @param array|false $url  Sitemap URL array or false to exclude.
 * @param string      $type Object type.
 * @param WP_Post     $post Post object.
 * @return array|false
 */
function nvx_filter_sitemap_entry_sensitive_pages( $url, $type, $post ) {
	unset( $type );
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
 * after route-specific renderers so a retired phrase cannot be reintroduced by
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
		// Brand / product typo seen in CMS titles.
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
	$valuation_label = 'valoración médica';
	$content         = preg_replace( '/\bvaloraci[oó]n\s+m[eé]dica\s+gratuita\b/iu', $valuation_label, $content ) ?? $content;
	$content         = preg_replace( '/\bvaloraci[oó]n\s+gratuita\b/iu', $valuation_label, $content ) ?? $content;
	$content         = preg_replace( '/\bvaloraci[oó]n\s+gratis\b/iu', $valuation_label, $content ) ?? $content;
	$content = preg_replace( '/\bconsulta\s+(?:m[eé]dica\s+)?gratuita\b/iu', 'consulta médica', $content ) ?? $content;
	$content = preg_replace( '/\bconsulta\s+gratis\b/iu', 'consulta médica', $content ) ?? $content;
	$content = preg_replace( '/\bpresupuestos?\s+personalizados?\b/iu', 'presupuesto individualizado tras la valoración médica', $content ) ?? $content;
	$content = preg_replace( '/\bsin\s+compromiso\b/iu', 'sin obligación de continuar con un tratamiento', $content ) ?? $content;

	// Endolift≠radiofrecuencia clinical conflations were corrected at source in CMS
	// (staging2 audit: zero remaining "Endolift es/como radiofrecuencia" matches).
	// Do not reintroduce per-request clinical rewrites here — fix post_content instead.

	// Valoración CTA fixes (residual incomplete CMS labels).
	$content = preg_replace( '/\bSolicitar\.(?=\s|<|$)/u', 'Solicitar valoración médica', $content ) ?? $content;

	return $content;
}
// Keep this after all page-specific builders (the valoración module runs at 16).
add_filter( 'the_content', 'nvx_public_content_text_hygiene', 240 );
add_filter( 'the_title', 'nvx_public_content_text_hygiene', 240 );
add_filter( 'wpseo_metadesc', 'nvx_public_content_text_hygiene', 240 );
add_filter( 'wpseo_opengraph_desc', 'nvx_public_content_text_hygiene', 240 );
add_filter( 'wpseo_twitter_description', 'nvx_public_content_text_hygiene', 240 );

/**
 * Keep QA on staging2 inside the same environment when CMS copy uses
 * absolute production URLs. Production keeps its public URLs untouched.
 *
 * Hostnames are rewritten for both schemes without embedding clear-text
 * protocol literals in source (production is HTTPS; residual HTTP hosts are
 * still normalized so staging never leaks out to production absolute links).
 */
function nvx_normalize_staging2_internal_links( $content ) {
	if ( ! is_string( $content ) || '' === $content || ! function_exists( 'nvx_environment_is_staging2' ) || ! nvx_environment_is_staging2() ) {
		return $content;
	}

	$staging_home = untrailingslashit( home_url( '/' ) );
	$hosts        = array( 'www.nuvanx.com', 'nuvanx.com' );
	$schemes      = array( 'https', 'http' );

	foreach ( $schemes as $scheme ) {
		foreach ( $hosts as $host ) {
			$content = str_ireplace( $scheme . '://' . $host, $staging_home, $content );
		}
	}

	return $content;
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

	$html  = '<section class="nvx-brand-section nvx-equipo-profile nvx-equipo-cristina" id="physician-cristina-marquez" aria-labelledby="nvx-equipo-cristina-title">';
	$html .= '<div class="nvx-container nvx-equipo-diagnosis__grid">';
	$html .= '<div class="nvx-equipo-diagnosis__copy">';
	$html .= '<p class="nvx-brand-kicker">' . esc_html__( 'Radiología mamaria y medicina estética', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-equipo-cristina-title" class="nvx-heading">' . esc_html__( 'Dra. Cristina Márquez González', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p class="nvx-body"><strong>' . esc_html__( 'Colegiada ICOMEM 282858861.', 'nuvanx-medical' ) . '</strong> ' . esc_html__( 'Radióloga y médica estética, especialista en radiología mamaria y diagnóstico mamario avanzado, con práctica como facultativa especialista en HM Hospitales.', 'nuvanx-medical' ) . '</p>';
	$html .= '<p class="nvx-body"><strong>' . esc_html__( 'Formación:', 'nuvanx-medical' ) . '</strong> ' . esc_html__( 'Licenciatura en Medicina · Especialización en Senología y Patología Mamaria · Máster en Medicina Estética.', 'nuvanx-medical' ) . '</p>';
	$html .= '<p class="nvx-body">' . wp_kses(
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

	return (string) preg_replace_callback(
		'/<(article|div)\b[^>]*\bclass=["\'][^"\']*\bnvx-brand-card\b[^"\']*["\'][^>]*>[\s\S]*?<\/\1>/iu',
		static function ( array $matches ) use ( $name_pattern ): string {
			return preg_match( $name_pattern, $matches[0] ) ? '' : $matches[0];
		},
		$content
	);
}

/**
 * Insert the canonical Cristina profile before the remaining-team section.
 */
function nvx_enrich_cristina_marquez_profile( string $content ): string {
	// Enforce the current ICOMEM credential if residual CMS copy still has the retired number.
	$content = preg_replace( '/<p\b[^>]*\bnvx-team-credentials\b[^>]*>[^<]*282869501[^<]*<\/p>/iu', '', $content ) ?? $content;
	$content = str_replace( '282869501', '282858861', $content );

	if ( false !== strpos( $content, 'physician-cristina-marquez' ) ) {
		return $content;
	}

	$content = nvx_remove_duplicate_cristina_staff_card( $content );
	$profile = nvx_cristina_marquez_authority_markup();
	$marker  = '<section class="nvx-brand-section nvx-equipo-staff"';
	$offset  = strpos( $content, $marker );
	if ( false === $offset ) {
		$marker = '<section class="nvx-endolift-section nvx-equipo-staff"';
		$offset = strpos( $content, $marker );
	}

	if ( false !== $offset ) {
		return substr( $content, 0, $offset ) . $profile . substr( $content, $offset );
	}

	return $content . $profile;
}

/**
 * Resolve a published page ID by slug (environment-safe; no hard-coded IDs).
 */
function nvx_page_id_by_slug( string $slug ): int {
	static $cache = array();
	$slug         = trim( $slug, '/' );
	if ( '' === $slug ) {
		return 0;
	}
	if ( array_key_exists( $slug, $cache ) ) {
		return $cache[ $slug ];
	}
	$page = get_page_by_path( $slug, OBJECT, 'page' );
	$cache[ $slug ] = $page instanceof WP_Post ? (int) $page->ID : 0;
	return $cache[ $slug ];
}

/**
 * Whether the current main request is one of the given page slugs.
 *
 * @param string|string[] $slugs Page slug or list of slugs.
 */
function nvx_is_page_slug( $slugs ): bool {
	if ( ! is_page() ) {
		return false;
	}
	$current = (string) get_post_field( 'post_name', get_queried_object_id() );
	$slugs   = (array) $slugs;
	return in_array( $current, $slugs, true );
}

/**
 * Runtime publication safeguards for residual CMS body only.
 *
 * Contacto and valoración are theme-template owned (no the_content HubSpot).
 * Legal/equipo still accept CMS body and need structural contracts here.
 *
 * @param string $content HTML content.
 * @return string
 */
function nvx_apply_production_business_rules( $content ) {
	if ( ! is_string( $content ) || '' === trim( $content ) ) {
		return $content;
	}

	// Privacidad y Aviso Legal: regulatory context + single H1 (slug-based).
	if ( nvx_is_page_slug( array( 'politica-privacidad', 'aviso-legal' ) ) ) {
		$content = preg_replace( '/<div\b[^>]*\bnvx-legal-placeholder\b[^>]*>[\s\S]*?<\/div>/iu', '', $content ) ?? $content;
		if ( false === strpos( $content, 'El artículo 13 del RGPD' ) ) {
			$content .= nvx_legal_framework_note_markup();
		}
		$content = nvx_legal_ensure_document_h1( $content );
	}

	// Equipo médico: canonical Cristina profile (slug-based; was hard-coded ID 1575).
	if ( nvx_is_page_slug( 'equipo-medico' ) ) {
		$content = nvx_enrich_cristina_marquez_profile( $content );
	}

	// EXION pages: strip unapproved Morpheus8 comparatives and bare euro prices in copy.
	if ( false !== stripos( $content, 'EXION' ) || false !== stripos( $content, 'Morpheus' ) ) {
		$content = preg_replace( '/<details[^>]*>.*?Morpheus.*?<\/details>/is', '', $content ) ?? $content;
		$content = preg_replace( '/(EXION[^<]*?)\b\d{3,4}\s*€/i', '$1 (Presupuesto tras valoración)', $content ) ?? $content;
	}

	return $content;
}
add_filter( 'the_content', 'nvx_apply_production_business_rules', 99 );
