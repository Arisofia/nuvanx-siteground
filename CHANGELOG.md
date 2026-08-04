# CHANGELOG

All notable changes to the NUVANX codebase are documented in this file.

## [Unreleased]

### Added - Quality & Automation (2026-08-04)

- **CSS/PHP Linting Pipeline**:
  - `no-hardcoded-colors.mjs`: Detects hardcoded hex colors in CSS, ignores tokens, shadows, and legitimate use cases
  - `no-hardcoded-fontsize.mjs`: Detects hardcoded px font-size values, allows exception markers
  - `no-inline-layout-styles.mjs`: Detects dangerous inline styles (margin, padding, font-size, color) in PHP
  - All lints integrated into CI workflows (ci-quality.yml, deploy-staging2.yml, deploy.yml)
  - Fail-fast before expensive browser tests

- **Layout Contract Enforcement**:
  - `header.php`: Always opens `<main id="nvx-main" class="nvx-main" role="main" tabindex="-1">` and `<div class="nvx-brand-page">`
  - `footer.php`: Now properly closes both wrappers (was missing closure)
  - Templates verified: page-landing-valoracion.php and page-sede.php trust global wrapper
  - No duplicate nvx-brand-page wrappers across codebase

### Changed - Design System Updates

- **DESIGN_GUIDE.md**: Updated with official 13-point system and component patterns
- **README.md**: Added linting commands and updated workflow documentation
- Documentation cleanup: Removed temporary audit reports and legacy documentation files

### Technical Debt Resolution

- **PHP Code Quality**: Resolved PHPCS and PHPStan errors
- **CSS Migration**: Hardcoded values migrated to CSS tokens via migration script
- **Icon Sizing**: Fixed SVG icon dimensions to use CSS tokens consistently
- **DOM Structure**: Corrected conditional duplication of `<main>` elements

### Fixed - Critical Issues

- **PHP Code Leak**: Removed problematic `?>` tag from footer.php
- **FacebookSignal Integration**: Added HTML output filter to strip from final output
- **H1/Meta Title Duplication**: Identified and documented duplication between Contact and Clinics pages
- **Third-Party Scripts**: Audited FacebookSignal and HubSpot integrations
