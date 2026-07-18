<?php
/**
 * Canonical contract for the front-page hero copy.
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

$required = array(
	'Medicina estética láser en Madrid' => 'Canonical home H1 is missing.',
	'Equipo médico hospitalario. Tecnología certificada. Resultados naturales.' => 'Canonical hospital-team lead is missing.',
	'Valoración 15–30 min. Endolift®, CO₂ y EXION® BTL en Chamberí (CS20144) y Salamanca–Goya (CS20073). Diagnóstico primero.' => 'Canonical valuation, treatment and clinic description is missing.',
);

if ( 1 !== preg_match_all( '/<h1\b/iu', $output ) ) {
	fwrite( STDERR, "Canonical hero must contain exactly one H1.\n" );
	exit( 1 );
}
foreach ( $required as $needle => $message ) {
	if ( false === strpos( $output, $needle ) ) {
		fwrite( STDERR, $message . "\n" );
		exit( 1 );
	}
}
if ( false !== strpos( $output, 'EXPERIENCIA NUVANX' ) ) {
	fwrite( STDERR, "Internal brand-first H1 remains in the hero.\n" );
	exit( 1 );
}
if ( false !== strpos( $output, 'Resultados naturales, sin cirugía.' ) ) {
	fwrite( STDERR, "The global no-surgery promise was not removed.\n" );
	exit( 1 );
}

fwrite( STDOUT, "Canonical home hero tests passed.\n" );
