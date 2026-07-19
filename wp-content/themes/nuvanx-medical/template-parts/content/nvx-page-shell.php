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
	$content = get_post_field( 'post_content', get_the_ID() );
	$content = is_string( $content ) ? $content : '';

	// Raw CMS markers (authoring-time).
	$has_content_h1   = (bool) preg_match( '/<h1\b|<!--\s*wp:heading\s+\{[^}]*"level"\s*:\s*1[^}]*\}/i', $content );
	$has_content_hero = (bool) preg_match( '/nvx-brand-hero|nvx-editorial-hero|nvx-page-hero|nvx-home-hero-stage/i', $content );

	// Modules that inject a canonical hero + H1 via the_content even when CMS body is empty/legacy.
	// Without this, the shell prints a second H1 (e.g. EXION Body / Face / EMFUSION).
	$has_managed_editorial = false;
	if ( function_exists( 'nvx_btl_detail_current_key' ) && null !== nvx_btl_detail_current_key( $content ) ) {
		$has_managed_editorial = true;
	}
	if ( ! $has_managed_editorial && function_exists( 'nvx_content_is_endolift_page' ) && nvx_content_is_endolift_page( $content ) ) {
		$has_managed_editorial = true;
	}
	if ( ! $has_managed_editorial && function_exists( 'nvx_content_is_endolaser_page' ) && nvx_content_is_endolaser_page( $content ) ) {
		$has_managed_editorial = true;
	}
	if ( ! $has_managed_editorial && function_exists( 'nvx_content_is_co2_page' ) && nvx_content_is_co2_page( $content ) ) {
		$has_managed_editorial = true;
	}
	if ( ! $has_managed_editorial && function_exists( 'nvx_content_is_laser_medicine_page' ) && nvx_content_is_laser_medicine_page( $content ) ) {
		$has_managed_editorial = true;
	}
	if ( ! $has_managed_editorial && function_exists( 'nvx_content_is_aesthetic_medicine_page' ) && nvx_content_is_aesthetic_medicine_page( $content ) ) {
		$has_managed_editorial = true;
	}
	if ( ! $has_managed_editorial && function_exists( 'nvx_content_is_equipo_page' ) && nvx_content_is_equipo_page( $content ) ) {
		$has_managed_editorial = true;
	}
	if ( ! $has_managed_editorial && function_exists( 'nvx_content_is_nosotros_page' ) && nvx_content_is_nosotros_page( $content ) ) {
		$has_managed_editorial = true;
	}
	if ( ! $has_managed_editorial && function_exists( 'nvx_aesthetic_treatment_current_key' ) && null !== nvx_aesthetic_treatment_current_key() ) {
		$has_managed_editorial = true;
	}

	$has_media = has_post_thumbnail();
	// Theme-owned hero only when content does not already own the page hierarchy.
	// A raw content H1 is author-owned hierarchy even if it is not wrapped in a
	// dedicated hero block. Rendering another shell H1 above it creates a
	// duplicate primary heading on legal and legacy CMS pages.
	$show_theme_hero = $has_media && ! $has_content_h1 && ! $has_content_hero && ! $has_managed_editorial && ! is_front_page();
	// Title-only header only if no content H1 and no theme/content/managed hero.
	$show_theme_title = ! $has_content_h1 && ! $show_theme_hero && ! $has_content_hero && ! $has_managed_editorial && ! is_front_page();
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

	<?php if ( $show_theme_title ) : ?>
		<header class="nvx-page__header nvx-section-intro nvx-shell">
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

	<?php // No shell/page wrapper div — gutters live on section inners (global design). ?>
	<div class="entry-content nvx-page__content nvx-prose">
		<?php the_content(); ?>
	</div>

	<?php if ( is_singular( 'post' ) ) : ?>
		<nav class="nvx-page__nav" aria-label="<?php esc_attr_e( 'Navegación entre artículos', 'nuvanx-medical' ); ?>">
			<?php
				$prev = get_previous_post();
				$next = get_next_post();
				$quarantined_post_ids = function_exists( 'nvx_quarantined_comparison_post_ids' )
					? nvx_quarantined_comparison_post_ids()
					: array();
				if ( $prev && in_array( (int) $prev->ID, $quarantined_post_ids, true ) ) {
					$prev = null;
				}
				if ( $next && in_array( (int) $next->ID, $quarantined_post_ids, true ) ) {
					$next = null;
				}
				if ( $prev ) :
				?>
				<a class="nvx-text-link" href="<?php echo esc_url( get_permalink( $prev ) ); ?>" rel="prev">&larr; <?php echo esc_html( get_the_title( $prev ) ); ?></a>
			<?php endif; ?>
			<?php if ( $next ) : ?>
				<a class="nvx-text-link" href="<?php echo esc_url( get_permalink( $next ) ); ?>" rel="next"><?php echo esc_html( get_the_title( $next ) ); ?> &rarr;</a>
			<?php endif; ?>
		</nav>
	<?php endif; ?>
</article>
	<?php
endwhile;
