<?php
/**
 * Audit or explicitly apply medical-review provenance to one treatment page.
 *
 * Audit (default):
 *   NVX_REVIEW_PAGE_PATH=/endolift-facial-papada-mandibula/ \
 *   NVX_REVIEWER=rivera NVX_REVIEW_DATE=2026-07-17 \
 *   wp eval-file scripts/staging2/set-medical-review.php
 *
 * Apply only after medical approval:
 *   NVX_MEDICAL_REVIEW_APPLY=YES ... wp eval-file scripts/staging2/set-medical-review.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "WordPress must be loaded.\n" );
	exit( 1 );
}

if ( ! function_exists( 'nvx_medical_reviewers' ) || ! function_exists( 'nvx_schema_resolve_treatment_key' ) ) {
	fwrite( STDERR, "The canonical NUVANX theme and review module must be active.\n" );
	exit( 1 );
}

$path       = trim( (string) getenv( 'NVX_REVIEW_PAGE_PATH' ) );
$reviewer   = strtolower( trim( (string) getenv( 'NVX_REVIEWER' ) ) );
$review_date = trim( (string) getenv( 'NVX_REVIEW_DATE' ) );
$apply      = 'YES' === strtoupper( trim( (string) getenv( 'NVX_MEDICAL_REVIEW_APPLY' ) ) );

if ( '' === $path || '' === $reviewer || '' === $review_date ) {
	fwrite( STDERR, "NVX_REVIEW_PAGE_PATH, NVX_REVIEWER and NVX_REVIEW_DATE are required.\n" );
	exit( 1 );
}

$normalized_path = trim( (string) wp_parse_url( $path, PHP_URL_PATH ), '/' );
$page            = get_page_by_path( $normalized_path, OBJECT, 'page' );
if ( ! $page instanceof WP_Post ) {
	fwrite( STDERR, "Page not found for path: {$path}\n" );
	exit( 1 );
}

$treatment_key = nvx_schema_resolve_treatment_key( (int) $page->ID );
if ( null === $treatment_key ) {
	fwrite( STDERR, "Page is not a registered treatment page: {$path}\n" );
	exit( 1 );
}

$reviewers = nvx_medical_reviewers();
if ( ! isset( $reviewers[ $reviewer ] ) ) {
	fwrite( STDERR, "Unknown reviewer key: {$reviewer}\n" );
	exit( 1 );
}
if ( ! nvx_medical_review_valid_date( $review_date ) ) {
	fwrite( STDERR, "Review date must be a real YYYY-MM-DD date.\n" );
	exit( 1 );
}

$review_time = strtotime( $review_date . ' 23:59:59' );
if ( false === $review_time || $review_time > current_time( 'timestamp', true ) ) {
	fwrite( STDERR, "Review date cannot be in the future.\n" );
	exit( 1 );
}

$current = array(
	'status'   => (string) get_post_meta( $page->ID, '_nvx_medical_review_status', true ),
	'reviewer' => (string) get_post_meta( $page->ID, '_nvx_medical_reviewer', true ),
	'date'     => (string) get_post_meta( $page->ID, '_nvx_medical_review_date', true ),
);
$proposed = array(
	'status'        => 'approved',
	'reviewer'      => $reviewer,
	'reviewer_name' => $reviewers[ $reviewer ]['name'],
	'date'          => $review_date,
);

printf(
	"page_id=%d\npath=%s\ntreatment=%s\nmode=%s\ncurrent=%s\nproposed=%s\n",
	(int) $page->ID,
	get_permalink( $page ),
	$treatment_key,
	$apply ? 'APPLY' : 'AUDIT',
	wp_json_encode( $current, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ),
	wp_json_encode( $proposed, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES )
);

if ( ! $apply ) {
	fwrite( STDOUT, "No changes written. Set NVX_MEDICAL_REVIEW_APPLY=YES only after medical approval.\n" );
	exit( 0 );
}

update_post_meta( $page->ID, '_nvx_medical_review_status', 'approved' );
update_post_meta( $page->ID, '_nvx_medical_reviewer', $reviewer );
update_post_meta( $page->ID, '_nvx_medical_review_date', $review_date );
clean_post_cache( $page->ID );

$verified = array(
	'status'   => (string) get_post_meta( $page->ID, '_nvx_medical_review_status', true ),
	'reviewer' => (string) get_post_meta( $page->ID, '_nvx_medical_reviewer', true ),
	'date'     => (string) get_post_meta( $page->ID, '_nvx_medical_review_date', true ),
);

if ( 'approved' !== $verified['status'] || $reviewer !== $verified['reviewer'] || $review_date !== $verified['date'] ) {
	fwrite( STDERR, "Medical review metadata verification failed after write.\n" );
	exit( 1 );
}

fwrite( STDOUT, "MEDICAL_REVIEW_METADATA_APPLIED\n" );
