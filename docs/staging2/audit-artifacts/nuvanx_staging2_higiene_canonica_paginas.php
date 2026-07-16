<?php
/**
 * NUVANX canonical page hygiene for staging validation.
 *
 * Integrate into the canonical theme and remove superseded database content.
 * This file does not print schema or CSS.
 *
 * @package NUVANX
 */

defined( 'ABSPATH' ) || exit;

/**
 * Redirect superseded cookie documents to the Complianz EU statement.
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
 * Keep transactional and incomplete evidence pages out of search results.
 *
 * Page 2645 may be indexed only after editorial approval is recorded as
 * `_nvx_cases_publication_ready` = `1`.
 *
 * @param string $robots Existing Yoast robots directive.
 * @return string
 */
function nvx_sensitive_page_robots( $robots ) {
    $page_id = (int) get_queried_object_id();

    if ( 78 === $page_id ) {
        return 'noindex, nofollow';
    }

    if ( 2645 === $page_id && '1' !== (string) get_post_meta( $page_id, '_nvx_cases_publication_ready', true ) ) {
        return 'noindex, follow';
    }

    return $robots;
}
add_filter( 'wpseo_robots', 'nvx_sensitive_page_robots', 20 );

/**
 * Remove transactional pages from the Yoast XML sitemap.
 *
 * @param bool   $excluded Current exclusion result.
 * @param string $post_type Post type.
 * @param object $post Post object.
 * @return bool
 */
function nvx_exclude_transactional_pages_from_sitemap( $excluded, $post_type, $post ) {
    if ( ! is_object( $post ) || ! isset( $post->ID ) ) {
        return $excluded;
    }

    $page_id = (int) $post->ID;

    if ( 78 === $page_id ) {
        return true;
    }

    if ( 2645 === $page_id && '1' !== (string) get_post_meta( $page_id, '_nvx_cases_publication_ready', true ) ) {
        return true;
    }

    return $excluded;
}
add_filter( 'wpseo_exclude_from_sitemap_by_post_ids', '__return_empty_array' );
add_filter( 'wpseo_sitemap_entry', function ( $url, $type, $post ) {
    if ( ! is_object( $post ) || ! isset( $post->ID ) ) {
        return $url;
    }

    $page_id = (int) $post->ID;

    if ( 78 === $page_id ) {
        return false;
    }

    if ( 2645 === $page_id && '1' !== (string) get_post_meta( $page_id, '_nvx_cases_publication_ready', true ) ) {
        return false;
    }

    return $url;
}, 20, 3 );
