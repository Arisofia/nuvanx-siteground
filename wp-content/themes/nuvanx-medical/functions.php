<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'NVX_THEME_VERSION', '2.0.0-plata-pulida-canonical' );

function nvx_theme_setup() {
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

function nvx_primary_menu_fallback() {
	$items = array(
		array( 'url' => home_url( '/' ), 'label' => __( 'Inicio', 'nuvanx-medical' ) ),
		array(
			'url'      => home_url( '/tratamientos/' ),
			'label'    => __( 'Tratamientos', 'nuvanx-medical' ),
			'children' => array(
				array( 'url' => home_url( '/exion-face/' ), 'label' => 'EXION Face' ),
				array( 'url' => home_url( '/exion-body/' ), 'label' => 'EXION Body' ),
				array( 'url' => home_url( '/exion-fractional/' ), 'label' => 'EXION Fractional' ),
				array( 'url' => home_url( '/emfusion/' ), 'label' => 'EMFUSION' ),
			),
		),
		array( 'url' => home_url( '/equipo-medico/' ), 'label' => __( 'Equipo médico', 'nuvanx-medical' ) ),
		array( 'url' => home_url( '/clinicas-de-medicina-estetica-nuvanx/' ), 'label' => __( 'Clínicas', 'nuvanx-medical' ) ),
		array( 'url' => home_url( '/blog/' ), 'label' => __( 'Blog', 'nuvanx-medical' ) ),
		array( 'url' => home_url( '/contacto/' ), 'label' => __( 'Contacto', 'nuvanx-medical' ) ),
	);

	echo '<ul class="nvx-nav__list">';
	foreach ( $items as $item ) {
		$has_children = ! empty( $item['children'] );
		$li_class     = 'nvx-nav__item' . ( $has_children ? ' menu-item-has-children' : '' );
		
		printf(
			'<li class="%1$s"><a class="nvx-nav__link" href="%2$s">%3$s</a>',
			esc_attr( $li_class ),
			esc_url( $item['url'] ),
			esc_html( $item['label'] )
		);
		
		if ( $has_children ) {
			echo '<ul class="sub-menu">';
			foreach ( $item['children'] as $child ) {
				printf(
					'<li class="nvx-nav__item"><a class="nvx-nav__link" href="%1$s">%2$s</a></li>',
					esc_url( $child['url'] ),
					esc_html( $child['label'] )
				);
			}
			echo '</ul>';
		}
		
		echo '</li>';
	}
	echo '</ul>';
}

function nvx_theme_fonts() {
	$path = get_template_directory() . '/assets/css/nvx-fonts.css';
	$ver  = is_readable( $path ) ? (string) filemtime( $path ) : NVX_THEME_VERSION;
	wp_enqueue_style( 'nvx-fonts', get_template_directory_uri() . '/assets/css/nvx-fonts.css', array(), $ver );
}
add_action( 'wp_enqueue_scripts', 'nvx_theme_fonts', 5 );

function nvx_theme_is_home_page(): bool {
	return is_front_page() || is_page( 9 );
}

/**
 * Post-conversion / thank-you page slugs (hide site closing CTA).
 * Single source of truth — CSS uses body.nvx-hide-closing-cta.
 *
 * @return string[]
 */
function nvx_theme_thank_you_page_slugs(): array {
	return apply_filters(
		'nvx_theme_thank_you_page_slugs',
		array( 'gracias', 'solicitud-recibida', 'thank-you', 'thankyou' )
	);
}

/**
 * Valoración / consulta form page slugs (primary CTA may target #nvx-hubspot-form).
 *
 * @return string[]
 */
function nvx_theme_valoracion_form_page_slugs(): array {
	return apply_filters(
		'nvx_theme_valoracion_form_page_slugs',
		array( 'valoracion', 'consulta-medica', 'consultamedica' )
	);
}

/**
 * Current singular page slug, or empty when not a page request.
 */
function nvx_theme_current_page_slug(): string {
	if ( ! is_page() ) {
		return '';
	}
	return (string) get_post_field( 'post_name', get_queried_object_id() );
}

/**
 * Whether the current page slug is one of the given values.
 *
 * @param string[] $slugs Allowed slugs.
 */
function nvx_theme_is_page_slug_in( array $slugs ): bool {
	$slug = nvx_theme_current_page_slug();
	if ( '' === $slug || array() === $slugs ) {
		return false;
	}
	return in_array( $slug, $slugs, true );
}

/**
 * Whether the current request is a thank-you / post-submit page.
 */
function nvx_theme_is_thank_you_page(): bool {
	return nvx_theme_is_page_slug_in( nvx_theme_thank_you_page_slugs() );
}

/**
 * Whether the current request is a valoración form landing.
 */
function nvx_theme_is_valoracion_form_page(): bool {
	return nvx_theme_is_page_slug_in( nvx_theme_valoracion_form_page_slugs() );
}

/**
 * Site-wide pre-footer valoración band (canonical closing CTA).
 * Same markup on every public page so the close matches treatments/blog/sedes.
 * Only hide on thank-you / post-submit pages (form already completed).
 */
function nvx_theme_show_cta_banner(): bool {
	if ( is_admin() ) {
		return false;
	}

	// Post-conversion only — keep contacto/valoración with the same global close.
	return ! nvx_theme_is_thank_you_page();
}

/**
 * Body hook so footer CSS can hide the closing band from the centralized slug list.
 *
 * @param string[] $classes Body classes.
 * @return string[]
 */
function nvx_theme_cta_body_class( array $classes ): array {
	if ( nvx_theme_is_thank_you_page() ) {
		$classes[] = 'nvx-hide-closing-cta';
	}
	return array_values( array_unique( $classes ) );
}
add_filter( 'body_class', 'nvx_theme_cta_body_class' );

function nvx_asset_version( string $relative_path ): string {
	$path = get_template_directory() . '/' . ltrim( $relative_path, '/' );
	return is_readable( $path ) ? (string) filemtime( $path ) : NVX_THEME_VERSION;
}

function nvx_theme_scripts() {
	$uri = get_template_directory_uri();
	$css = $uri . '/assets/css/';

	wp_enqueue_style( 'nvx-tokens', $css . 'nvx-tokens.css', array( 'nvx-fonts' ), nvx_asset_version( 'assets/css/nvx-tokens.css' ) );
	wp_enqueue_style( 'nvx-base', $css . 'nvx-base.css', array( 'nvx-tokens' ), nvx_asset_version( 'assets/css/nvx-base.css' ) );
	wp_enqueue_style( 'nvx-layout', $css . 'nvx-site-layout.css', array( 'nvx-base' ), nvx_asset_version( 'assets/css/nvx-site-layout.css' ) );
	wp_enqueue_style( 'nvx-components', $css . 'nvx-components.css', array( 'nvx-layout' ), nvx_asset_version( 'assets/css/nvx-components.css' ) );
	wp_enqueue_style( 'nvx-patterns', $css . 'nvx-patterns-editorial.css', array( 'nvx-components' ), nvx_asset_version( 'assets/css/nvx-patterns-editorial.css' ) );
	wp_enqueue_style( 'nvx-header', $css . 'nvx-header.css', array( 'nvx-patterns' ), nvx_asset_version( 'assets/css/nvx-header.css' ) );
	wp_enqueue_style( 'nvx-footer', $css . 'nvx-footer.css', array( 'nvx-header' ), nvx_asset_version( 'assets/css/nvx-footer.css' ) );

	// nvx-brand-home.css owns hero CTA styles (.hero-cta-group, .nvx-home-hero-ctas)
	// used sitewide by nvx_cta_pair_markup(). Load on all pages, not just home.
	wp_enqueue_style( 'nvx-home', $css . 'nvx-brand-home.css', array( 'nvx-footer' ), nvx_asset_version( 'assets/css/nvx-brand-home.css' ) );

	$hero_blackout_dependency = 'nvx-home';
	if ( nvx_theme_is_home_page() ) {
		wp_enqueue_script(
			'nvx-home-video',
			$uri . '/assets/js/nvx-home-video.js',
			array(),
			nvx_asset_version( 'assets/js/nvx-home-video.js' ),
			true
		);
	}

	// Temporary: black opening heroes (toggle NVX_HERO_BLACKOUT or body class filter).
	if ( nvx_theme_hero_blackout_enabled() ) {
		wp_enqueue_style(
			'nvx-hero-blackout',
			$css . 'nvx-hero-blackout.css',
			array( $hero_blackout_dependency ),
			nvx_asset_version( 'assets/css/nvx-hero-blackout.css' )
		);
	}

	wp_enqueue_script( 'nvx-main', $uri . '/assets/js/nvx-main.js', array(), nvx_asset_version( 'assets/js/nvx-main.js' ), true );
}
add_action( 'wp_enqueue_scripts', 'nvx_theme_scripts' );

/**
 * Hero blackout: solid ink opening stages, hide still photos.
 * Keeps home (and any) hero video visible.
 *
 * Default ON until new photography is approved. Opt out with
 * define( 'NVX_HERO_BLACKOUT', false ) or the filter.
 */
function nvx_theme_hero_blackout_enabled(): bool {
	$enabled = true;
	if ( defined( 'NVX_HERO_BLACKOUT' ) ) {
		$enabled = (bool) NVX_HERO_BLACKOUT;
	}
	/**
	 * Filter whether hero blackout is active.
	 *
	 * @param bool $enabled Default true (black heads; video only on home stage).
	 */
	return (bool) apply_filters( 'nvx_theme_hero_blackout_enabled', $enabled );
}

/**
 * Scope blackout CSS under body.nvx-hero-blackout (see nvx-hero-blackout.css).
 *
 * @param string[] $classes Body classes.
 * @return string[]
 */
function nvx_theme_hero_blackout_body_class( array $classes ): array {
	if ( nvx_theme_hero_blackout_enabled() ) {
		$classes[] = 'nvx-hero-blackout';
	}
	return $classes;
}
add_filter( 'body_class', 'nvx_theme_hero_blackout_body_class' );

function nvx_theme_dequeue_wp_core_inline_css() {
	if ( is_admin() ) {
		return;
	}
	foreach ( array( 'global-styles', 'classic-theme-styles', 'wp-block-library', 'wp-block-library-theme', 'core-block-supports', 'wp-img-auto-sizes-contain' ) as $handle ) {
		wp_dequeue_style( $handle );
	}
}
add_action( 'wp_enqueue_scripts', 'nvx_theme_dequeue_wp_core_inline_css', 100 );

remove_action( 'wp_enqueue_scripts', 'wp_enqueue_global_styles' );
remove_action( 'wp_footer', 'wp_enqueue_global_styles', 1 );
remove_action( 'wp_enqueue_scripts', 'wp_enqueue_stored_styles' );
remove_action( 'wp_footer', 'wp_enqueue_stored_styles', 1 );

function nvx_reading_time( $post_id = null ) {
	$post_id = $post_id ?: get_the_ID();
	$content = wp_strip_all_tags( strip_shortcodes( (string) get_post_field( 'post_content', $post_id ) ) );
	$words   = preg_split( '/\s+/u', trim( $content ), -1, PREG_SPLIT_NO_EMPTY );
	$minutes = max( 1, (int) ceil( count( is_array( $words ) ? $words : array() ) / 220 ) );
	return sprintf( _n( '%s min', '%s min', $minutes, 'nuvanx-medical' ), number_format_i18n( $minutes ) );
}

/**
 * Blog archive shows more posts on page 1.
 * Reading settings often keep posts_per_page=6, which hid older articles.
 *
 * Use only $query conditionals here: global is_*() can disagree with this
 * query while pre_get_posts is still building the main loop.
 */
function nvx_blog_pre_get_posts( WP_Query $query ): void {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}

	// Posts page (/blog/): is_home on the query, not the static front page.
	if ( $query->is_home() && ! $query->is_front_page() ) {
		$query->set( 'posts_per_page', 12 );
		$query->set( 'ignore_sticky_posts', true );
	}
}
add_action( 'pre_get_posts', 'nvx_blog_pre_get_posts' );

