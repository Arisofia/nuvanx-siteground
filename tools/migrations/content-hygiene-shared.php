<?php
require_once __DIR__ . '/../../lib/nvx-content-hygiene-rules.php';

// ── Bootstrap ─────────────────────────────────────────────────────────────────

global $wpdb;

$dry_run = '1' === getenv( 'MIGRATION_DRY_RUN' );
$start   = microtime( true );

printf( "=== NVX Shared Content Migration ===\n" );
printf( "Mode        : %s\n", $dry_run ? 'DRY RUN (no writes)' : 'LIVE' );
printf( "Site        : %s\n", get_option( 'siteurl' ) );
printf( "Started     : %s\n\n", current_time( 'Y-m-d H:i:s' ) );

if ( $dry_run ) {
    echo "[DRY RUN] No changes will be persisted.\n\n";
}

// ── Counters / accumulators ───────────────────────────────────────────────────

$blocks_ok     = 0;
$blocks_fail   = 0;
$total_updated = 0;
$total_checked = 0;

// ── Fetch posts ───────────────────────────────────────────────────────────────

$fields     = nvx_hygiene_fields();
$fields_sql = implode( ', ', array_map( fn( $f ) => "`$f`", $fields ) );

$posts = $wpdb->get_results(
    "SELECT ID, post_name, post_status, {$fields_sql}
       FROM {$wpdb->posts}
      WHERE post_status IN ('publish','draft','private','pending')
        AND post_type   NOT IN ('revision','nav_menu_item','attachment')
      ORDER BY ID ASC",
    ARRAY_A
);

if ( null === $posts ) {
    fwrite( STDERR, "[FATAL] wpdb returned null. " . $wpdb->last_error . "\n" );
    echo "Status: MIGRATION_FAIL\n";
    exit( 1 );
}

printf( "Posts loaded  : %d\n\n", count( $posts ) );

// ── Low-level mutation helpers ────────────────────────────────────────────────

/**
 * Create the durable production rollback marker before the first database write.
 */
$arm_write_marker = function() use ( $dry_run ): bool {
    if ( $dry_run ) {
        return true;
    }

    $marker_file = getenv( 'MIGRATION_WRITE_MARKER' );
    if ( ! $marker_file || file_exists( $marker_file ) ) {
        return true;
    }

    if ( ! touch( $marker_file ) ) {
        fwrite( STDERR, "[FATAL] Failed to create migration write marker at {$marker_file}. Aborting to prevent silent DB rollback disable.\n" );
        return false;
    }

    echo "MIGRATION_WRITE_MARKER_CREATED at={$marker_file}\n";
    return true;
};

/**
 * Write a single post field to the database (or simulate in dry-run mode).
 * Returns true on success, false on DB error.
 */
$write_field = function( int $post_id, string $field, string $new_value ) use ( $wpdb, $dry_run, $arm_write_marker ): bool {
    if ( $dry_run ) {
        return true;
    }
    if ( ! $arm_write_marker() ) {
        return false;
    }
    $result = $wpdb->update(
        $wpdb->posts,
        [ $field => $new_value ],
        [ 'ID'   => $post_id  ],
        [ '%s'   ],
        [ '%d'   ]
    );
    if ( false === $result ) {
        fwrite( STDERR, "[DB ERROR] ID={$post_id} field={$field}: " . $wpdb->last_error . "\n" );
        return false;
    }
    return true;
};

/**
 * Apply a plain-string replacement to one post field.
 * Returns 'clean' | 'updated' | 'error'.
 *
 * Legal-page H1 integrity is a separate, verify-only gate (Block D and the
 * pre-cutover divergence audit). The replacement contract must stay identical
 * to audit-content-divergence.php: if the audit reports a string as migratable,
 * this function must be able to remove it.
 */
$apply_str = function(
    array  &$post,
    string  $field,
    string  $from,
    string  $to
) use ( &$total_checked, &$total_updated, $write_field, $dry_run ): string {
    $total_checked++;
    $original = $post[ $field ] ?? '';

    if ( false === mb_strpos( $original, $from ) ) {
        return 'clean';
    }

    $new_value = str_replace( $from, $to, $original );

    if ( $new_value === $original ) {
        return 'clean';
    }

    $prefix = $dry_run ? '[DRY-RUN] Would update' : '[UPDATED]';
    printf(
        "%s POST_ID=%-5d | field=%-16s | \"%s\" → \"%s\"\n",
        $prefix, $post['ID'], $field, $from, $to
    );

    $total_updated++;

    if ( ! $write_field( (int) $post['ID'], $field, $new_value ) ) {
        return 'error';
    }

    $post[ $field ] = $new_value;
    return 'updated';
};

