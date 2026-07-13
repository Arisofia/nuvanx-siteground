<?php
/**
 * Plugin Name: NUVANX · Valoracion Native HubSpot Form
 * Description: HubSpot frame embed on /madrid/valoracion/ with dataLayer conversion events.
 * Version: 2026.07.11.1
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('NVX_VALORACION_HS_FRAME_PORTAL_ID')) {
    define('NVX_VALORACION_HS_FRAME_PORTAL_ID', '147416356');
}
if (!defined('NVX_VALORACION_HS_FRAME_FORM_ID')) {
    define('NVX_VALORACION_HS_FRAME_FORM_ID', '5042522a-0bc5-4381-ac3e-5aee8649b69c');
}
if (!defined('NVX_VALORACION_HS_FRAME_REGION')) {
    define('NVX_VALORACION_HS_FRAME_REGION', 'eu1');
}

function nvx_valoracion_native_hubspot_is_target_page(): bool
{
    return is_page(2636) || is_page('valoracion');
}

function nvx_valoracion_native_hubspot_mount_markup(): string
{
    $portal_id = esc_attr(NVX_VALORACION_HS_FRAME_PORTAL_ID);
    $form_id = esc_attr(NVX_VALORACION_HS_FRAME_FORM_ID);
    $region = esc_attr(NVX_VALORACION_HS_FRAME_REGION);
    $portal_script = esc_url('https://js-eu1.hsforms.net/forms/embed/' . NVX_VALORACION_HS_FRAME_PORTAL_ID . '.js');
    $disclaimer = '<div class="nvx-form-privacy-disclaimer" style="font-size: 12px; margin-top: 15px; color: var(--nvx-text-muted, #666); text-align: left;">Al facilitar tus datos aceptas la <a href="/politica-privacidad/" style="text-decoration: underline; color: inherit;">Política de privacidad</a>.</div>';

    return '<script src="' . $portal_script . '" defer></script>'
        . '<div class="hs-form-frame" data-region="' . $region . '" data-form-id="' . $form_id . '" data-portal-id="' . $portal_id . '"></div>'
        . $disclaimer;
}

function nvx_valoracion_native_hubspot_ensure_mount_script(string $html): string
{
    if (stripos($html, 'id="nvx-hubspot-native-form"') === false
        && stripos($html, "id='nvx-hubspot-native-form'") === false
    ) {
        return $html;
    }

    if (preg_match('/forms\/embed\/' . preg_quote(NVX_VALORACION_HS_FRAME_PORTAL_ID, '/') . '\.js/i', $html)) {
        return $html;
    }

    $portal_script = esc_url('https://js-eu1.hsforms.net/forms/embed/' . NVX_VALORACION_HS_FRAME_PORTAL_ID . '.js');
    $script_tag = '<script src="' . $portal_script . '" defer></script>';

    $replaced = preg_replace(
        '/(<div[^>]*id=["\']nvx-hubspot-native-form["\'][^>]*>)(\s*<div class="hs-form-frame")/is',
        '$1' . $script_tag . '$2',
        $html,
        1
    );

    if (is_string($replaced) && $replaced !== $html) {
        return $replaced;
    }

    $mount = nvx_valoracion_native_hubspot_mount_markup();
    $replaced = preg_replace(
        '/(<div[^>]*id=["\']nvx-hubspot-native-form["\'][^>]*>)\s*<\/div>/is',
        '$1' . $mount . '</div>',
        $html,
        1
    );

    return is_string($replaced) ? $replaced : $html;
}

add_action('template_redirect', function () {
    if (!nvx_valoracion_native_hubspot_is_target_page()) {
        return;
    }

    ob_start('nvx_valoracion_native_hubspot_ensure_mount_script');
}, 1);



add_action('wp_head', function () {
    if (!nvx_valoracion_native_hubspot_is_target_page()) {
        return;
    }
    ?>
    <!-- NVX_VALORACION_NATIVE_HUBSPOT_FORM_CSS -->
    <style id="nvx-valoracion-native-hubspot-form-css">
      body.page-id-2636 #nvx-hubspot-form.nvx-valoracion-form-section {
        scroll-margin-top: 96px;
      }

      body.page-id-2636 #nvx-hubspot-native-form {
        margin: 24px 0 18px;
      }

      body.page-id-2636 #nvx-hubspot-native-form .hs-form-frame {
        display: block;
        width: 100%;
        min-height: 120px;
      }

      body.page-id-2636 #nvx-hubspot-native-form .hs-form-frame iframe {
        width: 100% !important;
        max-width: 100%;
        border: 0;
        display: block;
      }
    </style>
    <!-- /NVX_VALORACION_NATIVE_HUBSPOT_FORM_CSS -->
    <?php
}, 90);