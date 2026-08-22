<?php
/**
 * Execute vendor-image helpers and inspect the public templates they protect.
 *
 * @package nuvanx-medical
 */

declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );

$root = dirname( __DIR__, 2 );

$fail = static function ( string $message ): void {
	fwrite( STDERR, 'EDITORIAL_VENDOR_IMAGES=FAIL ' . $message . PHP_EOL );
	exit( 1 );
};

$GLOBALS['nvx_test_path'] = '/tratamientos/';

function add_filter( $hook_name = null, $callback = null, $priority = 10, $accepted_args = 1 ) {
	unset( $hook_name, $callback, $priority, $accepted_args );
	return true;
}
function add_action( $hook_name = null, $callback = null, $priority = 10, $accepted_args = 1 ) {
	unset( $hook_name, $callback, $priority, $accepted_args );
	return true;
}
function remove_action( $hook_name = null, $callback = null, $priority = 10 ) {
	unset( $hook_name, $callback, $priority );
	return true;
}
function is_admin(): bool {
	return false;
}
function wp_doing_ajax(): bool {
	return false;
}
function is_page( $page = '' ): bool {
	unset( $page );
	return false;
}
function is_singular( $post_types = '' ): bool {
	unset( $post_types );
	return false;
}
function get_queried_object_id(): int {
	return 0;
}
function get_post_field( $field, $post = null ) {
	unset( $field, $post );
	return '';
}
function esc_url( $value ): string {
	return (string) $value;
}
function esc_attr( $value ): string {
	return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' );
}
function esc_html( $value ): string {
	return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' );
}
function esc_html__( $text, $domain = 'default' ): string {
	unset( $domain );
	return (string) $text;
}
function __( $text, $domain = 'default' ): string {
	unset( $domain );
	return (string) $text;
}
function wp_parse_url( $url, $component = -1 ) {
	return parse_url( $url, $component );
}
function content_url( $path = '' ): string {
	return 'https://staging2.nuvanx.com/wp-content/' . ltrim( (string) $path, '/' );
}
function get_template_directory(): string {
	return dirname( __DIR__, 2 ) . '/wp-content/themes/nuvanx-medical';
}
function get_template_directory_uri(): string {
	return 'https://staging2.nuvanx.com/wp-content/themes/nuvanx-medical';
}
function trailingslashit( $value ): string {
	return rtrim( (string) $value, '/\\' ) . '/';
}
function untrailingslashit( $value ): string {
	return rtrim( (string) $value, '/\\' );
}
function wp_upload_dir(): array {
	$dir = dirname( __DIR__, 2 ) . '/wp-content/uploads';
	return array(
		'path'    => $dir,
		'url'     => 'https://staging2.nuvanx.com/wp-content/uploads',
		'subdir'  => '',
		'basedir' => $dir,
		'baseurl' => 'https://staging2.nuvanx.com/wp-content/uploads',
		'error'   => false,
	);
}
function get_attached_file( $attachment_id ): string {
	$path = sys_get_temp_dir() . '/nvx-editorial-partner-' . (int) $attachment_id . '.webp';
	if ( ! is_file( $path ) ) {
		file_put_contents( $path, 'webp' );
	}
	return $path;
}
function wp_get_attachment_url( $attachment_id ): string {
	return 'https://staging2.nuvanx.com/wp-content/uploads/partner-' . (int) $attachment_id . '.webp';
}
function nvx_schema_current_path( int $page_id = 0 ): string {
	unset( $page_id );
	return (string) ( $GLOBALS['nvx_test_path'] ?? '/' );
}

class WP_Query {
	public function is_main_query(): bool {
		return false;
	}

	public function is_search(): bool {
		return false;
	}
}

require $root . '/wp-content/themes/nuvanx-medical/inc/nvx-constants.php';
require $root . '/wp-content/themes/nuvanx-medical/inc/nvx-gbp-local.php';
require $root . '/wp-content/themes/nuvanx-medical/inc/nvx-page-render-helpers.php';
require $root . '/wp-content/themes/nuvanx-medical/inc/nvx-treatments-catalog.php';
require $root . '/wp-content/themes/nuvanx-medical/inc/nvx-blog-system.php';
require $root . '/wp-content/themes/nuvanx-medical/inc/nvx-bridal-page.php';
require $root . '/wp-content/themes/nuvanx-medical/inc/nvx-authentic-page-photography.php';

$vendor_img = '<img src="https://staging2.nuvanx.com/wp-content/uploads/2026/07/btl-exilite-ipl-madrid.webp" alt="Tratamiento BTL EXILITE IPL en Madrid">';
$lazy_vendor = '<img src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="https://staging2.nuvanx.com/wp-content/uploads/2026/02/deka.webp" alt="DEKA">';
$consulta    = '<img src="https://staging2.nuvanx.com/wp-content/uploads/2026/07/consulta-medica-personalizada-nuvanx-madrid.webp" alt="Consulta médica personalizada en NUVANX">';
$copy        = '<p>Endolift facial y BTL EXION se indican tras valoración médica en Madrid.</p>';

if ( ! nvx_public_html_is_vendor_image( $vendor_img ) ) {
	$fail( 'detector missed BTL EXILITE src/alt' );
}
if ( ! nvx_public_html_is_vendor_image( $lazy_vendor ) ) {
	$fail( 'detector missed lazy data-src DEKA logo' );
}
if ( nvx_public_html_is_vendor_image( $consulta ) ) {
	$fail( 'detector flagged an approved consultation photo' );
}
if ( nvx_public_html_is_vendor_image( $copy ) ) {
	$fail( 'detector treated technological copy as a vendor image' );
}

