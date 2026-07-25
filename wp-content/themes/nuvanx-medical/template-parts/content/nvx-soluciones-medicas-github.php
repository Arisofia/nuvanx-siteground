<?php
/**
 * GitHub-managed medical solutions hub.
 *
 * WordPress provides routing and metadata only. All visible content and
 * structure for /soluciones-medicas/ are versioned in this template.
 *
 * @package nuvanx-medical
 */

defined( 'ABSPATH' ) || exit;

$valuation_url = home_url( '/madrid/valoracion/' );
$hero_art_url  = get_template_directory_uri() . '/assets/images/nvx-solutions-hero-architecture.svg';

$solution_groups = array(
	array(
		'id'          => 'rostro-cuello',
		'index'       => '01',
		'eyebrow'     => 'ROSTRO Y CUELLO',
		'title'       => 'Definición, soporte y calidad cutánea',
		'intro'       => 'La misma preocupación visible puede proceder de grasa, laxitud, pérdida de soporte, pigmentación o una combinación. La exploración determina qué componente debe tratarse y cuál no.',
		'surface'     => 'light',
		'solutions'   => array(
			array(
				'title'    => 'Papada y línea mandibular',
				'question' => 'Grasa localizada, laxitud, soporte del mentón y continuidad entre rostro y cuello.',
				'limit'    => 'Un exceso importante de piel o una alteración estructural puede requerir una alternativa quirúrgica.',
				'path'     => '/papada-definicion-mandibular-madrid/',
				'protocol' => 'Profile Definition™',
			),
			array(
				'title'    => 'Región periocular y mirada',
				'question' => 'Surco, pigmentación, vascularización, calidad de piel, laxitud y bolsas reales o aparentes.',
				'limit'    => 'Las bolsas grasas verdaderas o las alteraciones funcionales requieren valoración específica.',
				'path'     => '/ojeras-surco-lagrimal-madrid/',
				'protocol' => 'Eye Frame',
			),
			array(
				'title'    => 'Firmeza y densidad facial',
				'question' => 'Calidad dérmica, pérdida de firmeza, textura, poros y luminosidad.',
				'limit'    => 'La modalidad depende del fototipo, la profundidad del problema y el tiempo de recuperación disponible.',
				'path'     => '/calidad-piel-firmeza-luminosidad-madrid/',
				'protocol' => 'Skin Architecture™',
			),
		),
	),
	array(
		'id'          => 'piel-superficie',
		'index'       => '02',
		'eyebrow'     => 'PIEL Y SUPERFICIE',
		'title'       => 'Textura, cicatrices, manchas y rojeces',
		'intro'       => 'Las alteraciones de superficie requieren diagnóstico diferencial. El fototipo, la profundidad, la inflamación y el riesgo de pigmentación condicionan la energía y la secuencia de tratamiento.',
		'surface'     => 'soft',
		'solutions'   => array(
			array(
				'title'    => 'Cicatrices de acné, poros y textura',
				'question' => 'Tipo y profundidad de cicatriz, irregularidad de superficie, poros y calidad dérmica.',
				'limit'    => 'Las cicatrices profundas o mixtas pueden requerir varias técnicas y fases.',
				'path'     => '/cicatrices-acne-poros-textura-madrid/',
				'protocol' => 'Surface Renewal™',
			),
			array(
				'title'    => 'Manchas, rojeces y fotodaño',
				'question' => 'Léntigos, eritema, telangiectasias, melasma y pigmentación postinflamatoria.',
				'limit'    => 'Las lesiones pigmentadas sospechosas deben evaluarse antes de aplicar luz o láser.',
				'path'     => '/manchas-rojeces-fotorejuvenecimiento-ipl-madrid/',
				'protocol' => 'Tone Correction™',
			),
			array(
				'title'    => 'Renovación cutánea con láser CO₂',
				'question' => 'Textura, arrugas finas, cicatrices y alteraciones seleccionadas de superficie.',
				'limit'    => 'La intensidad y la recuperación se individualizan según la indicación y el fototipo.',
				'path'     => '/laser-co2-fraccionado-madrid-textura-cicatrices-poro/',
				'protocol' => 'Láser CO₂ fraccionado',
			),
		),
	),
	array(
		'id'          => 'contorno-corporal',
		'index'       => '03',
		'eyebrow'     => 'CONTORNO CORPORAL',
		'title'       => 'Grasa localizada, laxitud y continuidad anatómica',
		'intro'       => 'No tratamos zonas como compartimentos aislados. Analizamos la relación entre abdomen, flancos, espalda, brazos, muslos y rodillas para preservar proporción y continuidad.',
		'surface'     => 'dark',
		'solutions'   => array(
			array(
				'title'    => 'Abdomen y flancos',
				'question' => 'Grasa subcutánea, laxitud, estrías, estabilidad de peso y pared abdominal.',
				'limit'    => 'La grasa visceral, una diástasis relevante o un exceso importante de piel no se resuelven con un tratamiento focal.',
				'path'     => '/grasa-localizada-abdomen-flancos-madrid/',
				'protocol' => 'Contour Architecture™',
			),
			array(
				'title'    => 'Brazos y continuidad axilar',
				'question' => 'Grasa localizada, laxitud posterior y relación con axila, espalda y torso.',
				'limit'    => 'La reserva de piel condiciona cuánto puede mejorar el contorno sin cirugía.',
				'path'     => '/flacidez-grasa-localizada-brazos-madrid/',
				'protocol' => 'Contour Architecture™',
			),
			array(
				'title'    => 'Espalda y zona del sujetador',
				'question' => 'Pliegues por grasa, laxitud, presión de la prenda y continuidad con brazos y flancos.',
				'limit'    => 'Cada zona debe tener una indicación documentada; una combinación no se prescribe por defecto.',
				'path'     => '/grasa-espalda-zona-sujetador-madrid/',
				'protocol' => 'Contour Architecture™',
			),
			array(
				'title'    => 'Muslos y región subglútea',
				'question' => 'Laxitud, grasa localizada, celulitis estructural y continuidad del tren inferior.',
				'limit'    => 'La grasa, la laxitud y la celulitis responden a mecanismos distintos.',
				'path'     => '/flacidez-muslos-internos-subgluteo-madrid/',
				'protocol' => 'Contour Architecture™',
			),
			array(
				'title'    => 'Rodillas',
				'question' => 'Grasa localizada, laxitud y relación con muslo interno y pierna.',
				'limit'    => 'La anatomía de la zona y la calidad de piel determinan la indicación.',
				'path'     => '/tratamiento-rodillas-grasa-flacidez-madrid/',
				'protocol' => 'Contour Architecture™',
			),
		),
	),
	array(
		'id'          => 'planes-especificos',
		'index'       => '04',
		'eyebrow'     => 'PLANIFICACIÓN ESPECÍFICA',
		'title'       => 'Contextos que requieren una lectura propia',
		'intro'       => 'Algunos cambios no deben abordarse como una zona aislada. La historia clínica, la etapa vital, el patrón anatómico y los procedimientos previos modifican la planificación.',
		'surface'     => 'base',
		'solutions'   => array(
			array(
				'title'    => 'Cambios posgestacionales',
				'question' => 'Grasa localizada, laxitud, estrías, cicatriz de cesárea, diástasis y cambio de proporción.',
				'limit'    => 'La diástasis, la hernia o el exceso importante de piel pueden requerir valoración especializada.',
				'path'     => '/tratamiento-postparto-abdomen-contorno-corporal-madrid/',
				'protocol' => 'Post-Maternity Contour™',
			),
			array(
				'title'    => 'Contorno masculino',
				'question' => 'Perfil mandibular, grasa localizada, calidad de piel y proporciones del patrón anatómico masculino.',
				'limit'    => 'La planificación preserva la anatomía individual y no impone una forma estándar.',
				'path'     => '/contorno-corporal-masculino-madrid/',
				'protocol' => 'Male Contour',
			),
			array(
				'title'    => 'Procedimientos previos',
				'question' => 'Evolución, materiales utilizados, tiempos biológicos y posibilidad real de corregir o esperar.',
				'limit'    => 'Una segunda valoración puede concluir que lo indicado es observar, derivar o no intervenir.',
				'path'     => '/madrid/valoracion/',
				'protocol' => 'Segunda valoración médica',
			),
		),
	),
);
?>
<div class="nvx-solutions-page" id="nvx-solutions-page">
	<header class="nvx-solutions-hero" aria-labelledby="nvx-solutions-title">
		<div class="nvx-solutions-hero__copy">
			<p class="nvx-solutions-eyebrow">SOLUCIONES MÉDICAS · NUVANX MADRID</p>
			<h1 id="nvx-solutions-title">La preocupación orienta la consulta. El diagnóstico define el tratamiento.</h1>
			<p class="nvx-solutions-hero__lead">Organizamos las soluciones por anatomía y por causa clínica, no por catálogo de máquinas. Antes de recomendar una tecnología diferenciamos grasa, laxitud, soporte, textura, pigmentación y otros componentes que pueden producir signos similares.</p>
			<div class="nvx-solutions-actions">
				<a class="nvx-solutions-button nvx-solutions-button--primary" href="<?php echo esc_url( $valuation_url ); ?>">Solicitar valoración médica</a>
				<a class="nvx-solutions-link" href="#mapa-soluciones">Explorar soluciones</a>
			</div>
			<p class="nvx-solutions-hero__note">Diagnóstico individual · Indicación proporcionada · Seguimiento médico</p>
		</div>
		<figure class="nvx-solutions-hero__media" aria-hidden="true">
			<img src="<?php echo esc_url( $hero_art_url ); ?>" alt="" width="1600" height="1200" loading="eager" decoding="async">
		</figure>
	</header>

	<nav id="mapa-soluciones" class="nvx-solutions-nav" aria-label="Mapa de soluciones médicas">
		<div class="nvx-solutions-shell nvx-solutions-nav__inner">
			<?php foreach ( $solution_groups as $group ) : ?>
				<a href="#<?php echo esc_attr( $group['id'] ); ?>"><span><?php echo esc_html( $group['index'] ); ?></span><?php echo esc_html( $group['eyebrow'] ); ?></a>
			<?php endforeach; ?>
		</div>
	</nav>

	<section class="nvx-solutions-principle" aria-labelledby="nvx-solutions-principle-title">
		<div class="nvx-solutions-shell nvx-solutions-principle__grid">
			<div>
				<p class="nvx-solutions-eyebrow">ANTES DE LA TECNOLOGÍA</p>
				<h2 id="nvx-solutions-principle-title">Una misma apariencia puede tener causas diferentes.</h2>
			</div>
			<div class="nvx-solutions-principle__body">
				<p>Dos personas pueden consultar por la misma zona y necesitar planes distintos. La anatomía, la calidad del tejido, los antecedentes y los límites clínicos cambian la indicación.</p>
				<p>La valoración también puede concluir que conviene esperar, derivar o no tratar. Esa decisión forma parte del criterio médico.</p>
			</div>
		</div>
	</section>

	<?php foreach ( $solution_groups as $group ) : ?>
		<section id="<?php echo esc_attr( $group['id'] ); ?>" class="nvx-solutions-group nvx-solutions-group--<?php echo esc_attr( $group['surface'] ); ?>" aria-labelledby="<?php echo esc_attr( $group['id'] ); ?>-title">
			<div class="nvx-solutions-shell">
				<header class="nvx-solutions-group__header">
					<div class="nvx-solutions-group__index"><?php echo esc_html( $group['index'] ); ?></div>
					<div>
						<p class="nvx-solutions-eyebrow"><?php echo esc_html( $group['eyebrow'] ); ?></p>
						<h2 id="<?php echo esc_attr( $group['id'] ); ?>-title"><?php echo esc_html( $group['title'] ); ?></h2>
					</div>
					<p class="nvx-solutions-group__intro"><?php echo esc_html( $group['intro'] ); ?></p>
				</header>
				<div class="nvx-solutions-grid">
					<?php foreach ( $group['solutions'] as $solution ) : ?>
						<article class="nvx-solutions-card">
							<div class="nvx-solutions-card__content">
								<?php if ( ! empty( $solution['protocol'] ) ) : ?><p class="nvx-solutions-card__protocol"><?php echo esc_html( $solution['protocol'] ); ?></p><?php endif; ?>
								<h3><?php echo esc_html( $solution['title'] ); ?></h3>
								<dl>
									<div><dt>Qué se valora</dt><dd><?php echo esc_html( $solution['question'] ); ?></dd></div>
									<div><dt>Límites</dt><dd><?php echo esc_html( $solution['limit'] ); ?></dd></div>
								</dl>
							</div>
							<a class="nvx-solutions-card__link" href="<?php echo esc_url( home_url( $solution['path'] ) ); ?>">Explorar solución <span aria-hidden="true">→</span></a>
						</article>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
	<?php endforeach; ?>

	<section class="nvx-solutions-method" aria-labelledby="nvx-solutions-method-title">
		<div class="nvx-solutions-shell">
			<p class="nvx-solutions-eyebrow">CÓMO SE CONSTRUYE EL PLAN</p>
			<h2 id="nvx-solutions-method-title">De la preocupación visible a una indicación documentada.</h2>
			<ol class="nvx-solutions-method__steps">
				<li><span>01</span><h3>Escuchar el motivo de consulta</h3><p>Definimos qué cambio buscas y qué resultado consideras proporcionado.</p></li>
				<li><span>02</span><h3>Explorar anatomía y tejido</h3><p>Revisamos estructura, grasa, laxitud, superficie, fototipo y antecedentes.</p></li>
				<li><span>03</span><h3>Separar causas y límites</h3><p>Diferenciamos qué componente puede tratarse y qué requiere otra alternativa.</p></li>
				<li><span>04</span><h3>Documentar el plan</h3><p>Explicamos técnica, fases, cuidados, seguimiento y presupuesto individualizado.</p></li>
			</ol>
		</div>
	</section>

	<section class="nvx-solutions-closure" aria-labelledby="nvx-solutions-closure-title">
		<div class="nvx-solutions-shell nvx-solutions-closure__inner">
			<p class="nvx-solutions-eyebrow">TU PRIMERA VALORACIÓN</p>
			<h2 id="nvx-solutions-closure-title">No necesitas elegir un tratamiento antes de consultar.</h2>
			<p>Cuéntanos qué zona o cambio quieres valorar. El equipo médico estudiará la causa, las alternativas razonables y los límites antes de proponer cualquier procedimiento.</p>
			<div class="nvx-solutions-actions">
				<a class="nvx-solutions-button nvx-solutions-button--primary" href="<?php echo esc_url( $valuation_url ); ?>">Solicitar valoración médica</a>
				<a class="nvx-solutions-button nvx-solutions-button--secondary" href="<?php echo esc_url( home_url( '/equipo-medico/' ) ); ?>">Conocer al equipo médico</a>
			</div>
		</div>
	</section>
</div>
