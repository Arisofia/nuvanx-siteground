<?php
/**
 * Canonical facial aesthetic treatment pages.
 *
 * One versioned catalogue drives visible content, metadata, FAQ schema and the
 * staging-only page seeder. Production pages remain drafts until medical review.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/nvx-13-point-renderer.php';

/**
 * Canonical catalogue for facial injectable/regenerative treatment pages.
 *
 * No prices, fixed session counts or guaranteed durations are published here.
 * Every entry is explicitly pending medical sign-off before production release.
 *
 * @return array<string, array<string, mixed>>
 */
function nvx_aesthetic_treatment_catalog(): array {
	return array(
		'lips_ha' => array(
			'slug'        => 'labios-acido-hialuronico-madrid',
			'page_id'     => 3318,
			'h1'          => 'Ácido hialurónico en labios en Madrid',
			'seo_title'   => 'Ácido hialurónico en labios Madrid | NUVANX',
			'description' => 'Valoración médica para hidratación, perfilado o corrección de asimetrías labiales con ácido hialurónico, según anatomía, movimiento y objetivos.',
			'kicker'      => 'Medicina estética facial · Madrid',
			'lead'        => 'El tratamiento labial no parte de un volumen predeterminado. Estudiamos proporción, soporte, hidratación, simetría y movimiento para decidir si existe una indicación y qué grado de corrección es razonable.',
			'diagnosis'   => 'La valoración diferencia pérdida de definición, deshidratación, asimetría, cambios relacionados con la edad y expectativas que no pueden resolverse de forma segura con un inyectable. También revisa tratamientos previos, antecedentes médicos y la dinámica de la sonrisa.',
			'mechanism'   => 'Cuando está indicado, se emplea un gel de ácido hialurónico seleccionado por sus propiedades de integración y comportamiento en una zona móvil. La técnica, el plano y la cantidad se individualizan para evitar sobrecorrección y preservar la expresión.',
			'indications' => array(
				'Hidratación y definición del bermellón cuando la anatomía lo permite.',
				'Pérdida de volumen o soporte relacionada con el tiempo.',
				'Asimetrías seleccionadas que pueden mejorar sin alterar la función.',
			),
			'precautions' => array(
				'Infección activa, lesión herpética o inflamación en la zona requieren aplazar el procedimiento.',
				'Embarazo, lactancia, alergias, enfermedades relevantes y antecedentes de reacciones a rellenos deben revisarse en consulta.',
				'El uso de anticoagulantes o antiagregantes exige valoración individual. No debe suspenderse medicación sin indicación del profesional que la prescribe.',
				'Una expectativa de cambio desproporcionado o una anatomía no apta son motivos para no tratar.',
			),
			'process'     => array(
				'Historia clínica, exploración estática y dinámica y registro fotográfico clínico.',
				'Definición del objetivo, producto, plano y límites del procedimiento.',
				'Inyección conservadora con técnica adaptada y control inmediato del tejido.',
				'Indicaciones de cuidados y canal de contacto para cualquier síntoma inesperado.',
			),
			'evolution'   => 'Son frecuentes la inflamación, sensibilidad o pequeños hematomas durante los primeros días. El resultado no debe juzgarse mientras exista edema. La revisión posterior permite valorar integración, simetría y necesidad —o no— de ajustes.',
			'risks'       => array(
				'Inflamación, hematoma, dolor, asimetría, irregularidades o infección.',
				'Reacciones inflamatorias tempranas o tardías y necesidad de tratamiento médico.',
				'La inyección intravascular es una complicación poco frecuente pero grave; requiere reconocimiento y actuación inmediata.',
			),
			'combinations'=> array(
				'El soporte del tercio medio o la calidad cutánea pueden requerir un plan distinto antes de tratar el labio.',
				'No se combinan procedimientos por rutina: cada intervención debe responder a un diagnóstico concreto.',
			),
			'faqs'        => array(
				array(
					'q' => '¿El objetivo es aumentar siempre el volumen de los labios?',
					'a' => 'No. Puede existir una indicación de hidratación, definición o corrección limitada de asimetría sin buscar un aumento evidente. El plan depende de la anatomía y del movimiento.',
				),
				array(
					'q' => '¿El ácido hialurónico labial puede retirarse?',
					'a' => 'La hialuronidasa puede utilizarse por un profesional cualificado para degradar ácido hialurónico cuando existe una indicación clínica. No convierte el procedimiento en trivial ni garantiza una reversión inmediata o idéntica en todos los casos.',
				),
				array(
					'q' => '¿Debo suspender anticoagulantes antes del tratamiento?',
					'a' => 'No suspenda anticoagulantes ni antiagregantes por su cuenta. El riesgo de sangrado y el riesgo trombótico deben valorarse individualmente con el médico que prescribe la medicación.',
				),
				array(
					'q' => '¿Cuándo se aprecia el resultado real?',
					'a' => 'La forma inicial está condicionada por la inflamación. La valoración definitiva se realiza cuando el edema ha disminuido y el producto se ha integrado.',
				),
			),
			'schema'      => array(
				'name'              => 'Tratamiento labial con ácido hialurónico',
				'alternateName'     => array( 'Perfilado labial', 'Hidratación labial con ácido hialurónico' ),
				'bodyLocation'      => 'Labios y región perioral',
				'procedureType'     => 'https://schema.org/MinimallyInvasiveProcedure',
				'preparation'       => 'Historia clínica, exploración anatómica y dinámica, revisión de tratamientos previos y medicación y definición de expectativas realistas.',
				'howPerformed'      => 'Inyección médica de ácido hialurónico mediante técnica, plano y cantidad individualizados para la anatomía labial.',
				'followup'          => 'Cuidados posteriores y revisión clínica tras la fase inicial de inflamación; atención inmediata ante síntomas inesperados.',
				'indications'       => array( 'Pérdida de definición labial', 'Deshidratación labial', 'Asimetría labial seleccionada' ),
				'conditions'        => array( 'Pérdida de volumen labial relacionada con la edad', 'Asimetría labial' ),
			),
		),
		'rhinomodeling_ha' => array(
			'slug'        => 'rinomodelacion-sin-cirugia-madrid',
			'page_id'     => 3319,
			'h1'          => 'Rinomodelación con ácido hialurónico en Madrid',
			'seo_title'   => 'Rinomodelación con ácido hialurónico Madrid | NUVANX',
			'description' => 'Corrección médica no quirúrgica de irregularidades seleccionadas del perfil nasal con ácido hialurónico, con evaluación anatómica y vascular previa.',
			'kicker'      => 'Armonización del perfil · Madrid',
			'lead'        => 'La rinomodelación puede camuflar determinadas irregularidades añadiendo soporte en puntos concretos. No reduce el tamaño de la nariz, no corrige problemas respiratorios y no sustituye una rinoplastia cuando la indicación es quirúrgica.',
			'diagnosis'   => 'La exploración analiza dorso, radix, punta, proyección, piel, antecedentes de cirugía o rellenos y relación con mentón y tercio medio. Una nariz con alteración funcional, deformidad importante o expectativa de reducción debe derivarse a valoración quirúrgica.',
			'mechanism'   => 'En los casos seleccionados se utiliza ácido hialurónico para modificar ópticamente líneas y ángulos mediante pequeños depósitos en planos definidos. Esta página no equipara la hidroxiapatita cálcica con el ácido hialurónico: son materiales diferentes y no comparten el mismo mecanismo de reversión.',
			'indications' => array(
				'Irregularidades leves del dorso que pueden camuflarse añadiendo soporte.',
				'Necesidad seleccionada de ajustar proyección o rotación visual de la punta.',
				'Armonización del perfil cuando el diagnóstico descarta una necesidad quirúrgica.',
			),
			'precautions' => array(
				'Problemas respiratorios, deformidades importantes o deseo de reducir tamaño requieren valoración quirúrgica.',
				'Cirugías nasales, traumatismos o rellenos previos modifican la anatomía y el riesgo.',
				'Infección activa, embarazo, lactancia, alergias, enfermedades relevantes y medicación deben revisarse.',
				'No se suspende medicación anticoagulante o antiagregante sin indicación del prescriptor.',
			),
			'process'     => array(
				'Historia clínica, análisis facial y nasal y registro fotográfico estandarizado.',
				'Explicación de lo que puede camuflarse y de lo que no puede corregirse sin cirugía.',
				'Plan conservador con ácido hialurónico, técnica y puntos de aplicación individualizados.',
				'Observación inmediata y entrega de señales de alarma y contacto urgente.',
			),
			'evolution'   => 'Puede aparecer edema, sensibilidad o hematoma. La revisión se realiza tras la fase inflamatoria. Cualquier dolor intenso o creciente, cambio de coloración reticulada o blanquecina, frialdad cutánea o síntoma visual exige valoración médica inmediata.',
			'risks'       => array(
				'Inflamación, hematoma, irregularidad, asimetría, infección o resultado no deseado.',
				'Compromiso vascular con daño cutáneo; de forma excepcional pueden producirse alteraciones visuales o neurológicas.',
				'La nariz es una zona de riesgo elevado y el procedimiento debe realizarse con preparación para reconocer y tratar complicaciones.',
			),
			'combinations'=> array(
				'La relación nariz–mentón puede estudiarse de forma conjunta, sin asumir que ambas zonas deban tratarse.',
				'Una indicación quirúrgica no debe sustituirse por acumulación de producto.',
			),
			'faqs'        => array(
				array(
					'q' => '¿La rinomodelación hace la nariz más pequeña?',
					'a' => 'No. Añade soporte para camuflar determinadas irregularidades y modificar visualmente líneas o ángulos. Si el objetivo es reducir estructura o corregir función, debe valorarse cirugía.',
				),
				array(
					'q' => '¿Radiesse® es un ácido hialurónico reversible con hialuronidasa?',
					'a' => 'No. Radiesse® contiene hidroxiapatita cálcica, no ácido hialurónico, y no comparte el mismo mecanismo de degradación con hialuronidasa. En NUVANX el material y su reversibilidad se explican de forma específica antes de tratar.',
				),
				array(
					'q' => '¿Qué síntomas requieren atención urgente después de una rinomodelación?',
					'a' => 'Dolor intenso o creciente, piel pálida o con patrón reticulado, alteración marcada de temperatura o sensibilidad y cualquier síntoma visual requieren contacto y valoración médica inmediata.',
				),
				array(
					'q' => '¿Puede corregir una desviación o un problema respiratorio?',
					'a' => 'No corrige el tabique ni la función respiratoria. Las alteraciones funcionales o estructurales relevantes deben valorarse por cirugía u otorrinolaringología.',
				),
			),
			'schema'      => array(
				'name'              => 'Rinomodelación con ácido hialurónico',
				'alternateName'     => array( 'Rinomodelación sin cirugía', 'Armonización nasal no quirúrgica' ),
				'bodyLocation'      => 'Nariz y perfil facial',
				'procedureType'     => 'https://schema.org/MinimallyInvasiveProcedure',
				'preparation'       => 'Historia clínica y análisis anatómico y vascular, incluyendo cirugía, traumatismos, rellenos previos, medicación, función respiratoria y expectativas.',
				'howPerformed'      => 'Aplicación médica conservadora de ácido hialurónico en puntos y planos seleccionados para camuflar irregularidades sin modificar estructuras nasales.',
				'followup'          => 'Observación inmediata, instrucciones de alarma y revisión tras la fase inflamatoria.',
				'indications'       => array( 'Irregularidad leve del dorso nasal', 'Armonización seleccionada del perfil nasal' ),
				'conditions'        => array( 'Irregularidad estética del perfil nasal' ),
			),
		),
		'tear_trough_ha' => array(
			'slug'        => 'ojeras-surco-lagrimal-madrid',
			'page_id'     => 3320,
			'h1'          => 'Tratamiento de ojeras y surco lagrimal en Madrid',
			'seo_title'   => 'Ojeras y surco lagrimal Madrid | Diagnóstico NUVANX',
			'description' => 'Diagnóstico médico del surco lagrimal para diferenciar hundimiento, bolsas, edema y pigmentación antes de valorar ácido hialurónico u otras alternativas.',
			'kicker'      => 'Región periocular · Madrid',
			'lead'        => '“Ojera” no es un diagnóstico único. El hundimiento estructural, la pigmentación, la transparencia vascular, las bolsas, los festones y el edema requieren abordajes distintos; rellenar sin diferenciarlos puede empeorar el aspecto.',
			'diagnosis'   => 'La valoración revisa soporte del tercio medio, transición párpado–mejilla, bolsas grasas, laxitud, edema, calidad cutánea y antecedentes de rellenos o cirugía. El ácido hialurónico solo se considera cuando predomina un déficit estructural y no existen factores que desaconsejen su uso.',
			'mechanism'   => 'Cuando existe indicación, un ácido hialurónico con características adecuadas puede suavizar la transición entre párpado inferior y mejilla. No elimina pigmentación, vasos visibles, bolsas prominentes ni edema crónico.',
			'indications' => array(
				'Hundimiento estructural del surco lagrimal en pacientes seleccionados.',
				'Transición párpado–mejilla marcada que puede mejorar con soporte profundo.',
				'Asimetrías leves con anatomía favorable y expectativas realistas.',
			),
			'precautions' => array(
				'Bolsas prominentes, festones, edema malar o tendencia importante a retener líquido pueden requerir otro enfoque.',
				'Pigmentación o componente vascular no se corrigen añadiendo volumen.',
				'Rellenos previos, cirugía, enfermedad ocular, inflamación y medicación deben revisarse.',
				'El uso de anticoagulantes o antiagregantes requiere decisión individual; no se suspenden sin el prescriptor.',
			),
			'process'     => array(
				'Exploración de la región periocular y del soporte del tercio medio.',
				'Clasificación del componente estructural, pigmentario, vascular, graso y edematoso.',
				'Si está indicado, técnica conservadora con producto y plano seleccionados.',
				'Seguimiento de edema, integración, simetría y cualquier síntoma inesperado.',
			),
			'evolution'   => 'Es posible que aparezcan inflamación y hematomas. La zona puede retener líquido durante más tiempo que otras áreas faciales. La integración y la necesidad de corrección se valoran de forma diferida, no durante el edema inicial.',
			'risks'       => array(
				'Edema persistente, hematoma, irregularidad, asimetría, coloración azulada o efecto Tyndall.',
				'Infección, reacción inflamatoria o empeoramiento de bolsas y festones.',
				'Compromiso vascular, incluida la posibilidad excepcional de alteraciones visuales, que exige actuación inmediata.',
			),
			'combinations'=> array(
				'La calidad de piel puede tratarse con tecnologías o protocolos distintos al relleno.',
				'Cuando predomina bolsa grasa o laxitud relevante puede recomendarse valoración quirúrgica.',
			),
			'faqs'        => array(
				array(
					'q' => '¿Todas las ojeras se tratan con ácido hialurónico?',
					'a' => 'No. El ácido hialurónico puede considerarse cuando predomina el hundimiento estructural. Pigmentación, vasos, bolsas, festones o edema requieren otros enfoques.',
				),
				array(
					'q' => '¿Por qué puede aparecer edema después del tratamiento?',
					'a' => 'La región periocular tiene una anatomía y drenaje particulares. El producto, el plano, la cantidad y la predisposición del paciente influyen; por eso la selección y el seguimiento son esenciales.',
				),
				array(
					'q' => '¿Qué es el efecto Tyndall?',
					'a' => 'Es una coloración azulada que puede aparecer cuando un relleno queda demasiado superficial. Debe valorarse clínicamente para decidir la conducta adecuada.',
				),
				array(
					'q' => '¿Cuándo es preferible no rellenar el surco lagrimal?',
					'a' => 'Cuando predominan bolsas, festones, edema, laxitud relevante, pigmentación aislada o una anatomía que aumenta el riesgo de un resultado desfavorable.',
				),
			),
			'schema'      => array(
				'name'              => 'Tratamiento médico del surco lagrimal con ácido hialurónico',
				'alternateName'     => array( 'Relleno de ojeras', 'Corrección del surco lagrimal' ),
				'bodyLocation'      => 'Región periocular y transición párpado-mejilla',
				'procedureType'     => 'https://schema.org/MinimallyInvasiveProcedure',
				'preparation'       => 'Diagnóstico diferencial de hundimiento, bolsas, edema, pigmentación y componente vascular, con revisión de antecedentes y tratamientos previos.',
				'howPerformed'      => 'Inyección médica conservadora de ácido hialurónico en pacientes seleccionados, con producto, plano y cantidad adaptados a la anatomía periocular.',
				'followup'          => 'Seguimiento de edema, integración, simetría y signos de complicación.',
				'indications'       => array( 'Hundimiento estructural del surco lagrimal', 'Transición párpado-mejilla marcada' ),
				'conditions'        => array( 'Deformidad del surco lagrimal' ),
			),
		),
		'biostimulators' => array(
			'slug'        => 'bioestimuladores-colageno-madrid',
			'page_id'     => 3321,
			'h1'          => 'Bioestimuladores de colágeno en Madrid',
			'seo_title'   => 'Bioestimuladores de colágeno Madrid | NUVANX',
			'description' => 'Valoración médica de bioestimulación con ácido poli-L-láctico o hidroxiapatita cálcica según calidad cutánea, anatomía y objetivo terapéutico.',
			'kicker'      => 'Medicina regenerativa estética · Madrid',
			'lead'        => 'Los bioestimuladores no son una categoría uniforme. El ácido poli-L-láctico y la hidroxiapatita cálcica tienen composiciones, comportamiento tisular, técnicas y perfiles de manejo diferentes. La indicación comienza por la calidad cutánea y el soporte, no por una marca.',
			'diagnosis'   => 'La valoración estudia laxitud, espesor, distribución de volumen, calidad dérmica, zonas de movilidad, antecedentes de rellenos y capacidad de seguimiento. No toda flacidez responde a un inyectable y una pérdida estructural importante puede requerir otras técnicas.',
			'mechanism'   => 'El ácido poli-L-láctico (PLLA) y la hidroxiapatita cálcica (CaHA) pueden inducir una respuesta de remodelación tisular progresiva, pero no son ácido hialurónico. La CaHA puede aportar además un efecto de soporte inmediato según formulación y plano; ninguno se describe como reversible con hialuronidasa.',
			'indications' => array(
				'Pérdida seleccionada de calidad, densidad o firmeza cutánea.',
				'Laxitud leve o moderada cuando la anatomía permite un abordaje inyectable.',
				'Planes progresivos de remodelación tisular con expectativas realistas.',
			),
			'precautions' => array(
				'Inflamación o infección activa, embarazo, lactancia y antecedentes relevantes requieren valoración o aplazamiento.',
				'Zonas anatómicas, espesor cutáneo y productos previos condicionan material, dilución, plano o contraindicación.',
				'Los nódulos, granulomas y reacciones inflamatorias son riesgos que deben explicarse.',
				'No debe presentarse como tratamiento “sin volumen” de forma absoluta ni como resultado inmediato estandarizado.',
			),
			'process'     => array(
				'Diagnóstico de calidad cutánea, laxitud, soporte y distribución de volumen.',
				'Selección explícita entre PLLA, CaHA u otra alternativa; explicación de diferencias y límites.',
				'Plan de aplicación por zonas y sesiones definido por el médico, sin calendario comercial rígido.',
				'Seguimiento progresivo para evaluar respuesta y descartar reacciones adversas.',
			),
			'evolution'   => 'Puede existir un cambio inicial relacionado con el vehículo, el edema o el soporte del producto, pero la remodelación tisular se evalúa de forma progresiva. El número de sesiones y los intervalos no se fijan antes del diagnóstico y pueden variar entre pacientes.',
			'risks'       => array(
				'Inflamación, hematoma, dolor, asimetría, irregularidades, infección o resultado no deseado.',
				'Nódulos, granulomas o reacciones inflamatorias que pueden requerir seguimiento y tratamiento.',
				'Compromiso vascular u otras complicaciones graves asociadas a procedimientos inyectables.',
			),
			'combinations'=> array(
				'Puede formar parte de un plan que incluya tecnologías para superficie, laxitud o estructura, siempre de forma secuenciada.',
				'No se combinan materiales ni tecnologías sin definir el objetivo y el intervalo clínico de cada intervención.',
			),
			'faqs'        => array(
				array(
					'q' => '¿Radiesse® y Sculptra® son ácido hialurónico?',
					'a' => 'No. Radiesse® se basa en hidroxiapatita cálcica y Sculptra® en ácido poli-L-láctico. Son materiales distintos del ácido hialurónico y requieren indicación, técnica y manejo propios.',
				),
				array(
					'q' => '¿Los bioestimuladores pueden disolverse con hialuronidasa?',
					'a' => 'No. La hialuronidasa actúa sobre ácido hialurónico; no ofrece el mismo mecanismo de reversión para hidroxiapatita cálcica o ácido poli-L-láctico.',
				),
				array(
					'q' => '¿El resultado es inmediato?',
					'a' => 'Puede existir un cambio inicial por el vehículo, el edema o el soporte del material, pero la respuesta de remodelación se valora progresivamente y varía entre pacientes.',
				),
				array(
					'q' => '¿Cuántas sesiones necesito?',
					'a' => 'No debe fijarse un número sin diagnóstico. El producto, la zona, la calidad cutánea, la respuesta y los objetivos determinan el plan y su revisión.',
				),
			),
			'schema'      => array(
				'name'              => 'Bioestimulación de colágeno con PLLA o CaHA',
				'alternateName'     => array( 'Bioestimuladores de colágeno', 'Remodelación tisular inyectable' ),
				'bodyLocation'      => 'Rostro y zonas anatómicas seleccionadas',
				'procedureType'     => 'https://schema.org/MinimallyInvasiveProcedure',
				'preparation'       => 'Valoración de calidad cutánea, laxitud, soporte, volumen, tratamientos previos, medicación y capacidad de seguimiento.',
				'howPerformed'      => 'Aplicación médica de ácido poli-L-láctico o hidroxiapatita cálcica mediante técnica, dilución, plano y secuencia individualizados.',
				'followup'          => 'Seguimiento progresivo de la respuesta tisular y vigilancia de inflamación, nódulos u otras complicaciones.',
				'indications'       => array( 'Pérdida de calidad y densidad cutánea', 'Laxitud cutánea leve o moderada seleccionada' ),
				'conditions'        => array( 'Laxitud cutánea facial', 'Pérdida de densidad dérmica' ),
			),
		),
	);
}

