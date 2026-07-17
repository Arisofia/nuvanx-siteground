<?php
/**
 * Clinics hub navigation and unambiguous external map actions.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function nvx_is_clinics_hub(): bool {
	if ( ! is_page() ) {
		return false;
	}

	return 'clinicas-de-medicina-estetica-nuvanx' === (string) get_post_field( 'post_name', get_queried_object_id() );
}

function nvx_clinics_map_url( string $clinic ): string {
	$query = 'goya' === $clinic
		? 'NUVANX Medicina Estética Láser Salamanca Goya Madrid'
		: 'NUVANX Medicina Estética Láser Chamberí Madrid';

	return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( $query );
}

function nvx_clinics_nearest_block( DOMNode $node ): ?DOMElement {
	$current = $node;
	while ( $current instanceof DOMNode && $current->parentNode ) {
		if ( $current instanceof DOMElement && in_array( strtolower( $current->tagName ), array( 'section', 'article' ), true ) ) {
			return $current;
		}
		$current = $current->parentNode;
	}
	return null;
}

/**
 * Remove reading-measure classes when a CMS wrapper contains complete page
 * sections rather than prose. A plain block element then fills the canonical
 * section shell while genuine nested readable columns remain constrained.
 */
function nvx_clinics_normalize_layout( DOMXPath $xpath ): ?DOMElement {
	$nodes = $xpath->query(
		'//*[contains(concat(" ", normalize-space(@class), " "), " nvx-brand-readable ")]'
	);

	if ( false === $nodes ) {
		return null;
	}

	$layout_root = null;
	foreach ( iterator_to_array( $nodes ) as $node ) {
		if ( ! $node instanceof DOMElement ) {
			continue;
		}

		$structural_children = $xpath->query( './/section|.//article', $node );
		if ( false === $structural_children || $structural_children->length < 2 ) {
			continue;
		}

		$classes = preg_split( '/\s+/', trim( $node->getAttribute( 'class' ) ) ) ?: array();
		$classes = array_values(
			array_filter(
				$classes,
				static function ( string $class_name ): bool {
					return ! in_array( $class_name, array( 'nvx-brand-readable', 'nvx-brand-readable--wide' ), true );
				}
			)
		);
		$classes[] = 'nvx-clinics-content-flow';
		$node->setAttribute( 'class', implode( ' ', array_unique( $classes ) ) );
		$layout_root ??= $node;
	}

	return $layout_root;
}

function nvx_clinics_set_link_attributes( DOMElement $link, string $clinic ): void {
	$name = 'goya' === $clinic ? 'NUVANX Salamanca–Goya' : 'NUVANX Chamberí';
	$link->setAttribute( 'href', nvx_clinics_map_url( $clinic ) );
	$link->setAttribute( 'target', '_blank' );
	$link->setAttribute( 'rel', 'noopener noreferrer' );
	$link->setAttribute( 'aria-label', 'Abrir ' . $name . ' en Google Maps' );
	$link->nodeValue = 'Abrir en Google Maps';

	$class = trim( $link->getAttribute( 'class' ) . ' nvx-button nvx-button--primary nvx-clinic-map-cta' );
	$link->setAttribute( 'class', implode( ' ', array_unique( preg_split( '/\s+/', $class ) ?: array() ) ) );
}

