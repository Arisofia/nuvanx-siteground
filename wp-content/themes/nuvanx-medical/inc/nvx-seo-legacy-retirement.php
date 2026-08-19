<?php
/**
 * Retire page-local SEO title/description owners after all theme modules load.
 *
 * Canonical text metadata is owned by nvx-seo-metadata.php + the versioned
 * seo-metadata.json catalog. Contact-specific image and schema filters remain
 * active because they own different concerns.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Remove legacy text-metadata filters once every theme module is registered. */
function nvx_seo_retire_legacy_metadata_filters(): void {
	$legacy = array(
		array( 'wpseo_title', 'nvx_filter_valoracion_document_title', 21 ),
		array( 'wpseo_metadesc', 'nvx_filter_valoracion_metadesc', 21 ),
		array( 'wpseo_title', 'nvx_filter_contacto_document_title', 21 ),
		array( 'wpseo_metadesc', 'nvx_filter_contacto_metadesc', 21 ),
		array( 'wpseo_title', 'nvx_contacto_seo_title', 10 ),
		array( 'wpseo_metadesc', 'nvx_contacto_seo_metadesc', 10 ),
		array( 'wpseo_opengraph_title', 'nvx_filter_contacto_social_title', 110 ),
		array( 'wpseo_twitter_title', 'nvx_filter_contacto_social_title', 110 ),
		array( 'wpseo_opengraph_desc', 'nvx_filter_contacto_social_description', 110 ),
		array( 'wpseo_twitter_description', 'nvx_filter_contacto_social_description', 110 ),
	);

	foreach ( $legacy as $registration ) {
		remove_filter( $registration[0], $registration[1], $registration[2] );
	}
}
add_action( 'wp_loaded', 'nvx_seo_retire_legacy_metadata_filters', 1 );
