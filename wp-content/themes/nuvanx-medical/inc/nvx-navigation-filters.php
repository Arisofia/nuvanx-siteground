<?php
/**
 * Navigation and menu filters.
 *
 * Treatment links are resolved from published WordPress pages. Future or draft
 * routes must not be exposed in either the database-managed menu or the theme
 * fallback because that creates sitewide internal 404s.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Canonical treatment definitions eligible for the primary menu.
 *
 * Each entry may include historical/alternate slugs. The first published page
 * found becomes the public URL; missing and draft pages are omitted.
 *
 * @return array<string, array{label:string, slugs:string[]}>
 */
function nvx_navigation_treatment_definitions(): array {
	return apply_filters(
		'nvx_navigation_treatment_definitions',
		array(
			'exion-face' => array(
				'label' => 'EXION Face',
				'slugs' => array( 'exion-face' ),
			),
			'exion-body' => array(
				'label' => 'EXION Body',
				'slugs' => array( 'exion-body' ),
			),
			'exion-fractional' => array(
				'label' => 'EXION Fractional',
				'slugs' => array( 'exion-fractional' ),
			),
			'emfusion' => array(
				'label' => 'EMFUSION',
				'slugs' => array( 'emfusion' ),
			),
		)
	);
}

/**
 * Resolve the treatment catalogue to published WordPress pages only.
 *
 * @return array<string, array{label:string, slug:string, url:string, page_id:int}>
 */
function nvx_navigation_published_treatments(): array {
	static $catalogue = null;

	if ( is_array( $catalogue ) ) {
		return $catalogue;
	}

	$catalogue = array();

	foreach ( nvx_navigation_treatment_definitions() as $key => $definition ) {
		$label = isset( $definition['label'] ) ? trim( (string) $definition['label'] ) : '';
		$slugs = isset( $definition['slugs'] ) && is_array( $definition['slugs'] ) ? $definition['slugs'] : array();

		if ( '' === $label ) {
			continue;
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
			if ( ! is_string( $url ) || '' === trim( $url ) ) {
				continue;
			}

			$catalogue[ (string) $key ] = array(
				'label'   => $label,
				'slug'    => $slug,
				'url'     => $url,
				'page_id' => (int) $page->ID,
			);
			break;
		}
	}

	return $catalogue;
}

/**
 * Published-route-aware primary menu fallback.
 *
 * Replaces the legacy fallback through wp_nav_menu_args without changing the
 * public header contract. Treatment children are present only when their page
 * exists and is published.
 *
 * @param array<string, mixed> $args wp_nav_menu arguments.
 * @return string|null
 */
function nvx_navigation_primary_fallback( array $args = array() ) {
	$treatments = array_values( nvx_navigation_published_treatments() );
	$items      = array(
		array( 'url' => home_url( '/' ), 'label' => __( 'Inicio', 'nuvanx-medical' ) ),
		array(
			'url'      => home_url( '/tratamientos/' ),
			'label'    => __( 'Tratamientos', 'nuvanx-medical' ),
			'children' => $treatments,
		),
		array( 'url' => home_url( '/equipo-medico/' ), 'label' => __( 'Equipo médico', 'nuvanx-medical' ) ),
		array( 'url' => home_url( '/clinicas-de-medicina-estetica-nuvanx/' ), 'label' => __( 'Clínicas', 'nuvanx-medical' ) ),
		array( 'url' => home_url( '/blog/' ), 'label' => __( 'Blog', 'nuvanx-medical' ) ),
		array( 'url' => home_url( '/contacto/' ), 'label' => __( 'Contacto', 'nuvanx-medical' ) ),
	);

	$html = '<ul class="nvx-nav__list">';
	foreach ( $items as $item ) {
		$children     = isset( $item['children'] ) && is_array( $item['children'] ) ? $item['children'] : array();
		$has_children = array() !== $children;
		$li_class     = 'nvx-nav__item' . ( $has_children ? ' menu-item-has-children' : '' );

		$html .= sprintf(
			'<li class="%1$s"><a class="nvx-nav__link" href="%2$s">%3$s</a>',
			esc_attr( $li_class ),
			esc_url( (string) $item['url'] ),
			esc_html( (string) $item['label'] )
		);

		if ( $has_children ) {
			$html .= '<ul class="sub-menu">';
			foreach ( $children as $child ) {
				$html .= sprintf(
					'<li class="nvx-nav__item"><a class="nvx-nav__link" href="%1$s">%2$s</a></li>',
					esc_url( (string) $child['url'] ),
					esc_html( (string) $child['label'] )
				);
			}
			$html .= '</ul>';
		}

		$html .= '</li>';
	}
	$html .= '</ul>';

	if ( ! array_key_exists( 'echo', $args ) || $args['echo'] ) {
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped during assembly.
		return null;
	}

	return $html;
}

