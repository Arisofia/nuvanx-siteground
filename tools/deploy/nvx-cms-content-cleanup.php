<?php
/**
 * One-shot CMS content audit/cleanup for residual class tokens and blocks
 * that theme strippers currently neutralize at render time.
 *
 * Usage (on SiteGround host, from WordPress root):
 *   wp eval-file tools/deploy/nvx-cms-content-cleanup.php
 *   wp eval-file tools/deploy/nvx-cms-content-cleanup.php --confirm
 *
 * Default is dry-run (report only). Append --confirm to write and take a DB backup.
 *
 * Offline self-test (no WordPress):
 *   php tools/deploy/nvx-cms-content-cleanup.php --self-test
 */

/**
 * Defines the regular-expression rules used to remove or replace retired CMS content and copy.
 *
 * @return array<int, array{id:string, pattern:string, replace:string}> Cleanup rules with identifiers, patterns, and replacement text.
 */
function nvx_cms_cleanup_rules(): array {
	return array(
		array(
			'id'      => 'v3_metodo_section',
			'pattern' => '/<section\b[^>]*class="[^"]*nvx-v3-metodo[^"]*"[^>]*>[\s\S]*?<\/section>/iu',
			'replace' => '',
		),
		array(
			'id'      => 'home_metodo_section',
			'pattern' => '/<section\b[^>]*class="[^"]*nvx-home-metodo[^"]*"[^>]*>[\s\S]*?<\/section>/iu',
			'replace' => '',
		),
		array(
			'id'      => 'v3_intro_section',
			'pattern' => '/<section\b[^>]*class="[^"]*nvx-v3-intro[^"]*"[^>]*>[\s\S]*?<\/section>/iu',
			'replace' => '',
		),
		array(
			'id'      => 'home_editorial_section',
			'pattern' => '/<section\b[^>]*class="[^"]*nvx-home-editorial[^"]*"[^>]*>[\s\S]*?<\/section>/iu',
			'replace' => '',
		),
		array(
			'id'      => 'home_protocols_section',
			'pattern' => '/<section\b[^>]*\bnvx-home-protocols\b[^>]*>[\s\S]*?<\/section>/iu',
			'replace' => '',
		),
		array(
			'id'      => 'values_dual_cta',
			'pattern' => '/\s*<div class="nvx-cta-pair nvx-values__cta"[^>]*>[\s\S]*?<\/div>/iu',
			'replace' => '',
		),
		array(
			'id'      => 'page_closing_endolift',
			'pattern' => '/<section\b[^>]*\bclass=["\'][^"\']*\bnvx-endolift-action\b[^"\']*["\'][^>]*>[\s\S]*?<\/section>/iu',
			'replace' => '',
		),
		array(
			'id'      => 'page_closing_catalog',
			'pattern' => '/<section\b[^>]*\bclass=["\'][^"\']*\bnvx-catalog-close\b[^"\']*["\'][^>]*>[\s\S]*?<\/section>/iu',
			'replace' => '',
		),
		array(
			'id'      => 'page_closing_laser',
			'pattern' => '/<section\b[^>]*\bclass=["\'][^"\']*\bnvx-laser-action\b[^"\']*["\'][^>]*>[\s\S]*?<\/section>/iu',
			'replace' => '',
		),
		array(
			'id'      => 'page_closing_aes',
			'pattern' => '/<section\b[^>]*\bclass=["\'][^"\']*\bnvx-aes-action\b[^"\']*["\'][^>]*>[\s\S]*?<\/section>/iu',
			'replace' => '',
		),
		array(
			'id'      => 'home_cta_final_band',
			'pattern' => '/<(?:section|div)\b[^>]*\bclass=["\'][^"\']*\bnvx-home-cta-final-band\b[^"\']*["\'][^>]*>[\s\S]*?<\/(?:section|div)>/iu',
			'replace' => '',
		),
		array(
			'id'      => 'home_cta_final',
			'pattern' => '/<section\b[^>]*\bclass=["\'][^"\']*\bnvx-home-cta-final\b[^"\']*["\'][^>]*>[\s\S]*?<\/section>/iu',
			'replace' => '',
		),
		array(
			'id'      => 'site_closing_cta_in_content',
			'pattern' => '/<section\b[^>]*\bid=["\']nvx-site-closing-cta["\'][^>]*>[\s\S]{0,4000}?<\/section>/iu',
			'replace' => '',
		),
		array(
			// id may sit on the open tag or deeper in the section body.
			'id'      => 'footer_cta_banner_duplicate',
			'pattern' => '/<section\b(?=[^>]*\bnvx-cta-banner\b)(?=[^>]*\bid=["\']nvx-footer-cta["\'])[^>]*>[\s\S]*?<\/section>/iu',
			'replace' => '',
		),
		array(
			'id'      => 'footer_cta_banner_duplicate_nested',
			'pattern' => '/<section\b(?=[^>]*\bnvx-cta-banner\b)[^>]*>[\s\S]{0,4000}?\bid=["\']nvx-footer-cta["\'][\s\S]{0,2000}?<\/section>/iu',
			'replace' => '',
		),
		array(
			'id'      => 'brand_section_cta',
			'pattern' => '/<section\b[^>]*\bclass=["\'][^"\']*\bnvx-brand-section--cta\b[^"\']*["\'][^>]*>[\s\S]*?<\/section>/iu',
			'replace' => '',
		),
		array(
			'id'      => 'legal_placeholder',
			'pattern' => '/<div\b[^>]*\bnvx-legal-placeholder\b[^>]*>[\s\S]*?<\/div>/iu',
			'replace' => '',
		),
		array(
			'id'      => 'retired_product_couture',
			'pattern' => '/Couture Sculpt/iu',
			'replace' => 'Contour Architecture',
		),
		array(
			'id'      => 'retired_product_contour_sculpt',
			'pattern' => '/Contour Sculpt/iu',
			'replace' => 'Contour Architecture',
		),
		array(
			'id'      => 'retired_product_eye_frame',
			'pattern' => '/Eye Frame/iu',
			'replace' => 'Profile Definition',
		),
		// Claim/copy hygiene mirrored from nvx_public_content_text_hygiene (safe rewrites).
		array(
			'id'      => 'claim_valoracion_medica_gratuita',
			'pattern' => '/\bvaloraci[oó]n\s+m[eé]dica\s+gratuita\b/iu',
			'replace' => 'valoración médica',
		),
		array(
			'id'      => 'claim_valoracion_gratuita',
			'pattern' => '/\bvaloraci[oó]n\s+gratuita\b/iu',
			'replace' => 'valoración médica',
		),
		array(
			'id'      => 'claim_valoracion_gratis',
			'pattern' => '/\bvaloraci[oó]n\s+gratis\b/iu',
			'replace' => 'valoración médica',
		),
		array(
			'id'      => 'claim_consulta_gratuita',
			'pattern' => '/\bconsulta\s+(?:m[eé]dica\s+)?gratuita\b/iu',
			'replace' => 'consulta médica',
		),
		array(
			'id'      => 'claim_sin_compromiso',
			'pattern' => '/\bsin\s+compromiso\b/iu',
			'replace' => 'sin obligación de continuar con un tratamiento',
		),
		array(
			'id'      => 'typo_exilitet',
			'pattern' => '/\bEXILITET\b/iu',
			'replace' => 'EXILITE™',
		),
		array(
			'id'      => 'slogan_mejor_version',
			'pattern' => '/Tu mejor versi[oó]n empieza aqu[ií]\.?/iu',
			'replace' => 'Reserva 15–30 min de valoración médica.',
		),
		array(
			'id'      => 'vague_sede_framing',
			'pattern' => '/enfoque m[eé]dico premium/iu',
			'replace' => 'misma dirección médica que Chamberí',
		),
	);
}

