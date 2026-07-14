#!/usr/bin/env php
<?php
/**
 * Ticket 43 — compare canonical production copy vs V3 candidate.
 *
 * Normalizes whitespace and HTML entities only. Tag/wrapper/class changes are allowed.
 *
 * Usage:
 *   php scripts/ticket-43/verify-copy-integrity.php \
 *     qa/fixtures/ticket-43/home-production-post-content.html \
 *     deploy/ticket-43/post_content_v3-production-copy.html
 */

if ( PHP_SAPI !== 'cli' ) {
	fwrite( STDERR, "CLI only.\n" );
	exit( 1 );
}

$canonical_file = $argv[1] ?? '';
$candidate_file = $argv[2] ?? '';

if ( $canonical_file === '' || $candidate_file === '' ) {
	fwrite( STDERR, "Usage: php verify-copy-integrity.php <canonical.html> <candidate.html>\n" );
	exit( 1 );
}

foreach ( array( $canonical_file, $candidate_file ) as $path ) {
	if ( ! is_readable( $path ) ) {
		fwrite( STDERR, "ERROR: unreadable file: {$path}\n" );
		exit( 1 );
	}
}

$banned_phrases = array(
	'Medicina estética sin ruido',
	'Nuestro manifiesto',
	'Menos artificio. Más criterio.',
	'Conoce NUVANX',
	'Madrid · Chamberí y Goya-Salamanca',
	'Belleza real. Confianza auténtica.',
);

/**
 * @return string
 */
function nvx_ticket43_normalize_text( string $text ): string {
	$text = html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$text = preg_replace( '/\s+/u', ' ', $text );
	return trim( (string) $text );
}

/**
 * @return array{texts: array<string,int>, anchors: array<string,int>, h1: int, h2: int, links: int, faq: int, treatments: int, cs20144: int, cs20073: int}
 */
function nvx_ticket43_extract_manifest( string $html ): array {
	$dom = new DOMDocument();
	$prev = libxml_use_internal_errors( true );
	$dom->loadHTML(
		'<?xml encoding="UTF-8">' . $html,
		LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
	);
	libxml_clear_errors();
	libxml_use_internal_errors( $prev );

	$xpath = new DOMXPath( $dom );

	$texts   = array();
	$anchors = array();

	$text_nodes = $xpath->query( '//text()[normalize-space() != ""]' );
	if ( $text_nodes instanceof DOMNodeList ) {
		foreach ( $text_nodes as $node ) {
			$parent = $node->parentNode;
			if ( ! $parent instanceof DOMElement ) {
				continue;
			}
			if ( in_array( strtolower( $parent->nodeName ), array( 'script', 'style' ), true ) ) {
				continue;
			}
			$normalized = nvx_ticket43_normalize_text( $node->nodeValue ?? '' );
			if ( $normalized === '' ) {
				continue;
			}
			$texts[ $normalized ] = ( $texts[ $normalized ] ?? 0 ) + 1;
		}
	}

	$links = $xpath->query( '//a[@href]' );
	if ( $links instanceof DOMNodeList ) {
		foreach ( $links as $link ) {
			if ( ! $link instanceof DOMElement ) {
				continue;
			}
			$href = nvx_ticket43_normalize_text( $link->getAttribute( 'href' ) );
			$text = nvx_ticket43_normalize_text( $link->textContent ?? '' );
			if ( $href === '' ) {
				continue;
			}
			$key = $text . "\0" . $href;
			$anchors[ $key ] = ( $anchors[ $key ] ?? 0 ) + 1;
		}
	}

	$h1 = $xpath->query( '//h1' );
	$h2 = $xpath->query( '//h2' );
	$faq = $xpath->query( '//details[contains(@class,"nvx-brand-faq-item")]' );
	$treatments = $xpath->query(
		'//section[@aria-label="Tratamientos NUVANX"]//article[contains(@class,"nvx-brand-card")]'
	);

	$flat = nvx_ticket43_normalize_text( strip_tags( $html ) );

	return array(
		'texts'      => $texts,
		'anchors'    => $anchors,
		'h1'         => $h1 instanceof DOMNodeList ? $h1->length : 0,
		'h2'         => $h2 instanceof DOMNodeList ? $h2->length : 0,
		'links'      => $links instanceof DOMNodeList ? $links->length : 0,
		'faq'        => $faq instanceof DOMNodeList ? $faq->length : 0,
		'treatments' => $treatments instanceof DOMNodeList ? $treatments->length : 0,
		'cs20144'    => substr_count( $flat, 'CS20144' ),
		'cs20073'    => substr_count( $flat, 'CS20073' ),
	);
}

/**
 * @param array<string,int> $left
 * @param array<string,int> $right
 * @return array<int,string>
 */
