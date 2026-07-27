<?php
/**
 * Full-site UI governance for repository-managed and legacy CMS pages.
 *
 * Keeps complete HTML page compositions out of WordPress automatic paragraph
 * mutation and loads the final defensive layout contract on every public route.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/** Whether the current singular entry stores a complete NUVANX HTML composition. */
function nvxFullSiteManagedContentUsesRawHtml(): bool {
    if ( ! is_singular() ) {
        return false;
    }

    $post = get_post();
    if ( ! $post instanceof WP_Post ) {
        return false;
    }

    $content = ltrim( (string) $post->post_content );
    if ( '' === $content ) {
        return false;
    }

    if ( 1 === preg_match( '/^<!--\s*NUVANX_(?:GITHUB_MANAGED|SIGNATURE_PHASE|PROTOCOL_PAGE|STRATEGY_PAGE):/i', $content ) ) {
        return true;
    }

    return 1 === preg_match(
        '/^<(?:div|main|article|section)\b[^>]*\bclass=(["\'])[^"\']*\bnvx-[^"\']*\1/i',
        $content
    );
}

/** Prevent wpautop from corrupting complete HTML compositions and click areas. */
function nvxFullSiteDisableAutopForManagedContent(): void {
    if ( is_admin() || ! nvxFullSiteManagedContentUsesRawHtml() ) {
        return;
    }

    remove_filter( 'the_content', 'wpautop' );
    remove_filter( 'the_content', 'shortcode_unautop' );
}
add_action( 'wp', 'nvxFullSiteDisableAutopForManagedContent', 1 );

/**
 * Whether a paragraph HTML string is an empty wpautop artefact.
 *
 * Empty means no visible text after tag stripping and entity decoding, and no
 * meaningful non-br elements. Lone <br>, &nbsp; and whitespace are artefacts.
 */
function nvxFullSiteParagraphIsEmptyAutopArtefact( string $paragraphHtml ): bool {
    if ( preg_match( '/<(img|svg|video|iframe|input|button|a|ul|ol|table|span|strong|em|h[1-6])\b/i', $paragraphHtml ) ) {
        return false;
    }

    $text = wp_strip_all_tags( $paragraphHtml, true );
    $text = html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
    $text = preg_replace( '/\s+/u', '', $text );
    if ( ! is_string( $text ) ) {
        return false;
    }

    // NBSP becomes a real character after entity decode; strip it explicitly.
    $text = str_replace( "\u{00A0}", '', $text );

    return '' === $text;
}

/**
 * Strip wpautop artefacts that are not true empty nodes in the DOM.
 *
 * Scoped to public singular content that still runs through wpautop. Complete
 * HTML compositions (GitHub-managed raw markup) skip this filter.
 *
 * @param mixed $content Filtered post content.
 * @return mixed
 */
function nvxFullSiteStripEmptyAutopParagraphs( $content ) {
    $skip = (
        ! is_string( $content )
        || '' === $content
        || is_admin()
        || wp_doing_ajax()
        || is_feed()
        || ( defined( 'REST_REQUEST' ) && REST_REQUEST )
        || ! is_singular()
        || nvxFullSiteManagedContentUsesRawHtml()
    );
    if ( $skip ) {
        return $content;
    }

    $cleaned = preg_replace_callback(
        '/<p(?:\s[^>]*)?>[\s\S]*?<\/p>/iu',
        static function ( array $match ): string {
            return nvxFullSiteParagraphIsEmptyAutopArtefact( $match[0] ) ? '' : $match[0];
        },
        $content
    );

    return is_string( $cleaned ) ? $cleaned : $content;
}
add_filter( 'the_content', 'nvxFullSiteStripEmptyAutopParagraphs', 12 );

/**
 * Keep the shell's canonical `post-{ID}` anchor unique.
 *
 * Several legacy and generated compositions copied the outer WordPress article
 * identifier into their inner markup. Removing only the nested occurrence keeps
 * fragment compatibility on the shell while restoring valid document IDs.
 *
 * @param mixed $content Filtered post content.
 * @return mixed
 */
function nvxFullSiteRemoveNestedPostId( $content ) {
    if ( ! is_string( $content ) || ! is_singular() ) {
        return $content;
    }

    $post_id = (int) get_queried_object_id();
    if ( $post_id < 1 ) {
        return $content;
    }

    $pattern = '/<(article|div|section)([^>]*)\s+id=(["\'])post-' . preg_quote( (string) $post_id, '/' ) . '\3/i';
    $cleaned = preg_replace( $pattern, '<$1$2', $content );

    return is_string( $cleaned ) ? $cleaned : $content;
}
add_filter( 'the_content', 'nvxFullSiteRemoveNestedPostId', 999 );

