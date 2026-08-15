<?php
/** Standalone contract test for final Schema.org semantic governance. */

declare(strict_types=1);

define( 'ABSPATH', __DIR__ );

$GLOBALS['nvx_registered_filters'] = array();

function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['nvx_registered_filters'][] = array( $hook, $callback, $priority, $accepted_args );
	return true;
}
function is_admin(): bool { return false; }
function is_feed(): bool { return false; }
function wp_json_encode( $value ) { return json_encode( $value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ); }

require dirname( __DIR__, 2 ) . '/wp-content/themes/nuvanx-medical/inc/nvx-schema-semantic-governance.php';

function nvx_test_assert( bool $condition, string $message ): void {
	if ( ! $condition ) {
		fwrite( STDERR, "SCHEMA_SEMANTIC_GOVERNANCE_TEST=FAIL {$message}\n" );
		exit( 1 );
	}
}

$filter_registered = false;
foreach ( $GLOBALS['nvx_registered_filters'] as $filter ) {
	if (
		'wpseo_schema_graph' === $filter[0]
		&& 'nvx_schema_semantic_normalize_graph' === $filter[1]
		&& PHP_INT_MAX - 2 === $filter[2]
	) {
		$filter_registered = true;
		break;
	}
}
nvx_test_assert( $filter_registered, 'final filter must be registered at PHP_INT_MAX - 2' );

$graph = array(
	array(
		'@type'        => 'BlogPosting',
		'@id'          => 'https://example.test/post/#article',
		'reviewedBy'   => array( '@id' => 'https://example.test/#physician' ),
		'procedureType' => 'Procedimiento médico-estético láser',
		'about'         => array(
			'@type'         => 'MedicalProcedure',
			'procedureType' => 'Terapéutico médico-estético',
		),
	),
	array(
		'@type'         => array( 'MedicalProcedure', 'Service' ),
		'@id'           => 'https://example.test/treatment/#medical-procedure',
		'procedureType' => 'https://schema.org/PercutaneousProcedure',
		'performer'     => array( '@id' => 'https://example.test/#physician' ),
	),
	array(
		'@type'            => 'MedicalClinic',
		'@id'              => 'https://example.test/#clinic',
		'medicalSpecialty' => array( 'Medicina estética láser', 'https://schema.org/Geriatric' ),
		'priceRange'       => '€€€',
	),
	array(
		'@type'      => array( 'Organization', 'MedicalOrganization' ),
		'@id'        => 'https://example.test/#organization',
		'priceRange' => '€€€',
	),
	array(
		'@type'     => 'Event',
		'@id'       => 'https://example.test/#event',
		'performer' => array( '@id' => 'https://example.test/#physician' ),
	),
	array(
		'@type'                => 'MedicalProcedure',
		'@id'                  => 'https://example.test/#legacy-procedure',
		'procedureType'        => 'https://schema.org/NoninvasiveProcedure',
		'recognizingAuthority' => array(
			'@type' => 'Organization',
			'name'  => 'SEME',
		),
	),
);

$result = nvx_schema_semantic_normalize_graph( $graph );

nvx_test_assert( ! isset( $result[0]['reviewedBy'] ), 'reviewedBy must be removed from BlogPosting' );
nvx_test_assert( ! isset( $result[0]['procedureType'] ), 'procedureType must be removed from BlogPosting' );
nvx_test_assert( ! isset( $result[0]['about']['procedureType'] ), 'invalid MedicalProcedure procedureType must be removed recursively' );
nvx_test_assert( 'https://schema.org/PercutaneousProcedure' === $result[1]['procedureType'], 'valid procedureType must survive' );
nvx_test_assert( ! isset( $result[1]['performer'] ), 'performer must be removed from non-Event treatment node' );
nvx_test_assert( 'https://schema.org/Geriatric' === $result[2]['medicalSpecialty'], 'valid MedicalSpecialty enum must survive' );
nvx_test_assert( in_array( 'Medicina estética láser', (array) $result[2]['knowsAbout'], true ), 'free-text medical specialty must move to knowsAbout' );
nvx_test_assert( '€€€' === $result[2]['priceRange'], 'MedicalClinic priceRange must survive' );
nvx_test_assert( ! isset( $result[3]['priceRange'] ), 'parent Organization priceRange must be removed' );
nvx_test_assert( isset( $result[4]['performer'] ), 'Event performer must survive' );
nvx_test_assert( ! isset( $result[5]['recognizingAuthority'] ), 'ungoverned SEME recognizingAuthority must be removed' );
nvx_test_assert( 'https://schema.org/NoninvasiveProcedure' === $result[5]['procedureType'], 'valid noninvasive procedureType must survive' );

echo "SCHEMA_SEMANTIC_GOVERNANCE_TEST=PASS\n";
