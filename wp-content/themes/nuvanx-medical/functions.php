<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'NVX_THEME_VERSION', '1.9.3-chrome-cleanup-header-footer-20260715' );

const NVX_BRAND_TREATMENT_PAGE_IDS = array( 1241, 1200, 2017 );

function nvx_theme_setup() {
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
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


/**
 * Navegación de emergencia cuando no existe un menú principal asignado.
 *
 * @param array|object $args Argumentos recibidos desde wp_nav_menu().
 * @return void
 */
function nvx_primary_menu_fallback( $args = array() ) {
	$pages = get_pages(
		array(
			'parent'      => 0,
			'post_status' => 'publish',
			'sort_column' => 'menu_order,post_title',
		)
	);

	if ( empty( $pages ) ) {
		return;
	}

	echo '<ul class="nvx-nav__list" role="menubar">';

	foreach ( $pages as $page ) {
		printf(
			'<li class="nvx-nav__item" role="none"><a class="nvx-nav__link" role="menuitem" href="%1$s">%2$s</a></li>',
			esc_url( get_permalink( $page ) ),
			esc_html( get_the_title( $page ) )
		);
	}

	echo '</ul>';
}

function nvx_theme_fonts() {
    wp_enqueue_style(
        'nvx-fonts',
        'https://fonts.googleapis.com/css2?family=Bodoni+Moda:ital,opsz,wght@0,6..96,400;0,6..96,500;1,6..96,400&family=Manrope:wght@200;300;400;500;600&family=Pinyon+Script&display=swap',
        array(),
        null
    );
}
add_action( 'wp_enqueue_scripts', 'nvx_theme_fonts', 5 );

function nvx_theme_is_home_page(): bool {
	if ( is_front_page() ) {
		return true;
	}
	if ( is_page( 9 ) ) {
		return true;
	}
	$queried = get_queried_object();
	return $queried instanceof WP_Post && in_array( $queried->post_name, array( 'home', 'inicio' ), true );
}

function nvx_theme_is_valoracion_page(): bool {
	if ( is_page_template( 'templates/page-landing-valoracion.php' ) ) {
		return true;
	}
	if ( is_page( 2636 ) ) {
		return true;
	}
	$queried = get_queried_object();
	return $queried instanceof WP_Post && 'valoracion' === $queried->post_name;
}

function nvx_theme_is_contacto_page(): bool {
	if ( is_page_template( 'templates/page-contacto.php' ) ) {
		return true;
	}
	if ( is_page( 14 ) ) {
		return true;
	}
	$queried = get_queried_object();
	return $queried instanceof WP_Post && 'contacto' === $queried->post_name;
}

function nvx_theme_is_p0_shell_page(): bool {
	return nvx_theme_is_valoracion_page() || nvx_theme_is_contacto_page();
}

function nvx_theme_is_brand_treatment(): bool {
	return is_page( NVX_BRAND_TREATMENT_PAGE_IDS );
}

function nvx_theme_uses_brand_system(): bool {
	return ! nvx_theme_is_p0_shell_page()
		&& ! nvx_theme_is_home_page()
		&& ! nvx_theme_is_brand_treatment();
}


/**
 * Calcula el tiempo estimado de lectura de una entrada.
 *
 * @param int|null $post_id ID opcional de la entrada.
 * @return string Tiempo de lectura localizado.
 */
function nvx_reading_time( $post_id = null ) {
	$post_id = $post_id ?: get_the_ID();
	$content = get_post_field( 'post_content', $post_id );

	if ( ! is_string( $content ) || '' === trim( $content ) ) {
		return esc_html__( '1 min', 'nuvanx-medical' );
	}

	$content = strip_shortcodes( $content );
	$content = wp_strip_all_tags( $content );
	$words   = preg_split( '/\s+/u', trim( $content ), -1, PREG_SPLIT_NO_EMPTY );
	$count   = is_array( $words ) ? count( $words ) : 0;
	$minutes = max( 1, (int) ceil( $count / 220 ) );

	return sprintf(
		_n( '%s min', '%s min', $minutes, 'nuvanx-medical' ),
		number_format_i18n( $minutes )
	);
}

function nvx_theme_scripts() {
	$ver = NVX_THEME_VERSION;
	$css = get_template_directory_uri() . '/assets/css/';
	$dir = get_template_directory() . '/assets/css/';

	$asset_version = static function ( $relative_path ) use ( $dir, $ver ) {
		$absolute_path = $dir . $relative_path;

		return is_readable( $absolute_path )
			? (string) filemtime( $absolute_path )
			: $ver;
	};

	$is_front_page = nvx_theme_is_home_page();
	$is_p0         = nvx_theme_is_p0_shell_page();
	$is_treatment  = nvx_theme_is_brand_treatment();

	$is_post_context = is_home()
		|| is_archive()
		|| is_search()
		|| is_singular( 'post' );

	$is_form_page = is_page( array( 14, 2636 ) )
		|| is_page_template( 'templates/page-contacto.php' )
		|| is_page_template( 'templates/page-landing-valoracion.php' );

	$current_post = is_singular()
		? get_queried_object()
		: null;

	if (
		! $is_form_page
		&& $current_post instanceof WP_Post
		&& is_string( $current_post->post_content )
	) {
		$content = $current_post->post_content;

		$is_form_page = false !== stripos( $content, 'hs-form' )
			|| false !== stripos( $content, 'hubspot' )
			|| has_shortcode( $content, 'hubspot' );
	}

	$is_sede_page = is_page_template( 'templates/page-sede.php' );

	if (
		! $is_sede_page
		&& is_singular( 'page' )
		&& $current_post instanceof WP_Post
	) {
		$is_sede_page = false !== strpos(
			$current_post->post_content,
			'nvx-sede-page'
		) || false !== strpos(
			$current_post->post_content,
			'nvx-advanced-page'
		);
	}

	$is_generic_page = is_singular( 'page' )
		&& ! $is_front_page
		&& ! $is_p0
		&& ! $is_treatment
		&& ! $is_form_page
		&& ! $is_sede_page;

	/*
	 * Orden obligatorio
	 */
	wp_enqueue_style( 'nvx-tokens', $css . 'nvx-tokens.css', array( 'nvx-fonts' ), $asset_version( 'nvx-tokens.css' ) );
	wp_enqueue_style( 'nvx-base', $css . 'nvx-base.css', array( 'nvx-tokens' ), $asset_version( 'nvx-base.css' ) );
	wp_enqueue_style( 'nvx-components', $css . 'nvx-components.css', array( 'nvx-base' ), $asset_version( 'nvx-components.css' ) );
	wp_enqueue_style( 'nvx-site-layout', $css . 'nvx-site-layout.css', array( 'nvx-components' ), $asset_version( 'nvx-site-layout.css' ) );
	wp_enqueue_style( 'nvx-fluid-organic-2026', $css . 'nvx-fluid-organic-2026.css', array( 'nvx-site-layout' ), $asset_version( 'nvx-fluid-organic-2026.css' ) );
	wp_enqueue_style( 'nvx-header', $css . 'nvx-header.css', array( 'nvx-fluid-organic-2026' ), $asset_version( 'nvx-header.css' ) );
	wp_enqueue_style( 'nvx-footer', $css . 'nvx-footer.css', array( 'nvx-header' ), $asset_version( 'nvx-footer.css' ) );
	wp_enqueue_style( 'nvx-pages', $css . 'nvx-pages.css', array( 'nvx-footer' ), $asset_version( 'nvx-pages.css' ) );

	$visual_deps = array( 'nvx-pages' );

	if ( $is_generic_page ) {
		wp_enqueue_style( 'nvx-gutenberg-pages', $css . 'nvx-gutenberg-pages.css', array( 'nvx-pages' ), $asset_version( 'nvx-gutenberg-pages.css' ) );
		$visual_deps = array( 'nvx-gutenberg-pages' );
	}

	if ( $is_p0 || $is_generic_page || $is_form_page || $is_sede_page ) {
		wp_enqueue_style( 'nvx-secondary-pages', $css . 'nvx-secondary-pages.css', $visual_deps, $asset_version( 'nvx-secondary-pages.css' ) );
		$visual_deps = array( 'nvx-secondary-pages' );
	}

	wp_enqueue_style( 'nvx-visual-system', $css . 'nvx-visual-system.css', $visual_deps, $asset_version( 'nvx-visual-system.css' ) );
	wp_enqueue_style( 'nvx-typography-alignment', $css . 'nvx-typography-alignment.css', array( 'nvx-visual-system' ), $asset_version( 'nvx-typography-alignment.css' ) );

	$last_dep = 'nvx-typography-alignment';

	if ( $is_form_page ) {
		wp_enqueue_style( 'nvx-forms', $css . 'nvx-forms.css', array( $last_dep ), $asset_version( 'nvx-forms.css' ) );
	}
	if ( $is_post_context ) {
		wp_enqueue_style( 'nvx-posts', $css . 'nvx-posts.css', array( $last_dep ), $asset_version( 'nvx-posts.css' ) );
	}
	if ( $is_sede_page ) {
		wp_enqueue_style( 'nvx-sede-page', $css . 'nvx-sede-page.css', array( $last_dep ), $asset_version( 'nvx-sede-page.css' ) );
	}

	if ( $is_front_page ) {
		wp_enqueue_style( 'nvx-brand-home', $css . 'nvx-brand-home.css', array( $last_dep ), $asset_version( 'nvx-brand-home.css' ) );
		wp_enqueue_script( 'nvx-brand-system', get_template_directory_uri() . '/assets/js/nvx-brand-system.js', array(), filemtime( get_template_directory() . '/assets/js/nvx-brand-system.js' ), true );
	} elseif ( $is_treatment ) {
		wp_enqueue_style( 'nvx-brand-treatment-core', $css . 'nvx-brand-treatment-core.css', array( $last_dep ), $asset_version( 'nvx-brand-treatment-core.css' ) );
		
		$treatment_addon = '';
		if ( is_page( 1241 ) ) {
			$treatment_addon = 'nvx-brand-treatment-endolift';
		} elseif ( is_page( 1200 ) ) {
			$treatment_addon = 'nvx-brand-treatment-endolaser';
		} elseif ( is_page( 2017 ) ) {
			$treatment_addon = 'nvx-brand-treatment-co2';
		}
		
		if ( '' !== $treatment_addon ) {
			wp_enqueue_style( $treatment_addon, $css . $treatment_addon . '.css', array( 'nvx-brand-treatment-core' ), $asset_version( $treatment_addon . '.css' ) );
		}
	} elseif ( nvx_theme_uses_brand_system() ) {
		wp_enqueue_style( 'nvx-brand-system', $css . 'nvx-brand-system.css', array( $last_dep ), $asset_version( 'nvx-brand-system.css' ) );
		wp_enqueue_script( 'nvx-brand-system', get_template_directory_uri() . '/assets/js/nvx-brand-system.js', array(), filemtime( get_template_directory() . '/assets/js/nvx-brand-system.js' ), true );
	}

	wp_enqueue_script( 'nvx-main', get_template_directory_uri() . '/assets/js/nvx-main.js', array(), $ver, true );
}

add_action( 'wp_enqueue_scripts', 'nvx_theme_scripts' );

/**
 * Front-end: sin CSS inline de WordPress core (global-styles / blocks usan ).
 */
function nvx_theme_dequeue_wp_core_inline_css() {
	if ( is_admin() ) {
		return;
	}
	wp_dequeue_style( 'global-styles' );
	wp_dequeue_style( 'classic-theme-styles' );
	wp_dequeue_style( 'wp-block-library' );
	wp_dequeue_style( 'wp-block-library-theme' );
	wp_dequeue_style( 'core-block-supports' );
	wp_dequeue_style( 'wp-img-auto-sizes-contain' );
}
add_action( 'wp_enqueue_scripts', 'nvx_theme_dequeue_wp_core_inline_css', 100 );

remove_action( 'wp_enqueue_scripts', 'wp_enqueue_global_styles' );
remove_action( 'wp_footer', 'wp_enqueue_global_styles', 1 );
remove_action( 'wp_enqueue_scripts', 'wp_enqueue_stored_styles' );
remove_action( 'wp_footer', 'wp_enqueue_stored_styles', 1 );

/**
 * HTML público sin  (tema + plugins: Complianz, WP blocks, etc.).
 */


/**
 * Blog index cards — theme-only (replaces archived MU shortcode nvx_blog_index).
 */
function nvx_theme_blog_index_markup(): string {
	$posts = new WP_Query(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => 12,
			'paged'          => max( 1, (int) get_query_var( 'paged' ) ),
		)
	);

	if ( ! $posts->have_posts() ) {
		return '<p class="nvx-blog-empty">No se encontraron artículos.</p>';
	}

	$output = '<div class="nvx-brand-grid nvx-brand-grid--3">';
	while ( $posts->have_posts() ) {
		$posts->the_post();
		$output .= '<article id="post-' . get_the_ID() . '" class="' . implode( ' ', get_post_class( 'nvx-brand-card' ) ) . '">';
		$output .= '<h2 class="nvx-brand-card__title"><a href="' . get_permalink() . '">' . get_the_title() . '</a></h2>';
		$output .= '<p class="nvx-brand-card__kicker">' . esc_html( get_the_date() ) . '</p>';
		$output .= '<div class="nvx-brand-card__body">' . get_the_excerpt() . '</div>';
		$output .= '<a href="' . get_permalink() . '" class="nvx-brand-btn nvx-brand-btn--outline">Leer más</a>';
		$output .= '</article>';
	}
	$output .= '</div>';
	wp_reset_postdata();

	return $output;
}

