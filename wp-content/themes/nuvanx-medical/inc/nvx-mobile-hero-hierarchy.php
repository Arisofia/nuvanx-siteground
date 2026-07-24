<?php
/**
 * Global hero hierarchy.
 *
 * Keeps identity, H1, actions and local proof inside the hero while moving
 * explanatory clinical copy into a readable block immediately after it.
 * Applies by structural classes, not page IDs.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue the responsive hierarchy and the canonical institutional hero layer.
 *
 * Dependency handles must match functions.php (`nvx-patterns`).
 */
function nvx_enqueue_mobile_hero_hierarchy(): void {
	$hierarchy_relative = 'assets/css/nvx-mobile-hero-hierarchy.css';
	$canonical_relative = 'assets/css/nvx-canonical-page-hero.css';

	wp_enqueue_style(
		'nvx-mobile-hero-hierarchy',
		get_template_directory_uri() . '/' . $hierarchy_relative,
		array( 'nvx-patterns' ),
		nvx_asset_version( $hierarchy_relative )
	);

	wp_enqueue_style(
		'nvx-canonical-page-hero',
		get_template_directory_uri() . '/' . $canonical_relative,
		array( 'nvx-mobile-hero-hierarchy' ),
		nvx_asset_version( $canonical_relative )
	);
}
add_action( 'wp_enqueue_scripts', 'nvx_enqueue_mobile_hero_hierarchy', 40 );

/**
 * Test whether a DOM node has a class.
 */
function nvx_dom_node_has_class( DOMElement $node, string $class_name ): bool {
	$classes = preg_split( '/\s+/', trim( $node->getAttribute( 'class' ) ) ) ?: array();
	return in_array( $class_name, $classes, true );
}

/**
 * Add one class token without duplicating existing classes.
 */
function nvx_dom_node_add_class( DOMElement $node, string $class_name ): void {
	$classes = preg_split( '/\s+/', trim( $node->getAttribute( 'class' ) ) ) ?: array();
	$classes[] = $class_name;
	$classes = array_values( array_unique( array_filter( $classes ) ) );
	$node->setAttribute( 'class', implode( ' ', $classes ) );
}

/**
 * Remove one class token while preserving the remaining class list.
 */
function nvx_dom_node_remove_class( DOMElement $node, string $class_name ): void {
	$classes = preg_split( '/\s+/', trim( $node->getAttribute( 'class' ) ) ) ?: array();
	$classes = array_values(
		array_filter(
			$classes,
			static fn( string $class ): bool => $class !== $class_name
		)
	);
	$node->setAttribute( 'class', implode( ' ', $classes ) );
}

/**
 * Pages governed by the same institutional header design.
 */
function nvx_is_canonical_page_header_request(): bool {
	return is_page(
		array(
			'protocolos-signature',
			'medicina-estetica-laser',
			'exion-fractional',
			'equipo-medico',
		)
	);
}

/**
 * Identify explanatory paragraphs that must live below hero media.
 */
