<?php
/**
 * HubSpot Secure Attribution Bridge — Runtime Contract v3.
 *
 * The public first-party form remains the patient-facing owner. HubSpot Forms
 * receives only fields that actually exist on the canonical V4 form, while
 * CRM enrichment owns message, lineage, QA identity and extended attribution.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Resolve the canonical HubSpot portal id. */
function nvx_hubspot_secure_portal_id(): string {
	if ( defined( 'NVX_HUBSPOT_PORTAL_ID' ) && '' !== (string) NVX_HUBSPOT_PORTAL_ID ) {
		return (string) NVX_HUBSPOT_PORTAL_ID;
	}
	if ( defined( 'NVX_VALORACION_HS_FRAME_PORTAL_ID' ) && '' !== (string) NVX_VALORACION_HS_FRAME_PORTAL_ID ) {
		return (string) NVX_VALORACION_HS_FRAME_PORTAL_ID;
	}
	$env = (string) ( getenv( 'NVX_HUBSPOT_PORTAL_ID' ) ?: '' );
	return '' !== $env ? $env : '147416356';
}

/** Resolve the canonical HubSpot valoración form id. */
function nvx_hubspot_secure_form_id(): string {
	if ( defined( 'NVX_HUBSPOT_VALORACION_FORM_ID' ) && '' !== (string) NVX_HUBSPOT_VALORACION_FORM_ID ) {
		return (string) NVX_HUBSPOT_VALORACION_FORM_ID;
	}
	if ( defined( 'NVX_VALORACION_HS_FRAME_FORM_ID' ) && '' !== (string) NVX_VALORACION_HS_FRAME_FORM_ID ) {
		return (string) NVX_VALORACION_HS_FRAME_FORM_ID;
	}
	$env = (string) ( getenv( 'NVX_HUBSPOT_VALORACION_FORM_ID' ) ?: '' );
	return '' !== $env ? $env : '5042522a-0bc5-4381-ac3e-5aee8649b69c';
}

/** Public Forms endpoint intercepted by this bridge. */
function nvx_hubspot_secure_original_url(): string {
	return 'https://api.hsforms.com/submissions/v3/integration/submit/'
		. rawurlencode( nvx_hubspot_secure_portal_id() ) . '/'
		. rawurlencode( nvx_hubspot_secure_form_id() );
}

/** Authenticated Forms endpoint. */
function nvx_hubspot_secure_submit_url(): string {
	return 'https://api.hsforms.com/submissions/v3/integration/secure/submit/'
		. rawurlencode( nvx_hubspot_secure_portal_id() ) . '/'
		. rawurlencode( nvx_hubspot_secure_form_id() );
}

/** Contacts CRM endpoint. */
function nvx_hubspot_secure_contacts_url(): string {
	return 'https://api.hubapi.com/crm/v3/objects/contacts';
}

/** Retrieve a bounded POST value from the validated first-party request. */
function nvx_hubspot_secure_post_value( string $name, int $max_len = 4096 ): string {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- bridge executes inside the nonce-validated direct form request.
	$value = isset( $_POST[ $name ] ) ? (string) wp_unslash( $_POST[ $name ] ) : '';
	$value = function_exists( 'mb_substr' ) ? mb_substr( $value, 0, $max_len ) : substr( $value, 0, $max_len );
	return trim( $value );
}

/** Validate a canonical UUID v4 lineage value. */
function nvx_hubspot_secure_is_uuid_v4( string $value ): bool {
	return 1 === preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $value );
}

/** Fields whose browser values never control QA identity. */
function nvx_hubspot_secure_server_owned_fields(): array {
	return array( 'nvx_is_test_lead', 'nvx_test_run_id' );
}

/** Marketing fields stripped when consent is absent. */
function nvx_hubspot_secure_marketing_fields(): array {
	return array(
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
		'nvx_google_click_id',
		'nvx_google_braid',
		'nvx_google_wbraid',
		'nvx_google_gclsrc',
		'nvx_utm_source',
		'nvx_utm_medium',
		'nvx_utm_campaign',
		'nvx_utm_content',
		'nvx_utm_term',
		'nvx_landing_url',
		'nvx_attribution_captured_at',
		'nvx_attribution_expires_at',
		'hs_google_click_id',
	);
}

