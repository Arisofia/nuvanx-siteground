<?php
/**
 * Unified 13-Point Data Matrix Renderer.
 *
 * Implements the NUVANX Contour Architecture™ for rendering clinical treatment pages,
 * anatomical hubs, and protocol specs in a highly DRY, centralized way.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Renders the hero section with kicker, title, lead, and CTA.
 *
 * @param array<string, mixed> $data The 13-point schema data array.
 * @return string HTML markup for the hero section.
 */
function nvx_render_matrix_hero( array $data ): string {
    $html = '<header class="nvx-strategy-intro">';
    if ( ! empty( $data['kicker'] ) ) {
        $html .= '<p class="nvx-eyebrow">' . esc_html( $data['kicker'] ) . '</p>';
    }
    $html .= '<h1 class="nvx-strategy-title">' . esc_html( $data['h1'] ?? $data['title'] ?? '' ) . '</h1>';
    if ( ! empty( $data['lead'] ) ) {
        $html .= '<p class="nvx-brand-lead">' . esc_html( $data['lead'] ) . '</p>';
    }
    $html .= '<p><a class="nvx-btn nvx-btn--primary" href="' . esc_url( home_url( '/madrid/valoracion/' ) ) . '">' . esc_html__( 'Solicitar valoración médica', 'nuvanx-medical' ) . '</a></p>';
    $html .= '</header>';
    return $html;
}

/**
 * Renders a text section with heading and paragraph.
 *
 * @param string $heading Section heading.
 * @param string $content Section content.
 * @return string HTML markup for the text section.
 */
function nvx_render_matrix_text_section( string $heading, string $content ): string {
    $html  = '<section class="nvx-brand-section">';
    $html .= '<h2>' . esc_html( $heading ) . '</h2>';
    $html .= '<p>' . esc_html( $content ) . '</p>';
    $html .= '</section>';
    return $html;
}

/**
 * Renders a list section (ul or ol) with heading and items.
 *
 * @param string $heading  Section heading.
 * @param array  $items    List items.
 * @param string $list_tag List element type ('ul' or 'ol').
 * @return string HTML markup for the list section.
 */
function nvx_render_matrix_list_section( string $heading, array $items, string $list_tag = 'ul' ): string {
    $html  = '<section class="nvx-brand-section">';
    $html .= '<h2>' . esc_html( $heading ) . '</h2>';
    $html .= '<' . esc_attr( $list_tag ) . ' class="nvx-brand-list">';
    foreach ( $items as $item ) {
        $html .= '<li>' . esc_html( $item ) . '</li>';
    }
    $html .= '</' . esc_attr( $list_tag ) . '></section>';
    return $html;
}

/**
 * Renders the evolution, risks, and combinations section for injectable treatments.
 *
 * @param array<string, mixed> $data The 13-point schema data array.
 * @return string HTML markup for the evolution section, or empty string if no evolution data.
 */
function nvx_render_matrix_evolution_section( array $data ): string {
    if ( empty( $data['evolution'] ) ) {
        return '';
    }

    $html  = '<section class="nvx-brand-section">';
    $html .= '<h2>' . esc_html__( 'Evolución y seguridad', 'nuvanx-medical' ) . '</h2>';
    $html .= '<p>' . esc_html( $data['evolution'] ) . '</p>';

    if ( ! empty( $data['risks'] ) && is_array( $data['risks'] ) ) {
        $html .= '<h3>' . esc_html__( 'Riesgos que deben explicarse', 'nuvanx-medical' ) . '</h3>';
        $html .= '<ul class="nvx-brand-list">';
        foreach ( $data['risks'] as $risk ) {
            $html .= '<li>' . esc_html( $risk ) . '</li>';
        }
        $html .= '</ul>';
    }

    if ( ! empty( $data['combinations'] ) && is_array( $data['combinations'] ) ) {
        $html .= '<h3>' . esc_html__( 'Combinaciones posibles', 'nuvanx-medical' ) . '</h3>';
        $html .= '<ul class="nvx-brand-list">';
        foreach ( $data['combinations'] as $comb ) {
            $html .= '<li>' . esc_html( $comb ) . '</li>';
        }
        $html .= '</ul>';
    }

    $html .= '</section>';
    return $html;
}

