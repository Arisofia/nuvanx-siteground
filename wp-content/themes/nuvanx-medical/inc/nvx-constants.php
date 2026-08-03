<?php
/**
 * Global application constants.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// -----------------------------------------------------------------------------
// Robots Policy Directives
// -----------------------------------------------------------------------------

const NVX_ROBOTS_INHERIT         = 0;
const NVX_ROBOTS_INDEX_FOLLOW    = 1;
const NVX_ROBOTS_NOINDEX_FOLLOW  = 2;
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
const NVX_HOOK_PRIO_MODULE_RENDER  = 16;

/**
 * Secondary feature module injection (e.g. CO2 editorial restructure).
 */
const NVX_HOOK_PRIO_CO2_MODULE     = 19;

/**
 * Trust badge stripping or authority injection (Cristina is at 99 inside apply_production_business_rules).
 */
const NVX_HOOK_PRIO_TRUST_BADGES   = 22;

/**
 * General production business rules.
 */
const NVX_HOOK_PRIO_BUSINESS_RULES = 99;


