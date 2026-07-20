<?php
/**
 * Functional regression test for the strategy-page guard added to nvx-page-shell.php.
 *
 * Confirms that pages resolved by nvx_strategy_current_page_key() are treated as
 * managed editorial content, so the shell does not render a duplicate theme-owned
 * hero or title header above the strategy page's own hero markup.
 */

declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );

$GLOBALS['nvx_shell_test'] = array(
	'post_returned'    => false,
	'has_thumbnail'    => false,
	'is_front_page'    => false,
	'is_singular_post' => false,
	'strategy_key'     => null,
	'content'          => '',
);

function have_posts() {
	return ! $GLOBALS['nvx_shell_test']['post_returned'];
}
function the_post() {
	$GLOBALS['nvx_shell_test']['post_returned'] = true;
}
function get_post_field( $field, $post_id ) {
	return $GLOBALS['nvx_shell_test']['content'];
}
function get_the_ID() {
	return 101;
}
function has_post_thumbnail() {
	return $GLOBALS['nvx_shell_test']['has_thumbnail'];
}
function is_front_page() {
	return $GLOBALS['nvx_shell_test']['is_front_page'];
}
function is_singular( $type = null ) {
	if ( 'post' === $type ) {
		return $GLOBALS['nvx_shell_test']['is_singular_post'];
	}
	return false;
}
function post_class( $classes = array() ) {
	echo 'class="' . implode( ' ', (array) $classes ) . '"';
}
function the_ID() {
	echo get_the_ID();
}
function the_post_thumbnail( $size, $attrs = array() ) {
	echo '<img class="nvx-test-thumb" />';
}
function the_title_attribute( $args = array() ) {
	return 'Test Title';
}
function get_the_category() {
	return array();
}
function esc_html( $text ) {
	return htmlspecialchars( (string) $text, ENT_QUOTES );
}
function esc_html_e( $text, $domain = null ) {
	echo esc_html( $text );
}
function the_title( $before = '', $after = '' ) {
	echo $before . 'Test Title' . $after;
}
function get_the_date( $format = '' ) {
	return '2026-01-01';
}
function esc_attr( $text ) {
	return htmlspecialchars( (string) $text, ENT_QUOTES );
}
function the_content() {
	echo $GLOBALS['nvx_shell_test']['content'];
}
function get_previous_post() {
	return null;
}
function get_next_post() {
	return null;
}
function esc_url( $url ) {
	return $url;
}
function get_permalink( $post ) {
	return '#';
}
function get_the_title( $post ) {
	return 'Adjacent';
}

/**
 * Returns the configured strategy page key for the current test scenario.
 *
 * @return string|null The strategy key under test, or null when none is configured.
 */
function nvx_strategy_current_page_key() {
	return $GLOBALS['nvx_shell_test']['strategy_key'];
}

$template_path = dirname( __DIR__, 2 ) . '/wp-content/themes/nuvanx-medical/template-parts/content/nvx-page-shell.php';
if ( ! is_readable( $template_path ) ) {
	fwrite( STDERR, "Missing required file: {$template_path}\n" );
	exit( 1 );
}

/**
 * Renders the page shell template with a fresh single-post loop for the given state.
 *
 * @param array $state Loop and content state to merge over the defaults.
 * @return string The rendered HTML output.
 */
function nvx_shell_render( array $state ) {
	global $template_path;
	$GLOBALS['nvx_shell_test'] = array_merge(
		array(
			'post_returned'    => false,
			'has_thumbnail'    => false,
			'is_front_page'    => false,
			'is_singular_post' => false,
			'strategy_key'     => null,
			'content'          => '<p>Body copy without any heading markers.</p>',
		),
		$state
	);
	ob_start();
	include $template_path;
	return (string) ob_get_clean();
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

// A strategy page with a featured image and no content H1/hero markers must NOT
// receive the theme-owned hero or the title-only header — its own hero already
// owns the page's H1.
$strategy_output = nvx_shell_render(
	array(
		'has_thumbnail' => true,
		'strategy_key'  => 'why_nuvanx',
	)
);
nvx_test_assert(
	false === strpos( $strategy_output, 'nvx-page-hero--theme' ),
	'a strategy page with a featured image must not render the theme-owned hero'
);
nvx_test_assert(
	false === strpos( $strategy_output, 'nvx-page__header' ),
	'a strategy page must not render the theme title-only header either'
);
nvx_test_assert(
	false === strpos( $strategy_output, 'nvx-page--has-hero' ),
	'a strategy page must not receive the has-hero article class from the theme'
);
nvx_test_assert(
	false !== strpos( $strategy_output, 'Body copy without any heading markers.' ),
	'the page content must still render inside entry-content'
);

// Baseline: the same content/thumbnail combination without a resolvable strategy
// key falls back to the theme-owned hero — this is what the added guard prevents
// for strategy pages.
$baseline_output = nvx_shell_render(
	array(
		'has_thumbnail' => true,
		'strategy_key'  => null,
	)
);
nvx_test_assert(
	false !== strpos( $baseline_output, 'nvx-page-hero--theme' ),
	'a non-strategy page with a featured image and no content heading must still get the theme hero'
);
nvx_test_assert(
	false !== strpos( $baseline_output, 'nvx-page--has-hero' ),
	'the theme hero must add the has-hero article class'
);

// The front page must never receive the theme-owned hero, even if it also
// resolves as a strategy page.
$front_output = nvx_shell_render(
	array(
		'has_thumbnail' => true,
		'strategy_key'  => 'investment',
		'is_front_page' => true,
	)
);
nvx_test_assert(
	false === strpos( $front_output, 'nvx-page-hero--theme' ),
	'the front page must never receive the theme-owned hero'
);

// A pending-review strategy key must be treated the same as an approved one for
// shell hero suppression — the guard only checks whether a key resolves at all.
$pending_output = nvx_shell_render(
	array(
		'has_thumbnail' => true,
		'strategy_key'  => 'liposculpt_air',
	)
);
nvx_test_assert(
	false === strpos( $pending_output, 'nvx-page-hero--theme' ),
	'a pending-review strategy page must also be treated as managed editorial'
);

// A strategy page without a featured image must not render the theme title-only
// header either, since the strategy guard applies regardless of media presence.
$no_media_output = nvx_shell_render(
	array(
		'has_thumbnail' => false,
		'strategy_key'  => 'why_nuvanx',
	)
);
nvx_test_assert(
	false === strpos( $no_media_output, 'nvx-page__header' ),
	'a strategy page without a featured image must not render the theme title-only header'
);

fwrite( STDOUT, "PASS: nvx-page-shell.php suppresses the theme hero and title header for strategy pages\n" );