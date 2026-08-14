const GOOGLE_PLACE_WIDGET_METADATA = /maps\.googleapis\.com\/\$rpc\/google\.internal\.maps\.mapsjs\.v1\.MapsJsInternalService\/GetPlaceWidgetMetadata/i;

/**
 * Identify third-party console noise that does not originate in NUVANX code.
 *
 * The Google Place compact widget can emit a transient CORS pair from its own
 * google.com frame while the clinic page and map remain usable. Keep the
 * allowlist endpoint-specific so first-party and unknown errors still block.
 */
export function isIgnorableExternalConsoleError(message) {
  const text = String(message || '');
  if (!GOOGLE_PLACE_WIDGET_METADATA.test(text)) return false;

  return /blocked by CORS policy/i.test(text) ||
    /<gmp-place-details-compact>[\s\S]*(?:network request error|Rpc failed)/i.test(text);
}
