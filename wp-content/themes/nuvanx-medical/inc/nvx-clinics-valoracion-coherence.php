<?php
/**
 * Final render coherence for the clinics hub and valoración landing.
 *
 * Repairs two route-specific conflicts created by global content filters:
 * - preserves navigation/map actions inside clinic cards;
 * - keeps the valoración hierarchy as hero → clinical introduction → form.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Current page slug. */
function nvxCvCurrentSlug(): string {
	return is_page() ? (string) get_post_field( 'post_name', get_queried_object_id() ) : '';
}

/** Whether an element owns a class token. */
function nvxCvHasClass( DOMElement $element, string $class_name ): bool {
	$classes = preg_split( '/\s+/', trim( $element->getAttribute( 'class' ) ) ) ?: array();
	return in_array( $class_name, $classes, true );
}

/** Deduplicate class tokens. */
function nvxCvNormalizeClasses( DOMElement $element ): void {
	$classes = preg_split( '/\s+/', trim( $element->getAttribute( 'class' ) ) ) ?: array();
	$classes = array_values( array_unique( array_filter( $classes ) ) );
	$element->setAttribute( 'class', implode( ' ', $classes ) );
}

/** Remove every child from an element. */
function nvxCvClearElement( DOMElement $element ): void {
	while ( $element->firstChild ) {
		$element->removeChild( $element->firstChild );
	}
}

/** Append a safe link. */
function nvxCvAppendLink(
	DOMDocument $document,
	DOMElement $parent,
	string $label,
	string $href,
	string $class_name,
	bool $external = false
): void {
	$link = $document->createElement( 'a', $label );
	$link->setAttribute( 'class', $class_name );
	$link->setAttribute( 'href', $href );
	if ( $external ) {
		$link->setAttribute( 'target', '_blank' );
		$link->setAttribute( 'rel', 'nofollow noopener' );
	}
	$parent->appendChild( $link );
}

/** Locate the first descendant with any of the supplied class tokens. */
function nvxCvFindByClasses( DOMXPath $xpath, DOMNode $context, array $classes ): ?DOMElement {
	$parts = array();
	foreach ( $classes as $class_name ) {
		$parts[] = 'contains(concat(" ", normalize-space(@class), " "), " ' . $class_name . ' ")';
	}
	$nodes = $xpath->query( './/*[' . implode( ' or ', $parts ) . ']', $context );
	$node  = false !== $nodes ? $nodes->item( 0 ) : null;
	return $node instanceof DOMElement ? $node : null;
}

/** Canonical headers are text-first; featured media belongs in the body. */
function nvxCvRemoveHeroMedia( DOMXPath $xpath, DOMElement $hero ): void {
	$nodes = $xpath->query(
		'.//*[contains(concat(" ", normalize-space(@class), " "), " nvx-brand-hero__media ") or contains(concat(" ", normalize-space(@class), " "), " nvx-page-hero__media ") or contains(concat(" ", normalize-space(@class), " "), " nvx-hero__media ")]',
		$hero
	);
	if ( false === $nodes ) {
		return;
	}
	foreach ( iterator_to_array( $nodes ) as $node ) {
		if ( $node instanceof DOMElement && $node->parentNode ) {
			$node->parentNode->removeChild( $node );
		}
	}
}

/** Replace a hero action cluster without invoking the global modal rewrite. */
function nvxCvSetHeroActions( DOMDocument $document, DOMXPath $xpath, DOMElement $hero, string $route ): void {
	$actions = nvxCvFindByClasses( $xpath, $hero, array( 'nvx-brand-actions', 'nvx-cta-cluster', 'nvx-page__cta' ) );
	if ( ! $actions instanceof DOMElement ) {
		$copy = nvxCvFindByClasses( $xpath, $hero, array( 'nvx-editorial-hero__copy', 'nvx-brand-hero__copy', 'nvx-hero__copy' ) );
		if ( ! $copy instanceof DOMElement ) {
			return;
		}
		$actions = $document->createElement( 'div' );
		$copy->appendChild( $actions );
	}

	nvxCvClearElement( $actions );
	$actions->setAttribute( 'class', 'nvx-brand-actions' );

	if ( 'valoracion' === $route ) {
		nvxCvAppendLink( $document, $actions, 'Completar solicitud', '#nvx-hubspot-form', 'nvx-brand-btn nvx-brand-btn--primary' );
		nvxCvAppendLink( $document, $actions, 'Contactar por WhatsApp', 'https://wa.me/34669319836', 'nvx-brand-btn nvx-brand-btn--secondary', true );
		return;
	}

	nvxCvAppendLink( $document, $actions, 'Solicitar valoración médica', '/madrid/valoracion/#nvx-hubspot-form', 'nvx-brand-btn nvx-brand-btn--primary' );
	nvxCvAppendLink( $document, $actions, 'Conocer al equipo médico', '/equipo-medico/', 'nvx-brand-btn nvx-brand-btn--secondary' );
}

