<?php
/**
 * Create/update WordPress pages for EXION Face/Body/Fractional + EMFUSION (staging2).
 *
 * Theme module nvx-btl-detail-pages.php rewrites the_content on these paths.
 *
 * Usage:
 *   NVX_BTL_PAGES_APPLY=1 wp eval-file publish-btl-detail-pages.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "ERROR: wp eval-file only\n" );
	exit( 1 );
}

$apply_value = strtolower( trim( (string) getenv( 'NVX_BTL_PAGES_APPLY' ) ) );
$apply       = in_array( $apply_value, array( '1', 'yes', 'true' ), true );
$expected    = 'https://staging2.nuvanx.com';

if ( rtrim( (string) get_option( 'siteurl' ), '/' ) !== $expected || rtrim( (string) get_option( 'home' ), '/' ) !== $expected ) {
	fwrite( STDERR, "ERROR: staging2 URL guard\n" );
	exit( 1 );
}
if ( 'nuvanx-medical' !== wp_get_theme()->get_stylesheet() ) {
	fwrite( STDERR, "ERROR: theme\n" );
	exit( 1 );
}

if ( '' !== (string) getenv( 'NVX_BLOG_APPLY' ) ) {
	fwrite( STDERR, "ERROR: NVX_BLOG_APPLY is not accepted by the BTL page publisher. Use NVX_BTL_PAGES_APPLY.\n" );
	exit( 1 );
}

$pages = array(
	array(
		'slug'    => 'exion-face',
		'title'   => 'EXION® Face en Madrid',
		'content' => '<div class="nvx-brand-page nvx-brand-page--exion-face"><p class="nvx-brand-body">EXION Face — contenido editorial gestionado por el tema NUVANX.</p></div>',
		'yoast_t' => 'EXION Face Madrid | Calidad cutánea y valoración médica | NUVANX',
		'yoast_d' => 'EXION® Face en NUVANX Madrid: radiofrecuencia y ultrasonido para protocolos individualizados de calidad cutánea. Valoración en Chamberí y Goya.',
	),
	array(
		'slug'    => 'exion-body',
		'title'   => 'EXION® Body en Madrid',
		'content' => '<div class="nvx-brand-page nvx-brand-page--exion-body"><p class="nvx-brand-body">EXION Body — contenido editorial gestionado por el tema NUVANX.</p></div>',
		'yoast_t' => 'EXION Body Madrid | Contorno corporal y firmeza | NUVANX',
		'yoast_d' => 'EXION® Body en NUVANX Madrid: protocolo corporal con radiofrecuencia y refrigeración activa, indicado tras valoración de grasa localizada y calidad cutánea.',
	),
	array(
		'slug'    => 'exion-fractional',
		'title'   => 'EXION® Fractional RF en Madrid',
		'content' => '<div class="nvx-brand-page nvx-brand-page--exion-fractional"><p class="nvx-brand-body">EXION Fractional — contenido editorial gestionado por el tema NUVANX.</p></div>',
		'yoast_t' => 'EXION Fractional RF Madrid | Textura y cicatrices | NUVANX',
		'yoast_d' => 'EXION® Fractional RF en NUVANX Madrid: radiofrecuencia fraccionada para protocolos de textura, poro y cicatrices según diagnóstico y fototipo.',
	),
	array(
		'slug'    => 'emfusion',
		'title'   => 'EMFUSION® en Madrid',
		'content' => '<div class="nvx-brand-page nvx-brand-page--emfusion"><p class="nvx-brand-body">EMFUSION — contenido editorial gestionado por el tema NUVANX.</p></div>',
		'yoast_t' => 'EMFUSION Madrid | Hidratación y barrera cutánea | NUVANX',
		'yoast_d' => 'EMFUSION® en NUVANX Madrid: protocolo de apoyo a hidratación y barrera cutánea con tecnología DYNAMiQ™, indicado tras valoración profesional.',
	),
);

$results = array();

foreach ( $pages as $page ) {
	$existing = get_page_by_path( $page['slug'], OBJECT, 'page' );
	$post_id  = ( $existing instanceof WP_Post ) ? (int) $existing->ID : 0;
	$row      = array(
		'slug'     => $page['slug'],
		'existing' => $post_id,
		'type'     => 'page',
	);

	if ( $apply ) {
		$post_data = array(
			'post_title'   => $page['title'],
			'post_name'    => $page['slug'],
			'post_content' => $page['content'],
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_author'  => 1,
		);

		if ( $post_id ) {
			$post_data['ID'] = $post_id;
			$result          = wp_update_post( wp_slash( $post_data ), true );
		} else {
			$result = wp_insert_post( wp_slash( $post_data ), true );
		}

		if ( is_wp_error( $result ) ) {
			fwrite( STDERR, $result->get_error_message() . "\n" );
			exit( 1 );
		}

		$post_id = (int) $result;
		update_post_meta( $post_id, '_yoast_wpseo_title', $page['yoast_t'] );
		update_post_meta( $post_id, '_yoast_wpseo_metadesc', $page['yoast_d'] );
		$row['post_id']   = $post_id;
		$row['permalink'] = get_permalink( $post_id );
	}

	$results[] = $row;
}

if ( $apply ) {
	flush_rewrite_rules( false );
}

echo wp_json_encode(
	array(
		'mode'       => $apply ? 'apply' : 'audit',
		'apply_env'  => 'NVX_BTL_PAGES_APPLY',
		'results'    => $results,
	),
	JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
) . "\n";

if ( ! $apply ) {
	echo "DRY_RUN_OK\n";
	exit( 0 );
}

echo "STAGING2_BTL_DETAIL_PAGES_OK\n";
exit( 0 );
