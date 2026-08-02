# CHANGELOG

All notable changes to the NUVANX codebase are documented in this file.

## [Unreleased]

### Added

- Repository documentation enhancements: added prerequisites, secret inventory, local validation commands, and step-by-step deployment checklist to `README.md`.
- Standardized `.gitignore` rules to keep standard environment boundaries without masking repository artifacts.

### Fixed / Cleaned (Recent Audit Refactors)

- `bf4245d`: Code hygiene — removed duplicate docblock and normalized indentation in `nvx_remove_unverified_quantitative_trust_badges` (`inc/nvx-page-hygiene.php`).
- `3c1d267`: Theme pagination & safety — added `page` query var fallback in `nvx_blog_index` shortcode (`functions.php`) and removed legacy MU plugin function existence guards.
- `13b8f8c`: Asset cleanup — removed unused `nvx-hero-blackout.css` stylesheet.
- `ad886e4`: Navigation hygiene — force-gated `casos-de-pacientes` 404 links dynamically.
- `a8b023d`: Repository hygiene — purged QA screenshot binaries from git history and added `images/` to `.gitignore`.
- `775ef38`: Code hygiene — removed dead PHP helper functions, unused CSS variables from `nvx-tokens.css`, and orphan CSS selectors.
