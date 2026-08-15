<?php
/** Standalone contract test for final Schema.org semantic governance. */

declare(strict_types=1);

define( 'ABSPATH', __DIR__ );

$GLOBALS['nvx_registered_filters'] = array();
$GLOBALS['nvx_registered_actions'] = array();

function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['nvx_registered_filters'][] = array( $hook, $callback, $priority, $accepted_args );
	return true;
}
function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['nvx_registered_actions'][] = array( $hook, $callback, $priority, $accepted_args );
	return true;
}
function remove_action( $hook, $callback, $priority = 10 ) {
	global $wp_filter;
	if ( empty( $wp_filter[ $hook ]->callbacks[ $priority ] ) ) {
		return false;
	}
	foreach ( $wp_filter[ $hook ]->callbacks[ $priority ] as $id => $registered ) {
		if ( ( $registered['function'] ?? null ) === $callback ) {
			unset( $wp_filter[ $hook ]->callbacks[ $priority ][ $id ] );
			return true;
		}
	}
	return false;
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

$runtime_retirement_registered = false;
foreach ( $GLOBALS['nvx_registered_actions'] as $action ) {
	if (
		'wp_loaded' === $action[0]
		&& 'nvx_schema_runtime_retire_legacy_emitters' === $action[1]
		&& PHP_INT_MAX === $action[2]
	) {
		$runtime_retirement_registered = true;
		break;
	}
}
nvx_test_assert( $runtime_retirement_registered, 'legacy emitter retirement must run at wp_loaded/PHP_INT_MAX' );

$graph = array(
	array(
		'@type'         => 'BlogPosting',
		'@id'           => 'https://example.test/post/#article',
		'reviewedBy'    => array( '@id' => 'https://example.test/#physician' ),
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

$jsonld_rule = null;
foreach ( nvx_hygiene_regex_reps() as $rule ) {
	if ( false !== strpos( $rule['pattern'], 'application\\/ld\\+json' ) ) {
		$jsonld_rule = $rule;
		break;
	}
}
nvx_test_assert( is_array( $jsonld_rule ), 'shared content hygiene must define the embedded Schema JSON-LD rule' );

$schema_one = '<script type="application/ld+json">{"@context":"https://schema.org","@type":"MedicalClinic","name":"Legacy"}</script>';
$schema_two = '<script nonce="abc" data-source="legacy" type=\'application/ld+json\'>{"@graph":[{"@type":"BlogPosting"}]}</script>';
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

// Runtime ownership contract. Only the proven legacy NUVANX Schema callbacks
// may be removed. Yoast remains canonical and generic Code Snippets survives.
function nvx_seo_geo_output_jsonld(): void {}
function nvx_seo_geo_output_breadcrumb(): void {}
$legacy_faq_closure = require __DIR__ . '/fixtures/nuvanx-home-unified-faq-schema.php';
$code_snippet_closure = static function (): void {};

$wp_filter = array(
	'wp_head' => (object) array(
		'callbacks' => array(
			6 => array(
				'legacy-jsonld' => array( 'function' => 'nvx_seo_geo_output_jsonld' ),
			),
			7 => array(
				'legacy-breadcrumb' => array( 'function' => 'nvx_seo_geo_output_breadcrumb' ),
			),
			9 => array(
				'legacy-faq' => array( 'function' => $legacy_faq_closure ),
			),
			10 => array(
				'code-snippets' => array( 'function' => $code_snippet_closure ),
			),
		),
	),
);

nvx_schema_runtime_retire_legacy_emitters();

nvx_test_assert( empty( $wp_filter['wp_head']->callbacks[6] ), 'legacy nvx_seo_geo_output_jsonld callback must be removed' );
nvx_test_assert( empty( $wp_filter['wp_head']->callbacks[7] ), 'legacy standalone SEO/GEO breadcrumb Schema callback must be removed' );
nvx_test_assert( empty( $wp_filter['wp_head']->callbacks[9] ), 'legacy standalone home FAQ Schema callback must be removed' );
nvx_test_assert( isset( $wp_filter['wp_head']->callbacks[10]['code-snippets'] ), 'generic Code Snippets callback must survive' );

echo "LEGACY_SCHEMA_EMITTER_RETIREMENT_TEST=PASS\n";
echo "JSONLD_CONTENT_HYGIENE_TEST=PASS\n";
echo "SCHEMA_SEMANTIC_GOVERNANCE_TEST=PASS\n";
