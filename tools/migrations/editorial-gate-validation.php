<?php
/**
 * Editorial Gate Pre-Publication Validation
 *
 * Blocks any page/post containing forbidden content before publication.
 * Validates against editorial standards and governance rules.
 *
 * Run with:
 *   wp eval "require 'tools/migrations/editorial-gate-validation.php';" --allow-root
 *
 * @package NVX\Migrations
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "ERROR: must run inside WordPress via wp eval \"require '...';\".\n" );
	exit( 1 );
}

// Editorial validation rules
$editorial_rules = [
	'placeholders' => [
		'pattern' => '/@nvx-[a-z0-9_-]+/i',
		'description' => 'NUVANX placeholders (@nvx-*)',
		'severity' => 'error',
	],
	'format_strings' => [
		'pattern' => '/%[sd]/',
		'description' => 'Format strings (%s, %d)',
		'severity' => 'error',
	],
	'markdown_links' => [
		'pattern' => '/\[([^\]]+)\]\([^)]+\)/',
		'description' => 'Markdown links [text](url)',
		'severity' => 'error',
	],
	'markdown_headings' => [
		'pattern' => '/^#{1,6}\s+.+$/m',
		'description' => 'Markdown headings (#, ##, etc.)',
		'severity' => 'error',
	],
	'draft_keywords' => [
		'pattern' => '/(borrador|pendiente de revisión|para revisar|wip|work in progress)/i',
		'description' => 'Draft workflow keywords',
		'severity' => 'error',
	],
	'placeholders_generic' => [
		'pattern' => '/(TODO|FIXME|XXX|HACK|TEMP|placeholder)/i',
		'description' => 'Generic placeholders',
		'severity' => 'error',
	],
	'inline_styles' => [
		'pattern' => '/style=["\'][^"\']*color:\s*#[0-9a-f]{3,6}[^"\']*["\']/i',
		'description' => 'Hardcoded inline colors',
		'severity' => 'error',
	],
	'inline_styles_unauthorized' => [
		'pattern' => '/style=["\'][^"\']*["\']/i',
		'description' => 'Unauthorized inline styles',
		'severity' => 'warning',
	],
];

// Blocked claims list
$blocked_claims = [
	'garantizado',
	'garantía',
	'siempre',
	'jamás',
	'único',
	'el mejor',
	'el único',
	'mejor que',
	'infalible',
	'sin riesgos',
	'sin efectos secundarios',
	'100% efectivo',
	'resultado inmediato',
	'resultado garantizado',
];

// Get all published posts and pages
$posts = get_posts( [
	'post_type'      => [ 'page', 'post' ],
	'post_status'    => 'publish',
	'posts_per_page' => -1,
	'fields'         => 'ids',
] );

$validation_results = [
	'total_checked'   => count( $posts ),
	'passed'          => 0,
	'failed'          => 0,
	'warnings'        => 0,
	'violations'      => [],
];

foreach ( $posts as $post_id ) {
	$post = get_post( $post_id );
	if ( ! $post instanceof WP_Post ) {
		continue;
	}

	$post_violations = [
		'post_id'   => $post_id,
		'post_type' => $post->post_type,
		'title'     => $post->post_title,
		'errors'    => [],
		'warnings'  => [],
	];

	// Get content
	$content = $post->post_content;

	// Check editorial rules
	foreach ( $editorial_rules as $rule_name => $rule ) {
		$matches = [];
		preg_match_all( $rule['pattern'], $content, $matches );

		if ( ! empty( $matches[0] ) ) {
			$violation = [
				'rule'        => $rule_name,
				'description' => $rule['description'],
				'matches'     => array_slice( $matches[0], 0, 5 ), // Limit to 5 matches
				'count'       => count( $matches[0] ),
			];

			if ( $rule['severity'] === 'error' ) {
				$post_violations['errors'][] = $violation;
			} else {
				$post_violations['warnings'][] = $violation;
			}
		}
	}

	// Check blocked claims
	foreach ( $blocked_claims as $claim ) {
		if ( stripos( $content, $claim ) !== false ) {
			$post_violations['errors'][] = [
				'rule'        => 'blocked_claim',
				'description' => 'Blocked marketing claim',
				'matches'     => [ $claim ],
				'count'       => 1,
			];
		}
	}

	// Check for draft/404 links
	$dom = new DOMDocument();
	libxml_use_internal_errors( true );
	$dom->loadHTML( '<?xml encoding="UTF-8">' . $content );
	libxml_clear_errors();

	$links = $dom->getElementsByTagName( 'a' );
	foreach ( $links as $link ) {
		$href = $link->getAttribute( 'href' );
		if ( ! empty( $href ) ) {
			// Check for draft/404 patterns
			if ( stripos( $href, 'draft' ) !== false || stripos( $href, '404' ) !== false ) {
				$post_violations['errors'][] = [
					'rule'        => 'invalid_link',
					'description' => 'Link to draft/404',
					'matches'     => [ $href ],
					'count'       => 1,
				];
			}
		}
	}

	// Add to results if violations found
	if ( ! empty( $post_violations['errors'] ) || ! empty( $post_violations['warnings'] ) ) {
		$validation_results['violations'][] = $post_violations;
		$validation_results['failed']++;
		if ( ! empty( $post_violations['errors'] ) ) {
			$validation_results['warnings']++;
		}
	} else {
		$validation_results['passed']++;
	}
}

// Output validation report
$report = [
	'schema'       => 'editorial-gate-validation',
	'checked_at'   => gmdate( 'c' ),
	'source'       => home_url( '/' ),
	'summary'      => [
		'total_checked' => $validation_results['total_checked'],
		'passed'        => $validation_results['passed'],
		'failed'        => $validation_results['failed'],
		'warnings'      => $validation_results['warnings'],
	],
	'violations'   => $validation_results['violations'],
	'rules'        => $editorial_rules,
	'blocked_claims' => $blocked_claims,
];

echo json_encode( $report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

// Console summary
fwrite( STDERR, "\n=== EDITORIAL GATE VALIDATION ===\n" );
fwrite( STDERR, "Total checked: " . $validation_results['total_checked'] . "\n" );
fwrite( STDERR, "Passed: " . $validation_results['passed'] . "\n" );
fwrite( STDERR, "Failed: " . $validation_results['failed'] . "\n" );
fwrite( STDERR, "Warnings: " . $validation_results['warnings'] . "\n" );

if ( ! empty( $validation_results['violations'] ) ) {
	fwrite( STDERR, "\n=== VIOLATIONS ===\n" );
	foreach ( $validation_results['violations'] as $violation ) {
		fwrite( STDERR, "\nPost: {$violation['title']} (ID: {$violation['post_id']})\n" );
		foreach ( $violation['errors'] as $error ) {
			fwrite( STDERR, "  ERROR: {$error['description']} ({$error['count']} matches)\n" );
		}
		foreach ( $violation['warnings'] as $warning ) {
			fwrite( STDERR, "  WARNING: {$warning['description']} ({$warning['count']} matches)\n" );
		}
	}
}

// Exit with error if validation failed
if ( $validation_results['failed'] > 0 ) {
	fwrite( STDERR, "\nEDITORIAL_GATE_VALIDATION=FAIL errors={$validation_results['failed']}\n" );
	exit( 1 );
}

fwrite( STDERR, "\nEDITORIAL_GATE_VALIDATION=PASS\n" );