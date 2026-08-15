<?php
/**
 * Read-only forensic diagnostic for legacy Schema.org JSON-LD storage.
 *
 * Run with:
 *   wp eval "require 'tools/migrations/diagnose-jsonld-storage.php';" --allow-root
 *
 * SECURITY CONTRACT:
 * - Never prints stored values.
 * - Reports identifiers, byte lengths, serialization state and matched signature labels only.
 * - Potentially sensitive option/meta names are represented by a short hash instead of plaintext.
 * - Performs no INSERT/UPDATE/DELETE and calls no mutating WordPress API.
 *
 * @package NVX\Migrations
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
    fwrite( STDERR, "ERROR: must run inside WordPress via wp eval \"require '...';\".\n" );
    exit( 1 );
}

global $wpdb;

$signatures = [
    'blogposting-fragment'        => '#blogposting',
    'local-procedure-fragment'    => '#medicalprocedure-local',
    'free-text-specialty'         => 'Medicina estética láser',
    'therapeutic-procedure-label' => 'Terapéutico médico-estético',
    'medical-aesthetic-label'     => 'Procedimiento médico-estético',
    'health-lifesci-physicalexam' => 'health-lifesci.schema.org/PhysicalExam',
];

/** Return labels of known legacy signatures found in a stored value.
 *
 * NOTE: This function uses mb_stripos which is accent-sensitive, while the
 * SQL LIKE query that selected the row is accent-insensitive (utf8mb4_*_ci).
 * This means a stored value like "Medicina estetica laser" (unaccented) may
 * be selected by the query but produce signatures=[], showing a match with
 * no indication of which legacy signature triggered it. Detection is never
 * lost (LIKE is the broader matcher), only the label attribution.
 */
function nvx_jsonld_diag_signature_labels( string $value, array $signatures ): array {
    $labels = [];
    foreach ( $signatures as $label => $needle ) {
        if ( false !== mb_stripos( $value, $needle, 0, 'UTF-8' ) ) {
            $labels[] = $label;
        }
    }
    return $labels;
}

/** Avoid exposing identifiers whose names themselves suggest credentials. */
function nvx_jsonld_diag_safe_name( string $name ): array {
    if ( preg_match( '/secret|token|password|passwd|credential|private|api[_-]?key|auth/i', $name ) ) {
        return [
            'name'      => '[redacted-sensitive-name]',
            'name_hash' => substr( hash( 'sha256', $name ), 0, 16 ),
        ];
    }
    return [ 'name' => $name ];
}

/** Build a prepared OR-LIKE clause for all diagnostic signatures. */
function nvx_jsonld_diag_like_clause( string $column, array $signatures, wpdb $wpdb ): array {
    $parts  = [];
    $params = [];
    foreach ( $signatures as $needle ) {
        $parts[]  = "{$column} LIKE %s";
        $params[] = '%' . $wpdb->esc_like( $needle ) . '%';
    }
    return [ '(' . implode( ' OR ', $parts ) . ')', $params ];
}

/** Execute one bounded prepared query. */
function nvx_jsonld_diag_rows( string $sql_template, array $params, wpdb $wpdb ): array {
    // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- placeholders are assembled above; values are prepared here.
    $prepared = $wpdb->prepare( $sql_template, ...$params );
    if ( ! is_string( $prepared ) ) {
        return [];
    }
    // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- query has been prepared above and is read-only SELECT.
    $rows = $wpdb->get_results( $prepared, ARRAY_A );
    return is_array( $rows ) ? $rows : [];
}

$matches = [];