/**
 * Class-token rewrites (retired versioned home shells).
 *
 * @return array<string, string>
 */
function nvx_cms_class_token_map(): array {
	return array(
		'nvx-editorial-home-v4' => '',
		'nvx-v3-shell'          => 'nvx-shell',
		'nvx-v3-intro'          => '',
		'nvx-v3-metodo'         => '',
		'nvx-v3-tratamientos'   => 'nvx-home-tratamientos',
		'nvx-v3-direccion'      => 'nvx-home-direccion',
		'nvx-v3-cta-final'      => 'nvx-home-cta-final',
		'nvx-v3-faq'            => '',
	);
}

/**
 * Apply cleanup rules to HTML.
 *
 * @return array{html:string, hits:array<string,int>}
 */
function nvx_cms_cleanup_apply( string $html ): array {
	$hits = array();

	foreach ( nvx_cms_cleanup_rules() as $rule ) {
		$count = 0;
		$html  = preg_replace( $rule['pattern'], $rule['replace'], $html, -1, $count ) ?? $html;
		if ( $count > 0 ) {
			$hits[ $rule['id'] ] = ( $hits[ $rule['id'] ] ?? 0 ) + $count;
		}
	}

	foreach ( nvx_cms_class_token_map() as $from => $to ) {
		$count   = 0;
		$pattern = '/(?<=[\s"\'])' . preg_quote( $from, '/' ) . '(?=[\s"\'])/u';
		$html    = preg_replace( $pattern, $to, $html, -1, $count ) ?? $html;
		if ( $count > 0 ) {
			$hits[ 'class:' . $from ] = ( $hits[ 'class:' . $from ] ?? 0 ) + $count;
		}
	}

	// Collapse double spaces in class attributes after token removal.
	$html = preg_replace_callback(
		'/\bclass=(["\'])([^"\']*)\1/u',
		static function ( array $m ): string {
			$q     = $m[1];
			$clean = preg_replace( '/\s+/u', ' ', trim( $m[2] ) ) ?? $m[2];
			return 'class=' . $q . $clean . $q;
		},
		$html
	) ?? $html;

	return array(
		'html' => $html,
		'hits' => $hits,
	);
}

