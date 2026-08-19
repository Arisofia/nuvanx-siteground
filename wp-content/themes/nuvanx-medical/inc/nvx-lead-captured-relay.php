<?php
/**
 * Canonical lead-captured relay.
 *
 * Observes only successful authenticated HubSpot submissions and mirrors
 * first-party lineage to Supabase. It never creates Deals or sends ad feedback.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Resolve the canonical Supabase lead-captured endpoint.
 */
function nvx_lead_captured_endpoint(): string {
	if ( defined( 'NVX_LEAD_CAPTURE_ENDPOINT' ) && '' !== trim( (string) NVX_LEAD_CAPTURE_ENDPOINT ) ) {
		return esc_url_raw( (string) NVX_LEAD_CAPTURE_ENDPOINT );
	}
	$env = trim( (string) ( getenv( 'NVX_LEAD_CAPTURE_ENDPOINT' ) ?: '' ) );
	if ( '' !== $env ) {
		return esc_url_raw( $env );
	}
	return 'https://ssvvuuysgxyqvmovrlvk.supabase.co/functions/v1/lead-captured';
}

/**
 * Resolve the server-only relay secret without any source fallback.
 */
function nvx_lead_captured_secret(): string {
	if ( defined( 'NUVANX_LEAD_CAPTURE_SECRET' ) ) {
		return trim( (string) NUVANX_LEAD_CAPTURE_SECRET );
	}
	return trim( (string) ( getenv( 'NUVANX_LEAD_CAPTURE_SECRET' ) ?: '' ) );
}

/**
 * Convert HubSpot fields to a simple name => value map.
 *
 * @param array $payload HubSpot request payload.
 * @return array<string, string>
 */
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
		$value           = isset( $field['value'] ) ? (string) $field['value'] : '';
		$mapped[ $name ] = trim( $value );
	}
	return $mapped;
}

/**
 * Build a non-clinical attribution snapshot from already-consent-filtered fields.
 *
 * @param array<string, string> $fields HubSpot field map.
 * @param string                $prefix nvx_first_ or nvx_conversion_.
 * @return array<string, string>
 */
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

/**
 * Extract optional IDs returned by HubSpot without depending on their presence.
 *
 * @param mixed $response WordPress HTTP response.
 * @return array{contact_id:string,submission_id:string}
 */
function nvx_lead_captured_hubspot_ids( $response ): array {
	$result = array( 'contact_id' => '', 'submission_id' => '' );
	if ( is_wp_error( $response ) ) {
		return $result;
	}
	$decoded = json_decode( (string) wp_remote_retrieve_body( $response ), true );
	if ( ! is_array( $decoded ) ) {
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

/**
 * Mirror one successful secure HubSpot submission into the canonical capture ledger.
 * The HubSpot response remains authoritative for the patient request; relay failures
 * are logged and never turn a successful submission into a browser-visible failure.
 *
 * @param mixed  $response HTTP response.
 * @param array  $parsed_args Parsed HTTP args.
 * @param string $url Requested URL.
 * @return mixed
 */
function nvx_lead_captured_on_http_response( $response, array $parsed_args, string $url ) {
	if ( ! function_exists( 'nvx_hubspot_secure_submit_url' ) || nvx_hubspot_secure_submit_url() !== $url ) {
		return $response;
	}
	if ( is_wp_error( $response ) ) {
		return $response;
	}
	$status = (int) wp_remote_retrieve_response_code( $response );
	if ( $status < 200 || $status >= 300 ) {
		return $response;
	}

	$secret = nvx_lead_captured_secret();
	if ( '' === $secret ) {
		error_log( '[NUVANX] lead-captured relay skipped: NUVANX_LEAD_CAPTURE_SECRET missing.' );
		return $response;
	}

	$raw_payload = isset( $parsed_args['body'] ) ? $parsed_args['body'] : '';
	$payload     = is_string( $raw_payload ) ? json_decode( $raw_payload, true ) : (array) $raw_payload;
	if ( ! is_array( $payload ) ) {
		return $response;
	}
	$fields  = nvx_lead_captured_field_map( $payload );
	$lead_id = isset( $fields['nvx_lead_id'] ) ? strtolower( $fields['nvx_lead_id'] ) : '';
	if ( ! function_exists( 'nvx_hubspot_secure_is_uuid_v4' ) || ! nvx_hubspot_secure_is_uuid_v4( $lead_id ) ) {
		error_log( '[NUVANX] lead-captured relay skipped: valid nvx_lead_id missing.' );
		return $response;
	}

	$is_test     = isset( $fields['nvx_is_test_lead'] ) && 'true' === strtolower( $fields['nvx_is_test_lead'] );
	$test_run_id = isset( $fields['nvx_test_run_id'] ) ? $fields['nvx_test_run_id'] : '';
	$email       = isset( $fields['email'] ) ? strtolower( trim( $fields['email'] ) ) : '';
	$ids         = nvx_lead_captured_hubspot_ids( $response );

	$relay_payload = array(
		'nvx_lead_id'           => $lead_id,
		'form_id'               => nvx_hubspot_secure_form_id(),
		'hubspot_contact_id'     => '' !== $ids['contact_id'] ? $ids['contact_id'] : null,
		'hubspot_submission_id'  => '' !== $ids['submission_id'] ? $ids['submission_id'] : null,
		'email_hash'             => '' !== $email ? hash( 'sha256', $email ) : null,
		'nvx_is_test_lead'       => $is_test,
		'nvx_test_run_id'        => '' !== $test_run_id ? $test_run_id : null,
		'first_attribution'      => nvx_lead_captured_attribution( $fields, 'nvx_first_' ),
		'conversion_attribution' => nvx_lead_captured_attribution( $fields, 'nvx_conversion_' ),
	);

	$relay = wp_remote_post(
		nvx_lead_captured_endpoint(),
		array(
			'timeout'  => 5,
			'blocking' => true,
			'headers'  => array(
				'Content-Type'              => 'application/json',
				'x-nvx-lead-capture-secret' => $secret,
			),
			'body'     => wp_json_encode( $relay_payload ),
		)
	);
	if ( is_wp_error( $relay ) || (int) wp_remote_retrieve_response_code( $relay ) >= 300 ) {
		error_log( '[NUVANX] lead-captured relay failed after successful HubSpot submission.' );
	}

	return $response;
}
add_filter( 'http_response', 'nvx_lead_captured_on_http_response', 10, 3 );
