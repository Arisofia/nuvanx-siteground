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
			'h1'           => __( 'EXION® Face: regeneración endógena de colágeno, ácido hialurónico y elastina', 'nuvanx-medical' ),
			'lead'         => __( 'Sinergia de radiofrecuencia monopolar y ultrasonido dirigido para reactivar la matriz cutánea cuando el diagnóstico lo indica. Sin inyecciones, sin cirugía y con recuperación habitualmente mínima. Chamberí y Salamanca–Goya.', 'nuvanx-medical' ),
			'meta'         => __( 'Chamberí · Goya · Indicación médica personalizada', 'nuvanx-medical' ),
			'aria'         => __( 'EXION Face NUVANX', 'nuvanx-medical' ),
			'marker'       => 'nvx-exion-face',
			'yoast_title'  => 'EXION Face Madrid | RF + ultrasonido | Regeneración facial | NUVANX',
			'yoast_desc'   => 'EXION® Face en NUVANX Madrid: radiofrecuencia y ultrasonido a microtemperaturas controladas para calidad de piel e hidratación endógena. Valoración en Chamberí y Goya.',
			'focuskw'      => 'EXION Face Madrid',
			'blog'         => $blog_face,
			'hub'          => $hub,
			'combo'        => $blog_combo,
			'schema_name'  => 'EXION® Face en Madrid',
			'schema_type'  => 'Protocolo médico facial con EXION® Face (RF monopolar + ultrasonido)',
			'schema_desc'  => 'Tratamiento médico de calidad de piel facial con plataforma EXION® Face: hipertermia controlada y ultrasonido terapéutico. Indicación tras valoración en NUVANX Madrid.',
			'mechanism'    => array(
				'title' => __( 'Cómo funciona EXION® Face: doble acción biomecánica', 'nuvanx-medical' ),
				'body'  => array(
					// Shared with clinical governance (nvx_btl_claim_library).
					function_exists( 'nvx_btl_claim_source' ) ? nvx_btl_claim_source( 'exion_face_mech_intro' ) : '',
					function_exists( 'nvx_btl_claim_source' ) ? nvx_btl_claim_source( 'exion_face_ha_224' ) : '',
				),
				'items' => array(
					array(
						'title' => __( 'Radiofrecuencia monopolar (~40–42 °C en dermis)', 'nuvanx-medical' ),
						'body'  => __( 'Busca hipertermia controlada y reversible: activación de vías de estrés adaptativo (p. ej. HSPs), señalización de síntesis de matriz y aporte energético celular. Objetivo: remodelado de colágeno de calidad, no cicatricial. El rango térmico es orientativo de protocolo, no una temperatura universal en cada paciente.', 'nuvanx-medical' ),
					),
					array(
						'title' => __( 'Ultrasonido dirigido (TUS)', 'nuvanx-medical' ),
						'body'  => __( 'Mecanoestimulación no invasiva que complementa la RF: favorece cascadas de señalización en fibroblastos y síntesis de matriz (ácido hialurónico, colágeno, elastina) sin el perfil de cavitación de ultrasonidos de alto poder. Se usa como estímulo, no como trauma controlado.', 'nuvanx-medical' ),
					),
				),
			),
			'indications'  => array(
				array( 'title' => __( 'Envejecimiento cronológico leve–moderado', 'nuvanx-medical' ), 'body' => __( 'Pérdida progresiva de densidad y turgencia. EXION Face puede contribuir a redensificar la dermis cuando el diagnóstico apunta a déficit de matriz más que a exceso de grasa o ptosis severa.', 'nuvanx-medical' ) ),
				array( 'title' => __( 'Deshidratación y piel apagada', 'nuvanx-medical' ), 'body' => __( 'Cuando se busca mejorar hidratación y luminosidad vía estimulación de matriz endógena, con buena tolerancia en pieles sensibles según valoración.', 'nuvanx-medical' ) ),
				array( 'title' => __( 'Pérdida de volumen incipiente (sin ptosis severa)', 'nuvanx-medical' ), 'body' => __( 'No es un relleno inyectable ni un lifting. Puede ayudar a la redensificación cuando el “deshinchado” es principalmente de matriz, no de defecto estructural profundo.', 'nuvanx-medical' ) ),
				array( 'title' => __( 'Poros y textura irregular', 'nuvanx-medical' ), 'body' => __( 'El remodelado de colágeno y la mejora de compactación pueden refinar apariencia de poro y textura; en cicatrices profundas se valora Fractional RF o CO₂.', 'nuvanx-medical' ) ),
				array( 'title' => __( 'Historial de HIFU / RF de alto pico', 'nuvanx-medical' ), 'body' => __( 'Tras plataformas de alto pico térmico se reevalúan volumen, textura y sensibilidad antes de un nuevo protocolo energético.', 'nuvanx-medical' ) ),
				array( 'title' => __( 'Mantenimiento well-aging', 'nuvanx-medical' ), 'body' => __( 'Series de sesiones y mantenimiento según respuesta; combinable con EMFUSION® o inductores cuando el médico lo indique.', 'nuvanx-medical' ) ),
			),
			'compare'      => array(
				'title' => __( 'EXION Face frente a HIFU y RF de alto pico (p. ej. Thermage®)', 'nuvanx-medical' ),
				'body'  => function_exists( 'nvx_btl_claim_source' ) ? nvx_btl_claim_source( 'exion_face_compare' ) : '',
				'link'  => $blog_face,
				'label' => __( 'Leer: EXION Face vs HIFU y Thermage', 'nuvanx-medical' ),
			),
			'process'      => array(
				array(
					'title' => __( 'Valoración y procedimiento', 'nuvanx-medical' ),
					'body'  => __( 'Fototipo, grasa facial, historial de HIFU/RF y expectativas. Sesión orientativa ~30 min. Anestesia habitualmente no requerida. Sensación: calor tolerable. Recuperación: en muchos protocolos nula o mínima; eritema leve posible 1–2 h.', 'nuvanx-medical' ),
				),
				array(
					'title' => __( 'Sesiones orientativas', 'nuvanx-medical' ),
					'body'  => __( 'Piel media 35–50 años: a menudo 3 sesiones (~4 semanas). Envejecimiento moderado: 4–5. Daño solar avanzado: valorar 5–6 o combinación (p. ej. EMFUSION®). Mantenimiento: 1 sesión cada 12–18 meses según respuesta. El número final lo decide el médico.', 'nuvanx-medical' ),
				),
				array(
					'title' => __( 'Cuidados posteriores', 'nuvanx-medical' ),
					'body'  => __( 'Hidratación habitual. Primeras 48 h: evitar sauna y calor intenso. Fotoprotección SPF 30+. Evitar exfoliación mecánica agresiva ~7 días. Presupuesto cerrado tras indicación.', 'nuvanx-medical' ),
				),
				array(
					'title' => __( 'Contraindicaciones relativas', 'nuvanx-medical' ),
					'body'  => __( 'Implantes metálicos faciales relevantes, embarazo/lactancia (precisión clínica), fármacos fotosensibles de alto impacto, infecciones activas de piel. Fototipos altos: perfil habitualmente favorable; la indicación es siempre médica.', 'nuvanx-medical' ),
				),
			),
			'faqs'         => array(
				array(
					'q' => __( '¿EXION Face genera realmente +224% de ácido hialurónico?', 'nuvanx-medical' ),
					'a' => __( 'La documentación del fabricante describe un aumento de hasta ~224% en marcadores de HA endógeno en modelos evaluados a ~4 semanas. Es un dato de estudio/protocolo, no una promesa personalizada: en consulta se explica como potencial de estimulación y se contextualiza con biopsia/método del estudio.', 'nuvanx-medical' ),
				),
				array(
					'q' => __( '¿Por qué HIFU a veces “funciona” al principio y luego decepciona?', 'nuvanx-medical' ),
					'a' => __( 'Algunos protocolos de alto pico inducen contracción inmediata por desnaturalización de colágeno. A medio plazo, si hay pérdida de volumen o reabsorción de tejido cicatricial, la percepción puede cambiar. No es universal: depende de energía, plano y anatomía. Por eso reevaluamos antes de repetir plataformas agresivas.', 'nuvanx-medical' ),
				),
				array(
					'q' => __( '¿Cuántas sesiones necesito?', 'nuvanx-medical' ),
					'a' => __( 'Depende de edad, calidad basal y objetivo. Rangos orientativos: 3 sesiones en piel media; 4–5 en envejecimiento moderado; más o combinado en daño solar severo. Mantenimiento cada 12–18 meses según evolución. No hay un número fijo “para todos”.', 'nuvanx-medical' ),
				),
				array(
					'q' => __( '¿Es seguro en fototipos altos?', 'nuvanx-medical' ),
					'a' => __( 'Al trabajar con microtemperaturas controladas, el perfil inflamatorio puede diferir del de picos térmicos altos. El riesgo de PIH depende de fototipo, parámetros y cuidados: no se garantiza “cero PIH”; sí se planifica con criterio médico.', 'nuvanx-medical' ),
				),
				array(
					'q' => __( '¿Duele?', 'nuvanx-medical' ),
					'a' => __( 'La mayoría describe calor tolerable (referencias de 0–2/10 en materiales y experiencia de centros). No es comparable al dolor habitual de algunos HIFU de alta energía sin anestesia, pero la percepción es individual.', 'nuvanx-medical' ),
				),
				array(
					'q' => __( '¿Puedo combinarlo con toxina o rellenos?', 'nuvanx-medical' ),
					'a' => __( 'Sí, con secuencia médica. EXION Face no sustituye automáticamente a rellenos: mejora matriz e hidratación; el déficit estructural puede seguir requiriendo inductores o HA inyectable. El orden e intervalos se definen en valoración.', 'nuvanx-medical' ),
				),
				array(
					'q' => __( '¿Cuándo se ven resultados?', 'nuvanx-medical' ),
					'a' => __( 'La respuesta es progresiva (semanas), no “antes/después del mismo día”. Los hitos de 2–12 semanas son orientativos y dependen del protocolo y de la biología individual.', 'nuvanx-medical' ),
				),
				array(
					'q' => __( '¿Puedo hacerlo en verano?', 'nuvanx-medical' ),
					'a' => __( 'En muchos casos sí, con fotoprotección rigurosa. Se individualiza según fototipo, exposición y otros tratamientos concurrentes.', 'nuvanx-medical' ),
				),
			),
		),
		'exion-body'       => array(
			'path'         => '/exion-body/',
			'key'          => 'exion_body',
			'kicker'       => __( 'EXION® Body · NUVANX Madrid', 'nuvanx-medical' ),
			'h1'           => __( 'EXION® Body: adiposidad localizada y retracción cutánea en un solo protocolo', 'nuvanx-medical' ),
			'lead'         => __( 'Radiofrecuencia monopolar con refrigeración activa para abordar grasa localizada y laxitud asociada — cuando el diagnóstico lo permite — sin el downtime típico de microagujas corporales ni la ecuación “solo frío” de la criolipólisis.', 'nuvanx-medical' ),
			'meta'         => __( 'Chamberí · Goya · Contorno corporal con criterio médico', 'nuvanx-medical' ),
			'aria'         => __( 'EXION Body NUVANX', 'nuvanx-medical' ),
			'marker'       => 'nvx-exion-body',
			'yoast_title'  => 'EXION Body Madrid | Grasa localizada y tensado | NUVANX',
			'yoast_desc'   => 'EXION® Body en NUVANX Madrid: radiofrecuencia con refrigeración activa para grasa localizada y calidad cutánea, según valoración médica. Chamberí y Goya.',
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
					__( 'El cabezal integra refrigeración activa de la superficie y radiofrecuencia monopolar profunda: la epidermis se protege mientras se deposita calor en hipodermis y dermis.', 'nuvanx-medical' ),
					function_exists( 'nvx_btl_claim_source' ) ? nvx_btl_claim_source( 'exion_body_btl_22' ) : '',
				),
				'items' => array(
					array(
						'title' => __( 'Refrigeración activa en superficie', 'nuvanx-medical' ),
						// Neutral wording — no unreviewed “lower burn risk” comparative claim.
						'body'  => function_exists( 'nvx_btl_claim_source' ) ? nvx_btl_claim_source( 'exion_body_cooling' ) : '',
					),
					array(
						'title' => __( 'RF monopolar profunda (~40–45 °C en tejido objetivo)', 'nuvanx-medical' ),
						'body'  => __( 'En rangos documentados se busca apoptosis programada de adipocitos y, en paralelo, contracción/remodelado de colágeno. El rango es de protocolo evaluado, no una temperatura fija en cada cuerpo.', 'nuvanx-medical' ),
					),
					array(
						'title' => __( 'Ecuación grasa + piel', 'nuvanx-medical' ),
						'body'  => __( 'La criolipólisis actúa sobre grasa pero no tensa. Las microagujas corporales tensan con más trauma. EXION Body busca ambos efectos en un perfil de tolerancia habitualmente más favorable; la superioridad no es universal.', 'nuvanx-medical' ),
					),
				),
			),
			'indications'  => array(
				array( 'title' => __( 'Adiposidad localizada leve–moderada', 'nuvanx-medical' ), 'body' => __( 'Flancos, abdomen inferior y pliegues rebeldes cuando no hay indicación prioritaria de abdominoplastia o liposucción mayor.', 'nuvanx-medical' ) ),
				array( 'title' => __( 'Flacidez leve–moderada con o sin grasa', 'nuvanx-medical' ), 'body' => __( 'Brazos, muslos internos y zonas donde reducir volumen sin tensar dejaría piel laxa; se valora RF vs láser intersticial según espesor.', 'nuvanx-medical' ) ),
				array( 'title' => __( 'Grasa + flacidez combinadas', 'nuvanx-medical' ), 'body' => __( 'Indicación más favorable para un protocolo que no se limite a “solo congelar” o “solo pinchar”.', 'nuvanx-medical' ) ),
				array( 'title' => __( 'Celulitis grado I–II con adiposidad', 'nuvanx-medical' ), 'body' => __( 'Mejora de contorno y calidad de superficie cuando el componente adiposo y la laxitud predominan; no es tratamiento de celulitis severa estructural sola.', 'nuvanx-medical' ) ),
				array( 'title' => __( 'Post-criolipólisis', 'nuvanx-medical' ), 'body' => __( 'Pacientes con grasa reducida pero laxitud residual: se reorienta a retracción y calidad, no a más frío sin reevaluación.', 'nuvanx-medical' ) ),
				array( 'title' => __( 'Combinación con endoláser (espesor alto)', 'nuvanx-medical' ), 'body' => __( 'En adiposidad >~4–5 cm puede proponerse laserlipólisis y, en fase posterior (~semana 5+), EXION Body para consolidar tensado. Umbral e intervalo son clínicos.', 'nuvanx-medical' ) ),
			),
			'compare'      => array(
				'title' => __( 'EXION Body frente a CoolSculpting y Morpheus8 Body', 'nuvanx-medical' ),
				'body'  => function_exists( 'nvx_btl_claim_source' ) ? nvx_btl_claim_source( 'exion_body_compare' ) : '',
				'link'  => $blog_body,
				'label' => __( 'Leer: EXION Body vs CoolSculpting y Morpheus8', 'nuvanx-medical' ),
			),
			'process'      => array(
				array(
					'title' => __( 'Valoración y procedimiento', 'nuvanx-medical' ),
					'body'  => __( 'Diagnóstico de zona, pliegue, flacidez y expectativas realistas de contorno (no de “kilos”). Sesión 45–60 min según áreas. Sin microagujas largas. Downtime habitual nulo o mínimo; eritema posible en horas.', 'nuvanx-medical' ),
				),
				array(
					'title' => __( 'Sesiones orientativas', 'nuvanx-medical' ),
					'body'  => __( 'Leve: 2–3. Moderada: 3–4. Severa: valorar endoláser + EXION en secuencia. Mantenimiento cada 18–24 meses según evolución y estilo de vida. No es un protocolo de “una sesión y listo” para todos.', 'nuvanx-medical' ),
				),
				array(
					'title' => __( 'Cuidados posteriores', 'nuvanx-medical' ),
					'body'  => __( 'Actividad habitual en la mayoría de casos; se individualizan 24–48 h según zona e intensidad. Hidratación y hábitos de peso estables ayudan a mantener el contorno.', 'nuvanx-medical' ),
				),
				array(
					'title' => __( 'Contraindicaciones y límites', 'nuvanx-medical' ),
					'body'  => __( 'Embarazo/lactancia, marcapasos u otros implantes relevantes, infecciones activas, hernias no controladas en zona, expectativas de pérdida de peso global. No sustituye cirugía cuando el exceso cutáneo o graso lo exige.', 'nuvanx-medical' ),
				),
			),
			'related'      => array(
				array( 'url' => $endolaser, 'label' => __( 'Endoláser corporal (adiposidad de mayor espesor)', 'nuvanx-medical' ) ),
				array( 'url' => $blog_combo, 'label' => __( 'Protocolos combinados del ecosistema NUVANX', 'nuvanx-medical' ) ),
			),
			'faqs'         => array(
				array(
					'q' => __( '¿EXION Body elimina grasa o solo tensa la piel?', 'nuvanx-medical' ),
					'a' => __( 'Busca ambos efectos en un mismo protocolo: acción sobre adiposidad localizada y remodelado de colágeno. No es liposucción quirúrgica ni un método de pérdida de peso corporal.', 'nuvanx-medical' ),
				),
				array(
					'q' => __( '¿Por qué la criolipólisis a veces deja flacidez residual?', 'nuvanx-medical' ),
					'a' => __( 'Si se reduce volumen graso y la piel no se adapta, puede quedar laxitud. No es inevitable en todos los casos, pero explica por qué valoramos grasa y calidad cutánea juntos.', 'nuvanx-medical' ),
				),
				array(
					'q' => __( '¿Cuántas sesiones necesito?', 'nuvanx-medical' ),
					'a' => __( 'Rangos orientativos 2–4 según severidad y zona; casos de mayor espesor pueden requerir endoláser previo. El plan se cierra tras exploración.', 'nuvanx-medical' ),
				),
				array(
					'q' => __( '¿Hay hematomas como con microagujas?', 'nuvanx-medical' ),
					'a' => __( 'No es un sistema de perforación con agujas largas. El eritema, si aparece, suele resolverse en horas, con variabilidad individual.', 'nuvanx-medical' ),
				),
				array(
					'q' => __( '¿Puedo combinarlo con ejercicio o dieta?', 'nuvanx-medical' ),
					'a' => __( 'Sí. EXION Body no sustituye hábitos: estabilizar peso y actividad mejora la duración del contorno. Tampoco “quema” kilos de grasa sistémica.', 'nuvanx-medical' ),
				),
				array(
					'q' => __( '¿Cuándo combinar con laserlipólisis?', 'nuvanx-medical' ),
					'a' => __( 'En espesores altos (orientativo >~4–5 cm) puede proponerse fase 1 con endoláser y fase 2 con EXION Body para tensado. Intervalos y candidatura son médicos.', 'nuvanx-medical' ),
				),
				array(
					'q' => __( '¿Los resultados son permanentes?', 'nuvanx-medical' ),
					'a' => __( 'La apoptosis de adipocitos tratados no regenera esas células, pero un aumento de peso puede crear depósito en otras zonas. El mantenimiento y el estilo de vida marcan la duración práctica del contorno.', 'nuvanx-medical' ),
				),
				array(
					'q' => __( '¿Es seguro en fototipos altos?', 'nuvanx-medical' ),
					'a' => __( 'El perfil térmico controlado y la refrigeración de superficie ayudan a un plan seguro, pero la indicación y los parámetros son siempre individuales.', 'nuvanx-medical' ),
				),
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

	// Mechanism (same zone-list pattern as Endoláser / CO₂ — no page-exclusive layout).
	$body .= '<section class="nvx-endolift-section" aria-labelledby="' . esc_attr( $id ) . '-mech">';
	$body .= '<div class="nvx-endolift-section__inner">';
	$body .= '<p class="nvx-endolift-kicker">' . esc_html__( 'Mecanismo', 'nuvanx-medical' ) . '</p>';
	$body .= '<h2 id="' . esc_attr( $id ) . '-mech" class="nvx-endolift-heading">' . esc_html( (string) ( $c['mechanism']['title'] ?? '' ) ) . '</h2>';
	foreach ( (array) ( $c['mechanism']['body'] ?? array() ) as $p ) {
		$p = is_string( $p ) ? trim( $p ) : '';
		if ( '' === $p ) {
			continue;
		}
		$body .= '<p class="nvx-endolift-body nvx-endolift-body--measure">' . esc_html( $p ) . '</p>';
	}
	if ( ! empty( $c['mechanism']['items'] ) && is_array( $c['mechanism']['items'] ) ) {
		$body .= '<ul class="nvx-endolaser-zone-list">';
		foreach ( $c['mechanism']['items'] as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$title = trim( (string) ( $item['title'] ?? '' ) );
			$text  = trim( (string) ( $item['body'] ?? '' ) );
			if ( '' === $title && '' === $text ) {
				continue;
			}
			$body .= '<li class="nvx-endolaser-zone">';
			if ( '' !== $title ) {
				$body .= '<h3 class="nvx-endolaser-zone__title">' . esc_html( $title ) . '</h3>';
			}
			if ( '' !== $text ) {
				$body .= '<p class="nvx-endolift-body">' . esc_html( $text ) . '</p>';
			}
			$body .= '</li>';
		}
		$body .= '</ul>';
	}
	$body .= '<p class="nvx-endolift-body"><a class="nvx-brand-inline-link" href="' . esc_url( $c['hub'] ) . '">' . esc_html__( 'Ver plataforma EXION® BTL (hub)', 'nuvanx-medical' ) . '</a></p>';
	$body .= '</div></section>';

	// Indications.
	$body .= '<section class="nvx-endolift-section" aria-labelledby="' . esc_attr( $id ) . '-ind">';
	$body .= '<div class="nvx-endolift-section__inner">';
	$body .= '<p class="nvx-endolift-kicker">' . esc_html__( 'Indicaciones', 'nuvanx-medical' ) . '</p>';
	$body .= '<h2 id="' . esc_attr( $id ) . '-ind" class="nvx-endolift-heading">' . esc_html__( 'Cuándo tiene sentido este protocolo', 'nuvanx-medical' ) . '</h2>';
	$body .= '<ul class="nvx-endolaser-zone-list">';
	foreach ( (array) ( $c['indications'] ?? array() ) as $item ) {
		if ( ! is_array( $item ) ) {
			continue;
		}
		$title = trim( (string) ( $item['title'] ?? '' ) );
		$text  = trim( (string) ( $item['body'] ?? '' ) );
		if ( '' === $title && '' === $text ) {
			continue;
		}
		$body .= '<li class="nvx-endolaser-zone">';
		if ( '' !== $title ) {
			$body .= '<h3 class="nvx-endolaser-zone__title">' . esc_html( $title ) . '</h3>';
		}
		if ( '' !== $text ) {
			$body .= '<p class="nvx-endolift-body">' . esc_html( $text ) . '</p>';
		}
		$body .= '</li>';
	}
	$body .= '</ul></div></section>';

	// Compare + blog depth (strategy: internal link to money content).
	$body .= '<section class="nvx-endolift-section" aria-labelledby="' . esc_attr( $id ) . '-cmp">';
	$body .= '<div class="nvx-endolift-section__inner">';
	$body .= '<p class="nvx-endolift-kicker">' . esc_html__( 'Criterio diferencial', 'nuvanx-medical' ) . '</p>';
	$body .= '<h2 id="' . esc_attr( $id ) . '-cmp" class="nvx-endolift-heading">' . esc_html( (string) ( $c['compare']['title'] ?? '' ) ) . '</h2>';
	$compare_body = trim( (string) ( $c['compare']['body'] ?? '' ) );
	if ( '' !== $compare_body ) {
		$body .= '<p class="nvx-endolift-body nvx-endolift-body--measure">' . esc_html( $compare_body ) . '</p>';
	}
	$body .= '<p class="nvx-endolift-body"><a class="nvx-brand-inline-link" href="' . esc_url( (string) ( $c['compare']['link'] ?? '' ) ) . '">' . esc_html( (string) ( $c['compare']['label'] ?? '' ) ) . '</a>';
	if ( ! empty( $c['combo'] ) ) {
		$body .= ' · <a class="nvx-brand-inline-link" href="' . esc_url( $c['combo'] ) . '">' . esc_html__( 'Protocolos combinados NUVANX', 'nuvanx-medical' ) . '</a>';
	}
	$body .= '</p>';
	if ( ! empty( $c['related'] ) && is_array( $c['related'] ) ) {
		foreach ( $c['related'] as $rel ) {
			if ( ! is_array( $rel ) || empty( $rel['url'] ) || empty( $rel['label'] ) ) {
				continue;
			}
			$body .= '<p class="nvx-endolift-body"><a class="nvx-brand-inline-link" href="' . esc_url( (string) $rel['url'] ) . '">' . esc_html( (string) $rel['label'] ) . '</a></p>';
		}
	}
	$body .= '</div></section>';

	// Process (string steps or titled steps — same list chrome).
	$body .= '<section class="nvx-endolift-section" aria-labelledby="' . esc_attr( $id ) . '-proc">';
	$body .= '<div class="nvx-endolift-section__inner">';
	$body .= '<p class="nvx-endolift-kicker">' . esc_html__( 'Proceso médico', 'nuvanx-medical' ) . '</p>';
	$body .= '<h2 id="' . esc_attr( $id ) . '-proc" class="nvx-endolift-heading">' . esc_html__( 'Procedimiento, sesiones y cuidados', 'nuvanx-medical' ) . '</h2>';
	$body .= '<ol class="nvx-endolaser-zone-list">';
	foreach ( (array) ( $c['process'] ?? array() ) as $step ) {
		if ( is_array( $step ) ) {
			$title = trim( (string) ( $step['title'] ?? '' ) );
			$text  = trim( (string) ( $step['body'] ?? '' ) );
			if ( '' === $title && '' === $text ) {
				continue;
			}
			$body .= '<li class="nvx-endolaser-zone">';
			if ( '' !== $title ) {
				$body .= '<h3 class="nvx-endolaser-zone__title">' . esc_html( $title ) . '</h3>';
			}
			if ( '' !== $text ) {
				$body .= '<p class="nvx-endolift-body">' . esc_html( $text ) . '</p>';
			}
			$body .= '</li>';
		} else {
			$text = trim( (string) $step );
			if ( '' === $text ) {
				continue;
			}
			$body .= '<li class="nvx-endolaser-zone"><p class="nvx-endolift-body">' . esc_html( $text ) . '</p></li>';
		}
	}
	$body .= '</ol></div></section>';

	// FAQ.
	$body .= '<section class="nvx-endolift-section" aria-labelledby="' . esc_attr( $id ) . '-faq">';
	$body .= '<div class="nvx-endolift-section__inner">';
	$body .= '<p class="nvx-endolift-kicker">' . esc_html__( 'FAQ', 'nuvanx-medical' ) . '</p>';
	$body .= '<h2 id="' . esc_attr( $id ) . '-faq" class="nvx-endolift-heading">' . esc_html__( 'Preguntas frecuentes', 'nuvanx-medical' ) . '</h2>';
	$body .= '<div class="nvx-faq nvx-brand-faq-accordion">';
	foreach ( (array) ( $c['faqs'] ?? array() ) as $faq ) {
		if ( ! is_array( $faq ) ) {
			continue;
		}
		$q = trim( (string) ( $faq['q'] ?? '' ) );
		$a = trim( (string) ( $faq['a'] ?? '' ) );
		if ( '' === $q && '' === $a ) {
			continue;
		}
		$body .= '<details class="nvx-brand-faq-item">';
		$body .= '<summary>' . esc_html( $q ) . '</summary>';
		$body .= '<div class="nvx-brand-faq-content"><p>' . esc_html( $a ) . '</p></div>';
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
