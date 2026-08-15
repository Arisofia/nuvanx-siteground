<?php
/**
 * NUVANX Bioestimuladores Official Source
 *
 * Single source of truth for bioestimuladores clinical and commercial parameters.
 * Consumed by card, ficha, FAQ, and schema generation.
 *
 * @package NUVANX
 */

defined( 'ABSPATH' ) || exit;

/**
 * Get official bioestimuladores offer data.
 *
 * @return array<string, mixed> Official bioestimuladores configuration
 */
function nvx_get_bioestimuladores_official(): array {
	$official_file = get_template_directory() . '/inc/data/bioestimuladores-official.json';
	if ( ! is_readable( $official_file ) ) {
		return [];
	}

	$official = json_decode( file_get_contents( $official_file ), true );
	if ( ! is_array( $official ) || ! isset( $official['products'] ) ) {
		return [];
	}

	return $official['products'];
}

/**
 * Get specific bioestimuladores offer by key.
 *
 * @param string $key Offer key (e.g., 'polynucleotides_cara')
 * @return array<string, mixed> Offer data or empty array if not found
 */
function nvx_get_bioestimuladores_offer( string $key ): array {
	$official = nvx_get_bioestimuladores_official();
	return $official[ $key ] ?? [];
}

/**
 * Render bioestimuladores card from official source.
 *
 * @param string $key Offer key
 * @return string HTML card content
 */
function nvx_render_bioestimuladores_card( string $key ): string {
	$offer = nvx_get_bioestimuladores_offer( $key );
	if ( empty( $offer ) ) {
		return '';
	}

	$content = $offer['content'] ?? [];
	$price = $offer['price'] ?? 0;
	$sessions = $offer['sessions'] ?? 1;
	$interval = $offer['interval'] ?? '';

	ob_start();
	?>
	<div class="nvx-bioestimuladores-card">
		<div class="nvx-card-kicker"><?php echo esc_html( $content['kicker'] ?? '' ); ?></div>
		<h3 class="nvx-card-title"><?php echo esc_html( $offer['treatment'] ?? '' ); ?></h3>
	<div class="nvx-card-description">
		<?php echo wp_kses_post( $content['description'] ?? '' ); ?>
	</div>
	<div class="nvx-card-lead">
		<?php echo wp_kses_post( $content['lead'] ?? '' ); ?>
	</div>
	<div class="nvx-card-meta">
		<div class="nvx-card-price">
			<span class="nvx-price-value"><?php echo number_format( $price, 2, ',', '.' ); ?>€</span>
			<span class="nvx-price-unit">por sesión</span>
		</div>
		<?php if ( $sessions > 1 ) : ?>
		<div class="nvx-card-sessions">
			<?php echo esc_html( $sessions ); ?> sesiones recomendadas
			<?php if ( $interval ) : ?>
				<span class="nvx-card-interval">(cada <?php echo esc_html( $interval ); ?>)</span>
			<?php endif; ?>
		</div>
		<?php endif; ?>
	</div>
</div>
	<?php
	return ob_get_clean();
}

/**
 * Get bioestimuladores FAQ from official source.
 *
 * @param string $key Offer key
 * @return array<string, string> FAQ pairs
 */
function nvx_get_bioestimuladores_faqs( string $key ): array {
	$offer = nvx_get_bioestimuladores_offer( $key );
	if ( empty( $offer ) ) {
		return [];
	}

	return $offer['faqs'] ?? [];
}

/**
 * Get bioestimuladores schema data from official source.
 *
 * @param string $key Offer key
 * @return array<string, mixed> Schema configuration
 */
function nvx_get_bioestimuladores_schema( string $key ): array {
	$offer = nvx_get_bioestimuladores_offer( $key );
	if ( empty( offer ) ) {
		return [];
	}

	return $offer['schema'] ?? [];
}

/**
 * Filter tariff catalog to use official bioestimuladores prices.
 *
 * @param array $tariffs Existing tariff catalog
 * @return array Filtered tariff catalog with official bioestimuladores data
 */
function nvx_apply_bioestimuladores_official_prices( array $tariffs ): array {
	$official = nvx_get_bioestimuladores_official();
	if ( empty( $official ) ) {
		return $tariffs;
	}

	// Update bioestimuladores section with official prices
	if ( isset( $tariffs['bioestimuladores'] ) ) {
		foreach ( $official as $key => $offer ) {
			$tariff_key = str_replace( '_', '', $key ); // e.g., polynucleotides_cara -> polynucleotidescara
			if ( isset( $tariffs['bioestimuladores'][ $tariff_key ] ) ) {
				$tariffs['bioestimuladores'][ $tariff_key ]['pvp'] = $offer['price'];
				$tariffs['bioestimuladores'][ $tariff_key ]['sessions_recommended'] = $offer['sessions'];
			}
		}
	}

	return $tariffs;
}