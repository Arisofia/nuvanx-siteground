<?php
/**
 * Equipo médico — E-E-A-T: Rivera Tejeda + Rivera Deras + Quiñónez Bareiro + rest of staff.
 *
 * Wire-frame: Hero → Director → Dra. Ivon → Dr. Fabio → Resto CMS → CTA.
 * Schema Physicians via Yoast graph only (no standalone ld+json). No AggregateRating hardcode.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'NVX_REGEX_MEDIA' ) ) {
    define( 'NVX_REGEX_MEDIA', '/<(?:figure|div|img)\b[^>]*>[\s\S]*?(?:<\/(?:figure|div)>|$)/iu' );
}

/**
 * Singular context.
 */
function nvx_equipo_is_singular_context(): bool {
    if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
        return false;
    }

    return is_singular( 'page' ) || is_page();
}

/**
 * Detect equipo médico page only (path/markers — not every Rivera mention sitewide).
 */
function nvx_content_is_equipo_page( string $content ): bool {
    if ( false !== strpos( $content, 'nvx-equipo-editorial' ) ) {
        return false;
    }

    if ( ! nvx_equipo_is_singular_context() ) {
        return false;
    }

    if ( is_front_page() || is_home() ) {
        return false;
    }

    if ( is_page( 'equipo-medico' ) ) {
        return true;
    }

    $path = function_exists( 'nvx_schema_current_path' )
        ? nvx_schema_current_path( (int) get_queried_object_id() )
        : '';

    if ( is_string( $path ) && function_exists( 'nvx_schema_path_matches' ) && nvx_schema_path_matches( $path, '/equipo-medico/' ) ) {
        return true;
    }

    return (bool) preg_match(
        '/aria-label=["\']Equipo médico NUVANX["\']|id=["\']nvx-equipo-h1["\']|class=["\'][^"\']*nvx-equipo-hero|equipo especialista/iu',
        $content
    );
}

/**
 * Hero.
 */
function nvx_equipo_hero_copy_markup(): string {
    $colegiado_dir   = defined( 'NVX_DIRECTOR_COLEGIADO' ) ? NVX_DIRECTOR_COLEGIADO : '282864786';
    $colegiado_ivon  = defined( 'NVX_IVON_COLEGIADO' ) ? NVX_IVON_COLEGIADO : '284621525';
    $colegiado_fabio = defined( 'NVX_FABIO_COLEGIADO' ) ? NVX_FABIO_COLEGIADO : '282877543';

    $html  = '<div class="nvx-editorial-hero__copy-copy">';
    $html .= '<p class="nvx-eyebrow">' . esc_html__( 'NUVANX · Equipo médico', 'nuvanx-medical' ) . '</p>';
    $html .= '<h1 class="nvx-heading" id="nvx-equipo-h1">' . esc_html__( 'Equipo médico NUVANX: quién te valora y quién trata', 'nuvanx-medical' ) . '</h1>';
    $html .= '<p class="nvx-lead">' . esc_html__( 'En muchas clínicas, quien te atiende al principio no es quien luego te trata — te ve un comercial, y el médico solo aparece para aplicar lo que ya se vendió. Aquí no. La persona que te explora es la misma que te trata y la que te sigue viendo después. Nadie cambia a mitad de tu plan.', 'nuvanx-medical' ) . '</p>';
    $html .= '<p class="nvx-lead">' . esc_html(
        sprintf(
            /* translators: 1: director license, 2: Dra. Ivon license, 3: Dr. Fabio license */
            __( 'Dr. José Javier Rivera Tejeda (ICOMEM %1$s), director médico; Dra. Ivon Yamileth Rivera Deras (ICOMEM %2$s), well-aging y geriatría preventiva; y Dr. Fabio Augusto Quiñónez Bareiro (ICOMEM %3$s), geriatría y paciente complejo — junto al resto del equipo clínico NUVANX.', 'nuvanx-medical' ),
            $colegiado_dir,
            $colegiado_ivon,
            $colegiado_fabio
        )
    ) . '</p>';

    if ( function_exists( 'nvx_cta_pair_markup' ) ) {
        $html .= nvxCtaPairMarkup( 'nvx-equipo-hero-ctas nvx-home-hero-ctas' );
    }

    $html .= '<p class="nvx-brand-meta">' . esc_html__( 'Chamberí · Goya · Medicina basada en evidencia', 'nuvanx-medical' ) . '</p>';
    $html .= '</div>';

    return $html;
}

