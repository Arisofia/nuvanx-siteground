<?php
/**
 * WP-CLI command for the NUVANX production-readiness content migration.
 *
 * @package nuvanx-siteground
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    return;
}

final class NvxProductionReadinessHelper {
    public static function approvedPages(): array {
        return array(
            'por-que-nuvanx' => array( 'title' => 'Por qué NUVANX', 'content' => '<!-- NUVANX_STRATEGY_PAGE:why_nuvanx -->', 'promote_draft' => false ),
            'inversion-medicina-estetica' => array( 'title' => 'Inversión en medicina estética', 'content' => '<!-- NUVANX_STRATEGY_PAGE:investment -->', 'promote_draft' => false ),
            'soluciones-medicas' => array( 'title' => 'Soluciones médicas', 'content' => '<!-- NUVANX_STRATEGY_PAGE:solutions -->', 'promote_draft' => true ),
            'protocolos-signature' => array( 'title' => 'Protocolos Signature', 'content' => '<!-- NUVANX_PROTOCOL_HUB -->', 'promote_draft' => true ),
            'remodelacion-corporal-laser-madrid' => array( 'title' => 'Remodelación corporal láser diseñada según tu anatomía.', 'content' => '<!-- NUVANX_PROTOCOL_PAGE:contour-architecture -->', 'promote_draft' => true ),
            'tratamiento-postparto-abdomen-contorno-corporal-madrid' => array( 'title' => 'Tratamiento Postparto: Abdomen y Contorno Corporal en Madrid', 'content' => '<!-- NUVANX_PROTOCOL_PAGE:post-maternity -->', 'promote_draft' => true ),
            'papada-definicion-mandibular-madrid' => array( 'title' => 'Papada y definición mandibular en Madrid', 'content' => '<!-- NUVANX_SIGNATURE_PHASE:profile-definition -->', 'promote_draft' => true ),
            'calidad-piel-firmeza-luminosidad-madrid' => array( 'title' => 'Calidad, firmeza y luminosidad de la piel en Madrid', 'content' => '<!-- NUVANX_SIGNATURE_PHASE:skin-architecture -->', 'promote_draft' => true ),
            'cicatrices-acne-poros-textura-madrid' => array( 'title' => 'Cicatrices de acné, poros y textura en Madrid', 'content' => '<!-- NUVANX_SIGNATURE_PHASE:surface-renewal -->', 'promote_draft' => true ),
            'manchas-rojeces-fotorejuvenecimiento-ipl-madrid' => array( 'title' => 'Manchas, rojeces y fotodaño en Madrid', 'content' => '<!-- NUVANX_SIGNATURE_PHASE:tone-correction -->', 'promote_draft' => true ),
            'grasa-localizada-abdomen-flancos-madrid' => array( 'title' => 'Grasa localizada en abdomen y flancos en Madrid', 'content' => '<!-- NUVANX_SIGNATURE_PHASE:abdomen-flancos -->', 'promote_draft' => true ),
            'flacidez-grasa-localizada-brazos-madrid' => array( 'title' => 'Flacidez y grasa localizada en brazos en Madrid', 'content' => '<!-- NUVANX_SIGNATURE_PHASE:brazos -->', 'promote_draft' => true ),
            'grasa-espalda-zona-sujetador-madrid' => array( 'title' => 'Grasa de espalda y zona del sujetador en Madrid', 'content' => '<!-- NUVANX_SIGNATURE_PHASE:espalda -->', 'promote_draft' => true ),
            'flacidez-muslos-internos-subgluteo-madrid' => array( 'title' => 'Flacidez en muslos internos y región subglútea en Madrid', 'content' => '<!-- NUVANX_SIGNATURE_PHASE:muslos -->', 'promote_draft' => true ),
            'tratamiento-rodillas-grasa-flacidez-madrid' => array( 'title' => 'Grasa localizada y flacidez en rodillas en Madrid', 'content' => '<!-- NUVANX_SIGNATURE_PHASE:rodillas -->', 'promote_draft' => true ),
            'contorno-corporal-masculino-madrid' => array( 'title' => 'Contorno corporal masculino en Madrid', 'content' => '<!-- NUVANX_SIGNATURE_PHASE:male-contour -->', 'promote_draft' => true ),
        );
    }

    public static function governedPages(): array {
        if ( ! function_exists( 'nvx_production_readiness_governed_pages' ) ) {
            WP_CLI::error( 'Production-readiness governed-page contract is unavailable.' );
        }
        return nvx_production_readiness_governed_pages();
    }

    public static function pageBySlug( string $slug ): ?WP_Post {
        $page = get_page_by_path( $slug, OBJECT, 'page' );
        return $page instanceof WP_Post ? $page : null;
    }

    public static function menuItemIds( int $page_id ): array {
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

    public static function primaryMenuId(): int {
        $locations = get_nav_menu_locations();
        return isset( $locations['primary'] ) ? (int) $locations['primary'] : 0;
    }

    public static function flattenBlueprint( array $items, int $depth = 0 ): array {
        $rows = array();
        foreach ( $items as $item ) {
            $rows[] = $depth . '|' . trim( (string) $item['label'] ) . '|' . untrailingslashit( (string) $item['url'] );
            $children = isset( $item['children'] ) && is_array( $item['children'] ) ? $item['children'] : array();
            $rows = array_merge( $rows, self::flattenBlueprint( $children, $depth + 1 ) );
        }
        return $rows;
    }

    public static function flattenMenuItems( array $items, int $parent = 0, int $depth = 0 ): array {
        $rows = array();
        foreach ( $items as $item ) {
            if ( (int) $item->menu_item_parent !== $parent ) {
                continue;
            }
            $rows[] = $depth . '|' . trim( (string) $item->title ) . '|' . untrailingslashit( (string) $item->url );
            $rows = array_merge( $rows, self::flattenMenuItems( $items, (int) $item->ID, $depth + 1 ) );
        }
        return $rows;
    }

    public static function canonicalMenuSignature(): array {
        if ( ! function_exists( 'nvx_navigation_resolved_fallback' ) ) {
            return array();
        }
        return self::flattenBlueprint( nvx_navigation_resolved_fallback() );
    }

    public static function currentMenuSignature(): array {
        $menu_id = self::primaryMenuId();
        if ( $menu_id < 1 ) {
            return array();
        }
        $items = wp_get_nav_menu_items( $menu_id, array( 'post_status' => 'publish' ) );
        return is_array( $items ) ? self::flattenMenuItems( $items ) : array();
    }
}

/** Audits and applies the idempotent production-readiness migration. */
final class NvxProductionReadinessCommand {
    private const CONFIRMATION_TOKEN = 'retire-prototypes';
    private const LOCK_OPTION        = '_nvx_production_readiness_migration_lock';
    private const LOCK_TTL_SECONDS   = 900;

