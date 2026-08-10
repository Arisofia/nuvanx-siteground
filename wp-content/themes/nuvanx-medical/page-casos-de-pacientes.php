<?php
/**
 * Canonical patient-cases page.
 *
 * While real evidence is pending explicit editorial approval, this slug-specific
 * template renders a responsible holding state owned by the theme. Once
 * `_nvx_cases_publication_ready=1`, control returns to the ordinary page shell.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$page_id = (int) get_queried_object_id();
$ready   = $page_id > 0 && '1' === (string) get_post_meta( $page_id, '_nvx_cases_publication_ready', true );

if ( $ready ) {
	require get_template_directory() . '/page.php';
	return;
}

$css_relative = '/assets/css/nvx-cases-holding.css';
$css_path     = get_template_directory() . $css_relative;
if ( is_readable( $css_path ) ) {
	$version = function_exists( 'nvx_asset_version' )
		? nvx_asset_version( $css_relative )
		: (string) filemtime( $css_path );

	wp_enqueue_style(
		'nvx-cases-holding',
		get_template_directory_uri() . $css_relative,
		array( 'nvx-components', 'nvx-patterns' ),
		$version
	);
}

get_header();
?>
<main id="main-content" class="nvx-page nvx-brand-page nvx-cases-holding" aria-labelledby="nvx-cases-h1">
	<section class="nvx-brand-hero nvx-cases-holding__hero" aria-labelledby="nvx-cases-h1">
		<div class="nvx-brand-hero__inner">
			<div class="nvx-brand-hero__copy">
				<p class="nvx-brand-kicker"><?php esc_html_e( 'EVIDENCIA CLÍNICA · MADRID', 'nuvanx-medical' ); ?></p>
				<h1 id="nvx-cases-h1" class="nvx-brand-hero__title"><?php esc_html_e( 'Casos de pacientes', 'nuvanx-medical' ); ?></h1>
			</div>
		</div>
	</section>

	<section class="nvx-brand-section nvx-cases-holding__intro" aria-labelledby="nvx-cases-intro-title">
		<div class="nvx-shell nvx-brand-section__inner">
			<p class="nvx-brand-kicker"><?php esc_html_e( 'PUBLICACIÓN RESPONSABLE', 'nuvanx-medical' ); ?></p>
			<h2 id="nvx-cases-intro-title" class="nvx-brand-title"><?php esc_html_e( 'Evolución documentada, no promesas', 'nuvanx-medical' ); ?></h2>
			<p class="nvx-brand-body nvx-cases-holding__lead"><?php esc_html_e( 'Estamos preparando esta sección con casos clínicos reales revisados por el equipo médico. Solo publicaremos material con consentimiento documentado y contexto suficiente para interpretar la evolución sin convertir una imagen en una promesa de resultado.', 'nuvanx-medical' ); ?></p>

			<ul class="nvx-cases-holding__grid">
				<li class="nvx-brand-card nvx-cases-holding__card">
					<h3 class="nvx-brand-card__title"><?php esc_html_e( 'Misma persona y seguimiento', 'nuvanx-medical' ); ?></h3>
					<p class="nvx-brand-card__body"><?php esc_html_e( 'Cada caso identificará el momento de seguimiento y evitará presentar imágenes de personas distintas como una misma evolución.', 'nuvanx-medical' ); ?></p>
				</li>
				<li class="nvx-brand-card nvx-cases-holding__card">
					<h3 class="nvx-brand-card__title"><?php esc_html_e( 'Fotografía comparable', 'nuvanx-medical' ); ?></h3>
					<p class="nvx-brand-card__body"><?php esc_html_e( 'Cuando sea posible, mantendremos encuadre, posición y luz comparables para reducir distorsiones visuales.', 'nuvanx-medical' ); ?></p>
				</li>
				<li class="nvx-brand-card nvx-cases-holding__card">
					<h3 class="nvx-brand-card__title"><?php esc_html_e( 'Contexto clínico', 'nuvanx-medical' ); ?></h3>
					<p class="nvx-brand-card__body"><?php esc_html_e( 'La indicación, el tratamiento realizado, el seguimiento y los límites del caso acompañarán a las imágenes.', 'nuvanx-medical' ); ?></p>
				</li>
			</ul>
		</div>
	</section>

	<section class="nvx-brand-section nvx-cases-holding__scope" aria-labelledby="nvx-cases-scope-title">
		<div class="nvx-shell nvx-brand-section__inner">
			<p class="nvx-brand-kicker"><?php esc_html_e( 'EN PREPARACIÓN', 'nuvanx-medical' ); ?></p>
			<h2 id="nvx-cases-scope-title" class="nvx-brand-title"><?php esc_html_e( 'Qué encontrarás cuando se publique', 'nuvanx-medical' ); ?></h2>

			<ul class="nvx-cases-holding__grid">
				<li class="nvx-brand-card nvx-cases-holding__card">
					<p class="nvx-brand-card__kicker"><?php esc_html_e( 'ROSTRO', 'nuvanx-medical' ); ?></p>
					<h3 class="nvx-brand-card__title"><?php esc_html_e( 'Contorno y calidad de piel', 'nuvanx-medical' ); ?></h3>
					<p class="nvx-brand-card__body"><?php esc_html_e( 'Casos seleccionados por indicación médica, con seguimiento suficiente para explicar qué cambió y qué no.', 'nuvanx-medical' ); ?></p>
				</li>
				<li class="nvx-brand-card nvx-cases-holding__card">
					<p class="nvx-brand-card__kicker"><?php esc_html_e( 'CUERPO', 'nuvanx-medical' ); ?></p>
					<h3 class="nvx-brand-card__title"><?php esc_html_e( 'Grasa localizada y firmeza', 'nuvanx-medical' ); ?></h3>
					<p class="nvx-brand-card__body"><?php esc_html_e( 'Evoluciones corporales contextualizadas por zona, técnica, tiempos y características de partida.', 'nuvanx-medical' ); ?></p>
				</li>
				<li class="nvx-brand-card nvx-cases-holding__card">
					<p class="nvx-brand-card__kicker"><?php esc_html_e( 'PIEL', 'nuvanx-medical' ); ?></p>
					<h3 class="nvx-brand-card__title"><?php esc_html_e( 'Textura, cicatrices y fotodaño', 'nuvanx-medical' ); ?></h3>
					<p class="nvx-brand-card__body"><?php esc_html_e( 'Documentación clínica que permita valorar respuesta y recuperación sin ocultar variabilidad individual.', 'nuvanx-medical' ); ?></p>
				</li>
			</ul>
		</div>
	</section>

	<section class="nvx-brand-section nvx-cases-holding__criteria" aria-labelledby="nvx-cases-criteria-title">
		<div class="nvx-shell nvx-brand-section__inner">
			<p class="nvx-brand-kicker"><?php esc_html_e( 'CRITERIO MÉDICO', 'nuvanx-medical' ); ?></p>
			<h2 id="nvx-cases-criteria-title" class="nvx-brand-title"><?php esc_html_e( 'Antes de comparar casos, revisamos su situación clínica', 'nuvanx-medical' ); ?></h2>
			<div class="nvx-cases-holding__criteria-grid">
				<div><p class="nvx-brand-body"><?php esc_html_e( 'Una fotografía aislada no explica una indicación. Por eso cada publicación deberá identificar, cuando corresponda, la zona tratada, la técnica utilizada, el tiempo transcurrido y las condiciones de la toma fotográfica.', 'nuvanx-medical' ); ?></p></div>
				<div><p class="nvx-brand-body"><?php esc_html_e( 'Los resultados pueden variar me entre pacientes. La valoración médica individual sigue siendo el punto de partida para determinar si un tratamiento tiene indicación y qué expectativas son razonables.', 'nuvanx-medical' ); ?></p></div>
			</div>
			<?php
			if ( have_posts() ) {
				while ( have_posts() ) {
					the_post();
					if ( get_the_content() ) {
						echo '<div class="nvx-cases-holding__editorial-content">';

						$downgrade_h1 = function ( $content ) {
							if ( false === stripos( $content, '<h1' ) ) {
								return $content;
							}
							$content = (string) preg_replace( '/<h1(\b[^>]*)>/iu', '<h2$1>', $content );
							return (string) str_ireplace( '</h1>', '</h2>', $content );
						};

						add_filter( 'the_content', $downgrade_h1, 20 );
						the_content();
						remove_filter( 'the_content', $downgrade_h1, 20 );

						echo '</div>';
					}
				}
			}
			?>
		</div>
	</section>
</main>
<?php
get_footer();
