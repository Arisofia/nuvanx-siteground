<?php
/**
 * Shared editorial validation rules and constants.
 *
 * Centralizes regex patterns and thresholds used across editorial gate validation
 * and content normalization to keep definitions in sync over time.
 *
 * @package NVX\Migrations
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Editorial validation rules pattern library.
 */
class NVX_Editorial_Rules {

    /**
     * Draft/review workflow language pattern.
     * Restricted to actual workflow residue. Natural patient copy such as
     * "valoración para revisar el perfil" is not a draft marker.
     */
    public const DRAFT_KEYWORDS_PATTERN = '/\b(?:borrador|pendiente de revisión|marcad[oa]\s+para\s+revisión|pendiente\s+por\s+revisar|work in progress)\b/iu';

    /**
     * Generic placeholder pattern.
     * Requires explicit editorial-marker syntax. Bare "placeholder" is a
     * legitimate HTML/data attribute used by Complianz and form controls.
     * Does not match attribute-style hyphens (e.g., data-placeholder-image) to avoid
     * false positives with cookie banner markup.
     */
    public const GENERIC_PLACEHOLDERS_PATTERN = '/(?:\[(?:TODO|FIXME|XXX|HACK|PLACEHOLDER)\]|\b(?:TODO|FIXME|XXX|HACK|PLACEHOLDER)\b\s*:\s*)/iu';

    /**
     * Uppercase placeholder pattern (no brackets or separators).
     * Catches bare TODO/FIXME/XXX/HACK markers in uppercase that may have been
     * missed by the primary pattern. This is a stricter fallback for explicit editorial markers.
     */
    public const GENERIC_PLACEHOLDERS_UPPERCASE_PATTERN = '/\b(?:TODO|FIXME|XXX|HACK)\b/';

    /**
     * Markdown link pattern (excluding images).
     */
    public const MARKDOWN_LINKS_PATTERN = '/\[[^\]]+\]\([^)]+\)/';

    /**
     * Markdown heading pattern.
     */
    public const MARKDOWN_HEADINGS_PATTERN = '/^#{1,6}\s+.+$/m';

    /**
     * Markdown list marker pattern.
     */
    public const MARKDOWN_LISTS_PATTERN = '/^\s*(?:[-+*]|\d+[.)])\s+\S+/m';

    /**
     * Editorial marker pattern (📌).
     */
    public const EDITORIAL_MARKER_PATTERN = '/📌/u';

    /**
     * Blocked claim patterns.
     * Removed /u modifier from non-accented patterns to maintain fail-closed behavior on malformed UTF-8.
     * The /u modifier would cause preg_match_all() to return false on invalid UTF-8,
     * converting a fail-closed check into fail-open. Without /u, byte-wise matching
     * still works even with malformed content. Kept /u on patterns with accented characters
     * (márgenes, sin efectos secundarios, etc.) which require proper UTF-8 handling.
     */
    public const BLOCKED_CLAIM_PATTERNS = array(
        '/\bresultado(?:s)?\s+garantizado(?:s|as)?\b/i' => 'resultado garantizado',
        '/\b100\s*%\s*efectiv[oa]s?\b/i'               => '100% efectivo',
        '/\bsin\s+riesgos?\b/i'                        => 'sin riesgos',
        '/\bsin\s+efectos?\s+secundarios?\b/iu'        => 'sin efectos secundarios',
        '/\binfalible\b/iu'                              => 'infalible',
        '/\bnaturalidad\s+absoluta\b/iu'                => 'naturalidad absoluta',
        '/\bcero\s+sobretratamiento\b/iu'               => 'cero sobretratamiento',
        '/\bm[aá]rgenes?\s+de\s+seguridad\s+exactos?\b/iu' => 'márgenes de seguridad exactos',
        '/\bsin\s+tiempo\s+de\s+inactividad\b/iu'      => 'sin tiempo de inactividad',
        '/\bpiel\s+impecable\b/iu'                      => 'piel impecable',
    );

