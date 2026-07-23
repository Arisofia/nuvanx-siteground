<?php
/**
 * Soluciones clínicas problema-primero (Papada y Mandíbula, etc.).
 *
 * Usa el renderer de matriz de 13 puntos para páginas de solución específicas,
 * alineadas con los documentos maestros de contenido (P10, P11, etc.).
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Catálogo de páginas de solución clínica (inicialmente Papada y Mandíbula).
 *
 * Cada entrada se ajusta al patrón de 13 puntos:
 * - h1, seo_title, description, kicker, lead
 * - diagnosis_heading, diagnosis
 * - mechanism_heading, mechanism
 * - indications_heading, indications[]
 * - precautions_heading, precautions[]
 * - process_heading, process[]
 * - faqs[]
 */
function nvx_solution_pages_catalog(): array {
	return array(
		'papada_mandibular' => array(
			'slug'                => 'papada-definicion-mandibular-madrid',
			'seo_title'           => 'Eliminar papada sin cirugía en Madrid | Definición Mandibular',
			'description'         => 'Valoración médica para papada y marcación mandibular. Diferenciamos grasa, flacidez y estructura ósea antes de indicar Endolift® o radiofrecuencia.',
			'kicker'              => 'SOLUCIONES MÉDICAS: ROSTRO Y CUELLO',
			'h1'                  => 'Tratamiento médico de papada y definición mandibular en Madrid.',
			'lead'                => 'El perfil facial no se mejora aplicando la misma máquina a todo el mundo. El primer paso es un diagnóstico anatómico preciso para diferenciar si el problema es un exceso de grasa, pérdida de tensión en el tejido o falta de soporte estructural óseo.',
			'diagnosis_heading'   => 'Grasa, laxitud o estructura: el diagnóstico lo es todo.',
			'diagnosis'           => 'La mayoría de las insatisfacciones en medicina estética facial ocurren por tratar el problema equivocado. Un paciente puede consultar para "quitar la papada", pero la anatomía revela tres realidades muy distintas: exceso de grasa submentoniana, laxitud cutánea sin grasa significativa o déficit estructural óseo (retrognatia). Si aplicamos tecnología para destruir grasa en un cuello que solo tiene flacidez, el resultado será un empeoramiento visible. Por eso, en NUVANX no vendemos tratamientos por teléfono: diagnosticamos primero.',
			'mechanism_heading'   => 'Tecnologías y vías de actuación clínica',
			'mechanism'           => 'Bajo el paraguas de nuestro protocolo NUVANX Profile Definition™, el Dr. Rivera y el equipo médico seleccionan la herramienta adecuada para la causa de tu alteración. Para grasa localizada y retracción profunda, puede indicarse Endolift® facial (láser intersticial 1470nm) capaz de licuar pequeños depósitos adiposos y tensar el tejido desde el interior. Para laxitud cutánea de moderada a severa, se utilizan protocolos de redensificación con EXION® Face o radiofrecuencia fraccionada. Para falta de soporte (ángulo y mentón), pueden plantearse inyectables estructurales de ácido hialurónico de alta densidad para proyectar el mentón y tensar mecánicamente el reborde mandibular.',
			'indications_heading' => 'Qué podemos tratar',
			'indications'         => array(
				'Exceso de grasa submentoniana susceptible de láser intersticial cuando la exploración lo confirma.',
				'Laxitud cutánea leve o moderada en el cuello, con capacidad elástica residual.',
				'Definición mandibular disminuida cuando el soporte óseo permite un abordaje médico-estético.',
			),
			'precautions_heading' => 'Cuándo no está indicado un tratamiento estético',
			'precautions'         => array(
				'Exceso importante de piel redundante ("cuello de pavo" severo), cuya solución real es un lifting cérvico-facial quirúrgico.',
				'Volumen cervical debido a hipertrofia glandular o alteraciones del tiroides, que requieren valoración maxilofacial o endocrinológica.',
				'Expectativas de mandíbula hipermasculinizada o cambios faciales desproporcionados respecto a tu anatomía.',
			),
			'process_heading'     => 'Tu primera valoración clínica',
			'process'             => array(
				'Exploración de papada, cuello y mandíbula como un mismo perfil clínico.',
				'Diferenciación de grasa, laxitud y soporte óseo antes de hablar de tecnología.',
				'Entrega de un presupuesto cerrado por escrito sólo cuando existe una indicación médica razonable.',
			),
			'faqs'                => array(
				array(
					'q' => '¿Siempre se trata la papada con láser?',
					'a' => 'No. Si el problema predominante es óseo o de piel sin grasa susceptible de tratamiento, explicamos por qué la tecnología no aportaría el resultado que buscas y planteamos alternativas o derivación.',
				),
				array(
					'q' => '¿Es una alternativa a la liposucción de papada?',
					'a' => 'En casos seleccionados puede ser una alternativa menos invasiva para tratar depósitos grasos submentonianos y tensar la piel, pero no sustituye a cirugía cuando el exceso de piel o la estructura lo requieren.',
				),
				array(
					'q' => '¿Qué ocurre si sólo tengo flacidez en el cuello?',
					'a' => 'Cuando la exploración revela flacidez sin grasa significativa, se priorizan protocolos de inducción de colágeno y tensado superficial en lugar de procedimientos de lipólisis.',
				),
			),
		),
		'calidad_piel' => array(
			'slug'                => 'calidad-piel-firmeza-luminosidad-madrid',
			'seo_title'           => 'Tratamiento médico para firmeza y calidad de piel | Madrid',
			'description'         => 'Redensificación dérmica y well-aging. Abordaje médico de la laxitud, tono irregular y deshidratación profunda con tecnología avanzada en Madrid.',
			'kicker'              => 'SOLUCIONES MÉDICAS: CALIDAD CUTÁNEA',
			'h1'                  => 'Tratamiento médico para firmeza, densidad y calidad cutánea.',
			'lead'                => 'Una piel sana, densa y luminosa es el cimiento de la medicina estética (Well-Aging). Sin una buena estructura dérmica, los rellenos y neuromoduladores pierden naturalidad.',
			'diagnosis_heading'   => 'El envejecimiento no es solo "arrugas". Es pérdida de arquitectura.',
			'diagnosis'           => 'La medicina estética tradicional se obsesionó durante años con rellenar surcos y paralizar músculos, olvidando el lienzo: la propia piel. Con la edad, los fibroblastos disminuyen su actividad, la piel se adelgaza, pierde su capacidad de retener agua y cede a la gravedad. Tratar esta condición requiere estimular biológicamente a las células para que vuelvan a trabajar, no solo estirar la superficie.',
			'mechanism_heading'   => 'Diagnóstico y componentes de la calidad de piel',
			'mechanism'           => 'En tu primera valoración, el equipo clínico evalúa los tres pilares de una piel sana: firmeza y elasticidad (red de colágeno y elastina), hidratación y volumen tisular (ácido hialurónico natural) e integridad de la barrera (tono homogéneo y resistencia a agresiones externas).',
			'indications_heading' => 'NUVANX Skin Architecture™: Recuperando la matriz dérmica',
			'indications'         => array(
				'Pérdida de firmeza y densidad cutánea con signos de adelgazamiento dérmico.',
				'Deshidratación profunda y piel apagada que no mejora sólo con cosmética de superficie.',
				'Alteraciones de textura y tono asociadas al envejecimiento cronológico o fotoenvejecimiento.',
			),
			'precautions_heading' => 'Cuándo no está indicado un abordaje exclusivamente estético',
			'precautions'         => array(
				'Patologías dermatológicas activas que requieren tratamiento específico (p. ej. dermatitis severa, procesos inflamatorios activos).',
				'Expectativa de "borrar arrugas al instante" sin aceptar el tiempo biológico de remodelación.',
				'Anatomías donde el déficit principal es óseo o volumétrico profundo, que requieren otros protocolos antes de centrarse en matriz dérmica.',
			),
			'process_heading'     => 'Tu primera valoración clínica',
			'process'             => array(
				'Exploración de firmeza, hidratación y estado de la barrera cutánea en rostro o zonas seleccionadas.',
				'Selección de la combinación terapéutica (EXION® Face, bioestimulación, EMFUSION® o mesoterapia médica) según diagnóstico.',
				'Diseño de un plan de Well-Aging a medio plazo, con sesiones y seguimiento adaptados a tu biología y a tus objetivos.',
			),
			'faqs'                => array(
				array(
					'q' => '¿En cuánto tiempo se nota la mejora de la calidad de piel?',
					'a' => 'Los cambios en matriz dérmica son progresivos. En general se aprecia mejoría en firmeza y luminosidad en semanas, consolidándose en meses según el protocolo y tu biología.',
				),
				array(
					'q' => '¿Es un tratamiento de una sola sesión?',
					'a' => 'Habitualmente requiere varias sesiones y un mantenimiento periódico. No es un "facial exprés" de spa, sino un plan médico de Well-Aging estructural.',
				),
				array(
					'q' => '¿Puedo combinarlo con otros tratamientos estéticos?',
					'a' => 'Sí, pero siempre con secuencia médica. Mejorar la matriz cutánea suele ser la base sobre la que se planifican otros procedimientos de volumen o neuromodulación.',
				),
			),
		),
		'cicatrices_textura' => array(
			'slug'                => 'cicatrices-acne-poros-textura-madrid',
			'seo_title'           => 'Tratamiento de cicatrices, acné y poros dilatados | Madrid',
			'description'         => 'Renovación de la superficie cutánea. Tratamiento médico de cicatrices atróficas, estrías y textura irregular con láser CO₂ y radiofrecuencia fraccionada.',
			'kicker'              => 'SOLUCIONES MÉDICAS: SUPERFICIE CUTÁNEA',
			'h1'                  => 'Tratamiento médico de cicatrices, poros dilatados y textura cutánea.',
			'lead'                => 'Abordaje avanzado de las alteraciones topográficas de la piel: cicatrices de acné, marcas quirúrgicas, poros dilatados y estrías.',
			'diagnosis_heading'   => 'La topografía de la piel requiere precisión focal',
			'diagnosis'           => 'La superficie de la piel puede presentar desniveles por exceso (cicatrices hipertróficas) o por defecto (cicatrices atróficas de acné, poros muy dilatados). Antes de aplicar un láser, el médico clasifica la lesión, mide su profundidad, evalúa la fase inflamatoria (si la hubiera) y determina el fototipo (color de piel) del paciente. Tratar una cicatriz profunda en pieles oscuras exige parámetros y tecnologías muy diferentes a las utilizadas en pieles claras, para evitar riesgos de hiperpigmentación postinflamatoria.',
			'mechanism_heading'   => 'Tecnologías de resurfacing y remodelación',
			'mechanism'           => array(
				'Láser CO₂ Fraccionado: El estándar de oro para el resurfacing severo. Vaporiza fracciones de piel dañada, creando columnas térmicas que regeneran el tejido desde cero. Ideal para cicatrices profundas de acné y fotoenvejecimiento avanzado.',
				'EXION® Fractional RF: Radiofrecuencia fraccionada con microagujas asistidas por inteligencia artificial (IA). Permite llegar a capas profundas sin sobrecalentar la epidermis. Excelente para estrías, poros dilatados y pieles oscuras con riesgo de manchas.',
			),
			'indications_heading' => 'Qué podemos abordar con resurfacing y RF',
			'indications'         => array(
				'Cicatrices atróficas post-acné y marcas quirúrgicas deprimidas.',
				'Poros significativamente dilatados y textura cutánea irregular persistente.',
				'Estrías (especialmente eficaces en su fase vascular roja, aunque se mejora la textura en la fase alba).',
			),
			'precautions_heading' => 'Límites y tiempos de recuperación realistas',
			'precautions'         => array(
				'La honestidad por delante: Un tratamiento ablativo profundo requerirá entre 3 y 7 días de baja social (enrojecimiento agudo, descamación, costras puntiformes). No ocultamos este hecho.',
				'Tolerancia: Aplicamos anestesia tópica de grado magistral para controlar las molestias durante la sesión.',
				'Límites: Las estrías antiguas (blancas) y las cicatrices atróficas profundas mejoran notablemente su aspecto y textura, pero la medicina actual no las borra al 100%. Te explicaremos el porcentaje de mejora realista esperado para tu caso.',
			),
			'process_heading'     => 'Tu primera valoración clínica',
			'process'             => array(
				'Evaluación de la topografía, profundidad y fototipo.',
				'Definición de expectativas reales de mejora porcentual.',
				'Planificación del procedimiento, tiempos de recuperación social necesarios y cuidados pre/post.',
			),
		),
		'manchas_rojeces' => array(
			'slug'                => 'manchas-rojeces-fotorejuvenecimiento-ipl-madrid',
			'seo_title'           => 'Tratamiento médico de manchas, rojeces e IPL en Madrid',
			'description'         => 'Fotorejuvenecimiento e IPL médico en Madrid. Tratamiento de léntigos solares, rosácea, telangiectasias y fotodaño bajo estricta indicación clínica.',
			'kicker'              => 'SOLUCIONES MÉDICAS: CORRECCIÓN DE TONO',
			'h1'                  => 'Tratamiento médico de manchas, rojeces y daño solar.',
			'lead'                => 'Fotorejuvenecimiento y corrección del tono de la piel. Abordamos clínicamente el daño solar acumulado, los léntigos, la rosácea y las arañas vasculares.',
			'diagnosis_heading'   => 'No todas las manchas se tratan igual (y algunas no deben tratarse)',
			'diagnosis'           => 'El mayor error en el tratamiento de manchas es abordarlas todas con la misma máquina como si fueran iguales. El melasma de origen hormonal, por ejemplo, puede empeorar gravemente si se somete a ciertas fuentes de calor intenso, mientras que el léntigo solar responde excelentemente bien. Más importante aún: Ciertas lesiones pigmentadas irregulares y sospechosas requieren una valoración dermatológica estricta y biopsia. En NUVANX, la seguridad clínica precede a la estética. Si una lesión nos genera la más mínima sospecha oncológica, detendremos el proceso y te derivaremos a dermatología inmediatamente.',
			'mechanism_heading'   => 'Luz Pulsada Intensa (IPL) de grado médico',
			'mechanism'           => array(
				'Lesiones Pigmentarias (Manchas solares, léntigos, pecas): La energía lumínica impacta en la melanina concentrada, rompiendo el pigmento que posteriormente la piel descama de forma natural en unos días.',
				'Lesiones Vasculares (Rojeces, cuperosis, rosácea, arañas vasculares): La energía lumínica es absorbida por la hemoglobina de los pequeños capilares dilatados, sellándolos mediante fotocoagulación térmica sin dañar la piel circundante.',
			),
			'indications_heading' => 'El efecto fotorejuvenecimiento',
			'indications'         => array(
				'Al tratar de forma combinada el componente vascular (rojo) y el pigmentario (marrón), la piel recupera una apariencia "limpia", traslúcida y de tono uniforme (efecto glass-skin).',
				'El efecto térmico residual de la luz estimula sutilmente la producción de nuevo colágeno a nivel superficial, mejorando la luminosidad global.',
				'Dependiendo de las lesiones, pueden requerirse múltiples sesiones espaciadas.',
			),
			'precautions_heading' => 'Límites estrictos y contraindicaciones',
			'precautions'         => array(
				'Pacientes bronceados recientemente o con exposición solar prevista inmediatamente después del tratamiento.',
				'Tipos específicos de melasma que requieren abordajes farmacológicos o de baja energía térmica.',
				'Lesiones sospechosas de malignidad que requieren biopsia.',
			),
			'process_heading'     => 'Tu primera valoración clínica',
			'process'             => array(
				'Evaluación del fototipo y diagnóstico diferencial del tipo de pigmentación o alteración vascular.',
				'Determinación de viabilidad para fotorejuvenecimiento.',
				'Prescripción de la preparación pre-tratamiento y pautas estrictas de fotoprotección.',
			),
		),
	);
}

