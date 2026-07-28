<?php
/**
 * Canonical facial aesthetic treatment pages.
 *
 * One versioned catalogue drives visible content, metadata, FAQ schema and the
 * staging-only page seeder. Content, SEO and schema inject only when
 * review_status is approved_for_publication (production fail-closed). Staging2
 * may preview pending_medical_review entries (globally noindex).
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Schema.org procedureType constants for medical procedures.
if ( ! defined( 'NVX_SCHEMA_MINIMALLY_INVASIVE' ) ) {
	define( 'NVX_SCHEMA_MINIMALLY_INVASIVE', 'https://schema.org/MinimallyInvasiveProcedure' );
}
if ( ! defined( 'NVX_SCHEMA_PERCUTANEOUS' ) ) {
	define( 'NVX_SCHEMA_PERCUTANEOUS', 'https://schema.org/PercutaneousProcedure' );
}
if ( ! defined( 'NVX_SCHEMA_NONINVASIVE' ) ) {
	define( 'NVX_SCHEMA_NONINVASIVE', 'https://schema.org/NoninvasiveProcedure' );
}

require_once __DIR__ . '/nvx-13-point-renderer.php';

/**
 * Build one FAQ pair for a treatment entry.
 *
 * @return array{q:string,a:string}
 */
function nvx_aesthetic_treatment_faq( string $question, string $answer ): array {
	return nvx_editorial_faq( $question, $answer );
}

/**
 * Build the schema.org MedicalProcedure payload nested under a treatment entry.
 *
 * @param string[] $alternate_name
 * @param string[] $indications
 * @param string[] $conditions
 * @return array<string, mixed>
 */
function nvx_aesthetic_treatment_schema_payload(
	string $name,
	array $alternate_name,
	string $body_location,
	string $procedure_type,
	string $preparation,
	string $how_performed,
	string $followup,
	array $indications,
	array $conditions
): array {
	return array(
		'name'          => $name,
		'alternateName' => $alternate_name,
		'bodyLocation'  => $body_location,
		'procedureType' => $procedure_type,
		'preparation'   => $preparation,
		'howPerformed'  => $how_performed,
		'followup'      => $followup,
		'indications'   => $indications,
		'conditions'    => $conditions,
	);
}

/**
 * Build one canonical treatment catalogue entry (13-point matrix + SEO + schema).
 *
 * page_id is unused for routing (slug/path is authoritative). Prefer 0.
 * review_status: approved_for_publication | pending_medical_review
 *
 * @param string[]                         $indications
 * @param string[]                         $precautions
 * @param string[]                         $process
 * @param string[]                         $risks
 * @param string[]                         $combinations
 * @param array<int, array{q:string,a:string}> $faqs
 * @param array<string, mixed>             $schema
 * @return array<string, mixed>
 */
function nvx_aesthetic_treatment_entry(
	string $slug,
	int $page_id,
	string $h1,
	string $seo_title,
	string $description,
	string $kicker,
	string $lead,
	string $diagnosis,
	string $mechanism,
	array $indications,
	array $precautions,
	array $process,
	string $evolution,
	array $risks,
	array $combinations,
	array $faqs,
	array $schema,
	string $review_status = 'pending_medical_review'
): array {
	return array(
		'slug'          => $slug,
		'page_id'       => $page_id,
		'h1'            => $h1,
		'seo_title'     => $seo_title,
		'description'   => $description,
		'kicker'        => $kicker,
		'lead'          => $lead,
		'diagnosis'     => $diagnosis,
		'mechanism'     => $mechanism,
		'indications'   => $indications,
		'precautions'   => $precautions,
		'process'       => $process,
		'evolution'     => $evolution,
		'risks'         => $risks,
		'combinations'  => $combinations,
		'faqs'          => $faqs,
		'schema'        => $schema,
		'review_status' => $review_status,
	);
}

/**
 * Canonical catalogue for facial injectable/regenerative treatment pages.
 *
 * No prices, fixed session counts or guaranteed durations are published here.
 * Entries use pending_medical_review until clinical sign-off; production is
 * fail-closed. Staging2 previews pending entries (see is_renderable).
 *
 * @return array<string, array<string, mixed>>
 */
