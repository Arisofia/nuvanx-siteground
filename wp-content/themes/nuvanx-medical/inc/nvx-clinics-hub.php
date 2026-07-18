<?php
/**
 * Clinics hub: map CTAs + promote CMS markup to global brand-section shells.
 *
 * No page-exclusive layout. Nested bare sections inherit the same
 * nvx-brand-section / __inner gutters used on Goya, Chamberí and treatments.
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

/**
 * Whether the current page uses the Sede Local template (hub + branch pages).
 */
function nvx_is_sede_template(): bool {
	if ( ! is_page() ) {
		return false;
	}

	$template = (string) get_page_template_slug();

	return in_array(
		$template,
		array(
			'templates/page-sede.php',
			'page-sede.php',
		),
		true
	);
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
 * Promote bare CMS <section>/<div> wrappers to global brand shells.
 */
function nvx_clinics_promote_bare_sections( DOMXPath $xpath ): void {
	$sections = $xpath->query( '//section' );
	if ( false === $sections ) {
		return;
	}

	foreach ( $sections as $section ) {
		if ( ! $section instanceof DOMElement ) {
			continue;
		}

		$class = trim( $section->getAttribute( 'class' ) );
		// Leave heroes, CTAs, nav and already-canonical sections alone.
		if ( preg_match( '/\b(nvx-brand-hero|nvx-cta-banner|nvx-clinics-nav|nvx-hero-intro)\b/i', $class ) ) {
			continue;
		}

		if ( '' === $class || ! preg_match( '/\bnvx-brand-section\b/i', $class ) ) {
			$section->setAttribute( 'class', trim( $class . ' nvx-brand-section' ) );
		}

		foreach ( $section->childNodes as $child ) {
			if ( ! $child instanceof DOMElement || 'div' !== strtolower( $child->tagName ) ) {
				continue;
			}
			$child_class = trim( $child->getAttribute( 'class' ) );
			if ( preg_match( '/\b(nvx-brand-section__inner|nvx-brand-grid|nvx-shell|nvx-clinics-content-flow|nvx-brand-readable)\b/i', $child_class ) ) {
				break;
			}
			// Bare first div → canonical section inner (global gutters).
			if ( '' === $child_class || ! preg_match( '/\bnvx-/', $child_class ) ) {
				$child->setAttribute( 'class', trim( $child_class . ' nvx-brand-section__inner' ) );
			}
			break;
		}
	}
}

/**
 * When a readable / flow wrapper holds multiple page sections, drop the
 * measure constraint so children can use full brand-section shells.
 */
function nvx_clinics_normalize_layout( DOMXPath $xpath ): ?DOMElement {
	$nodes = $xpath->query(
		'//*[contains(concat(" ", normalize-space(@class), " "), " nvx-brand-readable ") or contains(concat(" ", normalize-space(@class), " "), " nvx-clinics-content-flow ")]'
	);

	if ( false === $nodes ) {
		return null;
	}

	$layout_root = null;
	foreach ( iterator_to_array( $nodes ) as $node ) {
		if ( ! $node instanceof DOMElement ) {
			continue;
		}

		$structural_children = $xpath->query( './section|./article|.//section|.//article', $node );
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
		// Marker only — no exclusive CSS. Full-width stack of global sections.
		$classes[] = 'nvx-content-flow';
		$node->setAttribute( 'class', implode( ' ', array_unique( $classes ) ) );
		$layout_root ??= $node;
	}

	return $layout_root;
}

/**
 * Unwrap anonymous divs that only group sections (legacy CMS).
 */
function nvx_clinics_unwrap_section_groups( DOMXPath $xpath ): void {
	$divs = $xpath->query( '//div' );
	if ( false === $divs ) {
		return;
	}

	foreach ( iterator_to_array( $divs ) as $div ) {
		if ( ! $div instanceof DOMElement || ! $div->parentNode ) {
			continue;
		}

		$class = trim( $div->getAttribute( 'class' ) );
		if ( preg_match( '/\b(nvx-brand-section__inner|nvx-brand-grid|nvx-shell|nvx-brand-hero|nvx-brand-actions|nvx-brand-card|nvx-content-flow|nvx-clinics-content-flow|nvx-brand-readable|nvx-brand-page)\b/i', $class ) ) {
			continue;
		}

		$section_children = array();
		$element_children = 0;
		foreach ( $div->childNodes as $child ) {
			if ( ! $child instanceof DOMElement ) {
				continue;
			}
			++$element_children;
			if ( 'section' === strtolower( $child->tagName ) ) {
				$section_children[] = $child;
			}
		}

		// Need multiple sections (or aria-labelledby legacy grouping of sections).
		$has_aria_group = $div->hasAttribute( 'aria-labelledby' );
		if ( count( $section_children ) < 2 && ! ( $has_aria_group && count( $section_children ) >= 1 ) ) {
			continue;
		}
		if ( count( $section_children ) < $element_children ) {
			// Mixed content — only unwrap pure section groups.
			if ( ! $has_aria_group || count( $section_children ) !== $element_children ) {
				continue;
			}
		}

		$parent = $div->parentNode;
		while ( $div->firstChild ) {
			$parent->insertBefore( $div->firstChild, $div );
		}
		$parent->removeChild( $div );
	}
}

/**
 * Hoist multi-section stacks out of a single outer brand-section shell so each
 * block gets the same pad-section rhythm as Goya / Chamberí.
 *
 * @return DOMElement|null First hoisted element (for nav insertion).
 */
function nvx_clinics_hoist_section_stack( DOMXPath $xpath ): ?DOMElement {
	$flows = $xpath->query(
		'//*[contains(concat(" ", normalize-space(@class), " "), " nvx-content-flow ") or contains(concat(" ", normalize-space(@class), " "), " nvx-clinics-content-flow ")]'
	);

	if ( false === $flows ) {
		return null;
	}

	$first = null;

	foreach ( iterator_to_array( $flows ) as $flow ) {
		if ( ! $flow instanceof DOMElement || ! $flow->parentNode ) {
			continue;
		}

		// Climb to nearest brand-section ancestor that is not a hero.
		$brand_section = null;
		$current       = $flow->parentNode;
		while ( $current instanceof DOMElement ) {
			$class = $current->getAttribute( 'class' );
			if ( preg_match( '/\bnvx-brand-page\b/i', $class ) ) {
				break;
			}
			if (
				preg_match( '/\bnvx-brand-section\b/i', $class )
				&& ! preg_match( '/\bnvx-brand-hero\b/i', $class )
			) {
				$brand_section = $current;
			}
			$current = $current->parentNode;
		}

		if ( ! $brand_section instanceof DOMElement || ! $brand_section->parentNode ) {
			continue;
		}

		// Only hoist when this outer section is a wrapper (contains nested sections).
		$nested = $xpath->query( './/section', $flow );
		if ( false === $nested || $nested->length < 1 ) {
			continue;
		}

		$parent = $brand_section->parentNode;
		while ( $flow->firstChild ) {
			$child = $flow->firstChild;
			$parent->insertBefore( $child, $brand_section );
			if ( null === $first && $child instanceof DOMElement ) {
				$first = $child;
			}
		}

		// Drop empty wrapper chain (flow → optional inners → brand-section).
		$parent->removeChild( $brand_section );
	}

	return $first;
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

/**
 * Strip CMS inline layout styles that fight global spacing/color tokens.
 * Applied on Sede template pages only.
 */
function nvx_sede_strip_layout_inline_styles( string $content ): string {
	if ( is_admin() || ! nvx_is_sede_template() || '' === trim( $content ) ) {
		return $content;
	}

	return preg_replace_callback(
		'/\sstyle=(["\'])([^"\']*)\1/iu',
		static function ( array $match ): string {
			$decls = array_filter( array_map( 'trim', explode( ';', $match[2] ) ) );
			$keep  = array();
			foreach ( $decls as $decl ) {
				// Drop exclusive layout/color that should come from design tokens.
				if ( preg_match( '/^(margin|padding|max-width|width|color|font-size|text-align|background|line-height)\s*:/i', $decl ) ) {
					continue;
				}
				$keep[] = $decl;
			}
			if ( array() === $keep ) {
				return '';
			}
			return ' style=' . $match[1] . implode( '; ', $keep ) . $match[1];
		},
		$content
	) ?? $content;
}
add_filter( 'the_content', 'nvx_sede_strip_layout_inline_styles', 28 );

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

	$xpath = new DOMXPath( $dom );

	// 1) Canonical section shells (same as Goya/Chamberí).
	nvx_clinics_promote_bare_sections( $xpath );

	// 2) Drop prose measure on multi-section CMS wrappers.
	$layout_root = nvx_clinics_normalize_layout( $xpath );

	// 3) Unwrap legacy divs that only group sections.
	nvx_clinics_unwrap_section_groups( $xpath );

	// 4) Hoist nested sections so each owns pad-section + shell gutters once.
	$hoisted = nvx_clinics_hoist_section_stack( $xpath );

	// 5) Unwrap again after hoist, then re-promote shells.
	nvx_clinics_unwrap_section_groups( $xpath );
	nvx_clinics_promote_bare_sections( $xpath );

	$clinics = array(
		'chamberi' => array( 'id' => 'clinica-chamberi', 'label' => 'Chamberí', 'match' => '/chamber[ií]/iu' ),
		'goya'     => array( 'id' => 'clinica-goya', 'label' => 'Salamanca–Goya', 'match' => '/(?:salamanca|goya)/iu' ),
	);
	$blocks  = array();

	foreach ( $xpath->query( '//h2|//h3|//h4' ) ?: array() as $heading ) {
		$text = trim( preg_replace( '/\s+/u', ' ', $heading->textContent ) ?? $heading->textContent );
		foreach ( $clinics as $key => $config ) {
			if ( isset( $blocks[ $key ] ) || ! preg_match( $config['match'], $text ) ) {
				continue;
			}
			$block = nvx_clinics_nearest_block( $heading );
			if ( $block ) {
				// Prefer article cards as location anchors when heading is inside a card.
				$article = $xpath->query( 'ancestor::article[contains(concat(" ", normalize-space(@class), " "), " nvx-brand-card ")][1]', $heading );
				if ( $article && $article->length && $article->item( 0 ) instanceof DOMElement ) {
					$block = $article->item( 0 );
				}
				$block->setAttribute( 'id', $config['id'] );
				$block->setAttribute( 'class', trim( $block->getAttribute( 'class' ) . ' nvx-clinic-location' ) );
				$blocks[ $key ] = $block;
			}
		}
	}

	foreach ( $blocks as $key => $block ) {
		$links           = $xpath->query( './/a', $block );
		$map_action_seen = false;
		foreach ( $links ?: array() as $link ) {
			if ( ! $link instanceof DOMElement ) {
				continue;
			}
			$text           = trim( preg_replace( '/\s+/u', ' ', $link->textContent ) ?? $link->textContent );
			$href           = $link->getAttribute( 'href' );
			$is_map_action  = preg_match( '/(?:cómo llegar|como llegar|google maps|maps\.app|google\.[^\/]+\/maps)/iu', $text . ' ' . $href );
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
		// Single shell gutter (nav is full-bleed; inner uses global .nvx-shell).
		$inner->setAttribute( 'class', 'nvx-shell nvx-clinics-nav__inner' );
		foreach ( $clinics as $config ) {
			$link = $dom->createElement( 'a', $config['label'] );
			$link->setAttribute( 'href', '#' . $config['id'] );
			$link->setAttribute( 'class', 'nvx-clinics-nav__link' );
			$inner->appendChild( $link );
		}
		$nav->appendChild( $inner );

		// Insert as full-bleed sibling after hero / before first body section — not inside a section shell.
		$insert_parent = null;
		$insert_before = null;

		$page = $xpath->query( '//*[contains(concat(" ", normalize-space(@class), " "), " nvx-brand-page ")]' )->item( 0 );
		if ( $page instanceof DOMElement ) {
			$insert_parent = $page;
			foreach ( $page->childNodes as $child ) {
				if ( ! $child instanceof DOMElement ) {
					continue;
				}
				$c = $child->getAttribute( 'class' );
				if ( preg_match( '/\bnvx-brand-hero\b/i', $c ) ) {
					continue;
				}
				if ( preg_match( '/\bnvx-brand-section\b|\bnvx-content-flow\b/i', $c ) || 'section' === strtolower( $child->tagName ) || 'nav' === strtolower( $child->tagName ) ) {
					$insert_before = $child;
					break;
				}
			}
		}

		if ( $insert_parent instanceof DOMElement ) {
			if ( $insert_before instanceof DOMElement ) {
				$insert_parent->insertBefore( $nav, $insert_before );
			} else {
				$insert_parent->appendChild( $nav );
			}
		} elseif ( $hoisted instanceof DOMElement && $hoisted->parentNode ) {
			$hoisted->parentNode->insertBefore( $nav, $hoisted );
		} elseif ( $layout_root instanceof DOMElement ) {
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
