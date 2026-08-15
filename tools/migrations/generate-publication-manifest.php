<?php
/**
 * Validate the live WordPress public topology against the versioned manifest.
 *
 * The committed manifest is the expected truth. This command never derives the
 * expected route/status/slug/type from the runtime it is validating.
 *
 * Usage:
 *   wp eval-file tools/migrations/generate-publication-manifest.php --allow-root
 *
 * @package NVX\Migrations
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "ERROR: must run inside WordPress via wp eval-file.\n" );
	exit( 1 );
}

$manifest_file = get_template_directory() . '/inc/data/publication-manifest.json';
if ( ! is_readable( $manifest_file ) ) {
	fwrite( STDERR, "PUBLICATION_MANIFEST=FAIL reason=manifest_unavailable\n" );
	exit( 1 );
}

$manifest = json_decode( (string) file_get_contents( $manifest_file ), true );
if (
	! is_array( $manifest )
	|| 'nuvanx-publication-manifest' !== ( $manifest['schema'] ?? '' )
	|| ! isset( $manifest['routes'] )
	|| ! is_array( $manifest['routes'] )
) {
	fwrite( STDERR, "PUBLICATION_MANIFEST=FAIL reason=manifest_invalid\n" );
	exit( 1 );
}

/** Convert a permalink into a canonical site-relative route. */
$route_from_permalink = static function ( string $permalink ): string {
	$home = trailingslashit( home_url( '/' ) );
	if ( 0 !== strpos( $permalink, $home ) ) {
		return '';
	}
	$relative = trim( substr( $permalink, strlen( $home ) ), '/' );
	return '' === $relative ? '/' : '/' . $relative . '/';
};

/** Canonical expected URL for a manifest route in the environment under test. */
$expected_canonical = static function ( string $route ): string {
	return '/' === $route ? trailingslashit( home_url( '/' ) ) : home_url( $route );
};

$actual = array();
$ids    = get_posts(
	array(
		'post_type'              => array( 'page', 'post' ),
		'post_status'            => 'publish',
		'posts_per_page'         => -1,
		'fields'                 => 'ids',
		'orderby'                => 'ID',
		'order'                  => 'ASC',
		'no_found_rows'          => true,
		'update_post_meta_cache' => true,
		'update_post_term_cache' => false,
	)
);

foreach ( $ids as $post_id ) {
	$post = get_post( (int) $post_id );
	if ( ! ( $post instanceof WP_Post ) ) {
		continue;
	}

	$permalink = get_permalink( $post );
	if ( ! is_string( $permalink ) || '' === $permalink ) {
		continue;
	}

	$route = $route_from_permalink( $permalink );
	if ( '' === $route ) {
		continue;
	}

	$actual[ $route ] = array(
		'post_id'   => (int) $post->ID,
		'post_type' => (string) $post->post_type,
		'slug'      => (string) $post->post_name,
		'status'    => (string) $post->post_status,
		'canonical' => (string) $permalink,
		'renderer'  => 'page' === $post->post_type ? (string) get_page_template_slug( $post->ID ) : 'single-post.php',
	);
}

$expected_routes = array_keys( $manifest['routes'] );
$actual_routes   = array_keys( $actual );
sort( $expected_routes, SORT_STRING );
sort( $actual_routes, SORT_STRING );

$missing    = array_values( array_diff( $expected_routes, $actual_routes ) );
$surplus    = array_values( array_diff( $actual_routes, $expected_routes ) );
$mismatches = array();
$errors     = array();

foreach ( $missing as $route ) {
	$errors[] = 'Missing expected public URL: ' . $route;
}
foreach ( $surplus as $route ) {
	$errors[] = 'Surplus public URL not present in canonical manifest: ' . $route;
}

foreach ( $manifest['routes'] as $route => $expected ) {
	if ( ! isset( $actual[ $route ] ) || ! is_array( $expected ) ) {
		continue;
	}

	$route_changes = array();
	foreach ( array( 'post_id', 'post_type', 'slug', 'status' ) as $field ) {
		$expected_value = $expected[ $field ] ?? null;
		$actual_value   = $actual[ $route ][ $field ] ?? null;
		if ( $expected_value !== $actual_value ) {
			$route_changes[] = sprintf(
				'%s: expected=%s actual=%s',
				$field,
				wp_json_encode( $expected_value ),
				wp_json_encode( $actual_value )
			);
		}
	}

	$canonical = $expected['canonical'] ?? $expected_canonical( (string) $route );
	if ( untrailingslashit( (string) $canonical ) !== untrailingslashit( (string) $actual[ $route ]['canonical'] ) ) {
		$route_changes[] = sprintf(
			'canonical: expected=%s actual=%s',
			(string) $canonical,
			(string) $actual[ $route ]['canonical']
		);
	}

	if ( ! empty( $route_changes ) ) {
		$mismatches[] = array(
			'route'   => (string) $route,
			'changes' => $route_changes,
		);
		$errors[] = 'Attribute mismatch for ' . $route . ': ' . implode( '; ', $route_changes );
	}
}

$manifest['validation'] = array(
	'pass'          => empty( $errors ),
	'errors_count'  => count( $errors ),
	'errors'        => $errors,
	'missing'       => $missing,
	'surplus'       => $surplus,
	'changed'       => $mismatches,
	'expected_count'=> count( $expected_routes ),
	'actual_count'  => count( $actual_routes ),
	'checked_at'    => gmdate( 'c' ),
	'checked_host'  => wp_parse_url( home_url( '/' ), PHP_URL_HOST ),
	'actual'        => $actual,
);

echo wp_json_encode( $manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

if ( ! empty( $errors ) ) {
	fwrite(
		STDERR,
		sprintf(
			"PUBLICATION_MANIFEST=FAIL expected=%d actual=%d missing=%d surplus=%d changed=%d\n",
			count( $expected_routes ),
			count( $actual_routes ),
			count( $missing ),
			count( $surplus ),
			count( $mismatches )
		)
	);
	exit( 1 );
}

fwrite( STDERR, sprintf( "PUBLICATION_MANIFEST=PASS routes=%d\n", count( $expected_routes ) ) );
