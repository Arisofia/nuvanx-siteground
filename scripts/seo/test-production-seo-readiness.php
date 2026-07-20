<?php
/** Standalone regression test for production SEO readiness. */

define( 'ABSPATH', __DIR__ );

$GLOBALS['nvx_test_nonproduction'] = true;
$GLOBALS['nvx_test_filters']       = array();

function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['nvx_test_filters'][] = array( $hook, $callback, $priority, $accepted_args );
}
function is_admin() { return false; }
function is_feed() { return false; }
function is_front_page() { return false; }
function home_url( $path = '/' ) { return 'https://staging2.nuvanx.com' . ( '/' === $path ? '/' : $path ); }
function get_queried_object_id() { return 42; }
function get_permalink( $page_id ) { return 42 === (int) $page_id ? 'https://staging2.nuvanx.com/exion-face/' : ''; }
function trailingslashit( $value ) { return rtrim( (string) $value, '/' ) . '/'; }
function wp_strip_all_tags( $value ) { return strip_tags( (string) $value ); }
function wp_kses_post( $value ) { return (string) $value; }
function nvx_seo_is_nonproduction_environment() { return (bool) $GLOBALS['nvx_test_nonproduction']; }
function nvx_schema_add_type( $types, $type ) {
	$types = is_array( $types ) ? $types : array( $types );
	if ( ! in_array( $type, $types, true ) ) {
		$types[] = $type;
	}
	return array_values( array_filter( $types ) );
}
function nvx_schema_has_type( $types, $type ) { return in_array( $type, is_array( $types ) ? $types : array( $types ), true ); }
function nvx_schema_find_organization( $graph ) { return array( 'index' => 0, 'id' => 'https://staging2.nuvanx.com/#organization' ); }
function nvx_schema_resolve_treatment_key( $page_id ) { return 42 === (int) $page_id ? 'exion_face' : null; }
function nvx_btl_detail_registry() {
	return array(
		'exion-face' => array(
			'faqs' => array(
				array( 'q' => '¿Pregunta uno?', 'a' => 'Respuesta uno.' ),
				array( 'q' => '¿Pregunta dos?', 'a' => 'Respuesta dos.' ),
			),
		),
	);
}

require dirname( __DIR__, 2 ) . '/wp-content/themes/nuvanx-medical/inc/nvx-seo-production-readiness.php';

function nvx_test_assert( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
}

$headers = nvx_seo_nonproduction_x_robots_headers( array() );
nvx_test_assert( isset( $headers['X-Robots-Tag'] ), 'nonproduction must emit X-Robots-Tag' );
nvx_test_assert( false !== strpos( $headers['X-Robots-Tag'], 'noindex' ), 'nonproduction header must contain noindex' );

$GLOBALS['nvx_test_nonproduction'] = false;
$headers = nvx_seo_nonproduction_x_robots_headers( array() );
nvx_test_assert( ! isset( $headers['X-Robots-Tag'] ), 'production must not receive a global X-Robots-Tag override' );

$graph = array(
	array(
		'@type'           => array( 'Organization' ),
		'@id'             => 'https://staging2.nuvanx.com/#organization',
		'subOrganization' => array( array( '@id' => 'legacy' ) ),
	),
	array(
		'@type' => 'MedicalClinic',
		'@id'   => 'https://staging2.nuvanx.com/#chamberi',
		'openingHoursSpecification' => array(
			array( '@type' => 'OpeningHoursSpecification', 'dayOfWeek' => array( 'Monday', 'Tuesday' ), 'opens' => '12:00', 'closes' => '20:00' ),
		),
	),
	array(
		'@type' => 'MedicalClinic',
		'@id'   => 'https://staging2.nuvanx.com/#goya',
		'openingHoursSpecification' => array(
			array( '@type' => 'OpeningHoursSpecification', 'dayOfWeek' => 'Friday', 'opens' => '11:00', 'closes' => '20:00' ),
		),
	),
	array(
		'@type' => 'Service',
		'@id'   => 'https://staging2.nuvanx.com/exion-face/#service',
		'url'   => 'https://staging2.nuvanx.com/exion-face/',
		'name'  => 'EXION Face',
	),
	array(
		'@type' => 'WebPage',
		'@id'   => 'https://staging2.nuvanx.com/exion-face/#webpage',
		'url'   => 'https://staging2.nuvanx.com/exion-face/',
	),
);

$graph = nvx_seo_production_readiness_schema_graph( $graph, null );

$organization = $graph[0];
nvx_test_assert( in_array( 'MedicalOrganization', (array) $organization['@type'], true ), 'organization must include MedicalOrganization' );
nvx_test_assert( isset( $organization['department'] ) && 2 === count( $organization['department'] ), 'organization must reference both clinic departments' );
nvx_test_assert( ! isset( $organization['subOrganization'] ), 'legacy subOrganization relation must be consolidated' );

$clinic = $graph[1];
nvx_test_assert( 'https://staging2.nuvanx.com/#organization' === $clinic['parentOrganization']['@id'], 'clinic must reference parent organization' );
nvx_test_assert( 'https://schema.org/Monday' === $clinic['openingHoursSpecification'][0]['dayOfWeek'][0], 'opening day must use canonical Schema.org URL' );
nvx_test_assert( '€€€' === $clinic['priceRange'], 'clinic must expose priceRange' );

$procedure = $graph[3];
nvx_test_assert( in_array( 'MedicalProcedure', (array) $procedure['@type'], true ), 'EXION detail must be MedicalProcedure + Service' );
nvx_test_assert( 'https://schema.org/NoninvasiveProcedure' === $procedure['procedureType'], 'EXION detail must be noninvasive' );

$webpage = $graph[4];
nvx_test_assert( $procedure['@id'] === $webpage['mainEntity']['@id'], 'WebPage must point to the treatment mainEntity' );

$faq_nodes = array_values( array_filter( $graph, static function ( $piece ) {
	return 'FAQPage' === ( $piece['@type'] ?? '' );
} ) );
nvx_test_assert( 1 === count( $faq_nodes ), 'one FAQPage node must be emitted' );
nvx_test_assert( 2 === count( $faq_nodes[0]['mainEntity'] ), 'FAQPage must mirror the visible BTL registry' );

fwrite( STDOUT, "PASS: production SEO readiness and Schema graph contract\n" );
