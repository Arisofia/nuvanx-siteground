<?php
/**
 * Clinical and route governance for the Medicina Estética hub.
 *
 * The legacy editorial builder remains responsible for layout. This final output
 * filter removes unsupported absolutes and prevents draft/missing treatment pages
 * from being exposed as public links.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Resolve the valuation URL without inventing an unavailable treatment route. */
function nvx_aesthetic_hub_valuation_url(): string {
	return function_exists( 'nvx_cta_valoracion_url' )
		? nvx_cta_valoracion_url()
		: home_url( '/madrid/valoracion/' );
}

/**
 * Whether at least one canonical or legacy slug is publicly published.
 *
 * @param string             $primary Canonical slug.
 * @param array<int, string> $alternates Historical slugs.
 */
function nvx_aesthetic_hub_has_published_route( string $primary, array $alternates = array() ): bool {
	if ( ! function_exists( 'nvx_aesthetic_lookup_published_url' ) ) {
		return false;
	}

	foreach ( array_merge( array( $primary ), $alternates ) as $slug ) {
		if ( null !== nvx_aesthetic_lookup_published_url( $slug ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Replace a speculative treatment-card link with the valuation route.
 *
 * @param string             $html       Rendered hub HTML.
 * @param string             $primary    Canonical slug.
 * @param array<int, string> $alternates Historical slugs.
 */
function nvx_aesthetic_hub_guard_route( string $html, string $primary, array $alternates = array() ): string {
	if ( nvx_aesthetic_hub_has_published_route( $primary, $alternates ) ) {
		return $html;
	}

	$speculative = home_url( '/' . trim( $primary, '/' ) . '/' );
	$old_link    = '<a class="nvx-aes-card__link" href="' . esc_url( $speculative ) . '">' . esc_html__( 'Ver protocolo', 'nuvanx-medical' ) . '</a>';
	$new_link    = '<a class="nvx-aes-card__link nvx-open-valoracion-modal" href="' . esc_url( nvx_aesthetic_hub_valuation_url() ) . '" data-gtag="click-reserve">' . esc_html__( 'Solicitar valoración', 'nuvanx-medical' ) . '</a>';

	return str_replace( $old_link, $new_link, $html );
}

/**
 * Normalize the rendered hub after its layout builder runs at priority 19.
 */
function nvx_aesthetic_hub_governance_filter( string $content ): string {
	if ( false === strpos( $content, 'nvx-aesthetic-editorial' ) ) {
		return $content;
	}

	$replacements = array(
		'Restauramos el soporte estructural, la turgencia y la armonía del rostro mediante procedimientos médicos inyectables y regenerativos de alta precisión. Sin alterar tu identidad y guiados exclusivamente por el diagnóstico personalizado de nuestro equipo médico.'
			=> 'Valoramos soporte, calidad cutánea, expresión y proporciones para decidir si un procedimiento inyectable o regenerativo está indicado. El objetivo es preservar la identidad y explicar con claridad los límites de cada opción.',
		'Con el paso de los años, la reabsorción ósea y el desplazamiento de los compartimentos grasos profundos provocan la caída de los tejidos. Tratar una arruga de forma aislada sin restaurar este soporte óseo subyacente genera volúmenes artificiales y rostros pesados.'
			=> 'Los cambios óseos, grasos y ligamentarios pueden modificar el soporte facial. La valoración evita tratar una arruga de forma aislada cuando el problema dominante se encuentra en otro plano.',
		'Estudiamos tu rostro en movimiento (estática y dinámica gesticular). La colocación de un inyectable debe respetar la contracción natural de la musculatura mímica facial, evitando congelar la mirada o alterar la sonrisa.'
			=> 'La exploración estática y dinámica permite valorar cómo un procedimiento podría modificar la mirada, la sonrisa o la expresión. La indicación debe respetar la función y el movimiento individual.',
		'Analizamos el espesor dermoepidérmico y el nivel de elastosis. Esto determina la reología y el módulo de elasticidad del producto médico a inyectar, garantizando que sea imperceptible tanto a la vista como al tacto.'
			=> 'El espesor, la calidad cutánea, la zona y el plano ayudan a seleccionar las propiedades del material. Ningún producto permite garantizar que el resultado sea imperceptible en todos los pacientes.',
		'Reestablecemos la definición del arco de Cupido, las columnas del filtrum y el volumen del bermellón respetando la anatomía original del paciente. Seleccionamos geles de ácido hialurónico con alta cohesividad y elasticidad adaptada para que el labio se mueva de forma natural con el habla y la sonrisa.'
			=> 'La valoración diferencia hidratación, definición, pérdida de soporte y asimetrías. Cuando existe indicación, el ácido hialurónico, la técnica y la cantidad se seleccionan según anatomía y movimiento.',
		'Labios delgados, pérdida de volumen por envejecimiento o asimetrías severas.'
			=> 'Pérdida de definición o volumen, deshidratación y asimetrías seleccionadas.',
		'Corrección de irregularidades en el dorso nasal (caballete) y elevación sutil de la punta mediante la infiltración precisa de ácido hialurónico de alta densidad en el plano supraperiosteal. Un procedimiento de alta precisión que armoniza el perfil sin los tiempos de baja de una cirugía.'
			=> 'En casos seleccionados, el ácido hialurónico puede camuflar irregularidades del dorso o modificar visualmente determinados ángulos. No reduce el tamaño, no corrige la respiración y no sustituye una rinoplastia cuando la indicación es quirúrgica.',
		'Desviaciones leves del dorso nasal o puntas caídas. No sustituye a la rinoplastia quirúrgica.'
			=> 'Irregularidades estéticas seleccionadas del perfil. Las alteraciones funcionales o estructurales relevantes requieren valoración quirúrgica.',
		'Tratamiento estructural del hundimiento de la ojera mediante la infiltración profunda de ácido hialurónico específico para la zona periocular. El objetivo es eliminar el aspecto de cansancio visual de forma segura, reduciendo la sombra de la ojera y proyectando la luz en el tercio medio.'
			=> 'La ojera exige diferenciar hundimiento, bolsas, festones, edema, pigmentación y componente vascular. El ácido hialurónico solo se valora cuando predomina un déficit estructural y la anatomía es favorable.',
		'Ojeras hundidas o surco lagrimal marcado. Requiere dermis de calidad y ausencia de bolsas grasas.'
			=> 'Hundimiento estructural seleccionado, sin predominio de bolsas, festones o edema.',
		'Protocolos inductores de colágeno mediante la infiltración de ácido poliláctico (Sculptra®) o hidroxiapatita de calcio (Radiesse®). Estos principios activos desencadenan una respuesta celular en la dermis profunda que estimula a los fibroblastos a producir nuevas fibras elásticas, tensando el tejido sin añadir volumen artificial al rostro.'
			=> 'El ácido poli-L-láctico y la hidroxiapatita cálcica son materiales distintos del ácido hialurónico. Pueden formar parte de planes de remodelación tisular progresiva, con técnicas, riesgos y efectos de soporte diferentes.',
		'Flacidez moderada, pérdida de elasticidad y piel desvitalizada.'
			=> 'Pérdida seleccionada de calidad, densidad o firmeza cutánea.',
		'Los bioestimuladores (Sculptra®, Radiesse® y protocolos con PDRN) no rellenan: inducen una respuesta celular controlada en la dermis profunda. Los fibroblastos aumentan la síntesis de colágeno y matriz extracelular, densificando la piel y mejorando la turgencia con un resultado progresivo y natural.'
			=> 'Los productos denominados bioestimuladores no son uniformes. PLLA, CaHA y otros materiales requieren indicaciones, planos y seguimientos diferentes; algunos pueden aportar soporte además de una respuesta tisular progresiva.',
		'Tensado por neocolagénesis, no por relleno masivo.'
			=> 'Remodelación progresiva según material, técnica y respuesta individual.',
		'Mejora progresiva entre semanas y meses según el protocolo.'
			=> 'La evolución y los intervalos se revisan según producto, zona y respuesta.',
		'Fototipo, elastosis y calidad dérmica definen el plan.'
			=> 'Anatomía, calidad cutánea, antecedentes y objetivos definen el plan.',
		'Donde σ₀ representa la amplitud del esfuerzo mecánico aplicado, γ₀ es la amplitud de la deformación resultante, y δ corresponde al ángulo de fase del gel. Un gel con alto G′ ofrece gran resistencia a la deformación y capacidad de elevación: lo indicamos en planos profundos y supraperiosteales (mandíbula, pómulos). En labios u ojeras seleccionamos G′ bajo y alta cohesividad para integración imperceptible sin migración.'
			=> 'Donde σ₀ representa la amplitud del esfuerzo aplicado, γ₀ la deformación y δ el ángulo de fase. Estas propiedades ayudan a comparar geles, pero la selección también depende de cohesividad, zona, plano, técnica y ficha técnica; no permiten garantizar ausencia de visibilidad, irregularidad o migración.',
		'El Polidesoxirribonucleótido (PDRN), comúnmente conocido como ADN de salmón, actúa a nivel celular profundo. A diferencia de los rellenos de ácido hialurónico, cuya función es mecánica (aportar volumen y captar agua), el PDRN se une de forma selectiva a los receptores de adenosina A2A de los fibroblastos, acelerando la síntesis de colágeno, promoviendo la angiogénesis y reparando el ADN dañado por la radiación ultravioleta. Es un tratamiento regenerativo para densificar la piel desde dentro, sin aportar volumen volumétrico.'
			=> 'PDRN y polinucleótidos no son rellenos de ácido hialurónico. Su composición, autorización, indicaciones y evidencia dependen del producto concreto; no deben atribuirse efectos universales ni mecanismos clínicos garantizados sin revisar su documentación vigente.',
		'El efecto Tyndall es una complicación estética menor que ocurre cuando la luz incide sobre un depósito de ácido hialurónico colocado demasiado superficial en la piel ultrafina de la ojera, provocando una coloración azulada o grisácea. En Chamberí y Goya lo prevenimos depositando el producto en plano profundo, inmediatamente por encima del periostio, con microcánulas romas, y seleccionando geles con nula capacidad de retención de agua y bajísima dispersión de luz, para un resultado invisible y natural.'
			=> 'El efecto Tyndall es una coloración azulada o grisácea asociada a material demasiado superficial. La prevención exige diagnóstico, selección del producto, plano y técnica adecuados, pero ninguna técnica elimina por completo el riesgo de edema, irregularidad o efecto Tyndall.',
		'Desde 290 €' => 'Según valoración médica',
		'Desde 380 €' => 'Según valoración médica',
		'Estimuladores de colágeno desde 490 €' => 'Según valoración médica',
	);

	$content = str_replace( array_keys( $replacements ), array_values( $replacements ), $content );

	$routes = array(
		array( 'labios-acido-hialuronico-madrid', array( 'labios', 'acido-hialuronico-labios', 'tratamiento-labios' ) ),
		array( 'rinomodelacion-sin-cirugia-madrid', array( 'rinomodelacion', 'rinomodelacion-sin-cirugia' ) ),
		array( 'ojeras-surco-lagrimal-madrid', array( 'ojeras', 'tratamiento-ojeras', 'surco-lagrimal' ) ),
		array( 'bioestimuladores-colageno-madrid', array( 'bioestimulacion', 'bioestimuladores', 'sculptra', 'radiesse' ) ),
	);

	foreach ( $routes as $route ) {
		$content = nvx_aesthetic_hub_guard_route( $content, $route[0], $route[1] );
	}

	return $content;
}
add_filter( 'the_content', 'nvx_aesthetic_hub_governance_filter', 20 );
