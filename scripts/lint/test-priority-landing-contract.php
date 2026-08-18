<?php
/**
 * Contract: priority Ads landings expose H1, authority, tariff and recovery.
 *
 * @package nuvanx-medical
 */

declare(strict_types=1);

$root = dirname( __DIR__, 2 );
$fail = static function ( string $message ): void {
	fwrite( STDERR, 'PRIORITY_LANDING_CONTRACT=FAIL ' . $message . PHP_EOL );
	exit( 1 );
};

$endolift_php = (string) file_get_contents( $root . '/wp-content/themes/nuvanx-medical/inc/nvx-endolift-page.php' );
$endolift_json = (string) file_get_contents( $root . '/wp-content/themes/nuvanx-medical/inc/data/endolift-page.json' );
$helpers       = (string) file_get_contents( $root . '/wp-content/themes/nuvanx-medical/inc/nvx-page-render-helpers.php' );
$sede          = (string) file_get_contents( $root . '/wp-content/themes/nuvanx-medical/templates/page-sede.php' );
$neuro         = (string) file_get_contents( $root . '/wp-content/themes/nuvanx-medical/inc/data/aesthetic-treatment-pages.json' );

if ( ! str_contains( $endolift_php, 'endolift-facial' ) || str_contains( $endolift_php, "strpos( \$content, 'nvx-endolift-editorial' )" ) ) {
	$fail( 'endolift detector must claim the path and ignore CMS HTML comments' );
}

if ( ! str_contains( $endolift_json, 'papada y mandíbula sin cirugía' ) ) {
	$fail( 'endolift H1 must state Madrid + papada/mandible + without surgery' );
}

if ( ! str_contains( $helpers, 'function nvx_clinical_authority_byline_markup' )
	|| ! str_contains( $helpers, 'function nvx_recovery_table_markup' )
	|| ! str_contains( $helpers, 'function nvx_candidacy_markup' )
	|| ! str_contains( $helpers, 'function nvx_tariff_price_label' ) ) {
	$fail( 'shared competitive helpers are missing' );
}

if ( ! str_contains( $sede, 'Medicina estética en Chamberí, Madrid' )
	|| ! str_contains( $sede, 'nvx_chamberi_landing_photos' ) ) {
	$fail( 'chamberi landing must have local-intent H1 and photo gallery' );
}

if ( ! str_contains( $neuro, 'arrugas de expresión del tercio superior' ) ) {
	$fail( 'neuromodulators H1 must include Madrid indication' );
}

$photos = array(
	'chamberi-interior.jpg',
	'chamberi-sala.jpg',
	'chamberi-equipo.jpg',
);
foreach ( $photos as $photo ) {
	$path = $root . '/wp-content/themes/nuvanx-medical/assets/images/clinics/' . $photo;
	if ( ! is_readable( $path ) || filesize( $path ) < 10000 ) {
		$fail( 'missing clinic photo ' . $photo );
	}
}

echo 'PRIORITY_LANDING_CONTRACT=PASS' . PHP_EOL;
