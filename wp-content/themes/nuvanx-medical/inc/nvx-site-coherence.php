<?php
/**
 * Cross-route visual and conversion coherence.
 *
 * Keeps technology headers, the journal archive, conversion buttons and the
 * valoración landing on one durable design-system contract.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/** @return string[] */
function nvxSiteCoherencePageSlugs(): array {
    return array(
        'endolift-facial-papada-mandibula',
        'endolaser-corporal-grasa-localizada',
        'exion-face',
        'exion-body',
        'exion-fractional',
        'laser-co2-fraccionado-madrid-textura-cicatrices-poro',
        'btl-exilite-ipl-madrid',
        'emfusion',
        'medicina-estetica',
        'labios-acido-hialuronico-madrid',
        'rinomodelacion-sin-cirugia-madrid',
        'ojeras-surco-lagrimal-madrid',
        'bioestimuladores-colageno-madrid',
        'valoracion',
        'soluciones-medicas',
        'medicina-estetica-goya-barrio-salamanca',
        'medicina-estetica-chamberi',
        'clinicas-de-medicina-estetica-nuvanx',
        'por-que-nuvanx',
        'inversion-medicina-estetica',
        'equipo-medico',
        'nosotros',
    );
}

if ( ! function_exists( 'nvx_site_coherence_page_slugs' ) ) {
    function nvx_site_coherence_page_slugs(): array {
        return nvxSiteCoherencePageSlugs();
    }
}

/** Current public page slug. */
function nvxSiteCoherenceCurrentSlug(): string {
    return is_page() ? (string) get_post_field( 'post_name', get_queried_object_id() ) : '';
}

if ( ! function_exists( 'nvx_site_coherence_current_slug' ) ) {
    function nvx_site_coherence_current_slug(): string {
        return nvxSiteCoherenceCurrentSlug();
    }
}

/** Whether the current page uses the shared coherence contract. */
function nvxSiteCoherenceIsTargetPage(): bool {
    if ( ! is_page() ) {
        return false;
    }
    if ( in_array( nvxSiteCoherenceCurrentSlug(), nvxSiteCoherencePageSlugs(), true ) ) {
        return true;
    }
    if ( function_exists( 'nvxIsSedeTemplate' ) && nvxIsSedeTemplate() ) {
        return true;
    }
    if ( function_exists( 'nvxIsClinicsHub' ) && nvxIsClinicsHub() ) {
        return true;
    }
    return false;
}

if ( ! function_exists( 'nvx_site_coherence_is_target_page' ) ) {
    function nvx_site_coherence_is_target_page(): bool {
        return nvxSiteCoherenceIsTargetPage();
    }
}

/** Load the single cross-route stylesheet after the canonical component layers. */
function nvxSiteCoherenceEnqueueAssets(): void {
    $relative = 'assets/css/nvx-site-coherence.css';
    $absolute = get_template_directory() . '/' . $relative;
    if ( ! is_readable( $absolute ) ) {
        return;
    }

    wp_enqueue_style(
        'nvx-site-coherence',
        get_template_directory_uri() . '/' . $relative,
        array( 'nvx-components', 'nvx-canonical-page-hero' ),
        nvx_asset_version( $relative )
    );
}
add_action( 'wp_enqueue_scripts', 'nvxSiteCoherenceEnqueueAssets', 80 );

if ( ! function_exists( 'nvx_site_coherence_enqueue_assets' ) ) {
    function nvx_site_coherence_enqueue_assets(): void {
        nvxSiteCoherenceEnqueueAssets();
    }
}

/** Stable body hooks for scoped presentation and browser acceptance tests. */
function nvxSiteCoherenceBodyClasses( array $classes ): array {
    if ( nvxSiteCoherenceIsTargetPage() ) {
        $classes[] = 'nvx-site-coherent-page';
    }
    if ( 'valoracion' === nvxSiteCoherenceCurrentSlug() ) {
        $classes[] = 'nvx-valoracion-page';
    }
    return array_values( array_unique( $classes ) );
}
add_filter( 'body_class', 'nvxSiteCoherenceBodyClasses' );

if ( ! function_exists( 'nvx_site_coherence_body_classes' ) ) {
    function nvx_site_coherence_body_classes( array $classes ): array {
        return nvxSiteCoherenceBodyClasses( $classes );
    }
}

