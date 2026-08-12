<?php
/**
 * nvx-content-hygiene-rules.php
 *
 * NVX Content Hygiene Rules — Single Source of Truth.
 *
 * Consumed by:
 *   - tools/migrations/audit-content-divergence.php
 *   - tools/migrations/content-hygiene-shared.php
 *   - tools/migrations/content-hygiene-staging-only.php
 *
 * NEVER add mutation logic here. Rule definitions only.
 *
 * @package NVX\Migrations
 * @version 1.0.0
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "[nvx-rules] Must be run inside a WordPress context (wp eval-file).\n" );
	exit( 1 );
}

/**
 * Plain-string replacements applied to wp_posts fields.
 *
 * Ordering rules:
 *   1. Longest / most-specific strings first to prevent partial shadowing.
 *   2. Within the same prefix, dot-terminated variants precede bare ones.
 *   3. UPPERCASE variants precede Title-case variants.
 *
 * @return list<array{from:string,to:string}>
 */
function nvx_hygiene_str_reps(): array {
	return array(
		array( 'from' => 'valoración médica gratuita', 'to' => 'valoración médica' ),
		array( 'from' => 'valoración gratuita', 'to' => 'valoración médica' ),
		array( 'from' => 'valoración gratis', 'to' => 'valoración médica' ),
		array( 'from' => 'consulta médica gratuita', 'to' => 'consulta médica' ),
		array( 'from' => 'consulta gratuita', 'to' => 'consulta médica' ),
		array( 'from' => 'consulta gratis', 'to' => 'consulta médica' ),
		array( 'from' => 'Tu mejor versión empieza aquí.', 'to' => 'Reserva 15–30 min de valoración médica.' ),
		array( 'from' => 'Tu mejor versión empieza aquí', 'to' => 'Reserva 15–30 min de valoración médica' ),
		array( 'from' => 'EXILITET', 'to' => 'EXILITE™' ),
		array( 'from' => 'Exilitet', 'to' => 'EXILITE™' ),
		array( 'from' => 'Solicitar.', 'to' => 'Solicitar valoración médica' ),
		array( 'from' => 'enfoque médico premium', 'to' => 'misma dirección médica que Chamberí' ),
		array( 'from' => 'presupuestos personalizados', 'to' => 'presupuesto individualizado tras la valoración médica' ),
		array( 'from' => 'sin compromiso', 'to' => 'sin obligación de continuar con un tratamiento' ),
	);
}

/**
 * PCRE-regex replacements.
 *
 * @return list<array{pattern:string,replacement:string,flags:string}>
 */
function nvx_hygiene_regex_reps(): array {
	return array(
		array(
			'pattern'     => 'valoraci[oó]n\s+m[eé]dica\s+gratu[íi]ta',
			'replacement' => 'valoración médica',
			'flags'       => 'iu',
		),
		array(
			'pattern'     => 'consulta\s+m[eé]dica\s+gratu[íi]ta',
			'replacement' => 'consulta médica',
			'flags'       => 'iu',
		),
	);
}

/**
 * wp_posts fields that hygiene rules are applied against.
 *
 * @return string[]
 */
function nvx_hygiene_fields(): array {
	return array( 'post_title', 'post_content', 'post_excerpt' );
}

/**
 * Legal pages that must have exactly one H1 matching the expected text.
 *
 * @return array<string,string>
 */
function nvx_hygiene_legal_pages(): array {
	return array(
		'politica-privacidad' => 'Política de privacidad',
		'aviso-legal'         => 'Aviso legal',
	);
}
