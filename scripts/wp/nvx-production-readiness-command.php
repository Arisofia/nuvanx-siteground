<?php
/**
 * WP-CLI command for the NUVANX production-readiness content migration.
 *
 * Usage:
 * wp --require=scripts/wp/nvx-production-readiness-command.php nvx production-readiness audit
 * wp --require=scripts/wp/nvx-production-readiness-command.php nvx production-readiness audit --allow-pending
 * wp --require=scripts/wp/nvx-production-readiness-command.php nvx production-readiness apply --confirm=retire-prototypes
 *
 * @package nuvanx-siteground
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

/**
 * Audits and applies the idempotent production-readiness content migration.
 */
final class NVX_Production_Readiness_Command {
	private const CONFIRMATION_TOKEN = 'retire-prototypes';
	private const LOCK_OPTION        = '_nvx_production_readiness_migration_lock';
	private const LOCK_TTL_SECONDS   = 900;

	/**
	 * Approved pages that must exist after the migration.
	 *
	 * @return array<string,array{title:string,content:string}>
	 */
	private function approved_pages(): array {
		return array(
			'por-que-nuvanx' => array(
				'title'   => 'Por qué NUVANX',
				'content' => '<!-- NUVANX_STRATEGY_PAGE:why_nuvanx -->',
			),
			'inversion-medicina-estetica' => array(
				'title'   => 'Inversión en medicina estética',
				'content' => '<!-- NUVANX_STRATEGY_PAGE:investment -->',
			),
			'protocolos-signature' => array(
				'title'   => 'Protocolos Signature',
				'content' => '<!-- NUVANX_PROTOCOL_HUB -->',
			),
			'remodelacion-corporal-laser-madrid' => array(
				'title'   => 'Remodelación corporal láser diseñada según tu anatomía.',
				'content' => '<!-- NUVANX_PROTOCOL_PAGE:couture-sculpt -->',
			),
		);
	}

	/**
	 * Retired or unpublished pages and their desired final status.
	 *
	 * @return array<string,array{status:string,target:string}>
	 */
	private function governed_pages(): array {
		return array(
			'liposculpt-air' => array(
				'status' => 'trash',
				'target' => '/remodelacion-corporal-laser-madrid/',
			),
			'v-lift-awake' => array(
				'status' => 'trash',
				'target' => '/papada-definicion-mandibular-madrid/',
			),
			'tratamiento-postparto-abdomen-contorno-corporal-madrid' => array(
				'status' => 'draft',
				'target' => '/protocolos-signature/',
			),
		);
	}

	/**
	 * Finds a page by its path slug regardless of publication status.
	 *
	 * @param string $slug The page path slug.
	 * @return WP_Post|null The matching page, or null if no page is found.
	 */
	private function page_by_slug( string $slug ): ?WP_Post {
		$page = get_page_by_path( $slug, OBJECT, 'page' );
		return $page instanceof WP_Post ? $page : null;
	}

	/**
	 * Returns menu item IDs that reference a page.
	 *
	 * @return int[]
	 */
	private function menu_item_ids( int $page_id ): array {
		$ids   = array();
		$menus = wp_get_nav_menus();
		foreach ( $menus as $menu ) {
			$items = wp_get_nav_menu_items( $menu->term_id );
			if ( ! is_array( $items ) ) {
				continue;
			}
			foreach ( $items as $item ) {
				if ( 'page' === $item->object && $page_id === (int) $item->object_id ) {
					$ids[] = (int) $item->ID;
				}
			}
		}
		return array_values( array_unique( $ids ) );
	}

	/**
	 * Builds the current audit rows.
	 *
	 * @return array<int,array<string,string|int>>
	 */
	private function audit_rows(): array {
		$rows = array();
		foreach ( $this->approved_pages() as $slug => $definition ) {
			$page   = $this->page_by_slug( $slug );
			$rows[] = array(
				'type'       => 'approved',
				'slug'       => $slug,
				'id'         => $page ? (int) $page->ID : 0,
				'status'     => $page ? (string) $page->post_status : 'missing',
				'menu_items' => $page ? count( $this->menu_item_ids( (int) $page->ID ) ) : 0,
				'expected'   => 'publish',
			);
		}

		foreach ( $this->governed_pages() as $slug => $definition ) {
			$page   = $this->page_by_slug( $slug );
			$rows[] = array(
				'type'       => 'governed',
				'slug'       => $slug,
				'id'         => $page ? (int) $page->ID : 0,
				'status'     => $page ? (string) $page->post_status : 'absent',
				'menu_items' => $page ? count( $this->menu_item_ids( (int) $page->ID ) ) : 0,
				'expected'   => $definition['status'],
			);
		}

		return $rows;
	}

