<?php
/**
 * Server-side rendered content checks (bypasses staging HTTP basic auth).
 *
 * Usage:
 *   wp eval-file scripts/ticket-43/verify-rendered-content.php
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_CLI' ) ) {
	fwrite( STDERR, "WP-CLI is required.\n" );
	exit( 1 );
}

$post_id = 9;
$front_id = (int) get_option( 'page_on_front' );

if ( $front_id !== $post_id ) {
	WP_CLI::error( sprintf( 'page_on_front is %d, expected %d.', $front_id, $post_id ) );
}

$post = get_post( $post_id );
if ( ! $post instanceof WP_Post ) {
	WP_CLI::error( "Post {$post_id} not found." );
}

$rendered = apply_filters( 'the_content', $post->post_content );
if ( ! is_string( $rendered ) || $rendered === '' ) {
	WP_CLI::error( 'Rendered the_content is empty.' );
}

$required = array(
	'nvx-editorial-home-v3' => 'V3 home wrapper class',
	'nvx-home-hero-video'    => 'hero video marker',
	'id="nvx-home-main"'     => 'home main wrapper',
	'nvx-home-organic-wave'  => 'hero organic wave',
);

foreach ( $required as $needle => $label ) {
	if ( strpos( $rendered, $needle ) === false ) {
		WP_CLI::error( "Rendered content missing {$label} ({$needle})." );
	}
}

if ( strpos( $rendered, 'id="nvx-home-manifiesto"' ) !== false ) {
	WP_CLI::error( 'Rendered content contains rejected V2 manifiesto anchor.' );
}

if ( stripos( $rendered, 'sin ruido' ) !== false ) {
	WP_CLI::error( 'Rendered content contains rejected V2 copy.' );
}

if (
	strpos( $rendered, 'id="nvx-home-tratamientos"' ) === false
	&& strpos( $rendered, 'aria-label="Tratamientos NUVANX"' ) === false
	&& stripos( $rendered, '>Tratamientos</p>' ) === false
) {
	WP_CLI::error( 'Rendered content missing Tratamientos section marker.' );
}

$video_src = '/wp-content/uploads/2026/07/nvx-home-video-portada-hero-12s-720p.mp4';
if ( strpos( $rendered, $video_src ) === false && strpos( $post->post_content, $video_src ) === false ) {
	WP_CLI::error( "Rendered/DB content missing hero video source {$video_src}." );
}

$header_path = get_template_directory() . '/header.php';
if ( is_readable( $header_path ) ) {
	$header = file_get_contents( $header_path );
	$main_count = substr_count( $header, '<main' );
	if ( $main_count !== 1 ) {
		WP_CLI::error( "Theme header.php must contain exactly one <main> (found {$main_count})." );
	}
} else {
	WP_CLI::warning( 'Could not read theme header.php for <main> validation.' );
}

WP_CLI::success( 'Rendered content and theme landmark checks passed.' );