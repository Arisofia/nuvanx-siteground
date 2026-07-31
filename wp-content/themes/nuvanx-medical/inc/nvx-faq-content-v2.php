<?php
/**
 * Canonical home FAQ: one catalogue for visible HTML and Yoast schema.
 *
 * GEO pattern: first sentence answers the question directly (same model as Endolift FAQ).
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/** @return array<int,array{id:string,q:string,a:string}> */
function nvx_home_faq_v2_catalog(): array {
	static $catalog = null;
	if ( null !== $catalog ) {
		return $catalog;
	}

	$catalog = nvx_theme_load_json_catalog( 'nvx-home-faq-v2.json' );
	return $catalog;
}

function nvx_home_faq_v2_markup(): string {
    $html  = '<section id="nvx-home-faq" class="nvx-brand-section nvx-home-faq-editorial" aria-labelledby="nvx-home-faq-title" data-nvx-faq-source="canonical" data-nvx-home-content="faq-v2">';
    $html .= '<div class="nvx-shell nvx-brand-section__inner">';
    $html .= '<p class="nvx-eyebrow">' . esc_html__( 'Preguntas frecuentes', 'nuvanx-medical' ) . '</p>';
    $html .= '<h2 id="nvx-home-faq-title" class="nvx-brand-title">' . esc_html__( 'Información clara antes de decidir', 'nuvanx-medical' ) . '</h2>';
    $html .= '<div class="nvx-faq nvx-brand-faq-accordion">';
    foreach ( nvx_home_faq_v2_catalog() as $faq ) {
        $html .= '<details class="nvx-brand-faq-item" id="faq-' . esc_attr( $faq['id'] ) . '">';
        $html .= '<summary><span>' . esc_html( $faq['q'] ) . '</span></summary>';
        $html .= '<div class="nvx-brand-faq-content"><p>' . esc_html( $faq['a'] ) . '</p></div></details>';
    }
    return $html . '</div></div></section>';
}

function nvxHomeFaqV2NearestSection( DOMElement $node ): DOMElement {
    $current = $node;
    do {
        if ( 'section' === strtolower( $current->tagName ) ) {
            return $current;
        }
        $current = $current->parentNode;
    } while ( $current instanceof DOMElement );
    return $node;
}

/** @return array<int,DOMElement> */
function nvxHomeFaqV2Candidates( DOMXPath $xpath, DOMElement $root ): array {
    $found = array();
    $nodes = $xpath->query(
        './/*[contains(concat(" ",normalize-space(@class)," ")," nvx-home-faq-editorial ") '
        . 'or contains(concat(" ",normalize-space(@class)," ")," nvx-brand-faq-accordion ") '
        . 'or contains(concat(" ",normalize-space(@class)," ")," nvx-faq ")]',
        $root
    );
    if ( false !== $nodes ) {
        foreach ( $nodes as $node ) {
            if ( $node instanceof DOMElement ) {
                $section = nvxHomeFaqV2NearestSection( $node );
                $found[ spl_object_id( $section ) ] = $section;
            }
        }
    }
    return array_values( $found );
}

function nvxHomeFaqV2Import( DOMDocument $target ): ?DOMElement {
    $source = new DOMDocument( '1.0', 'UTF-8' );
    $source->loadHTML( '<?xml encoding="utf-8" ?>' . nvx_home_faq_v2_markup(), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
    $imported = $target->importNode( $source->documentElement, true );
    return $imported instanceof DOMElement ? $imported : null;
}

/** Replace candidates with imported FAQ element in DOM. */
function nvxHomeFaqV2ReplaceCandidates( DOMElement $import, array $candidates, DOMElement $root ): void {
    if ( ! empty( $candidates ) ) {
        $first = true;
        foreach ( $candidates as $section ) {
            if ( $first && $section->parentNode ) {
                $section->parentNode->replaceChild( $import, $section );
                $first = false;
            } elseif ( $section->parentNode ) {
                $section->parentNode->removeChild( $section );
            }
        }
    } else {
        $root->appendChild( $import );
    }
}

function nvxHomeFaqV2Transform( string $content ): string {
    if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ! is_front_page() || false !== strpos( $content, 'data-nvx-faq-source="canonical"' ) || ! class_exists( 'DOMDocument' ) ) {
        // Allow refresh when already marked: rebuild markup.
        if ( is_front_page() && false !== strpos( $content, 'data-nvx-faq-source="canonical"' ) ) {
            $refreshed = preg_replace(
                '/<section\b[^>]*\bid=["\']nvx-home-faq["\'][^>]*>[\s\S]*?<\/section>/iu',
                nvx_home_faq_v2_markup(),
                $content,
                1
            );
            return is_string( $refreshed ) ? $refreshed : $content;
        }
        return $content;
    }

    $previous = libxml_use_internal_errors( true );
    $document = new DOMDocument( '1.0', 'UTF-8' );
    $wrapped  = '<div id="nvx-home-faq-v2-root">' . $content . '</div>';
    if ( ! $document->loadHTML( '<?xml encoding="utf-8" ?>' . $wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD ) ) {
        libxml_clear_errors();
        libxml_use_internal_errors( $previous );
        return $content;
    }

    $root = $document->getElementById( 'nvx-home-faq-v2-root' );
    if ( ! $root ) {
        libxml_clear_errors();
        libxml_use_internal_errors( $previous );
        return $content;
    }

    $xpath      = new DOMXPath( $document );
    $candidates = nvxHomeFaqV2Candidates( $xpath, $root );
    $import     = nvxHomeFaqV2Import( $document );
    if ( ! $import ) {
        libxml_clear_errors();
        libxml_use_internal_errors( $previous );
        return $content;
    }

    nvxHomeFaqV2ReplaceCandidates( $import, $candidates, $root );

    $output = '';
    foreach ( $root->childNodes as $child ) {
        $output .= $document->saveHTML( $child );
    }
    libxml_clear_errors();
    libxml_use_internal_errors( $previous );
    return is_string( $output ) && '' !== trim( $output ) ? $output : $content;
}
add_filter( 'the_content', 'nvxHomeFaqV2Transform', 140 );

