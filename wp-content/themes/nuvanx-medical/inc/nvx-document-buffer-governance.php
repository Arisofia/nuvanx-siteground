<?php
/**
 * Public document-buffer governance.
 *
 * NUVANX must never rewrite the complete HTML document through a theme-owned
 * output buffer. The legacy closure in nvx-integrations.php can truncate the
 * response when another optimization/consent layer participates in buffering.
 * Queue/tag-level integration filters remain active and rendered acceptance
 * owns the final-document contract.
 *
 * This retirement is deliberately surgical: only the anonymous
 * template_redirect callback registered by nvx-integrations.php at priority
 * 999999 is removed. Other WordPress/plugin template_redirect callbacks and
 * output buffers are left untouched.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/** Retire the legacy theme-owned full-document output-buffer callback. */
( static function (): void {
    global $wp_filter;

    $hook = $wp_filter['template_redirect'] ?? null;
    if ( ! $hook instanceof WP_Hook ) {
        return;
    }

    $priority_callbacks = $hook->callbacks[999999] ?? array();
    if ( ! is_array( $priority_callbacks ) || array() === $priority_callbacks ) {
        return;
    }

    $integration_file = wp_normalize_path( __DIR__ . '/nvx-integrations.php' );

    foreach ( $priority_callbacks as $registration ) {
        $callback = $registration['function'] ?? null;
        if ( ! $callback instanceof Closure ) {
            continue;
        }

        try {
            $reflection = new ReflectionFunction( $callback );
        } catch ( ReflectionException $exception ) {
            unset( $exception );
            continue;
        }

        $source_file = $reflection->getFileName();
        if ( ! is_string( $source_file ) || wp_normalize_path( $source_file ) !== $integration_file ) {
            continue;
        }

        $hook->remove_filter( 'template_redirect', $callback, 999999 );
    }
} )();
