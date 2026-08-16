<?php
/**
 * Staging-only parity guard for the governed matrix article.
 *
 * The production post payload is exported read-only with WP-CLI by
 * deploy-to-staging2.sh and supplied through PRODUCTION_POST_JSON_FILE. This
 * script runs inside the canonical Staging2 WordPress context and upserts only
 * the exact governed post ID/slug needed by the runtime identity acceptance.
 *
 * @package NUVANX
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$expected_id   = 3334;
$expected_slug = 'matriz-diagnostico-facial-estructura-piel-musculo-grasa';

$identity = [
	'db_name'        => defined( 'DB_NAME' ) ? (string) DB_NAME : '',
	'home'           => (string) get_option( 'home' ),
	'siteurl'        => (string) get_option( 'siteurl' ),
	'nvx_env'        => defined( 'NVX_ENV' ) ? (string) NVX_ENV : '',
	'wp_environment' => function_exists( 'wp_get_environment_type' ) ? (string) wp_get_environment_type() : '',
];

$is_staging2 = 'dbshcocboodiwr' === $identity['db_name']
	&& 'https://staging2.nuvanx.com' === $identity['home']
	&& 'https://staging2.nuvanx.com' === $identity['siteurl']
	&& 'staging' === $identity['nvx_env']
	&& 'staging' === $identity['wp_environment'];

if ( ! $is_staging2 ) {
	fwrite( STDERR, "STAGING_GOVERNED_BLOG_PARITY=FAIL reason=wrong_environment\n" );
	exit( 1 );
}

$payload_file = trim( (string) getenv( 'PRODUCTION_POST_JSON_FILE' ) );
if ( '' === $payload_file || ! is_file( $payload_file ) || ! is_readable( $payload_file ) ) {
	fwrite( STDERR, "STAGING_GOVERNED_BLOG_PARITY=FAIL reason=production_payload_unavailable\n" );
	exit( 1 );
}

$raw = file_get_contents( $payload_file );
if ( ! is_string( $raw ) || '' === trim( $raw ) ) {
	fwrite( STDERR, "STAGING_GOVERNED_BLOG_PARITY=FAIL reason=production_payload_empty\n" );
	exit( 1 );
}

$source = json_decode( $raw, true );
if ( ! is_array( $source ) || JSON_ERROR_NONE !== json_last_error() ) {
	fwrite( STDERR, 'STAGING_GOVERNED_BLOG_PARITY=FAIL reason=production_payload_invalid_json error=' . json_last_error_msg() . "\n" );
	exit( 1 );
}

$source_id     = (int) ( $source['ID'] ?? 0 );
$source_slug   = (string) ( $source['post_name'] ?? '' );
$source_type   = (string) ( $source['post_type'] ?? '' );
$source_status = (string) ( $source['post_status'] ?? '' );
$source_title  = (string) ( $source['post_title'] ?? '' );
$source_body   = (string) ( $source['post_content'] ?? '' );

if (
	$expected_id !== $source_id
	|| $expected_slug !== $source_slug
	|| 'post' !== $source_type
	|| 'publish' !== $source_status
	|| '' === trim( $source_title )
	|| '' === trim( $source_body )
) {
	fwrite(
		STDERR,
		sprintf(
			"STAGING_GOVERNED_BLOG_PARITY=FAIL reason=production_identity_mismatch id=%d slug=%s type=%s status=%s\n",
			$source_id,
			$source_slug,
			$source_type,
			$source_status
		)
	);
	exit( 1 );
}

$existing_id = get_post( $expected_id );
$by_slug     = get_page_by_path( $expected_slug, OBJECT, 'post' );

if ( $existing_id instanceof WP_Post && ( 'post' !== $existing_id->post_type || $expected_slug !== $existing_id->post_name ) ) {
	fwrite(
		STDERR,
		sprintf(
			"STAGING_GOVERNED_BLOG_PARITY=FAIL reason=id_collision id=%d existing_type=%s existing_slug=%s\n",
			$expected_id,
			(string) $existing_id->post_type,
			(string) $existing_id->post_name
		)
	);
	exit( 1 );
}

if ( $by_slug instanceof WP_Post && $expected_id !== (int) $by_slug->ID ) {
	fwrite(
		STDERR,
		sprintf(
			"STAGING_GOVERNED_BLOG_PARITY=FAIL reason=slug_collision expected_id=%d existing_id=%d\n",
			$expected_id,
			(int) $by_slug->ID
		)
	);
	exit( 1 );
}

$postarr = array(
	'post_author'    => max( 1, (int) ( $source['post_author'] ?? 1 ) ),
	'post_date'      => (string) ( $source['post_date'] ?? '' ),
	'post_date_gmt'  => (string) ( $source['post_date_gmt'] ?? '' ),
	'post_content'   => $source_body,
	'post_title'     => $source_title,
	'post_excerpt'   => (string) ( $source['post_excerpt'] ?? '' ),
	'post_status'    => 'publish',
	'post_name'      => $expected_slug,
	'post_type'      => 'post',
	'comment_status' => (string) ( $source['comment_status'] ?? 'closed' ),
	'ping_status'    => (string) ( $source['ping_status'] ?? 'closed' ),
	'post_parent'    => 0,
	'menu_order'     => (int) ( $source['menu_order'] ?? 0 ),
);

$mode = 'update';
if ( $existing_id instanceof WP_Post ) {
	$postarr['ID'] = $expected_id;
	$result        = wp_update_post( wp_slash( $postarr ), true );
} else {
	$mode                 = 'insert';
	$postarr['import_id'] = $expected_id;
	$result               = wp_insert_post( wp_slash( $postarr ), true );
}

if ( is_wp_error( $result ) || $expected_id !== (int) $result ) {
	$message = is_wp_error( $result ) ? $result->get_error_message() : 'unexpected_insert_id';
	fwrite( STDERR, 'STAGING_GOVERNED_BLOG_PARITY=FAIL reason=upsert_failed detail=' . $message . "\n" );
	exit( 1 );
}

// The versioned governed metadata catalogue is authoritative for this article.
delete_post_meta( $expected_id, '_yoast_wpseo_title' );
delete_post_meta( $expected_id, '_yoast_wpseo_metadesc' );
delete_post_meta( $expected_id, '_yoast_wpseo_canonical' );
clean_post_cache( $expected_id );

$verified  = get_post( $expected_id );
$permalink = get_permalink( $expected_id );
$expected_permalink = 'https://staging2.nuvanx.com/' . $expected_slug . '/';

if (
	! ( $verified instanceof WP_Post )
	|| 'publish' !== $verified->post_status
	|| 'post' !== $verified->post_type
	|| $expected_slug !== $verified->post_name
	|| $source_title !== $verified->post_title
	|| $expected_permalink !== $permalink
) {
	fwrite(
		STDERR,
		sprintf(
			"STAGING_GOVERNED_BLOG_PARITY=FAIL reason=post_write_verification id=%d slug=%s status=%s permalink=%s\n",
			$verified instanceof WP_Post ? (int) $verified->ID : 0,
			$verified instanceof WP_Post ? (string) $verified->post_name : '',
			$verified instanceof WP_Post ? (string) $verified->post_status : '',
			is_string( $permalink ) ? $permalink : ''
		)
	);
	exit( 1 );
}

echo sprintf(
	"STAGING_GOVERNED_BLOG_PARITY=PASS id=%d slug=%s mode=%s source=production-readonly\n",
	$expected_id,
	$expected_slug,
	$mode
);
