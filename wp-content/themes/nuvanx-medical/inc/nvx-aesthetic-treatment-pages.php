<?php
/**
 * Canonical facial aesthetic treatment pages.
 *
 * One versioned catalogue drives visible content, metadata, FAQ schema and the
 * staging-only page seeder. Production pages remain drafts until medical review.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Canonical catalogue for facial injectable/regenerative treatment pages.
 *
 * Orientative reference tariffs, session counts, durations and clinical parameters
 * are published here, subject to individual medical assessment in consultation.
 *
 * @return array<string, array<string, mixed>>
 */

/**
 * Normalize aesthetic catalog entry clinical fields from nested sources.
 *
 * @param array<string,mixed> $entry Catalog record reference.
 */
function nvx_aesthetic_catalog_normalize_entry( array &$entry ): void {
	$sources = array(
		$entry['protocol'] ?? array(),
		$entry['schema'] ?? array(),
	);
	foreach ( $sources as $src ) {
		if ( ! is_array( $src ) ) {
			continue;
		}
		if ( empty( $entry['price_range'] ) && ! empty( $src['price_range'] ) ) {
			$entry['price_range'] = $src['price_range'];
		}
		if ( empty( $entry['session_time'] ) && ! empty( $src['session_time'] ) ) {
			$entry['session_time'] = $src['session_time'];
		}
		if ( empty( $entry['duration'] ) ) {
			if ( ! empty( $src['duration_result'] ) ) {
				$entry['duration'] = $src['duration_result'];
			} elseif ( ! empty( $src['duration'] ) ) {
				$entry['duration'] = $src['duration'];
			}
		}
		if ( empty( $entry['anesthesia'] ) && ! empty( $src['anesthesia'] ) ) {
			$entry['anesthesia'] = $src['anesthesia'];
		}
		if ( empty( $entry['brands'] ) ) {
			if ( ! empty( $src['brands'] ) ) {
				$entry['brands'] = (array) $src['brands'];
			} elseif ( ! empty( $src['products_used'] ) ) {
				$entry['brands'] = (array) $src['products_used'];
			}
		}
		if ( empty( $entry['sessions'] ) && ! empty( $src['sessions'] ) ) {
			$entry['sessions'] = $src['sessions'];
		}
		if ( empty( $entry['downtime'] ) && ! empty( $src['downtime'] ) ) {
			$entry['downtime'] = $src['downtime'];
		}
	}
}

function nvx_aesthetic_treatment_catalog(): array {
	static $catalog = null;

	if ( null === $catalog ) {
		require_once __DIR__ . '/nvx-catalog-json.php';
		$raw_catalog = nvx_catalog_json_resolved( 'aesthetic-treatment-pages.json' );

		if ( is_array( $raw_catalog ) ) {
			foreach ( $raw_catalog as &$entry ) {
				if ( is_array( $entry ) ) {
					nvx_aesthetic_catalog_normalize_entry( $entry );
				}
			}
			unset( $entry );
		}


		$catalog = nvx_catalog_filter_records(
			$raw_catalog,
			array(
				'slug',
				'kicker',
				'h1',
				'lead',
				'description',
				'seo_title',
				'diagnosis',
				'indications',
				'precautions',
				'mechanism',
				'process',
				'evolution',
				'risks',
				'combinations',
				'faqs',
				'schema',
				'protocol',
				'brands',
				'duration',
				'session_time',
				'anesthesia',
				'techniques',
				'price_range',
				'sessions',
				'downtime',
			),
			'aesthetic-treatment-pages.json'
		);
	}


	return $catalog;
}


/** Resolve a treatment key from slug or current singular page. */
function nvx_aesthetic_treatment_key_from_slug( string $slug ): ?string {
	$slug = trim( $slug, '/' );
	foreach ( nvx_aesthetic_treatment_catalog() as $key => $entry ) {
		if ( $slug === $entry['slug'] ) {
			return $key;
		}
	}
	return null;
}

