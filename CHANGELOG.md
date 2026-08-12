# CHANGELOG

All notable changes to the NUVANX codebase are documented in this file.

## [Unreleased]

### Fixed - Deployment SSH Issues (2026-08-05)

- **production.yml** and **staging.yml**: Applied SSH fixes:
  - ConnectTimeout increased from default to 30 seconds
  - ServerAliveInterval 60 for keep-alive
  - ServerAliveCountMax 3 for retries
  - StrictHostKeyChecking yes for security

### Changed - CSS Optimization (2026-08-05)

- **nvx-brand-home.css**: Removed unused CSS rules:
  - `.nvx-home-invitation` (not used in front-page.php)
  - `.nvx-home-action-banner*` (not used in front-page.php)
  - `.nvx-benefits__grid` and `.nvx-benefit-item` (not used in front-page.php)
  - Mobile responsive breakpoints for unused hero CTA clusters
  - Reduced file from 96 lines to 31 lines (~68% reduction)

### Added - Quality & Automation (2026-08-04)

- **CSS/PHP Linting Pipeline**:
  - `no-hardcoded-colors.mjs`: Detects hardcoded hex colors in CSS, ignores tokens, shadows, and legitimate use cases
  - `no-hardcoded-fontsize.mjs`: Detects hardcoded px font-size values, allows exception markers
  - `no-inline-layout-styles.mjs`: Detects dangerous inline styles (margin, padding, font-size, color) in PHP
  - All lints integrated into CI workflows (staging.yml, production.yml)
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

### Removed - Legacy Files and Dependencies

- **PHP Legacy Files**: Removed inc/ directory (ai-telemetry-wrapper.php, assets-loader.php, cli-clean-divi.php) - legacy Divi migration files
- **Root Theme Files**: Removed page.php and single.php from root (duplicates of theme files)
- **Scratch Directory**: Removed scratch/refactor.php (temporary development file)
- **Node.js Dependencies**: Removed package.json, package-lock.json, eslint.config.js
- **Playwright Infrastructure**: Removed tests/, playwright.config.ts, TypeScript test files
- **CSS Build Process**: Removed src/, css-backups/, Tailwind CSS configuration
- **Testing Artifacts**: Removed screenshots, test reports PNG files
- **Validation Scripts**: Removed validate-hidden-pages.mjs, migrate-css-tokens.js

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
