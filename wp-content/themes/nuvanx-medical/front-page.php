<?php
/**
 * Canonical editorial front page.
 *
 * Home is an editorial cover, not a media catalogue. The page intentionally
 * uses one editorial image and one medical-authority image after the video.
 * Protocol, solution and clinical-evolution media belong to their own routes.
 *
 * @package nuvanx-medical
 */

defined( 'ABSPATH' ) || exit;

$hero_video_url   = content_url( '/uploads/2026/07/nvx-home-video-portada-hero-12s-720p.mp4' );
$hero_poster_url  = content_url( '/uploads/2026/07/nvx-home-video-portada-poster.webp' );
$editorial_image  = content_url( '/uploads/2026/07/Endolift-Corporal-Portada.webp' );
$authority_image  = content_url( '/uploads/2026/07/proceso-medico-laser-nuvanx-madrid.webp' );

define( 'NVX_URL_REMODELACION', '/remodelacion-corporal-laser-madrid/' );

$protocols = array(
	array(
		'number' => '01',
		'label'  => 'NUVANX Profile Definition',
		'title'  => 'Tratamiento de papada y definición mandibular en Madrid.',
		'copy'   => 'Puede involucrar: Endolift® facial · EXION® Face · soporte inyectable si está indicado.',
		'url'    => '/papada-definicion-mandibular-madrid/',
	),
	array(
		'number' => '02',
		'label'  => 'NUVANX Post-Maternity Contour',
		'title'  => 'Contorno corporal posgestacional: diagnóstico y tratamiento por zonas.',
		'copy'   => 'Puede involucrar: Endoláser corporal · EXION® Body · CO₂ fraccionado · EXION® Fractional RF.',
		'url'    => '/tratamiento-postparto-abdomen-contorno-corporal-madrid/',
	),
	array(
		'number' => '03',
		'label'  => 'NUVANX Abdomen Architecture',
		'title'  => 'Tratamiento de grasa localizada en abdomen y flancos en Madrid.',
		'copy'   => 'Puede involucrar: Endoláser corporal · EXION® Body · tratamiento de superficie según indicación.',
		'url'    => '/grasa-localizada-abdomen-flancos-madrid/',
	),
	array(
		'number' => '04',
		'label'  => 'NUVANX Skin Architecture',
		'title'  => 'Tratamiento médico para calidad, firmeza y luminosidad de la piel.',
		'copy'   => 'Puede involucrar: EXION® Face · EMFUSION® · bioestimulación si existe indicación documentada.',
		'url'    => '/calidad-piel-firmeza-luminosidad-madrid/',
	),
);

$solutions = array(
	array(
		'kicker' => 'ROSTRO Y PAPADA',
		'title'  => 'Papada y definición mandibular',
		'copy'   => 'Grasa submentoniana, laxitud de tejido o pérdida de definición mandibular. El diagnóstico diferencia causa y proporción.',
		'url'    => '/papada-definicion-mandibular-madrid/',
	),
	array(
		'kicker' => 'CONTORNO ABDOMINAL',
		'title'  => 'Abdomen, flancos y contorno corporal',
		'copy'   => 'Grasa subcutánea localizada, laxitud o ambas. Distinción crítica: la grasa visceral y la diástasis no tienen indicación en medicina estética.',
		'url'    => '/grasa-localizada-abdomen-flancos-madrid/',
	),
	array(
		'kicker' => 'BRAZOS Y ESPALDA',
		'title'  => 'Brazos y zona del sujetador',
		'copy'   => 'Flacidez posterior del brazo, grasa localizada o pliegues en la zona del sujetador. El objetivo es la continuidad del contorno, no el brazo ideal.',
		'url'    => '/flacidez-grasa-localizada-brazos-madrid/',
	),
	array(
		'kicker' => 'PIERNAS Y GLÚTEOS',
		'title'  => 'Muslos internos y región subglútea',
		'copy'   => 'Laxitud, grasa localizada o pliegue subglúteo. Los límites de la corrección y la distinción con celulitis se explican en consulta.',
		'url'    => '/flacidez-muslos-internos-subgluteo-madrid/',
	),
	array(
		'kicker' => 'POSPARTO',
		'title'  => 'Cambios posgestacionales',
		'copy'   => 'Después del embarazo puede haber varios componentes distintos. Cada uno exige una valoración diferente antes de cualquier plan.',
		'url'    => '/tratamiento-postparto-abdomen-contorno-corporal-madrid/',
	),
	array(
		'kicker' => 'SALUD Y CALIDAD CUTÁNEA',
		'title'  => 'Calidad, firmeza y luminosidad de la piel',
		'copy'   => 'Pérdida de tono, textura irregular, poros dilatados o luminosidad disminuida. Pueden coexistir varios componentes con tecnologías distintas.',
		'url'    => '/calidad-piel-firmeza-luminosidad-madrid/',
	),
	array(
		'kicker' => 'TEXTURA Y MARCAS',
		'title'  => 'Cicatrices, poros y textura',
		'copy'   => 'Cicatrices atróficas o hipertróficas, poros dilatados, textura irregular. El fototipo y la profundidad determinan la tecnología indicada.',
		'url'    => '/cicatrices-acne-poros-textura-madrid/',
	),
	array(
		'kicker' => 'TONO Y PIGMENTACIÓN',
		'title'  => 'Manchas, rojeces y fotodaño',
		'copy'   => 'Léntigos, eritema, telangiectasias o pigmentación postinflamatoria. Algunas lesiones requieren valoración dermatológica previa.',
		'url'    => '/manchas-rojeces-fotorejuvenecimiento-ipl-madrid/',
	),
);

