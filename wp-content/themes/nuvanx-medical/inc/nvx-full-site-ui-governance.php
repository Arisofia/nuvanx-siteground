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

    $pattern = '/\s+id=(["\'])post-' . preg_quote( (string) $post_id, '/' ) . '\1/i';
    $cleaned = preg_replace( $pattern, '', $content );

    return is_string( $cleaned ) ? $cleaned : $content;
}
add_filter( 'the_content', 'nvxFullSiteRemoveNestedPostId', 999 );

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
