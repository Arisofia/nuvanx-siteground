<?php
/**
 * Regression contract for governed blog routes with a stale WordPress query.
 *
 * The requested route remains authoritative when WordPress reports is_404()
 * even though an exact published post exists for the catalogued slug.
 */

define( 'ABSPATH', __DIR__ . '/' );
define( 'OBJECT', 'OBJECT' );

class WP_Post {
	public int $ID;
	public string $post_status;
	public string $post_name;

	public function __construct( int $id, string $slug ) {
		$this->ID          = $id;
		$this->post_status = 'publish';
		$this->post_name   = $slug;
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

$GLOBALS['nvx_test_path'] = '/matriz-diagnostico-facial-estructura-piel-musculo-grasa/';
$GLOBALS['nvx_test_404']  = true;
$GLOBALS['nvx_test_posts'] = array(
	'tratamientos-faciales-sin-cirugia-guia-medica-diagnostico' => new WP_Post( 3310, 'tratamientos-faciales-sin-cirugia-guia-medica-diagnostico' ),
	'matriz-diagnostico-facial-estructura-piel-musculo-grasa'   => new WP_Post( 3334, 'matriz-diagnostico-facial-estructura-piel-musculo-grasa' ),
);
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
function get_page_by_path( $slug ) { return $GLOBALS['nvx_test_posts'][ $slug ] ?? null; }
function get_posts() { return array(); }
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
nvx_document_governance_bind_blog_pre_get_posts( $query );
if ( 3334 !== $query->get( 'p' ) || $query->is_404 || ! $query->is_single || ! $query->is_singular || $query->is_archive ) {
	fwrite( STDERR, 'GOVERNED_BLOG_MAIN_QUERY_REBIND=FAIL' . PHP_EOL );
	exit( 1 );
}

echo 'GOVERNED_BLOG_STALE_CONTEXT=PASS requested_post=3334 neighbouring_post=3310' . PHP_EOL;
