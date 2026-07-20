<?php
/**
 * Canonical runtime contract for colors, icon presentation and numbering.
 *
 * This module closes legacy markup at the final render boundary while the
 * reusable visual rules remain token-driven. It does not introduce page IDs,
 * private palettes or page-specific type scales.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return a controlled inline SVG for assets that previously depended on
 * color-locked files or unresolved symbol sprites.
 *
 * All stroke icons share viewBox 32, currentColor and the CSS stroke token.
 *
 * @param string $name  Icon key.
 * @param string $class Space-separated classes.
 */
function nvx_visual_icon_svg( string $name, string $class = 'nvx-icon nvx-icon--md' ): string {
	$icons = array(
		'target'    => '<circle cx="16" cy="16" r="12"/><circle cx="16" cy="16" r="6"/><path d="m12.5 16 2.2 2.2 4.8-5"/>',
		'recovery'  => '<path d="M6 20c3-7 6-10 10-10s7 3 10 10"/><path d="M9 12c2-1.5 4-2 7-2s5 .5 7 2"/><circle cx="16" cy="22" r="2.5"/>',
		'patient'   => '<circle cx="16" cy="10" r="5"/><path d="M7 28c1-7 4-10 9-10s8 3 9 10"/>',
		'technique' => '<path d="M6 24 21 7l4 4-15 17H6v-4Z"/><path d="m18 10 4 4"/>',
		'plan'      => '<rect x="7" y="5" width="18" height="22" rx="2"/><path d="M11 11h10M11 16h10M11 21h6"/>',
		'harmony'   => '<path d="M5 18c3-6 7-9 11-9s8 3 11 9"/><path d="M7 22c4 3 7 4 9 4s5-1 9-4"/><circle cx="16" cy="16" r="2"/>',
		'location'  => '<path d="M16 29s9-8 9-16a9 9 0 1 0-18 0c0 8 9 16 9 16Z"/><circle cx="16" cy="13" r="3"/>',
		'phone'     => '<path d="M8 5h5l2 6-3 2c2 4 4 6 8 8l2-3 6 2v5c0 2-2 3-4 3C14 27 5 18 4 8c0-2 2-3 4-3Z"/>',
		'clock'     => '<circle cx="16" cy="16" r="12"/><path d="M16 9v8l5 3"/>',
		'doctor'    => '<circle cx="16" cy="9" r="4"/><path d="M8 28v-5c0-5 3-8 8-8s8 3 8 8v5M12 20v4M20 20v4"/>',
	);

	$key  = array_key_exists( $name, $icons ) ? $name : 'target';
	$body = $icons[ $key ];

	return '<svg class="' . esc_attr( $class ) . '" viewBox="0 0 32 32" fill="none" aria-hidden="true" focusable="false">' . $body . '</svg>';
}

/**
 * Late visual rules. Attached to the last canonical stylesheet so they replace
 * legacy private sizing without another physical CSS file or load-order branch.
 */
