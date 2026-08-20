<?php
/**
 * Canonical lead-captured relay.
 *
 * Observes successful authenticated HubSpot submissions and mirrors first-party
 * lineage to Supabase. Lineage and QA are derived from the validated WordPress
 * request/server context, never from the restricted HubSpot Forms schema.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Canonical Supabase capture ledger URL. */
function nvx_lead_captured_endpoint(): string {
	return 'https://ssvvuuysgxyqvmovrlvk.supabase.co/functions/v1/lead-captured';
}

/** Canonical one-purpose runtime bootstrap URL. */
function nvx_lead_captured_bootstrap_endpoint(): string {
	return 'https://ssvvuuysgxyqvmovrlvk.supabase.co/functions/v1/runtime-bootstrap';
}

/** Resolve the existing server-only HubSpot private-app token. */
function nvx_lead_captured_hubspot_token(): string {
	if ( ! defined( 'NVX_HUBSPOT_ACCESS_TOKEN' ) ) {
		return '';
	}
	return trim( (string) NVX_HUBSPOT_ACCESS_TOKEN );
}

/** Store the current request relay result for Staging2 acceptance. */
function nvx_lead_captured_set_last_relay_ok( bool $ok ): void {
	$GLOBALS['nvx_lead_captured_last_relay_ok'] = $ok;
}

/** Whether the current secure HubSpot response was mirrored successfully. */
function nvx_lead_captured_last_relay_ok(): bool {
	return true === ( $GLOBALS['nvx_lead_captured_last_relay_ok'] ?? false );
}

/** Derive a dedicated HMAC key from the HubSpot token. */
function nvx_lead_captured_derive_hmac_key( string $token ): string {
	$context = 'nuvanx-lead-capture-hmac-key-v1';
	$info    = hash_hmac( 'sha256', $context, $token, true );
	return bin2hex( $info );
}

/** Bootstrap the validated HubSpot credential into Supabase Vault. */
function nvx_lead_captured_bootstrap_runtime( string $token, bool $force = false ): bool {
	$transient = 'nvx_runtime_bootstrap_ok_v1';
	if ( ! $force && '1' === (string) get_transient( $transient ) ) {
		return true;
	}
	if ( $force ) {
		delete_transient( $transient );
	}

	$response = wp_remote_post(
		nvx_lead_captured_bootstrap_endpoint(),
		array(
			'timeout'     => 5,
			'redirection' => 0,
			'blocking'    => true,
			'headers'     => array(
				'Authorization' => 'Bearer ' . $token,
				'Content-Type'  => 'application/json',
			),
			'body'        => '{}',
		)
	);

	if ( is_wp_error( $response ) ) {
		error_log(
			sprintf(
				'[NUVANX] runtime bootstrap transport failure; wp_error_code=%s.',
				sanitize_key( (string) $response->get_error_code() )
			)
		);
		return false;
	}
	$status = (int) wp_remote_retrieve_response_code( $response );
	if ( $status < 200 || $status >= 300 ) {
		error_log( sprintf( '[NUVANX] runtime bootstrap HTTP failure; status=%d.', $status ) );
		return false;
	}

	set_transient( $transient, '1', HOUR_IN_SECONDS );
	return true;
}

/** Convert HubSpot request fields to name => value. */
function nvx_lead_captured_field_map( array $payload ): array {
	$mapped = array();
	$fields = isset( $payload['fields'] ) && is_array( $payload['fields'] ) ? $payload['fields'] : array();
	foreach ( $fields as $field ) {
		if ( ! is_array( $field ) || ! isset( $field['name'] ) ) {
			continue;
		}
		$name = (string) $field['name'];
		if ( '' === $name ) {
			continue;
		}
		$mapped[ $name ] = trim( (string) ( $field['value'] ?? '' ) );
	}
	return $mapped;
}

/**
 * Build the capture map from the validated first-party POST instead of the
 * schema-limited HubSpot Forms payload.
 */
function nvx_lead_captured_request_field_map(): array {
	$names = array(
		'email',
		'nvx_lead_id',
		'nvx_first_source',
		'nvx_first_medium',
		'nvx_first_campaign_id',
		'nvx_first_referrer_domain',
		'nvx_first_landing_url',
		'nvx_first_timestamp',
		'nvx_first_channel',
		'nvx_conversion_channel',
		'nvx_conversion_source',
		'nvx_conversion_medium',
		'nvx_conversion_campaign_id',
		'nvx_conversion_landing_url',
		'nvx_conversion_timestamp',
		'nvx_utm_source',
		'nvx_utm_medium',
		'nvx_utm_campaign',
		'nvx_utm_content',
		'nvx_utm_term',
		'nvx_google_click_id',
		'nvx_google_braid',
		'nvx_google_wbraid',
		'nvx_google_gclsrc',
	);
	$out = array();
	foreach ( $names as $name ) {
		$value = function_exists( 'nvx_hubspot_secure_post_value' ) ? nvx_hubspot_secure_post_value( $name, 1200 ) : '';
		if ( '' !== $value ) {
			$out[ $name ] = $value;
		}
	}
	return $out;
}

