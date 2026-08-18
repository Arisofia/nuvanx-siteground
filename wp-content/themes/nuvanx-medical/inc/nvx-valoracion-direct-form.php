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

	$identity_fields = array(
		array(
			'id'           => 'firstname',
			'label'        => esc_html__( 'Nombre', 'nuvanx-medical' ),
			'autocomplete' => 'given-name',
			'maxlength'    => 80,
		),
		array(
			'id'           => 'lastname',
			'label'        => esc_html__( 'Apellidos', 'nuvanx-medical' ),
			'autocomplete' => 'family-name',
			'maxlength'    => 120,
		),
	);

	foreach ( $identity_fields as $field ) {
		$html .= '<p class="nvx-valoracion-direct-form__field">';
		$html .= '<label for="nvx-valoracion-' . $field['id'] . '">' . $field['label'] . '</label>';
		$html .= '<input class="hs-input" id="nvx-valoracion-' . $field['id'] . '" name="' . $field['id'] . '" type="text" autocomplete="' . $field['autocomplete'] . '" maxlength="' . $field['maxlength'] . '" required>';
		$html .= '</p>';
	}

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
 * Character length for first-party name fields. Bytes would accept a single
 * multi-byte surname such as Ñ or 李 as if it met the two-character minimum.
 */
function nvx_valoracion_name_length( string $value ): int {
	if ( function_exists( 'mb_strlen' ) ) {
		return (int) mb_strlen( $value, 'UTF-8' );
	}

	if ( function_exists( 'iconv_strlen' ) ) {
		$iconv_length = @iconv_strlen( $value, 'UTF-8' );
		if ( false !== $iconv_length ) {
			return (int) $iconv_length;
		}
	}

	$utf8_count = preg_match_all( '/./us', $value );
	// Invalid UTF-8 must fail closed. strlen() would count Ñ as 2 and pass the minimum.
	return false === $utf8_count ? 0 : (int) $utf8_count;
}

/**
 * Emit a bounded operational event without personal data, request identifiers,
 * cookies, tokens or HubSpot payload content.
 */
function nvx_valoracion_log_outcome( string $outcome, string $reason = '', int $status = 0 ): void {
	$allowed_outcomes = array( 'FAILURE', 'SUCCESS' );
	$allowed_reasons  = array( 'nonce', 'rate_limit', 'validation', 'hubspot_transport', 'hubspot_http' );
	$outcome          = strtoupper( $outcome );

	if ( ! in_array( $outcome, $allowed_outcomes, true ) ) {
		return;
	}

	$line = 'NVX_VALORACION_' . $outcome;
	if ( 'FAILURE' === $outcome && in_array( $reason, $allowed_reasons, true ) ) {
		$line .= ' reason=' . $reason;
	}
	if ( $status > 0 ) {
		$line .= ' status=' . (int) $status;
	}

	error_log( $line );
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
		nvx_valoracion_log_outcome( 'FAILURE', 'nonce' );
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
		nvx_valoracion_log_outcome( 'FAILURE', 'rate_limit' );
		wp_safe_redirect( $fail );
		exit;
	}
	set_transient( $rate_key, $hits + 1, HOUR_IN_SECONDS );

	$firstname = isset( $_POST['firstname'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['firstname'] ) ) : '';
	$lastname  = isset( $_POST['lastname'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['lastname'] ) ) : '';
	$phone     = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['phone'] ) ) : '';
	$email     = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( (string) $_POST['email'] ) ) : '';
	$message   = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( (string) $_POST['message'] ) ) : '';
	$privacy   = ! empty( $_POST['privacy'] );

	if ( ! $privacy || ! is_email( $email ) || nvx_valoracion_name_length( $firstname ) < 2 || nvx_valoracion_name_length( $lastname ) < 2 || strlen( $phone ) < 7 || nvx_valoracion_name_length( $message ) < 3 ) {
		nvx_valoracion_log_outcome( 'FAILURE', 'validation' );
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
			'name'         => 'lastname',
			'value'        => $lastname,
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

	$result = nvx_valoracion_forward_to_hubspot( $fields, $context );
	if ( $result['ok'] ) {
		nvx_valoracion_log_outcome( 'SUCCESS', '', $result['status'] );
		try {
			$token = bin2hex( random_bytes( 32 ) );
			$hash  = hash( 'sha256', $token );
			set_transient( 'nvx_success_' . $hash, 1, 10 * MINUTE_IN_SECONDS );
			wp_safe_redirect( add_query_arg( 'nvx_success', $token, home_url( '/gracias/' ) ) );
		} catch ( Exception $e ) {
			wp_safe_redirect( home_url( '/gracias/' ) );
		}
		exit;
	}

	nvx_valoracion_log_outcome( 'FAILURE', $result['reason'], $result['status'] );
	wp_safe_redirect( $fail );
	exit;
}
add_action( 'template_redirect', 'nvx_valoracion_maybe_handle_direct_submit', 0 );

