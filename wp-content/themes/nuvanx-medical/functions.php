<?php
/**
 * NUVANX Medical theme bootstrap.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'NVX_THEME_VERSION', '2.0.0-plata-pulida-canonical' );

/** Register theme supports and navigation locations. */
function nvx_theme_setup(): void {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'custom-logo' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );

	register_nav_menus(
		array(
			'primary' => esc_html__( 'Primary', 'nuvanx-medical' ),
			'footer'  => esc_html__( 'Footer', 'nuvanx-medical' ),
			'legal'   => esc_html__( 'Legal', 'nuvanx-medical' ),
		)
	);
}
add_action( 'after_setup_theme', 'nvx_theme_setup' );

/** Enqueue the canonical font stylesheet once. */
function nvx_theme_fonts(): void {
	$path = get_template_directory() . '/assets/css/nvx-fonts.css';
	$ver  = is_readable( $path ) ? (string) filemtime( $path ) : NVX_THEME_VERSION;

	wp_enqueue_style( 'nvx-fonts', get_template_directory_uri() . '/assets/css/nvx-fonts.css', array(), $ver );
}
add_action( 'wp_enqueue_scripts', 'nvx_theme_fonts', 5 );

/** Whether the current request is the configured front page. */
function nvx_theme_is_home_page(): bool {
	return is_front_page();
}

/**
 * Provides the slugs used to identify thank-you pages.
 *
 * @return array The filterable thank-you page slugs.
 */
function nvx_theme_thank_you_page_slugs(): array {
	return apply_filters( 'nvx_theme_thank_you_page_slugs', array( 'gracias', 'solicitud-recibida', 'thank-you', 'thankyou' ) );
}

/**
 * Provides the slugs used by valoración form pages.
 *
 * @return array The filterable valoración form page slugs.
 */
function nvx_theme_valoracion_form_page_slugs(): array {
	return apply_filters( 'nvx_theme_valoracion_form_page_slugs', array( 'valoracion', 'consulta-medica', 'consultamedica' ) );
}

/** Current singular page slug, or an empty string outside page requests. */
function nvx_theme_current_page_slug(): string {
	if ( ! is_page() ) {
		return '';
	}
	return (string) get_post_field( 'post_name', get_queried_object_id() );
}

/**
 * Determines whether the current page matches one of the provided slugs.
 *
 * @param array $slugs Page slugs to compare with the current page.
 * @return bool `true` if the current page slug is included in the provided values, `false` otherwise.
 */
function nvx_theme_is_page_slug_in( array $slugs ): bool {
	$slug = nvx_theme_current_page_slug();
	if ( '' === $slug || array() === $slugs ) {
		return false;
	}
	return in_array( $slug, $slugs, true );
}

/**
 * Determines whether the current page is a post-conversion thank-you page.
 *
 * @return bool `true` if the current page matches a configured thank-you page slug, `false` otherwise.
 */
function nvx_theme_is_thank_you_page(): bool {
	return nvx_theme_is_page_slug_in( nvx_theme_thank_you_page_slugs() );
}

/** Whether the current request is a valoración form page. */
function nvx_theme_is_valoracion_form_page(): bool {
	return nvx_theme_is_page_slug_in( nvx_theme_valoracion_form_page_slugs() );
}

/**
 * Determines whether the shared closing CTA banner should be displayed.
 *
 * @return bool `true` when the banner should be displayed, `false` in the admin area, on a thank-you page, or when the page provides its own complete markup.
 */
function nvx_theme_show_cta_banner(): bool {
	if ( is_admin() || nvx_theme_is_thank_you_page() ) {
		return false;
	}
	if ( function_exists( 'nvx_theme_owns_complete_page_markup' ) && nvx_theme_owns_complete_page_markup() ) {
		return false;
	}
	return true;
}

/** Filemtime asset version with a theme-version fallback. */
function nvx_asset_version( string $relative_path ): string {
	$path = get_template_directory() . '/' . ltrim( $relative_path, '/' );
	return is_readable( $path ) ? (string) filemtime( $path ) : NVX_THEME_VERSION;
}