/** Build a non-clinical attribution snapshot. */
function nvx_lead_captured_attribution( array $fields, string $prefix ): array {
	$property_map = array(
		'source'      => $prefix . 'source',
		'medium'      => $prefix . 'medium',
		'campaign_id' => $prefix . 'campaign_id',
		'landing_url' => $prefix . 'landing_url',
		'timestamp'   => $prefix . 'timestamp',
		'channel'     => $prefix . 'channel',
	);
	if ( 'nvx_first_' === $prefix ) {
		$property_map['referrer_domain'] = 'nvx_first_referrer_domain';
	}

	$out = array();
	foreach ( $property_map as $key => $property ) {
		if ( isset( $fields[ $property ] ) && '' !== $fields[ $property ] ) {
			$out[ $key ] = $fields[ $property ];
		}
	}
	if ( 'nvx_conversion_' === $prefix ) {
		$generic = array(
			'utm_source'   => 'nvx_utm_source',
			'utm_medium'   => 'nvx_utm_medium',
			'utm_campaign' => 'nvx_utm_campaign',
			'utm_content'  => 'nvx_utm_content',
			'utm_term'     => 'nvx_utm_term',
			'gclid'        => 'nvx_google_click_id',
			'gbraid'       => 'nvx_google_braid',
			'wbraid'       => 'nvx_google_wbraid',
			'gclsrc'       => 'nvx_google_gclsrc',
		);
		foreach ( $generic as $key => $property ) {
			if ( isset( $fields[ $property ] ) && '' !== $fields[ $property ] ) {
				$out[ $key ] = $fields[ $property ];
			}
		}
	}
	return $out;
}

/** Extract optional IDs from a HubSpot response without logging body content. */
function nvx_lead_captured_hubspot_ids( $response ): array {
	$result = array( 'contact_id' => '', 'submission_id' => '' );
	if ( is_wp_error( $response ) ) {
		return $result;
	}
	$body    = (string) wp_remote_retrieve_body( $response );
	$decoded = json_decode( $body, true );
	if ( ! is_array( $decoded ) ) {
		$status     = (int) wp_remote_retrieve_response_code( $response );
		$json_error = function_exists( 'json_last_error' ) ? (int) json_last_error() : -1;
		error_log(
			sprintf(
				'[NUVANX] lead-captured relay: HubSpot response IDs unavailable; status=%d json_error=%d.',
				$status,
				$json_error
			)
		);
		return $result;
	}
	foreach ( array( 'contactId', 'contact_id' ) as $key ) {
		if ( isset( $decoded[ $key ] ) && preg_match( '/^[1-9][0-9]{0,18}$/', (string) $decoded[ $key ] ) ) {
			$result['contact_id'] = (string) $decoded[ $key ];
			break;
		}
	}
	foreach ( array( 'submissionId', 'submission_id', 'conversionId', 'conversion_id' ) as $key ) {
		if ( isset( $decoded[ $key ] ) ) {
			$value = trim( (string) $decoded[ $key ] );
			if ( '' !== $value && strlen( $value ) <= 180 ) {
				$result['submission_id'] = $value;
				break;
			}
		}
	}
	return $result;
}

/** Build one signed capture request. */
function nvx_lead_captured_post_signed( string $body, string $token ) {
	$timestamp = (string) time();
	$hmac_key  = nvx_lead_captured_derive_hmac_key( $token );
	$signature = hash_hmac( 'sha256', $timestamp . '.' . $body, $hmac_key );
	return wp_remote_post(
		nvx_lead_captured_endpoint(),
		array(
			'timeout'     => 5,
			'redirection' => 0,
			'blocking'    => true,
			'headers'     => array(
				'Content-Type'    => 'application/json',
				'x-nvx-timestamp' => $timestamp,
				'x-nvx-signature' => $signature,
			),
			'body'        => $body,
		)
	);
}

/** Whether a relay response is a real 2xx. */
function nvx_lead_captured_relay_ok( $relay ): bool {
	if ( is_wp_error( $relay ) ) {
		return false;
	}
	$status = (int) wp_remote_retrieve_response_code( $relay );
	return $status >= 200 && $status < 300;
}

/** Log only bounded transport/status metadata for a failed capture. */
function nvx_lead_captured_log_relay_failure( $relay ): void {
	if ( is_wp_error( $relay ) ) {
		error_log(
			sprintf(
				'[NUVANX] lead-captured relay transport failure; wp_error_code=%s.',
				sanitize_key( (string) $relay->get_error_code() )
			)
		);
		return;
	}
	$status = (int) wp_remote_retrieve_response_code( $relay );
	if ( $status < 200 || $status >= 300 ) {
		error_log( sprintf( '[NUVANX] lead-captured relay HTTP failure; status=%d.', $status ) );
	}
}

