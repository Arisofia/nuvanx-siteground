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

/** Return the late CSS contract for external widgets and unresolved aliases. */
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
