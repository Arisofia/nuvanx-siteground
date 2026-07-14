<?php
defined( 'ABSPATH' ) || exit;
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'nvx-brand-page nvx-brand-page--treatment' ); ?>>
	<div class="entry-content nvx-page__content">
		<?php the_content(); ?>
	</div>
</article>
