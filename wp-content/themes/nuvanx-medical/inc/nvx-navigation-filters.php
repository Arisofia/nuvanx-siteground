<?php
/**
 * Navigation and menu filters.
 *
 * The WordPress menu assigned to the `primary` location is the source of truth.
 * Theme code provides presentation classes, removes unpublished page targets and
 * supplies a published-route-aware fallback when no menu has been assigned.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/** Build one navigation leaf. */
function nvxNavigationItem( string $label, array $slugs ): array {
    return compact( 'label', 'slugs' );
}

/**
 * Defines the canonical fallback navigation structure for the primary menu.
 *
 * The blueprint is used when no database-managed menu is available. Page targets
 * are resolved at runtime so unpublished routes can be omitted.
 *
 * @return array<int,array<string,mixed>> The filtered fallback navigation blueprint.
 */
if ( ! function_exists( 'nvxNavigationPrimaryBlueprint' ) ) {
    function nvxNavigationPrimaryBlueprint(): array {
    return apply_filters(
        'nvx_navigation_primary_blueprint',
        array(
            array(
                'label' => __( 'Inicio', 'nuvanx-medical' ),
                'url'   => home_url( '/' ),
            ),
            array(
                'label'    => __( 'Soluciones médicas', 'nuvanx-medical' ),
                'slugs'    => array( 'soluciones', 'soluciones-medicas' ),
                'mega'     => true,
                'children' => array(
                    array(
                        'label'    => 'Rostro',
                        'slugs'    => array( 'soluciones-medicas/rostro' ),
                        'children' => array(
                            nvxNavigationItem( 'Tercio Superior', array( 'soluciones-medicas/rostro/tercio-superior' ) ),
                            nvxNavigationItem( 'Mirada', array( 'soluciones-medicas/rostro/mirada' ) ),
                            nvxNavigationItem( 'Tercio Medio', array( 'soluciones-medicas/rostro/tercio-medio' ) ),
                            nvxNavigationItem( 'Labios', array( 'soluciones-medicas/rostro/labios' ) ),
                            nvxNavigationItem( 'Tercio Inferior', array( 'soluciones-medicas/rostro/tercio-inferior' ) ),
                        ),
                    ),
                    array(
                        'label'    => 'Cuerpo',
                        'slugs'    => array( 'soluciones-medicas/cuerpo' ),
                        'children' => array(
                            nvxNavigationItem( 'Abdomen y Flancos', array( 'soluciones-medicas/cuerpo/abdomen-y-flancos' ) ),
                            nvxNavigationItem( 'Brazos y Espalda', array( 'soluciones-medicas/cuerpo/brazos-y-espalda' ) ),
                            nvxNavigationItem( 'Tren Inferior', array( 'soluciones-medicas/cuerpo/tren-inferior' ) ),
                        ),
                    ),
                ),
            ),
            array(
                'label'    => __( 'Protocolos Signature', 'nuvanx-medical' ),
                'slugs'    => array( 'protocolos-signature' ),
                'mega'     => true,
                'children' => array(
                    array(
                        'label'    => 'NUVANX Contour Architecture™',
                        'slugs'    => array( 'remodelacion-corporal-laser-madrid' ),
                        'children' => array(
                            nvxNavigationItem( 'Abdomen y flancos', array( 'grasa-localizada-abdomen-flancos-madrid' ) ),
                            nvxNavigationItem( 'Cintura', array( 'cintura' ) ),
                            nvxNavigationItem( 'Brazos', array( 'flacidez-grasa-localizada-brazos-madrid' ) ),
                            nvxNavigationItem( 'Espalda y zona del sujetador', array( 'grasa-espalda-zona-sujetador-madrid' ) ),
                            nvxNavigationItem( 'Muslos internos', array( 'flacidez-muslos-internos-subgluteo-madrid' ) ),
                            nvxNavigationItem( 'Cara externa de muslos', array( 'cara-externa-muslos' ) ),
                            nvxNavigationItem( 'Región subglútea', array( 'region-subglutea' ) ),
                            nvxNavigationItem( 'Rodillas', array( 'tratamiento-rodillas-grasa-flacidez-madrid' ) ),
                            nvxNavigationItem( 'Contorno masculino', array( 'contorno-corporal-masculino-madrid' ) ),
                        ),
                    ),
                    array(
                        'label'    => 'NUVANX Post-Maternity Contour™',
                        'slugs'    => array( 'tratamiento-postparto-abdomen-contorno-corporal-madrid' ),
                        'children' => array(
                            nvxNavigationItem( 'Abdomen posgestacional', array( 'abdomen-posgestacional' ) ),
                            nvxNavigationItem( 'Flancos y espalda', array( 'flancos-espalda-posparto' ) ),
                            nvxNavigationItem( 'Laxitud cutánea', array( 'laxitud-cutanea-posparto' ) ),
                            nvxNavigationItem( 'Estrías y textura', array( 'estrias-textura-posparto' ) ),
                            nvxNavigationItem( 'Cicatriz de cesárea', array( 'cicatriz-cesarea' ) ),
                            nvxNavigationItem( 'Límites frente a diástasis', array( 'diastasis-hernia-limites' ) ),
                        ),
                    ),
                    array(
                        'label'    => 'NUVANX Profile Definition™',
                        'slugs'    => array( 'papada-definicion-mandibular-madrid' ),
                        'children' => array(
                            nvxNavigationItem( 'Papada', array( 'papada' ) ),
                            nvxNavigationItem( 'Mandíbula', array( 'mandibula' ) ),
                            nvxNavigationItem( 'Cuello', array( 'cuello' ) ),
                            nvxNavigationItem( 'Mentón y proporción facial', array( 'menton-proporcion' ) ),
                            nvxNavigationItem( 'Contorno facial masculino', array( 'contorno-facial-masculino' ) ),
                        ),
                    ),
                    array(
                        'label'    => 'NUVANX Eye Frame™',
                        'slugs'    => array( 'eye-frame-rejuvenecimiento-mirada-madrid' ),
                        'children' => array(
                            nvxNavigationItem( 'Surco lagrimal', array( 'surco-lagrimal' ) ),
                            nvxNavigationItem( 'Calidad cutánea periocular', array( 'calidad-cutanea-periocular' ) ),
                            nvxNavigationItem( 'Pigmentación', array( 'pigmentacion-periocular' ) ),
                            nvxNavigationItem( 'Bolsas, edema y límites', array( 'bolsas-edema-limites' ) ),
                        ),
                    ),
                    nvxNavigationItem( 'NUVANX Skin Architecture™', array( 'calidad-piel-firmeza-luminosidad-madrid' ) ),
                    nvxNavigationItem( 'NUVANX Surface Renewal™', array( 'cicatrices-acne-poros-textura-madrid' ) ),
                    nvxNavigationItem( 'NUVANX Tone Correction™', array( 'manchas-rojeces-fotorejuvenecimiento-ipl-madrid' ) ),
                ),
            ),
            array(
                'label'    => __( 'Tecnología', 'nuvanx-medical' ),
                'slugs'    => array( 'medicina-estetica-laser' ),
                'children' => array(
                    nvxNavigationItem( 'Endolift® facial', array( 'endolift-facial-papada-mandibula' ) ),
                    nvxNavigationItem( 'Endoláser corporal', array( 'endolaser-corporal-grasa-localizada' ) ),
                    nvxNavigationItem( 'EXION® Face', array( 'exion-face' ) ),
                    nvxNavigationItem( 'EXION® Body', array( 'exion-body' ) ),
                    nvxNavigationItem( 'EXION® Fractional RF', array( 'exion-fractional' ) ),
                    nvxNavigationItem( 'Láser CO₂ fraccionado', array( 'laser-co2-fraccionado-madrid-textura-cicatrices-poro' ) ),
                    nvxNavigationItem( 'BTL EXILITE™ IPL', array( 'btl-exilite-ipl-madrid' ) ),
                    nvxNavigationItem( 'EMFUSION®', array( 'emfusion' ) ),
                    array(
                        'label'    => 'Medicina inyectable',
                        'slugs'    => array( 'medicina-estetica' ),
                        'children' => array(
                            nvxNavigationItem( 'Ácido hialurónico en labios', array( 'labios-acido-hialuronico-madrid' ) ),
                            nvxNavigationItem( 'Rinomodelación sin cirugía', array( 'rinomodelacion-sin-cirugia-madrid' ) ),
                            nvxNavigationItem( 'Tratamiento de ojeras', array( 'ojeras-surco-lagrimal-madrid' ) ),
                            nvxNavigationItem( 'Bioestimuladores de colágeno', array( 'bioestimuladores-colageno-madrid' ) ),
                        ),
                    ),
                ),
            ),
            nvxNavigationItem( __( 'Casos clínicos', 'nuvanx-medical' ), array( 'casos-clinicos' ) ),
            nvxNavigationItem( __( 'Equipo médico', 'nuvanx-medical' ), array( 'equipo-medico' ) ),
            array(
                'label'    => __( 'Clínicas', 'nuvanx-medical' ),
                'slugs'    => array( 'clinicas-de-medicina-estetica-nuvanx' ),
                'children' => array(
                    nvxNavigationItem( 'Chamberí', array( 'medicina-estetica-chamberi', 'clinica-medicina-estetica-chamberi' ) ),
                    nvxNavigationItem( 'Salamanca–Goya', array( 'clinicas-de-medicina-estetica-nuvanx/medicina-estetica-goya-barrio-salamanca', 'medicina-estetica-goya-barrio-salamanca' ) ),
                ),
            ),
            nvxNavigationItem( __( 'Journal', 'nuvanx-medical' ), array( 'blog' ) ),
            nvxNavigationItem( __( 'Contacto', 'nuvanx-medical' ), array( 'contacto' ) ),
        )
    );
}
}