/**
 * Whether media HTML is a logo / non-portrait asset (never use as staff/hero photo).
 */
function nvx_equipo_media_is_logo( string $html ): bool {
    return (bool) preg_match(
        '/logo-nuvanx|nuvanx-web\.webp|\/logo[-_]|nvx-logo|site-logo|custom-logo/iu',
        $html
    );
}

/** Promote data-src to src for lazyloaded images. */
function nvx_equipo_promote_lazy_src( string $attrs ): string {
    if ( ( preg_match( '/\ssrc=["\']data:image\//i', $attrs ) || preg_match( '/\ssrc=["\']["\']/i', $attrs ) )
        && preg_match( '/\sdata-(?:src|lazy-src|original)=["\']([^"\']+)["\']/i', $attrs, $ds )
    ) {
        $real = esc_url( $ds[1] );
        if ( '' !== $real ) {
            return preg_match( '/\ssrc=/i', $attrs )
                ? ( nvxContentPregReplaceKeep( '/\ssrc=["\'][^"\']*["\']/i', ' src="' . $real . '"', $attrs, 1 ) ?? $attrs )
                : $attrs . ' src="' . $real . '"';
        }
    }
    return $attrs;
}

/**
 * Normalize a portrait snippet to a single clean <img> (doctor crop).
 *
 * @param string $media Figure or img HTML from CMS.
 * @return string Safe img markup or empty.
 */
function nvx_equipo_clean_portrait_img( string $media ): string {
    if ( '' === trim( $media ) || nvx_equipo_media_is_logo( $media ) ) {
        return '';
    }

    // Prefer real <img> over noscript twin / decorative placeholders.
    if ( ! preg_match( '/<img\b([^>]*)>/iu', $media, $m ) ) {
        return '';
    }

    $attrs = nvx_equipo_promote_lazy_src( $m[1] );

    // Drop inline size/style that fights portrait crop; strip body role.
    $attrs = nvxContentPregReplaceKeep( '/\s+style=["\'][^"\']*["\']/i', '', $attrs );
    $attrs = nvxContentPregReplaceKeep( '/\s+(?:width|height)=["\'][^"\']*["\']/i', '', $attrs ) ?? $attrs;
    $attrs = nvxContentPregReplaceKeep( '/\s*nvx-media--body\s*/i', ' ', $attrs );
    // Re-emit loading/decoding once (CMS + cleaners often duplicate).
    $attrs = nvxContentPregReplaceKeep( '/\s+loading=["\'][^"\']*["\']/i', '', $attrs );
    $attrs = nvxContentPregReplaceKeep( '/\s+decoding=["\'][^"\']*["\']/i', '', $attrs );
    
    if ( function_exists( 'nvx_html_attrs_add_class' ) ) {
        $attrs = nvxHtmlAttrsAddClass( $attrs, 'nvx-media' );
        $attrs = nvxHtmlAttrsAddClass( $attrs, 'nvx-media--doctor' );
    } elseif ( ! preg_match( '/\bclass=/i', $attrs ) ) {
        $attrs .= ' class="nvx-media nvx-media--doctor"';
    }

    return '<img' . $attrs . ' loading="lazy" decoding="async">';
}

/**
 * Whether a CMS card is a real clinician (photo + person name), not sedes/reseñas/listas.
 */
