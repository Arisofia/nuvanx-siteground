<?php
/**
 * Template Name: Contacto NUVANX
 * Template Post Type: page
 *
 * /contacto/ — NAP, mapas y rutas directas de contacto. Sin formularios.
 *
 * SEO ownership:
 * - Open Graph / Twitter → nvx-contacto-audit-fixes.php.
 * - MedicalClinic graph → nvx_schema_clinics() + Yoast graph.
 * - Canonical privacy URL → /politica-privacidad/.
 *
 * @package nuvanx-medical
 */

defined( 'ABSPATH' ) || exit;

ob_start();

$clinics = function_exists( 'nvx_schema_clinics' ) ? nvx_schema_clinics() : array();

$chamberi_phone = ! empty( $clinics['chamberi']['telephone'] ) ? (string) $clinics['chamberi']['telephone'] : '+34669319836';
$goya_phone     = ! empty( $clinics['goya']['telephone'] ) ? (string) $clinics['goya']['telephone'] : '+34647505107';

$chamberi_tel_display = trim( chunk_split( (string) preg_replace( '/^\+34/', '', $chamberi_phone ), 3, ' ' ) );
$goya_tel_display     = trim( chunk_split( (string) preg_replace( '/^\+34/', '', $goya_phone ), 3, ' ' ) );

$chamberi_maps = ! empty( $clinics['chamberi']['hasMap'] )
	? (string) $clinics['chamberi']['hasMap']
	: 'https://www.google.com/maps/search/?api=1&query=NUVANX%20C%2F%20de%20Fern%C3%A1ndez%20de%20la%20Hoz%204%2028010%20Madrid';

$goya_maps = ! empty( $clinics['goya']['hasMap'] )
	? (string) $clinics['goya']['hasMap']
	: 'https://www.google.com/maps/search/?api=1&query=NUVANX%20C%2F%20de%20Fern%C3%A1n%20Gonz%C3%A1lez%2026%2028009%20Madrid';

$chamberi_embed = 'https://maps.google.com/maps?q=' . rawurlencode( 'Calle de Fernández de la Hoz 4, 28010 Madrid, Spain' ) . '&z=16&output=embed';
$goya_embed     = 'https://maps.google.com/maps?q=' . rawurlencode( 'Calle de Fernán González 26, 28009 Madrid, Spain' ) . '&z=16&output=embed';
?>

