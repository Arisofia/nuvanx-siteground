<?php
/**
 * Clinics hub: map CTAs + promote CMS markup to global brand-section shells.
 *
 * No page-exclusive layout. Nested bare sections inherit the same
 * nvx-brand-section / __inner gutters used on Goya, Chamberí and treatments.
 *
 * DOM layout pipeline (ordered, see nvx_clinics_run_layout_pipeline):
 *   promote → normalize → unwrap → hoist → unwrap → promote
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
-------------------------------------------------------------------------
 * Shared class / style lists (defined once; helpers return static caches)
 * ---------------------------------------------------------------------- */

/**
 * Section class tokens that must not be rewritten to brand-section shells.
 *
 * @return string[]
 */
function nvxClinicsSectionSkipClasses(): array {
	static $classes = null;
	if ( null === $classes ) {
		$classes = array(
			'nvx-brand-hero',
			'nvx-cta-banner',
			'nvx-clinics-nav',
			'nvx-hero-intro',
		);
	}
	return $classes;
}

/**
 * First-child div classes that already act as section inners / grids / shells.
 *
 * @return string[]
 */
function nvxClinicsSectionInnerReadyClasses(): array {
	static $classes = null;
	if ( null === $classes ) {
		$classes = array(
			'nvx-brand-section__inner',
			'nvx-brand-grid',
			'nvx-shell',
			'nvx-clinics-content-flow',
			'nvx-content-flow',
			'nvx-brand-readable',
			'wp-block-columns',
			'wp-block-group',
			'is-layout-flex',
			'is-layout-grid',
		);
	}
	return $classes;
}

/**
 * Div wrappers that must not be unwrapped (canonical structure).
 *
 * @return string[]
 */
function nvxClinicsUnwrapProtectedClasses(): array {
	static $classes = null;
	if ( null === $classes ) {
		$classes = array(
			'nvx-brand-section__inner',
			'nvx-brand-grid',
			'nvx-shell',
			'nvx-brand-hero',
			'nvx-brand-actions',
			'nvx-brand-card',
			'nvx-content-flow',
			'nvx-clinics-content-flow',
			'nvx-brand-readable',
			'nvx-brand-page',
		);
	}
	return $classes;
}

/**
 * Class tokens that identify multi-section flow containers (hoist targets).
 *
 * @return string[]
 */
function nvxClinicsFlowClasses(): array {
	static $classes = null;
	if ( null === $classes ) {
		$classes = array(
			'nvx-content-flow',
			'nvx-clinics-content-flow',
		);
	}
	return $classes;
}

/**
 * Classes that mark a measure-constrained wrapper (normalized into content-flow).
 *
 * @return string[]
 */
function nvxClinicsReadableMeasureClasses(): array {
	static $classes = null;
	if ( null === $classes ) {
		$classes = array(
			'nvx-brand-readable',
			'nvx-brand-readable--wide',
		);
	}
	return $classes;
}

/**
 * CMS wrapper classes where inline layout styles may be stripped on Sede pages.
 * Editors can keep custom styles on other elements; only these get cleaned.
 *
 * @return string[]
 */
function nvxSedeInlineStyleTargetClasses(): array {
	static $classes = null;
	if ( null === $classes ) {
		$classes = array(
			'nvx-brand-card',
			'nvx-brand-actions',
			'nvx-brand-body',
			'nvx-brand-section__inner',
			'nvx-brand-grid',
		);
	}
	return $classes;
}

/**
 * CSS properties stripped from targeted Sede wrappers only (spacing that fights tokens).
 * Intentionally narrow: keep color, font-size, text-align, width, background for editorial opt-in.
 *
 * @return string[]
 */
function nvxSedeBlockedInlineStyleProperties(): array {
	static $props = null;
	if ( null === $props ) {
		$props = array(
			'margin',
			'margin-top',
			'margin-right',
			'margin-bottom',
			'margin-left',
			'margin-block',
			'margin-inline',
			'padding',
			'padding-top',
			'padding-right',
			'padding-bottom',
			'padding-left',
			'padding-block',
			'padding-inline',
		);
	}
	return $props;
}

/**
 * Tags allowed when rewriting style attributes (no void/self-closing noise).
 *
 * @return string[]
 */
function nvxSedeInlineStyleAllowedTags(): array {
	static $tags = null;
	if ( null === $tags ) {
		$tags = array( 'div', 'section', 'article', 'p', 'span', 'a', 'li', 'h2', 'h3', 'h4' );
	}
	return $tags;
}

/**
 * PHP 7-compatible string prefix check (avoid str_starts_with for WP hosts on 7.x).
 */
function nvxStrStartsWith( string $haystack, string $needle ): bool {
	if ( '' === $needle ) {
		return true;
	}
	return 0 === strpos( $haystack, $needle );
}

/**
 * Whether a space-separated class attribute contains any of the given tokens.
 *
 * @param string   $class_attr Element class attribute.
 * @param string[] $tokens     Class tokens.
 */
