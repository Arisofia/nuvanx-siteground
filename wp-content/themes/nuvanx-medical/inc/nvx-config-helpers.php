<?php
/**
 * Configuration helpers for externalized values.
 *
 * Loads and provides access to configuration data from JSON files for values
 * that are not part of the canonical clinic business configuration.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get configuration value from JSON config file.
 *
 * @param string $key     Configuration key in dot notation.
 * @param mixed  $default Default value if key not found.
 * @return mixed Configuration value or default.
 */
function nvx_config_get( string $key, $default = '' ) {
	static $config = null;

	if ( null === $config ) {
		$config_file = __DIR__ . '/data/config.json';
		if ( is_readable( $config_file ) ) {
			$config = json_decode( file_get_contents( $config_file ), true );
			if ( json_last_error() !== JSON_ERROR_NONE ) {
				$config = array();
			}
		} else {
			$config = array();
		}
	}

	$keys  = explode( '.', $key );
	$value = $config;

	foreach ( $keys as $k ) {
		if ( ! is_array( $value ) || ! array_key_exists( $k, $value ) ) {
			return $default;
		}
		$value = $value[ $k ];
	}

	return $value;
}

/**
 * Get WhatsApp number for a specific clinic or the primary clinic.
 *
 * Clinic phone data is owned by nvx_get_clinics_config(); this helper only
 * normalizes that canonical value for wa.me links. Unknown clinic keys retain
 * the historical fallback to the primary Chamberí contact.
 *
 * @param string $clinic Clinic identifier ('primary', 'chamberi', 'goya').
 * @return string WhatsApp number in international digits-only format.
 */
function nvx_whatsapp_number( string $clinic = 'primary' ): string {
	if ( ! function_exists( 'nvx_get_clinics_config' ) ) {
		return '';
	}

	$primary_key = 'chamberi';
	$key         = 'primary' === $clinic ? $primary_key : $clinic;
	$clinics     = nvx_get_clinics_config();

	if ( ! isset( $clinics[ $key ]['phone_href'] ) ) {
		$key = $primary_key;
	}

	$phone = isset( $clinics[ $key ]['phone_href'] ) ? (string) $clinics[ $key ]['phone_href'] : '';
	return preg_replace( '/\D+/', '', $phone ) ?? '';
}

/**
 * Get full WhatsApp URL for specific clinic.
 *
 * @param string $clinic Clinic identifier ('primary', 'chamberi', 'goya').
 * @return string Full WhatsApp URL (https://wa.me/NUMBER).
 */
function nvx_whatsapp_url( string $clinic = 'primary' ): string {
	$number = nvx_whatsapp_number( $clinic );
	return $number ? 'https://wa.me/' . $number : '';
}

/**
 * Get medical colegiado number by doctor ID.
 *
 * @param string $doctor_id Doctor identifier ('director', 'ivon', 'fabio').
 * @return string Colegiado number or empty string if not found.
 */
function nvx_medical_colegiado( string $doctor_id ): string {
	$staff = nvx_config_get( 'medical_staff.directors', array() );
	foreach ( $staff as $doctor ) {
		if ( isset( $doctor['id'] ) && $doctor['id'] === $doctor_id ) {
			return $doctor['colegiado'] ?? '';
		}
	}
	return '';
}

/**
 * Get doctor name by doctor ID.
 *
 * @param string $doctor_id Doctor identifier ('director', 'ivon', 'fabio').
 * @return string Doctor name or empty string if not found.
 */
function nvx_medical_doctor_name( string $doctor_id ): string {
	$staff = nvx_config_get( 'medical_staff.directors', array() );
	foreach ( $staff as $doctor ) {
		if ( isset( $doctor['id'] ) && $doctor['id'] === $doctor_id ) {
			return $doctor['name'] ?? '';
		}
	}
	return '';
}