/** Add a class token without duplicating it. */
function nvxSiteCoherenceAddClass( DOMElement $node, string $class_name ): void {
    $classes   = preg_split( '/\s+/', trim( $node->getAttribute( 'class' ) ) ) ?: array();
    $classes[] = $class_name;
    $node->setAttribute( 'class', implode( ' ', array_values( array_unique( array_filter( $classes ) ) ) ) );
}

if ( ! function_exists( 'nvx_site_coherence_add_class' ) ) {
    function nvx_site_coherence_add_class( DOMElement $node, string $class_name ): void {
        nvxSiteCoherenceAddClass( $node, $class_name );
    }
}

/** Check whether an element owns a class token. */
function nvxSiteCoherenceHasClass( DOMElement $node, string $class_name ): bool {
    $classes = preg_split( '/\s+/', trim( $node->getAttribute( 'class' ) ) ) ?: array();
    return in_array( $class_name, $classes, true );
}

if ( ! function_exists( 'nvx_site_coherence_has_class' ) ) {
    function nvx_site_coherence_has_class( DOMElement $node, string $class_name ): bool {
        return nvxSiteCoherenceHasClass( $node, $class_name );
    }
}

/** Create a canonical valoración header when legacy content has only an H1. */
function nvxSiteCoherenceCreateValoracionHero( DOMDocument $document, DOMElement $root ): ?DOMElement {
    $xpath = new DOMXPath( $document );
    $h1s   = $xpath->query( './/h1', $root );
    $h1    = false !== $h1s ? $h1s->item( 0 ) : null;
    if ( ! $h1 instanceof DOMElement ) {
        return null;
    }

    $hero = $document->createElement( 'section' );
    $hero->setAttribute( 'class', 'nvx-brand-hero nvx-editorial-hero nvx-canonical-page-hero' );
    $hero->setAttribute( 'aria-labelledby', 'nvx-valoracion-h1' );
    $inner = $document->createElement( 'div' );
    $inner->setAttribute( 'class', 'nvx-brand-hero__inner' );
    $copy = $document->createElement( 'div' );
    $copy->setAttribute( 'class', 'nvx-editorial-hero__copy' );
    $eyebrow = $document->createElement( 'p', 'VALORACIÓN MÉDICA · MADRID' );
    $eyebrow->setAttribute( 'class', 'nvx-eyebrow' );
    $copy->appendChild( $eyebrow );
    $h1->setAttribute( 'id', 'nvx-valoracion-h1' );
    nvxSiteCoherenceAddClass( $h1, 'nvx-heading' );
    $copy->appendChild( $h1 );
    $action = $document->createElement( 'p' );
    $link   = $document->createElement( 'a', 'Completar solicitud' );
    $link->setAttribute( 'class', 'nvx-btn nvx-btn--primary' );
    $link->setAttribute( 'href', '#nvx-hubspot-form' );
    $action->appendChild( $link );
    $copy->appendChild( $action );
    $inner->appendChild( $copy );
    $hero->appendChild( $inner );
    $root->insertBefore( $hero, $root->firstChild );
    return $hero;
}

if ( ! function_exists( 'nvx_site_coherence_create_valoracion_hero' ) ) {
    function nvx_site_coherence_create_valoracion_hero( DOMDocument $document, DOMElement $root ): ?DOMElement {
        return nvxSiteCoherenceCreateValoracionHero( $document, $root );
    }
}

/** Locate the first shared hero primitive within page content. */
function nvxSiteCoherenceFindHero( DOMXPath $xpath, DOMElement $root ): ?DOMElement {
    $nodes = $xpath->query(
        './/*[contains(concat(" ", normalize-space(@class), " "), " nvx-canonical-page-hero ") or contains(concat(" ", normalize-space(@class), " "), " nvx-brand-hero ") or contains(concat(" ", normalize-space(@class), " "), " nvx-editorial-hero ") or contains(concat(" ", normalize-space(@class), " "), " nvx-page-hero ") or contains(concat(" ", normalize-space(@class), " "), " nvx-hero-section ") or contains(concat(" ", normalize-space(@class), " "), " nvx-ipl-hero ") or contains(concat(" ", normalize-space(@class), " "), " nvx-strategy-intro ")]',
        $root
    );
    $hero = false !== $nodes ? $nodes->item( 0 ) : null;
    return $hero instanceof DOMElement ? $hero : null;
}