function nvxClinicsClassHasAny( string $class_attr, array $tokens ): bool {
	if ( '' === trim( $class_attr ) || array() === $tokens ) {
		return false;
	}
	$classes = preg_split( NVX_REGEX_WHITESPACE, strtolower( trim( $class_attr ) ) ) ?: array();
	$lookup  = array_fill_keys( $classes, true );
	foreach ( $tokens as $token ) {
		if ( isset( $lookup[ strtolower( $token ) ] ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Build a safe word-boundary class regex from a list of tokens (for rare string matches).
 *
 * @param string[] $tokens Class tokens.
 */
function nvxClinicsClassTokenRegex( array $tokens ): string {
	$escaped = array_map(
		static function ( string $token ): string {
			return preg_quote( $token, '/' );
		},
		$tokens
	);
	return '/\b(?:' . implode( '|', $escaped ) . ')\b/i';
}

/*
-------------------------------------------------------------------------
 * Page / map helpers
 * ---------------------------------------------------------------------- */

function nvxIsClinicsHub(): bool {
	if ( ! is_page() ) {
		return false;
	}

	return 'clinicas-de-medicina-estetica-nuvanx' === (string) get_post_field( 'post_name', get_queried_object_id() );
}

/**
 * Whether the current page uses the Sede Local template (hub + branch pages).
 */
function nvxIsSedeTemplate(): bool {
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

function nvxClinicsMapUrl( string $clinic ): string {
	$query = 'goya' === $clinic
		? 'NUVANX Medicina Estética Láser Salamanca Goya Madrid'
		: 'NUVANX Medicina Estética Láser Chamberí Madrid';

	return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( $query );
}

function nvxClinicsNearestBlock( DOMNode $node ): ?DOMElement {
	$current = $node;
	while ( $current instanceof DOMNode && $current->parentNode ) {
		if ( $current instanceof DOMElement && in_array( strtolower( $current->tagName ), array( 'section', 'article' ), true ) ) {
			return $current;
		}
		$current = $current->parentNode;
	}
	return null;
}

/*
-------------------------------------------------------------------------
 * Layout pipeline steps
 * ---------------------------------------------------------------------- */

/** Promote first inner div to section inner. */
function nvxClinicsPromoteSectionInnerDiv( DOMElement $section, array $inner_ready ): void {
	foreach ( $section->childNodes as $child ) {
		if ( ! $child instanceof DOMElement || 'div' !== strtolower( $child->tagName ) ) {
			continue;
		}
		$child_class = trim( $child->getAttribute( 'class' ) );
		if ( nvxClinicsClassHasAny( $child_class, $inner_ready ) ) {
			break;
		}
		// Bare first div (no nvx-* class) → canonical section inner (global gutters).
		if ( '' === $child_class || ! preg_match( '/\bnvx-/', $child_class ) ) {
			$child->setAttribute( 'class', trim( $child_class . ' nvx-brand-section__inner' ) );
		}
		break;
	}
}

/**
 * Promote bare CMS <section>/<div> wrappers to global brand shells.
 */
function nvxClinicsPromoteBareSections( DOMXPath $xpath ): void {
	$sections = $xpath->query( '//section' );
	if ( false === $sections ) {
		return;
	}

	$skip_classes = nvxClinicsSectionSkipClasses();
	$inner_ready  = nvxClinicsSectionInnerReadyClasses();

	foreach ( $sections as $section ) {
		if ( ! $section instanceof DOMElement ) {
			continue;
		}

		$class = trim( $section->getAttribute( 'class' ) );
		if ( nvxClinicsClassHasAny( $class, $skip_classes ) ) {
			continue;
		}

		if ( '' === $class || ! nvxClinicsClassHasAny( $class, array( 'nvx-brand-section' ) ) ) {
			$section->setAttribute( 'class', trim( $class . ' nvx-brand-section' ) );
		}

		nvxClinicsPromoteSectionInnerDiv( $section, $inner_ready );
	}
}

/**
 * Normalizes a wrapper containing multiple page sections into a full-width content flow.
 *
 * @param DOMXPath $xpath The XPath instance used to locate layout wrappers.
 * @return DOMElement|null The first normalized wrapper, or null if none qualifies.
 */
function nvxClinicsNormalizeLayout( DOMXPath $xpath ): ?DOMElement {
	$readable = nvxClinicsReadableMeasureClasses();
	$flow     = nvxClinicsFlowClasses();
	// Match either readable measure or alternate flow class.
	$parts = array();
	foreach ( array_merge( $readable, $flow ) as $token ) {
		$parts[] = 'contains(concat(" ", normalize-space(@class), " "), " ' . $token . ' ")';
	}
	$nodes = $xpath->query( '//*[' . implode( ' or ', $parts ) . ']' );

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

		$classes = preg_split( NVX_REGEX_WHITESPACE, trim( $node->getAttribute( 'class' ) ) ) ?: array();
		$classes = array_values(
			array_filter(
				$classes,
				static function ( string $class_name ) use ( $readable ): bool {
					return ! in_array( $class_name, $readable, true );
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

/** Check if div group qualifies for unwrapping. */
function nvxClinicsShouldUnwrapDivGroup( DOMElement $div, array $protected ): bool {
	$class = trim( $div->getAttribute( 'class' ) );
	if ( nvxClinicsClassHasAny( $class, $protected ) ) {
		return false;
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

	// Need multiple sections (or aria-labelledby grouping of sections).
	$has_aria_group = $div->hasAttribute( 'aria-labelledby' );
	if ( count( $section_children ) < 2 && ! ( $has_aria_group && count( $section_children ) >= 1 ) ) {
		return false;
	}
	if ( count( $section_children ) < $element_children && ( ! $has_aria_group || count( $section_children ) !== $element_children ) ) {
		return false;
	}

	return true;
}

/**
 * Unwrap anonymous divs that only group sections (CMS residue).
 */
function nvxClinicsUnwrapSectionGroups( DOMXPath $xpath ): void {
	$divs = $xpath->query( '//div' );
	if ( false === $divs ) {
		return;
	}

	$protected = nvxClinicsUnwrapProtectedClasses();

	foreach ( iterator_to_array( $divs ) as $div ) {
		if ( ! $div instanceof DOMElement || ! $div->parentNode ) {
			continue;
		}

		if ( ! nvxClinicsShouldUnwrapDivGroup( $div, $protected ) ) {
			continue;
		}

		$parent = $div->parentNode;
		while ( $div->firstChild ) {
			$parent->insertBefore( $div->firstChild, $div );
		}
		$parent->removeChild( $div );
	}
}

/** Locate nearest brand-section ancestor. */
function nvxClinicsFindBrandSectionAncestor( DOMElement $flow ): ?DOMElement {
	$brand_section = null;
	$current       = $flow->parentNode;
	while ( $current instanceof DOMElement ) {
		$class = $current->getAttribute( 'class' );
		if ( nvxClinicsClassHasAny( $class, array( 'nvx-brand-page' ) ) ) {
			break;
		}
		if (
			nvxClinicsClassHasAny( $class, array( 'nvx-brand-section' ) )
			&& ! nvxClinicsClassHasAny( $class, array( 'nvx-brand-hero' ) )
		) {
			$brand_section = $current;
		}
		$current = $current->parentNode;
	}
	return $brand_section;
}

function nvxClinicsShouldHoistFlow( DOMElement $flow, DOMXPath $xpath ): ?DOMElement {
	$brand_section = null;

	if ( $flow->parentNode ) {
		$candidate = nvxClinicsFindBrandSectionAncestor( $flow );
		if ( $candidate instanceof DOMElement && $candidate->parentNode ) {
			$nested = $xpath->query( './/section', $flow );
			if ( false !== $nested && $nested->length > 0 ) {
				$brand_section = $candidate;
			}
		}
	}

	return $brand_section;
}

/**
 * Hoist multi-section stacks out of a single outer brand-section shell so each
 * block gets the same pad-section rhythm as Goya / Chamberí.
 *
 * @return DOMElement|null First hoisted element (for nav insertion).
 */
function nvxClinicsHoistSectionStack( DOMXPath $xpath ): ?DOMElement {
	$flow_tokens = nvxClinicsFlowClasses();
	$parts       = array();
	foreach ( $flow_tokens as $token ) {
		$parts[] = 'contains(concat(" ", normalize-space(@class), " "), " ' . $token . ' ")';
	}
	$flows = $xpath->query( '//*[' . implode( ' or ', $parts ) . ']' );

	if ( false === $flows ) {
		return null;
	}

	$first = null;

	foreach ( iterator_to_array( $flows ) as $flow ) {
		if ( ! $flow instanceof DOMElement ) {
			continue;
		}

		$brand_section = nvxClinicsShouldHoistFlow( $flow, $xpath );
		if ( ! $brand_section ) {
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

/**
 * Ordered layout pipeline for clinics hub CMS HTML.
 *
 * Sequence (do not reorder without checking hoist/unwrap assumptions):
 * 1. promote bare sections → brand-section shells
 * 2. normalize readable multi-section wrappers → content-flow
 * 3. unwrap anonymous section groups
 * 4. hoist flow children out of a single outer brand-section
 * 5. unwrap again (groups revealed by hoist)
 * 6. promote again (new bare sections after hoist)
 *
 * @return array{layout_root: ?DOMElement, hoisted: ?DOMElement}
 */
function nvxClinicsRunLayoutPipeline( DOMXPath $xpath ): array {
	nvxClinicsPromoteBareSections( $xpath );
	$layout_root = nvxClinicsNormalizeLayout( $xpath );
	nvxClinicsUnwrapSectionGroups( $xpath );
	$hoisted = nvxClinicsHoistSectionStack( $xpath );
	nvxClinicsUnwrapSectionGroups( $xpath );
	nvxClinicsPromoteBareSections( $xpath );

	return array(
		'layout_root' => $layout_root instanceof DOMElement ? $layout_root : null,
		'hoisted'     => $hoisted instanceof DOMElement ? $hoisted : null,
	);
}

function nvxClinicsSetLinkAttributes( DOMElement $link, string $clinic ): void {
	$name = 'goya' === $clinic ? 'NUVANX Salamanca–Goya' : 'NUVANX Chamberí';
	$link->setAttribute( 'href', nvxClinicsMapUrl( $clinic ) );
	$link->setAttribute( 'target', '_blank' );
	$link->setAttribute( 'rel', 'noopener noreferrer' );
	$link->setAttribute( 'aria-label', 'Abrir ' . $name . ' en Google Maps' );
	$link->nodeValue = 'Abrir en Google Maps';

	// Map = secondary action (not competing primary); keep non-button utilities.
	nvxClinicsSetBrandButton( $link, 'secondary', array( 'nvx-clinic-map-cta' ) );
}

/**
 * Removes button-related class tokens from a class string.
 *
 * @param string $class The space-separated class string to process.
 * @return string The class string without button-related tokens.
 */
function nvxClinicsClassWithoutButtonChrome( string $class ): string {
	$classes = preg_split( NVX_REGEX_WHITESPACE, trim( $class ) ) ?: array();
	$classes = array_values(
		array_filter(
			$classes,
			static function ( string $c ): bool {
				if ( '' === $c ) {
					return false;
				}
				return ! preg_match( '/^(nvx-brand-btn|nvx-btn)(--[\w-]+)?$/i', $c )
					&& 'nvx-clinic-map-cta' !== $c;
			}
		)
	);
	return implode( ' ', array_unique( $classes ) );
}

/**
 * Applies brand button classes while preserving unrelated class tokens.
 *
 * @param string[] $extra Additional class tokens to include.
 */
function nvxClinicsSetBrandButton( DOMElement $link, string $variant, array $extra = array() ): void {
	$kept   = nvxClinicsClassWithoutButtonChrome( $link->getAttribute( 'class' ) );
	$tokens = preg_split( NVX_REGEX_WHITESPACE, trim( $kept ) ) ?: array();
	$tokens = array_merge(
		$tokens,
		array( 'nvx-brand-btn', 'nvx-brand-btn--' . $variant ),
		$extra
	);
	$tokens = array_values( array_unique( array_filter( $tokens ) ) );
	$link->setAttribute( 'class', implode( ' ', $tokens ) );
}

/**
 * Removes button styling classes from a link while preserving other classes.
 *
 * @param DOMElement $link The link whose classes should be updated.
 * @param string     $replace_with Optional class to add after removing button styling classes.
 */
function nvxClinicsStripButtonClasses( DOMElement $link, string $replace_with = 'nvx-brand-inline-link' ): void {
	$kept   = nvxClinicsClassWithoutButtonChrome( $link->getAttribute( 'class' ) );
	$tokens = preg_split( NVX_REGEX_WHITESPACE, trim( $kept ) ) ?: array();
	if ( '' !== $replace_with && ! in_array( $replace_with, $tokens, true ) ) {
		$tokens[] = $replace_with;
	}
	$link->setAttribute( 'class', implode( ' ', array_unique( array_filter( $tokens ) ) ) );
}

/**
 * Phone / WhatsApp links (hosts + common labels). Demoted to inline text.
 */
function nvxClinicsIsPhoneOrWhatsappLink( string $href, string $text ): bool {
	if ( preg_match( '/^tel:/i', $href ) ) {
		return true;
	}
	// wa.me deep links + official WhatsApp web/api/chat hosts.
	if ( preg_match( '/(?:wa\.me\/|api\.whatsapp\.com|web\.whatsapp\.com|chat\.whatsapp\.com)/i', $href ) ) {
		return true;
	}
	// Labels: WhatsApp, Whats App, WApp.
	if ( preg_match( '/whats\s*app|wapp\b/iu', $text ) ) {
		return true;
	}
	return false;
}

/**
 * Normalizes clinic hub call-to-action links into appropriate primary, secondary, or inline-link styles.
 *
 * @param DOMDocument $dom The document containing the clinic hub markup.
 * @param DOMXPath    $xpath XPath evaluator for locating links within the document.
 */
function nvxClinicsLinkIsMapAction( string $href, string $text ): bool {
	return (bool) preg_match( '/(?:google\.com\/maps|maps\.app|google maps|abrir .+ maps)/iu', $href . ' ' . $text );
}

function nvxClinicsLinkIsSecondaryAction( string $href, string $text ): bool {
	return (bool) preg_match( '/equipo|ver todos|explorar|catálogo|catalogo/iu', $text . ' ' . $href );
}

function nvxClinicsLinkIsPrimaryAction( string $text ): bool {
	return (bool) preg_match( '/valoraci[oó]n|ver sede|reservar/iu', $text );
}

function nvxClinicsCardLinkNeedsDemotion( string $text, DOMElement $link ): bool {
	if ( preg_match( '/^(ver|reservar|solicitar|abrir)\b/iu', $text ) ) {
		return false;
	}
	return (bool) preg_match( '/\bnvx-brand-card\b/i', nvxClinicsAncestorClassBlob( $link ) );
}

/** Determine the presentation treatment for one clinic CTA. */
function nvxClinicsCtaTreatment( DOMElement $link, string $href, string $text, bool $isBtn ): string {
	$treatment = '';
	$parent    = $link->parentNode;

	if ( preg_match( '/^Solicitar\s*$/iu', $text ) ) {
		$treatment = 'solicitar';
	} elseif ( $parent instanceof DOMElement && in_array( strtolower( $parent->tagName ), array( 'h1', 'h2', 'h3', 'h4' ), true ) ) {
		$treatment = 'inline';
	} elseif ( nvxClinicsIsPhoneOrWhatsappLink( $href, $text ) ) {
		$treatment = 'inline';
	} elseif ( nvxClinicsLinkIsMapAction( $href, $text ) ) {
		$treatment = 'map';
	} elseif ( nvxClinicsLinkIsSecondaryAction( $href, $text ) ) {
		$treatment = 'secondary';
	} elseif ( $isBtn && nvxClinicsLinkIsPrimaryAction( $text ) ) {
		$treatment = 'primary';
	} elseif ( $isBtn && nvxClinicsCardLinkNeedsDemotion( $text, $link ) ) {
		$treatment = 'inline';
	}

	return $treatment;
}

/**
 * Classifies a CTA link and applies the corresponding brand styling and content.
 *
 * @param DOMElement $link The CTA link element to classify and update.
 * @param string $href The link destination.
 * @param string $text The visible link text.
 * @param string $class The link's existing class attribute.
 */
function nvxClinicsClassifySingleCtaLink( DOMElement $link, string $href, string $text, string $class ): void {
	$isBtn     = (bool) preg_match( '/\b(nvx-brand-btn|nvx-btn)\b/i', $class );
	$treatment = nvxClinicsCtaTreatment( $link, $href, $text, $isBtn );

	switch ( $treatment ) {
		case 'solicitar':
			$link->nodeValue = 'Reservar valoración';
			nvxClinicsSetBrandButton( $link, 'primary' );
			if ( '' === $href || '#' === $href ) {
				$valoracion = function_exists( 'nvx_cta_valoracion_url' )
					? nvx_cta_valoracion_url()
					: home_url( '/madrid/valoracion/' );
				$link->setAttribute( 'href', $valoracion );
			}
			break;
		case 'inline':
			nvxClinicsStripButtonClasses( $link, 'nvx-brand-inline-link' );
			break;
		case 'map':
			nvxClinicsSetBrandButton( $link, 'secondary', array( 'nvx-clinic-map-cta' ) );
			break;
		case 'secondary':
			nvxClinicsSetBrandButton( $link, 'secondary' );
			break;
		case 'primary':
			nvxClinicsSetBrandButton( $link, 'primary' );
			break;
		default:
			// No treatment means the existing link presentation is already appropriate.
			break;
	}
}

/**
 * Normalizes clinic hub call-to-action links into appropriate primary, secondary, or inline-link styles.
 *
 * @param DOMDocument $dom The document containing the clinic hub markup.
 * @param DOMXPath    $xpath XPath evaluator for locating links within the document.
 */
function nvxClinicsNormalizeCtaHierarchy( DOMDocument $dom, DOMXPath $xpath ): void {
	$root = $dom->getElementById( 'nvx-clinics-document' );
	if ( ! $root ) {
		return;
	}

	foreach ( $xpath->query( './/a', $root ) ?: array() as $link ) {
		if ( ! $link instanceof DOMElement ) {
			continue;
		}

		$href  = $link->getAttribute( 'href' );
		$text  = trim( preg_replace( NVX_REGEX_WHITESPACE_U, ' ', $link->textContent ) ?? '' );
		$class = $link->getAttribute( 'class' );

		nvxClinicsClassifySingleCtaLink( $link, $href, $text, $class );
	}
}

/**
 * Concatenated class attributes of ancestors (for context checks).
 */
function nvxClinicsAncestorClassBlob( DOMNode $node ): string {
	$blob = '';
	$cur  = $node->parentNode;
	while ( $cur instanceof DOMElement ) {
		$blob .= ' ' . $cur->getAttribute( 'class' );
		$cur   = $cur->parentNode;
	}
	return $blob;
}

/*
-------------------------------------------------------------------------
 * Sede inline styles (narrow, class-guarded)
 * ---------------------------------------------------------------------- */

/** Filter out blocked inline style declarations. */
function nvxSedeFilterStyleDeclarations( string $style_v, array $blocked ): array {
	$decls = array_filter( array_map( 'trim', explode( ';', $style_v ) ) );
	$keep  = array();
	foreach ( $decls as $decl ) {
		if ( ! preg_match( '/^([a-z-]+)\s*:/i', $decl, $prop_m ) ) {
			$keep[] = $decl;
			continue;
		}
		$prop = strtolower( $prop_m[1] );
		if ( in_array( $prop, $blocked, true ) || nvxStrStartsWith( $prop, 'margin' ) || nvxStrStartsWith( $prop, 'padding' ) ) {
			continue;
		}
		$keep[] = $decl;
	}
	return $keep;
}

/** Rebuild a single opening tag after filtering its inline style declarations. */
function nvxSedeRebuildTagWithFilteredStyles( array $match, string $class_re, array $blocked ): string {
	$tag_original = $match[1];
	$open_mid     = $match[2];
	if ( ! preg_match( '/\bclass\s*=\s*(["\'])([^"\']*)\1/iu', $open_mid, $class_m ) || ! preg_match( $class_re, $class_m[2] ) ) {
		return $match[0];
	}

	$style_q = $match[3];
	$style_v = $match[4];
	$keep    = nvxSedeFilterStyleDeclarations( $style_v, $blocked );

	if ( array() === $keep ) {
		$new_mid = preg_replace( '/\sstyle=(["\'])([^"\']*)\1/iu', '', $open_mid, 1 ) ?? $open_mid;
		return '<' . $tag_original . $new_mid . '>';
	}

	$new_style = implode( '; ', $keep );
	$new_mid   = preg_replace( '/\sstyle=(["\'])([^"\']*)\1/iu', ' style=' . $style_q . $new_style . $style_q, $open_mid, 1 ) ?? $open_mid;

	return '<' . $tag_original . $new_mid . '>';
}

/**
 * Strip only spacing-related inline styles on known Sede wrapper classes.
 * Other properties (color, width, text-align, etc.) are left for editors.
 *
 * Only rewrites a fixed allow-list of non-void tags so self-closing markup
 * and unrelated elements are never rebuilt.
 */
function nvxSedeStripLayoutInlineStyles( string $content ): string {
	if ( is_admin() || ! nvxIsSedeTemplate() || '' === trim( $content ) ) {
		return $content;
	}

	$targets  = nvxSedeInlineStyleTargetClasses();
	$blocked  = nvxSedeBlockedInlineStyleProperties();
	$allowed  = nvxSedeInlineStyleAllowedTags();
	$class_re = nvxClinicsClassTokenRegex( $targets );
	$tag_alt  = implode(
		'|',
		array_map(
			static function ( string $tag ): string {
				return preg_quote( $tag, '/' );
			},
			$allowed
		)
	);
	// Opening tags only (no trailing /), allow-listed names, style + class required.
	$pattern = '/<(' . $tag_alt . ')\b(?![^>]*\/\s*>)([^>]*?\sstyle=(["\'])([^"\']*)\3[^>]*)>/iu';

	return preg_replace_callback(
		$pattern,
		static function ( array $match ) use ( $class_re, $blocked ): string {
			return nvxSedeRebuildTagWithFilteredStyles( $match, $class_re, $blocked );
		},
		$content
	) ?? $content;
}
add_filter( 'the_content', 'nvxSedeStripLayoutInlineStyles', NVX_HOOK_PRIO_SEDE_INLINE_STYLES );

function nvxClinicsBindLocationBlock( DOMXPath $xpath, DOMElement $heading, array $config ): ?DOMElement {
	$block = nvxClinicsNearestBlock( $heading );
	if ( ! $block ) {
		return null;
	}

	$article = $xpath->query( 'ancestor::article[contains(concat(" ", normalize-space(@class), " "), " nvx-brand-card ")][1]', $heading );
	if ( $article && $article->length && $article->item( 0 ) instanceof DOMElement ) {
		$block = $article->item( 0 );
	}

	$block->setAttribute( 'id', $config['id'] );
	$block->setAttribute( 'class', trim( $block->getAttribute( 'class' ) . ' nvx-clinic-location' ) );
	return $block;
}

/** Identify location block elements for clinics. */
function nvxClinicsIdentifyLocationBlocks( DOMXPath $xpath, array $clinics ): array {
	$blocks = array();
	foreach ( $xpath->query( '//h2|//h3|//h4' ) ?: array() as $heading ) {
		$text = trim( preg_replace( NVX_REGEX_WHITESPACE_U, ' ', $heading->textContent ) ?? $heading->textContent );
		foreach ( $clinics as $key => $config ) {
			if ( isset( $blocks[ $key ] ) || ! preg_match( $config['match'], $text ) ) {
				continue;
			}
			$block = nvxClinicsBindLocationBlock( $xpath, $heading, $config );
			if ( $block ) {
				$blocks[ $key ] = $block;
			}
		}
	}
	return $blocks;
}

/** Process map action links in location blocks. */
function nvxClinicsProcessMapActions( DOMDocument $dom, DOMXPath $xpath, array $blocks ): void {
	foreach ( $blocks as $key => $block ) {
		$links           = $xpath->query( './/a', $block );
		$map_action_seen = false;
		foreach ( $links ?: array() as $link ) {
			if ( ! $link instanceof DOMElement ) {
				continue;
			}
			$text          = trim( preg_replace( NVX_REGEX_WHITESPACE_U, ' ', $link->textContent ) ?? $link->textContent );
			$href          = $link->getAttribute( 'href' );
			$is_map_action = preg_match( '/(?:cómo llegar|como llegar|google maps|maps\.app|google\.[^\/]+\/maps)/iu', $text . ' ' . $href );
			if ( $is_map_action && ! $map_action_seen ) {
				nvxClinicsSetLinkAttributes( $link, $key );
				$map_action_seen = true;
			} elseif ( $is_map_action ) {
				$link->parentNode?->removeChild( $link );
			}
		}

		if ( ! $map_action_seen ) {
			$link = $dom->createElement( 'a', 'Abrir en Google Maps' );
			nvxClinicsSetLinkAttributes( $link, $key );
			$actions = $dom->createElement( 'div' );
			$actions->setAttribute( 'class', 'nvx-brand-actions nvx-clinic-location__actions' );
			$actions->appendChild( $link );
			$block->appendChild( $actions );
		}
	}
}

function nvxClinicsFindNavAnchorInPage( DOMElement $page ): ?DOMElement {
	foreach ( $page->childNodes as $child ) {
		if ( ! $child instanceof DOMElement ) {
			continue;
		}
		$c = $child->getAttribute( 'class' );
		if ( nvxClinicsClassHasAny( $c, array( 'nvx-brand-hero' ) ) ) {
			continue;
		}
		if (
			nvxClinicsClassHasAny( $c, array( 'nvx-brand-section', 'nvx-content-flow' ) )
			|| in_array( strtolower( $child->tagName ), array( 'section', 'nav' ), true )
		) {
			return $child;
		}
	}
	return null;
}

/** Resolve parent+before insertion point for clinic nav. */
function nvxClinicsNavInsertionPoint( DOMXPath $xpath, ?DOMElement $hoisted, ?DOMElement $layout_root ): array {
	$insert_parent = null;
	$insert_before = null;

	$page = $xpath->query( '//*[contains(concat(" ", normalize-space(@class), " "), " nvx-brand-page ")]' )->item( 0 );
	if ( $page instanceof DOMElement ) {
		$insert_parent = $page;
		$insert_before = nvxClinicsFindNavAnchorInPage( $page );
	} elseif ( $hoisted instanceof DOMElement && $hoisted->parentNode ) {
		$insert_parent = $hoisted->parentNode;
		$insert_before = $hoisted;
	} elseif ( $layout_root instanceof DOMElement ) {
		$insert_parent = $layout_root;
		$insert_before = $layout_root->firstChild instanceof DOMElement ? $layout_root->firstChild : null;
	}

	return array(
		'parent' => $insert_parent,
		'before' => $insert_before,
	);
}

/** Insert clinic nav element into document. */
function nvxClinicsInsertNavElement( DOMDocument $dom, DOMXPath $xpath, array $clinics, array $blocks, ?DOMElement $hoisted, ?DOMElement $layout_root ): void {
	if ( ! isset( $blocks['chamberi'], $blocks['goya'] ) || $dom->getElementById( 'nvx-clinics-nav' ) ) {
		return;
	}

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

	$point         = nvxClinicsNavInsertionPoint( $xpath, $hoisted, $layout_root );
	$insert_parent = $point['parent'];
	$insert_before = $point['before'];

	if ( $insert_parent instanceof DOMElement ) {
		if ( $insert_before instanceof DOMElement ) {
			$insert_parent->insertBefore( $nav, $insert_before );
		} else {
			$insert_parent->appendChild( $nav );
		}
	} else {
		$blocks['chamberi']->parentNode?->insertBefore( $nav, $blocks['chamberi'] );
	}
}

/**
 * Whether hub post_content is theme-owned (marker or empty of visible text).
 */
function nvx_clinics_hub_is_managed_content( string $content ): bool {
	if ( false !== strpos( $content, 'NUVANX_GITHUB_MANAGED:clinics-hub' ) ) {
		return true;
	}

	$text = trim( wp_strip_all_tags( $content ) );
	return '' === $text;
}

/**
 * Format E.164 phone for display (ES mobile without +34, spaced).
 */
function nvx_clinics_hub_phone_display( string $e164 ): string {
	$digits = preg_replace( '/^\+34/', '', $e164 );
	$digits = is_string( $digits ) ? preg_replace( '/\D+/', '', $digits ) : '';
	if ( ! is_string( $digits ) || '' === $digits ) {
		return $e164;
	}

	return trim( chunk_split( $digits, 3, ' ' ) );
}

/**
 * Builds the canonical NUVANX clinics hub markup with clinic details, contact links, directions, and valuation calls to action.
 *
 * @return string The complete rendered clinics hub HTML.
 */
function nvx_clinics_hub_page_markup(): string {
	// Global flag to prevent duplicate hero media injection from nvx_ensure_hero_featured_media
	global $nvx_page_shell_has_hero;
	$nvx_page_shell_has_hero = true;

	$clinics  = function_exists( 'nvx_schema_clinics' ) ? nvx_schema_clinics() : array();
	$registry = function_exists( 'nvx_schema_page_registry' ) ? nvx_schema_page_registry() : array();
	$config   = function_exists( 'nvx_get_clinics_config' ) ? nvx_get_clinics_config() : array();

	$chamberi_path = isset( $registry['clinics']['chamberi']['path'] )
		? (string) $registry['clinics']['chamberi']['path']
		: '/medicina-estetica-chamberi/';
	$goya_path     = isset( $registry['clinics']['goya']['path'] )
		? (string) $registry['clinics']['goya']['path']
		: '/clinicas-de-medicina-estetica-nuvanx/medicina-estetica-goya-barrio-salamanca/';

	$chamberi_phone = ! empty( $clinics['chamberi']['telephone'] ) ? (string) $clinics['chamberi']['telephone'] : '+34669319836';
	$goya_phone     = ! empty( $clinics['goya']['telephone'] ) ? (string) $clinics['goya']['telephone'] : '+34647505107';
	$chamberi_maps  = ! empty( $clinics['chamberi']['hasMap'] ) ? (string) $clinics['chamberi']['hasMap'] : nvxClinicsMapUrl( 'chamberi' );
	$goya_maps      = ! empty( $clinics['goya']['hasMap'] ) ? (string) $clinics['goya']['hasMap'] : nvxClinicsMapUrl( 'goya' );
	$chamberi_url   = home_url( $chamberi_path );
	$goya_url       = home_url( $goya_path );
	$valoracion     = home_url( '/madrid/valoracion/' );

	$chamberi_tel_disp = nvx_clinics_hub_phone_display( $chamberi_phone );
	$goya_tel_disp     = nvx_clinics_hub_phone_display( $goya_phone );

	$chamberi_wa = ! empty( $config['chamberi']['whatsapp_href'] ) ? (string) $config['chamberi']['whatsapp_href'] : 'https://wa.me/' . preg_replace( '/\D/', '', $chamberi_phone );
	$goya_wa     = ! empty( $config['goya']['whatsapp_href'] ) ? (string) $config['goya']['whatsapp_href'] : 'https://wa.me/' . preg_replace( '/\D/', '', $goya_phone );

	$chamberi_hours = ! empty( $config['chamberi']['hours'] ) ? (string) $config['chamberi']['hours'] : __( 'lunes a viernes, 12:00–20:00; sábados, 10:00–18:00', 'nuvanx-medical' );
	$goya_hours     = ! empty( $config['goya']['hours'] ) ? (string) $config['goya']['hours'] : __( 'lunes a viernes, 11:00–20:00', 'nuvanx-medical' );

	$chamberi_addr = ! empty( $config['chamberi']['address'] ) ? sprintf( '%s, %s %s', $config['chamberi']['address'], $config['chamberi']['postal_code'], $config['chamberi']['locality'] ) : __( 'Calle de Fernández de la Hoz, 4, Bajo Derecha, 28010 Madrid', 'nuvanx-medical' );
	$goya_addr     = ! empty( $config['goya']['address'] ) ? sprintf( '%s, %s %s', $config['goya']['address'], $config['goya']['postal_code'], $config['goya']['locality'] ) : __( 'Calle de Fernán González, 26, 28009 Madrid', 'nuvanx-medical' );

	$html  = '<div class="nvx-brand-page nvx-clinics-hub-page">';
	$html .= '<section class="nvx-brand-hero" aria-labelledby="nvx-clinics-hub-h1">';
	$html .= '<div class="nvx-brand-hero__inner"><div class="nvx-brand-hero__copy">';
	$html .= '<p class="nvx-brand-kicker">' . esc_html__( 'Clínicas NUVANX · Madrid', 'nuvanx-medical' ) . '</p>';
	$html .= '<h1 id="nvx-clinics-hub-h1" class="nvx-brand-hero__title">' . esc_html__( 'Clínicas NUVANX Medicina Estética Láser en Madrid', 'nuvanx-medical' ) . '</h1>';
	$html .= '<p class="nvx-brand-hero__lead">' . esc_html__( 'Dos centros sanitarios autorizados, una sola dirección médica. Chamberí y Salamanca–Goya con el mismo criterio clínico, protocolos láser y valoración presencial antes de cualquier tratamiento.', 'nuvanx-medical' ) . '</p>';
	$html .= '<div class="nvx-brand-actions">';
	$html .= '<a class="nvx-brand-btn nvx-brand-btn--primary" href="' . esc_url( $valoracion ) . '">' . esc_html__( 'Solicitar valoración médica', 'nuvanx-medical' ) . '</a>';
	$html .= '<a class="nvx-brand-btn nvx-brand-btn--secondary" href="#clinica-chamberi">' . esc_html__( 'Ver sedes', 'nuvanx-medical' ) . '</a>';
	$html .= '</div>';
	$html .= '<p class="nvx-brand-meta">' . esc_html__( 'Chamberí CS20144 · Salamanca–Goya CS20073 · Medicina basada en evidencia', 'nuvanx-medical' ) . '</p>';
	$html .= '</div></div></section>';

	$html .= '<nav class="nvx-brand-section nvx-clinics-nav" aria-label="' . esc_attr__( 'Sedes NUVANX', 'nuvanx-medical' ) . '">';
	$html .= '<div class="nvx-brand-section__inner nvx-cta-pair">';
	$html .= '<a class="nvx-brand-btn nvx-brand-btn--secondary" href="#clinica-chamberi">' . esc_html__( 'Chamberí', 'nuvanx-medical' ) . '</a>';
	$html .= '<a class="nvx-brand-btn nvx-brand-btn--secondary" href="#clinica-goya">' . esc_html__( 'Salamanca–Goya', 'nuvanx-medical' ) . '</a>';
	$html .= '</div></nav>';

	// Chamberí.
	$html .= '<section id="clinica-chamberi" class="nvx-brand-section" aria-labelledby="nvx-clinic-chamberi-title">';
	$html .= '<div class="nvx-brand-section__inner">';
	$html .= '<p class="nvx-brand-kicker">' . esc_html__( 'Registro sanitario CS20144', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-clinic-chamberi-title" class="nvx-brand-title">' . esc_html__( 'Centro Clínico NUVANX Chamberí', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p class="nvx-brand-lead">' . esc_html__( 'A dos minutos de la Plaza de Olavide. Valoración, Endolift®, láser CO₂ y seguimiento en un centro autorizado por la Comunidad de Madrid.', 'nuvanx-medical' ) . '</p>';
	$html .= '<ul class="nvx-brand-list">';
	$html .= '<li><svg class="nvx-icon" aria-hidden="true"><use href="#icon-location"></use></svg> ' . esc_html( $chamberi_addr ) . '</li>';
	$html .= '<li><svg class="nvx-icon" aria-hidden="true"><use href="#icon-phone"></use></svg> <a class="nvx-brand-inline-link" href="' . esc_url( 'tel:' . $chamberi_phone ) . '">' . esc_html( $chamberi_tel_disp ) . '</a> · <a class="nvx-brand-inline-link" href="' . esc_url( $chamberi_wa ) . '" rel="noopener noreferrer" target="_blank">WhatsApp</a></li>';
	$html .= '<li>' . esc_html__( 'Horario:', 'nuvanx-medical' ) . ' ' . esc_html( $chamberi_hours ) . '</li>';
	$html .= '<li>' . esc_html__( 'El Dr. Rivera atiende en Chamberí los martes y jueves.', 'nuvanx-medical' ) . '</li>';
	$html .= '</ul>';
	$html .= '<div class="nvx-brand-actions">';
	$html .= '<a class="nvx-brand-btn nvx-brand-btn--primary" href="' . esc_url( $chamberi_url ) . '">' . esc_html__( 'Ficha de la sede Chamberí', 'nuvanx-medical' ) . '</a>';
	$html .= '<a class="nvx-brand-btn nvx-brand-btn--secondary" href="' . esc_url( $chamberi_maps ) . '" rel="noopener noreferrer" target="_blank">' . esc_html__( 'Cómo llegar', 'nuvanx-medical' ) . '</a>';
	$html .= '</div></div></section>';

	// Goya.
	$html .= '<section id="clinica-goya" class="nvx-brand-section" aria-labelledby="nvx-clinic-goya-title">';
	$html .= '<div class="nvx-brand-section__inner">';
	$html .= '<p class="nvx-brand-kicker">' . esc_html__( 'Registro sanitario CS20073', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-clinic-goya-title" class="nvx-brand-title">' . esc_html__( 'Centro Clínico NUVANX Salamanca–Goya', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p class="nvx-brand-lead">' . esc_html__( 'En el Barrio de Salamanca. Misma dirección médica y protocolos que Chamberí, con atención y valoración en sede propia.', 'nuvanx-medical' ) . '</p>';
	$html .= '<ul class="nvx-brand-list">';
	$html .= '<li><svg class="nvx-icon" aria-hidden="true"><use href="#icon-location"></use></svg> ' . esc_html( $goya_addr ) . '</li>';
	$html .= '<li><svg class="nvx-icon" aria-hidden="true"><use href="#icon-phone"></use></svg> <a class="nvx-brand-inline-link" href="' . esc_url( 'tel:' . $goya_phone ) . '">' . esc_html( $goya_tel_disp ) . '</a> · <a class="nvx-brand-inline-link" href="' . esc_url( $goya_wa ) . '" rel="noopener noreferrer" target="_blank">WhatsApp</a></li>';
	$html .= '<li>' . esc_html__( 'Horario:', 'nuvanx-medical' ) . ' ' . esc_html( $goya_hours ) . '</li>';
	$html .= '<li>' . esc_html__( 'El Dr. Rivera atiende en Salamanca–Goya los miércoles.', 'nuvanx-medical' ) . '</li>';
	$html .= '</ul>';
	$html .= '<div class="nvx-brand-actions">';
	$html .= '<a class="nvx-brand-btn nvx-brand-btn--primary" href="' . esc_url( $goya_url ) . '">' . esc_html__( 'Ficha de la sede Goya', 'nuvanx-medical' ) . '</a>';
	$html .= '<a class="nvx-brand-btn nvx-brand-btn--secondary" href="' . esc_url( $goya_maps ) . '" rel="noopener noreferrer" target="_blank">' . esc_html__( 'Cómo llegar', 'nuvanx-medical' ) . '</a>';
	$html .= '</div></div></section>';

	$html .= '<section class="nvx-brand-section" aria-labelledby="nvx-clinics-hub-cta-title">';
	$html .= '<div class="nvx-brand-section__inner">';
	$html .= '<p class="nvx-brand-kicker">' . esc_html__( 'Siguiente paso', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-clinics-hub-cta-title" class="nvx-brand-title">' . esc_html__( 'Valoración médica en la sede que elijas', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p class="nvx-brand-lead">' . esc_html__( 'La indicación y el presupuesto se definen en consulta presencial. Elige sede al solicitar la valoración o llama directamente a tu centro.', 'nuvanx-medical' ) . '</p>';
	$html .= '<div class="nvx-brand-actions">';
	$html .= '<a class="nvx-brand-btn nvx-brand-btn--primary" href="' . esc_url( $valoracion ) . '">' . esc_html__( 'Solicitar valoración', 'nuvanx-medical' ) . '</a>';
	$html .= '<a class="nvx-brand-btn nvx-brand-btn--secondary" href="' . esc_url( 'tel:' . $chamberi_phone ) . '">' . esc_html( sprintf( __( 'Chamberí · %s', 'nuvanx-medical' ), $chamberi_tel_disp ) ) . '</a>';
	$html .= '<a class="nvx-brand-btn nvx-brand-btn--secondary" href="' . esc_url( 'tel:' . $goya_phone ) . '">' . esc_html( sprintf( __( 'Goya · %s', 'nuvanx-medical' ), $goya_tel_disp ) ) . '</a>';
	$html .= '</div></div></section>';

	$html .= '</div>';

	return $html;
}

/**
 * Replace theme-owned hub marker/empty body with the canonical clinics page.
 */
function nvx_clinics_hub_render_managed( string $content ): string {
	if ( is_admin() || ! nvxIsClinicsHub() ) {
		return $content;
	}

	return nvx_clinics_hub_page_markup();
}
add_filter( 'the_content', 'nvx_clinics_hub_render_managed', NVX_HOOK_PRIO_CLINICS_HUB );

/**
 * Enhances residual CMS clinics markup (legacy path) with layout pipeline.
 *
 * @param string $content The HTML content to enhance.
 * @return string The enhanced HTML content, or the original content when enhancement is unavailable.
 */
function nvxClinicsHubEnhance( string $content ): string {
	if ( is_admin() || ( ! nvxIsClinicsHub() && ! nvxIsSedeTemplate() ) || '' === trim( $content ) ) {
		return $content;
	}
	// Managed hub is fully theme-owned — do not run the CMS residual pipeline.
	if ( nvxIsClinicsHub() && nvx_clinics_hub_is_managed_content( $content ) ) {
		return $content;
	}
	// After managed render the marker is gone; skip DOM work on our own markup.
	if ( nvxIsClinicsHub() && false !== strpos( $content, 'nvx-clinics-hub-page' ) ) {
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
	$pipeline    = nvxClinicsRunLayoutPipeline( $xpath );
	$layout_root = $pipeline['layout_root'];
	$hoisted     = $pipeline['hoisted'];

	$clinics = array(
		'chamberi' => array(
			'id'    => 'clinica-chamberi',
			'label' => 'Chamberí',
			'match' => '/chamber[ií]/iu',
		),
		'goya'     => array(
			'id'    => 'clinica-goya',
			'label' => 'Salamanca–Goya',
			'match' => '/(?:salamanca|goya)/iu',
		),
	);

	$blocks = nvxClinicsIdentifyLocationBlocks( $xpath, $clinics );
	nvxClinicsProcessMapActions( $dom, $xpath, $blocks );
	nvxClinicsNormalizeCtaHierarchy( $dom, $xpath );
	nvxClinicsInsertNavElement( $dom, $xpath, $clinics, $blocks, $hoisted, $layout_root );

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
add_filter( 'the_content', 'nvxClinicsHubEnhance', NVX_HOOK_PRIO_CLINICS_ENHANCE );

/**
 * Register clinics hub as page owner to prevent shell hero duplication.
 *
 * When the shell evaluates $has_managed_editorial in nvx-page-shell.php,
 * this filter ensures clinics hub pages are recognized as managed,
 * preventing the shell from rendering its own hero in addition to
 * the renderer's hero.
 */
add_filter(
	'nvx_page_owner',
	function ( $owner ) {
		if ( ! empty( $owner ) || is_admin() ) {
			return $owner;
		}
		if ( function_exists( 'nvxIsClinicsHub' ) && nvxIsClinicsHub() ) {
			return 'nvx_clinics_hub';
		}
		return $owner;
	},
	10
);

