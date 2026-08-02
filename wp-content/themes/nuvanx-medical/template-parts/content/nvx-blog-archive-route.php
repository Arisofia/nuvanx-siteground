<?php
/**
 * Shared journal list route body (home / archive / search).
 *
 * WP template hierarchy still needs home.php, archive.php and search.php;
 * those files only document the route and load this partial.
 *
 * @package nuvanx-medical
 */
defined( 'ABSPATH' ) || exit;

ob_start();
?>
<div class="nvx-main nvx-blog-index">
	<?php get_template_part( 'template-parts/content/nvx-blog-archive' ); ?>
</div>
<?php
$content = ob_get_clean();

set_query_var( 'nvx_shell_content', $content );
set_query_var( 'nvx_shell_skip_header', true );
set_query_var( 'nvx_shell_no_wrapper', true );
get_template_part( 'template-parts/content/nvx-page-shell' );
