<?php
/**
 * Navigation and menu filters.
 *
 * The WordPress menu assigned to the `primary` location is the source of truth.
 * Theme code provides presentation classes, removes unpublished page targets and
 * supplies a published-route-aware fallback when no menu has been assigned.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Canonical fallback architecture.
 *
 * This is not used to overwrite a database-managed menu. It exists only so a
 * missing menu assignment cannot leave desktop or mobile navigation empty.
 * Candidate pages are resolved at runtime and unpublished routes are omitted.
 *
 * @return array<int,array<string,mixed>>
 */
function nvx_navigation_primary_blueprint(): array {
	return apply_filters(
		'nvx_navigation_primary_blueprint',
		array(
			array(
				'label' => __( 'Inicio', 'nuvanx-medical' ),
				'url'   => home_url( '/' ),
			),
			array(
				'label'    => __( 'Soluciones', 'nuvanx-medical' ),
				'slugs'    => array( 'soluciones-medicas', 'tratamientos' ),
				'mega'     => true,
				'children' => array(
					array( 'label' => 'Rostro y cuello', 'slugs' => array( 'papada-definicion-mandibular-madrid', 'endolift-facial-papada-mandibula' ) ),
					array( 'label' => 'Calidad de piel', 'slugs' => array( 'calidad-piel-firmeza-luminosidad-madrid', 'exion-face' ) ),
					array( 'label' => 'Contorno corporal', 'slugs' => array( 'remodelacion-corporal-laser-madrid', 'endolaser-corporal-grasa-localizada' ) ),
					array( 'label' => 'Cambios posgestacionales', 'slugs' => array( 'tratamiento-postparto-abdomen-contorno-corporal-madrid' ) ),
					array( 'label' => 'Cicatrices, poros y textura', 'slugs' => array( 'cicatrices-acne-poros-textura-madrid', 'laser-co2-fraccionado-madrid-textura-cicatrices-poro' ) ),
					array( 'label' => 'Manchas, rojeces y fotodaño', 'slugs' => array( 'manchas-rojeces-fotorejuvenecimiento-ipl-madrid', 'btl-exilite-ipl-madrid' ) ),
					array( 'label' => 'Medicina estética masculina', 'slugs' => array( 'contorno-corporal-masculino-madrid' ) ),
				),
			),
			array(
				'label'    => __( 'Protocolos Signature', 'nuvanx-medical' ),
				'slugs'    => array( 'protocolos-signature' ),
				'mega'     => true,
				'children' => array(
					array( 'label' => 'NUVANX Contour Architecture™', 'slugs' => array( 'remodelacion-corporal-laser-madrid' ) ),
					array( 'label' => 'NUVANX Post-Maternity Contour™', 'slugs' => array( 'tratamiento-postparto-abdomen-contorno-corporal-madrid' ) ),
					array( 'label' => 'NUVANX Profile Definition™', 'slugs' => array( 'papada-definicion-mandibular-madrid' ) ),
					array( 'label' => 'NUVANX Skin Architecture™', 'slugs' => array( 'calidad-piel-firmeza-luminosidad-madrid' ) ),
					array( 'label' => 'NUVANX Surface Renewal™', 'slugs' => array( 'cicatrices-acne-poros-textura-madrid' ) ),
					array( 'label' => 'NUVANX Tone Correction™', 'slugs' => array( 'manchas-rojeces-fotorejuvenecimiento-ipl-madrid' ) ),
				),
			),
			array(
				'label'    => __( 'Tecnología', 'nuvanx-medical' ),
				'slugs'    => array( 'medicina-estetica-laser' ),
				'mega'     => true,
				'children' => array(
					array( 'label' => 'Endolift® facial', 'slugs' => array( 'endolift-facial-papada-mandibula' ) ),
					array( 'label' => 'Endoláser corporal', 'slugs' => array( 'endolaser-corporal-grasa-localizada' ) ),
					array( 'label' => 'EXION® Face', 'slugs' => array( 'exion-face' ) ),
					array( 'label' => 'EXION® Body', 'slugs' => array( 'exion-body' ) ),
					array( 'label' => 'EXION® Fractional RF', 'slugs' => array( 'exion-fractional' ) ),
					array( 'label' => 'Láser CO₂ fraccionado', 'slugs' => array( 'laser-co2-fraccionado-madrid-textura-cicatrices-poro' ) ),
					array( 'label' => 'BTL EXILITE™ IPL', 'slugs' => array( 'btl-exilite-ipl-madrid' ) ),
					array( 'label' => 'EMFUSION®', 'slugs' => array( 'emfusion' ) ),
					array( 'label' => 'Medicina inyectable', 'slugs' => array( 'medicina-estetica' ) ),
				),
			),
			array( 'label' => __( 'Casos clínicos', 'nuvanx-medical' ), 'slugs' => array( 'casos-clinicos' ) ),
			array( 'label' => __( 'Equipo médico', 'nuvanx-medical' ), 'slugs' => array( 'equipo-medico' ) ),
			array(
				'label'    => __( 'Clínicas', 'nuvanx-medical' ),
				'slugs'    => array( 'clinicas-de-medicina-estetica-nuvanx' ),
				'children' => array(
					array( 'label' => 'Chamberí', 'slugs' => array( 'medicina-estetica-chamberi', 'clinica-medicina-estetica-chamberi' ) ),
					array( 'label' => 'Salamanca–Goya', 'slugs' => array( 'clinicas-de-medicina-estetica-nuvanx/medicina-estetica-goya-barrio-salamanca', 'medicina-estetica-goya-barrio-salamanca' ) ),
				),
			),
			array( 'label' => __( 'Journal', 'nuvanx-medical' ), 'slugs' => array( 'blog' ) ),
		)
	);
}

