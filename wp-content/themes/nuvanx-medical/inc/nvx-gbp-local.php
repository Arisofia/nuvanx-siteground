<?php
/**
 * Google Business Profile contract, clinic galleries and T+7 review requests.
 *
 * Live GBP category/photos cannot be mutated from the theme. This module owns
 * the website-side gallery, the canonical profile copy, and the post-visit
 * review email. No incentives, no star coaching.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const NVX_GBP_VISIT_CPT   = 'nvx_gbp_visit';
const NVX_GBP_CRON_HOOK   = 'nvx_gbp_send_due_review_requests';
const NVX_GBP_DELAY_DAYS  = 7;

/** @return array<string,mixed> */
function nvx_gbp_profiles_catalog(): array {
	if ( ! function_exists( 'nvx_catalog_json_load' ) ) {
		return array();
	}
	$catalog = nvx_catalog_json_load( 'gbp-profiles.json' );
	return is_array( $catalog ) && empty( $catalog['_error'] ) ? $catalog : array();
}

/** @return array<string,mixed> */
function nvx_gbp_clinic_profile( string $clinic_key ): array {
	$catalog = nvx_gbp_profiles_catalog();
	$clinic  = $catalog['clinics'][ $clinic_key ] ?? null;
	return is_array( $clinic ) ? $clinic : array();
}

function nvx_gbp_primary_category(): string {
	$catalog = nvx_gbp_profiles_catalog();
	$category = trim( (string) ( $catalog['primary_category'] ?? '' ) );
	return '' !== $category ? $category : 'Clínica de medicina estética';
}

function nvx_gbp_review_url( string $clinic_key ): string {
	$profile  = nvx_gbp_clinic_profile( $clinic_key );
	$place_id = trim( (string) ( $profile['place_id'] ?? '' ) );
	if ( '' !== $place_id ) {
		return 'https://search.google.com/local/writereview?placeid=' . rawurlencode( $place_id );
	}
	$query = trim( (string) ( $profile['maps_query'] ?? '' ) );
	if ( '' === $query ) {
		return '';
	}
	return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( $query );
}

/**
 * Approved editorial photographs for a clinic landing (max 4).
 *
 * Missing files are skipped rather than replaced with vendor packshots or
 * unverified theme JPEGs.
 *
 * @return array<int,array{id:int,alt:string,caption:string}>
 */
function nvx_clinic_editorial_photo_map( string $clinic_key ): array {
	if ( 'goya' === $clinic_key ) {
		return array(
			array(
				'id'      => 2071,
				'alt'     => __( 'Fachada de NUVANX Salamanca–Goya, Madrid', 'nuvanx-medical' ),
				'caption' => __( 'Fachada', 'nuvanx-medical' ),
			),
			array(
				'id'      => 1077,
				'alt'     => __( 'Sala clínica NUVANX — Salamanca–Goya, Madrid', 'nuvanx-medical' ),
				'caption' => __( 'Sala clínica', 'nuvanx-medical' ),
			),
			array(
				'id'      => 1078,
				'alt'     => __( 'Box clínico de NUVANX Salamanca–Goya', 'nuvanx-medical' ),
				'caption' => __( 'Box clínico', 'nuvanx-medical' ),
			),
			array(
				'id'      => 2892,
				'alt'     => __( 'Consulta médica y valoración en NUVANX', 'nuvanx-medical' ),
				'caption' => __( 'Valoración médica', 'nuvanx-medical' ),
			),
		);
	}

	return array(
		array(
			'id'      => 2796,
			'alt'     => __( 'Fachada de NUVANX Chamberí, Madrid', 'nuvanx-medical' ),
			'caption' => __( 'Fachada', 'nuvanx-medical' ),
		),
		array(
			'id'      => 1632,
			'alt'     => __( 'Sala de espera de NUVANX Chamberí', 'nuvanx-medical' ),
			'caption' => __( 'Sala', 'nuvanx-medical' ),
		),
		array(
			'id'      => 1630,
			'alt'     => __( 'Sala clínica NUVANX — Chamberí, Madrid', 'nuvanx-medical' ),
			'caption' => __( 'Sala clínica', 'nuvanx-medical' ),
		),
		array(
			'id'      => 2892,
			'alt'     => __( 'Consulta médica y valoración en NUVANX', 'nuvanx-medical' ),
			'caption' => __( 'Valoración médica', 'nuvanx-medical' ),
		),
	);
}

