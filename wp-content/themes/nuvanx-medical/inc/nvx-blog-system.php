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
 * Keep the Journal search surface limited to published articles. This prevents
 * commercial pages from inheriting the editorial search template and styling.
 */
function nvx_theme_constrain_blog_search( WP_Query $query ): void {
	if ( is_admin() || ! $query->is_main_query() || ! $query->is_search() ) {
		return;
	}

	$query->set( 'post_type', 'post' );
	$query->set( 'post_status', 'publish' );
}
add_action( 'pre_get_posts', 'nvx_theme_constrain_blog_search', 20 );

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

	$absolute = get_template_directory() . '/assets/css/nvx-posts.css';

	if ( ! is_readable( $absolute ) ) {
		return;
	}

	wp_enqueue_style(
		'nvx-posts',
		get_template_directory_uri() . '/assets/css/nvx-posts.css',
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

/**
 * Canonical medical author for journal (E-E-A-T). Not the WP login display name.
 *
 * @return array{name:string,url:string,role:string}
 */
function nvx_blog_medical_author(): array {
	return array(
		'name' => __( 'Dr. José Javier Rivera Tejeda', 'nuvanx-medical' ),
		'url'  => home_url( '/equipo-medico/#physician-rivera-tejeda' ),
		'role' => __( 'Director Médico NUVANX', 'nuvanx-medical' ),
	);
}

/**
 * Strip hardcoded CMS bylines (Autor / Fecha / Lectura) from post body.
 * Hero meta in nvx-blog-single.php owns author, date and reading time.
 */
function nvx_theme_strip_blog_content_bylines( string $content ): string {
	if ( is_admin() || ! is_singular( 'post' ) || '' === trim( $content ) ) {
		return $content;
	}

	// Explicit byline class from publish scripts.
	$content = (string) preg_replace(
		'/<p\b[^>]*\bclass=["\'][^"\']*\bnvx-blog-byline\b[^"\']*["\'][^>]*>[\s\S]*?<\/p>/iu',
		'',
		$content
	);

	// Legacy plain paragraphs: Autor: … Fecha: … Lectura: …
	$content = (string) preg_replace(
		'/<p\b[^>]*>\s*(?:<strong>)?\s*Autor\s*:?\s*(?:<\/strong>)?[\s\S]{0,500}?(?:Fecha|Lectura)\s*:[\s\S]*?<\/p>/iu',
		'',
		$content,
		3
	);

	// Orphan keyword dumps right after byline leftovers.
	$content = (string) preg_replace(
		'/<p\b[^>]*>\s*(?:<strong>)?\s*Palabras clave\s*:?\s*(?:<\/strong>)?[\s\S]{0,400}?<\/p>/iu',
		'',
		$content,
		2
	);

	// Collapse excess leading whitespace after strips.
	$content = (string) preg_replace( '/^(?:\s|<br\s*\/?>|&nbsp;)+/iu', '', $content );

	return $content;
}
add_filter( 'the_content', 'nvx_theme_strip_blog_content_bylines', 9 );
