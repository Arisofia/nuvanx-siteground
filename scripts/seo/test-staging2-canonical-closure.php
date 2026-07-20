<?php
/**
 * Behavioral contract for the staging2 canonical URL closure.
 *
 * Exercises nvx_staging2_canonical_is_protected_review(), nvx_staging2_public_canonical_url()
 * and nvx_staging2_filter_public_canonical() with controllable stubs, since these functions
 * depend on WordPress conditionals, the strategy-page catalogue and the SEO environment helper.
 */

declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );

$GLOBALS['nvx_test_filters'] = array();

/**
 * Records a filter registration for later inspection.
 *
 * @param string   $hook          The filter hook name.
 * @param callable $callback      The callback associated with the filter.
 * @param int      $priority      The filter execution priority.
 * @param int      $accepted_args The number of callback arguments.
 */
function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['nvx_test_filters'][] = array( $hook, $callback, $priority, $accepted_args );
	return true;
}

/**
 * Terminates the test with a failure message when a condition is false.
 *
 * @param bool   $condition The condition that must be true for the test to continue.
 * @param string $message   The message to write when the assertion fails.
 */
function nvx_test_assert( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
}

require dirname( __DIR__, 2 ) . '/wp-content/themes/nuvanx-medical/inc/nvx-staging2-canonical-closure.php';

// --- add_filter hook registration ---
$canonical_filters = array_values(
	array_filter(
		$GLOBALS['nvx_test_filters'],
		static function ( $filter ) {
			return in_array( $filter[0], array( 'wpseo_canonical', 'wpseo_opengraph_url' ), true );
		}
	)
);
nvx_test_assert( 2 === count( $canonical_filters ), 'the module must register exactly two Yoast filters' );
foreach ( $canonical_filters as $filter ) {
	nvx_test_assert( 'nvx_staging2_filter_public_canonical' === $filter[1], "filter {$filter[0]} must use the canonical callback" );
	nvx_test_assert( 1000 === $filter[2], "filter {$filter[0]} must run at priority 1000 so it overrides Yoast's default" );
}

// --- nvx_staging2_canonical_is_protected_review(): before the strategy helpers exist ---
nvx_test_assert(
	false === nvx_staging2_canonical_is_protected_review(),
	'is_protected_review must be false when the strategy-page helpers are unavailable'
);

$GLOBALS['nvx_test_strategy_key']     = null;
$GLOBALS['nvx_test_strategy_catalog'] = array(
	'why_nuvanx'     => array( 'review_status' => 'approved_for_publication' ),
	'liposculpt_air' => array( 'review_status' => 'pending_medical_legal' ),
	'no_status'      => array(),
);

/**
 * Returns the configured strategy page key for the current test scenario.
 *
 * @return string|null The strategy key under test, or null when none is configured.
 */
function nvx_strategy_current_page_key() {
	return $GLOBALS['nvx_test_strategy_key'];
}

/**
 * Returns the configured strategy page catalogue for the current test scenario.
 *
 * @return array The strategy page catalogue keyed by strategy key.
 */
function nvx_strategy_page_catalog() {
	return $GLOBALS['nvx_test_strategy_catalog'];
}

// --- nvx_staging2_canonical_is_protected_review(): now that the helpers exist ---
$GLOBALS['nvx_test_strategy_key'] = null;
nvx_test_assert(
	false === nvx_staging2_canonical_is_protected_review(),
	'is_protected_review must be false when there is no resolvable strategy key'
);

$GLOBALS['nvx_test_strategy_key'] = 'why_nuvanx';
nvx_test_assert(
	false === nvx_staging2_canonical_is_protected_review(),
	'is_protected_review must be false for a strategy page approved for publication'
);

$GLOBALS['nvx_test_strategy_key'] = 'liposculpt_air';
nvx_test_assert(
	true === nvx_staging2_canonical_is_protected_review(),
	'is_protected_review must be true for a strategy page pending medical/legal review'
);

$GLOBALS['nvx_test_strategy_key'] = 'no_status';
nvx_test_assert(
	false === nvx_staging2_canonical_is_protected_review(),
	'is_protected_review must be false when the catalogue entry has no review_status'
);

$GLOBALS['nvx_test_strategy_key'] = 'unknown_key';
nvx_test_assert(
	false === nvx_staging2_canonical_is_protected_review(),
	'is_protected_review must be false when the resolved key is absent from the catalogue'
);

$GLOBALS['nvx_test_strategy_key'] = null;

// --- nvx_staging2_public_canonical_url() ---
$GLOBALS['nvx_test_flags'] = array(
	'is_404'         => false,
	'is_search'      => false,
	'is_preview'     => false,
	'is_front_page'  => false,
	'is_home'        => false,
	'is_singular'    => false,
);

/**
 * Resets all conditional test flags to their default (non-matching) state.
 */
function nvx_test_reset_flags() {
	$GLOBALS['nvx_test_flags'] = array(
		'is_404'        => false,
		'is_search'     => false,
		'is_preview'    => false,
		'is_front_page' => false,
		'is_home'       => false,
		'is_singular'   => false,
	);
}

