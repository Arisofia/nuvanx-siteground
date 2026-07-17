<?php
/**
 * Contract test for the front-page canonical hero copy.
 */

declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );

function add_filter( ...$args ): bool {
	return true;
}
function is_admin(): bool {
	return false;
}
function wp_doing_ajax(): bool {
	return false;
}
function is_front_page(): bool {
	return true;
}

require dirname( __DIR__, 2 ) . '/wp-content/themes/nuvanx-medical/inc/nvx-home-copy.php';

$input = '<section class="nvx-home-hero-stage"><div class="nvx-brand-hero__copy"><h1 class="nvx-brand-hero__title"><span>EXPERIENCIA NUVANX:</span><br>Excelencia en Medicina Estética Láser en Madrid</h1><p class="nvx-brand-hero__lead">Resultados naturales, sin cirugía.</p><p class="nvx-brand-hero__description">Texto anterior.</p></div></section>';
$output = nvx_home_copy_transform( $input );

if ( 1 !== preg_match_all( '/<h1\b/iu', $output ) ) {
	fwrite( STDERR, "Canonical hero must contain exactly one H1.\n" );
	exit( 1 );
}
if ( false === strpos( $output, 'Medicina estética láser en Madrid' ) ) {
	fwrite( STDERR, "Canonical home H1 is missing.\n" );
	exit( 1 );
}
if ( false !== strpos( $output, 'EXPERIENCIA NUVANX' ) ) {
	fwrite( STDERR, "Internal brand-first H1 remains in the hero.\n" );
	exit( 1 );
}
if ( false === strpos( $output, 'Diagnóstico médico, tecnología certificada y resultados naturales.' ) ) {
	fwrite( STDERR, "Canonical clinical lead is missing.\n" );
	exit( 1 );
}
if ( false === strpos( $output, 'Protocolos personalizados según valoración médica.' ) ) {
	fwrite( STDERR, "Canonical treatment and location description is missing.\n" );
	exit( 1 );
}
if ( false !== strpos( $output, 'Resultados naturales, sin cirugía.' ) ) {
	fwrite( STDERR, "The global no-surgery promise was not removed.\n" );
	exit( 1 );
}

fwrite( STDOUT, "Canonical home hero tests passed.\n" );