/** Resolve a treatment key from slug or current singular page. */
function nvx_aesthetic_treatment_key_from_slug( string $slug ): ?string {
	$slug = trim( $slug, '/' );
	foreach ( nvx_aesthetic_treatment_catalog() as $key => $entry ) {
		if ( $slug === $entry['slug'] ) {
			return $key;
		}
	}
	return null;
}

function nvx_aesthetic_treatment_current_key(): ?string {
	if ( is_admin() || ! is_singular( 'page' ) ) {
		return null;
	}
	$slug = (string) get_post_field( 'post_name', get_queried_object_id() );
	return nvx_aesthetic_treatment_key_from_slug( $slug );
}

/** @return array<string, array<int, array{q:string,a:string}>> */
function nvx_aesthetic_treatment_faq_catalog(): array {
	$result = array();
	foreach ( nvx_aesthetic_treatment_catalog() as $key => $entry ) {
		$result[ $key ] = $entry['faqs'];
	}
	return $result;
}

/** @return array<string, array<string, mixed>> */
function nvx_aesthetic_treatment_schema_catalog(): array {
	$result = array();
	foreach ( nvx_aesthetic_treatment_catalog() as $key => $entry ) {
		$result[ $key ] = $entry['schema'];
	}
	return $result;
}

nvx_register_catalog_content_filter( 'nvx_aesthetic_treatment_catalog', 80 );


