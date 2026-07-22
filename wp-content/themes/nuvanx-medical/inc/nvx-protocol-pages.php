<?php
/**
 * Published NUVANX Protocol Signature pages.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Approved protocol pages keyed by protocol identifier. */
function nvx_protocol_pages_catalog(): array {
	return array(
		'couture-sculpt' => array(
			'slug'          => 'remodelacion-corporal-laser-madrid',
			'title'         => 'Remodelación corporal láser diseñada según tu anatomía.',
			'kicker'        => 'PROTOCOLO SIGNATURE NUVANX',
			'lead'          => 'NUVANX Contour Sculpt™ es nuestro sistema médico de diagnóstico y tratamiento por unidades anatómicas. Estudiamos la grasa localizada, la laxitud cutánea y la continuidad del contorno para diseñar un plan proporcionado y orientado a una evolución discreta.',
			'description'   => 'NUVANX Contour Sculpt™ articula esta visión: la tecnología se selecciona después de valorar anatomía, tejido predominante, transiciones entre zonas y límites clínicos.',
			'review_status' => 'approved_for_publication',
		),
		'post-maternity' => array(
			'slug'          => 'tratamiento-postparto-abdomen-contorno-corporal-madrid',
			'title'         => 'Tratamiento Postparto: Abdomen y Contorno Corporal en Madrid',
			'kicker'        => 'PROTOCOLO NUVANX',
			'lead'          => 'Diagnóstico médico del abdomen y el contorno posgestacional para diferenciar grasa subcutánea, laxitud cutánea, estrías, cicatriz y posibles alteraciones de la pared muscular.',
			'description'   => 'Después del embarazo no existe un único abdomen posparto. Cada componente requiere una valoración diferente y, en algunos casos, una derivación a fisioterapia especializada o cirugía.',
			'review_status' => 'approved_for_publication',
		),
		'profile-definition' => array(
			'slug'          => 'papada-definicion-mandibular-madrid',
			'title'         => 'Profile Definition™: Papada y mandíbula.',
			'kicker'        => 'ARQUITECTURA FACIAL NUVANX',
			'lead'          => 'Para la papada o la mandíbula poco definida, sin pasar por quirófano. Te miramos primero, te decimos con claridad qué se puede conseguir, y solo si tiene sentido para ti, seguimos adelante.',
			'description'   => 'A veces es grasa, a veces es que la piel ya no aguanta, y a veces las dos cosas. Se nota igual desde fuera, pero el tratamiento no es el mismo — por eso primero te miramos de cerca.',
			'review_status' => 'approved_for_publication',
		),
		'skin-architecture' => array(
			'slug'          => 'calidad-piel-firmeza-luminosidad-madrid',
			'title'         => 'Skin Architecture™: Firmeza y luminosidad.',
			'kicker'        => 'ARQUITECTURA FACIAL NUVANX',
			'lead'          => 'Las cremas hidratan la superficie, pero la firmeza se sostiene desde capas a las que los cosméticos no llegan. Si notas que la piel "cede" o se ve apagada, trabajamos desde el interior para que tu propio cuerpo vuelva a tensarla.',
			'description'   => 'Si la piel se siente fina o ha perdido tensión, ponerle ácido hialurónico para "hincharla" no soluciona el problema de fondo — solo te cambia la forma de la cara.',
			'review_status' => 'approved_for_publication',
		),
		'surface-renewal' => array(
			'slug'          => 'cicatrices-acne-poros-textura-madrid',
			'title'         => 'Surface Renewal™: Cicatrices y textura.',
			'kicker'        => 'ARQUITECTURA FACIAL NUVANX',
			'lead'          => 'Ningún peeling suave va a borrar un agujero o un poro muy dilatado, porque el problema está en la estructura profunda de la piel, no en la superficie. Te explicamos exactamente qué nivel de mejoría es realista para tus marcas.',
			'description'   => 'Las cicatrices tiran de la piel hacia adentro. Para alisarlas, necesitamos soltar esos "hilos" invisibles por debajo y renovar la superficie por arriba.',
			'review_status' => 'approved_for_publication',
		),
		'tone-correction' => array(
			'slug'          => 'manchas-rojeces-fotorejuvenecimiento-ipl-madrid',
			'title'         => 'Tone Correction™: Manchas y rojeces.',
			'kicker'        => 'ARQUITECTURA FACIAL NUVANX',
			'lead'          => 'Hay manchas por el sol, manchas por hormonas y manchas rojas por venitas dilatadas. Si tratamos las de hormonas con el láser equivocado, se pondrán más oscuras. Por eso la máquina no importa tanto como el diagnóstico previo.',
			'description'   => 'Si el problema es de sol (lentigos) o rojeces (cuperosis), la luz la limpia en un par de sesiones. Si es melasma (mancha hormonal), el calor fuerte es el enemigo.',
			'review_status' => 'approved_for_publication',
		),
		'eye-frame' => array(
			'slug'          => 'eye-frame-rejuvenecimiento-mirada-madrid',
			'title'         => 'NUVANX Eye Frame™: Rejuvenecimiento de la mirada.',
			'kicker'        => 'ARQUITECTURA FACIAL NUVANX',
			'lead'          => 'Si te dicen que tienes "cara de cansada" aunque hayas dormido ocho horas, el problema no es el sueño, es cómo la luz cae sobre tus ojos. Miramos de cerca si es sombra por hundimiento, color o piel fina, antes de decidir qué hacer.',
			'description'   => 'Tu mirada no siempre refleja lo cansada que estás; a veces es solo la anatomía.',
			'review_status' => 'approved_for_publication',
		),
	);
}