function nvx_theme_blog_index_markup(): string {
	$query = new WP_Query(
		array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'posts_per_page'      => 12,
			'ignore_sticky_posts' => true,
			'paged'               => max( 1, (int) get_query_var( 'paged' ) ),
		)
	);

	if ( ! $query->have_posts() ) {
		return '<p class="nvx-copy">' . esc_html__( 'No se encontraron artículos.', 'nuvanx-medical' ) . '</p>';
	}

	$output = '<div class="nvx-brand-grid">';
	while ( $query->have_posts() ) {
		$query->the_post();
		// Blog index cards are text-only (no featured photos).
		$output .= '<article class="nvx-brand-card nvx-card nvx-card--blog nvx-card--blog-text">';
		$output .= '<p class="nvx-brand-card__kicker">' . esc_html( get_the_date() ) . '</p>';
		$output .= '<h2 class="nvx-brand-card__title"><a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a></h2>';
		$output .= '<div class="nvx-brand-card__body">' . wp_kses_post( get_the_excerpt() ) . '</div>';
		$output .= '<a href="' . esc_url( get_permalink() ) . '" class="nvx-button nvx-button--secondary">' . esc_html__( 'Leer más', 'nuvanx-medical' ) . '</a>';
		$output .= '</article>';
	}
	$output .= '</div>';
	wp_reset_postdata();
	return $output;
}

add_shortcode( 'nvx_blog_index', 'nvx_theme_blog_index_markup' );

require_once get_template_directory() . '/inc/nvx-hero-and-forms.php';
require_once get_template_directory() . '/inc/nvx-integrations.php';
require_once get_template_directory() . '/inc/nvx-content-presentation.php';
require_once get_template_directory() . '/inc/nvx-valoracion-modal.php';
require_once get_template_directory() . '/inc/nvx-treatments-catalog.php';
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
