<?php
/**
 * Static contract test for validated /contacto/ audit fixes.
 */

declare(strict_types=1);

$root        = dirname( __DIR__, 2 );
$module_path = $root . '/wp-content/themes/nuvanx-medical/inc/nvx-contacto-audit-fixes.php';
$footer_path = $root . '/wp-content/themes/nuvanx-medical/footer.php';
$loader_path = $root . '/wp-content/themes/nuvanx-medical/inc/nvx-integrations.php';

foreach ( array( $module_path, $footer_path, $loader_path ) as $path ) {
	if ( ! is_readable( $path ) ) {
		fwrite( STDERR, "Missing required file: {$path}\n" );
		exit( 1 );
	}
}

$module = (string) file_get_contents( $module_path );
$footer = (string) file_get_contents( $footer_path );
$loader = (string) file_get_contents( $loader_path );

$required_module_fragments = array(
	"add_filter( 'wpseo_opengraph_image', 'nvx_contacto_audit_social_image', 100 )",
	"add_filter( 'wpseo_twitter_image', 'nvx_contacto_audit_social_image', 100 )",
	'/wp-content/uploads/2026/07/consulta-medica-personalizada-nuvanx-madrid.webp',
	"add_filter( 'wpseo_schema_graph', 'nvx_contacto_audit_schema_graph', 30, 2 )",
	"array( 'chamberi', 'goya' )",
	"'parentOrganization'",
	'Horario de clínica:</strong> Lunes a viernes, 12:00–20:00; sábados, 10:00–18:00',
	'Horario de clínica:</strong> Lunes a viernes, 11:00–20:00',
	'Clínicas NUVANX en Madrid — Chamberí y Salamanca–Goya',
	'Datos de contacto y sedes autorizadas',
);

foreach ( $required_module_fragments as $fragment ) {
	if ( false === strpos( $module, $fragment ) ) {
		fwrite( STDERR, "Missing contacto contract fragment: {$fragment}\n" );
		exit( 1 );
	}
}

if ( false === strpos( $loader, "require_once __DIR__ . '/nvx-contacto-audit-fixes.php';" ) ) {
	fwrite( STDERR, "Contacto audit module is not loaded.\n" );
	exit( 1 );
}

if ( false === strpos( $footer, "home_url( '/politica-privacidad/' )" ) ) {
	fwrite( STDERR, "Footer does not link directly to the canonical privacy page.\n" );
	exit( 1 );
}

if ( false !== strpos( $footer, "home_url( '/politica-de-privacidad/' )" ) ) {
	fwrite( STDERR, "Footer still links through the legacy privacy redirect.\n" );
	exit( 1 );
}

fwrite( STDOUT, "Contacto audit-fix contracts passed.\n" );
