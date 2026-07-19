<?php
/**
 * 404 — mismo lenguaje visual global.
 *
 * @package nuvanx-medical
 */
defined( 'ABSPATH' ) || exit;

get_header();
?>
<div class="nvx-main nvx-page">
	<header class="nvx-section-intro nvx-shell">
		<p class="nvx-eyebrow">404</p>
		<h1 class="nvx-heading"><?php esc_html_e( 'Página no encontrada', 'nuvanx-medical' ); ?></h1>
		<p class="nvx-lead"><?php esc_html_e( 'La página que buscas no existe o ha sido movida.', 'nuvanx-medical' ); ?></p>
		<p>
			<a class="nvx-button nvx-button--primary" href="<?php echo esc_url( home_url( '/' ) ); ?>">
				<?php esc_html_e( 'Volver al inicio', 'nuvanx-medical' ); ?>
			</a>
		</p>
	</header>
</div>
<?php
get_footer();

