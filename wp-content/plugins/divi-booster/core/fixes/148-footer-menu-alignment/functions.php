<?php 
if (!defined('ABSPATH')) { exit(); } // No direct access

function db148_user_css($plugin) { 
	$align = dbdb_option('148-footer-menu-alignment', 'align', 'left');
	?>
	#et-footer-nav .container {
		text-align: <?php esc_html_e($align); ?>;
	}
	<?php 
}
add_action('wp_head.css', 'db148_user_css');