function nvx_visual_system_css(): string {
	return <<<'CSS'
/* NUVANX canonical icon, color, type-role and numbering closure. */
:where(
  .nvx-icon,
  .nvx-laser-icon,
  .nvx-aes-icon,
  .nvx-endolift-step__icon,
  .nvx-benefit-icon,
  .icon-whatsapp
) {
  display: inline-block;
  width: var(--nvx-icon-md);
  height: var(--nvx-icon-md);
  color: var(--nvx-accent-muted);
  flex: 0 0 auto;
  vertical-align: middle;
}
.nvx-icon--xs { width: var(--nvx-icon-xs); height: var(--nvx-icon-xs); }
.nvx-icon--sm { width: var(--nvx-icon-sm); height: var(--nvx-icon-sm); }
.nvx-icon--md { width: var(--nvx-icon-md); height: var(--nvx-icon-md); }
.nvx-icon--lg { width: var(--nvx-icon-lg); height: var(--nvx-icon-lg); }
:where(
  .nvx-icon,
  .nvx-laser-icon,
  .nvx-aes-icon,
  .nvx-endolift-step__icon,
  .nvx-benefit-icon
) :is(path, circle, rect, line, polyline, polygon, ellipse) {
  fill: none;
  stroke: currentColor;
  stroke-width: var(--nvx-icon-stroke);
  vector-effect: non-scaling-stroke;
}
.icon-whatsapp,
.icon-whatsapp path {
  width: var(--nvx-icon-sm);
  height: var(--nvx-icon-sm);
  fill: currentColor;
  stroke: none;
}

.nvx-value__icon,
.nvx-method-col__icon {
  width: var(--nvx-icon-frame);
  height: var(--nvx-icon-frame);
  color: var(--nvx-accent-muted);
}
.nvx-value__icon svg { width: var(--nvx-icon-sm); height: var(--nvx-icon-sm); }
.nvx-method-col__icon svg { width: var(--nvx-icon-md); height: var(--nvx-icon-md); }
.nvx-benefit-icon { width: var(--nvx-icon-lg); height: var(--nvx-icon-lg); }
.nvx-clinic-card__data .nvx-icon { margin-inline-end: var(--nvx-space-1); color: var(--nvx-accent-muted); }

.nvx-index-number,
.nvx-endolift-step__n,
.nvx-laser-platform__n,
.nvx-aes-card__n,
.nvx-co2-timeline__n,
.nvx-treatment-process__step::before {
  font-family: var(--nvx-sans);
  font-size: var(--nvx-index-number-size);
  font-weight: var(--nvx-index-number-weight);
  line-height: 1;
  letter-spacing: var(--nvx-index-number-track);
  text-transform: uppercase;
  color: var(--nvx-accent-muted);
}
.nvx-value { position: relative; }
.nvx-value__number {
  position: absolute;
  inset-block-start: var(--nvx-space-3);
  inset-inline-end: var(--nvx-space-3);
}

.nvx-main :is(.nvx-prose, .nvx-page__content, .entry-content, .nvx-copy) ol:not([class]) {
  list-style: decimal;
  padding-inline-start: var(--nvx-space-3);
}
.nvx-main :is(.nvx-prose, .nvx-page__content, .entry-content, .nvx-copy) ol:not([class]) > li::marker {
  font-family: var(--nvx-sans);
  font-weight: 600;
  color: var(--nvx-accent-muted);
}

.nvx-benefits__grid { gap: var(--nvx-space-4); }
.nvx-benefit-item {
  gap: var(--nvx-space-2);
  padding: var(--nvx-space-3);
  background: var(--nvx-surface-soft);
  border: var(--nvx-border-hairline) solid var(--nvx-color-line);
  border-radius: var(--nvx-radius-card);
}
.nvx-benefit-text {
  font-family: var(--nvx-sans);
  font-size: var(--nvx-type-small);
  font-weight: 600;
  line-height: var(--nvx-lh-caption);
  letter-spacing: var(--nvx-track-button);
  text-transform: uppercase;
  color: var(--nvx-ink);
}

.nvx-hs-native-box {
  border-color: var(--nvx-color-line);
  background: var(--nvx-light);
}
.nvx-hubspot-form-section .hs-input,
.nvx-hs-lead-form .hs-input,
.nvx-hubspot-form-section input.hs-input,
.nvx-hubspot-form-section input[type="text"],
.nvx-hubspot-form-section input[type="email"],
.nvx-hubspot-form-section input[type="tel"],
.nvx-hubspot-form-section input[type="number"],
.nvx-hubspot-form-section select.hs-input,
.nvx-hubspot-form-section textarea.hs-input {
  border-color: var(--nvx-color-line);
  background: var(--nvx-surface-base);
  color: var(--nvx-ink);
  font-family: var(--nvx-sans);
}
.nvx-hubspot-form-section .nvx-title,
.nvx-hubspot-form-section h2.nvx-title,
.nvx-form-stage .nvx-title {
  font-family: var(--nvx-serif);
  font-size: var(--nvx-type-h2);
  line-height: var(--nvx-lh-h2);
  letter-spacing: var(--nvx-track-h2);
}

.nvx-brand-hero__copy :is(.nvx-brand-kicker, .nvx-eyebrow, .nvx-kicker, .nvx-brand-meta),
.nvx-editorial-hero__copy :is(.nvx-brand-kicker, .nvx-eyebrow, .nvx-kicker, .nvx-brand-meta),
.nvx-page-hero__copy :is(.nvx-brand-kicker, .nvx-eyebrow, .nvx-kicker, .nvx-brand-meta),
.nvx-hero__copy :is(.nvx-brand-kicker, .nvx-eyebrow, .nvx-kicker, .nvx-brand-meta) {
  color: var(--nvx-text-on-dark-72);
}

.nvx-cta-banner {
  padding-block: var(--nvx-pad-section-tight);
  background: var(--nvx-ink);
  color: var(--nvx-light);
  text-align: center;
}
.nvx-cta-banner__inner {
  width: var(--nvx-shell);
  max-width: 100%;
  margin-inline: auto;
  padding-inline: var(--nvx-gutter-inner);
}
.nvx-cta-banner__kicker {
  margin: 0 0 var(--nvx-margin-kicker);
  font-family: var(--nvx-sans);
  font-size: var(--nvx-type-kicker);
  font-weight: 600;
  line-height: var(--nvx-lh-kicker);
  letter-spacing: var(--nvx-track-kicker);
  text-transform: uppercase;
  color: var(--nvx-text-on-dark-72);
}
.nvx-cta-banner__title {
  max-width: 22ch;
  margin: 0 auto var(--nvx-margin-h2);
  font-family: var(--nvx-serif);
  font-size: var(--nvx-type-h2);
  font-weight: 400;
  line-height: var(--nvx-lh-h2);
  letter-spacing: var(--nvx-track-h2);
  color: var(--nvx-light);
}
.nvx-cta-banner__sub {
  max-width: var(--nvx-measure-lead);
  margin: 0 auto var(--nvx-margin-lead);
  font-family: var(--nvx-sans);
  font-size: var(--nvx-type-lead);
  line-height: var(--nvx-lh-lead);
  color: var(--nvx-text-on-dark-82);
}
.nvx-cta-banner__actions { justify-content: center; }
CSS;
}

