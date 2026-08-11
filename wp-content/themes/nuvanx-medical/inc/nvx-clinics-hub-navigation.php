<?php
/**
 * Clinics hub in-page navigation contract.
 *
 * The hub itself contains the canonical `#clinica-chamberi` and
 * `#clinica-goya` sections. Its top navigation should move within the hub;
 * full clinic-page URLs remain owned by the "Ficha de la sede" CTAs inside
 * each clinic section.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Restore the two in-page destinations only inside the clinics navigation.
 *
 * @param string $content Rendered page content.
 * @return string
 */
function nvxClinicsHubRestoreJumpNavigation( string $content ): string {
    if ( is_admin() || ! function_exists( 'nvxIsClinicsHub' ) || ! nvxIsClinicsHub() || false === strpos( $content, 'nvx-clinics-nav' ) ) {
        return $content;
    }

    return (string) preg_replace_callback(
        '/<nav\b[^>]*class=["\'][^"\']*\bnvx-clinics-nav\b[^"\']*["\'][^>]*>[\s\S]*?<\/nav>/iu',
        static function ( array $matches ): string {
            $nav = (string) $matches[0];
            $nav = (string) preg_replace(
                '/(<a\b[^>]*\bhref=)["\'][^"\']*["\']([^>]*>\s*Chamber[ií]\s*<\/a>)/iu',
                '$1"#clinica-chamberi"$2',
                $nav,
                1
            );
            $nav = (string) preg_replace(
                '/(<a\b[^>]*\bhref=)["\'][^"\']*["\']([^>]*>\s*Salamanca[–-]Goya\s*<\/a>)/iu',
                '$1"#clinica-goya"$2',
                $nav,
                1
            );
            return $nav;
        },
        $content,
        1
    );
}
add_filter( 'the_content', 'nvxClinicsHubRestoreJumpNavigation', 31 );