function nvx_aesthetic_treatment_current_key(): ?string {
	if ( is_admin() || ! is_singular( 'page' ) ) {
		return null;
	}

	$post_id = get_queried_object_id();
	$slug    = (string) get_post_field( 'post_name', $post_id );
	$key     = nvx_aesthetic_treatment_key_from_slug( $slug );
	if ( null !== $key ) {
		return $key;
	}

	// Staging seed records can survive historical slug migrations. Resolve the
	// canonical treatment from the explicit seed metadata before falling back to
	// the inert CMS marker, so the versioned catalogue remains the only source of
	// visible clinical content and a stale post_name cannot collapse the page to
	// an empty marker plus shell title.
	$catalog  = nvx_aesthetic_treatment_catalog();
	$meta_key = get_post_meta( $post_id, '_nvx_aesthetic_treatment_key', true );
	if ( is_string( $meta_key ) && isset( $catalog[ $meta_key ] ) ) {
		return $meta_key;
	}

	$content = (string) get_post_field( 'post_content', $post_id );
	if ( preg_match( '/data-nvx-treatment=["\']([a-z0-9_-]+)["\']/i', $content, $matches ) ) {
		$marker_key = (string) $matches[1];
		if ( isset( $catalog[ $marker_key ] ) ) {
			return $marker_key;
		}
	}

	return null;
}

/** @return array<string, array<int, array{q:string,a:string}>> */
function nvx_aesthetic_treatment_faq_catalog(): array {
	$result = array();
	foreach ( nvx_aesthetic_treatment_catalog() as $key => $entry ) {
		$result[ $key ] = $entry['faqs'];
	}
	return $result;
}

/** @return array<string, array<string, mixed>> */
function nvx_aesthetic_treatment_schema_catalog(): array {
	$result = array();
	foreach ( nvx_aesthetic_treatment_catalog() as $key => $entry ) {
		$result[ $key ] = $entry['schema'];
	}
	return $result;
}

/** Current page data or null. */
function nvx_aesthetic_treatment_current(): ?array {
	$key = nvx_aesthetic_treatment_current_key();
	if ( null === $key ) {
		return null;
	}
	$catalog = nvx_aesthetic_treatment_catalog();
	return $catalog[ $key ] ?? null;
}

/** Whether the current page is one of the governed aesthetic treatment pages. */
function nvx_is_aesthetic_treatment_page(): bool {
	return null !== nvx_aesthetic_treatment_current_key();
}

/**
 * Render one treatment data row.
 *
 * @param string $label Row label.
 * @param string $value Row value.
 */
function nvx_aesthetic_treatment_data_row( string $label, string $value ): string {
	if ( '' === trim( $value ) ) {
		return '';
	}
	return '<li><strong>' . esc_html( $label ) . '</strong><span>' . esc_html( $value ) . '</span></li>';
}

/**
 * Render plain bullet list.
 *
 * @param array<int,string> $items List values.
 */
function nvx_aesthetic_treatment_list( array $items ): string {
	if ( empty( $items ) ) {
		return '';
	}
	$html = '<ul class="nvx-treatment-page__list">';
	foreach ( $items as $item ) {
		$html .= '<li>' . esc_html( $item ) . '</li>';
	}
	$html .= '</ul>';
	return $html;
}

/**
 * Render optional page section.
 *
 * @param string $title Section title.
 * @param string $copy Section copy.
 */
function nvx_aesthetic_treatment_copy_section( string $title, string $copy ): string {
	if ( '' === trim( $copy ) ) {
		return '';
	}
	return '<section class="nvx-treatment-page__section"><h2>' . esc_html( $title ) . '</h2><p>' . esc_html( $copy ) . '</p></section>';
}

/**
 * Render treatment page content.
 *
 * @param array<string,mixed> $page Current treatment data.
 */