/**
 * Apply a regex replacement to one post field.
 * Returns 'clean' | 'updated' | 'error'.
 */
$apply_rx = function(
    array  &$post,
    string  $field,
    string  $pattern,
    string  $replacement,
    string  $flags
) use ( &$total_checked, &$total_updated, $write_field, $dry_run ): string {
    $total_checked++;
    $original = $post[ $field ] ?? '';
    $pcre     = "/{$pattern}/{$flags}";

    if ( ! preg_match( $pcre, $original ) ) {
        return 'clean';
    }

    $new_value = preg_replace( $pcre, $replacement, $original );

    if ( null === $new_value ) {
        fwrite( STDERR, "[PREG ERROR] ID={$post['ID']} field={$field} pattern=/{$pattern}/{$flags}\n" );
        return 'error';
    }

    if ( $new_value === $original ) {
        return 'clean';
    }

    $prefix = $dry_run ? '[DRY-RUN] Would update' : '[UPDATED]';
    printf(
        "%s POST_ID=%-5d | field=%-16s | regex /%s/\n",
        $prefix, $post['ID'], $field, $pattern
    );

    $total_updated++;

    if ( ! $write_field( (int) $post['ID'], $field, $new_value ) ) {
        return 'error';
    }

    $post[ $field ] = $new_value;
    return 'updated';
};

// ── Block C2: Governed journal Markdown normalization ─────────────────────────
// Included here so it shares the same durable write marker and deployment-level
// database rollback contract as every other shared content migration.
//
// NOTE: This block executes before Block A intentionally. Block C2 must run first
// and re-syncs the in-memory $posts rows so that Blocks A/B, which write whole field
// values from that array, do not overwrite the normalized content with pre-normalization
// Markdown. The log output shows "--- Block C2 ---" before "--- Block A ---" because
// of this deliberate execution order.
require_once __DIR__ . '/governed-blog-markdown-hygiene.php';

// ── Block A: String replacements ──────────────────────────────────────────────

echo "--- Block A: String Replacements ---\n";

foreach ( nvx_hygiene_str_reps() as $rule ) {
    $block_errors = 0;

    foreach ( $posts as &$post ) {
        foreach ( $fields as $field ) {
            $status = $apply_str( $post, $field, $rule['from'], $rule['to'] );
            if ( 'error' === $status ) {
                $block_errors++;
            }
        }
    }
    unset( $post );

    if ( $block_errors > 0 ) {
        $blocks_fail++;
        fwrite( STDERR, "[BLOCK FAIL] str \"{$rule['from']}\" — {$block_errors} error(s)\n" );
    } else {
        $blocks_ok++;
    }
}

echo "\n";

// ── Block B: Regex replacements ───────────────────────────────────────────────

echo "--- Block B: Regex Replacements ---\n";

foreach ( nvx_hygiene_regex_reps() as $rule ) {
    $block_errors = 0;

    foreach ( $posts as &$post ) {
        foreach ( $fields as $field ) {
            $status = $apply_rx( $post, $field, $rule['pattern'], $rule['replacement'], $rule['flags'] );
            if ( 'error' === $status ) {
                $block_errors++;
            }
        }
    }
    unset( $post );

    if ( $block_errors > 0 ) {
        $blocks_fail++;
        fwrite( STDERR, "[BLOCK FAIL] regex /{$rule['pattern']}/ — {$block_errors} error(s)\n" );
    } else {
        $blocks_ok++;
    }
}

echo "\n";

// ── Block C: Retired strategy pages ───────────────────────────────────────────

echo "--- Block C: Retired Strategy Pages ---\n";

$retired_errors = 0;

