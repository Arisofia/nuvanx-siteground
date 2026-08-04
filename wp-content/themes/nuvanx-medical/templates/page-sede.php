<?php
/**
 * Template Name: Sede Local
 *
 * Uses unified nvx-brand-hero pattern with banner for consistency.
 * Content and clinic details are managed by nvx-clinics-hub functions.
 *
 * @package nuvanx-medical
 */

defined( 'ABSPATH' ) || exit;

// Get clinic-specific data for individual clinic pages
$clinics  = function_exists( 'nvx_schema_clinics' ) ? nvx_schema_clinics() : array();
$registry = function_exists( 'nvx_schema_page_registry' ) ? nvx_schema_page_registry() : array();
$config   = function_exists( 'nvx_get_clinics_config' ) ? nvx_get_clinics_config() : array();

// Determine which clinic this page represents based on URL/slug
$current_slug = get_post_field( 'post_name', get_the_ID() );
$clinic_key = 'chamberi'; // Default

if ( strpos( $current_slug, 'goya' ) !== false || strpos( $current_slug, 'salamanca' ) !== false ) {
	$clinic_key = 'goya';
}

$clinic_data = $clinics[ $clinic_key ] ?? array();
$clinic_config = $config[ $clinic_key ] ?? array();

$clinic_name = ! empty( $clinic_data['name'] ) ? $clinic_data['name'] : ( 'chamberi' === $clinic_key ? 'Centro Clínico NUVANX Chamberí' : 'Centro Clínico NUVANX Salamanca–Goya' );
$clinic_address = ! empty( $clinic_config['address'] ) ? sprintf( '%s, %s %s', $clinic_config['address'], $clinic_config['postal_code'], $clinic_config['locality'] ) : '';
$clinic_phone = ! empty( $clinic_data['telephone'] ) ? $clinic_data['telephone'] : '';
$clinic_hours = ! empty( $clinic_config['hours'] ) ? $clinic_config['hours'] : '';
$clinic_maps = ! empty( $clinic_data['hasMap'] ) ? $clinic_data['hasMap'] : '';

$phone_display = ! empty( $clinic_phone ) ? trim( chunk_split( preg_replace( '/^\+34/', '', $clinic_phone ), 3, ' ' ) ) : '';
$whatsapp_url = ! empty( $clinic_config['whatsapp_href'] ) ? $clinic_config['whatsapp_href'] : ( ! empty( $clinic_phone ) ? 'https://wa.me/' . preg_replace( '/\D/', '', $clinic_phone ) : '' );
$valoracion_url = home_url( '/madrid/valoracion/' );

get_header();
?>

<div class="nvx-brand-page nvx-sede-page">
	<section class="nvx-brand-hero" aria-labelledby="nvx-sede-hero-title">
		<div class="nvx-brand-hero__inner">
			<div class="nvx-brand-hero__copy">
				<p class="nvx-brand-kicker"><?php esc_html_e( 'Clínicas NUVANX · Madrid', 'nuvanx-medical' ); ?></p>
				<h1 id="nvx-sede-hero-title" class="nvx-brand-hero__title">
					<?php echo esc_html( $clinic_name ); ?>
				</h1>
				<p class="nvx-brand-hero__lead">
					<?php esc_html_e( 'Centro sanitario autorizado por la Consejería de Sanidad de la Comunidad de Madrid. Medicina estética avanzada con criterio clínico único.', 'nuvanx-medical' ); ?>
				</p>
				<div class="nvx-brand-actions">
					<a href="<?php echo esc_url( $valoracion_url ); ?>" class="nvx-brand-btn nvx-brand-btn--primary">
						<?php esc_html_e( 'Solicitar valoración médica', 'nuvanx-medical' ); ?>
					</a>
					<?php if ( ! empty( $whatsapp_url ) ) : ?>
						<a href="<?php echo esc_url( $whatsapp_url ); ?>" class="nvx-brand-btn nvx-brand-btn--secondary" rel="noopener noreferrer" target="_blank">
							<?php esc_html_e( 'Contactar por WhatsApp', 'nuvanx-medical' ); ?>
						</a>
					<?php endif; ?>
				</div>
				<p class="nvx-brand-meta">
					<?php
					if ( 'chamberi' === $clinic_key ) {
						esc_html_e( 'Registro sanitario: CS20144 · Chamberí, Madrid', 'nuvanx-medical' );
					} else {
						esc_html_e( 'Registro sanitario: CS20073 · Salamanca–Goya, Madrid', 'nuvanx-medical' );
					}
					?>
				</p>
			</div>
		</div>
	</section>

	<div class="nvx-brand-section-wrap">
		<section class="nvx-brand-section" aria-label="<?php esc_attr_e( 'Información de la sede', 'nuvanx-medical' ); ?>">
			<div class="nvx-container">
				<p class="nvx-brand-kicker"><?php esc_html_e( 'Datos de contacto', 'nuvanx-medical' ); ?></p>
				<h2 class="nvx-heading"><?php esc_html_e( 'Ubicación y horarios', 'nuvanx-medical' ); ?></h2>
				
				<div class="nvx-brand-grid nvx-brand-grid--2">
					<div class="nvx-brand-card">
						<h3 class="nvx-brand-subtitle"><?php esc_html_e( 'Dirección', 'nuvanx-medical' ); ?></h3>
						<p class="nvx-body">
							<svg class="nvx-icon" aria-hidden="true" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
							<?php echo esc_html( $clinic_address ); ?>
						</p>
					</div>
					
					<div class="nvx-brand-card">
						<h3 class="nvx-brand-subtitle"><?php esc_html_e( 'Teléfono', 'nuvanx-medical' ); ?></h3>
						<p class="nvx-body">
							<svg class="nvx-icon" aria-hidden="true" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
							<?php if ( ! empty( $phone_display ) ) : ?>
								<a href="<?php echo esc_url( 'tel:' . $clinic_phone ); ?>"><?php echo esc_html( $phone_display ); ?></a>
							<?php endif; ?>
						</p>
					</div>
					
					<div class="nvx-brand-card">
						<h3 class="nvx-brand-subtitle"><?php esc_html_e( 'Horario', 'nuvanx-medical' ); ?></h3>
						<p class="nvx-body">
							<svg class="nvx-icon" aria-hidden="true" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
							<?php echo esc_html( $clinic_hours ); ?>
						</p>
					</div>
					
					<?php if ( ! empty( $clinic_maps ) ) : ?>
						<div class="nvx-brand-card">
							<h3 class="nvx-brand-subtitle"><?php esc_html_e( 'Cómo llegar', 'nuvanx-medical' ); ?></h3>
							<p class="nvx-body">
								<a href="<?php echo esc_url( $clinic_maps ); ?>" class="nvx-brand-btn nvx-brand-btn--secondary" rel="noopener noreferrer" target="_blank">
									<?php esc_html_e( 'Ver en Google Maps', 'nuvanx-medical' ); ?>
								</a>
							</p>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</section>
	</div>

	<div class="entry-content nvx-page__content nvx-prose">
		<?php the_content(); ?>
	</div>
</div>

<?php get_footer(); ?>
