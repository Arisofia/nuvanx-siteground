<?php
/**
 * Global application constants.
 *
 * This file maps all hook priorities to named constants to provide a
 * deterministic, self-documenting execution graph without magic numbers.
 *
 * NOTE: This scope currently covers ONLY the_content filters. Other priority
 * graphs (e.g. wpseo_metadesc, template_include) are deferred as future
 * technical debt.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// -----------------------------------------------------------------------------
// Robots Policy Directives
// -----------------------------------------------------------------------------

const NVX_ROBOTS_INHERIT          = 0;
const NVX_ROBOTS_INDEX_FOLLOW     = 1;
const NVX_ROBOTS_NOINDEX_FOLLOW   = 2;
const NVX_ROBOTS_NOINDEX_NOFOLLOW = 3;

// -----------------------------------------------------------------------------
// Filter Priorities for `the_content`
// -----------------------------------------------------------------------------
// Establishes a predictable sequence for content transformations and modules.

/**
 * Early substitutions, host normalization (staging2).
 */
const NVX_HOOK_PRIO_INTERNAL_LINKS = 13;

/**
 * Main feature module injection (e.g. Valuation form).
 */
const NVX_HOOK_PRIO_MODULE_RENDER = 16;

/**
 * Secondary feature module injection (e.g. CO2 editorial restructure).
 */
const NVX_HOOK_PRIO_MODULE_RESTRUCTURE = 19;

/**
 * Trust badge stripping or authority injection.
 */
const NVX_HOOK_PRIO_TRUST_BADGES = 22;

/**
 * General production business rules.
 */
const NVX_HOOK_PRIO_BUSINESS_RULES = 99;