/**
 * POST a lead to the canonical HubSpot form. The returned contract contains
 * only an allow-listed reason and an HTTP status; it never contains request,
 * cookie, token, payload or response-body data.
 *
 * @param array<int,array{objectTypeId:string,name:string,value:string}> $fields  HubSpot fields.
 * @param array<string,string>                                           $context Submission context.
 * @return array{ok:bool,reason:string,status:int}
 */
function nvx_valoracion_forward_to_hubspot( array $fields, array $context ): array {
	$portal = defined( 'NVX_VALORACION_HS_FRAME_PORTAL_ID' ) ? (string) NVX_VALORACION_HS_FRAME_PORTAL_ID : '147416356';
	$form   = defined( 'NVX_VALORACION_HS_FRAME_FORM_ID' ) ? (string) NVX_VALORACION_HS_FRAME_FORM_ID : '5042522a-0bc5-4381-ac3e-5aee8649b69c';
	$url    = 'https://api.hsforms.com/submissions/v3/integration/submit/' . rawurlencode( $portal ) . '/' . rawurlencode( $form );

	$failed = static function ( string $reason, int $status ): array {
		return array(
			'ok'     => false,
			'reason' => $reason,
			'status' => $status,
		);
	};

	$body = wp_json_encode(
		array(
			'fields'              => $fields,
			'context'             => $context,
			'legalConsentOptions' => array(
				'consent' => array(
					'consentToProcess' => true,
					'text'             => 'Al facilitar tus datos aceptas la Política de privacidad y el tratamiento de mis datos para gestionar esta solicitud.',
				),
			),
		)
	);
	if ( ! is_string( $body ) ) {
		return $failed( 'hubspot_transport', 0 );
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
		return $failed( 'hubspot_transport', 0 );
	}

	$code = (int) wp_remote_retrieve_response_code( $response );
	if ( $code >= 200 && $code < 300 ) {
		return array(
			'ok'     => true,
			'reason' => '',
			'status' => $code,
		);
	}

	return $failed( 'hubspot_http', $code );
}

/**
 * Validate and consume a single-use canonical success token before rendering.
 * Requests carrying the token are explicitly non-cacheable, including invalid
 * or already-consumed tokens, so cached HTML can never replay the conversion.
 */
function nvx_valoracion_prepare_canonical_success(): void {
	$GLOBALS['nvx_valoracion_success_ready'] = false;

	if ( ! function_exists( 'nvx_theme_thank_you_page_slugs' ) || ! is_page( nvx_theme_thank_you_page_slugs() ) ) {
		return;
	}
	if ( ! isset( $_GET['nvx_success'] ) ) {
		return;
	}

	if ( ! defined( 'DONOTCACHEPAGE' ) ) {
		define( 'DONOTCACHEPAGE', true );
	}
	nocache_headers();

	$token = sanitize_text_field( wp_unslash( (string) $_GET['nvx_success'] ) );
	if ( 1 !== preg_match( '/^[a-f0-9]{64}$/D', $token ) ) {
		return;
	}

	$hash = hash( 'sha256', $token );
	$key  = 'nvx_success_' . $hash;
	if ( ! get_transient( $key ) ) {
		return;
	}

	delete_transient( $key );
	$GLOBALS['nvx_valoracion_success_ready'] = true;
}
add_action( 'template_redirect', 'nvx_valoracion_prepare_canonical_success', 1 );

/** Emit the already-consumed canonical success signal during render. */
function nvx_valoracion_emit_canonical_success(): void {
	if ( empty( $GLOBALS['nvx_valoracion_success_ready'] ) ) {
		return;
	}
	$GLOBALS['nvx_valoracion_success_ready'] = false;
	$script = "window.dataLayer=window.dataLayer||[];window.dataLayer.push({event:'nvx_valoracion_success',form:'valoracion',source:'first_party'});";
	wp_print_inline_script_tag( $script );
}
add_action( 'wp_head', 'nvx_valoracion_emit_canonical_success', 5 );
