<?php
/**
 * Canonical facial aesthetic treatment pages.
 *
 * One versioned catalogue drives visible content, metadata, FAQ schema and the
 * staging-only page seeder. Content, SEO and schema inject only when
 * review_status is approved_for_publication (production fail-closed). Staging2
 * may preview pending_medical_review entries (globally noindex).
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Schema.org procedureType constants for medical procedures.
if ( ! defined( 'NVX_SCHEMA_MINIMALLY_INVASIVE' ) ) {
	define( 'NVX_SCHEMA_MINIMALLY_INVASIVE', 'https://schema.org/MinimallyInvasiveProcedure' );
}
if ( ! defined( 'NVX_SCHEMA_PERCUTANEOUS' ) ) {
	define( 'NVX_SCHEMA_PERCUTANEOUS', 'https://schema.org/PercutaneousProcedure' );
}
if ( ! defined( 'NVX_SCHEMA_NONINVASIVE' ) ) {
	define( 'NVX_SCHEMA_NONINVASIVE', 'https://schema.org/NoninvasiveProcedure' );
}

require_once __DIR__ . '/nvx-13-point-renderer.php';

/**
 * Load raw aesthetic treatment specs from the versioned JSON catalogue.
 *
 * @return array<string, array<string, mixed>>
 */
function nvx_aesthetic_treatment_catalog(): array {
	static $catalog = null;
	if ( null !== $catalog ) {
		return $catalog;
	}

	$catalog = nvx_theme_load_json_catalog( 'nvx-aesthetic-treatment-catalog.json' );
	return $catalog;
}

/**
 * Whether catalogue content, SEO and schema may inject for this entry.
 *
 * Production: only approved_for_publication.
 * Staging2: also previews pending_medical_review (environment is noindex).
 */
function nvxAestheticTreatmentIsRenderable( array $entry ): bool {
	$status = (string) ( $entry['review_status'] ?? '' );
	$approved = 'approved_for_publication' === $status;
	$staging_pending = 'pending_medical_review' === $status
		&& function_exists( 'nvx_environment_is_staging2' )
		&& nvx_environment_is_staging2();
	return $approved || $staging_pending;
}

/**
 * Catalogue slice for the_content matching.
 *
 * Renderable entries are forced to approved_for_publication so the shared
 * matcher (which ignores non-approved statuses) can inject on staging2 previews.
 *
 * @return array<string, array<string, mixed>>
 */
function nvxAestheticTreatmentCatalogForRender(): array {
	$out = array();
	foreach ( nvx_aesthetic_treatment_catalog() as $key => $entry ) {
		if ( ! nvxAestheticTreatmentIsRenderable( $entry ) ) {
			continue;
		}
		$entry['review_status'] = 'approved_for_publication';
		$out[ $key ]            = $entry;
	}
	return $out;
}

/**
 * Pluck one field from every catalogue entry, keyed by treatment key.
 *
 * @return array<string, mixed>
 */
function nvx_aesthetic_treatment_pluck( string $field ): array {
	$result = array();
	foreach ( nvx_aesthetic_treatment_catalog() as $key => $entry ) {
		if ( array_key_exists( $field, $entry ) ) {
			$result[ $key ] = $entry[ $field ];
		}
	}
	return $result;
}

/** Resolve a treatment key from slug (does not apply render gate). */
function nvx_aesthetic_treatment_key_from_slug( string $slug ): ?string {
	$slug = trim( $slug, '/' );
	foreach ( nvx_aesthetic_treatment_catalog() as $key => $entry ) {
		if ( $slug === $entry['slug'] ) {
			return $key;
		}
	}
	return null;
}

/**
 * Current treatment key only when the entry is renderable (content + SEO + schema gate).
 */
function nvx_aesthetic_treatment_current_key(): ?string {
	$key = null;
	if ( ! is_admin() && is_singular( 'page' ) ) {
		$slug = (string) get_post_field( 'post_name', get_queried_object_id() );
		$key  = nvx_aesthetic_treatment_key_from_slug( $slug );
		if ( null !== $key ) {
			$entry = nvx_aesthetic_treatment_catalog()[ $key ] ?? null;
			if ( ! is_array( $entry ) || ! nvxAestheticTreatmentIsRenderable( $entry ) ) {
				$key = null;
			}
		}
	}
	return $key;
}

/** @return array<string, array<int, array{q:string,a:string}>> */
function nvx_aesthetic_treatment_faq_catalog(): array {
	return nvx_aesthetic_treatment_pluck( 'faqs' );
}

/** @return array<string, array<string, mixed>> */
function nvx_aesthetic_treatment_schema_catalog(): array {
	return nvx_aesthetic_treatment_pluck( 'schema' );
}

nvx_register_catalog_content_filter( 'nvxAestheticTreatmentCatalogForRender', 80 );

/** Canonical SEO metadata for the four pages. */
function nvx_aesthetic_treatment_current_entry(): ?array {
	$key     = nvx_aesthetic_treatment_current_key();
	$catalog = nvx_aesthetic_treatment_catalog();
	return null !== $key && isset( $catalog[ $key ] ) ? $catalog[ $key ] : null;
}

/**
 * Read a field from the current treatment entry, or return the Yoast/WP fallback.
 *
 * @param mixed $fallback
 * @return mixed
 */
function nvx_aesthetic_treatment_meta_field( string $field, $fallback ) {
	$entry = nvx_aesthetic_treatment_current_entry();
	return null !== $entry && isset( $entry[ $field ] ) ? $entry[ $field ] : $fallback;
}

function nvx_aesthetic_treatment_filter_title( $title ) {
	return nvx_aesthetic_treatment_meta_field( 'seo_title', $title );
}

function nvx_aesthetic_treatment_filter_description( $description ) {
	return nvx_aesthetic_treatment_meta_field( 'description', $description );
}

function nvx_aesthetic_treatment_filter_canonical( $canonical ) {
	$entry = nvx_aesthetic_treatment_current_entry();
	return null === $entry ? $canonical : home_url( '/' . $entry['slug'] . '/' );
}

nvx_register_yoast_seo_filters(
	'nvx_aesthetic_treatment_filter_title',
	'nvx_aesthetic_treatment_filter_description',
	'nvx_aesthetic_treatment_filter_canonical',
	90
);

function nvx_aesthetic_treatment_document_title( array $parts ): array {
	$seo_title = nvx_aesthetic_treatment_meta_field( 'seo_title', null );
	if ( null !== $seo_title && '' !== (string) $seo_title ) {
		$parts['title'] = $seo_title;
	}
	return $parts;
}
add_filter( 'document_title_parts', 'nvx_aesthetic_treatment_document_title', 90 );

/** Seed the four pages only in staging2, which is globally noindex. */
function nvx_aesthetic_treatment_seed_staging_pages(): void {
	nvx_seed_staging_pages(
		nvx_aesthetic_treatment_catalog(),
		'_nvx_aesthetic_treatment_key',
		'<div class="nvx-aesthetic-treatment-source" data-nvx-treatment="{key}"></div>'
	);
}
add_action( 'init', 'nvx_aesthetic_treatment_seed_staging_pages', 30 );
