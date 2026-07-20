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
		'"Si no hay indicación clínica, no hay tratamiento." El Dr. Rivera evalúa cada caso antes de recomendar cualquier procedimiento. No existe protocolo estándar: existe tu protocolo.'
			=> 'Cada caso se evalúa antes de recomendar un procedimiento. La indicación, las alternativas y los parámetros se definen de forma individual.',
		'"Sabes exactamente qué se te aplica, en qué cantidad y quién lo hace." Inyectables Allergan® y Merz® con código de lote en tu historial. Técnica firmada en el presupuesto antes del procedimiento.'
			=> 'Cuando corresponde, documentamos producto, cantidad, lote, profesional responsable y técnica prevista en el historial clínico y la información del procedimiento.',
		'"El Dr. Rivera que hace tu diagnóstico es el mismo que ejecuta el procedimiento y el mismo que hace tu seguimiento." No hay rotación de médicos. No hay delegación silenciosa.'
			=> 'La responsabilidad clínica, el profesional que realiza el procedimiento y el plan de seguimiento se identifican de forma explícita antes de comenzar.',
		'Nº Colegiado ICOMEM: 282864786 · Especialista en Medicina Estética Láser e Ingeniería Tisular'
			=> 'Nº Colegiado ICOMEM: 282864786 · Director médico de NUVANX Medicina Estética Láser',
		'Mi criterio de indicación clínica es innegociable: si no hay razón médica objetiva para un tratamiento, no lo recomiendo. Hay clínicas que venden su catálogo de máquinas; en NUVANX, yo te vendo el diagnóstico anatómico.'
			=> 'Mi criterio de indicación clínica es claro: si un tratamiento no está justificado para tu caso, no lo recomiendo. El diagnóstico y las alternativas deben explicarse antes de decidir.',
		'Como Director Médico de NUVANX Medicina Estética Láser, mi enfoque se centra en la geriatría preventiva y la regeneración tisular sin procedimientos quirúrgicos invasivos. Personalmente ejecuto los tratamientos de alta complejidad energética y acompaño al paciente durante toda la curva de recuperación.'
			=> 'Como director médico de NUVANX Medicina Estética Láser, superviso el criterio diagnóstico, la planificación clínica y los protocolos de seguimiento. El profesional responsable de cada procedimiento se identifica antes del tratamiento.',
		'Procedimientos de Alta Complejidad Ejecutados Personalmente:'
			=> 'Procedimientos y áreas de práctica clínica:',
		'Láser CO₂ Fraccionado Quirúrgico:'
			=> 'Láser CO₂ fraccionado:',
		'Inyectables Estructurales (Allergan / Merz):'
			=> 'Inyectables estructurales y neuromodulación:',
		'Reposicionamiento volumétrico y neuromodulación bajo trazabilidad absoluta.'
			=> 'Planificación conservadora y trazabilidad documentada del producto y la técnica cuando corresponde.',
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
