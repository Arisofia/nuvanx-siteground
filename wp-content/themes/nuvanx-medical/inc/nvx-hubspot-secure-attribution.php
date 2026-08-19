<?php
/**
 * HubSpot Secure Attribution Bridge — Runtime Contract v2
 *
 * Intercepts the WordPress HTTP layer to preempt the canonical HubSpot public
 * form submission and replace it with an authenticated server-side request that:
 *
 *   1. Strips all client-supplied reserved attribution and QA fields (reserved strip).
 *   2. Rebuilds QA identity server-side (nvx_is_test_lead, nvx_test_run_id).
 *   3. Injects verified attribution fields from the server context.
 *   4. Sends exactly one authenticated POST using Bearer auth.
 *   5. On Staging2, blocks outbound by default; releases only for server-owned QA payloads.
 *
 * Security requirements:
 *   - NVX_HUBSPOT_ACCESS_TOKEN must be a server-side constant — never hardcoded here.
 *   - Browser POST data must NEVER be able to enable test-lead mode (see strip below).
 *   - Staging outbound is blocked at priority 10; QA release fires at PHP_INT_MAX.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return the canonical HubSpot public forms submit URL that we intercept.
 */
function nvx_hubspot_secure_original_url(): string {
	$portal_id = defined( 'NVX_HUBSPOT_PORTAL_ID' ) ? (string) NVX_HUBSPOT_PORTAL_ID : (string) ( getenv( 'NVX_HUBSPOT_PORTAL_ID' ) ?: '' );
	$form_id   = defined( 'NVX_HUBSPOT_VALORACION_FORM_ID' ) ? (string) NVX_HUBSPOT_VALORACION_FORM_ID : (string) ( getenv( 'NVX_HUBSPOT_VALORACION_FORM_ID' ) ?: '' );
	return 'https://forms.hsforms.com/submissions/v3/integration/submit/' . $portal_id . '/' . $form_id;
}

/**
 * Return the authenticated HubSpot server-to-server submit URL.
 */
function nvx_hubspot_secure_submit_url(): string {
	$portal_id = defined( 'NVX_HUBSPOT_PORTAL_ID' ) ? (string) NVX_HUBSPOT_PORTAL_ID : (string) ( getenv( 'NVX_HUBSPOT_PORTAL_ID' ) ?: '' );
	$form_id   = defined( 'NVX_HUBSPOT_VALORACION_FORM_ID' ) ? (string) NVX_HUBSPOT_VALORACION_FORM_ID : (string) ( getenv( 'NVX_HUBSPOT_VALORACION_FORM_ID' ) ?: '' );
	return 'https://api.hsforms.com/submissions/v3/integration/secure/submit/' . $portal_id . '/' . $form_id;
}

/**
 * Retrieve a field value from browser POST data, with an integer max-length guard.
 *
 * @param string $name    POST field name.
 * @param int    $max_len Maximum allowed length (default 4096).
 */
function nvx_hubspot_secure_post_value( string $name, int $max_len = 4096 ): string {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- server bridge, nonce not applicable
	$value = isset( $_POST[ $name ] ) ? (string) $_POST[ $name ] : '';
	return function_exists( 'mb_substr' ) ? mb_substr( $value, 0, $max_len ) : substr( $value, 0, $max_len );
}

/**
 * Reserved attribution and QA fields that must NEVER be accepted from browser POST.
 *
 * @return string[]
 */
function nvx_hubspot_secure_reserved_fields(): array {
	return array(
		'nvx_is_test_lead',
		'nvx_test_run_id',
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
	);
}

/**
 * Canonical UTM parameter → CRM property mapping.
 *
 * @return array<string, string>
 */
function nvx_hubspot_secure_utm_field_map(): array {
	return array(
		'utm_source'   => 'nvx_utm_source',
		'utm_medium'   => 'nvx_utm_medium',
		'utm_campaign' => 'nvx_utm_campaign',
		'utm_content'  => 'nvx_utm_content',
		'utm_term'     => 'nvx_utm_term',
	);
}

/**
 * Canonical Google click ID → CRM property mapping.
 *
 * @return array<string, string>
 */
function nvx_hubspot_secure_click_field_map(): array {
	return array(
		'gclid'  => 'nvx_google_click_id',
		'gbraid' => 'nvx_google_braid',
		'wbraid' => 'nvx_google_wbraid',
		'gclsrc' => 'nvx_google_gclsrc',
	);
}

/**
 * Remove all reserved fields from a HubSpot V3 fields array.
 *
 * @param array $fields Raw fields array from the client payload.
 * @return array
 */
function nvx_hubspot_secure_strip_reserved_fields( array $fields ): array {
	$reserved = array_fill_keys( nvx_hubspot_secure_reserved_fields(), true );
	return array_values(
		array_filter(
			$fields,
			static function ( $field ) use ( $reserved ): bool {
				return isset( $field['name'] ) && ! isset( $reserved[ $field['name'] ] );
			}
		)
	);
}

/**
 * Append server-owned QA identity fields. Browser POST cannot set these.
 *
 * @param array $fields Sanitized fields array.
 * @return array
 */
