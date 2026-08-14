/**
 * Shared HubSpot form selectors used across staging tests and runtime governance.
 *
 * Centralized to ensure consistency when HubSpot mount attributes change.
 */

/**
 * Primary selector for the canonical HubSpot form mount.
 * Prefers the lazy attribute but includes fallback for legacy resilience.
 */
export const HUBSPOT_MOUNTED_SELECTOR = [
  '#nvx-hubspot-form .hs-form-frame[data-nvx-hubspot-lazy="1"] iframe[data-test-id^="embedded-form-"]',
  '#nvx-hubspot-form .hs-form-frame iframe[data-test-id^="embedded-form-"]',
].join(', ');

/**
 * Selector for the HubSpot frame container (without iframe).
 * Used for counting mounts and verifying container presence.
 */
export const HUBSPOT_FRAME_CONTAINER_SELECTOR = '.hs-form-frame[data-nvx-hubspot-lazy="1"]';

/**
 * Fallback selector for legacy HubSpot frames without the lazy attribute.
 * Used for backward compatibility with older implementations.
 */
export const HUBSPOT_LEGACY_FRAME_SELECTOR = '.hs-form-frame';

/**
 * Comprehensive selector matching any HubSpot frame mount.
 * Used in runtime governance for broad detection.
 */
export const HUBSPOT_ANY_FRAME_SELECTOR = [
  '.hs-form-frame[data-nvx-hubspot-lazy="1"]',
  '#nvx-hubspot-native-form .hs-form-frame',
  '[data-nvx-hubspot-native="1"] .hs-form-frame',
].join(', ');
