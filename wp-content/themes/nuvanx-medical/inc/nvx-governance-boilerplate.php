<?php
/**
 * Deduplicated boilerplate for governed thematic pages.
 *
 * Provides central factories to register repetitive Yoast SEO hooks and
 * staging environment database seeders, preventing code duplication.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers standard Yoast SEO metadata and canonical filters for a subsystem.
 *
 * @param callable      $title_cb     Function to process the SEO title.
 * @param callable      $desc_cb      Function to process the meta description.
 * @param callable|null $canonical_cb Optional function to process the canonical URL.
 * @param int           $priority     Hook priority (default 90).
 */
function nvx_register_yoast_seo_filters( callable $title_cb, callable $desc_cb, ?callable $canonical_cb = null, int $priority = 90 ): void {
	add_filter( 'wpseo_title', $title_cb, $priority );
	add_filter( 'wpseo_opengraph_title', $title_cb, $priority );
	add_filter( 'wpseo_twitter_title', $title_cb, $priority );

	add_filter( 'wpseo_metadesc', $desc_cb, $priority );
	add_filter( 'wpseo_opengraph_desc', $desc_cb, $priority );
	add_filter( 'wpseo_twitter_description', $desc_cb, $priority );

	if ( null !== $canonical_cb ) {
		add_filter( 'wpseo_canonical', $canonical_cb, $priority );
		add_filter( 'wpseo_opengraph_url', $canonical_cb, $priority );
	}
}

/**
 * Ensures governed pages exist in the WordPress database for the staging2 environment.
 *
 * Only executes if nvx_environment_is_staging2() is true.
 *
 * @param array  $catalog       Array of entries containing 'slug', 'h1' (or 'title'), and 'description'.
 * @param string $meta_key_name The name of the post meta key used to tag the catalog key.
 * @param string $content_html  Optional HTML to seed into the post_content.
 */
function nvx_seed_staging_pages( array $catalog, string $meta_key_name, string $content_html = '' ): void {
	if ( ! function_exists( 'nvx_environment_is_staging2' ) || ! nvx_environment_is_staging2() ) {
		return;
	}

	foreach ( $catalog as $key => $entry ) {
		if ( ! is_array( $entry ) || empty( $entry['slug'] ) ) {
			continue;
		}

		$page = get_page_by_path( $entry['slug'], OBJECT, 'page' );
		if ( $page instanceof WP_Post ) {
			if ( '' === (string) get_post_meta( $page->ID, $meta_key_name, true ) ) {
				update_post_meta( $page->ID, $meta_key_name, $key );
			}
			if ( '' === (string) get_post_meta( $page->ID, '_nvx_medical_review_status', true ) ) {
				update_post_meta( $page->ID, '_nvx_medical_review_status', 'pending' );
			}
			continue;
		}

		$title = $entry['h1'] ?? ( $entry['title'] ?? '' );
		$desc  = $entry['description'] ?? ( $entry['intro'] ?? '' );
		$html  = str_replace( '{key}', $key, $content_html );

		$post_id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => $title,
				'post_name'    => $entry['slug'],
				'post_excerpt' => $desc,
				'post_content' => $html,
			),
			true
		);

		if ( ! is_wp_error( $post_id ) ) {
			update_post_meta( (int) $post_id, $meta_key_name, $key );
			update_post_meta( (int) $post_id, '_nvx_medical_review_status', 'pending' );
		}
	}
}
