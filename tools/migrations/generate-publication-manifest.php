<?php
/**
 * Generate NUVANX Publication Manifest
 *
 * Creates a single source of truth for NUVANX public topology.
 * Represents exactly what a release NUVANX allows to publish.
 *
 * Run with:
 *   wp eval "require 'tools/migrations/generate-publication-manifest.php';" --allow-root
 *
 * @package NVX\Migrations
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "ERROR: must run inside WordPress via wp eval \"require '...';\".\n" );
	exit( 1 );
}

global $wpdb;

// Load existing routes configuration
$routes_file = get_template_directory() . '/inc/data/routes.json';
if ( ! is_readable( $routes_file ) ) {
	fwrite( STDERR, "ERROR: Cannot read routes.json from {$routes_file}\n" );
	exit( 1 );
}

$routes_config = json_decode( file_get_contents( $routes_file ), true );
if ( ! is_array( $routes_config ) ) {
	fwrite( STDERR, "ERROR: Invalid JSON in routes.json\n" );
	exit( 1 );
}

// Load SEO metadata
$seo_metadata_file = get_template_directory() . '/inc/data/seo-metadata.json';
$seo_metadata = [];
if ( is_readable( $seo_metadata_file ) ) {
	$seo_metadata = json_decode( file_get_contents( $seo_metadata_file ), true );
	if ( ! is_array( $seo_metadata ) ) {
		$seo_metadata = [];
	}
}

$manifest = [
	'schema'       => 'nuvanx-publication-manifest',
	'version'      => '1.0.0',
	'generated_at' => gmdate( 'c' ),
	'source'       => home_url( '/' ),
	'routes'       => [],
];

foreach ( $routes_config as $route => $config ) {
	// Skip route aliases
	if ( isset( $config['route_alias'] ) ) {
		continue;
	}

	$seo_id    = $config['seo_id'] ?? '';
	$post_id   = $config['post_id'] ?? 0;
	$slug      = '';
	$post_type = 'page';
	$status    = 'publish';
	$renderer  = '';
	$canonical = home_url( $route );
	$robots    = [
		'index'   => true,
		'follow'  => true,
		'archive' => true,
		'snippet' => true,
	];

	// Try to resolve post information if post_id is provided
	if ( $post_id > 0 ) {
		$post = get_post( $post_id );
		if ( $post instanceof WP_Post ) {
			$slug      = $post->post_name;
			$post_type = $post->post_type;
			$status    = $post->post_status;
			$renderer  = get_page_template_slug( $post_id ) ?: '';

			// Get canonical URL from Yoast if available
			if ( function_exists( 'wpseo_get_meta_robots' ) ) {
				$robots_meta = wpseo_get_meta_robots( $post_id, $post_type );
				$robots['index'] = ! ( ( $robots_meta & 1 ) === 1 ); // Yoast uses bit flags
				$robots['follow'] = ! ( ( $robots_meta & 2 ) === 2 );
			}
		}
	}

	// Resolve schema configuration
	$schema = [
		'group'   => $config['schema_group'] ?? 'other',
		'id'      => $config['schema_id'] ?? '',
		'type'    => $config['schema_type'] ?? 'none',
		'context' => 'https://schema.org',
	];

	if ( empty( $schema['id'] ) && empty( $schema['group'] ) ) {
		$schema['group'] = 'none';
		$schema['type'] = 'none';
	}

	// Resolve SEO configuration
	$seo = [
		'seo_id'       => $seo_id,
		'route_alias'  => $config['route_alias'] ?? '',
	];

	// Build route entry
	$manifest['routes'][ $route ] = [
		'post_id'   => $post_id,
		'post_type' => $post_type,
		'slug'      => $slug,
		'status'    => $status,
		'renderer'  => $renderer,
		'canonical' => $canonical,
		'robots'    => $robots,
		'schema'    => $schema,
		'seo'       => $seo,
	];
}

// Output the manifest
echo json_encode( $manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );