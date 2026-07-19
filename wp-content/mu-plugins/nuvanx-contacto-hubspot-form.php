<?php
/**
 * Plugin Name: NUVANX Contacto HubSpot Form
 * Description: Mounts the dedicated HubSpot contact form and enforces temporary SEO/claims publication safeguards.
 * Version: 2026.07.19.2
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'NVX_CONTACTO_HS_PORTAL_ID' ) ) {
	define( 'NVX_CONTACTO_HS_PORTAL_ID', '147416356' );
}
if ( ! defined( 'NVX_CONTACTO_HS_REGION' ) ) {
	define( 'NVX_CONTACTO_HS_REGION', 'eu1' );
}

/**
 * Render the dedicated contact form. The form ID must be supplied in wp-config.php
 * so deployments cannot silently reuse the medical-assessment form.
 */
function nvx_contacto_hubspot_form_markup(): string {
	$form_id = defined( 'NVX_CONTACTO_HS_FORM_ID' ) ? strtolower( trim( (string) NVX_CONTACTO_HS_FORM_ID ) ) : '';

	if ( '5042522a-0bc5-4381-ac3e-5aee8649b69c' === $form_id ) {
		_doing_it_wrong( __FUNCTION__, 'The assessment form cannot be used on /contacto/.', '2026.07.18' );
		$form_id = '';
	}

	if ( 1 !== preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $form_id ) ) {
		return '<div class="nvx-form-status" role="status"><p>'
			. esc_html__( 'El formulario de contacto no está disponible temporalmente. Puedes contactar con cualquiera de nuestras clínicas por teléfono o WhatsApp.', 'nuvanx-medical' )
			. '</p></div>';
	}

	$portal_id = preg_replace( '/\D+/', '', (string) NVX_CONTACTO_HS_PORTAL_ID );
	$region    = preg_replace( '/[^a-z0-9-]/i', '', (string) NVX_CONTACTO_HS_REGION );
	$script    = 'https://js-' . $region . '.hsforms.net/forms/embed/' . $portal_id . '.js';

	return '<script src="' . esc_url( $script ) . '" defer></script>'
		. '<div class="hs-form-frame" data-region="' . esc_attr( $region ) . '" data-form-id="' . esc_attr( $form_id ) . '" data-portal-id="' . esc_attr( $portal_id ) . '"></div>'
		. '<p class="nvx-form__privacy-note">' . esc_html__( 'Al enviar tus datos aceptas la', 'nuvanx-medical' ) . ' '
		. '<a href="' . esc_url( home_url( '/politica-privacidad/' ) ) . '">' . esc_html__( 'Política de privacidad', 'nuvanx-medical' ) . '</a>.</p>';
}

/**
 * Remove quantitative trust badges until every figure has an approved evidence
 * owner, source, calculation period and refresh process. This prevents the
 * unverified 3,500+, 4.8/5, 15+ and 89% claims from reaching rendered HTML.
 */
function nvx_remove_unverified_quantitative_trust_badges( string $content ): string {
	if ( false === strpos( $content, 'nvx-trust-badges' ) ) {
		return $content;
	}

	$filtered = preg_replace(
		'#<section\b[^>]*\bnvx-trust-badges\b[^>]*>.*?</section>#isu',
		'',
		$content
	);

	return is_string( $filtered ) ? $filtered : $content;
}
add_filter( 'the_content', 'nvx_remove_unverified_quantitative_trust_badges', 22 );

/**
 * Add the contact-page Open Graph image even when Yoast has no existing image
 * presenter. The legacy wpseo_opengraph_image filter only alters an image that
 * already exists, so use Yoast's image-container hook as the canonical fallback.
 *
 * @param mixed $image_container Yoast Open Graph image container.
 */
function nvx_contacto_add_yoast_opengraph_image( $image_container ): void {
	if ( ! function_exists( 'nvx_contacto_audit_is_contact_page' ) || ! nvx_contacto_audit_is_contact_page() ) {
		return;
	}

	$image_url = home_url( '/wp-content/uploads/2026/07/consulta-medica-personalizada-nuvanx-madrid.webp' );
	$image_id  = function_exists( 'attachment_url_to_postid' ) ? (int) attachment_url_to_postid( $image_url ) : 0;

	if ( $image_id > 0 && is_object( $image_container ) && method_exists( $image_container, 'add_image_by_id' ) ) {
		$image_container->add_image_by_id( $image_id );
		return;
	}

	if ( is_object( $image_container ) && method_exists( $image_container, 'add_image' ) ) {
		$image_container->add_image( $image_url );
	}
}
add_filter( 'wpseo_add_opengraph_images', 'nvx_contacto_add_yoast_opengraph_image', 100 );

/**
 * Start a narrow output buffer for wp_head on front and page requests. This is
 * used only to remove legacy Schema.org JSON-LD blocks emitted directly by old
 * head callbacks; Yoast's canonical schema graph is preserved.
 */
function nvx_canonical_schema_head_buffer_start(): void {
	if ( is_admin() || ( ! is_front_page() && ! is_singular( 'page' ) ) ) {
		return;
	}

	$GLOBALS['nvx_schema_head_buffer_level'] = ob_get_level();
	ob_start();
}
add_action( 'wp_head', 'nvx_canonical_schema_head_buffer_start', -9999 );

/**
 * Remove Schema.org JSON-LD scripts from wp_head unless they are Yoast's
 * canonical `yoast-schema-graph` block. Non-schema ld+json payloads are kept.
 */
function nvx_canonical_schema_head_buffer_end(): void {
	if ( ! isset( $GLOBALS['nvx_schema_head_buffer_level'] ) ) {
		return;
	}

	$initial_level = (int) $GLOBALS['nvx_schema_head_buffer_level'];
	unset( $GLOBALS['nvx_schema_head_buffer_level'] );

	if ( ob_get_level() !== $initial_level + 1 ) {
		return;
	}

	$html = ob_get_clean();
	if ( ! is_string( $html ) || false === stripos( $html, 'ld+json' ) ) {
		echo is_string( $html ) ? $html : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		return;
	}

	$filtered = preg_replace_callback(
		'#<script\b(?=[^>]*\btype\s*=\s*(["\'])application/ld\+json\1)[^>]*>([\s\S]*?)</script>#iu',
		static function ( array $matches ): string {
			$tag     = isset( $matches[0] ) ? (string) $matches[0] : '';
			$payload = isset( $matches[2] ) ? (string) $matches[2] : '';

			if ( false !== stripos( $tag, 'yoast-schema-graph' ) ) {
				return $tag;
			}

			if ( preg_match( '/schema\.org|@graph\b|"@type"\s*:/i', $payload ) ) {
				return '';
			}

			return $tag;
		},
		$html
	);

	echo is_string( $filtered ) ? $filtered : $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
add_action( 'wp_head', 'nvx_canonical_schema_head_buffer_end', PHP_INT_MAX );