/** Canonical SEO metadata for the four pages. */
function nvx_aesthetic_treatment_current_entry(): ?array {
	$key     = nvx_aesthetic_treatment_current_key();
	$catalog = nvx_aesthetic_treatment_catalog();
	return null !== $key && isset( $catalog[ $key ] ) ? $catalog[ $key ] : null;
}

function nvx_aesthetic_treatment_filter_title( $title ) {
	$entry = nvx_aesthetic_treatment_current_entry();
	return null === $entry ? $title : $entry['seo_title'];
}
add_filter( 'wpseo_title', 'nvx_aesthetic_treatment_filter_title', 90 );
add_filter( 'wpseo_opengraph_title', 'nvx_aesthetic_treatment_filter_title', 90 );
add_filter( 'wpseo_twitter_title', 'nvx_aesthetic_treatment_filter_title', 90 );

function nvx_aesthetic_treatment_filter_description( $description ) {
	$entry = nvx_aesthetic_treatment_current_entry();
	return null === $entry ? $description : $entry['description'];
}
add_filter( 'wpseo_metadesc', 'nvx_aesthetic_treatment_filter_description', 90 );
add_filter( 'wpseo_opengraph_desc', 'nvx_aesthetic_treatment_filter_description', 90 );
add_filter( 'wpseo_twitter_description', 'nvx_aesthetic_treatment_filter_description', 90 );

