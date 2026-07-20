<?php
/**
 * Terminal visual closure for third-party widgets and legacy role aliases.
 *
 * Loaded after plugin styles so external UI follows the same Playfair Display +
 * Manrope and icon-size contract as the NUVANX theme.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Non-public canonical output used by rendered QA and pre-production review. */
require_once __DIR__ . '/nvx-staging2-canonical-closure.php';

/**
 * Provides the late-loaded CSS for external widgets and site-specific visual layouts.
 *
 * @return string The inline stylesheet content.
 */
function nvx_external_visual_closure_css(): string {
	return <<<'CSS'
/* NUVANX — terminal external visual closure */
body,
button,
input,
select,
textarea,
label,
summary,
.joinchat,
.joinchat__chatbox,
.joinchat__bubble,
.joinchat__open__text,
.joinchat__powered {
  font-family: var(--nvx-sans);
}

:where(.joinchat, .joinchat *) {
  font-family: var(--nvx-sans);
}

:where(
  h1,
  h2,
  h3,
  .nvx-display,
  .nvx-heading,
  .nvx-heading-1,
  .nvx-heading-2,
  .nvx-heading-3,
  .nvx-page__title,
  .nvx-page-title,
  .nvx-section-title,
  .nvx-brand-hero__title,
  .nvx-hero__title,
  .nvx-editorial-hero__title,
  .nvx-brand-title,
  .nvx-brand-subtitle,
  .nvx-card__title,
  .nvx-clinic-card__name
) {
  font-family: var(--nvx-serif);
}

.icon-whatsapp {
  display: inline-block;
  width: var(--nvx-icon-xs);
  height: var(--nvx-icon-xs);
  color: currentColor;
  flex: 0 0 var(--nvx-icon-xs);
  vertical-align: middle;
}

.icon-whatsapp path {
  fill: currentColor;
  stroke: none;
}

.joinchat {
  --s: var(--nvx-icon-frame);
}

.joinchat__button {
  width: var(--nvx-icon-frame);
  height: var(--nvx-icon-frame);
  min-width: var(--nvx-icon-frame);
  min-height: var(--nvx-icon-frame);
}

.joinchat__open__icon {
  width: var(--nvx-icon-sm);
  height: var(--nvx-icon-sm);
  flex: 0 0 var(--nvx-icon-sm);
}

/* Strategy pages retain the canonical shell restored in PHP markup. */

.nvx-strategy-page > .nvx-brand-hero {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  justify-content: center;
  width: 100%;
  max-width: none;
  min-height: calc(var(--nvx-space-12) * 4);
  height: auto;
  margin: 0;
  padding-block: var(--nvx-pad-section);
  padding-inline: max(
    var(--nvx-gutter-inner),
    calc((100vw - var(--nvx-shell)) / 2 + var(--nvx-gutter-inner))
  );
  box-sizing: border-box;
}

.nvx-strategy-page > .nvx-brand-hero .nvx-brand-title {
  max-width: 22ch;
  color: var(--nvx-light);
}

.nvx-strategy-page > .nvx-brand-hero .nvx-brand-lead {
  max-width: var(--nvx-measure-lead);
  color: var(--nvx-surface-soft);
}

.nvx-strategy-page > .nvx-brand-section {
  width: var(--nvx-shell);
  max-width: 100%;
  margin-inline: auto;
  padding-inline: var(--nvx-gutter-inner);
  box-sizing: border-box;
}

.nvx-strategy-page .nvx-endolift-price-table-wrap {
  width: 100%;
  overflow-x: auto;
}

.nvx-strategy-page .nvx-endolift-price-table {
  width: 100%;
}

/* Native details controls preserve the canonical control-size interaction target. */
.nvx-faq summary,
.nvx-brand-faq-accordion summary,
.nvx-home-faq-editorial summary {
  min-height: var(--nvx-control-size);
  box-sizing: border-box;
}

/* Closing CTA presentation formerly duplicated in markup attributes. */
.nvx-cta-banner {
  text-align: center;
}

.nvx-cta-banner__kicker {
  margin-bottom: var(--nvx-space-2);
  color: var(--nvx-accent-muted);
  font-size: var(--nvx-type-kicker);
  font-weight: 600;
  letter-spacing: var(--nvx-track-kicker);
  text-transform: uppercase;
}

.nvx-cta-banner__title {
  margin-bottom: var(--nvx-space-3);
  color: var(--nvx-light);
}

.nvx-cta-banner__sub {
  margin-bottom: var(--nvx-space-4);
  color: var(--nvx-surface-soft);
}

.nvx-cta-banner__actions {
  justify-content: center;
}

#nvx-footer-cta {
  font-weight: 600;
  letter-spacing: var(--nvx-track-button);
  text-transform: uppercase;
}

.nvx-dr-rivera-kicker {
  margin-bottom: var(--nvx-space-2);
}

@media (max-width: 45em) {

  .nvx-strategy-page > .nvx-brand-hero {
    min-height: calc(var(--nvx-space-12) * 3);
    padding-inline: var(--nvx-gutter-inner);
  }
}
CSS;
}

/** Enqueue the closure after theme and plugin styles. */
function nvx_external_visual_closure_enqueue(): void {
	$version = defined( 'NVX_THEME_VERSION' ) ? NVX_THEME_VERSION : null;

	wp_register_style( 'nvx-external-visual-closure', false, array(), $version );
	wp_enqueue_style( 'nvx-external-visual-closure' );
	wp_add_inline_style( 'nvx-external-visual-closure', nvx_external_visual_closure_css() );
}
add_action( 'wp_enqueue_scripts', 'nvx_external_visual_closure_enqueue', 1000 );
