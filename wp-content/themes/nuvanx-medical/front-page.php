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

$hero_video_url  = content_url( '/uploads/2026/07/nvx-home-video-portada-hero-12s-720p.mp4' );
$hero_poster_url = content_url( '/uploads/2026/07/nvx-home-video-portada-poster.webp' );
$editorial_image = content_url( '/uploads/2026/07/Endolift-Corporal-Portada.webp' );
$authority_image = content_url( '/uploads/2026/07/proceso-medico-laser-nuvanx-madrid.webp' );

define( 'NVX_URL_REMODELACION', '/remodelacion-corporal-laser-madrid/' );

$protocols = array(
	array(
		'number' => '01',
		'label'  => 'Profile Definition™',
		'title'  => 'Perfil y línea mandibular',
		'copy'   => 'Analizamos el rostro de forma global: soporte óseo, distribución del volumen, laxitud y calidad de la piel. Solo después definimos qué técnica puede mejorar el contorno sin alterar tu expresión.',
		'url'    => '/papada-definicion-mandibular-madrid/',
	),
	array(
		'number' => '02',
		'label'  => 'Skin Architecture™',
		'title'  => 'Firmeza y densidad de la piel',
		'copy'   => 'Trabajamos sobre la estructura interna del tejido para mejorar su firmeza, elasticidad y calidad, evitando resultados rígidos, excesivos o artificiales.',
		'url'    => '/calidad-piel-firmeza-luminosidad-madrid/',
	),
	array(
		'number' => '03',
		'label'  => 'Surface Renewal™',
		'title'  => 'Textura, poros, manchas y marcas',
		'copy'   => 'Adaptamos cada tratamiento al fototipo, la sensibilidad y las necesidades reales de tu piel para renovar su superficie de forma progresiva y controlada.',
		'url'    => '/cicatrices-acne-poros-textura-madrid/',
	),
	array(
		'number' => '04',
		'label'  => 'Contour Architecture™',
		'title'  => 'Silueta por unidades anatómicas',
		'copy'   => 'No tratamos zonas de manera aislada. Estudiamos cómo se relacionan abdomen, flancos, espalda, brazos o muslos para mejorar la continuidad y la proporción del contorno corporal.',
		'url'    => '/remodelacion-corporal-laser-madrid/',
	),
);