if ( ! function_exists( 'nvx_navigation_primary_blueprint' ) ) {
    function nvx_navigation_primary_blueprint(): array {
        return nvxNavigationPrimaryBlueprint();
    }
}

/**
 * Resolve the first published page among a list of candidate slugs.
 *
 * @param string[] $slugs Candidate page paths.
 * @return array{url:string,page_id:int}|null
 */
function nvxNavigationResolvePublishedPage( array $slugs ): ?array {
    static $cache = array();

    $key = implode( '|', array_map( 'strval', $slugs ) );
    if ( array_key_exists( $key, $cache ) ) {
        return $cache[ $key ];
    }

    foreach ( $slugs as $candidate ) {
        $slug = trim( (string) $candidate, '/' );
        if ( '' === $slug ) {
            continue;
        }

        $page = get_page_by_path( $slug, OBJECT, 'page' );
        if ( ! $page instanceof WP_Post || 'publish' !== get_post_status( $page ) ) {
            continue;
        }

        $url = get_permalink( $page );
        if ( is_string( $url ) && '' !== trim( $url ) ) {
            $cache[ $key ] = array(
                'url'     => $url,
                'page_id' => (int) $page->ID,
            );
            return $cache[ $key ];
        }
    }

    $cache[ $key ] = null;
    return null;
}

