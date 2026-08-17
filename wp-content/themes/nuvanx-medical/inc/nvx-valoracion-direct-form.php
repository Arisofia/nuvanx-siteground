<?php
/**
 * First-party valoración form.
 *
 * HubSpot's embed is a marketing iframe. Complianz leaves it blank until
 * cookie consent, which is the default state for paid mobile traffic.
 * This form is first-party HTML and posts through WordPress so a clean
 * visit can convert without accepting cookies. Leads are forwarded to the
 * same HubSpot form via the server-side Forms API.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Markup for the consent-independent valoración form.
 */
function nvx_valoracion_direct_form_markup(): string {
	$privacy_url = esc_url( home_url( '/politica-privacidad/' ) );
	$action      = esc_url( home_url( '/madrid/valoracion/' ) );
	$nonce       = wp_nonce_field( 'nvx_valoracion_submit', 'nvx_valoracion_nonce', true, false );

	$error = isset( $_GET['valoracion'] ) && 'error' === sanitize_key( wp_unslash( (string) $_GET['valoracion'] ) );

	$html  = '<form class="nvx-valoracion-direct-form" method="post" action="' . $action . '" data-nvx-direct-form>';
	$html .= '<input type="hidden" name="nvx_valoracion_submit" value="1">';
	$html .= is_string( $nonce ) ? $nonce : '';
	$html .= '<div class="nvx-hp" aria-hidden="true"><label>' . esc_html__( 'Empresa', 'nuvanx-medical' ) . '<input type="text" name="nvx_company" tabindex="-1" autocomplete="off"></label></div>';

	if ( $error ) {
		$html .= '<p class="nvx-valoracion-direct-form__error" role="alert">' . esc_html__( 'No hemos podido enviar la solicitud. Revisa los datos o contáctanos por WhatsApp o teléfono.', 'nuvanx-medical' ) . '</p>';
	}

	$html .= '<p class="nvx-valoracion-direct-form__field">';
	$html .= '<label for="nvx-valoracion-firstname">' . esc_html__( 'Nombre', 'nuvanx-medical' ) . '</label>';
	$html .= '<input class="hs-input" id="nvx-valoracion-firstname" name="firstname" type="text" autocomplete="given-name" maxlength="80" required>';
	$html .= '</p>';

	$html .= '<p class="nvx-valoracion-direct-form__field">';
	$html .= '<label for="nvx-valoracion-phone">' . esc_html__( 'Teléfono', 'nuvanx-medical' ) . '</label>';
	$html .= '<input class="hs-input" id="nvx-valoracion-phone" name="phone" type="tel" autocomplete="tel" inputmode="tel" maxlength="20" required>';
	$html .= '</p>';

	$html .= '<p class="nvx-valoracion-direct-form__field">';
	$html .= '<label for="nvx-valoracion-email">' . esc_html__( 'Email', 'nuvanx-medical' ) . '</label>';
	$html .= '<input class="hs-input" id="nvx-valoracion-email" name="email" type="email" autocomplete="email" maxlength="120" required>';
	$html .= '</p>';

	$html .= '<p class="nvx-valoracion-direct-form__field">';
	$html .= '<label for="nvx-valoracion-message">' . esc_html__( 'Qué quieres valorar', 'nuvanx-medical' ) . '</label>';
	$html .= '<textarea class="hs-input" id="nvx-valoracion-message" name="message" rows="4" maxlength="2000" required></textarea>';
	$html .= '</p>';

	$html .= '<p class="nvx-valoracion-direct-form__consent">';
	$html .= '<label for="nvx-valoracion-privacy">';
	$html .= '<input id="nvx-valoracion-privacy" name="privacy" type="checkbox" value="1" required> ';
	$html .= sprintf(
		/* translators: %s: privacy policy link */
		esc_html__( 'Acepto la %s y el tratamiento de mis datos para gestionar esta solicitud.', 'nuvanx-medical' ),
		'<a class="nvx-text-link" href="' . $privacy_url . '">' . esc_html__( 'Política de privacidad', 'nuvanx-medical' ) . '</a>'
	);
	$html .= '</label></p>';

	foreach ( array( 'gclid', 'gbraid', 'wbraid', 'utm_source', 'utm_medium', 'utm_campaign' ) as $param ) {
		$value = isset( $_GET[ $param ] ) ? sanitize_text_field( wp_unslash( (string) $_GET[ $param ] ) ) : '';
		$html .= '<input type="hidden" name="' . esc_attr( $param ) . '" value="' . esc_attr( $value ) . '">';
	}

	$html .= '<button type="submit" class="nvx-brand-btn nvx-btn--primary nvx-valoracion-direct-form__submit">' . esc_html__( 'Solicitar valoración médica', 'nuvanx-medical' ) . '</button>';
	$html .= '</form>';

	$html .= '<script>(function(){try{var form=document.querySelector("[data-nvx-direct-form]");if(!form)return;var params=new URLSearchParams(window.location.search||"");["gclid","gbraid","wbraid","utm_source","utm_medium","utm_campaign"].forEach(function(key){var input=form.querySelector(\'input[name="\'+key+\'"]\');var value=params.get(key);if(input&&value){input.value=value;}});}catch(e){}})();</script>';

	return $html;
}

/**
 * Handle a first-party valoración POST and forward it to HubSpot.
 */