function nvx_aesthetic_treatment_catalog(): array {
	return array(
		'lips_ha'          => nvx_aesthetic_treatment_entry(
			'labios-acido-hialuronico-madrid',
			0,
			'Ácido hialurónico en labios en Madrid',
			'Ácido hialurónico en labios Madrid | NUVANX',
			'Valoración médica para hidratación, perfilado o corrección de asimetrías labiales con ácido hialurónico, según anatomía, movimiento y objetivos.',
			'Medicina estética facial · Madrid',
			'El tratamiento labial no parte de un volumen predeterminado. Estudiamos proporción, soporte, hidratación, simetría y movimiento para decidir si existe una indicación y qué grado de corrección es razonable.',
			'La valoración diferencia pérdida de definición, deshidratación, asimetría, cambios relacionados con la edad y expectativas que no pueden resolverse de forma segura con un inyectable. También revisa tratamientos previos, antecedentes médicos y la dinámica de la sonrisa.',
			'Cuando está indicado, se emplea un gel de ácido hialurónico seleccionado por sus propiedades de integración y comportamiento en una zona móvil. La técnica, el plano y la cantidad se individualizan para evitar sobrecorrección y preservar la expresión.',
			array(
				'Hidratación y definición del bermellón cuando la anatomía lo permite.',
				'Pérdida de volumen o soporte relacionada con el tiempo.',
				'Asimetrías seleccionadas que pueden mejorar sin alterar la función.',
			),
			array(
				'Infección activa, lesión herpética o inflamación en la zona requieren aplazar el procedimiento.',
				'Embarazo, lactancia, alergias, enfermedades relevantes y antecedentes de reacciones a rellenos deben revisarse en consulta.',
				'El uso de anticoagulantes o antiagregantes exige valoración individual. No debe suspenderse medicación sin indicación del profesional que la prescribe.',
				'Una expectativa de cambio desproporcionado o una anatomía no apta son motivos para no tratar.',
			),
			array(
				'Historia clínica, exploración estática y dinámica y registro fotográfico clínico.',
				'Definición del objetivo, producto, plano y límites del procedimiento.',
				'Inyección conservadora con técnica adaptada y control inmediato del tejido.',
				'Indicaciones de cuidados y canal de contacto para cualquier síntoma inesperado.',
			),
			'Son frecuentes la inflamación, sensibilidad o pequeños hematomas durante los primeros días. El resultado no debe juzgarse mientras exista edema. La revisión posterior permite valorar integración, simetría y necesidad —o no— de ajustes.',
			array(
				'Inflamación, hematoma, dolor, asimetría, irregularidades o infección.',
				'Reacciones inflamatorias tempranas o tardías y necesidad de tratamiento médico.',
				'La inyección intravascular es una complicación poco frecuente pero grave; requiere reconocimiento y actuación inmediata.',
			),
			array(
				'El soporte del tercio medio o la calidad cutánea pueden requerir un plan distinto antes de tratar el labio.',
				'No se combinan procedimientos por rutina: cada intervención debe responder a un diagnóstico concreto.',
			),
			array(
				nvx_aesthetic_treatment_faq(
					'¿El objetivo es aumentar siempre el volumen de los labios?',
					'No. Puede existir una indicación de hidratación, definición o corrección limitada de asimetría sin buscar un aumento evidente. El plan depende de la anatomía y del movimiento.'
				),
				nvx_aesthetic_treatment_faq(
					'¿El ácido hialurónico labial puede retirarse?',
					'La hialuronidasa puede utilizarse por un profesional cualificado para degradar ácido hialurónico cuando existe una indicación clínica. No convierte el procedimiento en trivial ni garantiza una reversión inmediata o idéntica en todos los casos.'
				),
				nvx_aesthetic_treatment_faq(
					'¿Debo suspender anticoagulantes antes del tratamiento?',
					'No suspenda anticoagulantes ni antiagregantes por su cuenta. El riesgo de sangrado y el riesgo trombótico deben valorarse individualmente con el médico que prescribe la medicación.'
				),
				nvx_aesthetic_treatment_faq(
					'¿Cuándo se aprecia el resultado real?',
					'La forma inicial está condicionada por la inflamación. La valoración definitiva se realiza cuando el edema ha disminuido y el producto se ha integrado.'
				),
			),
			nvx_aesthetic_treatment_schema_payload(
				'Tratamiento labial con ácido hialurónico',
				array( 'Perfilado labial', 'Hidratación labial con ácido hialurónico' ),
				'Labios y región perioral',
				NVX_SCHEMA_MINIMALLY_INVASIVE,
				'Historia clínica, exploración anatómica y dinámica, revisión de tratamientos previos y medicación y definición de expectativas realistas.',
				'Inyección médica de ácido hialurónico mediante técnica, plano y cantidad individualizados para la anatomía labial.',
				'Cuidados posteriores y revisión clínica tras la fase inicial de inflamación; atención inmediata ante síntomas inesperados.',
				array( 'Pérdida de definición labial', 'Deshidratación labial', 'Asimetría labial seleccionada' ),
				array( 'Pérdida de volumen labial relacionada con la edad', 'Asimetría labial' )
			),
			'pending_medical_review'
		),
		'rhinomodeling_ha' => nvx_aesthetic_treatment_entry(
			'rinomodelacion-sin-cirugia-madrid',
			0,
			'Rinomodelación con ácido hialurónico en Madrid',
			'Rinomodelación con ácido hialurónico Madrid | NUVANX',
			'Corrección médica no quirúrgica de irregularidades seleccionadas del perfil nasal con ácido hialurónico, con evaluación anatómica y vascular previa.',
			'Armonización del perfil · Madrid',
			'La rinomodelación puede camuflar determinadas irregularidades añadiendo soporte en puntos concretos. No reduce el tamaño de la nariz, no corrige problemas respiratorios y no sustituye una rinoplastia cuando la indicación es quirúrgica.',
			'La exploración analiza dorso, radix, punta, proyección, piel, antecedentes de cirugía o rellenos y relación con mentón y tercio medio. Una nariz con alteración funcional, deformidad importante o expectativa de reducción debe derivarse a valoración quirúrgica.',
			'En los casos seleccionados se utiliza ácido hialurónico para modificar ópticamente líneas y ángulos mediante pequeños depósitos en planos definidos. Esta página no equipara la hidroxiapatita cálcica con el ácido hialurónico: son materiales diferentes y no comparten el mismo mecanismo de reversión.',
			array(
				'Irregularidades leves del dorso que pueden camuflarse añadiendo soporte.',
				'Necesidad seleccionada de ajustar proyección o rotación visual de la punta.',
				'Armonización del perfil cuando el diagnóstico descarta una necesidad quirúrgica.',
			),
			array(
				'Problemas respiratorios, deformidades importantes o deseo de reducir tamaño requieren valoración quirúrgica.',
				'Cirugías nasales, traumatismos o rellenos previos modifican la anatomía y el riesgo.',
				'Infección activa, embarazo, lactancia, alergias, enfermedades relevantes y medicación deben revisarse.',
				'No se suspende medicación anticoagulante o antiagregante sin indicación del prescriptor.',
			),
			array(
				'Historia clínica, análisis facial y nasal y registro fotográfico estandarizado.',
				'Explicación de lo que puede camuflarse y de lo que no puede corregirse sin cirugía.',
				'Plan conservador con ácido hialurónico, técnica y puntos de aplicación individualizados.',
				'Observación inmediata y entrega de señales de alarma y contacto urgente.',
			),
			'Puede aparecer edema, sensibilidad o hematoma. La revisión se realiza tras la fase inflamatoria. Cualquier dolor intenso o creciente, cambio de coloración reticulada o blanquecina, frialdad cutánea o síntoma visual exige valoración médica inmediata.',
			array(
				'Inflamación, hematoma, irregularidad, asimetría, infección o resultado no deseado.',
				'Compromiso vascular con daño cutáneo; de forma excepcional pueden producirse alteraciones visuales o neurológicas.',
				'La nariz es una zona de riesgo elevado y el procedimiento debe realizarse con preparación para reconocer y tratar complicaciones.',
			),
			array(
				'La relación nariz–mentón puede estudiarse de forma conjunta, sin asumir que ambas zonas deban tratarse.',
				'Una indicación quirúrgica no debe sustituirse por acumulación de producto.',
			),
			array(
				nvx_aesthetic_treatment_faq(
					'¿La rinomodelación hace la nariz más pequeña?',
					'No. Añade soporte para camuflar determinadas irregularidades y modificar visualmente líneas o ángulos. Si el objetivo es reducir estructura o corregir función, debe valorarse cirugía.'
				),
				nvx_aesthetic_treatment_faq(
					'¿Radiesse® es un ácido hialurónico reversible con hialuronidasa?',
					'No. Radiesse® contiene hidroxiapatita cálcica, no ácido hialurónico, y no comparte el mismo mecanismo de degradación con hialuronidasa. En NUVANX el material y su reversibilidad se explican de forma específica antes de tratar.'
				),
				nvx_aesthetic_treatment_faq(
					'¿Qué síntomas requieren atención urgente después de una rinomodelación?',
					'Dolor intenso o creciente, piel pálida o con patrón reticulado, alteración marcada de temperatura o sensibilidad y cualquier síntoma visual requieren contacto y valoración médica inmediata.'
				),
				nvx_aesthetic_treatment_faq(
					'¿Puede corregir una desviación o un problema respiratorio?',
					'No corrige el tabique ni la función respiratoria. Las alteraciones funcionales o estructurales relevantes deben valorarse por cirugía u otorrinolaringología.'
				),
			),
			nvx_aesthetic_treatment_schema_payload(
				'Rinomodelación con ácido hialurónico',
				array( 'Rinomodelación sin cirugía', 'Armonización nasal no quirúrgica' ),
				'Nariz y perfil facial',
				NVX_SCHEMA_MINIMALLY_INVASIVE,
				'Historia clínica y análisis anatómico y vascular, incluyendo cirugía, traumatismos, rellenos previos, medicación, función respiratoria y expectativas.',
				'Aplicación médica conservadora de ácido hialurónico en puntos y planos seleccionados para camuflar irregularidades sin modificar estructuras nasales.',
				'Observación inmediata, instrucciones de alarma y revisión tras la fase inflamatoria.',
				array( 'Irregularidad leve del dorso nasal', 'Armonización seleccionada del perfil nasal' ),
				array( 'Irregularidad estética del perfil nasal' )
			),
			'pending_medical_review'
		),
		'tear_trough_ha'   => nvx_aesthetic_treatment_entry(
			'ojeras-surco-lagrimal-madrid',
			0,
			'Tratamiento de ojeras y surco lagrimal en Madrid',
			'Ojeras y surco lagrimal Madrid | Diagnóstico NUVANX',
			'Diagnóstico médico del surco lagrimal para diferenciar hundimiento, bolsas, edema y pigmentación antes de valorar ácido hialurónico u otras alternativas.',
			'Región periocular · Madrid',
			'“Ojera” no es un diagnóstico único. El hundimiento estructural, la pigmentación, la transparencia vascular, las bolsas, los festones y el edema requieren abordajes distintos; rellenar sin diferenciarlos puede empeorar el aspecto.',
			'La valoración revisa soporte del tercio medio, transición párpado–mejilla, bolsas grasas, laxitud, edema, calidad cutánea y antecedentes de rellenos o cirugía. El ácido hialurónico solo se considera cuando predomina un déficit estructural y no existen factores que desaconsejen su uso.',
			'Cuando existe indicación, un ácido hialurónico con características adecuadas puede suavizar la transición entre párpado inferior y mejilla. No elimina pigmentación, vasos visibles, bolsas prominentes ni edema crónico.',
			array(
				'Hundimiento estructural del surco lagrimal en pacientes seleccionados.',
				'Transición párpado–mejilla marcada que puede mejorar con soporte profundo.',
				'Asimetrías leves con anatomía favorable y expectativas realistas.',
			),
			array(
				'Bolsas prominentes, festones, edema malar o tendencia importante a retener líquido pueden requerir otro enfoque.',
				'Pigmentación o componente vascular no se corrigen añadiendo volumen.',
				'Rellenos previos, cirugía, enfermedad ocular, inflamación y medicación deben revisarse.',
				'El uso de anticoagulantes o antiagregantes requiere decisión individual; no se suspenden sin el prescriptor.',
			),
			array(
				'Exploración de la región periocular y del soporte del tercio medio.',
				'Clasificación del componente estructural, pigmentario, vascular, graso y edematoso.',
				'Si está indicado, técnica conservadora con producto y plano seleccionados.',
				'Seguimiento de edema, integración, simetría y cualquier síntoma inesperado.',
			),
			'Es posible que aparezcan inflamación y hematomas. La zona puede retener líquido durante más tiempo que otras áreas faciales. La integración y la necesidad de corrección se valoran de forma diferida, no durante el edema inicial.',
			array(
				'Edema persistente, hematoma, irregularidad, asimetría, coloración azulada o efecto Tyndall.',
				'Infección, reacción inflamatoria o empeoramiento de bolsas y festones.',
				'Compromiso vascular, incluida la posibilidad excepcional de alteraciones visuales, que exige actuación inmediata.',
			),
			array(
				'La calidad de piel puede tratarse con tecnologías o protocolos distintos al relleno.',
				'Cuando predomina bolsa grasa o laxitud relevante puede recomendarse valoración quirúrgica.',
			),
			array(
				nvx_aesthetic_treatment_faq(
					'¿Todas las ojeras se tratan con ácido hialurónico?',
					'No. El ácido hialurónico puede considerarse cuando predomina el hundimiento estructural. Pigmentación, vasos, bolsas, festones o edema requieren otros enfoques.'
				),
				nvx_aesthetic_treatment_faq(
					'¿Por qué puede aparecer edema después del tratamiento?',
					'La región periocular tiene una anatomía y drenaje particulares. El producto, el plano, la cantidad y la predisposición del paciente influyen; por eso la selección y el seguimiento son esenciales.'
				),
				nvx_aesthetic_treatment_faq(
					'¿Qué es el efecto Tyndall?',
					'Es una coloración azulada que puede aparecer cuando un relleno queda demasiado superficial. Debe valorarse clínicamente para decidir la conducta adecuada.'
				),
				nvx_aesthetic_treatment_faq(
					'¿Cuándo es preferible no rellenar el surco lagrimal?',
					'Cuando predominan bolsas, festones, edema, laxitud relevante, pigmentación aislada o una anatomía que aumenta el riesgo de un resultado desfavorable.'
				),
			),
			nvx_aesthetic_treatment_schema_payload(
				'Tratamiento médico del surco lagrimal con ácido hialurónico',
				array( 'Relleno de ojeras', 'Corrección del surco lagrimal' ),
				'Región periocular y transición párpado-mejilla',
				NVX_SCHEMA_MINIMALLY_INVASIVE,
				'Diagnóstico diferencial de hundimiento, bolsas, edema, pigmentación y componente vascular, con revisión de antecedentes y tratamientos previos.',
				'Inyección médica conservadora de ácido hialurónico en pacientes seleccionados, con producto, plano y cantidad adaptados a la anatomía periocular.',
				'Seguimiento de edema, integración, simetría y signos de complicación.',
				array( 'Hundimiento estructural del surco lagrimal', 'Transición párpado-mejilla marcada' ),
				array( 'Deformidad del surco lagrimal' )
			),
			'pending_medical_review'
		),
		'biostimulators'   => nvx_aesthetic_treatment_entry(
			'bioestimuladores-colageno-madrid',
			0,
			'Bioestimuladores de colágeno en Madrid',
			'Bioestimuladores de colágeno Madrid | NUVANX',
			'Valoración médica de bioestimulación con ácido poli-L-láctico o hidroxiapatita cálcica según calidad cutánea, anatomía y objetivo terapéutico.',
			'Medicina regenerativa estética · Madrid',
			'Los bioestimuladores no son una categoría uniforme. El ácido poli-L-láctico y la hidroxiapatita cálcica tienen composiciones, comportamiento tisular, técnicas y perfiles de manejo diferentes. La indicación comienza por la calidad cutánea y el soporte, no por una marca.',
			'La valoración estudia laxitud, espesor, distribución de volumen, calidad dérmica, zonas de movilidad, antecedentes de rellenos y capacidad de seguimiento. No toda flacidez responde a un inyectable y una pérdida estructural importante puede requerir otras técnicas.',
			'El ácido poli-L-láctico (PLLA) y la hidroxiapatita cálcica (CaHA) pueden inducir una respuesta de remodelación tisular progresiva, pero no son ácido hialurónico. La CaHA puede aportar además un efecto de soporte inmediato según formulación y plano; ninguno se describe como reversible con hialuronidasa.',
			array(
				'Pérdida seleccionada de calidad, densidad o firmeza cutánea.',
				'Laxitud leve o moderada cuando la anatomía permite un abordaje inyectable.',
				'Planes progresivos de remodelación tisular con expectativas realistas.',
			),
			array(
				'Inflamación o infección activa, embarazo, lactancia y antecedentes relevantes requieren valoración o aplazamiento.',
				'Zonas anatómicas, espesor cutáneo y productos previos condicionan material, dilución, plano o contraindicación.',
				'Los nódulos, granulomas y reacciones inflamatorias son riesgos que deben explicarse.',
				'No debe presentarse como tratamiento “sin volumen” de forma absoluta ni como resultado inmediato estandarizado.',
			),
			array(
				'Diagnóstico de calidad cutánea, laxitud, soporte y distribución de volumen.',
				'Selección explícita entre PLLA, CaHA u otra alternativa; explicación de diferencias y límites.',
				'Plan de aplicación por zonas y sesiones definido por el médico, sin calendario comercial rígido.',
				'Seguimiento progresivo para evaluar respuesta y descartar reacciones adversas.',
			),
			'Puede existir un cambio inicial relacionado con el vehículo, el edema o el soporte del producto, pero la remodelación tisular se evalúa de forma progresiva. El número de sesiones y los intervalos no se fijan antes del diagnóstico y pueden variar entre pacientes.',
			array(
				'Inflamación, hematoma, dolor, asimetría, irregularidades, infección o resultado no deseado.',
				'Nódulos, granulomas o reacciones inflamatorias que pueden requerir seguimiento y tratamiento.',
				'Compromiso vascular u otras complicaciones graves asociadas a procedimientos inyectables.',
			),
			array(
				'Puede formar parte de un plan que incluya tecnologías para superficie, laxitud o estructura, siempre de forma secuenciada.',
				'No se combinan materiales ni tecnologías sin definir el objetivo y el intervalo clínico de cada intervención.',
			),
			array(
				nvx_aesthetic_treatment_faq(
					'¿Radiesse® y Sculptra® son ácido hialurónico?',
					'No. Radiesse® se basa en hidroxiapatita cálcica y Sculptra® en ácido poli-L-láctico. Son materiales distintos del ácido hialurónico y requieren indicación, técnica y manejo propios.'
				),
				nvx_aesthetic_treatment_faq(
					'¿Los bioestimuladores pueden disolverse con hialuronidasa?',
					'No. La hialuronidasa actúa sobre ácido hialurónico; no ofrece el mismo mecanismo de reversión para hidroxiapatita cálcica o ácido poli-L-láctico.'
				),
				nvx_aesthetic_treatment_faq(
					'¿El resultado es inmediato?',
					'Puede existir un cambio inicial por el vehículo, el edema o el soporte del material, pero la respuesta de remodelación se valora progresivamente y varía entre pacientes.'
				),
				nvx_aesthetic_treatment_faq(
					'¿Cuántas sesiones necesito?',
					'No debe fijarse un número sin diagnóstico. El producto, la zona, la calidad cutánea, la respuesta y los objetivos determinan el plan y su revisión.'
				),
			),
			nvx_aesthetic_treatment_schema_payload(
				'Bioestimulación de colágeno con PLLA o CaHA',
				array( 'Bioestimuladores de colágeno', 'Remodelación tisular inyectable' ),
				'Rostro y zonas anatómicas seleccionadas',
				NVX_SCHEMA_MINIMALLY_INVASIVE,
				'Valoración de calidad cutánea, laxitud, soporte, volumen, tratamientos previos, medicación y capacidad de seguimiento.',
				'Aplicación médica de ácido poli-L-láctico o hidroxiapatita cálcica mediante técnica, dilución, plano y secuencia individualizados.',
				'Seguimiento progresivo de la respuesta tisular y vigilancia de inflamación, nódulos u otras complicaciones.',
				array( 'Pérdida de calidad y densidad cutánea', 'Laxitud cutánea leve o moderada seleccionada' ),
				array( 'Laxitud cutánea facial', 'Pérdida de densidad dérmica' )
			),
			'pending_medical_review'
		),
	);
}

