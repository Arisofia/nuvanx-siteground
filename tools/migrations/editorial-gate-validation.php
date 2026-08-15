<?php
/**
 * Read-only editorial gate for public WordPress content.
 *
 * This command never mutates content. It blocks publication/release acceptance
 * when storage-format leakage, editorial workflow markers, unauthorized inline
 * styling or explicitly forbidden absolute claims are present in a published
 * page/post.
 *
 * @package NVX\Migrations
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "ERROR: must run inside WordPress via wp eval-file.\n" );
	exit( 1 );
}

$editorial_rules = array(
	'nvx_tokens' => array(
		'pattern'     => '/@nvx-[a-z0-9_:-]+/i',
		'description' => 'Unresolved NUVANX runtime token',
	),
	'format_strings' => array(
		'pattern'     => '/%(?:\d+\$)?[sd]/',
		'description' => 'Unresolved format string',
	),
	'markdown_links' => array(
		'pattern'     => '/\[[^\]]+\]\([^)]+\)/',
		'description' => 'Raw Markdown link',
	),
	'markdown_headings' => array(
		'pattern'     => '/^#{1,6}\s+.+$/m',
		'description' => 'Raw Markdown heading',
	),
	'markdown_lists' => array(
		'pattern'     => '/^\s*(?:[-+*]|\d+[.)])\s+\S+/m',
		'description' => 'Raw Markdown list marker',
	),
	'draft_keywords' => array(
		'pattern'     => '/\b(?:borrador|pendiente de revisión|para revisar|work in progress)\b/i',
		'description' => 'Draft/review workflow language in published content',
	),
	'generic_placeholders' => array(
		'pattern'     => '/\b(?:TODO|FIXME|XXX|HACK|placeholder)\b/i',
		'description' => 'Editorial placeholder in published content',
	),
	'inline_styles' => array(
		'pattern'     => '/\sstyle\s*=\s*["\'][^"\']+["\']/i',
		'description' => 'Unauthorized inline style',
	),
);

$blocked_claim_patterns = array(
	'/\bresultado(?:s)?\s+garantizado(?:s|as)?\b/i' => 'resultado garantizado',
	'/\b100\s*%\s*efectiv[oa]s?\b/i'               => '100% efectivo',
	'/\bsin\s+riesgos?\b/i'                        => 'sin riesgos',
	'/\bsin\s+efectos?\s+secundarios?\b/i'        => 'sin efectos secundarios',
	'/\binfalible\b/i'                              => 'infalible',
	'/\bnaturalidad\s+absoluta\b/i'                => 'naturalidad absoluta',
	'/\bcero\s+sobretratamiento\b/i'               => 'cero sobretratamiento',
	'/\bm[aá]rgenes?\s+de\s+seguridad\s+exactos?\b/i' => 'márgenes de seguridad exactos',
	'/\bsin\s+tiempo\s+de\s+inactividad\b/i'      => 'sin tiempo de inactividad',
	'/\bpiel\s+impecable\b/i'                      => 'piel impecable',
);

$ids = get_posts(
	array(
		'post_type'              => array( 'page', 'post' ),
		'post_status'            => 'publish',
		'posts_per_page'         => -1,
		'fields'                 => 'ids',
		'orderby'                => 'ID',
		'order'                  => 'ASC',
		'no_found_rows'          => true,
		'update_post_meta_cache' => true,
		'update_post_term_cache' => false,
	)
);

$violations = array();
$passed     = 0;

foreach ( $ids as $post_id ) {
	$post = get_post( (int) $post_id );
	if ( ! ( $post instanceof WP_Post ) ) {
		continue;
	}

	$content = (string) $post->post_content;
	$errors  = array();

	foreach ( $editorial_rules as $rule_name => $rule ) {
		$matches = array();
		preg_match_all( $rule['pattern'], $content, $matches );
		if ( ! empty( $matches[0] ) ) {
			$errors[] = array(
				'rule'        => $rule_name,
				'description' => $rule['description'],
				'matches'     => array_values( array_unique( array_slice( $matches[0], 0, 5 ) ) ),
				'count'       => count( $matches[0] ),
			);
		}
	}

	foreach ( $blocked_claim_patterns as $pattern => $label ) {
		$matches = array();
		preg_match_all( $pattern, $content, $matches );
		if ( ! empty( $matches[0] ) ) {
			$errors[] = array(
				'rule'        => 'blocked_claim',
				'description' => 'Blocked absolute marketing/clinical claim',
				'matches'     => array_values( array_unique( array_slice( $matches[0], 0, 5 ) ) ),
				'count'       => count( $matches[0] ),
				'label'       => $label,
			);
		}
	}

	// Resolve internal HTML links that WordPress can map to a page/post. A link
	// mapping to a non-public object is an error; unknown custom/taxonomy routes
	// are not guessed here and remain the crawler's responsibility.
	if ( class_exists( 'DOMDocument' ) && false !== stripos( $content, '<a ' ) ) {
		$dom = new DOMDocument();
		$previous = libxml_use_internal_errors( true );
		$dom->loadHTML( '<?xml encoding="UTF-8">' . $content );
		libxml_clear_errors();
		libxml_use_internal_errors( $previous );
		$home_host = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );

		foreach ( $dom->getElementsByTagName( 'a' ) as $link ) {
			$href = trim( (string) $link->getAttribute( 'href' ) );
			if ( '' === $href || '#' === $href[0] || preg_match( '#^(?:mailto:|tel:|sms:|javascript:)#i', $href ) ) {
				continue;
			}

			$absolute = wp_http_validate_url( $href ) ? $href : home_url( '/' . ltrim( $href, '/' ) );
			$host     = strtolower( (string) wp_parse_url( $absolute, PHP_URL_HOST ) );
			if ( '' === $host || $home_host !== $host ) {
				continue;
			}

			$target_id = url_to_postid( $absolute );
			if ( $target_id <= 0 ) {
				continue;
			}
			$target = get_post( $target_id );
			if ( $target instanceof WP_Post && in_array( $target->post_type, array( 'page', 'post' ), true ) && 'publish' !== $target->post_status ) {
				$errors[] = array(
					'rule'        => 'link_to_nonpublic_content',
					'description' => 'Internal link resolves to non-public page/post',
					'matches'     => array( $href ),
					'count'       => 1,
					'target_id'   => (int) $target_id,
					'target_status'=> (string) $target->post_status,
				);
			}
		}
	}

	if ( empty( $errors ) ) {
		++$passed;
		continue;
	}

	$violations[] = array(
		'post_id'   => (int) $post->ID,
		'post_type' => (string) $post->post_type,
		'slug'      => (string) $post->post_name,
		'title'     => (string) $post->post_title,
		'errors'    => $errors,
	);
}

$report = array(
	'schema'     => 'editorial-gate-validation',
	'checked_at' => gmdate( 'c' ),
	'source'     => home_url( '/' ),
	'summary'    => array(
		'total_checked' => count( $ids ),
		'passed'        => $passed,
		'failed'        => count( $violations ),
	),
	'violations' => $violations,
);

echo wp_json_encode( $report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

if ( ! empty( $violations ) ) {
	fwrite( STDERR, sprintf( "EDITORIAL_GATE_VALIDATION=FAIL posts=%d\n", count( $violations ) ) );
	exit( 1 );
}

fwrite( STDERR, sprintf( "EDITORIAL_GATE_VALIDATION=PASS posts=%d\n", count( $ids ) ) );