// 1. wp_posts: proves whether the shared post-field hygiene source still contains signatures.
[ $posts_where, $posts_params ] = nvx_jsonld_diag_like_clause( 'post_content', $signatures, $wpdb );
[ $excerpt_where, $excerpt_params ] = nvx_jsonld_diag_like_clause( 'post_excerpt', $signatures, $wpdb );
[ $title_where, $title_params ] = nvx_jsonld_diag_like_clause( 'post_title', $signatures, $wpdb );
$post_rows = nvx_jsonld_diag_rows(
    "SELECT ID, post_type, post_status, post_name, post_title, post_content, post_excerpt
     FROM {$wpdb->posts}
     WHERE {$posts_where} OR {$excerpt_where} OR {$title_where}
     ORDER BY ID ASC
     LIMIT 250",
    array_merge( $posts_params, $excerpt_params, $title_params ),
    $wpdb
);
foreach ( $post_rows as $row ) {
    $combined = (string) $row['post_title'] . "\n" . (string) $row['post_content'] . "\n" . (string) $row['post_excerpt'];
    $matches[] = [
        'source'      => 'posts',
        'post_id'     => (int) $row['ID'],
        'post_type'   => (string) $row['post_type'],
        'post_status' => (string) $row['post_status'],
        'post_name'   => (string) $row['post_name'],
        'bytes'       => strlen( $combined ),
        'signatures'  => nvx_jsonld_diag_signature_labels( $combined, $signatures ),
    ];
}

// 2. wp_postmeta: most likely location for editor/SEO/custom-field generated schema.
[ $meta_where, $meta_params ] = nvx_jsonld_diag_like_clause( 'pm.meta_value', $signatures, $wpdb );
$postmeta_rows = nvx_jsonld_diag_rows(
    "SELECT pm.meta_id, pm.post_id, pm.meta_key, pm.meta_value, p.post_type, p.post_status, p.post_name
     FROM {$wpdb->postmeta} pm
     LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
     WHERE {$meta_where}
     ORDER BY pm.post_id ASC, pm.meta_id ASC
     LIMIT 500",
    $meta_params,
    $wpdb
);
foreach ( $postmeta_rows as $row ) {
    $value     = (string) $row['meta_value'];
    $safe_name = nvx_jsonld_diag_safe_name( (string) $row['meta_key'] );
    $matches[] = array_merge(
        [
            'source'      => 'postmeta',
            'meta_id'     => (int) $row['meta_id'],
            'post_id'     => (int) $row['post_id'],
            'post_type'   => (string) ( $row['post_type'] ?? '' ),
            'post_status' => (string) ( $row['post_status'] ?? '' ),
            'post_name'   => (string) ( $row['post_name'] ?? '' ),
            'bytes'       => strlen( $value ),
            'serialized'  => is_serialized( $value ),
            'signatures'  => nvx_jsonld_diag_signature_labels( $value, $signatures ),
        ],
        $safe_name
    );
}

// 3. wp_options: catches plugin/theme settings that emit schema globally.
[ $option_where, $option_params ] = nvx_jsonld_diag_like_clause( 'option_value', $signatures, $wpdb );
$option_rows = nvx_jsonld_diag_rows(
    "SELECT option_id, option_name, option_value, autoload
     FROM {$wpdb->options}
     WHERE {$option_where}
     ORDER BY option_id ASC
     LIMIT 500",
    $option_params,
    $wpdb
);
foreach ( $option_rows as $row ) {
    $value      = (string) $row['option_value'];
    $safe_name  = nvx_jsonld_diag_safe_name( (string) $row['option_name'] );
    $matches[]  = array_merge(
        [
            'source'     => 'options',
            'option_id'  => (int) $row['option_id'],
            'autoload'   => (string) $row['autoload'],
            'bytes'      => strlen( $value ),
            'serialized' => is_serialized( $value ),
            'signatures' => nvx_jsonld_diag_signature_labels( $value, $signatures ),
        ],
        $safe_name
    );
}

// 4. Other core metadata tables. Values remain private; only owner ID/key metadata is exposed.
$meta_tables = [
    'termmeta'    => [ $wpdb->termmeta, 'meta_id', 'term_id' ],
    'commentmeta' => [ $wpdb->commentmeta, 'meta_id', 'comment_id' ],
    'usermeta'    => [ $wpdb->usermeta, 'umeta_id', 'user_id' ],
];
foreach ( $meta_tables as $source => [ $table, $pk, $owner ] ) {
    if ( ! is_string( $table ) || '' === $table ) {
        continue;
    }
    [ $where, $params ] = nvx_jsonld_diag_like_clause( 'meta_value', $signatures, $wpdb );
    $rows = nvx_jsonld_diag_rows(
        "SELECT {$pk} AS record_id, {$owner} AS owner_id, meta_key, meta_value
         FROM {$table}
         WHERE {$where}
         ORDER BY {$pk} ASC
         LIMIT 250",
        $params,
        $wpdb
    );
    foreach ( $rows as $row ) {
        $value     = (string) $row['meta_value'];
        $safe_name = nvx_jsonld_diag_safe_name( (string) $row['meta_key'] );
        $matches[] = array_merge(
            [
                'source'     => $source,
                'record_id'  => (int) $row['record_id'],
                'owner_id'   => (int) $row['owner_id'],
                'bytes'      => strlen( $value ),
                'serialized' => is_serialized( $value ),
                'signatures' => nvx_jsonld_diag_signature_labels( $value, $signatures ),
            ],
            $safe_name
        );
    }
}

