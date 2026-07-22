<?php
/**
 * WP-CLI command for the NUVANX production-readiness migration.
 *
 * @package nuvanx-siteground
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

final class NVX_Production_Readiness_Command {
	private const CONFIRMATION_TOKEN = 'retire-prototypes';
	private const LOCK_OPTION        = '_nvx_production_readiness_migration_lock';
	private const LOCK_TTL_SECONDS   = 900;

	/**
	 * Defines the pages approved for publication by governed theme modules.
	 *
	 * @return array Approved page definitions keyed by slug, including title, content marker, and promotion policy.
	 */
	private function approved_pages(): array {
		return array(
			'por-que-nuvanx' => array( 'title' => 'Por qué NUVANX', 'marker' => '<!-- NUVANX_STRATEGY_PAGE:why_nuvanx -->', 'promote' => false ),
			'inversion-medicina-estetica' => array( 'title' => 'Inversión en medicina estética', 'marker' => '<!-- NUVANX_STRATEGY_PAGE:investment -->', 'promote' => false ),
			'soluciones-medicas' => array( 'title' => 'Soluciones médicas', 'marker' => '<!-- NUVANX_STRATEGY_PAGE:solutions -->', 'promote' => true ),
			'protocolos-signature' => array( 'title' => 'Protocolos Signature', 'marker' => '<!-- NUVANX_PROTOCOL_HUB -->', 'promote' => true ),
			'remodelacion-corporal-laser-madrid' => array( 'title' => 'Remodelación corporal láser diseñada según tu anatomía.', 'marker' => '<!-- NUVANX_PROTOCOL_PAGE:contour-architecture -->', 'promote' => true ),
			'tratamiento-postparto-abdomen-contorno-corporal-madrid' => array( 'title' => 'Tratamiento Postparto: Abdomen y Contorno Corporal en Madrid', 'marker' => '<!-- NUVANX_PROTOCOL_PAGE:post-maternity -->', 'promote' => true ),
			'papada-definicion-mandibular-madrid' => array( 'title' => 'Profile Definition: Papada y mandíbula', 'marker' => '<!-- NUVANX_PROTOCOL_PAGE:profile-definition -->', 'promote' => true ),
			'calidad-piel-firmeza-luminosidad-madrid' => array( 'title' => 'Skin Architecture: Firmeza y luminosidad', 'marker' => '<!-- NUVANX_PROTOCOL_PAGE:skin-architecture -->', 'promote' => true ),
			'cicatrices-acne-poros-textura-madrid' => array( 'title' => 'Surface Renewal: Cicatrices y textura', 'marker' => '<!-- NUVANX_PROTOCOL_PAGE:surface-renewal -->', 'promote' => true ),
			'manchas-rojeces-fotorejuvenecimiento-ipl-madrid' => array( 'title' => 'Tone Correction: Manchas y rojeces', 'marker' => '<!-- NUVANX_PROTOCOL_PAGE:tone-correction -->', 'promote' => true ),
		
'tratamiento-ojeras-bolsas-mirada-madrid' => array( 'title' => 'Tratamiento ojeras, bolsas y mirada Madrid', 'marker' => '<!-- NUVANX_PROTOCOL_PAGE:eye-frame -->', 'promote' => true ),
			'grasa-localizada-abdomen-flancos-madrid' => array( 'title' => 'Grasa localizada en abdomen y flancos en Madrid', 'marker' => '<!-- NUVANX_SIGNATURE_PHASE:abdomen-flancos -->', 'promote' => true ),
			'flacidez-grasa-localizada-brazos-madrid' => array( 'title' => 'Flacidez y grasa localizada en brazos en Madrid', 'marker' => '<!-- NUVANX_SIGNATURE_PHASE:brazos -->', 'promote' => true ),
			'grasa-espalda-zona-sujetador-madrid' => array( 'title' => 'Grasa de espalda y zona del sujetador en Madrid', 'marker' => '<!-- NUVANX_SIGNATURE_PHASE:espalda -->', 'promote' => true ),
			'flacidez-muslos-internos-subgluteo-madrid' => array( 'title' => 'Flacidez en muslos internos y región subglútea en Madrid', 'marker' => '<!-- NUVANX_SIGNATURE_PHASE:muslos -->', 'promote' => true ),
		
'tratamiento-rodillas-grasa-flacidez-madrid' => array( 'title' => 'Grasa localizada y flacidez en rodillas en Madrid', 'marker' => '<!-- NUVANX_SIGNATURE_PHASE:rodillas -->', 'promote' => true ),
			'contorno-corporal-masculino-madrid' => array( 'title' => 'Contorno corporal masculino en Madrid', 'marker' => '<!-- NUVANX_SIGNATURE_PHASE:male-contour -->', 'promote' => true ),
		);
	}

	/**
	 * Retrieves the externally defined governed-page contract.
	 *
	 * @return array The governed-page definitions.
	 */
	private function governed_pages(): array {
		if ( ! function_exists( 'nvx_production_readiness_governed_pages' ) ) {
			WP_CLI::error( 'Production-readiness governed-page contract is unavailable.' );
		}
		return nvx_production_readiness_governed_pages();
	}

	/**
	 * Finds a page by its slug.
	 *
	 * @param string $slug The page slug.
	 * @return WP_Post|null The matching page, or null if no page is found.
	 */
	private function page_by_slug( string $slug ): ?WP_Post {
		$page = get_page_by_path( $slug, OBJECT, 'page' );
		return $page instanceof WP_Post ? $page : null;
	}

	/**
	 * Finds navigation menu items that link to a page.
	 *
	 * @param int $page_id The page ID to locate in navigation menus.
	 * @return int[] Unique navigation menu item IDs linked to the page.
	 */
	private function menu_item_ids_for_page( int $page_id ): array {
		$ids = array();
		foreach ( wp_get_nav_menus() as $menu ) {
			$items = wp_get_nav_menu_items( $menu->term_id );
			foreach ( is_array( $items ) ? $items : array() as $item ) {
				if ( 'page' === $item->object && $page_id === (int) $item->object_id ) {
					$ids[] = (int) $item->ID;
				}
			}
		}
		return array_values( array_unique( $ids ) );
	}

	/**
	 * Retrieves the menu ID assigned to the primary navigation location.
	 *
	 * @return int The assigned menu ID, or 0 when no primary menu is configured.
	 */
	private function primary_menu_id(): int {
		$locations = get_nav_menu_locations();
		return isset( $locations['primary'] ) ? (int) $locations['primary'] : 0;
	}

	/**
	 * Flattens navigation blueprint nodes into depth-prefixed signature rows.
	 *
	 * @param array $nodes Navigation nodes containing labels, URLs, and optional children.
	 * @param int   $depth Current nesting depth.
	 * @return array The flattened navigation signature rows.
	 */
	private function flatten_blueprint( array $nodes, int $depth = 0 ): array {
		$rows = array();
		foreach ( $nodes as $node ) {
			$rows[] = $depth . '|' . trim( (string) $node['label'] ) . '|' . untrailingslashit( (string) $node['url'] );
			$children = isset( $node['children'] ) && is_array( $node['children'] ) ? $node['children'] : array();
			$rows = array_merge( $rows, $this->flatten_blueprint( $children, $depth + 1 ) );
		}
		return $rows;
	}

	/**
	 * Flattens hierarchical navigation menu items into signature rows.
	 *
	 * @param array $items Menu items to process.
	 * @param int   $parent Parent menu item ID.
	 * @param int   $depth Current nesting depth.
	 * @return array Flattened menu signature rows.
	 */
	private function flatten_menu( array $items, int $parent = 0, int $depth = 0 ): array {
		$rows = array();
		foreach ( $items as $item ) {
			if ( (int) $item->menu_item_parent !== $parent ) {
				continue;
			}
			$rows[] = $depth . '|' . trim( (string) $item->title ) . '|' . untrailingslashit( (string) $item->url );
			$rows = array_merge( $rows, $this->flatten_menu( $items, (int) $item->ID, $depth + 1 ) );
		}
		return $rows;
	}

	/**
	 * Builds the canonical signature for the primary navigation menu.
	 *
	 * @return array The flattened canonical navigation structure, or an empty array when unavailable.
	 */
	private function canonical_menu_signature(): array {
		if ( ! function_exists( 'nvx_navigation_resolved_fallback' ) ) {
			return array();
		}
		return $this->flatten_blueprint( nvx_navigation_resolved_fallback() );
	}

	/**
	 * Builds the signature of the published items in the primary navigation menu.
	 *
	 * @return array The flattened menu signature, or an empty array when the primary menu is unavailable.
	 */
	private function current_menu_signature(): array {
		$menu_id = $this->primary_menu_id();
		if ( $menu_id < 1 ) {
			return array();
		}
		$items = wp_get_nav_menu_items( $menu_id, array( 'post_status' => 'publish' ) );
		return is_array( $items ) ? $this->flatten_menu( $items ) : array();
	}

	/**
	 * Builds audit rows for approved pages, governed pages, and the primary navigation menu.
	 *
	 * @return array Audit rows containing current status, menu item counts, and expected values.
	 */
	private function audit_rows(): array {
		$rows = array();
		foreach ( $this->approved_pages() as $slug => $definition ) {
			$page = $this->page_by_slug( $slug );
			$rows[] = array(
				'type' => 'approved', 'slug' => $slug, 'id' => $page ? (int) $page->ID : 0,
				'status' => $page ? (string) $page->post_status : 'missing',
				'menu_items' => $page ? count( $this->menu_item_ids_for_page( (int) $page->ID ) ) : 0,
				'expected' => 'publish',
			);
		}
		foreach ( $this->governed_pages() as $slug => $definition ) {
			$page = $this->page_by_slug( $slug );
			$rows[] = array(
				'type' => 'governed', 'slug' => $slug, 'id' => $page ? (int) $page->ID : 0,
				'status' => $page ? (string) $page->post_status : 'absent',
				'menu_items' => $page ? count( $this->menu_item_ids_for_page( (int) $page->ID ) ) : 0,
				'expected' => $definition['status'],
			);
		}
		$current = $this->current_menu_signature();
		$expected = $this->canonical_menu_signature();
		$rows[] = array(
			'type' => 'navigation', 'slug' => 'primary', 'id' => $this->primary_menu_id(),
			'status' => array() !== $expected && $current === $expected ? 'clean' : 'drift',
			'menu_items' => count( $current ), 'expected' => 'canonical',
		);
		return $rows;
	}

	/**
	 * Determines whether all production-readiness audit rows meet their expected state.
	 *
	 * @param array $rows Audit rows describing approved pages, governed pages, and navigation.
	 * @return bool `true` if every row meets its readiness requirements, `false` otherwise.
	 */
	private function is_clean( array $rows ): bool {
		foreach ( $rows as $row ) {
			if ( 'approved' === $row['type'] && 'publish' !== $row['status'] ) {
				return false;
			}
			if ( 'governed' === $row['type'] && ( ! in_array( $row['status'], array( 'absent', $row['expected'] ), true ) || 0 !== (int) $row['menu_items'] ) ) {
				return false;
			}
			if ( 'navigation' === $row['type'] && 'clean' !== $row['status'] ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Acquires the migration lock and schedules its removal when execution ends.
	 *
	 * @return void
	 */
	private function acquire_lock(): void {
		$now = time();
		$existing = (int) get_option( self::LOCK_OPTION, 0 );
		if ( $existing > 0 && ( $now - $existing ) > self::LOCK_TTL_SECONDS ) {
			delete_option( self::LOCK_OPTION );
		}
		if ( ! add_option( self::LOCK_OPTION, (string) $now, '', false ) ) {
			WP_CLI::error( 'Another production-readiness migration is already running.' );
		}
		register_shutdown_function( static function (): void { delete_option( self::LOCK_OPTION ); } );
	}

	/**
	 * Validates confirmation, host, production authorization, and trash settings before applying the migration.
	 *
	 * @param array $assoc_args Associative command arguments, including safety flags.
	 */
	private function validate_invocation( array $assoc_args ): void {
		if ( self::CONFIRMATION_TOKEN !== (string) ( $assoc_args['confirm'] ?? '' ) ) {
			WP_CLI::error( 'Refusing to apply: use --confirm=' . self::CONFIRMATION_TOKEN );
		}
		$host = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
		if ( ! in_array( $host, array( 'staging2.nuvanx.com', 'nuvanx.com', 'www.nuvanx.com' ), true ) ) {
			WP_CLI::error( 'Refusing to apply on unexpected host: ' . $host );
		}
		if ( in_array( $host, array( 'nuvanx.com', 'www.nuvanx.com' ), true ) && ! isset( $assoc_args['allow-production'] ) ) {
			WP_CLI::error( 'Production requires the explicit --allow-production flag.' );
		}
		if ( ! defined( 'EMPTY_TRASH_DAYS' ) || (int) EMPTY_TRASH_DAYS < 1 ) {
			WP_CLI::error( 'Refusing to apply: WordPress trash is disabled.' );
		}
	}

	/**
	 * Publishes approved pages, creates missing pages, and updates their canonical content.
	 *
	 * Existing pages in other statuses are preserved unless their definition permits promotion.
	 */
	private function apply_approved_pages(): void {
		foreach ( $this->approved_pages() as $slug => $definition ) {
			$page = $this->page_by_slug( $slug );
			if ( $page && 'publish' === $page->post_status ) {
				$result = wp_update_post( array( 'ID' => $page->ID, 'post_title' => $definition['title'], 'post_content' => $definition['marker'] ), true );
			} elseif ( $page && ! empty( $definition['promote'] ) && in_array( $page->post_status, array( 'draft', 'pending', 'private' ), true ) ) {
				$result = wp_update_post( array( 'ID' => $page->ID, 'post_status' => 'publish', 'post_title' => $definition['title'], 'post_content' => $definition['marker'] ), true );
			} elseif ( ! $page ) {
				$result = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => $definition['title'], 'post_name' => $slug, 'post_content' => $definition['marker'] ), true );
			} else {
				WP_CLI::warning( sprintf( 'Preserving %s with status %s for manual review.', $slug, $page->post_status ) );
				continue;
			}
			if ( is_wp_error( $result ) ) {
				WP_CLI::error( sprintf( 'Unable to publish %s: %s', $slug, $result->get_error_message() ) );
			}
		}
	}

	/**
	 * Applies the governed-page contract by removing menu links and review metadata, then updating each existing page to its required status.
	 *
	 * @return void
	 */
	private function apply_governed_pages(): void {
		foreach ( $this->governed_pages() as $slug => $definition ) {
			$page = $this->page_by_slug( $slug );
			if ( ! $page ) {
				continue;
			}
			foreach ( $this->menu_item_ids_for_page( (int) $page->ID ) as $item_id ) {
				wp_delete_post( $item_id, true );
			}
			delete_post_meta( (int) $page->ID, '_nvx_strategy_review_status' );
			if ( $definition['status'] === $page->post_status ) {
				continue;
			}
			if ( 'trash' === $definition['status'] ) {
				$result = wp_trash_post( (int) $page->ID );
				if ( ! $result instanceof WP_Post ) {
					WP_CLI::error( 'Unable to trash ' . $slug );
				}
			} else {
				$result = wp_update_post( array( 'ID' => $page->ID, 'post_status' => $definition['status'] ), true );
				if ( is_wp_error( $result ) ) {
					WP_CLI::error( 'Unable to update ' . $slug . ': ' . $result->get_error_message() );
				}
			}
		}
	}

	/**
	 * Adds menu items and their child items to a navigation menu.
	 *
	 * @param int   $menu_id   The navigation menu ID.
	 * @param array $nodes     The menu node definitions to insert.
	 * @param int   $parent_id The parent menu item ID.
	 */
	private function insert_menu_nodes( int $menu_id, array $nodes, int $parent_id = 0 ): void {
		foreach ( $nodes as $node ) {
			$item_id = wp_update_nav_menu_item( $menu_id, 0, array(
				'menu-item-title' => (string) $node['label'],
				'menu-item-url' => (string) $node['url'],
				'menu-item-status' => 'publish',
				'menu-item-parent-id' => $parent_id,
				'menu-item-classes' => ! empty( $node['mega'] ) ? 'nvx-menu--mega' : '',
			) );
			if ( is_wp_error( $item_id ) ) {
				WP_CLI::error( 'Unable to create menu item: ' . $item_id->get_error_message() );
			}
			$this->insert_menu_nodes( $menu_id, is_array( $node['children'] ?? null ) ? $node['children'] : array(), (int) $item_id );
		}
	}

	/**
	 * Rebuilds the primary navigation menu from the canonical navigation blueprint.
	 *
	 * @return void
	 */
	private function apply_primary_menu(): void {
		if ( ! function_exists( 'nvx_navigation_resolved_fallback' ) ) {
			WP_CLI::error( 'Canonical navigation blueprint is unavailable.' );
		}
		$nodes = nvx_navigation_resolved_fallback();
		if ( array() === $nodes ) {
			WP_CLI::error( 'Canonical navigation resolved to an empty tree.' );
		}
		$menu_id = $this->primary_menu_id();
		if ( $menu_id < 1 ) {
			$created = wp_create_nav_menu( 'NUVANX Principal' );
			if ( is_wp_error( $created ) ) {
				WP_CLI::error( $created->get_error_message() );
			}
			$menu_id = (int) $created;
		}
		$items = wp_get_nav_menu_items( $menu_id, array( 'post_status' => 'any' ) );
		foreach ( is_array( $items ) ? $items : array() as $item ) {
			wp_delete_post( (int) $item->ID, true );
		}
		$this->insert_menu_nodes( $menu_id, $nodes );
		$locations = get_nav_menu_locations();
		$locations['primary'] = $menu_id;
		set_theme_mod( 'nav_menu_locations', $locations );
	}

	/**
	 * Audits production-readiness requirements and reports their status.
	 *
	 * @param array $args Positional command arguments.
	 * @param array $assoc_args Associative command arguments, including output format and pending-change handling.
	 */
	public function audit( array $args, array $assoc_args ): void {
		$rows = $this->audit_rows();
		WP_CLI\Utils\format_items( (string) ( $assoc_args['format'] ?? 'table' ), $rows, array( 'type', 'slug', 'id', 'status', 'menu_items', 'expected' ) );
		if ( ! $this->is_clean( $rows ) ) {
			if ( isset( $assoc_args['allow-pending'] ) ) {
				WP_CLI::warning( 'Production-readiness audit found pending changes, as permitted.' );
				return;
			}
			WP_CLI::error( 'Production-readiness audit found pending changes.' );
		}
		WP_CLI::success( 'Production-readiness audit passed.' );
	}

	/**
	 * Applies the production-readiness migration and verifies the resulting site state.
	 *
	 * @param array $args       Positional command arguments.
	 * @param array $assoc_args Associative command arguments, including invocation safety options.
	 */
	public function apply( array $args, array $assoc_args ): void {
		$this->validate_invocation( $assoc_args );
		$this->acquire_lock();
		$this->apply_approved_pages();
		$this->apply_governed_pages();
		$this->apply_primary_menu();
		flush_rewrite_rules( false );
		$rows = $this->audit_rows();
		WP_CLI\Utils\format_items( 'table', $rows, array( 'type', 'slug', 'id', 'status', 'menu_items', 'expected' ) );
		if ( ! $this->is_clean( $rows ) ) {
			WP_CLI::error( 'Migration completed but the post-apply audit still has pending changes.' );
		}
		delete_option( self::LOCK_OPTION );
		WP_CLI::success( 'Migration applied and post-apply audit passed.' );
	}
}

WP_CLI::add_command( 'nvx production-readiness', 'NVX_Production_Readiness_Command' );
