<?php
/**
 * Global hero hierarchy.
 *
 * Keeps identity, H1, actions and local proof over hero media while moving
 * explanatory clinical copy into a readable block immediately after the hero.
 * Applies by structural classes, not page IDs.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue the responsive hero hierarchy after the canonical editorial patterns.
 */
function nvx_enqueue_mobile_hero_hierarchy(): void {
	$relative = '/assets/css/nvx-mobile-hero-hierarchy.css';
	$file     = get_stylesheet_directory() . $relative;

	wp_enqueue_style(
		'nvx-mobile-hero-hierarchy',
		get_stylesheet_directory_uri() . $relative,
		array( 'nvx-patterns-editorial' ),
		file_exists( $file ) ? (string) filemtime( $file ) : null
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
 * Identify explanatory paragraphs that must live below hero media.
 */
function nvx_is_explanatory_hero_node( DOMNode $node ): bool {
	if ( ! $node instanceof DOMElement || 'p' !== strtolower( $node->tagName ) ) {
		return false;
	}

	$movable_classes = array(
		'nvx-brand-hero__lead',
		'nvx-brand-hero__description',
		'nvx-hero__lead',
		'nvx-page-hero__lead',
		'nvx-lead',
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
 * Split explanatory hero paragraphs into a post-media reading block.
 */
function nvx_split_hero_explanatory_copy( string $content ): string {
	if (
		is_admin()
		|| wp_doing_ajax()
		|| ( defined( 'REST_REQUEST' ) && REST_REQUEST )
		|| false === stripos( $content, 'hero' )
		|| false !== stripos( $content, 'nvx-hero-context--generated' )
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

	$xpath = new DOMXPath( $document );
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

	foreach ( $hero_nodes as $hero ) {
		$copy_nodes = $xpath->query(
			'.//*[contains(concat(" ", normalize-space(@class), " "), " nvx-brand-hero__copy ") or contains(concat(" ", normalize-space(@class), " "), " nvx-editorial-hero__copy ") or contains(concat(" ", normalize-space(@class), " "), " nvx-page-hero__copy ") or contains(concat(" ", normalize-space(@class), " "), " nvx-hero__copy ")]',
			$hero
		);

		if ( false === $copy_nodes || 0 === $copy_nodes->length ) {
			continue;
		}

		$copy = $copy_nodes->item( 0 );
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

		$context = $document->createElement( 'div' );
		$context->setAttribute( 'class', 'nvx-hero-context nvx-hero-context--generated' );
		$context->setAttribute( 'data-nvx-hero-context', 'clinical-introduction' );

		$inner = $document->createElement( 'div' );
		$inner->setAttribute( 'class', 'nvx-hero-context__inner' );

		foreach ( $movable as $index => $paragraph ) {
			if ( $paragraph instanceof DOMElement ) {
				$existing = trim( $paragraph->getAttribute( 'class' ) );
				$extra    = 0 === $index ? 'nvx-hero-context__lead' : 'nvx-hero-context__description';
				$paragraph->setAttribute( 'class', trim( $existing . ' ' . $extra ) );
			}
			$inner->appendChild( $paragraph );
		}

		$context->appendChild( $inner );

		if ( $hero->nextSibling ) {
			$hero->parentNode->insertBefore( $context, $hero->nextSibling );
		} else {
			$hero->parentNode->appendChild( $context );
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
