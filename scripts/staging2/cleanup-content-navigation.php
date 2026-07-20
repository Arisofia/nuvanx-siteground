<?php
/**
 * Staging2-only WordPress content/navigation cleanup.
 *
 * Usage:
 *   wp eval-file /path/to/cleanup-content-navigation.php
 *   wp eval-file /path/to/cleanup-content-navigation.php --apply
 */



if (!defined('ABSPATH')) {
    fwrite(STDERR, "ERROR: run through wp eval-file.\n");
    exit(1);
}

$arguments = isset($args) && is_array($args) ? $args : [];
$apply     = '1' === getenv( 'NVX_CONTENT_CLEANUP_APPLY' ) || in_array( 'apply', $arguments, true );
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

$jsonldHelper = get_template_directory() . '/inc/nvx-jsonld-content.php';
$p0Guard = get_template_directory() . '/inc/nvx-p0-publication-guard.php';

foreach ([$jsonldHelper, $p0Guard] as $requiredFile) {
    if (!is_readable($requiredFile)) {
        fwrite(STDERR, "ERROR: missing theme helper {$requiredFile}\n");
        exit(1);
    }
    require_once $requiredFile;
}

function nvx_cleanup_endolift_rf_conflation(string $html): string
{
    $replacements = [
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

/**
 * Remove unverified price conditions and pressure language from persisted CMS
 * copy. The theme keeps a final public guard too, but this helper makes the
 * approved wording survive exports, plugins and future theme changes.
 */
function nvx_cleanup_public_copy(string $value): string
{
    $replacements = [
        '/\bvaloraci[oó]n\s+m[eé]dica\s+gratuita\b/iu' => 'valoración médica',
        '/\bvaloraci[oó]n\s+gratuita\b/iu' => 'valoración médica',
        '/\bvaloraci[oó]n\s+gratis\b/iu' => 'valoración médica',
        '/\bconsulta\s+(?:m[eé]dica\s+)?gratuita\b/iu' => 'consulta médica',
        '/\bconsulta\s+gratis\b/iu' => 'consulta médica',
        '/\bpresupuesto\s+personalizado\b/iu' => 'presupuesto individualizado tras la valoración médica',
        '/\bsin\s+compromiso\b/iu' => 'sin obligación de continuar con un tratamiento',
    ];

    foreach ($replacements as $pattern => $replacement) {
        $value = preg_replace($pattern, $replacement, $value) ?? $value;
    }

    return $value;
}

function nvx_cleanup_is_exion_record(int $postId, string $slug): bool
{
    return $postId === 2906 || in_array(
        trim($slug, '/'),
        ['exion-btl', 'exion-face', 'exion-body', 'exion-fractional'],
        true
    );
}

/**
 * Legal pages use the shell title as their primary heading. Their legacy CMS
 * bodies also contain an H1, which produces two H1s in the rendered document.
 * Keep the body heading and its inline content, but make it a secondary H2.
 */
function nvx_cleanup_demote_legal_content_h1s(
    DOMDocument $document,
    DOMXPath $xpath,
    DOMElement $root,
    int $postId,
    string $slug
): void {
    $legalIds = [3, 20, 577];
    $legalSlugs = [
        'aviso-legal',
        'politica-privacidad',
        'politica-de-privacidad',
        'politica-de-cookies-ue',
        'mas-informacion-sobre-las-cookies',
    ];

    if (!in_array($postId, $legalIds, true) && !in_array(trim($slug, '/'), $legalSlugs, true)) {
        return;
    }

    $headings = $xpath->query('.//h1', $root);
    if (false === $headings) {
        return;
    }

    $toDemote = [];
    foreach ($headings as $heading) {
        if ($heading instanceof DOMElement && $heading->parentNode) {
            $toDemote[] = $heading;
        }
    }

    foreach ($toDemote as $heading) {
        $replacement = $document->createElement('h2');
        foreach ($heading->attributes as $attribute) {
            $replacement->setAttribute($attribute->name, $attribute->value);
        }
        while ($heading->firstChild) {
            $replacement->appendChild($heading->firstChild);
        }
        $heading->parentNode->replaceChild($replacement, $heading);
    }
}

function nvx_cleanup_funnel_dom(DOMDocument $document, DOMXPath $xpath, DOMElement $root, int $postId, string $slug): void
{
    $isContact = $postId === 14 || in_array($slug, ['contacto', 'contact'], true);
    $isAssessment = $postId === 2636 || $slug === 'valoracion';

    if (!$isContact && !$isAssessment) {
        return;
    }

    $remove = [];

    foreach ($xpath->query('.//script[contains(@src,"hsforms.net") or contains(@src,"hubspot")]', $root) ?: [] as $node) {
        $remove[] = $node;
    }
    foreach ($xpath->query('.//iframe[contains(@src,"hsforms") or contains(@src,"hubspot")]', $root) ?: [] as $node) {
        $remove[] = $node;
    }
    foreach ($xpath->query(
        './/*[contains(concat(" ",normalize-space(@class)," ")," hs-form-frame ") or contains(concat(" ",normalize-space(@class)," ")," hbspt-form ")]',
        $root
    ) ?: [] as $node) {
        $remove[] = $node;
    }

    if ($isContact) {
        foreach ($xpath->query(
            './/*[@id="nvx-contacto-hubspot-form"] | .//section[contains(concat(" ",normalize-space(@class)," ")," nvx-section--contact-form ")]',
            $root
        ) ?: [] as $node) {
            $remove[] = $node;
        }
    }

    $seen = [];
    foreach ($remove as $node) {
        if (!$node instanceof DOMNode || !$node->parentNode) {
            continue;
        }
        $hash = spl_object_hash($node);
        if (isset($seen[$hash])) {
            continue;
        }
        $seen[$hash] = true;
        $node->parentNode->removeChild($node);
    }

    if ($isAssessment) {
        $mounts = [];
        foreach ($xpath->query('.//*[@id="nvx-hubspot-native-form"]', $root) ?: [] as $mount) {
            $mounts[] = $mount;
        }

        foreach ($mounts as $index => $mount) {
            if (!$mount instanceof DOMElement || !$mount->parentNode) {
                continue;
            }
            if ($index > 0) {
                $mount->parentNode->removeChild($mount);
                continue;
            }
            while ($mount->firstChild) {
                $mount->removeChild($mount->firstChild);
            }
        }
    }
}

function nvx_cleanup_html(string $html, int $postId, string $slug): string
{
    $html = str_replace('https://nuvanx.com', 'https://staging2.nuvanx.com', $html);
    $html = str_replace('características induales', 'características individuales', $html);
    $html = str_replace(
        [
            'EXILITET',
            'Exilitet',
            'Tu mejor versión empieza aquí.',
            'Tu mejor versión empieza aquí',
            'enfoque médico premium',
            'Medicina estética en Goya con enfoque médico premium',
            '282869501',
        ],
        [
            'EXILITE™',
            'EXILITE™',
            'Reserva 15–30 min de valoración médica.',
            'Reserva 15–30 min de valoración médica',
            'misma dirección médica que Chamberí',
            'Medicina estética láser en Goya–Barrio de Salamanca (CS20073)',
            '282858861',
        ],
        $html
    );

    $html = nvx_cleanup_public_copy($html);
    $html = preg_replace('/\bSolicitar\.(?=\s|<|$)/u', 'Solicitar valoración médica', $html) ?? $html;
    $html = preg_replace('/<(ul|ol)\b[^>]*>\s*<\/\1>/iu', '', $html) ?? $html;
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
        $root = $document->getElementById('nvx-cleanup-root');

        if ($root instanceof DOMElement) {
            foreach ($xpath->query('.//*[@style]', $root) ?: [] as $node) {
                if ($node instanceof DOMElement) {
                    $node->removeAttribute('style');
                }
            }

            foreach ($xpath->query('.//script[contains(translate(@type,"JSONLD","jsonld"),"ld+json")]', $root) ?: [] as $node) {
                if (!$node instanceof DOMElement || !$node->parentNode) {
                    continue;
                }
                $body = (string) $node->textContent;
                if (function_exists('nvx_jsonld_is_schema_org_payload') && !nvx_jsonld_is_schema_org_payload($body)) {
                    continue;
                }
                $node->parentNode->removeChild($node);
            }

            nvx_cleanup_demote_legal_content_h1s($document, $xpath, $root, $postId, $slug);
            nvx_cleanup_funnel_dom($document, $xpath, $root, $postId, $slug);

            $rebuilt = '';
            foreach ($root->childNodes as $child) {
                $rebuilt .= $document->saveHTML($child);
            }
            $html = $rebuilt;
        }
    }

    libxml_clear_errors();
    libxml_use_internal_errors($previous);

    if (nvx_cleanup_is_exion_record($postId, $slug) && function_exists('nvx_p0_sanitize_exion_content')) {
        $html = nvx_p0_sanitize_exion_content($html);
    }

    return $html;
}

function nvx_cleanup_excerpt(string $excerpt, string $slug): string
{
    $excerpt = str_replace('https://nuvanx.com', 'https://staging2.nuvanx.com', $excerpt);
    $excerpt = str_replace('características induales', 'características individuales', $excerpt);
    $excerpt = str_replace('282869501', '282858861', $excerpt);
    $excerpt = nvx_cleanup_public_copy($excerpt);
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
$hubspotResiduePattern = '/hsforms\.net|hs-form-frame|hbspt-form|nvx-contacto-hubspot-form/iu';

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

    $after['post_title'] = nvx_cleanup_public_copy((string) $after['post_title']);

    if ($post->post_type !== 'nav_menu_item') {
        $after['post_content'] = nvx_cleanup_html((string) $post->post_content, (int) $post->ID, (string) $post->post_name);
        $after['post_excerpt'] = nvx_cleanup_excerpt((string) $post->post_excerpt, (string) $post->post_name);
    }

    $afterSearchable = implode("\n", [
        $after['post_title'],
        $after['post_excerpt'],
        wp_strip_all_tags((string) $after['post_content']),
    ]);
    $flags = [];

    if (preg_match($virtualPattern, $afterSearchable) === 1) {
        $flags[] = 'UNAPPROVED_VIRTUAL_CONSULTATION_COPY';
    }
    if (preg_match($endoliftRfPattern, $afterSearchable) === 1) {
        $flags[] = 'ENDOLIFT_RADIOFREQUENCY_CONFLATION';
    }
    if (
        ((int) $post->ID === 14 || in_array((string) $post->post_name, ['contacto', 'contact'], true))
        && preg_match($hubspotResiduePattern, (string) $after['post_content']) === 1
    ) {
        $flags[] = 'CONTACT_HUBSPOT_RESIDUE';
    }
    if (
        nvx_cleanup_is_exion_record((int) $post->ID, (string) $post->post_name)
        && preg_match(nvx_p0_exion_price_pattern(), html_entity_decode(wp_strip_all_tags((string) $after['post_content']), ENT_QUOTES | ENT_HTML5, 'UTF-8')) === 1
    ) {
        $flags[] = 'EXION_EXPLICIT_PRICE';
    }
    if (strpos($afterSearchable, '282869501') !== false) {
        $flags[] = 'OBSOLETE_CRISTINA_CREDENTIAL';
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

    if (
        $before['post_title'] !== $after['post_title']
        || $before['post_excerpt'] !== $after['post_excerpt']
        || $before['post_content'] !== $after['post_content']
    ) {
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