function nvx_equipo_is_person_staff_card( string $card ): bool {
    if ( ! preg_match( '/<img\b/i', $card ) ) {
        return false;
    }
    if ( nvx_equipo_media_is_logo( $card ) ) {
        return false;
    }

    // Prefer cards with a named title (person).
    if ( preg_match( '/nvx-brand-card__title[^>]*>([\s\S]*?)<\//iu', $card, $tm ) ) {
        $title = trim( wp_strip_all_tags( $tm[1] ) );
        if ( '' === $title ) {
            return false;
        }
        // Titles that are places, proof widgets, or section headers — not people.
        if ( preg_match(
            '/^(Chamber[ií]|Goya\b|Especialidades|NUVANX Medicina|NUVANX en Doctoralia|Reseñas)/iu',
            $title
        ) ) {
            return false;
        }
        return true;
    }

    // No title: drop review/list chrome; keep only cards with portrait media.
    if ( preg_match( '/NUVANX en Doctoralia|Reseñas públicas|Especialidades y tecnolog/iu', $card ) ) {
        return false;
    }

    return (bool) preg_match( '/nvx-brand-card__media/i', $card );
}

/**
 * Portrait frame markup for authority profiles.
 */
function nvx_equipo_portrait_figure_markup( string $media, string $label ): string {
    $img = nvx_equipo_clean_portrait_img( $media );
    if ( '' === $img ) {
        return '';
    }

    return '<figure class="nvx-equipo-portrait" aria-label="' . esc_attr( $label ) . '">' . $img . '</figure>';
}

/**
 * Whether a card/block is the director Rivera Tejeda.
 */
function nvx_equipo_block_is_rivera_tejeda( string $html ): bool {
    return (bool) preg_match( '/Rivera\s+Tejeda|Jos[eé]\s+Javier\s+Rivera/iu', $html );
}

/**
 * Whether a card/block is Dra. Ivon Yamileth Rivera Deras.
 */
function nvx_equipo_block_is_ivon( string $html ): bool {
    return (bool) preg_match( '/Ivon|Yamileth|Rivera\s+Deras/iu', $html );
}

/**
 * Whether a card/block is Dr. Fabio Augusto Quiñónez Bareiro.
 */
function nvx_equipo_block_is_fabio( string $html ): bool {
    return (bool) preg_match( '/Fabio|Qui[nñ][oó]nez|Bareiro/iu', $html );
}

/** Categorize one staff card. */
function nvx_equipo_categorize_staff_card( string $card, string &$rivera_media, string &$ivon_media, string &$fabio_media, array &$other_cards ): void {
    if ( nvx_equipo_block_is_rivera_tejeda( $card ) ) {
        if ( '' === $rivera_media && preg_match( NVX_REGEX_MEDIA, $card, $image_match ) ) {
            $rivera_media = $image_match[0];
        }
        return;
    }
    if ( nvx_equipo_block_is_ivon( $card ) ) {
        if ( '' === $ivon_media && preg_match( NVX_REGEX_MEDIA, $card, $image_match ) ) {
            $ivon_media = $image_match[0];
        }
        return;
    }
    if ( nvx_equipo_block_is_fabio( $card ) ) {
        if ( '' === $fabio_media && preg_match( NVX_REGEX_MEDIA, $card, $image_match ) ) {
            $fabio_media = $image_match[0];
        }
        return;
    }
    if ( nvx_equipo_is_person_staff_card( $card ) ) {
        $other_cards[] = $card;
    }
}

/**
 * Extracts authority portraits and remaining clinician cards from CMS content.
 *
 * Authority cards are returned as media snippets for use in expanded profiles; other valid clinician cards are returned unchanged.
 *
 * @param string $content The CMS content containing staff cards.
 * @return array{rivera_media:string,ivon_media:string,fabio_media:string,other_cards:string[]} Extracted authority media and remaining clinician cards.
 */
