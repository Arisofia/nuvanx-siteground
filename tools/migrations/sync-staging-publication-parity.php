<?php
/**
 * Transactionally reconcile Staging2 public page/post topology from a verified
 * read-only Production snapshot.
 *
 * Safety properties:
 * - refuses every environment except canonical Staging2;
 * - requires the deployed theme's committed publication manifest;
 * - requires a Production snapshot already proven equal to that manifest;
 * - validates the complete plan before the first write;
 * - uses a database transaction and rolls back on any write/verification error;
 * - only page/post rows, selected post meta and existing category/tag terms are
 *   synchronized; unrelated WordPress data is untouched.
 *
 * @package NVX\Migrations
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "STAGING_PUBLICATION_PARITY=FAIL reason=wordpress_not_loaded\n" );
	exit( 1 );
}

$identity = array(
	'db_name'        => defined( 'DB_NAME' ) ? (string) DB_NAME : '',
	'home'           => (string) get_option( 'home' ),
	'siteurl'        => (string) get_option( 'siteurl' ),
	'blog_public'    => (string) get_option( 'blog_public' ),
	'nvx_env'        => defined( 'NVX_ENV' ) ? (string) NVX_ENV : '',
	'wp_environment' => function_exists( 'wp_get_environment_type' ) ? (string) wp_get_environment_type() : '',
);
if (
	'dbshcocboodiwr' !== $identity['db_name']
	|| 'https://staging2.nuvanx.com' !== untrailingslashit( $identity['home'] )
	|| 'https://staging2.nuvanx.com' !== untrailingslashit( $identity['siteurl'] )
	|| '0' !== $identity['blog_public']
	|| 'staging' !== $identity['nvx_env']
	|| 'staging' !== $identity['wp_environment']
) {
	fwrite( STDERR, "STAGING_PUBLICATION_PARITY=FAIL reason=wrong_environment\n" );
	exit( 1 );
}

$snapshot_file = trim( (string) getenv( 'PUBLICATION_SNAPSHOT_FILE' ) );
if ( '' === $snapshot_file || ! is_readable( $snapshot_file ) ) {
	fwrite( STDERR, "STAGING_PUBLICATION_PARITY=FAIL reason=snapshot_unavailable\n" );
	exit( 1 );
}

$snapshot = json_decode( (string) file_get_contents( $snapshot_file ), true );
$manifest_file = get_template_directory() . '/inc/data/publication-manifest.json';
$manifest = is_readable( $manifest_file )
	? json_decode( (string) file_get_contents( $manifest_file ), true )
	: null;

if (
	! is_array( $snapshot )
	|| 'nuvanx-production-publication-snapshot' !== ( $snapshot['schema'] ?? '' )
	|| 'https://nuvanx.com/' !== trailingslashit( (string) ( $snapshot['source'] ?? '' ) )
	|| ! isset( $snapshot['routes'] )
	|| ! is_array( $snapshot['routes'] )
	|| ! is_array( $manifest )
	|| 'nuvanx-publication-manifest' !== ( $manifest['schema'] ?? '' )
	|| ! isset( $manifest['routes'] )
	|| ! is_array( $manifest['routes'] )
	|| (string) ( $snapshot['manifest_version'] ?? '' ) !== (string) ( $manifest['version'] ?? '' )
) {
	fwrite( STDERR, "STAGING_PUBLICATION_PARITY=FAIL reason=snapshot_or_manifest_invalid\n" );
	exit( 1 );
}

$expected_routes = array_keys( $manifest['routes'] );
$snapshot_routes = array_keys( $snapshot['routes'] );
sort( $expected_routes, SORT_STRING );
sort( $snapshot_routes, SORT_STRING );
if ( $expected_routes !== $snapshot_routes ) {
	fwrite( STDERR, "STAGING_PUBLICATION_PARITY=FAIL reason=snapshot_route_set_mismatch\n" );
	exit( 1 );
}

$managed_meta_keys = array(
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

// Validate every source row and collision before starting the transaction.
foreach ( $manifest['routes'] as $route => $expected ) {
	$source = $snapshot['routes'][ $route ] ?? null;
	if ( ! is_array( $source ) ) {
		fwrite( STDERR, "STAGING_PUBLICATION_PARITY=FAIL reason=source_missing route={$route}\n" );
		exit( 1 );
	}

	if (
		(int) ( $source['ID'] ?? 0 ) !== (int) ( $expected['post_id'] ?? 0 )
		|| (string) ( $source['post_type'] ?? '' ) !== (string) ( $expected['post_type'] ?? '' )
		|| (string) ( $source['post_name'] ?? '' ) !== (string) ( $expected['slug'] ?? '' )
		|| 'publish' !== (string) ( $source['post_status'] ?? '' )
	) {
		fwrite( STDERR, "STAGING_PUBLICATION_PARITY=FAIL reason=source_identity_mismatch route={$route}\n" );
		exit( 1 );
	}

	$source_id   = (int) $source['ID'];
	$source_type = (string) $source['post_type'];
	$existing    = get_post( $source_id );
	if ( $existing instanceof WP_Post && $source_type !== $existing->post_type ) {
		fwrite( STDERR, "STAGING_PUBLICATION_PARITY=FAIL reason=id_collision id={$source_id}\n" );
		exit( 1 );
	}

	$slug_collision = get_page_by_path( (string) $source['post_name'], OBJECT, $source_type );
	if ( $slug_collision instanceof WP_Post && (int) $slug_collision->ID !== $source_id ) {
		fwrite( STDERR, "STAGING_PUBLICATION_PARITY=FAIL reason=slug_collision route={$route} existing_id={$slug_collision->ID}\n" );
		exit( 1 );
	}
}

$route_from_permalink = static function ( string $permalink ): string {
	$home = trailingslashit( home_url( '/' ) );
	if ( 0 !== strpos( $permalink, $home ) ) {
		return '';
	}
	$relative = trim( substr( $permalink, strlen( $home ) ), '/' );
	return '' === $relative ? '/' : '/' . $relative . '/';
};

$target_ids = array_values(
	array_map(
		static fn( array $row ): int => (int) $row['ID'],
		$snapshot['routes']
	)
);
$target_id_lookup = array_fill_keys( $target_ids, true );

$currently_published = get_posts(
	array(
		'post_type'      => array( 'page', 'post' ),
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
	)
);
$surplus_ids = array_values(
	array_filter(
		array_map( 'intval', $currently_published ),
		static fn( int $id ): bool => ! isset( $target_id_lookup[ $id ] )
	)
);

/** Convert a source snapshot row to wp_insert_post/wp_update_post payload. */
$build_post_payload = static function ( array $source ): array {
	return array(
		'post_author'    => (int) ( $source['post_author'] ?? 0 ),
		'post_date'      => (string) ( $source['post_date'] ?? '' ),
		'post_date_gmt'  => (string) ( $source['post_date_gmt'] ?? '' ),
		'post_content'   => wp_slash( (string) ( $source['post_content'] ?? '' ) ),
		'post_title'     => (string) ( $source['post_title'] ?? '' ),
		'post_excerpt'   => (string) ( $source['post_excerpt'] ?? '' ),
		'post_status'    => 'publish',
		'comment_status' => (string) ( $source['comment_status'] ?? 'closed' ),
		'ping_status'    => (string) ( $source['ping_status'] ?? 'closed' ),
		'post_password'  => (string) ( $source['post_password'] ?? '' ),
		'post_name'      => (string) ( $source['post_name'] ?? '' ),
		'post_parent'    => (int) ( $source['post_parent'] ?? 0 ),
		'menu_order'     => (int) ( $source['menu_order'] ?? 0 ),
		'post_type'      => (string) ( $source['post_type'] ?? 'post' ),
	);
};

