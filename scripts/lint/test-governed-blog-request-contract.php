<?php
/**
 * Regression contract for governed blog routes with a stale WordPress query.
 *
 * The requested route remains authoritative when WordPress reports is_404()
 * or carries a neighbouring post name even though an exact published post
 * exists for the catalogued request slug.
 */

define( 'ABSPATH', __DIR__ . '/' );
define( 'OBJECT', 'OBJECT' );

class WP_Post {
	public int $ID;
	public string $post_status;
	public string $post_name;
	public string $post_type;

	public function __construct( $id_or_row, ?string $slug = null ) {
		if ( is_object( $id_or_row ) ) {
			$this->ID          = (int) ( $id_or_row->ID ?? 0 );
			$this->post_status = (string) ( $id_or_row->post_status ?? '' );
			$this->post_name   = (string) ( $id_or_row->post_name ?? '' );
			$this->post_type   = (string) ( $id_or_row->post_type ?? 'post' );
			return;
		}

		$this->ID          = (int) $id_or_row;
		$this->post_status = 'publish';
		$this->post_name   = (string) $slug;
		$this->post_type   = 'post';
	}
}

class WP_Query {
	public bool $is_page = false;
	public bool $is_single = false;
	public bool $is_singular = false;
	public bool $is_404 = true;
	public bool $is_archive = true;
	public bool $is_home = false;
	private array $values = array();

	public function is_main_query(): bool {
		return true;
	}

	public function set( string $key, $value ): void {
		$this->values[ $key ] = $value;
	}

	public function get( string $key ) {
		return $this->values[ $key ] ?? null;
	}
}

class wpdb {
	public string $posts = 'wp_posts';

	public function prepare( string $query, ...$args ): string {
		unset( $args );
		return $query;
	}

	public function get_var( string $query ) {
		unset( $query );
		return 3334;
	}

	public function get_row( string $query ) {
		unset( $query );
		return $GLOBALS['nvx_test_db_row'] ?? null;
	}
}

function clean_post_cache( $post_id ) {
	// Cache repair function for production implementation
}

function wp_cache_set( $key, $value, $group ) {
	// Cache repair function for production implementation
}

$GLOBALS['nvx_test_path'] = '/matriz-diagnostico-facial-estructura-piel-musculo-grasa/';
$GLOBALS['nvx_test_404']  = true;
$GLOBALS['nvx_test_poison_cache'] = false;
$GLOBALS['nvx_test_posts'] = array(
	'tratamientos-faciales-sin-cirugia-guia-medica-diagnostico' => new WP_Post( 3310, 'tratamientos-faciales-sin-cirugia-guia-medica-diagnostico' ),
	'matriz-diagnostico-facial-estructura-piel-musculo-grasa'   => new WP_Post( 3334, 'matriz-diagnostico-facial-estructura-piel-musculo-grasa' ),
);
$GLOBALS['nvx_test_db_row'] = (object) array(
	'ID'          => 3334,
	'post_status' => 'publish',
	'post_name'   => 'matriz-diagnostico-facial-estructura-piel-musculo-grasa',
	'post_type'   => 'post',
);
$GLOBALS['wpdb'] = new wpdb();
$_SERVER['REQUEST_URI'] = $GLOBALS['nvx_test_path'];

function add_filter() { return true; }
function add_action() { return true; }
function is_admin() { return false; }
function wp_doing_ajax() { return false; }
function is_404() { return (bool) $GLOBALS['nvx_test_404']; }
function is_search() { return false; }
function is_feed() { return false; }
function is_preview() { return false; }
function is_front_page() { return false; }
function is_home() { return false; }
function is_singular() { return false; }
function get_queried_object_id() { return 0; }
function wp_parse_url( $url, $component = -1 ) { return parse_url( $url, $component ); }
function home_url( $path = '' ) { return 'https://nuvanx.com' . $path; }
function sanitize_title( $value ) { return strtolower( trim( (string) $value ) ); }
function get_page_by_path( $slug ) {
	if ( ! empty( $GLOBALS['nvx_test_poison_cache'] ) ) {
		return $GLOBALS['nvx_test_posts']['tratamientos-faciales-sin-cirugia-guia-medica-diagnostico'];
	}
	return $GLOBALS['nvx_test_posts'][ $slug ] ?? null;
}
function get_posts() { return array(); }
function get_post( $post_id ) {
	if ( ! empty( $GLOBALS['nvx_test_poison_cache'] ) ) {
		return $GLOBALS['nvx_test_posts']['tratamientos-faciales-sin-cirugia-guia-medica-diagnostico'];
	}
	return 3334 === (int) $post_id
		? $GLOBALS['nvx_test_posts']['matriz-diagnostico-facial-estructura-piel-musculo-grasa']
		: null;
}
function nvx_seo_is_nonproduction_environment() { return false; }
function nvx_seo_blog_post_metadata_catalog() {
	return array(
		'tratamientos-faciales-sin-cirugia-guia-medica-diagnostico' => array(
			'title' => 'Tratamientos faciales sin cirugía: guía médica | NUVANX',
			'description' => 'Guía de tratamientos faciales.',
		),
		'matriz-diagnostico-facial-estructura-piel-musculo-grasa' => array(
			'title' => 'Matriz de diagnóstico facial | NUVANX Madrid',
			'description' => 'Guía de diagnóstico facial.',
		),
	);
}

