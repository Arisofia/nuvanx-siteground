<?php
/**
 * Validated /contacto/ SEO, local schema and patient-facing copy fixes.
 *
 * This module closes only findings confirmed against the public production
 * document. It deliberately reuses the canonical clinic registry and omits
 * unverified coordinates.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether the current request is the public contact page.
 */
function nvx_contacto_audit_is_contact_page(): bool {
	if ( function_exists( 'nvx_is_contacto_page_request' ) ) {
		return nvx_is_contacto_page_request();
	}

	if ( ! is_singular( 'page' ) || is_front_page() ) {
		return false;
	}

	$path = function_exists( 'nvx_schema_current_path' )
		? nvx_schema_current_path( (int) get_queried_object_id() )
		: ( isset( $_SERVER['REQUEST_URI'] ) ? (string) strtok( (string) $_SERVER['REQUEST_URI'], '?' ) : '' );

	return '/contacto/' === '/' . trim( (string) $path, '/' ) . '/';
}

/**
 * Canonical social preview image for /contacto/.
 */
function nvx_contacto_audit_social_image( $image ): string {
	if ( ! nvx_contacto_audit_is_contact_page() ) {
		return (string) $image;
	}

	return home_url( '/wp-content/uploads/2026/07/consulta-medica-personalizada-nuvanx-madrid.webp' );
}
add_filter( 'wpseo_opengraph_image', 'nvx_contacto_audit_social_image', 100 );
add_filter( 'wpseo_twitter_image', 'nvx_contacto_audit_social_image', 100 );

/**
 * Keep the contact SERP and social title aligned with the two real branches.
 */
function nvx_contacto_audit_title( $title ): string {
	if ( ! nvx_contacto_audit_is_contact_page() ) {
		return (string) $title;
	}

	return 'Contacto NUVANX Madrid | Chamberí y Salamanca–Goya';
}
add_filter( 'wpseo_title', 'nvx_contacto_audit_title', 110 );
add_filter( 'wpseo_opengraph_title', 'nvx_contacto_audit_title', 110 );
add_filter( 'wpseo_twitter_title', 'nvx_contacto_audit_title', 110 );

/**
 * Patient-facing contact description shared by SERP and social cards.
 */
function nvx_contacto_audit_description( $description ): string {
	if ( ! nvx_contacto_audit_is_contact_page() ) {
		return (string) $description;
	}

	return 'Clínicas NUVANX en Chamberí y Salamanca–Goya: teléfonos, horarios, registros sanitarios, mapas y valoración médica en Madrid.';
}
add_filter( 'wpseo_metadesc', 'nvx_contacto_audit_description', 110 );
add_filter( 'wpseo_opengraph_desc', 'nvx_contacto_audit_description', 110 );
add_filter( 'wpseo_twitter_description', 'nvx_contacto_audit_description', 110 );

/**
 * Add both canonical MedicalClinic branches to the /contacto/ Yoast graph.
 *
 * @param array $graph Yoast schema graph.
 * @return array
 */
