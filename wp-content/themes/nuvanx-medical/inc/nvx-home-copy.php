<?php
/**
 * Canonical front-page hero copy.
 *
 * Replaces the internal "Experiencia NUVANX" heading with a patient-facing,
 * search-intent H1 while preserving the existing media, actions and layout.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Test whether a DOM element has a class.
 */
function nvx_home_copy_has_class( DOMElement $node, string $class_name ): bool {
	$classes = preg_split( '/\s+/', trim( $node->getAttribute( 'class' ) ) ) ?: array();
	return in_array( $class_name, $classes, true );
}

/**
 * Replace all children with one text node.
 */
function nvx_home_copy_set_text( DOMElement $node, string $text ): void {
	while ( $node->firstChild ) {
		$node->removeChild( $node->firstChild );
	}
	$node->appendChild( $node->ownerDocument->createTextNode( $text ) );
}

/**
 * Apply canonical hero copy to the rendered front-page content.
 */
function nvx_home_copy_transform( string $content ): string {
	if (
		is_admin()
		|| wp_doing_ajax()
		|| ( defined( 'REST_REQUEST' ) && REST_REQUEST )
		|| ! is_front_page()
		|| false !== strpos( $content, 'data-nvx-home-copy="canonical"' )
		|| ! class_exists( 'DOMDocument' )
	) {
		return $content;
	}

	$previous_errors = libxml_use_internal_errors( true );
	$document        = new DOMDocument( '1.0', 'UTF-8' );
	$wrapped         = '<div id="nvx-home-copy-root">' . $content . '</div>';
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
	$root  = $document->getElementById( 'nvx-home-copy-root' );
	if ( ! $root ) {
		libxml_clear_errors();
		libxml_use_internal_errors( $previous_errors );
		return $content;
	}

	$headings = $xpath->query( './/h1', $root );
	if ( false === $headings || 0 === $headings->length ) {
		libxml_clear_errors();
		libxml_use_internal_errors( $previous_errors );
		return $content;
	}

	$heading = $headings->item( 0 );
	if ( ! $heading instanceof DOMElement ) {
		libxml_clear_errors();
		libxml_use_internal_errors( $previous_errors );
		return $content;
	}

	nvx_home_copy_set_text( $heading, 'Medicina estética donde el diagnóstico precede a la tecnología' );
	$heading->setAttribute( 'data-nvx-home-copy', 'canonical' );

	$copy = $heading->parentNode;
	if ( ! $copy instanceof DOMElement ) {
		$copy = $root;
	}

	$lead_text        = 'Madrid. Dos sedes. Un único criterio médico.';
	$description_text = 'La valoración clínica define el plan, no el catálogo.';
	$lead             = null;
	$description      = null;

	$paragraphs = $xpath->query( './/p', $copy );
	if ( false !== $paragraphs ) {
		foreach ( $paragraphs as $paragraph ) {
			if ( ! $paragraph instanceof DOMElement ) {
				continue;
			}
			if ( nvx_home_copy_has_class( $paragraph, 'nvx-brand-hero__lead' ) || nvx_home_copy_has_class( $paragraph, 'nvx-hero__lead' ) ) {
				$lead = $paragraph;
			}
			if ( nvx_home_copy_has_class( $paragraph, 'nvx-brand-hero__description' ) || nvx_home_copy_has_class( $paragraph, 'nvx-hero__description' ) ) {
				$description = $paragraph;
			}
		}
	}

	if ( $lead instanceof DOMElement ) {
		nvx_home_copy_set_text( $lead, $lead_text );
	} else {
		$lead = $document->createElement( 'p' );
		$lead->setAttribute( 'class', 'nvx-brand-hero__lead' );
		$lead->appendChild( $document->createTextNode( $lead_text ) );
		if ( $heading->nextSibling ) {
			$copy->insertBefore( $lead, $heading->nextSibling );
		} else {
			$copy->appendChild( $lead );
		}
	}

	if ( $description instanceof DOMElement ) {
		nvx_home_copy_set_text( $description, $description_text );
	} else {
		$description = $document->createElement( 'p' );
		$description->setAttribute( 'class', 'nvx-brand-hero__description' );
		$description->appendChild( $document->createTextNode( $description_text ) );
		if ( $lead->nextSibling ) {
			$copy->insertBefore( $description, $lead->nextSibling );
		} else {
			$copy->appendChild( $description );
		}
	}

	$output = '';
	foreach ( $root->childNodes as $child ) {
		$output .= $document->saveHTML( $child );
	}

	libxml_clear_errors();
	libxml_use_internal_errors( $previous_errors );

	return is_string( $output ) && '' !== trim( $output ) ? $output : $content;
}
add_filter( 'the_content', 'nvx_home_copy_transform', 17 );
