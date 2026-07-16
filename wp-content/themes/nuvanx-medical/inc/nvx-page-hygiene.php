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
 * Post IDs that must stay out of the public index until editorial approval.
 *
 * @return int[]
 */
function nvx_noindex_page_ids() {
	$ids = array( 78 ); // Solicitud recibida — transactional thank-you.

	// Casos de pacientes: only index after explicit editorial meta.
	if ( '1' !== (string) get_post_meta( 2645, '_nvx_cases_publication_ready', true ) ) {
		$ids[] = 2645;
	}

	/**
	 * Filter page IDs forced to noindex.
	 *
	 * @param int[] $ids Page IDs.
	 */
	return array_values( array_unique( array_map( 'intval', apply_filters( 'nvx_noindex_page_ids', $ids ) ) ) );
}

/**
 * Keep transactional and incomplete evidence pages out of search results.
 *
 * @param string $robots Existing Yoast robots directive.
 * @return string
 */
function nvx_sensitive_page_robots( $robots ) {
	$page_id = (int) get_queried_object_id();

	if ( 78 === $page_id ) {
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
