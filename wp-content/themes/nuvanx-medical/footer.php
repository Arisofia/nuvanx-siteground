<?php
/**
 * Footer principal de NUVANX.
 *
 * @package NUVANX_Medical
 */

defined( 'ABSPATH' ) || exit;
?>

	</div><!-- .nvx-brand-page -->
</main><!-- #nvx-main -->

<?php
// Single site-wide closing CTA (same on home, tratamientos, equipo, blogs…).
if ( function_exists( 'nvx_theme_show_cta_banner' ) && nvx_theme_show_cta_banner() && function_exists( 'nvx_site_closing_cta_markup' ) ) {
	echo nvx_site_closing_cta_markup(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup helper escapes.
}

// Detail treatments are listed only when a corresponding WordPress page is public.
$nvx_footer_published_treatments = function_exists( 'nvx_navigation_published_treatments' )
	? nvx_navigation_published_treatments()
	: array();
$nvx_cases_id                    = function_exists( 'nvx_page_id_by_slug' ) ? nvx_page_id_by_slug( 'casos-de-pacientes' ) : 0;
$nvx_cases_public                = $nvx_cases_id > 0
	&& ( ! function_exists( 'nvx_noindex_page_ids' )
		|| ! in_array( $nvx_cases_id, nvx_noindex_page_ids(), true ) );
$nvx_why_nuvanx_url              = function_exists( 'nvx_strategy_published_url' ) ? nvx_strategy_published_url( 'why_nuvanx' ) : '';
$nvx_investment_url              = function_exists( 'nvx_strategy_published_url' ) ? nvx_strategy_published_url( 'investment' ) : '';
$nvx_cfg                         = function_exists( 'nvx_get_clinics_config' ) ? nvx_get_clinics_config() : array();
$nvx_cham                        = isset( $nvx_cfg['chamberi'] ) ? $nvx_cfg['chamberi'] : array(
	'phone'      => '669 319 836',
	'phone_href' => '+34669319836',
	'reg'        => 'CS20144',
);
$nvx_goya                        = isset( $nvx_cfg['goya'] ) ? $nvx_cfg['goya'] : array(
	'phone'      => '647 505 107',
	'phone_href' => '+34647505107',
	'reg'        => 'CS20073',
);
?>

<?php if ( ! ( function_exists( 'nvx_is_valoracion_page_request' ) && nvx_is_valoracion_page_request() ) ) : ?>
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
			<p class="nvx-footer__logo-sedes">Madrid · Chamberí<br>Madrid · Salamanca</p>
		</div>
		<details class="nvx-footer__col" open>
			<summary><?php esc_html_e( 'Tratamientos', 'nuvanx-medical' ); ?></summary>
			<div class="nvx-footer__col-content">
				<div class="nvx-footer__treatments-grid">
					<ul class="nvx-footer__links">
						<li><a href="<?php echo esc_url( home_url( '/endolift-facial-papada-mandibula/' ) ); ?>">Endolift® facial</a></li>
						<li><a href="<?php echo esc_url( home_url( '/endolaser-corporal-grasa-localizada/' ) ); ?>">Endoláser corporal</a></li>
						<li><a href="<?php echo esc_url( home_url( '/laser-co2-fraccionado-madrid-textura-cicatrices-poro/' ) ); ?>">Láser CO₂ fraccionado</a></li>
						<li><a href="<?php echo esc_url( home_url( '/exion-btl/' ) ); ?>">EXION® BTL</a></li>
						<?php
						// Split treatments into two columns.
						$split_at = 7;
						foreach ( $nvx_footer_published_treatments as $index => $treatment ) :
							if ( 0 === $index ) :
	
					</ul>
					<ul class="nvx-footer__links">
							<?php endif; ?>
							<li><a href="<?php echo esc_url( (string) $treatment['url'] ); ?>"><?php echo esc_html( (string) $treatment['label'] ); ?></a></li>
						<?php endforeach; ?>
					</ul>
						?>
						<li><a href="<?php echo esc_url( home_url( '/btl-exilite-ipl-madrid/' ) ); ?>">BTL EXILITE™ IPL</a></li>
						<li><a href="<?php echo esc_url( home_url( '/tratamientos/' ) ); ?>"><?php esc_html_e( 'Ver todos →', 'nuvanx-medical' ); ?></a></li>
					</ul>
				</div>
			</div>
		</details>
		
		<details class="nvx-footer__col" open>
			<summary><?php esc_html_e( 'Clínicas', 'nuvanx-medical' ); ?></summary>
			<div class="nvx-footer__col-content">
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
						<a href="tel:<?php echo esc_attr( $nvx_cham['phone_href'] ); ?>">
							Chamberí · <?php echo esc_html( $nvx_cham['phone'] ); ?>
						</a>
					</li>
					<li>
						<a href="tel:<?php echo esc_attr( $nvx_goya['phone_href'] ); ?>">
							Goya · <?php echo esc_html( $nvx_goya['phone'] ); ?>
						</a>
					</li>
				</ul>
			</div>
		</details>
		
		<details class="nvx-footer__col" open>
			<summary><?php esc_html_e( 'NUVANX', 'nuvanx-medical' ); ?></summary>
			<div class="nvx-footer__col-content">
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
		</details>
	</div>

	<div class="nvx-footer__bottom">
		<p class="nvx-footer__legal">
			&copy; <?php echo esc_html( wp_date( 'Y' ) ); ?> NUVANX. <?php esc_html_e( 'Todos los derechos reservados.', 'nuvanx-medical' ); ?>
		</p>
		<nav class="nvx-footer__legal-nav" aria-label="<?php esc_attr_e( 'Información legal', 'nuvanx-medical' ); ?>">
			<ul class="nvx-footer__legal-links">
				<li><a href="<?php echo esc_url( home_url( '/aviso-legal/' ) ); ?>"><?php esc_html_e( 'Aviso legal', 'nuvanx-medical' ); ?></a></li>
				<li><a href="<?php echo esc_url( home_url( '/politica-privacidad/' ) ); ?>"><?php esc_html_e( 'Privacidad', 'nuvanx-medical' ); ?></a></li>
				<li><a href="<?php echo esc_url( home_url( '/politica-de-cookies-ue/' ) ); ?>"><?php esc_html_e( 'Cookies', 'nuvanx-medical' ); ?></a></li>
			</ul>
			<span class="nvx-footer__bottom-separator" aria-hidden="true">·</span>
			<p class="nvx-footer__registrations">
				<?php echo esc_html( 'Chamberí · Centro sanitario autorizado ' . $nvx_cham['reg'] ); ?>
				<span aria-hidden="true"> · </span>
				<?php echo esc_html( 'Salamanca · Centro sanitario autorizado ' . $nvx_goya['reg'] ); ?>
			</p>
		</nav>
	</div>
</footer>
<?php else : ?>
<footer class="nvx-footer nvx-footer--minimal" role="contentinfo">
	<div class="nvx-footer__bottom">
		<nav class="nvx-footer__legal-nav" aria-label="<?php esc_attr_e( 'Información legal', 'nuvanx-medical' ); ?>">
			<ul class="nvx-footer__legal-links">
				<li>
					<a href="<?php echo esc_url( home_url( '/politica-privacidad/' ) ); ?>">
						Política de privacidad
					</a>
				</li>
			</ul>
		</nav>
	</div>
</footer>
<?php endif; ?>

<?php wp_footer(); ?>

</body>
</html>
