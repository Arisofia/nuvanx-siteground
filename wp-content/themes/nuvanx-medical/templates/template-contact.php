<?php
/**
 * Template Name: Contacto NUVANX
 * Template Post Type: page
 *
 * /contacto/ — NAP visible, formularios y CTAs.
 *
 * SEO ownership (do not reintroduce here):
 * - Open Graph / Twitter image, titles, descriptions → nvx-contacto-audit-fixes.php (Yoast filters)
 * - MedicalClinic graph → nvx_schema_clinics() + nvx_contacto_audit_schema_graph() (Yoast graph)
 * - Canonical privacy URL → /politica-privacidad/ (legacy /politica-de-privacidad/ 301s)
 *
 * Clinic hours match nvx_schema_clinics() (not generic placeholder hours).
 * Coordinates are intentionally omitted until independently verified.
 *
 * @package nuvanx-medical
 */

defined( 'ABSPATH' ) || exit;

get_header();

// Single source of truth when the schema registry is available.
$clinics = function_exists( 'nvx_schema_clinics' ) ? nvx_schema_clinics() : array();
$chamberi_phone = ! empty( $clinics['chamberi']['telephone'] ) ? (string) $clinics['chamberi']['telephone'] : '+34669319836';
$goya_phone     = ! empty( $clinics['goya']['telephone'] ) ? (string) $clinics['goya']['telephone'] : '+34647505107';
$chamberi_tel_display = preg_replace( '/^\+34/', '', $chamberi_phone );
$chamberi_tel_display = trim( chunk_split( (string) $chamberi_tel_display, 3, ' ' ) );
$goya_tel_display     = preg_replace( '/^\+34/', '', $goya_phone );
$goya_tel_display     = trim( chunk_split( (string) $goya_tel_display, 3, ' ' ) );
$chamberi_maps = ! empty( $clinics['chamberi']['hasMap'] )
	? (string) $clinics['chamberi']['hasMap']
	: 'https://www.google.com/maps/search/?api=1&query=NUVANX%20C%2F%20de%20Fern%C3%A1ndez%20de%20la%20Hoz%204%2028010%20Madrid';
$goya_maps = ! empty( $clinics['goya']['hasMap'] )
	? (string) $clinics['goya']['hasMap']
	: 'https://www.google.com/maps/search/?api=1&query=NUVANX%20C%2F%20de%20Fern%C3%A1n%20Gonz%C3%A1lez%2026%2028009%20Madrid';
// Address-based embeds (no approximate lat/lng in markup).
$chamberi_embed = 'https://maps.google.com/maps?q=' . rawurlencode( 'Calle de Fernández de la Hoz 4, 28010 Madrid, Spain' ) . '&z=16&output=embed';
$goya_embed     = 'https://maps.google.com/maps?q=' . rawurlencode( 'Calle de Fernán González 26, 28009 Madrid, Spain' ) . '&z=16&output=embed';
?>

