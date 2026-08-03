/**
 * Critical routes for comprehensive testing
 *
 * These routes represent the most important user journeys and conversion paths
 * that should be tested for accessibility, SEO, structure, and visual consistency.
 */

export const CRITICAL_ROUTES = [
  '/',                           // Home - main landing page
  '/contacto/',                  // Contacto - clinic information and contact
  '/blog/',                      // Blog index - content hub
  '/tratamientos/',             // Tratamientos hub - treatment catalog
  '/soluciones-medicas/',        // Soluciones médicas - service offerings
  '/clinicas/',                  // Clínicas - location information
  '/madrid/valoracion/',         // Valoración - main conversion page
  '/equipo-medico/',             // Equipo médico - medical team and credentials
  '/nosotros/',                  // Nosotros - about page
  '/404-expected-slug/'          // 404 - error handling
];

export const CONVERSION_ROUTES = [
  '/madrid/valoracion/',         // Main conversion funnel
];

export const CONTENT_ROUTES = [
  '/blog/',
  '/tratamientos/',
  '/soluciones-medicas/',
];

export const LOCATION_ROUTES = [
  '/clinicas/',
  '/contacto/',
];