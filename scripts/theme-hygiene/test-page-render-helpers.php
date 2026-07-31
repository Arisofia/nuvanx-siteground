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

nvx_page_helper_assert(
    nvx_page_extract_brand_hero_media('<main>' . $figure . '</main>') === $figure,
    'Figure hero media extraction changed.'
);
nvx_page_helper_assert(
    nvx_page_extract_brand_hero_media('<main>' . $division . '</main>') === $division,
    'Div hero media extraction changed.'
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
    nvx_page_render_brand_wrapper('<p>plain</p>', '<section>new</section>') === '<section>new</section>',
    'Empty fallback must not create a wrapper.'
);
nvx_page_helper_assert(
    nvx_page_render_brand_wrapper('<p>plain</p>', '<section>new</section>', 'nvx-brand-page nvx-brand-page--laser')
        === '<div class="nvx-brand-page nvx-brand-page--laser"><section>new</section></div>',
    'Fallback brand wrapper changed.'
);

echo "Page render helper contract passed.\n";