/**
 * Offline fixture self-test (no WordPress bootstrap).
 *
 * @return int Exit code (0 = pass).
 */
function nvx_cms_cleanup_self_test(): int {
	$fixture = <<<'HTML'
<div class="nvx-editorial-home-v4 nvx-v3-shell">
<section class="nvx-v3-metodo"><p>old method</p></section>
<section class="nvx-home-metodo"><p>old home method</p></section>
<section class="nvx-v3-intro"><p>old intro</p></section>
<section class="nvx-home-editorial"><p>old editorial</p></section>
<section class="nvx-home-protocols"><p>protocols</p></section>
<div class="nvx-cta-pair nvx-values__cta"><a href="#">CTA</a></div>
<section class="nvx-endolift-action"><p>close</p></section>
<section class="nvx-catalog-close"><p>close</p></section>
<section class="nvx-laser-action"><p>close</p></section>
<section class="nvx-aes-action"><p>close</p></section>
<section class="nvx-home-cta-final-band"><p>band</p></section>
<section class="nvx-home-cta-final"><p>final</p></section>
<section id="nvx-site-closing-cta"><p>site close</p></section>
<section class="nvx-cta-banner" id="nvx-footer-cta"><p>footer band</p></section>
<section class="nvx-brand-section--cta"><p>brand cta</p></section>
<div class="nvx-legal-placeholder"><p>legal</p></div>
<p>Couture Sculpt and Contour Sculpt and Eye Frame</p>
<p>valoración médica gratuita y valoración gratuita y valoración gratis</p>
<p>consulta gratuita sin compromiso</p>
<section class="nvx-v3-tratamientos nvx-v3-faq">cards</section>
</div>
HTML;

	$result   = nvx_cms_cleanup_apply( $fixture );
	$required = array(
		'v3_metodo_section',
		'home_metodo_section',
		'v3_intro_section',
		'home_editorial_section',
		'home_protocols_section',
		'values_dual_cta',
		'page_closing_endolift',
		'page_closing_catalog',
		'page_closing_laser',
		'page_closing_aes',
		'home_cta_final_band',
		'home_cta_final',
		'site_closing_cta_in_content',
		// Either same-tag or nested-id form of the footer CTA band.
		// (Only one is expected to fire for a given fixture.)
		'brand_section_cta',
		'legal_placeholder',
		'retired_product_couture',
		'retired_product_contour_sculpt',
		'retired_product_eye_frame',
		'claim_valoracion_medica_gratuita',
		'claim_valoracion_gratuita',
		'claim_valoracion_gratis',
		'claim_consulta_gratuita',
		'claim_sin_compromiso',
		'class:nvx-editorial-home-v4',
		'class:nvx-v3-shell',
		'class:nvx-v3-tratamientos',
		'class:nvx-v3-faq',
	);

	$missing = array();
	foreach ( $required as $id ) {
		if ( empty( $result['hits'][ $id ] ) ) {
			$missing[] = $id;
		}
	}
	if (
		empty( $result['hits']['footer_cta_banner_duplicate'] )
		&& empty( $result['hits']['footer_cta_banner_duplicate_nested'] )
	) {
		$missing[] = 'footer_cta_banner_duplicate|nested';
	}

	$leaks = array();
	foreach (
		array(
			'nvx-v3-metodo',
			'nvx-home-metodo',
			'nvx-v3-intro',
			'nvx-home-editorial',
			'nvx-home-protocols',
			'nvx-values__cta',
			'nvx-endolift-action',
			'nvx-site-closing-cta',
			'Couture Sculpt',
			'Contour Sculpt',
			'Eye Frame',
			'gratuita',
			'sin compromiso',
			'nvx-editorial-home-v4',
			'nvx-v3-shell',
			'nvx-v3-faq',
		) as $needle
	) {
		if ( false !== stripos( $result['html'], $needle ) ) {
			$leaks[] = $needle;
		}
	}

	// Expected replacements remain.
	if ( false === strpos( $result['html'], 'Contour Architecture' ) ) {
		$missing[] = 'replace:Contour Architecture';
	}
	if ( false === strpos( $result['html'], 'Profile Definition' ) ) {
		$missing[] = 'replace:Profile Definition';
	}
	if ( false === strpos( $result['html'], 'nvx-home-tratamientos' ) ) {
		$missing[] = 'replace:nvx-home-tratamientos';
	}
	if ( false === strpos( $result['html'], 'sin obligación de continuar con un tratamiento' ) ) {
		$missing[] = 'replace:sin obligación';
	}

	echo "NUVANX CMS cleanup self-test\n";
	echo 'hits=' . wp_json_encode( $result['hits'], JSON_UNESCAPED_UNICODE ) . "\n";

	if ( $missing || $leaks ) {
		echo 'FAIL missing_hits=' . wp_json_encode( $missing ) . "\n";
		echo 'FAIL residual_needles=' . wp_json_encode( $leaks ) . "\n";
		return 1;
	}

	echo "PASS all required rules fired and residual needles cleared\n";
	return 0;
}

