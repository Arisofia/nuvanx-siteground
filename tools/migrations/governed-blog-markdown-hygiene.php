<?php
/**
 * Normalize legacy Markdown only for published journal posts governed by the
 * versioned SEO catalogue. Intended to be included by content-hygiene-shared.php
 * after its guarded write helper has been initialized.
 *
 * @package NVX\Migrations
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
    fwrite( STDERR, "[BLOG MARKDOWN FAIL] WordPress is not loaded.\n" );
    $blocks_fail++;
    return;
}

if ( ! isset( $write_field ) || ! is_callable( $write_field ) ) {
    fwrite( STDERR, "[BLOG MARKDOWN FAIL] Shared guarded write helper is unavailable.\n" );
    $blocks_fail++;
    return;
}

require_once __DIR__ . '/content-normalizer.php';

echo "--- Block C2: Governed Journal Markdown Normalization ---\n";

$catalogFile = get_template_directory() . '/inc/data/seo-blog-post-metadata.json';
$blogErrors = 0;
$blogChecked = 0;
$blogUpdated = 0;

if ( ! is_readable( $catalogFile ) ) {
    fwrite( STDERR, "[BLOG MARKDOWN FAIL] SEO blog catalogue is unavailable.\n" );
    $blogErrors++;
} else {
    $catalog = json_decode( (string) file_get_contents( $catalogFile ), true );
    if ( ! is_array( $catalog ) ) {
        fwrite( STDERR, "[BLOG MARKDOWN FAIL] SEO blog catalogue is invalid JSON.\n" );
        $blogErrors++;
    } else {
        foreach ( array_keys( $catalog ) as $slug ) {
            $row = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT ID, post_content
                       FROM {$wpdb->posts}
                      WHERE post_type = 'post'
                        AND post_status = 'publish'
                        AND post_name = %s
                      ORDER BY ID ASC
                      LIMIT 1",
                    (string) $slug
                ),
                ARRAY_A
            );

            if ( null === $row ) {
                continue;
            }

            ++$blogChecked;
            ++$total_checked;
            $original = (string) $row['post_content'];
            if ( ! nvxNeedsMarkdownNormalization( $original ) ) {
                continue;
            }

            $normalized = nvxNormalizeToHtml( $original );
            $validation = nvxValidateNormalizedContent( $normalized );
            if ( ! $validation['valid'] ) {
                fwrite(
                    STDERR,
                    sprintf(
                        "[BLOG MARKDOWN FAIL] ID=%d slug=%s residual=%s\n",
                        (int) $row['ID'],
                        (string) $slug,
                        implode( '|', $validation['issues'] )
                    )
                );
                ++$blogErrors;
                continue;
            }

            if ( '' === trim( $normalized ) || $normalized === $original ) {
                fwrite( STDERR, sprintf( "[BLOG MARKDOWN FAIL] ID=%d slug=%s produced no safe change.\n", (int) $row['ID'], (string) $slug ) );
                ++$blogErrors;
                continue;
            }

            $prefix = $dry_run ? '[DRY-RUN] Would normalize journal Markdown' : '[NORMALIZED JOURNAL]';
            printf( "%s ID=%d slug=%s\n", $prefix, (int) $row['ID'], (string) $slug );

            if ( ! $write_field( (int) $row['ID'], 'post_content', $normalized ) ) {
                ++$blogErrors;
                continue;
            }

            ++$blogUpdated;
            ++$total_updated;

            if ( ! $dry_run ) {
                clean_post_cache( (int) $row['ID'] );
                $storedPost = get_post( (int) $row['ID'] );
                if ( ! ( $storedPost instanceof WP_Post ) ) {
                    fwrite(
                        STDERR,
                        sprintf(
                            "[BLOG MARKDOWN FAIL] ID=%d slug=%s verification_failed=post_missing_after_write\n",
                            (int) $row['ID'],
                            (string) $slug
                        )
                    );
                    ++$blogErrors;
                    continue;
                }

                $stored = (string) $storedPost->post_content;
                $storedValidation = nvxValidateNormalizedContent( $stored );
                if ( ! $storedValidation['valid'] || nvxNeedsMarkdownNormalization( $stored ) ) {
                    fwrite(
                        STDERR,
                        sprintf(
                            "[BLOG MARKDOWN FAIL] ID=%d slug=%s verification_failed=%s\n",
                            (int) $row['ID'],
                            (string) $slug,
                            implode( '|', $storedValidation['issues'] )
                        )
                    );
                    ++$blogErrors;
                }
            }
        }
    }
}

if ( $blogErrors > 0 ) {
    ++$blocks_fail;
    fwrite( STDERR, sprintf( "[BLOCK FAIL] Governed journal Markdown — errors=%d checked=%d updated=%d\n", $blogErrors, $blogChecked, $blogUpdated ) );
} else {
    ++$blocks_ok;
    printf( "[BLOCK OK] Governed journal Markdown — checked=%d updated=%d\n", $blogChecked, $blogUpdated );
}

echo "\n";