/** Identifies the configured protocol page for the current request. */
function nvx_protocol_pages_current_key(): ?string {
	if ( ! is_page() ) {
		return null;
	}

	$slug = (string) get_post_field( 'post_name', get_queried_object_id() );
	foreach ( nvx_protocol_pages_catalog() as $key => $page ) {
		if ( $page['slug'] === $slug && 'approved_for_publication' === $page['review_status'] ) {
			return $key;
		}
	}
	return null;
}

/** Builds the NUVANX Contour Sculpt protocol page. */
function nvx_protocol_pages_contour_sculpt_markup( array $data ): string {
	$html  = '<article class="nvx-brand-readable nvx-protocol-page nvx-shell">';
	$html .= '<header class="nvx-strategy-intro">';
	$html .= '<p class="nvx-brand-kicker">' . esc_html( $data['kicker'] ) . '</p>';
	$html .= '<h1 class="nvx-strategy-title">' . esc_html( $data['title'] ) . '</h1>';
	$html .= '<p class="nvx-brand-lead">' . esc_html( $data['lead'] ) . '</p>';
	$html .= '<p>' . esc_html( $data['description'] ) . '</p>';
	$html .= '<p><a class="nvx-btn nvx-btn--primary" href="' . esc_url( home_url( '/madrid/valoracion/' ) ) . '">' . esc_html__( 'Solicitar valoración médica privada', 'nuvanx-medical' ) . '</a> <a class="nvx-btn nvx-btn--secondary" href="#zonas-tratamiento">' . esc_html__( 'Explorar zonas de tratamiento', 'nuvanx-medical' ) . '</a></p>';
	$html .= '<p class="nvx-brand-microcopy">' . esc_html__( 'La técnica, las zonas, la evolución y el presupuesto se determinan tras la exploración médica.', 'nuvanx-medical' ) . '</p>';
	$html .= '</header>';

	$html .= '<section class="nvx-brand-section">';
	$html .= '<h2>' . esc_html__( 'No tratamos zonas aisladas. Diseñamos continuidad.', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p>' . esc_html__( 'El abdomen no termina en el abdomen. El brazo se relaciona con la axila y el torso. La espalda, la cintura y los flancos forman una misma unidad visual. Por eso estudiamos cada zona valorando integración global, espacio negativo, proporción y asimetrías.', 'nuvanx-medical' ) . '</p>';
	$html .= '<div class="nvx-card-diagnostic-wrap"><h3>' . esc_html__( 'Cartografía Anatómica NUVANX', 'nuvanx-medical' ) . '</h3>';
	$html .= '<p>' . esc_html__( 'Antes de proponer tecnología, el médico analiza distribución de grasa localizada, calidad y capacidad de retracción de la piel, transición entre zonas, asimetrías y límites reales de una intervención médico-estética.', 'nuvanx-medical' ) . '</p></div>';
	$html .= '</section>';

	$html .= '<section class="nvx-brand-section">';
	$html .= '<h2>' . esc_html__( 'Tres decisiones clínicas: Reducir, Redefinir, Retraer', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p>' . esc_html__( 'La indicación se define según anatomía, tejido predominante y límites del procedimiento. Estas vías pueden utilizarse por separado o combinarse cuando existe justificación clínica.', 'nuvanx-medical' ) . '</p>';
	$html .= '<ul class="nvx-check-list">';
	$html .= '<li><strong>REDUCIR:</strong> ' . esc_html__( 'cuando predomina grasa localizada susceptible de tratamiento mediante energía térmica.', 'nuvanx-medical' ) . '</li>';
	$html .= '<li><strong>REDEFINIR:</strong> ' . esc_html__( 'cuando el objetivo es mejorar la transición y proporción entre zonas adyacentes.', 'nuvanx-medical' ) . '</li>';
	$html .= '<li><strong>RETRAER:</strong> ' . esc_html__( 'cuando existe indicación para actuar sobre laxitud y calidad del tejido.', 'nuvanx-medical' ) . '</li>';
	$html .= '</ul></section>';

	$html .= '<section class="nvx-brand-section" id="zonas-tratamiento">';
	$html .= '<h2>' . esc_html__( 'Cartografía Anatómica: Zonas de tratamiento', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p>' . esc_html__( 'Aplicamos el sistema por unidades de contorno y justificamos cada combinación.', 'nuvanx-medical' ) . '</p>';
	$html .= '<p><strong>' . esc_html__( 'Abdomen y cintura', 'nuvanx-medical' ) . '</strong><br>' . esc_html__( 'Abdomen superior e inferior, flancos y espalda baja se valoran como una transición continua. Grasa y piel requieren diagnósticos distintos.', 'nuvanx-medical' ) . '</p>';
	$html .= '<p><strong>' . esc_html__( 'Torso superior', 'nuvanx-medical' ) . '</strong><br>' . esc_html__( 'Brazos, axila anterior y zona del sujetador se valoran como una unidad de continuidad.', 'nuvanx-medical' ) . '</p>';
	$html .= '<p><strong>' . esc_html__( 'Piernas y tren inferior', 'nuvanx-medical' ) . '</strong><br>' . esc_html__( 'Muslos internos y externos, región subglútea y rodillas se estudian según laxitud, grasa localizada y proporción.', 'nuvanx-medical' ) . '</p>';
	$html .= '</section>';

	$html .= '<section class="nvx-brand-section">';
	$html .= '<h2>' . esc_html__( 'NUVANX Contour Sculpt™: El protocolo y la tecnología', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p>' . esc_html__( 'NUVANX Contour Sculpt™ se articula a través del diagnóstico. El médico selecciona la modalidad que corresponde a la anatomía y al objetivo clínico, en lugar de depender de una única plataforma.', 'nuvanx-medical' ) . '</p>';
	$html .= '<ul class="nvx-check-list">';
	$html .= '<li><strong>Endoláser Corporal / Endolift®:</strong> ' . esc_html__( 'para abordar grasa localizada y laxitud cuando la exploración médica lo indique.', 'nuvanx-medical' ) . '</li>';
	$html .= '<li><strong>EXION® Body:</strong> ' . esc_html__( 'para apoyar firmeza y calidad tisular según indicación y plan médico.', 'nuvanx-medical' ) . '</li>';
	$html .= '<li><strong>' . esc_html__( 'Protocolos combinados:', 'nuvanx-medical' ) . '</strong> ' . esc_html__( 'para integrar modalidades distintas cuando existe una justificación clínica documentada.', 'nuvanx-medical' ) . '</li>';
	$html .= '</ul></section>';

	$html .= '<section class="nvx-brand-section nvx-strategy-checklist nvx-strategy-checklist--no">';
	$html .= '<h2>' . esc_html__( 'Cuándo no es el tratamiento adecuado', 'nuvanx-medical' ) . '</h2>';
	$html .= '<ul class="nvx-check-list nvx-check-list--no">';
	$html .= '<li>' . esc_html__( 'Cuando el objetivo es una pérdida general de peso.', 'nuvanx-medical' ) . '</li>';
	$html .= '<li>' . esc_html__( 'Cuando existe un exceso importante de piel que requiere valoración quirúrgica.', 'nuvanx-medical' ) . '</li>';
	$html .= '<li>' . esc_html__( 'Cuando existe sospecha de diástasis o hernia que requiera valoración o derivación específica.', 'nuvanx-medical' ) . '</li>';
	$html .= '</ul></section>';

	$html .= '<section class="nvx-brand-section"><h2>' . esc_html__( 'Tu primera valoración clínica', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p>' . esc_html__( 'El objetivo es definir una intervención proporcionada y médicamente defendible. Todo comienza con una valoración exhaustiva en Chamberí o Salamanca–Goya.', 'nuvanx-medical' ) . '</p>';
	$html .= '<p><a class="nvx-btn nvx-btn--primary" href="' . esc_url( home_url( '/madrid/valoracion/' ) ) . '">' . esc_html__( 'Solicitar valoración médica privada', 'nuvanx-medical' ) . '</a></p></section>';
	$html .= '</article>';
	return $html;
}

/** Builds the Post-Maternity Contour protocol page. */
function nvx_protocol_pages_post_maternity_markup( array $data ): string {
	$html  = '<article class="nvx-brand-readable nvx-protocol-page nvx-shell">';
	$html .= '<header class="nvx-strategy-intro">';
	$html .= '<p class="nvx-brand-kicker">' . esc_html( $data['kicker'] ) . '</p>';
	$html .= '<h1 class="nvx-strategy-title">' . esc_html( $data['title'] ) . '</h1>';
	$html .= '<p class="nvx-brand-lead">' . esc_html( $data['lead'] ) . '</p>';
	$html .= '<p>' . esc_html( $data['description'] ) . '</p>';
	$html .= '<p><a class="nvx-btn nvx-btn--primary" href="' . esc_url( home_url( '/madrid/valoracion/' ) ) . '">' . esc_html__( 'Solicitar valoración de viabilidad', 'nuvanx-medical' ) . '</a> <a class="nvx-btn nvx-btn--secondary" href="#alteraciones-posparto">' . esc_html__( 'Ver qué podemos valorar', 'nuvanx-medical' ) . '</a></p>';
	$html .= '</header>';

	$html .= '<section class="nvx-brand-section">';
	$html .= '<h2>' . esc_html__( 'Por qué un tratamiento posparto genérico no es suficiente', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p>' . esc_html__( 'El aumento de volumen o la pérdida de definición tras el embarazo no constituyen una sola alteración. Pueden coexistir grasa subcutánea, laxitud, estrías, cicatriz de cesárea, diástasis o exceso de piel. Aplicar una tecnología sin diferenciar el componente predominante puede no responder al problema real.', 'nuvanx-medical' ) . '</p>';
	$html .= '</section>';

	$html .= '<section class="nvx-brand-section">';
	$html .= '<h2>' . esc_html__( 'El Protocolo NUVANX Post-Maternity Contour™', 'nuvanx-medical' ) . '</h2>';
	$html .= '<ol class="nvx-check-list">';
	$html .= '<li><strong>' . esc_html__( 'Diagnóstico anatómico diferencial:', 'nuvanx-medical' ) . '</strong> ' . esc_html__( 'se valora qué proporción corresponde a grasa subcutánea, laxitud cutánea, cicatriz, estrías o alteración muscular.', 'nuvanx-medical' ) . '</li>';
	$html .= '<li><strong>' . esc_html__( 'Selección tecnológica:', 'nuvanx-medical' ) . '</strong> ' . esc_html__( 'solo si existe indicación se plantea Endoláser corporal, EXION® Body, Láser CO₂ u otra modalidad disponible.', 'nuvanx-medical' ) . '</li>';
	$html .= '<li><strong>' . esc_html__( 'Presupuesto y planificación:', 'nuvanx-medical' ) . '</strong> ' . esc_html__( 'el plan se documenta por escrito e incluye tiempos orientativos, cuidados y seguimiento.', 'nuvanx-medical' ) . '</li>';
	$html .= '</ol></section>';

	$html .= '<section class="nvx-brand-section" id="alteraciones-posparto">';
	$html .= '<h2>' . esc_html__( 'Las alteraciones del posparto: qué podemos tratar y cuándo derivamos', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p>' . esc_html__( 'Si lo que te sobra es grasa que no se va ni con dieta ni con gimnasio, ahí sí podemos ayudarte con láser. Si lo que ha pasado es que la piel se quedó floja pero no hay tanta grasa debajo, la solución es distinta — trabajamos la piel, no la grasa. Y si lo que hay es una separación de los músculos del abdomen (diástasis) o sobra bastante piel, te lo decimos claro: eso lo arregla un cirujano, no nosotros — e igualmente te ayudamos a encontrar quién.', 'nuvanx-medical' ) . '</p>';
	$html .= '</section>';

	$html .= '<section class="nvx-brand-section">';
	$html .= '<h2>' . esc_html__( 'La valoración médica: el paso indispensable', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p>' . esc_html__( 'La consulta revisa antecedentes, momento posparto, lactancia cuando corresponda, estabilidad de peso, pared abdominal, piel, grasa subcutánea, cicatrices y expectativas. Con esa información se explica qué puede tratarse, qué debe esperar y qué requiere derivación.', 'nuvanx-medical' ) . '</p>';
	$html .= '</section>';

	$html .= '<section class="nvx-brand-section">';
	$html .= '<h2>' . esc_html__( 'Preguntas frecuentes', 'nuvanx-medical' ) . '</h2>';
	$html .= '<h3>' . esc_html__( '¿Cuándo puede realizarse una valoración?', 'nuvanx-medical' ) . '</h3>';
	$html .= '<p>' . esc_html__( 'El momento se individualiza. Habitualmente se espera a que la recuperación inicial haya avanzado, el peso sea estable y los tejidos hayan tenido tiempo de evolucionar. La lactancia, los antecedentes y el procedimiento considerado también influyen.', 'nuvanx-medical' ) . '</p>';
	$html .= '<h3>' . esc_html__( '¿Se puede valorar la cicatriz de cesárea?', 'nuvanx-medical' ) . '</h3>';
	$html .= '<p>' . esc_html__( 'Sí. Se revisan madurez, textura, color, relieve y síntomas. La indicación depende del estado de la cicatriz y de la tecnología disponible.', 'nuvanx-medical' ) . '</p>';
	$html .= '<h3>' . esc_html__( '¿Qué ocurre si hay diástasis?', 'nuvanx-medical' ) . '</h3>';
	$html .= '<p>' . esc_html__( 'Si la exploración sugiere una diástasis significativa o una alteración de la pared abdominal, se recomienda valoración especializada. Un tratamiento estético sobre grasa o piel no corrige el componente muscular.', 'nuvanx-medical' ) . '</p>';
	$html .= '</section>';

	$html .= '<section class="nvx-brand-section">';
	$html .= '<p class="nvx-brand-kicker">' . esc_html__( 'TU PRIMERA VALORACIÓN CLÍNICA', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2>' . esc_html__( 'Una consulta médica para determinar la indicación de tu caso.', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p>' . esc_html__( 'Evaluamos el caso en nuestras clínicas autorizadas de Chamberí o Salamanca–Goya y documentamos el plan cuando existe una indicación médico-estética.', 'nuvanx-medical' ) . '</p>';
	$html .= '<p><a class="nvx-btn nvx-btn--primary" href="' . esc_url( home_url( '/madrid/valoracion/' ) ) . '">' . esc_html__( 'Iniciar valoración', 'nuvanx-medical' ) . '</a></p>';
	$html .= '</section></article>';
	return $html;
}

function nvx_protocol_pages_profile_definition_markup( array $data ): string {
	$html  = '<article class="nvx-brand-readable nvx-protocol-page nvx-shell">';
	$html .= '<header class="nvx-strategy-intro">';
	$html .= '<p class="nvx-brand-kicker">' . esc_html( $data['kicker'] ) . '</p>';
	$html .= '<h1 class="nvx-strategy-title">' . esc_html__( 'Papada y mandíbula: a veces es grasa, a veces es piel.', 'nuvanx-medical' ) . '</h1>';
	$html .= '<p class="nvx-brand-lead">' . esc_html( $data['lead'] ) . '</p>';
	$html .= '<p>' . esc_html( $data['description'] ) . '</p>';
	$html .= '<p><a class="nvx-btn nvx-btn--primary" href="' . esc_url( home_url( '/madrid/valoracion/' ) ) . '">' . esc_html__( 'Solicitar valoración médica', 'nuvanx-medical' ) . '</a></p>';
	$html .= '</header>';

	$html .= '<section class="nvx-brand-section">';
	$html .= '<h2>' . esc_html__( 'Por qué un tratamiento genérico no funciona', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p>' . esc_html__( 'A veces no hay papada real — lo que pasa es que el mentón es pequeño y eso hace que el cuello parezca más lleno de lo que es. Ahí ningún láser va a cambiar nada; lo que ayuda es dar un poco de volumen al mentón, no quitar nada del cuello.', 'nuvanx-medical' ) . '</p>';
	$html .= '<p>' . esc_html__( 'Cuando sí hay un problema de grasa localizada (adiposidad submentoniana) o de flacidez en el óvalo facial, la solución médica es el Endolift® láser. Permite destruir la grasa y tensar la piel desde dentro en un solo acto médico ambulatorio, sin cirugía.', 'nuvanx-medical' ) . '</p>';
	$html .= '</section></article>';
	return $html;
}

function nvx_protocol_pages_skin_architecture_markup( array $data ): string {
	$html  = '<article class="nvx-brand-readable nvx-protocol-page nvx-shell">';
	$html .= '<header class="nvx-strategy-intro">';
	$html .= '<p class="nvx-brand-kicker">' . esc_html( $data['kicker'] ) . '</p>';
	$html .= '<h1 class="nvx-strategy-title">' . esc_html__( 'Tu piel no necesita más cremas, necesita reconstruirse por dentro.', 'nuvanx-medical' ) . '</h1>';
	$html .= '<p class="nvx-brand-lead">' . esc_html( $data['lead'] ) . '</p>';
	$html .= '<p>' . esc_html( $data['description'] ) . '</p>';
	$html .= '<p><a class="nvx-btn nvx-btn--primary" href="' . esc_url( home_url( '/madrid/valoracion/' ) ) . '">' . esc_html__( 'Solicitar valoración médica', 'nuvanx-medical' ) . '</a></p>';
	$html .= '</header>';

	$html .= '<section class="nvx-brand-section">';
	$html .= '<h2>' . esc_html__( 'Por qué un tratamiento genérico no funciona', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p>' . esc_html__( 'Lo que hacemos es usar calor para obligar a tus células a fabricar colágeno nuevo. Es un proceso natural de tu cuerpo; nosotros solo le damos el estímulo correcto.', 'nuvanx-medical' ) . '</p>';
	$html .= '<p>' . esc_html__( 'Para lograrlo, empleamos plataformas avanzadas como la Radiofrecuencia Fraccionada BTL EXION® o bioestimuladores, dependiendo del grosor de tu dermis, el nivel de elastosis solar y tu fototipo.', 'nuvanx-medical' ) . '</p>';
	$html .= '</section></article>';
	return $html;
}

function nvx_protocol_pages_surface_renewal_markup( array $data ): string {
	$html  = '<article class="nvx-brand-readable nvx-protocol-page nvx-shell">';
	$html .= '<header class="nvx-strategy-intro">';
	$html .= '<p class="nvx-brand-kicker">' . esc_html( $data['kicker'] ) . '</p>';
	$html .= '<h1 class="nvx-strategy-title">' . esc_html__( 'Para mejorar las marcas de acné hay que romper la cicatriz, no solo pelar la piel.', 'nuvanx-medical' ) . '</h1>';
	$html .= '<p class="nvx-brand-lead">' . esc_html( $data['lead'] ) . '</p>';
	$html .= '<p>' . esc_html( $data['description'] ) . '</p>';
	$html .= '<p><a class="nvx-btn nvx-btn--primary" href="' . esc_url( home_url( '/madrid/valoracion/' ) ) . '">' . esc_html__( 'Solicitar valoración médica', 'nuvanx-medical' ) . '</a></p>';
	$html .= '</header>';

	$html .= '<section class="nvx-brand-section">';
	$html .= '<h2>' . esc_html__( 'Por qué un tratamiento genérico no funciona', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p>' . esc_html__( 'No te vamos a mentir: las marcas profundas rara vez desaparecen al 100%, pero sí podemos hacer que dejen de ser lo primero que ves en el espejo.', 'nuvanx-medical' ) . '</p>';
	$html .= '<p>' . esc_html__( 'Para conseguirlo, combinamos resurfacing ablativo con Láser CO₂ fraccionado o radiofrecuencia profunda con microagujas, dependiendo de si tus cicatrices atróficas son en furgón (boxcar), picahielo (icepick) o rodante (rolling).', 'nuvanx-medical' ) . '</p>';
	$html .= '</section></article>';
	return $html;
}

function nvx_protocol_pages_tone_correction_markup( array $data ): string {
	$html  = '<article class="nvx-brand-readable nvx-protocol-page nvx-shell">';
	$html .= '<header class="nvx-strategy-intro">';
	$html .= '<p class="nvx-brand-kicker">' . esc_html( $data['kicker'] ) . '</p>';
	$html .= '<h1 class="nvx-strategy-title">' . esc_html__( 'Quitar una mancha es fácil; que no vuelva a salir es la parte médica.', 'nuvanx-medical' ) . '</h1>';
	$html .= '<p class="nvx-brand-lead">' . esc_html( $data['lead'] ) . '</p>';
	$html .= '<p>' . esc_html( $data['description'] ) . '</p>';
	$html .= '<p><a class="nvx-btn nvx-btn--primary" href="' . esc_url( home_url( '/madrid/valoracion/' ) ) . '">' . esc_html__( 'Solicitar valoración médica', 'nuvanx-medical' ) . '</a></p>';
	$html .= '</header>';

	$html .= '<section class="nvx-brand-section">';
	$html .= '<h2>' . esc_html__( 'Por qué un tratamiento genérico no funciona', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p>' . esc_html__( 'Te decimos qué tipo tienes tú y cómo mantenerla a raya todo el año. Ahí toca ir poco a poco, combinando terapias en clínica con cremas médicas en casa.', 'nuvanx-medical' ) . '</p>';
	$html .= '<p>' . esc_html__( 'Diferenciamos entre pigmento dérmico o epidérmico y componente vascular para elegir entre Luz Pulsada Intensa (BTL EXILITE IPL), láser Q-Switched o pautas despigmentantes específicas.', 'nuvanx-medical' ) . '</p>';
	$html .= '</section></article>';
	return $html;
}

function nvx_protocol_pages_eye_frame_markup( array $data ): string {
	$html  = '<article class="nvx-brand-readable nvx-protocol-page nvx-shell">';
	$html .= '<header class="nvx-strategy-intro">';
	$html .= '<p class="nvx-brand-kicker">' . esc_html( $data['kicker'] ) . '</p>';
	$html .= '<h1 class="nvx-strategy-title">' . esc_html__( 'Tu mirada no siempre refleja lo cansada que estás; a veces es solo la anatomía.', 'nuvanx-medical' ) . '</h1>';
	$html .= '<p class="nvx-brand-lead">' . esc_html( $data['lead'] ) . '</p>';
	$html .= '<p><a class="nvx-btn nvx-btn--primary" href="' . esc_url( home_url( '/madrid/valoracion/' ) ) . '">' . esc_html__( 'Solicitar valoración médica', 'nuvanx-medical' ) . '</a></p>';
	$html .= '</header>';

	$html .= '<section class="nvx-brand-section">';
	$html .= '<h2>' . esc_html__( 'Por qué un tratamiento genérico no funciona', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p>' . esc_html__( 'A veces la ojera se marca porque falta apoyo debajo y se crea una sombra oscura — eso lo arreglamos dando soporte suave. Pero si el problema es que tienes bolsas que empujan hacia afuera, o la piel se ha quedado demasiado fina y transparente, poner relleno solo empeora las cosas hinchando la zona.', 'nuvanx-medical' ) . '</p>';
	$html .= '<p>' . esc_html__( 'Por eso no existe un tratamiento único para "las ojeras". Diferenciamos entre hundimiento estructural, componente vascular (venitas), pigmentación y edema (retención de líquido). Según lo que encontremos, te propondremos usar ácido hialurónico muy específico, mejorar la calidad de la piel con láser o radiofrecuencia, o, siendo francos, te derivaremos a un cirujano si lo que necesitas es quitar unas bolsas marcadas.', 'nuvanx-medical' ) . '</p>';
	$html .= '</section></article>';
	return $html;
}

/** Dispatches the markup for one approved protocol page. */
function nvx_protocol_pages_markup( string $key, array $data ): string {
	if ( 'couture-sculpt' === $key ) {
		return nvx_protocol_pages_contour_sculpt_markup( $data );
	}
	if ( 'post-maternity' === $key ) {
		return nvx_protocol_pages_post_maternity_markup( $data );
	}
	if ( 'profile-definition' === $key ) {
		return nvx_protocol_pages_profile_definition_markup( $data );
	}
	if ( 'skin-architecture' === $key ) {
		return nvx_protocol_pages_skin_architecture_markup( $data );
	}
	if ( 'surface-renewal' === $key ) {
		return nvx_protocol_pages_surface_renewal_markup( $data );
	}
	if ( 'tone-correction' === $key ) {
		return nvx_protocol_pages_tone_correction_markup( $data );
	}
	if ( 'eye-frame' === $key ) {
		return nvx_protocol_pages_eye_frame_markup( $data );
	}
	return '';
}

/** Replaces the content of a matching approved protocol page. */
function nvx_protocol_pages_content_filter( string $content ): string {
	if ( is_admin() || ! is_main_query() || ! in_the_loop() ) {
		return $content;
	}

	$key = nvx_protocol_pages_current_key();
	if ( null === $key ) {
		return $content;
	}

	$data   = nvx_protocol_pages_catalog()[ $key ];
	$markup = nvx_protocol_pages_markup( $key, $data );
	return '' === $markup ? $content : $markup;
}
add_filter( 'the_content', 'nvx_protocol_pages_content_filter', 21 );
