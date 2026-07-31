<?php
declare(strict_types=1);

define('ABSPATH', __DIR__ . '/');

function esc_attr(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

require_once dirname(__DIR__, 2) . '/wp-content/themes/nuvanx-medical/inc/nvx-page-render-helpers.php';

function nvx_page_helper_assert(bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, $message . "\n");
        exit(1);
    }
}

$figure = '<figure class="nvx-brand-hero__media"><img src="figure.jpg" alt=""></figure>';
$division = '<div class="nvx-brand-hero__media"><img src="division.jpg" alt=""></div>';
$nestedDivision = '<div class="nvx-brand-hero__media"><div class="frame"><img src="nested.jpg" alt=""></div></div>';

nvx_page_helper_assert(
    nvx_page_extract_brand_hero_media('<main>' . $figure . '</main>') === $figure,
    'Figure hero media extraction changed.'
);
nvx_page_helper_assert(
    nvx_page_extract_brand_hero_media('<main>' . $division . '</main>') === $division,
    'Div hero media extraction changed.'
);
nvx_page_helper_assert(
    nvx_page_extract_brand_hero_media('<main>' . $nestedDivision . '</main>') === $nestedDivision,
    'Nested div hero media extraction changed.'
);
nvx_page_helper_assert(
    nvx_page_extract_brand_hero_media('<main>' . $division . $figure . '</main>') === $figure,
    'Canonical figure media must keep precedence over the legacy div slot.'
);
nvx_page_helper_assert(
    nvx_page_extract_brand_hero_media('<main>No media</main>') === '',
    'Missing hero media must return an empty string.'
);

$existing = '<div class="nvx-brand-page nvx-brand-page--existing" data-page="one"><p>old</p></div>';
nvx_page_helper_assert(
    nvx_page_render_brand_wrapper($existing, '<section>new</section>')
        === '<div class="nvx-brand-page nvx-brand-page--existing" data-page="one"><section>new</section></div>',
    'Existing brand wrapper was not preserved.'
);
nvx_page_helper_assert(
    nvx_page_render_brand_wrapper('<p>plain</p>', '<section>new</section>')
        === '<div class="nvx-brand-page"><section>new</section></div>',
    'Default brand wrapper changed.'
);
nvx_page_helper_assert(
    nvx_page_render_brand_wrapper('<p>plain</p>', '<section>new</section>', 'nvx-brand-page nvx-brand-page--laser')
        === '<div class="nvx-brand-page nvx-brand-page--laser"><section>new</section></div>',
    'Explicit fallback brand wrapper changed.'
);

$fallbackContracts = array(
    'nvx-endolift-page.php' => 'nvx-brand-page nvx-brand-page--endolift',
    'nvx-endolaser-page.php' => 'nvx-brand-page nvx-brand-page--endolaser',
    'nvx-co2-page.php' => 'nvx-brand-page nvx-brand-page--co2',
);
foreach ($fallbackContracts as $filename => $fallbackClass) {
    $source = (string) file_get_contents(
        dirname(__DIR__, 2) . '/wp-content/themes/nuvanx-medical/inc/' . $filename
    );
    nvx_page_helper_assert(
        str_contains($source, "'" . $fallbackClass . "'"),
        $filename . ' must preserve its page-specific fallback wrapper.'
    );
}

$sectionOpen = nvx_page_brand_section_open_markup(
    'nvx-example',
    'example-title',
    'nvx-example-grid',
    array('id' => 'example-section')
);
nvx_page_helper_assert(
    $sectionOpen === '<section class="nvx-brand-section nvx-example" aria-labelledby="example-title" id="example-section"><div class="nvx-shell nvx-brand-section__inner nvx-example-grid">',
    'Canonical section opening markup changed.'
);
nvx_page_helper_assert(
    nvx_page_brand_section_open_markup('', 'plain-title')
        === '<section class="nvx-brand-section" aria-labelledby="plain-title"><div class="nvx-shell nvx-brand-section__inner">',
    'Canonical section opening without modifiers changed.'
);
foreach (array('data bad', 'onclick', 'class', 'aria-labelledby') as $attribute) {
    nvx_page_helper_assert(
        strpos(
            nvx_page_brand_section_open_markup('', 'plain-title', '', array($attribute => 'unsafe')),
            $attribute . '="unsafe"'
        ) === false,
        'Disallowed section attribute was not discarded: ' . $attribute
    );
}
nvx_page_helper_assert(
    nvx_page_brand_section_heading_markup('Kicker', 'heading-id', 'Heading')
        === '<p class="nvx-brand-kicker">Kicker</p><h2 id="heading-id" class="nvx-brand-title">Heading</h2>',
    'Canonical section heading markup changed.'
);

$solutionsModule = (string) file_get_contents(
    dirname(__DIR__, 2) . '/wp-content/themes/nuvanx-medical/inc/nvx-solutions-page.php'
);
$pageShell = (string) file_get_contents(
    dirname(__DIR__, 2) . '/wp-content/themes/nuvanx-medical/template-parts/content/nvx-page-shell.php'
);
$functions = (string) file_get_contents(
    dirname(__DIR__, 2) . '/wp-content/themes/nuvanx-medical/functions.php'
);
nvx_page_helper_assert(
    str_contains($solutionsModule, "'soluciones-medicas' === \$slug")
        && str_contains($solutionsModule, 'NUVANX_STRATEGY_PAGE:solutions')
        && str_contains($solutionsModule, "get_template_part( 'template-parts/content/nvx-soluciones-medicas-github' )"),
    'Solutions route must resolve by slug/marker and render the canonical template.'
);
nvx_page_helper_assert(
    str_contains($pageShell, 'nvx_content_is_solutions_page( $content )'),
    'Page shell must treat the solutions hub as managed editorial markup.'
);
nvx_page_helper_assert(
    1 === substr_count($functions, "require_once get_template_directory() . '/inc/nvx-solutions-page.php';"),
    'Theme bootstrap must load the solutions page module exactly once.'
);

echo "Page render helper contract passed.\n";