function nvx_clinics_hub_enhance( string $content ): string {
	if ( is_admin() || ! nvx_is_clinics_hub() || '' === trim( $content ) ) {
		return $content;
	}

	$previous = libxml_use_internal_errors( true );
	$dom      = new DOMDocument( '1.0', 'UTF-8' );
	$wrapper  = '<div id="nvx-clinics-document">' . $content . '</div>';
	$loaded   = $dom->loadHTML( '<?xml encoding="utf-8" ?>' . $wrapper, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
	libxml_clear_errors();
	libxml_use_internal_errors( $previous );

	if ( ! $loaded ) {
		return $content;
	}

	$xpath       = new DOMXPath( $dom );
	$layout_root = nvx_clinics_normalize_layout( $xpath );
	$clinics     = array(
		'chamberi' => array( 'id' => 'clinica-chamberi', 'label' => 'Chamberí', 'match' => '/chamber[ií]/iu' ),
		'goya'     => array( 'id' => 'clinica-goya', 'label' => 'Salamanca–Goya', 'match' => '/(?:salamanca|goya)/iu' ),
	);
	$blocks      = array();

	foreach ( $xpath->query( '//h2|//h3|//h4' ) ?: array() as $heading ) {
		$text = trim( preg_replace( '/\s+/u', ' ', $heading->textContent ) ?? $heading->textContent );
		foreach ( $clinics as $key => $config ) {
			if ( isset( $blocks[ $key ] ) || ! preg_match( $config['match'], $text ) ) {
				continue;
			}
			$block = nvx_clinics_nearest_block( $heading );
			if ( $block ) {
				$block->setAttribute( 'id', $config['id'] );
				$block->setAttribute( 'class', trim( $block->getAttribute( 'class' ) . ' nvx-clinic-location' ) );
				$blocks[ $key ] = $block;
			}
		}
	}

	foreach ( $blocks as $key => $block ) {
		$links = $xpath->query( './/a', $block );
		$map_action_seen = false;
		foreach ( $links ?: array() as $link ) {
			if ( ! $link instanceof DOMElement ) {
				continue;
			}
			$text = trim( preg_replace( '/\s+/u', ' ', $link->textContent ) ?? $link->textContent );
			$href = $link->getAttribute( 'href' );
			$is_map_action = preg_match( '/(?:cómo llegar|como llegar|google maps|maps\.app|google\.[^\/]+\/maps)/iu', $text . ' ' . $href );
			if ( $is_map_action && ! $map_action_seen ) {
				nvx_clinics_set_link_attributes( $link, $key );
				$map_action_seen = true;
			} elseif ( $is_map_action ) {
				$link->parentNode?->removeChild( $link );
			}
		}

		if ( ! $map_action_seen ) {
			$link = $dom->createElement( 'a', 'Abrir en Google Maps' );
			nvx_clinics_set_link_attributes( $link, $key );
			$actions = $dom->createElement( 'div' );
			$actions->setAttribute( 'class', 'nvx-brand-actions nvx-clinic-location__actions' );
			$actions->appendChild( $link );
			$block->appendChild( $actions );
		}
	}

	if ( isset( $blocks['chamberi'], $blocks['goya'] ) && ! $dom->getElementById( 'nvx-clinics-nav' ) ) {
		$nav = $dom->createElement( 'nav' );
		$nav->setAttribute( 'id', 'nvx-clinics-nav' );
		$nav->setAttribute( 'class', 'nvx-clinics-nav' );
		$nav->setAttribute( 'aria-label', 'Navegación entre las clínicas NUVANX en Madrid' );
		$inner = $dom->createElement( 'div' );
		$inner->setAttribute( 'class', 'nvx-shell nvx-clinics-nav__inner' );
		foreach ( $clinics as $config ) {
			$link = $dom->createElement( 'a', $config['label'] );
			$link->setAttribute( 'href', '#' . $config['id'] );
			$link->setAttribute( 'class', 'nvx-clinics-nav__link' );
			$inner->appendChild( $link );
		}
		$nav->appendChild( $inner );

		if ( $layout_root instanceof DOMElement ) {
			$layout_root->insertBefore( $nav, $layout_root->firstChild );
		} else {
			$blocks['chamberi']->parentNode?->insertBefore( $nav, $blocks['chamberi'] );
		}
	}

	$root = $dom->getElementById( 'nvx-clinics-document' );
	if ( ! $root ) {
		return $content;
	}

	$output = '';
	foreach ( $root->childNodes as $child ) {
		$output .= $dom->saveHTML( $child );
	}
	return $output ?: $content;
}
add_filter( 'the_content', 'nvx_clinics_hub_enhance', 30 );
