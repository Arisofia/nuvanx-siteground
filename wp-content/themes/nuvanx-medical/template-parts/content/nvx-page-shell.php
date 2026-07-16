<?php
/**
 * Shell unificado para páginas y posts (un solo diseño).
 *
 * Media-first page hero: featured image (when present) is full-bleed with
 * kicker + title overlaid. Body content follows below.
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
	$has_content_hero = is_string( $content ) && preg_match( '/nvx-brand-hero|nvx-editorial-hero|nvx-page-hero|nvx-home-hero-stage/i', $content );
	$has_media        = has_post_thumbnail();
	// Theme-owned hero when we have media and content does not already own a hero block.
	$show_theme_hero  = $has_media && ! $has_content_hero && ! is_front_page();
	// Title-only header only if no content H1 and no theme/content hero.
	$show_theme_title = ! $has_content_h1 && ! $show_theme_hero && ! $has_content_hero && ! is_front_page();
	$classes          = array( 'nvx-page' );
	if ( is_singular( 'post' ) ) {
		$classes[] = 'nvx-page--single';
	}
	if ( $show_theme_hero || $has_content_hero ) {
		$classes[] = 'nvx-page--has-hero';
	}
	?>
<article id="post-<?php the_ID(); ?>" <?php post_class( $classes ); ?>>

	<?php if ( $show_theme_hero ) : ?>
		<header class="nvx-page-hero nvx-page-hero--theme" aria-labelledby="nvx-page-hero-title-<?php the_ID(); ?>">
			<div class="nvx-page-hero__inner">
				<figure class="nvx-page-hero__media">
					<?php
					the_post_thumbnail(
						'full',
						array(
							'class'   => 'nvx-media nvx-media--hero',
							'alt'     => the_title_attribute( array( 'echo' => false ) ),
							'loading' => 'eager',
						)
					);
					?>
				</figure>
				<div class="nvx-page-hero__copy">
					<?php if ( is_singular( 'post' ) ) : ?>
						<?php
						$cats = get_the_category();
						if ( ! empty( $cats ) ) :
							?>
							<p class="nvx-eyebrow"><?php echo esc_html( $cats[0]->name ); ?></p>
						<?php else : ?>
							<p class="nvx-eyebrow"><?php esc_html_e( 'NUVANX', 'nuvanx-medical' ); ?></p>
						<?php endif; ?>
					<?php else : ?>
						<p class="nvx-eyebrow"><?php esc_html_e( 'NUVANX', 'nuvanx-medical' ); ?></p>
					<?php endif; ?>
					<?php the_title( '<h1 id="nvx-page-hero-title-' . get_the_ID() . '" class="nvx-heading">', '</h1>' ); ?>
					<?php if ( is_singular( 'post' ) ) : ?>
						<p class="nvx-lead">
							<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
							<?php if ( function_exists( 'nvx_reading_time' ) ) : ?>
								<span aria-hidden="true"> · </span><?php echo esc_html( nvx_reading_time() ); ?> de lectura
							<?php endif; ?>
						</p>
					<?php endif; ?>
				</div>
			</div>
		</header>
	<?php endif; ?>

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
