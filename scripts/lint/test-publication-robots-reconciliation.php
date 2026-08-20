<?php
/**
 * Static contract for publication-manifest robots reconciliation.
 */

$root          = dirname( __DIR__, 2 );
$manifest_path = $root . '/wp-content/themes/nuvanx-medical/inc/data/publication-manifest.json';
$migration     = $root . '/tools/migrations/reconcile-publication-robots.php';
$seo_metadata  = $root . '/wp-content/themes/nuvanx-medical/inc/nvx-seo-metadata.php';
$staging       = $root . '/.github/workflows/staging.yml';
$production    = $root . '/.github/workflows/production.yml';
$deploy        = $root . '/tools/deploy/deploy-to-prod.sh';

$manifest_raw = file_get_contents( $manifest_path );
$manifest     = false === $manifest_raw ? null : json_decode( $manifest_raw, true );
if ( ! is_array( $manifest ) || 'nuvanx-publication-manifest' !== (string) ( $manifest['schema'] ?? '' ) || ! is_array( $manifest['routes'] ?? null ) ) {
	fwrite( STDERR, "PUBLICATION_ROBOTS_RECONCILIATION_STATIC=FAIL reason=manifest_invalid\n" );
	exit( 1 );
}

$indexable = 0;
$noindex   = 0;
$ids       = array();
foreach ( $manifest['routes'] as $route => $config ) {
	if ( ! is_array( $config ) || 'publish' !== (string) ( $config['status'] ?? '' ) || ! is_array( $config['robots'] ?? null ) ) {
		fwrite( STDERR, "PUBLICATION_ROBOTS_RECONCILIATION_STATIC=FAIL reason=invalid_route route={$route}\n" );
		exit( 1 );
	}
	if ( ! is_bool( $config['robots']['index'] ?? null ) || true !== ( $config['robots']['follow'] ?? null ) ) {
		fwrite( STDERR, "PUBLICATION_ROBOTS_RECONCILIATION_STATIC=FAIL reason=unsupported_robots route={$route}\n" );
		exit( 1 );
	}
	$id = (int) ( $config['post_id'] ?? 0 );
	if ( $id <= 0 || isset( $ids[ $id ] ) ) {
		fwrite( STDERR, "PUBLICATION_ROBOTS_RECONCILIATION_STATIC=FAIL reason=invalid_post_identity route={$route}\n" );
		exit( 1 );
	}
	$ids[ $id ] = true;
	if ( $config['robots']['index'] ) {
		++$indexable;
	} else {
		++$noindex;
	}
}

foreach ( array( $migration, $seo_metadata, $staging, $production, $deploy ) as $path ) {
	if ( ! is_file( $path ) || false === file_get_contents( $path ) ) {
		fwrite( STDERR, "PUBLICATION_ROBOTS_RECONCILIATION_STATIC=FAIL reason=unreadable_dependency\n" );
		exit( 1 );
	}
}

$migration_raw    = file_get_contents( $migration );
$seo_metadata_raw = file_get_contents( $seo_metadata );
$staging_raw      = file_get_contents( $staging );
$production_raw = file_get_contents( $production );
$deploy_raw     = file_get_contents( $deploy );

$required = array(
	array( $migration_raw, "_yoast_wpseo_meta-robots-noindex" ),
	array( $migration_raw, "_yoast_wpseo_meta-robots-nofollow" ),
	array( $migration_raw, "delete_post_meta" ),
	array( $migration_raw, "update_post_meta" ),
	array( $migration_raw, "PUBLICATION_ROBOTS_RECONCILIATION=PASS" ),
	array( $seo_metadata_raw, "defined( 'WP_CLI' ) && WP_CLI && '1' === getenv( 'NVX_ALLOW_STAGING_YOAST_INDEXABLE_REBUILD' )" ),
	array( $staging_raw, "reconcile-publication-robots.php" ),
	array( $staging_raw, "NVX_ALLOW_STAGING_YOAST_INDEXABLE_REBUILD=1 wp yoast index --reindex --allow-root" ),
	array( $production_raw, "reconcile-publication-robots.php" ),
	array( $deploy_raw, "ROBOTS_RECONCILIATION_SCRIPT" ),
	array( $deploy_raw, "wp yoast index --reindex --allow-root" ),
);
foreach ( $required as $pair ) {
	if ( false === strpos( $pair[0], $pair[1] ) ) {
		fwrite( STDERR, "PUBLICATION_ROBOTS_RECONCILIATION_STATIC=FAIL reason=missing_contract_marker marker={$pair[1]}\n" );
		exit( 1 );
	}
}

printf(
	"PUBLICATION_ROBOTS_RECONCILIATION_STATIC=PASS routes=%d indexable=%d noindex=%d\n",
	count( $manifest['routes'] ),
	$indexable,
	$noindex
);