ob_start();
?>
<div id="nvx-home-v3" class="nvx-home-v4 nvx-home-v5">
	<section class="nvx-home-hero" aria-labelledby="nvx-home-hero-title">
		<div class="nvx-home-hero__media" aria-hidden="true">
			<video id="nvx-home-hero-video" class="nvx-home-hero__video nvx-home-hero-video" autoplay muted loop playsinline preload="metadata" poster="<?php echo esc_url( $hero_poster_url ); ?>">
				<source src="<?php echo esc_url( $hero_video_url ); ?>" type="video/mp4">
				<track kind="subtitles" src="/uploads/captions.vtt" srclang="es" label="Español">
				<track kind="descriptions" src="/uploads/descriptions.vtt" srclang="es" label="Audiodescripción">
			</video>
			<button id="nvx-home-hero-video-pause" class="nvx-home-hero__video-control" aria-label="Pause background video">Pause</button>
		</div>
		<div class="nvx-home-hero__copy">
			<p class="nvx-home-eyebrow">NUVANX · MEDICINA ESTÉTICA LÁSER</p>
			<h1 id="nvx-home-hero-title" class="nvx-home-hero__title">Medicina estética con criterio. Madrid.</h1>
			<p class="nvx-home-hero__lead">Antes de proponerte una tecnología, estudiamos qué componente explica realmente lo que quieres mejorar.</p>
			<div class="nvx-home-actions">
				<a href="<?php echo esc_url( home_url( '/madrid/valoracion/' ) ); ?>" class="nvx-btn nvx-btn--primary">Solicitar valoración médica</a>
				<a href="<?php echo esc_url( home_url( '/soluciones-medicas/' ) ); ?>" class="nvx-home-text-link">Explorar soluciones</a>
			</div>
			<p class="nvx-home-hero__note">Diagnóstico individual · Plan documentado · Seguimiento médico</p>
		</div>
	</section>

	<section class="nvx-home-manifesto" aria-labelledby="nvx-home-manifesto-title">
		<p class="nvx-home-eyebrow">EL PUNTO DE PARTIDA</p>
		<h2 id="nvx-home-manifesto-title">No tratamos una fotografía. Tratamos anatomía, tejido y contexto clínico.</h2>
		<p>Cada protocolo comienza con una valoración individual. Si no existe una indicación proporcionada, no se propone un procedimiento.</p>
	</section>

	<section class="nvx-home-feature" aria-labelledby="nvx-home-feature-title">
		<div class="nvx-home-feature__media">
			<img src="<?php echo esc_url( $editorial_image ); ?>" alt="Preparación corporal editorial NUVANX" loading="eager" decoding="async">
		</div>
		<div class="nvx-home-feature__copy">
			<p class="nvx-home-eyebrow">PREPARACIÓN CORPORAL</p>
			<h2 id="nvx-home-feature-title">La disciplina también puede ser delicada.</h2>
			<p>Un plan corporal no empieza por una máquina. Empieza por entender la anatomía, la calidad del tejido y qué cambio puede ser proporcionado sin perder naturalidad.</p>
			<a href="<?php echo esc_url( home_url( NVX_URL_REMODELACION ) ); ?>" class="nvx-home-text-link">Explorar remodelación corporal</a>
		</div>
	</section>

	<section class="nvx-home-protocols" aria-labelledby="nvx-home-protocols-title">
		<header class="nvx-home-section-header">
			<div>
				<p class="nvx-home-eyebrow">PROTOCOLOS MÉDICOS NUVANX</p>
				<h2 id="nvx-home-protocols-title">Planes con nombre, criterio y trazabilidad.</h2>
			</div>
			<p>Cada protocolo tiene un nombre que describe el objetivo, las zonas posibles y la lógica de selección. La tecnología exacta se decide en la valoración, no antes.</p>
		</header>
		<div class="nvx-home-protocols__list">
			<?php foreach ( $protocols as $protocol ) : ?>
				<article class="nvx-home-protocol">
					<p class="nvx-home-protocol__number"><?php echo esc_html( $protocol['number'] ); ?></p>
					<div class="nvx-home-protocol__body">
						<p class="nvx-home-protocol__label"><?php echo esc_html( $protocol['label'] ); ?></p>
						<h3><?php echo esc_html( $protocol['title'] ); ?></h3>
						<p><?php echo esc_html( $protocol['copy'] ); ?></p>
					</div>
					<a href="<?php echo esc_url( home_url( $protocol['url'] ) ); ?>" class="nvx-home-protocol__link" aria-label="Conocer <?php echo esc_attr( $protocol['label'] ); ?>">→</a>
				</article>
			<?php endforeach; ?>
		</div>
	</section>

	<section class="nvx-home-solutions" aria-labelledby="nvx-home-solutions-title">
  		<header class="nvx-home-section-header">
  			<div>
  				<p class="nvx-home-eyebrow">SOLUCIONES MÉDICAS POR ZONA</p>
  				<h2 id="nvx-home-solutions-title">Tu preocupación, no el catálogo de máquinas.</h2>
  			</div>
  			<p>Una misma preocupación puede tener causas distintas. Antes de proponer una tecnología, evaluamos qué está ocurriendo realmente en tu caso.</p>
  		</header>
		<div class="nvx-home-solutions__grid">
			<?php foreach ( $solutions as $solution ) : ?>
				<article class="nvx-home-solution-card">
					<p class="nvx-home-solution-card__label"><?php echo esc_html( $solution['kicker'] ); ?></p>
					<h3><?php echo esc_html( $solution['title'] ); ?></h3>
					<p><?php echo esc_html( $solution['copy'] ); ?></p>
					<a href="<?php echo esc_url( home_url( $solution['url'] ) ); ?>">Explorar solución <span aria-hidden="true">→</span></a>
				</article>
			<?php endforeach; ?>
		</div>
	</section>

	<section class="nvx-home-cases" aria-labelledby="nvx-home-cases-title">
		<div>
			<p class="nvx-home-eyebrow">DOCUMENTACIÓN CLÍNICA</p>
			<h2 id="nvx-home-cases-title">La evolución necesita contexto, no una promesa.</h2>
		</div>
		<div class="nvx-home-cases__copy">
			<p>Un resultado estético solo es válido si se comprende el punto de partida. Documentamos nuestros casos con el mismo rigor que la propia intervención: sin filtros, en la misma postura y bajo idéntica iluminación.</p>
			<a href="<?php echo esc_url( home_url( '/casos-de-pacientes/' ) ); ?>" class="nvx-btn nvx-btn--secondary-on-dark">Explorar casos clínicos</a>
		</div>
	</section>

	<section class="nvx-home-authority" aria-labelledby="nvx-home-authority-title">
		<div class="nvx-home-authority__media">
			<img src="<?php echo esc_url( $authority_image ); ?>" alt="Proceso médico láser en NUVANX Madrid" loading="lazy" decoding="async">
		</div>
		<div class="nvx-home-authority__copy">
			<p class="nvx-home-eyebrow">DIRECCIÓN MÉDICA</p>
			<h2 id="nvx-home-authority-title">Continuidad desde la valoración hasta el seguimiento.</h2>
			<p>El plan identifica quién valora, quién realiza el procedimiento y cómo se revisa la evolución. La tecnología se selecciona después del diagnóstico.</p>
			<a href="<?php echo esc_url( home_url( '/equipo-medico/' ) ); ?>" class="nvx-home-text-link">Conocer al equipo médico</a>
		</div>
	</section>

	<section class="nvx-home-locations" aria-labelledby="nvx-home-locations-title">
		<header class="nvx-home-section-header">
			<div>
				<p class="nvx-home-eyebrow">CLÍNICAS NUVANX</p>
				<h2 id="nvx-home-locations-title">Madrid. Dos sedes. Un mismo criterio médico.</h2>
			</div>
			<p>Atención privada en Chamberí y Salamanca–Goya, con trazabilidad clínica y una experiencia discreta.</p>
		</header>
		<div class="nvx-home-locations__grid">
			<article class="nvx-home-location">
				<p class="nvx-home-location__code">CS20144</p>
				<h3>Chamberí</h3>
				<p>Serenidad, privacidad y continuidad asistencial.</p>
			</article>
			<article class="nvx-home-location">
				<p class="nvx-home-location__code">CS20073</p>
				<h3>Salamanca–Goya</h3>
				<p>Accesibilidad, precisión y el mismo estándar médico.</p>
			</article>
		</div>
	</section>

	<section class="nvx-home-closure" aria-labelledby="nvx-home-closure-title">
		<p class="nvx-home-eyebrow">TU PRIMERA DECISIÓN</p>
		<h2 id="nvx-home-closure-title">Entender qué necesitas antes de decidir qué hacer.</h2>
		<p class="nvx-home-closure__desc">La valoración permite confirmar si existe indicación, explicar alternativas y definir un presupuesto individualizado.</p>
		<div class="nvx-home-closure__actions">
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
