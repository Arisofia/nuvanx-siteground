<?php
/**
 * audit-content-divergence.php
 *
 * Read-only content divergence audit for production baseline.
 *
 * Scans wp_posts for content that would be modified by hygiene rules.
 * Outputs a complete inventory of divergent records WITHOUT making any changes.
 *
 * Usage (on SiteGround host, from WordPress root):
 *   wp eval-file tools/migrations/audit-content-divergence.php --allow-root
 *
 * This script NEVER writes to the database. It only reports.
 *
 * @package NVX\Migrations
 * @version 1.0.0
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
    fwrite( STDERR, "[audit] Must be run inside a WordPress context (wp eval-file).\n" );
    exit( 1 );
}

// Load hygiene rules
require_once __DIR__ . '/../../lib/nvx-content-hygiene-rules.php';

/**
 * Apply all hygiene rules to a string and return the transformed result.
 *
 * @param string $input Input string.
 * @return string Transformed string.
 */
function nvx_audit_apply_hygiene( string $input ): string {
    $result = $input;

    // Apply plain string replacements
    foreach ( nvx_hygiene_str_reps() as $rule ) {
        $result = str_replace( $rule['from'], $rule['to'], $result );
    }

    // Apply regex replacements
    foreach ( nvx_hygiene_regex_reps() as $rule ) {
        $pattern = '/' . $rule['pattern'] . '/' . $rule['flags'];
        $result  = preg_replace( $pattern, $rule['replacement'], $result ) ?? $result;
    }

    return $result;
}

/**
 * Check if a string would be modified by hygiene rules.
 *
 * @param string $input Input string.
 * @return bool True if rules would modify the string.
 */
function nvx_audit_is_divergent( string $input ): bool {
    return nvx_audit_apply_hygiene( $input ) !== $input;
}

/**
 * Generate a diff summary for a divergent field.
 *
 * @param string $field Field name.
 * @param string $original Original value.
 * @param string $transformed Transformed value.
 * @return string Diff summary.
 */
function nvx_audit_diff_summary( string $field, string $original, string $transformed ): string {
    $summary = "  {$field}:\n";
    $summary .= "    BEFORE: " . substr( $original, 0, 200 ) . ( strlen( $original ) > 200 ? '...' : '' ) . "\n";
    $summary .= "    AFTER:  " . substr( $transformed, 0, 200 ) . ( strlen( $transformed ) > 200 ? '...' : '' ) . "\n";
    return $summary;
}

// ---------------------------------------------------------------------------
// Main audit logic
// ---------------------------------------------------------------------------

$siteurl = (string) get_option( 'siteurl' );
echo "=== NVX Content Divergence Audit ===\n";
echo "Site: {$siteurl}\n";
echo "Timestamp: " . gmdate( 'Y-m-d H:i:s' ) . " UTC\n";
echo "Mode: READ-ONLY (no writes)\n";
echo "\n";

$fields = nvx_hygiene_fields();

$q = new WP_Query(
    array(
        'post_type'              => array( 'page', 'post' ),
        'post_status'            => array( 'publish', 'draft', 'private', 'pending', 'future' ),
        'posts_per_page'         => -1,
        'orderby'                => 'ID',
        'order'                  => 'ASC',
        'no_found_rows'          => true,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
    )
);

$scanned     = 0;
$divergent   = 0;
$divergence_report = array();

foreach ( $q->posts as $post ) {
    if ( ! $post instanceof WP_Post ) {
        continue;
    }
    ++$scanned;

    $divergent_fields = array();
    $field_changes    = array();

    foreach ( $fields as $field ) {
        $original = (string) $post->$field;
        if ( '' === trim( $original ) ) {
            continue;
        }

        $transformed = nvx_audit_apply_hygiene( $original );
        if ( $transformed !== $original ) {
            $divergent_fields[] = $field;
            $field_changes[ $field ] = array(
                'original'    => $original,
                'transformed' => $transformed,
            );
        }
    }

    if ( empty( $divergent_fields ) ) {
        continue;
    }

    ++$divergent;

    $report_line = sprintf(
        "ID=%d type=%s status=%s slug=%s fields=[%s]",
        (int) $post->ID,
        $post->post_type,
        $post->post_status,
        $post->post_name,
        implode( ', ', $divergent_fields )
    );
    $divergence_report[] = array(
        'line' => $report_line,
        'changes' => $field_changes,
    );
}

echo "Scanned: {$scanned} posts/pages\n";
echo "Divergent: {$divergent} posts/pages\n";
echo "\n";

if ( $divergent > 0 ) {
    echo "=== Divergence Details ===\n";
    echo "\n";

    foreach ( $divergence_report as $item ) {
        echo $item['line'] . "\n";
        foreach ( $item['changes'] as $field => $change ) {
            echo nvx_audit_diff_summary( $field, $change['original'], $change['transformed'] );
        }
        echo "\n";
    }
} else {
    echo "No divergence found. Content is already compliant with hygiene rules.\n";
}

echo "\n=== Audit Complete ===\n";
echo "This is a READ-ONLY audit. No changes were made to the database.\n";
