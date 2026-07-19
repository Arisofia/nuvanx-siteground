<?php
/** Contract test for canonical facial treatment pages. */

declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );
function add_filter( ...$args ): bool { return true; }
function add_action( ...$args ): bool { return true; }

require dirname( __DIR__, 2 ) . '/wp-content/themes/nuvanx-medical/inc/nvx-aesthetic-treatment-pages.php';

$catalog = nvx_aesthetic_treatment_catalog();
$expected = array(
	'lips_ha'          => 'labios-acido-hialuronico-madrid',
	'rhinomodeling_ha' => 'rinomodelacion-sin-cirugia-madrid',
	'tear_trough_ha'   => 'ojeras-surco-lagrimal-madrid',
	'biostimulators'   => 'bioestimuladores-colageno-madrid',
);

if ( array_keys( $catalog ) !== array_keys( $expected ) ) {
	fwrite( STDERR, "Unexpected facial treatment catalogue keys.\n" );
	exit( 1 );
}

$slugs = array();
$h1s   = array();
foreach ( $expected as $key => $slug ) {
	$entry = $catalog[ $key ] ?? array();
	if ( $slug !== ( $entry['slug'] ?? '' ) ) {
		fwrite( STDERR, "Unexpected slug for {$key}.\n" );
		exit( 1 );
	}
	foreach ( array( 'h1', 'seo_title', 'description', 'lead', 'diagnosis', 'mechanism', 'evolution', 'schema' ) as $field ) {
		if ( empty( $entry[ $field ] ) ) {
			fwrite( STDERR, "Missing {$field} for {$key}.\n" );
			exit( 1 );
		}
	}
	foreach ( array( 'indications', 'precautions', 'process', 'risks', 'combinations', 'faqs' ) as $field ) {
		if ( count( $entry[ $field ] ?? array() ) < 2 ) {
			fwrite( STDERR, "Insufficient {$field} for {$key}.\n" );
			exit( 1 );
		}
	}
	if ( count( $entry['faqs'] ) < 4 ) {
		fwrite( STDERR, "FAQ catalogue is incomplete for {$key}.\n" );
		exit( 1 );
	}
	$slugs[] = $entry['slug'];
	$h1s[]   = $entry['h1'];
}

if ( count( array_unique( $slugs ) ) !== count( $slugs ) || count( array_unique( $h1s ) ) !== count( $h1s ) ) {
	fwrite( STDERR, "Facial treatment slugs or H1 values are duplicated.\n" );
	exit( 1 );
}

$text      = json_encode( $catalog, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
$forbidden = array(
	'pausa obligatoria',
	'síndrome de inmunoglobulina del sarcoma',
	'Radiesse® es ácido hialurónico',
	'Radiesse es ácido hialurónico',
	'elimina la papada',
	'es reversible',
	'garantizado',
	'resultado permanente',
	'el mejor tratamiento',
);
foreach ( $forbidden as $phrase ) {
	if ( false !== stripos( (string) $text, $phrase ) ) {
		fwrite( STDERR, "Forbidden clinical claim remains: {$phrase}.\n" );
		exit( 1 );
	}
}

if ( false === strpos( (string) $text, 'No suspenda anticoagulantes' ) ) {
	fwrite( STDERR, "Anticoagulant safety wording is missing.\n" );
	exit( 1 );
}
if ( false === strpos( (string) $text, 'hidroxiapatita cálcica, no ácido hialurónico' ) ) {
	fwrite( STDERR, "CaHA and HA distinction is missing.\n" );
	exit( 1 );
}
if ( false === strpos( (string) $text, 'síntoma visual' ) ) {
	fwrite( STDERR, "Vascular/visual emergency wording is missing.\n" );
	exit( 1 );
}

$faqs = nvx_aesthetic_treatment_faq_catalog();
foreach ( $catalog as $key => $entry ) {
	if ( $faqs[ $key ] !== $entry['faqs'] ) {
		fwrite( STDERR, "Visible/schema FAQ parity failed for {$key}.\n" );
		exit( 1 );
	}
}

fwrite( STDOUT, "Aesthetic treatment page contracts passed.\n" );