if ( ! function_exists( 'nvx_site_coherence_find_hero' ) ) {
    function nvx_site_coherence_find_hero( DOMXPath $xpath, DOMElement $root ): ?DOMElement {
        return nvxSiteCoherenceFindHero( $xpath, $root );
    }
}

/** Remove visual media from the governed header only. */
function nvxSiteCoherenceRemoveHeroMedia( DOMXPath $xpath, DOMElement $hero ): void {
    $nodes = $xpath->query(
        './/*[contains(concat(" ", normalize-space(@class), " "), " nvx-brand-hero__media ") or contains(concat(" ", normalize-space(@class), " "), " nvx-page-hero__media ") or contains(concat(" ", normalize-space(@class), " "), " nvx-hero__media ") or contains(concat(" ", normalize-space(@class), " "), " nvx-ipl-hero__media ")]',
        $hero
    );
    if ( false === $nodes ) {
        return;
    }
    foreach ( iterator_to_array( $nodes ) as $media ) {
        if ( $media instanceof DOMElement && $media->parentNode ) {
            $media->parentNode->removeChild( $media );
        }
    }
}

if ( ! function_exists( 'nvx_site_coherence_remove_hero_media' ) ) {
    function nvx_site_coherence_remove_hero_media( DOMXPath $xpath, DOMElement $hero ): void {
        nvxSiteCoherenceRemoveHeroMedia( $xpath, $hero );
    }
}

/** Find or create the canonical copy/inner structure. */
function nvxSiteCoherenceNormalizeHeroCopy( DOMDocument $document, DOMXPath $xpath, DOMElement $hero ): DOMElement {
    $nodes = $xpath->query(
        './/*[contains(concat(" ", normalize-space(@class), " "), " nvx-editorial-hero__copy ") or contains(concat(" ", normalize-space(@class), " "), " nvx-editorial-hero__copy-copy ") or contains(concat(" ", normalize-space(@class), " "), " nvx-brand-hero__copy ") or contains(concat(" ", normalize-space(@class), " "), " nvx-page-hero__copy ") or contains(concat(" ", normalize-space(@class), " "), " nvx-hero__copy ") or contains(concat(" ", normalize-space(@class), " "), " nvx-ipl-hero__copy ")]',
        $hero
    );
    $copy = false !== $nodes ? $nodes->item( 0 ) : null;

    if ( ! $copy instanceof DOMElement ) {
        $inner = $document->createElement( 'div' );
        $inner->setAttribute( 'class', 'nvx-brand-hero__inner' );
        $copy = $document->createElement( 'div' );
        $copy->setAttribute( 'class', 'nvx-editorial-hero__copy' );
        foreach ( iterator_to_array( $hero->childNodes ) as $child ) {
            $copy->appendChild( $child );
        }
        $inner->appendChild( $copy );
        $hero->appendChild( $inner );
        return $copy;
    }

    nvxSiteCoherenceAddClass( $copy, 'nvx-editorial-hero__copy' );
    if ( $copy->parentNode instanceof DOMElement && $copy->parentNode->isSameNode( $hero ) ) {
        $inner = $document->createElement( 'div' );
        $inner->setAttribute( 'class', 'nvx-brand-hero__inner' );
        $hero->replaceChild( $inner, $copy );
        $inner->appendChild( $copy );
    }
    return $copy;
}

if ( ! function_exists( 'nvx_site_coherence_normalize_hero_copy' ) ) {
    function nvx_site_coherence_normalize_hero_copy( DOMDocument $document, DOMXPath $xpath, DOMElement $hero ): DOMElement {
        return nvxSiteCoherenceNormalizeHeroCopy( $document, $xpath, $hero );
    }
}

/**
 * Collect paragraph DOM elements matching lead class definitions.
 *
 * @param DOMElement $copy         Container element.
 * @param array      $lead_classes Array of target lead class names.
 * @return array List of matching DOMElement objects.
 */
function nvxSiteCoherenceCollectMovableLeads( DOMElement $copy, array $lead_classes ): array {
    $movable = array();
    foreach ( iterator_to_array( $copy->childNodes ) as $child ) {
        if ( ! $child instanceof DOMElement || 'p' !== strtolower( $child->tagName ) ) {
            continue;
        }
        foreach ( $lead_classes as $class_name ) {
            if ( nvxSiteCoherenceHasClass( $child, $class_name ) ) {
                $movable[] = $child;
                break;
            }
        }
    }
    return $movable;
}

