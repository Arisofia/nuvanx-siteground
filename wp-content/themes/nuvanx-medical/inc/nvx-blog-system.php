<?php
/**
 * Blog and journal presentation bootstrap.
 *
 * Keeps the editorial layer isolated from commercial pages while covering the
 * posts page, taxonomy/date/author archives, search results and single posts.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether the current public request belongs to the editorial journal.
 */
function nvx_theme_is_blog_context(): bool {
	if ( is_admin() ) {
		return false;
	}

	return is_home()
		|| is_singular( 'post' )
		|| is_category()
		|| is_tag()
		|| is_date()
		|| is_author()
		|| is_search()
		|| is_post_type_archive( 'post' );
}

/** Load the journal layer after the global design system. */
function nvx_theme_enqueue_blog_styles(): void {
	if ( ! nvx_theme_is_blog_context() ) {
		return;
	}

	$relative = 'assets/css/nvx-posts.css';
	$absolute = get_template_directory() . '/' . $relative;

	if ( ! is_readable( $absolute ) ) {
		return;
	}

	wp_enqueue_style(
		'nvx-posts',
		get_template_directory_uri() . '/' . $relative,
		array( 'nvx-footer' ),
		(string) filemtime( $absolute )
	);
}
add_action( 'wp_enqueue_scripts', 'nvx_theme_enqueue_blog_styles', 40 );

/** Stable body hook for scoped editorial rules and smoke tests. */
function nvx_theme_blog_body_class( array $classes ): array {
	if ( nvx_theme_is_blog_context() ) {
		$classes[] = 'nvx-blog-context';
	}

	return array_values( array_unique( $classes ) );
}
add_filter( 'body_class', 'nvx_theme_blog_body_class' );

/**
 * The article template owns the only H1. Demote legacy H1 tags saved inside old
 * post content so historical entries inherit the same accessible hierarchy.
 */
function nvx_theme_normalize_blog_headings( string $content ): string {
	if ( ! is_singular( 'post' ) || false === stripos( $content, '<h1' ) ) {
		return $content;
	}

	$content = (string) preg_replace( '/<h1(\b[^>]*)>/iu', '<h2$1>', $content );
	return (string) preg_replace( '/<\/h1>/iu', '</h2>', $content );
}
add_filter( 'the_content', 'nvx_theme_normalize_blog_headings', 8 );
