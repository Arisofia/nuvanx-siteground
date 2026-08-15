<?php
/**
 * Content normalizer for legacy Markdown stored in WordPress post_content.
 *
 * The normalizer is intentionally semantics-preserving: it converts formatting
 * syntax to HTML but never deletes editorial markers, draft language or NUVANX
 * tokens. Those are validation failures and must be fixed at source rather than
 * silently removed from clinical copy.
 *
 * @package NVX\Migrations
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "ERROR: must run inside WordPress via wp eval-file.\n" );
	exit( 1 );
}

/** Convert Markdown inline syntax while escaping untrusted raw text. */
function nvx_normalize_markdown_inline( string $text ): string {
	$text = esc_html( $text );

	// Links are converted before emphasis so labels may still contain emphasis.
	$text = preg_replace_callback(
		'/\[([^\]]+)\]\(([^)\s]+)\)/',
		static function ( array $matches ): string {
			$label = (string) $matches[1];
			$url   = html_entity_decode( (string) $matches[2], ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			return '<a href="' . esc_url( $url ) . '">' . $label . '</a>';
		},
		$text
	) ?? $text;

	$text = preg_replace( '/\*\*([^*\n]+)\*\*/', '<strong>$1</strong>', $text ) ?? $text;
	$text = preg_replace( '/__([^_\n]+)__/', '<strong>$1</strong>', $text ) ?? $text;
	$text = preg_replace( '/(?<!\*)\*([^*\n]+)\*(?!\*)/', '<em>$1</em>', $text ) ?? $text;
	$text = preg_replace( '/(?<!_)_([^_\n]+)_(?!_)/', '<em>$1</em>', $text ) ?? $text;

	return $text;
}

/**
 * Convert the subset of Markdown used by legacy NUVANX articles to valid HTML.
 *
 * @return string Normalized HTML, or the original block/HTML document when it
 *                is already governed WordPress content.
 */
function nvx_normalize_content( string $content ): string {
	$content = str_replace( array( "\r\n", "\r" ), "\n", trim( $content ) );
	if ( '' === $content ) {
		return '';
	}

	// Do not reinterpret existing Gutenberg or clearly HTML-authored documents.
	if ( false !== strpos( $content, '<!-- wp:' ) ) {
		return $content;
	}

	$lines      = explode( "\n", $content );
	$output     = array();
	$paragraph  = array();
	$list_type  = '';
	$list_items = array();

	$flush_paragraph = static function () use ( &$paragraph, &$output ): void {
		if ( empty( $paragraph ) ) {
			return;
		}
		$text        = preg_replace( '/\s+/', ' ', implode( ' ', $paragraph ) ) ?? implode( ' ', $paragraph );
		$output[]    = '<p>' . nvx_normalize_markdown_inline( trim( $text ) ) . '</p>';
		$paragraph   = array();
	};

	$flush_list = static function () use ( &$list_type, &$list_items, &$output ): void {
		if ( '' === $list_type || empty( $list_items ) ) {
			$list_type  = '';
			$list_items = array();
			return;
		}
		$tag   = 'ol' === $list_type ? 'ol' : 'ul';
		$items = array_map(
			static fn( string $item ): string => '<li>' . nvx_normalize_markdown_inline( trim( $item ) ) . '</li>',
			$list_items
		);
		$output[]    = '<' . $tag . '>' . implode( '', $items ) . '</' . $tag . '>';
		$list_type   = '';
		$list_items  = array();
	};

	foreach ( $lines as $line ) {
		$line = rtrim( $line );
		if ( '' === trim( $line ) ) {
			$flush_paragraph();
			$flush_list();
			continue;
		}

		if ( preg_match( '/^(#{1,6})\s+(.+)$/', $line, $matches ) ) {
			$flush_paragraph();
			$flush_list();
			$level    = strlen( $matches[1] );
			$output[] = '<h' . $level . '>' . nvx_normalize_markdown_inline( trim( $matches[2] ) ) . '</h' . $level . '>';
			continue;
		}

		if ( preg_match( '/^\s*(?:---+|___+|\*\*\*+)\s*$/', $line ) ) {
			$flush_paragraph();
			$flush_list();
			$output[] = '<hr />';
			continue;
		}

		if ( preg_match( '/^\s*[-+*]\s+(.+)$/', $line, $matches ) ) {
			$flush_paragraph();
			if ( 'ul' !== $list_type ) {
				$flush_list();
				$list_type = 'ul';
			}
			$list_items[] = $matches[1];
			continue;
		}

		if ( preg_match( '/^\s*\d+[.)]\s+(.+)$/', $line, $matches ) ) {
			$flush_paragraph();
			if ( 'ol' !== $list_type ) {
				$flush_list();
				$list_type = 'ol';
			}
			$list_items[] = $matches[1];
			continue;
		}

		// Preserve intentionally authored HTML rather than escaping it into text.
		if ( preg_match( '/^\s*<\/?[a-z][^>]*>/i', $line ) ) {
			$flush_paragraph();
			$flush_list();
			$output[] = wp_kses_post( $line );
			continue;
		}

		$paragraph[] = trim( $line );
	}

	$flush_paragraph();
	$flush_list();

	return trim( implode( "\n\n", $output ) );
}

/** Retained compatibility wrapper used by the migration command. */
function nvx_html_to_blocks( string $html ): string {
	return trim( $html );
}

/** Full normalization pipeline. */
function nvx_normalize_to_blocks( string $content ): string {
	return nvx_html_to_blocks( nvx_normalize_content( $content ) );
}

/**
 * Validate that public content contains no storage-format or editorial leakage.
 *
 * @return array{valid:bool,issues:array<int,string>}
 */
function nvx_validate_normalized_content( string $content ): array {
	$issues = array();

	if ( preg_match( '/\[[^\]]+\]\([^)]+\)/', $content ) ) {
		$issues[] = 'Markdown links still present';
	}
	if ( preg_match( '/^#{1,6}\s+.+$/m', $content ) ) {
		$issues[] = 'Markdown headings still present';
	}
	if ( preg_match( '/^\s*[-+*]\s+\S+/m', $content ) || preg_match( '/^\s*\d+[.)]\s+\S+/m', $content ) ) {
		$issues[] = 'Markdown list markers still present';
	}
	if ( preg_match( '/@nvx-[a-z0-9_:-]+/i', $content ) ) {
		$issues[] = '@nvx-* token still present';
	}
	if ( preg_match( '/%(?:\d+\$)?[sd]/', $content ) ) {
		$issues[] = 'Format string still present';
	}
	if ( preg_match( '/\b(?:borrador|pendiente de revisión|para revisar|work in progress)\b/i', $content ) ) {
		$issues[] = 'Draft/review language still present';
	}
	if ( preg_match( '/\b(?:TODO|FIXME|XXX|HACK|placeholder)\b/i', $content ) ) {
		$issues[] = 'Editorial placeholder still present';
	}

	return array(
		'valid'  => empty( $issues ),
		'issues' => $issues,
	);
}
