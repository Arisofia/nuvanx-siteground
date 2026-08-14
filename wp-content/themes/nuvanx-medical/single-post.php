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

/**
 * Rebind one WP_Query instance to the exact published post.
 */
function nvxSinglePostRebindQuery( WP_Query $query, WP_Post $exact_post, string $slug ): void {
    $query->queried_object       = $exact_post;
    $query->queried_object_id    = (int) $exact_post->ID;
    $query->post                 = $exact_post;
    $query->posts                = array( $exact_post );
    $query->post_count           = 1;
    $query->found_posts          = 1;
    $query->max_num_pages        = 1;
    $query->is_404               = false;
    $query->is_page              = false;
    $query->is_attachment        = false;
    $query->is_home              = false;
    $query->is_archive           = false;
    $query->is_single            = true;
    $query->is_singular          = true;
    $query->query['name']        = $slug;
    $query->query['post_type']   = 'post';
    $query->query_vars['name']   = $slug;
    $query->query_vars['post_type'] = 'post';
    $query->query_vars['p']      = (int) $exact_post->ID;
    $query->query_vars['pagename'] = '';
    $query->query_vars['page_id']  = 0;
}

$nvx_uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';
$nvx_path = wp_parse_url( $nvx_uri, PHP_URL_PATH );
$nvx_path = is_string( $nvx_path ) ? '/' . trim( $nvx_path, '/' ) . '/' : '';
$nvx_slug = trim( $nvx_path, '/' );

if (
    '' !== $nvx_slug
    && false === strpos( $nvx_slug, '/' )
    && function_exists( 'nvx_seo_blog_post_metadata_catalog' )
) {
    $nvx_catalog = nvx_seo_blog_post_metadata_catalog();
    if ( isset( $nvx_catalog[ $nvx_slug ] ) && is_array( $nvx_catalog[ $nvx_slug ] ) ) {
        $nvx_exact_post = get_page_by_path( $nvx_slug, OBJECT, 'post' );
        if (
            $nvx_exact_post instanceof WP_Post
            && 'publish' === $nvx_exact_post->post_status
            && $nvx_slug === $nvx_exact_post->post_name
        ) {
            global $post, $wp_query, $wp_the_query;

            if ( $wp_query instanceof WP_Query ) {
                nvxSinglePostRebindQuery( $wp_query, $nvx_exact_post, $nvx_slug );
            }
            if ( $wp_the_query instanceof WP_Query && $wp_the_query !== $wp_query ) {
                nvxSinglePostRebindQuery( $wp_the_query, $nvx_exact_post, $nvx_slug );
            }

            $post = $nvx_exact_post;
            setup_postdata( $post );
        }
    }
}

require_once __DIR__ . '/single.php';
