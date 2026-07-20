<?php
/**
 * Contract test for the staging2 canonical closure (inc/nvx-staging2-canonical-closure.php).
 */

declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );

$GLOBALS['nvx_test_filters']    = array();
$GLOBALS['nvx_test_404']        = false;
$GLOBALS['nvx_test_search']     = false;
$GLOBALS['nvx_test_preview']    = false;
$GLOBALS['nvx_test_front_page'] = false;
$GLOBALS['nvx_test_home']       = false;
$GLOBALS['nvx_test_singular']   = false;

/**
 * Records a filter registration for test inspection.
 *
 * @param string   $hook          The filter hook name.
 * @param callable $callback      The callback associated with the filter.
 * @param int      $priority      The filter execution priority.
 * @param int      $accepted_args The number of callback arguments.
 */
function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['nvx_test_filters'][] = array( $hook, $callback, $priority, $accepted_args );
}
function is_404(): bool { return (bool) $GLOBALS['nvx_test_404']; }
function is_search(): bool { return (bool) $GLOBALS['nvx_test_search']; }
function is_preview(): bool { return (bool) $GLOBALS['nvx_test_preview']; }
function is_front_page(): bool { return (bool) $GLOBALS['nvx_test_front_page']; }
function is_home(): bool { return (bool) $GLOBALS['nvx_test_home']; }
function is_singular(): bool { return (bool) $GLOBALS['nvx_test_singular']; }
/**
 * Thin wrapper around PHP's native URL parser used by the module under test.
 *
 * @param string $url       The URL to parse.
 * @param int    $component The URL component to return.
 * @return mixed The requested URL component.
 */
function wp_parse_url( $url, $component = -1 ) { return parse_url( $url, $component ); }

require dirname( __DIR__, 2 ) . '/wp-content/themes/nuvanx-medical/inc/nvx-staging2-canonical-closure.php';

/**
 * Terminates the test with a failure message when a condition is false.
 *
 * @param bool   $condition The condition that must be true for the test to continue.
 * @param string $message   The message to write when the assertion fails.
 */
function nvx_test_assert( $condition, string $message ): void {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
}

// --- add_filter registration -------------------------------------------------
$hooks = array_column( $GLOBALS['nvx_test_filters'], 0 );
nvx_test_assert( in_array( 'wpseo_canonical', $hooks, true ), 'wpseo_canonical filter must be registered' );
nvx_test_assert( in_array( 'wpseo_opengraph_url', $hooks, true ), 'wpseo_opengraph_url filter must be registered' );
foreach ( $GLOBALS['nvx_test_filters'] as $registration ) {
	nvx_test_assert( 'nvx_staging2_filter_public_canonical' === $registration[1], 'both hooks must share the same canonical filter callback' );
	nvx_test_assert( 1000 === $registration[2], 'canonical filter must run late (priority 1000) to win over Yoast defaults' );
}

// --- nvx_staging2_canonical_is_protected_review(): missing collaborators ----
nvx_test_assert(
	false === nvx_staging2_canonical_is_protected_review(),
	'protected-review check must return false when the strategy catalogue helpers are unavailable'
);

// --- nvx_staging2_public_canonical_url(): guard clauses ---------------------
$_SERVER['REQUEST_URI'] = '/tratamientos/endolift/?utm_source=test';

$GLOBALS['nvx_test_404'] = true;
nvx_test_assert( '' === nvx_staging2_public_canonical_url(), '404 responses must not receive a public canonical URL' );
$GLOBALS['nvx_test_404'] = false;

$GLOBALS['nvx_test_search'] = true;
nvx_test_assert( '' === nvx_staging2_public_canonical_url(), 'search results must not receive a public canonical URL' );
$GLOBALS['nvx_test_search'] = false;

$GLOBALS['nvx_test_preview'] = true;
nvx_test_assert( '' === nvx_staging2_public_canonical_url(), 'preview requests must not receive a public canonical URL' );
$GLOBALS['nvx_test_preview'] = false;

nvx_test_assert(
	'' === nvx_staging2_public_canonical_url(),
	'a route that is neither the front page, home, nor singular must not receive a public canonical URL'
);

// --- nvx_staging2_public_canonical_url(): fallback path resolution (helper not yet defined) ---
$GLOBALS['nvx_test_singular'] = true;

$_SERVER['REQUEST_URI'] = '/tratamientos/endolift/?utm_source=test';
nvx_test_assert(
	'https://nuvanx.com/tratamientos/endolift/' === nvx_staging2_public_canonical_url(),
	'the fallback path resolver must strip the query string and normalize the trailing slash'
);

$_SERVER['REQUEST_URI'] = '/';
nvx_test_assert(
	'https://nuvanx.com/' === nvx_staging2_public_canonical_url(),
	'the root path must normalize to exactly one trailing slash, not a double slash'
);

$_SERVER['REQUEST_URI'] = 'tratamientos/endolift';
nvx_test_assert(
	'https://nuvanx.com/tratamientos/endolift/' === nvx_staging2_public_canonical_url(),
	'a path missing its leading slash must still normalize to a well-formed canonical URL'
);

// --- nvx_staging2_public_canonical_url(): nvx_seo_current_path() takes priority once available ---
$GLOBALS['nvx_test_custom_path'] = '/custom-routed-path/';
/**
 * Test double for the optional path-resolution helper the module prefers.
 *
 * @return string The globally-configured test path.
 */