function is_404() {
	return $GLOBALS['nvx_test_flags']['is_404'];
}
function is_search() {
	return $GLOBALS['nvx_test_flags']['is_search'];
}
function is_preview() {
	return $GLOBALS['nvx_test_flags']['is_preview'];
}
function is_front_page() {
	return $GLOBALS['nvx_test_flags']['is_front_page'];
}
function is_home() {
	return $GLOBALS['nvx_test_flags']['is_home'];
}
function is_singular() {
	return $GLOBALS['nvx_test_flags']['is_singular'];
}

/**
 * Mimics wp_parse_url() using PHP's native parse_url().
 *
 * @param string   $url       The URL to parse.
 * @param int      $component The URL component to return.
 * @return mixed The requested URL component.
 */
function wp_parse_url( $url, $component = -1 ) {
	return parse_url( $url, $component );
}

// Protected review must short-circuit before any conditional checks.
$GLOBALS['nvx_test_strategy_key'] = 'liposculpt_air';
nvx_test_reset_flags();
$GLOBALS['nvx_test_flags']['is_front_page'] = true;
nvx_test_assert(
	'' === nvx_staging2_public_canonical_url(),
	'public_canonical_url must be empty for a protected review route even when is_front_page is true'
);
$GLOBALS['nvx_test_strategy_key'] = null;

nvx_test_reset_flags();
$GLOBALS['nvx_test_flags']['is_404'] = true;
nvx_test_assert( '' === nvx_staging2_public_canonical_url(), 'public_canonical_url must be empty on a 404' );

nvx_test_reset_flags();
$GLOBALS['nvx_test_flags']['is_search'] = true;
nvx_test_assert( '' === nvx_staging2_public_canonical_url(), 'public_canonical_url must be empty on a search page' );

nvx_test_reset_flags();
$GLOBALS['nvx_test_flags']['is_preview'] = true;
nvx_test_assert( '' === nvx_staging2_public_canonical_url(), 'public_canonical_url must be empty on a preview' );

nvx_test_reset_flags();
nvx_test_assert(
	'' === nvx_staging2_public_canonical_url(),
	'public_canonical_url must be empty when the request is neither front page, home, nor singular'
);

// Path fallback via wp_parse_url()/$_SERVER, before nvx_seo_current_path() exists.
nvx_test_reset_flags();
$GLOBALS['nvx_test_flags']['is_singular'] = true;
$_SERVER['REQUEST_URI'] = '/tratamientos/endolift?utm=abc';
nvx_test_assert(
	'https://nuvanx.com/tratamientos/endolift/' === nvx_staging2_public_canonical_url(),
	'public_canonical_url must strip the query string and normalize slashes using the request URI fallback'
);

$_SERVER['REQUEST_URI'] = '/';
nvx_test_assert(
	'https://nuvanx.com/' === nvx_staging2_public_canonical_url(),
	'public_canonical_url must resolve the site root to a single trailing slash'
);

nvx_test_reset_flags();
$GLOBALS['nvx_test_flags']['is_home'] = true;
$_SERVER['REQUEST_URI'] = '/blog';
nvx_test_assert(
	'https://nuvanx.com/blog/' === nvx_staging2_public_canonical_url(),
	'public_canonical_url must also resolve for is_home() requests and add a trailing slash'
);

/**
 * Returns a fixed test path, taking precedence over the $_SERVER fallback.
 *
 * @return string The configured canonical path for the current test scenario.
 */
function nvx_seo_current_path() {
	return $GLOBALS['nvx_test_seo_path'];
}

nvx_test_reset_flags();
$GLOBALS['nvx_test_flags']['is_singular'] = true;
$GLOBALS['nvx_test_seo_path']             = '/custom-path/';
$_SERVER['REQUEST_URI']                   = '/ignored-path?should=not-be-used';
nvx_test_assert(
	'https://nuvanx.com/custom-path/' === nvx_staging2_public_canonical_url(),
	'public_canonical_url must prefer nvx_seo_current_path() over the request URI fallback once it exists'
);

// --- nvx_staging2_filter_public_canonical() ---
nvx_test_assert(
	'https://staging2.nuvanx.com/foo/' === nvx_staging2_filter_public_canonical( 'https://staging2.nuvanx.com/foo/' ),
	'filter_public_canonical must return the original URL when the environment helper is unavailable'
);

/**
 * Reports whether the current test scenario simulates a non-production environment.
 *
 * @return bool The configured non-production flag.
 */
function nvx_seo_is_nonproduction_environment() {
	return $GLOBALS['nvx_test_nonproduction'];
}

$GLOBALS['nvx_test_nonproduction'] = false;
nvx_test_assert(
	'https://staging2.nuvanx.com/foo/' === nvx_staging2_filter_public_canonical( 'https://staging2.nuvanx.com/foo/' ),
	'filter_public_canonical must return the original URL unchanged in production'
);

$GLOBALS['nvx_test_nonproduction'] = true;
nvx_test_assert(
	'https://nuvanx.com/custom-path/' === nvx_staging2_filter_public_canonical( 'https://staging2.nuvanx.com/foo/' ),
	'filter_public_canonical must replace the URL with the public production canonical in non-production'
);

$GLOBALS['nvx_test_strategy_key'] = 'liposculpt_air';
nvx_test_assert(
	'' === nvx_staging2_filter_public_canonical( 'https://staging2.nuvanx.com/liposculpt-air/' ),
	'filter_public_canonical must suppress the canonical entirely for a protected review route in non-production'
);
$GLOBALS['nvx_test_strategy_key'] = null;

fwrite( STDOUT, "PASS: staging2 canonical closure contract\n" );