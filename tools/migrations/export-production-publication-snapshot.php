<?php
/**
 * Export the canonical public page/post snapshot from Production (read-only).
 *
 * The candidate manifest is supplied explicitly from the immutable release.
 * The exporter aborts unless Production's current public route set and core
 * post identity exactly match that manifest. It performs no writes.
 *
 * @package NVX\Migrations
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "PRODUCTION_PUBLICATION_EXPORT=FAIL reason=wordpress_not_loaded\n" );
	exit( 1 );
}

$identity = array(
	'db_name' => defined( 'DB_NAME' ) ? (string) DB_NAME : '',
	'home'    => (string) get_option( 'home' ),
	'siteurl' => (string) get_option( 'siteurl' ),
);
if (
	'db0ecrycwv2tgb' !== $identity['db_name']
	|| 'https://nuvanx.com' !== untrailingslashit( $identity['home'] )
	|| 'https://nuvanx.com' !== untrailingslashit( $identity['siteurl'] )
) {
	fwrite( STDERR, "PRODUCTION_PUBLICATION_EXPORT=FAIL reason=wrong_environment\n" );
	exit( 1 );
}

$manifest_file = trim( (string) getenv( 'PUBLICATION_MANIFEST_FILE' ) );
if ( '' === $manifest_file || ! is_readable( $manifest_file ) ) {
	fwrite( STDERR, "PRODUCTION_PUBLICATION_EXPORT=FAIL reason=manifest_unavailable\n" );
	exit( 1 );
}

$manifest = json_decode( (string) file_get_contents( $manifest_file ), true );
if (
	! is_array( $manifest )
	|| 'nuvanx-publication-manifest' !== ( $manifest['schema'] ?? '' )
	|| ! isset( $manifest['routes'] )
	|| ! is_array( $manifest['routes'] )
) {
	fwrite( STDERR, "PRODUCTION_PUBLICATION_EXPORT=FAIL reason=manifest_invalid\n" );
	exit( 1 );
}

$route_from_permalink = static function ( string $permalink ): string {
	$home = trailingslashit( home_url( '/' ) );
	if ( 0 !== strpos( $permalink, $home ) ) {
		return '';
	}
	$relative = trim( substr( $permalink, strlen( $home ) ), '/' );
	return '' === $relative ? '/' : '/' . $relative . '/';
};

$meta_keys = array(
	'_wp_page_template',
	'_thumbnail_id',
	'_nvx_aesthetic_treatment_key',
	'_nvx_medical_review_status',
	'_yoast_wpseo_title',
	'_yoast_wpseo_metadesc',
	'_yoast_wpseo_canonical',
	'_yoast_wpseo_meta-robots-noindex',
	'_yoast_wpseo_meta-robots-nofollow',
);

$ids = get_posts(
	array(
		'post_type'              => array( 'page', 'post' ),
		'post_status'            => 'publish',
		'posts_per_page'         => -1,
		'fields'                 => 'ids',
		'orderby'                => 'ID',
		'order'                  => 'ASC',
		'no_found_rows'          => true,
		'update_post_meta_cache' => true,
		'update_post_term_cache' => true,
	)
);

$actual = array();
foreach ( $ids as $post_id ) {
	$post = get_post( (int) $post_id );
	if ( ! ( $post instanceof WP_Post ) ) {
		continue;
	}
	$permalink = get_permalink( $post );
	$route     = is_string( $permalink ) ? $route_from_permalink( $permalink ) : '';
	if ( '' === $route ) {
		continue;
	}

	$meta = array();
	foreach ( $meta_keys as $key ) {
		$values = get_post_meta( $post->ID, $key, false );
		if ( ! empty( $values ) ) {
			$meta[ $key ] = array_values( $values );
		}
	}

	$terms = array();
	foreach ( array( 'category', 'post_tag' ) as $taxonomy ) {
		$resolved = get_the_terms( $post->ID, $taxonomy );
		if ( is_array( $resolved ) ) {
			$terms[ $taxonomy ] = array_values(
				array_map(
					static fn( WP_Term $term ): string => (string) $term->slug,
					$resolved
				)
			);
		}
	}

	$actual[ $route ] = array(
		'ID'             => (int) $post->ID,
		'post_author'    => (int) $post->post_author,
		'post_date'      => (string) $post->post_date,
		'post_date_gmt'  => (string) $post->post_date_gmt,
		'post_content'   => (string) $post->post_content,
		'post_title'     => (string) $post->post_title,
		'post_excerpt'   => (string) $post->post_excerpt,
		'post_status'    => (string) $post->post_status,
		'comment_status' => (string) $post->comment_status,
		'ping_status'    => (string) $post->ping_status,
		'post_password'  => (string) $post->post_password,
		'post_name'      => (string) $post->post_name,
		'post_parent'    => (int) $post->post_parent,
		'menu_order'     => (int) $post->menu_order,
		'post_type'      => (string) $post->post_type,
		'permalink'      => (string) $permalink,
		'meta'           => $meta,
		'terms'          => $terms,
	);
}

$expected_routes = array_keys( $manifest['routes'] );
$actual_routes   = array_keys( $actual );
sort( $expected_routes, SORT_STRING );
sort( $actual_routes, SORT_STRING );
$missing = array_values( array_diff( $expected_routes, $actual_routes ) );
$surplus = array_values( array_diff( $actual_routes, $expected_routes ) );
$changed = array();

foreach ( $manifest['routes'] as $route => $expected ) {
	if ( ! isset( $actual[ $route ] ) || ! is_array( $expected ) ) {
		continue;
	}
	$source = $actual[ $route ];
	foreach ( array( 'post_id' => 'ID', 'post_type' => 'post_type', 'slug' => 'post_name', 'status' => 'post_status' ) as $expected_key => $source_key ) {
		if ( ( $expected[ $expected_key ] ?? null ) !== ( $source[ $source_key ] ?? null ) ) {
			$changed[] = array(
				'route'    => $route,
				'field'    => $expected_key,
				'expected' => $expected[ $expected_key ] ?? null,
				'actual'   => $source[ $source_key ] ?? null,
			);
		}
	}
}

if ( ! empty( $missing ) || ! empty( $surplus ) || ! empty( $changed ) ) {
	fwrite(
		STDERR,
		sprintf(
			"PRODUCTION_PUBLICATION_EXPORT=FAIL reason=manifest_drift missing=%d surplus=%d changed=%d\n",
			count( $missing ),
			count( $surplus ),
			count( $changed )
		)
	);
	exit( 1 );
}

$snapshot = array(
	'schema'           => 'nuvanx-production-publication-snapshot',
	'manifest_version' => (string) $manifest['version'],
	'exported_at'      => gmdate( 'c' ),
	'source'           => home_url( '/' ),
	'route_count'      => count( $actual ),
	'routes'           => $actual,
);

echo wp_json_encode( $snapshot, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
fwrite( STDERR, sprintf( "PRODUCTION_PUBLICATION_EXPORT=PASS routes=%d\n", count( $actual ) ) );