    private function approvedAuditRows(): array {
        $rows = array();
        foreach ( NvxProductionReadinessHelper::approvedPages() as $slug => $definition ) {
            $page   = NvxProductionReadinessHelper::pageBySlug( $slug );
            $rows[] = array( 'type' => 'approved', 'slug' => $slug, 'id' => $page ? (int) $page->ID : 0, 'status' => $page ? (string) $page->post_status : 'missing', 'menu_items' => $page ? count( NvxProductionReadinessHelper::menuItemIds( (int) $page->ID ) ) : 0, 'expected' => 'publish' );
        }
        return $rows;
    }

    private function governedAuditRows(): array {
        $rows = array();
        foreach ( NvxProductionReadinessHelper::governedPages() as $slug => $definition ) {
            $page   = NvxProductionReadinessHelper::pageBySlug( $slug );
            $rows[] = array( 'type' => 'governed', 'slug' => $slug, 'id' => $page ? (int) $page->ID : 0, 'status' => $page ? (string) $page->post_status : 'absent', 'menu_items' => $page ? count( NvxProductionReadinessHelper::menuItemIds( (int) $page->ID ) ) : 0, 'expected' => $definition['status'] );
        }
        return $rows;
    }

    private function navigationAuditRow(): array {
        $current_menu = NvxProductionReadinessHelper::currentMenuSignature();
        $canonical_menu = NvxProductionReadinessHelper::canonicalMenuSignature();
        return array( 'type' => 'navigation', 'slug' => 'primary', 'id' => NvxProductionReadinessHelper::primaryMenuId(), 'status' => $current_menu === $canonical_menu && array() !== $canonical_menu ? 'clean' : 'drift', 'menu_items' => count( $current_menu ), 'expected' => 'canonical' );
    }