function nvx_aesthetic_treatment_filter_canonical( $canonical ) {
	$entry = nvx_aesthetic_treatment_current_entry();
	return null === $entry ? $canonical : home_url( '/' . $entry['slug'] . '/' );
}
add_filter( 'wpseo_canonical', 'nvx_aesthetic_treatment_filter_canonical', 90 );
add_filter( 'wpseo_opengraph_url', 'nvx_aesthetic_treatment_filter_canonical', 90 );

function nvx_aesthetic_treatment_document_title( array $parts ): array {
	$entry = nvx_aesthetic_treatment_current_entry();
	if ( null !== $entry ) {
		$parts['title'] = $entry['h1'];
	}
	return $parts;
}
add_filter( 'document_title_parts', 'nvx_aesthetic_treatment_document_title', 90 );

/** Seed the four pages only in staging2, which is globally noindex. */
function nvx_aesthetic_treatment_seed_staging_pages(): void {
	if ( ! function_exists( 'nvx_environment_is_staging2' ) || ! nvx_environment_is_staging2() ) {
		return;
	}

	foreach ( nvx_aesthetic_treatment_catalog() as $key => $entry ) {
		$page = get_page_by_path( $entry['slug'], OBJECT, 'page' );
		if ( $page instanceof WP_Post ) {
			continue;
		}

		$post_id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => $entry['h1'],
				'post_name'    => $entry['slug'],
				'post_excerpt' => $entry['description'],
				'post_content' => '<div class="nvx-aesthetic-treatment-source" data-nvx-treatment="' . esc_attr( $key ) . '"></div>',
			),
			true
		);

		if ( ! is_wp_error( $post_id ) ) {
			update_post_meta( $post_id, '_nvx_aesthetic_treatment_key', $key );
			update_post_meta( $post_id, '_nvx_medical_review_status', 'pending' );
		}
	}
}
add_action( 'init', 'nvx_aesthetic_treatment_seed_staging_pages', 30 );