    /**
     * Advisory context detection constants.
     * Tightened to prevent whitelisting real claims in the same sentence.
     * Changed from sentence boundaries [^.!?] to clause separators [^.!?,;] to prevent
     * chaining clauses with commas/semicolons from being exempted. Also tightened max
     * character limits to require immediate proximity to advisory phrases.
     */
    public const ADVISORY_WINDOW_SIZE = 220;            // Characters to look back before match
    public const ADVISORY_NEGATION_MAX_LENGTH = 50;   // Max chars after negation (tightened from 170)
    public const ADVISORY_WARNING_MAX_LENGTH = 40;  // Max chars before warning phrases (tightened from 150)
    public const ADVISORY_WARNING_SUFFIX_LENGTH = 30; // Max chars after warning phrases (tightened from 80)
    public const ADVISORY_CLAUSE_SEPARATORS = '[^.!?,;]'; // Clause-level separators for context restriction
    public const ADVISORY_SENTENCE_BOUNDARY = '[^.!?,;]'; // Alias for clause separators (legacy name)

    /**
     * Get all editorial rules as associative array.
     *
     * @return array<string,array{pattern:string,description:string}>
     */
    public static function get_rules(): array {
        return array(
            'nvx_tokens' => array(
                'pattern'     => '/@nvx-[a-z0-9_:-]+/i',
                'description' => 'Unresolved NUVANX runtime token',
            ),
            'format_strings' => array(
                'pattern'     => '/%(?:\d+\$)?[sd]/',
                'description' => 'Unresolved format string',
            ),
            'markdown_links' => array(
                'pattern'     => self::MARKDOWN_LINKS_PATTERN,
                'description' => 'Raw Markdown link',
            ),
            'markdown_headings' => array(
                'pattern'     => self::MARKDOWN_HEADINGS_PATTERN,
                'description' => 'Raw Markdown heading',
            ),
            'markdown_lists' => array(
                'pattern'     => self::MARKDOWN_LISTS_PATTERN,
                'description' => 'Raw Markdown list marker',
            ),
            'draft_keywords' => array(
                'pattern'     => self::DRAFT_KEYWORDS_PATTERN,
                'description' => 'Draft/review workflow language in published content',
            ),
            'generic_placeholders' => array(
                'pattern'     => self::GENERIC_PLACEHOLDERS_PATTERN,
                'description' => 'Editorial placeholder in published content',
            ),
            'generic_placeholders_uppercase' => array(
                'pattern'     => self::GENERIC_PLACEHOLDERS_UPPERCASE_PATTERN,
                'description' => 'Editorial placeholder in published content (uppercase only)',
            ),
            'inline_styles' => array(
                'pattern'     => '/\sstyle\s*=\s*["\'][^"\']+["\']/i',
                'description' => 'Unauthorized inline style',
            ),
        );
    }

    /**
     * Get normalized content validation checks.
     *
     * @return array<string,string> Pattern => message
     */
    public static function get_validation_checks(): array {
        return array(
            '/(?<!!)\[[^\]]+\]\(([^)\s]+)\)/' => 'Markdown links still present',
            self::MARKDOWN_HEADINGS_PATTERN        => 'Markdown headings still present',
            self::MARKDOWN_LISTS_PATTERN           => 'Markdown list markers still present',
            '/@nvx-[a-z0-9_:-]+/i'               => '@nvx-* token still present',
            '/%(?:\d+\$)?[sd]/'                  => 'Format string still present',
            self::DRAFT_KEYWORDS_PATTERN          => 'Draft/review language still present',
            self::GENERIC_PLACEHOLDERS_PATTERN      => 'Editorial placeholder still present',
            self::GENERIC_PLACEHOLDERS_UPPERCASE_PATTERN => 'Uppercase editorial placeholder still present',
            self::EDITORIAL_MARKER_PATTERN         => 'Editorial marker (📌) present - may indicate mid-content placement',
        );
    }
}