    /**
     * Builds audit rows for approved pages, governed pages, and primary navigation.
     *
     * @return array Audit rows describing current states and expected migration states.
     */
    private function auditRows(): array {
        return array_merge( $this->approvedAuditRows(), $this->governedAuditRows(), array( $this->navigationAuditRow() ) );
    }

    /**
     * Determines whether audit rows satisfy the production-readiness migration contract.
     *
     * @param array $rows Audit rows to validate.
     * @return bool True if all audit rows satisfy the contract, false otherwise.
     */
    private function isClean( array $rows ): bool {
        foreach ( $rows as $row ) {
            if ( ! $this->isAuditRowClean( $row ) ) {
                return false;
            }
        }
        return true;
    }

    /** Determines whether one audit row satisfies its type-specific contract. */
    private function isAuditRowClean( array $row ): bool {
        if ( 'approved' === $row['type'] ) {
            return 'publish' === $row['status'];
        }
        if ( 'governed' === $row['type'] ) {
            $status_is_valid = in_array( $row['status'], array( 'absent', $row['expected'] ), true );
            return $status_is_valid && 0 === (int) $row['menu_items'];
        }
        return 'navigation' !== $row['type'] || 'clean' === $row['status'];
    }

    /**
     * Acquires a short-lived lock to prevent concurrent production-readiness migrations.
     */
    private function acquireLock(): void {
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

    /** Release the migration lock. */
    private function releaseLock(): void {
        delete_option( self::LOCK_OPTION );
    }

    /**
     * Validates confirmation, host, production authorization, and trash settings before applying the migration.
     *
     * @param array $assoc_args Command associative arguments.
     */
    private function validateInvocation( array $assoc_args ): void {
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

    private function updateApprovedPage( int $page_id, array $definition, string $slug ): void {
        $result = wp_update_post( array( 'ID' => $page_id, 'post_title' => $definition['title'], 'post_content' => $definition['content'] ), true );
        if ( is_wp_error( $result ) ) {
            WP_CLI::error( sprintf( 'Unable to refresh approved page %s: %s', $slug, $result->get_error_message() ) );
        }
    }

    private function promoteApprovedPage( int $page_id, array $definition, string $slug ): void {
        if ( empty( $definition['promote_draft'] ) ) {
            WP_CLI::warning( sprintf( 'Approved page %s exists; preserving it for manual review.', $slug ) );
            return;
        }
        $result = wp_update_post( array( 'ID' => $page_id, 'post_status' => 'publish', 'post_title' => $definition['title'], 'post_content' => $definition['content'] ), true );
        if ( is_wp_error( $result ) ) {
            WP_CLI::error( sprintf( 'Unable to publish approved page %s: %s', $slug, $result->get_error_message() ) );
        }
        WP_CLI::log( sprintf( 'Published approved page %s as ID %d.', $slug, $page_id ) );
    }

    /**
     * Ensures approved pages have the required published content and status.
     */
    private function applyApprovedPages(): void {
        foreach ( NvxProductionReadinessHelper::approvedPages() as $slug => $definition ) {
            $page = NvxProductionReadinessHelper::pageBySlug( $slug );
            if ( ! $page ) {
                $this->createApprovedPage( $slug, $definition );
                continue;
            }
            if ( 'publish' === $page->post_status ) {
                $this->updateApprovedPage( (int) $page->ID, $definition, $slug );
            } else {
                $this->promoteApprovedPage( (int) $page->ID, $definition, $slug );
            }
        }
    }
    private function createApprovedPage( string $slug, array $definition ): void {
        $page_id = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => $definition['title'], 'post_name' => $slug, 'post_content' => $definition['content'] ), true );
        if ( is_wp_error( $page_id ) ) {
            WP_CLI::error( sprintf( 'Unable to create %s: %s', $slug, $page_id->get_error_message() ) );
        }
        WP_CLI::log( sprintf( 'Created approved page %s as ID %d.', $slug, (int) $page_id ) );
    }

