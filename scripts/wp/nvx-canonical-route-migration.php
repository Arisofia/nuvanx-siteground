<?php
/**
 * Canonical legacy-route migration for NUVANX.
 *
 * Historical slugs are stored on their final published page through WordPress
 * core `_wp_old_slug`; the public runtime contains no redirect allowlist.
 *
 * @package nuvanx-siteground
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    return;
}

/** @return array<int,array{legacy:string,source_id:int,target:string,target_id:int}> */
function nvx_canonical_route_contract(): array {
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
function nvx_canonical_target( array $route ): ?WP_Post {
    $post = $route['target_id'] > 0
        ? get_post( $route['target_id'] )
        : get_page_by_path( $route['target'], OBJECT, 'page' );
    return $post instanceof WP_Post ? $post : null;
}

/** @param array{legacy:string,source_id:int,target:string,target_id:int} $route */
function nvx_canonical_source( array $route ): ?WP_Post {
    $post = $route['source_id'] > 0
        ? get_post( $route['source_id'] )
        : get_page_by_path( $route['legacy'], OBJECT, 'page' );
    return $post instanceof WP_Post ? $post : null;
}

/** @return int[] */
function nvx_canonical_old_slug_owners( string $legacy ): array {
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
function nvx_canonical_legacy_menu_items( string $legacy, int $source_id ): array {
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
function nvx_canonical_route_row( array $route ): array {
    $target = nvx_canonical_target( $route );
    $source = nvx_canonical_source( $route );
    $target_id = $target instanceof WP_Post ? (int) $target->ID : 0;
    $source_id = $source instanceof WP_Post ? (int) $source->ID : (int) $route['source_id'];
    $source_status = $source instanceof WP_Post ? (string) $source->post_status : 'absent';
    $owners = nvx_canonical_old_slug_owners( $route['legacy'] );
    $menu_items = nvx_canonical_legacy_menu_items( $route['legacy'], $source_id );
    $clean = (
        $target instanceof WP_Post
        && 'page' === $target->post_type
        && 'publish' === $target->post_status
        && $route['target'] === $target->post_name
        && ( 0 === $route['target_id'] || $route['target_id'] === $target_id )
        && ! in_array( $source_status, array( 'publish', 'private', 'future' ), true )
        && array( $target_id ) === $owners
        && array() === $menu_items
    );
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
function nvx_canonical_route_rows(): array {
    return array_map( 'nvx_canonical_route_row', nvx_canonical_route_contract() );
}

/** @param array<int,array<string,mixed>> $rows */
function nvx_canonical_routes_are_clean( array $rows ): bool {
    foreach ( $rows as $row ) {
        if ( 'clean' !== $row['status'] ) {
            return false;
        }
    }
    return true;
}

final class NvxCanonicalRouteCommand {
    private const CONFIRMATION = 'canonicalize-legacy-routes';

    /** @param string[] $args @param array<string,mixed> $assoc_args */
    public function audit( array $args, array $assoc_args ): void {
        unset( $args );
        $rows = nvx_canonical_route_rows();
        WP_CLI\Utils\format_items(
            isset( $assoc_args['format'] ) ? (string) $assoc_args['format'] : 'table',
            $rows,
            array( 'legacy', 'source_id', 'source_state', 'target_id', 'target', 'target_state', 'old_slug_ids', 'menu_items', 'status' )
        );
        if ( nvx_canonical_routes_are_clean( $rows ) ) {
            WP_CLI::success( 'Canonical route audit passed.' );
            return;
        }
        if ( isset( $assoc_args['allow-pending'] ) ) {
            WP_CLI::warning( 'Canonical route audit found pending changes, as permitted.' );
            return;
        }
        WP_CLI::error( 'Canonical route audit found drift.' );
    }

    /** @param string[] $args @param array<string,mixed> $assoc_args */
    public function apply( array $args, array $assoc_args ): void {
        unset( $args );
        $this->guard( $assoc_args );
        foreach ( nvx_canonical_route_contract() as $route ) {
            $this->applyRoute( $route );
        }
        flush_rewrite_rules( false );
        $rows = nvx_canonical_route_rows();
        WP_CLI\Utils\format_items(
            'table',
            $rows,
            array( 'legacy', 'source_id', 'source_state', 'target_id', 'target', 'target_state', 'old_slug_ids', 'menu_items', 'status' )
        );
        if ( ! nvx_canonical_routes_are_clean( $rows ) ) {
            WP_CLI::error( 'Canonical migration completed but drift remains.' );
        }
        WP_CLI::success( 'Canonical route migration applied.' );
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
    private function applyRoute( array $route ): void {
        $target = nvx_canonical_target( $route );
        if ( ! $target instanceof WP_Post || 'page' !== $target->post_type || 'publish' !== $target->post_status ) {
            WP_CLI::error( 'Missing published target: ' . $route['target'] );
        }
        if ( $route['target'] !== $target->post_name || ( $route['target_id'] > 0 && $route['target_id'] !== (int) $target->ID ) ) {
            WP_CLI::error( 'Canonical target identity mismatch for ' . $route['legacy'] );
        }

        $source = nvx_canonical_source( $route );
        $source_id = $source instanceof WP_Post ? (int) $source->ID : (int) $route['source_id'];
        if ( $source instanceof WP_Post && (int) $source->ID !== (int) $target->ID && 'trash' !== $source->post_status ) {
            if ( ! wp_trash_post( (int) $source->ID ) instanceof WP_Post ) {
                WP_CLI::error( 'Unable to trash legacy page: ' . $route['legacy'] );
            }
        }
        foreach ( nvx_canonical_legacy_menu_items( $route['legacy'], $source_id ) as $menu_item_id ) {
            wp_delete_post( $menu_item_id, true );
        }

        global $wpdb;
        $wpdb->delete(
            $wpdb->postmeta,
            array( 'meta_key' => '_wp_old_slug', 'meta_value' => $route['legacy'] ),
            array( '%s', '%s' )
        );
        add_post_meta( (int) $target->ID, '_wp_old_slug', $route['legacy'], false );
        clean_post_cache( (int) $target->ID );
        WP_CLI::log( sprintf( 'Canonicalized /%s/ -> /%s/.', $route['legacy'], $route['target'] ) );
    }
}

WP_CLI::add_command( 'nvx canonical-routes', 'NvxCanonicalRouteCommand' );
