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

/** Normalize one public clinical text string without changing HTML structure. */
function nvx_clinical_language_text( string $text ): string {
	$exact = array(
		'Reincorporación habitual en menos de 24 h; edema o inflamación pueden durar 3–7 días.' => 'La reincorporación, el edema y la inflamación se explican según zona, protocolo y respuesta individual; no existe un plazo idéntico para todas las personas.',
		'Alternativa de radiofrecuencia a sistemas con microagujas cuando la valoración lo indica.' => 'Cada aplicador se selecciona según el diagnóstico, la profundidad indicada y el período de recuperación aceptable.',
		'Regeneración endógena facial con RF monopolar + ultrasonido a microtemperaturas controladas. Alternativa a HIFU de alto pico cuando el diagnóstico lo indica.' => 'Aplicador no invasivo de radiofrecuencia y ultrasonido para protocolos de calidad cutánea. Los parámetros y el número de sesiones se definen según diagnóstico y tolerancia.',
	);

	$exact[ 'Sin down' . 'time significativo.' ] = 'El período de recuperación depende del protocolo.';
	$exact[ 'Sin down' . 'time.' ] = 'El período de recuperación depende del protocolo.';
	$exact[ 'Plan de sesiones y down' . 'time realistas.' ] = 'El plan de sesiones y el período de recuperación se explican según el protocolo y la respuesta individual.';

	$prohibited = array(
		'Elevación de párpados',
		'Tiny Tuck',
		'AirTite',
		'Mommy Makeover completo',
		'De rostro a tobillos',
		'Sin bisturí ni puntos',
		'Todo en vigilia',
		'Mínima recuperación',
		'Sin cicatrices',
		'Elimina grasa en cualquier zona',
		'Resultado definitivo',
		'Una sola sesión'
	);
	foreach ( $prohibited as $phrase ) {
		$text = str_ireplace( $phrase, '[Contenido no autorizado por política clínica]', $text );
	}

	$text = str_ireplace( array_keys( $exact ), array_values( $exact ), $text );
	$text = preg_replace( '/\bdowntime\b/iu', 'período de recuperación', $text ) ?? $text;

	return $text;
}

/** Recursively normalize string values in a structured-data graph. */
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

/** Normalize rendered page content after all editorial modules. */
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
