<?php
/**
 * BTL detail treatment pages: EXION Face / Body / Fractional RF + EMFUSION.
 *
 * Same editorial pattern as IPL EXILITE / CO₂: Hero → Mecanismo → Indicaciones →
 * Comparativa breve → Procedimiento → FAQ → CTA.
 * Does not replace hub /exion-btl/ or comparative blogs (linked as depth reading).
 *
 * Paths:
 *   /exion-face/
 *   /exion-body/
 *   /exion-fractional/
 *   /emfusion/
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Singular page context.
 */
function nvx_btl_detail_is_singular(): bool {
	if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return false;
	}
	return is_singular( 'page' ) || is_page();
}

/**
 * Registry of BTL detail pages (SEO + clinical copy).
 *
 * @return array<string, array<string, mixed>>
 */
function nvx_btl_detail_registry(): array {
	$blog_face   = home_url( '/exion-face-vs-hifu-ultherapy-thermage-regeneracion-endogena/' );
	$blog_body   = home_url( '/exion-body-vs-coolsculpting-morpheus8-lipolisis-retraccion/' );
	$blog_frac   = home_url( '/exion-fractional-vs-morpheus8-potenza-ia-vs-trauma/' );
	$blog_emf    = home_url( '/emfusion-vs-hydrafacial-dermapen-microcanales-acusticos/' );
	$blog_combo  = home_url( '/protocolos-combinados-ecosistema-nuvanx-exion-endolift-emfusion/' );
	$hub         = home_url( '/exion-btl/' );
	$endolaser   = home_url( '/endolaser-corporal-grasa-localizada/' );
	$endolift    = home_url( '/endolift-facial-papada-mandibula/' );

	return array(
		'exion-face'       => array(
			'path'         => '/exion-face/',
			'key'          => 'exion_face',
			'kicker'       => __( 'EXION® Face · NUVANX Madrid', 'nuvanx-medical' ),
			'h1'           => __( 'EXION® Face en Madrid: calidad de piel e hidratación con criterio médico', 'nuvanx-medical' ),
			'lead'         => __( 'Radiofrecuencia y ultrasonido terapéutico a temperaturas controladas para mejorar densidad e hidratación de la piel, cuando el diagnóstico lo indica. Chamberí y Goya.', 'nuvanx-medical' ),
			'meta'         => __( 'Chamberí · Goya · Alternativa a HIFU / RF de alto pico térmico', 'nuvanx-medical' ),
			'aria'         => __( 'EXION Face NUVANX', 'nuvanx-medical' ),
			'marker'       => 'nvx-exion-face',
			'yoast_title'  => 'EXION Face Madrid | Regeneración endógena facial | NUVANX',
			'yoast_desc'   => 'EXION® Face en NUVANX Madrid: RF + ultrasonido a 40–42 °C para calidad de piel e hidratación endógena. Valoración en Chamberí y Goya. Sin promesa de “lifting” milagroso.',
			'focuskw'      => 'EXION Face Madrid',
			'blog'         => $blog_face,
			'hub'          => $hub,
			'combo'        => $blog_combo,
			'schema_name'  => 'EXION® Face en Madrid',
			'schema_type'  => 'Protocolo médico facial con EXION® Face (RF monopolar + ultrasonido)',
			'schema_desc'  => 'Tratamiento médico de calidad de piel facial con plataforma EXION® Face: hipertermia controlada y ultrasonido terapéutico. Indicación tras valoración en NUVANX Madrid.',
			'mechanism'    => array(
				'title' => __( 'Cómo funciona EXION® Face', 'nuvanx-medical' ),
				'body'  => array(
					__( 'EXION Face combina radiofrecuencia monopolar y ultrasonido terapéutico en un protocolo orientado a estimular fibroblastos y matriz extracelular, no a necrosar tejido con picos térmicos de 60–70 °C.', 'nuvanx-medical' ),
					__( 'El rango de trabajo habitual (~40–42 °C en dermis) busca hipertermia controlada y reversible: activación de vías de estrés adaptativo (p. ej. HSPs) y señalización de síntesis de matriz, con ultrasonido como mecanoestimulación complementaria.', 'nuvanx-medical' ),
					__( 'Documentación del fabricante describe, en modelos evaluados, incrementos de marcadores de ácido hialurónico endógeno del orden del 224% a ~4 semanas; en consulta se presentan como potencial de estimulación, no como promesa personalizada.', 'nuvanx-medical' ),
				),
			),
			'indications'  => array(
				array( 'title' => __( 'Calidad e hidratación cutánea', 'nuvanx-medical' ), 'body' => __( 'Piel apagada, pérdida de turgencia y deshidratación dérmica cuando el diagnóstico apunta a déficit de matriz más que a exceso de grasa.', 'nuvanx-medical' ) ),
				array( 'title' => __( 'Firmeza leve–moderada', 'nuvanx-medical' ), 'body' => __( 'Descolgamiento incipiente de óvalo o cuello en pacientes que no son candidatos prioritarios a lifting quirúrgico ni a daño térmico agresivo.', 'nuvanx-medical' ) ),
				array( 'title' => __( 'Post-HIFU / RF de alto pico', 'nuvanx-medical' ), 'body' => __( 'Historial de plataformas de “quemadura controlada” con pérdida de volumen o textura rígida: se reevalúa anatomía y se prioriza regeneración sin agravar atrofia.', 'nuvanx-medical' ) ),
				array( 'title' => __( 'Mantenimiento well-aging', 'nuvanx-medical' ), 'body' => __( 'Planes de 3+ sesiones y mantenimiento anual/bianual según respuesta, integrables con EMFUSION® o inductores cuando el médico lo indique.', 'nuvanx-medical' ) ),
			),
			'compare'      => array(
				'title' => __( '¿En qué se diferencia de HIFU y Thermage®?', 'nuvanx-medical' ),
				'body'  => __( 'HIFU y RF volumétrica de alto pico buscan contracción por desnaturalización intensa. EXION Face prioriza regeneración a temperaturas más fisiológicas, con mejor tolerancia y menor downtime. La comparativa clínica ampliada está en el blog médico.', 'nuvanx-medical' ),
				'link'  => $blog_face,
				'label' => __( 'Leer: EXION Face vs HIFU y Thermage', 'nuvanx-medical' ),
			),
			'process'      => array(
				__( 'Valoración médica: fototipo, grasa facial, historial de HIFU/RF y expectativas.', 'nuvanx-medical' ),
				__( 'Protocolo habitual: 3 sesiones (~4 semanas). Casos avanzados: 4–5 o combinación (p. ej. EMFUSION®).', 'nuvanx-medical' ),
				__( 'Sesión ~30 min. Downtime mínimo o nulo en la mayoría de pacientes.', 'nuvanx-medical' ),
				__( 'Presupuesto cerrado tras indicación; sin catálogo de “precio milagro” online.', 'nuvanx-medical' ),
			),
			'faqs'         => array(
				array( 'q' => __( '¿Sustituye a los rellenos?', 'nuvanx-medical' ), 'a' => __( 'No automáticamente. Mejora matriz e hidratación; el volumen deficitario estructural puede seguir requiriendo inductores o ácido hialurónico inyectable según diagnóstico.', 'nuvanx-medical' ) ),
				array( 'q' => __( '¿Duele?', 'nuvanx-medical' ), 'a' => __( 'La mayoría describe calor tolerable (0–2/10). No es comparable al dolor habitual de HIFU de alta energía sin anestesia.', 'nuvanx-medical' ) ),
				array( 'q' => __( '¿Es seguro en fototipos altos?', 'nuvanx-medical' ), 'a' => __( 'Al trabajar con picos más fisiológicos, el riesgo de PIH suele ser inferior al de daño térmico agresivo; la indicación la marca el médico.', 'nuvanx-medical' ) ),
			),
		),
		'exion-body'       => array(
			'path'         => '/exion-body/',
			'key'          => 'exion_body',
			'kicker'       => __( 'EXION® Body · NUVANX Madrid', 'nuvanx-medical' ),
			'h1'           => __( 'EXION® Body en Madrid: lipólisis y retracción cutánea en un solo protocolo', 'nuvanx-medical' ),
			'lead'         => __( 'Radiofrecuencia monopolar con refrigeración activa para adiposidad localizada y laxitud asociada — sin el downtime de microagujas corporales ni la flacidez residual típica de solo congelar grasa.', 'nuvanx-medical' ),
			'meta'         => __( 'Chamberí · Goya · Contorno corporal con criterio médico', 'nuvanx-medical' ),
			'aria'         => __( 'EXION Body NUVANX', 'nuvanx-medical' ),
			'marker'       => 'nvx-exion-body',
			'yoast_title'  => 'EXION Body Madrid | Grasa localizada y tensado | NUVANX',
			'yoast_desc'   => 'EXION® Body en NUVANX: RF con refrigeración activa para adiposidad localizada y mejora de laxitud. Alternativa a CoolSculpting y Morpheus8 Body tras valoración médica.',
			'focuskw'      => 'EXION Body Madrid',
			'blog'         => $blog_body,
			'hub'          => $hub,
			'combo'        => $blog_combo,
			'schema_name'  => 'EXION® Body en Madrid',
			'schema_type'  => 'Protocolo médico corporal con EXION® Body',
			'schema_desc'  => 'Tratamiento de contorno corporal con EXION® Body: estímulo térmico con refrigeración epidérmica para grasa localizada y calidad cutánea en NUVANX Madrid.',
			'mechanism'    => array(
				'title' => __( 'Cómo funciona EXION® Body', 'nuvanx-medical' ),
				'body'  => array(
					__( 'El cabezal integra refrigeración activa de la superficie y radiofrecuencia monopolar profunda, de modo que la epidermis se protege mientras se deposita calor en hipodermis y dermis.', 'nuvanx-medical' ),
					__( 'En rangos de ~40–45 °C en tejido adiposo se busca apoptosis programada de adipocitos y, en paralelo, contracción y remodelado de colágeno — la “ecuación” grasa + piel que la criolipólisis sola no resuelve.', 'nuvanx-medical' ),
					__( 'Documentación BTL comunica órdenes de magnitud del tipo −22% adiposidad y mejoras relevantes de laxitud en series evaluadas; en NUVANX se individualiza por espesor graso, zona y calidad de piel.', 'nuvanx-medical' ),
				),
			),
			'indications'  => array(
				array( 'title' => __( 'Flancos y abdomen inferior', 'nuvanx-medical' ), 'body' => __( 'Adiposidad localizada con o sin flacidez leve–moderada, cuando no hay indicación prioritaria de abdominoplastia.', 'nuvanx-medical' ) ),
				array( 'title' => __( 'Brazos y muslos internos', 'nuvanx-medical' ), 'body' => __( 'Zonas donde perder grasa sin tensar deja piel “suelta”; se valora RF corporal vs láser intersticial según espesor.', 'nuvanx-medical' ) ),
				array( 'title' => __( 'Post-criolipólisis', 'nuvanx-medical' ), 'body' => __( 'Pacientes con grasa reducida pero laxitud residual: se reorienta el plan a retracción y calidad, no a más frío a ciegas.', 'nuvanx-medical' ) ),
				array( 'title' => __( 'Combinación con endoláser', 'nuvanx-medical' ), 'body' => __( 'En adiposidad >~4–5 cm puede proponerse laserlipólisis y, en fase posterior, EXION Body para consolidar tensado.', 'nuvanx-medical' ) ),
			),
			'compare'      => array(
				'title' => __( '¿CoolSculpting o Morpheus8 Body?', 'nuvanx-medical' ),
				'body'  => __( 'La criolipólisis reduce grasa pero no tensa. Las microagujas corporales tensan con más trauma y downtime. EXION Body busca ambos efectos con mejor tolerancia. Detalle comparativo en el blog.', 'nuvanx-medical' ),
				'link'  => $blog_body,
				'label' => __( 'Leer: EXION Body vs CoolSculpting y Morpheus8', 'nuvanx-medical' ),
			),
			'process'      => array(
				__( 'Diagnóstico de zona, pliegue, flacidez y expectativas realistas de contorno.', 'nuvanx-medical' ),
				__( 'Leve: 2–3 sesiones. Moderada: 3–4. Severa: valorar endoláser + EXION en secuencia.', 'nuvanx-medical' ),
				__( 'Sesión 45–60 min según áreas. Downtime habitual nulo o mínimo.', 'nuvanx-medical' ),
				__( 'Enlace clínico al protocolo de endoláser cuando el espesor lo exige.', 'nuvanx-medical' ),
			),
			'related'      => array(
				array( 'url' => $endolaser, 'label' => __( 'Endoláser corporal', 'nuvanx-medical' ) ),
			),
			'faqs'         => array(
				array( 'q' => __( '¿Elimina kilos?', 'nuvanx-medical' ), 'a' => __( 'No. Actúa sobre adiposidad localizada y calidad de piel, no sobre peso corporal global.', 'nuvanx-medical' ) ),
				array( 'q' => __( '¿Hay hematomas como con microagujas?', 'nuvanx-medical' ), 'a' => __( 'No es un sistema de perforación con agujas largas; el eritema, si aparece, suele resolverse en horas.', 'nuvanx-medical' ) ),
				array( 'q' => __( '¿Puedo hacer deporte?', 'nuvanx-medical' ), 'a' => __( 'En general sí tras la sesión; se individualizan las primeras 24–48 h según zona e intensidad.', 'nuvanx-medical' ) ),
			),
		),
		'exion-fractional' => array(
			'path'         => '/exion-fractional/',
			'key'          => 'exion_fractional',
			'kicker'       => __( 'EXION® Fractional RF · NUVANX Madrid', 'nuvanx-medical' ),
			'h1'           => __( 'EXION® Fractional RF en Madrid: radiofrecuencia fraccionada con control de impedancia', 'nuvanx-medical' ),
			'lead'         => __( 'Microagujas más cortas y gradiente térmico extendido con feedback de tejido — textura, poros y cicatrices con menos pasadas y downtime más predecible que la RF fraccionada “a ciegas”.', 'nuvanx-medical' ),
			'meta'         => __( 'Chamberí · Goya · Textura y remodelado dérmico', 'nuvanx-medical' ),
			'aria'         => __( 'EXION Fractional NUVANX', 'nuvanx-medical' ),
			'marker'       => 'nvx-exion-fractional',
			'yoast_title'  => 'EXION Fractional RF Madrid | Textura y cicatrices | NUVANX',
			'yoast_desc'   => 'EXION® Fractional RF en NUVANX Madrid: RF fraccionada con control de tejido y single-pass cuando el protocolo lo permite. Alternativa a Morpheus8/Potenza según diagnóstico.',
			'focuskw'      => 'EXION Fractional Madrid',
			'blog'         => $blog_frac,
			'hub'          => $hub,
			'combo'        => $blog_combo,
			'schema_name'  => 'EXION® Fractional RF en Madrid',
			'schema_type'  => 'Protocolo de radiofrecuencia fraccionada EXION®',
			'schema_desc'  => 'Remodelado de textura y dermis con EXION® Fractional RF en NUVANX Madrid tras valoración médica.',
			'mechanism'    => array(
				'title' => __( 'Cómo funciona EXION® Fractional RF', 'nuvanx-medical' ),
				'body'  => array(
					__( 'Emite radiofrecuencia a través de microagujas con control de entrega energética. El sistema incorpora feedback de impedancia para ajustar la coagulación al tejido real, no solo a un número fijo de pasadas “por si acaso”.', 'nuvanx-medical' ),
					__( 'Agujas más cortas con proyección térmica extendida permiten alcanzar profundidad de trabajo relevante reduciendo trauma mecánico superficial respecto a protocolos de aguja larga multipasada.', 'nuvanx-medical' ),
					__( 'Objetivo clínico: coagulación controlada de colágeno y renovación de textura (poros, cicatrices superficiales, arrugas finas) con plan de sesiones realista.', 'nuvanx-medical' ),
				),
			),
			'indications'  => array(
				array( 'title' => __( 'Textura y poro', 'nuvanx-medical' ), 'body' => __( 'Piel irregular, poro dilatado y falta de refinamiento óptico cuando no basta un peeling superficial.', 'nuvanx-medical' ) ),
				array( 'title' => __( 'Cicatrices de acné leves–moderadas', 'nuvanx-medical' ), 'body' => __( 'En atróficas profundas puede combinarse o priorizarse CO₂ fraccionado según profundidad y fototipo.', 'nuvanx-medical' ) ),
				array( 'title' => __( 'Arrugas finas y flacidez local', 'nuvanx-medical' ), 'body' => __( 'Remodelado dérmico progresivo; no sustituye un lifting cuando la ptosis es estructural.', 'nuvanx-medical' ) ),
				array( 'title' => __( 'Pacientes que no toleran multipasada agresiva', 'nuvanx-medical' ), 'body' => __( 'Historial de RF fraccionada con hematomas prolongados o abandono por dolor: se reevalúa energía y número de pases.', 'nuvanx-medical' ) ),
			),
			'compare'      => array(
				'title' => __( '¿Morpheus8 o Potenza?', 'nuvanx-medical' ),
				'body'  => __( 'Comparten familia tecnológica (RF fraccionada con microagujas), pero difieren en feedback, longitud de aguja, número de pasadas y tolerancia. El blog detalla la comparativa; en consulta manda el diagnóstico, no la marca.', 'nuvanx-medical' ),
				'link'  => $blog_frac,
				'label' => __( 'Leer: EXION Fractional vs Morpheus8 / Potenza', 'nuvanx-medical' ),
			),
			'process'      => array(
				__( 'Mapa de zonas, fototipo y objetivo (textura vs cicatriz vs firmeza).', 'nuvanx-medical' ),
				__( 'Sesiones según severidad (a menudo 2–4). Intervalos definidos en consulta.', 'nuvanx-medical' ),
				__( 'Downtime típico: eritema 12–48 h según energía; se explica antes de firmar el plan.', 'nuvanx-medical' ),
				__( 'Puede integrarse en protocolos combinados con Face o EMFUSION®.', 'nuvanx-medical' ),
			),
			'faqs'         => array(
				array( 'q' => __( '¿Es lo mismo que un láser CO₂?', 'nuvanx-medical' ), 'a' => __( 'No. El CO₂ vaporiza columnas de tejido (ablativo). Fractional RF coagula por calor con microagujas; downtime y profundidad se eligen distinto.', 'nuvanx-medical' ) ),
				array( 'q' => __( '¿Cuántas pasadas?', 'nuvanx-medical' ), 'a' => __( 'El diseño single-pass reduce pasadas innecesarias cuando el feedback de tejido es adecuado; el médico puede modular según zona.', 'nuvanx-medical' ) ),
				array( 'q' => __( '¿Anestesia?', 'nuvanx-medical' ), 'a' => __( 'Según energía y zona: tópica o protocolo de confort definido en valoración.', 'nuvanx-medical' ) ),
			),
		),
		'emfusion'         => array(
			'path'         => '/emfusion/',
			'key'          => 'emfusion',
			'kicker'       => __( 'EMFUSION® · NUVANX Madrid', 'nuvanx-medical' ),
			'h1'           => __( 'EMFUSION® en Madrid: infusión cutánea y restauración de barrera sin succión agresiva', 'nuvanx-medical' ),
			'lead'         => __( 'Tecnología DYNAMiQ™ de microcanales acústicos para favorecer la penetración de activos y apoyar la homeostasis epidérmica — alternativa a vórtices de succión y microneedling cuando la barrera está comprometida.', 'nuvanx-medical' ),
			'meta'         => __( 'Chamberí · Goya · Well-aging y piel sensible', 'nuvanx-medical' ),
			'aria'         => __( 'EMFUSION NUVANX', 'nuvanx-medical' ),
			'marker'       => 'nvx-emfusion',
			'yoast_title'  => 'EMFUSION Madrid | Barrera cutánea e infusión | NUVANX',
			'yoast_desc'   => 'EMFUSION® en NUVANX Madrid: microcanales acústicos DYNAMiQ™ para hidratación y barrera. Alternativa a Hydrafacial y Dermapen según indicación médica.',
			'focuskw'      => 'EMFUSION Madrid',
			'blog'         => $blog_emf,
			'hub'          => $hub,
			'combo'        => $blog_combo,
			'schema_name'  => 'EMFUSION® en Madrid',
			'schema_type'  => 'Protocolo de infusión cutánea EMFUSION®',
			'schema_desc'  => 'Infusión y soporte de barrera cutánea con EMFUSION® (DYNAMiQ™) en NUVANX Madrid tras valoración.',
			'mechanism'    => array(
				'title' => __( 'Cómo funciona EMFUSION® (DYNAMiQ™)', 'nuvanx-medical' ),
				'body'  => array(
					__( 'Convierte energía en ondas mecánicas que generan microcanales temporales en la superficie cutánea, facilitando la entrada de activos (p. ej. ceramidas, ectoína) sin el patrón de succión/vortex ni la perforación repetida del microneedling clásico.', 'nuvanx-medical' ),
					__( 'El objetivo no es “limpiar a presión”, sino apoyar la barrera y la hidratación cuando el diagnóstico muestra deshidratación, irritabilidad o necesidad de potenciar activos post-procedimiento.', 'nuvanx-medical' ),
					__( 'Datos de referencia del fabricante describen reducciones relevantes de pérdida de agua transepidérmica en modelos evaluados; se contextualizan en consulta sin cifras mágicas personalizadas.', 'nuvanx-medical' ),
				),
			),
			'indications'  => array(
				array( 'title' => __( 'Piel deshidratada / barrera frágil', 'nuvanx-medical' ), 'body' => __( 'Tirantez, descamación fina o sensibilidad donde la succión agresiva empeora la barrera.', 'nuvanx-medical' ) ),
				array( 'title' => __( 'Rosácea y piel reactiva (seleccionada)', 'nuvanx-medical' ), 'body' => __( 'Solo tras filtrar contraindicaciones; protocolos “sensitive” con activos calmantes.', 'nuvanx-medical' ) ),
				array( 'title' => __( 'Post-EXION / post-láser', 'nuvanx-medical' ), 'body' => __( 'Fase de consolidación de hidratación y barrera dentro de protocolos combinados NUVANX.', 'nuvanx-medical' ) ),
				array( 'title' => __( 'Fotodaño superficial', 'nuvanx-medical' ), 'body' => __( 'Como adyuvante de calidad cutánea, no como sustituto de CO₂ o Fractional cuando hay cicatriz profunda.', 'nuvanx-medical' ) ),
			),
			'compare'      => array(
				'title' => __( '¿Hydrafacial o Dermapen?', 'nuvanx-medical' ),
				'body'  => __( 'Hydrafacial prioriza limpieza por vortex/succión; el microneedling induce microlesiones para cicatrizar. EMFUSION se centra en infusión acústica y barrera. Amplía en el blog comparativo.', 'nuvanx-medical' ),
				'link'  => $blog_emf,
				'label' => __( 'Leer: EMFUSION vs Hydrafacial y Dermapen', 'nuvanx-medical' ),
			),
			'process'      => array(
				__( 'Valoración de barrera, inflamación y compatibilidad de activos.', 'nuvanx-medical' ),
				__( 'Protocolos tipo Hydration / Regeneration / Sensitive según caso.', 'nuvanx-medical' ),
				__( 'Sesiones cortas; downtime habitual mínimo.', 'nuvanx-medical' ),
				__( 'Frecuente como complemento de EXION Face o Fractional, no como monoterapia milagrosa.', 'nuvanx-medical' ),
			),
			'faqs'         => array(
				array( 'q' => __( '¿Sustituye a un facial cosmético?', 'nuvanx-medical' ), 'a' => __( 'Es un procedimiento médico de infusión/barrera dentro de un plan; no es un “glow” de cabina sin diagnóstico.', 'nuvanx-medical' ) ),
				array( 'q' => __( '¿Puedo maquillarme después?', 'nuvanx-medical' ), 'a' => __( 'Habitualmente sí en pocas horas; se indican cuidados según activos usados.', 'nuvanx-medical' ) ),
				array( 'q' => __( '¿Duele?', 'nuvanx-medical' ), 'a' => __( 'Sensación de vibración/calor leve en la mayoría de pacientes; no comparable a multipasada con aguja larga.', 'nuvanx-medical' ) ),
			),
		),
	);
}

