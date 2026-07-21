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
 * Render an Evidence Panel.
 *
 * @param array $args {
 *     @type int|string $image_id_or_url Media attachment ID or image URL.
 *     @type string     $image_alt       Alt text for the image.
 *     @type string     $media_meta      Meta description (e.g., '14 Días Post-Protocolo').
 *     @type string     $category        Category tag (e.g., 'Evidencia Clínica').
 *     @type string     $title           Main title of the panel.
 *     @type array      $specs           Associative array of protocol specs [ 'Densidad de Energía' => '15 mJ/px' ].
 *     @type string     $case_url        URL for 'Ver Caso Clínico' button.
 *     @type string     $contact_url     URL for 'Agendar Valoración' button.
 * }
 * @return string HTML output of the evidence panel.
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
					<img src="<?php echo esc_url( $parsed_args['image_id_or_url'] ); ?>" alt="<?php echo esc_attr( $parsed_args['image_alt'] ); ?>" class="nvx-evidence-panel__image" loading="lazy">
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