function nvx_equipo_extract_staff_cards( string $content ): array {
    $other_cards  = array();
    $rivera_media = '';
    $ivon_media   = '';
    $fabio_media  = '';

    $patterns = array(
        '/<article\b[^>]*\bclass=["\'][^"\']*\bnvx-brand-card\b[^"\']*["\'][^>]*>[\s\S]*?<\/article>/iu',
        '/<div\b[^>]*\bclass=["\'][^"\']*\bnvx-brand-card\b[^"\']*["\'][^>]*>[\s\S]*?<\/div>\s*(?=<div\b[^>]*\bnvx-brand-card\b|<section\b|<\/section>|$)/iu',
    );

    $found = array();
    foreach ( $patterns as $pattern ) {
        if ( preg_match_all( $pattern, $content, $m ) && ! empty( $m[0] ) ) {
            $found = $m[0];
            break;
        }
    }

    foreach ( $found as $card ) {
        nvx_equipo_categorize_staff_card( $card, $rivera_media, $ivon_media, $fabio_media, $other_cards );
    }

    return array(
        'rivera_media' => $rivera_media,
        'ivon_media'   => $ivon_media,
        'fabio_media'  => $fabio_media,
        'other_cards'  => $other_cards,
    );
}

/**
 * Normalize a CMS staff card: team class + portrait media crop.
 */
function nvx_equipo_normalize_staff_card( string $card ): string {
    if ( preg_match( '/\bclass=(["\'])/u', $card ) && false === strpos( $card, 'nvx-brand-card--team' ) ) {
        $card = nvxContentPregReplaceKeep( '/\bclass=(["\'])/u', 'class=$1nvx-brand-card--team ', $card, 1 ) ?? $card;
    }

    // Portrait frame: single clean img, no noscript/br noise inside figure.
    $card = nvxContentPregReplaceKeep(
        '/(<figure\b[^>]*\bclass=["\'][^"\']*\bnvx-brand-card__media\b)([^"\']*)(["\'][^>]*>)([\s\S]*?)(<\/figure>)/iu',
        static function ( array $m ): string {
            $open = $m[1] . $m[2];
            if ( false === strpos( $open . $m[3], 'nvx-brand-card__media--portrait' ) ) {
                $open .= ' nvx-brand-card__media--portrait';
            }
            $open = nvxContentPregReplaceKeep( '/\s*nvx-content-figure\s*/i', ' ', $open );
            $img  = nvx_equipo_clean_portrait_img( $m[4] );
            if ( '' === $img ) {
                return $open . $m[3] . $m[5];
            }
            return $open . $m[3] . $img . $m[5];
        },
        $card
    ) ?? $card;

    // Bare img without figure.
    if ( false === strpos( $card, 'nvx-brand-card__media' ) && preg_match( '/<img\b[^>]*>/iu', $card, $im ) ) {
        $img = nvx_equipo_clean_portrait_img( $im[0] );
        if ( '' !== $img ) {
            $card = nvxContentPregReplaceKeep( '/<noscript\b[\s\S]*?<\/noscript>/iu', '', $card );
            $card = nvxContentPregReplaceKeep(
                '/<img\b[^>]*>/iu',
                '<figure class="nvx-brand-card__media nvx-brand-card__media--portrait">' . $img . '</figure>',
                $card,
                1
            );
        }
    }

    $card = nvxContentPregReplaceKeep( '/<br\s*\/?>/iu', '', $card );

    return (string) $card;
}

/**
 * Markup for remaining clinical team (CMS cards, not the two authority profiles).
 *
 * @param string[] $other_cards HTML cards.
 */
function nvx_equipo_other_staff_section_markup( array $other_cards ): string {
    if ( empty( $other_cards ) ) {
        return '';
    }

    $html  = '<section class="nvx-editorial-section nvx-equipo-staff" aria-labelledby="nvx-equipo-staff-title">';
    $html .= '<div class="nvx-editorial-section__inner">';
    $html .= '<p class="nvx-editorial-kicker">' . esc_html__( 'Equipo clínico', 'nuvanx-medical' ) . '</p>';
    $html .= '<h2 id="nvx-equipo-staff-title" class="nvx-editorial-heading">' . esc_html__( 'Resto del equipo médico NUVANX', 'nuvanx-medical' ) . '</h2>';
    $html .= '<p class="nvx-editorial-body nvx-editorial-body--measure">' . esc_html__( 'Profesionales que atienden valoración, seguimiento y protocolos en Chamberí y Goya, junto a la dirección médica y al criterio científico de la clínica.', 'nuvanx-medical' ) . '</p>';
    $html .= '<div class="nvx-equipo-staff-grid">';
    foreach ( $other_cards as $card ) {
        $card = nvx_equipo_normalize_staff_card( $card );
        if ( '' !== $card ) {
            $html .= $card;
        }
    }
    $html .= '</div></div></section>';

    return $html;
}

