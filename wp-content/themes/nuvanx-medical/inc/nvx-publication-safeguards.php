<?php
/**
 * Narrow publication safeguards for legacy/generated content.
 *
 * These filters are intentionally exact-string and fail closed. They prevent
 * broad CTA rewrites and unsupported operational or clinical wording without
 * changing unrelated content.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Preserve generic links labelled “Enviar” before global CTA normalization. */
function nvx_publication_protect_generic_send_links( string $content ): string {
	if ( is_admin() || is_feed() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return $content;
	}

	$updated = preg_replace_callback(
		'/<a\b([^>]*)>\s*Enviar\s*<\/a>/iu',
		static function ( array $match ): string {
			$attrs = preg_replace( '/\sdata-nvx-preserve-send=["\'][^"\']*["\']/i', '', (string) $match[1] );
			return '<a' . $attrs . ' data-nvx-preserve-send="1">__NVX_PRESERVE_SEND__</a>';
		},
		$content
	);

	return is_string( $updated ) ? $updated : $content;
}
add_filter( 'the_content', 'nvx_publication_protect_generic_send_links', 19 );

/** Restore protected generic links after the global CTA normalization pass. */
function nvx_publication_restore_generic_send_links( string $content ): string {
	if ( false === strpos( $content, '__NVX_PRESERVE_SEND__' ) ) {
		return $content;
	}

	$content = str_replace( '__NVX_PRESERVE_SEND__', 'Enviar', $content );
	$updated = preg_replace( '/\sdata-nvx-preserve-send=["\']1["\']/i', '', $content );

	return is_string( $updated ) ? $updated : $content;
}
add_filter( 'the_content', 'nvx_publication_restore_generic_send_links', 21 );

/** Moderate exact legacy/public strings after page builders have completed. */
function nvx_publication_moderate_public_copy( string $content ): string {
	$replacements = array(
		'El Dr. Rivera o un miembro de su equipo te contactará en un plazo máximo de 24 horas para confirmar tu fecha de valoración.'
			=> 'Normalmente, un miembro del equipo te contactará durante el siguiente día laborable para confirmar la fecha de valoración.',
		'Una persona del equipo del Dr. Rivera te contactará en menos de 24 horas para confirmar tu valoración médica.'
			=> 'Normalmente, un miembro del equipo te contactará durante el siguiente día laborable para confirmar la fecha de valoración.',
		'EMFUSION® en Madrid: hidratación profunda y luminosidad cutánea'
			=> 'EMFUSION® en Madrid: hidratación y luminosidad cutánea',
	);

	return str_replace( array_keys( $replacements ), array_values( $replacements ), $content );
}
add_filter( 'the_content', 'nvx_publication_moderate_public_copy', 143 );

/** Keep translated/generated EMFUSION headings aligned with the governed claim. */
function nvx_publication_moderate_gettext( string $translated, string $text, string $domain ): string {
	if (
		'nuvanx-medical' === $domain
		&& 'EMFUSION® en Madrid: hidratación profunda y luminosidad cutánea' === $text
	) {
		return 'EMFUSION® en Madrid: hidratación y luminosidad cutánea';
	}

	return $translated;
}
add_filter( 'gettext', 'nvx_publication_moderate_gettext', 20, 3 );
