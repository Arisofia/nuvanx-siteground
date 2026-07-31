<?php
declare(strict_types=1);

/**
 * Replace duplicated hero-media extraction and wrapper reconstruction blocks.
 */
function nvx_consolidate_page_renderer(string $path, string $fallbackClass = ''): void {
    $source = (string) file_get_contents($path);

    $mediaOffset = strpos($source, '$media = \'\';');
    if (false === $mediaOffset) {
        throw new RuntimeException("Hero media block not found in {$path}");
    }
    $mediaLineStart = strrpos(substr($source, 0, $mediaOffset), "\n");
    $mediaLineStart = false === $mediaLineStart ? 0 : $mediaLineStart + 1;

    $heroOffset = strpos($source, '$hero  =', $mediaOffset);
    if (false === $heroOffset) {
        throw new RuntimeException("Hero assembly not found in {$path}");
    }
    $heroLineStart = strrpos(substr($source, 0, $heroOffset), "\n");
    $heroLineStart = false === $heroLineStart ? $heroOffset : $heroLineStart + 1;

    $mediaReplacement = "\trequire_once __DIR__ . '/nvx-page-render-helpers.php';\n"
        . "\t\$media = nvx_page_extract_brand_hero_media( \$content );\n\n";
    $source = substr($source, 0, $mediaLineStart)
        . $mediaReplacement
        . substr($source, $heroLineStart);

    $bodyOffset = strpos($source, '$body =', $mediaLineStart);
    if (false === $bodyOffset) {
        throw new RuntimeException("Body assembly not found in {$path}");
    }
    $wrapperOffset = strpos($source, "if ( preg_match( '/(<div class=\"nvx-brand-page", $bodyOffset);
    if (false === $wrapperOffset) {
        throw new RuntimeException("Brand wrapper block not found in {$path}");
    }
    $wrapperLineStart = strrpos(substr($source, 0, $wrapperOffset), "\n");
    $wrapperLineStart = false === $wrapperLineStart ? $wrapperOffset : $wrapperLineStart + 1;

    $filterOffset = strpos($source, 'add_filter(', $wrapperOffset);
    if (false === $filterOffset) {
        throw new RuntimeException("Content filter registration not found in {$path}");
    }
    $functionClose = strrpos(substr($source, 0, $filterOffset), "\n}");
    if (false === $functionClose) {
        throw new RuntimeException("Renderer function close not found in {$path}");
    }

    $fallbackArgument = '' !== $fallbackClass
        ? ', ' . var_export($fallbackClass, true)
        : '';
    $wrapperReplacement = "\treturn nvx_page_render_brand_wrapper( \$content, \$hero . \$body{$fallbackArgument} );\n";
    $source = substr($source, 0, $wrapperLineStart)
        . $wrapperReplacement
        . substr($source, $functionClose);

    if (false === file_put_contents($path, $source)) {
        throw new RuntimeException("Unable to rewrite {$path}");
    }
}

$root = dirname(__DIR__, 2) . '/wp-content/themes/nuvanx-medical/inc';
$targets = array(
    'nvx-endolift-page.php' => '',
    'nvx-endolaser-page.php' => '',
    'nvx-co2-page.php' => '',
    'nvx-laser-medicine-page.php' => 'nvx-brand-page nvx-brand-page--laser',
);

foreach ($targets as $filename => $fallbackClass) {
    nvx_consolidate_page_renderer($root . '/' . $filename, $fallbackClass);
}

echo "Consolidated four canonical page renderers.\n";
