<?php
/**
 * Integraciones de infraestructura del tema.
 *
 * Schema canónico de clínicas: únicamente vía nvx-structured-data.php (Yoast graph).
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/** Canonical public path for the ojeras / surco lagrimal treatment page. */
if ( ! defined( 'NVX_PATH_OJERAS_SURCO_LAGRIMAL' ) ) {
    define( 'NVX_PATH_OJERAS_SURCO_LAGRIMAL', '/ojeras-surco-lagrimal-madrid/' );
}

require_once __DIR__ . '/nvx-environment-flags.php';
require_once __DIR__ . '/nvx-visual-system.php';
require_once __DIR__ . '/nvx-external-visual-closure.php';
require_once __DIR__ . '/nvx-aesthetic-treatment-pages.php';
require_once __DIR__ . '/nvx-strategy-pages.php';
require_once __DIR__ . '/nvx-conversion-events.php';
require_once __DIR__ . '/nvx-aesthetic-hub-governance.php';

/** Prevent strategy pages from being written during normal web requests. */
remove_action( 'init', 'nvx_strategy_seed_staging2_pages', 31 );

/**
 * Returns the governed retired/deferred-page contract shared by the
 * production-readiness and canonical-route migrations.
 *
 * @return array<string,array{status:string,target:string}>
 */
function nvx_production_readiness_governed_pages(): array {
    return array(
        'liposculpt-air' => array(
            'status' => 'trash',
            'target' => '/remodelacion-corporal-laser-madrid/',
        ),
        'v-lift-awake' => array(
            'status' => 'trash',
            'target' => '/protocolos-signature/',
        ),
        'dr-javier-rivera-tejeda' => array(
            'status' => 'trash',
            'target' => '/equipo-medico/',
        ),
        'tratamientos' => array(
            'status' => 'trash',
            'target' => '/soluciones-medicas/',
        ),
        'eye-frame-rejuvenecimiento-mirada-madrid' => array(
            'status' => 'draft',
            'target' => NVX_PATH_OJERAS_SURCO_LAGRIMAL,
        ),
    );
}

/**
 * Slugs that are intentionally retired and must never be canonically guessed.
 *
 * @return string[]
 */
function nvx_retired_legacy_route_slugs(): array {
    return array(
        'mas-informacion-sobre-las-cookies',
        'politica-de-cookies',
        'politica-de-privacidad',
        'tratamiento-retirado',
        'tratamientos',
        'liposculpt-air',
        'v-lift-awake',
        'dr-javier-rivera-tejeda',
        'eye-frame-rejuvenecimiento-mirada-madrid',
        'eye-frame',
    );
}

/** Determines whether the current request targets an intentionally retired route. */
function nvx_is_retired_legacy_route_request(): bool {
    if ( is_admin() || ! isset( $_SERVER['REQUEST_URI'] ) ) {
        return false;
    }

    $request_uri  = wp_unslash( (string) $_SERVER['REQUEST_URI'] );
    $request_path = (string) wp_parse_url( $request_uri, PHP_URL_PATH );
    $slug         = trim( $request_path, '/' );

    return '' !== $slug && in_array( $slug, nvx_retired_legacy_route_slugs(), true );
}

/** Prevent WordPress from guessing a replacement permalink for retired routes. */
function nvx_disable_retired_legacy_route_redirect( $redirect_url ) {
    return nvx_is_retired_legacy_route_request() ? false : $redirect_url;
}
add_filter( 'redirect_canonical', 'nvx_disable_retired_legacy_route_redirect', -999999, 1 );

/**
 * Serve an explicit 410 response for retired routes before canonical redirects.
 */
function nvx_serve_retired_legacy_route(): void {
    if ( ! nvx_is_retired_legacy_route_request() ) {
        return;
    }

    remove_action( 'template_redirect', 'redirect_canonical' );

    global $wp_query;
    if ( $wp_query instanceof WP_Query ) {
        $wp_query->set_404();
    }

    status_header( 410 );
    nocache_headers();
    if ( ! headers_sent() ) {
        header( 'X-Robots-Tag: noindex, nofollow', true );
        header( 'X-NUVANX-Retired-Route: 1', true );
    }

    $template = get_404_template();
    if ( is_string( $template ) && '' !== $template ) {
        include $template;
        exit;
    }

    wp_die(
        esc_html__( 'Esta página ya no está disponible.', 'nuvanx-medical' ),
        esc_html__( 'Contenido retirado', 'nuvanx-medical' ),
        array( 'response' => 410 )
    );
}
add_action( 'template_redirect', 'nvx_serve_retired_legacy_route', -1000000 );

