<?php
/**
 * Contract for the staging2 canonical closure when the strategy pages and SEO
 * metadata helpers are NOT loaded (e.g. a theme update sequencing gap).
 *
 * The closure must degrade safely: never treat a route as protected, fall
 * back to $_SERVER['REQUEST_URI'] for the path, and leave the Yoast canonical
 * untouched when environment detection is unavailable.
 */

declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );

$GLOBALS['nvx_test_filters']       = array();
$GLOBALS['nvx_test_is_404']        = false;
$GLOBALS['nvx_test_is_search']     = false;
$GLOBALS['nvx_test_is_preview']    = false;
$GLOBALS['nvx_test_is_front_page'] = false;
$GLOBALS['nvx_test_is_home']       = false;
$GLOBALS['nvx_test_is_singular']   = false;

function add_filter( ...$args ): bool {
	$GLOBALS['nvx_test_filters'][] = $args;
	return true;
}

function is_404(): bool {
	return $GLOBALS['nvx_test_is_404'];
}
function is_search(): bool {
	return $GLOBALS['nvx_test_is_search'];
}
function is_preview(): bool {
	return $GLOBALS['nvx_test_is_preview'];
}
function is_front_page(): bool {
	return $GLOBALS['nvx_test_is_front_page'];
}
function is_home(): bool {
	return $GLOBALS['nvx_test_is_home'];
}
function is_singular(): bool {
	return $GLOBALS['nvx_test_is_singular'];
}

function wp_parse_url( string $url, int $component = -1 ) {
	return parse_url( $url, $component );
}

// Deliberately NOT defining nvx_strategy_current_page_key(), nvx_strategy_page_catalog(),
// nvx_seo_current_path() or nvx_seo_is_nonproduction_environment() so the module's
// function_exists() guards are exercised.

require dirname( __DIR__, 2 ) . '/wp-content/themes/nuvanx-medical/inc/nvx-staging2-canonical-closure.php';

function nvx_assert( bool $condition, string $message ): void {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
}

// A route can never be "protected" when the strategy catalogue is unavailable.
nvx_assert(
	false === nvx_staging2_canonical_is_protected_review(),
	'protected review must default to false when strategy helpers are missing'
);

// Path resolution falls back to $_SERVER['REQUEST_URI'] when nvx_seo_current_path()
// does not exist.
$GLOBALS['nvx_test_is_singular'] = true;
$_SERVER['REQUEST_URI']          = '/tratamientos/endolift/?utm_source=test';
nvx_assert(
	'https://nuvanx.com/tratamientos/endolift/' === nvx_staging2_public_canonical_url(),
	'fallback path resolution must use $_SERVER[REQUEST_URI] without the query string'
);

unset( $_SERVER['REQUEST_URI'] );
nvx_assert(
	'https://nuvanx.com/' === nvx_staging2_public_canonical_url(),
	'a missing REQUEST_URI must fall back to the site root'
);

// Without environment detection, the filter must be a pure pass-through.
$original = 'https://staging2.nuvanx.com/tratamientos/';
nvx_assert(
	$original === nvx_staging2_filter_public_canonical( $original ),
	'the filter must return the original value untouched when environment detection is unavailable'
);

fwrite( STDOUT, "Staging2 canonical closure standalone fallback tests passed.\n" );