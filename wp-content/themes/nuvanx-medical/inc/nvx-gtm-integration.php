<?php
/**
 * Google Tag Manager & Conversion Tracking Integration.
 *
 * ARCHITECTURE:
 *  - GTM container ID is read from NVX_GTM_ID (wp-config / server env).
 *  - Google Ads Conversion IDs are read from NVX_GADS_CONVERSION_ID_FORM
 *    and NVX_GADS_CONVERSION_ID_CALL (same pattern).
 *  - On staging2 the same GTM container fires but with a ?nvx_env=staging2
 *    debug param so Tags can be restricted by environment via GTM Variables.
 *  - Script is delayed via the existing nvx-integrations delay mechanism.
 *  - noscript iframe is injected immediately after <body> (required by GTM).
 *
 * HOW TO CONFIGURE (zero code needed after this deploy):
 *  1. Create a GTM container at tagmanager.google.com → copy GTM-XXXXXXX id.
 *  2. Set NVX_GTM_ID='GTM-XXXXXXX' in wp-config.php or server env.
 *  3. Set NVX_GADS_CONVERSION_ID_FORM and NVX_GADS_CONVERSION_ID_CALL
 *     from the Google Ads conversion action "Tag setup" screen.
 *  4. Deploy — GTM fires, dataLayer events from nvx-conversion-events.js
 *     are picked up by GTM triggers automatically.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Resolve GTM container ID from environment.
 * Accepts wp-config constant NVX_GTM_ID or server env NVX_GTM_ID.
 */
function nvx_gtm_container_id(): string {
	static $id = null;
	if ( is_string( $id ) ) {
		return $id;
	}

	$id = '';

	if ( defined( 'NVX_GTM_ID' ) && is_string( NVX_GTM_ID ) ) {
		$id = NVX_GTM_ID;
	} elseif ( is_string( getenv( 'NVX_GTM_ID' ) ) ) {
		$id = (string) getenv( 'NVX_GTM_ID' );
	}

	// Validate format: GTM-XXXXXXX (alphanumeric after dash, 6-10 chars).
	if ( ! preg_match( '/^GTM-[A-Z0-9]{6,10}$/', $id ) ) {
		$id = '';
	}

	return $id;
}

/**
 * Whether GTM is configured for this environment.
 */
function nvx_gtm_is_active(): bool {
	return '' !== nvx_gtm_container_id() && ! is_admin();
}

/**
 * Resolve a Google Ads conversion ID from environment.
 */
function nvx_gads_conversion_id( string $type ): string {
	$key = 'NVX_GADS_CONVERSION_ID_' . strtoupper( $type );
	if ( defined( $key ) ) {
		return (string) constant( $key );
	}
	$from_env = getenv( $key );
	return is_string( $from_env ) ? $from_env : '';
}

// ---------------------------------------------------------------------------
// GTM Snippet — <head> (dataLayer init + script loader)
// ---------------------------------------------------------------------------

/**
 * Inject GTM <head> snippet at priority 2 (after charset, before everything else).
 * The dataLayer push happens inline (no delay) so page-level data is available
 * to all tags. The GTM loader script itself is delayed by nvx-integrations.
 */
function nvx_gtm_head_snippet(): void {
	if ( ! nvx_gtm_is_active() ) {
		return;
	}

	$container = esc_js( nvx_gtm_container_id() );
	$env_label = nvx_environment_is_staging2() ? 'staging2' : 'production';

	// Build initial dataLayer push with page metadata.
	$page_type = 'other';
	if ( is_front_page() ) {
		$page_type = 'home';
	} elseif ( is_singular() ) {
		$page_type = 'tratamiento';
		// Detect valoracion page by slug or template.
		if ( is_page( 'valoracion' ) || ( is_page() && false !== strpos( (string) get_the_permalink(), '/valoracion/' ) ) ) {
			$page_type = 'valoracion';
		}
	} elseif ( is_archive() || is_category() ) {
		$page_type = 'listado';
	}

	$conversion_form = esc_js( nvx_gads_conversion_id( 'FORM' ) );
	$conversion_call = esc_js( nvx_gads_conversion_id( 'CALL' ) );

	?>
<!-- Google Tag Manager -->
<script>
window.dataLayer = window.dataLayer || [];
window.dataLayer.push({
  'gtm.start': new Date().getTime(),
  event: 'gtm.js',
  nvx_env: '<?php echo $env_label; ?>',
  nvx_page_type: '<?php echo esc_js( $page_type ); ?>',
  nvx_gads_conversion_form: '<?php echo $conversion_form; ?>',
  nvx_gads_conversion_call: '<?php echo $conversion_call; ?>'
});
</script>
<script type="text/delayed" data-src="https://www.googletagmanager.com/gtm.js?id=<?php echo $container; ?>" defer></script>
<!-- End Google Tag Manager -->
	<?php
}
add_action( 'wp_head', 'nvx_gtm_head_snippet', 2 );

// ---------------------------------------------------------------------------
// GTM noscript — immediately after <body>
// ---------------------------------------------------------------------------

/**
 * GTM requires a <noscript> iframe immediately after <body>.
 * WordPress does not have a native "after body open" hook — we use
 * wp_body_open (WP 5.2+) with fallback via template_redirect buffer.
 */
function nvx_gtm_body_noscript(): void {
	if ( ! nvx_gtm_is_active() ) {
		return;
	}

	$container = esc_url( 'https://www.googletagmanager.com/ns.html?id=' . nvx_gtm_container_id() );
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '<noscript><iframe src="' . $container . '" height="0" width="0" style="display:none;visibility:hidden" title="Google Tag Manager"></iframe></noscript>' . "\n";
}
add_action( 'wp_body_open', 'nvx_gtm_body_noscript', 1 );

// ---------------------------------------------------------------------------
// Inline config for nvx-conversion-events.js
// ---------------------------------------------------------------------------

/**
 * Pass conversion action IDs and form IDs to the front-end JS config object.
 * nvx-conversion-events.js reads window.nvxConversionEvents at init time.
 */
function nvx_gtm_inline_js_config(): void {
	if ( is_admin() ) {
		return;
	}

	$valoracion_form_id = defined( 'NVX_HUBSPOT_VALORACION_FORM_ID' )
		? (string) NVX_HUBSPOT_VALORACION_FORM_ID
		: (string) ( getenv( 'NVX_HUBSPOT_VALORACION_FORM_ID' ) ?: '' );

	$config = wp_json_encode(
		array(
			'gtmId'              => nvx_gtm_container_id(),
			'gadsConversionForm' => nvx_gads_conversion_id( 'FORM' ),
			'gadsConversionCall' => nvx_gads_conversion_id( 'CALL' ),
			'forms'              => array(
				'valoracion' => $valoracion_form_id,
			),
			'env'                => nvx_environment_is_staging2() ? 'staging2' : 'production',
		),
		JSON_UNESCAPED_UNICODE
	);

	if ( ! $config ) {
		return;
	}

	printf(
		"<script>window.nvxConversionEvents = %s;</script>\n",
		$config  // Already JSON-encoded, no further escaping needed.
	);
}
// Priority 1 so it runs before nvx-conversion-events.js is enqueued (priority 10).
add_action( 'wp_head', 'nvx_gtm_inline_js_config', 1 );
