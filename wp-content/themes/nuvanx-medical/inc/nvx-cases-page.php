<?php
/**
 * Canonical editorial renderer for the patient cases page.
 *
 * Presents authorised patient evolutions by treatment and anatomical area.
 * Publication governance remains in nvx-page-hygiene.php.
 *
 * @package nuvanx-medical
 */

defined( 'ABSPATH' ) || exit;

/** Whether the current request is the clinical cases page. */
function nvxCasesPageIsCurrent(): bool {
	if ( ! is_page() ) {
		return false;
	}

	$page_id = (int) get_queried_object_id();
	$slug    = (string) get_post_field( 'post_name', $page_id );

	return 2645 === $page_id || in_array( $slug, array( 'casos-de-pacientes', 'casos-clinicos' ), true );
}

/** Enqueue the isolated cases stylesheet only on the canonical route. */
function nvxCasesPageEnqueueAssets(): void {
	if ( ! nvxCasesPageIsCurrent() ) {
		return;
	}

	$relative = 'assets/css/nvx-cases-editorial.css';
	wp_enqueue_style(
		'nvx-cases-editorial',
		get_template_directory_uri() . '/' . $relative,
		array( 'nvx-components' ),
		nvx_asset_version( $relative )
	);
}
add_action( 'wp_enqueue_scripts', 'nvxCasesPageEnqueueAssets', 35 );

/** Add a stable page-state class for scoped layout ownership. */
function nvxCasesPageBodyClass( array $classes ): array {
	if ( nvxCasesPageIsCurrent() ) {
		$classes[] = 'nvx-cases-editorial-page';
	}

	return array_values( array_unique( $classes ) );
}
add_filter( 'body_class', 'nvxCasesPageBodyClass' );

