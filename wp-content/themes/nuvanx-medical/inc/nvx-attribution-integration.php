<?php
/**
 * Runtime wiring for Attribution Contract v2.
 *
 * - Applies the browser contract to the canonical HubSpot V4 form.
 * - Mirrors successful first-party form attribution to the Supabase collector.
 * - Keeps nvx_lead_id separate from submission_id and the reconciled lead FK.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Enqueue the canonical HubSpot V4 attribution synchronizer after the contract runtime. */
function nvx_attribution_enqueue_hubspot_sync(): void {
	if ( is_admin() ) {
		return;
	}

	wp_enqueue_script(
		'nvx-hubspot-attribution-sync',
		get_template_directory_uri() . '/assets/js/nvx-hubspot-attribution-sync.js',
		array( 'nvx-attribution-contract' ),
		nvx_asset_version( 'assets/js/nvx-hubspot-attribution-sync.js' ),
		array(
			'in_footer' => false,
			'strategy'  => 'defer',
		)
	);
}
add_action( 'wp_enqueue_scripts', 'nvx_attribution_enqueue_hubspot_sync', 9 );

/** Validate a canonical UUID v4 explicitly, matching the collector contract. */
function nvx_attribution_is_uuid_v4( string $value ): bool {
	return 1 === preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $value );
}

/**
 * Single collector configuration for Staging2 and production.
 *
 * Optional wp-config overrides (never env vars):
 * - NVX_ATTRIBUTION_COLLECTOR_URL must keep the pinned HTTPS host and path.
 * - NVX_ATTRIBUTION_ORIGIN_HOSTS may add extra *.nuvanx.com hosts.
 *
 * @return array{host:string,path:string,origin_hosts:string[]}
 */
function nvx_attribution_collector_config(): array {
	return array(
		'host'         => 'ssvvuuysgxyqvmovrlvk.supabase.co',
		'path'         => '/functions/v1/google-click-attribution',
		'origin_hosts' => array( 'nuvanx.com', 'www.nuvanx.com', 'staging2.nuvanx.com' ),
	);
}

