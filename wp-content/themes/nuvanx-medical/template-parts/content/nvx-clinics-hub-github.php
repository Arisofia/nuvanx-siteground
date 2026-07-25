<?php
/**
 * GitHub-managed Clinics hub.
 * WordPress stores only route metadata; visible copy and structure live here.
 *
 * @package nuvanx-medical
 */

defined( 'ABSPATH' ) || exit;

$clinics = array(
	array(
		'id'        => 'clinica-chamberi',
		'eyebrow'   => 'CHAMBERÍ · CS20144',
		'name'      => 'NUVANX Chamberí',
		'address'   => 'Calle de Fernández de la Hoz, 4, Bajo Derecha, 28010 Madrid.',
		'phone'     => '669 319 836',
		'phone_url' => 'tel:+34669319836',
		'page_url'  => '/medicina-estetica-chamberi/',
		'map_url'   => 'https://www.google.com/maps/search/?api=1&query=NUVANX%20Medicina%20Est%C3%A9tica%20L%C3%A1ser%20Chamber%C3%AD%20Madrid',
		'items'     => array(
			'Valoración médica y planificación facial o corporal.',
			'Endolift®, endoláser, láser CO₂, EXION® y medicina estética.',
		),
	),
	array(
		'id'        => 'clinica-goya',
		'eyebrow'   => 'GOYA · BARRIO SALAMANCA · CS20073',
		'name'      => 'NUVANX Goya · Barrio Salamanca',
		'address'   => 'Calle de Fernán González, 26, 28009 Madrid.',
		'phone'     => '647 505 107',
		'phone_url' => 'tel:+34647505107',
		'page_url'  => '/clinicas-de-medicina-estetica-nuvanx/medicina-estetica-goya-barrio-salamanca/',
		'map_url'   => 'https://www.google.com/maps/search/?api=1&query=NUVANX%20Medicina%20Est%C3%A9tica%20L%C3%A1ser%20Salamanca%20Goya%20Madrid',
		'items'     => array(
			'Valoración médica y protocolos de medicina estética.',
			'Endolift®, endoláser, láser CO₂, EXION® y well-aging.',
		),
	),
);