/**
 * Whether catalogue content, SEO and schema may inject for this entry.
 *
 * Production: only approved_for_publication.
 * Staging2: also previews pending_medical_review (environment is noindex).
 */
function nvx_aesthetic_treatment_is_renderable( array $entry ): bool {
	$status = (string) ( $entry['review_status'] ?? '' );
	if ( 'approved_for_publication' === $status ) {
		return true;
	}
	return function_exists( 'nvx_environment_is_staging2' )
		&& nvx_environment_is_staging2()
		&& 'pending_medical_review' === $status;
}

/**
 * Catalogue slice for the_content matching.
 *
 * Pending entries are promoted to approved_for_publication only inside this
 * slice so the shared matcher can inject on staging2 without changing its default.
 *
 * @return array<string, array<string, mixed>>
 */
function nvx_aesthetic_treatment_catalog_for_render(): array {
	$out = array();
	foreach ( nvx_aesthetic_treatment_catalog() as $key => $entry ) {
		if ( ! nvx_aesthetic_treatment_is_renderable( $entry ) ) {
			continue;
		}
		if ( 'pending_medical_review' === (string) ( $entry['review_status'] ?? '' ) ) {
			$entry['review_status'] = 'approved_for_publication';
		}
		$out[ $key ] = $entry;
	}
	return $out;
}

