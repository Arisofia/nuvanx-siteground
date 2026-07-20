<?php
/**
 * Single Journal article.
 *
 * @package nuvanx-medical
 */
defined( 'ABSPATH' ) || exit;

ob_start();
?>
<div class="nvx-main nvx-blog-single">
	<?php get_template_part( 'template-parts/content/nvx-blog-single' ); ?>
</div>
<?php
$content = ob_get_clean();

set_query_var( 'nvx_shell_content', $content );
set_query_var( 'nvx_shell_skip_header', true );
set_query_var( 'nvx_shell_no_wrapper', true );
get_template_part( 'template-parts/content/nvx-page-shell' );