/** Move explanatory lead copy immediately below the governed header. */
function nvxSiteCoherenceMoveLead( DOMDocument $document, DOMElement $hero, DOMElement $copy ): void {
    $lead_classes = array( 'nvx-lead', 'nvx-brand-hero__lead', 'nvx-hero__lead', 'nvx-page-hero__lead', 'nvx-subtitle', 'nvx-hero-subtitle', 'nvx-ipl-lead' );
    $movable      = nvxSiteCoherenceCollectMovableLeads( $copy, $lead_classes );

    $next = $hero->nextSibling;
    while ( $next && XML_TEXT_NODE === $next->nodeType && '' === trim( (string) $next->textContent ) ) {
        $next = $next->nextSibling;
    }
    $has_intro = $next instanceof DOMElement && ( nvxSiteCoherenceHasClass( $next, 'nvx-hero-intro--generated' ) || nvxSiteCoherenceHasClass( $next, 'nvx-hero-intro--coherent' ) );
    if ( array() === $movable || $has_intro || ! $hero->parentNode ) {
        return;
    }

    $intro = $document->createElement( 'section' );
    $intro->setAttribute( 'class', 'nvx-brand-section nvx-hero-intro nvx-hero-intro--coherent' );
    $intro->setAttribute( 'aria-label', 'Introducción clínica' );
    $inner = $document->createElement( 'div' );
    $inner->setAttribute( 'class', 'nvx-brand-section__inner' );
    $readable = $document->createElement( 'div' );
    $readable->setAttribute( 'class', 'nvx-brand-readable nvx-brand-readable--wide' );
    foreach ( $movable as $paragraph ) {
        nvxSiteCoherenceAddClass( $paragraph, 'nvx-brand-body' );
        $readable->appendChild( $paragraph );
    }
    $inner->appendChild( $readable );
    $intro->appendChild( $inner );
    if ( $hero->nextSibling ) {
        $hero->parentNode->insertBefore( $intro, $hero->nextSibling );
    } else {
        $hero->parentNode->appendChild( $intro );
    }
}

if ( ! function_exists( 'nvx_site_coherence_move_lead' ) ) {
    function nvx_site_coherence_move_lead( DOMDocument $document, DOMElement $hero, DOMElement $copy ): void {
        nvxSiteCoherenceMoveLead( $document, $hero, $copy );
    }
}

/** Perform DOM header normalization for non-GitHub-managed templates. */
function nvxSiteCoherencePerformDomHeaderNormalization( string $content ): string {
    $previous_errors = libxml_use_internal_errors( true );
    $document        = new DOMDocument( '1.0', 'UTF-8' );
    $loaded          = $document->loadHTML(
        '<?xml encoding="utf-8" ?><div id="nvx-site-coherence-root">' . $content . '</div>',
        LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
    );

    $root  = $loaded ? $document->getElementById( 'nvx-site-coherence-root' ) : null;
    $xpath = $root instanceof DOMElement ? new DOMXPath( $document ) : null;
    $hero  = $xpath ? nvxSiteCoherenceFindHero( $xpath, $root ) : null;
    if ( ! $hero instanceof DOMElement && $xpath && 'valoracion' === nvxSiteCoherenceCurrentSlug() ) {
        $hero = nvxSiteCoherenceCreateValoracionHero( $document, $root );
    }

    if ( ! $loaded || ! $root instanceof DOMElement || ! $hero instanceof DOMElement ) {
        libxml_clear_errors();
        libxml_use_internal_errors( $previous_errors );
        return $content;
    }

    nvxSiteCoherenceAddClass( $hero, 'nvx-brand-hero' );
    nvxSiteCoherenceAddClass( $hero, 'nvx-editorial-hero' );
    nvxSiteCoherenceAddClass( $hero, 'nvx-canonical-page-hero' );
    nvxSiteCoherenceRemoveHeroMedia( $xpath, $hero );
    $copy = nvxSiteCoherenceNormalizeHeroCopy( $document, $xpath, $hero );
    nvxSiteCoherenceMoveLead( $document, $hero, $copy );

    $output = '';
    foreach ( $root->childNodes as $child ) {
        $output .= $document->saveHTML( $child );
    }
    libxml_clear_errors();
    libxml_use_internal_errors( $previous_errors );
    return '' !== trim( $output ) ? $output : $content;
}

