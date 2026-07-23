<?php
/**
 * Footer principal de NUVANX.
 *
 * @package NUVANX_Medical
 */

defined( 'ABSPATH' ) || exit;
?>

</main>

<?php
// Single site-wide closing CTA (same on home, tratamientos, equipo, blogs…).
if ( function_exists( 'nvx_theme_show_cta_banner' ) && nvx_theme_show_cta_banner() ) {
	if ( function_exists( 'nvx_site_closing_cta_markup' ) ) {
		echo nvx_site_closing_cta_markup(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup helper escapes.
	}
}

// Detail treatments are listed only when a corresponding WordPress page is public.
$nvx_footer_published_treatments = function_exists( 'nvx_navigation_published_treatments' )
	? nvx_navigation_published_treatments()
	: array();
$nvx_cases_public = ! function_exists( 'nvx_noindex_page_ids' ) || ! in_array( 2645, nvx_noindex_page_ids(), true );
$nvx_why_nuvanx_url = function_exists( 'nvx_strategy_published_url' ) ? nvx_strategy_published_url( 'why_nuvanx' ) : '';
$nvx_investment_url = function_exists( 'nvx_strategy_published_url' ) ? nvx_strategy_published_url( 'investment' ) : '';
?>

<footer class="nvx-footer" role="contentinfo">
	<div class="nvx-footer__inner">

		<div class="nvx-footer__logo">
			<a
				href="<?php echo esc_url( home_url( '/' ) ); ?>"
				class="nvx-logo"
				aria-label="<?php esc_attr_e( 'NUVANX MEDICINA ESTÉTICA LÁSER — Inicio', 'nuvanx-medical' ); ?>"
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
					<a href="<?php echo esc_url( home_url( '/protocolos-signature/' ) ); ?>">
						Protocolos Signature
					</a>
				</li>
				<li>
					<a href="<?php echo esc_url( home_url( '/soluciones-medicas/' ) ); ?>">
						Soluciones médicas
					</a>
				</li>
				<li>
					<a href="<?php echo esc_url( home_url( '/remodelacion-corporal-laser-madrid/' ) ); ?>">
						Remodelación corporal láser
					</a>
				</li>
				<li>
					<a href="<?php echo esc_url( home_url( '/tratamiento-postparto-abdomen-contorno-corporal-madrid/' ) ); ?>">
						Postparto y abdomen
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

				<?php if ( '' !== $nvx_why_nuvanx_url ) : ?>
					<li>
						<a href="<?php echo esc_url( $nvx_why_nuvanx_url ); ?>">
							Por qué NUVANX
						</a>
					</li>
				<?php endif; ?>

				<?php if ( '' !== $nvx_investment_url ) : ?>
					<li>
						<a href="<?php echo esc_url( $nvx_investment_url ); ?>">
							Inversión
						</a>
					</li>
				<?php endif; ?>

				<li>
					<a href="<?php echo esc_url( home_url( '/equipo-medico/' ) ); ?>">
						Equipo médico
					</a>
				</li>

					<?php if ( $nvx_cases_public ) : ?>
						<li>
							<a href="<?php echo esc_url( home_url( '/casos-de-pacientes/' ) ); ?>">
								Casos de pacientes
							</a>
						</li>
					<?php endif; ?>

				<li>
					<a href="<?php echo esc_url( home_url( '/blog/' ) ); ?>">
						<?php esc_html_e( 'Blog', 'nuvanx-medical' ); ?>
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
			MEDICINA ESTÉTICA LÁSER · CRITERIO MÉDICO Y ATENCIÓN INDIVIDUALIZADA
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
					<a href="<?php echo esc_url( home_url( '/politica-privacidad/' ) ); ?>">
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
