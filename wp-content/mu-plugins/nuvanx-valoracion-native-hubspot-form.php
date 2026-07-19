<?php
/**
 * Plugin Name: NUVANX Valoración Native HubSpot Form
 * Description: Enforces one canonical HubSpot form on /madrid/valoracion/.
 * Version: 2026.07.19.5
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'NVX_VALORACION_HS_FRAME_PORTAL_ID' ) ) {
	define( 'NVX_VALORACION_HS_FRAME_PORTAL_ID', '147416356' );
}
if ( ! defined( 'NVX_VALORACION_HS_FRAME_FORM_ID' ) ) {
	define( 'NVX_VALORACION_HS_FRAME_FORM_ID', '5042522a-0bc5-4381-ac3e-5aee8649b69c' );
}
if ( ! defined( 'NVX_VALORACION_HS_FRAME_REGION' ) ) {
	define( 'NVX_VALORACION_HS_FRAME_REGION', 'eu1' );
}

function nvx_valoracion_native_hubspot_is_target_page(): bool {
	return is_page( 2636 ) || is_page( 'valoracion' );
}

/**
 * Canonical server-side form markup.
 */
function nvx_valoracion_native_hubspot_mount_markup(): string {
	$portal_id     = esc_attr( NVX_VALORACION_HS_FRAME_PORTAL_ID );
	$form_id       = esc_attr( NVX_VALORACION_HS_FRAME_FORM_ID );
	$region        = esc_attr( NVX_VALORACION_HS_FRAME_REGION );
	$portal_script = esc_url( 'https://js-eu1.hsforms.net/forms/embed/' . NVX_VALORACION_HS_FRAME_PORTAL_ID . '.js' );
	$privacy_url   = esc_url( home_url( '/politica-privacidad/' ) );

	return '<script src="' . $portal_script . '" defer></script>'
		. '<div class="hs-form-frame" data-region="' . $region . '" data-form-id="' . $form_id . '" data-portal-id="' . $portal_id . '"></div>'
		. '<p class="nvx-copy nvx-hubspot-privacy">Al facilitar tus datos aceptas la <a class="nvx-text-link" href="' . $privacy_url . '">Política de privacidad</a>.</p>';
}

/**
 * Return the complete range of a balanced div beginning at the supplied offset.
 *
 * @return array{start:int,length:int}|null
 */
function nvx_valoracion_balanced_div_range( string $html, int $open_offset ): ?array {
	if ( $open_offset < 0 || ! preg_match( '/\G<div\b[^>]*>/i', $html, $opening, 0, $open_offset ) ) {
		return null;
	}

	if ( ! preg_match_all( '/<div\b[^>]*>|<\/div\s*>/i', $html, $tokens, PREG_OFFSET_CAPTURE, $open_offset ) ) {
		return null;
	}

	$depth = 0;
	foreach ( $tokens[0] as $token ) {
		$markup = (string) $token[0];
		$offset = (int) $token[1];

		if ( 0 === stripos( $markup, '</div' ) ) {
			$depth--;
		} else {
			$depth++;
		}

		if ( 0 === $depth ) {
			return array(
				'start'  => $open_offset,
				'length' => $offset + strlen( $markup ) - $open_offset,
			);
		}
	}

	return null;
}

/**
 * Remove complete div blocks carrying a class token.
 */
function nvx_valoracion_remove_divs_by_class( string $html, string $class_token ): string {
	$pattern = '/<div\b(?=[^>]*\bclass=["\'][^"\']*\b'
		. preg_quote( $class_token, '/' )
		. '\b[^"\']*["\'])[^>]*>/i';

	if ( ! preg_match_all( $pattern, $html, $matches, PREG_OFFSET_CAPTURE ) ) {
		return $html;
	}

	$ranges = array();
	foreach ( $matches[0] as $match ) {
		$range = nvx_valoracion_balanced_div_range( $html, (int) $match[1] );
		if ( is_array( $range ) ) {
			$ranges[] = $range;
		}
	}

	usort(
		$ranges,
		static function ( array $a, array $b ): int {
			return $b['start'] <=> $a['start'];
		}
	);

	foreach ( $ranges as $range ) {
		$html = substr_replace( $html, '', $range['start'], $range['length'] );
	}

	return $html;
}

/**
 * Rebuild the valoración mount and remove every competing HubSpot instance.
 */
function nvx_valoracion_native_hubspot_enforce_single_mount( string $html ): string {
	$mount_pattern = '/<div\b[^>]*\bid=["\']nvx-hubspot-native-form["\'][^>]*>/i';

	if ( ! preg_match_all( $mount_pattern, $html, $mounts, PREG_OFFSET_CAPTURE ) || empty( $mounts[0] ) ) {
		return $html;
	}

	$ranges = array();
	foreach ( $mounts[0] as $mount ) {
		$range = nvx_valoracion_balanced_div_range( $html, (int) $mount[1] );
		if ( is_array( $range ) ) {
			$range['opening'] = (string) $mount[0];
			$ranges[]         = $range;
		}
	}

	if ( empty( $ranges ) ) {
		return $html;
	}

	usort(
		$ranges,
		static function ( array $a, array $b ): int {
			return $a['start'] <=> $b['start'];
		}
	);

	$first_offset  = (int) $ranges[0]['start'];
	$first_opening = (string) $ranges[0]['opening'];

	$descending = $ranges;
	usort(
		$descending,
		static function ( array $a, array $b ): int {
			return $b['start'] <=> $a['start'];
		}
	);
	foreach ( $descending as $range ) {
		$html = substr_replace( $html, '', (int) $range['start'], (int) $range['length'] );
	}

	$html = preg_replace( '#<script\b[^>]*\bsrc=["\'][^"\']*hsforms\.net/[^"\']*["\'][^>]*>\s*</script>#iu', '', $html ) ?? $html;
	$html = preg_replace( '#<iframe\b[^>]*(?:hsforms|hubspot)[^>]*>[\s\S]*?</iframe>#iu', '', $html ) ?? $html;
	$html = nvx_valoracion_remove_divs_by_class( $html, 'hs-form-frame' );
	$html = nvx_valoracion_remove_divs_by_class( $html, 'hbspt-form' );

	$canonical = $first_opening . nvx_valoracion_native_hubspot_mount_markup() . '</div>';

	return substr( $html, 0, $first_offset ) . $canonical . substr( $html, $first_offset );
}

add_action(
	'template_redirect',
	static function (): void {
		if ( nvx_valoracion_native_hubspot_is_target_page() ) {
			ob_start( 'nvx_valoracion_native_hubspot_enforce_single_mount' );
		}
	},
	1
);

add_action(
	'wp_footer',
	static function (): void {
		if ( nvx_valoracion_native_hubspot_is_target_page() ) {
			echo '<script>window.nuvanxValoracionForm=true;</script>' . "\n";
		}
	},
	20
);
