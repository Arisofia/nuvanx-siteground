<?php
/**
 * Blog index partial — mismo diseño global (lista en una columna).
 *
 * @package nuvanx-medical
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="nvx-shell nvx-page__shell">
	<header class="nvx-section-intro">
		<p class="nvx-eyebrow">NUVANX</p>
		<h1 class="nvx-heading" id="nvx-blog-h1"><?php esc_html_e( 'Blog NUVANX', 'nuvanx-medical' ); ?></h1>
		<p class="nvx-lead"><?php esc_html_e( 'Artículos sobre medicina estética, tecnología láser y cuidado de la piel en Madrid.', 'nuvanx-medical' ); ?></p>
	</header>

	<?php if ( have_posts() ) : ?>
		<div class="nvx-brand-grid">
			<?php
			while ( have_posts() ) :
				the_post();
				?>
				<article id="post-<?php the_ID(); ?>" <?php post_class( 'nvx-brand-card' ); ?>>
					<?php if ( has_post_thumbnail() ) : ?>
						<div class="nvx-brand-card__media">
							<a href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
								<?php the_post_thumbnail( 'large' ); ?>
							</a>
						</div>
					<?php endif; ?>
					<p class="nvx-brand-card__kicker"><?php echo esc_html( get_the_date() ); ?></p>
					<h2 class="nvx-brand-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
					<div class="nvx-brand-card__body"><?php the_excerpt(); ?></div>
					<a class="nvx-button nvx-button--secondary" href="<?php the_permalink(); ?>"><?php esc_html_e( 'Leer más', 'nuvanx-medical' ); ?></a>
				</article>
			<?php endwhile; ?>
		</div>
		<?php the_posts_pagination(); ?>
	<?php else : ?>
		<p class="nvx-copy"><?php esc_html_e( 'No se encontraron artículos.', 'nuvanx-medical' ); ?></p>
	<?php endif; ?>
</div>
