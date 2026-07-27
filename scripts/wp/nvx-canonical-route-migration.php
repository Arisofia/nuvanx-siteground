<?php
/**
 * Legacy-route retirement for NUVANX.
 *
 * Duplicate and obsolete routes are removed from the public WordPress state.
 * The migration deliberately removes `_wp_old_slug` ownership so WordPress
 * does not redirect retired URLs to another page.
 *
 * @package nuvanx-siteground
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    return;
}

/** @return array<int,array{legacy:string,source_id:int,target:string,target_id:int}> */
function nvx_legacy_route_contract(): array {
    return array(
        array( 'legacy' => 'mas-informacion-sobre-las-cookies', 'source_id' => 18, 'target' => 'politica-de-cookies-ue', 'target_id' => 577 ),
        array( 'legacy' => 'politica-de-cookies', 'source_id' => 31, 'target' => 'politica-de-cookies-ue', 'target_id' => 577 ),
        array( 'legacy' => 'politica-de-privacidad', 'source_id' => 0, 'target' => 'politica-privacidad', 'target_id' => 0 ),
        array( 'legacy' => 'tratamiento-retirado', 'source_id' => 0, 'target' => 'soluciones-medicas', 'target_id' => 0 ),
        array( 'legacy' => 'tratamientos', 'source_id' => 0, 'target' => 'soluciones-medicas', 'target_id' => 0 ),
        array( 'legacy' => 'liposculpt-air', 'source_id' => 0, 'target' => 'remodelacion-corporal-laser-madrid', 'target_id' => 0 ),
        array( 'legacy' => 'v-lift-awake', 'source_id' => 0, 'target' => 'protocolos-signature', 'target_id' => 0 ),
        array( 'legacy' => 'dr-javier-rivera-tejeda', 'source_id' => 0, 'target' => 'equipo-medico', 'target_id' => 0 ),
        array( 'legacy' => 'eye-frame-rejuvenecimiento-mirada-madrid', 'source_id' => 0, 'target' => 'ojeras-surco-lagrimal-madrid', 'target_id' => 0 ),
        array( 'legacy' => 'eye-frame', 'source_id' => 0, 'target' => 'ojeras-surco-lagrimal-madrid', 'target_id' => 0 ),
    );
}

/** @param array{legacy:string,source_id:int,target:string,target_id:int} $route */
function nvx_legacy_target( array $route ): ?WP_Post {
    $post = $route['target_id'] > 0
        ? get_post( $route['target_id'] )
        : get_page_by_path( $route['target'], OBJECT, 'page' );
    return $post instanceof WP_Post ? $post : null;
}

/** @param array{legacy:string,source_id:int,target:string,target_id:int} $route */
function nvx_legacy_source( array $route ): ?WP_Post {
    $post = $route['source_id'] > 0
        ? get_post( $route['source_id'] )
        : get_page_by_path( $route['legacy'], OBJECT, 'page' );
    return $post instanceof WP_Post ? $post : null;
}

/** @return int[] */
function nvx_legacy_old_slug_owners( string $legacy ): array {
    global $wpdb;
    $ids = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_old_slug' AND meta_value = %s ORDER BY post_id",
            $legacy
        )
    );
    return array_values( array_unique( array_map( 'intval', is_array( $ids ) ? $ids : array() ) ) );
}

/** @return int[] */
function nvx_legacy_menu_items( string $legacy, int $source_id ): array {
    $ids = array();
    foreach ( wp_get_nav_menus() as $menu ) {
        $items = wp_get_nav_menu_items( $menu->term_id, array( 'post_status' => 'any' ) );
        if ( ! is_array( $items ) ) {
            continue;
        }
        foreach ( $items as $item ) {
            $path = trim( (string) wp_parse_url( (string) $item->url, PHP_URL_PATH ), '/' );
            $references_source = $source_id > 0 && 'page' === $item->object && $source_id === (int) $item->object_id;
            if ( $references_source || $legacy === $path ) {
                $ids[] = (int) $item->ID;
            }
        }
    }
    return array_values( array_unique( $ids ) );
}

/** @param array{legacy:string,source_id:int,target:string,target_id:int} $route */
function nvx_legacy_route_row( array $route ): array {
    $target = nvx_legacy_target( $route );
    $source = nvx_legacy_source( $route );
    $target_id = $target instanceof WP_Post ? (int) $target->ID : 0;
    $source_id = $source instanceof WP_Post ? (int) $source->ID : (int) $route['source_id'];
    $source_status = $source instanceof WP_Post ? (string) $source->post_status : 'absent';
    $owners = nvx_legacy_old_slug_owners( $route['legacy'] );
    $menu_items = nvx_legacy_menu_items( $route['legacy'], $source_id );
    $target_is_valid = (
        $target instanceof WP_Post
        && 'page' === $target->post_type
        && 'publish' === $target->post_status
        && $route['target'] === $target->post_name
        && ( 0 === $route['target_id'] || $route['target_id'] === $target_id )
    );
    $source_is_retired = ! in_array( $source_status, array( 'publish', 'private', 'future' ), true );
    $clean = $target_is_valid && $source_is_retired && array() === $owners && array() === $menu_items;

    return array(
        'legacy'       => $route['legacy'],
        'source_id'    => $source_id,
        'source_state' => $source_status,
        'target_id'    => $target_id,
        'target'       => $route['target'],
        'target_state' => $target instanceof WP_Post ? (string) $target->post_status : 'missing',
        'old_slug_ids' => implode( ',', $owners ),
        'menu_items'   => implode( ',', $menu_items ),
        'status'       => $clean ? 'clean' : 'drift',
    );
}

