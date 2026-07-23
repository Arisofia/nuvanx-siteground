<?php
/**
 * Signature UI Pattern: The Bipartite Evidence Display
 *
 * Renders the structural markup for clinical evidence panels, separating 
 * documentary photography from rigorous quantitative data.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders an evidence panel with clinical media, protocol data, and optional action links.
 *
 * @param array $args {
 *     Arguments for configuring the panel.
 *
 *     @type int|string $image_id_or_url Media attachment ID or direct image URL.
 *     @type string     $image_alt       Alternative text for a direct image URL.
 *     @type string     $media_meta      Caption or label for the media.
 *     @type string     $category        Category displayed above the title.
 *     @type string     $title           Main panel heading.
 *     @type array      $specs           Associative array of protocol labels and values.
 *     @type string     $case_url        URL for the clinical case link.
 *     @type string     $contact_url     URL for the consultation link.
 * }
 * @return string The rendered evidence panel HTML.
 */
function nvx_render_evidence_panel( array $args ): string {
	$defaults = array(
		'image_id_or_url' => '',
		'image_alt'       => '',
		'media_meta'      => '',
		'category'        => __( 'Evidencia Clínica', 'nuvanx-medical' ),
		'title'           => '',
		'specs'           => array(),
		'case_url'        => '',
		'contact_url'     => home_url( '/madrid/valoracion/' ),
	);

	$parsed_args = wp_parse_args( $args, $defaults );

	ob_start();
	?>
	<section class="nvx-evidence-panel">
		<div class="nvx-evidence-panel__grid">
			
			<!-- Clinical Photography -->
			<figure class="nvx-evidence-panel__media">
				<?php
				if ( is_numeric( $parsed_args['image_id_or_url'] ) ) {
					echo wp_get_attachment_image( (int) $parsed_args['image_id_or_url'], 'large', false, array( 'class' => 'nvx-evidence-panel__image' ) );
				} else {
					?>
					<img src="<?php echo esc_url( $parsed_args['image_id_or_url'] ); ?>" alt="<?php echo esc_attr( trim( (string) preg_replace( '/\b(imagen|image|foto|fotografía)\b/ui', '', (string) $parsed_args['image_alt'] ) ) ); ?>" class="nvx-evidence-panel__image" loading="lazy">
					<?php
				}
				?>
				<?php if ( ! empty( $parsed_args['media_meta'] ) ) : ?>
					<figcaption class="nvx-evidence-panel__media-meta">
						<span class="nvx-data-label"><?php esc_html_e( 'Registro Fotográfico:', 'nuvanx-medical' ); ?></span> <?php echo esc_html( $parsed_args['media_meta'] ); ?>
					</figcaption>
				<?php endif; ?>
			</figure>

			<!-- Quantitative Protocol Data -->
			<div class="nvx-evidence-panel__data-core">
				<header class="nvx-evidence-panel__header">
					<p class="nvx-evidence-panel__category"><?php echo esc_html( $parsed_args['category'] ); ?></p>
					<h2 class="nvx-evidence-panel__title"><?php echo esc_html( $parsed_args['title'] ); ?></h2>
				</header>

				<?php if ( ! empty( $parsed_args['specs'] ) && is_array( $parsed_args['specs'] ) ) : ?>
					<ul class="nvx-protocol-specs">
						<?php foreach ( $parsed_args['specs'] as $key => $val ) : ?>
							<li class="nvx-protocol-specs__item">
								<span class="nvx-protocol-specs__key"><?php echo esc_html( $key ); ?></span>
								<span class="nvx-protocol-specs__value"><?php echo esc_html( $val ); ?></span>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>

				<div class="nvx-evidence-panel__actions">
					<?php if ( ! empty( $parsed_args['case_url'] ) ) : ?>
						<a href="<?php echo esc_url( $parsed_args['case_url'] ); ?>" class="nvx-btn nvx-btn--secondary">
							<?php esc_html_e( 'Ver Caso Clínico', 'nuvanx-medical' ); ?>
						</a>
					<?php endif; ?>
					<?php if ( ! empty( $parsed_args['contact_url'] ) ) : ?>
						<a href="<?php echo esc_url( $parsed_args['contact_url'] ); ?>" class="nvx-btn nvx-btn--primary">
							<?php esc_html_e( 'Agendar Valoración', 'nuvanx-medical' ); ?>
						</a>
					<?php endif; ?>
				</div>
			</div>

		</div>
	</section>
	<?php
	return ob_get_clean();
}