/**
 * Live canonical V4 form schema, verified read-only through HubSpot API.
 * Extended lineage properties intentionally live on the Contact CRM object.
 */
function nvx_hubspot_secure_form_field_names(): array {
	return array(
		'firstname',
		'lastname',
		'email',
		'phone',
		'nvx_attribution_captured_at',
		'nvx_attribution_expires_at',
		'nvx_google_braid',
		'nvx_google_click_id',
		'nvx_google_gclsrc',
		'nvx_google_wbraid',
		'nvx_landing_url',
		'nvx_utm_campaign',
		'nvx_utm_content',
		'nvx_utm_medium',
		'nvx_utm_source',
		'nvx_utm_term',
	);
}

/** Strip browser-owned QA and non-consented marketing fields. */
function nvx_hubspot_secure_filter_fields( array $fields, bool $marketing_consent ): array {
	$server_owned = array_fill_keys( nvx_hubspot_secure_server_owned_fields(), true );
	$marketing    = array_fill_keys( nvx_hubspot_secure_marketing_fields(), true );
	$output       = array();

	foreach ( $fields as $field ) {
		if ( ! is_array( $field ) || ! isset( $field['name'] ) ) {
			continue;
		}
		$name = (string) $field['name'];
		if ( isset( $server_owned[ $name ] ) ) {
			continue;
		}
		if ( ! $marketing_consent && isset( $marketing[ $name ] ) ) {
			continue;
		}
		if ( 'nvx_lead_id' === $name ) {
			$value = strtolower( trim( (string) ( $field['value'] ?? '' ) ) );
			if ( ! nvx_hubspot_secure_is_uuid_v4( $value ) ) {
				continue;
			}
			$field['value'] = $value;
		}
		$output[] = $field;
	}

	return array_values( $output );
}

/** Keep only fields accepted by the live Forms schema. */
function nvx_hubspot_secure_filter_form_fields( array $fields ): array {
	$allowed = array_fill_keys( nvx_hubspot_secure_form_field_names(), true );
	return array_values(
		array_filter(
			$fields,
			static function ( $field ) use ( $allowed ): bool {
				return is_array( $field ) && isset( $field['name'] ) && isset( $allowed[ (string) $field['name'] ] );
			}
		)
	);
}

/** Convert HubSpot field rows to name => scalar value. */
function nvx_hubspot_secure_field_map( array $fields ): array {
	$mapped = array();
	foreach ( $fields as $field ) {
		if ( ! is_array( $field ) || ! isset( $field['name'] ) ) {
			continue;
		}
		$mapped[ (string) $field['name'] ] = trim( (string) ( $field['value'] ?? '' ) );
	}
	return $mapped;
}

/** Server-owned QA context. */
function nvx_hubspot_secure_qa_context(): array {
	$qa = function_exists( 'nvx_attribution_qa_context' )
		? nvx_attribution_qa_context()
		: array( 'is_test_lead' => false, 'test_run_id' => '' );
	return array(
		'is_test_lead' => ! empty( $qa['is_test_lead'] ),
		'test_run_id'  => trim( (string) ( $qa['test_run_id'] ?? '' ) ),
	);
}

/** Preserve the resolved CRM contact id for the synchronous capture relay. */
function nvx_hubspot_secure_set_last_contact_id( string $contact_id ): void {
	$GLOBALS['nvx_hubspot_secure_last_contact_id'] = preg_match( '/^[1-9][0-9]{0,18}$/', $contact_id ) ? $contact_id : '';
}

/** Return the CRM contact id resolved during the current request. */
function nvx_hubspot_secure_last_contact_id(): string {
	$value = isset( $GLOBALS['nvx_hubspot_secure_last_contact_id'] ) ? (string) $GLOBALS['nvx_hubspot_secure_last_contact_id'] : '';
	return preg_match( '/^[1-9][0-9]{0,18}$/', $value ) ? $value : '';
}

