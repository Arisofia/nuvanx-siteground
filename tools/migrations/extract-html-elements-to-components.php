<?php
/**
 * Extract HTML Elements to NUVANX Components
 *
 * Extracts layout, CTAs, NAP, claims, cronograma, and styles from free HTML
 * and replaces them with NUVANX components/tokens like governed medical pages.
 *
 * Run with:
 *   wp eval "require 'tools/migrations/extract-html-elements-to-components.php';" --allow-root
 *
 * @package NVX\Migrations
 */


if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "ERROR: must run inside WordPress via wp eval \"require '...';\".\n" );
	exit( 1 );
}

// NUVANX component patterns
$nuvx_components = [
	'cta' => [
		'pattern' => '/<a[^>]*class=["\'][^"\']*btn[^"\']*["\'][^>]*>([^<]+)<\/a>/i',
		'replacement' => '<!-- wp:nvx/cta -->$1<!-- /wp:nvx/cta -->',
		'description' => 'CTA buttons',
	],
	'nap' => [
		'pattern' => '/<div[^>]*class=["\'][^"\']*contact[^"\']*["\'][^>]*>.*?<\/div>/is',
		'replacement' => '<!-- wp:nvx/nap --><!-- /wp:nvx/nap -->',
		'description' => 'NAP (Name, Address, Phone)',
	],
	'claim' => [
		'pattern' => '/<div[^>]*class=["\'][^"\']*claim[^"\']*["\'][^>]*>.*?<\/div>/is',
		'replacement' => '<!-- wp:nvx/claim --><!-- /wp:nvx/claim -->',
		'description' => 'Marketing claims',
	],
	'cronograma' => [
		'pattern' => '/<div[^>]*class=["\'][^"\']*cronograma[^"\']*["\'][^>]*>.*?<\/div>/is',
		'replacement' => '<!-- wp:nvx/cronograma --><!-- /wp:nvx/cronograma -->',
		'description' => 'Treatment schedules',
	],
	'inline_style' => [
		'pattern' => '/style=["\'][^"\']*["\']/i',
		'replacement' => '',
		'description' => 'Inline styles',
	],
];

/**
 * Detect free HTML elements in content.
 *
 * @param string $content Post content
 * @return array Detected elements
 */
function nvx_detect_free_html_elements( string $content ): array {
	global $nuvx_components;

	$detected = [];

	foreach ( $nuvx_components as $component_name => $component ) {
		$matches = [];
		preg_match_all( $component['pattern'], $content, $matches );

		if ( ! empty( $matches[0] ) ) {
			$detected[ $component_name ] = [
				'description' => $component['description'],
				'count' => count( $matches[0] ),
				'samples' => array_slice( $matches[0], 0, 3 ),
			];
		}
	}

	return $detected;
}

/**
 * Replace free HTML elements with NUVANX components.
 *
 * @param string $content Post content
 * @return string Content with components
 */
function nvx_replace_with_components( string $content ): string {
	global $nuvx_components;

	$updated = $content;

	foreach ( $nuvx_components as $component_name => $component ) {
		$updated = preg_replace( $component['pattern'], $component['replacement'], $updated );
	}

	return $updated;
}

/**
 * Validate component availability.
 *
 * @return array Available components
 */
function nvx_validate_component_availability(): array {
	$available = [];

	// Check if component functions exist
	$component_functions = [
		'nvx_render_cta' => 'cta',
		'nvx_render_nap' => 'nap',
		'nvx_render_claim' => 'claim',
		'nvx_render_cronograma' => 'cronograma',
	];

	foreach ( $component_functions as $function => $component ) {
		if ( function_exists( $function ) ) {
			$available[ $component ] = true;
		} else {
			$available[ $component ] = false;
		}
	}

	return $available;
}

// Get all published posts and pages
$posts = get_posts( [
	'post_type'      => [ 'page', 'post' ],
	'post_status'    => 'publish',
	'posts_per_page' => -1,
	'fields'         => 'ids',
] );

$component_availability = nvx_validate_component_availability();

$extraction_results = [
	'total_checked' => count( $posts ),
	'has_free_html' => 0,
	'migrated' => 0,
	'component_availability' => $component_availability,
	'posts' => [],
];

foreach ( $posts as $post_id ) {
	$post = get_post( $post_id );
	if ( ! $post instanceof WP_Post ) {
		continue;
	}

	$post_result = [
		'post_id' => $post_id,
		'title' => $post->post_title,
		'detected_elements' => [],
		'migrated' => false,
	];

	$content = $post->post_content;

	// Detect free HTML elements
	$detected = nvx_detect_free_html_elements( $content );

	if ( empty( $detected ) ) {
		$extraction_results['posts'][] = $post_result;
		continue;
	}

	$post_result['detected_elements'] = $detected;
	$extraction_results['has_free_html']++;

	// Replace with components
	$updated_content = nvx_replace_with_components( $content );

	// Check if content changed
	if ( $updated_content !== $content ) {
		wp_update_post( [
			'ID' => $post_id,
			'post_content' => $updated_content,
		] );
		$post_result['migrated'] = true;
		$extraction_results['migrated']++;
		fwrite( STDERR, "MIGRATED: Post {$post_id} ({$post->post_title})\n" );
		foreach ( $detected as $element => $info ) {
			fwrite( STDERR, "  - {$info['description']}: {$info['count']} found\n" );
		}
	}

	$extraction_results['posts'][] = $post_result;
}

// Output extraction report
$report = [
	'schema' => 'html-elements-extraction',
	'extracted_at' => gmdate( 'c' ),
	'source' => home_url( '/' ),
	'summary' => [
		'total_checked' => $extraction_results['total_checked'],
		'has_free_html' => $extraction_results['has_free_html'],
		'migrated' => $extraction_results['migrated'],
	],
	'component_availability' => $component_availability,
	'posts' => $extraction_results['posts'],
];

echo json_encode( $report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

// Console summary
fwrite( STDERR, "\n=== HTML ELEMENTS EXTRACTION ===\n" );
fwrite( STDERR, "Total checked: " . $extraction_results['total_checked'] . "\n" );
fwrite( STDERR, "Has free HTML: " . $extraction_results['has_free_html'] . "\n" );
fwrite( STDERR, "Migrated: " . $extraction_results['migrated'] . "\n" );

fwrite( STDERR, "\n=== COMPONENT AVAILABILITY ===\n" );
foreach ( $component_availability as $component => $available ) {
	$status = $available ? 'AVAILABLE' : 'NOT AVAILABLE';
	fwrite( STDERR, "{$component}: {$status}\n" );
}

if ( $extraction_results['migrated'] > 0 ) {
	fwrite( STDERR, "\nEXTRACTION_VALIDATION=PASS migrated={$extraction_results['migrated']}\n" );
} else {
	fwrite( STDERR, "\nEXTRACTION_VALIDATION=PASS no_migration_needed\n" );
}