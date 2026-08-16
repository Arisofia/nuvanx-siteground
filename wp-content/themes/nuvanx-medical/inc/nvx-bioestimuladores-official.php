<?php
/**
 * NUVANX Bioestimuladores Official Source.
 *
 * Single source of truth for bioestimuladores clinical and commercial
 * parameters. Consumers must use the offer key from
 * inc/data/bioestimuladores-official.json; tariff keys intentionally use the
 * same identifiers so no positional or label-based mapping is required.
 *
 * @package NUVANX
 */

defined( 'ABSPATH' ) || exit;

/**
 * Return the official bioestimuladores offer catalogue.
 *
 * @return array<string, array<string, mixed>>
 */
function nvx_get_bioestimuladores_official(): array {
	$official_file = get_template_directory() . '/inc/data/bioestimuladores-official.json';
	if ( ! is_readable( $official_file ) ) {
		return array();
	}

	$official = json_decode( (string) file_get_contents( $official_file ), true );
	if ( ! is_array( $official ) || ! isset( $official['products'] ) || ! is_array( $official['products'] ) ) {
		return array();
	}

	return $official['products'];
}

/**
 * Return a single official offer.
 *
 * @return array<string, mixed>
 */
function nvx_get_bioestimuladores_offer( string $key ): array {
	$official = nvx_get_bioestimuladores_official();
	$offer    = $official[ $key ] ?? array();

	return is_array( $offer ) ? $offer : array();
}

/** Render a bioestimuladores card from the official source. */
function nvx_render_bioestimuladores_card( string $key ): string {
	$offer = nvx_get_bioestimuladores_offer( $key );
	if ( empty( $offer ) ) {
		return '';
	}

	$content  = isset( $offer['content'] ) && is_array( $offer['content'] ) ? $offer['content'] : array();
	$price    = isset( $offer['price'] ) && is_numeric( $offer['price'] ) ? (float) $offer['price'] : 0.0;
	$sessions = isset( $offer['sessions'] ) ? max( 1, (int) $offer['sessions'] ) : 1;
	$interval = isset( $offer['interval'] ) ? (string) $offer['interval'] : '';

	ob_start();
	?>
	<div class="nvx-bioestimuladores-card">
		<div class="nvx-card-kicker"><?php echo esc_html( (string) ( $content['kicker'] ?? '' ) ); ?></div>
		<h3 class="nvx-card-title"><?php echo esc_html( (string) ( $offer['treatment'] ?? '' ) ); ?></h3>
		<div class="nvx-card-description"><?php echo wp_kses_post( (string) ( $content['description'] ?? '' ) ); ?></div>
		<div class="nvx-card-lead"><?php echo wp_kses_post( (string) ( $content['lead'] ?? '' ) ); ?></div>
		<div class="nvx-card-meta">
			<div class="nvx-card-price">
				<span class="nvx-price-value"><?php echo esc_html( number_format( $price, 2, ',', '.' ) . '€' ); ?></span>
				<span class="nvx-price-unit"><?php echo esc_html__( 'por sesión', 'nuvanx-medical' ); ?></span>
			</div>
			<?php if ( $sessions > 1 ) : ?>
				<div class="nvx-card-sessions">
					<?php echo esc_html( sprintf( __( '%d sesiones recomendadas', 'nuvanx-medical' ), $sessions ) ); ?>
					<?php if ( '' !== $interval ) : ?>
						<span class="nvx-card-interval"><?php echo esc_html( sprintf( __( '(cada %s)', 'nuvanx-medical' ), $interval ) ); ?></span>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
	<?php
	return (string) ob_get_clean();
}

/**
 * Return FAQ items for an offer.
 *
 * @return array<int, array<string, string>>
 */
function nvx_get_bioestimuladores_faqs( string $key ): array {
	$offer = nvx_get_bioestimuladores_offer( $key );
	$faqs  = $offer['faqs'] ?? array();

	return is_array( $faqs ) ? $faqs : array();
}

/**
 * Return Schema.org configuration for an offer.
 *
 * @return array<string, mixed>
 */
function nvx_get_bioestimuladores_schema( string $key ): array {
	$offer = nvx_get_bioestimuladores_offer( $key );
	if ( empty( $offer ) ) {
		return array();
	}

	$schema = $offer['schema'] ?? array();
	return is_array( $schema ) ? $schema : array();
}
