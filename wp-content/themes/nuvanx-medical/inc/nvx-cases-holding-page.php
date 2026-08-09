<?php
/**
 * Canonical holding state for the patient-cases route.
 *
 * Until real patient evidence has explicit editorial approval, the public route
 * stays reachable but noindex. This renderer prevents unfinished CMS markup from
 * leaking into the visual system without inventing patient outcomes.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether the current request is the patient-cases page.
 */
function nvx_is_cases_page_request(): bool {
	if ( ! is_singular( 'page' ) ) {
		return false;
	}

	$slug = (string) get_post_field( 'post_name', get_queried_object_id() );
	return 'casos-de-pacientes' === $slug;
}

/**
 * Whether real patient evidence has explicit publication approval.
 */
function nvx_cases_publication_ready(): bool {
	$page_id = function_exists( 'nvx_page_id_by_slug' )
		? nvx_page_id_by_slug( 'casos-de-pacientes' )
		: ( nvx_is_cases_page_request() ? (int) get_queried_object_id() : 0 );

	return $page_id > 0 && '1' === (string) get_post_meta( $page_id, '_nvx_cases_publication_ready', true );
}

/**
 * Build the responsible holding page shown before evidence approval.
 */
function nvx_cases_holding_markup(): string {
	global $nvx_page_shell_has_hero;
	$nvx_page_shell_has_hero = true;

	$valuation_url = home_url( '/madrid/valoracion/' );

	$html  = '<div class="nvx-brand-page nvx-cases-holding" id="nvx-cases-main" aria-labelledby="nvx-cases-h1">';
	$html .= '<section class="nvx-brand-hero nvx-cases-holding__hero" aria-labelledby="nvx-cases-h1">';
	$html .= '<div class="nvx-brand-hero__inner"><div class="nvx-brand-hero__copy">';
	$html .= '<p class="nvx-brand-kicker">' . esc_html__( 'EVIDENCIA CLÍNICA · MADRID', 'nuvanx-medical' ) . '</p>';
	$html .= '<h1 id="nvx-cases-h1" class="nvx-brand-hero__title">' . esc_html__( 'Casos de pacientes', 'nuvanx-medical' ) . '</h1>';
	$html .= '</div></div></section>';

	$html .= '<section class="nvx-brand-section nvx-cases-holding__intro" aria-labelledby="nvx-cases-intro-title">';
	$html .= '<div class="nvx-shell nvx-brand-section__inner">';
	$html .= '<p class="nvx-brand-kicker">' . esc_html__( 'PUBLICACIÓN RESPONSABLE', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-cases-intro-title" class="nvx-brand-title">' . esc_html__( 'Evolución documentada, no promesas', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p class="nvx-brand-body nvx-cases-holding__lead">' . esc_html__( 'Estamos preparando esta sección con casos clínicos reales revisados por el equipo médico. Solo publicaremos material con consentimiento documentado y contexto suficiente para interpretar la evolución sin convertir una imagen en una promesa de resultado.', 'nuvanx-medical' ) . '</p>';
	$html .= '<div class="nvx-cases-holding__grid" role="list">';

	$principles = array(
		array(
			'title' => __( 'Misma persona y seguimiento', 'nuvanx-medical' ),
			'body'  => __( 'Cada caso identificará el momento de seguimiento y evitará presentar imágenes de personas distintas como una misma evolución.', 'nuvanx-medical' ),
		),
		array(
			'title' => __( 'Fotografía comparable', 'nuvanx-medical' ),
			'body'  => __( 'Cuando sea posible, mantendremos encuadre, posición y luz comparables para reducir distorsiones visuales.', 'nuvanx-medical' ),
		),
		array(
			'title' => __( 'Contexto clínico', 'nuvanx-medical' ),
			'body'  => __( 'La indicación, el tratamiento realizado, el seguimiento y los límites del caso acompañarán a las imágenes.', 'nuvanx-medical' ),
		),
	);

	foreach ( $principles as $principle ) {
		$html .= '<article class="nvx-brand-card nvx-cases-holding__card" role="listitem">';
		$html .= '<h3 class="nvx-brand-card__title">' . esc_html( $principle['title'] ) . '</h3>';
		$html .= '<p class="nvx-brand-card__body">' . esc_html( $principle['body'] ) . '</p>';
		$html .= '</article>';
	}
	$html .= '</div></div></section>';

	$html .= '<section class="nvx-brand-section nvx-cases-holding__scope" aria-labelledby="nvx-cases-scope-title">';
	$html .= '<div class="nvx-shell nvx-brand-section__inner">';
	$html .= '<p class="nvx-brand-kicker">' . esc_html__( 'EN PREPARACIÓN', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-cases-scope-title" class="nvx-brand-title">' . esc_html__( 'Qué encontrarás cuando se publique', 'nuvanx-medical' ) . '</h2>';
	$html .= '<div class="nvx-cases-holding__grid" role="list">';

	$case_groups = array(
		array(
			'kicker' => __( 'ROSTRO', 'nuvanx-medical' ),
			'title'  => __( 'Contorno y calidad de piel', 'nuvanx-medical' ),
			'body'   => __( 'Casos seleccionados por indicación médica, con seguimiento suficiente para explicar qué cambió y qué no.', 'nuvanx-medical' ),
		),
		array(
			'kicker' => __( 'CUERPO', 'nuvanx-medical' ),
			'title'  => __( 'Grasa localizada y firmeza', 'nuvanx-medical' ),
			'body'   => __( 'Evoluciones corporales contextualizadas por zona, técnica, tiempos y características de partida.', 'nuvanx-medical' ),
		),
		array(
			'kicker' => __( 'PIEL', 'nuvanx-medical' ),
			'title'  => __( 'Textura, cicatrices y fotodaño', 'nuvanx-medical' ),
			'body'   => __( 'Documentación clínica que permita valorar respuesta y recuperación sin ocultar variabilidad individual.', 'nuvanx-medical' ),
		),
	);

	foreach ( $case_groups as $group ) {
		$html .= '<article class="nvx-brand-card nvx-cases-holding__card" role="listitem">';
		$html .= '<p class="nvx-brand-card__kicker">' . esc_html( $group['kicker'] ) . '</p>';
		$html .= '<h3 class="nvx-brand-card__title">' . esc_html( $group['title'] ) . '</h3>';
		$html .= '<p class="nvx-brand-card__body">' . esc_html( $group['body'] ) . '</p>';
		$html .= '</article>';
	}
	$html .= '</div></div></section>';

	$html .= '<section class="nvx-brand-section nvx-cases-holding__criteria" aria-labelledby="nvx-cases-criteria-title">';
	$html .= '<div class="nvx-shell nvx-brand-section__inner">';
	$html .= '<p class="nvx-brand-kicker">' . esc_html__( 'CRITERIO MÉDICO', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-cases-criteria-title" class="nvx-brand-title">' . esc_html__( 'Antes de comparar casos, revisamos su situación clínica', 'nuvanx-medical' ) . '</h2>';
	$html .= '<div class="nvx-cases-holding__criteria-grid">';
	$html .= '<div><p class="nvx-brand-body">' . esc_html__( 'Una fotografía aislada no explica una indicación. Por eso cada publicación deberá identificar, cuando corresponda, la zona tratada, la técnica utilizada, el tiempo transcurrido y las condiciones de la toma fotográfica.', 'nuvanx-medical' ) . '</p></div>';
	$html .= '<div><p class="nvx-brand-body">' . esc_html__( 'Los resultados pueden variar entre pacientes. La valoración médica individual sigue siendo el punto de partida para determinar si un tratamiento tiene indicación y qué expectativas son razonables.', 'nuvanx-medical' ) . '</p></div>';
	$html .= '</div></div></section>';

	$html .= '<section class="nvx-brand-section nvx-cases-holding__cta" aria-labelledby="nvx-cases-cta-title">';
	$html .= '<div class="nvx-shell nvx-brand-section__inner">';
	$html .= '<p class="nvx-brand-kicker">' . esc_html__( 'TU CASO ES INDIVIDUAL', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-cases-cta-title" class="nvx-brand-title">' . esc_html__( 'Empieza por una valoración médica', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p class="nvx-brand-body nvx-cases-holding__lead">' . esc_html__( 'Revisamos tu caso, la posible indicación, los tiempos de recuperación y el presupuesto individualizado antes de decidir cualquier tratamiento.', 'nuvanx-medical' ) . '</p>';
	$html .= '<a class="nvx-brand-btn nvx-brand-btn--primary" href="' . esc_url( $valuation_url . '#nvx-hubspot-form' ) . '">' . esc_html__( 'Solicitar valoración médica', 'nuvanx-medical' ) . '</a>';
	$html .= '</div></section>';
	$html .= '</div>';

	return $html;
}

/**
 * Replace unfinished CMS HTML only while evidence is not publication-ready.
 *
 * @param string $content Original WordPress content.
 */
function nvx_render_cases_holding_page( $content ): string {
	$content = is_string( $content ) ? $content : '';
	if ( is_admin() || ! is_main_query() || ! nvx_is_cases_page_request() || nvx_cases_publication_ready() ) {
		return $content;
	}

	return nvx_cases_holding_markup();
}
add_filter( 'the_content', 'nvx_render_cases_holding_page', 10 );

/**
 * Prevent page-shell hero duplication while the holding renderer owns the page.
 */
add_filter(
	'nvx_page_owner',
	function ( $owner ) {
		if ( ! empty( $owner ) || is_admin() ) {
			return $owner;
		}
		if ( nvx_is_cases_page_request() && ! nvx_cases_publication_ready() ) {
			return 'nvx_cases_holding';
		}
		return $owner;
	},
	10
);

/**
 * Load only the holding-page styles while the evidence page is under review.
 */
function nvx_enqueue_cases_holding_assets(): void {
	if ( is_admin() || ! nvx_is_cases_page_request() || nvx_cases_publication_ready() ) {
		return;
	}

	$css_relative = '/assets/css/nvx-cases-holding.css';
	$css_path     = get_template_directory() . $css_relative;
	if ( ! file_exists( $css_path ) ) {
		return;
	}

	$version = function_exists( 'nvx_asset_version' )
		? nvx_asset_version( $css_relative )
		: (string) filemtime( $css_path );

	wp_enqueue_style(
		'nvx-cases-holding',
		get_template_directory_uri() . $css_relative,
		array( 'nvx-components', 'nvx-patterns' ),
		$version
	);
}
add_action( 'wp_enqueue_scripts', 'nvx_enqueue_cases_holding_assets', 20 );