/**
 * Resolve the first published page among a list of candidate slugs.
 *
 * @param string[] $slugs Candidate page paths.
 * @return array{url:string,page_id:int}|null
 */
function nvx_navigation_resolve_published_page( array $slugs ): ?array {
	static $cache = array();

	$key = implode( '|', array_map( 'strval', $slugs ) );
	if ( array_key_exists( $key, $cache ) ) {
		return $cache[ $key ];
	}

	foreach ( $slugs as $candidate ) {
		$slug = trim( (string) $candidate, '/' );
		if ( '' === $slug ) {
			continue;
		}

		$page = get_page_by_path( $slug, OBJECT, 'page' );
		if ( ! $page instanceof WP_Post || 'publish' !== get_post_status( $page ) ) {
			continue;
		}

		$url = get_permalink( $page );
		if ( is_string( $url ) && '' !== trim( $url ) ) {
			$cache[ $key ] = array(
				'url'     => $url,
				'page_id' => (int) $page->ID,
			);
			return $cache[ $key ];
		}
	}

	$cache[ $key ] = null;
	return null;
}

/**
 * Resolve one fallback blueprint node and its published descendants.
 *
 * @param array<string,mixed> $node Blueprint node.
 * @return array<string,mixed>|null
 */
function nvx_navigation_resolve_blueprint_node( array $node ): ?array {
	$children = array();
	foreach ( isset( $node['children'] ) && is_array( $node['children'] ) ? $node['children'] : array() as $child ) {
		if ( ! is_array( $child ) ) {
			continue;
		}
		$resolved_child = nvx_navigation_resolve_blueprint_node( $child );
		if ( is_array( $resolved_child ) ) {
			$children[] = $resolved_child;
		}
	}

	$url = isset( $node['url'] ) ? trim( (string) $node['url'] ) : '';
	if ( '' === $url ) {
		$destination = nvx_navigation_resolve_published_page(
			isset( $node['slugs'] ) && is_array( $node['slugs'] ) ? $node['slugs'] : array()
		);
		$url = is_array( $destination ) ? $destination['url'] : '';
	}

	if ( '' === $url && array() !== $children ) {
		$url = (string) $children[0]['url'];
	}

	$label = isset( $node['label'] ) ? trim( (string) $node['label'] ) : '';
	if ( '' === $label || ( '' === $url && array() === $children ) ) {
		return null;
	}

	return array(
		'label'    => $label,
		'url'      => $url,
		'mega'     => ! empty( $node['mega'] ),
		'children' => $children,
	);
}

/**
 * Resolve the fallback architecture without exposing drafts or missing pages.
 *
 * @return array<int,array<string,mixed>>
 */
