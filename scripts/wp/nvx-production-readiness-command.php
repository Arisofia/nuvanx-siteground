<?php
/**
 * WP-CLI command for the NUVANX production-readiness content migration.
 *
 * @package nuvanx-siteground
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

/** Audits and applies the idempotent production-readiness migration. */
final class NVX_Production_Readiness_Command {
	private const CONFIRMATION_TOKEN = 'retire-prototypes';
	private const LOCK_OPTION        = '_nvx_production_readiness_migration_lock';
	private const LOCK_TTL_SECONDS   = 900;

	/** Approved pages that must exist after the migration. */
	private function approved_pages(): array {
		return array(
			'por-que-nuvanx' => array(
				'title'         => 'Por qué NUVANX',
				'content'       => '<!-- NUVANX_STRATEGY_PAGE:why_nuvanx -->',
				'promote_draft' => false,
			),
			'inversion-medicina-estetica' => array(
				'title'         => 'Inversión en medicina estética',
				'content'       => '<!-- NUVANX_STRATEGY_PAGE:investment -->',
				'promote_draft' => false,
			),
			'soluciones-medicas' => array(
				'title'         => 'Soluciones médicas',
				'content'       => '<!-- NUVANX_STRATEGY_PAGE:solutions -->',
				'promote_draft' => true,
			),
			'protocolos-signature' => array(
				'title'         => 'Protocolos Signature',
				'content'       => '<!-- NUVANX_PROTOCOL_HUB -->',
				'promote_draft' => false,
			),
			'remodelacion-corporal-laser-madrid' => array(
				'title'         => 'Remodelación corporal láser diseñada según tu anatomía.',
				'content'       => '<!-- NUVANX_PROTOCOL_PAGE:couture-sculpt -->',
				'promote_draft' => false,
			),
			'tratamiento-postparto-abdomen-contorno-corporal-madrid' => array(
				'title'         => 'Tratamiento Postparto: Abdomen y Contorno Corporal en Madrid',
				'content'       => '<!-- NUVANX_PROTOCOL_PAGE:post-maternity -->',
				'promote_draft' => true,
			),
		);
	}

	/** Shared retired-page contract from the active theme. */
	private function governed_pages(): array {
		if ( ! function_exists( 'nvx_production_readiness_governed_pages' ) ) {
			WP_CLI::error( 'Production-readiness governed-page contract is unavailable.' );
		}
		return nvx_production_readiness_governed_pages();
	}

	/** Find one page by slug regardless of publication status. */
	private function page_by_slug( string $slug ): ?WP_Post {
		$page = get_page_by_path( $slug, OBJECT, 'page' );
		return $page instanceof WP_Post ? $page : null;
	}

	/** Return menu-item IDs that reference a page. */
	private function menu_item_ids( int $page_id ): array {
		$ids = array();
		foreach ( wp_get_nav_menus() as $menu ) {
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

	/** Build current audit rows. */
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

	/** Check audit rows against the migration contract. */
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

	/** Acquire a short-lived migration lock. */
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

	/** Release the migration lock. */
	private function release_lock(): void {
		delete_option( self::LOCK_OPTION );
	}

	/** Validate mutation confirmation and host restrictions. */
	private function validate_invocation( array $assoc_args ): void {
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
		if ( ! defined( 'EMPTY_TRASH_DAYS' ) || (int) EMPTY_TRASH_DAYS < 1 ) {
			WP_CLI::error( 'Refusing to apply: WordPress trash is disabled, which could permanently delete governed pages.' );
		}
	}

	/** Create absent approved pages and explicitly promote approved drafts. */
	private function apply_approved_pages(): void {
		foreach ( $this->approved_pages() as $slug => $definition ) {
			$page = $this->page_by_slug( $slug );
			if ( $page ) {
				if ( 'publish' === $page->post_status ) {
					continue;
				}
				if ( empty( $definition['promote_draft'] ) || ! in_array( $page->post_status, array( 'draft', 'pending', 'private' ), true ) ) {
					WP_CLI::warning( sprintf( 'Approved page %s exists with status %s; preserving it for manual review.', $slug, $page->post_status ) );
					continue;
				}

				$result = wp_update_post(
					array(
						'ID'           => (int) $page->ID,
						'post_status'  => 'publish',
						'post_title'   => $definition['title'],
						'post_content' => $definition['content'],
					),
					true
				);
				if ( is_wp_error( $result ) ) {
					WP_CLI::error( sprintf( 'Unable to publish approved page %s: %s', $slug, $result->get_error_message() ) );
				}
				WP_CLI::log( sprintf( 'Published approved page %s as ID %d.', $slug, (int) $page->ID ) );
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
	}

	/** Remove menu references and apply governed states to retired pages. */
	private function apply_governed_pages(): void {
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
			if ( $definition['status'] === $page->post_status ) {
				continue;
			}

			if ( 'trash' === $definition['status'] ) {
				$result = wp_trash_post( (int) $page->ID );
				if ( ! $result instanceof WP_Post ) {
					WP_CLI::error( sprintf( 'Unable to update %s to trash.', $slug ) );
				}
			} else {
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
			}
			WP_CLI::log( sprintf( 'Updated %s to %s.', $slug, $definition['status'] ) );
		}
	}

	/** Audit production-readiness state. */
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

	/** Apply approved publication and retired-page governance. */
	public function apply( array $args, array $assoc_args ): void {
		$this->validate_invocation( $assoc_args );
		$this->acquire_lock();
		$this->apply_approved_pages();
		$this->apply_governed_pages();

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
