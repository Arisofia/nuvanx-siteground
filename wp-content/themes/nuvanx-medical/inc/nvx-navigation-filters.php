<?php
/**
 * Navigation and menu filters.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Dynamically inject EXION pages under 'Tratamientos' in the primary menu.
 * This ensures the items appear even if the menu is managed via WordPress Admin.
 *
 * @param array<int, WP_Post> $items Menu items.
 * @param stdClass            $args  Menu args.
 * @return array<int, WP_Post>
 */
function nvx_add_exion_to_tratamientos_menu( $items, $args ) {
	if ( 'primary' !== $args->theme_location ) {
		return $items;
	}

	$tratamientos_id = 0;
	$max_id          = 0;

	// Find the Tratamientos menu item and the highest ID to prevent collisions.
	foreach ( $items as $item ) {
		if ( $item->ID > $max_id ) {
			$max_id = $item->ID;
		}
		if ( false !== strpos( $item->url, '/tratamientos/' ) || 'Tratamientos' === $item->title ) {
			$tratamientos_id = $item->ID;
			// Ensure parent has the correct classes for CSS dropdowns.
			if ( ! in_array( 'menu-item-has-children', $item->classes, true ) ) {
				$item->classes[] = 'menu-item-has-children';
			}
		}
	}

	if ( ! $tratamientos_id ) {
		return $items;
	}

	$exion_pages = array(
		array( 'url' => home_url( '/exion-face/' ), 'label' => 'EXION Face' ),
		array( 'url' => home_url( '/exion-body/' ), 'label' => 'EXION Body' ),
		array( 'url' => home_url( '/exion-fractional/' ), 'label' => 'EXION Fractional' ),
	);

	$order = 1;
	foreach ( $exion_pages as $page ) {
		$max_id++;
		$custom_item                   = new stdClass();
		$custom_item->ID               = $max_id;
		$custom_item->db_id            = $max_id;
		$custom_item->title            = $page['label'];
		$custom_item->url              = $page['url'];
		$custom_item->menu_order       = $order++;
		$custom_item->menu_item_parent = $tratamientos_id;
		$custom_item->type             = 'custom';
		$custom_item->object           = 'custom';
		$custom_item->object_id        = $max_id;
		$custom_item->classes          = array();
		$custom_item->target           = '';
		$custom_item->attr_title       = '';
		$custom_item->description      = '';
		$custom_item->xfn              = '';
		$custom_item->status           = 'publish';

		$items[] = $custom_item;
	}

	return $items;
}
add_filter( 'wp_nav_menu_objects', 'nvx_add_exion_to_tratamientos_menu', 10, 2 );