// 5. If core storage has no hits, perform a count-only scan across plugin/custom
// prefixed text columns. No row values or primary keys are returned.
$custom_columns = [];
if ( 0 === count( $matches ) ) {
    $db_name = (string) DB_NAME;
    $prefix_like = $wpdb->esc_like( $wpdb->prefix ) . '%';
    $core_tables = array_filter( [
        $wpdb->posts,
        $wpdb->postmeta,
        $wpdb->options,
        $wpdb->termmeta,
        $wpdb->commentmeta,
        $wpdb->usermeta,
        $wpdb->terms,
        $wpdb->term_taxonomy,
        $wpdb->term_relationships,
        $wpdb->comments,
        $wpdb->users,
        $wpdb->links,
    ] );
    $placeholders = implode( ',', array_fill( 0, count( $core_tables ), '%s' ) );
    $sql = "SELECT TABLE_NAME, COLUMN_NAME
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = %s
              AND TABLE_NAME LIKE %s
              AND DATA_TYPE IN ('tinytext','text','mediumtext','longtext','varchar')
              AND TABLE_NAME NOT IN ({$placeholders})
            ORDER BY TABLE_NAME, ORDINAL_POSITION
            LIMIT 500";
    $params = array_merge( [ $db_name, $prefix_like ], array_values( $core_tables ) );
    $columns = nvx_jsonld_diag_rows( $sql, $params, $wpdb );

    foreach ( $columns as $column ) {
        $table_name  = (string) $column['TABLE_NAME'];
        $column_name = (string) $column['COLUMN_NAME'];
        // Validate identifiers against strict allowlist to prevent SQL injection
        // This is the primary security control; identifiers come from information_schema
        // which is trusted database metadata, but we validate regardless
        if ( ! preg_match( '/^[A-Za-z0-9_$]+$/', $table_name ) || ! preg_match( '/^[A-Za-z0-9_$]+$/', $column_name ) ) {
            continue;
        }
        [ $where, $like_params ] = nvx_jsonld_diag_like_clause( '`' . $column_name . '`', $signatures, $wpdb );
        $count_sql = "SELECT COUNT(*) FROM `{$table_name}` WHERE {$where}";
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- identifiers validated with strict allowlist; data is prepared.
        $prepared = $wpdb->prepare( $count_sql, ...$like_params );
        if ( ! is_string( $prepared ) ) {
            continue;
        }
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- prepared read-only count query.
        $count = (int) $wpdb->get_var( $prepared );
        if ( $count > 0 ) {
            $custom_columns[] = [
                'table'        => $table_name,
                'column'       => $column_name,
                'match_rows'   => $count,
            ];
        }
    }
}