function nvx_aesthetic_treatment_render( array $page ): void {
	$schema    = is_array( $page['schema'] ?? null ) ? $page['schema'] : array();
	$protocol  = is_array( $page['protocol'] ?? null ) ? $page['protocol'] : array();
	$brands    = array_values( array_filter( array_map( 'strval', (array) ( $page['brands'] ?? array() ) ) ) );
	$techniques = array_values( array_filter( array_map( 'strval', (array) ( $page['techniques'] ?? array() ) ) ) );
	$price     = (string) ( $page['price_range'] ?? $protocol['price_range'] ?? $schema['price_range'] ?? '' );
	$duration  = (string) ( $page['duration'] ?? $protocol['duration_result'] ?? $schema['duration'] ?? '' );
	$session   = (string) ( $page['session_time'] ?? $protocol['session_time'] ?? $schema['session_time'] ?? '' );
	$anesthesia = (string) ( $page['anesthesia'] ?? $protocol['anesthesia'] ?? $schema['anesthesia'] ?? '' );
	$sessions  = (string) ( $page['sessions'] ?? $protocol['sessions'] ?? $schema['sessions'] ?? '' );
	$downtime  = (string) ( $page['downtime'] ?? $protocol['downtime'] ?? $schema['downtime'] ?? '' );
	?>
	<article class="nvx-treatment-page" data-nvx-treatment-page>
		<header class="nvx-treatment-page__hero">
			<p class="nvx-kicker"><?php echo esc_html( (string) $page['kicker'] ); ?></p>
			<h1><?php echo esc_html( (string) $page['h1'] ); ?></h1>
			<p class="nvx-treatment-page__lead"><?php echo esc_html( (string) $page['lead'] ); ?></p>
			<a class="nvx-button nvx-button--primary" href="<?php echo esc_url( home_url( '/madrid/valoracion/' ) ); ?>"><?php esc_html_e( 'Solicitar valoración médica', 'nuvanx-medical' ); ?></a>
		</header>

		<section class="nvx-treatment-page__section"><h2><?php esc_html_e( 'Diagnóstico médico', 'nuvanx-medical' ); ?></h2><p><?php echo esc_html( (string) $page['diagnosis'] ); ?></p></section>
		<section class="nvx-treatment-page__section"><h2><?php esc_html_e( 'Cuándo puede estar indicado', 'nuvanx-medical' ); ?></h2><?php echo wp_kses_post( nvx_aesthetic_treatment_list( (array) $page['indications'] ) ); ?></section>
		<?php echo wp_kses_post( nvx_aesthetic_treatment_copy_section( __( 'Cómo actúa', 'nuvanx-medical' ), (string) $page['mechanism'] ) ); ?>
		<?php echo wp_kses_post( nvx_aesthetic_treatment_copy_section( __( 'Cómo es el tratamiento', 'nuvanx-medical' ), (string) $page['process'] ) ); ?>
		<?php echo wp_kses_post( nvx_aesthetic_treatment_copy_section( __( 'Evolución esperable', 'nuvanx-medical' ), (string) $page['evolution'] ) ); ?>

		<?php if ( $price || $duration || $session || $anesthesia || $sessions || $downtime || $brands || $techniques ) : ?>
			<section class="nvx-treatment-page__facts" aria-label="<?php esc_attr_e( 'Datos orientativos', 'nuvanx-medical' ); ?>">
				<h2><?php esc_html_e( 'Datos orientativos', 'nuvanx-medical' ); ?></h2>
				<ul>
					<?php echo wp_kses_post( nvx_aesthetic_treatment_data_row( __( 'Precio orientativo', 'nuvanx-medical' ), $price ) ); ?>
					<?php echo wp_kses_post( nvx_aesthetic_treatment_data_row( __( 'Duración del resultado', 'nuvanx-medical' ), $duration ) ); ?>
					<?php echo wp_kses_post( nvx_aesthetic_treatment_data_row( __( 'Tiempo de sesión', 'nuvanx-medical' ), $session ) ); ?>
					<?php echo wp_kses_post( nvx_aesthetic_treatment_data_row( __( 'Anestesia', 'nuvanx-medical' ), $anesthesia ) ); ?>
					<?php echo wp_kses_post( nvx_aesthetic_treatment_data_row( __( 'Sesiones', 'nuvanx-medical' ), $sessions ) ); ?>
					<?php echo wp_kses_post( nvx_aesthetic_treatment_data_row( __( 'Recuperación', 'nuvanx-medical' ), $downtime ) ); ?>
					<?php echo wp_kses_post( nvx_aesthetic_treatment_data_row( __( 'Marcas / productos', 'nuvanx-medical' ), implode( ', ', $brands ) ) ); ?>
					<?php echo wp_kses_post( nvx_aesthetic_treatment_data_row( __( 'Técnicas', 'nuvanx-medical' ), implode( ', ', $techniques ) ) ); ?>
				</ul>
			</section>
		<?php endif; ?>

		<section class="nvx-treatment-page__section"><h2><?php esc_html_e( 'Seguridad y valoración', 'nuvanx-medical' ); ?></h2><?php echo wp_kses_post( nvx_aesthetic_treatment_list( (array) $page['precautions'] ) ); ?></section>
		<section class="nvx-treatment-page__section"><h2><?php esc_html_e( 'Riesgos y efectos adversos', 'nuvanx-medical' ); ?></h2><?php echo wp_kses_post( nvx_aesthetic_treatment_list( (array) $page['risks'] ) ); ?></section>

		<?php if ( ! empty( $page['combinations'] ) ) : ?>
			<section class="nvx-treatment-page__section"><h2><?php esc_html_e( 'Combinaciones posibles', 'nuvanx-medical' ); ?></h2><?php echo wp_kses_post( nvx_aesthetic_treatment_list( (array) $page['combinations'] ) ); ?></section>
		<?php endif; ?>

		<?php if ( ! empty( $page['faqs'] ) ) : ?>
			<section class="nvx-treatment-page__faq" aria-label="<?php esc_attr_e( 'Preguntas frecuentes', 'nuvanx-medical' ); ?>">
				<h2><?php esc_html_e( 'Preguntas frecuentes', 'nuvanx-medical' ); ?></h2>
				<?php foreach ( (array) $page['faqs'] as $faq ) : ?>
					<details><summary><?php echo esc_html( (string) ( $faq['q'] ?? '' ) ); ?></summary><p><?php echo esc_html( (string) ( $faq['a'] ?? '' ) ); ?></p></details>
				<?php endforeach; ?>
			</section>
		<?php endif; ?>
	</article>
	<?php
}