$solutions = array(
	array(
		'kicker' => 'ROSTRO Y CUELLO',
		'title'  => 'Definición sin perder tu expresión',
		'copy'   => 'Valoramos definición, firmeza y soporte estructural preservando la identidad y la expresión natural de tu rostro.',
		'url'    => '/papada-definicion-mandibular-madrid/',
	),
	array(
		'kicker' => 'PIEL Y TEXTURA',
		'title'  => 'Tratar la causa, no solo lo visible',
		'copy'   => 'Manchas, rojeces, poros, marcas y pérdida de luminosidad se abordan según el origen y las características de cada alteración.',
		'url'    => '/calidad-piel-firmeza-luminosidad-madrid/',
	),
	array(
		'kicker' => 'CONTORNO CORPORAL',
		'title'  => 'Proporción, firmeza y continuidad',
		'copy'   => 'Estudiamos grasa localizada, laxitud y proporción dentro del conjunto de la silueta, no como áreas independientes.',
		'url'    => '/remodelacion-corporal-laser-madrid/',
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
			<p class="nvx-home-eyebrow">NUVANX · MEDICINA ESTÉTICA LÁSER · MADRID</p>
			<h1 id="nvx-home-hero-title" class="nvx-home-hero__title">Medicina estética con criterio médico y resultados naturales.</h1>
			<p class="nvx-home-hero__lead">En NUVANX unimos criterio médico, tecnología avanzada y una forma más serena de entender la medicina estética. Estudiamos tu anatomía, la calidad de tu piel y la respuesta de tus tejidos para diseñar un plan personalizado, proporcionado y orientado a resultados naturales.</p>
			<div class="nvx-home-actions">
				<a href="<?php echo esc_url( home_url( '/madrid/valoracion/' ) ); ?>" class="nvx-btn nvx-btn--primary">Solicitar valoración médica</a>
				<a href="<?php echo esc_url( home_url( '/soluciones-medicas/' ) ); ?>" class="nvx-home-text-link">Explorar soluciones</a>
			</div>
			<p class="nvx-home-hero__note">Diagnóstico individualizado · Plan clínico documentado · Seguimiento médico</p>
		</div>
	</section>

	<section class="nvx-home-manifesto" aria-labelledby="nvx-home-manifesto-title">
		<p class="nvx-home-eyebrow">TODO EMPIEZA POR COMPRENDER TU PIEL</p>
		<h2 id="nvx-home-manifesto-title">Antes de recomendar un tratamiento, necesitamos entender qué está ocurriendo y por qué.</h2>
		<p>Valoramos la calidad cutánea, la estructura facial o corporal, el grado de laxitud, la distribución del tejido y cualquier antecedente relevante. A partir de ese análisis decidimos qué puede aportar un beneficio real y qué no está indicado. Porque una buena medicina estética no consiste en hacer más, sino en elegir mejor.</p>
	</section>

	<section class="nvx-home-feature" aria-labelledby="nvx-home-feature-title">
		<div class="nvx-home-feature__media">
			<img src="<?php echo esc_url( $editorial_image ); ?>" alt="Preparación corporal editorial NUVANX" loading="eager" decoding="async">
		</div>
		<div class="nvx-home-feature__copy">
			<p class="nvx-home-eyebrow">REMODELACIÓN CORPORAL CON CRITERIO MÉDICO</p>
			<h2 id="nvx-home-feature-title">Cada cuerpo responde de una forma diferente.</h2>
			<p>Por eso, un tratamiento corporal no debería comenzar por una máquina, sino por una valoración anatómica completa. Estudiamos la grasa localizada, la firmeza de la piel y la continuidad entre las distintas zonas de la silueta para proponer cambios equilibrados y coherentes con tu cuerpo.</p>
			<a href="<?php echo esc_url( home_url( NVX_URL_REMODELACION ) ); ?>" class="nvx-home-text-link">Descubrir soluciones corporales</a>
		</div>
	</section>

	<section class="nvx-home-protocols" aria-labelledby="nvx-home-protocols-title">
		<header class="nvx-home-section-header">
			<div>
				<p class="nvx-home-eyebrow">PROTOCOLOS SIGNATURE</p>
				<h2 id="nvx-home-protocols-title">Una forma de organizar el diagnóstico y el tratamiento con mayor precisión.</h2>
			</div>
			<p>Sin fórmulas generales ni combinaciones innecesarias. Cada protocolo ordena la valoración, la indicación y la secuencia de tratamiento según lo que necesita cada persona.</p>
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
				<p class="nvx-home-eyebrow">SOLUCIONES MÉDICAS AVANZADAS</p>
				<h2 id="nvx-home-solutions-title">La zona orienta la consulta. El diagnóstico define el tratamiento.</h2>
			</div>
			<p>No proponemos procedimientos innecesarios ni resultados que no podamos abordar de manera razonable y responsable.</p>
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
			<p class="nvx-home-eyebrow">RESULTADOS REALES, DOCUMENTADOS CON RIGOR</p>
			<h2 id="nvx-home-cases-title">Una evolución clínica debe poder observarse con honestidad.</h2>
		</div>
		<div class="nvx-home-cases__copy">
			<p>Documentamos el proceso manteniendo, siempre que es posible, condiciones comparables de iluminación, postura, encuadre y distancia. Sin filtros, sin distorsiones y sin presentar como resultado lo que no corresponde al cambio real del tejido.</p>
			<a href="<?php echo esc_url( home_url( '/casos-de-pacientes/' ) ); ?>" class="nvx-btn nvx-btn--secondary-on-dark">Ver casos clínicos</a>
		</div>
	</section>

	<section class="nvx-home-authority" aria-labelledby="nvx-home-authority-title">
		<div class="nvx-home-authority__media">
			<img src="<?php echo esc_url( $authority_image ); ?>" alt="Proceso médico láser en NUVANX Madrid" loading="lazy" decoding="async">
		</div>
		<div class="nvx-home-authority__copy">
			<p class="nvx-home-eyebrow">CONTINUIDAD ASISTENCIAL</p>
			<h2 id="nvx-home-authority-title">Tu tratamiento sigue una misma línea médica.</h2>
			<p>Desde la primera valoración hasta las revisiones posteriores, el equipo conoce tu diagnóstico, la indicación realizada, los parámetros utilizados y la evolución observada. Aquí no eres una sesión aislada, sino un proceso clínico que merece seguimiento, criterio y tiempo.</p>
			<a href="<?php echo esc_url( home_url( '/equipo-medico/' ) ); ?>" class="nvx-home-text-link">Conocer al equipo médico</a>
		</div>
	</section>

	<section class="nvx-home-locations" aria-labelledby="nvx-home-locations-title">
		<header class="nvx-home-section-header">
			<div>
				<p class="nvx-home-eyebrow">CLÍNICAS NUVANX EN MADRID</p>
				<h2 id="nvx-home-locations-title">Dos espacios privados. Un mismo criterio médico.</h2>
			</div>
			<p>Entornos diseñados para ofrecer una atención médica tranquila, discreta y personalizada.</p>
		</header>
		<div class="nvx-home-locations__grid">
			<article class="nvx-home-location">
				<p class="nvx-home-location__code">CS20144</p>
				<h3>Chamberí</h3>
				<p>Un entorno orientado a la calma, la privacidad y la continuidad asistencial.</p>
			</article>
			<article class="nvx-home-location">
				<p class="nvx-home-location__code">CS20073</p>
				<h3>Salamanca–Goya</h3>
				<p>Accesibilidad, precisión técnica y el mismo estándar médico de NUVANX.</p>
			</article>
		</div>
	</section>

	<section class="nvx-home-closure" aria-labelledby="nvx-home-closure-title">
		<p class="nvx-home-eyebrow">TU PRIMERA VALORACIÓN</p>
		<h2 id="nvx-home-closure-title">Entender qué necesitas antes de decidir qué hacer.</h2>
		<p class="nvx-home-closure__desc">Durante la consulta recibirás un análisis personalizado de la zona que deseas tratar, una evaluación de la anatomía y la calidad del tejido, una explicación clara de las opciones que pueden estar indicadas y un presupuesto individualizado según el plan médico propuesto.</p>
		<p class="nvx-home-closure__desc">También puede ocurrir que la mejor decisión sea no tratar, esperar o plantear una alternativa diferente. Esa recomendación forma parte de nuestro compromiso médico.</p>
		<div class="nvx-home-closure__actions">
			<a href="<?php echo esc_url( home_url( '/madrid/valoracion/' ) ); ?>" class="nvx-btn nvx-btn--primary">Agendar valoración médica</a>
			<a href="<?php echo esc_url( nvx_cta_whatsapp_url() ); ?>" class="nvx-btn nvx-btn--secondary-on-dark" target="_blank" rel="noopener noreferrer">Hablar por WhatsApp</a>
		</div>
	</section>
</div>
<?php
$content = ob_get_clean();

set_query_var( 'nvx_shell_content', $content );
set_query_var( 'nvx_shell_skip_header', true );
set_query_var( 'nvx_shell_no_wrapper', true );
get_template_part( 'template-parts/content/nvx-page-shell' );