    /**
     * Removes governed pages from navigation and applies their contractually defined post statuses.
     */
    private function applyGovernedPages(): void {
        foreach ( NvxProductionReadinessHelper::governedPages() as $slug => $definition ) {
            $page = NvxProductionReadinessHelper::pageBySlug( $slug );
            if ( ! $page ) {
                continue;
            }
            $this->removePageMenuItems( $page, $slug );
            delete_post_meta( (int) $page->ID, '_nvx_strategy_review_status' );
            if ( $definition['status'] === $page->post_status ) {
                continue;
            }
            $this->updateGovernedPageStatus( $page, $slug, $definition['status'] );
            WP_CLI::log( sprintf( 'Updated %s to %s.', $slug, $definition['status'] ) );
        }
    }

    /** Removes every navigation item that references a governed page. */
    private function removePageMenuItems( WP_Post $page, string $slug ): void {
        foreach ( NvxProductionReadinessHelper::menuItemIds( (int) $page->ID ) as $menu_item_id ) {
            wp_delete_post( $menu_item_id, true );
            WP_CLI::log( sprintf( 'Deleted menu item %d referencing %s.', $menu_item_id, $slug ) );
        }
    }

    /** Applies and validates the governed status for a page. */
    private function updateGovernedPageStatus( WP_Post $page, string $slug, string $status ): void {
        if ( 'trash' === $status ) {
            $result = wp_trash_post( (int) $page->ID );
            if ( ! $result instanceof WP_Post ) {
                WP_CLI::error( sprintf( 'Unable to update %s to trash.', $slug ) );
            }
            return;
        }
        $result = wp_update_post( array( 'ID' => (int) $page->ID, 'post_status' => $status ), true );
        if ( is_wp_error( $result ) ) {
            WP_CLI::error( sprintf( 'Unable to update %s: %s', $slug, $result->get_error_message() ) );
        }
    }

    /**
     * Inserts canonical navigation nodes and their children into a menu.
     *
     * @param int   $menu_id   The menu ID receiving the nodes.
     * @param array $nodes     The canonical menu nodes to insert.
     * @param int   $parent_id The parent menu item ID.
     */
    private function insertMenuNodes( int $menu_id, array $nodes, int $parent_id = 0 ): void {
        foreach ( $nodes as $node ) {
            $classes = ! empty( $node['mega'] ) ? 'nvx-menu--mega' : '';
            $item_id = wp_update_nav_menu_item(
                $menu_id,
                0,
                array(
                    'menu-item-title' => (string) $node['label'],
                    'menu-item-url' => (string) $node['url'],
                    'menu-item-status' => 'publish',
                    'menu-item-parent-id' => $parent_id,
                    'menu-item-classes' => $classes,
                )
            );
            if ( is_wp_error( $item_id ) ) {
                WP_CLI::error( sprintf( 'Unable to create primary menu item %s: %s', (string) $node['label'], $item_id->get_error_message() ) );
            }
            $children = isset( $node['children'] ) && is_array( $node['children'] ) ? $node['children'] : array();
            $this->insertMenuNodes( $menu_id, $children, (int) $item_id );
        }
    }

