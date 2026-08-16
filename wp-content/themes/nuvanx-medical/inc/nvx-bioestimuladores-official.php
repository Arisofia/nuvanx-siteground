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

/**
 * Reconcile the canonical tariff catalogue with the official offer source.
 *
 * The key names are intentionally identical in both catalogues. Never derive
 * them by stripping characters or by array position.
 *
 * @param array<string, mixed> $tariffs Existing tariff catalogue.
 * @return array<string, mixed>
 */
function nvx_apply_bioestimuladores_official_prices( array $tariffs ): array {
	$official = nvx_get_bioestimuladores_official();
	if ( empty( $official ) || ! isset( $tariffs['bioestimuladores'] ) || ! is_array( $tariffs['bioestimuladores'] ) ) {
		return $tariffs;
	}

	foreach ( $official as $key => $offer ) {
		if ( ! isset( $tariffs['bioestimuladores'][ $key ] ) || ! is_array( $offer ) ) {
			continue;
		}
		if ( isset( $offer['price'] ) && is_numeric( $offer['price'] ) ) {
			$tariffs['bioestimuladores'][ $key ]['pvp'] = (float) $offer['price'];
		}
		if ( isset( $offer['sessions'] ) ) {
			$tariffs['bioestimuladores'][ $key ]['sessions_recommended'] = (int) $offer['sessions'];
		}
	}

	return $tariffs;
}

/**
 * Reconcile legacy broad bioestimuladores copy with its broad schema contract.
 *
 * The general /bioestimuladores-colageno-madrid/ page covers different product
 * families (PLLA, CaHA and polynucleotides). A fixed 3-session / 4-week schedule
 * is valid for the governed facial polynucleotide offer, but must not be exposed
 * as a universal protocol for every bioestimulador. The broad page therefore
 * keeps the product-dependent 1–3 session / 4–6 week contract already emitted
 * by its structured data, while the official polynucleotide catalogue retains
 * its product-specific 3-session schedule.
 *
 * This filter is scoped to the main query to avoid unintended replacements
 * in auxiliary content (custom loops, blocks, or programmatic the_content calls).
 */
function nvx_reconcile_bioestimuladores_general_page_copy( string $content ): string {
	if (
		is_admin()
		|| ! is_page( 'bioestimuladores-colageno-madrid' )
		|| ! is_main_query()
		|| ! in_the_loop()
	) {
		return $content;
	}

	// Use stable key phrases instead of full sentences to make matching more robust
	// against minor copy edits while still preventing false positives
	$legacy_protocol_marker = 'El protocolo inicial estándar es de 3 sesiones';
	$governed_protocol = 'El número de sesiones depende del producto, la zona, la calidad cutánea y el objetivo clínico. Como orientación general, los protocolos pueden requerir entre 1 y 3 sesiones espaciadas aproximadamente 4–6 semanas; el mantenimiento se define en revisión según la evolución.';

	$legacy_price_marker = 'El precio de sesión de bioestimulador parte de 248€';
	$governed_price = 'El presupuesto depende del producto, la zona y el protocolo indicado. En polinucleótidos, la tarifa de referencia parte de 248 € por sesión en manos; el presupuesto definitivo se establece tras valoración médica.';

	// Replace all occurrences - this is intentional to ensure consistency
	// across the entire page content
	$content = str_replace( $legacy_protocol_marker, $governed_protocol, $content );
	$content = str_replace( $legacy_price_marker, $governed_price, $content );

	return $content;
}
add_filter( 'the_content', 'nvx_reconcile_bioestimuladores_general_page_copy', 81 );
