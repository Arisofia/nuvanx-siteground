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

/* -------------------------------------------------------------------------
 * Shared class / style lists (defined once; helpers return static caches)
 * ---------------------------------------------------------------------- */

/**
 * Section class tokens that must not be rewritten to brand-section shells.
 *
 * @return string[]
 */
function nvx_clinics_section_skip_classes(): array {
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
function nvx_clinics_section_inner_ready_classes(): array {
	static $classes = null;
	if ( null === $classes ) {
		$classes = array(
			'nvx-brand-section__inner',
			'nvx-brand-grid',
			'nvx-shell',
			'nvx-clinics-content-flow',
			'nvx-content-flow',
			'nvx-brand-readable',
		);
	}
	return $classes;
}

/**
 * Div wrappers that must not be unwrapped (canonical structure).
 *
 * @return string[]
 */
function nvx_clinics_unwrap_protected_classes(): array {
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
function nvx_clinics_flow_classes(): array {
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
function nvx_clinics_readable_measure_classes(): array {
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
 * CMS / legacy wrapper classes where inline layout styles may be stripped on Sede pages.
 * Editors can keep custom styles on other elements; only these get cleaned.
 *
 * @return string[]
 */
function nvx_sede_inline_style_target_classes(): array {
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
function nvx_sede_blocked_inline_style_properties(): array {
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
function nvx_sede_inline_style_allowed_tags(): array {
	static $tags = null;
	if ( null === $tags ) {
		$tags = array( 'div', 'section', 'article', 'p', 'span', 'a', 'li', 'h2', 'h3', 'h4' );
	}
	return $tags;
}

/**
 * PHP 7-compatible string prefix check (avoid str_starts_with for WP hosts on 7.x).
 */
function nvx_str_starts_with( string $haystack, string $needle ): bool {
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
function nvx_clinics_class_has_any( string $class_attr, array $tokens ): bool {
	if ( '' === trim( $class_attr ) || array() === $tokens ) {
		return false;
	}
	$classes = preg_split( '/\s+/', strtolower( trim( $class_attr ) ) ) ?: array();
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
function nvx_clinics_class_token_regex( array $tokens ): string {
	$escaped = array_map(
		static function ( string $token ): string {
			return preg_quote( $token, '/' );
		},
		$tokens
	);
	return '/\b(?:' . implode( '|', $escaped ) . ')\b/i';
}

/* -------------------------------------------------------------------------
 * Page / map helpers
 * ---------------------------------------------------------------------- */

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

/* -------------------------------------------------------------------------
 * Layout pipeline steps
 * ---------------------------------------------------------------------- */

/**
 * Promote bare CMS <section>/<div> wrappers to global brand shells.
 */
function nvx_clinics_promote_bare_sections( DOMXPath $xpath ): void {
	$sections = $xpath->query( '//section' );
	if ( false === $sections ) {
		return;
	}

	$skip_classes  = nvx_clinics_section_skip_classes();
	$inner_ready   = nvx_clinics_section_inner_ready_classes();

	foreach ( $sections as $section ) {
		if ( ! $section instanceof DOMElement ) {
			continue;
		}

		$class = trim( $section->getAttribute( 'class' ) );
		if ( nvx_clinics_class_has_any( $class, $skip_classes ) ) {
			continue;
		}

		if ( '' === $class || ! nvx_clinics_class_has_any( $class, array( 'nvx-brand-section' ) ) ) {
			$section->setAttribute( 'class', trim( $class . ' nvx-brand-section' ) );
		}

		foreach ( $section->childNodes as $child ) {
			if ( ! $child instanceof DOMElement || 'div' !== strtolower( $child->tagName ) ) {
				continue;
			}
			$child_class = trim( $child->getAttribute( 'class' ) );
			if ( nvx_clinics_class_has_any( $child_class, $inner_ready ) ) {
				break;
			}
			// Bare first div (no nvx-* class) → canonical section inner (global gutters).
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
	$readable = nvx_clinics_readable_measure_classes();
	$flow     = nvx_clinics_flow_classes();
	// Match either readable measure or legacy/current flow class.
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

		$classes = preg_split( '/\s+/', trim( $node->getAttribute( 'class' ) ) ) ?: array();
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

/**
 * Unwrap anonymous divs that only group sections (legacy CMS).
 */
function nvx_clinics_unwrap_section_groups( DOMXPath $xpath ): void {
	$divs = $xpath->query( '//div' );
	if ( false === $divs ) {
		return;
	}

	$protected = nvx_clinics_unwrap_protected_classes();

	foreach ( iterator_to_array( $divs ) as $div ) {
		if ( ! $div instanceof DOMElement || ! $div->parentNode ) {
			continue;
		}

		$class = trim( $div->getAttribute( 'class' ) );
		if ( nvx_clinics_class_has_any( $class, $protected ) ) {
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
	$flow_tokens = nvx_clinics_flow_classes();
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
		if ( ! $flow instanceof DOMElement || ! $flow->parentNode ) {
			continue;
		}

		// Climb to nearest brand-section ancestor that is not a hero.
		$brand_section = null;
		$current       = $flow->parentNode;
		while ( $current instanceof DOMElement ) {
			$class = $current->getAttribute( 'class' );
			if ( nvx_clinics_class_has_any( $class, array( 'nvx-brand-page' ) ) ) {
				break;
			}
			if (
				nvx_clinics_class_has_any( $class, array( 'nvx-brand-section' ) )
				&& ! nvx_clinics_class_has_any( $class, array( 'nvx-brand-hero' ) )
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
function nvx_clinics_run_layout_pipeline( DOMXPath $xpath ): array {
	nvx_clinics_promote_bare_sections( $xpath );
	$layout_root = nvx_clinics_normalize_layout( $xpath );
	nvx_clinics_unwrap_section_groups( $xpath );
	$hoisted = nvx_clinics_hoist_section_stack( $xpath );
	nvx_clinics_unwrap_section_groups( $xpath );
	nvx_clinics_promote_bare_sections( $xpath );

	return array(
		'layout_root' => $layout_root instanceof DOMElement ? $layout_root : null,
		'hoisted'     => $hoisted instanceof DOMElement ? $hoisted : null,
	);
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

/* -------------------------------------------------------------------------
 * Sede inline styles (narrow, class-guarded)
 * ---------------------------------------------------------------------- */

/**
 * Strip only spacing-related inline styles on known Sede wrapper classes.
 * Other properties (color, width, text-align, etc.) are left for editors.
 *
 * Only rewrites a fixed allow-list of non-void tags so self-closing markup
 * and unrelated elements are never rebuilt.
 */
function nvx_sede_strip_layout_inline_styles( string $content ): string {
	if ( is_admin() || ! nvx_is_sede_template() || '' === trim( $content ) ) {
		return $content;
	}

	$targets   = nvx_sede_inline_style_target_classes();
	$blocked   = nvx_sede_blocked_inline_style_properties();
	$allowed   = nvx_sede_inline_style_allowed_tags();
	$class_re  = nvx_clinics_class_token_regex( $targets );
	$tag_alt   = implode(
		'|',
		array_map(
			static function ( string $tag ): string {
				return preg_quote( $tag, '/' );
			},
			$allowed
		)
	);
	// Opening tags only (no trailing /), allow-listed names, style + class required.
	$pattern   = '/<(' . $tag_alt . ')\b(?![^>]*\/\s*>)([^>]*?\sstyle=(["\'])([^"\']*)\3[^>]*)>/iu';

	return preg_replace_callback(
		$pattern,
		static function ( array $match ) use ( $class_re, $blocked ): string {
			$tag      = strtolower( $match[1] );
			$open_mid = $match[2]; // attributes including style=...
			if ( ! preg_match( '/\bclass\s*=\s*(["\'])([^"\']*)\1/iu', $open_mid, $class_m ) ) {
				return $match[0];
			}
			if ( ! preg_match( $class_re, $class_m[2] ) ) {
				return $match[0];
			}

			$style_q = $match[3];
			$style_v = $match[4];
			$decls   = array_filter( array_map( 'trim', explode( ';', $style_v ) ) );
			$keep    = array();
			foreach ( $decls as $decl ) {
				if ( ! preg_match( '/^([a-z-]+)\s*:/i', $decl, $prop_m ) ) {
					$keep[] = $decl;
					continue;
				}
				$prop = strtolower( $prop_m[1] );
				if ( in_array( $prop, $blocked, true ) ) {
					continue;
				}
				// Catch margin-* / padding-* without listing every longhand.
				if ( nvx_str_starts_with( $prop, 'margin' ) || nvx_str_starts_with( $prop, 'padding' ) ) {
					continue;
				}
				$keep[] = $decl;
			}

			// Rebuild by replacing only the style attribute value (keep attribute order).
			if ( array() === $keep ) {
				$new_mid = preg_replace(
					'/\sstyle=(["\'])([^"\']*)\1/iu',
					'',
					$open_mid,
					1
				) ?? $open_mid;
				return '<' . $tag . $new_mid . '>';
			}

			$new_style = implode( '; ', $keep );
			$new_mid   = preg_replace(
				'/\sstyle=(["\'])([^"\']*)\1/iu',
				' style=' . $style_q . $new_style . $style_q,
				$open_mid,
				1
			) ?? $open_mid;

			return '<' . $tag . $new_mid . '>';
		},
		$content
	) ?? $content;
}
add_filter( 'the_content', 'nvx_sede_strip_layout_inline_styles', 28 );

/* -------------------------------------------------------------------------
 * Hub enhance (map CTAs + nav + layout pipeline)
 * ---------------------------------------------------------------------- */

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

	$xpath    = new DOMXPath( $dom );
	$pipeline = nvx_clinics_run_layout_pipeline( $xpath );
	$layout_root = $pipeline['layout_root'];
	$hoisted     = $pipeline['hoisted'];

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
			$text          = trim( preg_replace( '/\s+/u', ' ', $link->textContent ) ?? $link->textContent );
			$href          = $link->getAttribute( 'href' );
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
				if ( nvx_clinics_class_has_any( $c, array( 'nvx-brand-hero' ) ) ) {
					continue;
				}
				if (
					nvx_clinics_class_has_any( $c, array( 'nvx-brand-section', 'nvx-content-flow' ) )
					|| in_array( strtolower( $child->tagName ), array( 'section', 'nav' ), true )
				) {
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
