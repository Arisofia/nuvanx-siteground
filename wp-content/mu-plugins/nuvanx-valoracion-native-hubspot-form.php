<?php
/**
 * Plugin Name: NUVANX Valoración Native HubSpot Form
 * Description: Enforces one canonical HubSpot form on /madrid/valoracion/.
 * Version: 2026.07.31.4
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'NVX_VALORACION_HS_FRAME_PORTAL_ID' ) ) {
    define( 'NVX_VALORACION_HS_FRAME_PORTAL_ID', '147416356' );
}
if ( ! defined( 'NVX_VALORACION_HS_FRAME_FORM_ID' ) ) {
    define( 'NVX_VALORACION_HS_FRAME_FORM_ID', '5042522a-0bc5-4381-ac3e-5aee8649b69c' );
}
if ( ! defined( 'NVX_VALORACION_HS_FRAME_REGION' ) ) {
    define( 'NVX_VALORACION_HS_FRAME_REGION', 'eu1' );
}

function nvxValoracionNativeHubspotIsTargetPage(): bool {
    return is_page( 2636 ) || is_page( 'valoracion' );
}

function nvxValoracionNativeHubspotMountMarkup(): string {
    $portal_id   = preg_replace( '/\D+/', '', (string) NVX_VALORACION_HS_FRAME_PORTAL_ID );
    $form_id     = strtolower( trim( (string) NVX_VALORACION_HS_FRAME_FORM_ID ) );
    $region      = preg_replace( '/[^a-z0-9-]/i', '', (string) NVX_VALORACION_HS_FRAME_REGION );
    $target_id   = 'nvx-hubspot-v2-target';
    $status_id   = 'nvx-hubspot-v2-status';
    $privacy_url = esc_url( home_url( '/politica-privacidad/' ) );
    $contact_url = esc_url( home_url( '/contacto/' ) );

    if ( ! preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $form_id ) ) {
        return '<p class="nvx-form-status is-error" role="status">'
            . esc_html__( 'El formulario no está disponible temporalmente. Contacta con la clínica para solicitar tu valoración.', 'nuvanx-medical' )
            . '</p>';
    }

    $config = wp_json_encode(
        array(
            'region'         => $region,
            'portalId'       => $portal_id,
            'formId'         => $form_id,
            'target'         => '#' . $target_id,
            'locale'         => 'es',
            'formInstanceId' => 'nvx-valoracion-main',
        ),
        JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    );

    if ( ! is_string( $config ) ) {
        return '<p class="nvx-form-status is-error" role="status">'
            . esc_html__( 'El formulario no está disponible temporalmente. Contacta con la clínica para solicitar tu valoración.', 'nuvanx-medical' )
            . '</p>';
    }

    $runtime = <<<'JS'
<script>
(function () {
  'use strict';
  var config = __NVX_CONFIG__;
  var target = document.getElementById('__NVX_TARGET__');
  var status = document.getElementById('__NVX_STATUS__');
  var timeoutId = null;
  var observer = null;

  function setStatus(message, failed) {
    if (!status) return;
    status.textContent = message;
    status.hidden = false;
    status.classList.toggle('is-error', Boolean(failed));
  }

  function containsRenderedForm() {
    return Boolean(target && target.querySelector('iframe, form, .hs-form, .hbspt-form'));
  }

  function markReady(form) {
    window.clearTimeout(timeoutId);
    if (observer) observer.disconnect();
    if (target) target.dataset.nvxHubspotState = 'ready';
    if (status) status.hidden = true;
    var node = form && form[0] ? form[0] : form;
    if (node && typeof node.setAttribute === 'function') {
      node.setAttribute('data-nvx-valoracion-form', 'ready');
    }
  }

  function watchRenderedForm() {
    if (!target || typeof MutationObserver !== 'function') return;
    observer = new MutationObserver(function () {
      if (containsRenderedForm()) markReady(target.querySelector('form, iframe'));
    });
    observer.observe(target, { childList: true, subtree: true });
    if (containsRenderedForm()) markReady(target.querySelector('form, iframe'));
  }

  function mountForm() {
    if (!target || target.dataset.nvxHubspotState === 'ready') return;
    if (!(window.hbspt && window.hbspt.forms && typeof window.hbspt.forms.create === 'function')) {
      target.dataset.nvxHubspotState = 'sdk-missing';
      setStatus('No ha sido posible iniciar el formulario. Puedes solicitar tu valoración desde la página de contacto.', true);
      return;
    }

    target.dataset.nvxHubspotState = 'mounting';
    watchRenderedForm();
    try {
      window.hbspt.forms.create(Object.assign({}, config, {
        onFormReady: markReady,
        onFormSubmitted: function () {
          target.dataset.nvxHubspotState = 'submitted';
        }
      }));
      timeoutId = window.setTimeout(function () {
        if (containsRenderedForm()) {
          markReady(target.querySelector('form, iframe'));
          return;
        }
        target.dataset.nvxHubspotState = 'render-timeout';
        setStatus('El formulario está tardando más de lo esperado. Puedes solicitar tu valoración desde la página de contacto.', true);
      }, 20000);
    } catch (error) {
      target.dataset.nvxHubspotState = 'create-error';
      setStatus('No ha sido posible cargar el formulario. Puedes solicitar tu valoración desde la página de contacto.', true);
    }
  }

  function loadSdk() {
    if (!target || target.dataset.nvxHubspotState === 'ready') return;
    if (window.hbspt && window.hbspt.forms) {
      mountForm();
      return;
    }

    var existing = document.querySelector('script[data-nvx-hubspot-loader="valoracion"]');
    if (existing) {
      if (existing.dataset.nvxHubspotLoaded === 'true') {
        mountForm();
        return;
      }
      existing.addEventListener('load', function () {
        existing.dataset.nvxHubspotLoaded = 'true';
        mountForm();
      }, { once: true });
      existing.addEventListener('error', function () {
        target.dataset.nvxHubspotState = 'sdk-error';
        setStatus('No ha sido posible conectar con el formulario. Puedes solicitar tu valoración desde la página de contacto.', true);
      }, { once: true });
      return;
    }

    target.dataset.nvxHubspotState = 'sdk-loading';
    var loader = document.createElement('script');
    loader.src = 'https://js.hsforms.net/forms/embed/v2.js';
    loader.async = true;
    loader.charset = 'utf-8';
    loader.type = 'text/javascript';
    loader.dataset.category = 'functional';
    loader.dataset.nvxHubspotLoader = 'valoracion';
    loader.addEventListener('load', function () {
      loader.dataset.nvxHubspotLoaded = 'true';
      mountForm();
    }, { once: true });
    loader.addEventListener('error', function () {
      target.dataset.nvxHubspotState = 'sdk-error';
      setStatus('No ha sido posible conectar con el formulario. Puedes solicitar tu valoración desde la página de contacto.', true);
    }, { once: true });
    document.head.appendChild(loader);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', loadSdk, { once: true });
  } else {
    loadSdk();
  }
}());
</script>
JS;

    $runtime = str_replace(
        array( '__NVX_CONFIG__', '__NVX_TARGET__', '__NVX_STATUS__' ),
        array( $config, esc_js( $target_id ), esc_js( $status_id ) ),
        $runtime
    );

    return '<p id="' . esc_attr( $status_id ) . '" class="nvx-form-status" role="status">'
        . esc_html__( 'Cargando el formulario de valoración médica…', 'nuvanx-medical' )
        . '</p>'
        . '<div id="' . esc_attr( $target_id ) . '" class="nvx-hubspot-v2-target" data-nvx-hubspot-state="pending"></div>'
        . '<noscript><p class="nvx-form-status">'
        . esc_html__( 'Activa JavaScript para completar el formulario o utiliza nuestros canales de contacto.', 'nuvanx-medical' )
        . ' <a href="' . $contact_url . '">' . esc_html__( 'Ver contacto', 'nuvanx-medical' ) . '</a>.</p></noscript>'
        . $runtime
        . '<p class="nvx-copy nvx-hubspot-privacy">'
        . esc_html__( 'Al facilitar tus datos aceptas la ', 'nuvanx-medical' )
        . '<a class="nvx-text-link" href="' . $privacy_url . '">' . esc_html__( 'Política de privacidad', 'nuvanx-medical' ) . '</a>. '
        . esc_html__( 'La indicación definitiva se confirma siempre en valoración presencial.', 'nuvanx-medical' )
        . '</p>';
}

/** @return array{start:int,length:int}|null */
function nvxValoracionBalancedDivRange( string $html, int $open_offset ): ?array {
    if ( $open_offset < 0
        || ! preg_match( '/\G<div\b[^>]*>/i', $html, $opening, 0, $open_offset )
        || ! preg_match_all( '/<div\b[^>]*>|<\/div\s*>/i', $html, $tokens, PREG_OFFSET_CAPTURE, $open_offset )
    ) {
        return null;
    }

    $depth = 0;
    foreach ( $tokens[0] as $token ) {
        $markup = (string) $token[0];
        $offset = (int) $token[1];
        $depth += 0 === stripos( $markup, '</div' ) ? -1 : 1;
        if ( 0 === $depth ) {
            return array(
                'start'  => $open_offset,
                'length' => $offset + strlen( $markup ) - $open_offset,
            );
        }
    }
    return null;
}