function nvx_seo_current_path(): string { return $GLOBALS['nvx_test_custom_path']; }

$_SERVER['REQUEST_URI'] = '/should-be-ignored/';
nvx_test_assert(
	'https://nuvanx.com/custom-routed-path/' === nvx_staging2_public_canonical_url(),
	'nvx_seo_current_path() must take priority over the raw REQUEST_URI once it is available'
);

$GLOBALS['nvx_test_custom_path'] = 'no-leading-or-trailing-slash';
nvx_test_assert(
	'https://nuvanx.com/no-leading-or-trailing-slash/' === nvx_staging2_public_canonical_url(),
	'a helper-provided path without slashes must still be normalized'
);

// --- nvx_staging2_canonical_is_protected_review(): strategy catalogue integration ---
$GLOBALS['nvx_test_strategy_key']     = null;
$GLOBALS['nvx_test_strategy_catalog'] = array();
/**
 * Test double for the strategy-page route resolver.
 *
 * @return string|null The globally-configured test strategy key.
 */
function nvx_strategy_current_page_key(): ?string { return $GLOBALS['nvx_test_strategy_key']; }
/**
 * Test double for the strategy-page catalogue.
 *
 * @return array The globally-configured test catalogue.
 */
function nvx_strategy_page_catalog(): array { return $GLOBALS['nvx_test_strategy_catalog']; }

nvx_test_assert(
	false === nvx_staging2_canonical_is_protected_review(),
	'a page with no matching strategy key must not be treated as a protected review route'
);

$GLOBALS['nvx_test_strategy_key']     = 'liposculpt_air';
$GLOBALS['nvx_test_strategy_catalog'] = array(
	'liposculpt_air' => array( 'review_status' => 'pending_medical_legal' ),
);
nvx_test_assert(
	true === nvx_staging2_canonical_is_protected_review(),
	'a strategy page pending medical/legal review must be treated as protected'
);

$GLOBALS['nvx_test_strategy_key']     = 'why_nuvanx';
$GLOBALS['nvx_test_strategy_catalog'] = array(
	'why_nuvanx' => array( 'review_status' => 'approved_for_publication' ),
);
nvx_test_assert(
	false === nvx_staging2_canonical_is_protected_review(),
	'a strategy page approved for publication must not be treated as protected'
);

$GLOBALS['nvx_test_strategy_key']     = 'untracked_key';
$GLOBALS['nvx_test_strategy_catalog'] = array(
	'why_nuvanx' => array( 'review_status' => 'approved_for_publication' ),
);
nvx_test_assert(
	false === nvx_staging2_canonical_is_protected_review(),
	'a strategy key absent from the catalogue must not be treated as protected'
);

$GLOBALS['nvx_test_strategy_key']     = 'missing_status';
$GLOBALS['nvx_test_strategy_catalog'] = array(
	'missing_status' => array(),
);
nvx_test_assert(
	false === nvx_staging2_canonical_is_protected_review(),
	'a catalogue entry without a review_status field must not be treated as protected'
);

// --- nvx_staging2_public_canonical_url(): protected review overrides an otherwise-valid singular route ---
$GLOBALS['nvx_test_strategy_key']     = 'liposculpt_air';
$GLOBALS['nvx_test_strategy_catalog'] = array(
	'liposculpt_air' => array( 'review_status' => 'pending_medical_legal' ),
);
$GLOBALS['nvx_test_custom_path']      = '/liposculpt-air/';
nvx_test_assert(
	'' === nvx_staging2_public_canonical_url(),
	'a protected working-name route must never expose a public canonical URL, even when singular'
);

// Reset the strategy key so subsequent filter tests exercise the happy path.
$GLOBALS['nvx_test_strategy_key']     = null;
$GLOBALS['nvx_test_strategy_catalog'] = array();

// --- nvx_staging2_filter_public_canonical(): environment gating -------------
nvx_test_assert(
	'https://original-yoast-value.test/' === nvx_staging2_filter_public_canonical( 'https://original-yoast-value.test/' ),
	'when the environment-detection helper is unavailable, the original Yoast value must pass through unchanged'
);

$GLOBALS['nvx_test_nonproduction'] = false;
/**
 * Test double for the environment-detection helper the filter depends on.
 *
 * @return bool The globally-configured test environment flag.
 */
function nvx_seo_is_nonproduction_environment(): bool { return (bool) $GLOBALS['nvx_test_nonproduction']; }

nvx_test_assert(
	'https://original-yoast-value.test/' === nvx_staging2_filter_public_canonical( 'https://original-yoast-value.test/' ),
	'production requests must keep the original Yoast-generated canonical value'
);

$GLOBALS['nvx_test_nonproduction'] = true;
$GLOBALS['nvx_test_custom_path']   = '/tratamientos/endolift/';
nvx_test_assert(
	'https://nuvanx.com/tratamientos/endolift/' === nvx_staging2_filter_public_canonical( 'https://staging2.nuvanx.com/tratamientos/endolift/' ),
	'nonproduction requests must be rewritten to the public production canonical URL'
);

$GLOBALS['nvx_test_singular']      = false;
$GLOBALS['nvx_test_nonproduction'] = true;
nvx_test_assert(
	'' === nvx_staging2_filter_public_canonical( 'https://staging2.nuvanx.com/blog/' ),
	'nonproduction archive-style routes must resolve to an empty canonical instead of leaking the staging URL'
);

fwrite( STDOUT, "Staging2 canonical closure contracts passed.\n" );