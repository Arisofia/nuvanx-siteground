<?php
$files = [
    'inc/nvx-aesthetic-medicine-page.php' => [
        'func' => 'nvx_content_restructure_aesthetic_medicine_page',
        'check' => 'nvx_content_is_aesthetic_medicine_page',
        'owner' => 'nvx_aesthetic_medicine_page'
    ],
    'inc/nvx-btl-detail-pages.php' => [
        'func' => 'nvx_content_restructure_btl_detail_page',
        'check' => 'nvx_btl_detail_current_key',
        'owner' => 'nvx_btl_detail_page'
    ],
    'inc/nvx-co2-page.php' => [
        'func' => 'nvx_content_restructure_co2_page',
        'check' => 'nvx_content_is_co2_page',
        'owner' => 'nvx_co2_page'
    ],
    'inc/nvx-endolaser-page.php' => [
        'func' => 'nvx_content_restructure_endolaser_page',
        'check' => 'nvx_content_is_endolaser_page',
        'owner' => 'nvx_endolaser_page'
    ],
    'inc/nvx-equipo-page.php' => [
        'func' => 'nvx_content_restructure_equipo_page',
        'check' => 'nvx_content_is_equipo_page',
        'owner' => 'nvx_equipo_page'
    ],
    'inc/nvx-laser-medicine-page.php' => [
        'func' => 'nvx_content_restructure_laser_medicine_page',
        'check' => 'nvx_laser_is_hub_request',
        'owner' => 'nvx_laser_medicine_page'
    ],
    'inc/nvx-nosotros-page.php' => [
        'func' => 'nvx_content_restructure_nosotros_page',
        'check' => 'nvx_content_is_nosotros_page',
        'owner' => 'nvx_nosotros_page'
    ],
];

foreach ($files as $file => $data) {
    $path = 'C:/Users/IvónYamilethRiveraDe/public_html/wp-content/themes/nuvanx-medical/' . $file;
    if (!file_exists($path)) {
        echo "Missing: $path\n";
        continue;
    }
    
    $content = file_get_contents($path);
    
    // Find the function definition
    $search = "function " . $data['func'] . "( string \$content ): string {\n\tif ( ! " . $data['check'];
    $search2 = "function " . $data['func'] . "( string \$content ): string {\n\t\$key = " . $data['check']; // for btl_detail_pages
    
    $replacement = "add_filter( 'nvx_page_owner', function( \$owner ) {\n" .
                   "\tif ( ! empty( \$owner ) ) return \$owner;\n" .
                   "\tglobal \$post;\n" .
                   "\t\$content = \$post ? \$post->post_content : '';\n" .
                   "\tif ( function_exists('" . $data['check'] . "') && " . ($data['check'] === 'nvx_btl_detail_current_key' ? "null !== " . $data['check'] . "( \$content )" : ($data['check'] === 'nvx_laser_is_hub_request' ? $data['check'] . "()" : $data['check'] . "( \$content )")) . " ) {\n" .
                   "\t\treturn '" . $data['owner'] . "';\n" .
                   "\t}\n" .
                   "\treturn \$owner;\n" .
                   "});\n\n" .
                   "function " . $data['func'] . "( string \$content ): string {\n" .
                   "\t\$owner = function_exists( 'nvx_get_page_owner' ) ? nvx_get_page_owner() : null;\n" .
                   "\tif ( \$owner !== '" . $data['owner'] . "' ) {\n" .
                   "\t\treturn \$content;\n" .
                   "\t}";
    
    // We need a more robust regex if the exact string match fails
    $pattern = '/function\s+' . preg_quote($data['func'], '/') . '\s*\(\s*string\s+\$content\s*\)\s*:\s*string\s*\{.*?(?:return \$content;\s*\})/s';
    
    if (preg_match($pattern, $content, $matches)) {
        $content = str_replace($matches[0], $replacement, $content);
        file_put_contents($path, $content);
        echo "Updated $file\n";
    } else {
        echo "Pattern not found in $file\n";
    }
}