global $wpdb;
$wpdb->query( 'START TRANSACTION' );
$touched_ids = array();
$created     = 0;
$updated     = 0;
$drafted     = 0;

try {
	foreach ( $snapshot['routes'] as $route => $source ) {
		$source_id = (int) $source['ID'];
		$payload   = $build_post_payload( $source );
		$existing  = get_post( $source_id );

		if ( $existing instanceof WP_Post ) {
			$payload['ID'] = $source_id;
			$result = wp_update_post( $payload, true );
			++$updated;
		} else {
			$payload['import_id'] = $source_id;
			$result = wp_insert_post( $payload, true );
			++$created;
		}

		if ( is_wp_error( $result ) || (int) $result !== $source_id ) {
			$message = is_wp_error( $result ) ? $result->get_error_message() : 'unexpected_insert_id';
			throw new RuntimeException( "post_write_failed route={$route} reason={$message}" );
		}
		$touched_ids[] = $source_id;

		$source_meta = isset( $source['meta'] ) && is_array( $source['meta'] ) ? $source['meta'] : array();
		foreach ( $managed_meta_keys as $meta_key ) {
			delete_post_meta( $source_id, $meta_key );
			if ( ! isset( $source_meta[ $meta_key ] ) || ! is_array( $source_meta[ $meta_key ] ) ) {
				continue;
			}
			foreach ( $source_meta[ $meta_key ] as $meta_value ) {
				add_post_meta( $source_id, $meta_key, maybe_unserialize( $meta_value ) );
			}
		}

		if ( 'post' === (string) $source['post_type'] && isset( $source['terms'] ) && is_array( $source['terms'] ) ) {
			foreach ( array( 'category', 'post_tag' ) as $taxonomy ) {
				$slugs = isset( $source['terms'][ $taxonomy ] ) && is_array( $source['terms'][ $taxonomy ] )
					? array_values( array_filter( array_map( 'sanitize_title', $source['terms'][ $taxonomy ] ) ) )
					: array();
				$term_ids = array();
				foreach ( $slugs as $slug ) {
					$term = get_term_by( 'slug', $slug, $taxonomy );
					if ( $term instanceof WP_Term ) {
						$term_ids[] = (int) $term->term_id;
					}
				}
				wp_set_object_terms( $source_id, $term_ids, $taxonomy, false );
			}
		}
	}

	foreach ( $surplus_ids as $surplus_id ) {
		$result = wp_update_post(
			array(
				'ID'          => $surplus_id,
				'post_status' => 'draft',
			),
			true
		);
		if ( is_wp_error( $result ) ) {
			throw new RuntimeException( 'surplus_draft_failed id=' . $surplus_id . ' reason=' . $result->get_error_message() );
		}
		$touched_ids[] = $surplus_id;
		++$drafted;
	}

	foreach ( array_unique( $touched_ids ) as $touched_id ) {
		clean_post_cache( $touched_id );
	}

	// Verify exact topology and identity before committing the transaction.
	$verify_ids = get_posts(
		array(
			'post_type'      => array( 'page', 'post' ),
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);
	$verified = array();
	foreach ( $verify_ids as $verify_id ) {
		$post = get_post( (int) $verify_id );
		if ( ! ( $post instanceof WP_Post ) ) {
			continue;
		}
		$permalink = get_permalink( $post );
		$route = is_string( $permalink ) ? $route_from_permalink( $permalink ) : '';
		if ( '' !== $route ) {
			$verified[ $route ] = array(
				'post_id'   => (int) $post->ID,
				'post_type' => (string) $post->post_type,
				'slug'      => (string) $post->post_name,
				'status'    => (string) $post->post_status,
			);
		}
	}

	$verified_routes = array_keys( $verified );
	sort( $verified_routes, SORT_STRING );
	if ( $verified_routes !== $expected_routes ) {
		throw new RuntimeException( 'post_write_verification_route_set_mismatch' );
	}
	foreach ( $manifest['routes'] as $route => $expected ) {
		$actual = $verified[ $route ] ?? array();
		foreach ( array( 'post_id', 'post_type', 'slug', 'status' ) as $field ) {
			if ( ( $expected[ $field ] ?? null ) !== ( $actual[ $field ] ?? null ) ) {
				throw new RuntimeException( "post_write_verification_attribute_mismatch route={$route} field={$field}" );
			}
		}
	}

	$wpdb->query( 'COMMIT' );
} catch ( Throwable $error ) {
	$wpdb->query( 'ROLLBACK' );
	foreach ( array_unique( $touched_ids ) as $touched_id ) {
		clean_post_cache( $touched_id );
	}
	fwrite( STDERR, 'STAGING_PUBLICATION_PARITY=FAIL reason=' . preg_replace( '/\s+/', '_', $error->getMessage() ) . "\n" );
	exit( 1 );
}

flush_rewrite_rules( false );
wp_cache_flush();

$report = array(
	'schema'           => 'nuvanx-staging-publication-parity',
	'manifest_version' => (string) $manifest['version'],
	'synced_at'        => gmdate( 'c' ),
	'source'           => (string) $snapshot['source'],
	'target'           => home_url( '/' ),
	'route_count'      => count( $expected_routes ),
	'created'          => $created,
	'updated'          => $updated,
	'drafted_surplus'  => $drafted,
);

echo wp_json_encode( $report, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
fwrite(
	STDERR,
	sprintf(
		"STAGING_PUBLICATION_PARITY=PASS routes=%d created=%d updated=%d drafted=%d\n",
		count( $expected_routes ),
		$created,
		$updated,
		$drafted
	)
);
