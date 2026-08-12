<?php
/**
 * Lightweight contract test for governed REST/headless SEO metadata.
 *
 * Runs without booting WordPress by stubbing only the functions needed by the
 * aesthetic-treatment branch. This protects the catalog allowlist → resolver
 * contract that keeps seo_title/description/canonical available to Yoast.
 */

define( 'ABSPATH', __DIR__ . '/' );

function add_filter() { return true; }
function add_action() { return true; }
function get_post_type( $post_id ) { return 4242 === (int) $post_id ? 'page' : ''; }
function get_post_field( $field, $post_id ) {
	if ( 4242 !== (int) $post_id ) {
		return '';
	}
	return 'post_name' === $field ? 'rinomodelacion-sin-cirugia-madrid' : '';
}
function get_permalink( $post_id ) {
	return 4242 === (int) $post_id ? 'https://nuvanx.com/rinomodelacion-sin-cirugia-madrid/' : false;
}
function nvx_aesthetic_treatment_catalog() {
	return array(
		'rhinoplasty' => array(
			'slug'        => 'rinomodelacion-sin-cirugia-madrid',
			'seo_title'   => 'Rinomodelación Sin Cirugía Madrid | NUVANX',
			'description' => 'Rinomodelación médica sin cirugía en Madrid con ácido hialurónico, valoración individual y criterio anatómico para un resultado natural.',
		),
	);
}

require_once dirname( __DIR__, 2 ) . '/wp-content/themes/nuvanx-medical/inc/nvx-seo-metadata.php';

$actual = nvx_seo_governed_metadata_for_post_id( 4242 );
$expected = array(
	'title'       => 'Rinomodelación Sin Cirugía Madrid | NUVANX',
	'description' => 'Rinomodelación médica sin cirugía en Madrid con ácido hialurónico, valoración individual y criterio anatómico para un resultado natural.',
	'canonical'   => 'https://nuvanx.com/rinomodelacion-sin-cirugia-madrid/',
);

if ( $actual !== $expected ) {
	fwrite( STDERR, 'SEO_GOVERNED_METADATA_TEST=FAIL' . PHP_EOL );
	fwrite( STDERR, var_export( $actual, true ) . PHP_EOL );
	exit( 1 );
}

echo 'SEO_GOVERNED_METADATA_TEST=PASS' . PHP_EOL;
