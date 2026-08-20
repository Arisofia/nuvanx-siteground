<?php
/**
 * Reconcile Yoast robots meta with the versioned publication manifest.
 *
 * This migration owns only the two legacy Yoast robots post-meta keys. It
 * never changes content, post status, permalinks, canonicals or any property
 * outside the declared manifest route.
 *
 * Run through WP-CLI after the theme has been deployed.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "PUBLICATION_ROBOTS_RECONCILIATION=FAIL reason=wordpress_not_bootstrapped\n" );
	exit( 1 );
}

$theme_dir     = get_template_directory();
$manifest_path = $theme_dir . '/inc/data/publication-manifest.json';
$dry_run       = '1' === getenv( 'MIGRATION_DRY_RUN' );

if ( ! is_file( $manifest_path ) || ! is_readable( $manifest_path ) ) {
	fwrite( STDERR, "PUBLICATION_ROBOTS_RECONCILIATION=FAIL reason=manifest_unreadable\n" );
	exit( 1 );
}

$decoded = json_decode( (string) file_get_contents( $manifest_path ), true );
if ( ! is_array( $decoded ) || 'nuvanx-publication-manifest' !== (string) ( $decoded['schema'] ?? '' ) || ! is_array( $decoded['routes'] ?? null ) ) {
	fwrite( STDERR, "PUBLICATION_ROBOTS_RECONCILIATION=FAIL reason=manifest_invalid\n" );
	exit( 1 );
}

$normalize_path = static function ( string $value ): string {
	$path = (string) wp_parse_url( $value, PHP_URL_PATH );
	if ( '' === $path ) {
		return '/';
	}
	return '/' . trim( $path, '/' ) . '/';
};

$write_marker = static function (): bool {
	$marker = getenv( 'MIGRATION_WRITE_MARKER' );
	if ( ! is_string( $marker ) || '' === $marker || file_exists( $marker ) ) {
		return true;
	}
	if ( ! touch( $marker ) ) {
		fwrite( STDERR, "PUBLICATION_ROBOTS_RECONCILIATION=FAIL reason=write_marker_failed\n" );
		return false;
	}
	echo "PUBLICATION_ROBOTS_WRITE_MARKER=CREATED\n";
	return true;
};

$seen_ids   = array();
$changed    = 0;
$checked    = 0;
$indexable  = 0;
$noindex    = 0;
$meta_keys  = array( '_yoast_wpseo_meta-robots-noindex', '_yoast_wpseo_meta-robots-nofollow' );

foreach ( $decoded['routes'] as $route => $config ) {
	$route = $normalize_path( (string) $route );
	if ( ! is_array( $config ) || 'publish' !== (string) ( $config['status'] ?? '' ) || ! is_array( $config['robots'] ?? null ) ) {
		fwrite( STDERR, "PUBLICATION_ROBOTS_RECONCILIATION=FAIL reason=invalid_route_config route={$route}\n" );
		exit( 1 );
	}

	$post_id = (int) ( $config['post_id'] ?? 0 );
	if ( $post_id <= 0 || isset( $seen_ids[ $post_id ] ) ) {
		fwrite( STDERR, "PUBLICATION_ROBOTS_RECONCILIATION=FAIL reason=invalid_or_duplicate_post_id route={$route}\n" );
		exit( 1 );
	}
	$seen_ids[ $post_id ] = true;

	$expected_index  = $config['robots']['index'] ?? null;
	$expected_follow = $config['robots']['follow'] ?? null;
	if ( ! is_bool( $expected_index ) || true !== $expected_follow ) {
		fwrite( STDERR, "PUBLICATION_ROBOTS_RECONCILIATION=FAIL reason=unsupported_robots_policy route={$route}\n" );
		exit( 1 );
	}

	$post = get_post( $post_id );
	if ( ! ( $post instanceof WP_Post ) || 'publish' !== $post->post_status || (string) ( $config['post_type'] ?? '' ) !== $post->post_type || (string) ( $config['slug'] ?? '' ) !== $post->post_name || $route !== $normalize_path( get_permalink( $post_id ) ) ) {
		fwrite( STDERR, "PUBLICATION_ROBOTS_RECONCILIATION=FAIL reason=post_identity_mismatch route={$route}\n" );
		exit( 1 );
	}

	++$checked;
	if ( $expected_index ) {
		++$indexable;
	} else {
		++$noindex;
	}

	$current_noindex  = (string) get_post_meta( $post_id, $meta_keys[0], true );
	$current_nofollow = (string) get_post_meta( $post_id, $meta_keys[1], true );
	$needs_change     = $expected_index
		? ( '' !== $current_noindex || '' !== $current_nofollow )
		: ( '1' !== $current_noindex || '' !== $current_nofollow );

	if ( ! $needs_change ) {
		continue;
	}

	if ( ! $dry_run && ! $write_marker() ) {
		exit( 1 );
	}

	if ( ! $dry_run ) {
		if ( $expected_index ) {
			delete_post_meta( $post_id, $meta_keys[0] );
		} else {
			update_post_meta( $post_id, $meta_keys[0], '1' );
		}
		delete_post_meta( $post_id, $meta_keys[1] );
	}

	++$changed;
	echo sprintf(
		"PUBLICATION_ROBOTS_ROUTE=%s policy=%s changed=%s\n",
		$route,
		$expected_index ? 'index,follow' : 'noindex,follow',
		$dry_run ? 'would_write' : 'yes'
	);
}

if ( class_exists( 'WPSEO_Utils' ) && method_exists( 'WPSEO_Utils', 'clear_cache' ) ) {
	WPSEO_Utils::clear_cache();
}
wp_cache_flush();

printf(
	"PUBLICATION_ROBOTS_RECONCILIATION=PASS routes=%d indexable=%d noindex=%d changed=%d mode=%s\n",
	$checked,
	$indexable,
	$noindex,
	$changed,
	$dry_run ? 'dry_run' : 'live'
);
