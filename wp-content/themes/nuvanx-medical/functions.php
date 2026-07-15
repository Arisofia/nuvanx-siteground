<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'NVX_THEME_VERSION', '1.9.4-single-design-video-only-home' );

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

	/*
	 * Un solo diseño en todo el sitio:
	 * tokens → base → site-layout → header → footer → components
	 * Única excepción: brand-home (vídeo portada).
	 * Consulta/contacto/blog/sedes/tratamientos: mismo stack (sin CSS por página).
	 */
	wp_enqueue_style( 'nvx-tokens', $css . 'nvx-tokens.css', array( 'nvx-fonts' ), $asset_version( 'nvx-tokens.css' ) );
	wp_enqueue_style( 'nvx-base', $css . 'nvx-base.css', array( 'nvx-tokens' ), $asset_version( 'nvx-base.css' ) );
	wp_enqueue_style( 'nvx-site-layout', $css . 'nvx-site-layout.css', array( 'nvx-base' ), $asset_version( 'nvx-site-layout.css' ) );
	wp_enqueue_style( 'nvx-header', $css . 'nvx-header.css', array( 'nvx-site-layout' ), $asset_version( 'nvx-header.css' ) );
	wp_enqueue_style( 'nvx-footer', $css . 'nvx-footer.css', array( 'nvx-header' ), $asset_version( 'nvx-footer.css' ) );
	wp_enqueue_style( 'nvx-components', $css . 'nvx-components.css', array( 'nvx-footer' ), $asset_version( 'nvx-components.css' ) );

	if ( $is_front_page ) {
		wp_enqueue_style( 'nvx-brand-home', $css . 'nvx-brand-home.css', array( 'nvx-components' ), $asset_version( 'nvx-brand-home.css' ) );
		wp_enqueue_script(
			'nvx-brand-system',
			get_template_directory_uri() . '/assets/js/nvx-brand-system.js',
			array(),
			filemtime( get_template_directory() . '/assets/js/nvx-brand-system.js' ),
			true
		);
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
		return '<p class="nvx-copy">' . esc_html__( 'No se encontraron artículos.', 'nuvanx-medical' ) . '</p>';
	}

	$output = '<div class="nvx-brand-grid">';
	while ( $posts->have_posts() ) {
		$posts->the_post();
		$output .= '<article id="post-' . get_the_ID() . '" class="' . esc_attr( implode( ' ', get_post_class( 'nvx-brand-card' ) ) ) . '">';
		if ( has_post_thumbnail() ) {
			$output .= '<div class="nvx-brand-card__media"><a href="' . esc_url( get_permalink() ) . '" tabindex="-1" aria-hidden="true">';
			$output .= get_the_post_thumbnail( get_the_ID(), 'large' );
			$output .= '</a></div>';
		}
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

function nvx_theme_blog_index_shortcode(): string {
	return nvx_theme_blog_index_markup();
}
add_shortcode( 'nvx_blog_index', 'nvx_theme_blog_index_shortcode' );

/**
 * Normaliza markup de contenido al diseño global único:
 * - quita style="" inline (diseños por página en el editor)
 * - aplana columnas Gutenberg a un solo flujo
 * - renombra clases legacy a clases canónicas
 * - elimina wrappers de diseño paralelo
 */
function nvx_theme_normalize_content_markup( string $content ): string {
	if ( is_admin() || '' === $content ) {
		return $content;
	}

	// 1) Quitar estilos inline del editor (excepto en formularios HS si aparecen).
	// Home incluido: el vídeo se mantiene por markup/CSS de stage, no por aislar la página.
	$content = preg_replace_callback(
		'/<([a-z0-9]+)([^>]*?)\sstyle=(["\'])(.*?)\3([^>]*)>/i',
		static function ( $m ) {
			$tag = strtolower( $m[1] );
			// Conservar mínimos en scripts/styles tags (no deberían estar).
			if ( in_array( $tag, array( 'script', 'style' ), true ) ) {
				return $m[0];
			}
			return '<' . $m[1] . $m[2] . $m[5] . '>';
		},
		$content
	);

	// 2) Aplanar columnas Gutenberg: sacar hijos de .wp-block-column y eliminar wrappers de columnas.
	// Iterativo por si hay anidación simple.
	for ( $i = 0; $i < 4; $i++ ) {
		$prev = $content;
		$content = preg_replace(
			'/<div[^>]*\bwp-block-column\b[^>]*>([\s\S]*?)<\/div>/i',
			'$1',
			$content
		);
		$content = preg_replace(
			'/<div[^>]*\bwp-block-columns\b[^>]*>([\s\S]*?)<\/div>/i',
			'$1',
			$content
		);
		if ( $content === $prev ) {
			break;
		}
	}

	// 3) Clases legacy → canónicas (diseño único).
	// Consulta/contacto: quitar raíz de landing paralela y alias tipográficos.
	$replacements = array(
		'nvx-display-section'         => 'nvx-heading',
		'nvx-page__title'             => 'nvx-heading',
		'nvx-journal-hero__title'     => 'nvx-heading',
		'nvx-journal-item__title'     => 'nvx-brand-card__title',
		'nvx-journal-item__cat'       => 'nvx-eyebrow',
		'nvx-journal-item__excerpt'   => 'nvx-brand-card__body',
		'nvx-journal-item__date'      => 'nvx-brand-card__kicker',
		'nvx-journal-item'            => 'nvx-brand-card',
		'nvx-blog-card__title'        => 'nvx-brand-card__title',
		'nvx-blog-card__meta'         => 'nvx-brand-card__kicker',
		'nvx-blog-card__excerpt'      => 'nvx-brand-card__body',
		'nvx-blog-card'               => 'nvx-brand-card',
		'nvx-bg-ivory'                => '',
		'nvx-container--text'         => 'nvx-shell',
		'nvx-container'               => 'nvx-shell',
		'nvx-single-hero'             => 'nvx-section-intro',
		'nvx-single-content'          => 'nvx-page__content',
		'nvx-article-hero'            => 'nvx-section-intro',
		/* nvx-title se deja: el tamaño lo da h1/h2/h3 (no mapear a .nvx-heading = h2). */
		'nvx-subtitle'                => 'nvx-lead',
		'nvx-hero-subtitle'           => 'nvx-lead',
		'nvx-shell-page'              => '',
		'nvx-valoracion-page'         => '',
		'nvx-landing-valoracion'      => '',
		'nvx-med-page'                => '',
		'nvx-contacto-page'           => '',
		'nvx-contact-page'            => '',
		'nvx-tratamiento-page'        => '',
		'nvx-sede-page'               => '',
		'nvx-section--soft'           => '',
		'nvx-section--cta'            => '',
		'nvx-width-narrow'            => '',
		'nvx-width-normal'            => '',
		'nvx-width-wide'              => '',
		'nvx-valoracion-form-section' => '',
		'nvx-hubspot-form-section'    => '',
		'nvx-hs-native-section'       => 'nvx-form',
		'nvx-hs-native-box'           => '',
		'nvx-hubspot-native-form-v2'  => '',
		'nvx-card--cta'               => '',
		'nvx-registro'                => 'nvx-copy',
		'nvx-form-note'               => 'nvx-copy',
		'nvx-editorial-home-v4'       => '',
		'nvx-brand-page'              => '',
		'nvx-home-editorial'          => '',
		'nvx-v3-intro'                => '',
		'nvx-v3-tratamientos'         => '',
		'nvx-v3-metodo'               => '',
		'nvx-v3-direccion'            => '',
		'nvx-v3-faq'                  => '',
		'nvx-v3-cta-final'            => '',
		'nvx-home-image-feature'      => '',
		'has-background'              => '',
		'has-text-color'              => '',
	);
	foreach ( $replacements as $from => $to ) {
		if ( '' === $to ) {
			$content = preg_replace( '/\s*\b' . preg_quote( $from, '/' ) . '\b/', '', $content );
		} else {
			$content = preg_replace( '/\b' . preg_quote( $from, '/' ) . '\b/', $to, $content );
		}
	}

	// 4) Limpiar class="" vacíos, duplicados o dobles espacios en class.
	$content = preg_replace_callback(
		'/class=(["\'])(.*?)\1/i',
		static function ( $m ) {
			$classes = preg_split( '/\s+/', trim( $m[2] ) );
			$classes = array_values( array_unique( array_filter( $classes ) ) );
			if ( empty( $classes ) ) {
				return '';
			}
			return 'class=' . $m[1] . esc_attr( implode( ' ', $classes ) ) . $m[1];
		},
		$content
	);

	// 5) Quitar <style> embebidos en contenido.
	$content = preg_replace( '/<style\b[^>]*>[\s\S]*?<\/style>/i', '', $content );

	return $content;
}
add_filter( 'the_content', 'nvx_theme_normalize_content_markup', 12 );

/**
 * Bloques: no reintroducir embeds de estilo en render_block.
 */
function nvx_theme_strip_block_embeds( string $block_content, array $block ): string {
	if ( is_admin() || '' === $block_content ) {
		return $block_content;
	}
	// Aplanar columnas a nivel de bloque también.
	if ( isset( $block['blockName'] ) && 'core/columns' === $block['blockName'] ) {
		// Dejar solo el HTML de las columnas hijas ya aplanado por the_content; aquí quitamos wrapper flex.
		$block_content = preg_replace( '/\bwp-block-columns\b/', 'nvx-content-stack', $block_content );
		$block_content = preg_replace( '/\bwp-block-column\b/', 'nvx-content-block', $block_content );
	}
	return $block_content;
}
add_filter( 'render_block', 'nvx_theme_strip_block_embeds', 10, 2 );

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




