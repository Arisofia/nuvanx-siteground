<?php
/**
 * Template Name: Soluciones médicas
 * Template Post Type: page
 *
 * Dedicated route template for /soluciones-medicas/.
 * Renders the GitHub-owned hub markup without the_content filters.
 *
 * @package nuvanx-medical
 */

defined( 'ABSPATH' ) || exit;

// Fail closed with visible diagnostics if the canonical partial is missing.
$partial = get_template_directory() . '/template-parts/content/nvx-soluciones-medicas-github.php';

// header.php owns the single public-document buffer. This template only
// captures the solutions partial (local view buffer), never a second document rewrite.

get_header();

echo "\n<!-- nvx-solutions-template-active -->\n";

$markup = function_exists( 'nvx_solutions_hub_markup' ) ? nvx_solutions_hub_markup() : '';
if ( '' === trim( $markup ) && is_readable( $partial ) ) {
	// Fallback if the helper is unavailable: load the view without require_once.
	$level = ob_get_level();
	ob_start();
	load_template( $partial, false );
	$markup = (string) ob_get_clean();
	while ( ob_get_level() > $level ) {
		ob_end_clean();
	}
}

if ( '' !== trim( $markup ) ) {
	// Partial builds HTML with escaped helpers; do not re-escape compound markup.
	echo $markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- composed from escaped theme partial.
} elseif ( is_readable( $partial ) ) {
	echo '<div class="nvx-shell"><h1 class="nvx-heading">Soluciones médicas</h1><p>Plantilla de soluciones vacía.</p></div>';
} else {
	echo '<div class="nvx-shell"><h1 class="nvx-heading">Soluciones médicas</h1><p>Falta el partial versionado de soluciones.</p></div>';
}

get_footer();
