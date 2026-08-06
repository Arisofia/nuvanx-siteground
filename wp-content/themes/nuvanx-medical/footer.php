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

$nvx_col_one = array();
$nvx_col_two = array();
if ( is_array( $nvx_footer_published_treatments ) && ! empty( $nvx_footer_published_treatments ) ) {
	$nvx_split_at = (int) ceil( count( $nvx_footer_published_treatments ) / 2 );
	$nvx_col_one  = array_slice( $nvx_footer_published_treatments, 0, $nvx_split_at );
	$nvx_col_two  = array_slice( $nvx_footer_published_treatments, $nvx_split_at );
}

?>

<footer class="nvx-footer" role="contentinfo">
	<div class="nvx-footer__inner">

		<div class="nvx-footer__brand">
			<a
				href="<?php echo esc_url( home_url( '/' ) ); ?>"
				class="nvx-logo"
				aria-label="<?php esc_attr_e( 'NUVANX MEDICINA ESTÉTICA LÁSER — Inicio', 'nuvanx-medical' ); ?>"
			>
				<span class="nvx-logo__wordmark">NUVANX</span>
				<span class="nvx-logo__tagline">MEDICINA ESTÉTICA LÁSER</span>
				<div class="nvx-footer__social">
					<a href="https://www.instagram.com/nuvanx/" class="nvx-footer__social-link" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e( 'Síguenos en Instagram', 'nuvanx-medical' ); ?>">
						<svg class="nvx-footer__social-icon" aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line></svg>
					</a>
					<a href="https://www.facebook.com/nuvanx/" class="nvx-footer__social-link" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e( 'Síguenos en Facebook', 'nuvanx-medical' ); ?>">
						<svg class="nvx-footer__social-icon" aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg>
					</a>
				</div>
			</a>
		</div>

		<div class="nvx-footer__main">
			<div class="nvx-footer__section">
				<p class="nvx-footer__section-title">Tratamientos</p>
				<div class="nvx-footer__links-compact">
					<a href="<?php echo esc_url( home_url( '/endolift-facial-papada-mandibula/' ) ); ?>">Endolift® facial</a>
					<a href="<?php echo esc_url( home_url( '/endolaser-corporal-grasa-localizada/' ) ); ?>">Endoláser corporal</a>
					<a href="<?php echo esc_url( home_url( '/laser-co2-fraccionado-madrid-textura-cicatrices-poro/' ) ); ?>">Láser CO₂ fraccionado</a>
					<a href="<?php echo esc_url( home_url( '/exion-btl/' ) ); ?>">EXION® BTL</a>
					<?php foreach ( $nvx_col_one as $nvx_treatment ) : ?>
						<a href="<?php echo esc_url( (string) $nvx_treatment['url'] ); ?>"><?php echo esc_html( (string) $nvx_treatment['label'] ); ?></a>
					<?php endforeach; ?>
					<?php foreach ( $nvx_col_two as $nvx_treatment ) : ?>
						<a href="<?php echo esc_url( (string) $nvx_treatment['url'] ); ?>"><?php echo esc_html( (string) $nvx_treatment['label'] ); ?></a>
					<?php endforeach; ?>
					<a href="<?php echo esc_url( home_url( '/btl-exilite-ipl-madrid/' ) ); ?>">BTL EXILITE™ IPL</a>
				</div>
			</div>

			<div class="nvx-footer__section">
				<p class="nvx-footer__section-title">Clínicas</p>
				<div class="nvx-footer__links-compact">
					<a href="<?php echo esc_url( home_url( '/clinicas-de-medicina-estetica-nuvanx/' ) ); ?>">Nuestras clínicas</a>
					<a href="<?php echo esc_url( home_url( '/medicina-estetica-chamberi/' ) ); ?>">Chamberí</a>
					<a href="<?php echo esc_url( home_url( '/clinicas-de-medicina-estetica-nuvanx/medicina-estetica-goya-barrio-salamanca/' ) ); ?>">Salamanca–Goya</a>
					<a href="tel:+34669319836">Chamberí · 669 319 836</a>
					<a href="tel:+34647505107">Goya · 647 505 107</a>
					<address class="nvx-footer__address">Calle de Fernández de la Hoz, 4, Bajo Derecha, 28010 Madrid</address>
					<address class="nvx-footer__address">Calle de Fernán González, 26, 28009 Madrid</address>
				</div>
			</div>

			<div class="nvx-footer__section">
				<p class="nvx-footer__section-title">NUVANX</p>
				<div class="nvx-footer__links-compact">
					<a href="<?php echo esc_url( home_url( '/nosotros/' ) ); ?>">Nosotros</a>
					<?php if ( '' !== $nvx_why_nuvanx_url ) : ?>
						<a href="<?php echo esc_url( $nvx_why_nuvanx_url ); ?>">Por qué NUVANX</a>
					<?php endif; ?>
					<?php if ( '' !== $nvx_investment_url ) : ?>
						<a href="<?php echo esc_url( $nvx_investment_url ); ?>">Inversión</a>
					<?php endif; ?>
					<a href="<?php echo esc_url( home_url( '/equipo-medico/' ) ); ?>">Equipo médico</a>
					<?php if ( $nvx_cases_public ) : ?>
						<a href="<?php echo esc_url( home_url( '/casos-de-pacientes/' ) ); ?>">Casos de pacientes</a>
					<?php endif; ?>
					<a href="<?php echo esc_url( home_url( '/blog/' ) ); ?>">Blog</a>
					<a href="<?php echo esc_url( home_url( '/contacto/' ) ); ?>">Contacto</a>
					<a href="<?php echo esc_url( home_url( '/madrid/valoracion/' ) ); ?>">Valoración médica</a>
				</div>
			</div>
		</div>

	</div>

	<div class="nvx-footer__bottom">
		<div class="nvx-footer__bottom-inner">
			<p class="nvx-footer__copyright">
				&copy; <?php echo esc_html( wp_date( 'Y' ) ); ?> NUVANX Medicina Estética Láser en Madrid
			</p>
			<nav class="nvx-footer__legal-nav" aria-label="<?php esc_attr_e( 'Información legal', 'nuvanx-medical' ); ?>">
				<a href="<?php echo esc_url( home_url( '/aviso-legal/' ) ); ?>">Aviso legal</a>
				<span aria-hidden="true"> · </span>
				<a href="<?php echo esc_url( home_url( '/politica-privacidad/' ) ); ?>">Política de privacidad</a>
				<span aria-hidden="true"> · </span>
				<a href="<?php echo esc_url( home_url( '/politica-de-cookies-ue/' ) ); ?>">Política de cookies</a>
			</nav>
			<p class="nvx-footer__registrations">
				Chamberí · Centro sanitario autorizado CS20144 · Salamanca–Goya · Centro sanitario autorizado CS20073
			</p>
		</div>
	</div>
</footer>

		</div><!-- .nvx-brand-page -->
	</main>

<?php wp_footer(); ?>

</body>
</html>
