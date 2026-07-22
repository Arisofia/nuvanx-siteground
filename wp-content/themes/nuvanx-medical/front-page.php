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
			<p class="nvx-home-hero__lead">Protocolos de precisión para resultados naturales, según valoración médica.</p>
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
				<h3 class="nvx-home-feature__title">Sin bisturí, sin ingresos.</h3>
				<p class="nvx-home-feature__desc">Cuando tiene sentido para tu caso, trabajamos con microcánula o fibra óptica — nunca sin haberte explorado antes.</p>
			</div>
			<div class="nvx-home-feature">
				<span class="nvx-home-feature__number" aria-hidden="true">II</span>
				<h3 class="nvx-home-feature__title">Recuperación real, no de folleto.</h3>
				<p class="nvx-home-feature__desc">Te decimos cuántos días vas a perder de tu rutina, por tratamiento y por persona — no una cifra genérica que luego no se cumple.</p>
			</div>
			<div class="nvx-home-feature">
				<span class="nvx-home-feature__number" aria-hidden="true">III</span>
				<h3 class="nvx-home-feature__title">Anestesia local cuando toca, nunca de más.</h3>
			</div>
			<div class="nvx-home-feature">
				<span class="nvx-home-feature__number" aria-hidden="true">IV</span>
				<h3 class="nvx-home-feature__title">Grasa y flacidez, en la misma cita</h3>
				<p class="nvx-home-feature__desc">Cuando el diagnóstico lo permite.</p>
			</div>
			<div class="nvx-home-feature">
				<span class="nvx-home-feature__number" aria-hidden="true">V</span>
				<h3 class="nvx-home-feature__title">Seguimiento con nombre y apellido.</h3>
				<p class="nvx-home-feature__desc">Revisamos cómo evolucionas tú, no un ticket de atención al cliente.</p>
			</div>
		</div>
	</section>

	<section class="nvx-home-portfolio" aria-labelledby="nvx-home-portfolio-title">
		<header class="nvx-home-portfolio__header">
			<p id="nvx-home-portfolio-title" class="nvx-home-portfolio__title">Un protocolo para cada cosa — no un catálogo para todos</p>
		</header>
		<div class="nvx-home-portfolio__list">
			<article class="nvx-home-portfolio__item">
				<span class="nvx-home-portfolio__number" aria-hidden="true">01</span>
				<h3 class="nvx-home-portfolio__name">Endolift® Facial</h3>
				<p class="nvx-home-portfolio__desc">Para la papada y la línea de la mandíbula, con una fibra láser finísima bajo la piel — nada de cirugía.</p>
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
				<p class="nvx-home-evidence__desc">Cada caso que ves aquí es real, con el permiso de la paciente, y sin enseñar nada que ella no quisiera compartir. No usamos fotos de catálogo del fabricante del láser — usamos las tuyas, si algún día quieres formar parte de esto.</p>
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
				<p class="nvx-home-team__desc">El médico que te explora es el mismo que te trata y el mismo que te hace el seguimiento después. No hay traspasos ni rotación de personal a mitad de tu plan.</p>
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
		<h2 id="nvx-home-closure-title" class="nvx-home-closure__title">Aquí no vas a salir con más de lo que necesitas.</h2>
		<p class="nvx-home-closure__desc">Un plan pensado para ti, hecho por quien te va a seguir viendo después — no una promesa de folleto.</p>
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