function nvx_theme_blog_index_shortcode(): string {
	return nvx_theme_blog_index_markup();
}
add_shortcode( 'nvx_blog_index', 'nvx_theme_blog_index_shortcode' );

/**
 * Blog posts: one H1 from article hero, not theme title + duplicate h2.
 */
function nvx_theme_article_hero_heading( string $content ): string {
	if ( ! is_singular( 'post' ) || ! preg_match( '/\bnvx-article-hero\b/i', $content ) ) {
		return $content;
	}
	return $content;
}
add_filter( 'the_content', 'nvx_theme_article_hero_heading', 8 );

/**
 * Sin CSS/JS embebido en contenido — solo tema + MU infra.
 */
function nvx_theme_strip_content_embeds( string $content ): string {
	return $content;
}
add_filter( 'the_content', 'nvx_theme_strip_content_embeds', 3 );
add_filter( 'render_block', 'nvx_theme_strip_block_embeds', 10, 2 );

function nvx_theme_strip_block_embeds( string $block_content, array $block ): string {
	if ( is_admin() || '' === $block_content ) {
		return $block_content;
	}
	return nvx_theme_strip_content_embeds( $block_content );
}

function nvx_theme_is_nvx_page_shell(): bool {
	return is_singular() || is_home() || is_front_page() || is_archive();
}