/**
 * Theme-owned gallery for a clinic landing. Only readable attachments.
 *
 * @return array<int,array{id:int,file:string,alt:string,caption:string}>
 */
function nvx_clinic_landing_photos( string $clinic_key ): array {
	$clinic_key = 'goya' === $clinic_key ? 'goya' : 'chamberi';
	$photos     = array();

	foreach ( nvx_clinic_editorial_photo_map( $clinic_key ) as $item ) {
		$attachment_id = (int) $item['id'];
		$source_path   = get_attached_file( $attachment_id );
		if ( ! is_string( $source_path ) || '' === $source_path || ! is_readable( $source_path ) ) {
			continue;
		}

		$url = wp_get_attachment_url( $attachment_id );
		if ( ! is_string( $url ) || '' === $url ) {
			continue;
		}

		$photos[] = array(
			'id'      => $attachment_id,
			'file'    => $url,
			'alt'     => (string) $item['alt'],
			'caption' => (string) $item['caption'],
		);

		if ( count( $photos ) >= 4 ) {
			break;
		}
	}

	return $photos;
}

/** Backward-compatible Chamberí helper used by schema. */
function nvx_chamberi_landing_photos(): array {
	return nvx_clinic_landing_photos( 'chamberi' );
}

/**
 * Absolute URLs of existing clinic photos for Schema.org image.
 *
 * @return string[]
 */
function nvx_clinic_schema_image_urls( string $clinic_key ): array {
	$urls = array();
	foreach ( nvx_clinic_landing_photos( $clinic_key ) as $photo ) {
		$url = isset( $photo['id'] ) ? wp_get_attachment_url( (int) $photo['id'] ) : '';
		if ( is_string( $url ) && '' !== $url ) {
			$urls[] = $url;
		}
	}
	return $urls;
}

function nvx_chamberi_schema_image_url(): string {
	$urls = nvx_clinic_schema_image_urls( 'chamberi' );
	return $urls[0] ?? '';
}

function nvx_gbp_review_email_subject( string $clinic_key ): string {
	$profile = nvx_gbp_clinic_profile( $clinic_key );
	$name    = (string) ( $profile['name'] ?? 'NUVANX' );
	return sprintf(
		/* translators: %s: clinic name */
		__( 'Tu visita a %s', 'nuvanx-medical' ),
		$name
	);
}

function nvx_gbp_review_email_body( string $name, string $clinic_key ): string {
	$profile = nvx_gbp_clinic_profile( $clinic_key );
	$clinic  = (string) ( $profile['name'] ?? 'NUVANX' );
	$url     = nvx_gbp_review_url( $clinic_key );
	$first   = trim( $name );
	$hello   = '' !== $first
		? sprintf( /* translators: %s: first name */ __( 'Hola %s,', 'nuvanx-medical' ), $first )
		: __( 'Hola,', 'nuvanx-medical' );

	$lines   = array();
	$lines[] = $hello;
	$lines[] = '';
	$lines[] = sprintf(
		/* translators: %s: clinic name */
		__( 'Han pasado unos días desde tu visita a %s. Si quieres dejar tu opinión en Google, este es el enlace directo al perfil de la sede:', 'nuvanx-medical' ),
		$clinic
	);
	$lines[] = $url;
	$lines[] = '';
	$lines[] = __( 'No es obligatorio. No hay contraprestación ni condición asociada a esta solicitud.', 'nuvanx-medical' );
	$lines[] = '';
	$lines[] = 'NUVANX';

	return implode( "\n", $lines );
}

function nvx_gbp_register_visit_cpt(): void {
	register_post_type(
		NVX_GBP_VISIT_CPT,
		array(
			'labels'              => array(
				'name'          => __( 'Solicitudes GBP', 'nuvanx-medical' ),
				'singular_name' => __( 'Solicitud GBP', 'nuvanx-medical' ),
			),
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'capability_type'     => 'post',
			'map_meta_cap'        => true,
			'supports'            => array( 'title' ),
			'menu_icon'           => 'dashicons-star-filled',
		)
	);
}
add_action( 'init', 'nvx_gbp_register_visit_cpt' );

