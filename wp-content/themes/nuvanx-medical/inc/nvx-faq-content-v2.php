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
	require_once __DIR__ . '/nvx-catalog-json.php';

	return nvx_catalog_filter_records(
		nvx_catalog_json_resolved( 'home-faq-v2.json' ),
		array( 'id', 'q', 'a' ),
		'home-faq-v2.json'
	);
}

function nvx_home_faq_v2_markup(): string {
	$html  = '<section id="nvx-home-faq" class="nvx-brand-section nvx-home-faq-editorial" aria-labelledby="nvx-home-faq-title" data-nvx-faq-source="canonical" data-nvx-home-content="faq-v2">';
	$html .= '<div class="nvx-shell nvx-brand-section__inner">';
	$html .= '<p class="nvx-brand-kicker">' . esc_html__( 'Preguntas frecuentes', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-home-faq-title" class="nvx-brand-title">' . esc_html__( 'Información clara antes de decidir', 'nuvanx-medical' ) . '</h2>';
	$html .= '<div class="nvx-faq nvx-brand-faq-accordion">';
	foreach ( nvx_home_faq_v2_catalog() as $faq ) {
		$html .= '<details class="nvx-brand-faq-item" id="faq-' . esc_attr( $faq['id'] ) . '">';
		$html .= '<summary><span>' . esc_html( $faq['q'] ) . '</span></summary>';
		$html .= '<div class="nvx-brand-faq-content"><p>' . esc_html( $faq['a'] ) . '</p></div></details>';
	}
	return $html . '</div></div></section>';
}

function nvx_home_faq_v2_nearest_section( DOMElement $node ): DOMElement {
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
function nvx_home_faq_v2_candidates( DOMXPath $xpath, DOMElement $root ): array {
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
				$section = nvx_home_faq_v2_nearest_section( $node );
				$found[ spl_object_id( $section ) ] = $section;
			}
		}
	}
	return array_values( $found );
}

function nvx_home_faq_v2_import( DOMDocument $target ): ?DOMElement {
	$source = new DOMDocument( '1.0', 'UTF-8' );
	$source->loadHTML( '<?xml encoding="utf-8" ?>' . nvx_home_faq_v2_markup(), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
	$imported = $target->importNode( $source->documentElement, true );
	return $imported instanceof DOMElement ? $imported : null;
}

/**
 * Refresh already-canonical FAQ markup on the homepage.
 */
function nvx_home_faq_v2_refresh_canonical( string $content ): string {
	$refreshed = preg_replace(
		'/<section\b[^>]*\bid=["\']nvx-home-faq["\'][^>]*>[\s\S]*?<\/section>/iu',
		nvx_home_faq_v2_markup(),
		$content,
		1
	);
	return is_string( $refreshed ) ? $refreshed : $content;
}

/**
 * Whether the homepage FAQ transform should run a full DOM rebuild.
 */
function nvx_home_faq_v2_should_transform( string $content ): bool {
	if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return false;
	}
	if ( ! is_front_page() || ! class_exists( 'DOMDocument' ) ) {
		return false;
	}
	return false === strpos( $content, 'data-nvx-faq-source="canonical"' );
}

/**
 * Replace or append the canonical FAQ import among candidate sections.
 *
 * @param array<int,DOMElement> $candidates FAQ section candidates.
 */
function nvx_home_faq_v2_apply_import( DOMElement $root, DOMElement $import, array $candidates ): void {
	if ( empty( $candidates ) ) {
		$root->appendChild( $import );
		return;
	}

	$first = true;
	foreach ( $candidates as $section ) {
		if ( $first && $section->parentNode ) {
			$section->parentNode->replaceChild( $import, $section );
			$first = false;
			continue;
		}
		if ( $section->parentNode ) {
			$section->parentNode->removeChild( $section );
		}
	}
}

/**
 * Serialize the temporary DOM root back to HTML.
 */
function nvx_home_faq_v2_serialize_root( DOMDocument $document, DOMElement $root, string $fallback ): string {
	$output = '';
	foreach ( $root->childNodes as $child ) {
		$output .= $document->saveHTML( $child );
	}
	return is_string( $output ) && '' !== trim( $output ) ? $output : $fallback;
}

