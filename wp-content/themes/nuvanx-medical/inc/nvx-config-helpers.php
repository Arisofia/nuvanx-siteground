<?php
/**
 * Configuration helpers for externalized values.
 *
 * Loads and provides access to configuration data from JSON files,
 * centralizing values like WhatsApp numbers and medical staff credentials.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get configuration value from JSON config file.
 *
 * @param string $key     Configuration key in dot notation (e.g., 'contact.whatsapp.primary')
 * @param mixed  $default Default value if key not found
 * @return mixed Configuration value or default
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
 * Get WhatsApp number for specific clinic or primary.
 *
 * @param string $clinic Clinic identifier ('primary', 'chamberi', 'goya')
 * @return string WhatsApp number
 */
function nvx_whatsapp_number( string $clinic = 'primary' ): string {
	$number = nvx_config_get( 'contact.whatsapp.' . $clinic, '' );
	if ( '' === $number && 'primary' !== $clinic ) {
		$number = nvx_config_get( 'contact.whatsapp.primary', '' );
	}
	return $number;
}

/**
 * Get full WhatsApp URL for specific clinic.
 *
 * @param string $clinic Clinic identifier ('primary', 'chamberi', 'goya')
 * @return string Full WhatsApp URL (https://wa.me/NUMBER)
 */
function nvx_whatsapp_url( string $clinic = 'primary' ): string {
	$number = nvx_whatsapp_number( $clinic );
	return $number ? 'https://wa.me/' . $number : '';
}

/**
 * Get medical colegiado number by doctor ID.
 *
 * @param string $doctor_id Doctor identifier ('director', 'ivon', 'fabio')
 * @return string Colegiado number or empty string if not found
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
 * @param string $doctor_id Doctor identifier ('director', 'ivon', 'fabio')
 * @return string Doctor name or empty string if not found
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
