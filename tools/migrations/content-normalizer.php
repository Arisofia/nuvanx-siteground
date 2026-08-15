<?php
/**
 * Semantics-preserving normalizer for legacy Markdown stored in post_content.
 *
 * @package NVX\Migrations
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
    fwrite( STDERR, "CONTENT_NORMALIZER=FAIL reason=wordpress_not_loaded\n" );
    exit( 1 );
}

function nvxNeedsMarkdownNormalization( string $content ): bool {
    if ( '' === trim( $content ) || false !== strpos( $content, '<!-- wp:' ) ) {
        return false;
    }

    return 1 === preg_match( '/\[[^\]]+\]\([^)]+\)/', $content )
        || 1 === preg_match( '/^#{1,6}\s+.+$/m', $content )
        || 1 === preg_match( '/^\s*(?:[-+*]|\d+[.)])\s+\S+/m', $content );
}

function nvxNormalizeMarkdownInline( string $text ): string {
    $escaped = esc_html( $text );
    $escaped = preg_replace_callback(
        '/\[([^\]]+)\]\(([^)\s]+)\)/',
        static function ( array $matches ): string {
            $url = html_entity_decode( (string) $matches[2], ENT_QUOTES | ENT_HTML5, 'UTF-8' );
            return '<a href="' . esc_url( $url ) . '">' . (string) $matches[1] . '</a>';
        },
        $escaped
    ) ?? $escaped;
    $escaped = preg_replace( '/\*\*([^*\n]+)\*\*/', '<strong>$1</strong>', $escaped ) ?? $escaped;
    $escaped = preg_replace( '/__([^_\n]+)__/', '<strong>$1</strong>', $escaped ) ?? $escaped;
    $escaped = preg_replace( '/(?<!\*)\*([^*\n]+)\*(?!\*)/', '<em>$1</em>', $escaped ) ?? $escaped;
    return preg_replace( '/(?<!_)_([^_\n]+)_(?!_)/', '<em>$1</em>', $escaped ) ?? $escaped;
}

/** @param array<int,string> $paragraph @param array<int,string> $output */
function nvxFlushMarkdownParagraph( array &$paragraph, array &$output ): void {
    if ( empty( $paragraph ) ) {
        return;
    }

    $joined = implode( ' ', $paragraph );
    $joined = preg_replace( '/\s+/', ' ', $joined ) ?? $joined;
    $output[] = '<p>' . nvxNormalizeMarkdownInline( trim( $joined ) ) . '</p>';
    $paragraph = array();
}

/** @param array<int,string> $items @param array<int,string> $output */
function nvxFlushMarkdownList( string &$listType, array &$items, array &$output ): void {
    if ( '' === $listType || empty( $items ) ) {
        $listType = '';
        $items = array();
        return;
    }

    $tag = 'ol' === $listType ? 'ol' : 'ul';
    $htmlItems = array_map(
        static fn( string $item ): string => '<li>' . nvxNormalizeMarkdownInline( trim( $item ) ) . '</li>',
        $items
    );
    $output[] = '<' . $tag . '>' . implode( '', $htmlItems ) . '</' . $tag . '>';
    $listType = '';
    $items = array();
}

/** @return array{type:string,value:string,level:int} */
function nvxClassifyMarkdownLine( string $line ): array {
    $trimmed = trim( $line );
    $token = array( 'type' => 'text', 'value' => $trimmed, 'level' => 0 );
    $matches = array();

    if ( '' === $trimmed ) {
        $token = array( 'type' => 'blank', 'value' => '', 'level' => 0 );
    } elseif ( preg_match( '/^#{2,6}\s*📌\s*$/u', $trimmed ) ) {
        $token = array( 'type' => 'editorial_residue', 'value' => '', 'level' => 0 );
    } elseif ( preg_match( '/^(#{1,6})\s+(.+)$/', $trimmed, $matches ) ) {
        $token = array( 'type' => 'heading', 'value' => trim( $matches[2] ), 'level' => strlen( $matches[1] ) );
    } elseif ( preg_match( '/^(?:---+|___+|\*\*\*+)$/', $trimmed ) ) {
        $token = array( 'type' => 'rule', 'value' => '', 'level' => 0 );
    } elseif ( preg_match( '/^[-+*]\s+(.+)$/', $trimmed, $matches ) ) {
        $token = array( 'type' => 'ul', 'value' => $matches[1], 'level' => 0 );
    } elseif ( preg_match( '/^\d+[.)]\s+(.+)$/', $trimmed, $matches ) ) {
        $token = array( 'type' => 'ol', 'value' => $matches[1], 'level' => 0 );
    } elseif ( preg_match( '/^<\/?[a-z][^>]*>/i', $trimmed ) ) {
        $token = array( 'type' => 'html', 'value' => $line, 'level' => 0 );
    }

    return $token;
}

