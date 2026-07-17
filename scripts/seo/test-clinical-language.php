<?php
/** Contract test for final clinical-language normalization. */

declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );

function add_filter( ...$args ): bool { return true; }
function is_admin(): bool { return false; }
function wp_doing_ajax(): bool { return false; }

require dirname( __DIR__, 2 ) . '/wp-content/themes/nuvanx-medical/inc/nvx-clinical-language.php';

$visible = '<p>Plan de sesiones y downtime realistas.</p><p>Sin downtime significativo.</p>';
$clean   = nvx_clinical_language_content( $visible );

if ( false !== stripos( $clean, 'downtime' ) ) {
	fwrite( STDERR, "English recovery terminology remains visible.\n" );
	exit( 1 );
}
if ( false === strpos( $clean, 'período de recuperación' ) ) {
	fwrite( STDERR, "Canonical recovery terminology was not inserted.\n" );
	exit( 1 );
}

$graph = array(
	array(
		'@type'       => 'MedicalProcedure',
		'description' => 'Downtime típico 4–7 días.',
		'followup'    => 'Reincorporación habitual en menos de 24 h; edema o inflamación pueden durar 3–7 días.',
	),
	array(
		'@type'       => 'Service',
		'description' => 'Alternativa de radiofrecuencia a sistemas con microagujas cuando la valoración lo indica.',
		'nested'      => array(
			'copy' => 'Regeneración endógena facial con RF monopolar + ultrasonido a microtemperaturas controladas. Alternativa a HIFU de alto pico cuando el diagnóstico lo indica.',
		),
	),
);

$result = nvx_clinical_language_schema_graph( $graph );
$json   = json_encode( $result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );

foreach ( array( 'downtime', 'Reincorporación habitual en menos de 24 h', 'Alternativa de radiofrecuencia', 'Alternativa a HIFU', 'microtemperaturas controladas' ) as $forbidden ) {
	if ( false !== stripos( (string) $json, $forbidden ) ) {
		fwrite( STDERR, "Non-canonical schema terminology remains: {$forbidden}.\n" );
		exit( 1 );
	}
}
foreach ( array( 'período de recuperación típico 4–7 días', 'La reincorporación depende de la zona y del protocolo', 'Cada aplicador se selecciona según el diagnóstico', 'Aplicador no invasivo de radiofrecuencia y ultrasonido' ) as $required ) {
	if ( false === strpos( (string) $json, $required ) ) {
		fwrite( STDERR, "Canonical schema terminology is missing: {$required}.\n" );
		exit( 1 );
	}
}

if ( $result !== nvx_clinical_language_schema_graph( $result ) ) {
	fwrite( STDERR, "Clinical-language normalization is not idempotent.\n" );
	exit( 1 );
}

fwrite( STDOUT, "Clinical language normalization tests passed.\n" );