<div class="nvx-page nvx-page--contact">
	<section class="nvx-brand-hero nvx-brand-hero--laser nvx-editorial-hero nvx-contacto-hero nvx-equipo-hero--copy-only" aria-labelledby="nvx-contact-h1">
		<div class="nvx-brand-hero__inner">
			<div class="nvx-editorial-hero__copy">
				<p class="nvx-eyebrow"><?php esc_html_e( 'Clínicas NUVANX · Madrid', 'nuvanx-medical' ); ?></p>
				<h1 id="nvx-contact-h1" class="nvx-heading">
					<?php esc_html_e( 'Clínicas NUVANX en Madrid — Chamberí y Salamanca–Goya', 'nuvanx-medical' ); ?>
				</h1>
				<p class="nvx-lead">
						<?php esc_html_e( 'Si ya sabes que quieres venir, aquí tienes todo lo que necesitas para encontrarnos. Si todavía no sabes si esto es para ti, mejor empieza por la valoración.', 'nuvanx-medical' ); ?>
				</p>
				<div class="nvx-cta-group nvx-cta-group--hero">
					<a href="<?php echo esc_url( home_url( '/madrid/valoracion/' ) ); ?>" class="nvx-btn nvx-btn--primary">
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
		</div>
	</section>

	<section class="nvx-section nvx-section--nap" aria-label="<?php esc_attr_e( 'Sedes y datos de contacto', 'nuvanx-medical' ); ?>">
		<div class="nvx-shell">
			<h2 class="nvx-heading-2"><?php esc_html_e( 'Datos de contacto y sedes autorizadas', 'nuvanx-medical' ); ?></h2>
			<p class="nvx-body"><?php esc_html_e( 'Dos consultas en el centro de Madrid, cada una con su propio registro sanitario — no es un local reformado, es una clínica de verdad, con lo que eso implica en cuanto a esterilización y seguridad.', 'nuvanx-medical' ); ?></p>

			<div class="nvx-clinics-grid">
				<article class="nvx-clinic-card" itemscope itemtype="https://schema.org/MedicalClinic">
					<meta itemprop="identifier" content="CS20144">
					<header class="nvx-clinic-card__header">
						<h3 class="nvx-clinic-card__name" itemprop="name"><?php esc_html_e( 'Centro Clínico NUVANX Chamberí', 'nuvanx-medical' ); ?></h3>
						<span class="nvx-clinic-card__reg"><?php esc_html_e( 'Registro sanitario:', 'nuvanx-medical' ); ?> <strong>CS20144</strong></span>
					</header>
					<ul class="nvx-clinic-card__data">
						<li itemprop="address" itemscope itemtype="https://schema.org/PostalAddress">
							<svg class="nvx-icon" aria-hidden="true" width="16" height="16"><use href="#icon-location"/></svg>
							<span itemprop="streetAddress"><?php esc_html_e( 'Calle de Fernández de la Hoz, 4, Bajo Derecha', 'nuvanx-medical' ); ?></span>,
							<span itemprop="postalCode">28010</span> <span itemprop="addressLocality">Madrid</span>
							<br><small><?php esc_html_e( 'A dos minutos de la Plaza de Olavide', 'nuvanx-medical' ); ?></small>
						</li>
						<li>
							<svg class="nvx-icon" aria-hidden="true" width="16" height="16"><use href="#icon-phone"/></svg>
							<a href="<?php echo esc_url( 'tel:' . $chamberi_phone ); ?>" itemprop="telephone"><?php echo esc_html( $chamberi_tel_display ); ?></a>
							· <a href="https://wa.me/34669319836" rel="noopener noreferrer" target="_blank">WhatsApp</a>
						</li>
						<li><svg class="nvx-icon" aria-hidden="true" width="16" height="16"><use href="#icon-clock"/></svg><?php esc_html_e( 'Horario de clínica: lunes a viernes, 12:00–20:00; sábados, 10:00–18:00', 'nuvanx-medical' ); ?></li>
						<li><svg class="nvx-icon" aria-hidden="true" width="16" height="16"><use href="#icon-doctor"/></svg><?php esc_html_e( 'El Dr. Rivera atiende en Chamberí los martes y jueves.', 'nuvanx-medical' ); ?></li>
					</ul>
					<div class="nvx-clinic-card__map" aria-label="<?php esc_attr_e( 'Mapa NUVANX Chamberí', 'nuvanx-medical' ); ?>">
						<iframe
							title="<?php esc_attr_e( 'Cómo llegar a NUVANX Chamberí — Calle Fernández de la Hoz 4', 'nuvanx-medical' ); ?>"
							src="<?php echo esc_url( $chamberi_embed ); ?>"
							width="100%"
							height="260"
							allowfullscreen=""
							loading="lazy"
							referrerpolicy="no-referrer-when-downgrade"></iframe>
					</div>
					<a href="<?php echo esc_url( $chamberi_maps ); ?>" class="nvx-btn nvx-btn--secondary" rel="noopener noreferrer" target="_blank">
						<?php esc_html_e( 'Cómo llegar', 'nuvanx-medical' ); ?>
					</a>
				</article>

				<article class="nvx-clinic-card" itemscope itemtype="https://schema.org/MedicalClinic">
					<meta itemprop="identifier" content="CS20073">
					<header class="nvx-clinic-card__header">
						<h3 class="nvx-clinic-card__name" itemprop="name"><?php esc_html_e( 'Centro Clínico NUVANX Salamanca–Goya', 'nuvanx-medical' ); ?></h3>
						<span class="nvx-clinic-card__reg"><?php esc_html_e( 'Registro sanitario:', 'nuvanx-medical' ); ?> <strong>CS20073</strong></span>
					</header>
					<ul class="nvx-clinic-card__data">
						<li itemprop="address" itemscope itemtype="https://schema.org/PostalAddress">
							<svg class="nvx-icon" aria-hidden="true" width="16" height="16"><use href="#icon-location"/></svg>
							<span itemprop="streetAddress"><?php esc_html_e( 'Calle de Fernán González, 26', 'nuvanx-medical' ); ?></span>,
							<span itemprop="postalCode">28009</span> <span itemprop="addressLocality">Madrid</span>
								<br><small><?php esc_html_e( 'Barrio de Salamanca, Madrid', 'nuvanx-medical' ); ?></small>
						</li>
						<li>
							<svg class="nvx-icon" aria-hidden="true" width="16" height="16"><use href="#icon-phone"/></svg>
							<a href="<?php echo esc_url( 'tel:' . $goya_phone ); ?>" itemprop="telephone"><?php echo esc_html( $goya_tel_display ); ?></a>
							· <a href="https://wa.me/34647505107" rel="noopener noreferrer" target="_blank">WhatsApp</a>
						</li>
						<li><svg class="nvx-icon" aria-hidden="true" width="16" height="16"><use href="#icon-clock"/></svg><?php esc_html_e( 'Horario de clínica: lunes a viernes, 11:00–20:00', 'nuvanx-medical' ); ?></li>
						<li><svg class="nvx-icon" aria-hidden="true" width="16" height="16"><use href="#icon-doctor"/></svg><?php esc_html_e( 'El Dr. Rivera atiende en Salamanca–Goya los miércoles.', 'nuvanx-medical' ); ?></li>
					</ul>
					<div class="nvx-clinic-card__map" aria-label="<?php esc_attr_e( 'Mapa NUVANX Salamanca–Goya', 'nuvanx-medical' ); ?>">
						<iframe
							title="<?php esc_attr_e( 'Cómo llegar a NUVANX Salamanca–Goya — Calle Fernán González 26', 'nuvanx-medical' ); ?>"
							src="<?php echo esc_url( $goya_embed ); ?>"
							width="100%"
							height="260"
							allowfullscreen=""
							loading="lazy"
							referrerpolicy="no-referrer-when-downgrade"></iframe>
					</div>
					<a href="<?php echo esc_url( $goya_maps ); ?>" class="nvx-btn nvx-btn--secondary" rel="noopener noreferrer" target="_blank">
						<?php esc_html_e( 'Cómo llegar', 'nuvanx-medical' ); ?>
					</a>
				</article>
			</div>
		</div>
	</section>

	<section class="nvx-section nvx-section--cta-secondary" aria-label="<?php esc_attr_e( 'Reservar valoración médica', 'nuvanx-medical' ); ?>">
		<div class="nvx-shell">
			<p class="nvx-cta-secondary__text"><?php esc_html_e( 'También puedes llamar directamente a cada sede:', 'nuvanx-medical' ); ?></p>
			<div class="nvx-cta-group nvx-cta-group--centered">
				<a href="<?php echo esc_url( 'tel:' . $chamberi_phone ); ?>" class="nvx-btn nvx-btn--secondary">
					<?php echo esc_html( sprintf( __( 'Chamberí · %s', 'nuvanx-medical' ), $chamberi_tel_display ) ); ?>
				</a>
				<a href="<?php echo esc_url( 'tel:' . $goya_phone ); ?>" class="nvx-btn nvx-btn--secondary">
					<?php echo esc_html( sprintf( __( 'Salamanca–Goya · %s', 'nuvanx-medical' ), $goya_tel_display ) ); ?>
				</a>
				<a href="<?php echo esc_url( home_url( '/madrid/valoracion/' ) ); ?>" class="nvx-btn nvx-btn--primary">
					<?php esc_html_e( 'Solicitar valoración', 'nuvanx-medical' ); ?>
				</a>
			</div>
		</div>
	</section>
</div>

<?php
$content = ob_get_clean();

set_query_var( 'nvx_shell_content', $content );
set_query_var( 'nvx_shell_skip_header', true );
set_query_var( 'nvx_shell_no_wrapper', true );
get_template_part( 'template-parts/content/nvx-page-shell' );
