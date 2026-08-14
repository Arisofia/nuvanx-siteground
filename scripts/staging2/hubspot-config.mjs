export const HUBSPOT_PORTAL_ID = String(process.env.HUBSPOT_PORTAL || '147416356').trim();
export const HUBSPOT_FORM_ID = String(process.env.HUBSPOT_FORM_ID || '5042522a-0bc5-4381-ac3e-5aee8649b69c').trim();
export const HUBSPOT_SUBMISSION_HOST_PATTERN = /(^|\.)hsforms\.(com|net)$/i;
export const HUBSPOT_SUBMISSION_PATH_PREFIX = '/submissions/v3/integration/';
