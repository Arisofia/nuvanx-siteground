<?php
/**
 * Footer principal de NUVANX.
 *
 * @package NUVANX_Medical
 */

defined( 'ABSPATH' ) || exit;
?>

</main>

<?php if ( function_exists( 'nvx_theme_show_cta_banner' ) && nvx_theme_show_cta_banner() ) : ?>
<section class="nvx-cta-banner" aria-label="<?php esc_attr_e( 'Solicitar valoración médica', 'nuvanx-medical' ); ?>">
	<div class="nvx-cta-banner__inner">
		<div>
			<h2 class="nvx-cta-banner__title">
				<?php esc_html_e( 'Tu mejor versión empieza aquí.', 'nuvanx-medical' ); ?>
			</h2>

			<p class="nvx-cta-banner__sub">
				<?php esc_html_e( 'Solicita una valoración médica personalizada y descubre qué tratamiento puede estar indicado para tu caso.', 'nuvanx-medical' ); ?>
			</p>
		</div>

		<a
			href="<?php echo esc_url( home_url( '/madrid/valoracion/' ) ); ?>"
			class="nvx-btn nvx-btn--ghost"
			id="nvx-footer-cta"
		>
			<?php esc_html_e( 'SOLICITAR VALORACIÓN', 'nuvanx-medical' ); ?>
		</a>
	</div>
</section>
<?php endif; ?>

<footer class="nvx-footer" role="contentinfo">
	<div class="nvx-footer__inner">

		<div class="nvx-footer__logo">
			<a
				href="<?php echo esc_url( home_url( '/' ) ); ?>"
				class="nvx-logo"
				aria-label="<?php esc_attr_e( 'NUVANX — Inicio', 'nuvanx-medical' ); ?>"
			>
				<span class="nvx-logo__wordmark">NUVANX</span>
				<span class="nvx-logo__tagline">MEDICINA ESTÉTICA LÁSER</span>
			</a>
		</div>

		<div class="nvx-footer__col">
			<p class="nvx-footer__col-title">
				<?php esc_html_e( 'Tratamientos', 'nuvanx-medical' ); ?>
			</p>

			<ul class="nvx-footer__links">
				<li>
					<a href="<?php echo esc_url( home_url( '/endolift-facial-papada-mandibula/' ) ); ?>">
						Endolift® facial
					</a>
				</li>

				<li>
					<a href="<?php echo esc_url( home_url( '/endolaser-corporal-grasa-localizada/' ) ); ?>">
						Endoláser corporal
					</a>
				</li>

				<li>
					<a href="<?php echo esc_url( home_url( '/laser-co2-fraccionado-madrid-textura-cicatrices-poro/' ) ); ?>">
						Láser CO₂ fraccionado
					</a>
				</li>

				<li>
					<a href="<?php echo esc_url( home_url( '/exion-btl/' ) ); ?>">
						EXION® BTL
					</a>
				</li>

				<li>
					<a href="<?php echo esc_url( home_url( '/btl-exilite-ipl-madrid/' ) ); ?>">
						BTL EXILITE™ IPL
					</a>
				</li>

				<li>
					<a href="<?php echo esc_url( home_url( '/tratamientos/' ) ); ?>">
						Ver todos los tratamientos
					</a>
				</li>
			</ul>
		</div>

		<div class="nvx-footer__col">
			<p class="nvx-footer__col-title">
				<?php esc_html_e( 'Clínicas', 'nuvanx-medical' ); ?>
			</p>

			<ul class="nvx-footer__links">
				<li>
					<a href="<?php echo esc_url( home_url( '/clinicas-de-medicina-estetica-nuvanx/' ) ); ?>">
						Nuestras clínicas
					</a>
				</li>

				<li>
					<a href="<?php echo esc_url( home_url( '/medicina-estetica-chamberi/' ) ); ?>">
						Chamberí
					</a>
				</li>

				<li>
					<a href="<?php echo esc_url( home_url( '/clinicas-de-medicina-estetica-nuvanx/medicina-estetica-goya-barrio-salamanca/' ) ); ?>">
						Salamanca–Goya
					</a>
				</li>

				<li>
					<a href="tel:+34669319836">
						Chamberí · 669 319 836
					</a>
				</li>

				<li>
					<a href="tel:+34647505107">
						Goya · 647 505 107
					</a>
				</li>
			</ul>
		</div>

		<div class="nvx-footer__col">
			<p class="nvx-footer__col-title">
				<?php esc_html_e( 'NUVANX', 'nuvanx-medical' ); ?>
			</p>

			<ul class="nvx-footer__links">
				<li>
					<a href="<?php echo esc_url( home_url( '/nosotros/' ) ); ?>">
						Nosotros
					</a>
				</li>

				<li>
					<a href="<?php echo esc_url( home_url( '/equipo-medico/' ) ); ?>">
						Equipo médico
					</a>
				</li>

				<li>
					<a href="<?php echo esc_url( home_url( '/casos-de-pacientes/' ) ); ?>">
						Casos de pacientes
					</a>
				</li>

				<li>
					<a href="<?php echo esc_url( home_url( '/blog/' ) ); ?>">
						Journal
					</a>
				</li>

				<li>
					<a href="<?php echo esc_url( home_url( '/contacto/' ) ); ?>">
						Contacto
					</a>
				</li>

				<li>
					<a href="<?php echo esc_url( home_url( '/madrid/valoracion/' ) ); ?>">
						Valoración médica
					</a>
				</li>
			</ul>
		</div>

	</div>

	<div class="nvx-footer__bottom">

		<p class="nvx-footer__tagline">
			MEDICINA ESTÉTICA LÁSER · PRECISIÓN MÉDICA, RESULTADOS NATURALES
		</p>

		<p class="nvx-footer__legal">
			&copy;
			<?php echo esc_html( wp_date( 'Y' ) ); ?>
			NUVANX Medicina Estética Láser en Madrid
		</p>

		<nav class="nvx-footer__legal-nav" aria-label="<?php esc_attr_e( 'Información legal', 'nuvanx-medical' ); ?>">
			<ul class="nvx-footer__legal-links">
				<li>
					<a href="<?php echo esc_url( home_url( '/aviso-legal/' ) ); ?>">
						Aviso legal
					</a>
				</li>

				<li>
					<a href="<?php echo esc_url( home_url( '/politica-de-privacidad/' ) ); ?>">
						Política de privacidad
					</a>
				</li>

				<li>
					<a href="<?php echo esc_url( home_url( '/politica-de-cookies-ue/' ) ); ?>">
						Política de cookies
					</a>
				</li>
			</ul>
		</nav>

		<p class="nvx-footer__registrations">
			Chamberí · Centro sanitario autorizado CS20144
			<span aria-hidden="true"> · </span>
			Salamanca–Goya · Centro sanitario autorizado CS20073
		</p>

	</div>
</footer>

<?php wp_footer(); ?>

</body>
</html>