/** Whether a site host may represent the Origin sent to the collector. */
function nvx_attribution_is_allowed_origin_host( string $host ): bool {
	$host    = strtolower( trim( $host ) );
	$config  = nvx_attribution_collector_config();
	$allowed = $config['origin_hosts'];

	if ( defined( 'NVX_ATTRIBUTION_ORIGIN_HOSTS' ) && is_array( NVX_ATTRIBUTION_ORIGIN_HOSTS ) ) {
		foreach ( NVX_ATTRIBUTION_ORIGIN_HOSTS as $extra ) {
			$extra = strtolower( trim( (string) $extra ) );
			if ( 'nuvanx.com' === $extra || 1 === preg_match( '/^[a-z0-9-]+(?:\.[a-z0-9-]+)*\.nuvanx\.com$/', $extra ) ) {
				$allowed[] = $extra;
			}
	}

	return in_array( $host, array_values( array_unique( $allowed ) ), true );
}

/** Resolve the only collector endpoint this theme may call. */
function nvx_attribution_collector_endpoint(): string {
	$config    = nvx_attribution_collector_config();
	$canonical = 'https://' . $config['host'] . $config['path'];
	if ( ! defined( 'NVX_ATTRIBUTION_COLLECTOR_URL' ) ) {
		return $canonical;
	}

	$candidate = trim( (string) NVX_ATTRIBUTION_COLLECTOR_URL );
	$parts     = wp_parse_url( $candidate );
	if (
		is_array( $parts )
		&& 'https' === ( $parts['scheme'] ?? '' )
		&& $config['host'] === strtolower( (string) ( $parts['host'] ?? '' ) )
		&& $config['path'] === (string) ( $parts['path'] ?? '' )
		&& ! isset( $parts['query'], $parts['fragment'], $parts['user'], $parts['pass'], $parts['port'] )
	) {
		return $candidate;
	}
	return $canonical;
}

/** Resolve a collector Origin accepted by the production Edge Function. */
function nvx_attribution_collector_origin(): string {
	$host = strtolower( (string) wp_parse_url( get_site_url(), PHP_URL_HOST ) );
	if ( ! nvx_attribution_is_allowed_origin_host( $host ) ) {
		error_log( 'NVX_ATTRIBUTION_DIRECT_RELAY=FAILURE reason=origin_not_allowed' );
		return '';
	}
	return 'https://' . $host;
}

/**
 * Convert HubSpot fields to a simple name => value map.
 *
 * @param array<string,mixed> $payload HubSpot Forms API payload.
 * @return array<string,string>
 */
function nvx_attribution_hubspot_field_map( array $payload ): array {
	$fields = isset( $payload['fields'] ) && is_array( $payload['fields'] ) ? $payload['fields'] : array();
	$output = array();
	foreach ( $fields as $field ) {
		if ( ! is_array( $field ) || ! isset( $field['name'] ) ) {
			continue;
		}
		$name = (string) $field['name'];
		if ( '' === $name ) {
			continue;
		}
		$output[ $name ] = trim( (string) ( $field['value'] ?? '' ) );
	}
	return $output;
}

/** Emit bounded, non-PII collector telemetry. */
function nvx_attribution_log_direct_relay( string $outcome, int $status = 0 ): void {
	$outcome = strtoupper( $outcome );
	if ( ! in_array( $outcome, array( 'SUCCESS', 'FAILURE' ), true ) ) {
		return;
	}
	$line = 'NVX_ATTRIBUTION_DIRECT_RELAY=' . $outcome;
	if ( $status > 0 ) {
		$line .= ' status=' . $status;
	}
	error_log( $line );
}

/**
 * Relay attribution only after the secure bridge has produced a successful HubSpot response.
 *
 * This callback runs after nvx_hubspot_secure_pre_http_request() on the same
 * pre_http_request filter. The original public-form payload is still available
 * in $args, while $preempt already contains the authenticated HubSpot response.
 * Collector failure never changes the HubSpot result or the lead outcome.
 *
 * @param mixed               $preempt Existing preempted HTTP response.
 * @param array<string,mixed> $args    Original public HubSpot request args.
 * @param string              $url     Original public HubSpot request URL.
 * @return mixed
 */
function nvx_attribution_relay_direct_form_after_hubspot( $preempt, array $args, string $url ) {
	if ( ! function_exists( 'nvx_hubspot_secure_original_url' ) || nvx_hubspot_secure_original_url() !== $url ) {
		return $preempt;
	}
	if ( false === $preempt || is_wp_error( $preempt ) ) {
		return $preempt;
	}

	$hubspot_status = (int) wp_remote_retrieve_response_code( $preempt );
	if ( $hubspot_status < 200 || $hubspot_status >= 300 ) {
		return $preempt;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- the direct-form handler validates the nonce before issuing the HubSpot request.
	if ( empty( $_POST['nvx_valoracion_submit'] ) ) {
		return $preempt;
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- same validated direct-form request.
	$marketing_consent = isset( $_POST['nvx_marketing_consent'] ) && '1' === sanitize_text_field( wp_unslash( (string) $_POST['nvx_marketing_consent'] ) );
	if ( ! $marketing_consent ) {
		return $preempt;
	}

	$body    = $args['body'] ?? '';
	$payload = is_string( $body ) ? json_decode( $body, true ) : (array) $body;
	if ( ! is_array( $payload ) ) {
		return $preempt;
	}
	$fields = nvx_attribution_hubspot_field_map( $payload );

	$lead_id = strtolower( (string) ( $fields['nvx_lead_id'] ?? '' ) );
	$email   = strtolower( trim( (string) ( $fields['email'] ?? '' ) ) );
	if ( ! nvx_attribution_is_uuid_v4( $lead_id ) || ! is_email( $email ) ) {
		return $preempt;
	}

	$gclid  = (string) ( $fields['nvx_google_click_id'] ?? '' );
	$gbraid = (string) ( $fields['nvx_google_braid'] ?? '' );
	$wbraid = (string) ( $fields['nvx_google_wbraid'] ?? '' );
	$gclsrc = (string) ( $fields['nvx_google_gclsrc'] ?? '' );
	if ( '' === $gclid && '' === $gbraid && '' === $wbraid ) {
		return $preempt;
	}

	$submission_id = function_exists( 'wp_generate_uuid4' ) ? strtolower( wp_generate_uuid4() ) : '';
	if ( ! nvx_attribution_is_uuid_v4( $submission_id ) ) {
		return $preempt;
	}

	if ( ! function_exists( 'nvx_hubspot_secure_form_id' ) ) {
		return $preempt;
	}
	$form_id = nvx_hubspot_secure_form_id();
	if ( '' === $form_id ) {
		return $preempt;
	}

	$context     = isset( $payload['context'] ) && is_array( $payload['context'] ) ? $payload['context'] : array();
	$landing_url = isset( $context['pageUri'] ) ? esc_url_raw( (string) $context['pageUri'] ) : home_url( '/madrid/valoracion/' );
	$origin      = nvx_attribution_collector_origin();
	if ( '' === $origin ) {
		return $preempt;
	}

	$collector_payload = array(
		'submission_id' => $submission_id,
		'nvx_lead_id'   => $lead_id,
		'email_hash'    => hash( 'sha256', $email ),
		'gclid'         => '' !== $gclid ? $gclid : null,
		'gbraid'        => '' !== $gbraid ? $gbraid : null,
		'wbraid'        => '' !== $wbraid ? $wbraid : null,
		'gclsrc'        => '' !== $gclsrc ? $gclsrc : null,
		'form_id'       => $form_id,
		'landing_url'   => $landing_url,
	);

	$response = wp_remote_post(
		nvx_attribution_collector_endpoint(),
		array(
			'timeout'     => 1.5,
			'redirection' => 0,
			'headers'     => array(
				'Content-Type' => 'application/json',
				'Origin'       => $origin,
			),
			'body'        => wp_json_encode( $collector_payload ),
		)
	);

	if ( is_wp_error( $response ) ) {
		nvx_attribution_log_direct_relay( 'FAILURE' );
		return $preempt;
	}
	$status = (int) wp_remote_retrieve_response_code( $response );
	if ( $status >= 200 && $status < 300 ) {
		nvx_attribution_log_direct_relay( 'SUCCESS', $status );
	} else {
		nvx_attribution_log_direct_relay( 'FAILURE', $status );
	}

	return $preempt;
}
add_filter( 'pre_http_request', 'nvx_attribution_relay_direct_form_after_hubspot', 20, 3 );
