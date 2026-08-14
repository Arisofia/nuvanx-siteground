<?php
/**
 * Canonical single-post entrypoint.
 *
 * Rebind version-governed journal routes to the exact published post before the
 * shared single template renders the document head. This is a final runtime
 * guard for hosts where a stale singular query/indexable can survive earlier
 * request parsing and leak a neighbouring article's canonical metadata.
 *
 * @package nuvanx-medical
 */

defined( 'ABSPATH' ) || exit;

global $wp_query;

$nvx_slug = '';
if ( $wp_query instanceof WP_Query && is_string( $wp_query->get( 'name' ) ) && '' !== $wp_query->get( 'name' ) ) {
    $nvx_slug = (string) $wp_query->get( 'name' );
}

if ( '' === $nvx_slug ) {
    $nvx_uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';
    $nvx_path = wp_parse_url( $nvx_uri, PHP_URL_PATH );
    $nvx_path = is_string( $nvx_path ) ? '/' . trim( $nvx_path, '/' ) . '/' : '';
    $nvx_slug = trim( $nvx_path, '/' );
}

if (
    '' !== $nvx_slug
    && false === strpos( $nvx_slug, '/' )
    && function_exists( 'nvx_seo_blog_post_metadata_catalog' )
    && function_exists( 'nvx_single_post_rebind_query' )
) {
    $nvx_catalog = nvx_seo_blog_post_metadata_catalog();
    if ( isset( $nvx_catalog[ $nvx_slug ] ) && is_array( $nvx_catalog[ $nvx_slug ] ) ) {
        $nvx_exact_post = function_exists( 'nvx_document_governance_get_published_post_by_slug' )
            ? nvx_document_governance_get_published_post_by_slug( $nvx_slug )
            : get_page_by_path( $nvx_slug, OBJECT, 'post' );
        if (
            $nvx_exact_post instanceof WP_Post
            && 'publish' === $nvx_exact_post->post_status
            && $nvx_slug === $nvx_exact_post->post_name
        ) {
            global $post, $wp_the_query;

            if ( $wp_query instanceof WP_Query ) {
                nvx_single_post_rebind_query( $wp_query, $nvx_exact_post, $nvx_slug );
            }
            if ( $wp_the_query instanceof WP_Query && $wp_the_query !== $wp_query ) {
                nvx_single_post_rebind_query( $wp_the_query, $nvx_exact_post, $nvx_slug );
            }

            $post = $nvx_exact_post;
            setup_postdata( $post );
        }
    }
}

require_once __DIR__ . '/single.php';

