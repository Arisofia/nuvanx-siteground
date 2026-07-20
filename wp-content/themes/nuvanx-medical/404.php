<?php
/**
 * 404 — mismo lenguaje visual global.
 *
 * @package nuvanx-medical
 */
defined( 'ABSPATH' ) || exit;

ob_start();
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
$content = ob_get_clean();

set_query_var( 'nvx_shell_content', $content );
set_query_var( 'nvx_shell_skip_header', true );
set_query_var( 'nvx_shell_no_wrapper', true );
get_template_part( 'template-parts/content/nvx-page-shell' );