function nvx_theme_body_class( array $classes ): array {
	if ( nvx_theme_is_nvx_page_shell() ) {
		$classes[] = 'nvx-unified-shell';
	}
	return $classes;
}
add_filter( 'body_class', 'nvx_theme_body_class' );
/**
 * Strict HTML Control: Disable classic WordPress auto-paragraph filters.
 * Ensures custom block structures and flex/grid layouts do not get injected with empty <p> tags.
 */
remove_filter( 'the_content', 'wpautop' );
remove_filter( 'the_excerpt', 'wpautop' );

require_once get_template_directory() . '/inc/nvx-integrations.php';


/**
 * Home: preload poster del vídeo hero (LCP).
 */
function nvx_theme_home_video_lcp_preload() {
	if ( ! nvx_theme_is_home_page() ) {
		return;
	}
	$poster = content_url( 'uploads/2026/07/nvx-home-video-portada-poster.webp' );
	echo '<link rel="preload" as="image" href="' . esc_url( $poster ) . '" fetchpriority="high">' . "\n";
}
add_action( 'wp_head', 'nvx_theme_home_video_lcp_preload', 2 );

/**
 * Tratamientos comerciales: preload LCP hero editorial.
 */
function nvx_theme_treatment_lcp_preload() {
	$heroes = array(
		1241 => 'uploads/2026/07/Endolift-facial.webp',
		1200 => 'uploads/2026/07/endolaser-corporal-grasa-localizada.webp',
	);
	$hero = '';
	if ( is_page( array_keys( $heroes ) ) ) {
		$id   = (int) get_queried_object_id();
		$hero = content_url( $heroes[ $id ] ?? '' );
	} elseif ( is_page( 2017 ) ) {
		$hero = content_url( 'uploads/2026/07/laser-co2-fraccionado-madrid-textura-cicatrices-poro.webp' );
	}
	if ( '' === $hero ) {
		return;
	}
	echo '<link rel="preload" as="image" href="' . esc_url( $hero ) . '" fetchpriority="high">' . "\n";
}
add_action( 'wp_head', 'nvx_theme_treatment_lcp_preload', 2 );




