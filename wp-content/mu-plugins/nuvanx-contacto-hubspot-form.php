<?php
/**
 * Plugin Name: NUVANX Contacto HubSpot Form
 * Description: Mounts the dedicated HubSpot contact form and enforces temporary SEO/claims publication safeguards.
 * Version: 2026.07.19.4
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
function nvxContactoHubspotFormMarkup(): string {
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
function nvxRemoveUnverifiedQuantitativeTrustBadges( string $content ): string {
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
add_filter( 'the_content', 'nvxRemoveUnverifiedQuantitativeTrustBadges', 22 );

/**
 * Canonical contact social image (path relative to uploads when possible).
 *
 * @return array{url:string,width:int,height:int,type:string,alt:string}
 */
function nvxContactoOpengraphImageMeta(): array {
	return array(
		'url'    => home_url( '/wp-content/uploads/2026/07/consulta-medica-personalizada-nuvanx-madrid.webp' ),
		'width'  => 1672,
		'height' => 941,
		'type'   => 'image/webp',
		'alt'    => 'Consulta médica personalizada NUVANX Madrid',
	);
}

/**
 * Add the contact-page Open Graph image through Yoast when its image presenter
 * is available. The final head-output safeguard below covers installations in
 * which the presenter still omits the Open Graph image tags.
 *
 * Yoast's Open_Graph\Images::add_image() expects an image *metadata array*
 * (url/width/height/type), not a bare URL string. Passing a string fails or is
 * ignored depending on Yoast version.
 *
 * @param mixed $image_container Yoast Open Graph image container.
 */
function nvxContactoAddYoastOpengraphImage( $image_container ): void {
	if ( ! function_exists( 'nvx_contacto_audit_is_contact_page' ) || ! nvx_contacto_audit_is_contact_page() || ! is_object( $image_container ) ) {
		return;
	}

	$meta      = nvxContactoOpengraphImageMeta();
	$image_url = $meta['url'];
	$image_id  = function_exists( 'attachment_url_to_postid' ) ? (int) attachment_url_to_postid( $image_url ) : 0;

	if ( $image_id > 0 && method_exists( $image_container, 'add_image_by_id' ) ) {
		$image_container->add_image_by_id( $image_id );
	} elseif ( method_exists( $image_container, 'add_image_by_url' ) ) {
		$image_container->add_image_by_url( $image_url );
	} elseif ( method_exists( $image_container, 'add_image' ) ) {
		$image_container->add_image(
			array(
				'url'    => $image_url,
				'width'  => (int) $meta['width'],
				'height' => (int) $meta['height'],
				'type'   => $meta['type'],
				'alt'    => $meta['alt'],
				'path'   => $image_url,
			)
		);
	}
}
add_filter( 'wpseo_add_opengraph_images', 'nvxContactoAddYoastOpengraphImage', 100 );

/**
 * Start a narrow output buffer for wp_head on front and page requests. This is
 * used to remove legacy Schema.org JSON-LD blocks emitted directly by old head
 * callbacks and to enforce the final contact Open Graph image contract.
 */
function nvxCanonicalSchemaHeadBufferStart(): void {
	if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}
	if ( ! is_front_page() && ! is_page() ) {
		return;
	}

	$GLOBALS['nvx_schema_head_buffer_level'] = ob_get_level();
	ob_start();
}
add_action( 'wp_head', 'nvxCanonicalSchemaHeadBufferStart', PHP_INT_MIN );

/**
 * Helper to filter non-canonical Schema.org JSON-LD scripts.
 *
 * @param array $matches Regular expression matches.
 * @return string Filtered script tag or empty string.
 */
function nvxContactoCleanLdjsonTag( array $matches ): string {
	$tag     = (string) ( $matches[0] ?? '' );
	$payload = (string) ( $matches[2] ?? '' );

	if ( false !== stripos( $tag, 'yoast-schema-graph' ) ) {
		return $tag;
	}

	return preg_match( '/schema\.org|@graph\b|"@type"\s*:/i', $payload ) ? '' : $tag;
}

/**
 * Add the canonical contact Open Graph image tags to the final head output only
 * when no existing `og:image` tag was emitted by Yoast or another integration.
 */
function nvxContactoEnforceFinalOgImage( string $html ): string {
	if (
		! function_exists( 'nvx_contacto_audit_is_contact_page' )
		|| ! nvx_contacto_audit_is_contact_page()
		|| preg_match( '/<meta\b[^>]*\bproperty\s*=\s*(["\'])og:image\1/i', $html )
	) {
		return $html;
	}

	$meta      = nvxContactoOpengraphImageMeta();
	$image_url = esc_url( $meta['url'] );
	$tags      = '<meta property="og:image" content="' . $image_url . '" />'
		. '<meta property="og:image:secure_url" content="' . $image_url . '" />'
		. '<meta property="og:image:width" content="' . (int) $meta['width'] . '" />'
		. '<meta property="og:image:height" content="' . (int) $meta['height'] . '" />'
		. '<meta property="og:image:type" content="' . esc_attr( $meta['type'] ) . '" />'
		. '<meta property="og:image:alt" content="' . esc_attr( $meta['alt'] ) . '" />';

	$with_tags = preg_replace( '/(?=<meta\b[^>]*\bname\s*=\s*(["\'])twitter:card\1)/i', $tags, $html, 1 );

	return is_string( $with_tags ) && $with_tags !== $html ? $with_tags : $html . $tags;
}

/**
 * Remove Schema.org JSON-LD scripts from wp_head unless they are Yoast's
 * canonical `yoast-schema-graph` block. Non-schema ld+json payloads are kept.
 */
function nvxCanonicalSchemaHeadBufferEnd(): void {
	if ( ! isset( $GLOBALS['nvx_schema_head_buffer_level'] ) ) {
		return;
	}

	$initial_level = (int) $GLOBALS['nvx_schema_head_buffer_level'];
	unset( $GLOBALS['nvx_schema_head_buffer_level'] );

	if ( ob_get_level() !== $initial_level + 1 ) {
		return;
	}

	$html = ob_get_clean();
	if ( ! is_string( $html ) ) {
		return;
	}

	if ( false !== stripos( $html, 'ld+json' ) ) {
		$schema_filtered = preg_replace_callback(
			'#<script\b(?=[^>]*\btype\s*=\s*(["\'])application/ld\+json\1)[^>]*>([\s\S]*?)</script>#iu',
			'nvxContactoCleanLdjsonTag',
			$html
		);
		if ( is_string( $schema_filtered ) ) {
			$html = $schema_filtered;
		}
	}

	echo nvxContactoEnforceFinalOgImage( $html ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
add_action( 'wp_head', 'nvxCanonicalSchemaHeadBufferEnd', PHP_INT_MAX );
