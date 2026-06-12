<?php 

function db_head_js() { ?>
	<script>
	<?php do_action('db_head_js'); ?> 
	</script>
<?php	
}
add_action('wp_head', 'db_head_js');