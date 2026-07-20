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
function get_queried_object_id() { return $GLOBALS['nvx_test_page_id'] ?? 42; }
function get_permalink( $page_id ) {
	if ( 42 === (int) $page_id ) return 'https://staging2.nuvanx.com/exion-face/';
	if ( 43 === (int) $page_id ) return 'https://staging2.nuvanx.com/emfusion/';
	if ( 44 === (int) $page_id ) return 'https://staging2.nuvanx.com/exilite/';
	return '';
}
/**
 * Ensures a value has exactly one trailing slash.
 *
 * @param mixed $value The value to normalize.
 * @return string The value with trailing slashes removed and one slash appended.
 */
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
/**
 * Resolves the treatment registry key for a page.
 *
 * @param mixed $page_id Page identifier to resolve.
 * @return string|null The treatment key, or null when the page is not recognized.
 */
function nvx_schema_resolve_treatment_key( $page_id ) {
	if ( 42 === (int) $page_id ) return 'exion_face';
	if ( 43 === (int) $page_id ) return 'emfusion';
	if ( 44 === (int) $page_id ) return 'exilite_btl';
	return null;
}
/**
 * Provides treatment detail data used by the BTL schema tests.
 *
 * @return array Treatment details keyed by treatment slug, including FAQ entries.
 */
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

foreach ( array(
	'_nvx_seo_schema_enrich_organization',
	'_nvx_seo_schema_enrich_clinics',
	'_nvx_seo_schema_promote_services',
	'_nvx_seo_schema_link_main_entity',
) as $nvx_test_helper ) {
	nvx_test_assert( function_exists( $nvx_test_helper ), "graph normalization helper {$nvx_test_helper} must be defined" );
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

$GLOBALS['nvx_test_page_id'] = 43;
$graph_emfusion = array(
	array( '@type' => 'Service', '@id' => 'https://staging2.nuvanx.com/emfusion/#service', 'url' => 'https://staging2.nuvanx.com/emfusion/', 'name' => 'EMFUSION' ),
	array( '@type' => 'WebPage', '@id' => 'https://staging2.nuvanx.com/emfusion/#webpage', 'url' => 'https://staging2.nuvanx.com/emfusion/' ),
);
$graph_emfusion = nvx_seo_production_readiness_schema_graph( $graph_emfusion, null );
nvx_test_assert( in_array( 'MedicalProcedure', (array) $graph_emfusion[0]['@type'], true ), 'EMFUSION detail must be MedicalProcedure + Service' );
nvx_test_assert( $graph_emfusion[0]['@id'] === $graph_emfusion[1]['mainEntity']['@id'], 'EMFUSION WebPage must point to mainEntity' );

$GLOBALS['nvx_test_page_id'] = 44;
$graph_exilite = array(
	array( '@type' => 'Service', '@id' => 'https://staging2.nuvanx.com/exilite/#service', 'url' => 'https://staging2.nuvanx.com/exilite/', 'name' => 'EXILITE' ),
	array( '@type' => 'WebPage', '@id' => 'https://staging2.nuvanx.com/exilite/#webpage', 'url' => 'https://staging2.nuvanx.com/exilite/' ),
);
$graph_exilite = nvx_seo_production_readiness_schema_graph( $graph_exilite, null );
nvx_test_assert( in_array( 'MedicalProcedure', (array) $graph_exilite[0]['@type'], true ), 'EXILITE detail must be MedicalProcedure + Service' );
nvx_test_assert( $graph_exilite[0]['@id'] === $graph_exilite[1]['mainEntity']['@id'], 'EXILITE WebPage must point to mainEntity' );

fwrite( STDOUT, "PASS: production SEO readiness and Schema graph contract\n" );