function nvx_gbp_schedule_cron(): void {
	if ( ! wp_next_scheduled( NVX_GBP_CRON_HOOK ) ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', NVX_GBP_CRON_HOOK );
	}
}
add_action( 'init', 'nvx_gbp_schedule_cron' );

function nvx_gbp_unschedule_cron(): void {
	$timestamp = wp_next_scheduled( NVX_GBP_CRON_HOOK );
	if ( $timestamp ) {
		wp_unschedule_event( $timestamp, NVX_GBP_CRON_HOOK );
	}
}
add_action( 'switch_theme', 'nvx_gbp_unschedule_cron' );

function nvx_gbp_visit_send_on( string $visit_date ): string {
	$time = strtotime( $visit_date . ' 09:00:00' );
	if ( false === $time ) {
		return '';
	}
	return gmdate( 'Y-m-d', $time + ( NVX_GBP_DELAY_DAYS * DAY_IN_SECONDS ) );
}

/**
 * @return int|\WP_Error
 */
function nvx_gbp_register_visit( string $name, string $email, string $clinic_key, string $visit_date ) {
	$email      = sanitize_email( $email );
	$clinic_key = 'goya' === $clinic_key ? 'goya' : 'chamberi';
	$name       = sanitize_text_field( $name );
	if ( ! is_email( $email ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $visit_date ) ) {
		return new WP_Error( 'nvx_gbp_invalid_visit', __( 'Email o fecha de visita no válidos.', 'nuvanx-medical' ) );
	}

	$send_on = nvx_gbp_visit_send_on( $visit_date );
	if ( '' === $send_on ) {
		return new WP_Error( 'nvx_gbp_invalid_date', __( 'No se pudo calcular la fecha de envío.', 'nuvanx-medical' ) );
	}

	$post_id = wp_insert_post(
		array(
			'post_type'   => NVX_GBP_VISIT_CPT,
			'post_status' => 'private',
			'post_title'  => $name . ' · ' . $clinic_key . ' · ' . $visit_date,
		),
		true
	);
	if ( is_wp_error( $post_id ) ) {
		return $post_id;
	}

	update_post_meta( $post_id, '_nvx_gbp_email', $email );
	update_post_meta( $post_id, '_nvx_gbp_clinic', $clinic_key );
	update_post_meta( $post_id, '_nvx_gbp_visit_date', $visit_date );
	update_post_meta( $post_id, '_nvx_gbp_send_on', $send_on );
	update_post_meta( $post_id, '_nvx_gbp_status', 'scheduled' );

	return (int) $post_id;
}

function nvx_gbp_send_review_email( int $post_id ): bool {
	$status = (string) get_post_meta( $post_id, '_nvx_gbp_status', true );
	if ( 'sent' === $status ) {
		return true;
	}

	$email  = sanitize_email( (string) get_post_meta( $post_id, '_nvx_gbp_email', true ) );
	$clinic = (string) get_post_meta( $post_id, '_nvx_gbp_clinic', true );
	$title  = (string) get_the_title( $post_id );
	$name   = trim( (string) explode( '·', $title )[0] );

	if ( ! is_email( $email ) || '' === nvx_gbp_review_url( $clinic ) ) {
		return false;
	}

	$sent = wp_mail(
		$email,
		nvx_gbp_review_email_subject( $clinic ),
		nvx_gbp_review_email_body( $name, $clinic )
	);
	if ( ! $sent ) {
		return false;
	}

	update_post_meta( $post_id, '_nvx_gbp_status', 'sent' );
	update_post_meta( $post_id, '_nvx_gbp_sent_at', gmdate( 'c' ) );
	return true;
}

