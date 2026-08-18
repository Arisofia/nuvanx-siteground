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

$exilite_json = (string) file_get_contents( $root . '/wp-content/themes/nuvanx-medical/inc/data/btl-detail-pages.json' );
$exilite_php  = (string) file_get_contents( $root . '/wp-content/themes/nuvanx-medical/inc/nvx-btl-detail-pages.php' );
$blog_meta    = (string) file_get_contents( $root . '/wp-content/themes/nuvanx-medical/inc/data/seo-blog-post-metadata.json' );
$blog_php     = (string) file_get_contents( $root . '/wp-content/themes/nuvanx-medical/inc/nvx-blog-system.php' );
$blog_runtime = (string) file_get_contents( $root . '/wp-content/themes/nuvanx-medical/inc/nvx-governed-blog-runtime.php' );

if ( ! str_contains( $exilite_json, 'manchas y rojeces' )
	|| ! str_contains( $exilite_php, 'nvx_btl_detail_reservation_markup' )
	|| ! str_contains( $exilite_php, 'nvx_btl_detail_hydrate_tariffs' ) ) {
	$fail( 'EXILITE transactional page must expose candidacy/reservation/tariff hydration' );
}

if ( ! str_contains( $blog_meta, '"canonical_path": "/btl-exilite-ipl-madrid/"' )
	|| ! str_contains( $blog_php, 'tratamiento IPL médico en Madrid' )
	|| ! str_contains( $blog_runtime, 'function nvx_governed_blog_html_canonical_url' ) ) {
	$fail( 'IPL Journal article must canonical to EXILITE and use the exact transactional anchor' );
}

$governance = (string) file_get_contents( $root . '/wp-content/themes/nuvanx-medical/inc/nvx-native-style-governance.php' );
$aesthetic  = (string) file_get_contents( $root . '/wp-content/themes/nuvanx-medical/inc/nvx-aesthetic-medicine-page.php' );
$signature  = (string) file_get_contents( $root . '/wp-content/themes/nuvanx-medical/inc/nvx-signature-phase-pages.php' );
$solutions  = (string) file_get_contents( $root . '/wp-content/themes/nuvanx-medical/template-parts/content/nvx-soluciones-medicas.php' );
$valoracion = (string) file_get_contents( $root . '/wp-content/themes/nuvanx-medical/templates/page-landing-valoracion.php' );
$shell      = (string) file_get_contents( $root . '/wp-content/themes/nuvanx-medical/template-parts/content/nvx-page-shell.php' );

if ( ! str_contains( $endolift_php, 'must not block the theme renderer' ) ) {
	$fail( 'endolift detector must ignore CMS editorial comments' );
}

if ( str_contains( $shell, '$is_exion_btl' ) ) {
	$fail( 'page shell must not force a prose wrapper on EXION BTL' );
}

if ( ! str_contains( $aesthetic, "nvx_schema_path_matches( \$path, '/medicina-estetica/' )" )
	|| str_contains( $aesthetic, "entry-content nvx-page__content nvx-prose" ) ) {
	$fail( 'aesthetic hub must be path-owned and emit no outer nvx-prose' );
}

if ( ! str_contains( $governance, 'nvx-aes-section' ) ) {
	$fail( 'prose normalizer must accept aes-section component pages' );
}

if ( ! str_contains( $signature, "return 'nvx_signature_phase_pages'" )
	|| str_contains( $signature, 'nvx-page__content nvx-prose' ) ) {
	$fail( 'signature pages must declare owner and drop the prose wrapper' );
}

if ( str_contains( $solutions, 'nvx-page__content nvx-prose' )
	|| str_contains( $valoracion, 'nvx-page__content nvx-prose' ) ) {
	$fail( 'soluciones and valoracion templates must not emit the conflicting wrapper' );
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
echo 'EXILITE_CANNIBALIZATION_CONTRACT=PASS' . PHP_EOL;