<?php // Theme shell already opens the primary main landmark — page content uses a div wrapper. ?>
<div class="nvx-page nvx-page--contact">

	<?php /* ── HERO ─────────────────────────────────────────────────────── */ ?>
	<section class="nvx-section nvx-section--contact-hero" aria-labelledby="nvx-contact-h1">
		<div class="nvx-shell">

			<p class="nvx-eyebrow"><?php esc_html_e( 'Clínicas NUVANX · Madrid', 'nuvanx-medical' ); ?></p>

			<h1 id="nvx-contact-h1" class="nvx-heading-1">
				<?php esc_html_e( 'Clínicas NUVANX en Madrid — Chamberí y Salamanca–Goya', 'nuvanx-medical' ); ?>
			</h1>

			<p class="nvx-lead">
				<?php esc_html_e( 'Valoración médica presencial en Chamberí o Salamanca–Goya. Respuesta en menos de 24 horas laborables. El equipo te orientará hacia la sede y el médico disponible.', 'nuvanx-medical' ); ?>
			</p>

			<div class="nvx-cta-group">
				<a href="<?php echo esc_url( home_url( '/madrid/valoracion/' ) ); ?>"
					class="nvx-btn nvx-btn--primary nvx-open-valoracion-modal"
					data-nvx-valoracion-modal="1"
					aria-haspopup="dialog">
					<?php esc_html_e( 'Solicitar valoración médica', 'nuvanx-medical' ); ?>
				</a>
				<a href="https://wa.me/34669319836"
					class="nvx-btn nvx-btn--secondary"
					rel="noopener noreferrer"
					target="_blank"
					aria-label="<?php esc_attr_e( 'Contactar por WhatsApp con NUVANX', 'nuvanx-medical' ); ?>">
					<?php esc_html_e( 'Contactar por WhatsApp', 'nuvanx-medical' ); ?>
				</a>
			</div>

		</div>
	</section>

	<?php /* ── NAP ──────────────────────────────────────────────────────── */ ?>
	<section class="nvx-section nvx-section--nap" aria-label="<?php esc_attr_e( 'Sedes y datos de contacto', 'nuvanx-medical' ); ?>">
		<div class="nvx-shell">

			<h2 class="nvx-heading-2"><?php esc_html_e( 'Datos de contacto y sedes autorizadas', 'nuvanx-medical' ); ?></h2>
			<p class="nvx-body">
				<?php esc_html_e( 'Centros de medicina estética autorizados por la Comunidad de Madrid.', 'nuvanx-medical' ); ?>
			</p>

			<div class="nvx-clinics-grid">

				<?php /* Chamberí */ ?>
				<article class="nvx-clinic-card" itemscope itemtype="https://schema.org/MedicalClinic">
					<meta itemprop="identifier" content="CS20144">

					<header class="nvx-clinic-card__header">
						<h3 class="nvx-clinic-card__name" itemprop="name">
							<?php esc_html_e( 'Centro Clínico NUVANX Chamberí', 'nuvanx-medical' ); ?>
						</h3>
						<span class="nvx-clinic-card__reg">
							<?php esc_html_e( 'Registro sanitario:', 'nuvanx-medical' ); ?> <strong>CS20144</strong>
						</span>
					</header>

					<ul class="nvx-clinic-card__data" role="list">
						<li itemprop="address" itemscope itemtype="https://schema.org/PostalAddress">
							<svg class="nvx-icon" aria-hidden="true" width="16" height="16"><use href="#icon-location"/></svg>
							<span itemprop="streetAddress"><?php esc_html_e( 'Calle de Fernández de la Hoz, 4, Bajo Derecha', 'nuvanx-medical' ); ?></span>,
							<span itemprop="postalCode">28010</span>
							<span itemprop="addressLocality">Madrid</span>
							<br><small><?php esc_html_e( 'A dos minutos de la Plaza de Olavide', 'nuvanx-medical' ); ?></small>
						</li>
						<li>
							<svg class="nvx-icon" aria-hidden="true" width="16" height="16"><use href="#icon-phone"/></svg>
							<a href="<?php echo esc_url( 'tel:' . $chamberi_phone ); ?>" itemprop="telephone" aria-label="<?php esc_attr_e( 'Llamar a NUVANX Chamberí', 'nuvanx-medical' ); ?>"><?php echo esc_html( $chamberi_tel_display ); ?></a>
							· <a href="https://wa.me/34669319836" rel="noopener noreferrer" target="_blank" aria-label="<?php esc_attr_e( 'Contactar por WhatsApp con NUVANX Chamberí', 'nuvanx-medical' ); ?>">WhatsApp</a>
						</li>
						<li>
							<svg class="nvx-icon" aria-hidden="true" width="16" height="16"><use href="#icon-clock"/></svg>
							<?php esc_html_e( 'Horario de clínica: lunes a viernes, 12:00–20:00; sábados, 10:00–18:00', 'nuvanx-medical' ); ?>
						</li>
						<li>
							<svg class="nvx-icon" aria-hidden="true" width="16" height="16"><use href="#icon-doctor"/></svg>
							<?php esc_html_e( 'El Dr. Rivera atiende en Chamberí los martes y jueves.', 'nuvanx-medical' ); ?>
						</li>
					</ul>

					<div class="nvx-clinic-card__map" aria-label="<?php esc_attr_e( 'Mapa NUVANX Chamberí', 'nuvanx-medical' ); ?>">
						<iframe
							title="<?php esc_attr_e( 'Cómo llegar a NUVANX Chamberí — Calle Fernández de la Hoz 4', 'nuvanx-medical' ); ?>"
							src="<?php echo esc_url( $chamberi_embed ); ?>"
							width="100%"
							height="260"
							style="border:0;"
							allowfullscreen=""
							loading="lazy"
							referrerpolicy="no-referrer-when-downgrade">
						</iframe>
					</div>

					<a href="<?php echo esc_url( $chamberi_maps ); ?>"
						class="nvx-btn nvx-btn--secondary"
						rel="noopener noreferrer"
						target="_blank"
						aria-label="<?php esc_attr_e( 'Cómo llegar a NUVANX Chamberí', 'nuvanx-medical' ); ?>">
						<?php esc_html_e( 'Cómo llegar', 'nuvanx-medical' ); ?>
					</a>
				</article>

				<?php /* Salamanca–Goya */ ?>
				<article class="nvx-clinic-card" itemscope itemtype="https://schema.org/MedicalClinic">
					<meta itemprop="identifier" content="CS20073">

					<header class="nvx-clinic-card__header">
						<h3 class="nvx-clinic-card__name" itemprop="name">
							<?php esc_html_e( 'Centro Clínico NUVANX Salamanca–Goya', 'nuvanx-medical' ); ?>
						</h3>
						<span class="nvx-clinic-card__reg">
							<?php esc_html_e( 'Registro sanitario:', 'nuvanx-medical' ); ?> <strong>CS20073</strong>
						</span>
					</header>

					<ul class="nvx-clinic-card__data" role="list">
						<li itemprop="address" itemscope itemtype="https://schema.org/PostalAddress">
							<svg class="nvx-icon" aria-hidden="true" width="16" height="16"><use href="#icon-location"/></svg>
							<span itemprop="streetAddress"><?php esc_html_e( 'Calle de Fernán González, 26', 'nuvanx-medical' ); ?></span>,
							<span itemprop="postalCode">28009</span>
							<span itemprop="addressLocality">Madrid</span>
							<br><small><?php esc_html_e( 'Entre Goya y Diego de León, Barrio de Salamanca', 'nuvanx-medical' ); ?></small>
						</li>
						<li>
							<svg class="nvx-icon" aria-hidden="true" width="16" height="16"><use href="#icon-phone"/></svg>
							<a href="<?php echo esc_url( 'tel:' . $goya_phone ); ?>" itemprop="telephone" aria-label="<?php esc_attr_e( 'Llamar a NUVANX Salamanca–Goya', 'nuvanx-medical' ); ?>"><?php echo esc_html( $goya_tel_display ); ?></a>
							· <a href="https://wa.me/34647505107" rel="noopener noreferrer" target="_blank" aria-label="<?php esc_attr_e( 'Contactar por WhatsApp con NUVANX Salamanca–Goya', 'nuvanx-medical' ); ?>">WhatsApp</a>
						</li>
						<li>
							<svg class="nvx-icon" aria-hidden="true" width="16" height="16"><use href="#icon-clock"/></svg>
							<?php esc_html_e( 'Horario de clínica: lunes a viernes, 11:00–20:00', 'nuvanx-medical' ); ?>
						</li>
						<li>
							<svg class="nvx-icon" aria-hidden="true" width="16" height="16"><use href="#icon-doctor"/></svg>
							<?php esc_html_e( 'El Dr. Rivera atiende en Salamanca–Goya los miércoles.', 'nuvanx-medical' ); ?>
						</li>
					</ul>

					<div class="nvx-clinic-card__map" aria-label="<?php esc_attr_e( 'Mapa NUVANX Salamanca–Goya', 'nuvanx-medical' ); ?>">
						<iframe
							title="<?php esc_attr_e( 'Cómo llegar a NUVANX Salamanca–Goya — Calle Fernán González 26', 'nuvanx-medical' ); ?>"
							src="<?php echo esc_url( $goya_embed ); ?>"
							width="100%"
							height="260"
							style="border:0;"
							allowfullscreen=""
							loading="lazy"
							referrerpolicy="no-referrer-when-downgrade">
						</iframe>
					</div>

					<a href="<?php echo esc_url( $goya_maps ); ?>"
						class="nvx-btn nvx-btn--secondary"
						rel="noopener noreferrer"
						target="_blank"
						aria-label="<?php esc_attr_e( 'Cómo llegar a NUVANX Salamanca–Goya', 'nuvanx-medical' ); ?>">
						<?php esc_html_e( 'Cómo llegar', 'nuvanx-medical' ); ?>
					</a>
				</article>

			</div><!-- .nvx-clinics-grid -->

		</div>
	</section>

	<?php /* ── FORMULARIO DE CONTACTO ──────────────────────────────────── */ ?>
	<section class="nvx-section nvx-section--contact-form" aria-labelledby="nvx-form-heading">
		<div class="nvx-shell nvx-reading">
			<h2 id="nvx-form-heading" class="nvx-heading-2">
				<?php esc_html_e( 'Contacta con el equipo NUVANX', 'nuvanx-medical' ); ?>
			</h2>
			<p class="nvx-body">
				<?php esc_html_e( 'Déjanos tus datos y tu consulta. Te responderemos en menos de 24 horas laborables.', 'nuvanx-medical' ); ?>
			</p>
			<div id="nvx-contacto-hubspot-form" class="nvx-form nvx-form--contact" aria-live="polite">
				<?php
				if ( function_exists( 'nvx_contacto_hubspot_form_markup' ) ) {
					echo nvx_contacto_hubspot_form_markup(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by the MU-plugin renderer.
				}
				?>
			</div>
		</div>
	</section>

	<?php /* ── CTA SECUNDARIO ─────────────────────────────────────────── */ ?>
	<section class="nvx-section nvx-section--cta-secondary" aria-label="<?php esc_attr_e( 'Reservar valoración médica', 'nuvanx-medical' ); ?>">
		<div class="nvx-shell">
			<p class="nvx-cta-secondary__text">
				<?php esc_html_e( 'También puedes llamar directamente a cada sede:', 'nuvanx-medical' ); ?>
			</p>
			<div class="nvx-cta-group nvx-cta-group--centered">
				<a href="<?php echo esc_url( 'tel:' . $chamberi_phone ); ?>" class="nvx-btn nvx-btn--secondary">
					<?php echo esc_html( sprintf( /* translators: %s: phone */ __( 'Chamberí · %s', 'nuvanx-medical' ), $chamberi_tel_display ) ); ?>
				</a>
				<a href="<?php echo esc_url( 'tel:' . $goya_phone ); ?>" class="nvx-btn nvx-btn--secondary">
					<?php echo esc_html( sprintf( /* translators: %s: phone */ __( 'Salamanca–Goya · %s', 'nuvanx-medical' ), $goya_tel_display ) ); ?>
				</a>
				<a href="<?php echo esc_url( home_url( '/madrid/valoracion/' ) ); ?>"
					class="nvx-btn nvx-btn--primary nvx-open-valoracion-modal"
					data-nvx-valoracion-modal="1"
					aria-haspopup="dialog">
					<?php esc_html_e( 'Solicitar valoración', 'nuvanx-medical' ); ?>
				</a>
			</div>
		</div>
	</section>

</div><!-- .nvx-page--contact -->

<?php
get_footer();