/** Render complete treatment page for governed slugs. */
function nvx_aesthetic_treatment_page_content( string $content ): string {
	if ( ! is_singular( 'page' ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	$page = nvx_aesthetic_treatment_current();
	if ( null === $page ) {
		return $content;
	}
	ob_start();
	nvx_aesthetic_treatment_render( $page );
	return (string) ob_get_clean();
}
add_filter( 'the_content', 'nvx_aesthetic_treatment_page_content', NVX_HOOK_PRIO_CATALOG_CONTENT );

/** Use a dedicated page template for governed aesthetic treatments. */
function nvx_aesthetic_treatment_template( string $template ): string {
	if ( ! nvx_is_aesthetic_treatment_page() ) {
		return $template;
	}
	$custom = get_template_directory() . '/templates/page-aesthetic-treatment.php';
	return is_readable( $custom ) ? $custom : $template;
}
add_filter( 'template_include', 'nvx_aesthetic_treatment_template', 50 );

/** Seed governed pages on staging only; never mutate production automatically. */
function nvx_aesthetic_treatment_seed_pages(): void {
	if ( ! function_exists( 'nvx_environment_is_staging2' ) || ! nvx_environment_is_staging2() ) {
		return;
	}
	if ( ! current_user_can( 'manage_options' ) && ! ( defined( 'WP_CLI' ) && WP_CLI ) ) {
		return;
	}
	foreach ( nvx_aesthetic_treatment_catalog() as $key => $page ) {
		$slug = (string) $page['slug'];
		$post = get_page_by_path( $slug, OBJECT, 'page' );
		$data = array(
			'post_title'   => wp_strip_all_tags( (string) $page['h1'] ),
			'post_name'    => $slug,
			'post_content' => '<div data-nvx-treatment="' . esc_attr( (string) $key ) . '"></div>',
			'post_status'  => 'publish',
			'post_type'    => 'page',
		);
		if ( $post instanceof WP_Post ) {
			$data['ID'] = $post->ID;
			$post_id    = wp_update_post( $data, true );
		} else {
			$post_id = wp_insert_post( $data, true );
		}
		if ( is_wp_error( $post_id ) ) {
			continue;
		}
		update_post_meta( (int) $post_id, '_nvx_aesthetic_treatment_key', (string) $key );
	}
}
add_action( 'admin_init', 'nvx_aesthetic_treatment_seed_pages', 30 );
