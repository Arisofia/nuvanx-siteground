<?php
/**
 * Contract test for the strategy-page suppression added to the shared page
 * shell (template-parts/content/nvx-page-shell.php).
 *
 * The shell must not render a duplicate theme hero/title header when the
 * current page is a recognised strategy page, because the strategy content
 * itself already owns the H1 hierarchy.
 */

declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );

$GLOBALS['nvx_test_strategy_key']    = null;
$GLOBALS['nvx_test_has_thumbnail']   = true;
$GLOBALS['nvx_test_is_front_page']   = false;
$GLOBALS['nvx_test_post_content']    = '<p>Body copy without an author H1 or a hero block.</p>';
$GLOBALS['nvx_test_iterations_left'] = 0;

function have_posts(): bool {
	if ( $GLOBALS['nvx_test_iterations_left'] > 0 ) {
		$GLOBALS['nvx_test_iterations_left']--;
		return true;
	}
	return false;
}
function the_post(): void {}
/**
 * Test double for the post-field accessor; only post_content is exercised.
 *
 * @param string $field   The requested post field.
 * @param int    $post_id The post identifier (unused by the test double).
 * @return string The requested field value.
 */
function get_post_field( string $field, int $post_id ) {
	return 'post_content' === $field ? $GLOBALS['nvx_test_post_content'] : '';
}
function get_the_ID(): int { return 101; }
function has_post_thumbnail(): bool { return (bool) $GLOBALS['nvx_test_has_thumbnail']; }
function is_singular( string $type = '' ): bool { return false; }
function is_front_page(): bool { return (bool) $GLOBALS['nvx_test_is_front_page']; }
/**
 * Test double that renders the resolved article classes so they can be
 * asserted against in the captured output buffer.
 *
 * @param string|array $classes One or more CSS classes to render.
 */
function post_class( $classes = array() ): void {
	echo 'class="' . implode( ' ', (array) $classes ) . '"';
}
function the_ID(): void { echo get_the_ID(); }
/**
 * Test double for the featured-image renderer.
 *
 * @param string $size  The requested image size (unused by the test double).
 * @param array  $attrs Additional image attributes (unused by the test double).
 */
function the_post_thumbnail( $size = '', $attrs = array() ): void { echo '<img data-test-thumbnail="1">'; }
function the_title_attribute( $args = array() ) { return 'Test title'; }
function get_the_category(): array { return array(); }
function esc_html( string $text ): string { return htmlspecialchars( $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' ); }
function esc_html_e( string $text, string $domain = 'default' ): void { echo htmlspecialchars( $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' ); }
function the_title( string $before = '', string $after = '' ): void { echo $before . 'Test Title' . $after; }
function get_the_date( string $format = '' ) { return '2026-07-20'; }
function esc_attr( string $text ): string { return htmlspecialchars( $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' ); }
function esc_attr_e( string $text, string $domain = 'default' ): void { echo htmlspecialchars( $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' ); }
function the_content(): void { echo $GLOBALS['nvx_test_post_content']; }
function get_previous_post() { return null; }
function get_next_post() { return null; }
function esc_url( string $url ): string { return $url; }
/**
 * Test double for the strategy-page route resolver under test.
 *
 * @return string|null The globally-configured test strategy key.
 */
function nvx_strategy_current_page_key(): ?string { return $GLOBALS['nvx_test_strategy_key']; }

$template = dirname( __DIR__, 2 ) . '/wp-content/themes/nuvanx-medical/template-parts/content/nvx-page-shell.php';

/**
 * Renders the page shell template once and returns its captured output.
 *
 * @param string $template The absolute path to the template file.
 * @return string The rendered markup.
 */
function nvx_test_render_shell( string $template ): string {
	$GLOBALS['nvx_test_iterations_left'] = 1;
	ob_start();
	require $template;
	return (string) ob_get_clean();
}

/**
 * Terminates the test with a failure message when a condition is false.
 *
 * @param bool   $condition The condition that must be true for the test to continue.
 * @param string $message   The message to write when the assertion fails.
 */
function nvx_test_assert( bool $condition, string $message ): void {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
}

// Scenario 1 (baseline): no strategy page key, a thumbnail is present, and the
// body has no author-owned heading or hero block — the theme must own the hero.
$GLOBALS['nvx_test_strategy_key']  = null;
$GLOBALS['nvx_test_has_thumbnail'] = true;
$baseline = nvx_test_render_shell( $template );
nvx_test_assert(
	false !== strpos( $baseline, 'nvx-page-hero--theme' ),
	'a page with a thumbnail and no managed editorial content must render the theme hero'
);
nvx_test_assert(
	false !== strpos( $baseline, 'nvx-page--has-hero' ),
	'a page rendering the theme hero must carry the has-hero article class'
);
nvx_test_assert(
	false !== strpos( $baseline, $GLOBALS['nvx_test_post_content'] ),
	'the shell must still render the post body content'
);

// Scenario 2: a recognised strategy page key must suppress the duplicate theme
// hero, because the strategy content already owns the H1 hierarchy.
$GLOBALS['nvx_test_strategy_key']  = 'why_nuvanx';
$GLOBALS['nvx_test_has_thumbnail'] = true;
$strategy_with_thumbnail = nvx_test_render_shell( $template );
nvx_test_assert(
	false === strpos( $strategy_with_thumbnail, 'nvx-page-hero--theme' ),
	'a strategy page must not receive a duplicate theme hero'
);
nvx_test_assert(
	false === strpos( $strategy_with_thumbnail, 'nvx-page--has-hero' ),
	'a strategy page must not carry the theme has-hero class'
);
nvx_test_assert(
	false !== strpos( $strategy_with_thumbnail, $GLOBALS['nvx_test_post_content'] ),
	'a strategy page shell must still render the post body content'
);

// Scenario 3: the same suppression must also apply to the title-only header
// path (no featured image at all).
$GLOBALS['nvx_test_strategy_key']  = 'investment';
$GLOBALS['nvx_test_has_thumbnail'] = false;
$strategy_without_thumbnail = nvx_test_render_shell( $template );
nvx_test_assert(
	false === strpos( $strategy_without_thumbnail, 'nvx-page-hero--theme' ),
	'a strategy page without a thumbnail must not receive the theme hero'
);
nvx_test_assert(
	false === strpos( $strategy_without_thumbnail, 'nvx-page__header nvx-section-intro' ),
	'a strategy page without a thumbnail must not receive the theme title-only header either'
);

// Regression check: without a strategy key, the title-only header path is
// still available for pages that have no thumbnail (pre-existing behaviour
// must remain intact).
$GLOBALS['nvx_test_strategy_key']  = null;
$GLOBALS['nvx_test_has_thumbnail'] = false;
$title_only_baseline = nvx_test_render_shell( $template );
nvx_test_assert(
	false !== strpos( $title_only_baseline, 'nvx-page__header nvx-section-intro' ),
	'a non-strategy page without a thumbnail must still render the theme title-only header'
);

// Boundary case: the shell only checks "null !== key", so a non-null but empty
// string key must also count as managed editorial content, matching the
// literal implementation of the added guard.
$GLOBALS['nvx_test_strategy_key']  = '';
$GLOBALS['nvx_test_has_thumbnail'] = true;
$empty_string_key = nvx_test_render_shell( $template );
nvx_test_assert(
	false === strpos( $empty_string_key, 'nvx-page-hero--theme' ),
	'an empty (but non-null) strategy key must still be treated as managed editorial content'
);

fwrite( STDOUT, "Page shell strategy-editorial suppression contracts passed.\n" );