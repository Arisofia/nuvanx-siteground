<?php
/**
 * Final Schema.org semantic governance for the Yoast graph.
 *
 * Runs after all ordinary graph producers and before the FAQ gate / @id dedupe.
 * Its purpose is deliberately narrow: remove or normalize properties whose
 * rendered domain/range is invalid, including legacy values that may originate
 * from WordPress/Yoast metadata rather than versioned theme source.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @return string[] */
function nvx_schema_semantic_allowed_procedure_types(): array {
	return array(
		'https://schema.org/PercutaneousProcedure',
		'https://schema.org/NoninvasiveProcedure',
	);
}

/** @return string[] */
function nvx_schema_semantic_allowed_medical_specialties(): array {
	$members = array(
		'Anesthesia',
		'Cardiovascular',
		'CommunityHealth',
		'Dentistry',
		'Dermatology',
		'DietNutrition',
		'Emergency',
		'Endocrine',
		'Gastroenterologic',
		'Genetic',
		'Geriatric',
		'Gynecologic',
		'Hematologic',
		'Infectious',
		'LaboratoryScience',
		'Midwifery',
		'Musculoskeletal',
		'Neurologic',
		'Nursing',
		'Obstetric',
		'Oncologic',
		'Optometric',
		'Otolaryngologic',
		'Pathology',
		'Pediatric',
		'PharmacySpecialty',
		'Physiotherapy',
		'PlasticSurgery',
		'Podiatric',
		'PrimaryCare',
		'Psychiatric',
		'PublicHealth',
		'Pulmonary',
		'Radiography',
		'Renal',
		'RespiratoryTherapy',
		'Rheumatologic',
		'SpeechPathology',
		'Surgical',
		'Toxicologic',
		'Urologic',
	);

	return array_map(
		static function ( string $member ): string {
			return 'https://schema.org/' . $member;
		},
		$members
	);
}

/** @param mixed $value @type value. @return string[] */
function nvx_schema_semantic_types( $value ): array {
	$types = is_array( $value ) ? $value : array( $value );
	return array_values(
		array_filter(
			array_map( 'strval', $types ),
			static function ( string $type ): bool {
				return '' !== $type;
			}
		)
	);
}

/** Whether this explicit type collection represents a WebPage or subtype. */
function nvx_schema_semantic_is_webpage( array $types ): bool {
	foreach ( $types as $type ) {
		if ( 'WebPage' === $type || str_ends_with( $type, 'Page' ) ) {
			return true;
		}
	}
	return false;
}

/** Whether this explicit type collection represents an Event or subtype. */
function nvx_schema_semantic_is_event( array $types ): bool {
	foreach ( $types as $type ) {
		if ( 'Event' === $type || str_ends_with( $type, 'Event' ) ) {
			return true;
		}
	}
	return false;
}

/** Whether this explicit type collection is a medical procedure or subtype. */
function nvx_schema_semantic_is_medical_procedure( array $types ): bool {
	$procedure_types = array(
		'MedicalProcedure',
		'DiagnosticProcedure',
		'PalliativeProcedure',
		'PhysicalExam',
		'PhysicalTherapy',
		'PsychologicalTreatment',
		'RadiationTherapy',
		'SurgicalProcedure',
		'TherapeuticProcedure',
	);

	return (bool) array_intersect( $types, $procedure_types );
}

/** Whether a node can carry descriptive expertise through knowsAbout. */
function nvx_schema_semantic_supports_knows_about( array $types ): bool {
	return (bool) array_intersect(
		$types,
		array( 'Organization', 'MedicalOrganization', 'MedicalClinic', 'LocalBusiness', 'Person', 'Physician' )
	);
}

/** @param mixed $value Schema value. */
function nvx_schema_semantic_value_id( $value ): string {
	if ( is_string( $value ) ) {
		return $value;
	}
	if ( is_array( $value ) && isset( $value['@id'] ) && is_string( $value['@id'] ) ) {
		return $value['@id'];
	}
	return '';
}

/** @param mixed $value Schema scalar-or-list value. @return array<int,mixed> */
function nvx_schema_semantic_value_list( $value ): array {
	if ( ! is_array( $value ) ) {
		return array( $value );
	}

	$keys = array_keys( $value );
	$is_list = array() === $value || $keys === range( 0, count( $value ) - 1 );
	return $is_list ? $value : array( $value );
}

