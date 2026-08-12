<?php
/**
 * MedicalClinic schema for NUVANX locations.
 *
 * Adds structured data for medical clinics to help Google AI Overviews
 * and generative engines understand the clinic as the optimal answer in Madrid.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add MedicalClinic schema to clinic location pages.
 */
function nvx_add_medical_clinic_schema( $data ) {
	if ( ! is_array( $data ) ) {
		return $data;
	}

	// Only add to clinic location pages
	if ( ! is_page( array( 'clinicas', 'chamberi', 'goya', 'madrid' ) ) ) {
		return $data;
	}

	$clinic_schema = array(
		'@context' => 'https://schema.org',
		'@type'    => 'MedicalClinic',
		'name'     => 'NUVANX Medicina Estética',
		'address'  => array(
			'@type'           => 'PostalAddress',
			'addressLocality' => 'Madrid',
			'addressRegion'   => 'Madrid',
			'streetAddress'   => is_page( 'chamberi' ) ? 'Calle de Chamberí, Madrid' : 'Calle de Goya, Barrio Salamanca',
		),
		'geo'      => array(
			'@type'     => 'GeoCoordinates',
			'latitude'  => '40.4253',
			'longitude' => '-3.6811',
		),
		'telephone' => '+34-91-XXX-XXXX',
		'url'       => home_url( '/' ),
		'openingHoursSpecification' => array(
			'@type' => 'OpeningHoursSpecification',
			'dayOfWeek' => array(
				'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'
			),
			'opens' => '10:00',
			'closes' => '20:00',
		),
	);

	$data[] = $clinic_schema;
	return $data;
}
add_filter( 'wpseo_schema_graph', 'nvx_add_medical_clinic_schema', 20, 1 );