$treatments = array(
	array( 'ROSTRO Y CUELLO', 'Endolift® facial', 'Papada, cuello y definición mandibular en casos seleccionados.', '/endolift-facial-papada-mandibula/' ),
	array( 'CONTORNO CORPORAL', 'Endoláser corporal', 'Grasa localizada y laxitud corporal según diagnóstico.', '/endolaser-corporal-grasa-localizada/' ),
	array( 'TEXTURA Y CICATRICES', 'Láser CO₂ fraccionado', 'Textura, poros, cicatrices y arrugas finas seleccionadas.', '/laser-co2-fraccionado-madrid-textura-cicatrices-poro/' ),
	array( 'FIRMEZA Y CALIDAD CUTÁNEA', 'EXION®', 'Aplicadores Face, Body y Fractional RF según objetivo clínico.', '/exion-btl/' ),
	array( 'MANCHAS Y ROJECES', 'BTL EXILITE™ IPL', 'Fotodaño, manchas y rojeces en pacientes seleccionados.', '/btl-exilite-ipl-madrid/' ),
	array( 'MEDICINA ESTÉTICA', 'Tratamientos inyectables', 'Ácido hialurónico, bioestimulación y otros procedimientos cuando están indicados.', '/medicina-estetica/' ),
);
?>
<div class="nvx-brand-page nvx-brand-page--clinicas">
	<header class="nvx-brand-hero nvx-editorial-hero nvx-canonical-page-hero" aria-labelledby="nvx-clinics-h1">
		<div class="nvx-brand-hero__inner">
			<div class="nvx-editorial-hero__copy">
				<p class="nvx-eyebrow">CLÍNICAS NUVANX · MADRID</p>
				<h1 id="nvx-clinics-h1" class="nvx-heading">Clínicas de medicina estética láser en Madrid</h1>
				<p class="nvx-brand-meta">Dos centros sanitarios autorizados en Chamberí y Goya · Barrio Salamanca, con dirección médica, diagnóstico individual y seguimiento clínico.</p>
				<div class="nvx-brand-actions">
					<a class="nvx-brand-btn nvx-brand-btn--primary" href="<?php echo esc_url( home_url( '/madrid/valoracion/#nvx-hubspot-form' ) ); ?>">Solicitar valoración médica</a>
					<a class="nvx-brand-btn nvx-brand-btn--secondary" href="<?php echo esc_url( home_url( '/equipo-medico/' ) ); ?>">Conocer al equipo médico</a>
				</div>
			</div>
		</div>
	</header>

	<nav id="nvx-clinics-nav" class="nvx-clinics-nav" aria-label="Navegación entre las clínicas NUVANX">
		<div class="nvx-shell nvx-clinics-nav__inner">
			<a class="nvx-clinics-nav__link" href="#clinica-chamberi">Chamberí</a>
			<a class="nvx-clinics-nav__link" href="#clinica-goya">Goya · Barrio Salamanca</a>
			<a class="nvx-clinics-nav__link" href="#como-elegir-sede">Cómo elegir sede</a>
		</div>
	</nav>

	<section class="nvx-brand-section" aria-labelledby="nvx-clinics-criterion">
		<div class="nvx-brand-section__inner">
			<p class="nvx-brand-kicker">UN MISMO CRITERIO MÉDICO</p>
			<h2 id="nvx-clinics-criterion" class="nvx-brand-title">Dos sedes, una misma forma de trabajar</h2>
			<div class="nvx-brand-readable nvx-brand-readable--wide">
				<p class="nvx-brand-body">NUVANX trabaja desde el diagnóstico, no desde una máquina concreta. En ambas clínicas revisamos anatomía, calidad de piel, antecedentes, expectativas y límites antes de proponer un tratamiento.</p>
				<p class="nvx-brand-body">La sede se asigna según disponibilidad médica, tecnología necesaria, agenda y preferencia del paciente. La indicación final se confirma siempre durante la valoración.</p>
			</div>
			<div class="nvx-brand-grid nvx-brand-grid--3">
				<article class="nvx-brand-card"><p class="nvx-brand-kicker">01</p><h3 class="nvx-brand-subtitle">Diagnóstico individual</h3><p class="nvx-brand-body">La recomendación depende del tejido, la zona, los antecedentes y el objetivo clínico.</p></article>
				<article class="nvx-brand-card"><p class="nvx-brand-kicker">02</p><h3 class="nvx-brand-subtitle">Tecnología seleccionada</h3><p class="nvx-brand-body">La plataforma se elige después de confirmar la indicación y los límites del caso.</p></article>
				<article class="nvx-brand-card"><p class="nvx-brand-kicker">03</p><h3 class="nvx-brand-subtitle">Seguimiento clínico</h3><p class="nvx-brand-body">Plan documentado, cuidados, revisiones y canal de contacto según el procedimiento.</p></article>
			</div>
		</div>
	</section>

	<section class="nvx-brand-section nvx-brand-section--soft" aria-labelledby="nvx-clinics-locations">
		<div class="nvx-brand-section__inner">
			<p class="nvx-brand-kicker">SEDES</p>
			<h2 id="nvx-clinics-locations" class="nvx-brand-title">Elige tu clínica NUVANX en Madrid</h2>
			<div class="nvx-brand-grid nvx-brand-grid--2">
				<?php foreach ( $clinics as $clinic ) : ?>
					<article id="<?php echo esc_attr( $clinic['id'] ); ?>" class="nvx-brand-card nvx-clinic-location">
						<p class="nvx-brand-kicker"><?php echo esc_html( $clinic['eyebrow'] ); ?></p>
						<h3 class="nvx-brand-subtitle"><a class="nvx-brand-inline-link" href="<?php echo esc_url( home_url( $clinic['page_url'] ) ); ?>"><?php echo esc_html( $clinic['name'] ); ?></a></h3>
						<p class="nvx-brand-body"><?php echo esc_html( $clinic['address'] ); ?></p>
						<ul class="nvx-brand-body nvx-brand-ux-list">
							<?php foreach ( $clinic['items'] as $item ) : ?><li><?php echo esc_html( $item ); ?></li><?php endforeach; ?>
							<li>Teléfono y WhatsApp: <a class="nvx-brand-inline-link" href="<?php echo esc_url( $clinic['phone_url'] ); ?>"><?php echo esc_html( $clinic['phone'] ); ?></a>.</li>
						</ul>
						<div class="nvx-brand-actions nvx-clinic-location__actions">
							<a class="nvx-brand-btn nvx-brand-btn--primary" href="<?php echo esc_url( home_url( $clinic['page_url'] ) ); ?>">Ver sede</a>
							<a class="nvx-brand-btn nvx-brand-btn--secondary nvx-clinic-map-cta" href="<?php echo esc_url( $clinic['map_url'] ); ?>" target="_blank" rel="nofollow noopener">Abrir en Google Maps</a>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section id="como-elegir-sede" class="nvx-brand-section" aria-labelledby="nvx-clinics-choice">
		<div class="nvx-brand-section__inner">
			<p class="nvx-brand-kicker">CÓMO ELEGIR SEDE</p>
			<h2 id="nvx-clinics-choice" class="nvx-brand-title">Chamberí o Goya: misma línea médica, distinta ubicación</h2>
			<div class="nvx-brand-grid nvx-brand-grid--3">
				<article class="nvx-brand-card"><h3 class="nvx-brand-subtitle">Por cercanía</h3><p class="nvx-brand-body">Puedes indicar la sede que te resulte más cómoda al completar la solicitud.</p></article>
				<article class="nvx-brand-card"><h3 class="nvx-brand-subtitle">Por agenda médica</h3><p class="nvx-brand-body">El equipo confirmará días y horarios disponibles para tu valoración.</p></article>
				<article class="nvx-brand-card"><h3 class="nvx-brand-subtitle">Por tecnología o procedimiento</h3><p class="nvx-brand-body">Algunos tratamientos se coordinan en la sede donde está disponible el equipo necesario.</p></article>
			</div>
		</div>
	</section>

	<section class="nvx-brand-section nvx-brand-section--soft" aria-labelledby="nvx-clinics-treatments">
		<div class="nvx-brand-section__inner">
			<p class="nvx-brand-kicker">TECNOLOGÍA Y TRATAMIENTOS</p>
			<h2 id="nvx-clinics-treatments" class="nvx-brand-title">Medicina estética láser, facial y corporal</h2>
			<p class="nvx-brand-body">No todos los tratamientos son adecuados para todos los pacientes. La tecnología se selecciona después de la valoración médica.</p>
			<div class="nvx-brand-grid nvx-brand-grid--3">
				<?php foreach ( $treatments as $treatment ) : ?>
					<article class="nvx-brand-card"><p class="nvx-brand-kicker"><?php echo esc_html( $treatment[0] ); ?></p><h3 class="nvx-brand-subtitle"><a class="nvx-brand-inline-link" href="<?php echo esc_url( home_url( $treatment[3] ) ); ?>"><?php echo esc_html( $treatment[1] ); ?></a></h3><p class="nvx-brand-body"><?php echo esc_html( $treatment[2] ); ?></p></article>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section class="nvx-brand-section" aria-labelledby="nvx-clinics-faq">
		<div class="nvx-brand-section__inner">
			<p class="nvx-brand-kicker">PREGUNTAS FRECUENTES</p>
			<h2 id="nvx-clinics-faq" class="nvx-brand-title">Dudas habituales sobre nuestras clínicas</h2>
			<div class="nvx-brand-faq-accordion">
				<details class="nvx-brand-faq-item"><summary><span>¿Cuántas clínicas NUVANX hay en Madrid?</span></summary><div class="nvx-brand-faq-content"><p>NUVANX cuenta con dos sedes: Chamberí y Goya · Barrio Salamanca.</p></div></details>
				<details class="nvx-brand-faq-item"><summary><span>¿Qué sede debo elegir?</span></summary><div class="nvx-brand-faq-content"><p>Puedes indicar tu preferencia. El equipo confirmará la sede según agenda, profesional y tecnología necesaria.</p></div></details>
				<details class="nvx-brand-faq-item"><summary><span>¿La valoración médica es gratuita?</span></summary><div class="nvx-brand-faq-content"><p>Sí. Permite revisar el caso, confirmar si existe indicación y explicar las opciones antes de decidir.</p></div></details>
				<details class="nvx-brand-faq-item"><summary><span>¿Ambas sedes realizan los mismos tratamientos?</span></summary><div class="nvx-brand-faq-content"><p>Comparten el mismo modelo médico. La disponibilidad concreta puede variar según tecnología, agenda y planificación clínica.</p></div></details>
			</div>
		</div>
	</section>
</div>