function nvx_valoracion_maybe_handle_direct_submit(): void {
	if ( 'POST' !== strtoupper( (string) ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) ) {
		return;
	}
	if ( empty( $_POST['nvx_valoracion_submit'] ) ) {
		return;
	}

	$referer = wp_get_referer();
	$back    = is_string( $referer ) && '' !== $referer ? $referer : home_url( '/madrid/valoracion/' );
	$fail    = add_query_arg( 'valoracion', 'error', $back );

	if ( ! isset( $_POST['nvx_valoracion_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( (string) $_POST['nvx_valoracion_nonce'] ) ), 'nvx_valoracion_submit' ) ) {
		wp_safe_redirect( $fail );
		exit;
	}

	$honeypot = isset( $_POST['nvx_company'] ) ? trim( (string) wp_unslash( $_POST['nvx_company'] ) ) : '';
	if ( '' !== $honeypot ) {
		wp_safe_redirect( home_url( '/gracias/' ) );
		exit;
	}

	$ip       = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( (string) $_SERVER['REMOTE_ADDR'] ) ) : '0';
	$rate_key = 'nvx_val_rl_' . md5( $ip );
	$hits     = (int) get_transient( $rate_key );
	if ( $hits >= 5 ) {
		wp_safe_redirect( $fail );
		exit;
	}
	set_transient( $rate_key, $hits + 1, HOUR_IN_SECONDS );

	$firstname = isset( $_POST['firstname'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['firstname'] ) ) : '';
	$phone     = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['phone'] ) ) : '';
	$email     = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( (string) $_POST['email'] ) ) : '';
	$message   = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( (string) $_POST['message'] ) ) : '';
	$privacy   = ! empty( $_POST['privacy'] );

	if ( ! $privacy || ! is_email( $email ) || strlen( $firstname ) < 2 || strlen( $phone ) < 7 || strlen( $message ) < 3 ) {
		wp_safe_redirect( $fail );
		exit;
	}

	$fields = array(
		array(
			'objectTypeId' => '0-1',
			'name'         => 'firstname',
			'value'        => $firstname,
		),
		array(
			'objectTypeId' => '0-1',
			'name'         => 'email',
			'value'        => $email,
		),
		array(
			'objectTypeId' => '0-1',
			'name'         => 'phone',
			'value'        => $phone,
		),
		array(
			'objectTypeId' => '0-1',
			'name'         => 'message',
			'value'        => $message,
		),
	);

	$click_id = isset( $_POST['gclid'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['gclid'] ) ) : '';
	if ( '' !== $click_id ) {
		$fields[] = array(
			'objectTypeId' => '0-1',
			'name'         => 'nvx_google_click_id',
			'value'        => $click_id,
		);
	}

	$context = array(
		'pageUri'  => home_url( '/madrid/valoracion/' ),
		'pageName' => 'Valoración médica estética en Madrid',
	);
	if ( isset( $_COOKIE['hubspotutk'] ) ) {
		$hutk = sanitize_text_field( wp_unslash( (string) $_COOKIE['hubspotutk'] ) );
		if ( '' !== $hutk ) {
			$context['hutk'] = $hutk;
		}
	}

	$ok = nvx_valoracion_forward_to_hubspot( $fields, $context );
	wp_safe_redirect( $ok ? home_url( '/gracias/' ) : $fail );
	exit;
}
add_action( 'template_redirect', 'nvx_valoracion_maybe_handle_direct_submit', 0 );

/**
 * POST a lead to the canonical HubSpot form. Returns true on HTTP 2xx.
 *
 * @param array<int,array{objectTypeId:string,name:string,value:string}> $fields  HubSpot fields.
 * @param array<string,string>                                           $context Submission context.
 */
function nvx_valoracion_forward_to_hubspot( array $fields, array $context ): bool {
	$portal = defined( 'NVX_VALORACION_HS_FRAME_PORTAL_ID' ) ? (string) NVX_VALORACION_HS_FRAME_PORTAL_ID : '147416356';
	$form   = defined( 'NVX_VALORACION_HS_FRAME_FORM_ID' ) ? (string) NVX_VALORACION_HS_FRAME_FORM_ID : '5042522a-0bc5-4381-ac3e-5aee8649b69c';
	$url    = 'https://api.hsforms.com/submissions/v3/integration/submit/' . rawurlencode( $portal ) . '/' . rawurlencode( $form );

	$attempts = array(
		array(
			'fields'               => $fields,
			'context'              => $context,
			'legalConsentOptions'  => array(
				'consent' => array(
					'consentToProcess' => true,
					'text'             => 'Al facilitar tus datos aceptas la Política de privacidad y el tratamiento de mis datos para gestionar esta solicitud.',
				),
			),
		),
		array(
			'fields'  => $fields,
			'context' => $context,
		),
	);

	foreach ( $attempts as $payload ) {
		$body = wp_json_encode( $payload );
		if ( ! is_string( $body ) ) {
			continue;
		}

		$response = wp_remote_post(
			$url,
			array(
				'timeout' => 12,
				'headers' => array(
					'Content-Type' => 'application/json',
				),
				'body'    => $body,
			)
		);

		if ( is_wp_error( $response ) ) {
			continue;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( $code >= 200 && $code < 300 ) {
			return true;
		}

		$raw = (string) wp_remote_retrieve_body( $response );
		if ( false !== strpos( $raw, 'REQUIRED_FIELD' ) || false !== strpos( $raw, 'INVALID_EMAIL' ) ) {
			return false;
		}
	}

	return false;
}
