<?php
/**
 * Ticket 43 — splice Hero/Manifiesto prefix into home post (ID 9).
 *
 * Preserves the DOM from the Tratamientos section downward by locating the
 * first Tratamientos <section> in the current post_content.
 *
 * Usage (on staging):
 *   NVX_POST_CONTENT_V2=/path/to/post_content_v2.html wp eval-file scripts/ticket-43/apply-post-content.php
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_CLI' ) ) {
	fwrite( STDERR, "WP-CLI is required.\n" );
	exit( 1 );
}

/**
 * @return array{start:int, anchor:string}|WP_Error
 */
function nvx_ticket43_find_tratamientos_section( string $content ) {
	$anchors = array(
		'id="nvx-home-tratamientos"',
		'aria-label="Tratamientos NUVANX"',
		'aria-labelledby="nvx-eh-tratamientos-title"',
	);

	foreach ( $anchors as $anchor ) {
		$pos = strpos( $content, $anchor );
		if ( $pos === false ) {
			continue;
		}

		$section_start = strrpos( substr( $content, 0, $pos ), '<section' );
		if ( $section_start !== false ) {
			return array(
				'start'  => $section_start,
				'anchor' => $anchor,
			);
		}
	}

	return new WP_Error(
		'nvx_ticket43_missing_anchor',
		'Tratamientos section not found. Expected one of: id="nvx-home-tratamientos", aria-label="Tratamientos NUVANX", aria-labelledby="nvx-eh-tratamientos-title".'
	);
}

$post_id = 9;

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

$match = nvx_ticket43_find_tratamientos_section( $current );
if ( is_wp_error( $match ) ) {
	WP_CLI::error( $match->get_error_message() );
}

$tail = substr( $current, $match['start'] );

$wrapped_prefix = '<div id="nvx-home-main" class="nvx-home nvx-brand-page nvx-editorial-home">' . "\n"
	. $prefix . "\n"
	. '</div>';

$new_content = $wrapped_prefix . "\n\n" . $tail;

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
		'Post %d updated (%d bytes). Spliced at Tratamientos anchor %s.',
		$post_id,
		strlen( $new_content ),
		$match['anchor']
	)
);