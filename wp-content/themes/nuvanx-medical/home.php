<?php
/**
 * Blog index — un solo diseño global (sin journal hero / sidebar).
 *
 * @package nuvanx-medical
 */
defined( 'ABSPATH' ) || exit;

get_header();
?>
<main id="nvx-main" class="nvx-main nvx-page" role="main">
	<div class="nvx-shell nvx-page__shell">
		<header class="nvx-section-intro">
			<p class="nvx-eyebrow">NUVANX</p>
			<h1 class="nvx-heading"><?php esc_html_e( 'Blog', 'nuvanx-medical' ); ?></h1>
			<p class="nvx-lead"><?php esc_html_e( 'Ciencia, experiencia y criterio médico sobre medicina estética y tecnología láser en Madrid.', 'nuvanx-medical' ); ?></p>
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
									<?php
									the_post_thumbnail(
										'large',
										array(
											'class' => 'nvx-media nvx-media--body',
											'alt'   => the_title_attribute( array( 'echo' => false ) ),
										)
									);
									?>
								</a>
							</div>
						<?php endif; ?>
						<p class="nvx-brand-card__kicker"><?php echo esc_html( get_the_date() ); ?></p>
						<h2 class="nvx-brand-card__title">
							<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
						</h2>
						<div class="nvx-brand-card__body"><?php the_excerpt(); ?></div>
						<a class="nvx-button nvx-button--secondary" href="<?php the_permalink(); ?>"><?php esc_html_e( 'Leer más', 'nuvanx-medical' ); ?></a>
					</article>
				<?php endwhile; ?>
			</div>
			<nav class="nvx-page__nav" aria-label="<?php esc_attr_e( 'Paginación', 'nuvanx-medical' ); ?>">
				<?php
				the_posts_pagination(
					array(
						'mid_size'  => 2,
						'prev_text' => '&larr;',
						'next_text' => '&rarr;',
					)
				);
				?>
			</nav>
		<?php else : ?>
			<p class="nvx-copy"><?php esc_html_e( 'No hay artículos publicados.', 'nuvanx-medical' ); ?></p>
		<?php endif; ?>
	</div>
</main>
<?php
get_footer();
