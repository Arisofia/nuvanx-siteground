<?php
/**
 * Enables WordPress core old-slug redirects for hierarchical pages.
 *
 * Core wp_old_slug_redirect() requires the `name` query variable even when a
 * page request was resolved through `pagename`. This bridge supplies only the
 * missing generic query context; the redirect target still comes exclusively
 * from WordPress `_wp_old_slug` metadata.
 *
 * @package NUVANX_Medical
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/** Prepare the query vars consumed by WordPress core old-slug resolution. */
function nvxPrepareOldPageSlugRedirect(): void {
    if ( ! is_404() || '' !== (string) get_query_var( 'name' ) ) {
        return;
    }

    $pagename = trim( (string) get_query_var( 'pagename' ), '/' );
    if ( '' === $pagename ) {
        return;
    }

    set_query_var( 'name', basename( $pagename ) );
}
add_action( 'template_redirect', 'nvxPrepareOldPageSlugRedirect', 9 );