/** Build CRM properties without allowing browser-controlled QA identity. */
function nvx_hubspot_secure_crm_properties( array $fields, bool $marketing_consent ): array {
	$mapped = nvx_hubspot_secure_field_map( $fields );
	$qa     = nvx_hubspot_secure_qa_context();
	$out    = array();

	foreach ( array( 'firstname', 'lastname', 'email', 'phone', 'message', 'nvx_lead_id' ) as $name ) {
		if ( isset( $mapped[ $name ] ) && '' !== $mapped[ $name ] ) {
			$out[ $name ] = $mapped[ $name ];
		}
	}

	$out['nvx_is_test_lead'] = $qa['is_test_lead'] ? 'true' : 'false';
	if ( $qa['is_test_lead'] && '' !== $qa['test_run_id'] ) {
		$out['nvx_test_run_id'] = $qa['test_run_id'];
	}

	if ( $marketing_consent ) {
		foreach ( nvx_hubspot_secure_marketing_fields() as $name ) {
			if ( 'hs_google_click_id' === $name ) {
				continue;
			}
			if ( isset( $mapped[ $name ] ) && '' !== $mapped[ $name ] ) {
				$out[ $name ] = $mapped[ $name ];
			}
		}
	}

	return $out;
}

/** Decode a bounded HubSpot JSON response. */
function nvx_hubspot_secure_response_json( $response ): array {
	if ( is_wp_error( $response ) ) {
		return array();
	}
	$decoded = json_decode( (string) wp_remote_retrieve_body( $response ), true );
	return is_array( $decoded ) ? $decoded : array();
}