// ---------------------------------------------------------------------------
// CLI entry: offline self-test without WordPress.
// ---------------------------------------------------------------------------
if ( ! defined( 'ABSPATH' ) ) {
	$argv_list = isset( $argv ) && is_array( $argv ) ? $argv : array();
	if ( in_array( '--self-test', $argv_list, true ) ) {
		if ( ! function_exists( 'wp_json_encode' ) ) {
			/**
			 * Minimal polyfill for offline self-test.
			 *
			 * @param mixed $data Data.
			 * @param int   $flags Flags.
			 */
			function wp_json_encode( $data, $flags = 0 ) {
				return json_encode( $data, $flags );
			}
		}
		exit( nvx_cms_cleanup_self_test() );
	}

	fwrite( STDERR, "Run via: wp eval-file tools/deploy/nvx-cms-content-cleanup.php\n" );
	fwrite( STDERR, "Or offline: php tools/deploy/nvx-cms-content-cleanup.php --self-test\n" );
	exit( 1 );
}

// ---------------------------------------------------------------------------
// WordPress host path: dry-run or apply against post_content.
// ---------------------------------------------------------------------------
$args_list = isset( $args ) && is_array( $args ) ? $args : array();
$apply     = in_array( '--confirm', $args_list, true );

if ( $apply && class_exists( 'WP_CLI' ) ) {
	echo "Creating database backup before cleanup...\n";
	WP_CLI::runcommand( 'db export nvx-cms-cleanup-backup-' . gmdate( 'Y-m-d-His' ) . '.sql' );
}

$q = new WP_Query(
	array(
		'post_type'              => array( 'page', 'post' ),
		'post_status'            => array( 'publish', 'draft', 'private', 'pending', 'future' ),
		'posts_per_page'         => -1,
		'orderby'                => 'ID',
		'order'                  => 'ASC',
		'no_found_rows'          => true,
		'update_post_meta_cache' => false,
		'update_post_term_cache' => false,
	)
);

$siteurl = (string) get_option( 'siteurl' );
$mode    = $apply ? 'APPLY' : 'DRY-RUN';
echo "NUVANX CMS cleanup mode={$mode} siteurl={$siteurl}\n";

$scanned  = 0;
$touched  = 0;
$updated  = 0;
$hit_sum  = array();
$examples = array();

foreach ( $q->posts as $post ) {
	if ( ! $post instanceof WP_Post ) {
		continue;
	}
	++$scanned;
	$content = (string) $post->post_content;
	if ( '' === trim( $content ) ) {
		continue;
	}

	$result = nvx_cms_cleanup_apply( $content );
	if ( array() === $result['hits'] || $result['html'] === $content ) {
		continue;
	}

	++$touched;
	foreach ( $result['hits'] as $id => $n ) {
		$hit_sum[ $id ] = ( $hit_sum[ $id ] ?? 0 ) + $n;
	}

	$examples[] = sprintf(
		'ID=%d type=%s status=%s slug=%s hits=%s',
		(int) $post->ID,
		$post->post_type,
		$post->post_status,
		$post->post_name,
		wp_json_encode( $result['hits'] )
	);

	if ( $apply ) {
		$ok = wp_update_post(
			array(
				'ID'           => (int) $post->ID,
				'post_content' => $result['html'],
			),
			true
		);
		if ( is_wp_error( $ok ) ) {
			echo 'ERROR update ID=' . (int) $post->ID . ' ' . $ok->get_error_message() . "\n";
		} else {
			++$updated;
		}
	}
}

echo "scanned={$scanned} dirty={$touched} updated={$updated}\n";
echo 'hit_totals=' . wp_json_encode( $hit_sum, JSON_UNESCAPED_UNICODE ) . "\n";
echo "examples:\n";
foreach ( array_slice( $examples, 0, 80 ) as $line ) {
	echo $line . "\n";
}
if ( count( $examples ) > 80 ) {
	echo '... ' . ( count( $examples ) - 80 ) . " more\n";
}

if ( ! $apply && $touched > 0 ) {
	echo "Re-run with --confirm to write changes and create a database backup.\n";
}