/** Add the closure after the last canonical theme stylesheet. */
function nvx_visual_system_enqueue_css(): void {
	$handle = wp_style_is( 'nvx-home', 'enqueued' ) ? 'nvx-home' : 'nvx-components';
	wp_add_inline_style( $handle, nvx_visual_system_css() );
}
add_action( 'wp_enqueue_scripts', 'nvx_visual_system_enqueue_css', 20 );

/**
 * Normalize legacy HTML to the canonical visual contract.
 *
 * @param string $html Complete public document.
 */
function nvx_visual_system_normalize_html( string $html ): string {
	$benefit_icons = array(
		'resultados-definitivos' => 'target',
		'recuperacion-rapida'    => 'recovery',
		'paciente-despierto'     => 'patient',
		'sin-bisturi'             => 'technique',
		'solo-una-vez'            => 'plan',
		'efecto-natural'          => 'harmony',
	);

	$html = (string) preg_replace_callback(
		'/<img\b[^>]*src=["\'][^"\']*\/assets\/images\/benefits\/([a-z0-9-]+)\.svg["\'][^>]*>/iu',
		static function ( array $match ) use ( $benefit_icons ): string {
			$key = $benefit_icons[ $match[1] ] ?? 'target';
			return nvx_visual_icon_svg( $key, 'nvx-benefit-icon nvx-icon nvx-icon--lg' );
		},
		$html
	);

	$html = (string) preg_replace_callback(
		'/<svg\b[^>]*class=["\'][^"\']*\bnvx-icon\b[^"\']*["\'][^>]*>\s*<use\s+href=["\']#icon-(location|phone|clock|doctor)["\']\s*\/?>(?:<\/use>)?\s*<\/svg>/iu',
		static function ( array $match ): string {
			return nvx_visual_icon_svg( strtolower( $match[1] ), 'nvx-icon nvx-icon--xs' );
		},
		$html
	);

	foreach ( array( 'nvx-laser-icon', 'nvx-aes-icon', 'nvx-endolift-step__icon' ) as $legacy_class ) {
		$html = str_replace(
			'class="' . $legacy_class . '"',
			'class="' . $legacy_class . ' nvx-icon nvx-icon--md"',
			$html
		);
	}

	$html = (string) preg_replace_callback(
		'/<h3([^>]*class=["\'][^"\']*\bnvx-value__title\b[^"\']*["\'][^>]*)>\s*([1-9])\.\s*([^<]+)<\/h3>/iu',
		static function ( array $match ): string {
			$number = str_pad( $match[2], 2, '0', STR_PAD_LEFT );
			return '<span class="nvx-index-number nvx-value__number" aria-hidden="true">' . esc_html( $number ) . '</span><h3' . $match[1] . '>' . esc_html( trim( $match[3] ) ) . '</h3>';
		},
		$html
	);

	$html = (string) preg_replace_callback(
		'/<section\b(?=[^>]*\bid=["\']nvx-site-closing-cta["\'])[^>]*>[\s\S]*?<\/section>/iu',
		static function ( array $match ): string {
			return (string) preg_replace( '/\sstyle=["\'][^"\']*["\']/iu', '', $match[0] );
		},
		$html,
		1
	);

	return $html;
}

/** Start a late public-document buffer after page modules have registered. */
function nvx_visual_system_start_buffer(): void {
	if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}
	ob_start( 'nvx_visual_system_normalize_html' );
}
add_action( 'template_redirect', 'nvx_visual_system_start_buffer', 9999 );