/**
 * Pluck one field from every catalogue entry, keyed by treatment key.
 *
 * @return array<string, mixed>
 */
function nvx_aesthetic_treatment_pluck( string $field ): array {
	$result = array();
	foreach ( nvx_aesthetic_treatment_catalog() as $key => $entry ) {
		if ( array_key_exists( $field, $entry ) ) {
			$result[ $key ] = $entry[ $field ];
		}
	}
	return $result;
}

/** Resolve a treatment key from slug (does not apply render gate). */
function nvx_aesthetic_treatment_key_from_slug( string $slug ): ?string {
	$slug = trim( $slug, '/' );
	foreach ( nvx_aesthetic_treatment_catalog() as $key => $entry ) {
		if ( $slug === $entry['slug'] ) {
			return $key;
		}
	}
	return null;
}

/**
 * Current treatment key only when the entry is renderable (content + SEO + schema gate).
 */
function nvx_aesthetic_treatment_current_key(): ?string {
	if ( is_admin() || ! is_singular( 'page' ) ) {
		return null;
	}
	$slug = (string) get_post_field( 'post_name', get_queried_object_id() );
	$key  = nvx_aesthetic_treatment_key_from_slug( $slug );
	if ( null === $key ) {
		return null;
	}
	$catalog = nvx_aesthetic_treatment_catalog();
	$entry   = $catalog[ $key ] ?? null;
	if ( ! is_array( $entry ) || ! nvx_aesthetic_treatment_is_renderable( $entry ) ) {
		return null;
	}
	return $key;
}

