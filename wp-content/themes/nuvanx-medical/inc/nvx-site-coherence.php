<?php
/**
 * Cross-route visual and conversion coherence.
 *
 * Keeps technology headers, the journal archive, conversion buttons and the
 * valoración landing on one durable design-system contract.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Canonical routes governed by this coherence layer.
 *
 * @return string[]
 */
function nvx_site_coherence_page_slugs(): array {
	return array(
		'endolift-facial-papada-mandibula',
		'endolaser-corporal-grasa-localizada',
		'exion-face',
		'exion-body',
		'exion-fractional',
		'laser-co2-fraccionado-madrid-textura-cicatrices-poro',
		'btl-exilite-ipl-madrid',
		'emfusion',
		'medicina-estetica',
		'labios-acido-hialuronico-madrid',
		'rinomodelacion-sin-cirugia-madrid',
		'ojeras-surco-lagrimal-madrid',
		'bioestimuladores-colageno-madrid',
		'valoracion',
	);
}

/** Current public page slug. */
function nvx_site_coherence_current_slug(): string {
	if ( ! is_page() ) {
		return '';
	}

	return (string) get_post_field( 'post_name', get_queried_object_id() );
}

/** Whether the current page uses the shared coherence contract. */
function nvx_site_coherence_is_target_page(): bool {
	return in_array( nvx_site_coherence_current_slug(), nvx_site_coherence_page_slugs(), true );
}

/** Load the single cross-route stylesheet after the canonical component layers. */
function nvx_site_coherence_enqueue_assets(): void {
	$relative = 'assets/css/nvx-site-coherence.css';
	$absolute = get_template_directory() . '/' . $relative;

	if ( ! is_readable( $absolute ) ) {
		return;
	}

	wp_enqueue_style(
		'nvx-site-coherence',
		get_template_directory_uri() . '/' . $relative,
		array( 'nvx-components', 'nvx-canonical-page-hero' ),
		nvx_asset_version( $relative )
	);
}
add_action( 'wp_enqueue_scripts', 'nvx_site_coherence_enqueue_assets', 80 );

/** Stable body hooks for scoped presentation and browser acceptance tests. */
function nvx_site_coherence_body_classes( array $classes ): array {
	if ( nvx_site_coherence_is_target_page() ) {
		$classes[] = 'nvx-site-coherent-page';
	}

	if ( 'valoracion' === nvx_site_coherence_current_slug() ) {
		$classes[] = 'nvx-valoracion-page';
	}

	return array_values( array_unique( $classes ) );
}
add_filter( 'body_class', 'nvx_site_coherence_body_classes' );

/** Add a class token without duplicating it. */
function nvx_site_coherence_add_class( DOMElement $node, string $class_name ): void {
	$classes   = preg_split( '/\s+/', trim( $node->getAttribute( 'class' ) ) ) ?: array();
	$classes[] = $class_name;
	$classes   = array_values( array_unique( array_filter( $classes ) ) );
	$node->setAttribute( 'class', implode( ' ', $classes ) );
}

/** Check whether an element owns a class token. */
function nvx_site_coherence_has_class( DOMElement $node, string $class_name ): bool {
	$classes = preg_split( '/\s+/', trim( $node->getAttribute( 'class' ) ) ) ?: array();
	return in_array( $class_name, $classes, true );
}

/** Create the canonical valoración header when legacy content has only an H1. */
function nvx_site_coherence_create_valoracion_hero( DOMDocument $document, DOMElement $root ): ?DOMElement {
	$xpath = new DOMXPath( $document );
	$h1s   = $xpath->query( './/h1', $root );
	$h1    = false !== $h1s ? $h1s->item( 0 ) : null;

	if ( ! $h1 instanceof DOMElement ) {
		return null;
	}

	$hero = $document->createElement( 'section' );
	$hero->setAttribute( 'class', 'nvx-brand-hero nvx-editorial-hero nvx-canonical-page-hero' );
	$hero->setAttribute( 'aria-labelledby', 'nvx-valoracion-h1' );

	$inner = $document->createElement( 'div' );
	$inner->setAttribute( 'class', 'nvx-brand-hero__inner' );
	$copy = $document->createElement( 'div' );
	$copy->setAttribute( 'class', 'nvx-editorial-hero__copy' );

	$eyebrow = $document->createElement( 'p', 'VALORACIÓN MÉDICA · MADRID' );
	$eyebrow->setAttribute( 'class', 'nvx-eyebrow' );
	$copy->appendChild( $eyebrow );

	$h1->setAttribute( 'id', 'nvx-valoracion-h1' );
	$h1->setAttribute( 'class', trim( $h1->getAttribute( 'class' ) . ' nvx-heading' ) );
	$copy->appendChild( $h1 );

	$action = $document->createElement( 'p' );
	$link   = $document->createElement( 'a', 'Completar solicitud' );
	$link->setAttribute( 'class', 'nvx-btn nvx-btn--primary' );
	$link->setAttribute( 'href', '#nvx-hubspot-form' );
	$action->appendChild( $link );
	$copy->appendChild( $action );

	$inner->appendChild( $copy );
	$hero->appendChild( $inner );

	if ( $root->firstChild ) {
		$root->insertBefore( $hero, $root->firstChild );
	} else {
		$root->appendChild( $hero );
	}

	return $hero;
}