/** Restore the two intended actions on each clinic card. */
function nvxCvNormalizeClinicCards( DOMDocument $document, DOMXPath $xpath ): void {
	$config = array(
		'clinica-chamberi' => array(
			'page'      => '/medicina-estetica-chamberi/',
			'page_text' => 'Ver sede Chamberí',
			'map'       => 'https://www.google.com/maps/search/?api=1&query=NUVANX%20Medicina%20Est%C3%A9tica%20L%C3%A1ser%20Chamber%C3%AD%20Madrid',
		),
		'clinica-goya' => array(
			'page'      => '/clinicas-de-medicina-estetica-nuvanx/medicina-estetica-goya-barrio-salamanca/',
			'page_text' => 'Ver sede Goya',
			'map'       => 'https://www.google.com/maps/search/?api=1&query=NUVANX%20Medicina%20Est%C3%A9tica%20L%C3%A1ser%20Salamanca%20Goya%20Madrid',
		),
	);

	foreach ( $config as $id => $clinic ) {
		$card = $document->getElementById( $id );
		if ( ! $card instanceof DOMElement ) {
			continue;
		}
		nvxCvNormalizeClasses( $card );
		$nodes = $xpath->query(
			'.//div[contains(concat(" ", normalize-space(@class), " "), " nvx-brand-actions ") or contains(concat(" ", normalize-space(@class), " "), " nvx-cta-cluster ")]',
			$card
		);
		if ( false !== $nodes ) {
			foreach ( iterator_to_array( $nodes ) as $node ) {
				if ( $node instanceof DOMElement && $node->parentNode ) {
					$node->parentNode->removeChild( $node );
				}
			}
		}

		$actions = $document->createElement( 'div' );
		$actions->setAttribute( 'class', 'nvx-brand-actions nvx-clinic-location__actions' );
		nvxCvAppendLink( $document, $actions, $clinic['page_text'], $clinic['page'], 'nvx-brand-btn nvx-brand-btn--primary' );
		nvxCvAppendLink( $document, $actions, 'Abrir en Google Maps', $clinic['map'], 'nvx-brand-btn nvx-brand-btn--secondary nvx-clinic-map-cta', true );
		$card->appendChild( $actions );
	}
}

/** Keep the authored valoración sequence instead of forcing the form first. */
function nvxCvOrderValoracionSections( DOMElement $wrapper, DOMElement $hero, ?DOMElement $intro, ?DOMElement $form ): void {
	$wrapper->insertBefore( $hero, $wrapper->firstChild );
	if ( $intro instanceof DOMElement ) {
		$wrapper->insertBefore( $intro, $hero->nextSibling );
	}
	if ( $form instanceof DOMElement ) {
		$reference = $intro instanceof DOMElement ? $intro->nextSibling : $hero->nextSibling;
		$wrapper->insertBefore( $form, $reference );
		nvxCvNormalizeClasses( $form );
	}
}

/** Route-scoped final content pass. */
function nvxClinicsValoracionCoherence( string $content ): string {
	if ( is_admin() || is_feed() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || '' === trim( $content ) || ! class_exists( 'DOMDocument' ) ) {
		return $content;
	}

	$route = nvxCvCurrentSlug();
	if ( ! in_array( $route, array( 'clinicas-de-medicina-estetica-nuvanx', 'valoracion' ), true ) ) {
		return $content;
	}

	$previous_errors = libxml_use_internal_errors( true );
	$document        = new DOMDocument( '1.0', 'UTF-8' );
	$loaded          = $document->loadHTML(
		'<?xml encoding="utf-8" ?><div id="nvx-cv-root">' . $content . '</div>',
		LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
	);
	if ( ! $loaded ) {
		libxml_clear_errors();
		libxml_use_internal_errors( $previous_errors );
		return $content;
	}

	$xpath = new DOMXPath( $document );
	$root  = $document->getElementById( 'nvx-cv-root' );
	if ( ! $root instanceof DOMElement ) {
		libxml_clear_errors();
		libxml_use_internal_errors( $previous_errors );
		return $content;
	}

	$hero = nvxCvFindByClasses( $xpath, $root, array( 'nvx-canonical-page-hero', 'nvx-brand-hero', 'nvx-page-hero' ) );
	if ( $hero instanceof DOMElement ) {
		nvxCvRemoveHeroMedia( $xpath, $hero );
		nvxCvSetHeroActions( $document, $xpath, $hero, $route );
	}

	if ( 'clinicas-de-medicina-estetica-nuvanx' === $route ) {
		nvxCvNormalizeClinicCards( $document, $xpath );
	} elseif ( $hero instanceof DOMElement ) {
		$wrapper = $document->getElementById( 'nvx-valoracion-main' );
		$intro   = $document->getElementById( 'nvx-valoracion-intro' );
		$form    = $document->getElementById( 'nvx-hubspot-form' );
		if ( $wrapper instanceof DOMElement ) {
			nvxCvOrderValoracionSections(
				$wrapper,
				$hero,
				$intro instanceof DOMElement ? $intro : null,
				$form instanceof DOMElement ? $form : null
			);
		}
	}

	$output = '';
	foreach ( $root->childNodes as $child ) {
		$output .= $document->saveHTML( $child );
	}

	libxml_clear_errors();
	libxml_use_internal_errors( $previous_errors );
	return '' !== trim( $output ) ? $output : $content;
}
add_filter( 'the_content', 'nvxClinicsValoracionCoherence', 320 );
