<?php
/**
 * Reconcile URL Drift Between Production and Staging
 *
 * Identifies and classifies URLs that exist in one environment but not the other.
 * Provides APPROVED/NOT APPROVED classification for reconciliation.
 *
 * Run with:
 *   wp eval "require 'tools/migrations/reconcile-url-drift.php';" --allow-root
 *
 * @package NVX\Migrations
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "ERROR: must run inside WordPress via wp eval \"require '...';\".\n" );
	exit( 1 );
}

global $wpdb;

// Load publication manifest
$manifest_file = get_template_directory() . '/inc/data/publication-manifest.json';
if ( ! is_readable( $manifest_file ) ) {
	fwrite( STDERR, "ERROR: Cannot read publication-manifest.json from {$manifest_file}\n" );
	exit( 1 );
}

$manifest = json_decode( file_get_contents( $manifest_file ), true );
if ( ! is_array( $manifest ) ) {
	fwrite( STDERR, "ERROR: Invalid JSON in publication-manifest.json\n" );
	exit( 1 );
}

// Capture actual WordPress state
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
		'title'     => $actual_post->post_title,
	];
}

// Identify drift
$expected_routes = array_keys( $manifest['routes'] ?? [] );
$actual_routes   = array_keys( $actual_published );

$prod_only  = array_diff( $expected_routes, $actual_routes );
$staging_only = array_diff( $actual_routes, $expected_routes );

// Priority immediate URLs
$priority_urls = [
	'/acido-hialuronico-relleno-madrid/',
	'/neuromoduladores-botox-madrid/',
	'/protocolo-novias-madrid/',
	'/labios-acido-hialuronico-madrid/',
	'/rinomodelacion-sin-cirugia-madrid/',
	'/ojeras-surco-lagrimal-madrid/',
	'/bioestimuladores-colageno-madrid/',
];

// Classification results
$reconciliation = [
	'prod_only'      => [],
	'staging_only'   => [],
	'priority_immediate' => [],
	'classification' => [],
];

// Classify prod-only URLs
foreach ( $prod_only as $route ) {
	$manifest_data = $manifest['routes'][ $route ] ?? null;
	$is_priority = in_array( $route, $priority_urls, true );

	$classification = [
		'route' => $route,
		'environment' => 'prod-only',
		'post_id' => $manifest_data['post_id'] ?? 0,
		'post_type' => $manifest_data['post_type'] ?? 'unknown',
		'status' => 'missing_from_staging',
		'priority' => $is_priority ? 'immediate' : 'normal',
		'action' => null, // To be set: 'APPROVED' or 'NOT APPROVED'
	];

	if ( $is_priority ) {
		$reconciliation['priority_immediate'][] = $classification;
	}

	$reconciliation['prod_only'][] = $classification;
	$reconciliation['classification'][] = $classification;
}

// Classify staging-only URLs
foreach ( $staging_only as $route ) {
	$actual_data = $actual_published[ $route ] ?? null;

	$classification = [
		'route' => $route,
		'environment' => 'staging-only',
		'post_id' => $actual_data['post_id'] ?? 0,
		'post_type' => $actual_data['post_type'] ?? 'unknown',
		'title' => $actual_data['title'] ?? '',
		'status' => 'surplus_in_staging',
		'priority' => 'normal',
		'action' => null, // To be set: 'APPROVED' or 'NOT APPROVED'
	];

	$reconciliation['staging_only'][] = $classification;
	$reconciliation['classification'][] = $classification;
}

// Output reconciliation report
$report = [
	'schema' => 'url-drift-reconciliation',
	'generated_at' => gmdate( 'c' ),
	'source' => home_url( '/' ),
	'summary' => [
		'total_expected' => count( $expected_routes ),
		'total_actual' => count( $actual_routes ),
		'prod_only_count' => count( $prod_only ),
		'staging_only_count' => count( $staging_only ),
		'priority_immediate_count' => count( $reconciliation['priority_immediate'] ),
	],
	'prod_only' => $reconciliation['prod_only'],
	'staging_only' => $reconciliation['staging_only'],
	'priority_immediate' => $reconciliation['priority_immediate'],
	'classification' => $reconciliation['classification'],
];

echo json_encode( $report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

// Console summary
fwrite( STDERR, "\n=== URL DRIFT RECONCILIATION ===\n" );
fwrite( STDERR, "Expected URLs: " . count( $expected_routes ) . "\n" );
fwrite( STDERR, "Actual URLs: " . count( $actual_routes ) . "\n" );
fwrite( STDERR, "Prod-only (missing from staging): " . count( $prod_only ) . "\n" );
fwrite( STDERR, "Staging-only (surplus in staging): " . count( $staging_only ) . "\n" );
fwrite( STDERR, "Priority immediate: " . count( $reconciliation['priority_immediate'] ) . "\n" );

if ( ! empty( $reconciliation['priority_immediate'] ) ) {
	fwrite( STDERR, "\n=== PRIORITY IMMEDIATE URLS ===\n" );
	foreach ( $reconciliation['priority_immediate'] as $item ) {
		fwrite( STDERR, "- {$item['route']} (post_id: {$item['post_id']})\n" );
	}
}