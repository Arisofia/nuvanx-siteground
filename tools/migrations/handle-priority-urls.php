<?php
/**
 * Handle Priority Immediate URLs
 *
 * Special handling for priority immediate URLs with business-specific logic.
 * Treats recent blog posts differently from static treatment pages.
 *
 * Run with:
 *   wp eval "require 'tools/migrations/handle-priority-urls.php';" --allow-root
 *
 * @package NVX\Migrations
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "ERROR: must run inside WordPress via wp eval \"require '...';\".\n" );
	exit( 1 );
}

global $wpdb;

// Priority immediate treatment pages
$priority_treatment_pages = [
	'/acido-hialuronico-relleno-madrid/',
	'/neuromoduladores-botox-madrid/',
	'/labios-acido-hialuronico-madrid/',
	'/rinomodelacion-sin-cirugia-madrid/',
	'/ojeras-surco-lagrimal-madrid/',
	'/bioestimuladores-colageno-madrid/',
];

// Special case: protocolo-novias-madrid (NOT APPROVED)
$not_approved_urls = [
	'/protocolo-novias-madrid/',
];

// Get recent blog posts (12 most recent)
$recent_posts = get_posts( [
	'post_type'      => 'post',
	'post_status'    => 'publish',
	'posts_per_page' => 12,
	'orderby'        => 'date',
	'order'          => 'DESC',
	'fields'         => 'ids',
] );

$priority_decisions = [];

// Classify treatment pages as APPROVED
foreach ( $priority_treatment_pages as $route ) {
	$priority_decisions[ $route ] = [
		'action' => 'APPROVED',
		'reason' => 'Established treatment page with production content',
		'priority' => 'immediate',
	];
}

// Classify special URLs as NOT APPROVED
foreach ( $not_approved_urls as $route ) {
	$priority_decisions[ $route ] = [
		'action' => 'NOT_APPROVED',
		'reason' => 'Business decision to remove from production',
		'priority' => 'immediate',
	];
}

// Classify recent blog posts based on governance catalog
$blog_catalog_file = get_template_directory() . '/inc/data/seo-blog-post-metadata.json';
$blog_catalog = [];

if ( is_readable( $blog_catalog_file ) ) {
	$blog_catalog = json_decode( file_get_contents( $blog_catalog_file ), true );
	if ( ! is_array( $blog_catalog ) ) {
		$blog_catalog = [];
	}
}

foreach ( $recent_posts as $post_id ) {
	$post = get_post( $post_id );
	if ( ! $post instanceof WP_Post ) {
		continue;
	}

	$slug = $post->post_name;
	$route = '/' . $slug . '/';

	// Check if in governance catalog
	$is_governed = isset( $blog_catalog[ $slug ] ) && is_array( $blog_catalog[ $slug ] );

	if ( $is_governed ) {
		$priority_decisions[ $route ] = [
			'action' => 'APPROVED',
			'reason' => 'Governed blog post in catalog',
			'priority' => 'immediate',
			'post_id' => $post_id,
		];
	} else {
		$priority_decisions[ $route ] = [
			'action' => 'NOT_APPROVED',
			'reason' => 'Ungoverned blog post - not in catalog',
			'priority' => 'immediate',
			'post_id' => $post_id,
		];
	}
}

// Output priority decisions
$report = [
	'schema' => 'priority-url-decisions',
	'generated_at' => gmdate( 'c' ),
	'source' => home_url( '/' ),
	'summary' => [
		'treatment_pages_approved' => count( $priority_treatment_pages ),
		'special_urls_not_approved' => count( $not_approved_urls ),
		'recent_posts_total' => count( $recent_posts ),
		'recent_posts_approved' => 0,
		'recent_posts_not_approved' => 0,
	],
	'decisions' => $priority_decisions,
];

// Count approvals
foreach ( $priority_decisions as $decision ) {
	if ( isset( $decision['post_id'] ) ) {
		if ( $decision['action'] === 'APPROVED' ) {
			$report['summary']['recent_posts_approved']++;
		} else {
			$report['summary']['recent_posts_not_approved']++;
		}
	}
}

echo json_encode( $report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

// Console summary
fwrite( STDERR, "\n=== PRIORITY URL DECISIONS ===\n" );
fwrite( STDERR, "Treatment pages approved: " . count( $priority_treatment_pages ) . "\n" );
fwrite( STDERR, "Special URLs not approved: " . count( $not_approved_urls ) . "\n" );
fwrite( STDERR, "Recent posts total: " . count( $recent_posts ) . "\n" );
fwrite( STDERR, "Recent posts approved: " . $report['summary']['recent_posts_approved'] . "\n" );
fwrite( STDERR, "Recent posts not approved: " . $report['summary']['recent_posts_not_approved'] . "\n" );

fwrite( STDERR, "\n=== TREATMENT PAGES (APPROVED) ===\n" );
foreach ( $priority_treatment_pages as $route ) {
	fwrite( STDERR, "- {$route}\n" );
}

fwrite( STDERR, "\n=== SPECIAL URLS (NOT APPROVED) ===\n" );
foreach ( $not_approved_urls as $route ) {
	fwrite( STDERR, "- {$route}\n" );
}

fwrite( STDERR, "\n=== RECENT BLOG POSTS ===\n" );
foreach ( $recent_posts as $post_id ) {
	$post = get_post( $post_id );
	if ( $post instanceof WP_Post ) {
		$slug = $post->post_name;
		$route = '/' . $slug . '/';
		$decision = $priority_decisions[ $route ] ?? null;
		$status = $decision ? $decision['action'] : 'UNKNOWN';
		fwrite( STDERR, "- {$route} ({$status})\n" );
	}
}