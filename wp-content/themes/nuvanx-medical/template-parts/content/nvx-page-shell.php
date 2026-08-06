<?php
/**
 * Shell unificado para páginas y posts (un solo diseño).
 *
 * Media-first page hero: featured image (when present) is full-bleed with
 * kicker + title overlaid. Body content follows below.
 *
 * @package nuvanx-medical
 */

get_header();

$shell_content  = get_query_var( 'nvx_shell_content' );
$shell_skip_hdr = get_query_var( 'nvx_shell_skip_header' );
$shell_with_wrap = get_query_var( 'nvx_shell_with_wrapper' );

if ( ! empty( $shell_content ) && ! is_singular() ) {
	?>
		<?php echo $shell_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- theme-built HTML already escaped at source. ?>
	<?php
	get_footer();
	return;
}

if ( ! empty( $shell_content ) ) {
	rewind_posts();
}

// Header.php already provides <main id="nvx-main" class="nvx-main" role="main"> and .nvx-brand-page wrapper
// Content renders directly without additional wrapper for consistency with custom templates
// Only add nvx-main-shell wrapper when nvx_shell_with_wrapper is set (for home page only)
if ( ! empty( $shell_with_wrap ) ) {
	echo '<div class="nvx-main-shell">' . "\n";
}

while ( have_posts() ) :
	the_post();
	$content = get_post_field( 'post_content', get_the_ID() );
	$content = is_string( $content ) ? $content : '';

	// Raw CMS markers (authoring-time).
	// Match a true level-1 heading only: bare <h1> or block comment with "level":1
	// not followed by another digit (avoids false positives on "level":10+).
	$has_content_h1   = (bool) preg_match( '/<h1\b|<!--\s*wp:heading\s+\{[^}]*"level"\s*:\s*1(?!\d)[^}]*\}/i', $content );
	$has_content_hero = (bool) preg_match( '/nvx-brand-hero|nvx-editorial-hero|nvx-page-hero|nvx-home-hero-stage|nvx-ipl-hero/i', $content );

	// Modules that inject a canonical hero + H1 via the_content even when CMS body is empty.
	// Without this, the shell prints a second H1 (e.g. EXION Body / Face / EMFUSION).
	$has_managed_editorial = false;
	if ( function_exists( 'nvx_get_page_owner' ) ) {
		$owner = nvx_get_page_owner();
		if ( ! empty( $owner ) ) {
			$has_managed_editorial = true;
		}
	}

	$has_media = has_post_thumbnail();
	// Legal CMS pages own the document H1 via content (promoted in page-hygiene).
	// Never inject a shell H1 there or the crawl contract sees duplicates.
	$is_legal_page = is_page() && in_array(
		(string) get_post_field( 'post_name', get_the_ID() ),
		array( 'politica-privacidad', 'aviso-legal' ),
		true
	);
	// Theme-owned hero only when content does not already own the page hierarchy.
	// A raw content H1 is author-owned hierarchy even if it is not wrapped in a
	// dedicated hero block. Rendering another shell H1 above it creates a
	// duplicate primary heading on legal and CMS pages.
	$show_theme_hero = $has_media && ! $has_content_h1 && ! $has_content_hero && ! $has_managed_editorial && ! is_front_page() && empty( $shell_skip_hdr );
	// Title-only header only if no content H1 and no theme/content/managed hero.
	$show_theme_title = ! $has_content_h1 && ! $show_theme_hero && ! $has_content_hero && ! $has_managed_editorial && ! is_front_page() && empty( $shell_skip_hdr );
	$classes          = array( 'nvx-page' );
	if ( is_single() ) {
		$classes[] = 'nvx-page--single';
	}
	if ( $show_theme_hero || $has_content_hero || $has_managed_editorial ) {
		$classes[] = 'nvx-page--has-hero';
	}
	?>
