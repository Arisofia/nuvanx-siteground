<?php
/**
 * Native WordPress style governance for fully theme-owned templates.
 *
 * Theme CSS is delivered through one inline critical bundle. The source files
 * remain canonical and linted, but public requests no longer serialise local
 * stylesheet requests ahead of the LCP element.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Whether the current request is the treatments hub page by slug. */
function nvx_theme_is_treatments_hub_page(): bool {
	return is_page() && 'tratamientos' === get_post_field( 'post_name', get_queried_object_id() );
}

/** Whether the theme owns the complete body markup for the current page. */
function nvx_theme_owns_complete_page_markup(): bool {
	return is_front_page() || nvx_theme_is_treatments_hub_page();
}

/**
 * Remove long-form prose semantics from managed component documents.
 *
 * Several canonical page renderers intentionally emit a full-bleed hero plus
 * component sections inside the standard entry-content wrapper. Keeping the
 * outer nvx-prose class makes descendant long-form rules override the spacing
 * owned by hero/section components, especially on narrow viewports.
 *
 * Only managed pages with a brand hero and component sections
 * (nvx-brand-section or nvx-aes-section) are normalized; posts, legal
 * documents and ordinary CMS prose remain unchanged.
 *
 * @param string $content Rendered post content.
 * @return string
 */
function nvx_theme_normalize_managed_component_prose_wrapper( string $content ): string {
	if ( is_admin() || '' === $content ) {
		return $content;
	}

	if ( ! function_exists( 'nvx_get_page_owner' ) || empty( nvx_get_page_owner() ) ) {
		return $content;
	}

	$has_component_sections = false !== strpos( $content, 'nvx-brand-section' )
		|| false !== strpos( $content, 'nvx-aes-section' );

	if ( false === strpos( $content, 'entry-content nvx-page__content nvx-prose' )
		|| false === strpos( $content, 'nvx-brand-hero' )
		|| ! $has_component_sections ) {
		return $content;
	}

	$normalized = preg_replace(
		'/\bentry-content\s+nvx-page__content\s+nvx-prose\b/',
		'entry-content nvx-page__content',
		$content,
		1
	);

	return is_string( $normalized ) ? $normalized : $content;
}
add_filter( 'the_content', 'nvx_theme_normalize_managed_component_prose_wrapper', PHP_INT_MAX );

/**
 * Return the ordered local stylesheets required by the current public route.
 *
 * The order mirrors the original dependency graph. Route-local styles are
 * appended only when their template can render the matching markup.
 *
 * @return string[] Relative paths below the active theme directory.
 */
function nvx_theme_critical_stylesheet_files(): array {
	$files = array(
		'assets/css/nvx-fonts.css',
		'assets/css/nvx-tokens.css',
		'assets/css/nvx-base.css',
		'assets/css/nvx-site-layout.css',
		'assets/css/nvx-components.css',
		'assets/css/nvx-patterns-editorial.css',
		'assets/css/nvx-header.css',
		'assets/css/nvx-footer.css',
		'assets/css/nvx-accessibility-governance.css',
	);

	if ( is_front_page() ) {
		$files[] = 'assets/css/nvx-home-v3.css';
	}

	if ( nvx_theme_is_treatments_hub_page() ) {
		$files[] = 'assets/css/nvx-portfolio-hub.css';
	}

	if ( function_exists( 'nvx_theme_is_blog_context' ) && nvx_theme_is_blog_context() ) {
		$files[] = 'assets/css/nvx-posts.css';
	}

	if ( function_exists( 'nvx_content_is_solutions_page' ) && nvx_content_is_solutions_page() ) {
		$files[] = 'assets/css/nvx-soluciones-medicas.css';
	}

	if ( is_page( 'casos-de-pacientes' ) ) {
		$files[] = 'assets/css/nvx-cases-holding.css';
	}

	return $files;
}

/**
 * Handles owned by the local theme stylesheets.
 *
 * @return string[]
 */
function nvx_theme_local_style_handles(): array {
	return array(
		'nvx-fonts',
		'nvx-tokens',
		'nvx-base',
		'nvx-layout',
		'nvx-components',
		'nvx-patterns',
		'nvx-header',
		'nvx-footer',
		'nvx-accessibility-governance',
		'nvx-home-v3',
		'nvx-portfolio-hub',
		'nvx-posts',
		'nvx-soluciones-medicas',
		'nvx-cases-holding',
	);
}

/**
 * Collect WordPress-generated inline additions for a stylesheet handle.
 *
 * @param WP_Styles $styles Registered style collection.
 * @param string    $handle Stylesheet handle.
 * @return string
 */
function nvx_theme_style_after_data( WP_Styles $styles, string $handle ): string {
	$after = $styles->get_data( $handle, 'after' );
	if ( ! is_array( $after ) ) {
		return '';
	}

	return implode( "\n", array_filter( $after, 'is_string' ) );
}

/**
 * Inline the complete theme stylesheet contract before WordPress prints head
 * styles. This removes local theme <link> requests while preserving dynamic
 * wp_add_inline_style() additions such as the valoración form stage image.
 */
