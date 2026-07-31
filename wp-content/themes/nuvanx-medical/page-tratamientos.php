<?php
/**
 * Template Name: Tratamientos Hub
 *
 * Canonical theme-owned treatment catalogue for /tratamientos/.
 *
 * @package nuvanx-medical
 */

defined( 'ABSPATH' ) || exit;

ob_start();
?>
<div id="nvx-hub" class="nvx-hub">
	<section class="nvx-hub-hero" aria-labelledby="nvx-hub-hero-title">
		<div class="nvx-hub-hero__content">
			<h1 id="nvx-hub-hero-title" class="nvx-hub-hero__title">Portafolio clínico.</h1>
			<p class="nvx-hub-hero__lead">Anatomía, diagnóstico y tecnología. El protocolo se adapta al tejido, nunca a la inversa.</p>
			<a href="<?php echo esc_url( home_url( '/madrid/valoracion/' ) ); ?>" class="nvx-btn nvx-btn--primary">Iniciar valoración médica</a>
		</div>
	</section>

	<section class="nvx-hub-map" aria-labelledby="nvx-hub-map-title">
		<div class="nvx-hub-map__inner">
			<header class="nvx-hub-map__header">
				<h2 id="nvx-hub-map-title" class="nvx-hub-map__title">Áreas de intervención clínica</h2>
				<p class="nvx-hub-map__desc">Seleccionamos la tecnología y el abordaje en función de la estructura anatómica a tratar.</p>
			</header>
			<div class="nvx-hub-map__grid">
				<div class="nvx-hub-region">
					<span class="nvx-hub-region__num" aria-hidden="true">I</span>
					<h3 class="nvx-hub-region__name">Contorno facial y cuello</h3>
					<p class="nvx-hub-region__list">Papada y submentón | Definición mandibular | Flacidez de cuello | Ojeras y tercio medio | Rinomodelación.</p>
				</div>
				<div class="nvx-hub-region">
					<span class="nvx-hub-region__num" aria-hidden="true">II</span>
					<h3 class="nvx-hub-region__name">Contorno corporal</h3>
					<p class="nvx-hub-region__list">Grasa localizada abdominal | Flancos y caderas | Zona infraumbilical | Firmeza abdominal | Contorno pectoral masculino.</p>
				</div>
				<div class="nvx-hub-region">
					<span class="nvx-hub-region__num" aria-hidden="true">III</span>
					<h3 class="nvx-hub-region__name">Extremidades y zonas complejas</h3>
					<p class="nvx-hub-region__list">Rodillas | Muslos | Brazos | Espalda y zona del sujetador | Contorno de tobillos.</p>
				</div>
				<div class="nvx-hub-region">
					<span class="nvx-hub-region__num" aria-hidden="true">IV</span>
					<h3 class="nvx-hub-region__name">Calidad cutánea y textura</h3>
					<p class="nvx-hub-region__list">Cicatrices | Fotodaño y pigmentación | Poros | Textura facial o corporal | Firmeza cutánea.</p>
				</div>
			</div>
		</div>
	</section>

	<section class="nvx-hub-catalog" aria-labelledby="nvx-hub-catalog-title">
		<header class="nvx-hub-catalog__header">
			<h2 id="nvx-hub-catalog-title" class="nvx-hub-catalog__title">Protocolos e indicaciones médicas</h2>
			<p class="nvx-hub-catalog__desc">La indicación, el número de sesiones y la tecnología se confirman tras la exploración médica.</p>
		</header>
		<div class="nvx-hub-catalog__list">
			<article class="nvx-hub-catalog__item">
				<span class="nvx-hub-catalog__item-num" aria-hidden="true">01</span>
				<h3 class="nvx-hub-catalog__item-title">Endolift® Facial</h3>
				<p class="nvx-hub-catalog__item-indication">Indicación orientativa: óvalo facial, mandíbula, cuello y papada.</p>
				<p class="nvx-hub-catalog__item-desc">Técnica con microfibra láser subdérmica para retracción tisular, indicada tras valoración médica.</p>
				<a href="<?php echo esc_url( home_url( '/endolift-facial-papada-mandibula/' ) ); ?>" class="nvx-text-link">Explorar protocolo</a>
			</article>

			<article class="nvx-hub-catalog__item">
				<span class="nvx-hub-catalog__item-num" aria-hidden="true">02</span>
				<h3 class="nvx-hub-catalog__item-title">Endoláser Corporal</h3>
				<p class="nvx-hub-catalog__item-indication">Indicación orientativa: grasa localizada y firmeza corporal.</p>
				<p class="nvx-hub-catalog__item-desc">Procedimiento láser mínimamente invasivo adaptado a la zona, la densidad del tejido y los objetivos clínicos.</p>
				<a href="<?php echo esc_url( home_url( '/endolaser-corporal-grasa-localizada/' ) ); ?>" class="nvx-text-link">Explorar protocolo</a>
			</article>

			<article class="nvx-hub-catalog__item">
				<span class="nvx-hub-catalog__item-num" aria-hidden="true">03</span>
				<h3 class="nvx-hub-catalog__item-title">Láser CO₂ Fraccionado</h3>
				<p class="nvx-hub-catalog__item-indication">Indicación orientativa: cicatrices, poros, textura y fotodaño.</p>
				<p class="nvx-hub-catalog__item-desc">Resurfacing fraccionado con profundidad, densidad y recuperación ajustadas al diagnóstico.</p>
				<a href="<?php echo esc_url( home_url( '/laser-co2-fraccionado-madrid-textura-cicatrices-poro/' ) ); ?>" class="nvx-text-link">Explorar protocolo</a>
			</article>

			<article class="nvx-hub-catalog__item">
				<span class="nvx-hub-catalog__item-num" aria-hidden="true">04</span>
				<h3 class="nvx-hub-catalog__item-title">Plataforma EXION® BTL</h3>
				<p class="nvx-hub-catalog__item-indication">Indicación orientativa: calidad cutánea, firmeza y protocolos faciales o corporales.</p>
				<p class="nvx-hub-catalog__item-desc">Aplicadores médicos seleccionados según la zona, la calidad de la piel y el resultado esperado.</p>
				<a href="<?php echo esc_url( home_url( '/exion-btl/' ) ); ?>" class="nvx-text-link">Explorar EXION®</a>
			</article>

			<article class="nvx-hub-catalog__item">
				<span class="nvx-hub-catalog__item-num" aria-hidden="true">05</span>
				<h3 class="nvx-hub-catalog__item-title">Medicina Estética Facial</h3>
				<p class="nvx-hub-catalog__item-indication">Indicación orientativa: soporte, proporción, expresión y calidad facial.</p>
				<p class="nvx-hub-catalog__item-desc">Planificación conservadora con técnicas seleccionadas tras diagnóstico, sin protocolos estandarizados.</p>
				<a href="<?php echo esc_url( home_url( '/medicina-estetica/' ) ); ?>" class="nvx-text-link">Explorar medicina estética facial</a>
			</article>

			<article class="nvx-hub-catalog__item">
				<span class="nvx-hub-catalog__item-num" aria-hidden="true">06</span>
				<h3 class="nvx-hub-catalog__item-title">Bioestimulación de colágeno</h3>
				<p class="nvx-hub-catalog__item-indication">Indicación orientativa: pérdida de densidad y firmeza incipiente.</p>
				<p class="nvx-hub-catalog__item-desc">Protocolos de estimulación tisular definidos según edad biológica, calidad cutánea y objetivos.</p>
				<a href="<?php echo esc_url( home_url( '/medicina-estetica/' ) ); ?>" class="nvx-text-link">Explorar opciones faciales</a>
			</article>

			<article class="nvx-hub-catalog__item">
				<span class="nvx-hub-catalog__item-num" aria-hidden="true">07</span>
				<h3 class="nvx-hub-catalog__item-title">BTL EXILITE™ IPL</h3>
				<p class="nvx-hub-catalog__item-indication">Indicación orientativa: manchas, rojeces, fotodaño y calidad superficial.</p>
				<p class="nvx-hub-catalog__item-desc">Luz pulsada médica con parámetros adaptados al fototipo y a la indicación clínica.</p>
				<a href="<?php echo esc_url( home_url( '/btl-exilite-ipl-madrid/' ) ); ?>" class="nvx-text-link">Explorar BTL EXILITE™ IPL</a>
			</article>
		</div>
	</section>

	<section class="nvx-hub-cta" aria-labelledby="nvx-hub-cta-title">
		<h2 id="nvx-hub-cta-title" class="nvx-hub-cta__title">Medicina estética con criterio clínico.</h2>
		<p class="nvx-hub-cta__desc">Si no hay indicación clínica, no hay tratamiento. La valoración permite definir un plan individualizado y explicar alternativas, recuperación y presupuesto.</p>
		<div class="nvx-hub-cta__actions">
			<a href="<?php echo esc_url( home_url( '/madrid/valoracion/' ) ); ?>" class="nvx-btn nvx-btn--primary">Solicitar valoración médica</a>
			<a href="<?php echo esc_url( nvx_cta_whatsapp_url() ); ?>" class="nvx-btn nvx-btn--secondary-on-dark" target="_blank" rel="noopener noreferrer">Contactar por WhatsApp</a>
		</div>
	</section>
</div>
<?php
$content = ob_get_clean();

set_query_var( 'nvx_shell_content', $content );
set_query_var( 'nvx_shell_skip_header', true );
set_query_var( 'nvx_shell_no_wrapper', true );
get_template_part( 'template-parts/content/nvx-page-shell' );
