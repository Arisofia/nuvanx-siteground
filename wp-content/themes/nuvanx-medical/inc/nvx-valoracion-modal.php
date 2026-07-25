<?php
/**
 * Site-wide valoración form modal.
 *
 * Opens from opted-in CTAs outside /contacto/, the full valoración landing and
 * post-conversion pages. Contacto remains a form-free NAP and routing page.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether the modal should load on this front request.
 */
function nvx_valoracion_modal_enabled(): bool {
	if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || is_feed() ) {
		return false;
	}

	// Contacto is contractually form-free: direct links route to the full landing.
	if (
		is_page( 14 )
		|| is_page( 'contacto' )
		|| ( function_exists( 'nvx_is_contacto_page_request' ) && nvx_is_contacto_page_request() )
	) {
		return false;
	}

	// Already on the form landing: keep full-page UX.
	if ( function_exists( 'nvx_theme_is_valoracion_form_page' ) && nvx_theme_is_valoracion_form_page() ) {
		return false;
	}
	if ( function_exists( 'nvx_theme_is_valoracion_landing' ) && nvx_theme_is_valoracion_landing() ) {
		return false;
	}
	if ( function_exists( 'nvx_theme_is_thank_you_page' ) && nvx_theme_is_thank_you_page() ) {
		return false;
	}

	return (bool) apply_filters( 'nvx_valoracion_modal_enabled', true );
}

/**
 * HubSpot portal / form / region for the modal.
 *
 * @return array{portal_id:string,form_id:string,region:string,script_url:string}
 */
function nvx_valoracion_modal_hubspot_config(): array {
	$portal = defined( 'NVX_VALORACION_HS_FRAME_PORTAL_ID' ) ? (string) NVX_VALORACION_HS_FRAME_PORTAL_ID : '147416356';
	$form   = defined( 'NVX_VALORACION_HS_FRAME_FORM_ID' ) ? (string) NVX_VALORACION_HS_FRAME_FORM_ID : '5042522a-0bc5-4381-ac3e-5aee8649b69c';
	$region = defined( 'NVX_VALORACION_HS_FRAME_REGION' ) ? (string) NVX_VALORACION_HS_FRAME_REGION : 'eu1';

	return array(
		'portal_id'  => $portal,
		'form_id'    => $form,
		'region'     => $region,
		'script_url' => 'https://js-eu1.hsforms.net/forms/embed/' . $portal . '.js',
	);
}

/**
 * Modal dialog markup.
 */
