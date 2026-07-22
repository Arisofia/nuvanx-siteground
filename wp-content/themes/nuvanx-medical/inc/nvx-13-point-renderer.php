<?php
/**
 * Shared renderer for governed clinical, protocol and anatomical pages.
 *
 * Supports the original 13-point data matrix and the governed roadmap fields
 * used by Phase 1 and Phase 2 without duplicating presentation logic.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Render a titled list section when values are available. */
function nvx_render_matrix_list_section( string $title, array $items, bool $ordered = false ): string {
	if ( array() === $items ) {
		return '';
	}
	$tag   = $ordered ? 'ol' : 'ul';
	$html  = '<section class="nvx-brand-section"><h2>' . esc_html( $title ) . '</h2>';
	$html .= '<' . $tag . ' class="nvx-brand-list">';
	foreach ( $items as $item ) {
		$html .= '<li>' . esc_html( (string) $item ) . '</li>';
	}
	return $html . '</' . $tag . '></section>';
}

/** Universal renderer for the governed clinical data matrix. */
function nvx_render_13_point_matrix( array $data ): string {
	$html  = '<article class="nvx-brand-readable nvx-protocol-page nvx-shell">';
	$html .= '<header class="nvx-strategy-intro">';
	if ( ! empty( $data['kicker'] ) ) {
		$html .= '<p class="nvx-brand-kicker">' . esc_html( (string) $data['kicker'] ) . '</p>';
	}
	$html .= '<h1 class="nvx-strategy-title">' . esc_html( (string) ( $data['h1'] ?? $data['title'] ?? '' ) ) . '</h1>';
	if ( ! empty( $data['lead'] ) ) {
		$html .= '<p class="nvx-brand-lead">' . esc_html( (string) $data['lead'] ) . '</p>';
	}
	$html .= '<p><a class="nvx-btn nvx-btn--primary" href="' . esc_url( home_url( '/madrid/valoracion/' ) ) . '">' . esc_html__( 'Solicitar valoración médica', 'nuvanx-medical' ) . '</a></p>';
	$html .= '<p class="nvx-brand-microcopy">' . esc_html__( 'La indicación, las modalidades, la evolución y el presupuesto se determinan después de la exploración médica.', 'nuvanx-medical' ) . '</p>';
	$html .= '</header>';

	if ( ! empty( $data['diagnosis'] ) ) {
		$html .= '<section class="nvx-brand-section"><h2>' . esc_html__( 'El valor del diagnóstico médico', 'nuvanx-medical' ) . '</h2><p>' . esc_html( (string) $data['diagnosis'] ) . '</p></section>';
	}

	$objectives = array();
	if ( ! empty( $data['objectives'] ) && is_array( $data['objectives'] ) ) {
		$objectives = $data['objectives'];
	} elseif ( ! empty( $data['indications'] ) && is_array( $data['indications'] ) ) {
		$objectives = $data['indications'];
	}
	$html .= nvx_render_matrix_list_section( esc_html__( 'Objetivos clínicos que se valoran', 'nuvanx-medical' ), $objectives );

	if ( ! empty( $data['planning_levels'] ) && is_array( $data['planning_levels'] ) ) {
		$html .= '<section class="nvx-brand-section"><h2>' . esc_html__( 'Niveles de planificación, no paquetes cerrados', 'nuvanx-medical' ) . '</h2><div class="nvx-catalog-grid">';
		foreach ( $data['planning_levels'] as $name => $body ) {
			$html .= '<article class="nvx-catalog-card"><div class="nvx-catalog-card__main"><h3 class="nvx-catalog-card__title">' . esc_html( (string) $name ) . '</h3><p class="nvx-catalog-card__body">' . esc_html( (string) $body ) . '</p></div></article>';
		}
		$html .= '</div></section>';
	}

	if ( ! empty( $data['mechanism'] ) ) {
		$html .= '<section class="nvx-brand-section"><h2>' . esc_html__( 'Cómo puede abordarse', 'nuvanx-medical' ) . '</h2><p>' . esc_html( (string) $data['mechanism'] ) . '</p></section>';
	}
	if ( ! empty( $data['modalities'] ) && is_array( $data['modalities'] ) ) {
		$html .= nvx_render_matrix_list_section( esc_html__( 'Qué puede formar parte del plan', 'nuvanx-medical' ), $data['modalities'] );
		$html .= '<section class="nvx-brand-section nvx-brand-section--compact"><p>' . esc_html__( 'No todas las modalidades se utilizan en todos los pacientes.', 'nuvanx-medical' ) . '</p></section>';
	}

	$limits = array();
	if ( ! empty( $data['limits'] ) && is_array( $data['limits'] ) ) {
		$limits = $data['limits'];
	} elseif ( ! empty( $data['precautions'] ) && is_array( $data['precautions'] ) ) {
		$limits = $data['precautions'];
	}
	$html .= nvx_render_matrix_list_section( esc_html__( 'Cuándo no es el tratamiento adecuado', 'nuvanx-medical' ), $limits );

	if ( ! empty( $data['process'] ) && is_array( $data['process'] ) ) {
		$html .= nvx_render_matrix_list_section( esc_html__( 'Proceso clínico', 'nuvanx-medical' ), $data['process'], true );
	}

	if ( ! empty( $data['evolution'] ) ) {
		$html .= '<section class="nvx-brand-section"><h2>' . esc_html__( 'Evolución y seguridad', 'nuvanx-medical' ) . '</h2><p>' . esc_html( (string) $data['evolution'] ) . '</p>';
		if ( ! empty( $data['risks'] ) && is_array( $data['risks'] ) ) {
			$html .= '<h3>' . esc_html__( 'Riesgos que deben explicarse', 'nuvanx-medical' ) . '</h3><ul class="nvx-brand-list">';
			foreach ( $data['risks'] as $risk ) {
				$html .= '<li>' . esc_html( (string) $risk ) . '</li>';
			}
			$html .= '</ul>';
		}
		if ( ! empty( $data['combinations'] ) && is_array( $data['combinations'] ) ) {
			$html .= '<h3>' . esc_html__( 'Combinaciones posibles', 'nuvanx-medical' ) . '</h3><ul class="nvx-brand-list">';
			foreach ( $data['combinations'] as $combination ) {
				$html .= '<li>' . esc_html( (string) $combination ) . '</li>';
			}
			$html .= '</ul>';
		}
		$html .= '</section>';
	}

	if ( ! empty( $data['faqs'] ) && is_array( $data['faqs'] ) ) {
		$html .= '<section class="nvx-brand-section"><h2>' . esc_html__( 'Preguntas frecuentes', 'nuvanx-medical' ) . '</h2><div class="nvx-faq-accordion">';
		foreach ( $data['faqs'] as $faq ) {
			if ( empty( $faq['q'] ) || empty( $faq['a'] ) ) {
				continue;
			}
			$html .= '<details class="nvx-faq-item"><summary class="nvx-faq-question">' . esc_html( (string) $faq['q'] ) . '</summary><div class="nvx-faq-answer"><p>' . esc_html( (string) $faq['a'] ) . '</p></div></details>';
		}
		$html .= '</div></section>';
	}

	$html .= '<section class="nvx-brand-section"><h2>' . esc_html__( 'Tu primera valoración clínica', 'nuvanx-medical' ) . '</h2><p>' . esc_html__( 'La consulta determina qué puede tener sentido, qué alternativas existen y en qué situaciones es preferible esperar, derivar o no intervenir.', 'nuvanx-medical' ) . '</p><p><a class="nvx-btn nvx-btn--primary" href="' . esc_url( home_url( '/madrid/valoracion/' ) ) . '">' . esc_html__( 'Iniciar valoración médica', 'nuvanx-medical' ) . '</a></p></section>';
	$html .= '</article>';
	return $html;
}