$mixed = '<section>' . $copy . '<figure class="nvx-brand-hero__media">' . $vendor_img . '</figure>' . $consulta . '</section>';
$stripped = nvx_public_strip_vendor_images( $mixed );
if ( false !== strpos( $stripped, 'btl-exilite-ipl-madrid.webp' ) || false !== strpos( $stripped, 'alt="Tratamiento BTL' ) ) {
	$fail( 'stripper left BTL hero markup in place' );
}
if ( false === strpos( $stripped, 'consulta-medica-personalizada-nuvanx-madrid.webp' ) ) {
	$fail( 'stripper removed the approved consultation photo' );
}
if ( false === strpos( $stripped, 'Endolift facial y BTL EXION se indican' ) ) {
	$fail( 'stripper removed technological copy' );
}

$vendor_hero = '<figure class="nvx-brand-hero__media">' . $vendor_img . '</figure>';
$own_hero    = '<figure class="nvx-brand-hero__media"><img src="https://staging2.nuvanx.com/wp-content/uploads/2026/06/nvx-co2-hero-760.webp" alt="NUVANX — Láser CO₂ fraccionado"></figure>';
if ( '' !== nvx_page_extract_brand_hero_media( $vendor_hero ) ) {
	$fail( 'hero extract preserved vendor media' );
}
if ( $own_hero !== nvx_page_extract_brand_hero_media( $own_hero ) ) {
	$fail( 'hero extract dropped authorized CO2 media' );
}

if ( '' !== nvx_public_filter_vendor_post_thumbnail( $vendor_img ) ) {
	$fail( 'thumbnail filter kept a vendor featured image' );
}
if ( $consulta !== nvx_public_filter_vendor_post_thumbnail( $consulta ) ) {
	$fail( 'thumbnail filter dropped an approved featured image' );
}

$GLOBALS['nvx_test_path'] = '/btl-exilite-ipl-madrid/';
$abdomen = '<img src="https://staging2.nuvanx.com/wp-content/uploads/2026/06/laser-medico-nuvanx-madrid.webp" alt="Laserlipólisis médica en NUVANX Madrid">';
if ( $abdomen === nvx_public_strip_vendor_images( $abdomen ) ) {
	$fail( 'abdomen asset remained on an IPL route' );
}
$GLOBALS['nvx_test_path'] = '/endolaser-corporal-grasa-localizada/';
if ( $abdomen !== nvx_public_strip_vendor_images( $abdomen ) ) {
	$fail( 'abdomen asset was stripped from a corporal route' );
}

$partner_labels = array_map(
	static function ( array $partner ): string {
		return (string) $partner['label'];
	},
	nvx_treatments_partner_assets()
);
foreach ( array( 'DEKA', 'BTL', 'Endolift', 'EXION', 'Eufoton' ) as $banned ) {
	if ( in_array( $banned, $partner_labels, true ) ) {
		$fail( 'tratamientos partner catalog still lists ' . $banned );
	}
}

$cloud = nvx_treatments_logo_cloud_markup();
if ( preg_match( '/deka|btl|endolift|exion|eufoton/i', $cloud ) ) {
	$fail( 'tratamientos logo cloud still emits vendor markup: ' . $cloud );
}
if ( false === strpos( $cloud, 'Teoxane' ) ) {
	$fail( 'tratamientos logo cloud dropped an authorized laboratory mark' );
}

$blog_catalog = nvx_blog_named_image_catalog();
$blog_blob    = json_encode( $blog_catalog, JSON_UNESCAPED_UNICODE );
if ( ! is_string( $blog_blob ) ) {
	$fail( 'blog catalog did not encode' );
}
foreach ( array( 'Endolift-ISO9001-Laser', 'endolift-lasemar-1500-eufoton', 'BTL-Exion-Mobile-Version', 'SmartLipo-for-Laserlipolysis-DEKA', 'Protocolo-Endolift-Thermage', '04-endolift.jpg' ) as $stem ) {
	if ( false !== strpos( $blog_blob, $stem ) ) {
		$fail( 'blog catalog still references ' . $stem );
	}
}
foreach ( $blog_catalog as $asset ) {
	if ( '' === trim( (string) ( $asset['alt'] ?? '' ) ) ) {
		$fail( 'blog named image ' . (string) ( $asset['id'] ?? '?' ) . ' is missing alt' );
	}
}

$bridal = nvx_bridal_gallery_markup();
if ( false !== strpos( $bridal, 'Protocolo-Endolift-Thermage-Morpheus8-ultherapy' ) ) {
	$fail( 'bridal studio still emits the protocol collage' );
}
if ( false === strpos( $bridal, 'Box-Clinica-Novias' ) || false === strpos( $bridal, 'Papada-novias' ) ) {
	$fail( 'bridal studio dropped approved existing plates' );
}

$registry = nvx_authentic_page_photo_registry();
$registry_ids = array();
foreach ( $registry as $entry ) {
	foreach ( (array) ( $entry['images'] ?? array() ) as $image ) {
		$registry_ids[] = (int) ( $image['id'] ?? 0 );
	}
}
foreach ( array( 2113, 2432, 2470 ) as $banned_id ) {
	if ( in_array( $banned_id, $registry_ids, true ) ) {
		$fail( 'authentic registry still maps attachment ' . $banned_id );
	}
}

echo 'EDITORIAL_VENDOR_IMAGES=PASS cases=12' . PHP_EOL;
