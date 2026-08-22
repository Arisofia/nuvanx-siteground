<?php
declare(strict_types=1);

const PHYSICIAN_ID = 'https://example.test/#physician';

/** Standalone contract test for final Schema.org semantic governance. */

define( 'ABSPATH', __DIR__ );

$GLOBALS['nvx_registered_filters'] = array();

function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['nvx_registered_filters'][] = array( $hook, $callback, $priority, $accepted_args );
	return true;
}
function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	return true;
}
function is_admin(): bool { return false; }
function is_feed(): bool { return false; }
function wp_json_encode( $value ) { return json_encode( $value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ); }

$repo_root = dirname( __DIR__, 2 );
require $repo_root . '/wp-content/themes/nuvanx-medical/inc/nvx-schema-semantic-governance.php';
require $repo_root . '/lib/nvx-content-hygiene-rules.php';

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
		'@type'         => 'BlogPosting',
		'@id'           => 'https://example.test/post/#article',
		'reviewedBy'    => array( '@id' => PHYSICIAN_ID ),
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
		'performer'     => array( '@id' => PHYSICIAN_ID ),
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
		'performer' => array( '@id' => PHYSICIAN_ID ),
	),
	array(
		'@type'                => 'MedicalProcedure',
		'@id'                  => 'https://example.test/#procedure',
		'procedureType'        => 'https://schema.org/NoninvasiveProcedure',
		'recognizingAuthority' => array(
			'@type' => 'Organization',
			'name'  => 'SEME',
		),
	),
	array(
		'@type'                => 'Service',
		'@id'                  => 'https://example.test/#service',
		'recognizingAuthority' => array(
			'@type' => 'Organization',
			'name'  => 'Ministerio de Sanidad',
		),
	),
	array(
		'@type'                => 'MedicalProcedure',
		'@id'                  => 'https://example.test/#procedure-aemps',
		'recognizingAuthority' => array(
			'@type' => 'Organization',
			'name'  => 'AEMPS',
		),
	),
	array(
		'@type'                => 'BlogPosting',
		'@id'                  => 'https://example.test/#article-authority',
		'recognizingAuthority' => array(
			'@type' => 'Organization',
			'name'  => 'Any organization outside governed treatment nodes',
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
nvx_test_assert( ! isset( $result[5]['recognizingAuthority'] ), 'SEME recognizingAuthority must be removed from MedicalProcedure' );
nvx_test_assert( 'https://schema.org/NoninvasiveProcedure' === $result[5]['procedureType'], 'valid noninvasive procedureType must survive' );
nvx_test_assert( ! isset( $result[6]['recognizingAuthority'] ), 'any recognizingAuthority value must be removed from Service' );
nvx_test_assert( ! isset( $result[7]['recognizingAuthority'] ), 'AEMPS recognizingAuthority must be removed from MedicalProcedure' );
nvx_test_assert( isset( $result[8]['recognizingAuthority'] ), 'recognizingAuthority outside governed MedicalProcedure and Service nodes must remain untouched' );

$jsonld_rule = null;
foreach ( nvx_hygiene_regex_reps() as $rule ) {
	if ( false !== strpos( $rule['pattern'], 'application\\/ld\\+json' ) ) {
		$jsonld_rule = $rule;
		break;
	}
}
nvx_test_assert( is_array( $jsonld_rule ), 'shared content hygiene must define the embedded Schema JSON-LD rule' );

$schema_one = '<script type="application/ld+json">{"@context":"https://schema.org","@type":"MedicalClinic","name":"Schema fixture"}</script>';
$schema_two = '<script nonce="abc" data-source="schema-fixture" type=\'application/ld+json\'>{"@graph":[{"@type":"BlogPosting"}]}</script>';
$non_schema = '<script type="application/ld+json">{"configuration":{"feature":"keep-me"}}</script>';
$mixed      = '<p>before</p>' . $non_schema . '<p>middle</p>' . $schema_one . '<p>after</p>' . $schema_two;
$pcre       = '/' . $jsonld_rule['pattern'] . '/' . $jsonld_rule['flags'];
$cleaned    = preg_replace( $pcre, $jsonld_rule['replacement'], $mixed );

nvx_test_assert( is_string( $cleaned ), 'embedded JSON-LD hygiene PCRE must compile' );
nvx_test_assert( false !== strpos( $cleaned, 'keep-me' ), 'non-Schema application/ld+json must survive' );
nvx_test_assert( false === strpos( $cleaned, 'MedicalClinic' ), 'Schema.org block must be removed' );
nvx_test_assert( false === strpos( $cleaned, 'BlogPosting' ), '@graph/@type Schema block must be removed' );
nvx_test_assert( false !== strpos( $cleaned, '<p>before</p><script' ), 'surrounding content must remain intact' );
nvx_test_assert( false !== strpos( $cleaned, '<p>after</p>' ), 'content after Schema blocks must remain intact' );

$only_non_schema = preg_replace( $pcre, $jsonld_rule['replacement'], $non_schema );
nvx_test_assert( $non_schema === $only_non_schema, 'non-Schema JSON-LD must be byte-preserved when no Schema block exists' );

echo "JSONLD_CONTENT_HYGIENE_TEST=PASS\n";
echo "SCHEMA_RECOGNIZING_AUTHORITY_ANY_VALUE=FAIL_EXPECTED\n";
echo "SCHEMA_SEMANTIC_GOVERNANCE_TEST=PASS\n";
