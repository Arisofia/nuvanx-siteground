<?php
/**
 * Single Journal article.
 *
 * @package nuvanx-medical
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

while ( have_posts() ) :
	the_post();

	$categories = get_the_category();
	$primary    = ! empty( $categories ) ? $categories[0] : null;
	$previous   = get_previous_post();
	$next       = get_next_post();
	$tags       = get_the_tags();
	$hero_class = has_post_thumbnail() ? 'nvx-blog-hero' : 'nvx-blog-hero nvx-blog-hero--text-only';
	?>
	<article id="post-<?php the_ID(); ?>" <?php post_class( 'nvx-blog-article' ); ?>>
		<header class="<?php echo esc_attr( $hero_class ); ?>" aria-labelledby="nvx-blog-title-<?php the_ID(); ?>">
			<div class="nvx-shell nvx-blog-hero__inner">
				<div class="nvx-blog-hero__copy">
					<?php if ( $primary instanceof WP_Term ) : ?>
						<a class="nvx-blog-hero__category" href="<?php echo esc_url( get_category_link( $primary ) ); ?>"><?php echo esc_html( $primary->name ); ?></a>
					<?php else : ?>
						<span class="nvx-blog-hero__category"><?php esc_html_e( 'NUVANX Journal', 'nuvanx-medical' ); ?></span>
					<?php endif; ?>

					<?php the_title( '<h1 id="nvx-blog-title-' . get_the_ID() . '" class="nvx-blog-hero__title">', '</h1>' ); ?>

					<?php if ( has_excerpt() ) : ?>
						<p class="nvx-blog-hero__excerpt"><?php echo esc_html( get_the_excerpt() ); ?></p>
					<?php endif; ?>

					<div class="nvx-blog-hero__meta">
						<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
						<?php if ( function_exists( 'nvx_reading_time' ) ) : ?>
							<span><?php echo esc_html( nvx_reading_time() ); ?> <?php esc_html_e( 'de lectura', 'nuvanx-medical' ); ?></span>
						<?php endif; ?>
					</div>
				</div>

				<?php if ( has_post_thumbnail() ) : ?>
					<figure class="nvx-blog-hero__media">
						<?php
						the_post_thumbnail(
							'full',
							array(
								'alt'           => the_title_attribute( array( 'echo' => false ) ),
								'loading'       => 'eager',
								'fetchpriority' => 'high',
							)
						);
						?>
					</figure>
				<?php endif; ?>
			</div>
		</header>

		<div class="nvx-blog-article__stage">
			<div class="nvx-blog-article__shell">
				<div class="entry-content nvx-blog-prose">
					<?php
					the_content();
					wp_link_pages(
						array(
							'before' => '<nav class="nvx-blog-pagination" aria-label="' . esc_attr__( 'Páginas del artículo', 'nuvanx-medical' ) . '">',
							'after'  => '</nav>',
						)
					);
					?>
				</div>

				<footer class="nvx-blog-article__footer">
					<p class="nvx-blog-article__notice"><?php esc_html_e( 'Contenido informativo. La indicación, los parámetros y el plan de tratamiento deben confirmarse mediante valoración médica individual.', 'nuvanx-medical' ); ?></p>

					<?php if ( ! empty( $categories ) || ! empty( $tags ) ) : ?>
						<div class="nvx-blog-article__terms" aria-label="<?php esc_attr_e( 'Temas del artículo', 'nuvanx-medical' ); ?>">
							<?php foreach ( $categories as $category ) : ?>
								<a href="<?php echo esc_url( get_category_link( $category ) ); ?>"><?php echo esc_html( $category->name ); ?></a>
							<?php endforeach; ?>
							<?php if ( ! empty( $tags ) ) : ?>
								<?php foreach ( $tags as $tag ) : ?>
									<a href="<?php echo esc_url( get_tag_link( $tag ) ); ?>">#<?php echo esc_html( $tag->name ); ?></a>
								<?php endforeach; ?>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				</footer>

				<?php if ( $previous || $next ) : ?>
					<nav class="nvx-blog-nav" aria-label="<?php esc_attr_e( 'Navegación entre artículos', 'nuvanx-medical' ); ?>">
						<?php if ( $previous ) : ?>
							<a class="nvx-blog-nav__item nvx-blog-nav__item--previous" href="<?php echo esc_url( get_permalink( $previous ) ); ?>" rel="prev">
								<span class="nvx-blog-nav__label"><?php esc_html_e( 'Artículo anterior', 'nuvanx-medical' ); ?></span>
								<span class="nvx-blog-nav__title"><?php echo esc_html( get_the_title( $previous ) ); ?></span>
							</a>
						<?php endif; ?>
						<?php if ( $next ) : ?>
							<a class="nvx-blog-nav__item nvx-blog-nav__item--next" href="<?php echo esc_url( get_permalink( $next ) ); ?>" rel="next">
								<span class="nvx-blog-nav__label"><?php esc_html_e( 'Siguiente artículo', 'nuvanx-medical' ); ?></span>
								<span class="nvx-blog-nav__title"><?php echo esc_html( get_the_title( $next ) ); ?></span>
							</a>
						<?php endif; ?>
					</nav>
				<?php endif; ?>
			</div>
		</div>
	</article>
	<?php
endwhile;
