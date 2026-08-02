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

	if ( ! is_page() || is_front_page() ) {
		return false;
	}

	if ( function_exists( 'nvx_schema_current_path' ) ) {
		$path = nvx_schema_current_path( (int) get_queried_object_id() );
	} else {
		$path = isset( $_SERVER['REQUEST_URI'] ) ? (string) strtok( (string) $_SERVER['REQUEST_URI'], '?' ) : '';
	}

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
 * Social title/description share the canonical SERP strings from
 * nvx-contacto-valoracion-page.php (single source of truth — no second override).
 */
function nvx_contacto_audit_title( $title ): string {
	if ( ! nvx_contacto_audit_is_contact_page() ) {
		return (string) $title;
	}

	// Prefer the same string used by wpseo_title when the dedicated filter exists.
	if ( function_exists( 'nvx_filter_contacto_document_title' ) ) {
		return (string) nvx_filter_contacto_document_title( $title );
	}

	return 'Contacto NUVANX Madrid | Chamberí y Goya · Teléfonos y Direcciones';
}
add_filter( 'wpseo_opengraph_title', 'nvx_contacto_audit_title', 110 );
add_filter( 'wpseo_twitter_title', 'nvx_contacto_audit_title', 110 );

/**
 * Patient-facing contact description shared by social cards.
 */
function nvx_contacto_audit_description( $description ): string {
	if ( ! nvx_contacto_audit_is_contact_page() ) {
		return (string) $description;
	}

	if ( function_exists( 'nvx_filter_contacto_metadesc' ) ) {
		return (string) nvx_filter_contacto_metadesc( $description );
	}

	return 'Contacto NUVANX: Chamberí CS20144 (669 319 836) y Goya CS20073 (647 505 107). Valoración médica en /madrid/valoracion/.';
}
add_filter( 'wpseo_opengraph_desc', 'nvx_contacto_audit_description', 110 );
add_filter( 'wpseo_twitter_description', 'nvx_contacto_audit_description', 110 );

/**
 * Normalize organization finder payload to a usable index/id pair.
 *
 * @param mixed $organization Finder result.
 * @return array{id:string,index:int|null}
 */
function nvx_contacto_audit_normalize_organization( $organization ): array {
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

	return array(
		'id'    => $org_id,
		'index' => $org_index,
	);
}

/**
 * Ensure the graph has an Organization node; append a minimal one when missing.
 *
 * @param array<int,array<string,mixed>> $graph Yoast graph.
 * @return array{0:array<int,array<string,mixed>>,1:int|null,2:string}
 */
function nvx_contacto_audit_ensure_organization_node( array $graph, string $org_id, $org_index ): array {
	if ( null !== $org_index ) {
		return array( $graph, $org_index, $org_id );
	}

	$graph[] = array(
		'@type' => array( 'Organization', 'MedicalOrganization' ),
		'@id'   => $org_id,
		'name'  => 'NUVANX Medicina Estética Láser',
		'url'   => home_url( '/' ),
	);

	return array( $graph, array_key_last( $graph ), $org_id );
}

/**
 * Collect existing @id values from graph pieces.
 *
 * @param array<int,mixed> $graph Yoast graph.
 * @return array<int,string>
 */
function nvx_contacto_audit_graph_ids( array $graph ): array {
	$existing_ids = array();
	foreach ( $graph as $piece ) {
		if ( is_array( $piece ) && ! empty( $piece['@id'] ) ) {
			$existing_ids[] = (string) $piece['@id'];
		}
	}
	return $existing_ids;
}

/**
 * Append missing clinic nodes and return subOrganization refs.
 *
 * @param array<int,array<string,mixed>> $graph Yoast graph.
 * @param array<string,array<string,mixed>> $clinics Clinic map.
 * @param array<int,string> $existing_ids Existing @id values.
 * @return array{0:array<int,array<string,mixed>>,1:array<int,array{@id:string}>}
 */
function nvx_contacto_audit_append_clinic_nodes( array $graph, array $clinics, array $existing_ids, string $org_id ): array {
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

	return array( $graph, $clinic_refs );
}

/**
 * Merge clinic refs into the organization subOrganization property.
 *
 * @param array<int,array<string,mixed>> $graph Yoast graph.
 * @param array<int,array{@id:string}> $clinic_refs Clinic references.
 * @return array<int,array<string,mixed>>
 */
function nvx_contacto_audit_merge_org_clinic_refs( array $graph, $org_index, array $clinic_refs ): array {
	if ( null === $org_index || ! isset( $graph[ $org_index ] ) || ! is_array( $graph[ $org_index ] ) ) {
		return $graph;
	}

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

	return $graph;
}

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
	$organization = nvx_contacto_audit_normalize_organization( nvx_schema_find_organization( $graph ) );
	list( $graph, $org_index, $org_id ) = nvx_contacto_audit_ensure_organization_node(
		$graph,
		$organization['id'],
		$organization['index']
	);

	list( $graph, $clinic_refs ) = nvx_contacto_audit_append_clinic_nodes(
		$graph,
		$clinics,
		nvx_contacto_audit_graph_ids( $graph ),
		$org_id
	);

	return nvx_contacto_audit_merge_org_clinic_refs( $graph, $org_index, $clinic_refs );
}
add_filter( 'wpseo_schema_graph', 'nvx_contacto_audit_schema_graph', 30, 2 );

// Visible /contacto/ copy is owned by templates/template-contact.php (shell content).
// Do not regex-rewrite CMS leftovers here — edit the template (or empty post_content).
