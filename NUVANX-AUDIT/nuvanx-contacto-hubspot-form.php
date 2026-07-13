<?php
/**
 * Plugin Name: NUVANX · Contacto Native HubSpot Form
 * Description: HubSpot frame embed on Contact page with dataLayer conversion events.
 * Version: 2026.07.11.2
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('NVX_CONTACTO_HS_FRAME_PORTAL_ID')) {
    define('NVX_CONTACTO_HS_FRAME_PORTAL_ID', '147416356');
}
if (!defined('NVX_CONTACTO_HS_FRAME_FORM_ID')) {
    define('NVX_CONTACTO_HS_FRAME_FORM_ID', '5042522a-0bc5-4381-ac3e-5aee8649b69c');
}
if (!defined('NVX_CONTACTO_HS_FRAME_REGION')) {
    define('NVX_CONTACTO_HS_FRAME_REGION', 'eu1');
}

function nvx_contacto_native_hubspot_mount_markup(): string
{
    $portal_id = esc_attr(NVX_CONTACTO_HS_FRAME_PORTAL_ID);
    $form_id = esc_attr(NVX_CONTACTO_HS_FRAME_FORM_ID);
    $region = esc_attr(NVX_CONTACTO_HS_FRAME_REGION);
    $portal_script = esc_url('https://js-eu1.hsforms.net/forms/embed/' . NVX_CONTACTO_HS_FRAME_PORTAL_ID . '.js');
    $disclaimer = '<div class="nvx-form-privacy-disclaimer" style="font-size: 12px; margin-top: 15px; color: var(--nvx-text-muted, #666); text-align: left;">Al facilitar tus datos aceptas la <a href="/politica-privacidad/" style="text-decoration: underline; color: inherit;">Política de privacidad</a>.</div>';

    return '<script src="' . $portal_script . '" defer></script>'
        . '<div class="hs-form-frame" data-region="' . $region . '" data-form-id="' . $form_id . '" data-portal-id="' . $portal_id . '"></div>'
        . $disclaimer;
}

function nvx_contacto_native_hubspot_ensure_mount_script(string $html): string
{
    if (stripos($html, 'id="nvx-hubspot-native-form"') === false
        && stripos($html, "id='nvx-hubspot-native-form'") === false
    ) {
        return $html;
    }

    if (preg_match('/forms\/embed\/' . preg_quote(NVX_CONTACTO_HS_FRAME_PORTAL_ID, '/') . '\.js/i', $html)) {
        return $html;
    }

    $portal_script = esc_url('https://js-eu1.hsforms.net/forms/embed/' . NVX_CONTACTO_HS_FRAME_PORTAL_ID . '.js');
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

    $mount = nvx_contacto_native_hubspot_mount_markup();
    $replaced = preg_replace(
        '/(<div[^>]*id=["\']nvx-hubspot-native-form["\'][^>]*>)\s*<\/div>/is',
        '$1' . $mount . '</div>',
        $html,
        1
    );

    return is_string($replaced) ? $replaced : $html;
}

add_action('template_redirect', function () {
    if (!is_page(14) && !is_page('contacto')) {
        return;
    }

    ob_start('nvx_contacto_native_hubspot_ensure_mount_script');
}, 1);



add_action('wp_head', function () {
    if (!is_page(14) && !is_page('contacto')) {
        return;
    }
    ?>
    <!-- NVX_CONTACTO_NATIVE_HUBSPOT_FORM_CSS -->
    <style id="nvx-contacto-native-hubspot-form-css">
      body.page-id-14 a.nvx-btn[href="#nvx-hubspot-form"],
      body.page-id-14 a[href="#nvx-hubspot-form"].nvx-btn--dark {
        display: none !important;
      }

      body.page-id-14 #nvx-hubspot-form.nvx-hs-native-section {
        width: 100%;
        margin: 28px 0 18px;
      }

      body.page-id-14 .nvx-hs-native-box {
        padding: 28px;
        background: #F7F1E8;
        border: 1px solid rgba(43,41,38,.12);
        box-shadow: 0 18px 44px rgba(23,23,23,.055);
        color: #2B2926;
      }

      body.page-id-14 .nvx-hs-lead-kicker,
      body.page-id-14 .nvx-hs-source {
        margin: 0 0 14px;
        color: #8B8176;
        font-size: 10px;
        line-height: 1.35;
        font-weight: 500;
        letter-spacing: .18em;
        text-transform: uppercase;
      }

      body.page-id-14 .nvx-hs-native-box h2 {
        margin: 0 0 14px;
        color: #171717;
        font-family: Georgia, "Times New Roman", serif;
        font-size: clamp(28px, 3.5vw, 44px);
        line-height: 1.05;
        font-weight: 400;
        letter-spacing: -.035em;
      }

      body.page-id-14 .nvx-hs-lead-copy,
      body.page-id-14 .nvx-hs-legal {
        max-width: 760px;
        margin: 0 0 22px;
        color: #5F5851;
        font-size: 14px;
        line-height: 1.72;
      }

      body.page-id-14 #nvx-hubspot-native-form {
        margin: 24px 0 18px;
      }

      body.page-id-14 #nvx-hubspot-native-form .hs-form-frame {
        display: block;
        width: 100%;
        min-height: 120px;
      }

      body.page-id-14 #nvx-hubspot-native-form .hs-form-frame iframe {
        width: 100% !important;
        max-width: 100%;
        border: 0;
        display: block;
      }

      @media(max-width:760px) {
        body.page-id-14 .nvx-hs-native-box {
          padding: 24px;
        }
      }
    </style>
    <!-- /NVX_CONTACTO_NATIVE_HUBSPOT_FORM_CSS -->
    <?php
}, 90);