/**
 * Resolve detail key from current request / content.
 *
 * @return string|null Registry key.
 */
function nvx_btl_detail_current_key( string $content = '' ): ?string {
	if ( ! nvx_btl_detail_is_singular() || is_front_page() || is_home() ) {
		return null;
	}

	// Never hijack posts (blogs share similar titles).
	if ( is_singular( 'post' ) ) {
		return null;
	}

	$path = function_exists( 'nvx_schema_current_path' )
		? nvx_schema_current_path( (int) get_queried_object_id() )
		: '';
	$path = is_string( $path ) ? $path : '';

	foreach ( nvx_btl_detail_registry() as $slug => $cfg ) {
		if ( function_exists( 'nvx_schema_path_matches' ) && nvx_schema_path_matches( $path, $cfg['path'] ) ) {
			return $slug;
		}
		if ( false !== strpos( $content, $cfg['marker'] . '-editorial' ) ) {
			return null; // already rebuilt
		}
		// Accept both canonical ids (nvx-exion-body-h1) and legacy double-prefixed ones.
		if (
			false !== strpos( $content, 'id="' . $cfg['marker'] . '-h1"' )
			|| false !== strpos( $content, "id='{$cfg['marker']}-h1'" )
			|| false !== strpos( $content, 'id="nvx-' . $cfg['marker'] . '-h1"' )
			|| false !== strpos( $content, "id='nvx-{$cfg['marker']}-h1'" )
		) {
			return $slug;
		}
	}

	$slug = (string) get_post_field( 'post_name', get_queried_object_id() );
	if ( isset( nvx_btl_detail_registry()[ $slug ] ) ) {
		return $slug;
	}

	return null;
}