/**
 * Ensure the canonical primary fallback cannot expose unpublished routes.
 *
 * @param array<string, mixed> $args wp_nav_menu arguments.
 * @return array<string, mixed>
 */
function nvx_navigation_filter_menu_args( array $args ): array {
	if ( 'primary' === ( $args['theme_location'] ?? '' ) ) {
		$args['fallback_cb'] = 'nvx_navigation_primary_fallback';
	}

	return $args;
}
add_filter( 'wp_nav_menu_args', 'nvx_navigation_filter_menu_args', 20 );

/**
 * Dynamically inject published EXION/EMFUSION pages under Tratamientos.
 *
 * Database-managed menus receive only published routes. Existing menu items are
 * preserved and deduplicated by normalized URL.
 *
 * @param array<int, WP_Post|stdClass> $items Menu items.
 * @param stdClass                     $args  Menu args.
 * @return array<int, WP_Post|stdClass>
 */
function nvx_add_exion_to_tratamientos_menu( $items, $args ) {
	if ( ! isset( $args->theme_location ) || 'primary' !== $args->theme_location ) {
		return $items;
	}

	$published       = nvx_navigation_published_treatments();
	$tratamientos_id = 0;
	$max_id          = 0;
	$max_child_order = 0;
	$existing_urls   = array();

	foreach ( $items as $item ) {
		$item_id = isset( $item->ID ) ? (int) $item->ID : 0;
		$max_id  = max( $max_id, $item_id );

		if ( isset( $item->url ) && is_string( $item->url ) ) {
			$existing_urls[ untrailingslashit( $item->url ) ] = true;
		}

		if (
			( isset( $item->url ) && false !== strpos( (string) $item->url, '/tratamientos/' ) )
			|| ( isset( $item->title ) && 'Tratamientos' === $item->title )
		) {
			$tratamientos_id = $item_id;
		}
	}

	if ( ! $tratamientos_id || array() === $published ) {
		return $items;
	}

	foreach ( $items as $item ) {
		if ( isset( $item->menu_item_parent ) && (int) $item->menu_item_parent === $tratamientos_id ) {
			$max_child_order = max( $max_child_order, isset( $item->menu_order ) ? (int) $item->menu_order : 0 );
		}
	}

	$added = 0;
	foreach ( $published as $page ) {
		$normalized_url = untrailingslashit( $page['url'] );
		if ( isset( $existing_urls[ $normalized_url ] ) ) {
			continue;
		}

		$max_id++;
		$max_child_order++;
		$custom_item                   = new stdClass();
		$custom_item->ID               = $max_id;
		$custom_item->db_id            = $max_id;
		$custom_item->title            = $page['label'];
		$custom_item->url              = $page['url'];
		$custom_item->menu_order       = $max_child_order;
		$custom_item->menu_item_parent = $tratamientos_id;
		$custom_item->type             = 'custom';
		$custom_item->object           = 'page';
		$custom_item->object_id        = $page['page_id'];
		$custom_item->classes          = array( 'menu-item', 'menu-item-type-post_type', 'menu-item-object-page' );
		$custom_item->target           = '';
		$custom_item->attr_title       = '';
		$custom_item->description      = '';
		$custom_item->xfn              = '';
		$custom_item->status           = 'publish';

		$items[]                           = $custom_item;
		$existing_urls[ $normalized_url ] = true;
		$added++;
	}

	if ( $added > 0 ) {
		foreach ( $items as $item ) {
			if ( isset( $item->ID ) && (int) $item->ID === $tratamientos_id ) {
				$item->classes = isset( $item->classes ) && is_array( $item->classes ) ? $item->classes : array();
				if ( ! in_array( 'menu-item-has-children', $item->classes, true ) ) {
					$item->classes[] = 'menu-item-has-children';
				}
				break;
			}
		}
	}

	return $items;
}
add_filter( 'wp_nav_menu_objects', 'nvx_add_exion_to_tratamientos_menu', 10, 2 );
