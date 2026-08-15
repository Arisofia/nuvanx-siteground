<?php
/**
 * Generate and Validate NUVANX Publication Manifest
 *
 * Creates a single source of truth for NUVANX public topology.
 * Performs bidirectional validation between expected and actual WordPress state.
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

$validation = [
	'errors'     => [],
	'warnings'   => [],
	'info'       => [],
	'missing'    => [], // Expected URLs not published
	'surplus'    => [], // Published URLs not in manifest
	'changed'    => [], // URLs with changed attributes
];

// Build expected manifest from routes configuration
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
		} else {
			$validation['errors'][] = "Expected post_id {$post_id} not found for route {$route}";
			$validation['missing'][] = $route;
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
		'post_id'               => $post_id,
		'post_type'             => $post_type,
		'slug'                  => $slug,
		'status'                => $status,
		'renderer'              => $renderer,
		'canonical'             => $canonical,
		'robots'                => $robots,
		'schema'                => $schema,
		'seo'                   => $seo,
		'reconciliation_status' => 'none', // Will be updated by reconciliation process
	];
}

// Capture actual WordPress published state
$actual_published = [];
$all_posts = get_posts( [
	'post_type'      => [ 'page', 'post' ],
	'post_status'    => 'publish',
	'posts_per_page' => -1,
	'fields'         => 'ids',
] );

foreach ( $all_posts as $actual_post_id ) {
	$actual_post = get_post( $actual_post_id );
	if ( ! $actual_post instanceof WP_Post ) {
		continue;
	}

	$actual_permalink = get_permalink( $actual_post_id );
	if ( ! $actual_permalink ) {
		continue;
	}

	$actual_route = str_replace( home_url( '/' ), '', $actual_permalink );
	$actual_route = '/' . trim( $actual_route, '/' ) . '/';

	$actual_published[ $actual_route ] = [
		'post_id'   => $actual_post_id,
		'post_type' => $actual_post->post_type,
		'slug'      => $actual_post->post_name,
		'status'    => $actual_post->post_status,
		'renderer'  => get_page_template_slug( $actual_post_id ) ?: '',
		'canonical' => $actual_permalink,
	];
}

// Bidirectional validation: EXPECTED_PUBLIC_URLS === ACTUAL_PUBLIC_URLS
$expected_routes = array_keys( $manifest['routes'] );
$actual_routes   = array_keys( $actual_published );

// Check for missing expected URLs
foreach ( $expected_routes as $expected_route ) {
	if ( ! in_array( $expected_route, $actual_routes, true ) ) {
		$validation['errors'][] = "Missing expected URL: {$expected_route}";
		$validation['missing'][] = $expected_route;
	}
}

// Check for surplus published URLs
foreach ( $actual_routes as $actual_route ) {
	if ( ! in_array( $actual_route, $expected_routes, true ) ) {
		$validation['errors'][] = "Surplus published URL not in manifest: {$actual_route}";
		$validation['surplus'][] = $actual_route;
	}
}

// Check for changes in attributes
foreach ( $expected_routes as $route ) {
	if ( ! isset( $actual_published[ $route ] ) ) {
		continue; // Already captured as missing
	}

	$expected = $manifest['routes'][ $route ];
	$actual   = $actual_published[ $route ];

	$changes = [];

	// Check status changes
	if ( $expected['status'] !== $actual['status'] ) {
		$changes[] = "status: expected {$expected['status']}, actual {$actual['status']}";
	}

	// Check slug changes
	if ( $expected['slug'] !== $actual['slug'] ) {
		$changes[] = "slug: expected {$expected['slug']}, actual {$actual['slug']}";
	}

	// Check renderer changes
	if ( $expected['renderer'] !== $actual['renderer'] ) {
		$changes[] = "renderer: expected {$expected['renderer']}, actual {$actual['renderer']}";
	}

	// Check canonical changes
	if ( $expected['canonical'] !== $actual['canonical'] ) {
		$changes[] = "canonical: expected {$expected['canonical']}, actual {$actual['canonical']}";
	}

	if ( ! empty( $changes ) ) {
		$validation['errors'][] = "Attribute changes for {$route}: " . implode( ', ', $changes );
		$validation['changed'][] = [
			'route'   => $route,
			'changes' => $changes,
		];
	}
}

// Add validation results to manifest
$manifest['validation'] = [
	'errors_count'   => count( $validation['errors'] ),
	'warnings_count' => count( $validation['warnings'] ),
	'info_count'     => count( $validation['info'] ),
	'errors'         => $validation['errors'],
	'warnings'       => $validation['warnings'],
	'info'           => $validation['info'],
	'missing'        => $validation['missing'],
	'surplus'        => $validation['surplus'],
	'changed'        => $validation['changed'],
	'pass'           => count( $validation['errors'] ) === 0,
];

// Output the manifest
echo json_encode( $manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

// Exit with error code if validation failed
if ( ! $manifest['validation']['pass'] ) {
	fwrite( STDERR, "VALIDATION_FAILED: " . count( $validation['errors'] ) . " error(s) found\n" );
	exit( 1 );
}