/** Return whether a Schema.org @type value contains the requested type. */
function nvxHomeFaqV2HasType( $types, string $type ): bool {
    return in_array( $type, is_array( $types ) ? $types : array( $types ), true );
}

/** Build Question nodes from the same catalogue used for visible HTML. */
function nvxHomeFaqV2SchemaEntities(): array {
    $entities = array();
    foreach ( nvx_home_faq_v2_catalog() as $faq ) {
        if ( empty( $faq['q'] ) || empty( $faq['a'] ) ) {
            continue;
        }
        $entities[] = array(
            '@type'          => 'Question',
            'name'           => $faq['q'],
            'acceptedAnswer' => array(
                '@type' => 'Answer',
                'text'  => $faq['a'],
            ),
        );
    }
    return $entities;
}

/** Find preferred Schema.org node index for homepage FAQ. */
function nvxHomeFaqV2FindPreferredSchemaIndex( array $graph ): ?int {
    $fallback_faq     = null;
    $fallback_webpage = null;
    foreach ( $graph as $index => $piece ) {
        if ( ! is_array( $piece ) || ! isset( $piece['@type'] ) ) {
            continue;
        }
        $is_faq = nvxHomeFaqV2HasType( $piece['@type'], 'FAQPage' );
        $is_web = nvxHomeFaqV2HasType( $piece['@type'], 'WebPage' );
        if ( $is_faq && $is_web ) {
            return $index;
        }
        if ( $is_faq && null === $fallback_faq ) {
            $fallback_faq = $index;
        }
        if ( $is_web && null === $fallback_webpage ) {
            $fallback_webpage = $index;
        }
    }
    return null !== $fallback_faq ? $fallback_faq : $fallback_webpage;
}

/**
 * Consolidate the homepage FAQ into one Yoast graph node.
 *
 * Preference order: an existing WebPage+FAQPage, an existing FAQPage, an
 * existing WebPage, or a new FAQPage. Every other FAQPage node is removed.
 */
function nvxHomeFaqV2SchemaGraph( array $graph, $context = null ): array {
    if ( ! is_front_page() ) {
        return $graph;
    }

    $preferred = nvxHomeFaqV2FindPreferredSchemaIndex( $graph );
    if ( null === $preferred ) {
        $graph[]   = array(
            '@type' => array( 'WebPage', 'FAQPage' ),
            '@id'   => home_url( '/#webpage' ),
            'url'   => home_url( '/' ),
        );
        $preferred = array_key_last( $graph );
    }

    $types = isset( $graph[ $preferred ]['@type'] ) && is_array( $graph[ $preferred ]['@type'] )
        ? $graph[ $preferred ]['@type']
        : array( $graph[ $preferred ]['@type'] ?? 'WebPage' );
    if ( ! in_array( 'FAQPage', $types, true ) ) {
        $types[] = 'FAQPage';
    }
    $graph[ $preferred ]['@type']      = array_values( array_unique( array_filter( $types ) ) );
    $graph[ $preferred ]['mainEntity'] = nvxHomeFaqV2SchemaEntities();
    $graph[ $preferred ]['url']        = $graph[ $preferred ]['url'] ?? home_url( '/' );
    $graph[ $preferred ]['@id']        = $graph[ $preferred ]['@id'] ?? home_url( '/#webpage' );

    foreach ( array_keys( $graph ) as $index ) {
        if ( $index === $preferred || ! isset( $graph[ $index ]['@type'] ) ) {
            continue;
        }
        if ( nvxHomeFaqV2HasType( $graph[ $index ]['@type'], 'FAQPage' ) ) {
            unset( $graph[ $index ] );
        }
    }

    return array_values( $graph );
}
add_filter( 'wpseo_schema_graph', 'nvxHomeFaqV2SchemaGraph', 99, 2 );
