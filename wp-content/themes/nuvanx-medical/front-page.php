<?php
/**
 * Canonical front page template.
 *
 * Complete theme-owned markup: no block-content dependency and no nested main
 * landmark. Media URLs use the active WordPress content origin.
 *
 * @package nuvanx-medical
 */

defined( 'ABSPATH' ) || exit;

$hero_video_url  = content_url( '/uploads/2026/07/nvx-home-video-portada-hero-12s-720p.mp4' );
$hero_poster_url = content_url( '/uploads/2026/07/nvx-home-video-portada-poster.webp' );
$evidence_image  = content_url( '/uploads/2026/07/consulta-medica-personalizada-nuvanx-madrid.webp' );

ob_start();
?>
<div id="nvx-home-v3" class="nvx-home-v3">
	<section class="nvx-home-hero" aria-labelledby="nvx-home-hero-title">
		<video id="nvx-home-hero-video" class="nvx-home-hero__video nvx-home-hero-video" autoplay muted loop playsinline preload="metadata" poster="<?php echo esc_url( $hero_poster_url ); ?>" aria-label="Experiencia NUVANX Medicina Estética Láser en Madrid">
			<source src="<?php echo esc_url( $hero_video_url ); ?>" type="video/mp4">
		</video>
		<div class="nvx-home-hero__content">
			<h1 id="nvx-home-hero-title" class="nvx-home-hero__title">Medicina Estética. Madrid.</h1>
			<p class="nvx-home-hero__lead">Llevas tiempo mirándote esa zona en el espejo. Antes de proponerte nada, queremos entender qué es exactamente lo que te molesta.</p>
			<a href="<?php echo esc_url( home_url( '/madrid/valoracion/' ) ); ?>" class="nvx-btn nvx-btn--primary">Iniciar valoración</a>
		</div>
	</section>

	<section class="nvx-home-philosophy" aria-labelledby="nvx-home-philosophy-title">
		<div class="nvx-home-philosophy__inner">
			<p class="nvx-home-philosophy__title" id="nvx-home-philosophy-title">El primer paso es el diagnóstico.</p>
			<p class="nvx-home-philosophy__lead">Cada protocolo comienza con una valoración médica individualizada. Sin suposiciones. Sin listas estándar. Si no hay indicación clínica, no hay tratamiento.</p>
		</div>
	</section>

	<section class="nvx-home-standard" aria-labelledby="nvx-home-standard-title">
		<header class="nvx-home-standard__header">
			<h2 id="nvx-home-standard-title" class="nvx-home-standard__title">Intervención mínima. Planificación estructural.</h2>
			<p class="nvx-home-standard__subtitle">Protocolos médicos definidos según anatomía, calidad del tejido y objetivos clínicos realistas.</p>
		</header>
		<div class="nvx-home-standard__grid">
			<div class="nvx-home-feature">
				<span class="nvx-home-feature__number" aria-hidden="true">I</span>
				<h3 class="nvx-home-feature__title">Abordajes sin incisiones quirúrgicas amplias</h3>
				<p class="nvx-home-feature__desc">Determinadas indicaciones pueden abordarse mediante microcánulas o fibra óptica, siempre tras exploración médica.</p>
			</div>
			<div class="nvx-home-feature">
				<span class="nvx-home-feature__number" aria-hidden="true">II</span>
				<h3 class="nvx-home-feature__title">Recuperación según el procedimiento</h3>
				<p class="nvx-home-feature__desc">El tiempo de reincorporación depende del tratamiento, la zona, los parámetros utilizados y la respuesta individual.</p>
			</div>
			<div class="nvx-home-feature">
				<span class="nvx-home-feature__number" aria-hidden="true">III</span>
				<h3 class="nvx-home-feature__title">Anestesia adaptada a la indicación</h3>
				<p class="nvx-home-feature__desc">Cuando procede, los tratamientos se realizan con anestesia local y seguimiento médico personalizado.</p>
			</div>
			<div class="nvx-home-feature">
				<span class="nvx-home-feature__number" aria-hidden="true">IV</span>
				<h3 class="nvx-home-feature__title">Tratamiento combinado del contorno</h3>
				<p class="nvx-home-feature__desc">La reducción adiposa y la mejora de la firmeza pueden integrarse en un mismo plan cuando existe indicación.</p>
			</div>
			<div class="nvx-home-feature">
				<span class="nvx-home-feature__number" aria-hidden="true">V</span>
				<h3 class="nvx-home-feature__title">Evolución progresiva y seguimiento</h3>
				<p class="nvx-home-feature__desc">La evolución se revisa en consulta y varía según el tratamiento, el tejido y los hábitos de cada paciente.</p>
			</div>
		</div>
	</section>

	<section class="nvx-home-portfolio" aria-labelledby="nvx-home-portfolio-title">
		<header class="nvx-home-portfolio__header">
			<p id="nvx-home-portfolio-title" class="nvx-home-portfolio__title">Arquitectura anatómica</p>
		</header>
		<div class="nvx-home-portfolio__list">
			<article class="nvx-home-portfolio__item">
				<span class="nvx-home-portfolio__number" aria-hidden="true">01</span>
				<h3 class="nvx-home-portfolio__name">Endolift® Facial</h3>
				<p class="nvx-home-portfolio__desc">Retracción tisular y definición del contorno mandibular mediante láser subdérmico.</p>
			</article>
			<article class="nvx-home-portfolio__item">
				<span class="nvx-home-portfolio__number" aria-hidden="true">02</span>
				<h3 class="nvx-home-portfolio__name">Endoláser Corporal</h3>
				<p class="nvx-home-portfolio__desc">Lipólisis láser focalizada para depósitos adiposos y flacidez.</p>
			</article>
			<article class="nvx-home-portfolio__item">
				<span class="nvx-home-portfolio__number" aria-hidden="true">03</span>
				<h3 class="nvx-home-portfolio__name">Láser CO₂ Fraccionado</h3>
				<p class="nvx-home-portfolio__desc">Renovación fraccionada para abordar fotodaño, cicatrices y textura según parámetros médicos.</p>
			</article>
			<article class="nvx-home-portfolio__item">
				<span class="nvx-home-portfolio__number" aria-hidden="true">04</span>
				<h3 class="nvx-home-portfolio__name">Medicina Estética Facial</h3>
				<p class="nvx-home-portfolio__desc">Planificación conservadora que respeta la identidad y las proporciones naturales.</p>
			</article>
			<article class="nvx-home-portfolio__item">
				<span class="nvx-home-portfolio__number" aria-hidden="true">05</span>
				<h3 class="nvx-home-portfolio__name">EXION® y tecnologías BTL</h3>
				<p class="nvx-home-portfolio__desc">Protocolos para calidad cutánea, firmeza y tratamiento facial o corporal tras diagnóstico.</p>
			</article>
		</div>
		<div class="nvx-home-portfolio__action">
			<a href="<?php echo esc_url( home_url( '/tratamientos/' ) ); ?>" class="nvx-btn nvx-btn--secondary">Ver portafolio completo</a>
		</div>
	</section>

	<section class="nvx-home-evidence" aria-labelledby="nvx-home-evidence-title">
		<div class="nvx-home-evidence__grid">
			<div class="nvx-home-evidence__image-col">
				<img src="<?php echo esc_url( $evidence_image ); ?>" alt="Valoración médica personalizada en NUVANX Madrid" class="nvx-home-evidence__image" loading="lazy" decoding="async">
			</div>
			<div class="nvx-home-evidence__text-col">
				<h2 id="nvx-home-evidence-title" class="nvx-home-evidence__title">Evidencia clínica</h2>
				<p class="nvx-home-evidence__desc">Documentamos la evolución clínica con consentimiento y seguimiento médico, preservando la privacidad de cada paciente.</p>
				<a href="<?php echo esc_url( home_url( '/casos-de-pacientes/' ) ); ?>" class="nvx-btn nvx-btn--secondary-on-dark">Explorar casos clínicos</a>
			</div>
		</div>
	</section>

	<section class="nvx-home-team" aria-labelledby="nvx-home-team-title">
		<div class="nvx-home-team__inner">
			<div class="nvx-home-team__header">
				<h2 id="nvx-home-team-title" class="nvx-home-team__title">Dirección y criterio médico</h2>
				<a href="<?php echo esc_url( home_url( '/equipo-medico/' ) ); ?>" class="nvx-btn nvx-btn--secondary">Conocer al equipo médico</a>
			</div>
			<div class="nvx-home-team__content">
				<p class="nvx-home-team__desc">El equipo integra experiencia clínica, valoración individual y seguimiento para seleccionar la tecnología adecuada en cada caso.</p>
				<ul class="nvx-home-team__list">
					<li><strong>Dr. José Javier Rivera Tejeda</strong> <span>Dirección médica. Endolift® y láser CO₂.</span></li>
					<li><strong>Dra. Ivon Yamileth Rivera Deras</strong> <span>Medicina y well-aging.</span></li>
					<li><strong>Dr. Fabio Augusto Quiñónez Bareiro</strong> <span>Medicina e investigación en fisiología del envejecimiento.</span></li>
				</ul>
			</div>
		</div>
	</section>

	<section class="nvx-home-seo" aria-labelledby="nvx-home-seo-title">
		<div class="nvx-home-seo__inner">
			<p id="nvx-home-seo-title" class="nvx-home-seo__title">Áreas de valoración y protocolos médicos</p>
			<div class="nvx-home-seo__grid">
				<div class="nvx-home-seo__col">
					<h3 class="nvx-home-seo__col-title">Contorno Corporal</h3>
					<ul class="nvx-home-seo__list">
						<li><strong>Remodelación global:</strong> <a href="<?php echo esc_url( home_url( '/remodelacion-corporal-laser-madrid/' ) ); ?>">NUVANX Contour Sculpt™</a> para el tratamiento de grasa localizada y firmeza.</li>
						<li><strong>Recuperación posgestacional:</strong> <a href="<?php echo esc_url( home_url( '/tratamiento-postparto-abdomen-contorno-corporal-madrid/' ) ); ?>">Post-Maternity Contour™</a> para valorar abdomen, diástasis y flacidez.</li>
						<li><strong>Láser subdérmico:</strong> <a href="<?php echo esc_url( home_url( '/endolaser-lipolisis-corporal-madrid/' ) ); ?>">Endoláser corporal</a> (lipólisis y retracción térmica ambulatoria).</li>
					</ul>
				</div>
				<div class="nvx-home-seo__col">
					<h3 class="nvx-home-seo__col-title">Arquitectura Facial</h3>
					<ul class="nvx-home-seo__list">
						<li><strong>Tercio inferior:</strong> <a href="<?php echo esc_url( home_url( '/papada-definicion-mandibular-madrid/' ) ); ?>">Profile Definition™</a> para diagnóstico de papada, mandíbula y cuello.</li>
						<li><strong>Tensión y calidad cutánea:</strong> <a href="<?php echo esc_url( home_url( '/calidad-piel-firmeza-luminosidad-madrid/' ) ); ?>">Skin Architecture™</a> y <a href="<?php echo esc_url( home_url( '/endolift-facial-papada-mandibula/' ) ); ?>">Endolift® Facial</a>.</li>
						<li><strong>Región periocular:</strong> <a href="<?php echo esc_url( home_url( '/eye-frame-rejuvenecimiento-mirada-madrid/' ) ); ?>">Eye Frame™</a> para el tratamiento integral de la mirada y las ojeras.</li>
						<li><strong>Renovación de superficie:</strong> <a href="<?php echo esc_url( home_url( '/cicatrices-acne-poros-textura-madrid/' ) ); ?>">Surface Renewal™</a> (<a href="<?php echo esc_url( home_url( '/laser-co2-fraccionado-madrid-textura-cicatrices-poro/' ) ); ?>">Láser CO₂ fraccionado</a> para marcas de acné) y <a href="<?php echo esc_url( home_url( '/manchas-rojeces-fotorejuvenecimiento-ipl-madrid/' ) ); ?>">Tone Correction™</a>.</li>
					</ul>
				</div>
			</div>
		</div>
	</section>

	<section class="nvx-home-locations" aria-labelledby="nvx-home-locations-title">
		<h2 id="nvx-home-locations-title" class="nvx-home-locations__title">Madrid. Dos sedes. Un único criterio médico.</h2>
		<div class="nvx-home-locations__grid">
			<div class="nvx-home-location">
				<h3 class="nvx-home-location__name">Chamberí</h3>
				<p class="nvx-home-location__desc">Serenidad y discreción.</p>
				<span class="nvx-home-location__code">CS20144</span>
			</div>
			<div class="nvx-home-location">
				<h3 class="nvx-home-location__name">Salamanca–Goya</h3>
				<p class="nvx-home-location__desc">Accesibilidad y sofisticación.</p>
				<span class="nvx-home-location__code">CS20073</span>
			</div>
		</div>
	</section>

	<section class="nvx-home-closure" aria-labelledby="nvx-home-closure-title">
		<h2 id="nvx-home-closure-title" class="nvx-home-closure__title">Medicina estética con criterio clínico.</h2>
		<p class="nvx-home-closure__desc">Plan individualizado. Precisión médica. Seguimiento según tu caso.</p>
		<div class="nvx-home-closure__actions">
			<a href="<?php echo esc_url( home_url( '/madrid/valoracion/' ) ); ?>" class="nvx-btn nvx-btn--primary">Definir mi plan clínico</a>
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