/** Enqueue the canonical design-system stack and page-owned assets. */
function nvx_theme_scripts(): void {
	$uri = get_template_directory_uri();
	$css = $uri . '/assets/css/';

	wp_enqueue_style( 'nvx-tokens', $css . 'nvx-tokens.css', array( 'nvx-fonts' ), nvx_asset_version( 'assets/css/nvx-tokens.css' ) );
	wp_enqueue_style( 'nvx-base', $css . 'nvx-base.css', array( 'nvx-tokens' ), nvx_asset_version( 'assets/css/nvx-base.css' ) );
	wp_enqueue_style( 'nvx-layout', $css . 'nvx-site-layout.css', array( 'nvx-base' ), nvx_asset_version( 'assets/css/nvx-site-layout.css' ) );
	wp_enqueue_style( 'nvx-components', $css . 'nvx-components.css', array( 'nvx-layout' ), nvx_asset_version( 'assets/css/nvx-components.css' ) );
	wp_enqueue_style( 'nvx-patterns', $css . 'nvx-patterns-editorial.css', array( 'nvx-components' ), nvx_asset_version( 'assets/css/nvx-patterns-editorial.css' ) );
	wp_enqueue_style( 'nvx-header', $css . 'nvx-header.css', array( 'nvx-patterns' ), nvx_asset_version( 'assets/css/nvx-header.css' ) );
	wp_enqueue_style( 'nvx-footer', $css . 'nvx-footer.css', array( 'nvx-header' ), nvx_asset_version( 'assets/css/nvx-footer.css' ) );
	wp_enqueue_style( 'nvx-home', $css . 'nvx-brand-home.css', array( 'nvx-footer' ), nvx_asset_version( 'assets/css/nvx-brand-home.css' ) );

	if ( nvx_theme_is_home_page() ) {
		wp_enqueue_style( 'nvx-home-v3', $css . 'nvx-home-v3.css', array( 'nvx-home' ), nvx_asset_version( 'assets/css/nvx-home-v3.css' ) );
		wp_enqueue_script( 'nvx-home-video', $uri . '/assets/js/nvx-home-video.js', array(), nvx_asset_version( 'assets/js/nvx-home-video.js' ), true );
	}

	if ( function_exists( 'nvx_theme_is_treatments_hub' ) && nvx_theme_is_treatments_hub() ) {
		wp_enqueue_style( 'nvx-portfolio-hub', $css . 'nvx-portfolio-hub.css', array( 'nvx-components' ), nvx_asset_version( 'assets/css/nvx-portfolio-hub.css' ) );
	}

	if ( nvx_theme_hero_blackout_enabled() ) {
		wp_enqueue_style( 'nvx-hero-blackout', $css . 'nvx-hero-blackout.css', array( 'nvx-home' ), nvx_asset_version( 'assets/css/nvx-hero-blackout.css' ) );
	}

	wp_enqueue_script( 'nvx-main', $uri . '/assets/js/nvx-main.js', array(), nvx_asset_version( 'assets/js/nvx-main.js' ), true );
}
add_action( 'wp_enqueue_scripts', 'nvx_theme_scripts' );

/** Whether the sitewide ink hero treatment is enabled. */
function nvx_theme_hero_blackout_enabled(): bool {
	$enabled = true;
	if ( defined( 'NVX_HERO_BLACKOUT' ) ) {
		$enabled = (bool) NVX_HERO_BLACKOUT;
	}
	return (bool) apply_filters( 'nvx_theme_hero_blackout_enabled', $enabled );
}

/**
 * Adds the hero blackout state class when the feature is enabled.
 *
 * @param array $classes Existing body classes.
 * @return array Body classes with duplicates removed.
 */
function nvx_theme_hero_blackout_body_class( array $classes ): array {
	if ( nvx_theme_hero_blackout_enabled() ) {
		$classes[] = 'nvx-hero-blackout';
	}
	return array_values( array_unique( $classes ) );
}
add_filter( 'body_class', 'nvx_theme_hero_blackout_body_class' );

/**
 * Estimates the reading time for an editorial post.
 *
 * @param int|null $post_id The post ID, or the current post when omitted.
 * @return string The estimated reading time in localized minutes.
 */
function nvx_reading_time( $post_id = null ): string {
	$post_id = $post_id ?: get_the_ID();
	$content = wp_strip_all_tags( strip_shortcodes( (string) get_post_field( 'post_content', $post_id ) ) );
	$words   = preg_split( '/\s+/u', trim( $content ), -1, PREG_SPLIT_NO_EMPTY );
	$minutes = max( 1, (int) ceil( count( is_array( $words ) ? $words : array() ) / 220 ) );
	return sprintf( _n( '%s min', '%s min', $minutes, 'nuvanx-medical' ), number_format_i18n( $minutes ) );
}

