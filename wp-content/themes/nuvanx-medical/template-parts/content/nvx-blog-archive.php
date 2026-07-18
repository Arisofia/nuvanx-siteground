<?php
/**
 * Shared journal archive for /blog/, taxonomies, dates, authors and search.
 *
 * @package nuvanx-medical
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$eyebrow = __( 'NUVANX Journal', 'nuvanx-medical' );
$title    = __( 'Medicina estética con criterio', 'nuvanx-medical' );
$lead     = __( 'Análisis médicos sobre tecnología láser, calidad de piel, well-aging, seguridad y decisiones terapéuticas en Madrid.', 'nuvanx-medical' );

if ( is_search() ) {
	$title = sprintf(
		/* translators: %s: search term. */
		__( 'Resultados para “%s”', 'nuvanx-medical' ),
		get_search_query()
	);
	$lead = __( 'Artículos y guías relacionadas con tu búsqueda.', 'nuvanx-medical' );
} elseif ( is_category() ) {
	$title       = single_cat_title( '', false );
	$description = category_description();
	$lead        = $description ? wp_strip_all_tags( $description ) : __( 'Artículos de esta especialidad dentro del Journal médico NUVANX.', 'nuvanx-medical' );
} elseif ( is_tag() ) {
	$title       = single_tag_title( '', false );
	$description = tag_description();
	$lead        = $description ? wp_strip_all_tags( $description ) : __( 'Artículos relacionados con este tema médico-estético.', 'nuvanx-medical' );
} elseif ( is_author() ) {
	$author = get_queried_object();
	$title  = $author instanceof WP_User ? $author->display_name : __( 'Autor', 'nuvanx-medical' );
	$lead   = __( 'Publicaciones y revisiones editoriales de este autor.', 'nuvanx-medical' );
} elseif ( is_date() ) {
	$title = wp_strip_all_tags( get_the_archive_title() );
	$lead  = __( 'Archivo cronológico del Journal médico NUVANX.', 'nuvanx-medical' );
}

$topics = get_categories(
	array(
		'hide_empty' => true,
		'number'     => 12,
		'orderby'    => 'count',
		'order'      => 'DESC',
	)
);
?>
<section class="nvx-blog-archive" aria-labelledby="nvx-blog-archive-title">
	<header class="nvx-blog-archive__hero">
		<div class="nvx-shell nvx-blog-archive__hero-inner">
			<p class="nvx-eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
			<h1 id="nvx-blog-archive-title" class="nvx-blog-archive__title"><?php echo esc_html( $title ); ?></h1>
			<p class="nvx-blog-archive__lead"><?php echo esc_html( $lead ); ?></p>
		</div>
	</header>

	<div class="nvx-blog-archive__body">
		<div class="nvx-shell">
			<?php if ( have_posts() ) : ?>
				<div class="nvx-blog-grid">
					<?php
					while ( have_posts() ) :
						the_post();
						$categories = get_the_category();
						$primary    = ! empty( $categories ) ? $categories[0] : null;
						$classes    = has_post_thumbnail() ? 'nvx-blog-card' : 'nvx-blog-card nvx-blog-card--no-media';
						?>
						<article id="post-<?php the_ID(); ?>" <?php post_class( $classes ); ?>>
							<?php if ( has_post_thumbnail() ) : ?>
								<a class="nvx-blog-card__media" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
									<?php the_post_thumbnail( 'large', array( 'loading' => 'lazy' ) ); ?>
								</a>
							<?php endif; ?>

							<div class="nvx-blog-card__content">
								<div class="nvx-blog-card__meta">
									<?php if ( $primary instanceof WP_Term ) : ?>
										<span class="nvx-blog-card__category"><a href="<?php echo esc_url( get_category_link( $primary->term_id ) ); ?>"><?php echo esc_html( $primary->name ); ?></a></span>
									<?php endif; ?>
									<time class="nvx-blog-card__date" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
									<?php if ( function_exists( 'nvx_reading_time' ) ) : ?>
										<span class="nvx-blog-card__reading"><?php echo esc_html( nvx_reading_time() ); ?></span>
									<?php endif; ?>
								</div>

								<h2 class="nvx-blog-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
								<div class="nvx-blog-card__excerpt"><p><?php echo esc_html( wp_trim_words( get_the_excerpt(), 30, '…' ) ); ?></p></div>
								<a class="nvx-blog-card__link" href="<?php the_permalink(); ?>"><?php esc_html_e( 'Leer artículo', 'nuvanx-medical' ); ?> <span aria-hidden="true">→</span></a>
							</div>
						</article>
					<?php endwhile; ?>
				</div>

				<nav class="nvx-blog-pagination" aria-label="<?php esc_attr_e( 'Paginación del Journal', 'nuvanx-medical' ); ?>">
					<?php
					the_posts_pagination(
						array(
							'mid_size'  => 2,
							'prev_text' => __( 'Anterior', 'nuvanx-medical' ),
							'next_text' => __( 'Siguiente', 'nuvanx-medical' ),
						)
					);
					?>
				</nav>
			<?php else : ?>
				<div class="nvx-blog-empty">
					<h2 class="nvx-brand-title"><?php esc_html_e( 'No se encontraron artículos', 'nuvanx-medical' ); ?></h2>
					<p class="nvx-copy"><?php esc_html_e( 'Prueba con otro tema o vuelve al Journal completo.', 'nuvanx-medical' ); ?></p>
					<a class="nvx-button nvx-button--primary" href="<?php echo esc_url( home_url( '/blog/' ) ); ?>"><?php esc_html_e( 'Ver todos los artículos', 'nuvanx-medical' ); ?></a>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $topics ) ) : ?>
				<nav class="nvx-blog-topics" aria-labelledby="nvx-blog-topics-title">
					<h2 id="nvx-blog-topics-title" class="nvx-blog-topics__title"><?php esc_html_e( 'Explorar por tema', 'nuvanx-medical' ); ?></h2>
					<ul class="nvx-blog-topics__list">
						<?php foreach ( $topics as $topic ) : ?>
							<li><a href="<?php echo esc_url( get_category_link( $topic->term_id ) ); ?>"><?php echo esc_html( $topic->name ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</nav>
			<?php endif; ?>
		</div>
	</div>
</section>
