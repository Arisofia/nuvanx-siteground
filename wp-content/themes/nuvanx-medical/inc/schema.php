<?php
defined( 'ABSPATH' ) || exit;

function nvx_add_schema_org(): void {
    if ( is_singular() ) {
        echo '<script type="application/ld+json">{"@context":"https://schema.org","@type":"MedicalBusiness"}</script>';
    }
}

add_action( 'wp_head', 'nvx_add_schema_org' );