/** Find a Contact CRM id by exact email. */
function nvx_hubspot_secure_find_contact( string $email, string $token ): array {
	$body = wp_json_encode(
		array(
			'filterGroups' => array(
				array(
					'filters' => array(
						array( 'propertyName' => 'email', 'operator' => 'EQ', 'value' => $email ),
					),
				),
			),
			'properties' => array( 'email' ),
			'limit'      => 1,
		)
	);
	if ( ! is_string( $body ) ) {
		return array( 'ok' => false, 'contact_id' => '', 'status' => 0 );
	}

	$response = wp_remote_post(
		nvx_hubspot_secure_contacts_url() . '/search',
		array(
			'timeout' => 10,
			'headers' => array( 'Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $token ),
			'body'    => $body,
		)
	);
	if ( is_wp_error( $response ) ) {
		return array( 'ok' => false, 'contact_id' => '', 'status' => 0 );
	}
	$status = (int) wp_remote_retrieve_response_code( $response );
	if ( $status < 200 || $status >= 300 ) {
		return array( 'ok' => false, 'contact_id' => '', 'status' => $status );
	}
	$json       = nvx_hubspot_secure_response_json( $response );
	$contact_id = isset( $json['results'][0]['id'] ) ? (string) $json['results'][0]['id'] : '';
	return array( 'ok' => true, 'contact_id' => preg_match( '/^[1-9][0-9]{0,18}$/', $contact_id ) ? $contact_id : '', 'status' => $status );
}

/** Create or update one Contact CRM record before the Forms submission. */
function nvx_hubspot_secure_enrich_contact( array $properties, string $token ): array {
	$email = isset( $properties['email'] ) ? sanitize_email( (string) $properties['email'] ) : '';
	if ( '' === $email || ! is_email( $email ) ) {
		return array( 'ok' => false, 'contact_id' => '', 'status' => 0 );
	}
	$properties['email'] = $email;
	$found               = nvx_hubspot_secure_find_contact( $email, $token );
	if ( ! $found['ok'] ) {
		return $found;
	}

	$body = wp_json_encode( array( 'properties' => $properties ) );
	if ( ! is_string( $body ) ) {
		return array( 'ok' => false, 'contact_id' => '', 'status' => 0 );
	}

	$contact_id = (string) $found['contact_id'];
	if ( '' !== $contact_id ) {
		$response = wp_remote_request(
			nvx_hubspot_secure_contacts_url() . '/' . rawurlencode( $contact_id ),
			array(
				'method'  => 'PATCH',
				'timeout' => 10,
				'headers' => array( 'Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $token ),
				'body'    => $body,
			)
		);
	} else {
		$response = wp_remote_post(
			nvx_hubspot_secure_contacts_url(),
			array(
				'timeout' => 10,
				'headers' => array( 'Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $token ),
				'body'    => $body,
			)
		);
	}

	if ( is_wp_error( $response ) ) {
		return array( 'ok' => false, 'contact_id' => '', 'status' => 0 );
	}
	$status = (int) wp_remote_retrieve_response_code( $response );
	if ( $status < 200 || $status >= 300 ) {
		return array( 'ok' => false, 'contact_id' => '', 'status' => $status );
	}
	$json = nvx_hubspot_secure_response_json( $response );
	$id   = isset( $json['id'] ) ? (string) $json['id'] : $contact_id;
	if ( ! preg_match( '/^[1-9][0-9]{0,18}$/', $id ) ) {
		return array( 'ok' => false, 'contact_id' => '', 'status' => $status );
	}
	nvx_hubspot_secure_set_last_contact_id( $id );
	return array( 'ok' => true, 'contact_id' => $id, 'status' => $status );
}

/** Staging2 may emit only deterministic server-owned QA submissions. */
function nvx_hubspot_secure_payload_is_staging_qa( array $payload ): bool {
	unset( $payload );
	$host = strtolower( (string) wp_parse_url( get_site_url(), PHP_URL_HOST ) );
	$qa   = nvx_hubspot_secure_qa_context();
	return 'staging2.nuvanx.com' === $host
		&& true === $qa['is_test_lead']
		&& 0 === strpos( $qa['test_run_id'], 'staging2-' );
}

/**
 * Preempt the public HubSpot form request with CRM enrichment plus one secure
 * Forms submission constrained to the live canonical form schema.
 */
function nvx_hubspot_secure_pre_http_request( $preempt, array $args, string $url ) {
	if ( nvx_hubspot_secure_original_url() !== $url ) {
		return $preempt;
	}
	if ( ! defined( 'NVX_HUBSPOT_ACCESS_TOKEN' ) ) {
		return new WP_Error( 'nvx_missing_credential', 'NVX_HUBSPOT_ACCESS_TOKEN is not defined.' );
	}
	$token = trim( (string) NVX_HUBSPOT_ACCESS_TOKEN );
	if ( '' === $token ) {
		return new WP_Error( 'nvx_missing_credential', 'NVX_HUBSPOT_ACCESS_TOKEN is empty.' );
	}

	$body    = isset( $args['body'] ) ? $args['body'] : '';
	$payload = is_string( $body ) ? json_decode( $body, true ) : (array) $body;
	if ( ! is_array( $payload ) ) {
		$payload = array();
	}

	$marketing_consent = '1' === nvx_hubspot_secure_post_value( 'nvx_marketing_consent', 1 );
	$fields            = isset( $payload['fields'] ) && is_array( $payload['fields'] ) ? $payload['fields'] : array();
	$fields            = nvx_hubspot_secure_filter_fields( $fields, $marketing_consent );

	if ( function_exists( 'nvx_environment_is_staging2' ) && nvx_environment_is_staging2() && ! nvx_hubspot_secure_payload_is_staging_qa( $payload ) ) {
		return new WP_Error( 'nvx_staging_outbound_blocked', 'Staging2 outbound HubSpot traffic is restricted to server-owned QA submissions.' );
	}

	$crm = nvx_hubspot_secure_enrich_contact( nvx_hubspot_secure_crm_properties( $fields, $marketing_consent ), $token );
	if ( ! $crm['ok'] ) {
		return new WP_Error( 'nvx_hubspot_crm_enrichment_failed', 'HubSpot CRM enrichment failed.', array( 'status' => (int) $crm['status'] ) );
	}

	$payload['fields'] = nvx_hubspot_secure_filter_form_fields( $fields );
	return wp_remote_post(
		nvx_hubspot_secure_submit_url(),
		array(
			'method'  => 'POST',
			'timeout' => 15,
			'headers' => array( 'Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $token ),
			'body'    => wp_json_encode( $payload ),
		)
	);
}
add_filter( 'pre_http_request', 'nvx_hubspot_secure_pre_http_request', 10, 3 );
