<?php
/**
 * Front page V3 - Quiet Luxury
 * Hardcoded structure replacing legacy DOMDocument parsing.
 *
 * @package NUVANX_Medical
 */
defined( 'ABSPATH' ) || exit;

ob_start();
?>
<main id="nvx-home-v3" class="nvx-home-v3" role="main" aria-label="<?php echo esc_attr( wp_strip_all_tags( get_the_title() ) ); ?>">

	<!-- SECCIÓN 01: HERO -->
	<section class="nvx-home-hero" aria-labelledby="nvx-home-hero-title">
		<!-- [!] Placeholder: Replace with actual video URL when available -->
		<div class="nvx-home-hero__content">
			<h1 id="nvx-home-hero-title" class="nvx-home-hero__title">Medicina Estética. Madrid.</h1>
			<p class="nvx-home-hero__lead">Protocolos de precisión para resultados que no necesitan filtro.</p>
			<a href="<?php echo esc_url( home_url( '/madrid/valoracion/' ) ); ?>" class="nvx-btn nvx-btn--primary">Iniciar Valoración</a>
		</div>
	</section>

	<!-- SECCIÓN 02: LA FILOSOFÍA -->
	<section class="nvx-home-philosophy" aria-labelledby="nvx-home-philosophy-title">
		<div class="nvx-home-philosophy__inner">
			<p class="nvx-home-philosophy__title" id="nvx-home-philosophy-title">El primer paso es el diagnóstico.</p>
			<p class="nvx-home-philosophy__lead">Cada protocolo comienza con una valoración médica individualizada. Sin suposiciones. Sin listas estándar. Si no hay indicación clínica, no hay tratamiento.</p>
		</div>
	</section>

	<!-- SECCIÓN 03: EL ESTÁNDAR CLÍNICO -->
	<section class="nvx-home-standard" aria-labelledby="nvx-home-standard-title">
		<header class="nvx-home-standard__header">
			<h2 id="nvx-home-standard-title" class="nvx-home-standard__title">Intervención mínima. Impacto estructural.</h2>
			<p class="nvx-home-standard__subtitle">Protocolos médicos diseñados para la máxima eficiencia biológica.</p>
		</header>
		<div class="nvx-home-standard__grid">
			<div class="nvx-home-feature">
				<span class="nvx-home-feature__number" aria-hidden="true">I</span>
				<h3 class="nvx-home-feature__title">Reducción sin incisiones quirúrgicas</h3>
				<p class="nvx-home-feature__desc">Abordaje subdérmico mediante micro-cánulas y fibra óptica. Sin bisturí. Sin suturas.</p>
			</div>
			<div class="nvx-home-feature">
				<span class="nvx-home-feature__number" aria-hidden="true">II</span>
				<h3 class="nvx-home-feature__title">Recuperación acelerada</h3>
				<p class="nvx-home-feature__desc">Procedimientos diseñados para una reincorporación en 24-48 horas. Menor trauma tisular, menor inflamación.</p>
			</div>
			<div class="nvx-home-feature">
				<span class="nvx-home-feature__number" aria-hidden="true">III</span>
				<h3 class="nvx-home-feature__title">Anestesia local</h3>
				<p class="nvx-home-feature__desc">Procedimientos ambulatorios con el paciente consciente. Control absoluto, mayor seguridad y confort clínico.</p>
			</div>
			<div class="nvx-home-feature">
				<span class="nvx-home-feature__number" aria-hidden="true">IV</span>
				<h3 class="nvx-home-feature__title">Retracción y lipólisis simultánea</h3>
				<p class="nvx-home-feature__desc">Eliminación de depósitos adiposos y tensado térmico de la piel en un único tiempo médico.</p>
			</div>
			<div class="nvx-home-feature">
				<span class="nvx-home-feature__number" aria-hidden="true">V</span>
				<h3 class="nvx-home-feature__title">Resultados definitivos</h3>
				<p class="nvx-home-feature__desc">Extracción selectiva de células adiposas. El tejido eliminado no se regenera.</p>
			</div>
		</div>
	</section>

	<!-- SECCIÓN 04: PORTAFOLIO DE PROCEDIMIENTOS -->
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
				<p class="nvx-home-portfolio__desc">Renovación celular profunda. Corrección paramétrica de fotodaño y textura.</p>
			</article>
			<article class="nvx-home-portfolio__item">
				<span class="nvx-home-portfolio__number" aria-hidden="true">04</span>
				<h3 class="nvx-home-portfolio__name">Armonización Facial</h3>
				<p class="nvx-home-portfolio__desc">Restauración volumétrica conservadora que respeta la identidad y proporciones naturales.</p>
			</article>
			<article class="nvx-home-portfolio__item">
				<span class="nvx-home-portfolio__number" aria-hidden="true">05</span>
				<h3 class="nvx-home-portfolio__name">Alta Tecnología (EXION® / EMFUSION)</h3>
				<p class="nvx-home-portfolio__desc">Regeneración dérmica avanzada y modulación tisular.</p>
			</article>
		</div>
		<div class="nvx-home-portfolio__action">
			<a href="<?php echo esc_url( home_url( '/tratamientos/' ) ); ?>" class="nvx-btn nvx-btn--secondary">Ver portafolio completo</a>
		</div>
	</section>

	<!-- SECCIÓN 05: DERIVACIÓN A CASOS (EVIDENCIA CLÍNICA) -->
	<section class="nvx-home-evidence" aria-labelledby="nvx-home-evidence-title">
		<div class="nvx-home-evidence__grid">
			<div class="nvx-home-evidence__image-col">
				<img src="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/images/home-evidence.webp' ); ?>" alt="Evidencia Clínica NUVANX" class="nvx-home-evidence__image" loading="lazy">
			</div>
			<div class="nvx-home-evidence__text-col">
				<h2 id="nvx-home-evidence-title" class="nvx-home-evidence__title">Evidencia Clínica</h2>
				<p class="nvx-home-evidence__desc">El resultado de la precisión médica aplicada. Mantenemos la privacidad de la consulta médica, pero documentamos la evolución real de la retracción tisular, la armonización y la lipólisis en nuestro archivo de casos.</p>
				<div>
					<a href="<?php echo esc_url( home_url( '/casos/' ) ); ?>" class="nvx-btn nvx-btn--secondary" style="border-color: var(--nvx-light); color: var(--nvx-light);">Explorar Archivo Clínico</a>
				</div>
			</div>
		</div>
	</section>

	<!-- SECCIÓN 06: DIRECCIÓN MÉDICA -->
	<section class="nvx-home-team" aria-labelledby="nvx-home-team-title">
		<div class="nvx-home-team__inner">
			<div class="nvx-home-team__header">
				<h2 id="nvx-home-team-title" class="nvx-home-team__title">Excelencia de origen hospitalario</h2>
				<a href="<?php echo esc_url( home_url( '/clinicas/equipo-medico/' ) ); ?>" class="nvx-btn nvx-btn--secondary">Conocer al equipo y filosofía</a>
			</div>
			<div class="nvx-home-team__content">
				<p class="nvx-home-team__desc">El rigor de la medicina interna aplicado a la estética y la longevidad. Nuestro equipo traslada la investigación y la práctica clínica de alto nivel al cuidado integral del tejido.</p>
				<ul class="nvx-home-team__list">
					<li><strong>Dr. José Javier Rivera Tejeda</strong> <span>Director Médico. Endolift® y Láser CO₂.</span></li>
					<li><strong>Dra. Ivon Yamileth Rivera Deras</strong> <span>Médico Especialista (Hospital La Paz). Well-aging.</span></li>
					<li><strong>Dr. Fabio Augusto Quiñónez Bareiro</strong> <span>Doctor (UAM) e Investigador. Fisiología del envejecimiento.</span></li>
				</ul>
			</div>
		</div>
	</section>

	<!-- SECCIÓN 07: ÍNDICE GEO/SEO -->
	<section class="nvx-home-seo" aria-labelledby="nvx-home-seo-title">
		<div class="nvx-home-seo__inner">
			<p id="nvx-home-seo-title" class="nvx-home-seo__title">Áreas de intervención clínica y divulgación</p>
			<div class="nvx-home-seo__grid">
				<div class="nvx-home-seo__col">
					<h3 class="nvx-home-seo__col-title">Corporal</h3>
					<ul class="nvx-home-seo__list">
						<li><strong>Adiposidad infraumbilical (FUPA):</strong> Abordaje médico sin cirugía mayor.</li>
						<li><strong>Depresiones trocantéreas (Hip Dips):</strong> Transición natural del contorno.</li>
						<li><strong>Lipodistrofia en extremidades:</strong> Tratamiento de tobillos, rodillas y muslos.</li>
						<li><strong>Tensado abdominal:</strong> Comparativa entre Endoláser y EXION®.</li>
					</ul>
				</div>
				<div class="nvx-home-seo__col">
					<h3 class="nvx-home-seo__col-title">Facial</h3>
					<ul class="nvx-home-seo__list">
						<li><strong>Tercio inferior:</strong> Redefinición mandibular y reducción de papada (Endolift®).</li>
						<li><strong>Longevidad vs. Relleno:</strong> Diferencia clínica en la restauración facial.</li>
						<li><strong>Calidad de piel:</strong> Regeneración celular y eliminación de fotodaño.</li>
					</ul>
				</div>
			</div>
		</div>
	</section>

	<!-- SECCIÓN 08: SEDES -->
	<section class="nvx-home-locations" aria-labelledby="nvx-home-locations-title">
		<h2 id="nvx-home-locations-title" class="nvx-home-locations__title">Madrid. Dos sedes. Un único criterio médico.</h2>
		<div class="nvx-home-locations__grid">
			<div class="nvx-home-location">
				<h3 class="nvx-home-location__name">Chamberí</h3>
				<p class="nvx-home-location__desc">Serenidad y discreción.</p>
				<span class="nvx-home-location__code">(CS20144)</span>
			</div>
			<div class="nvx-home-location">
				<h3 class="nvx-home-location__name">Salamanca–Goya</h3>
				<p class="nvx-home-location__desc">Accesibilidad y sofisticación.</p>
				<span class="nvx-home-location__code">(CS20073)</span>
			</div>
		</div>
	</section>

	<!-- SECCIÓN 09: CIERRE -->
	<section class="nvx-home-closure">
		<h2 class="nvx-home-closure__title">Medicina estética con criterio clínico.</h2>
		<p class="nvx-home-closure__desc">Plan individualizado. Precisión médica. Resultados medibles.</p>
		<div class="nvx-home-closure__actions">
			<a href="<?php echo esc_url( home_url( '/madrid/valoracion/' ) ); ?>" class="nvx-btn nvx-btn--primary">Definir mi plan clínico</a>
			<a href="https://wa.me/34669319836" class="nvx-btn nvx-btn--secondary" style="border-color: var(--nvx-light); color: var(--nvx-light);">Contactar por WhatsApp</a>
		</div>
	</section>

</main><!-- #nvx-home-v3 -->
<?php
$content = ob_get_clean();

set_query_var( 'nvx_shell_content', $content );
set_query_var( 'nvx_shell_skip_header', true );
set_query_var( 'nvx_shell_no_wrapper', true );
get_template_part( 'template-parts/content/nvx-page-shell' );
