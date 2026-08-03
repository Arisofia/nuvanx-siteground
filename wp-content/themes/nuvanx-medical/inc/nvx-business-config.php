<?php
/**
 * NUVANX Business Configuration
 *
 * Central source of truth for clinics data, PII (phones, emails, registration numbers).
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Returns the canonical business configuration.
 *
 * @return array<string, array{name:string,reg:string,address:string,postal_code:string,locality:string,phone:string,phone_href:string,days:string}>
 */
function nvx_get_clinics_config(): array {
	return array(
		'chamberi' => array(
			'name'          => 'Centro Clínico NUVANX Chamberí',
			'reg'           => 'CS20144',
			'address'       => 'Calle de Fernández de la Hoz, 4, Bajo Derecha',
			'postal_code'   => '28010',
			'locality'      => 'Madrid',
			'phone'         => '669 319 836',
			'phone_href'    => '+34669319836',
			'whatsapp_href' => 'https://wa.me/34669319836',
			'hours'         => 'lunes a viernes, 12:00–20:00; sábados, 10:00–18:00',
			'days'          => 'Martes y jueves',
		),
		'goya' => array(
			'name'          => 'Centro Clínico NUVANX Salamanca / Goya',
			'reg'           => 'CS20073',
			'address'       => 'Calle de Fernán González, 26',
			'postal_code'   => '28009',
			'locality'      => 'Madrid',
			'phone'         => '647 505 107',
			'phone_href'    => '+34647505107',
			'whatsapp_href' => 'https://wa.me/34647505107',
			'hours'         => 'lunes a viernes, 11:00–20:00',
			'days'          => 'Miércoles',
		),
	);
}