/** @return array<int,array<string,mixed>> */
function nvx_legacy_route_rows(): array {
    return array_map( 'nvx_legacy_route_row', nvx_legacy_route_contract() );
}

/** @param array<int,array<string,mixed>> $rows */
function nvx_legacy_routes_are_clean( array $rows ): bool {
    foreach ( $rows as $row ) {
        if ( 'clean' !== $row['status'] ) {
            return false;
        }
    }
    return true;
}

final class NvxLegacyRouteRetirementCommand {
    private const CONFIRMATION = 'retire-legacy-routes';

    /** @param string[] $args @param array<string,mixed> $assoc_args */
    public function audit( array $args, array $assoc_args ): void {
        unset( $args );
        $rows = nvx_legacy_route_rows();
        WP_CLI\Utils\format_items(
            isset( $assoc_args['format'] ) ? (string) $assoc_args['format'] : 'table',
            $rows,
            array( 'legacy', 'source_id', 'source_state', 'target_id', 'target', 'target_state', 'old_slug_ids', 'menu_items', 'status' )
        );
        if ( nvx_legacy_routes_are_clean( $rows ) ) {
            WP_CLI::success( 'Legacy route retirement audit passed.' );
            return;
        }
        if ( isset( $assoc_args['allow-pending'] ) ) {
            WP_CLI::warning( 'Legacy route retirement audit found pending changes, as permitted.' );
            return;
        }
        WP_CLI::error( 'Legacy route retirement audit found drift.' );
    }

    /** @param string[] $args @param array<string,mixed> $assoc_args */
    public function apply( array $args, array $assoc_args ): void {
        unset( $args );
        $this->guard( $assoc_args );
        foreach ( nvx_legacy_route_contract() as $route ) {
            $this->retireRoute( $route );
        }
        $rows = nvx_legacy_route_rows();
        WP_CLI\Utils\format_items(
            'table',
            $rows,
            array( 'legacy', 'source_id', 'source_state', 'target_id', 'target', 'target_state', 'old_slug_ids', 'menu_items', 'status' )
        );
        if ( ! nvx_legacy_routes_are_clean( $rows ) ) {
            WP_CLI::error( 'Legacy route retirement completed but drift remains.' );
        }
        WP_CLI::success( 'Legacy route retirement applied.' );
    }

    /** @param array<string,mixed> $assoc_args */
    private function guard( array $assoc_args ): void {
        if ( self::CONFIRMATION !== (string) ( $assoc_args['confirm'] ?? '' ) ) {
            WP_CLI::error( 'Refusing to apply: use --confirm=' . self::CONFIRMATION );
        }
        $host = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
        $allowed = array( 'staging2.nuvanx.com', 'nuvanx.com', 'www.nuvanx.com' );
        if ( ! in_array( $host, $allowed, true ) ) {
            WP_CLI::error( 'Refusing unexpected host: ' . $host );
        }
        if ( 'staging2.nuvanx.com' !== $host && ! isset( $assoc_args['allow-production'] ) ) {
            WP_CLI::error( 'Production requires --allow-production.' );
        }
        if ( ! defined( 'EMPTY_TRASH_DAYS' ) || (int) EMPTY_TRASH_DAYS < 1 ) {
            WP_CLI::error( 'Refusing to apply while WordPress trash is disabled.' );
        }
    }

    /** @param array{legacy:string,source_id:int,target:string,target_id:int} $route */
    private function retireRoute( array $route ): void {
        $target = nvx_legacy_target( $route );
        if ( ! $target instanceof WP_Post || 'page' !== $target->post_type || 'publish' !== $target->post_status ) {
            WP_CLI::error( 'Missing published target: ' . $route['target'] );
        }
        if ( $route['target'] !== $target->post_name || ( $route['target_id'] > 0 && $route['target_id'] !== (int) $target->ID ) ) {
            WP_CLI::error( 'Legacy target identity mismatch for ' . $route['legacy'] );
        }

        $source = nvx_legacy_source( $route );
        $source_id = $source instanceof WP_Post ? (int) $source->ID : (int) $route['source_id'];
        if ( $source instanceof WP_Post && (int) $source->ID !== (int) $target->ID && 'trash' !== $source->post_status ) {
            if ( ! wp_trash_post( (int) $source->ID ) instanceof WP_Post ) {
                WP_CLI::error( 'Unable to trash legacy page: ' . $route['legacy'] );
            }
        }
        foreach ( nvx_legacy_menu_items( $route['legacy'], $source_id ) as $menu_item_id ) {
            if ( false === wp_delete_post( $menu_item_id, true ) ) {
                WP_CLI::error( 'Unable to delete legacy menu item: ' . $menu_item_id );
            }
        }

        global $wpdb;
        $deleted = $wpdb->delete(
            $wpdb->postmeta,
            array( 'meta_key' => '_wp_old_slug', 'meta_value' => $route['legacy'] ),
            array( '%s', '%s' )
        );
        if ( false === $deleted ) {
            WP_CLI::error( 'Unable to remove old-slug ownership for ' . $route['legacy'] );
        }
        clean_post_cache( (int) $target->ID );
        if ( $source_id > 0 ) {
            clean_post_cache( $source_id );
        }
        WP_CLI::log( sprintf( 'Retired /%s/; retained target /%s/.', $route['legacy'], $route['target'] ) );
    }
}

WP_CLI::add_command( 'nvx legacy-routes', 'NvxLegacyRouteRetirementCommand' );
