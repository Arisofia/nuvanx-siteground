<?php
/**
 * Public HTML must not emit DEKA/BTL/EXION/Eufoton/Endolift logos or packshots.
 *
 * @package nuvanx-medical
 */

declare(strict_types=1);

$root = dirname( __DIR__, 2 );
$fail = static function ( string $message ): void {
	fwrite( STDERR, 'EDITORIAL_VENDOR_IMAGES=FAIL ' . $message . PHP_EOL );
	exit( 1 );
};

$gbp = (string) file_get_contents( $root . '/wp-content/themes/nuvanx-medical/inc/nvx-gbp-local.php' );
foreach ( array( 'nvx_public_html_is_vendor_image', 'nvx_public_strip_vendor_images', 'nvx_public_vendor_image_url_regex' ) as $symbol ) {
	if ( ! str_contains( $gbp, $symbol ) ) {
		$fail( 'missing ' . $symbol );
	}
}

$partners = (string) file_get_contents( $root . '/wp-content/themes/nuvanx-medical/inc/nvx-treatments-catalog.php' );
if ( ! preg_match( '/function nvx_treatments_partner_assets\(\): array \{([\s\S]+?)\n\}/', $partners, $match ) ) {
	$fail( 'partner asset catalog missing' );
}
foreach ( array( "'DEKA'", "'BTL'", "'Endolift'", "'EXION'", "'Eufoton'" ) as $label ) {
	if ( str_contains( $match[1], $label ) ) {
		$fail( 'tratamientos logo cloud still lists ' . $label );
	}
}

$blog = (string) file_get_contents( $root . '/wp-content/themes/nuvanx-medical/inc/nvx-blog-system.php' );
foreach ( array( 'Endolift-ISO9001-Laser', 'endolift-lasemar-1500-eufoton', 'BTL-Exion-Mobile-Version', 'SmartLipo-for-Laserlipolysis-DEKA', 'Protocolo-Endolift-Thermage', '04-endolift.jpg' ) as $stem ) {
	if ( str_contains( $blog, $stem ) ) {
		$fail( 'blog catalog still references ' . $stem );
	}
}
if ( ! str_contains( $blog, "'alt'  => 'Sala clínica de NUVANX Salamanca–Goya'" ) ) {
	$fail( 'blog named images must carry a descriptive alt' );
}

$authentic = (string) file_get_contents( $root . '/wp-content/themes/nuvanx-medical/inc/nvx-authentic-page-photography.php' );
foreach ( array( "'id' => 2113", "'id' => 2432", "'id' => 2470" ) as $banned ) {
	if ( str_contains( $authentic, $banned ) ) {
		$fail( 'authentic registry still maps ' . $banned );
	}
}

$bridal = (string) file_get_contents( $root . '/wp-content/themes/nuvanx-medical/inc/nvx-bridal-page.php' );
if ( str_contains( $bridal, 'Protocolo-Endolift-Thermage-Morpheus8-ultherapy' ) ) {
	$fail( 'bridal studio still emits the protocol collage filename' );
}

echo 'EDITORIAL_VENDOR_IMAGES=PASS' . PHP_EOL;
