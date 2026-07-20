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

	// Defensive: finder is expected to return {index,id}, but tolerate null/odd shapes.
	if ( ! is_array( $organization ) ) {
		$organization = array();
	}
	$org_id = ( isset( $organization['id'] ) && is_string( $organization['id'] ) && '' !== $organization['id'] )
		? $organization['id']
		: home_url( '/#/schema/organization/nuvanx' );
	$org_index = array_key_exists( 'index', $organization ) ? $organization['index'] : null;
	if ( null !== $org_index && ! is_int( $org_index ) && ! ( is_string( $org_index ) && ctype_digit( (string) $org_index ) ) ) {
		$org_index = null;
	}
	if ( is_string( $org_index ) ) {
		$org_index = (int) $org_index;
	}

	if ( null === $org_index ) {
		$graph[] = array(
			'@type' => array( 'Organization', 'MedicalOrganization' ),
			'@id'   => $org_id,
			'name'  => 'NUVANX Medicina Estética Láser',
			'url'   => home_url( '/' ),
		);
		$org_index = array_key_last( $graph );
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
		$clinic['parentOrganization'] = array( '@id' => $org_id );
		$graph[]                      = $clinic;
	}

	if ( null !== $org_index && isset( $graph[ $org_index ] ) && is_array( $graph[ $org_index ] ) ) {
		if ( function_exists( 'nvx_schema_add_type' ) ) {
			$graph[ $org_index ]['@type'] = nvx_schema_add_type( $graph[ $org_index ]['@type'] ?? 'Organization', 'MedicalOrganization' );
		}

		$existing_refs = isset( $graph[ $org_index ]['subOrganization'] )
			? (array) $graph[ $org_index ]['subOrganization']
			: array();
		$merged_refs   = array();
		foreach ( array_merge( $existing_refs, $clinic_refs ) as $reference ) {
			if ( is_array( $reference ) && ! empty( $reference['@id'] ) ) {
				$merged_refs[ (string) $reference['@id'] ] = array( '@id' => (string) $reference['@id'] );
			}
		}
		$graph[ $org_index ]['subOrganization'] = array_values( $merged_refs );
	}

	return $graph;
}
add_filter( 'wpseo_schema_graph', 'nvx_contacto_audit_schema_graph', 30, 2 );

/**
 * Remove internal SEO jargon and expose verified clinic hours in visible copy.
 *
 * Prefer short stable anchors (or clinic-block markup) over full-sentence
 * str_replace so minor punctuation/dash edits in CMS copy do not break the fix.
 */
function nvx_contacto_audit_visible_copy( string $content ): string {
	if ( is_admin() || ! nvx_contacto_audit_is_contact_page() ) {
		return $content;
	}

	// Short stable phrase anchors (not full-sentence, dash-sensitive blobs).
	$literal = array(
		'Contacto privado'                           => 'Clínicas NUVANX',
		'Contacto, sedes y consulta médica'          => 'Clínicas NUVANX en Madrid — Chamberí y Salamanca–Goya',
		'Agenda tu valoración médica'                => 'Clínicas NUVANX en Madrid — Chamberí y Salamanca–Goya',
		'Contacto directo y ubicaciones autorizadas por Sanidad' => 'Datos de contacto y sedes autorizadas',
	);
	$content = str_replace( array_keys( $literal ), array_values( $literal ), $content );

	// Lead-in anchored paragraph rewrites: match from a stable stem to sentence end(s).
	$patterns = array(
		// Hero/body intro that previously named "directorio NAP" / internal SEO wording.
		'/Contacta con NUVANX para solicitar una valoración médica personalizada[^.<]{0,220}\.\s*El equipo revisará[^.<]{0,160}\./u'
			=> 'Consulta médica presencial en Chamberí y Salamanca–Goya. El equipo te orientará hacia la sede y el médico disponible según tu caso.',
		'/Para diagnóstico y plan de tratamiento[^.<]{0,200}\.\s*Esta página es el directorio NAP[^.<]{0,120}\./u'
			=> 'Contacta por teléfono o WhatsApp para solicitar una valoración médica en Chamberí o Salamanca–Goya. Una persona del equipo del Dr. Rivera te contactará en menos de 24 horas para confirmar tu valoración médica.',
		// Clinic cards: inject verified hours via the existing days paragraph class.
		'/<p\b[^>]*\bclass=["\'][^"\']*\bnvx-contact-clinic__days\b[^"\']*["\'][^>]*>\s*<strong>\s*Consulta médica directa:\s*<\/strong>\s*Martes y jueves\s*<\/p>/iu'
			=> '<p class="nvx-contact-clinic__hours"><strong>Horario de clínica:</strong> Lunes a viernes, 12:00–20:00; sábados, 10:00–18:00</p><p class="nvx-contact-clinic__days"><strong>Consulta médica:</strong> Martes y jueves</p>',
		'/<p\b[^>]*\bclass=["\'][^"\']*\bnvx-contact-clinic__days\b[^"\']*["\'][^>]*>\s*<strong>\s*Consulta médica directa:\s*<\/strong>\s*Miércoles\s*<\/p>/iu'
			=> '<p class="nvx-contact-clinic__hours"><strong>Horario de clínica:</strong> Lunes a viernes, 11:00–20:00</p><p class="nvx-contact-clinic__days"><strong>Consulta médica:</strong> Miércoles</p>',
	);

	foreach ( $patterns as $pattern => $replacement ) {
		$updated = preg_replace( $pattern, $replacement, $content );
		if ( is_string( $updated ) ) {
			$content = $updated;
		}
	}

	return $content;
}
add_filter( 'the_content', 'nvx_contacto_audit_visible_copy', 18 );
