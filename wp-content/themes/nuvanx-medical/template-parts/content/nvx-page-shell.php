<?php
/**
 * Shell unificado para páginas y posts (un solo diseño).
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

while ( have_posts() ) :
	the_post();
	$content          = get_post_field( 'post_content', get_the_ID() );
	$has_content_h1   = is_string( $content ) && preg_match( '/<h1\b/i', $content );
	$show_theme_title = ! $has_content_h1 && ! is_front_page();
	$classes          = array( 'nvx-page' );
	if ( is_singular( 'post' ) ) {
		$classes[] = 'nvx-page--single';
	}
	?>
<article id="post-<?php the_ID(); ?>" <?php post_class( $classes ); ?>>
	<div class="nvx-shell nvx-page__shell">
		<?php if ( $show_theme_title ) : ?>
			<header class="nvx-page__header nvx-section-intro">
				<?php if ( is_singular( 'post' ) ) : ?>
					<?php
					$cats = get_the_category();
					if ( ! empty( $cats ) ) :
						?>
						<p class="nvx-eyebrow"><?php echo esc_html( $cats[0]->name ); ?></p>
					<?php endif; ?>
				<?php endif; ?>
				<?php the_title( '<h1 class="nvx-heading">', '</h1>' ); ?>
				<?php if ( is_singular( 'post' ) ) : ?>
					<p class="nvx-lead">
						<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
						<?php if ( function_exists( 'nvx_reading_time' ) ) : ?>
							<span aria-hidden="true"> · </span><?php echo esc_html( nvx_reading_time() ); ?> de lectura
						<?php endif; ?>
					</p>
				<?php endif; ?>
			</header>
		<?php endif; ?>

		<?php if ( is_singular( 'post' ) && has_post_thumbnail() ) : ?>
			<figure class="nvx-media nvx-media--editorial nvx-page__featured">
				<?php
				the_post_thumbnail(
					'large',
					array(
						'alt'     => the_title_attribute( array( 'echo' => false ) ),
						'loading' => 'eager',
					)
				);
				?>
			</figure>
		<?php endif; ?>

		<div class="entry-content nvx-page__content nvx-prose">
			<?php the_content(); ?>
		</div>

		<?php if ( is_singular( 'post' ) ) : ?>
			<nav class="nvx-page__nav" aria-label="<?php esc_attr_e( 'Navegación entre artículos', 'nuvanx-medical' ); ?>">
				<?php
				$prev = get_previous_post();
				$next = get_next_post();
				if ( $prev ) :
					?>
					<a class="nvx-text-link" href="<?php echo esc_url( get_permalink( $prev ) ); ?>" rel="prev">&larr; <?php echo esc_html( get_the_title( $prev ) ); ?></a>
				<?php endif; ?>
				<?php if ( $next ) : ?>
					<a class="nvx-text-link" href="<?php echo esc_url( get_permalink( $next ) ); ?>" rel="next"><?php echo esc_html( get_the_title( $next ) ); ?> &rarr;</a>
				<?php endif; ?>
			</nav>
		<?php endif; ?>
	</div>
</article>
	<?php
endwhile;
