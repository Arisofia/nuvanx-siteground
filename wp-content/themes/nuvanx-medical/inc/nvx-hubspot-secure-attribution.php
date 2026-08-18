<?php
/**
 * Authenticated HubSpot submission bridge for the first-party valoración form.
 *
 * The public form handler keeps its proven one-call contract. This module
 * short-circuits only that canonical unauthenticated Forms API request and
 * performs one authenticated secure submission after adding server-governed
 * lineage, QA identity and consent-gated attribution fields.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Resolve the canonical HubSpot portal/form pair. */
function nvx_hubspot_secure_form_identity(): array {
	$portal = defined( 'NVX_VALORACION_HS_FRAME_PORTAL_ID' ) ? (string) NVX_VALORACION_HS_FRAME_PORTAL_ID : '147416356';
	$form   = defined( 'NVX_VALORACION_HS_FRAME_FORM_ID' ) ? (string) NVX_VALORACION_HS_FRAME_FORM_ID : '5042522a-0bc5-4381-ac3e-5aee8649b69c';
	return array( $portal, $form );
}

/** Unauthenticated URL emitted by the existing direct-form transport. */
function nvx_hubspot_secure_original_url(): string {
	list( $portal, $form ) = nvx_hubspot_secure_form_identity();
	return 'https://api.hsforms.com/submissions/v3/integration/submit/' . rawurlencode( $portal ) . '/' . rawurlencode( $form );
}

/** Authenticated Forms API endpoint. */
function nvx_hubspot_secure_submit_url(): string {
	list( $portal, $form ) = nvx_hubspot_secure_form_identity();
	return 'https://api.hsforms.com/submissions/v3/integration/secure/submit/' . rawurlencode( $portal ) . '/' . rawurlencode( $form );
}

/** Return the runtime credential without ever exposing it to client code. */
function nvx_hubspot_secure_access_token(): string {
	if ( ! defined( 'NVX_HUBSPOT_ACCESS_TOKEN' ) ) {
		return '';
	}
	$token = trim( (string) NVX_HUBSPOT_ACCESS_TOKEN );
	if ( strlen( $token ) < 24 || 1 !== preg_match( '/^pat-[A-Za-z0-9-]+$/D', $token ) ) {
		return '';
	}
	return $token;
}

/** Sanitize one bounded browser-provided attribution value. */
function nvx_hubspot_secure_post_value( string $key, int $max_length = 512 ): string {
	if ( ! isset( $_POST[ $key ] ) ) {
		return '';
	}
	$value = sanitize_text_field( wp_unslash( (string) $_POST[ $key ] ) );
	if ( '' === $value || strlen( $value ) > $max_length ) {
		return '';
	}
	return $value;
}

