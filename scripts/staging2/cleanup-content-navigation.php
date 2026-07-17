<?php
/**
 * Staging2-only WordPress content/navigation cleanup.
 *
 * Usage from the staging2 WordPress root:
 *   wp eval-file /path/to/cleanup-content-navigation.php
 *   wp eval-file /path/to/cleanup-content-navigation.php --apply
 *
 * Dry-run is the default. The script refuses to run outside staging2.
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    fwrite(STDERR, "ERROR: run through wp eval-file.\n");
    exit(1);
}

$arguments = isset($args) && is_array($args) ? $args : [];
$apply = in_array('--apply', $arguments, true);
$expectedUrl = 'https://staging2.nuvanx.com';
$siteUrl = rtrim((string) get_option('siteurl'), '/');
$homeUrl = rtrim((string) get_option('home'), '/');

if ($siteUrl !== $expectedUrl || $homeUrl !== $expectedUrl) {
    fwrite(STDERR, "ERROR: staging guard failed. siteurl={$siteUrl}; home={$homeUrl}\n");
    exit(1);
}

if (wp_get_theme()->get_stylesheet() !== 'nuvanx-medical') {
    fwrite(STDERR, "ERROR: expected active theme nuvanx-medical.\n");
    exit(1);
}

// Shared Schema.org JSON-LD strip (same logic as runtime the_content filter).
$jsonldHelper = get_template_directory() . '/inc/nvx-jsonld-content.php';
if (!is_readable($jsonldHelper)) {
    fwrite(STDERR, "ERROR: missing theme helper {$jsonldHelper}\n");
    exit(1);
}
require_once $jsonldHelper;

/**
 * Deterministic clinical fix: Endolift® is laser-assisted subdermal treatment,
 * not monopolar radiofrequency. Replaces only known conflation phrases.
 */
function nvx_cleanup_endolift_rf_conflation(string $html): string
{
    $replacements = [
        // Card body seen on Clínicas hub under Endolift title.
        '/(Endolift®?\s*)Radiofrecuencia\s+monopolar\s+para\s+firmeza\s+sin\s+cirug[ií]a/iu'
            => '$1Técnica láser subdérmica para firmeza facial, indicada tras valoración médica',
        '/Firmeza\s+Endolift®?\s+Radiofrecuencia\s+monopolar\s+para\s+firmeza\s+sin\s+cirug[ií]a/iu'
            => 'Endolift®: técnica láser subdérmica para firmeza facial, indicada tras valoración médica',
        '/Endolift®?\s+(?:es|como|mediante)\s+(?:una\s+)?radiofrecuencia\s+monopolar/iu'
            => 'Endolift® es una técnica láser subdérmica',
        '/define\s+Endolift®?\s+como\s+radiofrecuencia\s+monopolar/iu'
            => 'describe Endolift® como técnica láser subdérmica',
    ];

    foreach ($replacements as $pattern => $replacement) {
        $html = preg_replace($pattern, $replacement, $html) ?? $html;
    }

    return $html;
}