function nvx_navigation_resolved_fallback(): array {
	$items = array();
	foreach ( nvx_navigation_primary_blueprint() as $node ) {
		if ( ! is_array( $node ) ) {
			continue;
		}
		$resolved = nvx_navigation_resolve_blueprint_node( $node );
		if ( is_array( $resolved ) ) {
			$items[] = $resolved;
		}
	}
	return $items;
}

/**
 * Render fallback menu items recursively.
 *
 * @param array<int,array<string,mixed>> $items Menu items.
 * @param int                            $depth Current depth.
 */
function nvx_navigation_render_fallback_items( array $items, int $depth = 0 ): string {
	$html = '';
	foreach ( $items as $item ) {
		$children     = isset( $item['children'] ) && is_array( $item['children'] ) ? $item['children'] : array();
		$has_children = array() !== $children;
		$classes      = array( 'nvx-nav__item', 'nvx-nav__item--depth-' . $depth );

		if ( $has_children ) {
			$classes[] = 'menu-item-has-children';
		}
		if ( 0 === $depth && ! empty( $item['mega'] ) ) {
			$classes[] = 'nvx-nav__item--mega';
		}

		$link_attributes = $has_children ? ' aria-haspopup="true" data-nvx-menu-parent="true"' : '';
		$html           .= '<li class="' . esc_attr( implode( ' ', $classes ) ) . '">';
		$html           .= '<a class="nvx-nav__link" data-nvx-menu-depth="' . esc_attr( (string) $depth ) . '" href="' . esc_url( (string) $item['url'] ) . '"' . $link_attributes . '>' . esc_html( (string) $item['label'] ) . '</a>';

		if ( $has_children ) {
			$html .= '<ul class="sub-menu">' . nvx_navigation_render_fallback_items( $children, $depth + 1 ) . '</ul>';
		}
		$html .= '</li>';
	}
	return $html;
}

/**
 * Published-route-aware fallback for both desktop and mobile navigation.
 *
 * @param array<string,mixed> $args wp_nav_menu arguments.
 * @return string|null
 */
function nvx_navigation_primary_fallback( array $args = array() ) {
	$menu_class = isset( $args['menu_class'] ) && '' !== trim( (string) $args['menu_class'] )
		? trim( (string) $args['menu_class'] )
		: 'nvx-nav__list';
	$menu_id = isset( $args['menu_id'] ) && '' !== trim( (string) $args['menu_id'] )
		? ' id="' . esc_attr( trim( (string) $args['menu_id'] ) ) . '"'
		: '';

	$html = '<ul' . $menu_id . ' class="' . esc_attr( $menu_class ) . '">';
	$html .= nvx_navigation_render_fallback_items( nvx_navigation_resolved_fallback() );
	$html .= '</ul>';

	if ( ! array_key_exists( 'echo', $args ) || $args['echo'] ) {
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped during assembly.
		return null;
	}

	return $html;
}

/**
 * Apply the same safe fallback and depth contract to every primary-menu render.
 *
 * @param array<string,mixed> $args wp_nav_menu arguments.
 * @return array<string,mixed>
 */
function nvx_navigation_filter_menu_args( array $args ): array {
	if ( 'primary' !== ( $args['theme_location'] ?? '' ) ) {
		return $args;
	}

	$args['fallback_cb'] = 'nvx_navigation_primary_fallback';
	$args['depth']       = 3;
	$args['item_spacing'] = 'discard';
	return $args;
}
add_filter( 'wp_nav_menu_args', 'nvx_navigation_filter_menu_args', 20 );

/**
 * Remove unpublished page items and all their descendants from public menus.
 *
 * WordPress remains the source of truth, but a stale menu entry cannot expose a
 * draft protocol or a pending medical/legal working-name page.
 *
 * @param array<int,WP_Post|stdClass> $items Menu items.
 * @param stdClass                    $args Menu arguments.
 * @return array<int,WP_Post|stdClass>
 */
