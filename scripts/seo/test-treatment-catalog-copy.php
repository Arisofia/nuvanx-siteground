<?php
/** Contract test for the canonical treatment catalogue copy. */

declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );

function home_url( string $path = '/' ): string { return 'https://nuvanx.com' . $path; }
function add_filter( ...$args ): bool { return true; }

require dirname( __DIR__, 2 ) . '/wp-content/themes/nuvanx-medical/inc/nvx-treatments-catalog.php';

$items = array();
foreach ( nvx_treatments_catalog_data() as $category ) {
	foreach ( $category['items'] as $item ) {
		$items[ $item['title'] ] = $item;
	}
}

$required = array(
	'EXION® BTL (hub)' => 'Cada modalidad tiene mecanismo, profundidad, recuperación y objetivos distintos',
	'EXION® Face' => 'Los parámetros y el número de sesiones se definen según diagnóstico y tolerancia.',
	'EXION® Body' => 'No sustituye procedimientos de reducción de grasa ni trata obesidad.',
	'EXION® Fractional RF' => 'Profundidad, anestesia, cuidados y período de recuperación dependen del protocolo.',
	'EMFUSION®' => 'No sustituye procedimientos médicos de energía ni tratamientos inyectables.',
	'BTL EXILITE™ IPL' => 'tras diagnóstico, fototipo y ajuste de parámetros.',
);

foreach ( $required as $title => $fragment ) {
	if ( empty( $items[ $title ]['body'] ) || false === strpos( $items[ $title ]['body'], $fragment ) ) {
		fwrite( STDERR, "Canonical treatment copy missing for {$title}.\n" );
		exit( 1 );
	}
}

$catalog_text = implode( "\n", array_column( $items, 'body' ) );
$forbidden    = array(
	'downtime',
	'Alternativa a HIFU',
	'microtemperaturas controladas',
	'EXION® Body elimina grasa',
	'sin período de recuperación',
);
foreach ( $forbidden as $text ) {
	if ( false !== stripos( $catalog_text, $text ) ) {
		fwrite( STDERR, "Prohibited treatment catalogue wording remains: {$text}.\n" );
		exit( 1 );
	}
}

if ( count( $items ) < 12 ) {
	fwrite( STDERR, "Treatment catalogue unexpectedly lost items.\n" );
	exit( 1 );
}

fwrite( STDOUT, "Treatment catalogue copy tests passed.\n" );
