<?php
/**
 * Canonical editorial front page.
 *
 * The hero separates moving media from copy. All subsequent modules use the
 * WordPress media library and a restrained editorial system owned by the theme.
 *
 * @package nuvanx-medical
 */

defined( 'ABSPATH' ) || exit;

$hero_video_url  = content_url( '/uploads/2026/07/nvx-home-video-portada-hero-12s-720p.mp4' );
$hero_poster_url = content_url( '/uploads/2026/07/nvx-home-video-portada-poster.webp' );
$img_papada = '/uploads/2026/07/Endolift-Papada.webp';

/** Helper for editorial stories. */
function nvx_story( string $img, string $alt, string $kick, string $title, string $desc, string $url ): array {
	return array( 'image' => content_url( $img ), 'alt' => $alt, 'kicker' => $kick, 'title' => $title, 'description' => $desc, 'url' => home_url( $url ) );
}
/** Helper for solution cards. */
function nvx_sol( string $img, string $alt, string $lbl, string $title, string $copy, string $url ): array {
	return array( 'image' => content_url( $img ), 'alt' => $alt, 'label' => $lbl, 'title' => $title, 'copy' => $copy, 'url' => home_url( $url ) );
}


$editorial_stories = array(
	nvx_story( '/uploads/2026/07/Endolift-Corporal-Portada.webp' , 'Detalle editorial de preparación corporal en NUVANX', 'PREPARACIÓN CORPORAL', 'La disciplina también puede ser delicada.', 'Planificación por zonas, anatomía y calidad del tejido. Sin fórmulas estándar.', '/remodelacion-corporal-laser-madrid/'  ),
	nvx_story( '/uploads/2026/07/Exion-IPL.webp' , 'Retrato editorial para el protocolo Tone Correction', 'TONE CORRECTION™', 'Un tono más uniforme, sin borrar tu piel real.', 'Manchas, rojeces y fotodaño se valoran según diagnóstico y fototipo.', '/manchas-rojeces-fotorejuvenecimiento-ipl-madrid/'  ),
	nvx_story( '/uploads/2026/07/laser-co2-fraccionado-madrid-textura-cicatrices-poro.webp' , 'Retrato editorial para el protocolo Surface Renewal', 'SURFACE RENEWAL™', 'Renovar la superficie sin uniformar tu identidad.', 'Textura, poros y cicatrices requieren parámetros definidos para cada piel.', '/cicatrices-acne-poros-textura-madrid/'  ),
	nvx_story( $img_papada , 'Perfil facial editorial para el protocolo Profile Definition', 'PROFILE DEFINITION™', 'Definición que se percibe. Intervención que no se anuncia.', 'Papada, mandíbula y cuello se estudian como una misma arquitectura anatómica.', '/papada-definicion-mandibular-madrid/'  ),
);

$solution_cards = array(
	nvx_sol( $img_papada , 'Valoración de papada, mandíbula y cuello', 'Rostro y cuello', 'Papada y definición mandibular', 'Se diferencia grasa localizada, laxitud y soporte anatómico antes de indicar una técnica.', '/papada-definicion-mandibular-madrid/'  ),
	nvx_sol( '/uploads/2026/07/Exion-IPL.webp' , 'Valoración de calidad y firmeza de la piel', 'Calidad cutánea', 'Firmeza, densidad y luminosidad', 'La modalidad depende del fototipo, la profundidad y el tiempo de recuperación disponible.', '/calidad-piel-firmeza-luminosidad-madrid/'  ),
	nvx_sol( '/uploads/2026/07/Endolift-Abdomen.webp' , 'Valoración corporal de abdomen y flancos', 'Contorno corporal', 'Abdomen y flancos', 'Grasa subcutánea, laxitud y continuidad del contorno se valoran por separado.', '/grasa-localizada-abdomen-flancos-madrid/'  ),
	nvx_sol( '/uploads/2026/07/Endolift-Brazos.webp' , 'Valoración corporal de brazos', 'Contorno corporal', 'Brazos y axila', 'La reserva de piel y la relación con axila y torso condicionan el alcance razonable.', '/flacidez-grasa-localizada-brazos-madrid/'  ),
	nvx_sol( '/uploads/2026/07/Endolift-Abdomen-y-Flancos-Frente.webp' , 'Valoración del abdomen después del embarazo', 'Post-Maternity Contour™', 'Recuperación posgestacional', 'Piel, grasa, cicatriz y pared abdominal requieren decisiones distintas y, a veces, derivación.', '/tratamiento-postparto-abdomen-contorno-corporal-madrid/'  ),
	nvx_sol( '/uploads/2026/07/laser-co2-fraccionado-madrid-textura-cicatrices-poro.webp' , 'Valoración de cicatrices, poros y textura', 'Superficie cutánea', 'Cicatrices, poros y textura', 'Tipo de cicatriz, fototipo y riesgo de pigmentación determinan la secuencia clínica.', '/cicatrices-acne-poros-textura-madrid/'  ),
);

$evolution_cards = array(
	array(
		'image' => content_url( $img_papada ),
		'alt'   => 'Documentación clínica de evolución de papada y perfil',
		'label' => 'Perfil y cuello',
	),
	array(
		'image' => content_url( '/uploads/2026/07/Endolift-Full-Face.webp' ),
		'alt'   => 'Documentación clínica de evolución facial integral',
		'label' => 'Arquitectura facial',
	),
	array(
		'image' => content_url( '/uploads/2026/07/Endolift-Brazos.webp' ),
		'alt'   => 'Documentación clínica de evolución de brazos',
		'label' => 'Brazos',
	),
	array(
		'image' => content_url( '/uploads/2026/07/Endolift-Abdomen.webp' ),
		'alt'   => 'Documentación clínica de evolución de abdomen',
		'label' => 'Abdomen',
	),
	array(
		'image' => content_url( '/uploads/2026/07/Endolift-Espalda-Flancos-y-Sujetador.webp' ),
		'alt'   => 'Documentación clínica de evolución de espalda y zona del sujetador',
		'label' => 'Espalda',
	),
);

