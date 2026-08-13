<?php
/**
 * Canonical theme-owned valuation landing.
 *
 * The CMS stores a stable route marker only. This module renders the full
 * hierarchy and the canonical HubSpot mount so staging and production do not
 * depend on historical database HTML.
 *
 * SEO title/description are owned exclusively by nvx-seo-metadata.php. HubSpot
 * script loading is owned exclusively by nvx-runtime-governance.js.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Canonical clinical explanation for the managed valuation landing.
 *
 * A virtual orientation can start the process, while physical examination is
 * still required whenever it is clinically necessary before treatment.
 */
function nvx_valoracion_managed_intro_markup(): string {
	$html  = '<section class="nvx-brand-section nvx-valoracion-intro" id="nvx-valoracion-intro" aria-labelledby="nvx-valoracion-intro-title">';
	$html .= '<div class="nvx-container">';
	$html .= '<p class="nvx-brand-kicker">' . esc_html__( 'Primer paso', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-valoracion-intro-title" class="nvx-heading">' . esc_html__( 'Una consulta médica para orientar tu caso', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p class="nvx-body nvx-body--measure">' . esc_html__( 'Puedes iniciar el proceso con una orientación virtual o reservar directamente una valoración presencial en Chamberí o Salamanca–Goya. La cita suele durar entre 15 y 30 minutos. Durante ese tiempo revisamos el motivo de consulta, las opciones de tratamiento, los tiempos de recuperación y el presupuesto individualizado. Cuando la exploración física sea necesaria para confirmar la indicación, se completa de forma presencial antes de tratar.', 'nuvanx-medical' ) . '</p>';
	$html .= '<p class="nvx-body nvx-body--measure">' . esc_html__( 'Al finalizar tendrás una orientación clara sobre los siguientes pasos. El equipo, bajo la dirección del Dr. Rivera Tejeda, sigue tres criterios:', 'nuvanx-medical' ) . '</p>';
	$html .= '<ol class="nvx-treatment-process__steps nvx-valoracion-steps">';

	$steps = function_exists( 'nvx_valoracion_process_steps' ) ? nvx_valoracion_process_steps() : array();
	foreach ( $steps as $step ) {
		$html .= '<li class="nvx-treatment-process__step">';
		$html .= '<h3 class="nvx-treatment-process__step-title">' . esc_html( $step['title'] ?? '' ) . '</h3>';
		$html .= '<p class="nvx-body">' . esc_html( $step['body'] ?? '' ) . '</p>';
		$html .= '</li>';
	}
	$html .= '</ol>';

	if ( function_exists( 'nvx_contact_privacy_disclaimer_markup' ) ) {
		$html .= nvx_contact_privacy_disclaimer_markup();
	}
	$html .= '</div></section>';

	$html .= '<section class="nvx-brand-section nvx-valoracion-locations" aria-labelledby="nvx-valoracion-loc-title">';
	$html .= '<div class="nvx-container">';
	$html .= '<p class="nvx-brand-kicker">' . esc_html__( 'Sedes', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-valoracion-loc-title" class="nvx-heading">' . esc_html__( 'Ubicaciones autorizadas por Sanidad', 'nuvanx-medical' ) . '</h2>';
	if ( function_exists( 'nvx_contact_clinics_markup' ) ) {
		$html .= nvx_contact_clinics_markup();
	}
	$html .= '</div></section>';

	$wa_url = function_exists( 'nvx_cta_whatsapp_url' ) ? nvx_cta_whatsapp_url() : 'https://wa.me/34689317399';
	$html  .= '<section class="nvx-home-closure" aria-labelledby="nvx-valoracion-closure-title">';
	$html  .= '<div class="nvx-container">';
	$html  .= '<h2 id="nvx-valoracion-closure-title" class="nvx-home-closure__title">' . esc_html__( '¿Dudas sobre tu caso o la indicación?', 'nuvanx-medical' ) . '</h2>';
	$html  .= '<p class="nvx-home-closure__desc">' . esc_html__( 'Nuestro equipo médico revisará tus consultas previas sin compromiso comercial.', 'nuvanx-medical' ) . '</p>';
	$html  .= '<div class="nvx-home-closure__actions">';
	$html  .= '<a href="#nvx-hubspot-form" class="nvx-brand-btn nvx-btn--primary">' . esc_html__( 'Solicitar valoración médica', 'nuvanx-medical' ) . '</a>';
	$html  .= '<a href="' . esc_url( $wa_url ) . '" class="nvx-brand-btn nvx-btn--secondary-on-dark" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Contactar por WhatsApp', 'nuvanx-medical' ) . '</a>';
	$html  .= '</div></div></section>';

	return $html;
}

/**
 * Build the canonical valuation page before form-order and HubSpot MU filters.
 */
function nvx_valoracion_managed_page_markup(): string {
	// Hero image-free by design: block the featured-image injection performed by
	// nvx_ensure_hero_featured_media (the_content prio 12). The managed renderer
	// runs earlier (prio 10), so setting the flag here short-circuits that filter.
	global $nvx_page_shell_has_hero;
	$nvx_page_shell_has_hero = true;

	$valuation_url  = home_url( '/madrid/valoracion/' );
	$doctoralia_url = 'https://www.doctoralia.es/clinicas/nuvanx-medicina-estetica-laser';
	$wa_url         = function_exists( 'nvx_cta_whatsapp_url' ) ? nvx_cta_whatsapp_url() : 'https://wa.me/34689317399';
	$form_id        = defined( 'NVX_VALORACION_HS_FRAME_FORM_ID' ) ? NVX_VALORACION_HS_FRAME_FORM_ID : '5042522a-0bc5-4381-ac3e-5aee8649b69c';
	$portal_id      = defined( 'NVX_VALORACION_HS_FRAME_PORTAL_ID' ) ? NVX_VALORACION_HS_FRAME_PORTAL_ID : '147416356';

	$html = '<div class="nvx-brand-page nvx-valoracion-page" id="nvx-valoracion-main" aria-labelledby="nvx-valoracion-h1">';

	// Conversion-first page header: site header/menu -> concise page heading -> form.
	$html .= '<section class="nvx-brand-hero nvx-valoracion-hero" aria-labelledby="nvx-valoracion-h1">';
	$html .= '<div class="nvx-brand-hero__inner">';
	$html .= '<div class="nvx-brand-hero__copy">';
	$html .= '<p class="nvx-brand-kicker">' . esc_html__( 'VALORACIÓN MÉDICA · MADRID', 'nuvanx-medical' ) . '</p>';
	$html .= '<h1 id="nvx-valoracion-h1" class="nvx-brand-hero__title">' . esc_html__( 'Valoración médica estética en Madrid', 'nuvanx-medical' ) . '</h1>';
	$html .= '</div></div></section>';

	// The form is deliberately the first content block after the heading.
	$html .= '<section class="nvx-brand-section nvx-hubspot-form-section nvx-form-stage" id="nvx-hubspot-form" aria-labelledby="nvx-valoracion-form-title">';
	$html .= '<div class="nvx-brand-section__inner">';
	$html .= '<p class="nvx-brand-kicker">' . esc_html__( 'SOLICITUD DE VALORACIÓN', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-valoracion-form-title" class="nvx-brand-title">' . esc_html__( 'Cuéntanos qué quieres valorar', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p class="nvx-brand-body">' . esc_html__( 'Completa tus datos e indica la zona o tratamiento de interés. El equipo de NUVANX te contactará para coordinar una orientación virtual o una valoración presencial. La cita suele durar entre 15 y 30 minutos y permite revisar tu caso, las opciones de tratamiento y el presupuesto individualizado.', 'nuvanx-medical' ) . '</p>';
	$html .= '<div class="nvx-valoracion-direct-contact">';
	$html .= '<p class="nvx-valoracion-direct-contact__label">' . esc_html__( '¿Prefieres coordinar directamente o tienes alguna duda?', 'nuvanx-medical' ) . '</p>';
	$html .= '<div class="nvx-valoracion-direct-contact__actions">';
	$html .= '<a href="' . esc_url( $wa_url ) . '" class="nvx-brand-btn nvx-btn--secondary" target="_blank" rel="noopener noreferrer">' . esc_html__( 'WhatsApp directo', 'nuvanx-medical' ) . '</a>';
	$html .= '<a href="tel:+34689317399" class="nvx-brand-btn nvx-btn--secondary">' . esc_html__( 'Llamar: 689 31 73 99', 'nuvanx-medical' ) . '</a>';
	$html .= '</div></div>';
	$html .= '<div class="nvx-form nvx-hs-native-section" aria-label="' . esc_attr__( 'Formulario de valoración médica NUVANX', 'nuvanx-medical' ) . '">';
	$html .= '<div class="nvx-hs-native-box">';
	$html .= '<div id="nvx-hubspot-native-form" class="nvx-hubspot-native-form-v2" data-nvx-hubspot-native="1" data-nvx-hubspot-eager="1" data-form-id="' . esc_attr( $form_id ) . '" data-portal-id="' . esc_attr( $portal_id ) . '" data-page-origin="' . esc_attr__( 'Valoración médica estética en Madrid', 'nuvanx-medical' ) . '" data-page-url="' . esc_url( $valuation_url ) . '"></div>';
	$html .= '<p class="nvx-copy nvx-form-note">' . esc_html__( 'La información enviada se utiliza para gestionar tu solicitud. La indicación final depende de valoración médica y los resultados pueden variar según cada paciente.', 'nuvanx-medical' ) . '</p>';
	$html .= '<p class="nvx-copy nvx-form-note nvx-doctoralia-proof">' . esc_html__( 'Más de 100 opiniones verificadas en Doctoralia.', 'nuvanx-medical' ) . ' <a class="nvx-brand-inline-link" href="' . esc_url( $doctoralia_url ) . '" target="_blank" rel="noopener noreferrer external">' . esc_html__( 'Consultar opiniones verificadas', 'nuvanx-medical' ) . '</a></p>';
	$html .= '</div></div></div></section>';

	// Keep clinical explanation and locations after the conversion block.
	$html .= nvx_valoracion_managed_intro_markup();

	// Runtime governance owns the HubSpot loader. Trigger the page mount once all
	// DOMContentLoaded listeners are installed so the dedicated form does not wait
	// for scroll/intersection before loading.
	$html .= '<script id="nvx-valoracion-form-eager">window.addEventListener("load",function(){var host=document.getElementById("nvx-hubspot-native-form");if(host){host.dispatchEvent(new Event("focusin",{bubbles:true}));}},{once:true});</script>';
	$html .= '</div>';

	return $html;
}

/**
 * Replace any prior body or CMS marker with the canonical managed landing.
 *
 * @param string $content Original page content.
 */
function nvx_render_managed_valoracion_page( $content ): string {
	$content = is_string( $content ) ? $content : '';
	if ( is_admin() || ! function_exists( 'nvx_is_valoracion_page_request' ) || ! nvx_is_valoracion_page_request() ) {
		return $content;
	}

	return nvx_valoracion_managed_page_markup();
}
add_filter( 'the_content', 'nvx_render_managed_valoracion_page', NVX_HOOK_PRIO_VALORACION_MANAGED );

/**
 * Register valoración page as page owner to prevent shell hero duplication.
 *
 * When the shell evaluates $has_managed_editorial in nvx-page-shell.php,
 * this filter ensures valoración pages are recognized as managed,
 * preventing the shell from rendering its own hero in addition to
 * the renderer's hero.
 */
add_filter(
	'nvx_page_owner',
	function ( $owner ) {
		if ( ! empty( $owner ) || is_admin() ) {
			return $owner;
		}
		if ( function_exists( 'nvx_is_valoracion_page_request' ) && nvx_is_valoracion_page_request() ) {
			return 'nvx_valoracion_managed';
		}
		return $owner;
	},
	10
);