/**
 * Renders the FAQs section with accordion markup.
 *
 * @param array $faqs FAQ items, each with 'q' and 'a' keys.
 * @return string HTML markup for the FAQs section, or empty string if no FAQs.
 */
function nvx_render_matrix_faqs_section( array $faqs ): string {
    if ( empty( $faqs ) || ! is_array( $faqs ) ) {
        return '';
    }

    $html  = '<section class="nvx-brand-section">';
    $html .= '<h2>' . esc_html__( 'Preguntas frecuentes', 'nuvanx-medical' ) . '</h2>';
    $html .= '<div class="nvx-faq-accordion">';
    foreach ( $faqs as $faq ) {
        $html .= '<details class="nvx-faq-item">';
        $html .= '<summary class="nvx-faq-question">' . esc_html( $faq['q'] ) . '</summary>';
        $html .= '<div class="nvx-faq-answer"><p>' . esc_html( $faq['a'] ) . '</p></div>';
        $html .= '</details>';
    }
    $html .= '</div></section>';
    return $html;
}

/**
 * Universal renderer for the 13-point data matrix pattern.
 *
 * Replaces duplicate rendering logic across Phase 1, Phase 2, and Phase 3 files.
 *
 * @param array<string, mixed> $data The 13-point schema data array.
 * @return string Extracted and validated HTML block.
 */
function nvx_render_13_point_matrix( array $data ): string {
    // Clase ajustada para representar tanto protocolos como páginas anatómicas de tratamiento.
    // Se mantiene `nvx-protocol-page` para compatibilidad con CSS existente y se añade una
    // clase más neutral para su uso futuro.
    $html  = '<article class="nvx-brand-readable nvx-treatment-page nvx-protocol-page nvx-shell">';

    // 1. Hero / Intro
    $html .= nvx_render_matrix_hero( $data );

    // 2. Diagnosis (Por qué un enfoque genérico no funciona / Diagnóstico)
    if ( ! empty( $data['diagnosis'] ) ) {
        $heading = ! empty( $data['diagnosis_heading'] )
            ? $data['diagnosis_heading']
            : __( 'El valor del diagnóstico médico', 'nuvanx-medical' );

        $html .= nvx_render_matrix_text_section(
            $heading,
            $data['diagnosis']
        );
    }

    // 3. Mechanism (Cómo actuamos)
    if ( ! empty( $data['mechanism'] ) ) {
        $heading = ! empty( $data['mechanism_heading'] )
            ? $data['mechanism_heading']
            : __( 'Mecanismo de acción', 'nuvanx-medical' );

        if ( is_array( $data['mechanism'] ) ) {
            $html .= nvx_render_matrix_list_section(
                $heading,
                $data['mechanism']
            );
        } else {
            $html .= nvx_render_matrix_text_section(
                $heading,
                $data['mechanism']
            );
        }
    }

    // 4. Indications
    if ( ! empty( $data['indications'] ) && is_array( $data['indications'] ) ) {
        $heading = ! empty( $data['indications_heading'] )
            ? $data['indications_heading']
            : __( 'Indicaciones: Qué tratamos', 'nuvanx-medical' );

        $html .= nvx_render_matrix_list_section(
            $heading,
            $data['indications'],
            'ul'
        );
    }

    // 5. Precautions
    if ( ! empty( $data['precautions'] ) && is_array( $data['precautions'] ) ) {
        $heading = ! empty( $data['precautions_heading'] )
            ? $data['precautions_heading']
            : __( 'Precauciones: Cuándo no tratar', 'nuvanx-medical' );

        $html .= nvx_render_matrix_list_section(
            $heading,
            $data['precautions'],
            'ul'
        );
    }

    // 6. Process
    if ( ! empty( $data['process'] ) && is_array( $data['process'] ) ) {
        $heading = ! empty( $data['process_heading'] )
            ? $data['process_heading']
            : __( 'Proceso en clínica', 'nuvanx-medical' );

        $html .= nvx_render_matrix_list_section(
            $heading,
            $data['process'],
            'ol'
        );
    }

    // 6.5 Evolution and Risks (Specific for injectable treatments)
    $html .= nvx_render_matrix_evolution_section( $data );

    // 7. FAQs
    $html .= nvx_render_matrix_faqs_section( $data['faqs'] ?? array() );

    $html .= '</article>';
    return $html;
}