/** Resolve child nodes for a blueprint item. */
function nvxNavigationResolveNodeChildren( array $raw_children ): array {
    $children = array();
    foreach ( $raw_children as $child ) {
        if ( is_array( $child ) ) {
            $resolved = nvxNavigationResolveBlueprintNode( $child );
            if ( is_array( $resolved ) ) {
                $children[] = $resolved;
            }
        }
    }
    return $children;
}

/**
 * Resolves a fallback blueprint node into a renderable navigation item.
 *
 * @param array<string,mixed> $node Blueprint node with an optional label, URL, slugs, mega-menu flag, and child nodes.
 * @return array<string,mixed>|null Resolved navigation item, or null when the node has no label or destination.
 */
function nvxNavigationResolveBlueprintNode( array $node ): ?array {
    $raw_children = isset( $node['children'] ) && is_array( $node['children'] ) ? $node['children'] : array();
    $children     = nvxNavigationResolveNodeChildren( $raw_children );

    $url = isset( $node['url'] ) ? trim( (string) $node['url'] ) : '';
    if ( '' === $url ) {
        $destination = nvxNavigationResolvePublishedPage(
            isset( $node['slugs'] ) && is_array( $node['slugs'] ) ? $node['slugs'] : array()
        );
        $url = is_array( $destination ) ? $destination['url'] : '';
    }

    if ( '' === $url && array() !== $children ) {
        $url = (string) $children[0]['url'];
    }

    $label = isset( $node['label'] ) ? trim( (string) $node['label'] ) : '';
    if ( '' === $label || ( '' === $url && array() === $children ) ) {
        return null;
    }

    return array(
        'label'    => $label,
        'url'      => $url,
        'mega'     => ! empty( $node['mega'] ),
        'children' => $children,
    );
}

/**
 * Resolve the fallback architecture without exposing drafts or missing pages.
 *
 * @return array<int,array<string,mixed>>
 */
if ( ! function_exists( 'nvxNavigationResolvedFallback' ) ) {
    function nvxNavigationResolvedFallback(): array {
        $items = array();
        foreach ( nvxNavigationPrimaryBlueprint() as $node ) {
            if ( ! is_array( $node ) ) {
                continue;
            }
            $resolved = nvxNavigationResolveBlueprintNode( $node );
            if ( is_array( $resolved ) ) {
                $items[] = $resolved;
            }
        }
        return $items;
    }
}

