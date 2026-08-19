<?php
/**
 * Optional Meta CAPI consumer for the canonical nvx_lead_captured lifecycle.
 *
 * Disabled by default. Delivery requires explicit server-side enablement,
 * explicit marketing consent, a non-QA lead, and the rotated shared secret.
 * No treatment, message, page path, diagnosis, procedure, or other clinical
 * semantics are included in the outbound payload.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Whether Meta CAPI delivery is explicitly enabled server-side. */
function nvx_meta_capi_enabled(): bool {
	if ( defined( 'NVX_META_CAPI_ENABLED' ) ) {
		return true === NVX_META_CAPI_ENABLED || '1' === (string) NVX_META_CAPI_ENABLED;
	}
	return '1' === (string) ( getenv( 'NVX_META_CAPI_ENABLED' ) ?: '' );
}

/** Canonical relay endpoint; runtime input cannot override it. */
function nvx_meta_capi_endpoint(): string {
	return 'https://ssvvuuysgxyqvmovrlvk.supabase.co/functions/v1/web-events';
}

/** Server-only rotated shared secret. */
function nvx_meta_capi_secret(): string {
	if ( defined( 'NVX_WEB_EVENT_SECRET' ) ) {
		return trim( (string) NVX_WEB_EVENT_SECRET );
	}
	return trim( (string) ( getenv( 'NVX_WEB_EVENT_SECRET' ) ?: '' ) );
}

/** Resolve an allowed first-party Meta cookie after the consent gate. */
function nvx_meta_capi_cookie_value( string $name ): string {
	if ( ! in_array( $name, array( '_fbc', '_fbp' ), true ) || ! isset( $_COOKIE[ $name ] ) ) {
		return '';
	}
	$value = sanitize_text_field( wp_unslash( (string) $_COOKIE[ $name ] ) );
	return strlen( $value ) <= 255 ? $value : '';
}

/** Emit bounded operational telemetry without PII. */
function nvx_meta_capi_log( string $outcome, int $status = 0 ): void {
	$outcome = strtoupper( $outcome );
	if ( ! in_array( $outcome, array( 'SUCCESS', 'FAILURE', 'SUPPRESSED' ), true ) ) {
		return;
	}
	$line = 'NVX_META_CAPI_' . $outcome;
	if ( $status > 0 ) {
		$line .= ' status=' . $status;
	}
	error_log( $line );
}

/**
 * Deliver one minimized Meta Lead event from a canonical captured lead.
 *
 * @param array<string,mixed> $event Canonical lead lifecycle event.
 */
function nvx_meta_capi_on_lead_captured( array $event ): void {
	if ( ! nvx_meta_capi_enabled() ) {
		return;
	}

	// Defense in depth: QA and no-consent leads never reach the relay.
	if ( ! empty( $event['nvx_is_test_lead'] ) || empty( $event['marketing_consent'] ) ) {
		nvx_meta_capi_log( 'SUPPRESSED' );
		return;
	}

	$lead_id = strtolower( trim( (string) ( $event['nvx_lead_id'] ?? '' ) ) );
	if ( ! function_exists( 'nvx_attribution_is_uuid_v4' ) || ! nvx_attribution_is_uuid_v4( $lead_id ) ) {
		nvx_meta_capi_log( 'FAILURE' );
		return;
	}

	$secret = nvx_meta_capi_secret();
	if ( '' === $secret ) {
		nvx_meta_capi_log( 'FAILURE' );
		return;
	}

	$email = sanitize_email( (string) ( $event['email'] ?? '' ) );
	$phone = preg_replace( '/[^0-9+]/', '', (string) ( $event['phone'] ?? '' ) );
	if ( ! is_string( $phone ) ) {
		$phone = '';
	}

	$payload = array(
		'event_name'        => 'Lead',
		'event_id'          => 'nvx-lead-' . $lead_id,
		'nvx_lead_id'       => $lead_id,
		'nvx_is_test_lead'  => false,
		'email'             => $email,
		'phone'             => substr( $phone, 0, 32 ),
		'fbc'               => nvx_meta_capi_cookie_value( '_fbc' ),
		'fbp'               => nvx_meta_capi_cookie_value( '_fbp' ),
		'user_agent'        => isset( $_SERVER['HTTP_USER_AGENT'] )
			? substr( sanitize_text_field( wp_unslash( (string) $_SERVER['HTTP_USER_AGENT'] ) ), 0, 512 )
			: '',
		'client_ip_address' => isset( $_SERVER['REMOTE_ADDR'] )
			? substr( sanitize_text_field( wp_unslash( (string) $_SERVER['REMOTE_ADDR'] ) ), 0, 45 )
			: '',
	);

	$response = wp_remote_post(
		nvx_meta_capi_endpoint(),
		array(
			'timeout'     => 8,
			'redirection' => 0,
			'headers'     => array(
				'Content-Type'           => 'application/json',
				'x-nvx-web-event-secret' => $secret,
			),
			'body'        => wp_json_encode( $payload ),
		)
	);

	if ( is_wp_error( $response ) ) {
		nvx_meta_capi_log( 'FAILURE' );
		return;
	}

	$status = (int) wp_remote_retrieve_response_code( $response );
	if ( $status >= 200 && $status < 300 ) {
		nvx_meta_capi_log( 'SUCCESS', $status );
		return;
	}
	nvx_meta_capi_log( 'FAILURE', $status );
}
add_action( 'nvx_lead_captured', 'nvx_meta_capi_on_lead_captured', 20, 1 );
