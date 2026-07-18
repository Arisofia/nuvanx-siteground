<?php
/**
 * Static contract test for validated /contacto/ audit fixes.
 */

declare(strict_types=1);

$root          = dirname( __DIR__, 2 );
$module_path   = $root . '/wp-content/themes/nuvanx-medical/inc/nvx-contacto-audit-fixes.php';
$footer_path   = $root . '/wp-content/themes/nuvanx-medical/footer.php';
$loader_path   = $root . '/wp-content/themes/nuvanx-medical/inc/nvx-integrations.php';
$template_path = $root . '/wp-content/themes/nuvanx-medical/templates/template-contact.php';

foreach ( array( $module_path, $footer_path, $loader_path, $template_path ) as $path ) {
	if ( ! is_readable( $path ) ) {
		fwrite( STDERR, "Missing required file: {$path}\n" );
		exit( 1 );
	}
}

$module   = (string) file_get_contents( $module_path );
$footer   = (string) file_get_contents( $footer_path );
$loader   = (string) file_get_contents( $loader_path );
$template = (string) file_get_contents( $template_path );

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

// Template must not reintroduce duplicate schema / raw OG / wrong privacy / placeholder hours.
$template_required = array(
	"home_url( '/politica-privacidad/' )",
	'lunes a viernes, 12:00–20:00; sábados, 10:00–18:00',
	'lunes a viernes, 11:00–20:00',
	'nvx-btn nvx-btn--primary',
	'nvx-btn nvx-btn--secondary',
	'class="nvx-page nvx-page--contact"',
);
foreach ( $template_required as $fragment ) {
	if ( false === strpos( $template, $fragment ) ) {
		fwrite( STDERR, "Missing contact template fragment: {$fragment}\n" );
		exit( 1 );
	}
}

$template_forbidden = array(
	"home_url( '/politica-de-privacidad/' )" => 'legacy privacy URL in contact template',
	'application/ld+json'                    => 'raw JSON-LD in contact template (use Yoast graph)',
	'nvx_contact_schema_ld'                  => 'duplicate schema head hook in contact template',
	'nvx_contact_og_image'                   => 'raw og:image head hook in contact template',
	'lunes a viernes, 10:00'                 => 'placeholder clinic hours starting 10:00 weekday',
	'<main '                                 => 'nested main landmark in contact template',
);
foreach ( $template_forbidden as $fragment => $reason ) {
	if ( false !== strpos( $template, $fragment ) ) {
		fwrite( STDERR, "Contact template still contains forbidden fragment ({$reason}): {$fragment}\n" );
		exit( 1 );
	}
}

// Historical WP assignment uses Template Name "Contacto" → must delegate to the full template.
if ( false === strpos( $page_template, "templates/template-contact.php" ) ) {
	fwrite( STDERR, "page-contacto.php does not load the canonical template-contact.php.\n" );
	exit( 1 );
}

// Both theme template slugs must be recognized as /contacto/.
if ( false === strpos( $valoracion, "'templates/template-contact.php'" ) ) {
	fwrite( STDERR, "nvx_is_contacto_page_request does not recognize templates/template-contact.php.\n" );
	exit( 1 );
}

// WhatsApp must come from registry-derived phones, not hard-coded E.164 paths.
if ( false !== strpos( $template, 'wa.me/34669319836' ) || false !== strpos( $template, 'wa.me/34647505107' ) ) {
	fwrite( STDERR, "Contact template hard-codes WhatsApp numbers instead of registry phones.\n" );
	exit( 1 );
}
if ( false === strpos( $template, '$chamberi_wa' ) || false === strpos( $template, '$goya_wa' ) ) {
	fwrite( STDERR, "Contact template is missing registry-derived WhatsApp URLs.\n" );
	exit( 1 );
}

fwrite( STDOUT, "Contacto audit-fix contracts passed.\n" );