/**
 * Normalize one treatment header and move explanatory copy below it.
 *
 * @param string $content Page HTML.
 */
function nvx_site_coherence_normalize_page_header( string $content ): string {
	if (
		is_admin()
		|| ! nvx_site_coherence_is_target_page()
		|| '' === trim( $content )
		|| ! class_exists( 'DOMDocument' )
	) {
		return $content;
	}

	$previous_errors = libxml_use_internal_errors( true );
	$document        = new DOMDocument( '1.0', 'UTF-8' );
	$wrapped         = '<div id="nvx-site-coherence-root">' . $content . '</div>';
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
	$root  = $document->getElementById( 'nvx-site-coherence-root' );
	if ( ! $root instanceof DOMElement ) {
		libxml_clear_errors();
		libxml_use_internal_errors( $previous_errors );
		return $content;
	}

	$heroes = $xpath->query(
		'.//*[contains(concat(" ", normalize-space(@class), " "), " nvx-canonical-page-hero ") or contains(concat(" ", normalize-space(@class), " "), " nvx-brand-hero ") or contains(concat(" ", normalize-space(@class), " "), " nvx-editorial-hero ") or contains(concat(" ", normalize-space(@class), " "), " nvx-page-hero ") or contains(concat(" ", normalize-space(@class), " "), " nvx-hero-section ") or contains(concat(" ", normalize-space(@class), " "), " nvx-endolift-hero ") or contains(concat(" ", normalize-space(@class), " "), " nvx-ipl-hero ") or contains(concat(" ", normalize-space(@class), " "), " nvx-strategy-intro ")]',
		$root
	);
	$hero = false !== $heroes ? $heroes->item( 0 ) : null;

	if ( ! $hero instanceof DOMElement && 'valoracion' === nvx_site_coherence_current_slug() ) {
		$hero = nvx_site_coherence_create_valoracion_hero( $document, $root );
	}

	if ( ! $hero instanceof DOMElement ) {
		libxml_clear_errors();
		libxml_use_internal_errors( $previous_errors );
		return $content;
	}

	nvx_site_coherence_add_class( $hero, 'nvx-brand-hero' );
	nvx_site_coherence_add_class( $hero, 'nvx-editorial-hero' );
	nvx_site_coherence_add_class( $hero, 'nvx-canonical-page-hero' );

	$media_nodes = $xpath->query(
		'.//*[contains(concat(" ", normalize-space(@class), " "), " nvx-brand-hero__media ") or contains(concat(" ", normalize-space(@class), " "), " nvx-page-hero__media ") or contains(concat(" ", normalize-space(@class), " "), " nvx-hero__media ") or contains(concat(" ", normalize-space(@class), " "), " nvx-endolift-hero__media ") or contains(concat(" ", normalize-space(@class), " "), " nvx-ipl-hero__media ")]',
		$hero
	);
	if ( false !== $media_nodes ) {
		$removable = iterator_to_array( $media_nodes );
		foreach ( $removable as $media ) {
			if ( $media instanceof DOMElement && $media->parentNode ) {
				$media->parentNode->removeChild( $media );
			}
		}
	}

	$copy_nodes = $xpath->query(
		'.//*[contains(concat(" ", normalize-space(@class), " "), " nvx-editorial-hero__copy ") or contains(concat(" ", normalize-space(@class), " "), " nvx-editorial-hero__copy-copy ") or contains(concat(" ", normalize-space(@class), " "), " nvx-brand-hero__copy ") or contains(concat(" ", normalize-space(@class), " "), " nvx-page-hero__copy ") or contains(concat(" ", normalize-space(@class), " "), " nvx-hero__copy ") or contains(concat(" ", normalize-space(@class), " "), " nvx-endolift-hero__copy ") or contains(concat(" ", normalize-space(@class), " "), " nvx-ipl-hero__copy ")]',
		$hero
	);
	$copy = false !== $copy_nodes ? $copy_nodes->item( 0 ) : null;

	if ( ! $copy instanceof DOMElement ) {
		$inner = $document->createElement( 'div' );
		$inner->setAttribute( 'class', 'nvx-brand-hero__inner' );
		$copy = $document->createElement( 'div' );
		$copy->setAttribute( 'class', 'nvx-editorial-hero__copy' );
		foreach ( iterator_to_array( $hero->childNodes ) as $child ) {
			$copy->appendChild( $child );
		}
		$inner->appendChild( $copy );
		$hero->appendChild( $inner );
	} else {
		nvx_site_coherence_add_class( $copy, 'nvx-editorial-hero__copy' );
		$parent = $copy->parentNode;
		if ( $parent instanceof DOMElement && $parent->isSameNode( $hero ) ) {
			$inner = $document->createElement( 'div' );
			$inner->setAttribute( 'class', 'nvx-brand-hero__inner' );
			$hero->replaceChild( $inner, $copy );
			$inner->appendChild( $copy );
		}
	}

	$lead_classes = array(
		'nvx-lead',
		'nvx-brand-hero__lead',
		'nvx-hero__lead',
		'nvx-page-hero__lead',
		'nvx-subtitle',
		'nvx-hero-subtitle',
		'nvx-ipl-lead',
	);
	$movable = array();
	foreach ( iterator_to_array( $copy->childNodes ) as $child ) {
		if ( ! $child instanceof DOMElement || 'p' !== strtolower( $child->tagName ) ) {
			continue;
		}
		foreach ( $lead_classes as $class_name ) {
			if ( nvx_site_coherence_has_class( $child, $class_name ) ) {
				$movable[] = $child;
				break;
			}
		}
	}

	$next = $hero->nextSibling;
	while ( $next && XML_TEXT_NODE === $next->nodeType && '' === trim( (string) $next->textContent ) ) {
		$next = $next->nextSibling;
	}
	$has_intro = $next instanceof DOMElement && (
		nvx_site_coherence_has_class( $next, 'nvx-hero-intro--generated' )
		|| nvx_site_coherence_has_class( $next, 'nvx-hero-intro--coherent' )
	);

	if ( array() !== $movable && ! $has_intro && $hero->parentNode ) {
		$intro = $document->createElement( 'section' );
		$intro->setAttribute( 'class', 'nvx-brand-section nvx-hero-intro nvx-hero-intro--coherent' );
		$intro->setAttribute( 'aria-label', 'Introducción clínica' );
		$inner = $document->createElement( 'div' );
		$inner->setAttribute( 'class', 'nvx-brand-section__inner' );
		$readable = $document->createElement( 'div' );
		$readable->setAttribute( 'class', 'nvx-brand-readable nvx-brand-readable--wide' );
		foreach ( $movable as $paragraph ) {
			nvx_site_coherence_add_class( $paragraph, 'nvx-brand-body' );
			$readable->appendChild( $paragraph );
		}
		$inner->appendChild( $readable );
		$intro->appendChild( $inner );
		if ( $hero->nextSibling ) {
			$hero->parentNode->insertBefore( $intro, $hero->nextSibling );
		} else {
			$hero->parentNode->appendChild( $intro );
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
add_filter( 'the_content', 'nvx_site_coherence_normalize_page_header', 150 );

/** Give modal buttons an ordinary link fallback when JavaScript is unavailable. */
function nvx_site_coherence_add_valoracion_fallbacks( string $content ): string {
	if ( false === strpos( $content, 'nvx-open-valoracion-modal' ) || false === stripos( $content, '<button' ) ) {
		return $content;
	}

	$result = preg_replace_callback(
		'/<button\b([^>]*\bclass=["\'][^"\']*\bnvx-open-valoracion-modal\b[^"\']*["\'][^>]*)>([\s\S]*?)<\/button>/iu',
		static function ( array $match ): string {
			$attributes = (string) preg_replace( '/\s+type=["\'][^"\']*["\']/iu', '', $match[1] );
			$attributes = (string) preg_replace( '/\s+href=["\'][^"\']*["\']/iu', '', $attributes );
			return '<a href="' . esc_url( home_url( '/madrid/valoracion/' ) ) . '"' . $attributes . '>' . $match[2] . '</a>';
		},
		$content
	);

	return is_string( $result ) ? $result : $content;
}
add_filter( 'the_content', 'nvx_site_coherence_add_valoracion_fallbacks', 220 );

/** Move the shared modal before footer scripts so its DOM exists when nvx-main runs. */
function nvx_site_coherence_reorder_valoracion_modal(): void {
	if ( ! function_exists( 'nvx_valoracion_modal_render' ) ) {
		return;
	}

	remove_action( 'wp_footer', 'nvx_valoracion_modal_render', 25 );
	add_action( 'wp_footer', 'nvx_valoracion_modal_render', 5 );
}
add_action( 'wp', 'nvx_site_coherence_reorder_valoracion_modal', 20 );

/** Provide nvx-main with the modal runtime contract and a canonical fallback URL. */
function nvx_site_coherence_configure_valoracion_modal(): void {
	if ( ! wp_script_is( 'nvx-main', 'registered' ) && ! wp_script_is( 'nvx-main', 'enqueued' ) ) {
		return;
	}

	$payload = array(
		'enabled' => true,
		'pageUrl' => home_url( '/madrid/valoracion/' ),
	);
	wp_add_inline_script(
		'nvx-main',
		'window.nvxValoracionModal = Object.assign({}, window.nvxValoracionModal || {}, ' . wp_json_encode( $payload ) . ');',
		'before'
	);
}
add_action( 'wp_enqueue_scripts', 'nvx_site_coherence_configure_valoracion_modal', 100 );
