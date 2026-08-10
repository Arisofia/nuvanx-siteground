<?php
/**
 * NUVANX Business Configuration
 *
 * Central source of truth for clinics data, phones, registration numbers and locations.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides the canonical configuration for each clinic.
 *
 * @return array<string, array{name: string, reg: string, address: string, postal_code: string, locality: string, phone: string, phone_href: string, whatsapp_href: string, hours: string, days: string, latitude: float, longitude: float}>
 */
function nvx_get_clinics_config(): array {
	$clinics = array(
		'chamberi' => array(
			'name'        => 'Centro Clínico NUVANX Chamberí',
			'reg'         => 'CS20144',
			'address'     => 'Calle de Fernández de la Hoz, 4, Bajo Derecha',
			'postal_code' => '28010',
			'locality'    => 'Madrid',
			'phone'       => '669 319 836',
			'phone_href'  => '+34669319836',
			'hours'       => 'lunes a viernes, 12:00–20:00; sábados, 10:00–18:00',
			'days'        => 'Martes y jueves',
			'latitude'    => 40.431204,
			'longitude'   => -3.693425,
		),
		'goya'     => array(
			'name'        => 'Centro Clínico NUVANX Salamanca / Goya',
			'reg'         => 'CS20073',
			'address'     => 'Calle de Fernán González, 26',
			'postal_code' => '28009',
			'locality'    => 'Madrid',
			'phone'       => '647 505 107',
			'phone_href'  => '+34647505107',
			'hours'       => 'lunes a viernes, 11:00–20:00',
			'days'        => 'Miércoles',
			'latitude'    => 40.423912,
			'longitude'   => -3.675648,
		),
	);

	foreach ( $clinics as &$clinic ) {
		$clinic['whatsapp_href'] = nvx_whatsapp_url_from_phone( (string) $clinic['phone_href'] );
	}
	unset( $clinic );

	return $clinics;
}
