<?php
/** Contract test for medical review provenance. */

declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );
define( 'NVX_DIRECTOR_COLEGIADO', '282864786' );

$GLOBALS['nvx_test_meta'] = array();

function add_filter( ...$args ): bool { return true; }
function add_action( ...$args ): bool { return true; }
function home_url( string $path = '/' ): string { return 'https://nuvanx.com' . $path; }
function get_queried_object_id(): int { return 1241; }
function get_post_meta( int $post_id, string $key, bool $single = true ) { return $GLOBALS['nvx_test_meta'][ $key ] ?? ''; }
function nvx_schema_resolve_treatment_key( int $post_id ): ?string { return 1241 === $post_id ? 'endolift_facial' : null; }
function wp_date( string $format, int $timestamp ): string { return date( $format, $timestamp ); }
function is_admin(): bool { return false; }
function wp_doing_ajax(): bool { return false; }
function is_singular( string $type = '' ): bool { return 'page' === $type; }
function esc_html__( string $text ): string { return htmlspecialchars( $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' ); }
function esc_html( string $text ): string { return htmlspecialchars( $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' ); }
function esc_url( string $text ): string { return $text; }
function esc_attr( string $text ): string { return htmlspecialchars( $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' ); }

require dirname( __DIR__, 2 ) . '/wp-content/themes/nuvanx-medical/inc/nvx-medical-review.php';

if ( null !== nvx_medical_review_record( 1241 ) ) {
	fwrite( STDERR, "A page without approval metadata must not expose a reviewer.\n" );
	exit( 1 );
}

$GLOBALS['nvx_test_meta'] = array(
	'_nvx_medical_review_status' => 'approved',
	'_nvx_medical_reviewer'      => 'rivera',
	'_nvx_medical_review_date'   => '2026-02-31',
);
if ( null !== nvx_medical_review_record( 1241 ) ) {
	fwrite( STDERR, "An invalid review date must reject the approval record.\n" );
	exit( 1 );
}

$GLOBALS['nvx_test_meta']['_nvx_medical_review_date'] = '2026-07-17';
$record = nvx_medical_review_record( 1241 );
if ( null === $record ) {
	fwrite( STDERR, "A complete approval record was not accepted.\n" );
	exit( 1 );
}
if ( 'Dr. José Javier Rivera Tejeda' !== $record['name'] || '282864786' !== $record['license'] ) {
	fwrite( STDERR, "Reviewer identity differs from the canonical physician record.\n" );
	exit( 1 );
}

$markup = nvx_medical_review_markup( $record );
foreach ( array( 'data-nvx-medical-review="approved"', 'Contenido revisado médicamente por', 'physician-rivera-tejeda', 'datetime="2026-07-17"' ) as $needle ) {
	if ( false === strpos( $markup, $needle ) ) {
		fwrite( STDERR, "Visible medical-review markup is missing: {$needle}.\n" );
		exit( 1 );
	}
}

$content = '<article>Contenido clínico.</article>';
$output  = nvx_medical_review_append( $content );
if ( 1 !== substr_count( $output, 'data-nvx-medical-review="approved"' ) ) {
	fwrite( STDERR, "Approved content must contain exactly one reviewer disclosure.\n" );
	exit( 1 );
}
if ( $output !== nvx_medical_review_append( $output ) ) {
	fwrite( STDERR, "Medical-review disclosure is not idempotent.\n" );
	exit( 1 );
}

$graph = array(
	array( '@type' => array( 'WebPage', 'FAQPage' ), '@id' => 'https://nuvanx.com/endolift/#webpage' ),
	array( '@type' => 'MedicalProcedure', '@id' => 'https://nuvanx.com/endolift/#procedure' ),
);
$result = nvx_medical_review_schema_graph( $graph );
if ( empty( $result[0]['reviewedBy']['@id'] ) || false === strpos( $result[0]['reviewedBy']['@id'], 'physician-rivera-tejeda' ) ) {
	fwrite( STDERR, "WebPage schema does not reference the visible reviewer.\n" );
	exit( 1 );
}
if ( isset( $result[1]['reviewedBy'] ) ) {
	fwrite( STDERR, "reviewedBy must be attached to WebPage, not the treatment entity.\n" );
	exit( 1 );
}

$GLOBALS['nvx_test_meta'] = array();
$unchanged = nvx_medical_review_schema_graph( $graph );
if ( $unchanged !== $graph ) {
	fwrite( STDERR, "Schema must remain unchanged without visible approval metadata.\n" );
	exit( 1 );
}

fwrite( STDOUT, "Medical review provenance tests passed.\n" );
