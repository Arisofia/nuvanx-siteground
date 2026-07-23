<?php
/**
 * Canonical clinical-language normalization for visible content and schema.
 *
 * This is a final-output safety layer while legacy CMS and PHP sources are
 * progressively migrated. It does not create entities or strengthen claims.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides the canonical clinical-marketing phrases prohibited from public content and schema.
 *
 * @return array<string> The prohibited phrases.
 */
function nvx_clinical_language_prohibited_phrases(): array {
	return array(
		'Elevación de párpados',
		'Tiny Tuck',
		'AirTite',
		'Mommy Makeover completo',
		'De rostro a tobillos',
		'Sin bisturí ni puntos',
		'Todo en vigilia',
		'Mínima recuperación',
		'Recuperación inmediata',
		'Cero recuperación',
		'Sin cicatrices',
		'Sin inflamación',
		'Sin dolor',
		'Sin riesgos',
		'Elimina grasa en cualquier zona',
		'Elimina grasa',
		'Resultado definitivo',
		'Resultados garantizados',
		'Una sola sesión',
		'Generalmente 3–4 sesiones',
		'Reducción del dolor',
		'Eritema reducido',
		'Eritema mínimo',
		'Para cualquier paciente',
		'Control térmico absoluto',
		'Sin huellas quirúrgicas evidentes',
		'Presupuesto muy bajo',
		'presupuesto muy bajo',
		'No usamos descuentos estacionales',
		'no usamos descuentos estacionales',
		'El estándar de oro',
		'el estándar de oro',
		'Absoluta discreción',
		'absoluta discreción',
	);
}

/**
 * Normalizes clinical language in a public-facing text string.
 *
 * @param string $text The text to normalize.
 * @return string The normalized text.
 */
function nvx_clinical_language_text( string $text ): string {
	$exact = array(
		'Reincorporación habitual en menos de 24 h; edema o inflamación pueden durar 3–7 días.' => 'La reincorporación, el edema y la inflamación se explican según zona, protocolo y respuesta individual; no existe un plazo idéntico para todas las personas.',
		'Alternativa de radiofrecuencia a sistemas con microagujas cuando la valoración lo indica.' => 'Cada aplicador se selecciona según el diagnóstico, la profundidad indicada y el período de recuperación aceptable.',
		'Regeneración endógena facial con RF monopolar + ultrasonido a microtemperaturas controladas. Alternativa a HIFU de alto pico cuando el diagnóstico lo indica.' => 'Aplicador no invasivo de radiofrecuencia y ultrasonido para protocolos de calidad cutánea. Los parámetros y el número de sesiones se definen según diagnóstico y tolerancia.',
	);
	$exact[ 'Sin down' . 'time significativo.' ] = 'El período de recuperación depende del protocolo.';
	$exact[ 'Sin down' . 'time.' ] = 'El período de recuperación depende del protocolo.';
	$exact[ 'Plan de sesiones y down' . 'time realistas.' ] = 'El plan de sesiones y el período de recuperación se explican según el protocolo y la respuesta individual.';

	foreach ( nvx_clinical_language_prohibited_phrases() as $phrase ) {
		$text = str_ireplace( $phrase, '[Contenido no autorizado por política clínica]', $text );
	}
	$text = str_ireplace( array_keys( $exact ), array_values( $exact ), $text );
	$text = preg_replace( '/\bdowntime\b/iu', 'período de recuperación', $text ) ?? $text;
	return $text;
}

/**
 * Recursively normalizes string values within structured data.
 *
 * @param mixed $value The value to normalize.
 * @return mixed The normalized value.
 */
function nvx_clinical_language_recursive( $value ) {
	if ( is_string( $value ) ) {
		return nvx_clinical_language_text( $value );
	}
	if ( is_array( $value ) ) {
		foreach ( $value as $key => $item ) {
			$value[ $key ] = nvx_clinical_language_recursive( $item );
		}
	}
	return $value;
}

/**
 * Normalizes rendered page content for public-facing requests.
 *
 * @param string $content The rendered page content.
 * @return string The normalized content, or the original content for admin, AJAX, and REST requests.
 */
function nvx_clinical_language_content( string $content ): string {
	if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return $content;
	}
	return nvx_clinical_language_text( $content );
}
add_filter( 'the_content', 'nvx_clinical_language_content', 220 );

/** Normalize the final Yoast graph after entity and FAQ extensions. */
function nvx_clinical_language_schema_graph( $graph ) {
	return is_array( $graph ) ? nvx_clinical_language_recursive( $graph ) : $graph;
}
add_filter( 'wpseo_schema_graph', 'nvx_clinical_language_schema_graph', 250, 1 );