// 6. Runtime emitter inventory: only plugin/MU-plugin callbacks attached to the
// output-oriented hooks. This reveals candidate owners without executing hooks or values.
//
// LIMITATION: WP-CLI boots WordPress up to init, so callbacks registered later in
// a front-end request lifecycle (inside wp, template_redirect, wp_enqueue_scripts,
// or conditionally on is_singular()/is_page()) are not present in $wp_filter yet.
// This diagnostic may report runtime_emitters=0 even when an emitter exists that
// registers conditionally on front-end requests. For NUVANX, the primary JSON-LD
// emitter (Yoast SEO wpseo_schema_graph) registers at plugin load time, so this
// limitation is acceptable for the current architecture.
$runtime_emitters = [];
$hooks = [ 'wp_head', 'wp_footer', 'wp_body_open' ];
foreach ( $hooks as $hook_name ) {
    global $wp_filter;
    if ( empty( $wp_filter[ $hook_name ] ) || ! isset( $wp_filter[ $hook_name ]->callbacks ) ) {
        continue;
    }
    foreach ( $wp_filter[ $hook_name ]->callbacks as $priority => $callbacks ) {
        foreach ( $callbacks as $callback ) {
            $fn = $callback['function'] ?? null;
            $label = '';
            $file  = '';
            try {
                if ( is_string( $fn ) && function_exists( $fn ) ) {
                    $reflection = new ReflectionFunction( $fn );
                    $label      = $fn;
                    $file       = (string) $reflection->getFileName();
                } elseif ( is_array( $fn ) && 2 === count( $fn ) ) {
                    $class      = is_object( $fn[0] ) ? get_class( $fn[0] ) : (string) $fn[0];
                    $method     = (string) $fn[1];
                    $reflection = new ReflectionMethod( $class, $method );
                    $label      = $class . '::' . $method;
                    $file       = (string) $reflection->getFileName();
                } elseif ( $fn instanceof Closure ) {
                    $reflection = new ReflectionFunction( $fn );
                    $label      = 'Closure';
                    $file       = (string) $reflection->getFileName();
                }
            } catch ( Throwable $e ) {
                continue;
            }

            if ( '' === $file ) {
                continue;
            }
            $normalized = wp_normalize_path( $file );
            $plugin_root = wp_normalize_path( WP_PLUGIN_DIR );
            $mu_root     = wp_normalize_path( WPMU_PLUGIN_DIR );
            if ( 0 !== strpos( $normalized, $plugin_root . '/' ) && 0 !== strpos( $normalized, $mu_root . '/' ) ) {
                continue;
            }
            $relative = str_replace( [ $plugin_root . '/', $mu_root . '/' ], '', $normalized );
            $runtime_emitters[] = [
                'hook'     => $hook_name,
                'priority' => (int) $priority,
                'callback' => $label,
                'file'     => $relative,
            ];
        }
    }
}

$summary = [
    'schema'                => 1,
    'site'                  => home_url( '/' ),
    'core_matches'          => count( $matches ),
    'custom_columns'        => count( $custom_columns ),
    'runtime_emitters'      => count( $runtime_emitters ),
    'matches'               => $matches,
    'custom_storage'        => $custom_columns,
    'emitters'              => $runtime_emitters,
    'inventory_limitation'  => 'wpcli_inventory_only',
];

foreach ( $matches as $match ) {
    $parts = [
        'source=' . $match['source'],
        'bytes=' . $match['bytes'],
        'signatures=' . implode( ',', $match['signatures'] ),
    ];
    foreach ( [ 'post_id', 'post_type', 'post_status', 'post_name', 'meta_id', 'option_id', 'autoload', 'record_id', 'owner_id', 'name', 'name_hash', 'serialized' ] as $key ) {
        if ( array_key_exists( $key, $match ) ) {
            $value = is_bool( $match[ $key ] ) ? ( $match[ $key ] ? 'yes' : 'no' ) : (string) $match[ $key ];
            $parts[] = $key . '=' . preg_replace( '/\s+/', '_', $value );
        }
    }
    echo 'JSONLD_STORAGE_MATCH ' . implode( ' ', $parts ) . "\n";
}
foreach ( $custom_columns as $column ) {
    echo sprintf(
        "JSONLD_CUSTOM_STORAGE_MATCH table=%s column=%s match_rows=%d\n",
        $column['table'],
        $column['column'],
        $column['match_rows']
    );
}
foreach ( $runtime_emitters as $emitter ) {
    echo sprintf(
        "JSONLD_RUNTIME_EMITTER hook=%s priority=%d callback=%s file=%s\n",
        $emitter['hook'],
        $emitter['priority'],
        preg_replace( '/\s+/', '_', $emitter['callback'] ),
        preg_replace( '/\s+/', '_', $emitter['file'] )
    );
}

echo 'JSONLD_STORAGE_DIAGNOSTIC_JSON=' . wp_json_encode( $summary, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "\n";
echo sprintf(
    "JSONLD_STORAGE_DIAGNOSTIC=PASS core_matches=%d custom_columns=%d runtime_emitters=%d mutation=none\n",
    count( $matches ),
    count( $custom_columns ),
    count( $runtime_emitters )
);
