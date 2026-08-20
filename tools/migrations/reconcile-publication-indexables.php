<?php
/**
 * Reconcile Yoast indexables with the versioned publication manifest.
 *
 * This migration only rebuilds Yoast's derived indexable rows for manifest
 * routes expected to be indexable. It never mutates post content, post status,
 * permalinks, or robots post meta. It must run under the narrow WP-CLI staging
 * bypass used exclusively to verify Staging2 sitemap coverage.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "PUBLICATION_INDEXABLE_RECONCILIATION=FAIL reason=wordpress_not_bootstrapped\n" );
	exit( 1 );
}

if ( ! defined( 'WP_CLI' ) || ! WP_CLI || '1' !== getenv( 'NVX_ALLOW_STAGING_YOAST_INDEXABLE_REBUILD' ) ) {
	fwrite( STDERR, "PUBLICATION_INDEXABLE_RECONCILIATION=FAIL reason=guarded_wp_cli_bypass_required\n" );
	exit( 1 );
}

$theme_dir     = get_template_directory();
$manifest_path = $theme_dir . '/inc/data/publication-manifest.json';

if ( ! is_file( $manifest_path ) || ! is_readable( $manifest_path ) ) {
	fwrite( STDERR, "PUBLICATION_INDEXABLE_RECONCILIATION=FAIL reason=manifest_unreadable\n" );
	exit( 1 );
}

$manifest = json_decode( (string) file_get_contents( $manifest_path ), true );
if ( ! is_array( $manifest ) || 'nuvanx-publication-manifest' !== (string) ( $manifest['schema'] ?? '' ) || ! is_array( $manifest['routes'] ?? null ) ) {
	fwrite( STDERR, "PUBLICATION_INDEXABLE_RECONCILIATION=FAIL reason=manifest_invalid\n" );
	exit( 1 );
}

$builder_class    = '\\Yoast\\WP\\SEO\\Builders\\Indexable_Builder';
$repository_class = '\\Yoast\\WP\\SEO\\Repositories\\Indexable_Repository';
if ( ! class_exists( $builder_class ) || ! class_exists( $repository_class ) || ! function_exists( 'YoastSEO' ) ) {
	fwrite( STDERR, "PUBLICATION_INDEXABLE_RECONCILIATION=FAIL reason=yoast_indexable_api_unavailable\n" );
	exit( 1 );
}

$container = YoastSEO();
if ( ! is_object( $container ) || ! isset( $container->classes ) || ! is_object( $container->classes ) || ! method_exists( $container->classes, 'get' ) ) {
	fwrite( STDERR, "PUBLICATION_INDEXABLE_RECONCILIATION=FAIL reason=yoast_container_unavailable\n" );
	exit( 1 );
}

try {
	$builder    = $container->classes->get( $builder_class );
	$repository = $container->classes->get( $repository_class );
} catch ( Throwable $error ) {
	fwrite( STDERR, "PUBLICATION_INDEXABLE_RECONCILIATION=FAIL reason=yoast_service_resolution_failed detail=" . $error->getMessage() . "\n" );
	exit( 1 );
}

if ( ! is_object( $builder ) || ! method_exists( $builder, 'build_for_id_and_type' ) || ! is_object( $repository ) || ! method_exists( $repository, 'find_by_id_and_type' ) ) {
	fwrite( STDERR, "PUBLICATION_INDEXABLE_RECONCILIATION=FAIL reason=yoast_service_contract_invalid\n" );
	exit( 1 );
}

$normalize_path = static function ( string $value ): string {
	$path = (string) wp_parse_url( $value, PHP_URL_PATH );
	if ( '' === $path || '/' === $path ) {
		return '/';
	}
	return '/' . trim( $path, '/' ) . '/';
};

$indexable = 0;
$rebuilt   = 0;
$failures  = array();
$seen_ids  = array();

foreach ( $manifest['routes'] as $route => $config ) {
	$route = $normalize_path( (string) $route );
	if ( ! is_array( $config ) || 'publish' !== (string) ( $config['status'] ?? '' ) || ! is_array( $config['robots'] ?? null ) || ! is_bool( $config['robots']['index'] ?? null ) || true !== ( $config['robots']['follow'] ?? null ) ) {
		$failures[] = "invalid_manifest_route:{$route}";
		continue;
	}
	if ( ! $config['robots']['index'] ) {
		continue;
	}

	$post_id = (int) ( $config['post_id'] ?? 0 );
	if ( $post_id <= 0 || isset( $seen_ids[ $post_id ] ) ) {
		$failures[] = "invalid_post_identity:{$route}";
		continue;
	}
	$seen_ids[ $post_id ] = true;

	$post = get_post( $post_id );
	if ( ! ( $post instanceof WP_Post ) || 'publish' !== $post->post_status || (string) ( $config['post_type'] ?? '' ) !== $post->post_type || (string) ( $config['slug'] ?? '' ) !== $post->post_name || $route !== $normalize_path( get_permalink( $post_id ) ) ) {
		$failures[] = "post_identity_mismatch:{$route}";
		continue;
	}

	try {
		$existing  = $repository->find_by_id_and_type( $post_id, 'post', false );
		$rebuilt_i = $builder->build_for_id_and_type( $post_id, 'post', $existing );
		if ( ! is_object( $rebuilt_i ) ) {
			$failures[] = "build_failed:{$route}";
			continue;
		}
		++$rebuilt;
		$actual = $repository->find_by_id_and_type( $post_id, 'post', false );
	} catch ( Throwable $error ) {
		$failures[] = "build_exception:{$route}:" . $error->getMessage();
		continue;
	}

	$actual_permalink   = is_object( $actual ) && isset( $actual->permalink ) ? (string) $actual->permalink : '';
	$actual_path        = '' !== $actual_permalink ? $normalize_path( $actual_permalink ) : '';
	$actual_noindex     = is_object( $actual ) && isset( $actual->is_robots_noindex ) ? $actual->is_robots_noindex : null;
	$actual_public      = is_object( $actual ) && isset( $actual->is_public ) ? $actual->is_public : null;
	$actual_status      = is_object( $actual ) && isset( $actual->post_status ) ? (string) $actual->post_status : '';
	$actual_sub_type    = is_object( $actual ) && isset( $actual->object_sub_type ) ? (string) $actual->object_sub_type : '';

	if ( ! is_object( $actual ) || 'publish' !== $actual_status || (string) $config['post_type'] !== $actual_sub_type || $route !== $actual_path || in_array( $actual_noindex, array( 1, '1', true ), true ) || in_array( $actual_public, array( 0, '0', false ), true ) ) {
		$failures[] = sprintf(
			'indexable_contract_mismatch:%s:status=%s:type=%s:path=%s:noindex=%s:public=%s',
			$route,
			$actual_status ?: 'missing',
			$actual_sub_type ?: 'missing',
			$actual_path ?: 'missing',
			var_export( $actual_noindex, true ),
			var_export( $actual_public, true )
		);
		continue;
	}

	++$indexable;
}

wp_cache_flush();

if ( ! empty( $failures ) ) {
	fwrite( STDERR, "PUBLICATION_INDEXABLE_RECONCILIATION=FAIL indexable={$indexable} rebuilt={$rebuilt} failures=" . implode( '|', $failures ) . "\n" );
	exit( 1 );
}

printf(
	"PUBLICATION_INDEXABLE_RECONCILIATION=PASS routes=%d indexable=%d rebuilt=%d\n",
	count( $manifest['routes'] ),
	$indexable,
	$rebuilt
);
