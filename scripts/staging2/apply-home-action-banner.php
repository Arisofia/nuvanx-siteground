<?php
/**
 * Staging2-only migration for the Home post-values action banner.
 *
 * Usage:
 *   wp eval-file scripts/staging2/apply-home-action-banner.php
 *   wp eval-file scripts/staging2/apply-home-action-banner.php --apply
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    fwrite(STDERR, "ERROR: run through wp eval-file.\n");
    exit(1);
}

/**
 * Staging2 migration configuration (override via constants before eval-file if needed).
 */
if (!defined('NVX_STAGING2_EXPECTED_URL')) {
    define('NVX_STAGING2_EXPECTED_URL', 'https://staging2.nuvanx.com');
}
if (!defined('NVX_STAGING2_EXPECTED_THEME')) {
    define('NVX_STAGING2_EXPECTED_THEME', 'nuvanx-medical');
}
if (!defined('NVX_STAGING2_EXPECTED_FRONT_PAGE_ID')) {
    define('NVX_STAGING2_EXPECTED_FRONT_PAGE_ID', 9);
}

/**
 * Canonical post-values action banner markup for the home page.
 *
 * Kept as a reusable builder so copy/CTA changes stay in one place
 * (theme partials may call this later if needed).
 */
function nvx_staging2_home_action_banner_html(): string
{
    $valoracion = function_exists('home_url')
        ? home_url('/madrid/valoracion/')
        : '/madrid/valoracion/';
    $contacto = function_exists('home_url')
        ? home_url('/contacto/')
        : '/contacto/';

    $valoracion = esc_url($valoracion);
    $contacto = esc_url($contacto);

    return <<<HTML
<section class="nvx-home-action-banner" data-nvx-action-banner="post-values" aria-labelledby="nvx-home-action-banner-title">
  <div class="nvx-home-action-banner__copy">
    <p class="nvx-brand-kicker">Valoración médica</p>
    <h2 id="nvx-home-action-banner-title" class="nvx-home-action-banner__title">Recupera la armonía de tu piel</h2>
    <p class="nvx-home-action-banner__text">Agenda una valoración médica personalizada y presencial en nuestras clínicas de Chamberí o Goya · Barrio Salamanca.</p>
  </div>
  <div class="nvx-home-action-banner__actions">
    <a class="nvx-button nvx-button--light" href="{$valoracion}">Reservar valoración gratuita</a>
    <a class="nvx-home-action-banner__link" href="{$contacto}">Resolver dudas</a>
  </div>
</section>
HTML;
}

$argsList = isset($args) && is_array($args) ? $args : [];
$apply = in_array('--apply', $argsList, true);
$expectedUrl = rtrim((string) NVX_STAGING2_EXPECTED_URL, '/');
$expectedTheme = (string) NVX_STAGING2_EXPECTED_THEME;
$expectedFrontPageId = (int) NVX_STAGING2_EXPECTED_FRONT_PAGE_ID;

$siteUrl = rtrim((string) get_option('siteurl'), '/');
$homeUrl = rtrim((string) get_option('home'), '/');
$frontPageId = (int) get_option('page_on_front');

if ($siteUrl !== $expectedUrl || $homeUrl !== $expectedUrl) {
    fwrite(STDERR, "ERROR: staging2 URL guard failed (expected {$expectedUrl}).\n");
    exit(1);
}

if (wp_get_theme()->get_stylesheet() !== $expectedTheme) {
    fwrite(STDERR, "ERROR: expected active theme {$expectedTheme}.\n");
    exit(1);
}

if ($frontPageId !== $expectedFrontPageId) {
    fwrite(STDERR, "ERROR: expected static front page ID {$expectedFrontPageId}.\n");
    exit(1);
}

$post = get_post($frontPageId);
if (!$post instanceof WP_Post) {
    fwrite(STDERR, "ERROR: front page not found.\n");
    exit(1);
}

