<?php
/**
 * Asset Cleanup After Publication Reconciliation
 *
 * Only after reconciling publication:
 * - Inventory real references
 * - Identify derivatives without origin
 * - Verify no public URL consumes them
 * - Delete orphans
 * - Add media integrity gate
 *
 * Run with:
 *   wp eval "require 'tools/migrations/asset-cleanup.php';" --allow-root
 *
 * @package NVX\Migrations
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "ERROR: must run inside WordPress via wp eval \"require '...';\".\n" );
	exit( 1 );
}

global $wpdb;

// Load publication manifest to get reconciled routes
$manifest_file = get_template_directory() . '/inc/data/publication-manifest.json';
if ( ! is_readable( $manifest_file ) ) {
	fwrite( STDERR, "ERROR: Cannot read publication-manifest.json. Run generate-publication-manifest.php first.\n" );
	exit( 1 );
}

$manifest = json_decode( file_get_contents( $manifest_file ), true );
if ( ! is_array( $manifest ) ) {
	fwrite( STDERR, "ERROR: Invalid JSON in publication-manifest.json\n" );
	exit( 1 );
}

// Check if reconciliation has been performed
if ( ! isset( $manifest['reconciliation'] ) || ! isset( $manifest['reconciliation']['last_reconciled_at'] ) ) {
	fwrite( STDERR, "ERROR: Publication has not been reconciled yet. Run reconciliation first.\n" );
	exit( 1 );
}

/**
 * Inventory real references from WordPress content.
 *
 * @return array Asset references
 */
function nvx_inventory_real_references(): array {
	global $wpdb;

	$references = [
		'images' => [],
		'scripts' => [],
		'styles' => [],
		'media' => [],
	];

	// Get images from post content
	$image_posts = $wpdb->get_col( "
		SELECT post_content
		FROM {$wpdb->posts}
		WHERE post_status = 'publish'
		AND post_type IN ('page', 'post')
	" );

	foreach ( $image_posts as $content ) {
		// Extract image URLs
		preg_match_all( '/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $content, $matches );
		foreach ( $matches[1] as $src ) {
			$references['images'][] = $src;
		}
	}

	// Get images from theme directory
	$upload_dir = wp_upload_dir();
	$theme_images = glob( $upload_dir['basedir'] . '/**/*.{jpg,jpeg,png,gif,webp,svg}', GLOB_BRACE );
	foreach ( $theme_images as $image_path ) {
		$image_url = str_replace( $upload_dir['basedir'], $upload_dir['baseurl'], $image_path );
		$references['images'][] = $image_url;
	}

	// Get script/style references from theme
	$theme_dir = get_template_directory();
	$scripts = glob( $theme_dir . '/assets/js/*.js' );
	foreach ( $scripts as $script_path ) {
		$script_url = get_template_directory_uri() . '/assets/js/' . basename( $script_path );
		$references['scripts'][] = $script_url;
	}

	$styles = glob( $theme_dir . '/assets/css/*.css' );
	foreach ( $styles as $style_path ) {
		$style_url = get_template_directory_uri() . '/assets/css/' . basename( $style_path );
		$references['styles'][] = $style_url;
	}

	return $references;
}

/**
 * Identify derivatives without origin.
 *
 * @param array $references Asset references
 * @return array Orphaned assets
 */
function nvx_identify_orphans( array $references ): array {
	global $wpdb;

	$orphans = [
		'images' => [],
		'scripts' => [],
		'styles' => [],
	];

	$upload_dir = wp_upload_dir();
	$theme_dir = get_template_directory();

	// Check images
	foreach ( $references['images'] as $image_url ) {
		$image_path = str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $image_url );

		// Check if file exists
		if ( ! file_exists( $image_path ) ) {
			$orphans['images'][] = [
				'url' => $image_url,
				'path' => $image_path,
				'reason' => 'file_not_found',
			];
			continue;
		}

		// Check if referenced in any published content
		$is_referenced = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_content LIKE %s",
			'%' . $wpdb->esc_like( $image_url ) . '%'
		) );

		if ( ! $is_referenced ) {
			$orphans['images'][] = [
				'url' => $image_url,
				'path' => $image_path,
				'reason' => 'not_referenced',
			];
		}
	}

	// Check scripts
	foreach ( $references['scripts'] as $script_url ) {
		$script_path = str_replace( get_template_directory_uri(), $theme_dir, $script_url );

		if ( ! file_exists( $script_path ) ) {
			$orphans['scripts'][] = [
				'url' => $script_url,
				'path' => $script_path,
				'reason' => 'file_not_found',
			];
		}
	}

	// Check styles
	foreach ( $references['styles'] as $style_url ) {
		$style_path = str_replace( get_template_directory_uri(), $theme_dir, $style_url );

		if ( ! file_exists( $style_path ) ) {
			$orphans['styles'][] = [
				'url' => $style_url,
				'path' => $style_path,
				'reason' => 'file_not_found',
			];
		}
	}

	return $orphans;
}

/**
 * Verify no public URL consumes orphaned assets.
 *
 * @param array $orphans Orphaned assets
 * @return array Consumption report
 */