<article id="post-<?php the_ID(); ?>" <?php post_class( $classes ); ?>>

	<?php if ( $show_theme_hero ) : ?>
		<section class="nvx-brand-hero" aria-labelledby="nvx-page-hero-title-<?php the_ID(); ?>">
			<?php $nvx_page_shell_has_hero = true; ?>
			<div class="nvx-brand-hero__inner">
				<figure class="nvx-brand-hero__media">
					<?php
					the_post_thumbnail(
						'full',
						array(
							'class'         => 'nvx-media nvx-media--hero',
							'alt'           => the_title_attribute( array( 'echo' => false ) ),
							'loading'       => 'eager',
							'fetchpriority' => 'high',
						)
					);
					?>
				</figure>
				<div class="nvx-brand-hero__copy">
					<?php if ( is_single() ) : ?>
						<?php
						$cats = get_the_category();
						if ( ! empty( $cats ) ) :
							?>
							<p class="nvx-brand-kicker"><?php echo esc_html( $cats[0]->name ); ?></p>
						<?php else : ?>
							<p class="nvx-brand-kicker"><?php esc_html_e( 'NUVANX', 'nuvanx-medical' ); ?></p>
						<?php endif; ?>
					<?php else : ?>
						<p class="nvx-brand-kicker"><?php esc_html_e( 'NUVANX', 'nuvanx-medical' ); ?></p>
					<?php endif; ?>
					<?php the_title( '<h1 id="nvx-page-hero-title-' . get_the_ID() . '" class="nvx-brand-hero__title">', '</h1>' ); ?>
					<?php if ( is_single() ) : ?>
						<p class="nvx-brand-hero__lead">
							<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
							<?php if ( function_exists( 'nvx_reading_time' ) ) : ?>
								<span aria-hidden="true"> · </span><?php echo esc_html( nvx_reading_time() ); ?> de lectura
							<?php endif; ?>
						</p>
					<?php else : ?>
						<div class="nvx-brand-actions">
							<?php if ( function_exists( 'nvx_signature_valoracion_url' ) ) : ?>
								<a href="<?php echo esc_url( nvx_signature_valoracion_url() ); ?>" class="nvx-brand-btn nvx-brand-btn--primary"><?php esc_html_e( 'Solicitar valoración médica', 'nuvanx-medical' ); ?></a>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( $show_theme_title ) : ?>
		<header class="nvx-page__header nvx-section-intro nvx-shell">
			<?php if ( is_single() ) : ?>
				<?php
				$cats = get_the_category();
				if ( ! empty( $cats ) ) :
					?>
					<p class="nvx-brand-kicker"><?php echo esc_html( $cats[0]->name ); ?></p>
				<?php endif; ?>
			<?php endif; ?>
			<?php the_title( '<h1 class="nvx-brand-title">', '</h1>' ); ?>
			<?php if ( is_single() ) : ?>
				<p class="nvx-brand-lead">
					<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
					<?php if ( function_exists( 'nvx_reading_time' ) ) : ?>
						<span aria-hidden="true"> · </span><?php echo esc_html( nvx_reading_time() ); ?> de lectura
					<?php endif; ?>
				</p>
			<?php endif; ?>
		</header>
	<?php endif; ?>

	<?php
	$no_prose_wrap = empty( $shell_with_wrap ) || $has_managed_editorial;
	if ( ! $no_prose_wrap ) :
		?>
		<div class="entry-content nvx-page__content nvx-prose">
	<?php endif; ?>
		<?php
		if ( ! empty( $shell_content ) ) {
			echo $shell_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- theme-built HTML already escaped at source.
		} else {
			the_content();
		}
		?>
	<?php if ( ! $no_prose_wrap ) : ?>
		</div>
	<?php endif; ?>

	<?php if ( is_single() ) : ?>
		<nav class="nvx-page__nav" aria-label="<?php esc_attr_e( 'Navegación entre artículos', 'nuvanx-medical' ); ?>">
			<?php
				$prev                 = get_previous_post();
				$next                 = get_next_post();
				$quarantined_post_ids = function_exists( 'nvx_quarantined_comparison_post_ids' )
					? nvx_quarantined_comparison_post_ids()
					: array();
			if ( $prev && in_array( (int) $prev->ID, $quarantined_post_ids, true ) ) {
				$prev = null;
			}
			if ( $next && in_array( (int) $next->ID, $quarantined_post_ids, true ) ) {
				$next = null;
			}
			if ( $prev ) {
				?>
				<a class="nvx-text-link" href="<?php echo esc_url( get_permalink( $prev ) ); ?>" rel="prev">&larr; <?php echo esc_html( get_the_title( $prev ) ); ?></a>
					<?php
			}
			if ( $next ) {
				?>
				<a class="nvx-text-link" href="<?php echo esc_url( get_permalink( $next ) ); ?>" rel="next"><?php echo esc_html( get_the_title( $next ) ); ?> &rarr;</a>
					<?php
			}
			?>
		</nav>
	<?php endif; ?>
</article>
	<?php
endwhile;

// Close nvx-main-shell wrapper conditionally if it was opened
if ( ! empty( $shell_with_wrap ) ) {
	echo "</div>\n";
}

get_footer();