/** Determines whether the current request is for the Goya clinic page. */
function nvx_theme_is_goya_page(): bool {
    if ( is_admin() ) {
        return false;
    }
    if ( is_page( 1537 ) ) {
        return true;
    }
    $path = isset( $_SERVER['REQUEST_URI'] ) ? strtok( (string) $_SERVER['REQUEST_URI'], '?' ) : '';
    return '/' . trim( $path, '/' ) . '/' === '/clinicas-de-medicina-estetica-nuvanx/medicina-estetica-goya-barrio-salamanca/';
}

add_filter(
    'redirect_canonical',
    function ( $redirect_url ) {
        return nvx_theme_is_goya_page() ? false : $redirect_url;
    },
    9999,
    1
);

add_action(
    'template_redirect',
    function () {
        if ( nvx_theme_is_goya_page() ) {
            remove_action( 'template_redirect', 'redirect_canonical' );
        }
    },
    -999999
);

/** Whether an HTML fragment starts with an application/ld+json script. */
function nvxThemeIsJsonLdScript( string $script ): bool {
    if ( ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
        return false;
    }

    $processor = new WP_HTML_Tag_Processor( $script );
    if ( ! $processor->next_tag( 'SCRIPT' ) ) {
        return false;
    }

    $type = $processor->get_attribute( 'type' );
    return is_string( $type ) && 'application/ld+json' === strtolower( trim( $type ) );
}

/** Whether a JSON-LD script contains Schema.org graph data. */
function nvxThemeIsSchemaJsonLdScript( string $script ): bool {
    return false !== stripos( $script, 'schema.org' )
        || false !== stripos( $script, '@graph' )
        || false !== stripos( $script, '"@type"' );
}

/**
 * Remove non-Yoast Schema.org scripts with a bounded linear scan.
 *
 * @param string $html Public document HTML.
 */
function nvxThemeNormalizeSchemaScripts( string $html ): string {
    $output = '';
    $cursor = 0;
    $length = strlen( $html );

    while ( $cursor < $length ) {
        $start = stripos( $html, '<script', $cursor );
        if ( false === $start ) {
            break;
        }

        $nameEnd = $start + 7;
        $next    = $nameEnd < $length ? $html[ $nameEnd ] : '';
        if ( '' !== $next && '>' !== $next && ! ctype_space( $next ) ) {
            $output .= substr( $html, $cursor, $nameEnd - $cursor );
            $cursor = $nameEnd;
            continue;
        }

        $close = stripos( $html, '</script>', $nameEnd );
        if ( false === $close ) {
            break;
        }

        $end    = $close + 9;
        $script = substr( $html, $start, $end - $start );
        $output .= substr( $html, $cursor, $start - $cursor );

        $isJsonLd = nvxThemeIsJsonLdScript( $script );
        $isYoast  = false !== stripos( $script, 'yoast-schema-graph' );
        $isSchema = nvxThemeIsSchemaJsonLdScript( $script );
        if ( ! $isJsonLd || $isYoast || ! $isSchema ) {
            $output .= $script;
        }

        $cursor = $end;
    }

    return $output . substr( $html, $cursor );
}

/**
 * Normalize public document markup and keep a single Yoast schema.org graph.
 *
 * Removes non-Yoast Schema.org application/ld+json blocks (embedded BlogPosting,
 * legacy MedicalClinic, FAQ dumps) while preserving the canonical yoast-schema-graph.
 */
