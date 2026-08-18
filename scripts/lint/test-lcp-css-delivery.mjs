import assert from 'node:assert/strict';
import fs from 'node:fs';

const conversionEvents = fs.readFileSync(
  'wp-content/themes/nuvanx-medical/assets/js/nvx-conversion-events.js',
  'utf8',
);
const functionsPhp = fs.readFileSync('wp-content/themes/nuvanx-medical/functions.php', 'utf8');
const governance = fs.readFileSync(
  'wp-content/themes/nuvanx-medical/inc/nvx-native-style-governance.php',
  'utf8',
);
const integrations = fs.readFileSync(
  'wp-content/themes/nuvanx-medical/inc/nvx-integrations.php',
  'utf8',
);
const components = fs.readFileSync(
  'wp-content/themes/nuvanx-medical/assets/css/nvx-components.css',
  'utf8',
);

assert.match(
  functionsPhp,
  /display=swap/,
  'Google Fonts request must keep font-display=swap',
);
assert.match(
  functionsPhp,
  /Public pages inline the local stack/,
  'theme stylesheet registration must document the public inline delivery',
);
assert.match(
  governance,
  /function nvx_theme_critical_stylesheet_files/,
  'the critical bundle must have a route-aware source manifest',
);
assert.match(
  governance,
  /assets\/css\/nvx-site-layout\.css/,
  'layout CSS must be part of the inline bundle',
);
assert.match(
  governance,
  /assets\/css\/nvx-components\.css/,
  'component CSS must be part of the inline bundle',
);
assert.match(
  governance,
  /assets\/css\/nvx-patterns-editorial\.css/,
  'interior hero CSS must be reserved before deferred assets',
);
assert.match(
  governance,
  /assets\/css\/nvx-accessibility-governance\.css/,
  'accessibility CSS must be part of the inline bundle',
);
assert.match(
  governance,
  /assets\/css\/nvx-home-v3\.css/,
  'home CSS must be included only on the front page',
);
assert.match(
  governance,
  /assets\/css\/nvx-posts\.css/,
  'blog CSS must be included on editorial routes',
);
assert.match(
  governance,
  /assets\/css\/nvx-soluciones-medicas\.css/,
  'solutions CSS must be included on its route',
);
assert.match(
  governance,
  /assets\/css\/nvx-cases-holding\.css/,
  'patient-cases CSS must be included on its route',
);
assert.match(
  governance,
  /function nvx_theme_public_delivers_inline_styles/,
  'public requests must skip the local CSS file chain',
);
assert.match(
  governance,
  /themes\/nuvanx-medical\/assets\/css/,
  'leftover theme CSS file links must be dropped even if a plugin re-enqueues them',
);
assert.match(
  governance,
  /function nvx_theme_defer_local_script_tags/,
  'theme JS must stay deferred on the public document',
);
assert.match(
  functionsPhp,
  /nvx_theme_public_delivers_inline_styles/,
  'the public stylesheet enqueue must skip file URLs when the inline bundle is active',
);
assert.match(
  governance,
  /function nvx_theme_inline_critical_style_foundation/,
  'the stylesheet bundle must be emitted inline',
);
assert.match(
  governance,
  /function nvx_theme_dequeue_late_local_styles/,
  'late template styles must not recreate local stylesheet links',
);
assert.match(
  governance,
  /function nvx_theme_nonblocking_google_fonts/,
  'Google Fonts stylesheet must not block first paint',
);
assert.match(
  integrations,
  /function nvx_theme_is_klaviyo_asset/,
  'Klaviyo assets must be identified on all public routes',
);
assert.match(
  integrations,
  /function nvx_dequeue_public_klaviyo_onsite/,
  'Klaviyo Onsite must be removed globally from the public frontend',
);
assert.match(
  integrations,
  /function nvx_theme_defer_auxiliary_script_tags/,
  'Complianz and Joinchat scripts must be deferred',
);
assert.match(
  integrations,
  /function nvx_theme_defer_auxiliary_style_tags/,
  'Complianz and Joinchat styles must be non-blocking',
);
assert.match(
  integrations,
  /function nvx_theme_demote_auxiliary_styles/,
  'Joinchat CSS must be forced to print media before it is printed',
);
assert.ok(
  integrations.includes("preg_replace( '/\\smedia="),
  'Joinchat tag rewrite must strip a leftover media=all attribute',
);
assert.match(
  integrations,
  /preg_replace\( '\/\^<script\\b\/i', '<script defer', \$tag, 1 \)/,
  'script deferral must only alter the opening tag, not inline JavaScript content',
);
assert.doesNotMatch(
  integrations,
  /return str_replace\( '<script', '<script defer', \$tag \)/,
  'script deferral must not rewrite comparisons such as <scripts.length inside inline code',
);
assert.match(
  components,
  /\.nvx-brand-microcopy--dark/,
  'dark hero microcopy must meet the requested AA contrast token',
);
assert.match(
  governance,
  /home-hero geometry reservation/,
  'front-page critical CSS must reserve home hero geometry',
);
assert.match(
  governance,
  /interior-hero first paint/,
  'interior brand heroes must reserve the dark stage in the inline bundle',
);
assert.match(
  governance,
  /\.nvx-brand-hero__copy\{[^}]*padding-block:var\(--nvx-space-8\)/,
  'interior hero copy padding must be reserved in the inline first-paint snippet',
);
assert.match(
  governance,
  /\.nvx-header\{min-height:var\(--nvx-header-height-mobile\)/,
  'header height must be reserved in the inline first-paint snippet',
);

const baseCss = fs.readFileSync(
  'wp-content/themes/nuvanx-medical/assets/css/nvx-base.css',
  'utf8',
);
const layoutCss = fs.readFileSync(
  'wp-content/themes/nuvanx-medical/assets/css/nvx-site-layout.css',
  'utf8',
);
const fontsCss = fs.readFileSync(
  'wp-content/themes/nuvanx-medical/assets/css/nvx-fonts.css',
  'utf8',
);
assert.match(
  baseCss,
  /\.nvx-brand-hero__copy\s*\{[\s\S]*padding-block:\s*var\(--nvx-space-8\)/,
  'base CSS must reserve interior hero padding before deferred stylesheets',
);
assert.match(
  baseCss,
  /\.nvx-brand-page > \.nvx-brand-section/,
  'base CSS must reserve brand-section padding on first paint',
);
assert.match(
  baseCss,
  /\.nvx-logo__img/,
  'base CSS must reserve logo geometry on first paint',
);
assert.doesNotMatch(
  layoutCss,
  /transition:\s*padding-block/,
  'section padding must not animate after first paint',
);
assert.doesNotMatch(
  layoutCss,
  /transition:\s*padding-inline/,
  'shell padding must not animate after first paint',
);
assert.match(
  fontsCss,
  /Playfair Display Fallback/,
  'Playfair must have a metric-matched fallback to limit font-swap CLS',
);
assert.match(
  fontsCss,
  /Manrope Fallback/,
  'Manrope must have a metric-matched fallback to limit font-swap CLS',
);
assert.match(
  integrations,
  /klaviyojs/,
  'the official plugin handle klaviyojs must be dequeued',
);

assert.match(
  conversionEvents,
  /AW-18236597403\/qut3CLWflOAcEJvJ8fdD/,
  'phone/WhatsApp clicks must send the official Ads click conversion',
);
assert.match(
  conversionEvents,
  /joinchat/,
  'Joinchat widget clicks must count as WhatsApp conversions',
);

const helpers = fs.readFileSync(
  'wp-content/themes/nuvanx-medical/inc/nvx-page-render-helpers.php',
  'utf8',
);
const presentation = fs.readFileSync(
  'wp-content/themes/nuvanx-medical/inc/nvx-content-presentation.php',
  'utf8',
);
const mainJs = fs.readFileSync(
  'wp-content/themes/nuvanx-medical/assets/js/nvx-main.js',
  'utf8',
);
assert.match(
  helpers,
  /function nvx_content_enhance_img_tag_attrs/,
  'content images must receive srcset/sizes from theme or upload derivatives',
);
assert.match(
  helpers,
  /function nvx_image_dimensions_for_url/,
  'content images must resolve explicit width and height without a network fetch',
);
assert.match(
  helpers,
  /'Sala-Nuvanx'\s*=>\s*array\(\s*1086,\s*1448\s*\)/,
  'Chamberí waiting-room intrinsic size must be catalogued',
);
assert.match(
  helpers,
  /'nuvanx-medicina-2'\s*=>\s*array\(\s*1220,\s*960\s*\)/,
  'Chamberí façade intrinsic size must be catalogued',
);
assert.match(
  helpers,
  /'Endolift-ISO9001-Laser'\s*=>\s*array\(\s*850,\s*470\s*\)/,
  'Endolift device intrinsic size must be catalogued',
);
assert.match(
  helpers,
  /'SmartLipo-for-Laserlipolysis-DEKA-1'\s*=>\s*array\(\s*447,\s*800\s*\)/,
  'SmartLipo PNG intrinsic size must be catalogued',
);
assert.match(
  helpers,
  /function nvx_theme_responsive_candidates/,
  'theme-hosted WebP derivatives must be discoverable by stem',
);
assert.match(
  helpers,
  /function nvx_lazy_map_embed_markup/,
  'Google Maps must not load until the user asks',
);
assert.match(
  helpers,
  /function nvx_rewrite_eager_maps_iframes/,
  'CMS and leftover Maps iframes must be rewritten to click-to-load',
);
assert.doesNotMatch(
  fs.readFileSync('wp-content/themes/nuvanx-medical/templates/page-sede.php', 'utf8'),
  /<iframe[^>]+maps\.google/,
  'the sede template must not emit an eager Maps iframe',
);
assert.doesNotMatch(
  fs.readFileSync('wp-content/themes/nuvanx-medical/front-page.php', 'utf8'),
  /<iframe[^>]+maps\.google/,
  'the home template must not emit an eager Maps iframe',
);
assert.match(
  presentation,
  /nvx_content_enhance_img_tag_attrs/,
  'body image normalization must attach responsive attributes',
);
assert.match(
  mainJs,
  /data-nvx-map-src/,
  'nvx-main must bind click-to-load maps',
);
assert.ok(
  fs.existsSync(
    'wp-content/themes/nuvanx-medical/assets/images/responsive/SmartLipo-for-Laserlipolysis-DEKA-1-447.webp',
  ),
  'SmartLipo must ship as WebP instead of the 329 KiB PNG',
);
assert.ok(
  fs.existsSync(
    'wp-content/themes/nuvanx-medical/assets/images/responsive/Sala-Nuvanx-480.webp',
  ),
  'Chamberí waiting-room photo must have a 480w WebP',
);

console.log('LCP_CSS_DELIVERY=PASS');