function nvx_is_explanatory_hero_node( DOMNode $node ): bool {
	if ( ! $node instanceof DOMElement || 'p' !== strtolower( $node->tagName ) ) {
		return false;
	}

	$movable_classes = array(
		'nvx-lead',
		'nvx-hero__lead',
		'nvx-page-hero__lead',
		'nvx-subtitle',
		'nvx-hero-subtitle',
	);

	foreach ( $movable_classes as $class_name ) {
		if ( nvx_dom_node_has_class( $node, $class_name ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Normalize the structure of a governed hero before its explanatory copy moves.
 */
function nvx_normalize_canonical_page_hero( DOMXPath $xpath, DOMElement $hero ): void {
	nvx_dom_node_add_class( $hero, 'nvx-canonical-page-hero' );

	$media_nodes = $xpath->query(
		'.//*[contains(concat(" ", normalize-space(@class), " "), " nvx-brand-hero__media ") or contains(concat(" ", normalize-space(@class), " "), " nvx-page-hero__media ")]',
		$hero
	);

	if ( false !== $media_nodes ) {
		$removable = array();
		foreach ( $media_nodes as $media ) {
			if ( $media instanceof DOMElement ) {
				$removable[] = $media;
			}
		}
		foreach ( $removable as $media ) {
			if ( $media->parentNode ) {
				$media->parentNode->removeChild( $media );
			}
		}
	}
}

/**
 * Find the copy node that owns the institutional hero hierarchy.
 */
function nvx_find_hero_copy_node( DOMXPath $xpath, DOMElement $hero, bool $canonical ): ?DOMElement {
	$query = $canonical
		? './/*[contains(concat(" ", normalize-space(@class), " "), " nvx-editorial-hero__copy ") or contains(concat(" ", normalize-space(@class), " "), " nvx-editorial-hero__copy-copy ") or contains(concat(" ", normalize-space(@class), " "), " nvx-brand-hero__copy ") or contains(concat(" ", normalize-space(@class), " "), " nvx-page-hero__copy ") or contains(concat(" ", normalize-space(@class), " "), " nvx-hero__copy ")]'
		: './/*[contains(concat(" ", normalize-space(@class), " "), " nvx-editorial-hero__copy ") or contains(concat(" ", normalize-space(@class), " "), " nvx-page-hero__copy ") or contains(concat(" ", normalize-space(@class), " "), " nvx-hero__copy ")]';

	$copy_nodes = $xpath->query( $query, $hero );
	if ( false === $copy_nodes || 0 === $copy_nodes->length ) {
		return null;
	}

	$copy = $copy_nodes->item( 0 );
	if ( ! $copy instanceof DOMElement ) {
		return null;
	}

	if ( $canonical ) {
		nvx_dom_node_remove_class( $copy, 'nvx-editorial-hero__copy-copy' );
		nvx_dom_node_add_class( $copy, 'nvx-editorial-hero__copy' );
	}

	return $copy;
}

/**
 * Split explanatory hero paragraphs into a post-hero reading block.
 */
function nvx_split_hero_explanatory_copy( string $content ): string {
	if (
		is_admin()
		|| wp_doing_ajax()
		|| ( defined( 'REST_REQUEST' ) && REST_REQUEST )
		|| false === stripos( $content, 'hero' )
		|| false !== stripos( $content, 'nvx-hero-context--generated' )
		|| false !== stripos( $content, 'nvx-hero-intro--generated' )
		|| ! class_exists( 'DOMDocument' )
	) {
		return $content;
	}

	$previous_errors = libxml_use_internal_errors( true );
	$document        = new DOMDocument( '1.0', 'UTF-8' );
	$wrapped         = '<div id="nvx-content-transform-root">' . $content . '</div>';
	$loaded          = $document->loadHTML(
		'<?xml encoding="utf-8" ?>' . $wrapped,
		LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
	);

	if ( ! $loaded ) {
		libxml_clear_errors();
		libxml_use_internal_errors( $previous_errors );
		return $content;
	}

	$xpath  = new DOMXPath( $document );
	$heroes = $xpath->query(
		'//*[@id="nvx-content-transform-root"]//*[contains(concat(" ", normalize-space(@class), " "), " nvx-brand-hero ") or contains(concat(" ", normalize-space(@class), " "), " nvx-editorial-hero ") or contains(concat(" ", normalize-space(@class), " "), " nvx-page-hero ") or contains(concat(" ", normalize-space(@class), " "), " nvx-hero-section ")]'
	);

	if ( false === $heroes ) {
		libxml_clear_errors();
		libxml_use_internal_errors( $previous_errors );
		return $content;
	}

	$hero_nodes = array();
	foreach ( $heroes as $hero ) {
		if ( $hero instanceof DOMElement ) {
			$hero_nodes[] = $hero;
		}
	}

	$canonical = nvx_is_canonical_page_header_request();

	foreach ( $hero_nodes as $hero ) {
		if ( $canonical ) {
			nvx_normalize_canonical_page_hero( $xpath, $hero );
		}

		$copy = nvx_find_hero_copy_node( $xpath, $hero, $canonical );
		if ( ! $copy instanceof DOMElement ) {
			continue;
		}

		$movable = array();
		foreach ( iterator_to_array( $copy->childNodes ) as $child ) {
			if ( nvx_is_explanatory_hero_node( $child ) ) {
				$movable[] = $child;
			}
		}

		if ( array() === $movable || ! $hero->parentNode ) {
			continue;
		}

		$section = $document->createElement( 'section' );
		$section->setAttribute( 'class', 'nvx-brand-section nvx-hero-intro nvx-hero-intro--generated' );
		$section->setAttribute( 'data-nvx-hero-context', 'clinical-introduction' );
		$section->setAttribute( 'aria-label', 'Introducción clínica' );

		$inner = $document->createElement( 'div' );
		$inner->setAttribute( 'class', 'nvx-brand-section__inner' );

		$readable = $document->createElement( 'div' );
		$readable->setAttribute( 'class', 'nvx-brand-readable nvx-brand-readable--wide' );

		foreach ( $movable as $index => $paragraph ) {
			if ( $paragraph instanceof DOMElement ) {
				$existing = trim( $paragraph->getAttribute( 'class' ) );
				$extra = 0 === $index
					? 'nvx-brand-body nvx-hero-context__lead'
					: 'nvx-brand-body nvx-hero-context__description';
				$paragraph->setAttribute( 'class', trim( $existing . ' ' . $extra ) );
			}
			$readable->appendChild( $paragraph );
		}

		$inner->appendChild( $readable );
		$section->appendChild( $inner );

		if ( $hero->nextSibling ) {
			$hero->parentNode->insertBefore( $section, $hero->nextSibling );
		} else {
			$hero->parentNode->appendChild( $section );
		}
	}

	$root = $document->getElementById( 'nvx-content-transform-root' );
	if ( ! $root ) {
		libxml_clear_errors();
		libxml_use_internal_errors( $previous_errors );
		return $content;
	}

	$output = '';
	foreach ( $root->childNodes as $child ) {
		$output .= $document->saveHTML( $child );
	}

	libxml_clear_errors();
	libxml_use_internal_errors( $previous_errors );

	return is_string( $output ) && '' !== trim( $output ) ? $output : $content;
}
add_filter( 'the_content', 'nvx_split_hero_explanatory_copy', 140 );
