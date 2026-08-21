<?php
/**
 * Apply URL Reconciliation to Manifest
 *
 * Updates publication manifest based on reconciliation decisions:
 * - APPROVED: Add to routes.json and ensure post exists
 * - NOT APPROVED: Remove from routes.json and optionally unpublish post
 *
 * Run with:
 *   wp eval "require 'tools/migrations/apply-url-reconciliation.php';" --allow-root
 *
 * @package NVX\Migrations
 */


if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "ERROR: must run inside WordPress via wp eval \"require '...';\".\n" );
	exit( 1 );
}

// Load reconciliation report
$reconciliation_file = get_template_directory() . '/inc/data/url-drift-reconciliation.json';
if ( ! is_readable( $reconciliation_file ) ) {
	fwrite( STDERR, "ERROR: Cannot read url-drift-reconciliation.json. Run classify-url-drift.php first.\n" );
	exit( 1 );
}

$reconciliation = json_decode( file_get_contents( $reconciliation_file ), true );
if ( ! is_array( $reconciliation ) ) {
	fwrite( STDERR, "ERROR: Invalid JSON in url-drift-reconciliation.json\n" );
	exit( 1 );
}

if ( ! isset( $reconciliation['plan'] ) ) {
	fwrite( STDERR, "ERROR: Reconciliation report does not contain plan. Run classify-url-drift.php first.\n" );
	exit( 1 );
}

// Load current routes.json
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

$changes = [
	'added'   => [],
	'removed' => [],
	'errors'  => [],
];

// Apply APPROVED URLs (add to routes.json)
foreach ( $reconciliation['plan']['approved_for_staging'] as $item ) {
	$route = $item['route'];

	// Check if already exists in routes.json
	if ( isset( $routes_config[ $route ] ) ) {
		fwrite( STDERR, "SKIP: {$route} already exists in routes.json\n" );
		continue;
	}

	// Get post information
	$post = get_post( $item['post_id'] );
	if ( ! $post instanceof WP_Post ) {
		$changes['errors'][] = "Cannot find post {$item['post_id']} for {$route}";
		fwrite( STDERR, "ERROR: Cannot find post {$item['post_id']} for {$route}\n" );
		continue;
	}

	// Add to routes.json with minimal configuration
	$routes_config[ $route ] = [
		'post_id' => $item['post_id'],
		'seo_id' => strtolower( str_replace( [ '/', '-', ' ' ], '_', trim( $route, '/' ) ) ),
	];

	$changes['added'][] = $route;
	fwrite( STDERR, "ADDED: {$route} to routes.json\n" );
}

// Load publication manifest to update reconciliation status
$manifest_file = get_template_directory() . '/inc/data/publication-manifest.json';
$manifest = [];

if ( is_readable( $manifest_file ) ) {
	$manifest = json_decode( file_get_contents( $manifest_file ), true );
	if ( ! is_array( $manifest ) ) {
		$manifest = [];
	}
}

// Apply NOT APPROVED URLs (remove from routes.json and update manifest)
foreach ( $reconciliation['plan']['remove_from_production'] as $item ) {
	$route = $item['route'];

	// Check if exists in routes.json
	if ( ! isset( $routes_config[ $route ] ) ) {
		fwrite( STDERR, "SKIP: {$route} not in routes.json\n" );
		continue;
	}

	// Remove from routes.json
	unset( $routes_config[ $route ] );
	$changes['removed'][] = $route;
	fwrite( STDERR, "REMOVED: {$route} from routes.json\n" );

	// Update manifest reconciliation status if exists
	if ( isset( $manifest['routes'][ $route ] ) ) {
		$manifest['routes'][ $route ]['reconciliation_status'] = 'removed';
	}
}

// Update reconciliation status for added routes
foreach ( $changes['added'] as $route ) {
	if ( isset( $manifest['routes'][ $route ] ) ) {
		$manifest['routes'][ $route ]['reconciliation_status'] = 'reconciled';
	}
}

// Add reconciliation metadata to manifest
$manifest['reconciliation'] = [
	'last_reconciled_at' => gmdate( 'c' ),
	'reconciled_routes' => array_merge( $changes['added'], $changes['removed'] ),
	'pending_reconciliation' => [],
];

// Save updated publication manifest
if ( ! empty( $manifest ) ) {
	file_put_contents( $manifest_file, json_encode( $manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );
	fwrite( STDERR, "UPDATED: publication-manifest.json with reconciliation status\n" );
}

// Save updated routes.json
file_put_contents( $routes_file, json_encode( $routes_config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );

// Generate summary
$summary = [
	'schema' => 'url-reconciliation-applied',
	'applied_at' => gmdate( 'c' ),
	'source' => home_url( '/' ),
	'changes' => $changes,
	'summary' => [
		'total_processed' => count( $reconciliation['classification'] ),
		'added_count' => count( $changes['added'] ),
		'removed_count' => count( $changes['removed'] ),
		'error_count' => count( $changes['errors'] ),
	],
];

echo json_encode( $summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

// Console summary
fwrite( STDERR, "\n=== URL RECONCILIATION APPLIED ===\n" );
fwrite( STDERR, "Total processed: " . count( $reconciliation['classification'] ) . "\n" );
fwrite( STDERR, "Added to routes.json: " . count( $changes['added'] ) . "\n" );
fwrite( STDERR, "Removed from routes.json: " . count( $changes['removed'] ) . "\n" );
fwrite( STDERR, "Errors: " . count( $changes['errors'] ) . "\n" );

if ( ! empty( $changes['errors'] ) ) {
	fwrite( STDERR, "\n=== ERRORS ===\n" );
	foreach ( $changes['errors'] as $error ) {
		fwrite( STDERR, "- {$error}\n" );
	}
}

// Exit with error if there were errors
if ( ! empty( $changes['errors'] ) ) {
	exit( 1 );
}