function nvx_home_faq_v2_transform( string $content ): string {
	if ( ! nvx_home_faq_v2_should_transform( $content ) ) {
		// Allow refresh when already marked: rebuild markup.
		if ( is_front_page() && false !== strpos( $content, 'data-nvx-faq-source="canonical"' ) ) {
			return nvx_home_faq_v2_refresh_canonical( $content );
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
	$candidates = nvx_home_faq_v2_candidates( $xpath, $root );
	$import     = nvx_home_faq_v2_import( $document );
	if ( ! $import ) {
		libxml_clear_errors();
		libxml_use_internal_errors( $previous );
		return $content;
	}

	nvx_home_faq_v2_apply_import( $root, $import, $candidates );
	$output = nvx_home_faq_v2_serialize_root( $document, $root, $content );
	libxml_clear_errors();
	libxml_use_internal_errors( $previous );
	return $output;
}
add_filter( 'the_content', 'nvx_home_faq_v2_transform', 140 );

/** Return whether a Schema.org @type value contains the requested type. */
function nvx_home_faq_v2_has_type( $types, string $type ): bool {
	return in_array( $type, is_array( $types ) ? $types : array( $types ), true );
}

/** Build Question nodes from the same catalogue used for visible HTML. */
function nvx_home_faq_v2_schema_entities(): array {
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

/**
 * Pick the preferred graph index for homepage FAQ consolidation.
 *
 * Preference order: WebPage+FAQPage, FAQPage, WebPage.
 *
 * @param array<int,mixed> $graph Yoast graph.
 * @return int|string|null
 */
function nvx_home_faq_v2_preferred_schema_index( array $graph ) {
	$preferred        = null;
	$fallback_faq     = null;
	$fallback_webpage = null;

	foreach ( $graph as $index => $piece ) {
		if ( ! is_array( $piece ) || ! isset( $piece['@type'] ) ) {
			continue;
		}
		$is_faq = nvx_home_faq_v2_has_type( $piece['@type'], 'FAQPage' );
		$is_web = nvx_home_faq_v2_has_type( $piece['@type'], 'WebPage' );
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
 * Ensure the preferred node carries FAQPage + mainEntity and drop duplicates.
 *
 * @param array<int,array<string,mixed>> $graph Yoast graph.
 * @param int|string                     $preferred Preferred index.
 * @return array<int,array<string,mixed>>
 */
function nvx_home_faq_v2_apply_schema_entities( array $graph, $preferred ): array {
	$types = isset( $graph[ $preferred ]['@type'] ) && is_array( $graph[ $preferred ]['@type'] )
		? $graph[ $preferred ]['@type']
		: array( $graph[ $preferred ]['@type'] ?? 'WebPage' );
	if ( ! in_array( 'FAQPage', $types, true ) ) {
		$types[] = 'FAQPage';
	}
	$graph[ $preferred ]['@type']      = array_values( array_unique( array_filter( $types ) ) );
	$graph[ $preferred ]['mainEntity'] = nvx_home_faq_v2_schema_entities();
	$graph[ $preferred ]['url']        = $graph[ $preferred ]['url'] ?? home_url( '/' );
	$graph[ $preferred ]['@id']        = $graph[ $preferred ]['@id'] ?? home_url( '/#webpage' );

	foreach ( array_keys( $graph ) as $index ) {
		if ( $index === $preferred || ! isset( $graph[ $index ]['@type'] ) ) {
			continue;
		}
		if ( nvx_home_faq_v2_has_type( $graph[ $index ]['@type'], 'FAQPage' ) ) {
			unset( $graph[ $index ] );
		}
	}

	return array_values( $graph );
}

/**
 * Consolidate the homepage FAQ into one Yoast graph node.
 *
 * Preference order: an existing WebPage+FAQPage, an existing FAQPage, an
 * existing WebPage, or a new FAQPage. Every other FAQPage node is removed.
 */
function nvx_home_faq_v2_schema_graph( array $graph, $context = null ): array {
	unset( $context );
	if ( ! is_front_page() ) {
		return $graph;
	}

	$preferred = nvx_home_faq_v2_preferred_schema_index( $graph );
	if ( null === $preferred ) {
		$graph[]   = array(
			'@type' => array( 'WebPage', 'FAQPage' ),
			'@id'   => home_url( '/#webpage' ),
			'url'   => home_url( '/' ),
		);
		$preferred = array_key_last( $graph );
	}

	return nvx_home_faq_v2_apply_schema_entities( $graph, $preferred );
}
add_filter( 'wpseo_schema_graph', 'nvx_home_faq_v2_schema_graph', 99, 2 );