/**
 * Build full editorial markup for a detail key.
 */
function nvx_btl_detail_page_markup( string $key ): string {
	$reg = nvx_btl_detail_registry();
	if ( empty( $reg[ $key ] ) ) {
		return '';
	}
	$c = $reg[ $key ];
	// Markers are already nvx-* (e.g. nvx-exion-body); do not prefix again.
	$id = $c['marker'];

	// Hero.
	$hero  = '<section class="nvx-brand-hero nvx-brand-hero--laser nvx-endolift-hero ' . esc_attr( $c['marker'] ) . '-hero" aria-labelledby="' . esc_attr( $id ) . '-h1" aria-label="' . esc_attr( $c['aria'] ) . '">';
	$hero .= '<div class="nvx-brand-hero__inner">';
	$hero .= '<div class="nvx-brand-hero__copy">';
	$hero .= '<p class="nvx-brand-kicker">' . esc_html( $c['kicker'] ) . '</p>';
	$hero .= '<h1 class="nvx-brand-hero__title" id="' . esc_attr( $id ) . '-h1">' . esc_html( $c['h1'] ) . '</h1>';
	$hero .= '<p class="nvx-brand-hero__lead">' . esc_html( $c['lead'] ) . '</p>';
	if ( function_exists( 'nvx_cta_pair_markup' ) ) {
		$hero .= nvx_cta_pair_markup( $c['marker'] . '-hero-ctas nvx-home-hero-ctas' );
	}
	$hero .= '<p class="nvx-brand-meta">' . esc_html( $c['meta'] ) . '</p>';
	$hero .= '</div></div></section>';

	$body  = '<div class="' . esc_attr( $c['marker'] ) . '-editorial nvx-endolift-editorial nvx-btl-detail-editorial">';

	// Mechanism.
	$body .= '<section class="nvx-endolift-section" aria-labelledby="' . esc_attr( $id ) . '-mech">';
	$body .= '<div class="nvx-endolift-section__inner">';
	$body .= '<p class="nvx-endolift-kicker">' . esc_html__( 'Mecanismo', 'nuvanx-medical' ) . '</p>';
	$body .= '<h2 id="' . esc_attr( $id ) . '-mech" class="nvx-endolift-heading">' . esc_html( $c['mechanism']['title'] ) . '</h2>';
	foreach ( $c['mechanism']['body'] as $p ) {
		$body .= '<p class="nvx-endolift-body nvx-endolift-body--measure">' . esc_html( $p ) . '</p>';
	}
	$body .= '<p class="nvx-endolift-body"><a class="nvx-brand-inline-link" href="' . esc_url( $c['hub'] ) . '">' . esc_html__( 'Ver plataforma EXION® BTL (hub)', 'nuvanx-medical' ) . '</a></p>';
	$body .= '</div></section>';

	// Indications.
	$body .= '<section class="nvx-endolift-section" aria-labelledby="' . esc_attr( $id ) . '-ind">';
	$body .= '<div class="nvx-endolift-section__inner">';
	$body .= '<p class="nvx-endolift-kicker">' . esc_html__( 'Indicaciones', 'nuvanx-medical' ) . '</p>';
	$body .= '<h2 id="' . esc_attr( $id ) . '-ind" class="nvx-endolift-heading">' . esc_html__( 'Cuándo tiene sentido este protocolo', 'nuvanx-medical' ) . '</h2>';
	$body .= '<ul class="nvx-endolaser-zone-list">';
	foreach ( $c['indications'] as $item ) {
		$body .= '<li class="nvx-endolaser-zone">';
		$body .= '<h3 class="nvx-endolaser-zone__title">' . esc_html( $item['title'] ) . '</h3>';
		$body .= '<p class="nvx-endolift-body">' . esc_html( $item['body'] ) . '</p>';
		$body .= '</li>';
	}
	$body .= '</ul></div></section>';

	// Compare + blog depth (strategy: internal link to money content).
	$body .= '<section class="nvx-endolift-section" aria-labelledby="' . esc_attr( $id ) . '-cmp">';
	$body .= '<div class="nvx-endolift-section__inner">';
	$body .= '<p class="nvx-endolift-kicker">' . esc_html__( 'Criterio diferencial', 'nuvanx-medical' ) . '</p>';
	$body .= '<h2 id="' . esc_attr( $id ) . '-cmp" class="nvx-endolift-heading">' . esc_html( $c['compare']['title'] ) . '</h2>';
	$body .= '<p class="nvx-endolift-body nvx-endolift-body--measure">' . esc_html( $c['compare']['body'] ) . '</p>';
	$body .= '<p class="nvx-endolift-body"><a class="nvx-brand-inline-link" href="' . esc_url( $c['compare']['link'] ) . '">' . esc_html( $c['compare']['label'] ) . '</a>';
	if ( ! empty( $c['combo'] ) ) {
		$body .= ' · <a class="nvx-brand-inline-link" href="' . esc_url( $c['combo'] ) . '">' . esc_html__( 'Protocolos combinados NUVANX', 'nuvanx-medical' ) . '</a>';
	}
	$body .= '</p>';
	if ( ! empty( $c['related'] ) ) {
		foreach ( $c['related'] as $rel ) {
			$body .= '<p class="nvx-endolift-body"><a class="nvx-brand-inline-link" href="' . esc_url( $rel['url'] ) . '">' . esc_html( $rel['label'] ) . '</a></p>';
		}
	}
	$body .= '</div></section>';

	// Process.
	$body .= '<section class="nvx-endolift-section" aria-labelledby="' . esc_attr( $id ) . '-proc">';
	$body .= '<div class="nvx-endolift-section__inner">';
	$body .= '<p class="nvx-endolift-kicker">' . esc_html__( 'Proceso médico', 'nuvanx-medical' ) . '</p>';
	$body .= '<h2 id="' . esc_attr( $id ) . '-proc" class="nvx-endolift-heading">' . esc_html__( 'Procedimiento, sesiones y cuidados', 'nuvanx-medical' ) . '</h2>';
	$body .= '<ol class="nvx-endolaser-zone-list">';
	foreach ( $c['process'] as $step ) {
		$body .= '<li class="nvx-endolaser-zone"><p class="nvx-endolift-body">' . esc_html( $step ) . '</p></li>';
	}
	$body .= '</ol></div></section>';

	// FAQ.
	$body .= '<section class="nvx-endolift-section" aria-labelledby="' . esc_attr( $id ) . '-faq">';
	$body .= '<div class="nvx-endolift-section__inner">';
	$body .= '<p class="nvx-endolift-kicker">' . esc_html__( 'FAQ', 'nuvanx-medical' ) . '</p>';
	$body .= '<h2 id="' . esc_attr( $id ) . '-faq" class="nvx-endolift-heading">' . esc_html__( 'Preguntas frecuentes', 'nuvanx-medical' ) . '</h2>';
	$body .= '<div class="nvx-faq nvx-brand-faq-accordion">';
	foreach ( $c['faqs'] as $faq ) {
		$body .= '<details class="nvx-brand-faq-item">';
		$body .= '<summary>' . esc_html( $faq['q'] ) . '</summary>';
		$body .= '<div class="nvx-brand-faq-content"><p>' . esc_html( $faq['a'] ) . '</p></div>';
		$body .= '</details>';
	}
	$body .= '</div></div></section>';

	// Closing valoración CTA: site-wide nvx-cta-banner in footer.php (not page-local).

	$body .= '</div>';

	return $hero . $body;
}