function nvx_ticket43_diff_counts( array $left, array $right ): array {
	$errors = array();
	$keys   = array_unique( array_merge( array_keys( $left ), array_keys( $right ) ) );
	sort( $keys );

	foreach ( $keys as $key ) {
		$l = $left[ $key ] ?? 0;
		$r = $right[ $key ] ?? 0;
		if ( $l !== $r ) {
			$display = str_replace( "\0", ' | href: ', $key );
			$errors[] = "Count mismatch for \"{$display}\": canonical={$l}, candidate={$r}";
		}
	}

	return $errors;
}

$canonical_html = (string) file_get_contents( $canonical_file );
$candidate_html = (string) file_get_contents( $candidate_file );

foreach ( $banned_phrases as $phrase ) {
	if ( stripos( $candidate_html, $phrase ) !== false ) {
		fwrite( STDERR, "ERROR: banned phrase detected in candidate: {$phrase}\n" );
		exit( 1 );
	}
}

if ( stripos( $candidate_html, 'Reservar cita' ) !== false
	&& stripos( $canonical_html, 'Reservar cita' ) === false ) {
	fwrite( STDERR, "ERROR: candidate introduces banned CTA copy: Reservar cita\n" );
	exit( 1 );
}

$canonical = nvx_ticket43_extract_manifest( $canonical_html );
$candidate = nvx_ticket43_extract_manifest( $candidate_html );

$errors = array_merge(
	nvx_ticket43_diff_counts( $canonical['texts'], $candidate['texts'] ),
	nvx_ticket43_diff_counts( $canonical['anchors'], $candidate['anchors'] )
);

$scalar_fields = array( 'h1', 'h2', 'links', 'faq', 'treatments', 'cs20144', 'cs20073' );
foreach ( $scalar_fields as $field ) {
	if ( ( $canonical[ $field ] ?? 0 ) !== ( $candidate[ $field ] ?? 0 ) ) {
		$errors[] = sprintf(
			'%s count mismatch: canonical=%d, candidate=%d',
			strtoupper( $field ),
			$canonical[ $field ],
			$candidate[ $field ]
		);
	}
}

$canonical_text_keys = array_keys( $canonical['texts'] );
$candidate_text_keys = array_keys( $candidate['texts'] );
$canonical_anchor_keys = array_keys( $canonical['anchors'] );
$candidate_anchor_keys = array_keys( $candidate['anchors'] );
sort( $canonical_text_keys, SORT_STRING );
sort( $candidate_text_keys, SORT_STRING );
sort( $canonical_anchor_keys, SORT_STRING );
sort( $candidate_anchor_keys, SORT_STRING );

$canonical_text_blob   = implode( "\n", $canonical_text_keys );
$candidate_text_blob   = implode( "\n", $candidate_text_keys );
$canonical_anchor_blob = implode( "\n", $canonical_anchor_keys );
$candidate_anchor_blob = implode( "\n", $candidate_anchor_keys );

$manifest = array(
	'canonical' => array(
		'visible_text_hash' => hash( 'sha256', nvx_ticket43_normalize_text( $canonical_text_blob ) ),
		'anchor_hash'       => hash( 'sha256', $canonical_anchor_blob ),
		'h1'                => $canonical['h1'],
		'h2'                => $canonical['h2'],
		'links'             => $canonical['links'],
		'faq'               => $canonical['faq'],
		'treatments'        => $canonical['treatments'],
		'cs20144'           => $canonical['cs20144'],
		'cs20073'           => $canonical['cs20073'],
	),
	'candidate' => array(
		'visible_text_hash' => hash( 'sha256', nvx_ticket43_normalize_text( $candidate_text_blob ) ),
		'anchor_hash'       => hash( 'sha256', $candidate_anchor_blob ),
		'h1'                => $candidate['h1'],
		'h2'                => $candidate['h2'],
		'links'             => $candidate['links'],
		'faq'               => $candidate['faq'],
		'treatments'        => $candidate['treatments'],
		'cs20144'           => $candidate['cs20144'],
		'cs20073'           => $candidate['cs20073'],
	),
);

echo json_encode( $manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) . "\n";

if ( $manifest['canonical']['visible_text_hash'] !== $manifest['candidate']['visible_text_hash'] ) {
	$errors[] = 'visible_text_hash mismatch between canonical and candidate';
}
if ( $manifest['canonical']['anchor_hash'] !== $manifest['candidate']['anchor_hash'] ) {
	$errors[] = 'anchor_hash mismatch between canonical and candidate';
}

if ( $errors !== array() ) {
	fwrite( STDERR, "COPY INTEGRITY FAIL:\n" );
	foreach ( $errors as $error ) {
		fwrite( STDERR, " - {$error}\n" );
	}
	exit( 1 );
}

fwrite( STDERR, "COPY INTEGRITY OK\n" );
exit( 0 );