function nvx_theme_normalize_public_document( string $html ): string {
    $html = (string) preg_replace(
        '/<meta\s+name=["\']viewport["\'][^>]*>/i',
        '<meta name="viewport" content="width=device-width, initial-scale=1.0" />',
        $html,
        1
    );

    $html = str_ireplace(
        array( 'NUVANX Couture Sculpt™', 'NUVANX Contour Sculpt™', 'Couture Sculpt™', 'Contour Sculpt™' ),
        'NUVANX Contour Architecture™',
        $html
    );
    $html = str_replace(
        array( '/eye-frame-rejuvenecimiento-mirada-madrid/', '/eye-frame/' ),
        array( NVX_PATH_OJERAS_SURCO_LAGRIMAL, NVX_PATH_OJERAS_SURCO_LAGRIMAL ),
        $html
    );
    $html = str_ireplace(
        array( 'NUVANX Eye Frame™', 'Eye Frame™' ),
        'Ojeras y surco lagrimal',
        $html
    );

    if ( false !== stripos( $html, 'ld+json' ) ) {
        $html = nvxThemeNormalizeSchemaScripts( $html );
    }

    return str_replace( '<!-- NUVANX_HOME_UNIFIED_FAQ_SCHEMA -->', '', $html );
}

add_action(
    'template_redirect',
    function () {
        if ( ! is_admin() ) {
            ob_start( 'nvx_theme_normalize_public_document' );
        }
    },
    0
);

require_once __DIR__ . '/nvx-structured-data.php';
require_once __DIR__ . '/nvx-aesthetic-treatment-schema.php';
require_once __DIR__ . '/nvx-page-hygiene.php';
require_once __DIR__ . '/nvx-p0-publication-guard.php';
require_once __DIR__ . '/nvx-seo-metadata.php';
require_once __DIR__ . '/nvx-editorial-seo-extension.php';
require_once __DIR__ . '/nvx-seo-production-readiness.php';
require_once __DIR__ . '/nvx-contacto-audit-fixes.php';
require_once __DIR__ . '/nvx-faq-content-v2.php';
require_once __DIR__ . '/nvx-medical-review.php';
require_once __DIR__ . '/nvx-publication-safeguards.php';
require_once __DIR__ . '/nvx-btl-clinical-governance.php';
require_once __DIR__ . '/nvx-clinical-language.php';
require_once __DIR__ . '/nvx-blog-system.php';
require_once __DIR__ . '/nvx-mobile-hero-hierarchy.php';
require_once __DIR__ . '/nvx-site-coherence.php';
require_once __DIR__ . '/nvx-hero-layout-coherence.php';
require_once __DIR__ . '/nvx-full-site-ui-governance.php';
require_once __DIR__ . '/nvx-protocol-hub.php';
require_once __DIR__ . '/nvx-protocol-pages.php';
require_once __DIR__ . '/nvx-signature-phase-pages.php';

/* GEO · Hreflang es-ES */
add_action(
    'wp_head',
    function (): void {
        $current_url = is_front_page() ? home_url( '/' ) : home_url( wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ) );
        echo '<link rel="alternate" hreflang="es-ES" href="' . esc_url( $current_url ) . '" />' . "\n";
        echo '<link rel="alternate" hreflang="x-default" href="' . esc_url( $current_url ) . '" />' . "\n";
    },
    1
);

/* Security headers */
add_action(
    'send_headers',
    function (): void {
        if ( headers_sent() ) {
            return;
        }
        header( 'X-Content-Type-Options: nosniff' );
        header( 'X-Frame-Options: SAMEORIGIN' );
        header( 'Referrer-Policy: strict-origin-when-cross-origin' );
        header( 'Permissions-Policy: camera=(), microphone=(), geolocation=()' );
    }
);

/* Meta Pixel · single-owner (dequeue SiteGround facebook-signal) */
add_action(
    'wp_enqueue_scripts',
    function (): void {
        wp_dequeue_script( 'siteground-facebook-signal' );
        wp_deregister_script( 'siteground-facebook-signal' );
    },
    100
);

add_filter(
    'script_loader_tag',
    function ( string $tag, string $handle ): string {
        if ( str_contains( $handle, 'facebook-signal' ) || str_contains( $tag, 'facebook-signal' ) ) {
            return '';
        }
        return $tag;
    },
    10,
    2
);