function nvx_hubspot_secure_append_qa( array $fields ): array {
	$qa = function_exists( 'nvx_attribution_qa_context' )
		? nvx_attribution_qa_context()
		: array(
			'is_test_lead' => false,
			'test_run_id'  => '',
		);
	$fields[] = array( 'name' => 'nvx_is_test_lead', 'value' => ! empty( $qa['is_test_lead'] ) ? 'true' : 'false' );
	$fields[] = array( 'name' => 'nvx_test_run_id', 'value' => (string) ( $qa['test_run_id'] ?? '' ) );
	return $fields;
}

/**
 * Whether the preempt value is the canonical staging outbound isolation error.
 *
 * @param mixed $preempt Value from the pre_http_request filter.
 */
function nvx_hubspot_secure_is_staging_isolation_error( $preempt ): bool {
	return $preempt instanceof WP_Error
		&& 'nvx_staging_outbound_blocked' === (string) $preempt->get_error_code();
}

/**
 * Whether a decoded HubSpot payload is a server-owned Staging2 QA payload.
 *
 * @param array $payload Decoded JSON payload.
 */
function nvx_hubspot_secure_payload_is_staging_qa( array $payload ): bool {
	$host = strtolower( (string) wp_parse_url( get_site_url(), PHP_URL_HOST ) );
	if ( ! ( 'staging2.nuvanx.com' === $host ) ) {
		return false;
	}

	$fields      = isset( $payload['fields'] ) && is_array( $payload['fields'] ) ? $payload['fields'] : array();
	$test_lead   = '';
	$test_run_id = '';
	foreach ( $fields as $field ) {
		if ( ! is_array( $field ) ) {
			continue;
		}
		if ( isset( $field['name'] ) && 'nvx_is_test_lead' === $field['name'] ) {
			$test_lead = (string) ( $field['value'] ?? '' );
		}
		if ( isset( $field['name'] ) && 'nvx_test_run_id' === $field['name'] ) {
			$test_run_id = (string) ( $field['value'] ?? '' );
		}
	}

	return 'true' === $test_lead && 0 === strpos( $test_run_id, 'staging2-' );
}

/**
 * Preempt the HubSpot public form submission and replace with authenticated POST.
 *
 * @param mixed  $preempt Existing preempt value.
 * @param array  $args    HTTP request arguments.
 * @param string $url     Target URL.
 * @return mixed
 */
function nvx_hubspot_secure_pre_http_request( $preempt, array $args, string $url ) {
	if ( nvx_hubspot_secure_original_url() !== $url ) {
		return $preempt;
	}

	if ( function_exists( 'nvx_environment_is_staging2' ) && nvx_environment_is_staging2() ) {
		return new WP_Error( 'nvx_staging_outbound_blocked', 'Staging2 outbound HubSpot traffic is blocked.' );
	}

	if ( ! defined( 'NVX_HUBSPOT_ACCESS_TOKEN' ) ) {
		return new WP_Error( 'nvx_missing_credential', 'NVX_HUBSPOT_ACCESS_TOKEN is not defined.' );
	}
	$token = (string) NVX_HUBSPOT_ACCESS_TOKEN;
	if ( '' === $token ) {
		return new WP_Error( 'nvx_missing_credential', 'NVX_HUBSPOT_ACCESS_TOKEN is empty.' );
	}

	if ( '1' !== nvx_hubspot_secure_post_value( 'nvx_marketing_consent', 1 ) ) {
		return new WP_Error( 'nvx_no_consent', 'Marketing attribution requires explicit consent.' );
	}

	$body    = isset( $args['body'] ) ? $args['body'] : '';
	$payload = is_string( $body ) ? json_decode( $body, true ) : (array) $body;
	if ( ! is_array( $payload ) ) {
		$payload = array();
	}

	$fields            = isset( $payload['fields'] ) && is_array( $payload['fields'] ) ? $payload['fields'] : array();
	$fields            = nvx_hubspot_secure_strip_reserved_fields( $fields );
	$fields            = nvx_hubspot_secure_append_qa( $fields );
	$payload['fields'] = $fields;

	$headers                  = array();
	$headers['Content-Type']  = 'application/json';
	$headers['Authorization'] = 'Bearer ' . $token;

	return wp_remote_post(
		nvx_hubspot_secure_submit_url(),
		array(
			'method'  => 'POST',
			'timeout' => 15,
			'headers' => $headers,
			'body'    => wp_json_encode( $payload ),
		)
	);
}
add_filter( 'pre_http_request', 'nvx_hubspot_secure_pre_http_request', 10, 3 );

/**
 * Release a server-owned Staging2 QA submission blocked by the isolation guard.
 *
 * @param mixed  $preempt Existing preempt value.
 * @param array  $args    HTTP request arguments.
 * @param string $url     Target URL.
 * @return mixed
 */
function nvx_hubspot_secure_allow_staging_qa_outbound( $preempt, array $args, string $url ) {
	if ( ! nvx_hubspot_secure_is_staging_isolation_error( $preempt ) ) {
		return $preempt;
	}

	if ( nvx_hubspot_secure_submit_url() !== $url ) {
		return $preempt;
	}

	$body    = isset( $args['body'] ) ? $args['body'] : '';
	$payload = is_string( $body ) ? json_decode( $body, true ) : array();
	if ( ! is_array( $payload ) || ! nvx_hubspot_secure_payload_is_staging_qa( $payload ) ) {
		return $preempt;
	}

	return false;
}
add_filter( 'pre_http_request', 'nvx_hubspot_secure_allow_staging_qa_outbound', PHP_INT_MAX, 3 );