/**
 * Restructure the_content for BTL detail pages.
 */
function nvx_content_restructure_btl_detail_page( string $content ): string {
	$key = nvx_btl_detail_current_key( $content );
	if ( null === $key ) {
		return $content;
	}

	$cfg = nvx_btl_detail_registry()[ $key ];
	if ( false !== strpos( $content, $cfg['marker'] . '-editorial' ) ) {
		return $content;
	}

	// Same media sources as Endolift / Endoláser / CO₂: content figure, then featured image.
	$media = '';
	if ( preg_match( '/<figure class="nvx-brand-hero__media"[\s\S]*?<\/figure>/iu', $content, $m ) ) {
		$media = $m[0];
	} elseif ( preg_match( '/<div class="nvx-brand-hero__media"[\s\S]*?<\/div>/iu', $content, $m ) ) {
		$media = $m[0];
	} elseif ( has_post_thumbnail() ) {
		$thumb = get_the_post_thumbnail(
			null,
			'full',
			array(
				'class'   => 'nvx-media nvx-media--hero wp-post-image',
				'alt'     => the_title_attribute( array( 'echo' => false ) ),
				'loading' => 'eager',
			)
		);
		if ( is_string( $thumb ) && '' !== $thumb ) {
			$media = '<figure class="nvx-brand-hero__media">' . $thumb . '</figure>';
		}
	}

	$built = nvx_btl_detail_page_markup( $key );
	// Inject media into hero if present (after copy, inside __inner).
	if ( '' !== $media && false !== strpos( $built, 'nvx-brand-hero__inner' ) ) {
		$built = preg_replace(
			'/(class="nvx-brand-hero__inner">[\s\S]*?<\/div>)(\s*<\/div>\s*<\/section>)/u',
			'$1' . $media . '$2',
			$built,
			1
		) ?? $built;
	}

	if ( preg_match( '/(<div class="nvx-brand-page[^"]*"[^>]*>)/iu', $content, $wrap ) ) {
		$open = $wrap[1];
		$mod  = 'nvx-brand-page--' . sanitize_html_class( $key );
		if ( false === strpos( $open, $mod ) ) {
			$open = preg_replace( '/\bclass=(["\'])/u', 'class=$1' . $mod . ' ', $open, 1 ) ?? $open;
		}
		return $open . $built . '</div>';
	}

	return '<div class="nvx-brand-page nvx-brand-page--' . esc_attr( $key ) . '">' . $built . '</div>';
}
add_filter( 'the_content', 'nvx_content_restructure_btl_detail_page', 19 );

/**
 * Yoast title for BTL detail pages.
 *
 * @param string $title Title.
 * @return string
 */
function nvx_filter_btl_detail_title( $title ) {
	$key = nvx_btl_detail_current_key( '' );
	if ( null === $key ) {
		return $title;
	}
	return nvx_btl_detail_registry()[ $key ]['yoast_title'];
}
add_filter( 'wpseo_title', 'nvx_filter_btl_detail_title', 21 );

/**
 * Yoast metadesc for BTL detail pages.
 *
 * @param string $desc Description.
 * @return string
 */
function nvx_filter_btl_detail_metadesc( $desc ) {
	$key = nvx_btl_detail_current_key( '' );
	if ( null === $key ) {
		return $desc;
	}
	return nvx_btl_detail_registry()[ $key ]['yoast_desc'];
}
add_filter( 'wpseo_metadesc', 'nvx_filter_btl_detail_metadesc', 21 );