ob_start();
?>
<div id="nvx-home-v3" class="nvx-home-v3 nvx-home-v4">
	<section class="nvx-home-hero" aria-labelledby="nvx-home-hero-title">
		<div class="nvx-home-hero__media" aria-hidden="true">
			<video aria-hidden="true" id="nvx-home-hero-video" class="nvx-home-hero__video nvx-home-hero-video" autoplay muted loop playsinline preload="metadata" poster="<?php echo esc_url( $hero_poster_url ); ?>">
				<source src="<?php echo esc_url( $hero_video_url ); ?>" type="video/mp4">
			  <track kind="captions" src="/uploads/captions.vtt" srclang="es" label="Espa�ol">
</video>
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

	<section class="nvx-home-editorial" aria-labelledby="nvx-home-editorial-title">
		<header class="nvx-home-section-header">
			<div>
				<p class="nvx-home-eyebrow">PROTOCOLOS SIGNATURE</p>
				<h2 id="nvx-home-editorial-title">Una línea editorial que también es una forma de trabajar.</h2>
			</div>
			<p>Imágenes serenas, lenguaje preciso y resultados naturales. La estética visual no sustituye la valoración médica: la hace comprensible.</p>
		</header>
		<div class="nvx-home-editorial__grid">
			<?php foreach ( $editorial_stories as $story ) : ?>
				<article class="nvx-home-editorial-card">
					<img src="<?php echo esc_url( $story['image'] ); ?>" alt="<?php echo esc_attr( $story['alt'] ); ?>" loading="lazy" decoding="async">
					<div class="nvx-home-editorial-card__panel">
						<p class="nvx-home-editorial-card__kicker"><?php echo esc_html( $story['kicker'] ); ?></p>
						<h3><?php echo esc_html( $story['title'] ); ?></h3>
						<p><?php echo esc_html( $story['description'] ); ?></p>
						<a href="<?php echo esc_url( $story['url'] ); ?>">Conocer el protocolo <span aria-hidden="true">→</span></a>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
	</section>

	<section class="nvx-home-solutions" aria-labelledby="nvx-home-solutions-title">
		<header class="nvx-home-section-header">
			<div>
				<p class="nvx-home-eyebrow">SOLUCIONES MÉDICAS</p>
				<h2 id="nvx-home-solutions-title">La zona orienta la consulta. El diagnóstico define el plan.</h2>
			</div>
			<p>Una misma preocupación puede tener causas diferentes. Por eso evitamos convertir una tecnología en una respuesta universal.</p>
		</header>
		<div class="nvx-home-solutions__grid">
			<?php foreach ( $solution_cards as $solution ) : ?>
				<article class="nvx-home-solution-card">
					<a href="<?php echo esc_url( $solution['url'] ); ?>" class="nvx-home-solution-card__media" tabindex="-1" aria-hidden="true">
						<img src="<?php echo esc_url( $solution['image'] ); ?>" alt="<?php echo esc_attr( $solution['alt'] ); ?>" loading="lazy" decoding="async">
					</a>
					<div class="nvx-home-solution-card__copy">
						<p class="nvx-home-solution-card__label"><?php echo esc_html( $solution['label'] ); ?></p>
						<h3><a href="<?php echo esc_url( $solution['url'] ); ?>"><?php echo esc_html( $solution['title'] ); ?></a></h3>
						<p><?php echo esc_html( $solution['copy'] ); ?></p>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
	</section>

	<section class="nvx-home-evolution" aria-labelledby="nvx-home-evolution-title">
		<header class="nvx-home-section-header nvx-home-section-header--light">
			<div>
				<p class="nvx-home-eyebrow">DOCUMENTACIÓN CLÍNICA</p>
				<h2 id="nvx-home-evolution-title">Evolución registrada. Contexto visible.</h2>
			</div>
			<p>Casos documentados con consentimiento, condiciones de captura coherentes y seguimiento médico. Cada evolución es individual.</p>
		</header>
		<div class="nvx-home-evolution__track">
			<?php foreach ( $evolution_cards as $case ) : ?>
				<figure class="nvx-home-evolution-card">
					<img src="<?php echo esc_url( $case['image'] ); ?>" alt="<?php echo esc_attr( $case['alt'] ); ?>" loading="lazy" decoding="async">
					<figcaption><?php echo esc_html( $case['label'] ); ?></figcaption>
				</figure>
			<?php endforeach; ?>
		</div>
		<p class="nvx-home-evolution__action"><a href="<?php echo esc_url( home_url( '/casos-de-pacientes/' ) ); ?>" class="nvx-btn nvx-btn--secondary-on-dark">Explorar casos clínicos</a></p>
	</section>

	<section class="nvx-home-authority" aria-labelledby="nvx-home-authority-title">
		<div class="nvx-home-authority__media">
			<img src="<?php echo esc_url( content_url( '/uploads/2026/07/proceso-medico-laser-nuvanx-madrid.webp' ) ); ?>" alt="Experiencia editorial NUVANX" loading="lazy" decoding="async">
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
