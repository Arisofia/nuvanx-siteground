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
	private const PRIMARY_MENU_NAME  = 'NUVANX Principal';

	/** Approved pages that must exist after the migration. */
	private function approved_pages(): array {
		return array(
			'por-que-nuvanx' => array( 'title' => 'Por qué NUVANX', 'content' => '<!-- NUVANX_STRATEGY_PAGE:why_nuvanx -->' ),
			'inversion-medicina-estetica' => array( 'title' => 'Inversión en medicina estética', 'content' => '<!-- NUVANX_STRATEGY_PAGE:investment -->' ),
			'soluciones-medicas' => array( 'title' => 'Soluciones médicas', 'content' => '<!-- NUVANX_STRATEGY_PAGE:solutions -->' ),
			'protocolos-signature' => array( 'title' => 'Protocolos Signature', 'content' => '<!-- NUVANX_PROTOCOL_HUB -->' ),
			'remodelacion-corporal-laser-madrid' => array( 'title' => 'Remodelación corporal láser diseñada según tu anatomía', 'content' => '<!-- NUVANX_PROTOCOL_PAGE:contour-architecture -->' ),
			'tratamiento-postparto-abdomen-contorno-corporal-madrid' => array( 'title' => 'Tratamiento postparto: abdomen y contorno corporal', 'content' => '<!-- NUVANX_PROTOCOL_PAGE:post-maternity -->' ),
			'papada-definicion-mandibular-madrid' => array( 'title' => 'Papada y definición mandibular', 'content' => '<!-- NUVANX_PROTOCOL_PAGE:profile-definition -->' ),
			'calidad-piel-firmeza-luminosidad-madrid' => array( 'title' => 'Calidad, firmeza y luminosidad de la piel', 'content' => '<!-- NUVANX_PROTOCOL_PAGE:skin-architecture -->' ),
			'cicatrices-acne-poros-textura-madrid' => array( 'title' => 'Cicatrices de acné, poros y textura', 'content' => '<!-- NUVANX_PROTOCOL_PAGE:surface-renewal -->' ),
			'manchas-rojeces-fotorejuvenecimiento-ipl-madrid' => array( 'title' => 'Manchas, rojeces y fotodaño', 'content' => '<!-- NUVANX_PROTOCOL_PAGE:tone-correction -->' ),
			'grasa-localizada-abdomen-flancos-madrid' => array( 'title' => 'Grasa localizada en abdomen y flancos', 'content' => '<!-- NUVANX_ANATOMICAL_PAGE:abdomen-flancos -->' ),
			'flacidez-grasa-localizada-brazos-madrid' => array( 'title' => 'Flacidez y grasa localizada en brazos', 'content' => '<!-- NUVANX_ANATOMICAL_PAGE:brazos -->' ),
			'grasa-espalda-zona-sujetador-madrid' => array( 'title' => 'Grasa en espalda y zona del sujetador', 'content' => '<!-- NUVANX_ANATOMICAL_PAGE:espalda-sujetador -->' ),
			'flacidez-muslos-internos-subgluteo-madrid' => array( 'title' => 'Flacidez en muslos internos y región subglútea', 'content' => '<!-- NUVANX_ANATOMICAL_PAGE:muslos-subgluteo -->' ),
			'tratamiento-rodillas-grasa-flacidez-madrid' => array( 'title' => 'Grasa y flacidez en rodillas', 'content' => '<!-- NUVANX_ANATOMICAL_PAGE:rodillas -->' ),
			'contorno-corporal-masculino-madrid' => array( 'title' => 'Contorno corporal masculino', 'content' => '<!-- NUVANX_ANATOMICAL_PAGE:contorno-masculino -->' ),
		);
	}

	/** Shared retired-page contract from the active theme. */
	private function governed_pages(): array {
		if ( ! function_exists( 'nvx_production_readiness_governed_pages' ) ) {
			WP_CLI::error( 'Production-readiness governed-page contract is unavailable.' );
		}
		return nvx_production_readiness_governed_pages();
	}

	/** Find one page by path regardless of publication status. */
	private function page_by_slug( string $slug ): ?WP_Post {
		$page = get_page_by_path( trim( $slug, '/' ), OBJECT, 'page' );
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
		$menu      = wp_get_nav_menu_object( self::PRIMARY_MENU_NAME );
		$locations = get_nav_menu_locations();
		$assigned  = $menu && isset( $locations['primary'] ) && (int) $locations['primary'] === (int) $menu->term_id;
		$rows[]    = array(
			'type'       => 'navigation',
			'slug'       => 'primary-menu',
			'id'         => $menu ? (int) $menu->term_id : 0,
			'status'     => $assigned ? 'assigned' : 'pending',
			'menu_items' => $menu ? count( (array) wp_get_nav_menu_items( $menu->term_id ) ) : 0,
			'expected'   => 'assigned',
		);
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
			if ( 'navigation' === $row['type'] && ( 'assigned' !== $row['status'] || (int) $row['menu_items'] < 8 ) ) {
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
		register_shutdown_function( static function (): void { delete_option( self::LOCK_OPTION ); } );
	}

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
			WP_CLI::error( 'Refusing to apply: WordPress trash is disabled.' );
		}
	}

	/** Create absent approved pages and promote approved drafts. */
	private function apply_approved_pages(): void {
		foreach ( $this->approved_pages() as $slug => $definition ) {
			$page = $this->page_by_slug( $slug );
			if ( $page ) {
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
			}
			delete_post_meta( (int) $page->ID, '_nvx_strategy_review_status' );
			if ( $definition['status'] === $page->post_status ) {
				continue;
			}
			if ( 'trash' === $definition['status'] ) {
				$result = wp_trash_post( (int) $page->ID );
				if ( ! $result instanceof WP_Post ) {
					WP_CLI::error( sprintf( 'Unable to trash %s.', $slug ) );
				}
				continue;
			}
			$result = wp_update_post( array( 'ID' => (int) $page->ID, 'post_status' => $definition['status'] ), true );
			if ( is_wp_error( $result ) ) {
				WP_CLI::error( sprintf( 'Unable to update %s: %s', $slug, $result->get_error_message() ) );
			}
		}
	}

	/** Add a published page to a menu and return its menu-item ID. */
	private function add_page_item( int $menu_id, string $title, string $slug, int $parent = 0, array $classes = array() ): int {
		$page = $this->page_by_slug( $slug );
		if ( ! $page || 'publish' !== $page->post_status ) {
			WP_CLI::warning( sprintf( 'Menu item %s omitted because %s is not published.', $title, $slug ) );
			return 0;
		}
		$item_id = wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-title'     => $title,
				'menu-item-object-id' => (int) $page->ID,
				'menu-item-object'    => 'page',
				'menu-item-type'      => 'post_type',
				'menu-item-parent-id' => $parent,
				'menu-item-classes'   => implode( ' ', $classes ),
				'menu-item-status'    => 'publish',
			),
			true
		);
		if ( is_wp_error( $item_id ) ) {
			WP_CLI::error( sprintf( 'Unable to add menu item %s: %s', $title, $item_id->get_error_message() ) );
		}
		return (int) $item_id;
	}

	/** Add a custom menu link. */
	private function add_custom_item( int $menu_id, string $title, string $url, int $parent = 0, array $classes = array() ): int {
		$item_id = wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-title'     => $title,
				'menu-item-url'       => $url,
				'menu-item-type'      => 'custom',
				'menu-item-parent-id' => $parent,
				'menu-item-classes'   => implode( ' ', $classes ),
				'menu-item-status'    => 'publish',
			),
			true
		);
		if ( is_wp_error( $item_id ) ) {
			WP_CLI::error( sprintf( 'Unable to add custom menu item %s: %s', $title, $item_id->get_error_message() ) );
		}
		return (int) $item_id;
	}

	/** Build and assign the canonical primary menu while preserving the legacy menu as rollback. */
	private function synchronize_primary_menu(): void {
		$menu = wp_get_nav_menu_object( self::PRIMARY_MENU_NAME );
		if ( ! $menu ) {
			$menu_id = wp_create_nav_menu( self::PRIMARY_MENU_NAME );
			if ( is_wp_error( $menu_id ) ) {
				WP_CLI::error( 'Unable to create the canonical primary menu: ' . $menu_id->get_error_message() );
			}
		} else {
			$menu_id = (int) $menu->term_id;
			foreach ( (array) wp_get_nav_menu_items( $menu_id ) as $item ) {
				wp_delete_post( (int) $item->ID, true );
			}
		}

		$this->add_custom_item( $menu_id, 'Inicio', home_url( '/' ) );

		$solutions = $this->add_page_item( $menu_id, 'Soluciones', 'soluciones-medicas', 0, array( 'nvx-menu--mega' ) );
		$this->add_page_item( $menu_id, 'Rostro y cuello', 'papada-definicion-mandibular-madrid', $solutions );
		$this->add_page_item( $menu_id, 'Calidad de piel', 'calidad-piel-firmeza-luminosidad-madrid', $solutions );
		$this->add_page_item( $menu_id, 'Contorno corporal', 'remodelacion-corporal-laser-madrid', $solutions );
		$this->add_page_item( $menu_id, 'Cambios posgestacionales', 'tratamiento-postparto-abdomen-contorno-corporal-madrid', $solutions );
		$this->add_page_item( $menu_id, 'Cicatrices, poros y textura', 'cicatrices-acne-poros-textura-madrid', $solutions );
		$this->add_page_item( $menu_id, 'Manchas, rojeces y fotodaño', 'manchas-rojeces-fotorejuvenecimiento-ipl-madrid', $solutions );
		$this->add_page_item( $menu_id, 'Medicina estética masculina', 'contorno-corporal-masculino-madrid', $solutions );

		$protocols = $this->add_page_item( $menu_id, 'Protocolos Signature', 'protocolos-signature', 0, array( 'nvx-menu--mega' ) );
		$this->add_page_item( $menu_id, 'NUVANX Contour Architecture™', 'remodelacion-corporal-laser-madrid', $protocols );
		$this->add_page_item( $menu_id, 'NUVANX Post-Maternity Contour™', 'tratamiento-postparto-abdomen-contorno-corporal-madrid', $protocols );
		$this->add_page_item( $menu_id, 'NUVANX Profile Definition™', 'papada-definicion-mandibular-madrid', $protocols );
		$this->add_page_item( $menu_id, 'NUVANX Skin Architecture™', 'calidad-piel-firmeza-luminosidad-madrid', $protocols );
		$this->add_page_item( $menu_id, 'NUVANX Surface Renewal™', 'cicatrices-acne-poros-textura-madrid', $protocols );
		$this->add_page_item( $menu_id, 'NUVANX Tone Correction™', 'manchas-rojeces-fotorejuvenecimiento-ipl-madrid', $protocols );

		$technology = $this->add_page_item( $menu_id, 'Tecnología', 'medicina-estetica-laser', 0, array( 'nvx-menu--mega' ) );
		foreach ( array(
			'Endolift® facial'       => 'endolift-facial-papada-mandibula',
			'Endoláser corporal'     => 'endolaser-corporal-grasa-localizada',
			'EXION® Face'            => 'exion-face',
			'EXION® Body'            => 'exion-body',
			'EXION® Fractional RF'   => 'exion-fractional',
			'Láser CO₂ fraccionado'  => 'laser-co2-fraccionado-madrid-textura-cicatrices-poro',
			'BTL EXILITE™ IPL'       => 'btl-exilite-ipl-madrid',
			'EMFUSION®'              => 'emfusion',
			'Medicina inyectable'    => 'medicina-estetica',
		) as $title => $slug ) {
			$this->add_page_item( $menu_id, $title, $slug, $technology );
		}

		$this->add_page_item( $menu_id, 'Casos clínicos', 'casos-de-pacientes' );
		$this->add_page_item( $menu_id, 'Equipo médico', 'equipo-medico' );
		$clinics = $this->add_page_item( $menu_id, 'Clínicas', 'clinicas-de-medicina-estetica-nuvanx' );
		$this->add_page_item( $menu_id, 'Chamberí', 'medicina-estetica-chamberi', $clinics );
		$this->add_page_item( $menu_id, 'Salamanca–Goya', 'medicina-estetica-goya-barrio-salamanca', $clinics );
		$this->add_page_item( $menu_id, 'Journal', 'blog' );
		$this->add_page_item( $menu_id, 'Contacto', 'contacto', 0, array( 'nvx-menu-mobile-only' ) );

		$locations            = get_nav_menu_locations();
		$locations['primary'] = $menu_id;
		set_theme_mod( 'nav_menu_locations', $locations );
		WP_CLI::log( sprintf( 'Assigned menu %s (%d) to Primary.', self::PRIMARY_MENU_NAME, $menu_id ) );
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

	/** Apply approved publication, retired-page governance and canonical navigation. */
	public function apply( array $args, array $assoc_args ): void {
		$this->validate_invocation( $assoc_args );
		$this->acquire_lock();
		$this->apply_approved_pages();
		$this->apply_governed_pages();
		$this->synchronize_primary_menu();
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
