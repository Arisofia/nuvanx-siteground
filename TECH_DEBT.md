# Technical Debt Log

## Overview
This document tracks technical debt identified during PHP audit and code review, with prioritization for remediation.

## Critical Issues (P1) - Immediate Action Required

### 1. PHPCS Violations
- **Total:** 2,563 errors, 350 warnings across 57 files
- **Auto-fixable:** 2,385 (93%)
- **Critical Files:**
  - `nvx-structured-data.php` - 1,059 errors, 131 warnings
  - `nvx-clinics-hub.php` - 790 errors, 13 warnings
  - `header.php` - 50 errors, 29 warnings

**Action:** Run PHPCBF to auto-fix, manually review remaining errors

### 2. PHPStan Configuration
- **Issue:** Using outdated PHPStan 1.12.34 (recommends 2.2+)
- **Issue:** Missing WordPress stubs configuration
- **Impact:** Cannot properly analyze WordPress-specific code

**Action:** Update to PHPStan 2.2+, configure proper WordPress stubs

## High Priority Issues (P2) - Next Sprint

### 3. Monolithic Modules (>50KB)
Files that should be refactored into smaller, focused modules:

- `nvx-structured-data.php` (62,946 bytes)
  - **Responsibility:** Schema.org generation for medical entities
  - **Plan:** Split by schema type (MedicalOrganization, Physician, MedicalProcedure, FAQPage, PriceRange)
  - **Target structure:** `inc/structured-data/*.php`

- `nvx-content-presentation.php` (59,918 bytes)
  - **Responsibility:** Global content presentation and CTAs
  - **Plan:** Split by function (ctas.php, hero.php, clinical-values.php, schema-helpers.php)
  - **Target structure:** `inc/content-presentation/*.php`

- `nvx-clinics-hub.php` (46,847 bytes)
  - **Responsibility:** Clinic layout pipeline and normalization
  - **Plan:** Split by function (layout-pipeline.php, class-lists.php, normalize-functions.php)
  - **Target structure:** `inc/clinics-hub/*.php`

- `nvx-equipo-page.php` (36,217 bytes)
  - **Responsibility:** Medical team page rendering
  - **Plan:** Extract team data processing, separate presentation logic
  - **Target structure:** `inc/equipo-page/*.php`

### 4. Test Coverage Gaps
> **Note (2026-08):** Routes listed below are now covered in `wp-content/themes/nuvanx-medical/tests/routes-critical.ts` and executed across `a11y.spec.ts`, `visual-regression.spec.ts`, and `seo-extended.spec.ts`. This section is pending cleanup.

**Current Coverage:** Limited to 4 routes in a11y.spec.ts, 2 routes in visual.spec.ts

**Missing Critical Routes:**
- `/tratamientos/` - Treatment catalog
- `/soluciones-medicas/` - Service offerings
- `/clinicas/` - Location information
- `/madrid/valoracion/` - Main conversion page
- `/equipo-medico/` - Medical team page
- `/nosotros/` - About page

**Action:** Expand Playwright test suite to cover all critical routes

### 5. Functions Potentially Orphaned
Functions that may not be used across the codebase (needs dependency analysis):

- `nvx_home_whatsapp_url()` - Only used in nvx-content-presentation.php
- `nvxClinicsIsPhoneOrWhatsappLink()` - Only used in nvx-clinics-hub.php
- Various helper functions in page-specific modules

**Action:** Complete dependency graph analysis to confirm usage

## Medium Priority Issues (P3) - Future Sprints

### 6. Code Documentation
- Missing function docblocks across most files
- Inconsistent parameter documentation
- Missing return type documentation in some functions

**Action:** Add comprehensive docblocks following WordPress standards

### 7. Error Handling
- Inconsistent error handling patterns
- Missing try-catch blocks for file operations
- No graceful degradation for missing config files

**Action:** Standardize error handling patterns

### 8. Configuration Management
- Mixed approach to configuration (constants, JSON, WordPress options)
- No validation of configuration values
- No fallback mechanisms for missing config

**Action:** Standardize configuration approach with validation

## Low Priority Issues (P4) - Backlog

### 9. Legacy Code
- Commented-out code blocks (needs removal)
- Feature flags that may be obsolete
- Compatibility code for old WordPress versions

**Action:** Review and remove unnecessary legacy code

### 10. Performance Optimization
- Large JSON files loaded on every request
- No caching of configuration data
- Potential N+1 query problems in some modules

**Action:** Implement caching strategies and optimize data loading

## Refactoring Progress

### Completed ✅
- [x] Externalized WhatsApp numbers to config.json
- [x] Externalized medical colegiado numbers to config.json
- [x] Created config helper functions (nvx_config_get, nvx_whatsapp_url, nvx_medical_colegiado)
- [x] Updated critical files to use new config helpers
- [x] Integrated PHPCS and PHPStan into CI workflow
- [x] Expanded Playwright test routes configuration
- [x] Created comprehensive SEO validation tests
- [x] Created SECURITY.md with rotation policies

### In Progress 🔄
- [ ] Run PHPCBF to auto-fix 2,385 violations
- [ ] Manual review of remaining PHPCS errors
- [ ] Update PHPStan to 2.2+ and configure WordPress stubs

### Planned 📋
- [ ] Refactor nvx-structured-data.php (63KB)
- [ ] Refactor nvx-content-presentation.php (60KB)
- [ ] Refactor nvx-clinics-hub.php (47KB)
- [ ] Complete dependency graph analysis
- [ ] Add comprehensive docblocks
- [ ] Standardize error handling
- [ ] Implement configuration caching

## Metrics

### Code Quality Metrics
- **PHPCS Errors:** 2,563 (target: <100)
- **PHPStan Errors:** 20 (target: 0 with proper stubs)
- **Test Coverage:** ~30% of critical routes (target: 100%)
- **Average Module Size:** 15KB (target: <30KB)

### Security Metrics
- **Known Vulnerabilities:** 0 (composer audit)
- **Secret Rotation:** 0 days since last rotation
- **Security Issues:** 0 open

## Notes

- Dependencies graph analysis needs to be completed to identify truly orphaned functions
- Some PHPCS errors may be acceptable given WordPress coding standards
- PHPStan errors are expected until proper WordPress stubs are configured
- Test expansion should prioritize conversion pages first

**Last Updated:** 2026-08-03
**Next Review:** 2026-08-17