/** @param array<int,string> $paragraph @param array<int,string> $items @param array<int,string> $output */
function nvxFlushMarkdownBuffers( array &$paragraph, string &$listType, array &$items, array &$output ): void {
    nvxFlushMarkdownParagraph( $paragraph, $output );
    nvxFlushMarkdownList( $listType, $items, $output );
}

/**
 * Apply one classified Markdown token.
 *
 * @param array{type:string,value:string,level:int} $token
 * @param array<int,string> $paragraph
 * @param array<int,string> $listItems
 * @param array<int,string> $output
 * @return bool False when the technical metadata tail has been reached.
 */
function nvxApplyMarkdownToken(
    array $token,
    array &$paragraph,
    string &$listType,
    array &$listItems,
    array &$output,
    bool &$leadingH1Removed
): bool {
    switch ( $token['type'] ) {
        case 'blank':
            nvxFlushMarkdownBuffers( $paragraph, $listType, $listItems, $output );
            break;
        case 'heading':
            nvxFlushMarkdownBuffers( $paragraph, $listType, $listItems, $output );
            if ( 1 === $token['level'] && ! $leadingH1Removed && empty( $output ) ) {
                $leadingH1Removed = true;
                break;
            }
            $level = 1 === $token['level'] ? 2 : $token['level'];
            $output[] = '<h' . $level . '>' . nvxNormalizeMarkdownInline( $token['value'] ) . '</h' . $level . '>';
            break;
        case 'rule':
            nvxFlushMarkdownBuffers( $paragraph, $listType, $listItems, $output );
            $output[] = '<hr />';
            break;
        case 'ul':
        case 'ol':
            nvxFlushMarkdownParagraph( $paragraph, $output );
            if ( '' !== $listType && $token['type'] !== $listType ) {
                nvxFlushMarkdownList( $listType, $listItems, $output );
            }
            $listType = $token['type'];
            $listItems[] = $token['value'];
            break;
        case 'html':
            nvxFlushMarkdownBuffers( $paragraph, $listType, $listItems, $output );
            $output[] = wp_kses_post( $token['value'] );
            break;
        case 'editorial_residue':
            nvxFlushMarkdownBuffers( $paragraph, $listType, $listItems, $output );
            return false;
        default:
            $paragraph[] = $token['value'];
    }

    return true;
}

function nvxNormalizeContent( string $content ): string {
    $normalized = str_replace( array( "\r\n", "\r" ), "\n", trim( $content ) );
    if ( '' === $normalized || false !== strpos( $normalized, '<!-- wp:' ) ) {
        return $normalized;
    }

    $output = array();
    $paragraph = array();
    $listType = '';
    $listItems = array();
    $leadingH1Removed = false;

    foreach ( explode( "\n", $normalized ) as $line ) {
        $token = nvxClassifyMarkdownLine( rtrim( $line ) );
        if ( ! nvxApplyMarkdownToken( $token, $paragraph, $listType, $listItems, $output, $leadingH1Removed ) ) {
            break;
        }
    }

    nvxFlushMarkdownBuffers( $paragraph, $listType, $listItems, $output );
    return trim( implode( "\n\n", $output ) );
}

function nvxNormalizeToHtml( string $content ): string {
    return nvxNormalizeContent( $content );
}

/** @return array{valid:bool,issues:array<int,string>} */
function nvxValidateNormalizedContent( string $content ): array {
    $checks = array(
        '/\[[^\]]+\]\([^)]+\)/' => 'Markdown links still present',
        '/^#{1,6}\s+.+$/m' => 'Markdown headings still present',
        '/^\s*(?:[-+*]|\d+[.)])\s+\S+/m' => 'Markdown list markers still present',
        '/@nvx-[a-z0-9_:-]+/i' => '@nvx-* token still present',
        '/%(?:\d+\$)?[sd]/' => 'Format string still present',
        '/\b(?:borrador|pendiente de revisión|para revisar|work in progress)\b/i' => 'Draft/review language still present',
        '/\b(?:TODO|FIXME|XXX|HACK|placeholder)\b/i' => 'Editorial placeholder still present',
    );
    $issues = array();

    foreach ( $checks as $pattern => $message ) {
        if ( preg_match( $pattern, $content ) ) {
            $issues[] = $message;
        }
    }

    return array( 'valid' => empty( $issues ), 'issues' => $issues );
}
