<?php
/**
 * Ticket 43 — apply V3 home post_content (ID 9) on staging.
 *
 * V3 replaces the full post_content when the candidate file already contains
 * the complete production copy with structural wrappers only.
 *
 * Usage (on staging):
 *   NVX_POST_CONTENT_V3=/path/to/post_content_v3-production-copy.html wp eval-file scripts/ticket-43/apply-post-content.php
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_CLI' ) ) {
	fwrite( STDERR, "WP-CLI is required.\n" );
	exit( 1 );
}

$post_id = 9;

$default_html = dirname( __DIR__, 2 ) . '/deploy/ticket-43/post_content_v3-production-copy.html';
$html_file    = getenv( 'NVX_POST_CONTENT_V3' ) ?: $default_html;

if ( ! is_readable( $html_file ) ) {
	WP_CLI::error( "Missing or unreadable HTML file: {$html_file}" );
}

$new_content = trim( (string) file_get_contents( $html_file ) );
if ( $new_content === '' ) {
	WP_CLI::error( 'post_content_v3-production-copy.html is empty.' );
}

$required_markers = array(
	'nvx-editorial-home-v3',
	'nvx-home-hero-video',
	'id="nvx-home-main"',
	'aria-label="Tratamientos NUVANX"',
);

foreach ( $required_markers as $marker ) {
	if ( strpos( $new_content, $marker ) === false ) {
		WP_CLI::error( "Candidate HTML missing required marker: {$marker}" );
	}
}

$front_id = (int) get_option( 'page_on_front' );
if ( $front_id !== $post_id ) {
	WP_CLI::error( sprintf( 'page_on_front is %d, expected %d.', $front_id, $post_id ) );
}

$result = wp_update_post(
	array(
		'ID'           => $post_id,
		'post_content' => $new_content,
	),
	true
);

if ( is_wp_error( $result ) ) {
	WP_CLI::error( $result->get_error_message() );
}

WP_CLI::success(
	sprintf(
		'Post %d updated (%d bytes) with Ticket 43 V3 production-copy candidate.',
		$post_id,
		strlen( $new_content )
	)
);