/** Build the canonical cases page markup. */
function nvxCasesPageMarkup(): string {
	$cases = array(
		array(
			'image'     => content_url( '/uploads/2026/07/Endolift-Papada.webp' ),
			'alt'       => 'Caso de paciente tratada con Endolift facial en perfil, papada y cuello',
			'label'     => 'CASO 01 · ENDOLIFT® FACIAL',
			'title'     => 'Perfil, papada y cuello',
			'treatment' => 'Tratamiento orientado a mejorar la continuidad entre mandíbula y cuello y acompañar la firmeza de la zona submentoniana.',
		),
		array(
			'image'     => content_url( '/uploads/2026/07/Endolift-Full-Face.webp' ),
			'alt'       => 'Caso de paciente tratada con Endolift facial integral',
			'label'     => 'CASO 02 · ENDOLIFT® FACIAL',
			'title'     => 'Abordaje facial integral',
			'treatment' => 'Tratamiento facial planificado para trabajar calidad cutánea, soporte y armonía del tercio inferior sin alterar los rasgos.',
		),
		array(
			'image'     => content_url( '/uploads/2026/07/Endolift-Brazos.webp' ),
			'alt'       => 'Caso de paciente tratada con Endolift corporal en brazos',
			'label'     => 'CASO 03 · ENDOLIFT® CORPORAL',
			'title'     => 'Brazos y continuidad con la axila',
			'treatment' => 'Tratamiento corporal orientado al contorno y la firmeza de la cara posterior del brazo, según la calidad de la piel.',
		),
		array(
			'image'     => content_url( '/uploads/2026/07/Endolift-Abdomen.webp' ),
			'alt'       => 'Caso de paciente tratada con Endolift corporal en abdomen y flancos',
			'label'     => 'CASO 04 · ENDOLIFT® CORPORAL',
			'title'     => 'Abdomen y flancos',
			'treatment' => 'Tratamiento planificado por zonas después de valorar distribución de grasa localizada, flacidez y calidad cutánea.',
		),
		array(
			'image'     => content_url( '/uploads/2026/07/Endolift-Espalda-Flancos-y-Sujetador.webp' ),
			'alt'       => 'Caso de paciente tratada con Endolift corporal en espalda y zona del sujetador',
			'label'     => 'CASO 05 · ENDOLIFT® CORPORAL',
			'title'     => 'Espalda y zona del sujetador',
			'treatment' => 'Tratamiento dirigido a mejorar la continuidad del contorno en la espalda, adaptado al tejido y a la flacidez de la zona.',
		),
	);

	ob_start();
	?>
	<article class="nvx-cases-page" aria-labelledby="nvx-cases-title">
		<section class="nvx-cases-hero">
			<div class="nvx-cases-hero__copy">
				<p class="nvx-cases-eyebrow">PACIENTES NUVANX · MADRID</p>
				<h1 id="nvx-cases-title">Casos de pacientes y tratamientos realizados en NUVANX.</h1>
				<p class="nvx-cases-hero__lead">Una selección de evoluciones clínicas de personas tratadas en rostro y contorno corporal. Cada caso muestra la zona abordada y el tratamiento indicado después de una valoración médica individual.</p>
				<a class="nvx-btn nvx-btn--primary" href="<?php echo esc_url( home_url( '/madrid/valoracion/' ) ); ?>">Solicitar valoración médica</a>
			</div>
			<div class="nvx-cases-hero__media">
				<img src="<?php echo esc_url( content_url( '/uploads/2026/07/proceso-medico-laser-nuvanx-madrid.webp' ) ); ?>" alt="Paciente durante su experiencia clínica en NUVANX Madrid" fetchpriority="high" decoding="async">
			</div>
		</section>

		<section class="nvx-cases-evolution" aria-labelledby="nvx-cases-evolution-title">
			<header class="nvx-cases-section-header">
				<div>
					<p class="nvx-cases-eyebrow">CASOS POR TRATAMIENTO Y ZONA</p>
					<h2 id="nvx-cases-evolution-title">Endolift® facial y corporal en pacientes NUVANX</h2>
				</div>
				<p>Las imágenes corresponden a casos individuales. La técnica, la extensión tratada y la evolución dependen de la anatomía, la calidad de la piel y el plan médico de cada persona.</p>
			</header>
			<div class="nvx-cases-evolution__grid">
				<?php foreach ( $cases as $case ) : ?>
					<figure class="nvx-cases-evolution-card">
						<div class="nvx-cases-evolution-card__media">
							<img src="<?php echo esc_url( $case['image'] ); ?>" alt="<?php echo esc_attr( $case['alt'] ); ?>" loading="lazy" decoding="async">
						</div>
						<figcaption>
							<span><?php echo esc_html( $case['label'] ); ?></span>
							<strong><?php echo esc_html( $case['title'] ); ?></strong>
							<p><?php echo esc_html( $case['treatment'] ); ?></p>
						</figcaption>
					</figure>
				<?php endforeach; ?>
			</div>
		</section>

		<section class="nvx-cases-disclosure" aria-labelledby="nvx-cases-disclosure-title">
			<div>
				<p class="nvx-cases-eyebrow">TU CASO ES DIFERENTE</p>
				<h2 id="nvx-cases-disclosure-title">El tratamiento se decide para tu anatomía, no a partir de una fotografía.</h2>
			</div>
			<div class="nvx-cases-disclosure__copy">
				<p>En consulta valoramos qué parte del cambio que buscas depende de grasa localizada, flacidez, estructura, cicatriz o calidad cutánea. Con esa información se determina si Endolift®, Endoláser u otra tecnología tiene sentido para ti.</p>
				<p>Las evoluciones mostradas sirven como referencia clínica. No garantizan un resultado idéntico y no sustituyen el diagnóstico médico.</p>
				<a class="nvx-cases-text-link" href="<?php echo esc_url( home_url( '/medicina-estetica-laser/' ) ); ?>">Explorar tratamientos y tecnologías <span aria-hidden="true">→</span></a>
			</div>
		</section>

		<section class="nvx-cases-closure" aria-labelledby="nvx-cases-closure-title">
			<p class="nvx-cases-eyebrow">VALORACIÓN INDIVIDUAL</p>
			<h2 id="nvx-cases-closure-title">Conoce qué tratamiento encaja con tu caso.</h2>
			<p>El médico revisará la zona que deseas tratar, las alternativas disponibles, el proceso esperado y el presupuesto correspondiente a tu plan.</p>
			<div class="nvx-cases-closure__actions">
				<a class="nvx-btn nvx-btn--primary" href="<?php echo esc_url( home_url( '/madrid/valoracion/' ) ); ?>">Solicitar valoración médica</a>
				<a class="nvx-btn nvx-btn--secondary-on-dark" href="<?php echo esc_url( nvx_cta_whatsapp_url() ); ?>" target="_blank" rel="noopener noreferrer">Contactar por WhatsApp</a>
			</div>
		</section>
	</article>
	<?php
	return (string) ob_get_clean();
}

/** Replace the inherited page content after route-level presentation filters. */
function nvxCasesPageReplaceContent( $content ) {
	if ( is_admin() || ! nvxCasesPageIsCurrent() || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}

	return nvxCasesPageMarkup();
}
add_filter( 'the_content', 'nvxCasesPageReplaceContent', 120 );
