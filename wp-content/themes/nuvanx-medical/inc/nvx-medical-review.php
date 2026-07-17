<?php
/**
 * Explicit medical-review provenance for approved clinical pages.
 *
 * Nothing is displayed or added to schema unless the page has a complete,
 * explicit approval record in post meta. This prevents false reviewer claims.
 *
 * Required post meta:
 * - `_nvx_medical_review_status`   = `approved`
 * - `_nvx_medical_reviewer`        = registered reviewer key
 * - `_nvx_medical_review_date`     = YYYY-MM-DD
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @return array<string,array{name:string,license:string,url:string,id:string}> */
function nvx_medical_reviewers(): array {
	$colegiado = defined( 'NVX_DIRECTOR_COLEGIADO' ) ? (string) NVX_DIRECTOR_COLEGIADO : '282864786';
	$url       = home_url( '/equipo-medico/#physician-rivera-tejeda' );

	return array(
		'rivera' => array(
			'name'    => 'Dr. José Javier Rivera Tejeda',
			'license' => $colegiado,
			'url'     => $url,
			'id'      => $url,
		),
	);
}

/** Validate an ISO calendar date without silently correcting it. */
function nvx_medical_review_valid_date( string $date ): bool {
	if ( ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $date, $match ) ) {
		return false;
	}

	return checkdate( (int) $match[2], (int) $match[3], (int) $match[1] );
}

/** Restrict review provenance to registered treatment pages. */
function nvx_medical_review_supported_page( int $post_id ): bool {
	if ( $post_id <= 0 || ! function_exists( 'nvx_schema_resolve_treatment_key' ) ) {
		return false;
	}

	return null !== nvx_schema_resolve_treatment_key( $post_id );
}

/**
 * Return one complete approval record or null.
 *
 * @return array{reviewer_key:string,name:string,license:string,url:string,id:string,date:string,date_label:string}|null
 */
function nvx_medical_review_record( int $post_id = 0 ): ?array {
	$post_id = $post_id > 0 ? $post_id : (int) get_queried_object_id();
	if ( ! nvx_medical_review_supported_page( $post_id ) ) {
		return null;
	}

	$status       = strtolower( trim( (string) get_post_meta( $post_id, '_nvx_medical_review_status', true ) ) );
	$reviewer_key = strtolower( trim( (string) get_post_meta( $post_id, '_nvx_medical_reviewer', true ) ) );
	$date         = trim( (string) get_post_meta( $post_id, '_nvx_medical_review_date', true ) );
	$reviewers    = nvx_medical_reviewers();

	if ( 'approved' !== $status || ! isset( $reviewers[ $reviewer_key ] ) || ! nvx_medical_review_valid_date( $date ) ) {
		return null;
	}

	$reviewer = $reviewers[ $reviewer_key ];
	$time     = strtotime( $date . ' 12:00:00' );
	if ( false === $time ) {
		return null;
	}

	return array(
		'reviewer_key' => $reviewer_key,
		'name'         => $reviewer['name'],
		'license'      => $reviewer['license'],
		'url'          => $reviewer['url'],
		'id'           => $reviewer['id'],
		'date'         => $date,
		'date_label'   => wp_date( 'j \d\e F \d\e Y', $time ),
	);
}

/** Build the visible reviewer disclosure from an approved record. */
function nvx_medical_review_markup( array $record ): string {
	$html  = '<aside class="nvx-medical-review" aria-label="Revisión médica" data-nvx-medical-review="approved">';
	$html .= '<p class="nvx-medical-review__kicker">' . esc_html__( 'Revisión médica', 'nuvanx-medical' ) . '</p>';
	$html .= '<p class="nvx-medical-review__identity">';
	$html .= esc_html__( 'Contenido revisado médicamente por ', 'nuvanx-medical' );
	$html .= '<a class="nvx-medical-review__reviewer" href="' . esc_url( $record['url'] ) . '">' . esc_html( $record['name'] ) . '</a>';
	$html .= ' · ' . esc_html__( 'Colegiado ICOMEM Nº', 'nuvanx-medical' ) . ' ' . esc_html( $record['license'] );
	$html .= '</p>';
	$html .= '<p class="nvx-medical-review__date">' . esc_html__( 'Última revisión clínica:', 'nuvanx-medical' ) . ' ';
	$html .= '<time datetime="' . esc_attr( $record['date'] ) . '">' . esc_html( $record['date_label'] ) . '</time></p>';
	$html .= '</aside>';

	return $html;
}

/** Append provenance only to the approved treatment content itself. */
function nvx_medical_review_append( string $content ): string {
	if (
		is_admin()
		|| wp_doing_ajax()
		|| ( defined( 'REST_REQUEST' ) && REST_REQUEST )
		|| ! is_singular( 'page' )
		|| false !== strpos( $content, 'data-nvx-medical-review="approved"' )
	) {
		return $content;
	}

	$record = nvx_medical_review_record();
	return null === $record ? $content : $content . nvx_medical_review_markup( $record );
}
add_filter( 'the_content', 'nvx_medical_review_append', 145 );

/** Load component CSS only when a valid approval record exists. */
function nvx_medical_review_enqueue(): void {
	if ( null === nvx_medical_review_record() ) {
		return;
	}

	$relative = '/assets/css/nvx-medical-review.css';
	$file     = get_stylesheet_directory() . $relative;
	wp_enqueue_style(
		'nvx-medical-review',
		get_stylesheet_directory_uri() . $relative,
		array( 'nvx-components' ),
		file_exists( $file ) ? (string) filemtime( $file ) : null
	);
}
add_action( 'wp_enqueue_scripts', 'nvx_medical_review_enqueue', 45 );

/** Test whether a schema type contains WebPage. */
function nvx_medical_review_schema_has_type( $types, string $type ): bool {
	return in_array( $type, is_array( $types ) ? $types : array( $types ), true );
}

/** Add reviewedBy only when the same reviewer disclosure is visible. */
function nvx_medical_review_schema_graph( $graph ) {
	$record = nvx_medical_review_record();
	if ( null === $record || ! is_array( $graph ) ) {
		return $graph;
	}

	foreach ( $graph as $index => $piece ) {
		if (
			is_array( $piece )
			&& isset( $piece['@type'] )
			&& nvx_medical_review_schema_has_type( $piece['@type'], 'WebPage' )
		) {
			$graph[ $index ]['reviewedBy'] = array( '@id' => $record['id'] );
		}
	}

	return $graph;
}
add_filter( 'wpseo_schema_graph', 'nvx_medical_review_schema_graph', 120, 1 );
