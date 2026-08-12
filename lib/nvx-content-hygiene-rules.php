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

/**
 * Plain-string replacements applied to wp_posts fields.
 *
 * Ordering rules:
 *   1. Longest / most-specific strings first to prevent partial shadowing.
 *   2. Within the same prefix, dot-terminated variants precede bare ones
 *      (e.g. "Tu mejor versión empieza aquí." before "Tu mejor versión empieza aquí").
 *   3. UPPERCASE variants precede Title-case variants (EXILITET before Exilitet).
 *
 * @return list<array{from: string, to: string}>
 */
function nvx_hygiene_str_reps(): array {
    return [

        // ── Valoración ───────────────────────────────────────────────────────
        [ 'from' => 'valoración médica gratuita', 'to' => 'valoración médica'  ],
        [ 'from' => 'valoración gratuita',        'to' => 'valoración médica'  ],
        [ 'from' => 'valoración gratis',          'to' => 'valoración médica'  ],

        // ── Consulta ─────────────────────────────────────────────────────────
        [ 'from' => 'consulta médica gratuita',   'to' => 'consulta médica'    ],
        [ 'from' => 'consulta gratuita',          'to' => 'consulta médica'    ],
        [ 'from' => 'consulta gratis',            'to' => 'consulta médica'    ],

        // ── Headline (dot-terminated variant first) ──────────────────────────
        [ 'from' => 'Tu mejor versión empieza aquí.', 'to' => 'Reserva 15–30 min de valoración médica.' ],
        [ 'from' => 'Tu mejor versión empieza aquí',  'to' => 'Reserva 15–30 min de valoración médica'  ],

        // ── Brand ────────────────────────────────────────────────────────────
        [ 'from' => 'EXILITET', 'to' => 'EXILITE™' ],
        [ 'from' => 'Exilitet', 'to' => 'EXILITE™' ],

        // ── CTA ──────────────────────────────────────────────────────────────
        // "Solicitar." with period first to avoid matching the bare word
        [ 'from' => 'Solicitar.',                 'to' => 'Solicitar valoración médica' ],

        // ── Claims ───────────────────────────────────────────────────────────
        [ 'from' => 'enfoque médico premium',     'to' => 'misma dirección médica que Chamberí' ],
        [ 'from' => 'presupuestos personalizados','to' => 'presupuesto individualizado tras la valoración médica' ],
        [ 'from' => 'sin compromiso',             'to' => 'sin obligación de continuar con un tratamiento' ],

    ];
}

/**
 * PCRE-regex replacements (patterns without delimiters; flags applied per-rule).
 *
 * Used for accent/entity variants that plain str_replace cannot safely catch
 * (e.g. HTML-entity-encoded vowels, mixed case from CMS editors).
 *
 * @return list<array{pattern: string, replacement: string, flags: string}>
 */
function nvx_hygiene_regex_reps(): array {
    return [
        // Handles é/e, ó/o, í/i mixed with HTML entities or incorrect encoding
        // Word boundaries (\b) prevent matching inside larger words
        [
            'pattern'     => '\bvaloraci[oó]n\s+m[eé]dica\s+gratu[íi]ta\b',
            'replacement' => 'valoración médica',
            'flags'       => 'iu',
        ],
        [
            'pattern'     => '\bconsulta\s+m[eé]dica\s+gratu[íi]ta\b',
            'replacement' => 'consulta médica',
            'flags'       => 'iu',
        ],
    ];
}

/**
 * wp_posts fields that hygiene rules are applied against.
 *
 * @return string[]
 */
function nvx_hygiene_fields(): array {
    return [ 'post_title', 'post_content', 'post_excerpt' ];
}

/**
 * Legal pages that must have exactly one H1 matching the expected text.
 *
 * Key   = post_name (slug)
 * Value = expected H1 text node (no HTML tags)
 *
 * IMPORTANT: The shared migration VERIFIES these; it does NOT overwrite
 * existing correct values. Production already has these correct as of 2026-08-12.
 *
 * @return array<string, string>
 */
function nvx_hygiene_legal_pages(): array {
    return [
        'politica-privacidad' => 'Política de privacidad',
        'aviso-legal'         => 'Aviso legal',
    ];
}
