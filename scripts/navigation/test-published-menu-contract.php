<?php
/**
 * Runtime contract for published-only primary navigation.
 */

declare(strict_types=1);

define( 'ABSPATH', __DIR__ );
define( 'OBJECT', 'OBJECT' );

final class WP_Post {
	public int $ID;
	public string $post_status;
	public string $post_name;

	public function __construct( int $id, string $status, string $slug ) {
		$this->ID          = $id;
		$this->post_status = $status;
		$this->post_name   = $slug;
	}
}

$GLOBALS['nvx_test_pages'] = array(
	'exion-face'       => new WP_Post( 101, 'publish', 'exion-face' ),
	'exion-body'       => new WP_Post( 102, 'draft', 'exion-body' ),
	'exion-fractional' => null,
	'emfusion'         => new WP_Post( 104, 'publish', 'emfusion' ),
);

function add_filter( ...$args ): void {}
function apply_filters( string $hook, $value ) { return $value; }
function __( string $text, string $domain = '' ): string { return $text; }
function esc_attr( string $value ): string { return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' ); }
function esc_url( string $value ): string { return $value; }
function esc_html( string $value ): string { return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' ); }
function home_url( string $path = '/' ): string { return 'https://example.test' . $path; }
function untrailingslashit( string $value ): string { return rtrim( $value, '/' ); }
function get_page_by_path( string $slug, string $output = OBJECT, string $type = 'page' ) {
	return $GLOBALS['nvx_test_pages'][ $slug ] ?? null;
}
function get_post_status( WP_Post $page ): string { return $page->post_status; }
function get_permalink( WP_Post $page ): string { return 'https://example.test/' . $page->post_name . '/'; }

require dirname( __DIR__, 2 ) . '/wp-content/themes/nuvanx-medical/inc/nvx-navigation-filters.php';

function nvx_assert( bool $condition, string $message ): void {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
}

$published = nvx_navigation_published_treatments();
nvx_assert( array_keys( $published ) === array( 'exion-face', 'emfusion' ), 'catalogue must contain only published pages' );
nvx_assert( ! isset( $published['exion-body'] ), 'draft EXION Body must be excluded' );
nvx_assert( ! isset( $published['exion-fractional'] ), 'missing EXION Fractional must be excluded' );

$fallback = nvx_navigation_primary_fallback(
	array(
		'echo'       => false,
		'menu_class' => 'nvx-test-menu',
		'menu_id'    => 'nvx-test-menu-id',
	)
);
nvx_assert( is_string( $fallback ), 'fallback must return HTML when echo=false' );
nvx_assert( false !== strpos( $fallback, 'class="nvx-test-menu"' ), 'fallback must respect the caller menu class' );
nvx_assert( false !== strpos( $fallback, 'id="nvx-test-menu-id"' ), 'fallback must respect the caller menu id' );
nvx_assert( false !== strpos( $fallback, '/exion-face/' ), 'published EXION Face must be present in fallback' );
nvx_assert( false !== strpos( $fallback, '/emfusion/' ), 'published EMFUSION must be present in fallback' );
nvx_assert( false === strpos( $fallback, '/exion-body/' ), 'draft EXION Body must not be present in fallback' );
nvx_assert( false === strpos( $fallback, '/exion-fractional/' ), 'missing EXION Fractional must not be present in fallback' );

$parent                   = new stdClass();
$parent->ID               = 10;
$parent->title            = 'Tratamientos';
$parent->url              = 'https://example.test/tratamientos/';
$parent->classes          = array();
$parent->menu_order       = 1;
$parent->menu_item_parent = 0;

$top_level_emfusion                   = new stdClass();
$top_level_emfusion->ID               = 11;
$top_level_emfusion->title            = 'EMFUSION destacado';
$top_level_emfusion->url              = 'https://example.test/emfusion/';
$top_level_emfusion->classes          = array();
$top_level_emfusion->menu_order       = 2;
$top_level_emfusion->menu_item_parent = 0;

$existing_child                   = new stdClass();
$existing_child->ID               = 12;
$existing_child->title            = 'EXION Face';
$existing_child->url              = 'https://example.test/exion-face/';
$existing_child->classes          = array();
$existing_child->menu_order       = 1;
$existing_child->menu_item_parent = 10;

$args                 = new stdClass();
$args->theme_location = 'primary';
$result               = nvx_add_exion_to_tratamientos_menu( array( $parent, $top_level_emfusion, $existing_child ), $args );

nvx_assert( 4 === count( $result ), 'published EMFUSION must still be injected beneath Tratamientos when linked elsewhere' );
$children = array_values(
	array_filter(
		$result,
		static fn( $item ): bool => isset( $item->menu_item_parent ) && 10 === (int) $item->menu_item_parent
	)
);
$child_urls = array_map( static fn( $item ): string => (string) $item->url, $children );
nvx_assert( 1 === count( array_filter( $child_urls, static fn( string $url ): bool => false !== strpos( $url, '/exion-face/' ) ) ), 'existing EXION Face child must not be duplicated' );
nvx_assert( 1 === count( array_filter( $child_urls, static fn( string $url ): bool => false !== strpos( $url, '/emfusion/' ) ) ), 'published EMFUSION must be injected once beneath Tratamientos' );
nvx_assert( 0 === count( array_filter( $child_urls, static fn( string $url ): bool => false !== strpos( $url, '/exion-body/' ) ) ), 'draft EXION Body must never be injected' );
nvx_assert( in_array( 'menu-item-has-children', $parent->classes, true ), 'parent must retain dropdown class after injection' );

$desktop_args = nvx_navigation_filter_menu_args( array( 'theme_location' => 'primary', 'fallback_cb' => 'legacy' ) );
nvx_assert( 'nvx_navigation_primary_fallback' === $desktop_args['fallback_cb'], 'enabled primary fallback must use the published-route-aware callback' );

$mobile_args = nvx_navigation_filter_menu_args(
	array(
		'theme_location' => 'primary',
		'fallback_cb'    => false,
		'menu_class'     => 'nvx-mobile-nav__list',
	)
);
nvx_assert( false === $mobile_args['fallback_cb'], 'explicit mobile fallback opt-out must be preserved' );
nvx_assert( 'nvx-mobile-nav__list' === $mobile_args['menu_class'], 'mobile menu class must remain unchanged' );

echo "PASS: published navigation route contract\n";