if ( ! function_exists( 'nvx_navigation_resolved_fallback' ) ) {
    function nvx_navigation_resolved_fallback(): array {
        return nvxNavigationResolvedFallback();
    }
}

/**
 * Render fallback menu items recursively.
 *
 * @param array<int,array<string,mixed>> $items Menu items.
 * @param int                            $depth Current depth.
 */
function nvxNavigationRenderFallbackItems( array $items, int $depth = 0 ): string {
    $html = '';
    foreach ( $items as $item ) {
        $children     = isset( $item['children'] ) && is_array( $item['children'] ) ? $item['children'] : array();
        $has_children = array() !== $children;
        $classes      = array( 'nvx-nav__item', 'nvx-nav__item--depth-' . $depth );

        if ( $has_children ) {
            $classes[] = 'menu-item-has-children';
        }
        if ( 0 === $depth && ! empty( $item['mega'] ) ) {
            $classes[] = 'nvx-nav__item--mega';
        }

        $link_attributes = $has_children ? ' aria-haspopup="true" data-nvx-menu-parent="true"' : '';
        $html           .= '<li class="' . esc_attr( implode( ' ', $classes ) ) . '">';
        $html           .= '<a class="nvx-nav__link" data-nvx-menu-depth="' . esc_attr( (string) $depth ) . '" href="' . esc_url( (string) $item['url'] ) . '"' . $link_attributes . '>' . esc_html( (string) $item['label'] ) . '</a>';

        if ( $has_children ) {
            $html .= '<ul class="sub-menu">' . nvxNavigationRenderFallbackItems( $children, $depth + 1 ) . '</ul>';
        }
        $html .= '</li>';
    }
    return $html;
}

/**
 * Renders the published primary navigation fallback.
 *
 * @param array<string,mixed> $args wp_nav_menu arguments used for the menu class, menu ID, and output mode.
 * @return string|null The rendered navigation HTML when output is disabled, or null after echoing it.
 */
function nvxNavigationPrimaryFallback( array $args = array() ) {
    $menu_class = isset( $args['menu_class'] ) && '' !== trim( (string) $args['menu_class'] )
        ? trim( (string) $args['menu_class'] )
        : 'nvx-nav__list';
    $menu_id = isset( $args['menu_id'] ) && '' !== trim( (string) $args['menu_id'] )
        ? ' id="' . esc_attr( trim( (string) $args['menu_id'] ) ) . '"'
        : '';

    $html = '<ul' . $menu_id . ' class="' . esc_attr( $menu_class ) . '">';
    $html .= nvxNavigationRenderFallbackItems( nvxNavigationResolvedFallback() );
    $html .= '</ul>';

    if ( ! array_key_exists( 'echo', $args ) || $args['echo'] ) {
        echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped during assembly.
        return null;
    }

    return $html;
}

/**
 * Configures fallback, depth, and spacing settings for the primary navigation menu.
 *
 * @param array<string,mixed> $args Navigation menu arguments.
 * @return array<string,mixed> Updated arguments for the primary menu, or the original arguments for other locations.
 */
function nvxNavigationFilterMenuArgs( array $args ): array {
    if ( 'primary' !== ( $args['theme_location'] ?? '' ) ) {
        return $args;
    }

    $args['fallback_cb'] = 'nvxNavigationPrimaryFallback';
    $args['depth']       = 3;
    $args['item_spacing'] = 'discard';
    return $args;
}
add_filter( 'wp_nav_menu_args', 'nvxNavigationFilterMenuArgs', 20 );

function nvxNavigationIsItemDirectlyBlocked( $item ): bool {
    $item_id   = isset( $item->ID ) ? (int) $item->ID : 0;
    $object_id = isset( $item->object_id ) ? (int) $item->object_id : 0;
    $object    = isset( $item->object ) ? (string) $item->object : '';

    return $item_id > 0 && 'page' === $object && $object_id > 0 && 'publish' !== get_post_status( $object_id );
}

function nvxNavigationPropagateBlockedStatus( array $items, array &$blocked ): bool {
    $changed = false;
    foreach ( $items as $item ) {
        $item_id = isset( $item->ID ) ? (int) $item->ID : 0;
        $parent  = isset( $item->menu_item_parent ) ? (int) $item->menu_item_parent : 0;
        if ( $item_id > 0 && $parent > 0 && isset( $blocked[ $parent ] ) && ! isset( $blocked[ $item_id ] ) ) {
            $blocked[ $item_id ] = true;
            $changed             = true;
        }
    }
    return $changed;
}

