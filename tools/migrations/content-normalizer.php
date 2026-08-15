<?php
/**
 * Content Normalizer - Convert Markdown to Valid HTML/Blocks
 *
 * Normalizes post content by converting Markdown artifacts to valid HTML
 * and WordPress blocks before persistence.
 *
 * @package NVX\Migrations
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "ERROR: must run inside WordPress via wp eval \"require '...';\".\n" );
	exit( 1 );
}

/**
 * Normalize content by converting Markdown to valid HTML.
 *
 * @param string $content Raw content
 * @return string Normalized content
 */
function nvx_normalize_content( string $content ): string {
	$normalized = $content;

	// Convert Markdown headings to HTML headings
	$normalized = preg_replace_callback(
		'/^#{1,6}\s+(.+)$/m',
		function( $matches ) {
			$level = strlen( $matches[0] ) - strlen( ltrim( $matches[0], '#' ) );
			$heading = trim( $matches[1] );
			return "<h{$level}>{$heading}</h{$level}>";
		},
		$normalized
	);

	// Convert Markdown links to HTML links
	$normalized = preg_replace(
		'/\[([^\]]+)\]\(([^)]+)\)/',
		'<a href="$2">$1</a>',
		$normalized
	);

	// Convert Markdown bold to HTML strong
	$normalized = preg_replace(
		'/\*\*([^*]+)\*\*/',
		'<strong>$1</strong>',
		$normalized
	);

	// Convert Markdown italic to HTML em
	$normalized = preg_replace(
		'/\*([^*]+)\*/',
		'<em>$1</em>',
		$normalized
	);

	// Remove format strings
	$normalized = preg_replace( '/%[sd]/', '', $normalized );

	// Remove @nvx-* placeholders
	$normalized = preg_replace( '/@nvx-[a-z0-9_-]+/i', '', $normalized );

	// Remove draft keywords
	$normalized = preg_replace(
		'/(borrador|pendiente de revisión|para revisar|wip|work in progress)/i',
		'',
		$normalized
	);

	// Remove generic placeholders
	$normalized = preg_replace(
		'/(TODO|FIXME|XXX|HACK|TEMP|placeholder)/i',
		'',
		$normalized
	);

	// Clean up extra whitespace
	$normalized = preg_replace( '/\n{3,}/', "\n\n", $normalized );
	$normalized = trim( $normalized );

	return $normalized;
}

/**
 * Convert plain HTML to WordPress blocks.
 *
 * @param string $html HTML content
 * @return string Block editor content
 */
function nvx_html_to_blocks( string $html ): string {
	// If content already contains blocks, return as-is
	if ( strpos( $html, '<!-- wp:' ) !== false ) {
		return $html;
	}

	// Simple conversion: wrap paragraphs in block markers
	$blocks = '';
	$paragraphs = explode( "\n\n", $html );

	foreach ( $paragraphs as $paragraph ) {
		$paragraph = trim( $paragraph );
		if ( empty( $paragraph ) ) {
			continue;
		}

		// Check if it's a heading
		if ( preg_match( '/^<h([1-6])>(.+)<\/h\1>$/', $paragraph, $matches ) ) {
			$level = $matches[1];
			$content = $matches[2];
			$blocks .= "<!-- wp:heading {\"level\":{$level}} -->\n";
			$blocks .= "<h{$level}>{$content}</h{$level}>\n";
			$blocks .= "<!-- /wp:heading -->\n\n";
		}
		// Check if it's a list
		elseif ( preg_match( '/^[*-]/', $paragraph ) ) {
			$items = preg_split( '/\n(?=[*-])/', $paragraph );
			$blocks .= "<!-- wp:list -->\n";
			$blocks .= "<ul>\n";
			foreach ( $items as $item ) {
				$item = trim( preg_replace( '/^[*-]\s+/', '', $item ) );
				if ( ! empty( $item ) ) {
					$blocks .= "<li>{$item}</li>\n";
				}
			}
			$blocks .= "</ul>\n";
			$blocks .= "<!-- /wp:list -->\n\n";
		}
		// Default: paragraph
		else {
			$blocks .= "<!-- wp:paragraph -->\n";
			$blocks .= "<p>{$paragraph}</p>\n";
			$blocks .= "<!-- /wp:paragraph -->\n\n";
		}
	}

	return trim( $blocks );
}

/**
 * Full normalization pipeline: Markdown → HTML → Blocks.
 *
 * @param string $content Raw content
 * @return string Normalized block content
 */
function nvx_normalize_to_blocks( string $content ): string {
	$html = nvx_normalize_content( $content );
	$blocks = nvx_html_to_blocks( $html );
	return $blocks;
}

/**
 * Validate normalized content.
 *
 * @param string $content Content to validate
 * @return array Validation result
 */
function nvx_validate_normalized_content( string $content ): array {
	$issues = [];

	// Check for remaining Markdown artifacts
	if ( preg_match( '/\[([^\]]+)\]\([^)]+\)/', $content ) ) {
		$issues[] = 'Markdown links still present';
	}

	if ( preg_match( '/^#{1,6}\s+.+$/m', $content ) ) {
		$issues[] = 'Markdown headings still present';
	}

	// Check for placeholders
	if ( preg_match( '/@nvx-[a-z0-9_-]+/i', $content ) ) {
		$issues[] = '@nvx-* placeholders still present';
	}

	if ( preg_match( '/%[sd]/', $content ) ) {
		$issues[] = 'Format strings still present';
	}

	// Check for draft keywords
	if ( preg_match( '/(borrador|pendiente de revisión|para revisar|wip|work in progress)/i', $content ) ) {
		$issues[] = 'Draft keywords still present';
	}

	// Check for generic placeholders
	if ( preg_match( '/(TODO|FIXME|XXX|HACK|TEMP|placeholder)/i', $content ) ) {
		$issues[] = 'Generic placeholders still present';
	}

	return [
		'valid'  => empty( $issues ),
		'issues' => $issues,
	];
}