/**
 * Builds a physician's authority profile markup.
 *
 * @param array $config Physician configuration data.
 * @return string The rendered authority profile HTML.
 */
function nvx_equipo_physician_authority_markup( array $config ): string {
    $colegiado  = $config['colegiado'] ?? '';
    $doctoralia = $config['doctoralia'] ?? '';

    $html  = '<div class="nvx-equipo-director" id="physician-rivera-tejeda">';

    // A. Profile: portrait + copy in structured grid.
    $html .= '<section class="nvx-editorial-section nvx-equipo-profile" aria-labelledby="nvx-equipo-profile-title">';
    $html .= '<div class="nvx-editorial-section__inner nvx-equipo-profile-layout">';
    $portrait = nvx_equipo_portrait_figure_markup( $config['media'] ?? '', $config['name'] ?? '' );
    if ( '' !== $portrait ) {
        $html .= $portrait;
    }
    $html .= '<div class="nvx-equipo-profile-layout__copy">';
    $html .= '<p class="nvx-editorial-kicker">' . esc_html( $config['kicker'] ?? '' ) . '</p>';
    $html .= '<h2 id="nvx-equipo-profile-title" class="nvx-editorial-heading">' . esc_html( $config['h2'] ?? '' ) . '</h2>';
    $html .= '<p class="nvx-editorial-body">' . esc_html(
        sprintf(
            /* translators: %s: medical license number */
            __( 'Con número de colegiación ICOMEM %s, el Dr. José Javier Rivera Tejeda ostenta la Dirección Médica de las clínicas NUVANX en Madrid. Médico estético hiper-especializado en la aplicación avanzada de tecnologías láser intervencionistas y medicina regenerativa tisular.', 'nuvanx-medical' ),
            $colegiado
        )
    ) . '</p>';
    $html .= '<p class="nvx-editorial-body">' . wp_kses(
        sprintf(
            /* translators: %s: Doctoralia URL */
            __( 'Su perfil público en <a class="nvx-brand-inline-link" href="%s" target="_blank" rel="noopener noreferrer">Doctoralia</a> concentra reseñas certificadas de pacientes (consultables en el directorio). Es el responsable del diseño de los protocolos de tratamiento en NUVANX: la aparatología se subordina al diagnóstico, no al revés.', 'nuvanx-medical' ),
            esc_url( $doctoralia )
        ),
        array(
            'a' => array(
                'class'  => true,
                'href'   => true,
                'target' => true,
                'rel'    => true,
            ),
        )
    ) . '</p>';
    $html .= '</div></div></section>';

    // B. Subespecialización.
    $html .= '<section class="nvx-editorial-section nvx-equipo-scope" aria-labelledby="nvx-equipo-scope-title">';
    $html .= '<div class="nvx-editorial-section__inner">';
    $html .= '<p class="nvx-editorial-kicker">' . esc_html__( 'Ámbito clínico', 'nuvanx-medical' ) . '</p>';
    $html .= '<h2 id="nvx-equipo-scope-title" class="nvx-editorial-heading">' . esc_html__( 'Subespecialización y experiencia', 'nuvanx-medical' ) . '</h2>';
    $html .= '<ul class="nvx-editorial-grid-list">';
    $scopes = array(
        array(
            'title' => __( 'Láser intersticial avanzado', 'nuvanx-medical' ),
            'body'  => __( 'Endolift® y laserlipólisis para modificación estructural de grasa submentoniana y corporal en casos seleccionados.', 'nuvanx-medical' ),
        ),
        array(
            'title' => __( 'Dermatología láser ablativa', 'nuvanx-medical' ),
            'body'  => __( 'Láser CO₂ fraccionado orientado a secuelas de acné, textura y fotodaño, con planificación de downtime.', 'nuvanx-medical' ),
        ),
        array(
            'title' => __( 'Arquitectura y geometría facial', 'nuvanx-medical' ),
            'body'  => __( 'Restauración volumétrica con inductores de colágeno (p. ej. Radiesse®, Ellansé®) y neuromoduladores cuando el diagnóstico lo indica — tras tensar, no al revés.', 'nuvanx-medical' ),
        ),
        array(
            'title' => __( 'Tricología médica', 'nuvanx-medical' ),
            'body'  => __( 'Abordaje médico del cabello y cuero cabelludo dentro del alcance de la consulta especializada.', 'nuvanx-medical' ),
        ),
    );
    foreach ( $scopes as $scope ) {
        $html .= '<li class="nvx-editorial-grid-item">';
        $html .= '<h3 class="nvx-editorial-grid-item__title">' . esc_html( $scope['title'] ) . '</h3>';
        $html .= '<p class="nvx-editorial-body">' . esc_html( $scope['body'] ) . '</p>';
        $html .= '</li>';
    }
    $html .= '</ul></div></section>';

    // C. Formación.
    $html .= '<section class="nvx-editorial-section nvx-equipo-formation" aria-labelledby="nvx-equipo-form-title">';
    $html .= '<div class="nvx-editorial-section__inner nvx-editorial-split">';
    $html .= '<div class="nvx-editorial-split__copy">';
    $html .= '<p class="nvx-editorial-kicker">' . esc_html__( 'Formación', 'nuvanx-medical' ) . '</p>';
    $html .= '<h2 id="nvx-equipo-form-title" class="nvx-editorial-heading">' . esc_html__( 'Formación académica y trayectoria', 'nuvanx-medical' ) . '</h2>';
    $html .= '<p class="nvx-editorial-body">' . esc_html__( 'Máster Universitario en Medicina Estética por la Universidad Complutense de Madrid (UCM). Máster especializado en Tricología y Cirugía Capilar (AMIR).', 'nuvanx-medical' ) . '</p>';
    $html .= '<p class="nvx-editorial-body">' . esc_html__( 'Trayectoria como director de cirugía cosmética láser en cadenas hospitalarias de referencia (Clínicas Londres, Clínicas Dr. Esquivel), aplicada hoy al modelo de doble sede NUVANX.', 'nuvanx-medical' ) . '</p>';
    $html .= '</div>';
    $html .= '<aside class="nvx-editorial-panel" aria-label="' . esc_attr__( 'Identidad profesional', 'nuvanx-medical' ) . '">';
    $html .= '<p class="nvx-editorial-panel__label">' . esc_html__( 'Identidad', 'nuvanx-medical' ) . '</p>';
    $html .= '<ul class="nvx-editorial-fact-list">';
    $html .= '<li><strong>' . esc_html__( 'Colegiado', 'nuvanx-medical' ) . '</strong> — ICOMEM ' . esc_html( $colegiado ) . '</li>';
    $html .= '<li><strong>' . esc_html__( 'Cargo', 'nuvanx-medical' ) . '</strong> — ' . esc_html__( 'Director médico NUVANX Madrid', 'nuvanx-medical' ) . '</li>';
    $html .= '<li><strong>' . esc_html__( 'Sedes', 'nuvanx-medical' ) . '</strong> — ' . esc_html__( 'Chamberí y Goya · Barrio Salamanca', 'nuvanx-medical' ) . '</li>';
    $html .= '<li><strong>' . esc_html__( 'Agenda', 'nuvanx-medical' ) . '</strong> — ' . esc_html__( 'Mar/Jue Chamberí · Mié Goya', 'nuvanx-medical' ) . '</li>';
    $html .= '</ul></aside></div></section>';

    // D. Quote.
    $html .= '<section class="nvx-editorial-section nvx-equipo-quote" aria-labelledby="nvx-equipo-quote-title">';
    $html .= '<div class="nvx-editorial-section__inner">';
    $html .= '<h2 id="nvx-equipo-quote-title" class="screen-reader-text">' . esc_html__( 'Visión clínica', 'nuvanx-medical' ) . '</h2>';
    $html .= '<blockquote class="nvx-equipo-blockquote">';
    $html .= '<p>' . esc_html__( 'Mi visión clínica rechaza la transformación anatómica artificial. La tecnología láser más sofisticada debe emplearse para desencadenar la regeneración celular propia del paciente, logrando una firmeza biológica real, no un aspecto quirúrgico evidente.', 'nuvanx-medical' ) . '</p>';
    $html .= '<footer>— ' . esc_html__( 'Dr. J.J. Rivera Tejeda', 'nuvanx-medical' ) . '</footer>';
    $html .= '</blockquote></div></section>';

    $html .= '</div>';

    return $html;
}

