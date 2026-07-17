<?php
/**
 * Static contract test for canonical SEO metadata.
 */

declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );

function add_filter( ...$args ): bool {
	return true;
}

require dirname( __DIR__, 2 ) . '/wp-content/themes/nuvanx-medical/inc/nvx-seo-metadata.php';

$catalog = nvx_seo_metadata_catalog();
$required = array( 'home', 'endolift', 'endolaser', 'co2', 'equipo', 'valoracion' );

foreach ( $required as $key ) {
	if ( empty( $catalog[ $key ]['title'] ) || empty( $catalog[ $key ]['description'] ) ) {
		fwrite( STDERR, "Missing metadata for {$key}.\n" );
		exit( 1 );
	}
}

foreach ( $catalog as $key => $metadata ) {
	$title_length = function_exists( 'mb_strlen' ) ? mb_strlen( $metadata['title'], 'UTF-8' ) : strlen( $metadata['title'] );
	$desc_length  = function_exists( 'mb_strlen' ) ? mb_strlen( $metadata['description'], 'UTF-8' ) : strlen( $metadata['description'] );

	if ( $title_length > 60 ) {
		fwrite( STDERR, "Title {$key} exceeds 60 characters: {$title_length}.\n" );
		exit( 1 );
	}
	if ( $desc_length > 160 ) {
		fwrite( STDERR, "Description {$key} exceeds 160 characters: {$desc_length}.\n" );
		exit( 1 );
	}
	if ( false !== stripos( $metadata['title'] . $metadata['description'], 'staging' ) ) {
		fwrite( STDERR, "Metadata {$key} contains a staging reference.\n" );
		exit( 1 );
	}
}

if ( false !== stripos( $catalog['home']['title'], 'Clínica de' ) ) {
	fwrite( STDERR, "Home title does not begin with the primary search intent.\n" );
	exit( 1 );
}

fwrite( STDOUT, "SEO metadata catalogue tests passed.\n" );