function nvx_navigation_prune_unpublished_items( $items, $args ) {
	if ( ! isset( $args->theme_location ) || 'primary' !== $args->theme_location ) {
		return $items;
	}

	$blocked = array();
	foreach ( $items as $item ) {
		$item_id   = isset( $item->ID ) ? (int) $item->ID : 0;
		$object_id = isset( $item->object_id ) ? (int) $item->object_id : 0;
		$object    = isset( $item->object ) ? (string) $item->object : '';

		if ( $item_id > 0 && 'page' === $object && $object_id > 0 && 'publish' !== get_post_status( $object_id ) ) {
			$blocked[ $item_id ] = true;
		}
	}

	$changed = true;
	while ( $changed ) {
		$changed = false;
		foreach ( $items as $item ) {
			$item_id = isset( $item->ID ) ? (int) $item->ID : 0;
			$parent  = isset( $item->menu_item_parent ) ? (int) $item->menu_item_parent : 0;
			if ( $item_id > 0 && $parent > 0 && isset( $blocked[ $parent ] ) && ! isset( $blocked[ $item_id ] ) ) {
				$blocked[ $item_id ] = true;
				$changed             = true;
			}
		}
	}

	return array_values(
		array_filter(
			$items,
			static function ( $item ) use ( $blocked ): bool {
				$item_id = isset( $item->ID ) ? (int) $item->ID : 0;
				return ! isset( $blocked[ $item_id ] );
			}
		)
	);
}
add_filter( 'wp_nav_menu_objects', 'nvx_navigation_prune_unpublished_items', 20, 2 );

/** Normalize a menu label for presentation-role detection. */
function nvx_navigation_label_key( string $label ): string {
	return sanitize_title( remove_accents( wp_strip_all_tags( $label ) ) );
}

/**
 * Add stable BEM/depth classes without requiring a custom walker.
 *
 * The optional `nvx-menu--mega` class can also be assigned manually in
 * Appearance → Menus. Known definitive pillars receive it automatically.
 *
 * @param string[]                 $classes Existing item classes.
 * @param WP_Post|stdClass         $item Menu item.
 * @param stdClass                 $args Menu arguments.
 * @param int                      $depth Menu depth.
 * @return string[]
 */
function nvx_navigation_item_classes( array $classes, $item, $args, int $depth ): array {
	if ( ! isset( $args->theme_location ) || 'primary' !== $args->theme_location ) {
		return $classes;
	}

	$classes[] = 'nvx-nav__item';
	$classes[] = 'nvx-nav__item--depth-' . $depth;

	$label_key  = isset( $item->title ) ? nvx_navigation_label_key( (string) $item->title ) : '';
	$mega_roots = array( 'soluciones', 'protocolos-signature', 'tecnologia' );
	if ( 0 === $depth && ( in_array( 'nvx-menu--mega', $classes, true ) || in_array( $label_key, $mega_roots, true ) ) ) {
		$classes[] = 'nvx-nav__item--mega';
	}

	return array_values( array_unique( array_filter( $classes ) ) );
}
add_filter( 'nav_menu_css_class', 'nvx_navigation_item_classes', 20, 4 );

/**
 * Add stable link classes and parent semantics without changing header.php.
 *
 * @param array<string,string> $atts Link attributes.
 * @param WP_Post|stdClass     $item Menu item.
 * @param stdClass             $args Menu arguments.
 * @param int                  $depth Menu depth.
 * @return array<string,string>
 */
function nvx_navigation_link_attributes( array $atts, $item, $args, int $depth ): array {
	if ( ! isset( $args->theme_location ) || 'primary' !== $args->theme_location ) {
		return $atts;
	}

	$classes       = preg_split( '/\s+/', trim( (string) ( $atts['class'] ?? '' ) ), -1, PREG_SPLIT_NO_EMPTY );
	$classes       = is_array( $classes ) ? $classes : array();
	$classes[]     = 'nvx-nav__link';
	$atts['class'] = implode( ' ', array_values( array_unique( $classes ) ) );
	$atts['data-nvx-menu-depth'] = (string) $depth;

	$item_classes = isset( $item->classes ) && is_array( $item->classes ) ? $item->classes : array();
	if ( in_array( 'menu-item-has-children', $item_classes, true ) ) {
		$atts['aria-haspopup']       = 'true';
		$atts['data-nvx-menu-parent'] = 'true';
	}

	return $atts;
}
add_filter( 'nav_menu_link_attributes', 'nvx_navigation_link_attributes', 20, 4 );