function nvx_gbp_send_due_review_requests(): void {
	$today = current_time( 'Y-m-d' );
	$query = new WP_Query(
		array(
			'post_type'      => NVX_GBP_VISIT_CPT,
			'post_status'    => 'private',
			'posts_per_page' => 50,
			'meta_query'     => array(
				'relation' => 'AND',
				array(
					'key'     => '_nvx_gbp_status',
					'value'   => 'scheduled',
					'compare' => '=',
				),
				array(
					'key'     => '_nvx_gbp_send_on',
					'value'   => $today,
					'compare' => '<=',
					'type'    => 'DATE',
				),
			),
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);

	foreach ( $query->posts as $post_id ) {
		nvx_gbp_send_review_email( (int) $post_id );
	}
}
add_action( NVX_GBP_CRON_HOOK, 'nvx_gbp_send_due_review_requests' );

function nvx_gbp_handle_admin_register(): void {
	if ( ! is_admin() || ! current_user_can( 'edit_posts' ) ) {
		return;
	}
	if ( empty( $_POST['nvx_gbp_register_visit'] ) ) {
		return;
	}
	check_admin_referer( 'nvx_gbp_register_visit' );

	$result = nvx_gbp_register_visit(
		isset( $_POST['nvx_gbp_name'] ) ? (string) wp_unslash( $_POST['nvx_gbp_name'] ) : '',
		isset( $_POST['nvx_gbp_email'] ) ? (string) wp_unslash( $_POST['nvx_gbp_email'] ) : '',
		isset( $_POST['nvx_gbp_clinic'] ) ? (string) wp_unslash( $_POST['nvx_gbp_clinic'] ) : 'chamberi',
		isset( $_POST['nvx_gbp_visit_date'] ) ? (string) wp_unslash( $_POST['nvx_gbp_visit_date'] ) : ''
	);

	$redirect = admin_url( 'edit.php?post_type=' . NVX_GBP_VISIT_CPT );
	$redirect = add_query_arg( 'nvx_gbp', is_wp_error( $result ) ? 'error' : 'scheduled', $redirect );
	wp_safe_redirect( $redirect );
	exit;
}
add_action( 'admin_init', 'nvx_gbp_handle_admin_register' );

function nvx_gbp_admin_register_notice(): void {
	if ( empty( $_GET['nvx_gbp'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}
	$status = sanitize_key( (string) wp_unslash( $_GET['nvx_gbp'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( 'scheduled' === $status ) {
		echo '<div class="notice notice-success"><p>' . esc_html__( 'Visita registrada. El email de reseña se enviará a los 7 días.', 'nuvanx-medical' ) . '</p></div>';
	}
	if ( 'error' === $status ) {
		echo '<div class="notice notice-error"><p>' . esc_html__( 'No se pudo registrar la visita. Revisa email y fecha.', 'nuvanx-medical' ) . '</p></div>';
	}
}
add_action( 'admin_notices', 'nvx_gbp_admin_register_notice' );

function nvx_gbp_admin_register_form(): void {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || NVX_GBP_VISIT_CPT !== $screen->post_type ) {
		return;
	}
	?>
	<div class="notice notice-info">
		<p><strong><?php esc_html_e( 'Solicitud de reseña GBP a T+7', 'nuvanx-medical' ); ?></strong></p>
		<p><?php esc_html_e( 'Sin incentivos ni petición de estrellas. Solo el enlace directo al perfil de la sede.', 'nuvanx-medical' ); ?></p>
		<form method="post">
			<?php wp_nonce_field( 'nvx_gbp_register_visit' ); ?>
			<input type="hidden" name="nvx_gbp_register_visit" value="1" />
			<p>
				<label><?php esc_html_e( 'Nombre', 'nuvanx-medical' ); ?> <input type="text" name="nvx_gbp_name" required /></label>
				<label><?php esc_html_e( 'Email', 'nuvanx-medical' ); ?> <input type="email" name="nvx_gbp_email" required /></label>
				<label><?php esc_html_e( 'Sede', 'nuvanx-medical' ); ?>
					<select name="nvx_gbp_clinic">
						<option value="chamberi"><?php esc_html_e( 'Chamberí', 'nuvanx-medical' ); ?></option>
						<option value="goya"><?php esc_html_e( 'Salamanca–Goya', 'nuvanx-medical' ); ?></option>
					</select>
				</label>
				<label><?php esc_html_e( 'Fecha de visita', 'nuvanx-medical' ); ?> <input type="date" name="nvx_gbp_visit_date" required value="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>" /></label>
				<button class="button button-primary" type="submit"><?php esc_html_e( 'Programar email T+7', 'nuvanx-medical' ); ?></button>
			</p>
		</form>
	</div>
	<?php
}
add_action( 'all_admin_notices', 'nvx_gbp_admin_register_form' );