/**
 * Resolve a menu item's parent through the canonical ID map.
 *
 * @param array<int, int> $canonicalIds Map of item ID => kept canonical ID.
 * @param int             $parent       Original parent menu item ID.
 */
function nvxFullSiteResolveCanonicalParent( array $canonicalIds, int $parent ): int {
    $guard = 0;
    while ( $parent > 0 && isset( $canonicalIds[ $parent ] ) && $canonicalIds[ $parent ] !== $parent && $guard < 20 ) {
        $parent = (int) $canonicalIds[ $parent ];
        ++$guard;
    }

    return $parent;
}

/**
 * Build a stable signature for primary-menu deduplication.
 */
function nvxFullSiteMenuItemSignature( object $item, int $parent ): string {
    $title_key = sanitize_title( remove_accents( wp_strip_all_tags( (string) ( $item->title ?? '' ) ) ) );
    $url_key   = strtolower( untrailingslashit( (string) ( $item->url ?? '' ) ) );

    return $parent . '|' . $title_key . '|' . $url_key;
}

/**
 * Register the first occurrence of a signature and map duplicate IDs to it.
 *
 * @param object            $item         Menu item.
 * @param string            $signature    Stable signature.
 * @param array<string,int> $seen         Signature => canonical item ID.
 * @param array<int,int>    $canonicalIds Item ID => canonical item ID.
 * @return bool True when the item is new and should be kept.
 */
function nvxFullSiteRegisterCanonicalMenuItem( object $item, string $signature, array &$seen, array &$canonicalIds ): bool {
    $itemId = isset( $item->ID ) ? (int) $item->ID : 0;

    if ( isset( $seen[ $signature ] ) ) {
        if ( $itemId > 0 ) {
            $canonicalIds[ $itemId ] = (int) $seen[ $signature ];
        }
        return false;
    }

    if ( $itemId > 0 ) {
        $seen[ $signature ]      = $itemId;
        $canonicalIds[ $itemId ] = $itemId;
    }

    return true;
}

/**
 * Deduplicate menu objects after all navigation providers have run.
 *
 * Orchestrates parent resolution, signature construction and registration.
 * Stale object caches or late navigation providers can append the same root
 * and subtree a second time; this keeps desktop and mobile output identical.
 *
 * @param mixed    $items Menu item objects.
 * @param stdClass $args  Menu render arguments.
 * @return mixed
 */
function nvxFullSiteDeduplicatePrimaryMenuItems( $items, $args ) {
    if ( ! is_array( $items ) || ! isset( $args->theme_location ) || 'primary' !== $args->theme_location ) {
        return $items;
    }

    $canonicalIds = array();
    $seen         = array();
    $deduplicated = array();

    foreach ( $items as $item ) {
        if ( ! is_object( $item ) ) {
            continue;
        }

        $parent                 = isset( $item->menu_item_parent ) ? (int) $item->menu_item_parent : 0;
        $parent                 = nvxFullSiteResolveCanonicalParent( $canonicalIds, $parent );
        $item->menu_item_parent = (string) $parent;
        $signature              = nvxFullSiteMenuItemSignature( $item, $parent );

        if ( ! nvxFullSiteRegisterCanonicalMenuItem( $item, $signature, $seen, $canonicalIds ) ) {
            continue;
        }

        $deduplicated[] = $item;
    }

    return $deduplicated;
}
add_filter( 'wp_nav_menu_objects', 'nvxFullSiteDeduplicatePrimaryMenuItems', 999, 2 );

/** Load the terminal full-site layout and typography contract. */
function nvxFullSiteUiGovernanceAssets(): void {
    if ( is_admin() ) {
        return;
    }

    $relative = 'assets/css/nvx-full-site-ui-governance.css';
    $absolute = get_template_directory() . '/' . $relative;
    if ( ! is_readable( $absolute ) ) {
        return;
    }

    $version = function_exists( 'nvx_asset_version' )
        ? nvx_asset_version( $relative )
        : (string) filemtime( $absolute );

    wp_enqueue_style(
        'nvx-full-site-ui-governance',
        get_template_directory_uri() . '/' . $relative,
        array( 'nvx-site-coherence', 'nvx-ui-regressions' ),
        $version
    );
}
add_action( 'wp_enqueue_scripts', 'nvxFullSiteUiGovernanceAssets', 120 );

/** Stable browser-audit hook for every public route. */
function nvxFullSiteUiBodyClasses( array $classes ): array {
    if ( ! is_admin() ) {
        $classes[] = 'nvx-full-site-ui-governed';
    }

    return array_values( array_unique( $classes ) );
}
add_filter( 'body_class', 'nvxFullSiteUiBodyClasses' );
