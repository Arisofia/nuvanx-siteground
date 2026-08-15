<?php
/**
 * Read-only editorial gate for public WordPress content.
 *
 * @package NVX\Migrations
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
    fwrite( STDERR, "EDITORIAL_GATE_VALIDATION=FAIL reason=wordpress_not_loaded\n" );
    exit( 1 );
}

require_once __DIR__ . '/publication-contract-lib.php';

/**
 * Return only affirmative absolute-claim matches.
 *
 * Clinical content often explains that a treatment is NOT "sin riesgo" or NOT
 * "100% efectivo". Those safety warnings must not be flagged as promises. A
 * negation in the same short clause suppresses the match; sentence punctuation
 * resets the context so a previous negation cannot hide a later positive claim.
 *
 * @return array<int,string>
 */
function nvxEditorialAffirmativeClaimMatches( string $content, string $pattern ): array {
    $matches = array();
    preg_match_all( $pattern, $content, $matches, PREG_OFFSET_CAPTURE );
    if ( empty( $matches[0] ) ) {
        return array();
    }

    $affirmative = array();
    foreach ( $matches[0] as $match ) {
        $text = (string) ( $match[0] ?? '' );
        $offset = (int) ( $match[1] ?? 0 );
        $prefix = substr( $content, max( 0, $offset - 160 ), min( 160, $offset ) );
        $prefix = html_entity_decode( wp_strip_all_tags( (string) $prefix ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );

        // A negation in the current clause turns the matched wording into a
        // warning/limitation rather than an absolute marketing promise.
        if ( preg_match( '/\b(?:no|nunca|jam[aá]s|tampoco|ni)\b[^.!?;:]{0,120}$/iu', $prefix ) ) {
            continue;
        }

        $affirmative[] = $text;
    }

    return array_values( array_unique( $affirmative ) );
}

$editorialRules = array(
    'nvx_tokens' => array(
        'pattern'     => '/@nvx-[a-z0-9_:-]+/i',
        'description' => 'Unresolved NUVANX runtime token',
    ),
    'format_strings' => array(
        'pattern'     => '/%(?:\d+\$)?[sd]/',
        'description' => 'Unresolved format string',
    ),
    'markdown_links' => array(
        'pattern'     => '/\[[^\]]+\]\([^)]+\)/',
        'description' => 'Raw Markdown link',
    ),
    'markdown_headings' => array(
        'pattern'     => '/^#{1,6}\s+.+$/m',
        'description' => 'Raw Markdown heading',
    ),
    'markdown_lists' => array(
        'pattern'     => '/^\s*(?:[-+*]|\d+[.)])\s+\S+/m',
        'description' => 'Raw Markdown list marker',
    ),
    'draft_keywords' => array(
        'pattern'     => '/\b(?:borrador|pendiente de revisión|para revisar|work in progress)\b/i',
        'description' => 'Draft/review workflow language in published content',
    ),
    'generic_placeholders' => array(
        'pattern'     => '/\b(?:TODO|FIXME|XXX|HACK|placeholder)\b/i',
        'description' => 'Editorial placeholder in published content',
    ),
    'inline_styles' => array(
        'pattern'     => '/\sstyle\s*=\s*["\'][^"\']+["\']/i',
        'description' => 'Unauthorized inline style',
    ),
);

$blockedClaimPatterns = array(
    '/\bresultado(?:s)?\s+garantizado(?:s|as)?\b/i' => 'resultado garantizado',
    '/\b100\s*%\s*efectiv[oa]s?\b/i'               => '100% efectivo',
    '/\bsin\s+riesgos?\b/i'                        => 'sin riesgos',
    '/\bsin\s+efectos?\s+secundarios?\b/i'        => 'sin efectos secundarios',
    '/\binfalible\b/i'                              => 'infalible',
    '/\bnaturalidad\s+absoluta\b/i'                => 'naturalidad absoluta',
    '/\bcero\s+sobretratamiento\b/i'               => 'cero sobretratamiento',
    '/\bm[aá]rgenes?\s+de\s+seguridad\s+exactos?\b/i' => 'márgenes de seguridad exactos',
    '/\bsin\s+tiempo\s+de\s+inactividad\b/i'      => 'sin tiempo de inactividad',
    '/\bpiel\s+impecable\b/i'                      => 'piel impecable',
);

$ids = nvxPublicationPublishedIds();
$violations = array();
$passed = 0;

foreach ( $ids as $postId ) {
    $post = get_post( $postId );
    if ( ! ( $post instanceof WP_Post ) ) {
        continue;
    }

    $content = (string) $post->post_content;
    $errors = array();

    foreach ( $editorialRules as $ruleName => $rule ) {
        $matches = array();
        preg_match_all( $rule['pattern'], $content, $matches );
        if ( ! empty( $matches[0] ) ) {
            $errors[] = array(
                'rule'        => $ruleName,
                'description' => $rule['description'],
                'matches'     => array_values( array_unique( array_slice( $matches[0], 0, 5 ) ) ),
                'count'       => count( $matches[0] ),
            );
        }
    }

    foreach ( $blockedClaimPatterns as $pattern => $label ) {
        $matches = nvxEditorialAffirmativeClaimMatches( $content, $pattern );
        if ( ! empty( $matches ) ) {
            $errors[] = array(
                'rule'        => 'blocked_claim',
                'description' => 'Blocked affirmative absolute marketing/clinical claim',
                'matches'     => array_slice( $matches, 0, 5 ),
                'count'       => count( $matches ),
                'label'       => $label,
            );
        }
    }

    if ( class_exists( 'DOMDocument' ) && false !== stripos( $content, '<a ' ) ) {
        $dom = new DOMDocument();
        $previous = libxml_use_internal_errors( true );
        $dom->loadHTML( '<?xml encoding="UTF-8">' . $content );
        libxml_clear_errors();
        libxml_use_internal_errors( $previous );
        $homeHost = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );

        foreach ( $dom->getElementsByTagName( 'a' ) as $link ) {
            $href = trim( (string) $link->getAttribute( 'href' ) );
            if ( '' === $href || '#' === $href[0] || preg_match( '#^(?:mailto:|tel:|sms:|javascript:)#i', $href ) ) {
                continue;
            }

            $absolute = wp_http_validate_url( $href ) ? $href : home_url( '/' . ltrim( $href, '/' ) );
            $host = strtolower( (string) wp_parse_url( $absolute, PHP_URL_HOST ) );
            if ( '' === $host || $homeHost !== $host ) {
                continue;
            }

            $targetId = url_to_postid( $absolute );
            if ( $targetId <= 0 ) {
                continue;
            }

            $target = get_post( $targetId );
            if ( $target instanceof WP_Post && in_array( $target->post_type, array( 'page', 'post' ), true ) && 'publish' !== $target->post_status ) {
                $errors[] = array(
                    'rule'          => 'link_to_nonpublic_content',
                    'description'   => 'Internal link resolves to non-public page/post',
                    'matches'       => array( $href ),
                    'count'         => 1,
                    'target_id'     => (int) $targetId,
                    'target_status' => (string) $target->post_status,
                );
            }
        }
    }

    if ( empty( $errors ) ) {
        ++$passed;
        continue;
    }

    $violations[] = array(
        'post_id'   => (int) $post->ID,
        'post_type' => (string) $post->post_type,
        'slug'      => (string) $post->post_name,
        'title'     => (string) $post->post_title,
        'errors'    => $errors,
    );
}

$report = array(
    'schema'     => 'editorial-gate-validation',
    'checked_at' => gmdate( 'c' ),
    'source'     => home_url( '/' ),
    'summary'    => array(
        'total_checked' => count( $ids ),
        'passed'        => $passed,
        'failed'        => count( $violations ),
    ),
    'violations' => $violations,
);

echo wp_json_encode( $report );

if ( ! empty( $violations ) ) {
    fwrite( STDERR, sprintf( "EDITORIAL_GATE_VALIDATION=FAIL posts=%d\n", count( $violations ) ) );
    exit( 1 );
}

fwrite( STDERR, sprintf( "EDITORIAL_GATE_VALIDATION=PASS posts=%d\n", count( $ids ) ) );