/** Append a descriptive value to knowsAbout without duplicating it. */
function nvx_schema_semantic_append_knows_about( array &$node, string $value ): void {
	$value = trim( $value );
	if ( '' === $value ) {
		return;
	}

	$existing = isset( $node['knowsAbout'] ) ? nvx_schema_semantic_value_list( $node['knowsAbout'] ) : array();
	foreach ( $existing as $item ) {
		if ( is_string( $item ) && $item === $value ) {
			return;
		}
	}
	$existing[]         = $value;
	$node['knowsAbout'] = array_values( $existing );
}

/** Normalize one associative Schema.org object recursively. */
function nvx_schema_semantic_sanitize_object( array $node ): array {
	$types = nvx_schema_semantic_types( $node['@type'] ?? array() );

	if ( array_key_exists( 'reviewedBy', $node ) && ! nvx_schema_semantic_is_webpage( $types ) ) {
		unset( $node['reviewedBy'] );
	}

	if ( array_key_exists( 'performer', $node ) && ! nvx_schema_semantic_is_event( $types ) ) {
		unset( $node['performer'] );
	}

	if ( array_key_exists( 'priceRange', $node ) && ! array_intersect( $types, array( 'LocalBusiness', 'MedicalClinic' ) ) ) {
		unset( $node['priceRange'] );
	}

	if ( array_key_exists( 'procedureType', $node ) ) {
		if ( ! nvx_schema_semantic_is_medical_procedure( $types ) ) {
			unset( $node['procedureType'] );
		} else {
			$allowed = nvx_schema_semantic_allowed_procedure_types();
			$valid   = array();
			foreach ( nvx_schema_semantic_value_list( $node['procedureType'] ) as $candidate ) {
				$id = nvx_schema_semantic_value_id( $candidate );
				if ( in_array( $id, $allowed, true ) ) {
					$valid[] = $candidate;
				}
			}
			if ( empty( $valid ) ) {
				unset( $node['procedureType'] );
			} else {
				$node['procedureType'] = 1 === count( $valid ) ? $valid[0] : array_values( $valid );
			}
		}
	}

	if ( array_key_exists( 'medicalSpecialty', $node ) ) {
		$allowed = nvx_schema_semantic_allowed_medical_specialties();
		$valid   = array();
		foreach ( nvx_schema_semantic_value_list( $node['medicalSpecialty'] ) as $candidate ) {
			$id = nvx_schema_semantic_value_id( $candidate );
			if ( in_array( $id, $allowed, true ) ) {
				$valid[] = $candidate;
				continue;
			}
			if ( is_string( $candidate ) && nvx_schema_semantic_supports_knows_about( $types ) ) {
				nvx_schema_semantic_append_knows_about( $node, $candidate );
			}
		}
		if ( empty( $valid ) ) {
			unset( $node['medicalSpecialty'] );
		} else {
			$node['medicalSpecialty'] = 1 === count( $valid ) ? $valid[0] : array_values( $valid );
		}
	}

	if ( isset( $node['recognizingAuthority'] ) ) {
		$serialized = wp_json_encode( $node['recognizingAuthority'] );
		if ( is_string( $serialized ) && preg_match( '/SEME|Sociedad Española de Medicina Estética|seme\.org/i', $serialized ) ) {
			unset( $node['recognizingAuthority'] );
		}
	}

	foreach ( $node as $key => $value ) {
		if ( is_array( $value ) ) {
			$node[ $key ] = nvx_schema_semantic_sanitize_value( $value );
		}
	}

	return $node;
}

/** Recursively sanitize associative objects and list values. */
function nvx_schema_semantic_sanitize_value( array $value ): array {
	$keys    = array_keys( $value );
	$is_list = array() === $value || $keys === range( 0, count( $value ) - 1 );

	if ( ! $is_list ) {
		return nvx_schema_semantic_sanitize_object( $value );
	}

	$result = array();
	foreach ( $value as $item ) {
		$result[] = is_array( $item ) ? nvx_schema_semantic_sanitize_value( $item ) : $item;
	}
	return $result;
}

/**
 * Final semantic pass over the rendered Yoast graph.
 *
 * @param array $graph Yoast Schema graph.
 * @return array
 */
function nvx_schema_semantic_normalize_graph( $graph ) {
	if ( ! is_array( $graph ) || is_admin() || is_feed() ) {
		return $graph;
	}

	$result = array();
	foreach ( $graph as $node ) {
		$result[] = is_array( $node ) ? nvx_schema_semantic_sanitize_value( $node ) : $node;
	}
	return array_values( $result );
}
add_filter( 'wpseo_schema_graph', 'nvx_schema_semantic_normalize_graph', PHP_INT_MAX - 2, 1 );
