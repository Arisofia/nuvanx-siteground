<?php
/**
 * Static contract for the /contacto/ map iframe border cleanup.
 *
 * The inline `style="border:0;"` attribute was removed from both clinic map
 * iframes; border removal is now owned by the canonical CSS contract for
 * `.nvx-page--contact .nvx-clinic-card__map iframe` instead of duplicated
 * markup attributes.
 */

declare(strict_types=1);

$root           = dirname( __DIR__, 2 );
$template_path  = $root . '/wp-content/themes/nuvanx-medical/templates/template-contact.php';
$closure_path   = $root . '/wp-content/themes/nuvanx-medical/inc/nvx-external-visual-closure.php';

foreach ( array( $template_path, $closure_path ) as $path ) {
	if ( ! is_readable( $path ) ) {
		fwrite( STDERR, "Missing required file: {$path}\n" );
		exit( 1 );
	}
}

$template = (string) file_get_contents( $template_path );
$closure  = (string) file_get_contents( $closure_path );

// The inline border style must not reappear on either clinic map iframe.
if ( false !== strpos( $template, 'style="border:0;"' ) ) {
	fwrite( STDERR, "Contact template must not reintroduce the inline iframe border style.\n" );
	exit( 1 );
}
if ( false !== stripos( $template, 'style=' ) ) {
	fwrite( STDERR, "Contact template must not use any inline style attribute.\n" );
	exit( 1 );
}

// Both map iframes must still exist with their essential attributes intact.
if ( 2 !== substr_count( $template, '<iframe' ) ) {
	fwrite( STDERR, "Contact template must render exactly two clinic map iframes.\n" );
	exit( 1 );
}

$iframe_required = array(
	'width="100%"',
	'height="260"',
	'allowfullscreen=""',
	'loading="lazy"',
	'referrerpolicy="no-referrer-when-downgrade"',
);
foreach ( $iframe_required as $fragment ) {
	if ( 2 !== substr_count( $template, $fragment ) ) {
		fwrite( STDERR, "Both clinic map iframes must retain the fragment: {$fragment}\n" );
		exit( 1 );
	}
}

// Border removal must now be owned by the canonical CSS contract instead of
// inline markup, keeping presentation out of the template.
if ( false === strpos( $closure, '.nvx-page--contact .nvx-clinic-card__map iframe' ) ) {
	fwrite( STDERR, "The external visual closure must own the clinic map iframe selector.\n" );
	exit( 1 );
}

$iframe_rule_pattern = '/\.nvx-page--contact \.nvx-clinic-card__map iframe\s*\{[^}]*border:\s*0;[^}]*\}/';
if ( 1 !== preg_match( $iframe_rule_pattern, $closure ) ) {
	fwrite( STDERR, "The clinic map iframe CSS rule must set border: 0.\n" );
	exit( 1 );
}

fwrite( STDOUT, "Contacto iframe border cleanup contract passed.\n" );