/** @return array<string, array<int, array{q:string,a:string}>> */
function nvx_aesthetic_treatment_faq_catalog(): array {
	return nvx_aesthetic_treatment_pluck( 'faqs' );
}

/** @return array<string, array<string, mixed>> */
function nvx_aesthetic_treatment_schema_catalog(): array {
	return nvx_aesthetic_treatment_pluck( 'schema' );
}

nvx_register_catalog_content_filter( 'nvx_aesthetic_treatment_catalog_for_render', 80 );

/** Canonical SEO metadata for the four pages. */
function nvx_aesthetic_treatment_current_entry(): ?array {
	$key     = nvx_aesthetic_treatment_current_key();
	$catalog = nvx_aesthetic_treatment_catalog();
	return null !== $key && isset( $catalog[ $key ] ) ? $catalog[ $key ] : null;
}

/**
 * Read a field from the current treatment entry, or return the Yoast/WP fallback.
 *
 * @param mixed $fallback
 * @return mixed
 */
function nvx_aesthetic_treatment_meta_field( string $field, $fallback ) {
	$entry = nvx_aesthetic_treatment_current_entry();
	return null !== $entry && isset( $entry[ $field ] ) ? $entry[ $field ] : $fallback;
}

function nvx_aesthetic_treatment_filter_title( $title ) {
	return nvx_aesthetic_treatment_meta_field( 'seo_title', $title );
}
add_filter( 'wpseo_title', 'nvx_aesthetic_treatment_filter_title', 90 );
add_filter( 'wpseo_opengraph_title', 'nvx_aesthetic_treatment_filter_title', 90 );
add_filter( 'wpseo_twitter_title', 'nvx_aesthetic_treatment_filter_title', 90 );