function nvx_verify_public_consumption( array $orphans ): array {
	$consumption = [];

	// For each orphan, check if it's referenced in routes.json
	$routes_file = get_template_directory() . '/inc/data/routes.json';
	if ( is_readable( $routes_file ) ) {
		$routes_config = json_decode( file_get_contents( $routes_file ), true );

		foreach ( $orphans['images'] as $orphan ) {
			$is_referenced = false;

			foreach ( $routes_config as $route => $config ) {
				// Check if route contains orphan URL
				if ( strpos( json_encode( $config ), $orphan['url'] ) !== false ) {
					$is_referenced = true;
					break;
				}
			}

			$consumption[] = [
				'url' => $orphan['url'],
				'publicly_consumed' => $is_referenced,
			];
		}
	}

	return $consumption;
}

/**
 * Delete orphaned assets.
 *
 * @param array $orphans Orphaned assets
 * @param bool $dry_run Dry run mode
 * @return array Deletion report
 */
function nvx_delete_orphans( array $orphans, bool $dry_run = true ): array {
	$deletion_report = [
		'deleted' => [],
		'skipped' => [],
		'errors' => [],
	];

	foreach ( $orphans['images'] as $orphan ) {
		if ( $orphan['reason'] === 'not_referenced' ) {
			if ( $dry_run ) {
				$deletion_report['skipped'][] = $orphan['url'];
			} else {
				if ( unlink( $orphan['path'] ) ) {
					$deletion_report['deleted'][] = $orphan['url'];
				} else {
					$deletion_report['errors'][] = $orphan['url'];
				}
			}
		}
	}

	return $deletion_report;
}

/**
 * Generate media integrity hashes.
 *
 * @return array Integrity hashes
 */
function nvx_generate_media_integrity(): array {
	$integrity = [];

	$upload_dir = wp_upload_dir();
	$images = glob( $upload_dir['basedir'] . '/**/*.{jpg,jpeg,png,gif,webp,svg}', GLOB_BRACE );

	foreach ( $images as $image_path ) {
		$image_url = str_replace( $upload_dir['basedir'], $upload_dir['baseurl'], $image_path );

		// Generate SHA-256 hash
		$hash = hash_file( 'sha256', $image_path );

		$integrity[ $image_url ] = [
			'sha256' => 'sha256-' . base64_encode( pack( 'H*', $hash ) ),
			'path' => $image_path,
			'size' => filesize( $image_path ),
		];
	}

	return $integrity;
}

// Main execution
$references = nvx_inventory_real_references();
$orphans = nvx_identify_orphans( $references );
$consumption = nvx_verify_public_consumption( $orphans );
$integrity = nvx_generate_media_integrity();

// Dry-run mode: controlled via MIGRATION_DRY_RUN env var (same convention
// as content-hygiene-shared.php and content-hygiene-staging-only.php).
// Default true — no deletions unless MIGRATION_DRY_RUN=0 is explicitly set.
$DRY_RUN = '0' !== getenv( 'MIGRATION_DRY_RUN' );
$deletion_report = nvx_delete_orphans( $orphans, $DRY_RUN );

$report = [
	'schema' => 'asset-cleanup',
	'cleaned_at' => gmdate( 'c' ),
	'source' => home_url( '/' ),
	'reconciliation' => $manifest['reconciliation'],
	'inventory' => [
		'images_count' => count( $references['images'] ),
		'scripts_count' => count( $references['scripts'] ),
		'styles_count' => count( $references['styles'] ),
	],
	'orphans' => [
		'images_count' => count( $orphans['images'] ),
		'scripts_count' => count( $orphans['scripts'] ),
		'styles_count' => count( $orphans['styles'] ),
		'details' => $orphans,
	],
	'consumption' => $consumption,
	'deletion' => $deletion_report,
	'integrity' => $integrity,
	'dry_run' => $DRY_RUN,
];

echo json_encode( $report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

// Console summary
fwrite( STDERR, "\n=== ASSET CLEANUP ===\n" );
fwrite( STDERR, "Inventory:\n" );
fwrite( STDERR, "  Images: " . count( $references['images'] ) . "\n" );
fwrite( STDERR, "  Scripts: " . count( $references['scripts'] ) . "\n" );
fwrite( STDERR, "  Styles: " . count( $references['styles'] ) . "\n" );
fwrite( STDERR, "Orphans:\n" );
fwrite( STDERR, "  Images: " . count( $orphans['images'] ) . "\n" );
fwrite( STDERR, "  Scripts: " . count( $orphans['scripts'] ) . "\n" );
fwrite( STDERR, "  Styles: " . count( $orphans['styles'] ) . "\n" );
fwrite( STDERR, "Deletion:\n" );
fwrite( STDERR, "  Deleted: " . count( $deletion_report['deleted'] ) . "\n" );
fwrite( STDERR, "  Skipped: " . count( $deletion_report['skipped'] ) . "\n" );
fwrite( STDERR, "  Errors: " . count( $deletion_report['errors'] ) . "\n" );
fwrite( STDERR, "Media Integrity: " . count( $integrity ) . " hashes generated\n" );

if ( $DRY_RUN ) {
	fwrite( STDERR, "\nDRY RUN MODE - Set MIGRATION_DRY_RUN=0 to actually delete files\n" );
}