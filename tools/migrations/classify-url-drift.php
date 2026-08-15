<?php
/**
 * Classify URL Drift for Reconciliation
 *
 * Marks URLs as APPROVED (publish in staging + audit) or NOT APPROVED (remove from production).
 *
 * Run with:
 *   wp eval "require 'tools/migrations/classify-url-drift.php';" --allow-root
 *
 * @package NVX\Migrations
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "ERROR: must run inside WordPress via wp eval \"require '...';\".\n" );
	exit( 1 );
}

// Load reconciliation report
$reconciliation_file = get_template_directory() . '/inc/data/url-drift-reconciliation.json';
if ( ! is_readable( $reconciliation_file ) ) {
	fwrite( STDERR, "ERROR: Cannot read url-drift-reconciliation.json. Run reconcile-url-drift.php first.\n" );
	exit( 1 );
}

$reconciliation = json_decode( file_get_contents( $reconciliation_file ), true );
if ( ! is_array( $reconciliation ) ) {
	fwrite( STDERR, "ERROR: Invalid JSON in url-drift-reconciliation.json\n" );
	exit( 1 );
}

// Classification decisions based on business rules
$classification_decisions = [
	// Priority immediate URLs - assume APPROVED for production content
	'/acido-hialuronico-relleno-madrid/' => 'APPROVED',
	'/neuromoduladores-botox-madrid/' => 'APPROVED',
	'/labios-acido-hialuronico-madrid/' => 'APPROVED',
	'/rinomodelacion-sin-cirugia-madrid/' => 'APPROVED',
	'/ojeras-surco-lagrimal-madrid/' => 'APPROVED',
	'/bioestimuladores-colageno-madrid/' => 'APPROVED',

	// Specific URLs to NOT APPROVE (remove from production)
	'/protocolo-novias-madrid/' => 'NOT_APPROVED',

	// Default classification for remaining URLs
	// Prod-only: conservative APPROVED for established content
	// Staging-only: conservative NOT_APPROVED for testing content
];

// Apply classifications
foreach ( $reconciliation['classification'] as &$item ) {
	$route = $item['route'];

	// Check for explicit decision
	if ( isset( $classification_decisions[ $route ] ) ) {
		$item['action'] = $classification_decisions[ $route ];
		continue;
	}

	// Default classification based on environment
	if ( $item['environment'] === 'prod-only' ) {
		// Conservative: APPROVE established production content
		$item['action'] = 'APPROVED';
	} else {
		// Conservative: NOT APPROVE testing/draft content
		$item['action'] = 'NOT_APPROVED';
	}
}
unset( $item );

// Generate reconciliation plan
$plan = [
	'approved_for_staging' => [],
	'remove_from_production' => [],
];

foreach ( $reconciliation['classification'] as $item ) {
	if ( $item['action'] === 'APPROVED' ) {
		$plan['approved_for_staging'][] = $item;
	} else {
		$plan['remove_from_production'][] = $item;
	}
}

// Add plan to reconciliation report
$reconciliation['plan'] = $plan;
$reconciliation['classified_at'] = gmdate( 'c' );

// Save updated reconciliation report
$output_file = get_template_directory() . '/inc/data/url-drift-reconciliation.json';
file_put_contents( $output_file, json_encode( $reconciliation, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );

// Output summary
echo json_encode( [
	'schema' => 'url-drift-classification',
	'classified_at' => $reconciliation['classified_at'],
	'summary' => [
		'total_classified' => count( $reconciliation['classification'] ),
		'approved_for_staging' => count( $plan['approved_for_staging'] ),
		'remove_from_production' => count( $plan['remove_from_production'] ),
	],
	'plan' => $plan,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

// Console summary
fwrite( STDERR, "\n=== URL DRIFT CLASSIFICATION ===\n" );
fwrite( STDERR, "Total classified: " . count( $reconciliation['classification'] ) . "\n" );
fwrite( STDERR, "APPROVED (publish in staging): " . count( $plan['approved_for_staging'] ) . "\n" );
fwrite( STDERR, "NOT APPROVED (remove from production): " . count( $plan['remove_from_production'] ) . "\n" );

if ( ! empty( $plan['approved_for_staging'] ) ) {
	fwrite( STDERR, "\n=== APPROVED FOR STAGING ===\n" );
	foreach ( $plan['approved_for_staging'] as $item ) {
		fwrite( STDERR, "- {$item['route']}\n" );
	}
}

if ( ! empty( $plan['remove_from_production'] ) ) {
	fwrite( STDERR, "\n=== REMOVE FROM PRODUCTION ===\n" );
	foreach ( $plan['remove_from_production'] as $item ) {
		fwrite( STDERR, "- {$item['route']}\n" );
	}
}