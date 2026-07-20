<?php
/**
 * Static contract test for validated /contacto/ audit fixes.
 */

declare(strict_types=1);

$root                = dirname( __DIR__, 2 );
$module_path         = $root . '/wp-content/themes/nuvanx-medical/inc/nvx-contacto-audit-fixes.php';
$footer_path         = $root . '/wp-content/themes/nuvanx-medical/footer.php';
$loader_path         = $root . '/wp-content/themes/nuvanx-medical/inc/nvx-integrations.php';
$template_path       = $root . '/wp-content/themes/nuvanx-medical/templates/template-contact.php';
$page_template_path  = $root . '/wp-content/themes/nuvanx-medical/templates/page-contacto.php';
$valoracion_path     = $root . '/wp-content/themes/nuvanx-medical/inc/nvx-contacto-valoracion-page.php';
$contact_form_path   = $root . '/wp-content/mu-plugins/nuvanx-contacto-hubspot-form.php';
$layout_path         = $root . '/wp-content/themes/nuvanx-medical/assets/css/nvx-site-layout.css';

foreach ( array( $module_path, $footer_path, $loader_path, $template_path, $page_template_path, $valoracion_path, $contact_form_path, $layout_path ) as $path ) {
	if ( ! is_readable( $path ) ) {
		fwrite( STDERR, "Missing required file: {$path}\n" );
		exit( 1 );
	}
}

$module        = (string) file_get_contents( $module_path );
$footer        = (string) file_get_contents( $footer_path );
$loader        = (string) file_get_contents( $loader_path );
$template      = (string) file_get_contents( $template_path );
$page_template = (string) file_get_contents( $page_template_path );
$valoracion    = (string) file_get_contents( $valoracion_path );
$contact_form  = (string) file_get_contents( $contact_form_path );
$layout         = (string) file_get_contents( $layout_path );

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
	'lunes a viernes, 12:00–20:00; sábados, 10:00–18:00',
	'lunes a viernes, 11:00–20:00',
	'nvx-btn nvx-btn--primary',
	'nvx-btn nvx-btn--secondary',
	'class="nvx-page nvx-page--contact"',
	'class="nvx-shell"',
	'El Dr. Rivera atiende en Chamberí los martes y jueves.',
	'El Dr. Rivera atiende en Salamanca–Goya los miércoles.',
	'Cómo llegar a NUVANX Chamberí',
	'Cómo llegar a NUVANX Salamanca–Goya',
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
	'<form'                                  => 'unhandled native contact form',
	'✔'                                      => 'non-system Unicode check mark',
	'about:blank'                            => 'invalid directions or map URL',
	'nvx-container'                          => 'undefined legacy container instead of the canonical shell',
	'style="border:0;"'                      => 'inline iframe border override now duplicating the external visual closure CSS contract',
);
foreach ( $template_forbidden as $fragment => $reason ) {
	if ( false !== strpos( $template, $fragment ) ) {
		fwrite( STDERR, "Contact template still contains forbidden fragment ({$reason}): {$fragment}\n" );
		exit( 1 );
	}
}

if ( 3 !== substr_count( $template, 'class="nvx-shell"' ) ) {
	fwrite( STDERR, "Contact template must use the canonical shell for all three full-width section inners.\n" );
	exit( 1 );
}

// Both clinic map iframes must retain their canonical embed attributes now that the
// inline border override was removed in favor of the external visual closure CSS.
if ( 2 !== substr_count( $template, 'allowfullscreen=""' ) ) {
	fwrite( STDERR, "Contact template must keep the allowfullscreen attribute on both clinic map iframes.\n" );
	exit( 1 );
}
if ( 2 !== substr_count( $template, 'width="100%"' ) || 2 !== substr_count( $template, 'height="260"' ) ) {
	fwrite( STDERR, "Contact template must keep the canonical iframe dimensions on both clinic maps.\n" );
	exit( 1 );
}

$closure_path = $root . '/wp-content/themes/nuvanx-medical/inc/nvx-external-visual-closure.php';
if ( ! is_readable( $closure_path ) ) {
	fwrite( STDERR, "Missing required file: {$closure_path}\n" );
	exit( 1 );
}
$closure = (string) file_get_contents( $closure_path );
if ( false === strpos( $closure, '.nvx-page--contact .nvx-clinic-card__map iframe' ) ) {
	fwrite( STDERR, "The external visual closure must supply the border-removal rule for clinic map iframes.\n" );
	exit( 1 );
}

$layout_required = array(
	'width: var(--nvx-shell);',
	'margin-inline: auto;',
	'padding-inline: var(--nvx-gutter-inner);',
	'.nvx-reading {',
	'width: min(100%, var(--nvx-readable));',
);
foreach ( $layout_required as $fragment ) {
	if ( false === strpos( $layout, $fragment ) ) {
		fwrite( STDERR, "Canonical layout contract is missing: {$fragment}\n" );
		exit( 1 );
	}
}

if ( "<?php\n/**" !== substr( $page_template, 0, 9 ) || false === strpos( $page_template, "require get_template_directory() . '/templates/template-contact.php';" ) ) {
	fwrite( STDERR, "The assigned contacto page template does not delegate to the canonical template.\n" );
	exit( 1 );
}

if ( false !== strpos( $page_template, 'get_template_part(' ) ) {
	fwrite( STDERR, "The assigned contacto page template still renders the generic shell.\n" );
	exit( 1 );
}

$contact_form_required = array(
	'NVX_CONTACTO_HS_FORM_ID',
	'class="hs-form-frame"',
	'data-form-id=',
	'data-portal-id=',
	"home_url( '/politica-privacidad/' )",
	"'https://js-' . \$region . '.hsforms.net/forms/embed/'",
);
foreach ( $contact_form_required as $fragment ) {
	if ( false === strpos( $contact_form, $fragment ) ) {
		fwrite( STDERR, "Missing dedicated contact-form contract: {$fragment}\n" );
		exit( 1 );
	}
}

if ( false !== strpos( $valoracion, 'nvx_content_restructure_contacto_page' ) ) {
	fwrite( STDERR, "A content filter still competes with the canonical contacto template.\n" );
	exit( 1 );
}

if ( false !== strpos( $valoracion, 'directorio NAP' ) ) {
	fwrite( STDERR, "Internal SEO jargon remains in the original contacto copy source.\n" );
	exit( 1 );
}

fwrite( STDOUT, "Contacto audit-fix contracts passed.\n" );