function nvxValoracionRemoveDivsByClass( string $html, string $class_token ): string {
    $pattern = '/<div\b(?=[^>]*\bclass=["\'][^"\']*\b'
        . preg_quote( $class_token, '/' )
        . '\b[^"\']*["\'])[^>]*>/i';

    if ( ! preg_match_all( $pattern, $html, $matches, PREG_OFFSET_CAPTURE ) ) {
        return $html;
    }

    $ranges = array();
    foreach ( $matches[0] as $match ) {
        $range = nvxValoracionBalancedDivRange( $html, (int) $match[1] );
        if ( is_array( $range ) ) {
            $ranges[] = $range;
        }
    }
    usort( $ranges, static fn( array $a, array $b ): int => $b['start'] <=> $a['start'] );
    foreach ( $ranges as $range ) {
        $html = substr_replace( $html, '', $range['start'], $range['length'] );
    }
    return $html;
}

function nvxValoracionNativeHubspotEnforceSingleMount( string $html ): string {
    $mount_pattern = '/<div\b[^>]*\bid=["\']nvx-hubspot-native-form["\'][^>]*>/i';
    if ( ! preg_match_all( $mount_pattern, $html, $mounts, PREG_OFFSET_CAPTURE ) || empty( $mounts[0] ) ) {
        return $html;
    }

    $ranges = array();
    foreach ( $mounts[0] as $mount ) {
        $range = nvxValoracionBalancedDivRange( $html, (int) $mount[1] );
        if ( is_array( $range ) ) {
            $range['opening'] = (string) $mount[0];
            $ranges[]         = $range;
        }
    }
    if ( empty( $ranges ) ) {
        return $html;
    }

    usort( $ranges, static fn( array $a, array $b ): int => $a['start'] <=> $b['start'] );
    $first_offset  = (int) $ranges[0]['start'];
    $first_opening = (string) $ranges[0]['opening'];
    $marker        = '<!-- NVX_VALORACION_CANONICAL_MOUNT -->';

    $descending = $ranges;
    usort( $descending, static fn( array $a, array $b ): int => $b['start'] <=> $a['start'] );
    foreach ( $descending as $range ) {
        $html = substr_replace( $html, '', (int) $range['start'], (int) $range['length'] );
    }
    $html = substr( $html, 0, $first_offset ) . $marker . substr( $html, $first_offset );

    $html = preg_replace( '#<script\b[^>]*\bsrc=["\'][^"\']*hsforms\.net/[^"\']*["\'][^>]*>\s*</script>#iu', '', $html ) ?? $html;
    $html = preg_replace( '#<iframe\b[^>]*(?:hsforms|hubspot)[^>]*>[\s\S]*?</iframe>#iu', '', $html ) ?? $html;
    $html = nvxValoracionRemoveDivsByClass( $html, 'hs-form-frame' );
    $html = nvxValoracionRemoveDivsByClass( $html, 'hbspt-form' );
    $html = nvxValoracionRemoveDivsByClass( $html, 'nvx-hubspot-native-form-v2' );

    $canonical = $first_opening . nvxValoracionNativeHubspotMountMarkup() . '</div>';
    return str_replace( $marker, $canonical, $html );
}

add_action(
    'template_redirect',
    static function (): void {
        if ( nvxValoracionNativeHubspotIsTargetPage() ) {
            ob_start( 'nvxValoracionNativeHubspotEnforceSingleMount' );
        }
    },
    1
);

add_action(
    'wp_footer',
    static function (): void {
        if ( nvxValoracionNativeHubspotIsTargetPage() ) {
            echo '<script>window.nuvanxValoracionForm=true;</script>' . "\n";
        }
    },
    20
);