if ( defined( 'EMPTY_TRASH_DAYS' ) && (int) EMPTY_TRASH_DAYS < 1 ) {
    fwrite( STDERR, "[BLOCK FAIL] EMPTY_TRASH_DAYS < 1 would make wp_trash_post() delete permanently. Refusing retirement mutation.\n" );
    $retired_errors++;
} else {
    foreach ( nvx_hygiene_retired_strategy_pages() as $slug => $contract ) {
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT ID, post_status, post_type
                   FROM {$wpdb->posts}
                  WHERE post_name = %s
                    AND post_type NOT IN ('revision','nav_menu_item','attachment')
                  ORDER BY ID ASC",
                $slug
            ),
            ARRAY_A
        );

        if ( null === $rows ) {
            fwrite( STDERR, "[BLOCK FAIL] Could not query retired slug /{$slug}/: {$wpdb->last_error}\n" );
            $retired_errors++;
            continue;
        }

        if ( empty( $rows ) ) {
            printf( "[OK] /%s/ — no WordPress content record remains; HTTP redirect target %s\n", $slug, $contract['target'] );
            continue;
        }

        foreach ( $rows as $row ) {
            $post_id = (int) $row['ID'];
            $status  = (string) $row['post_status'];

            if ( 'trash' === $status ) {
                printf( "[OK] /%s/ — ID %d already trash; redirect target %s\n", $slug, $post_id, $contract['target'] );
                continue;
            }

            if ( $dry_run ) {
                printf( "[DRY-RUN] Would trash retired page ID %d /%s/ (status=%s)\n", $post_id, $slug, $status );
                continue;
            }

            if ( ! $arm_write_marker() ) {
                $retired_errors++;
                continue;
            }

            $trashed = wp_trash_post( $post_id );
            if ( false === $trashed || 'trash' !== get_post_status( $post_id ) ) {
                fwrite( STDERR, "[BLOCK FAIL] Could not trash retired page ID {$post_id} /{$slug}/.\n" );
                $retired_errors++;
                continue;
            }

            $total_updated++;
            printf( "[RETIRED] ID %d /%s/ → trash; redirect target %s\n", $post_id, $slug, $contract['target'] );

            $menu_item_ids = $wpdb->get_col(
                $wpdb->prepare(
                    "SELECT pm.post_id
                       FROM {$wpdb->postmeta} pm
                       JOIN {$wpdb->posts} p ON p.ID = pm.post_id
                      WHERE p.post_type = 'nav_menu_item'
                        AND pm.meta_key = '_menu_item_object_id'
                        AND pm.meta_value = %s",
                    (string) $post_id
                )
            );

            foreach ( array_map( 'intval', $menu_item_ids ?: [] ) as $menu_item_id ) {
                if ( false === wp_delete_post( $menu_item_id, true ) ) {
                    fwrite( STDERR, "[BLOCK FAIL] Could not remove nav menu item {$menu_item_id} for retired page ID {$post_id}.\n" );
                    $retired_errors++;
                } else {
                    $total_updated++;
                    printf( "[MENU RETIRED] nav_item=%d retired_page_id=%d\n", $menu_item_id, $post_id );
                }
            }
        }
    }
}

if ( 0 === $retired_errors ) {
    $custom_items = $wpdb->get_results(
        "SELECT pm.post_id, pm.meta_value
           FROM {$wpdb->postmeta} pm
           JOIN {$wpdb->posts} p ON p.ID = pm.post_id
          WHERE p.post_type = 'nav_menu_item'
            AND pm.meta_key = '_menu_item_url'",
        ARRAY_A
    );

    if ( null === $custom_items ) {
        fwrite( STDERR, "[BLOCK FAIL] Could not query custom nav menu links: {$wpdb->last_error}\n" );
        $retired_errors++;
    } else {
        $retired_slugs = array_keys( nvx_hygiene_retired_strategy_pages() );
        foreach ( $custom_items as $item ) {
            $path = trim( (string) wp_parse_url( (string) $item['meta_value'], PHP_URL_PATH ), '/' );
            if ( ! in_array( $path, $retired_slugs, true ) ) {
                continue;
            }

            $menu_item_id = (int) $item['post_id'];
            if ( $dry_run ) {
                printf( "[DRY-RUN] Would remove custom nav menu item %d → /%s/\n", $menu_item_id, $path );
                continue;
            }

            if ( ! $arm_write_marker() || false === wp_delete_post( $menu_item_id, true ) ) {
                fwrite( STDERR, "[BLOCK FAIL] Could not remove custom nav menu item {$menu_item_id} → /{$path}/.\n" );
                $retired_errors++;
            } else {
                $total_updated++;
                printf( "[MENU RETIRED] nav_item=%d custom_path=/%s/\n", $menu_item_id, $path );
            }
        }
    }
}

if ( $retired_errors > 0 ) {
    $blocks_fail++;
} else {
    $blocks_ok++;
}

echo "\n";

// ── Block D: Legal page H1 verification (verify-only, no writes) ──────────────

echo "--- Block D: Legal Page H1 Verification (verify-only) ---\n";

