<?php
/**
 * Contract for the staging2 canonical closure when strategy/SEO helpers are loaded.
 *
 * Verifies protected working-name routes stay without a canonical, eligible
 * public routes expose the production URL while non-production, and the
 * filter is a pure pass-through on production.
 */

declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );

$GLOBALS['nvx_test_filters']        = array();
$GLOBALS['nvx_test_is_404']         = false;
$GLOBALS['nvx_test_is_search']      = false;
$GLOBALS['nvx_test_is_preview']     = false;
$GLOBALS['nvx_test_is_front_page']  = false;
$GLOBALS['nvx_test_is_home']        = false;
$GLOBALS['nvx_test_is_singular']    = false;
$GLOBALS['nvx_test_nonproduction']  = true;
$GLOBALS['nvx_test_strategy_key']   = null;
$GLOBALS['nvx_test_current_path']   = '/';

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

function nvx_strategy_current_page_key(): ?string {
	return $GLOBALS['nvx_test_strategy_key'];
}
function nvx_strategy_page_catalog(): array {
	return array(
		'liposculpt_air' => array( 'review_status' => 'pending_medical_legal' ),
		'v_lift_awake'   => array( 'review_status' => 'pending_medical_legal' ),
		'why_nuvanx'     => array( 'review_status' => 'approved_for_publication' ),
	);
}

function nvx_seo_current_path(): string {
	return $GLOBALS['nvx_test_current_path'];
}
function nvx_seo_is_nonproduction_environment(): bool {
	return $GLOBALS['nvx_test_nonproduction'];
}

require dirname( __DIR__, 2 ) . '/wp-content/themes/nuvanx-medical/inc/nvx-staging2-canonical-closure.php';

function nvx_assert( bool $condition, string $message ): void {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
}

function nvx_reset_canonical_test_state(): void {
	$GLOBALS['nvx_test_is_404']        = false;
	$GLOBALS['nvx_test_is_search']     = false;
	$GLOBALS['nvx_test_is_preview']    = false;
	$GLOBALS['nvx_test_is_front_page'] = false;
	$GLOBALS['nvx_test_is_home']       = false;
	$GLOBALS['nvx_test_is_singular']   = false;
	$GLOBALS['nvx_test_nonproduction'] = true;
	$GLOBALS['nvx_test_strategy_key']  = null;
	$GLOBALS['nvx_test_current_path']  = '/';
}

// --- add_filter registration -------------------------------------------------

nvx_assert( 2 === count( $GLOBALS['nvx_test_filters'] ), 'exactly two filters must be registered on load' );
nvx_assert(
	array( 'wpseo_canonical', 'nvx_staging2_filter_public_canonical', 1000 ) === $GLOBALS['nvx_test_filters'][0],
	'wpseo_canonical must be filtered at priority 1000'
);
nvx_assert(
	array( 'wpseo_opengraph_url', 'nvx_staging2_filter_public_canonical', 1000 ) === $GLOBALS['nvx_test_filters'][1],
	'wpseo_opengraph_url must be filtered at priority 1000'
);

// --- nvx_staging2_canonical_is_protected_review() -----------------------------

nvx_reset_canonical_test_state();
nvx_assert( false === nvx_staging2_canonical_is_protected_review(), 'no current strategy page must not be protected' );

$GLOBALS['nvx_test_strategy_key'] = 'liposculpt_air';
nvx_assert( true === nvx_staging2_canonical_is_protected_review(), 'pending_medical_legal strategy page must be protected' );

$GLOBALS['nvx_test_strategy_key'] = 'why_nuvanx';
nvx_assert( false === nvx_staging2_canonical_is_protected_review(), 'approved_for_publication strategy page must not be protected' );

$GLOBALS['nvx_test_strategy_key'] = 'unknown_key';
nvx_assert( false === nvx_staging2_canonical_is_protected_review(), 'strategy key absent from the catalogue must not be protected' );

