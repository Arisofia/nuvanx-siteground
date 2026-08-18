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

/** Markup for the consent-independent valoración form. */
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

	foreach (
		array(
			'nvx_lead_id',
			'nvx_is_test_lead',
			'nvx_test_run_id',
			'nvx_marketing_consent',
			'gclid',
			'gbraid',
			'wbraid',
			'gclsrc',
			'utm_source',
			'utm_medium',
			'utm_campaign',
			'utm_content',
			'utm_term',
			'nvx_landing_url',
			'nvx_attribution_captured_at',
			'nvx_attribution_expires_at',
			'nvx_first_channel',
			'nvx_first_source',
			'nvx_first_medium',
			'nvx_first_campaign_id',
			'nvx_first_referrer_domain',
			'nvx_first_landing_url',
			'nvx_first_timestamp',
			'nvx_conversion_channel',
			'nvx_conversion_source',
			'nvx_conversion_medium',
			'nvx_conversion_campaign_id',
			'nvx_conversion_landing_url',
			'nvx_conversion_timestamp',
		) as $param
	) {
		$html .= '<input type="hidden" name="' . esc_attr( $param ) . '" value="">';
	}

	$html .= '<button type="submit" class="nvx-brand-btn nvx-btn--primary nvx-valoracion-direct-form__submit">' . esc_html__( 'Solicitar valoración médica', 'nuvanx-medical' ) . '</button>';
	$html .= '</form>';

	return $html;
}

/** Character length for first-party name fields. */
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
	return false === $utf8_count ? 0 : (int) $utf8_count;
}

/** Emit a bounded operational event without personal data. */
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

/** Sanitize one bounded attribution token from the direct form POST. */
function nvx_valoracion_attribution_value( string $key, int $max_length = 512 ): string {
	if ( ! isset( $_POST[ $key ] ) ) {
		return '';
	}
	$value = sanitize_text_field( wp_unslash( (string) $_POST[ $key ] ) );
	if ( '' === $value || strlen( $value ) > $max_length ) {
		return '';
	}
	return $value;
}

/** Resolve a valid first-party lead lineage ID. */
function nvx_valoracion_lead_id(): string {
	$candidate = nvx_valoracion_attribution_value( 'nvx_lead_id', 36 );
	if ( 1 === preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $candidate ) ) {
		return strtolower( $candidate );
	}
	if ( function_exists( 'wp_generate_uuid4' ) ) {
		return strtolower( wp_generate_uuid4() );
	}
	try {
		$bytes    = random_bytes( 16 );
		$bytes[6] = chr( ( ord( $bytes[6] ) & 0x0f ) | 0x40 );
		$bytes[8] = chr( ( ord( $bytes[8] ) & 0x3f ) | 0x80 );
		$hex      = bin2hex( $bytes );
		return substr( $hex, 0, 8 ) . '-' . substr( $hex, 8, 4 ) . '-' . substr( $hex, 12, 4 ) . '-' . substr( $hex, 16, 4 ) . '-' . substr( $hex, 20 );
	} catch ( Throwable $error ) {
		unset( $error );
		return '';
	}
}

/** Append a contact property to a HubSpot Forms API field payload. */
function nvx_valoracion_append_field( array &$fields, string $name, string $value ): void {
	if ( '' === $value ) {
		return;
	}
	$fields[] = array(
		'objectTypeId' => '0-1',
		'name'         => $name,
		'value'        => $value,
	);
}

/**
 * Append deterministic QA metadata. Client values are deliberately ignored;
 * only the server-owned environment context may mark a submission as test.
 */
function nvx_valoracion_append_qa_context( array &$fields ): void {
	$qa = function_exists( 'nvx_attribution_qa_context' )
		? nvx_attribution_qa_context()
		: array( 'is_test_lead' => false, 'test_run_id' => '' );

	nvx_valoracion_append_field( $fields, 'nvx_is_test_lead', ! empty( $qa['is_test_lead'] ) ? 'true' : 'false' );
	if ( ! empty( $qa['is_test_lead'] ) && isset( $qa['test_run_id'] ) ) {
		nvx_valoracion_append_field( $fields, 'nvx_test_run_id', sanitize_key( (string) $qa['test_run_id'] ) );
	}
}

