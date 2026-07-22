<?php
/**
 * Template Name: Tratamientos Hub
 *
 * Canonical theme-owned clinical portfolio for /tratamientos/.
 *
 * @package nuvanx-medical
 */

defined( 'ABSPATH' ) || exit;

ob_start();
?>
<div id="nvx-hub" class="nvx-hub">
	<section class="nvx-hub-hero" aria-labelledby="nvx-hub-hero-title">
		<div class="nvx-hub-hero__content">
			<p class="nvx-brand-kicker">MEDICINA ESTÉTICA LÁSER</p>
			<h1 id="nvx-hub-hero-title" class="nvx-hub-hero__title">Portafolio clínico.</h1>
			<p class="nvx-hub-hero__lead">Invertimos en plataformas médicas de precisión, pero ninguna máquina sustituye al criterio clínico. Si no hay indicación, no hay tratamiento.</p>
			<p class="nvx-brand-body">Navega por nuestras áreas de actuación. La tecnología, los parámetros y el protocolo se determinan después de una exploración presencial en Madrid.</p>
			<a href="<?php echo esc_url( home_url( '/madrid/valoracion/' ) ); ?>" class="nvx-btn nvx-btn--primary">Solicitar valoración médica</a>
		</div>
	</section>

	<section class="nvx-brand-section" aria-labelledby="nvx-hub-philosophy-title">
		<div class="nvx-shell nvx-brand-section__inner">
			<h2 id="nvx-hub-philosophy-title" class="nvx-brand-heading-2">La anatomía dicta el plan; la tecnología lo ejecuta</h2>
			<p class="nvx-brand-body">Tener una plataforma avanzada no garantiza una indicación adecuada. En NUVANX, las tecnologías son herramientas al servicio del diagnóstico. El portafolio se organiza por anatomía, tejido predominante y objetivos realistas para que cada intervención pueda justificarse y explicarse antes de empezar.</p>
		</div>
	</section>

	<section class="nvx-hub-map" aria-labelledby="nvx-hub-map-title">
		<div class="nvx-hub-map__inner">
			<header class="nvx-hub-map__header">
				<h2 id="nvx-hub-map-title" class="nvx-hub-map__title">Áreas Anatómicas y Protocolos</h2>
				<p class="nvx-hub-map__desc">Una misma preocupación puede tener causas distintas. El diagnóstico diferencia grasa, laxitud, textura, soporte y alteraciones de la pared muscular antes de seleccionar el abordaje.</p>
			</header>
			<div class="nvx-hub-map__grid">
				<article class="nvx-hub-region">
					<span class="nvx-hub-region__num" aria-hidden="true">I</span>
					<h3 class="nvx-hub-region__name">Contorno y Proporción Facial</h3>
					<p class="nvx-hub-region__list">Profile Definition™ diferencia grasa submentoniana, laxitud cervical y pérdida de soporte mandibular antes de plantear Endolift®, tecnologías de superficie o medicina facial.</p>
					<a class="nvx-text-link" href="<?php echo esc_url( home_url( '/papada-definicion-mandibular-madrid/' ) ); ?>">Explorar papada y mandíbula</a>
				</article>
				<article class="nvx-hub-region">
					<span class="nvx-hub-region__num" aria-hidden="true">II</span>
					<h3 class="nvx-hub-region__name">Arquitectura Corporal y Couture Sculpt™</h3>
					<p class="nvx-hub-region__list">Abdomen, flancos, brazos, espalda, muslos y rodillas se valoran como unidades conectadas. Couture Sculpt™ organiza el plan según grasa localizada, laxitud y continuidad del contorno.</p>
					<a class="nvx-text-link" href="<?php echo esc_url( home_url( '/remodelacion-corporal-laser-madrid/' ) ); ?>">Explorar remodelación corporal</a>
				</article>
				<article class="nvx-hub-region">
					<span class="nvx-hub-region__num" aria-hidden="true">III</span>
					<h3 class="nvx-hub-region__name">Calidad de Piel, Tono y Superficie</h3>
					<p class="nvx-hub-region__list">Skin Architecture™, Surface Renewal™ y Tone Correction™ abordan firmeza, densidad, cicatrices, poros, textura, manchas y rojeces según diagnóstico y fototipo.</p>
					<a class="nvx-text-link" href="<?php echo esc_url( home_url( '/protocolos-signature/' ) ); ?>">Explorar Protocolos Signature</a>
				</article>
				<article class="nvx-hub-region">
					<span class="nvx-hub-region__num" aria-hidden="true">IV</span>
					<h3 class="nvx-hub-region__name">Cambios posgestacionales</h3>
					<p class="nvx-hub-region__list">Post-Maternity Contour™ diferencia grasa subcutánea, laxitud, estrías, cicatriz y posibles alteraciones musculares para determinar qué puede tratarse en medicina estética y cuándo conviene derivar.</p>
					<a class="nvx-text-link" href="<?php echo esc_url( home_url( '/tratamiento-postparto-abdomen-contorno-corporal-madrid/' ) ); ?>">Explorar valoración posgestacional</a>
				</article>
			</div>
		</div>
	</section>

	<section class="nvx-hub-catalog" aria-labelledby="nvx-hub-catalog-title">
		<header class="nvx-hub-catalog__header">
			<h2 id="nvx-hub-catalog-title" class="nvx-hub-catalog__title">Nuestro Arsenal Tecnológico</h2>
			<p class="nvx-hub-catalog__desc">Plataformas médicas seleccionadas por indicación. La modalidad, el número de sesiones, los parámetros y la recuperación se confirman tras la exploración.</p>
		</header>
		<div class="nvx-hub-catalog__list">
			<article class="nvx-hub-catalog__item">
				<span class="nvx-hub-catalog__item-num" aria-hidden="true">01</span>
				<h3 class="nvx-hub-catalog__item-title">Endolift® Facial</h3>
				<p class="nvx-hub-catalog__item-indication">Indicación orientativa: óvalo facial, mandíbula, cuello y papada.</p>
				<p class="nvx-hub-catalog__item-desc">Microfibra láser subdérmica utilizada cuando la exploración identifica una indicación para actuar sobre grasa localizada o laxitud.</p>
				<a href="<?php echo esc_url( home_url( '/endolift-facial-papada-mandibula/' ) ); ?>" class="nvx-text-link">Explorar Endolift® facial</a>
			</article>
			<article class="nvx-hub-catalog__item">
				<span class="nvx-hub-catalog__item-num" aria-hidden="true">02</span>
				<h3 class="nvx-hub-catalog__item-title">Endoláser Corporal</h3>
				<p class="nvx-hub-catalog__item-indication">Indicación orientativa: grasa localizada y laxitud corporal.</p>
				<p class="nvx-hub-catalog__item-desc">Procedimiento láser mínimamente invasivo adaptado a la zona, la densidad del tejido y los límites clínicos del caso.</p>
				<a href="<?php echo esc_url( home_url( '/endolaser-corporal-grasa-localizada/' ) ); ?>" class="nvx-text-link">Explorar Endoláser corporal</a>
			</article>
			<article class="nvx-hub-catalog__item">
				<span class="nvx-hub-catalog__item-num" aria-hidden="true">03</span>
				<h3 class="nvx-hub-catalog__item-title">Láser CO₂ Fraccionado</h3>
				<p class="nvx-hub-catalog__item-indication">Indicación orientativa: cicatrices, poros, textura, estrías y fotodaño.</p>
				<p class="nvx-hub-catalog__item-desc">Resurfacing fraccionado con profundidad, densidad y recuperación ajustadas al diagnóstico, la zona y el fototipo.</p>
				<a href="<?php echo esc_url( home_url( '/laser-co2-fraccionado-madrid-textura-cicatrices-poro/' ) ); ?>" class="nvx-text-link">Explorar Láser CO₂</a>
			</article>
			<article class="nvx-hub-catalog__item">
				<span class="nvx-hub-catalog__item-num" aria-hidden="true">04</span>
				<h3 class="nvx-hub-catalog__item-title">Plataforma EXION® BTL</h3>
				<p class="nvx-hub-catalog__item-indication">Indicación orientativa: firmeza, calidad cutánea y protocolos faciales o corporales.</p>
				<p class="nvx-hub-catalog__item-desc">Aplicadores seleccionados según la zona, la calidad del tejido y el objetivo definido durante la valoración.</p>
				<a href="<?php echo esc_url( home_url( '/exion-btl/' ) ); ?>" class="nvx-text-link">Explorar EXION®</a>
			</article>
			<article class="nvx-hub-catalog__item">
				<span class="nvx-hub-catalog__item-num" aria-hidden="true">05</span>
				<h3 class="nvx-hub-catalog__item-title">Medicina Estética Facial</h3>
				<p class="nvx-hub-catalog__item-indication">Indicación orientativa: soporte, proporción, expresión y calidad facial.</p>
				<p class="nvx-hub-catalog__item-desc">Planificación conservadora con técnicas seleccionadas después del diagnóstico, sin protocolos estandarizados por tendencia.</p>
				<a href="<?php echo esc_url( home_url( '/medicina-estetica/' ) ); ?>" class="nvx-text-link">Explorar medicina estética facial</a>
			</article>
			<article class="nvx-hub-catalog__item">
				<span class="nvx-hub-catalog__item-num" aria-hidden="true">06</span>
				<h3 class="nvx-hub-catalog__item-title">Bioestimulación de colágeno</h3>
				<p class="nvx-hub-catalog__item-indication">Indicación orientativa: pérdida de densidad y firmeza.</p>
				<p class="nvx-hub-catalog__item-desc">Protocolos de estimulación tisular definidos según calidad cutánea, anatomía y objetivos individualizados.</p>
				<a href="<?php echo esc_url( home_url( '/medicina-estetica/' ) ); ?>" class="nvx-text-link">Explorar opciones faciales</a>
			</article>
			<article class="nvx-hub-catalog__item">
				<span class="nvx-hub-catalog__item-num" aria-hidden="true">07</span>
				<h3 class="nvx-hub-catalog__item-title">BTL EXILITE™ IPL</h3>
				<p class="nvx-hub-catalog__item-indication">Indicación orientativa: manchas, rojeces, fotodaño y calidad superficial.</p>
				<p class="nvx-hub-catalog__item-desc">Luz pulsada médica con parámetros adaptados al diagnóstico, al fototipo y a la diana clínica.</p>
				<a href="<?php echo esc_url( home_url( '/btl-exilite-ipl-madrid/' ) ); ?>" class="nvx-text-link">Explorar BTL EXILITE™ IPL</a>
			</article>
		</div>
	</section>

	<section class="nvx-hub-cta" aria-labelledby="nvx-hub-cta-title">
		<h2 id="nvx-hub-cta-title" class="nvx-hub-cta__title">Tu primera valoración clínica</h2>
		<p class="nvx-hub-cta__desc">La valoración no parte de una máquina concreta. Define qué componente predomina, qué alternativas existen, qué límites deben explicarse y cuál es el presupuesto documentado del plan propuesto.</p>
		<div class="nvx-hub-cta__actions">
			<a href="<?php echo esc_url( home_url( '/madrid/valoracion/' ) ); ?>" class="nvx-btn nvx-btn--primary">Iniciar mi valoración</a>
			<a href="<?php echo esc_url( home_url( '/soluciones-medicas/' ) ); ?>" class="nvx-btn nvx-btn--secondary-on-dark">Explorar soluciones médicas</a>
		</div>
	</section>
</div>
<?php
$content = ob_get_clean();

set_query_var( 'nvx_shell_content', $content );
set_query_var( 'nvx_shell_skip_header', true );
set_query_var( 'nvx_shell_no_wrapper', true );
get_template_part( 'template-parts/content/nvx-page-shell' );