foreach ( nvx_hygiene_legal_pages() as $slug => $expected ) {
    $page = get_page_by_path( $slug, OBJECT, 'page' );

    if ( ! $page ) {
        fwrite( STDERR, "[BLOCK FAIL] Legal page /{$slug}/ not found in database.\n" );
        $blocks_fail++;
        continue;
    }

    $count = preg_match_all( '/<h1[^>]*>(.*?)<\/h1>/is', $page->post_content, $m );

    if ( 1 === $count ) {
        $found = trim( wp_strip_all_tags( $m[1][0] ) );
        if ( $found === $expected ) {
            printf( "[OK] /%s/ — H1 \"%s\" correct (ID %d)\n", $slug, $expected, $page->ID );
            $blocks_ok++;
        } else {
            fwrite( STDERR, sprintf(
                "[WARN] /%s/ — H1 mismatch. Expected \"%s\", found \"%s\" (ID %d). Manual review required.\n",
                $slug, $expected, $found, $page->ID
            ) );
            $blocks_ok++;
        }
    } elseif ( 0 === $count ) {
        fwrite( STDERR, sprintf( "[WARN] /%s/ — no <h1> found (ID %d). Manual review required.\n", $slug, $page->ID ) );
        $blocks_ok++;
    } else {
        fwrite( STDERR, sprintf( "[WARN] /%s/ — %d <h1> tags (ID %d). Manual review required.\n", $slug, $count, $page->ID ) );
        $blocks_ok++;
    }
}

echo "\n";

// ── Block E: Post-migration assertions ────────────────────────────────────────

echo "--- Block E: Post-migration Assertions ---\n";

if ( $dry_run ) {
    echo "[DRY RUN] Skipping assertions.\n";
    $blocks_ok++;
} else {
    $known_ids   = [ 14, 1543, 2636, 2715 ];
    $assert_fail = false;

    foreach ( $known_ids as $pid ) {
        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT post_title, post_content, post_excerpt
                   FROM {$wpdb->posts}
                  WHERE ID = %d",
                $pid
            ),
            ARRAY_A
        );

        if ( ! $row ) {
            printf( "[SKIP] ID %d — not found in this environment.\n", $pid );
            continue;
        }

        $combined = implode( ' ', $row );
        $clean    = true;

        foreach ( nvx_hygiene_str_reps() as $rule ) {
            if ( false !== mb_strpos( $combined, $rule['from'] ) ) {
                fwrite( STDERR, sprintf(
                    "[ASSERT FAIL] ID %d still contains \"%s\" after migration.\n",
                    $pid, $rule['from']
                ) );
                $clean       = false;
                $assert_fail = true;
            }
        }

        if ( $clean ) {
            printf( "[ASSERT OK] ID %d — no stale hygiene strings.\n", $pid );
        }
    }

    foreach ( nvx_hygiene_retired_strategy_pages() as $slug => $contract ) {
        $remaining = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*)
                   FROM {$wpdb->posts}
                  WHERE post_name = %s
                    AND post_type NOT IN ('revision','nav_menu_item','attachment')
                    AND post_status <> 'trash'",
                $slug
            )
        );
        if ( 0 !== $remaining ) {
            fwrite( STDERR, "[ASSERT FAIL] /{$slug}/ still has {$remaining} non-trash WordPress record(s).\n" );
            $assert_fail = true;
        } else {
            printf( "[ASSERT OK] /%s/ — no non-trash WordPress records; redirect=%s\n", $slug, $contract['target'] );
        }
    }

    if ( $assert_fail ) {
        $blocks_fail++;
    } else {
        $blocks_ok++;
    }
}

echo "\n";

// ── Final summary ─────────────────────────────────────────────────────────────

$elapsed = round( microtime( true ) - $start, 2 );

echo "=== MIGRATION SUMMARY ===\n";
printf( "Mode           : %s\n", $dry_run ? 'DRY RUN' : 'LIVE' );
printf( "Posts processed: %d\n", count( $posts ) );
printf( "Checks run     : %d\n", $total_checked );
printf( "Records updated: %d\n", $total_updated );
printf( "Blocks OK      : %d\n", $blocks_ok );
printf( "Blocks FAIL    : %d\n", $blocks_fail );
printf( "Elapsed        : %ss\n\n", $elapsed );

if ( 0 === $blocks_fail ) {
    echo "Status: MIGRATION_OK\n";
    exit( 0 );
}

printf( "Status: MIGRATION_FAIL (%d block(s) failed)\n", $blocks_fail );
exit( 1 );
