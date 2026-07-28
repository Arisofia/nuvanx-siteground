<?php
/**
 * Anatomical zone pages (Phase 2).
 *
 * Clinical copy lives in inc/data/nvx-anatomical-zones.json. This file only
 * hydrates that data into the 13-point matrix shape and registers rendering.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/nvx-13-point-renderer.php';

if ( ! defined( 'NVX_KICKER_ROSTRO' ) ) {
	define( 'NVX_KICKER_ROSTRO', 'Soluciones Médicas: Rostro' );
}
if ( ! defined( 'NVX_KICKER_CUERPO' ) ) {
	define( 'NVX_KICKER_CUERPO', 'Soluciones Médicas: Cuerpo' );
}

/**
 * Build one FAQ pair for an anatomical page entry.
 *
 * @return array{q:string,a:string}
 */
function nvx_anatomical_faq( string $question, string $answer ): array {
	return array(
		'q' => $question,
		'a' => $answer,
	);
}

/**
 * Helper to construct a structured anatomical page entry.
 *
 * @param string                               $slug        Route slug.
 * @param string                               $seo_title   Document title.
 * @param string                               $description Meta description.
 * @param string                               $kicker      Eyebrow kicker.
 * @param string                               $h1          Main heading.
 * @param string                               $lead        Lead text.
 * @param string                               $diagnosis   Clinical diagnosis text.
 * @param string                               $mechanism   Therapeutic mechanism text.
 * @param string[]                             $indications List of indications.
 * @param string[]                             $precautions List of precautions.
 * @param string[]                             $process     List of process steps.
 * @param array<int, array{q:string,a:string}> $faqs        FAQ list.
 * @return array<string, mixed>
 */
function nvx_anatomical_entry(
	string $slug,
	string $seo_title,
	string $description,
	string $kicker,
	string $h1,
	string $lead,
	string $diagnosis,
	string $mechanism,
	array $indications,
	array $precautions,
	array $process,
	array $faqs
): array {
	return array(
		'slug'          => $slug,
		'seo_title'     => $seo_title,
		'description'   => $description,
		'kicker'        => $kicker,
		'h1'            => $h1,
		'lead'          => $lead,
		'diagnosis'     => $diagnosis,
		'mechanism'     => $mechanism,
		'indications'   => $indications,
		'precautions'   => $precautions,
		'process'       => $process,
		'faqs'          => $faqs,
		'review_status' => 'approved_for_publication',
	);
}

/**
 * Load raw anatomical zone specs from the versioned JSON catalogue.
 *
 * @return array<string, array<string, mixed>>
 */
function nvx_anatomical_zone_specs(): array {
	static $specs = null;
	if ( null !== $specs ) {
		return $specs;
	}

	$path = __DIR__ . '/data/nvx-anatomical-zones.json';
	if ( ! is_readable( $path ) ) {
		$specs = array();
		return $specs;
	}

	$raw = file_get_contents( $path );
	if ( false === $raw || '' === $raw ) {
		$specs = array();
		return $specs;
	}

	$decoded = json_decode( $raw, true );
	$specs   = is_array( $decoded ) ? $decoded : array();
	return $specs;
}

/**
 * Map a raw JSON zone spec into a renderable matrix entry.
 *
 * @param array<string, mixed> $spec
 * @return array<string, mixed>
 */
function nvx_anatomical_entry_from_spec( array $spec ): array {
	$kicker_key = (string) ( $spec['kicker'] ?? 'rostro' );
	$kicker     = ( 'cuerpo' === $kicker_key ) ? NVX_KICKER_CUERPO : NVX_KICKER_ROSTRO;

	$faqs = array();
	foreach ( (array) ( $spec['faqs'] ?? array() ) as $faq ) {
		if ( ! is_array( $faq ) ) {
			continue;
		}
		$faqs[] = nvx_anatomical_faq(
			(string) ( $faq['q'] ?? '' ),
			(string) ( $faq['a'] ?? '' )
		);
	}

	return nvx_anatomical_entry(
		(string) ( $spec['slug'] ?? '' ),
		(string) ( $spec['seo_title'] ?? '' ),
		(string) ( $spec['description'] ?? '' ),
		$kicker,
		(string) ( $spec['h1'] ?? '' ),
		(string) ( $spec['lead'] ?? '' ),
		(string) ( $spec['diagnosis'] ?? '' ),
		(string) ( $spec['mechanism'] ?? '' ),
		array_values( array_map( 'strval', (array) ( $spec['indications'] ?? array() ) ) ),
		array_values( array_map( 'strval', (array) ( $spec['precautions'] ?? array() ) ) ),
		array_values( array_map( 'strval', (array) ( $spec['process'] ?? array() ) ) ),
		$faqs
	);
}

/**
 * Hydrate a subset of zone keys into catalogue entries.
 *
 * @param string[] $keys
 * @return array<string, array<string, mixed>>
 */
function nvx_anatomical_catalog_slice( array $keys ): array {
	$all    = nvx_anatomical_zone_specs();
	$result = array();
	foreach ( $keys as $key ) {
		if ( ! isset( $all[ $key ] ) || ! is_array( $all[ $key ] ) ) {
			continue;
		}
		$result[ $key ] = nvx_anatomical_entry_from_spec( $all[ $key ] );
	}
	return $result;
}

/**
 * Catalogue for Facial Anatomical Zone Pages (upper + mid thirds).
 *
 * @return array<string, array<string, mixed>>
 */
function nvx_anatomical_facial_catalog_upper_mid(): array {
	return nvx_anatomical_catalog_slice(
		array( 'tercio-superior', 'mirada', 'tercio-medio' )
	);
}

/**
 * Catalogue for Facial Anatomical Zone Pages (lower third + lips).
 *
 * @return array<string, array<string, mixed>>
 */
function nvx_anatomical_facial_catalog_lower(): array {
	return nvx_anatomical_catalog_slice(
		array( 'labios', 'tercio-inferior' )
	);
}

/**
 * Catalogue for Facial Anatomical Zone Pages.
 *
 * @return array<string, array<string, mixed>>
 */
function nvx_anatomical_facial_catalog(): array {
	return array_merge(
		nvx_anatomical_facial_catalog_upper_mid(),
		nvx_anatomical_facial_catalog_lower()
	);
}

/**
 * Catalogue for Body Anatomical Zone Pages.
 *
 * @return array<string, array<string, mixed>>
 */
function nvx_anatomical_body_catalog(): array {
	return nvx_anatomical_catalog_slice(
		array( 'abdomen-y-flancos', 'brazos-y-espalda', 'tren-inferior' )
	);
}

/**
 * Catalogue for Anatomical Zone Pages.
 *
 * @return array<string, array<string, mixed>>
 */
function nvx_anatomical_pages_catalog(): array {
	return array_merge(
		nvx_anatomical_facial_catalog(),
		nvx_anatomical_body_catalog()
	);
}

nvx_register_catalog_content_filter( 'nvx_anatomical_pages_catalog', 22 );