/** Append consent-gated advertising attribution to a HubSpot field payload. */
function nvx_valoracion_append_marketing_attribution( array &$fields ): void {
	if ( '1' !== nvx_valoracion_attribution_value( 'nvx_marketing_consent', 1 ) ) {
		return;
	}

	$mapping = array(
		'utm_source'                     => array( 'nvx_utm_source' ),
		'utm_medium'                     => array( 'nvx_utm_medium' ),
		'utm_campaign'                   => array( 'nvx_utm_campaign' ),
		'utm_content'                    => array( 'nvx_utm_content' ),
		'utm_term'                       => array( 'nvx_utm_term' ),
		'nvx_landing_url'                => array( 'nvx_landing_url' ),
		'nvx_attribution_captured_at'    => array( 'nvx_attribution_captured_at' ),
		'nvx_attribution_expires_at'     => array( 'nvx_attribution_expires_at' ),
		'gclid'                          => array( 'nvx_google_click_id' ),
		'gbraid'                         => array( 'nvx_google_braid' ),
		'wbraid'                         => array( 'nvx_google_wbraid' ),
		'gclsrc'                         => array( 'nvx_google_gclsrc' ),
		'nvx_first_channel'              => array( 'nvx_first_channel' ),
		'nvx_first_source'               => array( 'nvx_first_source' ),
		'nvx_first_medium'               => array( 'nvx_first_medium' ),
		'nvx_first_campaign_id'          => array( 'nvx_first_campaign_id' ),
		'nvx_first_referrer_domain'      => array( 'nvx_first_referrer_domain' ),
		'nvx_first_landing_url'          => array( 'nvx_first_landing_url' ),
		'nvx_first_timestamp'            => array( 'nvx_first_timestamp' ),
		'nvx_conversion_channel'         => array( 'nvx_conversion_channel' ),
		'nvx_conversion_source'          => array( 'nvx_conversion_source' ),
		'nvx_conversion_medium'          => array( 'nvx_conversion_medium' ),
		'nvx_conversion_campaign_id'     => array( 'nvx_conversion_campaign_id' ),
		'nvx_conversion_landing_url'     => array( 'nvx_conversion_landing_url' ),
		'nvx_conversion_timestamp'       => array( 'nvx_conversion_timestamp' ),
	);

	$url_fields       = array( 'nvx_landing_url', 'nvx_first_landing_url', 'nvx_conversion_landing_url' );
	$timestamp_fields = array( 'nvx_attribution_captured_at', 'nvx_attribution_expires_at', 'nvx_first_timestamp', 'nvx_conversion_timestamp' );

	foreach ( $mapping as $post_key => $property_names ) {
		$max_length = 512;
		if ( 'gclsrc' === $post_key ) {
			$max_length = 128;
		} elseif ( in_array( $post_key, $url_fields, true ) ) {
			$max_length = 1024;
		} elseif ( in_array( $post_key, $timestamp_fields, true ) ) {
			$max_length = 64;
		} elseif ( 'nvx_first_referrer_domain' === $post_key ) {
			$max_length = 253;
		}
		$value = nvx_valoracion_attribution_value( $post_key, $max_length );
		foreach ( $property_names as $property_name ) {
			nvx_valoracion_append_field( $fields, $property_name, $value );
		}
	}
}

/** Handle a first-party valoración POST and forward it to HubSpot. */
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
		array( 'objectTypeId' => '0-1', 'name' => 'firstname', 'value' => $firstname ),
		array( 'objectTypeId' => '0-1', 'name' => 'lastname', 'value' => $lastname ),
		array( 'objectTypeId' => '0-1', 'name' => 'email', 'value' => $email ),
		array( 'objectTypeId' => '0-1', 'name' => 'phone', 'value' => $phone ),
		array( 'objectTypeId' => '0-1', 'name' => 'message', 'value' => $message ),
	);

	nvx_valoracion_append_field( $fields, 'nvx_lead_id', nvx_valoracion_lead_id() );
	nvx_valoracion_append_qa_context( $fields );
	nvx_valoracion_append_marketing_attribution( $fields );

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
		wp_safe_redirect( home_url( '/gracias/' ) );
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
 * @param array<int,array{objectTypeId:string,name:string,value:string}> $fields HubSpot fields.
 * @param array<string,string> $context Submission context.
 * @return array{ok:bool,reason:string,status:int}
 */
function nvx_valoracion_forward_to_hubspot( array $fields, array $context ): array {
	$portal = defined( 'NVX_VALORACION_HS_FRAME_PORTAL_ID' ) ? (string) NVX_VALORACION_HS_FRAME_PORTAL_ID : '147416356';
	$form   = defined( 'NVX_VALORACION_HS_FRAME_FORM_ID' ) ? (string) NVX_VALORACION_HS_FRAME_FORM_ID : '5042522a-0bc5-4381-ac3e-5aee8649b69c';
	$url    = 'https://api.hsforms.com/submissions/v3/integration/submit/' . rawurlencode( $portal ) . '/' . rawurlencode( $form );

	$failed = static function ( string $reason, int $status ): array {
		return array( 'ok' => false, 'reason' => $reason, 'status' => $status );
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
			'headers' => array( 'Content-Type' => 'application/json' ),
			'body'    => $body,
		)
	);

	if ( is_wp_error( $response ) ) {
		return $failed( 'hubspot_transport', 0 );
	}

	$code = (int) wp_remote_retrieve_response_code( $response );
	if ( $code >= 200 && $code < 300 ) {
		return array( 'ok' => true, 'reason' => '', 'status' => $code );
	}

	return $failed( 'hubspot_http', $code );
}