/** Mirror one successful secure HubSpot submission into the capture ledger. */
function nvx_lead_captured_on_http_response( $response, array $parsed_args, string $url ): mixed {
	if ( $url === nvx_lead_captured_endpoint() || $url === nvx_lead_captured_bootstrap_endpoint() ) {
		return $response;
	}
	if ( ! function_exists( 'nvx_hubspot_secure_submit_url' ) || nvx_hubspot_secure_submit_url() !== $url ) {
		return $response;
	}
	nvx_lead_captured_set_last_relay_ok( false );
	if ( is_wp_error( $response ) ) {
		return $response;
	}
	$status = (int) wp_remote_retrieve_response_code( $response );
	if ( $status < 200 || $status >= 300 ) {
		return $response;
	}

	$token = nvx_lead_captured_hubspot_token();
	if ( '' === $token ) {
		error_log( '[NUVANX] lead-captured relay skipped: existing HubSpot server credential missing.' );
		return $response;
	}
	if ( ! nvx_lead_captured_bootstrap_runtime( $token ) ) {
		return $response;
	}

	$raw_payload = isset( $parsed_args['body'] ) ? $parsed_args['body'] : '';
	$payload     = is_string( $raw_payload ) ? json_decode( $raw_payload, true ) : (array) $raw_payload;
	if ( ! is_array( $payload ) ) {
		error_log( '[NUVANX] lead-captured relay skipped: authenticated HubSpot request payload is not decodable JSON.' );
		return $response;
	}
	$hubspot_fields = nvx_lead_captured_field_map( $payload );
	$request_fields = nvx_lead_captured_request_field_map();
	$lead_id        = isset( $request_fields['nvx_lead_id'] ) ? strtolower( $request_fields['nvx_lead_id'] ) : '';
	if ( ! function_exists( 'nvx_hubspot_secure_is_uuid_v4' ) || ! nvx_hubspot_secure_is_uuid_v4( $lead_id ) ) {
		error_log( '[NUVANX] lead-captured relay skipped: valid nvx_lead_id missing from first-party request.' );
		return $response;
	}

	$qa = function_exists( 'nvx_hubspot_secure_qa_context' )
		? nvx_hubspot_secure_qa_context()
		: array( 'is_test_lead' => false, 'test_run_id' => '' );
	$is_test           = ! empty( $qa['is_test_lead'] );
	$test_run_id       = trim( (string) ( $qa['test_run_id'] ?? '' ) );
	$marketing_consent = function_exists( 'nvx_hubspot_secure_post_value' )
		&& '1' === nvx_hubspot_secure_post_value( 'nvx_marketing_consent', 1 );
	$email             = isset( $hubspot_fields['email'] ) ? strtolower( trim( $hubspot_fields['email'] ) ) : '';
	if ( '' === $email && isset( $request_fields['email'] ) ) {
		$email = strtolower( trim( $request_fields['email'] ) );
	}
	$email_hash = '' !== $email ? hash( 'sha256', $email ) : null;
	unset( $email );

	$ids = nvx_lead_captured_hubspot_ids( $response );
	if ( '' === $ids['contact_id'] && function_exists( 'nvx_hubspot_secure_last_contact_id' ) ) {
		$ids['contact_id'] = nvx_hubspot_secure_last_contact_id();
	}

	$relay_payload = array(
		'nvx_lead_id'           => $lead_id,
		'form_id'               => nvx_hubspot_secure_form_id(),
		'hubspot_contact_id'     => '' !== $ids['contact_id'] ? $ids['contact_id'] : null,
		'hubspot_submission_id'  => '' !== $ids['submission_id'] ? $ids['submission_id'] : null,
		'email_hash'             => $email_hash,
		'nvx_is_test_lead'       => $is_test,
		'nvx_test_run_id'        => '' !== $test_run_id ? $test_run_id : null,
		'marketing_consent'      => $marketing_consent,
		'first_attribution'      => $marketing_consent ? nvx_lead_captured_attribution( $request_fields, 'nvx_first_' ) : array(),
		'conversion_attribution' => $marketing_consent ? nvx_lead_captured_attribution( $request_fields, 'nvx_conversion_' ) : array(),
	);
	$relay_body = wp_json_encode( $relay_payload );
	if ( false === $relay_body ) {
		error_log( '[NUVANX] lead-captured relay skipped: canonical payload encoding failed.' );
		return $response;
	}

	$relay = nvx_lead_captured_post_signed( $relay_body, $token );
	if ( ! is_wp_error( $relay ) ) {
		$relay_status = (int) wp_remote_retrieve_response_code( $relay );
		if ( 401 === $relay_status || 503 === $relay_status ) {
			if ( nvx_lead_captured_bootstrap_runtime( $token, true ) ) {
				$relay = nvx_lead_captured_post_signed( $relay_body, $token );
			}
		}
	}
	if ( ! nvx_lead_captured_relay_ok( $relay ) ) {
		nvx_lead_captured_log_relay_failure( $relay );
		return $response;
	}
	nvx_lead_captured_set_last_relay_ok( true );
	return $response;
}
add_filter( 'http_response', 'nvx_lead_captured_on_http_response', 10, 3 );
