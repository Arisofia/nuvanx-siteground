<?php
/**
 * Blog index — shell unificado nvx-page-hero.
 * Copiar a: wp-content/themes/nuvanx-medical/template-parts/content/nvx-blog-index.php
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="nvx-med-hub nvx-med-page nvx-blog-hub" aria-labelledby="nvx-blog-h1">
	<section class="nvx-page-hero nvx-hero-section nvx-text-center">
		<div class="nvx-page-hero__inner">
			<p class="nvx-kicker">NUVANX</p>
			<h1 id="nvx-blog-h1">Blog NUVANX</h1>
			<p class="nvx-hero-subtitle">Artículos sobre medicina estética, tecnología láser y cuidado de la piel en Madrid.</p>
			<p class="nvx-registro">✔ Presencial o virtual en Chamberí o Salamanca – Goya</p>
		</div>
	</section>

	<section class="nvx-page-body">
		<div class="nvx-page-body__inner">
			<div class="nvx-blog-index-v2__grid">
				<?php if ( have_posts() ) : ?>
					<?php while ( have_posts() ) : the_post(); ?>
						<article id="post-<?php the_ID(); ?>" <?php post_class( 'nvx-blog-card' ); ?>>
							<h2 class="nvx-blog-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
							<p class="nvx-blog-card__meta"><?php echo esc_html( get_the_date() ); ?></p>
							<div class="nvx-blog-card__excerpt"><?php the_excerpt(); ?></div>
							<a href="<?php the_permalink(); ?>" class="nvx-btn nvx-btn-secondary nvx-btn-small">Leer más</a>
						</article>
					<?php endwhile; ?>
					<div class="nvx-blog-index-v2__pagination">
						<?php the_posts_pagination(); ?>
					</div>
				<?php else : ?>
					<p class="nvx-blog-empty">No se encontraron artículos.</p>
				<?php endif; ?>
			</div>
		</div>
	</section>
</div>