function nvx_valoracion_modal_markup(): string {
	$cfg = nvx_valoracion_modal_hubspot_config();

	$privacy = esc_url( home_url( '/politica-privacidad/' ) );
	$page    = function_exists( 'nvx_cta_valoracion_url' )
		? nvx_cta_valoracion_url()
		: home_url( '/madrid/valoracion/' );

	$html  = '<div id="nvx-valoracion-modal" class="nvx-valoracion-modal" role="dialog" aria-modal="true" aria-labelledby="nvx-valoracion-modal-title" aria-hidden="true" hidden>';
	$html .= '<div class="nvx-valoracion-modal__backdrop" data-nvx-valoracion-modal-close tabindex="-1"></div>';
	$html .= '<div class="nvx-valoracion-modal__panel" role="document">';
	$html .= '<button type="button" class="nvx-valoracion-modal__close" data-nvx-valoracion-modal-close aria-label="' . esc_attr__( 'Cerrar formulario', 'nuvanx-medical' ) . '">&times;</button>';
	$html .= '<p class="nvx-eyebrow nvx-valoracion-modal__kicker">' . esc_html__( 'Valoración médica', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-valoracion-modal-title" class="nvx-valoracion-modal__title">' . esc_html__( 'Solicita una valoración médica', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p class="nvx-valoracion-modal__lead">' . esc_html__( 'Normalmente, un miembro del equipo te contactará durante el siguiente día laborable para confirmar la fecha de valoración.', 'nuvanx-medical' ) . '</p>';
	$html .= '<div id="nvx-valoracion-modal-form" class="nvx-valoracion-modal__form nvx-hubspot-form-section" data-nvx-valoracion-modal-form>';
	$html .= '<div class="hs-form-frame" data-region="' . esc_attr( $cfg['region'] ) . '" data-form-id="' . esc_attr( $cfg['form_id'] ) . '" data-portal-id="' . esc_attr( $cfg['portal_id'] ) . '"></div>';
	$html .= '</div>';
	$html .= '<p class="nvx-valoracion-modal__legal">' . wp_kses(
		sprintf(
			__( 'Al enviar aceptas la <a class="nvx-text-link" href="%s">Política de privacidad</a>.', 'nuvanx-medical' ),
			$privacy
		),
		array(
			'a' => array(
				'class' => true,
				'href'  => true,
			),
		)
	) . '</p>';
	$html .= '<p class="nvx-valoracion-modal__fallback"><a class="nvx-text-link" href="' . esc_url( $page ) . '">' . esc_html__( 'Abrir página de valoración completa', 'nuvanx-medical' ) . '</a></p>';
	$html .= '</div></div>';

	return $html;
}

/**
 * Print modal shell in footer on eligible public pages.
 */
function nvx_valoracion_modal_render(): void {
	if ( ! nvx_valoracion_modal_enabled() ) {
		return;
	}

	echo nvx_valoracion_modal_markup(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
add_action( 'wp_footer', 'nvx_valoracion_modal_render', 25 );

/**
 * Enqueue the modal runtime configuration, HubSpot embed and valuation-page fallback.
 */
function nvx_valoracion_modal_assets(): void {
	$uri = get_template_directory_uri();

	if ( function_exists( 'nvx_theme_is_valoracion_form_page' ) && nvx_theme_is_valoracion_form_page() ) {
		$relative = 'assets/js/nvx-valoracion-page.js';
		wp_enqueue_script(
			'nvx-valoracion-page',
			$uri . '/' . $relative,
			array( 'nvx-main' ),
			function_exists( 'nvx_asset_version' ) ? nvx_asset_version( $relative ) : null,
			true
		);
		return;
	}

	if ( ! nvx_valoracion_modal_enabled() ) {
		return;
	}

	$cfg  = nvx_valoracion_modal_hubspot_config();
	$page = function_exists( 'nvx_cta_valoracion_url' )
		? nvx_cta_valoracion_url()
		: home_url( '/madrid/valoracion/' );

	wp_add_inline_script(
		'nvx-main',
		'window.nvxValoracionModal = ' . wp_json_encode(
			array(
				'enabled' => true,
				'pageUrl' => esc_url_raw( $page ),
			)
		) . ';',
		'before'
	);

	wp_enqueue_script(
		'nvx-hubspot-forms-embed',
		$cfg['script_url'],
		array(),
		null,
		true
	);
}
add_action( 'wp_enqueue_scripts', 'nvx_valoracion_modal_assets', 30 );

/**
 * Load the terminal regression layer after the canonical theme styles.
 */
function nvx_ui_regression_assets(): void {
	$relative = 'assets/css/nvx-ui-regressions.css';
	wp_enqueue_style(
		'nvx-ui-regressions',
		get_template_directory_uri() . '/' . $relative,
		array( 'nvx-footer' ),
		function_exists( 'nvx_asset_version' ) ? nvx_asset_version( $relative ) : null
	);
}
add_action( 'wp_enqueue_scripts', 'nvx_ui_regression_assets', 100 );

/**
 * Technology and injectable pages that share the canonical institutional hero.
 *
 * @return string[]
 */
function nvx_ui_canonical_technology_slugs(): array {
	return array(
		'medicina-estetica-laser',
		'endolift-facial-papada-mandibula',
		'endolaser-corporal-grasa-localizada',
		'exion-btl',
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
	);
}

/**
 * Apply the canonical hero class after the global hierarchy has moved long copy.
 *
 * @param string $content Page HTML.
 * @return string
 */
function nvx_ui_normalize_technology_header( string $content ): string {
	if (
		is_admin()
		|| wp_doing_ajax()
		|| ( defined( 'REST_REQUEST' ) && REST_REQUEST )
		|| ! is_page( nvx_ui_canonical_technology_slugs() )
		|| false === stripos( $content, 'hero' )
		|| false !== stripos( $content, 'nvx-canonical-page-hero' )
		|| ! class_exists( 'DOMDocument' )
		|| ! function_exists( 'nvx_normalize_canonical_page_hero' )
		|| ! function_exists( 'nvx_find_hero_copy_node' )
	) {
		return $content;
	}

	$previous_errors = libxml_use_internal_errors( true );
	$document        = new DOMDocument( '1.0', 'UTF-8' );
	$wrapped         = '<div id="nvx-ui-regression-root">' . $content . '</div>';
	$loaded          = $document->loadHTML(
		'<?xml encoding="utf-8" ?>' . $wrapped,
		LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
	);

	if ( ! $loaded ) {
		libxml_clear_errors();
		libxml_use_internal_errors( $previous_errors );
		return $content;
	}

	$xpath  = new DOMXPath( $document );
	$heroes = $xpath->query(
		'//*[@id="nvx-ui-regression-root"]//*[contains(concat(" ", normalize-space(@class), " "), " nvx-brand-hero ") or contains(concat(" ", normalize-space(@class), " "), " nvx-editorial-hero ") or contains(concat(" ", normalize-space(@class), " "), " nvx-page-hero ") or contains(concat(" ", normalize-space(@class), " "), " nvx-hero-section ")]'
	);

	if ( false === $heroes || 0 === $heroes->length ) {
		libxml_clear_errors();
		libxml_use_internal_errors( $previous_errors );
		return $content;
	}

	$hero = $heroes->item( 0 );
	if ( $hero instanceof DOMElement ) {
		nvx_normalize_canonical_page_hero( $xpath, $hero );
		nvx_find_hero_copy_node( $xpath, $hero, true );
	}

	$root   = $document->getElementById( 'nvx-ui-regression-root' );
	$output = '';
	if ( $root ) {
		foreach ( $root->childNodes as $child ) {
			$output .= $document->saveHTML( $child );
		}
	}

	libxml_clear_errors();
	libxml_use_internal_errors( $previous_errors );

	return is_string( $output ) && '' !== trim( $output ) ? $output : $content;
}
add_filter( 'the_content', 'nvx_ui_normalize_technology_header', 145 );
