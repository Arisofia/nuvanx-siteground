<?php
/**
 * Functional contract for the unified page shell's managed-editorial guard.
 *
 * Strategy pages (por-que-nuvanx, inversion-medicina-estetica, etc.) inject
 * their own canonical hero + H1 via the_content(). Without recognizing
 * nvx_strategy_current_page_key(), the shell would print a second, competing
 * H1 above the theme's own generic hero/title header.
 */

declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );

$GLOBALS['nvx_test_have_posts_done'] = false;
$GLOBALS['nvx_test_content']         = '';
$GLOBALS['nvx_test_has_thumbnail']   = false;
$GLOBALS['nvx_test_is_front_page']   = false;
$GLOBALS['nvx_test_strategy_key']    = null;
$GLOBALS['nvx_test_post_id']         = 42;

function have_posts(): bool {
	if ( $GLOBALS['nvx_test_have_posts_done'] ) {
		return false;
	}
	$GLOBALS['nvx_test_have_posts_done'] = true;
	return true;
}
function the_post(): void {}
function get_post_field( string $field, $post_id ) {
	return $GLOBALS['nvx_test_content'];
}
function get_the_ID(): int {
	return $GLOBALS['nvx_test_post_id'];
}
function the_ID(): void {
	echo $GLOBALS['nvx_test_post_id'];
}
function has_post_thumbnail(): bool {
	return $GLOBALS['nvx_test_has_thumbnail'];
}
function is_front_page(): bool {
	return $GLOBALS['nvx_test_is_front_page'];
}
function is_singular( string $type = '' ): bool {
	return false;
}
function post_class( array $class = array() ): void {
	echo 'class="' . htmlspecialchars( implode( ' ', $class ), ENT_QUOTES ) . '"';
}
function the_post_thumbnail( $size = '', $attr = array() ): void {
	echo '<img class="nvx-media nvx-media--hero nvx-test-thumb" />';
}
function the_title_attribute( array $args = array() ) {
	return 'Test Title';
}
function esc_html_e( string $text, string $domain = 'default' ): void {
	echo $text;
}
function esc_html( string $text ): string {
	return htmlspecialchars( $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );
}
function esc_attr( string $text ): string {
	return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
}
function esc_attr_e( string $text, string $domain = 'default' ): void {
	echo $text;
}
function get_the_category(): array {
	return array();
}
function the_title( string $before = '', string $after = '' ): void {
	echo $before . 'Test Title' . $after;
}
function get_the_date( string $format = '' ) {
	return '2026-07-20';
}
function the_content(): void {
	echo '<p>Body copy.</p>';
}
function nvx_strategy_current_page_key(): ?string {
	return $GLOBALS['nvx_test_strategy_key'];
}

$nvx_page_shell_path = dirname( __DIR__, 2 ) . '/wp-content/themes/nuvanx-medical/template-parts/content/nvx-page-shell.php';

/**
 * Reset per-render state and execute the page shell template, capturing output.
 */
function nvx_render_page_shell( string $path ): string {
	$GLOBALS['nvx_test_have_posts_done'] = false;
	ob_start();
	require $path;
	return (string) ob_get_clean();
}

function nvx_assert( bool $condition, string $message ): void {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
}

// --- Baseline: media present, no content H1/hero, no managed editorial ------

$GLOBALS['nvx_test_content']       = '<p>Plain legacy body copy.</p>';
$GLOBALS['nvx_test_has_thumbnail'] = true;
$GLOBALS['nvx_test_strategy_key']  = null;

$baseline = nvx_render_page_shell( $nvx_page_shell_path );

nvx_assert( false !== strpos( $baseline, 'nvx-page-hero nvx-page-hero--theme' ), 'baseline page with media must render the theme-owned hero' );
nvx_assert( false !== strpos( $baseline, 'class="nvx-page nvx-page--has-hero"' ), 'baseline page with a rendered hero must carry the has-hero class' );
nvx_assert( false !== strpos( $baseline, 'nvx-test-thumb' ), 'baseline theme hero must render the featured image' );

// --- Strategy page: nvx_strategy_current_page_key() returns a key -----------

$GLOBALS['nvx_test_content']       = '<p>Plain legacy body copy.</p>';
$GLOBALS['nvx_test_has_thumbnail'] = true;
$GLOBALS['nvx_test_strategy_key']  = 'why_nuvanx';

$strategy = nvx_render_page_shell( $nvx_page_shell_path );

nvx_assert( false === strpos( $strategy, 'nvx-page-hero nvx-page-hero--theme' ), 'strategy pages must not render the theme-owned hero (avoids a second H1)' );
nvx_assert( false === strpos( $strategy, 'nvx-page__header nvx-section-intro' ), 'strategy pages must not render the theme-owned title-only header either' );
nvx_assert( false === strpos( $strategy, 'nvx-test-thumb' ), 'strategy pages must not render the featured image via the theme shell' );
nvx_assert( false === strpos( $strategy, 'class="nvx-page nvx-page--has-hero"' ), 'strategy pages must not receive the has-hero class from the theme shell' );
nvx_assert( false !== strpos( $strategy, 'class="nvx-page"' ), 'strategy pages must still render the base article class' );
nvx_assert( false !== strpos( $strategy, 'entry-content nvx-page__content nvx-prose' ), 'strategy pages must still render the content wrapper' );
nvx_assert( false !== strpos( $strategy, '<p>Body copy.</p>' ), 'strategy pages must still render the_content() output' );

// --- Regression: strategy helper exists but the current page is not one ----

$GLOBALS['nvx_test_content']       = '<p>Plain legacy body copy.</p>';
$GLOBALS['nvx_test_has_thumbnail'] = true;
$GLOBALS['nvx_test_strategy_key']  = null;

$not_strategy = nvx_render_page_shell( $nvx_page_shell_path );

nvx_assert(
	false !== strpos( $not_strategy, 'nvx-page-hero nvx-page-hero--theme' ),
	'a page that is not in the strategy catalogue must fall back to the theme-owned hero'
);

// --- Without a featured image, the strategy guard must still suppress the ---
// --- title-only header (both hero and title paths gate on managed editorial)

$GLOBALS['nvx_test_content']       = '<p>Plain legacy body copy.</p>';
$GLOBALS['nvx_test_has_thumbnail'] = false;
$GLOBALS['nvx_test_strategy_key']  = 'investment';

$strategy_no_media = nvx_render_page_shell( $nvx_page_shell_path );

nvx_assert(
	false === strpos( $strategy_no_media, 'nvx-page__header nvx-section-intro' ),
	'strategy pages without a featured image must still suppress the theme title-only header'
);
nvx_assert(
	false === strpos( $strategy_no_media, 'nvx-page-hero nvx-page-hero--theme' ),
	'strategy pages without a featured image must not render a theme hero either'
);

fwrite( STDOUT, "Page shell managed-editorial guard tests passed.\n" );