// --- nvx_staging2_public_canonical_url() --------------------------------------

nvx_reset_canonical_test_state();
$GLOBALS['nvx_test_is_singular']  = true;
$GLOBALS['nvx_test_current_path'] = '/tratamientos/endolift/';
nvx_assert(
	'https://nuvanx.com/tratamientos/endolift/' === nvx_staging2_public_canonical_url(),
	'singular eligible route must expose the production canonical URL'
);

nvx_reset_canonical_test_state();
$GLOBALS['nvx_test_is_front_page'] = true;
$GLOBALS['nvx_test_current_path']  = '/';
nvx_assert( 'https://nuvanx.com/' === nvx_staging2_public_canonical_url(), 'front page must resolve to the bare production root' );

nvx_reset_canonical_test_state();
$GLOBALS['nvx_test_is_home']       = true;
$GLOBALS['nvx_test_current_path']  = 'blog';
nvx_assert( 'https://nuvanx.com/blog/' === nvx_staging2_public_canonical_url(), 'path must be normalized with a leading and trailing slash' );

nvx_reset_canonical_test_state();
$GLOBALS['nvx_test_is_singular']   = true;
$GLOBALS['nvx_test_current_path']  = '/contacto';
nvx_assert(
	'https://nuvanx.com/contacto/' === nvx_staging2_public_canonical_url(),
	'a missing trailing slash on the source path must be appended'
);

nvx_reset_canonical_test_state();
$GLOBALS['nvx_test_is_404'] = true;
nvx_assert( '' === nvx_staging2_public_canonical_url(), '404 requests must never receive a canonical' );

nvx_reset_canonical_test_state();
$GLOBALS['nvx_test_is_search'] = true;
nvx_assert( '' === nvx_staging2_public_canonical_url(), 'search requests must never receive a canonical' );

nvx_reset_canonical_test_state();
$GLOBALS['nvx_test_is_preview'] = true;
nvx_assert( '' === nvx_staging2_public_canonical_url(), 'preview requests must never receive a canonical' );

nvx_reset_canonical_test_state();
// Neither front page, home nor singular.
nvx_assert( '' === nvx_staging2_public_canonical_url(), 'requests outside front page, home and singular must be excluded' );

nvx_reset_canonical_test_state();
$GLOBALS['nvx_test_is_singular']  = true;
$GLOBALS['nvx_test_strategy_key'] = 'v_lift_awake';
nvx_assert(
	'' === nvx_staging2_public_canonical_url(),
	'protected working-name routes must not expose a production canonical even when singular'
);

// --- nvx_staging2_filter_public_canonical() -----------------------------------

nvx_reset_canonical_test_state();
$GLOBALS['nvx_test_nonproduction'] = false;
nvx_assert(
	'https://staging2.nuvanx.com/whatever/' === nvx_staging2_filter_public_canonical( 'https://staging2.nuvanx.com/whatever/' ),
	'production environment must leave the Yoast-generated canonical untouched'
);

nvx_reset_canonical_test_state();
$GLOBALS['nvx_test_nonproduction'] = true;
$GLOBALS['nvx_test_is_singular']   = true;
$GLOBALS['nvx_test_current_path']  = '/tratamientos/';
nvx_assert(
	'https://nuvanx.com/tratamientos/' === nvx_staging2_filter_public_canonical( 'https://staging2.nuvanx.com/tratamientos/' ),
	'non-production eligible request must be rewritten to the production canonical'
);

nvx_reset_canonical_test_state();
$GLOBALS['nvx_test_nonproduction'] = true;
$GLOBALS['nvx_test_is_singular']   = true;
$GLOBALS['nvx_test_strategy_key']  = 'liposculpt_air';
nvx_assert(
	'' === nvx_staging2_filter_public_canonical( 'https://staging2.nuvanx.com/liposculpt-air/' ),
	'non-production protected review request must resolve to an empty canonical'
);

fwrite( STDOUT, "Staging2 canonical closure tests passed.\n" );