<?php
/**
 * Invalidate Yoast's XML sitemap storage after governed publication metadata
 * has changed. This is derived-cache maintenance only: it does not mutate
 * content, route status, permalink, robots policy, or canonical policy.
 */

if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	fwrite( STDERR, "PUBLICATION_SITEMAP_CACHE_INVALIDATION=FAIL reason=wp_cli_required\n" );
	exit( 1 );
}

if ( ! class_exists( 'WPSEO_Sitemaps_Cache_Validator' ) || ! method_exists( 'WPSEO_Sitemaps_Cache_Validator', 'invalidate_storage' ) ) {
	fwrite( STDERR, "PUBLICATION_SITEMAP_CACHE_INVALIDATION=FAIL reason=yoast_cache_validator_unavailable\n" );
	exit( 1 );
}

WPSEO_Sitemaps_Cache_Validator::invalidate_storage();

if ( class_exists( 'WPSEO_Utils' ) && method_exists( 'WPSEO_Utils', 'clear_cache' ) ) {
	WPSEO_Utils::clear_cache();
}

wp_cache_flush();

printf( "PUBLICATION_SITEMAP_CACHE_INVALIDATION=PASS scope=yoast_xml_storage\n" );
