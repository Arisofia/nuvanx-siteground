<?php
/**
 * Create/update WordPress pages for EXION Face/Body/Fractional + EMFUSION (staging2).
 *
 * Theme module nvx-btl-detail-pages.php rewrites the_content on these paths.
 *
 * Usage:
 *   NVX_BLOG_APPLY=1 wp eval-file publish-btl-detail-pages.php
 *
 * Note: reuses NVX_BLOG_APPLY env (same workflow as blog publish).
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "ERROR: wp eval-file only\n" );
	exit( 1 );
}

$apply    = ( '1' === getenv( 'NVX_BLOG_APPLY' ) || 'yes' === getenv( 'NVX_BLOG_APPLY' ) );
$expected = 'https://staging2.nuvanx.com';

if ( rtrim( (string) get_option( 'siteurl' ), '/' ) !== $expected || rtrim( (string) get_option( 'home' ), '/' ) !== $expected ) {
	fwrite( STDERR, "ERROR: staging2 URL guard\n" );
	exit( 1 );
}
if ( 'nuvanx-medical' !== wp_get_theme()->get_stylesheet() ) {
	fwrite( STDERR, "ERROR: theme\n" );
	exit( 1 );
}

$pages = array(
	array(
		'slug'    => 'exion-face',
		'title'   => 'EXION® Face en Madrid',
		'content' => '<div class="nvx-brand-page nvx-brand-page--exion-face"><p class="nvx-brand-body">EXION Face — contenido editorial gestionado por el tema NUVANX.</p></div>',
		'yoast_t' => 'EXION Face Madrid | Regeneración endógena facial | NUVANX',
		'yoast_d' => 'EXION® Face en NUVANX Madrid: RF + ultrasonido a microtemperaturas controladas. Valoración en Chamberí y Goya.',
	),
	array(
		'slug'    => 'exion-body',
		'title'   => 'EXION® Body en Madrid',
		'content' => '<div class="nvx-brand-page nvx-brand-page--exion-body"><p class="nvx-brand-body">EXION Body — contenido editorial gestionado por el tema NUVANX.</p></div>',
		'yoast_t' => 'EXION Body Madrid | Grasa localizada y tensado | NUVANX',
		'yoast_d' => 'EXION® Body en NUVANX: adiposidad localizada y retracción cutánea con refrigeración activa. Chamberí y Goya.',
	),
	array(
		'slug'    => 'exion-fractional',
		'title'   => 'EXION® Fractional RF en Madrid',
		'content' => '<div class="nvx-brand-page nvx-brand-page--exion-fractional"><p class="nvx-brand-body">EXION Fractional — contenido editorial gestionado por el tema NUVANX.</p></div>',
		'yoast_t' => 'EXION Fractional RF Madrid | Textura y cicatrices | NUVANX',
		'yoast_d' => 'EXION® Fractional RF en NUVANX Madrid: textura, poro y cicatrices con RF fraccionada controlada.',
	),
	array(
		'slug'    => 'emfusion',
		'title'   => 'EMFUSION® en Madrid',
		'content' => '<div class="nvx-brand-page nvx-brand-page--emfusion"><p class="nvx-brand-body">EMFUSION — contenido editorial gestionado por el tema NUVANX.</p></div>',
		'yoast_t' => 'EMFUSION Madrid | Barrera cutánea e infusión | NUVANX',
		'yoast_d' => 'EMFUSION® en NUVANX Madrid: microcanales acústicos DYNAMiQ™ para barrera e hidratación.',
	),
);

$results = array();

foreach ( $pages as $p ) {
	$existing = get_page_by_path( $p['slug'], OBJECT, 'page' );
	$post_id  = ( $existing instanceof WP_Post ) ? (int) $existing->ID : 0;

	// If a post (blog) already owns a similar slug path via redirect only, pages with exact slug win.
	$row = array(
		'slug'     => $p['slug'],
		'existing' => $post_id,
		'type'     => 'page',
	);

	if ( $apply ) {
		$arr = array(
			'post_title'   => $p['title'],
			'post_name'    => $p['slug'],
			'post_content' => $p['content'],
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_author'  => 1,
		);
		if ( $post_id ) {
			$arr['ID'] = $post_id;
			$res       = wp_update_post( wp_slash( $arr ), true );
		} else {
			$res = wp_insert_post( wp_slash( $arr ), true );
		}
		if ( is_wp_error( $res ) ) {
			fwrite( STDERR, $res->get_error_message() . "\n" );
			exit( 1 );
		}
		$post_id = (int) $res;
		update_post_meta( $post_id, '_yoast_wpseo_title', $p['yoast_t'] );
		update_post_meta( $post_id, '_yoast_wpseo_metadesc', $p['yoast_d'] );
		$row['post_id']   = $post_id;
		$row['permalink'] = get_permalink( $post_id );
	}

	$results[] = $row;
}

// Flush rewrite so /exion-face/ resolves to page not post guess.
if ( $apply ) {
	flush_rewrite_rules( false );
}

echo wp_json_encode(
	array(
		'mode'    => $apply ? 'apply' : 'audit',
		'results' => $results,
	),
	JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
) . "\n";

if ( ! $apply ) {
	echo "DRY_RUN_OK\n";
	exit( 0 );
}

echo "STAGING2_BTL_DETAIL_PAGES_OK\n";
exit( 0 );
