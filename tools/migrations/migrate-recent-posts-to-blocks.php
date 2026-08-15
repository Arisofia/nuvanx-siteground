<?php
/**
 * Normalize governed NUVANX journal posts that still contain legacy Markdown.
 *
 * Dry-run is the default. Apply only with NVX_MIGRATION_APPLY=yes after the
 * report has been reviewed. The migration selects posts from the versioned SEO
 * catalogue instead of an arbitrary "latest N posts" window.
 *
 * Usage:
 *   wp eval-file tools/migrations/migrate-recent-posts-to-blocks.php --allow-root
 *   NVX_MIGRATION_APPLY=yes wp eval-file tools/migrations/migrate-recent-posts-to-blocks.php --allow-root
 *
 * @package NVX\Migrations
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "ERROR: must run inside WordPress via wp eval-file.\n" );
	exit( 1 );
}

require_once __DIR__ . '/content-normalizer.php';

$apply = 'yes' === strtolower( trim( (string) getenv( 'NVX_MIGRATION_APPLY' ) ) );
$catalog_file = get_template_directory() . '/inc/data/seo-blog-post-metadata.json';
if ( ! is_readable( $catalog_file ) ) {
	fwrite( STDERR, "MIGRATION_VALIDATION=FAIL reason=seo_catalog_unavailable\n" );
	exit( 1 );
}

$catalog = json_decode( (string) file_get_contents( $catalog_file ), true );
if ( ! is_array( $catalog ) ) {
	fwrite( STDERR, "MIGRATION_VALIDATION=FAIL reason=seo_catalog_invalid\n" );
	exit( 1 );
}

$results = array(
	'total_catalogued'  => count( $catalog ),
	'published_checked' => 0,
	'needs_migration'   => 0,
	'migrated'          => 0,
	'validation_failed' => 0,
	'posts'             => array(),
);

foreach ( array_keys( $catalog ) as $slug ) {
	$post = get_page_by_path( (string) $slug, OBJECT, 'post' );
	if ( ! ( $post instanceof WP_Post ) || 'publish' !== $post->post_status ) {
		continue;
	}

	++$results['published_checked'];
	$original_validation = nvx_validate_normalized_content( (string) $post->post_content );
	$post_result = array(
		'post_id'         => (int) $post->ID,
		'slug'            => (string) $post->post_name,
		'needs_migration' => ! $original_validation['valid'],
		'migrated'        => false,
		'issues_before'   => $original_validation['issues'],
		'issues_after'    => array(),
	);

	if ( $original_validation['valid'] ) {
		$results['posts'][] = $post_result;
		continue;
	}

	++$results['needs_migration'];
	$normalized = nvx_normalize_to_blocks( (string) $post->post_content );
	$after      = nvx_validate_normalized_content( $normalized );
	$post_result['issues_after'] = $after['issues'];

	if ( ! $after['valid'] ) {
		++$results['validation_failed'];
		$results['posts'][] = $post_result;
		fwrite( STDERR, sprintf( "SKIP id=%d slug=%s validation_failed=%s\n", $post->ID, $post->post_name, implode( '|', $after['issues'] ) ) );
		continue;
	}

	if ( $apply ) {
		$updated = wp_update_post(
			array(
				'ID'           => (int) $post->ID,
				'post_content' => wp_slash( $normalized ),
			),
			true
		);
		if ( is_wp_error( $updated ) || (int) $updated !== (int) $post->ID ) {
			++$results['validation_failed'];
			$post_result['issues_after'][] = is_wp_error( $updated ) ? $updated->get_error_message() : 'unexpected_update_id';
			$results['posts'][] = $post_result;
			continue;
		}

		clean_post_cache( (int) $post->ID );
		$verified = get_post( (int) $post->ID );
		$verified_validation = $verified instanceof WP_Post
			? nvx_validate_normalized_content( (string) $verified->post_content )
			: array( 'valid' => false, 'issues' => array( 'post_missing_after_update' ) );

		if ( ! $verified_validation['valid'] ) {
			++$results['validation_failed'];
			$post_result['issues_after'] = $verified_validation['issues'];
			$results['posts'][] = $post_result;
			continue;
		}

		$post_result['migrated'] = true;
		++$results['migrated'];
	}

	$results['posts'][] = $post_result;
}

$report = array(
	'schema'      => 'governed-blog-content-migration',
	'generated_at'=> gmdate( 'c' ),
	'source'      => home_url( '/' ),
	'apply'       => $apply,
	'summary'     => array(
		'total_catalogued'  => $results['total_catalogued'],
		'published_checked' => $results['published_checked'],
		'needs_migration'   => $results['needs_migration'],
		'migrated'          => $results['migrated'],
		'validation_failed' => $results['validation_failed'],
	),
	'posts'       => $results['posts'],
);

echo wp_json_encode( $report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

if ( $results['validation_failed'] > 0 ) {
	fwrite( STDERR, sprintf( "\nMIGRATION_VALIDATION=FAIL failed=%d\n", $results['validation_failed'] ) );
	exit( 1 );
}

if ( ! $apply && $results['needs_migration'] > 0 ) {
	fwrite( STDERR, sprintf( "\nMIGRATION_VALIDATION=DRY_RUN needs_migration=%d\n", $results['needs_migration'] ) );
	exit( 0 );
}

fwrite(
	STDERR,
	sprintf(
		"\nMIGRATION_VALIDATION=PASS checked=%d migrated=%d\n",
		$results['published_checked'],
		$results['migrated']
	)
);