function nvx_theme_inline_critical_style_foundation(): void {
	if ( is_admin() ) {
		return;
	}

	$styles       = wp_styles();
	$critical_css = '';

	foreach ( nvx_theme_critical_stylesheet_files() as $relative_file ) {
		$absolute_file = get_template_directory() . '/' . $relative_file;
		if ( ! is_readable( $absolute_file ) ) {
			continue;
		}

		$contents = file_get_contents( $absolute_file );
		if ( false === $contents || '' === trim( $contents ) ) {
			continue;
		}

		$critical_css .= "\n/* " . basename( $relative_file ) . " */\n" . $contents;
	}

	if ( is_front_page() ) {
		$critical_css .= "\n/* home-hero geometry reservation */\n"
			. 'body.home .nvx-home-hero{height:var(--nvx-home-hero-h);min-height:var(--nvx-home-hero-h);display:flex;align-items:center;overflow:hidden;}' . "\n";
	}

	$critical_css .= "\n/* interior-hero first paint */\n"
		. '.nvx-brand-hero{background:var(--nvx-ink);color:var(--nvx-light);}'
		. '.nvx-brand-hero .nvx-brand-microcopy,.nvx-brand-hero .nvx-brand-microcopy--dark{color:var(--nvx-text-on-dark-82);}' . "\n";

	foreach ( nvx_theme_local_style_handles() as $handle ) {
		$inline_css = nvx_theme_style_after_data( $styles, $handle );
		if ( '' !== $inline_css ) {
			$critical_css .= "\n/* Inline additions for " . $handle . " */\n" . $inline_css;
		}

		wp_dequeue_style( $handle );
		wp_deregister_style( $handle );
	}

	if ( '' === trim( $critical_css ) ) {
		return;
	}

	wp_register_style( 'nvx-critical-inline', false, array(), NVX_THEME_VERSION );
	wp_enqueue_style( 'nvx-critical-inline' );
	wp_add_inline_style( 'nvx-critical-inline', $critical_css );
}
add_action( 'wp_enqueue_scripts', 'nvx_theme_inline_critical_style_foundation', 999 );

/**
 * Remove a local stylesheet that a page template registered after the normal
 * enqueue hook. Its source has already been incorporated in the route bundle.
 */
function nvx_theme_dequeue_late_local_styles(): void {
	if ( is_admin() ) {
		return;
	}

	foreach ( nvx_theme_local_style_handles() as $handle ) {
		wp_dequeue_style( $handle );
		wp_deregister_style( $handle );
	}
}
add_action( 'wp_head', 'nvx_theme_dequeue_late_local_styles', 7 );

/**
 * Start Google Fonts immediately without blocking first paint.
 *
 * display=swap is already on the request URL. The local theme CSS is inlined,
 * so a delayed font stylesheet cannot hide the page structure or hero surface.
 *
 * @param string $html   Generated stylesheet tag.
 * @param string $handle Registered stylesheet handle.
 * @param string $href   Stylesheet URL.
 * @param string $media  Original media attribute.
 */
function nvx_theme_nonblocking_google_fonts( string $html, string $handle, string $href, string $media ): string {
	unset( $media );
	if ( 'nvx-google-fonts' !== $handle || '' === $href ) {
		return $html;
	}

	$safe_href = esc_url( $href );
	$id        = esc_attr( $handle . '-css' );

	return '<link rel="preload" as="style" href="' . $safe_href . '" />' . "\n"
		. '<link rel="stylesheet" id="' . $id . '" href="' . $safe_href . '" media="print" onload="this.onload=null;this.media=\'all\'" />' . "\n"
		. '<noscript><link rel="stylesheet" id="' . esc_attr( $handle . '-css-noscript' ) . '" href="' . $safe_href . '" /></noscript>' . "\n";
}
add_filter( 'style_loader_tag', 'nvx_theme_nonblocking_google_fonts', 20, 4 );

/**
 * Drop leftover file <link> tags for CSS already in the inline bundle.
 *
 * @param string $html   Generated stylesheet tag.
 * @param string $handle Registered stylesheet handle.
 * @param string $href   Stylesheet URL.
 */
function nvx_theme_drop_inlined_file_links( string $html, string $handle, string $href = '' ): string {
	if ( is_admin() || '' === $href ) {
		return $html;
	}

	if ( in_array( $handle, nvx_theme_local_style_handles(), true ) ) {
		return '';
	}

	return $html;
}
add_filter( 'style_loader_tag', 'nvx_theme_drop_inlined_file_links', 5, 3 );

/** Dequeue block styles only when the rendered page contains no block markup. */
function nvx_theme_dequeue_native_block_styles(): void {
	if ( is_admin() || ! nvx_theme_owns_complete_page_markup() ) {
		return;
	}

	$handles = array(
		'global-styles',
		'classic-theme-styles',
		'wp-block-library',
		'wp-block-library-theme',
		'core-block-supports',
	);

	foreach ( $handles as $handle ) {
		wp_dequeue_style( $handle );
	}
}
add_action( 'wp_enqueue_scripts', 'nvx_theme_dequeue_native_block_styles', 100 );

/**
 * Dequeue stylesheet handles that are no longer shipped with the theme.
 * Prevents broken enqueues stored in the database or third-party plugins.
 */
function nvx_theme_dequeue_retired_stylesheet_handles(): void {
	if ( is_admin() ) {
		return;
	}

	$handles = array(
		'nvx-mobile-hero-hierarchy',
		'nvx-canonical-page-hero',
		'nvx-full-site-ui-governance',
		'nvx-editorial-coherence',
		'nvx-site-coherence',
		'nvx-ui-regressions',
		'nvx-hero-layout-coherence',
		'nvx-integrations',
	);

	foreach ( $handles as $handle ) {
		wp_dequeue_style( $handle );
		wp_deregister_style( $handle );
	}
}
add_action( 'wp_enqueue_scripts', 'nvx_theme_dequeue_retired_stylesheet_handles', 999 );
add_action( 'wp_head', 'nvx_theme_dequeue_retired_stylesheet_handles', 1 );