/** Identifica la solución actual por slug de la página. */
function nvx_solution_current_key(): ?string {
	if ( is_admin() || ! is_page() ) {
		return null;
	}
	$slug = (string) get_post_field( 'post_name', get_queried_object_id() );
	foreach ( nvx_solution_pages_catalog() as $key => $entry ) {
		if ( isset( $entry['slug'] ) && $entry['slug'] === $slug ) {
			return $key;
		}
	}
	return null;
}

/** Renderiza la página de solución actual usando el patrón de 13 puntos. */
function nvx_solution_render( string $key ): string {
	$catalog = nvx_solution_pages_catalog();
	if ( empty( $catalog[ $key ] ) ) {
		return '';
	}
	return nvx_render_13_point_matrix( $catalog[ $key ] );
}

/** Filtro de contenido: sustituye el contenido de la página de solución por el markup gobernado. */
function nvx_solution_filter_content( string $content ): string {
	if ( is_admin() || ! is_main_query() || ! in_the_loop() ) {
		return $content;
	}
	$key = nvx_solution_current_key();
	if ( null === $key ) {
		return $content;
	}
	$markup = nvx_solution_render( $key );
	return '' === $markup ? $content : $markup;
}
add_filter( 'the_content', 'nvx_solution_filter_content', 81 );