/** Build set of blocked menu items including descendants of unpublished pages. */
function nvxNavigationFindBlockedMenuItems( array $items ): array {
    $blocked = array();
    foreach ( $items as $item ) {
        if ( nvxNavigationIsItemDirectlyBlocked( $item ) ) {
            $item_id = isset( $item->ID ) ? (int) $item->ID : 0;
            $blocked[ $item_id ] = true;
        }
    }

    $changed = true;
    while ( $changed ) {
        $changed = nvxNavigationPropagateBlockedStatus( $items, $blocked );
    }
    return $blocked;
}

/**
 * Removes unpublished page items and their descendants from the primary navigation.
 *
 * Items for other theme locations are returned unchanged.
 *
 * @param array<int,WP_Post|stdClass> $items Menu items.
 * @param stdClass                    $args Menu arguments.
 * @return array<int,WP_Post|stdClass> The filtered menu items.
 */
function nvxNavigationPruneUnpublishedItems( $items, $args ) {
    if ( ! isset( $args->theme_location ) || 'primary' !== $args->theme_location ) {
        return $items;
    }

    $blocked = nvxNavigationFindBlockedMenuItems( $items );

    return array_values(
        array_filter(
            $items,
            static function ( $item ) use ( $blocked ): bool {
                $item_id = isset( $item->ID ) ? (int) $item->ID : 0;
                return ! isset( $blocked[ $item_id ] );
            }
        )
    );
}
add_filter( 'wp_nav_menu_objects', 'nvxNavigationPruneUnpublishedItems', 20, 2 );

/** Normalize a menu label for presentation-role detection. */
function nvxNavigationLabelKey( string $label ): string {
    return sanitize_title( remove_accents( wp_strip_all_tags( $label ) ) );
}

/**
 * Adds consistent navigation item classes for the primary menu.
 *
 * Top-level items marked as mega-menu roots receive the `nvx-nav__item--mega` class.
 *
 * @param string[] $classes Existing item classes.
 * @param WP_Post|stdClass $item Menu item.
 * @param stdClass $args Menu arguments.
 * @param int $depth Menu depth.
 * @return string[] The updated item classes.
 */
function nvxNavigationItemClasses( array $classes, $item, $args, int $depth ): array {
    if ( ! isset( $args->theme_location ) || 'primary' !== $args->theme_location ) {
        return $classes;
    }

    $classes[] = 'nvx-nav__item';
    $classes[] = 'nvx-nav__item--depth-' . $depth;

    $label_key  = isset( $item->title ) ? nvxNavigationLabelKey( (string) $item->title ) : '';
    $mega_roots = array( 'soluciones', 'protocolos-signature' );
    if ( 0 === $depth && ( in_array( 'nvx-menu--mega', $classes, true ) || in_array( $label_key, $mega_roots, true ) ) ) {
        $classes[] = 'nvx-nav__item--mega';
    }

    return array_values( array_unique( array_filter( $classes ) ) );
}
add_filter( 'nav_menu_css_class', 'nvxNavigationItemClasses', 20, 4 );

/**
 * Adds consistent link classes and parent-menu attributes for the primary navigation.
 *
 * @param array<string, string> $atts Link attributes.
 * @param WP_Post|stdClass $item Menu item.
 * @param stdClass $args Menu arguments.
 * @param int $depth Menu depth.
 * @return array<string, string> Updated link attributes.
 */
function nvxNavigationLinkAttributes( array $atts, $item, $args, int $depth ): array {
    if ( ! isset( $args->theme_location ) || 'primary' !== $args->theme_location ) {
        return $atts;
    }

    $classes       = preg_split( '/\s+/', trim( (string) ( $atts['class'] ?? '' ) ), -1, PREG_SPLIT_NO_EMPTY );
    $classes       = is_array( $classes ) ? $classes : array();
    $classes[]     = 'nvx-nav__link';
    $atts['class'] = implode( ' ', array_values( array_unique( $classes ) ) );
    $atts['data-nvx-menu-depth'] = (string) $depth;

    $item_classes = isset( $item->classes ) && is_array( $item->classes ) ? $item->classes : array();
    if ( in_array( 'menu-item-has-children', $item_classes, true ) ) {
        $atts['aria-haspopup']       = 'true';
        $atts['data-nvx-menu-parent'] = 'true';
    }

    return $atts;
}
add_filter( 'nav_menu_link_attributes', 'nvxNavigationLinkAttributes', 20, 4 );
