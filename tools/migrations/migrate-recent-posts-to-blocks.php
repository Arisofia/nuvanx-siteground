<?php
/**
 * Migrate Recent Posts to Valid HTML/Blocks
 *
 * Batch migration of recent posts to normalized HTML/Blocks content.
 * Uses the content normalizer to convert Markdown artifacts to valid HTML.
 *
 * Run with:
 *   wp eval "require 'tools/migrations/migrate-recent-posts-to-blocks.php';" --allow-root
 *
 * @package NVX\Migrations
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "ERROR: must run inside WordPress via wp eval \"require '...';\".\n" );
	exit( 1 );
}

// Load content normalizer
require_once __DIR__ . '/content-normalizer.php';

// Configuration
$POST_COUNT = 12; // Number of recent posts to migrate
$DRY_RUN = true; // Set to false to actually apply changes

// Get recent posts
$recent_posts = get_posts( [
	'post_type'      => 'post',
	'post_status'    => 'publish',
	'posts_per_page' => $POST_COUNT,
	'orderby'        => 'date',
	'order'          => 'DESC',
	'fields'         => 'ids',
] );

$migration_results = [
	'total_checked' => count( $recent_posts ),
	'needs_migration' => 0,
	'migrated' => 0,
	'validation_failed' => 0,
	'posts' => [],
];

foreach ( $recent_posts as $post_id ) {
	$post = get_post( $post_id );
	if ( ! $post instanceof WP_Post ) {
		continue;
	}

	$post_result = [
		'post_id' => $post_id,
		'title' => $post->post_title,
		'needs_migration' => false,
		'migrated' => false,
		'validation' => [],
	];

	$original_content = $post->post_content;

	// Check if content needs migration
	$validation_original = nvx_validate_normalized_content( $original_content );
	if ( $validation_original['valid'] ) {
		$post_result['validation'] = $validation_original;
		$migration_results['posts'][] = $post_result;
		continue;
	}

	$post_result['needs_migration'] = true;
	$migration_results['needs_migration']++;

	// Normalize content
	$normalized_content = nvx_normalize_to_blocks( $original_content );

	// Validate normalized content
	$validation_normalized = nvx_validate_normalized_content( $normalized_content );
	$post_result['validation'] = $validation_normalized;

	if ( ! $validation_normalized['valid'] ) {
		$migration_results['validation_failed']++;
		fwrite( STDERR, "SKIP: Post {$post_id} ({$post->post_title}) - validation failed after normalization\n" );
		foreach ( $validation_normalized['issues'] as $issue ) {
			fwrite( STDERR, "  - {$issue}\n" );
		}
		$migration_results['posts'][] = $post_result;
		continue;
	}

	// Apply migration (if not dry run)
	if ( ! $DRY_RUN ) {
		wp_update_post( [
			'ID' => $post_id,
			'post_content' => $normalized_content,
		] );
		$post_result['migrated'] = true;
		$migration_results['migrated']++;
		fwrite( STDERR, "MIGRATED: Post {$post_id} ({$post->post_title})\n" );
	} else {
		fwrite( STDERR, "DRY RUN: Post {$post_id} ({$post->post_title}) - would be migrated\n" );
	}

	$migration_results['posts'][] = $post_result;
}

// Output migration report
$report = [
	'schema' => 'recent-posts-migration',
	'migrated_at' => gmdate( 'c' ),
	'source' => home_url( '/' ),
	'dry_run' => $DRY_RUN,
	'summary' => [
		'total_checked' => $migration_results['total_checked'],
		'needs_migration' => $migration_results['needs_migration'],
		'migrated' => $migration_results['migrated'],
		'validation_failed' => $migration_results['validation_failed'],
	],
	'posts' => $migration_results['posts'],
];

echo json_encode( $report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

// Console summary
fwrite( STDERR, "\n=== RECENT POSTS MIGRATION ===\n" );
fwrite( STDERR, "Dry run: " . ( $DRY_RUN ? 'YES' : 'NO' ) . "\n" );
fwrite( STDERR, "Total checked: " . $migration_results['total_checked'] . "\n" );
fwrite( STDERR, "Needs migration: " . $migration_results['needs_migration'] . "\n" );
fwrite( STDERR, "Migrated: " . $migration_results['migrated'] . "\n" );
fwrite( STDERR, "Validation failed: " . $migration_results['validation_failed'] . "\n" );

if ( $DRY_RUN && $migration_results['needs_migration'] > 0 ) {
	fwrite( STDERR, "\nTo apply changes, set \$DRY_RUN = false in the script and run again.\n" );
}

// Exit with error if any posts failed validation
if ( $migration_results['validation_failed'] > 0 ) {
	fwrite( STDERR, "\nMIGRATION_VALIDATION=FAIL failed={$migration_results['validation_failed']}\n" );
	exit( 1 );
}

if ( $migration_results['needs_migration'] > 0 && $DRY_RUN ) {
	fwrite( STDERR, "\nMIGRATION_VALIDATION=DRY_RUN needs_migration={$migration_results['needs_migration']}\n" );
	exit( 0 );
}

if ( $migration_results['migrated'] > 0 ) {
	fwrite( STDERR, "\nMIGRATION_VALIDATION=PASS migrated={$migration_results['migrated']}\n" );
} else {
	fwrite( STDERR, "\nMIGRATION_VALIDATION=PASS no_migration_needed\n" );
}