<?php
/**
 * MedicalClinic schema graph integration for NUVANX locations.
 *
 * Emits canonical MedicalClinic structured data for Chamberí (CS20144) and
 * Salamanca-Goya (CS20073) based on nvx_get_clinics_config().
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Inject MedicalClinic schema graph nodes for clinic pages.
 *
 * @param array<int, mixed> $data Yoast Schema graph array.
 * @return array<int, mixed>
 */
function nvx_add_medical_clinic_schema( $data ) {
	if ( ! is_array( $data ) || is_admin() ) {
		return $data;
	}

	if ( ! function_exists( 'nvx_get_clinics_config' ) ) {
		return $data;
	}

	$clinics_config = nvx_get_clinics_config();

	// Determine active clinic slug(s) for the current page
	$active_keys = array();
	if ( is_page( 'medicina-estetica-chamberi' ) || false !== strpos( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), 'chamberi' ) ) {
		$active_keys[] = 'chamberi';
	} elseif ( is_page( 'medicina-estetica-goya-barrio-salamanca' ) || false !== strpos( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), 'goya' ) ) {
		$active_keys[] = 'goya';
	} elseif ( is_page( array( 'clinicas', 'clinicas-de-medicina-estetica-nuvanx', 'madrid' ) ) ) {
		$active_keys = array( 'chamberi', 'goya' );
	}

	if ( empty( $active_keys ) ) {
		return $data;
	}

	foreach ( $active_keys as $key ) {
		if ( empty( $clinics_config[ $key ] ) ) {
			continue;
		}

		$config   = $clinics_config[ $key ];
		$page_url = 'chamberi' === $key
			? home_url( '/clinicas-de-medicina-estetica-nuvanx/medicina-estetica-chamberi/' )
			: home_url( '/clinicas-de-medicina-estetica-nuvanx/medicina-estetica-goya-barrio-salamanca/' );

		$schema = array(
			'@context'  => 'https://schema.org',
			'@type'     => 'MedicalClinic',
			'@id'       => esc_url( $page_url ) . '#medicalclinic',
			'name'      => $config['name'] ?? 'NUVANX Medicina Estética',
			'url'       => esc_url( $page_url ),
			'telephone' => $config['phone_href'] ?? '',
			'address'   => array(
				'@type'           => 'PostalAddress',
				'streetAddress'   => $config['address'] ?? '',
				'addressLocality' => $config['locality'] ?? 'Madrid',
				'addressRegion'   => 'Madrid',
				'postalCode'      => $config['postal_code'] ?? '28010',
				'addressCountry'  => 'ES',
			),
			'geo'       => array(
				'@type'     => 'GeoCoordinates',
				'latitude'  => (float) ( $config['latitude'] ?? 40.431204 ),
				'longitude' => (float) ( $config['longitude'] ?? -3.693425 ),
			),
			'openingHoursSpecification' => array(
				array(
					'@type'     => 'OpeningHoursSpecification',
					'dayOfWeek' => array( 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday' ),
					'opens'     => '10:00',
					'closes'    => '20:00',
				),
			),
		);

		$data[] = $schema;
	}

	return $data;
}
add_filter( 'wpseo_schema_graph', 'nvx_add_medical_clinic_schema', 20, 1 );
