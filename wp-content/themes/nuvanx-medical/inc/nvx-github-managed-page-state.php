<?php
/**
 * Database-state normalizer for pages whose visible output is owned by GitHub.
 *
 * The page record remains available for routing, SEO metadata and WordPress
 * administration, but post_content is reduced to a deterministic marker.
 *
 * @package nuvanx-medical
 */

defined( 'ABSPATH' ) || exit;

/**
 * Replace stale CMS HTML with a GitHub ownership marker.
 */
function nvxSyncGithubManagedPageState( int $post_id, string $key ): void {
	if ( $post_id < 1 || '' === trim( $key ) || wp_is_post_revision( $post_id ) ) {
		return;
	}

	if ( ! is_admin() && ! ( defined( 'WP_CLI' ) && WP_CLI ) && ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$marker  = '<!-- NUVANX_GITHUB_MANAGED:' . sanitize_key( $key ) . ' -->';
	$current = (string) get_post_field( 'post_content', $post_id, 'raw' );
	if ( trim( $current ) === $marker ) {
		return;
	}

	$result = wp_update_post(
		array(
			'ID'           => $post_id,
			'post_content' => $marker,
		),
		true
	);

	if ( is_wp_error( $result ) && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( 'NUVANX managed-page state migration failed for post ' . $post_id . ': ' . $result->get_error_message() );
	}
}