function nvx_aesthetic_treatment_filter_description( $description ) {
	return nvx_aesthetic_treatment_meta_field( 'description', $description );
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
	$seo_title = nvx_aesthetic_treatment_meta_field( 'seo_title', null );
	if ( null !== $seo_title && '' !== (string) $seo_title ) {
		$parts['title'] = $seo_title;
	}
	return $parts;
}
add_filter( 'document_title_parts', 'nvx_aesthetic_treatment_document_title', 90 );

/**
 * Soft-sync meta on an existing staging page without overwriting body or status.
 */
function nvx_aesthetic_treatment_seed_sync_meta( int $post_id, string $key ): void {
	if ( $post_id <= 0 || '' === $key ) {
		return;
	}
	$existing_key = (string) get_post_meta( $post_id, '_nvx_aesthetic_treatment_key', true );
	if ( '' === $existing_key ) {
		update_post_meta( $post_id, '_nvx_aesthetic_treatment_key', $key );
	}
	$review = (string) get_post_meta( $post_id, '_nvx_medical_review_status', true );
	if ( '' === $review ) {
		update_post_meta( $post_id, '_nvx_medical_review_status', 'pending' );
	}
}

/** Seed the four pages only in staging2, which is globally noindex. */
function nvx_aesthetic_treatment_seed_staging_pages(): void {
	if ( ! function_exists( 'nvx_environment_is_staging2' ) || ! nvx_environment_is_staging2() ) {
		return;
	}

	foreach ( nvx_aesthetic_treatment_catalog() as $key => $entry ) {
		$page = get_page_by_path( $entry['slug'], OBJECT, 'page' );
		if ( $page instanceof WP_Post ) {
			nvx_aesthetic_treatment_seed_sync_meta( (int) $page->ID, $key );
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
			update_post_meta( (int) $post_id, '_nvx_aesthetic_treatment_key', $key );
			update_post_meta( (int) $post_id, '_nvx_medical_review_status', 'pending' );
		}
	}
}
add_action( 'init', 'nvx_aesthetic_treatment_seed_staging_pages', 30 );
