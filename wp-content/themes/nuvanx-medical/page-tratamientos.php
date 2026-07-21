<?php
/**
 * Template Name: Tratamientos Hub (V1)
 *
 * Hardcoded structure replacing the legacy catalog filter for the "/tratamientos/" hub.
 *
 * @package NUVANX_Medical
 */
defined( 'ABSPATH' ) || exit;

ob_start();
?>
<main id="nvx-hub" class="nvx-hub" role="main" aria-label="<?php echo esc_attr( wp_strip_all_tags( get_the_title() ) ); ?>">

	<!-- SECCIÓN 01: HERO VIEWPORT -->
	<section class="nvx-hub-hero" aria-labelledby="nvx-hub-hero-title">
		<div class="nvx-hub-hero__content">
			<h1 id="nvx-hub-hero-title" class="nvx-hub-hero__title">Portafolio Clínico.</h1>
			<p class="nvx-hub-hero__lead">Anatomía, diagnóstico y tecnología. El protocolo se adapta al tejido, nunca a la inversa.</p>
			<a href="<?php echo esc_url( home_url( '/madrid/valoracion/' ) ); ?>" class="nvx-btn nvx-btn--primary">Iniciar Valoración Médica</a>
		</div>
	</section>

	<!-- SECCIÓN 02: EL MAPA ANATÓMICO (El Motor SEO/GEO) -->
	<section class="nvx-hub-map" aria-labelledby="nvx-hub-map-title">
		<div class="nvx-hub-map__inner">
			<header class="nvx-hub-map__header">
				<h2 id="nvx-hub-map-title" class="nvx-hub-map__title">Áreas de intervención clínica</h2>
				<p class="nvx-hub-map__desc">Seleccionamos la tecnología y el abordaje en función de la estructura anatómica a tratar.</p>
			</header>
			
			<div class="nvx-hub-map__grid">
				<div class="nvx-hub-region">
					<span class="nvx-hub-region__num" aria-hidden="true">I</span>
					<h3 class="nvx-hub-region__name">Contorno Facial y Cuello</h3>
					<p class="nvx-hub-region__list">Papada y submentón | Definición mandibular | Flacidez de cuello | Ojeras y tercio medio | Rinomodelación.</p>
				</div>
				<div class="nvx-hub-region">
					<span class="nvx-hub-region__num" aria-hidden="true">II</span>
					<h3 class="nvx-hub-region__name">Arquitectura Corporal</h3>
					<p class="nvx-hub-region__list">Grasa localizada abdominal | Flancos y caderas | Adiposidad infraumbilical (FUPA) | Restauración post-parto (Tensado cutáneo) | Ginecomastia no quirúrgica.</p>
				</div>
				<div class="nvx-hub-region">
					<span class="nvx-hub-region__num" aria-hidden="true">III</span>
					<h3 class="nvx-hub-region__name">Extremidades y Zonas Complejas</h3>
					<p class="nvx-hub-region__list">Lipodistrofia en rodillas | Remodelación de muslos (cara interna y externa) | Retracción cutánea en brazos | Contorno de tobillos.</p>
				</div>
				<div class="nvx-hub-region">
					<span class="nvx-hub-region__num" aria-hidden="true">IV</span>
					<h3 class="nvx-hub-region__name">Regeneración Cutánea y Textura</h3>
					<p class="nvx-hub-region__list">Cicatrices atróficas | Fotodaño y pigmentación | Reducción de poro | Resurfacing facial profundo.</p>
				</div>
			</div>
		</div>
	</section>

	<!-- SECCIÓN 03: EL CATÁLOGO TECNOLÓGICO -->
	<section class="nvx-hub-catalog" aria-labelledby="nvx-hub-catalog-title">
		<header class="nvx-hub-catalog__header">
			<h2 id="nvx-hub-catalog-title" class="nvx-hub-catalog__title">Protocolos e Indicaciones Médicas</h2>
			<p class="nvx-hub-catalog__desc">La indicación definitiva, el número de sesiones y la tecnología a emplear se confirman exclusivamente tras la exploración médica.</p>
		</header>
		
		<div class="nvx-hub-catalog__list">
			<article class="nvx-hub-catalog__item">
				<span class="nvx-hub-catalog__item-num" aria-hidden="true">01</span>
				<h3 class="nvx-hub-catalog__item-title">Endolift® Facial</h3>
				<p class="nvx-hub-catalog__item-indication">Indicación: Redefinición del óvalo facial y reducción de papada.</p>
				<p class="nvx-hub-catalog__item-desc">Tensado subdérmico mediante microfibras ópticas estériles. Arquitectura láser para retracción tisular sin incisiones.</p>
				<div><a href="<?php echo esc_url( home_url( '/tratamientos/endolift/' ) ); ?>" class="nvx-text-link">Explorar protocolo</a></div>
			</article>

			<article class="nvx-hub-catalog__item">
				<span class="nvx-hub-catalog__item-num" aria-hidden="true">02</span>
				<h3 class="nvx-hub-catalog__item-title">Endoláser Corporal</h3>
				<p class="nvx-hub-catalog__item-indication">Indicación: Grasa localizada y flacidez corporal.</p>
				<p class="nvx-hub-catalog__item-desc">Reducción adiposa y mejora de la firmeza en un único tiempo médico. Protocolo progresivo adaptado a la densidad del tejido.</p>
				<div><a href="<?php echo esc_url( home_url( '/tratamientos/endolaser-corporal/' ) ); ?>" class="nvx-text-link">Explorar protocolo</a></div>
			</article>

			<article class="nvx-hub-catalog__item">
				<span class="nvx-hub-catalog__item-num" aria-hidden="true">03</span>
				<h3 class="nvx-hub-catalog__item-title">Láser CO₂ Fraccionado</h3>
				<p class="nvx-hub-catalog__item-indication">Indicación: Textura, cicatrices y rejuvenecimiento severo.</p>
				<p class="nvx-hub-catalog__item-desc">Vaporización fraccionada de alta precisión. Control absoluto sobre la profundidad de ablación y el tiempo de recuperación.</p>
				<div><a href="<?php echo esc_url( home_url( '/tratamientos/laser-co2-fraccionado/' ) ); ?>" class="nvx-text-link">Explorar protocolo</a></div>
			</article>

			<article class="nvx-hub-catalog__item">
				<span class="nvx-hub-catalog__item-num" aria-hidden="true">04</span>
				<h3 class="nvx-hub-catalog__item-title">Plataforma EXION®</h3>
				<p class="nvx-hub-catalog__item-indication">Indicación: Calidad cutánea, tensado no invasivo y regeneración.</p>
				<p class="nvx-hub-catalog__item-desc">Radiofrecuencia fraccionada y ultrasonido. Profundidad y parámetros ajustados por diagnóstico para maximizar la síntesis de colágeno y elastina.</p>
				<div><a href="<?php echo esc_url( home_url( '/tratamientos/exion/' ) ); ?>" class="nvx-text-link">Explorar protocolo</a></div>
			</article>

			<article class="nvx-hub-catalog__item">
				<span class="nvx-hub-catalog__item-num" aria-hidden="true">05</span>
				<h3 class="nvx-hub-catalog__item-title">Armonización y Volumetría Facial</h3>
				<p class="nvx-hub-catalog__item-indication">Indicación: Pérdida de soporte óseo, asimetrías y prevención del envejecimiento.</p>
				<p class="nvx-hub-catalog__item-desc">Uso estratégico de ácido hialurónico y neuromoduladores. Volumen selectivo para armonizar facciones bajo un estricto criterio conservador. No rigidizamos la expresión.</p>
				<div><a href="<?php echo esc_url( home_url( '/tratamientos/medicina-estetica-facial/' ) ); ?>" class="nvx-text-link">Explorar protocolo</a></div>
			</article>

			<article class="nvx-hub-catalog__item">
				<span class="nvx-hub-catalog__item-num" aria-hidden="true">06</span>
				<h3 class="nvx-hub-catalog__item-title">Bioestimulación de Colágeno</h3>
				<p class="nvx-hub-catalog__item-indication">Indicación: Flacidez incipiente y pérdida de densidad dérmica.</p>
				<p class="nvx-hub-catalog__item-desc">Inducción tisular mediante vectores biológicos. Salud estructural desde el interior hacia la superficie.</p>
				<div><a href="<?php echo esc_url( home_url( '/tratamientos/medicina-estetica-facial/' ) ); ?>" class="nvx-text-link">Explorar protocolo</a></div>
			</article>

			<article class="nvx-hub-catalog__item">
				<span class="nvx-hub-catalog__item-num" aria-hidden="true">07</span>
				<h3 class="nvx-hub-catalog__item-title">EMFUSION® y BTL EXILITE™ IPL</h3>
				<p class="nvx-hub-catalog__item-indication">Indicación: Lesiones vasculares, pigmentarias y soporte de barrera.</p>
				<p class="nvx-hub-catalog__item-desc">Luz pulsada médica y sistemas de infusión cutánea. Tratamientos de superficie confirmados tras análisis de fototipo.</p>
				<div><a href="<?php echo esc_url( home_url( '/tratamientos/emfusion/' ) ); ?>" class="nvx-text-link">Explorar protocolo</a></div>
			</article>
		</div>
	</section>

	<!-- SECCIÓN 04: SOCIOS TECNOLÓGICOS (Trust & Authority) -->
	<section class="nvx-hub-trust" aria-labelledby="nvx-hub-trust-title">
		<div class="nvx-hub-trust__inner">
			<h2 id="nvx-hub-trust-title" class="nvx-hub-trust__title">Aval Científico y Farmacéutico</h2>
			<p class="nvx-hub-trust__desc">Colaboramos exclusivamente con laboratorios y plataformas de grado médico con trazabilidad documentada y evidencia clínica contrastada.</p>
			
			<div class="nvx-hub-trust__logos">
				<span class="nvx-hub-trust__logo-text">DEKA</span>
				<span class="nvx-hub-trust__logo-text">BTL</span>
				<span class="nvx-hub-trust__logo-text">Teoxane</span>
				<span class="nvx-hub-trust__logo-text">Merz Pharma</span>
				<span class="nvx-hub-trust__logo-text">Vivacy</span>
				<span class="nvx-hub-trust__logo-text">IBSA</span>
				<span class="nvx-hub-trust__logo-text">Allergan Aesthetics</span>
				<span class="nvx-hub-trust__logo-text">Galderma</span>
			</div>
		</div>
	</section>

	<!-- SECCIÓN 05: LLAMADA A LA ACCIÓN FINAL -->
	<section class="nvx-hub-cta">
		<h2 class="nvx-hub-cta__title">Medicina estética con criterio clínico.</h2>
		<p class="nvx-hub-cta__desc">Si no hay indicación clínica, no hay tratamiento. Da el siguiente paso con una valoración médica personalizada. Plan individualizado. Precisión médica. Recuperación documentada.</p>
		<div class="nvx-hub-cta__actions">
			<a href="<?php echo esc_url( home_url( '/madrid/valoracion/' ) ); ?>" class="nvx-btn nvx-btn--primary">Solicitar Valoración Médica</a>
			<a href="https://wa.me/34669319836" class="nvx-btn nvx-btn--secondary" style="border-color: var(--nvx-light); color: var(--nvx-light);">Contactar por WhatsApp</a>
		</div>
	</section>

</main><!-- #nvx-hub -->
<?php
$content = ob_get_clean();

set_query_var( 'nvx_shell_content', $content );
set_query_var( 'nvx_shell_skip_header', true );
set_query_var( 'nvx_shell_no_wrapper', true );
get_template_part( 'template-parts/content/nvx-page-shell' );