	/**
	 * Checks whether audit rows meet the migration requirements.
	 *
	 * @param array<int,array<string,string|int>> $rows Audit rows to evaluate.
	 * @return bool `true` if all rows satisfy the migration contract, `false` otherwise.
	 */
	private function is_clean( array $rows ): bool {
		foreach ( $rows as $row ) {
			if ( 'approved' === $row['type'] && 'publish' !== $row['status'] ) {
				return false;
			}
			if ( 'governed' === $row['type'] && ! in_array( $row['status'], array( 'absent', $row['expected'] ), true ) ) {
				return false;
			}
			if ( 'governed' === $row['type'] && 0 !== (int) $row['menu_items'] ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Acquires the migration lock and registers its cleanup on shutdown.
	 */
	private function acquire_lock(): void {
		$now      = time();
		$existing = (int) get_option( self::LOCK_OPTION, 0 );
		if ( $existing > 0 && ( $now - $existing ) > self::LOCK_TTL_SECONDS ) {
			delete_option( self::LOCK_OPTION );
		}

		if ( ! add_option( self::LOCK_OPTION, (string) $now, '', false ) ) {
			WP_CLI::error( 'Another production-readiness migration is already running.' );
		}

		register_shutdown_function(
			static function (): void {
				delete_option( self::LOCK_OPTION );
			}
		);
	}

	/**
	 * Releases the migration lock.
	 */
	private function release_lock(): void {
		delete_option( self::LOCK_OPTION );
	}

	/**
	 * Audits approved and governed pages against the production-readiness requirements.
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : Output format, either table or json. Default: table.
	 *
	 * [--allow-pending]
	 * : Report pending changes without returning a failing exit code.
	 *   Intended for pre-apply audits in the protected staging2 workflow.
	 */
	public function audit( array $args, array $assoc_args ): void {
		$rows   = $this->audit_rows();
		$format = isset( $assoc_args['format'] ) ? (string) $assoc_args['format'] : 'table';
		WP_CLI\Utils\format_items( $format, $rows, array( 'type', 'slug', 'id', 'status', 'menu_items', 'expected' ) );

		if ( ! $this->is_clean( $rows ) ) {
			if ( isset( $assoc_args['allow-pending'] ) ) {
				WP_CLI::warning( 'Production-readiness audit found pending changes, as permitted for pre-apply inspection.' );
				return;
			}
			WP_CLI::error( 'Production-readiness audit found pending changes.' );
		}
		WP_CLI::success( 'Production-readiness audit passed.' );
	}

	/**
	 * Applies the approved-page migration, retires governed pages, and verifies the resulting site state.
	 *
	 * ## OPTIONS
	 *
	 * --confirm=<token>
	 * : Must be exactly "retire-prototypes".
	 *
	 * [--allow-production]
	 * : Required when the WordPress host is nuvanx.com or www.nuvanx.com.
	 */
	public function apply( array $args, array $assoc_args ): void {
		$confirmation = isset( $assoc_args['confirm'] ) ? (string) $assoc_args['confirm'] : '';
		if ( self::CONFIRMATION_TOKEN !== $confirmation ) {
			WP_CLI::error( 'Refusing to apply: use --confirm=' . self::CONFIRMATION_TOKEN );
		}

		$host = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
		if ( ! in_array( $host, array( 'staging2.nuvanx.com', 'nuvanx.com', 'www.nuvanx.com' ), true ) ) {
			WP_CLI::error( 'Refusing to apply on unexpected host: ' . $host );
		}
		if ( in_array( $host, array( 'nuvanx.com', 'www.nuvanx.com' ), true ) && ! isset( $assoc_args['allow-production'] ) ) {
			WP_CLI::error( 'Production requires the explicit --allow-production flag.' );
		}

		$this->acquire_lock();

		foreach ( $this->approved_pages() as $slug => $definition ) {
			$page = $this->page_by_slug( $slug );
			if ( $page ) {
				if ( 'publish' !== $page->post_status ) {
					WP_CLI::warning( sprintf( 'Approved page %s exists with status %s; preserving it for manual review.', $slug, $page->post_status ) );
				}
				continue;
			}

			$page_id = wp_insert_post(
				array(
					'post_type'    => 'page',
					'post_status'  => 'publish',
					'post_title'   => $definition['title'],
					'post_name'    => $slug,
					'post_content' => $definition['content'],
				),
				true
			);
			if ( is_wp_error( $page_id ) ) {
				WP_CLI::error( sprintf( 'Unable to create %s: %s', $slug, $page_id->get_error_message() ) );
			}
			WP_CLI::log( sprintf( 'Created approved page %s as ID %d.', $slug, (int) $page_id ) );
		}

		foreach ( $this->governed_pages() as $slug => $definition ) {
			$page = $this->page_by_slug( $slug );
			if ( ! $page ) {
				continue;
			}

			foreach ( $this->menu_item_ids( (int) $page->ID ) as $menu_item_id ) {
				wp_delete_post( $menu_item_id, true );
				WP_CLI::log( sprintf( 'Deleted menu item %d referencing %s.', $menu_item_id, $slug ) );
			}

			delete_post_meta( (int) $page->ID, '_nvx_strategy_review_status' );
			if ( $definition['status'] !== $page->post_status ) {
				$result = wp_update_post(
					array(
						'ID'          => (int) $page->ID,
						'post_status' => $definition['status'],
					),
					true
				);
				if ( is_wp_error( $result ) ) {
					WP_CLI::error( sprintf( 'Unable to update %s: %s', $slug, $result->get_error_message() ) );
				}
				WP_CLI::log( sprintf( 'Updated %s to %s.', $slug, $definition['status'] ) );
			}
		}

		flush_rewrite_rules( false );
		$rows = $this->audit_rows();
		WP_CLI\Utils\format_items( 'table', $rows, array( 'type', 'slug', 'id', 'status', 'menu_items', 'expected' ) );
		if ( ! $this->is_clean( $rows ) ) {
			WP_CLI::error( 'Migration completed but the post-apply audit still has pending changes.' );
		}

		$this->release_lock();
		WP_CLI::success( 'Migration applied and post-apply audit passed.' );
	}
}

WP_CLI::add_command( 'nvx production-readiness', 'NVX_Production_Readiness_Command' );
