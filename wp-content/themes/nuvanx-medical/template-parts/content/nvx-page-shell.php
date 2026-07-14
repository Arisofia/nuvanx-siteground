<?php
/**
 * Shell unificado: article + entry-content (páginas y posts).
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

while ( have_posts() ) :
	the_post();
	$content            = get_post_field( 'post_content', get_the_ID() );
	$has_content_h1     = is_string( $content ) && preg_match( '/<h1\b/i', $content );
	$has_article_layout = is_singular( 'post' ) && is_string( $content ) && preg_match( '/\bnvx-article\b/i', $content );
	$show_theme_title   = ! $has_content_h1 && ! $has_article_layout;
	$shell_classes      = array( 'nvx-page', 'nvx-page--default' );
	if ( is_front_page() ) {
		$shell_classes[] = 'nvx-page--home';
	}
	if ( is_singular( 'post' ) ) {
		$shell_classes[] = 'nvx-page--single';
	}
	?>
<article id="post-<?php the_ID(); ?>" <?php post_class( $shell_classes ); ?>>
	<?php if ( $show_theme_title ) : ?>
	<div class="nvx-site-shell">
		<header class="nvx-page__header">
			<?php the_title( '<h1 class="nvx-page__title">', '</h1>' ); ?>
		</header>
	</div>
	<?php endif; ?>
	<div class="entry-content nvx-page__content nvx-site-flow"<?php echo is_front_page() ? ' id="nvx-site-main"' : ''; ?>>
		<?php the_content(); ?>
	</div>
</article>
	<?php
endwhile;