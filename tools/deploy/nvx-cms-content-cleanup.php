<?php
/**
 * One-shot CMS content audit/cleanup for residual class tokens and blocks
 * that theme strippers currently neutralize at render time.
 *
 * Usage (on SiteGround host, from WordPress root):
 *   wp eval-file nvx-cms-content-cleanup.php
 *   NVX_CMS_CLEANUP_APPLY=1 wp eval-file nvx-cms-content-cleanup.php
 *
 * Default is dry-run (report only). Set NVX_CMS_CLEANUP_APPLY=1 to write.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run via: wp eval-file nvx-cms-content-cleanup.php\n" );
	exit( 1 );
}

$apply_env = getenv( 'NVX_CMS_CLEANUP_APPLY' );
$apply     = ( '1' === $apply_env || 'yes' === strtolower( (string) $apply_env ) );

/**
 * Patterns that mirror (or supersede) runtime strippers.
 *
 * @return array<int, array{id:string, pattern:string, replace:string}>
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
			'id'      => 'footer_cta_banner_duplicate',
			'pattern' => '/<section\b[^>]*\bclass=["\'][^"\']*\bnvx-cta-banner\b[^"\']*["\'][^>]*>[\s\S]{0,4000}?\bid=["\']nvx-footer-cta["\'][\s\S]{0,2000}?<\/section>/iu',
			'replace' => '',
		),
		array(
			'id'      => 'brand_section_cta',
			'pattern' => '/<section\b[^>]*\bclass=["\'][^"\']*\bnvx-brand-section--cta\b[^"\']*["\'][^>]*>[\s\S]*?<\/section>/iu',
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
	);
}

/**
 * Class-token rewrites (same map as nvx_content_strip_versioned_class_tokens).
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

$q = new WP_Query(
	array(
		'post_type' => array( 'page', 'post', 'revision' ),
		'post_status'            => array( 'publish', 'draft', 'private', 'pending', 'future', 'trash', 'auto-draft' ),
		'posts_per_page'         => -1,
		// Include revisions stored as separate rows.
		// phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post_status
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
echo "hit_totals=" . wp_json_encode( $hit_sum, JSON_UNESCAPED_UNICODE ) . "\n";
echo "examples:\n";
foreach ( array_slice( $examples, 0, 80 ) as $line ) {
	echo $line . "\n";
}
if ( count( $examples ) > 80 ) {
	echo '... ' . ( count( $examples ) - 80 ) . " more\n";
}

if ( ! $apply && $touched > 0 ) {
	echo "Re-run with -- --apply to write changes.\n";
}