$original = (string) $post->post_content;
$previous = libxml_use_internal_errors(true);
$document = new DOMDocument('1.0', 'UTF-8');
$wrapped = '<!DOCTYPE html><html><body><div id="nvx-migration-root">' . $original . '</div></body></html>';
$loaded = $document->loadHTML('<?xml encoding="utf-8" ?>' . $wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

if (!$loaded) {
    fwrite(STDERR, "ERROR: unable to parse front-page HTML.\n");
    exit(1);
}

$xpath = new DOMXPath($document);
$root = $document->getElementById('nvx-migration-root');
if (!$root instanceof DOMElement) {
    fwrite(STDERR, "ERROR: migration root unavailable.\n");
    exit(1);
}

$valueContainer = $xpath->query(
    '//*[contains(concat(" ", normalize-space(@class), " "), " nvx-values ") '
    . 'or contains(concat(" ", normalize-space(@class), " "), " nvx-value-grid ") '
    . 'or contains(concat(" ", normalize-space(@class), " "), " nvx-benefits-grid ")]'
)->item(0);

if (!$valueContainer instanceof DOMElement) {
    fwrite(STDERR, "ERROR: no canonical values container found on front page.\n");
    exit(1);
}

$unwrappedLinks = 0;
foreach ($xpath->query('.//a', $valueContainer) ?: [] as $link) {
    if (!$link instanceof DOMElement || !$link->parentNode) {
        continue;
    }
    while ($link->firstChild) {
        $link->parentNode->insertBefore($link->firstChild, $link);
    }
    $link->parentNode->removeChild($link);
    $unwrappedLinks++;
}

$bannerHtml = nvx_staging2_home_action_banner_html();
$fragmentDocument = new DOMDocument('1.0', 'UTF-8');
$fragmentDocument->loadHTML('<?xml encoding="utf-8" ?><body>' . $bannerHtml . '</body>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
$newBanner = null;
foreach ($fragmentDocument->getElementsByTagName('section') as $section) {
    $newBanner = $document->importNode($section, true);
    break;
}
if (!$newBanner instanceof DOMElement) {
    fwrite(STDERR, "ERROR: unable to build action banner.\n");
    exit(1);
}

$existingBanner = $xpath->query('//*[@data-nvx-action-banner="post-values"]')->item(0);
$bannerAction = 'inserted';
if ($existingBanner instanceof DOMElement && $existingBanner->parentNode) {
    $existingBanner->parentNode->replaceChild($newBanner, $existingBanner);
    $bannerAction = 'updated';
} else {
    $parent = $valueContainer->parentNode;
    if (!$parent) {
        fwrite(STDERR, "ERROR: values container has no parent.\n");
        exit(1);
    }
    if ($valueContainer->nextSibling) {
        $parent->insertBefore($newBanner, $valueContainer->nextSibling);
    } else {
        $parent->appendChild($newBanner);
    }
}

$rebuilt = '';
foreach ($root->childNodes as $child) {
    $rebuilt .= $document->saveHTML($child);
}

libxml_clear_errors();
libxml_use_internal_errors($previous);

$summary = [
    'mode' => $apply ? 'apply' : 'dry-run',
    'expected_url' => $expectedUrl,
    'expected_theme' => $expectedTheme,
    'front_page_id' => $frontPageId,
    'links_unwrapped_inside_values' => $unwrappedLinks,
    'banner_action' => $bannerAction,
    'videoconsulta_added' => false,
    'content_changed' => $rebuilt !== $original,
];

if (!$apply) {
    echo wp_json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
    echo "DRY_RUN_ONLY: rerun with --apply after review.\n";
    exit(0);
}

$homeDirectory = getenv('HOME');
if (!is_string($homeDirectory) || trim($homeDirectory) === '') {
    fwrite(STDERR, "ERROR: HOME unavailable for protected backup.\n");
    exit(1);
}

$backupDirectory = rtrim($homeDirectory, '/') . '/nuvanx-staging2-content-backups';
if (!is_dir($backupDirectory) && !wp_mkdir_p($backupDirectory)) {
    fwrite(STDERR, "ERROR: unable to create backup directory.\n");
    exit(1);
}
@chmod($backupDirectory, 0700);
$backupPath = $backupDirectory . '/home-before-action-banner-' . gmdate('Ymd-His') . '.html';
if (file_put_contents($backupPath, $original, LOCK_EX) === false) {
    fwrite(STDERR, "ERROR: unable to write protected backup.\n");
    exit(1);
}
@chmod($backupPath, 0600);

$result = wp_update_post([
    'ID' => $frontPageId,
    'post_content' => $rebuilt,
], true);

if (is_wp_error($result)) {
    fwrite(STDERR, 'ERROR: ' . $result->get_error_message() . "\n");
    exit(1);
}

clean_post_cache($frontPageId);
$summary['backup_path'] = $backupPath;
$summary['applied'] = true;
echo wp_json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
echo "STAGING2_HOME_ACTION_BANNER_APPLIED\n";
