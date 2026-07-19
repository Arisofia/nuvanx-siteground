<?php
/**
 * Static contract for strategy-led authority, investment and review pages.
 */

declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );

function add_action( ...$args ): bool { return true; }
function add_filter( ...$args ): bool { return true; }
function esc_html( string $text ): string { return htmlspecialchars( $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' ); }
function nvx_endolift_price_from_eur(): float { return 798.60; }
function nvx_endolift_price_papada_eur(): float { return 1064.80; }
function nvx_format_price_eur( float $amount ): string { return number_format( $amount, 2, ',', '.' ); }

$strategy_file = dirname( __DIR__, 2 ) . '/wp-content/themes/nuvanx-medical/inc/nvx-strategy-pages.php';
require $strategy_file;

$catalog = nvx_strategy_page_catalog();
$expected = array(
	'why_nuvanx'    => array( 'por-que-nuvanx', 'approved_for_publication' ),
	'investment'    => array( 'inversion-medicina-estetica', 'approved_for_publication' ),
	'liposculpt_air' => array( 'liposculpt-air', 'pending_medical_legal' ),
	'v_lift_awake'  => array( 'v-lift-awake', 'pending_medical_legal' ),
);

foreach ( $expected as $key => $expectation ) {
	if ( empty( $catalog[ $key ] ) || $catalog[ $key ]['slug'] !== $expectation[0] || $catalog[ $key ]['review_status'] !== $expectation[1] ) {
		fwrite( STDERR, "Strategy page catalogue mismatch for {$key}.\n" );
		exit( 1 );
	}
}

$rows = nvx_strategy_verified_investment_rows();
if ( 2 !== count( $rows ) || '798,60 €' !== $rows[0]['price'] || '1.064,80 €' !== $rows[1]['price'] ) {
	fwrite( STDERR, "Investment page must use only the two approved Endolift tariffs.\n" );
	exit( 1 );
}

$prototype = nvx_strategy_protocol_review_markup( 'liposculpt_air' );
if ( false === strpos( $prototype, 'Protocolo en evaluación.' ) || false === strpos( $prototype, 'no constituye una técnica ofrecida' ) ) {
	fwrite( STDERR, "Working-name protocol page does not remain clearly in review.\n" );
	exit( 1 );
}

$source = file_get_contents( $strategy_file );
if ( false === $source ) {
	fwrite( STDERR, "Cannot read strategy page source.\n" );
	exit( 1 );
}

$forbidden = array(
	'sin dolor',
	'sin downtime',
	'3–7 días',
	'4–7 días',
	'CoolSculpting',
	'Morpheus',
	'HIFU',
	'Thermage',
);
foreach ( $forbidden as $term ) {
	if ( false !== stripos( $source, $term ) ) {
		fwrite( STDERR, "Strategy page source contains prohibited claim or comparison: {$term}.\n" );
		exit( 1 );
	}
}

$hygiene_file = dirname( __DIR__, 2 ) . '/wp-content/themes/nuvanx-medical/inc/nvx-page-hygiene.php';
$hygiene = file_get_contents( $hygiene_file );
if ( false === $hygiene || false === strpos( $hygiene, 'nvx_strategy_pending_page_ids' ) ) {
	fwrite( STDERR, "Pending protocol pages are not protected by the noindex policy.\n" );
	exit( 1 );
}

fwrite( STDOUT, "Strategy page contracts passed.\n" );