/**
 * Configures the main blog index query.
 *
 * @param WP_Query $query The query being prepared.
 */
function nvx_blog_pre_get_posts( WP_Query $query ): void {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}
	if ( $query->is_home() && ! $query->is_front_page() ) {
		$query->set( 'posts_per_page', 12 );
		$query->set( 'ignore_sticky_posts', true );
	}
}
add_action( 'pre_get_posts', 'nvx_blog_pre_get_posts' );

/**
 * Renders the blog index shortcode markup.
 *
 * @return string The rendered blog listing, or a localized message when no posts are available.
 */
function nvx_theme_blog_index_markup(): string {
	$excluded_post_ids = function_exists( 'nvx_quarantined_comparison_post_ids' ) ? nvx_quarantined_comparison_post_ids() : array();
	$query = new WP_Query(
		array(
			'post_type' => 'post',
			'post_status' => 'publish',
			'posts_per_page' => 12,
			'ignore_sticky_posts' => true,
			'post__not_in' => $excluded_post_ids,
			'paged' => max( 1, (int) get_query_var( 'paged' ) ),
		)
	);
	if ( ! $query->have_posts() ) {
		return '<p class="nvx-copy">' . esc_html__( 'No se encontraron artículos.', 'nuvanx-medical' ) . '</p>';
	}
	$output = '<div class="nvx-brand-grid">';
	while ( $query->have_posts() ) {
		$query->the_post();
		$output .= '<article class="nvx-brand-card nvx-card nvx-card--blog nvx-card--blog-text">';
		$output .= '<p class="nvx-brand-card__kicker">' . esc_html( get_the_date() ) . '</p>';
		$output .= '<h2 class="nvx-brand-card__title"><a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a></h2>';
		$output .= '<div class="nvx-brand-card__body">' . wp_kses_post( get_the_excerpt() ) . '</div>';
		$output .= '<a href="' . esc_url( get_permalink() ) . '" class="nvx-button nvx-button--secondary">' . esc_html__( 'Leer más', 'nuvanx-medical' ) . '</a></article>';
	}
	$output .= '</div>';
	wp_reset_postdata();
	return $output;
}
add_shortcode( 'nvx_blog_index', 'nvx_theme_blog_index_markup' );

require_once get_template_directory() . '/inc/nvx-hero-and-forms.php';
require_once get_template_directory() . '/inc/nvx-integrations.php';
require_once get_template_directory() . '/inc/nvx-native-style-governance.php';
require_once get_template_directory() . '/inc/nvx-treatment-hub-schema.php';
require_once get_template_directory() . '/inc/nvx-content-presentation.php';
require_once get_template_directory() . '/inc/nvx-valoracion-modal.php';
require_once get_template_directory() . '/inc/nvx-portfolio-hub.php';
require_once get_template_directory() . '/inc/nvx-protocol-hub.php';
require_once get_template_directory() . '/inc/nvx-protocol-pages.php';
require_once get_template_directory() . '/inc/nvx-signature-phase-pages.php';
require_once get_template_directory() . '/inc/nvx-endolift-page.php';
require_once get_template_directory() . '/inc/nvx-endolaser-page.php';
require_once get_template_directory() . '/inc/nvx-co2-page.php';
require_once get_template_directory() . '/inc/nvx-btl-detail-pages.php';
require_once get_template_directory() . '/inc/nvx-equipo-page.php';
require_once get_template_directory() . '/inc/nvx-nosotros-page.php';
require_once get_template_directory() . '/inc/nvx-contacto-valoracion-page.php';
require_once get_template_directory() . '/inc/nvx-laser-medicine-page.php';
require_once get_template_directory() . '/inc/nvx-aesthetic-medicine-page.php';
require_once get_template_directory() . '/inc/nvx-clinics-hub.php';
require_once get_template_directory() . '/inc/nvx-dr-rivera-page.php';
require_once get_template_directory() . '/inc/nvx-que-exigir-page.php';
require_once get_template_directory() . '/inc/nvx-faq-catalog.php';
require_once get_template_directory() . '/inc/nvx-evidence-panel.php';