/** Normalize the treatment/valoración page header after page-specific renderers. */
function nvxSiteCoherenceNormalizePageHeader( string $content ): string {
    if ( is_admin() || ! nvxSiteCoherenceIsTargetPage() || '' === trim( $content ) || ! class_exists( 'DOMDocument' ) ) {
        return $content;
    }

    $slug = nvxSiteCoherenceCurrentSlug();
    if ( in_array( $slug, array( 'soluciones-medicas', 'clinicas-de-medicina-estetica-nuvanx', 'valoracion' ), true ) ) {
        return $content;
    }

    return nvxSiteCoherencePerformDomHeaderNormalization( $content );
}
add_filter( 'the_content', 'nvxSiteCoherenceNormalizePageHeader', 150 );

if ( ! function_exists( 'nvx_site_coherence_normalize_page_header' ) ) {
    function nvx_site_coherence_normalize_page_header( string $content ): string {
        return nvxSiteCoherenceNormalizePageHeader( $content );
    }
}

/** Give modal buttons an ordinary link fallback when JavaScript is unavailable. */
function nvxSiteCoherenceAddValoracionFallbacks( string $content ): string {
    if ( false === strpos( $content, 'nvx-open-valoracion-modal' ) || false === stripos( $content, '<button' ) ) {
        return $content;
    }

    $result = preg_replace_callback(
        '/<button\b([^>]*\bclass=["\'][^"\']*\bnvx-open-valoracion-modal\b[^"\']*["\'][^>]*)>([\s\S]*?)<\/button>/iu',
        static function ( array $match ): string {
            $attributes = (string) preg_replace( '/\s+type=["\'][^"\']*["\']/iu', '', $match[1] );
            $attributes = (string) preg_replace( '/\s+href=["\'][^"\']*["\']/iu', '', $attributes );
            return '<a href="' . esc_url( home_url( '/madrid/valoracion/' ) ) . '"' . $attributes . '>' . $match[2] . '</a>';
        },
        $content
    );
    return is_string( $result ) ? $result : $content;
}
add_filter( 'the_content', 'nvxSiteCoherenceAddValoracionFallbacks', 220 );

if ( ! function_exists( 'nvx_site_coherence_add_valoracion_fallbacks' ) ) {
    function nvx_site_coherence_add_valoracion_fallbacks( string $content ): string {
        return nvxSiteCoherenceAddValoracionFallbacks( $content );
    }
}

/** Move the shared modal before footer scripts so its DOM exists when nvx-main runs. */
function nvxSiteCoherenceReorderValoracionModal(): void {
    if ( function_exists( 'nvx_valoracion_modal_render' ) ) {
        remove_action( 'wp_footer', 'nvx_valoracion_modal_render', 25 );
        add_action( 'wp_footer', 'nvx_valoracion_modal_render', 5 );
    }
}
add_action( 'wp', 'nvxSiteCoherenceReorderValoracionModal', 20 );

if ( ! function_exists( 'nvx_site_coherence_reorder_valoracion_modal' ) ) {
    function nvx_site_coherence_reorder_valoracion_modal(): void {
        nvxSiteCoherenceReorderValoracionModal();
    }
}

/** Provide nvx-main with the modal runtime contract and fallback URL. */
function nvxSiteCoherenceConfigureValoracionModal(): void {
    if ( ! wp_script_is( 'nvx-main', 'registered' ) && ! wp_script_is( 'nvx-main', 'enqueued' ) ) {
        return;
    }
    $payload = array( 'enabled' => true, 'pageUrl' => home_url( '/madrid/valoracion/' ) );
    wp_add_inline_script(
        'nvx-main',
        'window.nvxValoracionModal = Object.assign({}, window.nvxValoracionModal || {}, ' . wp_json_encode( $payload ) . ');',
        'before'
    );
}
add_action( 'wp_enqueue_scripts', 'nvxSiteCoherenceConfigureValoracionModal', 100 );

if ( ! function_exists( 'nvx_site_coherence_configure_valoracion_modal' ) ) {
    function nvx_site_coherence_configure_valoracion_modal(): void {
        nvxSiteCoherenceConfigureValoracionModal();
    }
}
