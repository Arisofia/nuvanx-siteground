<?php

// ── Safety gate — must be the very first executable statement ─────────────────

if (
    ! function_exists( 'nvx_environment_is_staging2' )
    || ! nvx_environment_is_staging2()
) {
    fwrite( STDERR,
        "[FATAL] content-hygiene-staging-only.php executed outside Staging2. " .
        "This script is not permitted in production. Aborting.\n"
    );
    echo "Status: STAGING_ONLY_ABORT\n";
    exit( 1 );
}

if ( ! function_exists( 'nvx_aesthetic_treatment_catalog' ) ) {
    fwrite( STDERR,
        "[FATAL] nvx_aesthetic_treatment_catalog() not available. " .
        "Theme may not be fully loaded. Aborting.\n"
    );
    echo "Status: STAGING_ONLY_ABORT\n";
    exit( 1 );
}

require_once __DIR__ . '/lib/nvx-content-hygiene-rules.php';

// ── Bootstrap ─────────────────────────────────────────────────────────────────

global $wpdb;

$dry_run = '1' === getenv( 'MIGRATION_DRY_RUN' );
$start   = microtime( true );
$catalog = nvx_aesthetic_treatment_catalog();

printf( "=== NVX Staging-Only Content Migration ===\n" );
printf( "Mode        : %s\n", $dry_run ? 'DRY RUN (no writes)' : 'LIVE' );
printf( "Site        : %s\n", get_option( 'siteurl' ) );
printf( "Started     : %s\n\n", current_time( 'Y-m-d H:i:s' ) );
printf( "Catalog keys loaded : %d\n\n", count( $catalog ) );

// ── Block A: Aesthetic treatment seed normalization ───────────────────────────

echo "--- Block A: Aesthetic Treatment Seed Pages ---\n";

$seed_pages = $wpdb->get_results(
    "SELECT ID, post_name, post_status, post_content, post_excerpt
       FROM {$wpdb->posts}
      WHERE post_status IN ('publish','draft')
        AND post_type = 'page'
        AND post_content LIKE '%nvx-aesthetic-treatment-source%'
      ORDER BY ID ASC",
    ARRAY_A
);

printf( "Seed pages found : %d\n\n", count( $seed_pages ) );

$blocks_ok   = 0;
$blocks_fail = 0;

foreach ( $seed_pages as $page ) {
    $pid = (int) $page['ID'];

    // 1. Resolve treatment key: postmeta first, data-attribute fallback.
    $key = (string) get_post_meta( $pid, '_nvx_aesthetic_treatment_key', true );

    if ( '' === $key ) {
        if ( preg_match( '/data-nvx-treatment=["\']([^"\']+)["\']/', $page['post_content'], $attr ) ) {
            $key = $attr[1];
        }
    }

    if ( '' === $key ) {
        printf( "[SKIP] ID %d /%s/ — no treatment key resolved.\n", $pid, $page['post_name'] );
        $blocks_ok++;
        continue;
    }

    // 2. Key not in catalog → draft the page.
    if ( ! array_key_exists( $key, $catalog ) ) {
        printf(
            "[DRAFT%s] ID %d /%s/ — key \"%s\" absent from catalog.\n",
            $dry_run ? '-DRY' : '     ',
            $pid, $page['post_name'], $key
        );

        if ( ! $dry_run ) {
            $result = $wpdb->update(
                $wpdb->posts,
                [ 'post_status' => 'draft' ],
                [ 'ID' => $pid ],
                [ '%s' ], [ '%d' ]
            );
            if ( false === $result ) {
                fwrite( STDERR, "[ERROR] Could not draft ID {$pid}: " . $wpdb->last_error . "\n" );
                $blocks_fail++;
                continue;
            }
        }

        $blocks_ok++;
        continue;
    }

    // 3. Key is valid → normalize seed marker, excerpt, meta, review status.
    printf(
        "[NORMALIZE%s] ID %d /%s/ — key \"%s\"\n",
        $dry_run ? '-DRY' : '   ',
        $pid, $page['post_name'], $key
    );

    if ( ! $dry_run ) {
        // Normalize the marker: strip any extra attributes appended to the class.
        $new_content = preg_replace(
            '/nvx-aesthetic-treatment-source[^\s"\'>\]]*/',
            'nvx-aesthetic-treatment-source',
            $page['post_content']
        );

        $catalog_entry = $catalog[ $key ];
        $updates = [
            'post_content' => $new_content ?? $page['post_content'],
            'post_excerpt' => $catalog_entry['excerpt'] ?? $page['post_excerpt'],
        ];

        $result = $wpdb->update(
            $wpdb->posts,
            $updates,
            [ 'ID' => $pid ],
            array_fill( 0, count( $updates ), '%s' ),
            [ '%d' ]
        );

        if ( false === $result ) {
            fwrite( STDERR, "[ERROR] Could not normalize ID {$pid}: " . $wpdb->last_error . "\n" );
            $blocks_fail++;
            continue;
        }

        update_post_meta( $pid, '_nvx_aesthetic_treatment_key', $key );
        update_post_meta( $pid, '_nvx_medical_review_status', 'pending' );
    }

    $blocks_ok++;
}

// ── Summary ───────────────────────────────────────────────────────────────────

$elapsed = round( microtime( true ) - $start, 2 );

echo "\n=== STAGING-ONLY SUMMARY ===\n";
printf( "Mode        : %s\n", $dry_run ? 'DRY RUN' : 'LIVE' );
printf( "Seed pages  : %d\n", count( $seed_pages ) );
printf( "Blocks OK   : %d\n", $blocks_ok );
printf( "Blocks FAIL : %d\n", $blocks_fail );
printf( "Elapsed     : %ss\n\n", $elapsed );

if ( 0 === $blocks_fail ) {
    echo "Status: MIGRATION_OK\n";
    exit( 0 );
}

printf( "Status: MIGRATION_FAIL (%d block(s) failed)\n", $blocks_fail );
exit( 1 );
