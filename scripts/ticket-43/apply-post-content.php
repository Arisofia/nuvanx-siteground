<?php
/**
 * Ticket 43 — splice Hero/Manifiesto prefix into home post (ID 9).
 *
 * Preserves the DOM from the Tratamientos section downward by anchoring on
 * id="nvx-home-tratamientos" in the current post_content.
 *
 * Usage (on staging):
 *   NVX_POST_CONTENT_V2=/path/to/post_content_v2.html wp eval-file scripts/ticket-43/apply-post-content.php
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_CLI' ) ) {
	fwrite( STDERR, "WP-CLI is required.\n" );
	exit( 1 );
}

$post_id = 9;
$anchor  = 'id="nvx-home-tratamientos"';

$default_html = dirname( __DIR__, 2 ) . '/deploy/ticket-43/post_content_v2.html';
$html_file    = getenv( 'NVX_POST_CONTENT_V2' ) ?: $default_html;

if ( ! is_readable( $html_file ) ) {
	WP_CLI::error( "Missing or unreadable HTML file: {$html_file}" );
}

$prefix = trim( (string) file_get_contents( $html_file ) );
if ( $prefix === '' ) {
	WP_CLI::error( 'post_content_v2.html is empty.' );
}

$required_markers = array(
	'nvx-home-hero-video',
	'id="nvx-home-manifiesto"',
);

foreach ( $required_markers as $marker ) {
	if ( strpos( $prefix, $marker ) === false ) {
		WP_CLI::error( "Prefix HTML missing required marker: {$marker}" );
	}
}

$current = get_post_field( 'post_content', $post_id, 'raw' );
if ( ! is_string( $current ) || $current === '' ) {
	WP_CLI::error( "Post {$post_id} content is empty or unreadable." );
}

$pos = strpos( $current, $anchor );
if ( $pos === false ) {
	WP_CLI::error( "Anchor {$anchor} not found in post {$post_id}." );
}

$section_start = strrpos( substr( $current, 0, $pos ), '<section' );
if ( $section_start === false ) {
	WP_CLI::error( 'Could not locate Tratamientos <section> start in current post_content.' );
}

$tail        = substr( $current, $section_start );
$new_content = $prefix . "\n\n" . $tail;

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
		'Post %d updated (%d bytes). Spliced at Tratamientos anchor.',
		$post_id,
		strlen( $new_content )
	)
);