/** @return string */
function nvx_cleanup_html(string $html): string
{
    $html = str_replace('https://nuvanx.com', 'https://staging2.nuvanx.com', $html);
    $html = str_replace('características induales', 'características individuales', $html);
    // Brand / copy hygiene (legacy CMS strings).
    $html = str_replace(
        [
            'EXILITET',
            'Exilitet',
            'Tu mejor versión empieza aquí.',
            'Tu mejor versión empieza aquí',
            'enfoque médico premium',
            'Medicina estética en Goya con enfoque médico premium',
        ],
        [
            'EXILITE™',
            'EXILITE™',
            'Reserva 15–30 min de valoración médica.',
            'Reserva 15–30 min de valoración médica',
            'misma dirección médica que Chamberí',
            'Medicina estética láser en Goya–Barrio de Salamanca (CS20073)',
        ],
        $html
    );
    $html = preg_replace('/\bSolicitar\.(?=\s|<|$)/u', 'Solicitar valoración médica', $html) ?? $html;
    $html = preg_replace('/<(ul|ol)\b[^>]*>\s*<\/\1>/iu', '', $html) ?? $html;
    // Canonical schema is Yoast @graph only — shared helper (schema.org payloads only).
    $html = nvx_strip_embedded_jsonld_html($html);
    $html = nvx_cleanup_endolift_rf_conflation($html);

    if (trim($html) === '') {
        return $html;
    }

    $previous = libxml_use_internal_errors(true);
    $document = new DOMDocument('1.0', 'UTF-8');
    $wrapped = '<!DOCTYPE html><html><body><div id="nvx-cleanup-root">' . $html . '</div></body></html>';
    $loaded = $document->loadHTML('<?xml encoding="utf-8" ?>' . $wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

    if ($loaded) {
        $xpath = new DOMXPath($document);
        foreach ($xpath->query('//*[@style]') ?: [] as $node) {
            if ($node instanceof DOMElement) {
                $node->removeAttribute('style');
            }
        }
        // Second pass: DOM-held Schema.org ld+json (attribute order variants).
        foreach ($xpath->query('//script[contains(translate(@type,"JSONLD","jsonld"),"ld+json")]') ?: [] as $node) {
            if (!($node instanceof DOMElement) || !$node->parentNode) {
                continue;
            }
            $body = (string) $node->textContent;
            if (function_exists('nvx_jsonld_is_schema_org_payload') && !nvx_jsonld_is_schema_org_payload($body)) {
                continue;
            }
            $node->parentNode->removeChild($node);
        }

        $root = $document->getElementById('nvx-cleanup-root');
        if ($root instanceof DOMElement) {
            $rebuilt = '';
            foreach ($root->childNodes as $child) {
                $rebuilt .= $document->saveHTML($child);
            }
            $html = $rebuilt;
        }
    }

    libxml_clear_errors();
    libxml_use_internal_errors($previous);
    return $html;
}

/** @return string */
function nvx_cleanup_excerpt(string $excerpt, string $slug): string
{
    $excerpt = str_replace('https://nuvanx.com', 'https://staging2.nuvanx.com', $excerpt);
    $excerpt = str_replace('características induales', 'características individuales', $excerpt);
    $excerpt = preg_replace('/\bSolicitar\.(?=\s|<|$)/u', 'Solicitar valoración médica', $excerpt) ?? $excerpt;

    if (stripos($slug, 'goya') !== false || stripos($excerpt, 'goya') !== false) {
        $excerpt = preg_replace('/,\s*,+/u', ',', $excerpt) ?? $excerpt;
        $excerpt = preg_replace('/\s+,/u', ',', $excerpt) ?? $excerpt;
    }

    return $excerpt;
}

$records = get_posts([
    'post_type' => ['page', 'post', 'nav_menu_item'],
    'post_status' => ['publish', 'draft', 'private', 'pending'],
    'posts_per_page' => -1,
    'orderby' => 'ID',
    'order' => 'ASC',
]);

$changes = [];
$semanticFindings = [];
$virtualPattern = '/consulta\s+(virtual|online)|valoraci[oó]n\s+virtual|videoconsulta|consulta\s+por\s+v[ií]deo/iu';
$endoliftRfPattern = '/(?=.*\bEndolift\b)(?=.*radiofrecuencia\s+monopolar)/isu';

foreach ($records as $post) {
    if (!$post instanceof WP_Post) {
        continue;
    }

    $before = [
        'ID' => $post->ID,
        'post_type' => $post->post_type,
        'post_status' => $post->post_status,
        'post_name' => $post->post_name,
        'post_title' => $post->post_title,
        'post_excerpt' => $post->post_excerpt,
        'post_content' => $post->post_content,
    ];

    $after = $before;

    if ($post->post_type === 'nav_menu_item' && trim($post->post_title) === 'Clínica Salamanca-Goya') {
        $after['post_title'] = 'Clínica Goya · Barrio Salamanca';
    }

    if ($post->post_type !== 'nav_menu_item') {
        $after['post_content'] = nvx_cleanup_html((string) $post->post_content);
        $after['post_excerpt'] = nvx_cleanup_excerpt((string) $post->post_excerpt, (string) $post->post_name);
    }

    $searchable = implode("\n", [$post->post_title, $post->post_excerpt, wp_strip_all_tags($post->post_content)]);
    $flags = [];
    if (preg_match($virtualPattern, $searchable) === 1) {
        $flags[] = 'UNAPPROVED_VIRTUAL_CONSULTATION_COPY';
    }
    if (preg_match($endoliftRfPattern, $searchable) === 1) {
        $flags[] = 'ENDOLIFT_RADIOFREQUENCY_CONFLATION';
    }
    if ($flags !== []) {
        $semanticFindings[] = [
            'ID' => $post->ID,
            'post_type' => $post->post_type,
            'post_name' => $post->post_name,
            'post_title' => $post->post_title,
            'flags' => $flags,
        ];
    }

    if ($before['post_title'] !== $after['post_title']
        || $before['post_excerpt'] !== $after['post_excerpt']
        || $before['post_content'] !== $after['post_content']) {
        $changes[] = ['before' => $before, 'after' => $after];
    }
}

$summary = [
    'mode' => $apply ? 'apply' : 'dry-run',
    'siteurl' => $siteUrl,
    'home' => $homeUrl,
    'deterministic_change_count' => count($changes),
    'semantic_finding_count' => count($semanticFindings),
    'semantic_findings' => $semanticFindings,
    'changes' => array_map(static function (array $change): array {
        return [
            'ID' => $change['before']['ID'],
            'post_type' => $change['before']['post_type'],
            'post_name' => $change['before']['post_name'],
            'post_title_before' => $change['before']['post_title'],
            'post_title_after' => $change['after']['post_title'],
            'content_changed' => $change['before']['post_content'] !== $change['after']['post_content'],
            'excerpt_changed' => $change['before']['post_excerpt'] !== $change['after']['post_excerpt'],
        ];
    }, $changes),
];

if (!$apply) {
    echo wp_json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
    echo "DRY_RUN_ONLY: rerun with --apply after review.\n";
    exit(0);
}

$homeDirectory = getenv('HOME');
if (!is_string($homeDirectory) || trim($homeDirectory) === '') {
    fwrite(STDERR, "ERROR: HOME is unavailable for protected backup.\n");
    exit(1);
}

$timestamp = gmdate('Ymd-His');
$backupDirectory = rtrim($homeDirectory, '/') . '/nuvanx-staging2-content-backups';
if (!is_dir($backupDirectory) && !wp_mkdir_p($backupDirectory)) {
    fwrite(STDERR, "ERROR: unable to create backup directory.\n");
    exit(1);
}
@chmod($backupDirectory, 0700);

$backupPath = $backupDirectory . '/content-navigation-before-' . $timestamp . '.json';
$backupPayload = [
    'created_at_utc' => gmdate(DATE_ATOM),
    'siteurl' => $siteUrl,
    'records' => array_column($changes, 'before'),
    'semantic_findings' => $semanticFindings,
];

if (file_put_contents($backupPath, wp_json_encode($backupPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX) === false) {
    fwrite(STDERR, "ERROR: unable to write protected backup.\n");
    exit(1);
}
@chmod($backupPath, 0600);

foreach ($changes as $change) {
    $after = $change['after'];
    $result = wp_update_post([
        'ID' => $after['ID'],
        'post_title' => $after['post_title'],
        'post_excerpt' => $after['post_excerpt'],
        'post_content' => $after['post_content'],
    ], true);

    if (is_wp_error($result)) {
        fwrite(STDERR, 'ERROR updating post ' . $after['ID'] . ': ' . $result->get_error_message() . "\n");
        exit(1);
    }
}

clean_post_cache(0);

$summary['backup_path'] = $backupPath;
$summary['applied'] = true;
echo wp_json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
echo "STAGING2_CONTENT_CLEANUP_APPLIED\n";

if ($semanticFindings !== []) {
    echo "SEMANTIC_REVIEW_REQUIRED\n";
}
