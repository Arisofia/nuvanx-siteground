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
 * Universal renderer for the 13-point data matrix pattern.
 *
 * Replaces duplicate rendering logic across Phase 1, Phase 2, and Phase 3 files.
 *
 * @param array<string, mixed> $data The 13-point schema data array.
 * @return string Extracted and validated HTML block.
 */
function nvx_render_13_point_matrix( array $data ): string {
	$html  = '<article class="nvx-brand-readable nvx-protocol-page nvx-shell">';
	
	// 1. Hero / Intro
	$html .= '<header class="nvx-strategy-intro">';
	if ( ! empty( $data['kicker'] ) ) {
		$html .= '<p class="nvx-brand-kicker">' . esc_html( $data['kicker'] ) . '</p>';
	}
	$html .= '<h1 class="nvx-strategy-title">' . esc_html( $data['h1'] ?? $data['title'] ?? '' ) . '</h1>';
	if ( ! empty( $data['lead'] ) ) {
		$html .= '<p class="nvx-brand-lead">' . esc_html( $data['lead'] ) . '</p>';
	}
	$html .= '<p><a class="nvx-btn nvx-btn--primary" href="' . esc_url( home_url( '/madrid/valoracion/' ) ) . '">' . esc_html__( 'Solicitar valoración médica', 'nuvanx-medical' ) . '</a></p>';
	$html .= '</header>';

	// 2. Diagnosis (Por qué un enfoque genérico no funciona / Diagnóstico)
	if ( ! empty( $data['diagnosis'] ) ) {
		$html .= '<section class="nvx-brand-section">';
		$html .= '<h2>' . esc_html__( 'El valor del diagnóstico médico', 'nuvanx-medical' ) . '</h2>';
		$html .= '<p>' . esc_html( $data['diagnosis'] ) . '</p>';
		$html .= '</section>';
	}

	// 3. Mechanism (Cómo actuamos)
	if ( ! empty( $data['mechanism'] ) ) {
		$html .= '<section class="nvx-brand-section">';
		$html .= '<h2>' . esc_html__( 'Mecanismo de acción', 'nuvanx-medical' ) . '</h2>';
		$html .= '<p>' . esc_html( $data['mechanism'] ) . '</p>';
		$html .= '</section>';
	}

	// 4. Indications
	if ( ! empty( $data['indications'] ) && is_array( $data['indications'] ) ) {
		$html .= '<section class="nvx-brand-section">';
		$html .= '<h2>' . esc_html__( 'Indicaciones: Qué tratamos', 'nuvanx-medical' ) . '</h2>';
		$html .= '<ul class="nvx-brand-list">';
		foreach ( $data['indications'] as $ind ) {
			$html .= '<li>' . esc_html( $ind ) . '</li>';
		}
		$html .= '</ul></section>';
	}

	// 5. Precautions
	if ( ! empty( $data['precautions'] ) && is_array( $data['precautions'] ) ) {
		$html .= '<section class="nvx-brand-section">';
		$html .= '<h2>' . esc_html__( 'Precauciones: Cuándo no tratar', 'nuvanx-medical' ) . '</h2>';
		$html .= '<ul class="nvx-brand-list">';
		foreach ( $data['precautions'] as $prec ) {
			$html .= '<li>' . esc_html( $prec ) . '</li>';
		}
		$html .= '</ul></section>';
	}

	// 6. Process
	if ( ! empty( $data['process'] ) && is_array( $data['process'] ) ) {
		$html .= '<section class="nvx-brand-section">';
		$html .= '<h2>' . esc_html__( 'Proceso en clínica', 'nuvanx-medical' ) . '</h2>';
		$html .= '<ol class="nvx-brand-list">';
		foreach ( $data['process'] as $step ) {
			$html .= '<li>' . esc_html( $step ) . '</li>';
		}
		$html .= '</ol></section>';
	}

	// 6.5 Evolution and Risks (Specific for injectable treatments)
	if ( ! empty( $data['evolution'] ) ) {
		$html .= '<section class="nvx-brand-section">';
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
	}

	// 7. FAQs
	if ( ! empty( $data['faqs'] ) && is_array( $data['faqs'] ) ) {
		$html .= '<section class="nvx-brand-section">';
		$html .= '<h2>' . esc_html__( 'Preguntas frecuentes', 'nuvanx-medical' ) . '</h2>';
		$html .= '<div class="nvx-faq-accordion">';
		foreach ( $data['faqs'] as $faq ) {
			$html .= '<details class="nvx-faq-item">';
			$html .= '<summary class="nvx-faq-question">' . esc_html( $faq['q'] ) . '</summary>';
			$html .= '<div class="nvx-faq-answer"><p>' . esc_html( $faq['a'] ) . '</p></div>';
			$html .= '</details>';
		}
		$html .= '</div></section>';
	}

	$html .= '</article>';
	return $html;
}