function nvx_contacto_audit_schema_graph( $graph, $context ) {
	unset( $context );

	if (
		! nvx_contacto_audit_is_contact_page()
		|| ! is_array( $graph )
		|| ! function_exists( 'nvx_schema_clinics' )
		|| ! function_exists( 'nvx_schema_find_organization' )
	) {
		return $graph;
	}

	$clinics      = nvx_schema_clinics();
	$organization = nvx_schema_find_organization( $graph );

	if ( null === $organization['index'] ) {
		$graph[] = array(
			'@type' => array( 'Organization', 'MedicalOrganization' ),
			'@id'   => $organization['id'],
			'name'  => 'NUVANX Medicina Estética Láser',
			'url'   => home_url( '/' ),
		);
		$organization['index'] = array_key_last( $graph );
	}

	$existing_ids = array();
	foreach ( $graph as $piece ) {
		if ( is_array( $piece ) && ! empty( $piece['@id'] ) ) {
			$existing_ids[] = (string) $piece['@id'];
		}
	}

	$clinic_refs = array();
	foreach ( array( 'chamberi', 'goya' ) as $key ) {
		if ( empty( $clinics[ $key ]['@id'] ) ) {
			continue;
		}

		$clinic_refs[] = array( '@id' => $clinics[ $key ]['@id'] );
		if ( in_array( $clinics[ $key ]['@id'], $existing_ids, true ) ) {
			continue;
		}

		$clinic                       = $clinics[ $key ];
		$clinic['parentOrganization'] = array( '@id' => $organization['id'] );
		$graph[]                      = $clinic;
	}

	$index = $organization['index'];
	if ( null !== $index && isset( $graph[ $index ] ) ) {
		if ( function_exists( 'nvx_schema_add_type' ) ) {
			$graph[ $index ]['@type'] = nvx_schema_add_type( $graph[ $index ]['@type'] ?? 'Organization', 'MedicalOrganization' );
		}

		$existing_refs = isset( $graph[ $index ]['subOrganization'] )
			? (array) $graph[ $index ]['subOrganization']
			: array();
		$merged_refs   = array();
		foreach ( array_merge( $existing_refs, $clinic_refs ) as $reference ) {
			if ( is_array( $reference ) && ! empty( $reference['@id'] ) ) {
				$merged_refs[ (string) $reference['@id'] ] = array( '@id' => (string) $reference['@id'] );
			}
		}
		$graph[ $index ]['subOrganization'] = array_values( $merged_refs );
	}

	return $graph;
}
add_filter( 'wpseo_schema_graph', 'nvx_contacto_audit_schema_graph', 30, 2 );

/**
 * Remove internal SEO jargon and expose verified clinic hours in visible copy.
 */
function nvx_contacto_audit_visible_copy( string $content ): string {
	if ( is_admin() || ! nvx_contacto_audit_is_contact_page() ) {
		return $content;
	}

	$replacements = array(
		'Contacto privado · Madrid' => 'Clínicas NUVANX · Madrid',
		'Contacto, sedes y consulta médica' => 'Clínicas NUVANX en Madrid — Chamberí y Salamanca–Goya',
		'Agenda tu valoración médica' => 'Clínicas NUVANX en Madrid — Chamberí y Salamanca–Goya',
		'Contacta con NUVANX para solicitar una valoración médica personalizada en Chamberí o Goya · Barrio Salamanca. El equipo revisará tu interés y te orientará hacia la sede y el profesional adecuados.' => 'Consulta médica presencial en Chamberí y Salamanca–Goya. El equipo te orientará hacia la sede y el médico disponible según tu caso.',
		'Contacto directo y ubicaciones autorizadas por Sanidad' => 'Datos de contacto y sedes autorizadas',
		'Para diagnóstico y plan de tratamiento, reserve la valoración médica gratuita (15–30 min) en Chamberí o Goya. Esta página es el directorio NAP de sedes y teléfonos.' => 'Contacta por teléfono o WhatsApp para solicitar una valoración médica gratuita de 15–30 minutos en Chamberí o Salamanca–Goya. La indicación y el presupuesto se confirman tras la valoración.',
		'<p class="nvx-contact-clinic__days"><strong>Consulta médica directa:</strong> Martes y jueves</p>' => '<p class="nvx-contact-clinic__hours"><strong>Horario de clínica:</strong> Lunes a viernes, 12:00–20:00; sábados, 10:00–18:00</p><p class="nvx-contact-clinic__days"><strong>Consulta médica:</strong> Martes y jueves</p>',
		'<p class="nvx-contact-clinic__days"><strong>Consulta médica directa:</strong> Miércoles</p>' => '<p class="nvx-contact-clinic__hours"><strong>Horario de clínica:</strong> Lunes a viernes, 11:00–20:00</p><p class="nvx-contact-clinic__days"><strong>Consulta médica:</strong> Miércoles</p>',
	);

	return str_replace( array_keys( $replacements ), array_values( $replacements ), $content );
}
add_filter( 'the_content', 'nvx_contacto_audit_visible_copy', 18 );