require_once dirname( __DIR__, 2 ) . '/wp-content/themes/nuvanx-medical/inc/nvx-document-governance.php';

$title = nvx_document_governance_governed_blog_title( 'Wrong neighbouring title' );
$canonical = nvx_document_governance_canonical_url();
$og_url = nvx_document_governance_governed_blog_opengraph_url( 'https://nuvanx.com/tratamientos-faciales-sin-cirugia-guia-medica-diagnostico/' );

if ( 'Matriz de diagnóstico facial | NUVANX Madrid' !== $title
	|| 'https://nuvanx.com/matriz-diagnostico-facial-estructura-piel-musculo-grasa/' !== $canonical
	|| 'https://nuvanx.com/matriz-diagnostico-facial-estructura-piel-musculo-grasa/' !== $og_url ) {
	fwrite( STDERR, 'GOVERNED_BLOG_STALE_CONTEXT=FAIL' . PHP_EOL );
	exit( 1 );
}

$query = new WP_Query();
$query->set( 'name', 'tratamientos-faciales-sin-cirugia-guia-medica-diagnostico' );
nvx_document_governance_bind_blog_pre_get_posts( $query );
if ( 3334 !== $query->get( 'p' )
	|| 'matriz-diagnostico-facial-estructura-piel-musculo-grasa' !== $query->get( 'name' )
	|| $query->is_404
	|| ! $query->is_single
	|| ! $query->is_singular
	|| $query->is_archive ) {
	fwrite( STDERR, 'GOVERNED_BLOG_MAIN_QUERY_REBIND=FAIL' . PHP_EOL );
	exit( 1 );
}

// The last resolver layer must not re-enter get_post() after a direct SQL hit.
// Simulate a poisoned persistent object cache that maps the requested matrix
// route to neighbouring post 3310 while the database row itself is correct.
$GLOBALS['nvx_test_poison_cache'] = true;
$resolved = nvx_document_governance_get_published_post_by_slug(
	'matriz-diagnostico-facial-estructura-piel-musculo-grasa'
);
$GLOBALS['nvx_test_poison_cache'] = false;

if ( ! ( $resolved instanceof WP_Post )
	|| 3334 !== $resolved->ID
	|| 'matriz-diagnostico-facial-estructura-piel-musculo-grasa' !== $resolved->post_name ) {
	fwrite( STDERR, 'GOVERNED_BLOG_DB_AUTHORITATIVE_FALLBACK=FAIL' . PHP_EOL );
	exit( 1 );
}

// The final single-post entrypoint must resolve the real public path before it
// ever falls back to a potentially stale WP_Query name.
$single_entrypoint = file_get_contents( dirname( __DIR__, 2 ) . '/wp-content/themes/nuvanx-medical/single-post.php' );
$request_path_pos  = is_string( $single_entrypoint ) ? strpos( $single_entrypoint, '$nvx_request_slug = trim' ) : false;
$query_name_pos    = is_string( $single_entrypoint ) ? strpos( $single_entrypoint, '$wp_query->get( \'name\' )' ) : false;
if ( false === $request_path_pos || false === $query_name_pos || $request_path_pos >= $query_name_pos ) {
	fwrite( STDERR, 'GOVERNED_BLOG_ENTRYPOINT_PATH_AUTHORITY=FAIL' . PHP_EOL );
	exit( 1 );
}

echo 'GOVERNED_BLOG_DB_AUTHORITATIVE_FALLBACK=PASS requested_post=3334 poisoned_cache_post=3310' . PHP_EOL;
echo 'GOVERNED_BLOG_STALE_CONTEXT=PASS requested_post=3334 neighbouring_post=3310 path_authority=REQUEST_URI' . PHP_EOL;