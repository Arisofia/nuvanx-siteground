export const HUBSPOT_PORTAL_ID = String(process.env.HUBSPOT_PORTAL || '147416356').trim();
export const HUBSPOT_FORM_ID = String(process.env.HUBSPOT_FORM_ID || '5042522a-0bc5-4381-ac3e-5aee8649b69c').trim();
export const HUBSPOT_SUBMISSION_HOST_PATTERN = /(^|\.)hsforms\.(com|net)$/i;
export const HUBSPOT_SUBMISSION_PATH_PREFIX = '/submissions/v3/integration/';

export const HUBSPOT_PRODUCTION_FORBIDDEN_PATTERNS = [
  [/from\s+['"]playwright['"]/, 'production HubSpot probe must not launch a browser'],
  [/nvxqa-h1-/i, 'production HubSpot probe must not generate QA contact emails'],
  [/QA H1 Attribution/i, 'production HubSpot probe must not synthesize contact names'],
  [/wp_set_consent/i, 'production HubSpot probe must not grant marketing consent'],
  [/\?gclid=/i, 'production HubSpot probe must not generate synthetic paid-search attribution'],
  [/\.click\s*\(/, 'production HubSpot probe must not click a form submit control'],
  [/submissions\/v3/i, 'production HubSpot probe must not call or monitor submission endpoints'],
];
