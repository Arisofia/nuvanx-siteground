<?php
/**
 * Test contract for retired treatment slugs clinical governance redirect.
 */

$file = __DIR__ . '/../../wp-content/themes/nuvanx-medical/inc/nvx-integrations.php';
$content = file_get_contents( $file );

if ( strpos( $content, "'tratamiento-retirado'" ) === false ) {
	echo "FAIL: Retired slugs not populated correctly.\n";
	exit( 1 );
}

echo "PASS: Clinical governance redirect contract\n";
exit( 0 );