    /** Replaces the assigned primary menu with the canonical published navigation blueprint. */
    private function applyPrimaryMenu(): void {
        if ( ! function_exists( 'nvx_navigation_resolved_fallback' ) ) {
            WP_CLI::error( 'Canonical navigation blueprint is unavailable.' );
        }
        $nodes = nvx_navigation_resolved_fallback();
        if ( array() === $nodes ) {
            WP_CLI::error( 'Canonical navigation resolved to an empty tree.' );
        }
        $menu_id = NvxProductionReadinessHelper::primaryMenuId();
        if ( $menu_id < 1 ) {
            $created = wp_create_nav_menu( 'NUVANX Principal' );
            if ( is_wp_error( $created ) ) {
                WP_CLI::error( 'Unable to create NUVANX Principal menu: ' . $created->get_error_message() );
            }
            $menu_id = (int) $created;
        }
        $items = wp_get_nav_menu_items( $menu_id, array( 'post_status' => 'any' ) );
        if ( is_array( $items ) ) {
            foreach ( $items as $item ) {
                wp_delete_post( (int) $item->ID, true );
            }
        }
        $this->insertMenuNodes( $menu_id, $nodes );
        $locations = get_nav_menu_locations();
        $locations['primary'] = $menu_id;
        set_theme_mod( 'nav_menu_locations', $locations );
        WP_CLI::log( sprintf( 'Rebuilt canonical primary menu %d with %d items.', $menu_id, count( NvxProductionReadinessHelper::canonicalMenuSignature() ) ) );
    }

    /**
     * Audits production-readiness state and reports whether the migration contract is satisfied.
     *
     * The variadic signature preserves WP-CLI dispatch while avoiding an unused positional parameter.
     */
    public function audit( ...$arguments ): void {
        $assoc_args = isset( $arguments[1] ) && is_array( $arguments[1] ) ? $arguments[1] : array();
        $rows = $this->auditRows();
        $format = isset( $assoc_args['format'] ) ? (string) $assoc_args['format'] : 'table';
        WP_CLI\Utils\format_items( $format, $rows, array( 'type', 'slug', 'id', 'status', 'menu_items', 'expected' ) );
        if ( ! $this->isClean( $rows ) ) {
            if ( isset( $assoc_args['allow-pending'] ) ) {
                WP_CLI::warning( 'Production-readiness audit found pending changes, as permitted for pre-apply inspection.' );
                return;
            }
            WP_CLI::error( 'Production-readiness audit found pending changes.' );
        }
        WP_CLI::success( 'Production-readiness audit passed.' );
    }

    /**
     * Applies the production-readiness migration and verifies its final state.
     *
     * The variadic signature preserves WP-CLI's positional and associative argument
     * dispatch without declaring an unused positional-arguments parameter.
     */
    public function apply( ...$arguments ): void {
        $assoc_args = isset( $arguments[1] ) && is_array( $arguments[1] ) ? $arguments[1] : array();
        $this->validateInvocation( $assoc_args );
        $this->acquireLock();
        $this->applyApprovedPages();
        $this->applyGovernedPages();
        $this->applyPrimaryMenu();
        flush_rewrite_rules( false );
        $rows = $this->auditRows();
        WP_CLI\Utils\format_items( 'table', $rows, array( 'type', 'slug', 'id', 'status', 'menu_items', 'expected' ) );
        if ( ! $this->isClean( $rows ) ) {
            WP_CLI::error( 'Migration completed but the post-apply audit still has pending changes.' );
        }
        $this->releaseLock();
        WP_CLI::success( 'Migration applied and post-apply audit passed.' );
    }
}

WP_CLI::add_command( 'nvx production-readiness', 'NvxProductionReadinessCommand' );