/**
 * Rebuilds the Equipo médico page with an editorial hero, clinician authority profiles, and preserved staff cards.
 *
 * @param string $content The original page content.
 * @return string The rebuilt page content, or the original content when restructuring is not applicable.
 */
function nvxContentRestructureEquipoPage( string $content ): string {
    if ( ! nvx_content_is_equipo_page( $content ) ) {
        return $content;
    }

    $staff = nvx_equipo_extract_staff_cards( $content );

    // Hero media: only real page hero — never logo, never a stolen staff portrait.
    $media = '';
    if ( preg_match( '/<(?:figure|div) class="nvx-brand-hero__media"[\s\S]*?<\/(?:figure|div)>/iu', $content, $media_match ) ) {
        $media = $media_match[0];
    }
    if ( '' !== $media && nvx_equipo_media_is_logo( $media ) ) {
        $media = '';
    }

    $hero_classes = 'nvx-brand-hero nvx-brand-hero--laser nvx-editorial-hero';
    if ( '' === $media ) {
        $hero_classes .= '';
    }

    $hero  = '<section class="' . esc_attr( $hero_classes ) . '" aria-labelledby="nvx-equipo-h1" aria-label="' . esc_attr__( 'Equipo médico NUVANX', 'nuvanx-medical' ) . '">';
    $hero .= '<div class="nvx-brand-hero__inner">';
    $hero .= nvx_equipo_hero_copy_markup();
    $hero .= $media;
    $hero .= '</div></section>';

    // Director → Dra. Ivon → Dr. Fabio → resto del equipo (CMS).
    // Closing valoración CTA: site-wide nvx-cta-banner in footer.php.
    $body  = '<div class="nvx-equipo-editorial nvx-editorial-page">';
    $body .= nvx_equipo_physician_authority_markup( array(
        'media'      => $staff['rivera_media'],
        'name'       => __( 'Dr. José Javier Rivera Tejeda', 'nuvanx-medical' ),
        'kicker'     => __( 'Director médico', 'nuvanx-medical' ),
        'h2'         => __( 'Dr. José Javier Rivera Tejeda: Director Médico e Investigador Clínico', 'nuvanx-medical' ),
        'colegiado'  => defined( 'NVX_DIRECTOR_COLEGIADO' ) ? NVX_DIRECTOR_COLEGIADO : '282864786',
        'doctoralia' => 'https://www.doctoralia.es/jose-javier-rivera-tejeda/medico-estetico/madrid',
    ) );
    // The other two physicians can be refactored similarly if their markup structure is consistent.
    // For now, keeping them as they are to avoid breaking changes without full analysis.
    if ( function_exists( 'nvx_equipo_ivon_authority_markup' ) ) {
        $body .= nvx_equipo_ivon_authority_markup( $staff['ivon_media'] );
    }
    if ( function_exists( 'nvx_equipo_fabio_authority_markup' ) ) {
        $body .= nvx_equipo_fabio_authority_markup( $staff['fabio_media'] ?? '' );
    }
    $body .= nvx_equipo_other_staff_section_markup( $staff['other_cards'] );
    $body .= '</div>';

    if ( preg_match( '/(<div class="nvx-brand-page[^"]*"[^>]*>)/iu', $content, $wrap ) ) {
        return $wrap[1] . $hero . $body . '</div>';
    }

    return $hero . $body;
}
add_filter( 'the_content', 'nvxContentRestructureEquipoPage', 19 );
