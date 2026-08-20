<?php
/**
 * NUVANX Filter Priority Configuration
 *
 * Deterministic filter pipeline with explicit and unique priorities.
 * Eliminates dependencies based on require_once order.
 *
 * Priority scheme:
 * 10 → managed page
 * 20 → structural renderer
 * 30 → presentation
 * 40 → business rules
 * 50 → schema/content normalization
 * 60 → final hygiene
 *
 * Textual SEO metadata is intentionally absent from this registry: its sole
 * owner is nvx-seo-metadata.php at priority 100.
 *
 * @package NUVANX
 */

defined( 'ABSPATH' ) || exit;

/**
 * NUVANX Filter Priority Constants
 *
 * Each transformation has an explicit and unique priority.
 * Priority gaps allow for future additions without conflicts.
 */
return [
	// === 10-19: Managed Page ===
	'nvx_contacto_resolve_legacy_template'         => 10, // page_template
	'nvx_contacto_migrate_legacy_template_meta'    => 13, // template_redirect
	'nvx_theme_disable_public_facebook_pixel'      => 14, // option_active_plugins

	// === 20-29: Structural Renderer ===
	'nvx_contact_append_maps'                      => 20, // the_content
	'nvx_content_presentation_enhance'             => 21, // the_content
	'nvx_content_inject_global_treatment_sections' => 22, // the_content
	'nvx_content_ensure_exion_investment'          => 23, // the_content
	'nvxSedeStripLayoutInlineStyles'               => 24, // the_content
	'nvx_clinics_hub_render_managed'               => 25, // the_content
	'nvxClinicsHubEnhance'                         => 26, // the_content
	'nvx_contacto_enhance_valoracion_page'         => 27, // the_content
	'nvx_bridal_inject_media'                      => 29, // the_content

	// === 30-39: Presentation ===
	'nvx_filter_contacto_social_image'             => 34, // wpseo_opengraph_image
	'nvx_contacto_add_yoast_opengraph_image'       => 37, // wpseo_add_opengraph_images
	'nvx_content_strip_page_closing_ctas_late'     => 38, // the_content

	// === 40-49: Business Rules ===
	'nvx_seo_nonproduction_x_robots_headers'       => 40, // wp_headers
	'nvx_theme_print_google_attribution_meta'      => 41, // wp_head

	// === 50-59: Schema/Content Normalization ===
	'nvx_treatment_hub_extend_yoast_graph'         => 50, // wpseo_schema_graph
	'nvx_aesthetic_treatment_extend_yoast_graph'   => 51, // wpseo_schema_graph
	'nvx_extend_yoast_schema_graph'                => 52, // wpseo_schema_graph
	'nvx_seo_production_readiness_schema_graph'    => 53, // wpseo_schema_graph
	'nvx_filter_contacto_schema_graph'             => 54, // wpseo_schema_graph
	'nvx_schema_semantic_normalize_graph'          => 55, // wpseo_schema_graph

	// === 60-69: Final Hygiene ===
	'nvx_schema_gate_faq_emission'                 => 60, // wpseo_schema_graph
	'nvx_schema_deduplicate_ids'                   => 61, // wpseo_schema_graph
	'nvx_schema_runtime_retire_legacy_emitters'    => 62, // wp_loaded
	'nvx_render_deploy_stamp_meta'                 => 63, // wp_head
	'nvx_render_deploy_stamp_jsonld'               => 64, // wp_head
];

/**
 * Get filter priority by name.
 *
 * @param string $filter_name Filter hook name
 * @return int Explicit priority or 10 as default
 */
function nvx_get_filter_priority( string $filter_name ): int {
	$priorities = require __DIR__ . '/nvx-filter-priorities.php';
	return $priorities[ $filter_name ] ?? 10;
}

/**
 * Register filter with explicit priority.
 *
 * @param string   $hook          Filter hook name
 * @param callable $callback      Callback function
 * @param int      $accepted_args Number of arguments
 * @return bool True on success
 */
function nvx_add_filter_with_priority( string $hook, callable $callback, int $accepted_args = 1 ): bool {
	$priority = nvx_get_filter_priority( $callback );
	return add_filter( $hook, $callback, $priority, $accepted_args );
}

/**
 * Register action with explicit priority.
 *
 * @param string   $hook          Action hook name
 * @param callable $callback      Callback function
 * @param int      $accepted_args Number of arguments
 * @return bool True on success
 */
function nvx_add_action_with_priority( string $hook, callable $callback, int $accepted_args = 1 ): bool {
	$priority = nvx_get_filter_priority( $callback );
	return add_action( $hook, $callback, $priority, $accepted_args );
}