/** Resolve a valid session-lineage UUID, generating one when the client lacks it. */
function nvx_hubspot_secure_lead_id(): string {
	$candidate = nvx_hubspot_secure_post_value( 'nvx_lead_id', 36 );
	if ( 1 === preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/iD', $candidate ) ) {
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

/** Add one CRM contact property to the secure Forms API payload. */
function nvx_hubspot_secure_append_field( array &$fields, string $name, string $value ): void {
	if ( '' === $value ) {
		return;
	}
	$fields[] = array(
		'objectTypeId' => '0-1',
		'name'         => $name,
		'value'        => $value,
	);
}

/** Reserved fields must be rebuilt server-side instead of trusted from the browser. */
function nvx_hubspot_secure_reserved_fields(): array {
	return array(
		'nvx_lead_id',
		'nvx_is_test_lead',
		'nvx_test_run_id',
		'nvx_utm_source',
		'nvx_utm_medium',
		'nvx_utm_campaign',
		'nvx_utm_content',
		'nvx_utm_term',
		'nvx_landing_url',
		'nvx_attribution_captured_at',
		'nvx_attribution_expires_at',
		'nvx_google_click_id',
		'nvx_google_braid',
		'nvx_google_wbraid',
		'nvx_google_gclsrc',
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
	);
}

/** Strip all client/legacy copies of server-governed attribution fields. */
function nvx_hubspot_secure_strip_reserved_fields( array $fields ): array {
	$reserved = array_fill_keys( nvx_hubspot_secure_reserved_fields(), true );
	return array_values(
		array_filter(
			$fields,
			static function ( $field ) use ( $reserved ): bool {
				if ( ! is_array( $field ) || ! isset( $field['name'] ) ) {
					return false;
				}
				return ! isset( $reserved[ (string) $field['name'] ] );
			}
		)
	);
}

/** Append deterministic server-owned QA identity. */
function nvx_hubspot_secure_append_qa( array &$fields ): void {
	$qa = function_exists( 'nvx_attribution_qa_context' )
		? nvx_attribution_qa_context()
		: array( 'is_test_lead' => false, 'test_run_id' => '' );

	nvx_hubspot_secure_append_field( $fields, 'nvx_is_test_lead', ! empty( $qa['is_test_lead'] ) ? 'true' : 'false' );
	if ( ! empty( $qa['is_test_lead'] ) && isset( $qa['test_run_id'] ) ) {
		nvx_hubspot_secure_append_field( $fields, 'nvx_test_run_id', substr( sanitize_key( (string) $qa['test_run_id'] ), 0, 200 ) );
	}
}

/** Append marketing attribution only when the canonical consent marker is present. */
function nvx_hubspot_secure_append_marketing_attribution( array &$fields ): void {
	if ( '1' !== nvx_hubspot_secure_post_value( 'nvx_marketing_consent', 1 ) ) {
		return;
	}

	$mapping = array(
		'utm_source'                  => 'nvx_utm_source',
		'utm_medium'                  => 'nvx_utm_medium',
		'utm_campaign'                => 'nvx_utm_campaign',
		'utm_content'                 => 'nvx_utm_content',
		'utm_term'                    => 'nvx_utm_term',
		'nvx_landing_url'             => 'nvx_landing_url',
		'nvx_attribution_captured_at' => 'nvx_attribution_captured_at',
		'nvx_attribution_expires_at'  => 'nvx_attribution_expires_at',
		'gclid'                       => 'nvx_google_click_id',
		'gbraid'                      => 'nvx_google_braid',
		'wbraid'                      => 'nvx_google_wbraid',
		'gclsrc'                      => 'nvx_google_gclsrc',
		'nvx_first_channel'           => 'nvx_first_channel',
		'nvx_first_source'            => 'nvx_first_source',
		'nvx_first_medium'            => 'nvx_first_medium',
		'nvx_first_campaign_id'       => 'nvx_first_campaign_id',
		'nvx_first_referrer_domain'   => 'nvx_first_referrer_domain',
		'nvx_first_landing_url'       => 'nvx_first_landing_url',
		'nvx_first_timestamp'         => 'nvx_first_timestamp',
		'nvx_conversion_channel'      => 'nvx_conversion_channel',
		'nvx_conversion_source'       => 'nvx_conversion_source',
		'nvx_conversion_medium'       => 'nvx_conversion_medium',
		'nvx_conversion_campaign_id'  => 'nvx_conversion_campaign_id',
		'nvx_conversion_landing_url'  => 'nvx_conversion_landing_url',
		'nvx_conversion_timestamp'    => 'nvx_conversion_timestamp',
	);

	$url_fields       = array( 'nvx_landing_url', 'nvx_first_landing_url', 'nvx_conversion_landing_url' );
	$timestamp_fields = array( 'nvx_attribution_captured_at', 'nvx_attribution_expires_at', 'nvx_first_timestamp', 'nvx_conversion_timestamp' );

	foreach ( $mapping as $post_key => $property_name ) {
		$max_length = 512;
		if ( 'gclsrc' === $post_key ) {
			$max_length = 128;
		} elseif ( in_array( $post_key, $url_fields, true ) ) {
			$max_length = 1024;
		} elseif ( in_array( $post_key, $timestamp_fields, true ) ) {
			$max_length = 64;
		} elseif ( 'nvx_first_referrer_domain' === $post_key ) {
			$max_length = 253;
		} elseif ( 0 === strpos( $post_key, 'utm_' ) || false !== strpos( $post_key, 'campaign' ) ) {
			$max_length = 300;
		}
		nvx_hubspot_secure_append_field( $fields, $property_name, nvx_hubspot_secure_post_value( $post_key, $max_length ) );
	}
}

/** Confirm that the body belongs to the canonical first-party valoración submit. */
function nvx_hubspot_secure_payload_is_valoracion( array $payload ): bool {
	$fields = isset( $payload['fields'] ) && is_array( $payload['fields'] ) ? $payload['fields'] : array();
	$names  = array();
	foreach ( $fields as $field ) {
		if ( is_array( $field ) && isset( $field['name'] ) ) {
			$names[] = (string) $field['name'];
		}
	}
	foreach ( array( 'firstname', 'lastname', 'email', 'phone', 'message' ) as $required ) {
		if ( ! in_array( $required, $names, true ) ) {
			return false;
		}
	}

	$page_uri = isset( $payload['context']['pageUri'] ) ? (string) $payload['context']['pageUri'] : '';
	$path     = wp_parse_url( $page_uri, PHP_URL_PATH );
	return '/madrid/valoracion/' === $path;
}

/**
 * Short-circuit the existing unauthenticated Forms API call with one secure
 * authenticated request. The outer wp_remote_post receives this response and
 * therefore never sends a second network request.
 *
 * @param mixed               $preempt Existing preempted response or false.
 * @param array<string,mixed> $args    HTTP request arguments.
 * @param string              $url     Requested URL.
 * @return mixed
 */
function nvx_hubspot_secure_pre_http_request( $preempt, array $args, string $url ) {
	if ( false !== $preempt || nvx_hubspot_secure_original_url() !== $url ) {
		return $preempt;
	}

	$raw_body = isset( $args['body'] ) && is_string( $args['body'] ) ? $args['body'] : '';
	$payload  = '' !== $raw_body ? json_decode( $raw_body, true ) : null;
	if ( ! is_array( $payload ) || ! nvx_hubspot_secure_payload_is_valoracion( $payload ) ) {
		return $preempt;
	}

	$token = nvx_hubspot_secure_access_token();
	if ( '' === $token ) {
		return new WP_Error( 'nvx_hubspot_auth_missing', 'HubSpot secure submission credential is unavailable.' );
	}

	$fields = isset( $payload['fields'] ) && is_array( $payload['fields'] ) ? $payload['fields'] : array();
	$fields = nvx_hubspot_secure_strip_reserved_fields( $fields );
	nvx_hubspot_secure_append_field( $fields, 'nvx_lead_id', nvx_hubspot_secure_lead_id() );
	nvx_hubspot_secure_append_qa( $fields );
	nvx_hubspot_secure_append_marketing_attribution( $fields );
	$payload['fields'] = $fields;

	$body = wp_json_encode( $payload );
	if ( ! is_string( $body ) ) {
		return new WP_Error( 'nvx_hubspot_secure_encode', 'HubSpot secure submission payload could not be encoded.' );
	}

	$secure_args            = $args;
	$secure_args['method']  = 'POST';
	$secure_args['body']    = $body;
	$secure_args['headers'] = is_array( $args['headers'] ?? null ) ? $args['headers'] : array();
	$secure_args['headers']['Content-Type']  = 'application/json';
	$secure_args['headers']['Authorization'] = 'Bearer ' . $token;

	return wp_remote_post( nvx_hubspot_secure_submit_url(), $secure_args );
}
add_filter( 'pre_http_request', 'nvx_hubspot_secure_pre_http_request', 10, 3 );
