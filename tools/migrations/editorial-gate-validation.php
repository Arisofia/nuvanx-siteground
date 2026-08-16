<?php
/**
 * Read-only editorial gate for public WordPress content.
 *
 * @package NVX\Migrations
 */

if ( ! defined( 'ABSPATH' ) ) {
    fwrite( STDERR, "EDITORIAL_GATE_VALIDATION=FAIL reason=wordpress_not_loaded\n" );
    exit( 1 );
}

require_once __DIR__ . '/publication-contract-lib.php';

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
        // Keep this restricted to actual workflow residue. Natural patient copy
        // such as "valoración para revisar el perfil" is not a draft marker.
        'pattern'     => '/\b(?:borrador|pendiente de revisión|marcad[oa]\s+para\s+revisión|pendiente\s+por\s+revisar|work in progress)\b/iu',
        'description' => 'Draft/review workflow language in published content',
    ),
    'generic_placeholders' => array(
        // Require explicit editorial-marker syntax. Bare "placeholder" is a
        // legitimate HTML/data attribute used by Complianz and form controls.
        'pattern'     => '/(?:\[(?:TODO|FIXME|XXX|HACK|PLACEHOLDER)\]|\b(?:TODO|FIXME|XXX|HACK|PLACEHOLDER)\b\s*[:\-—–]\s*)/iu',
        'description' => 'Editorial placeholder in published content',
    ),
    'inline_styles' => array(
        'pattern'     => '/\sstyle\s*=\s*["\'][^"\']+["\']/i',
        'description' => 'Unauthorized inline style',
    ),
);

$blockedClaimPatterns = array(
    '/\bresultado(?:s)?\s+garantizado(?:s|as)?\b/iu' => 'resultado garantizado',
    '/\b100\s*%\s*efectiv[oa]s?\b/iu'               => '100% efectivo',
    '/\bsin\s+riesgos?\b/iu'                        => 'sin riesgos',
    '/\bsin\s+efectos?\s+secundarios?\b/iu'        => 'sin efectos secundarios',
    '/\binfalible\b/iu'                              => 'infalible',
    '/\bnaturalidad\s+absoluta\b/iu'                => 'naturalidad absoluta',
    '/\bcero\s+sobretratamiento\b/iu'               => 'cero sobretratamiento',
    '/\bm[aá]rgenes?\s+de\s+seguridad\s+exactos?\b/iu' => 'márgenes de seguridad exactos',
    '/\bsin\s+tiempo\s+de\s+inactividad\b/iu'      => 'sin tiempo de inactividad',
    '/\bpiel\s+impecable\b/iu'                      => 'piel impecable',
);

/**
 * Decide whether a blocked phrase is being quoted as something to avoid or
 * explicitly negated rather than asserted as a NUVANX claim.
 */
function nvxEditorialClaimIsAdvisoryContext( string $content, int $offset ): bool {
    $windowStart = max( 0, $offset - 220 );
    $beforeRaw   = substr( $content, $windowStart, $offset - $windowStart );
    $before      = html_entity_decode( wp_strip_all_tags( $beforeRaw ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
    $before      = preg_replace( '/\s+/u', ' ', $before ) ?? $before;

    // "No significa/supone/convierte ... sin riesgos" is risk disclosure,
    // not a risk-free claim. Restrict the context to the current sentence.
    if ( preg_match( '/\bno\s+(?:significa|implica|supone|convierte|equivale)\b[^.!?]{0,170}$/iu', $before ) ) {
        return true;
    }

    // "Desconfía/evita promesas de resultado garantizado" explicitly warns
    // against the prohibited promise and must remain publishable.
    if ( preg_match( '/\b(?:desconf[ií]a|evita|rechaza|cuestiona)\b[^.!?]{0,150}\b(?:promesas?|garant[ií]as?)\b[^.!?]{0,80}$/iu', $before ) ) {
        return true;
    }

    return false;
}

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
        $matches = array();
        preg_match_all( $pattern, $content, $matches, PREG_OFFSET_CAPTURE );
        if ( empty( $matches[0] ) ) {
            continue;
        }

        $assertedMatches = array();
        foreach ( $matches[0] as $match ) {
            $matchedText = (string) ( $match[0] ?? '' );
            $offset      = (int) ( $match[1] ?? -1 );
            if ( $offset >= 0 && nvxEditorialClaimIsAdvisoryContext( $content, $offset ) ) {
                continue;
            }
            $assertedMatches[] = $matchedText;
        }

        if ( ! empty( $assertedMatches ) ) {
            $errors[] = array(
                'rule'        => 'blocked_claim',
                'description' => 'Blocked absolute marketing/clinical claim',
                'matches'     => array_values( array_unique( array_slice( $assertedMatches, 0, 5 ) ) ),
                'count'       => count( $assertedMatches ),
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