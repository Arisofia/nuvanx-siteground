<?php
/**
 * Protocol pages mapped to the universal 13-point structure.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Catalogue for Signature Protocols.
 *
 * @return array<string, array<string, mixed>>
 */
function nvx_protocol_pages_catalog(): array {
	return array(
		'couture-sculpt' => array(
			'slug'          => 'remodelacion-corporal-laser-madrid',
			'seo_title'     => 'NUVANX Contour Sculpt™: Remodelación Corporal en Madrid',
			'description'   => 'Diagnóstico médico y remodelación corporal sin cirugía. Tratamos grasa localizada y firmeza con tecnología láser ajustada a tu anatomía.',
			'kicker'        => 'CONTORNO CORPORAL NUVANX',
			'h1'          => 'El diagnóstico determina el plan corporal. No la máquina de moda.',
			'lead'          => 'Si intentas tratar la grasa localizada como si fuera retención de líquidos, perderás tiempo y dinero. En NUVANX empezamos por un diagnóstico médico sincero para entender tu tejido antes de proponer un protocolo.',
			'diagnosis'   => 'Diferenciamos de forma precisa entre grasa subcutánea (que podemos tratar), grasa visceral (que requiere abordaje nutricional), laxitud cutánea y celulitis. No todas las zonas responden igual, y nuestro diagnóstico separa el tejido fibrótico del tejido laxo para elegir la tecnología adecuada.',
			'mechanism'   => 'Combinamos láser subdérmico (Endoláser) para lipólisis y retracción térmica con radiofrecuencia focalizada, destruyendo los adipocitos y forzando a la piel a tensarse en el mismo acto médico.',
			'indications' => array(
				'Grasa localizada rebelde a dieta y ejercicio en abdomen, flancos o muslos.',
				'Laxitud cutánea moderada que requiere soporte y tensión.',
				'Pérdida de definición en el contorno corporal por acumulación focalizada.',
			),
			'precautions' => array(
				'Exceso de peso generalizado u obesidad (requiere programa integral).',
				'Grasa visceral predominante (el láser solo actúa sobre grasa subcutánea).',
				'Laxitud cutánea extrema que ya tiene indicación quirúrgica (abdominoplastia).',
			),
			'process'     => array(
				'Ecografía cutánea y diagnóstico diferencial del tejido adiposo.',
				'Diseño topográfico de las áreas de lipólisis y las áreas de tensado.',
				'Procedimiento ambulatorio bajo anestesia local, sin hospitalización.',
				'Revisión médica post-procedimiento y pautas de recuperación activa.',
			),
			'faqs'        => array(
				array(
					'q' => '¿Es un tratamiento para perder peso?',
					'a' => 'No. Es un tratamiento de contorno para remodelar la silueta y eliminar volúmenes localizados. No sustituye a una pauta nutricional.',
				),
				array(
					'q' => '¿Cuándo se ven los resultados?',
					'a' => 'Hay un cambio inicial por la reducción de volumen, pero el tensado de la piel (formación de colágeno) es progresivo y se consolida a los 3-6 meses.',
				),
			),
			'review_status' => 'approved_for_publication',
		),
		'post-maternity' => array(
			'slug'          => 'tratamiento-postparto-abdomen-contorno-corporal-madrid',
			'seo_title'     => 'NUVANX Post-Maternity Contour™: Recuperación Posgestacional Madrid',
			'description'   => 'Valoración médica posparto. Abordamos diástasis, flacidez y grasa localizada con tecnología focalizada tras el alta ginecológica.',
			'kicker'        => 'RECUPERACIÓN POSGESTACIONAL',
			'h1'          => 'Recuperar tu cuerpo tras el embarazo empieza por saber qué se ha roto y qué se ha estirado.',
			'lead'          => 'El abdomen posparto no se resuelve haciendo abdominales a ciegas. Medimos exactamente tu diástasis, la calidad de la piel y los acúmulos grasos antes de proponer un plan de acción.',
			'diagnosis'   => 'Separamos claramente la laxitud de la piel de la separación muscular (diástasis) y de la grasa residual. Un diagnóstico fallido aquí es la causa de que muchos tratamientos posparto fracasen.',
			'mechanism'   => 'Empleamos tecnología de radiofrecuencia fraccionada para redensificar la piel estríada y terapias electromagnéticas para reeducar la pared muscular y ayudar en el cierre de la diástasis funcional.',
			'indications' => array(
				'Laxitud abdominal y alteración de la calidad cutánea posgestacional.',
				'Diástasis de rectos leve a moderada susceptible de mejora conservadora.',
				'Adiposidad localizada residual en abdomen inferior o flancos.',
			),
			'precautions' => array(
				'Diástasis severas o hernias que requieren valoración por cirugía general.',
				'Exceso de piel masivo (faldón abdominal) con indicación de dermolipectomía.',
				'Lactancia activa (algunas tecnologías y activos deben posponerse).',
			),
			'process'     => array(
				'Valoración ecográfica de la pared abdominal y medición de la diástasis.',
				'Evaluación del daño dérmico (estrías) y laxitud superficial.',
				'Diseño del protocolo combinando fortalecimiento muscular y redensificación cutánea.',
			),
			'faqs'        => array(
				array(
					'q' => '¿Cuándo puedo empezar el tratamiento tras dar a luz?',
					'a' => 'Normalmente recomendamos esperar al alta ginecológica (cuarentena) y estabilización hormonal, pero la valoración inicial puede realizarse antes para planificar.',
				),
			),
			'review_status' => 'approved_for_publication',
		),
		'profile-definition' => array(
			'slug'          => 'papada-definicion-mandibular-madrid',
			'seo_title'     => 'Profile Definition™: Papada y mandíbula | NUVANX',
			'description'   => 'A veces es grasa, a veces es falta de soporte estructural. Tratamientos médicos sin cirugía para redefinir el óvalo facial en Madrid.',
			'kicker'        => 'ARQUITECTURA FACIAL NUVANX',
			'h1'          => 'Papada y mandíbula: a veces es grasa, a veces es piel, y a veces falta hueso.',
			'lead'          => 'Para definir el óvalo facial sin pasar por quirófano, te miramos primero. Te decimos con claridad qué se puede conseguir, y solo si tiene sentido para tu anatomía, seguimos adelante.',
			'diagnosis'   => 'A veces no hay papada real: lo que pasa es que el mentón es pequeño y la piel cuelga por falta de soporte. Si inyectamos grasa o ponemos láser sin diagnosticar el díficit óseo, no habrá buen resultado.',
			'mechanism'   => 'Combinamos la lipólisis asistida por láser para eliminar grasa submentoniana con reposicionamiento de soporte estructural (ácido hialurónico) en el ángulo mandibular y mentón.',
			'indications' => array(
				'Acúmulo graso localizado en la región submentoniana (papada auténtica).',
				'Pérdida de definición en la línea mandibular por falta de soporte o laxitud leve.',
				'Mentón retraído leve que acentúa la sensación de papada.',
			),
			'precautions' => array(
				'Laxitud cutánea severa del cuello (cuello de pavo) que requiere lifting cervical.',
				'Micrognatia severa (falta de hueso extrema) que requiere cirugía maxilofacial.',
				'Glándulas submandibulares hipertróficas o problemas tiroideos no controlados.',
			),
			'process'     => array(
				'Análisis tridimensional del tercio inferior y el cuello.',
				'Identificación del problema primario (grasa, piel o soporte óseo).',
				'Diseño del ángulo mandibular y protocolo de reducción grasa si es preciso.',
			),
			'faqs'        => array(
				array(
					'q' => '¿Se puede marcar la mandíbula solo con ácido hialurónico?',
					'a' => 'Si hay grasa superpuesta, inyectar relleno solo ensanchará el rostro. Primero hay que tratar la grasa, y luego definir la estructura.',
				),
			),
			'review_status' => 'approved_for_publication',
		),
		'skin-architecture' => array(
			'slug'          => 'calidad-piel-firmeza-luminosidad-madrid',
			'seo_title'     => 'Skin Architecture™: Firmeza y Calidad de Piel | NUVANX',
			'description'   => 'Redensificación y firmeza dérmica. Obligamos a tus células a fabricar colágeno nuevo mediante estímulos médicos controlados.',
			'kicker'        => 'CALIDAD CUTÁNEA NUVANX',
			'h1'          => 'Tu piel no necesita más cremas, necesita reconstruirse por dentro.',
			'lead'          => 'Si la piel ha perdido grosor y soporte, ponerte crema es como pintar una pared que se está desmoronando. Hay que actuar a nivel celular.',
			'diagnosis'   => 'Diferenciamos entre deshidratación superficial, elastosis solar (daño por el sol) y atrofia dérmica (piel fina por la edad). Cada problema requiere una profundidad de actuación distinta.',
			'mechanism'   => 'Utilizamos calor (Radiofrecuencia Fraccionada BTL EXION) o inductores químicos (bioestimuladores) para obligar a los fibroblastos a fabricar colágeno nuevo, engrosando y tensando la piel de forma natural.',
			'indications' => array(
				'Piel fina y apergaminada (atrofia dérmica).',
				'Falta de firmeza y elasticidad por descenso en la producción de colágeno.',
				'Arrugas finas estáticas que no responden a neuromoduladores.',
			),
			'precautions' => array(
				'Infecciones activas o brotes de enfermedades autoinmunes cutáneas.',
				'Acné activo severo (que debe tratarse antes de la redensificación).',
				'Expectativas de efecto "lifting quirúrgico" (este protocolo mejora la calidad, no tracciona músculos).',
			),
			'process'     => array(
				'Evaluación del fototipo, grosor dérmico y nivel de daño solar.',
				'Selección de la plataforma de estímulo (físico o químico).',
				'Planificación de sesiones espaciadas para respetar el ciclo de neo-colagénesis.',
			),
			'faqs'        => array(
				array(
					'q' => '¿Duele el tratamiento con radiofrecuencia fraccionada?',
					'a' => 'Aplicamos anestesia tópica previa. Sentirás calor y un ligero picor, pero es un procedimiento muy tolerable.',
				),
			),
			'review_status' => 'approved_for_publication',
		),
		'surface-renewal' => array(
			'slug'          => 'cicatrices-acne-poros-textura-madrid',
			'seo_title'     => 'Surface Renewal™: Cicatrices y Textura | NUVANX',
			'description'   => 'Mejoramos las marcas de acné rompiendo la cicatriz. Resurfacing médico con Láser CO2 y radiofrecuencia.',
			'kicker'        => 'CALIDAD CUTÁNEA NUVANX',
			'h1'          => 'Para mejorar las marcas de acné hay que romper la cicatriz, no solo pelar la piel.',
			'lead'          => 'Los peelings suaves no llegan a la raíz del problema. Las cicatrices de acné tiran de la piel hacia abajo desde dentro; hay que liberar esa tensión.',
			'diagnosis'   => 'Clasificamos tus cicatrices en furgón (boxcar), picahielo (icepick) o rodantes (rolling). Un diagnóstico exacto es crucial porque cada tipo de cicatriz responde a una técnica diferente.',
			'mechanism'   => 'Combinamos resurfacing ablativo con Láser CO2 fraccionado para alisar los bordes de la cicatriz, y subcisión o radiofrecuencia con agujas para liberar el tejido fibroso que ancla la cicatriz a la dermis profunda.',
			'indications' => array(
				'Cicatrices atróficas de acné residual.',
				'Poros muy dilatados y textura irregular generalizada.',
				'Marcas post-inflamatorias crónicas.',
			),
			'precautions' => array(
				'Acné activo inflamatorio (debe estabilizarse antes del resurfacing).',
				'Fototipos altos (requieren preparación especial para evitar hiperpigmentación).',
				'Uso reciente de isotretinoína (según valoración médica actualizada).',
			),
			'process'     => array(
				'Mapeo de cicatrices bajo luz tangencial.',
				'Preparación de la piel y aplicación de anestesia local o tópica.',
				'Tratamiento combinado (ej. liberación profunda + láser superficial).',
				'Seguimiento estricto de la recuperación epidérmica.',
			),
			'faqs'        => array(
				array(
					'q' => '¿Las cicatrices desaparecerán por completo?',
					'a' => 'No te vamos a mentir: las marcas profundas rara vez desaparecen al 100%, pero sí podemos hacer que dejen de ser lo primero que ves en el espejo.',
				),
			),
			'review_status' => 'approved_for_publication',
		),
		'tone-correction' => array(
			'slug'          => 'manchas-rojeces-fotorejuvenecimiento-ipl-madrid',
			'seo_title'     => 'Tone Correction™: Fotorejuvenecimiento y Manchas | NUVANX',
			'description'   => 'Diagnóstico de pigmentación y rojeces. Tratamos el origen de la mancha para un tono uniforme y duradero.',
			'kicker'        => 'CALIDAD CUTÁNEA NUVANX',
			'h1'          => 'Quitar una mancha es fácil; que no vuelva a salir es la parte médica.',
			'lead'          => 'Quemar una mancha sin saber por qué ha salido es garantía de que volverá. Te decimos qué tipo tienes tú y cómo mantenerla a raya.',
			'diagnosis'   => 'Diferenciamos entre pigmento dérmico (profundo), epidérmico (superficial) y el componente vascular subyacente. Confundir un melasma con un léntigo solar empeora el problema drásticamente.',
			'mechanism'   => 'Usamos Luz Pulsada Intensa (BTL EXILITE IPL) y pautas médicas despigmentantes para fragmentar el pigmento existente y, lo más importante, frenar a la célula que lo produce.',
			'indications' => array(
				'Léntigos solares (manchas por daño solar acumulado).',
				'Rosácea y rojeces difusas (componente vascular).',
				'Melasma y pigmentación hormonal (requiere manejo crónico).',
			),
			'precautions' => array(
				'Exposición solar reciente o piel bronceada (contraindicación absoluta para IPL).',
				'Uso de medicamentos fotosensibilizantes.',
				'Melasma inestable (el exceso de calor puede producir efecto rebote).',
			),
			'process'     => array(
				'Diagnóstico con luz de Wood o dermatoscopio.',
				'Planificación de la terapia clínica y la pauta cosmética domiciliaria obligatoria.',
				'Sesiones de luz o láser con control de respuesta inflamatoria.',
			),
			'faqs'        => array(
				array(
					'q' => '¿Me puedo hacer este tratamiento en verano?',
					'a' => 'Los tratamientos con luz y láser para manchas se reservan para los meses de menor incidencia solar para evitar complicaciones.',
				),
			),
			'review_status' => 'approved_for_publication',
		),
		'eye-frame' => array(
			'slug'        => 'eye-frame-rejuvenecimiento-mirada-madrid',
			'seo_title'   => 'NUVANX Eye Frame™: Rejuvenecimiento de la mirada en Madrid',
			'description' => 'Tu mirada no siempre refleja lo cansada que estás; a veces es solo la anatomía. Tratamientos perioculares sin cirugía.',
			'kicker'      => 'ARQUITECTURA FACIAL NUVANX',
			'h1'          => 'Tu mirada no siempre refleja lo cansada que estás; a veces es solo la anatomía.',
			'lead'        => 'Si te dicen que tienes "cara de cansada" aunque hayas dormido ocho horas, el problema no es el sueño, es cómo la luz cae sobre tus ojos. Miramos de cerca si es sombra por hundimiento, color o piel fina.',
			'diagnosis'   => 'Diferenciamos de forma estricta entre hundimiento estructural (falta de hueso/grasa), componente vascular (venitas), pigmentación y edema (retención de líquido).',
			'mechanism'   => 'A veces la ojera se marca porque falta apoyo debajo y se crea una sombra oscura — eso lo arreglamos dando soporte suave. Pero si tienes bolsas que empujan hacia afuera, poner relleno solo empeora las cosas.',
			'indications' => array(
				'Aspecto de cansancio constante por sombra estructural en el surco lagrimal.',
				'Pérdida de soporte en la transición párpado-mejilla.',
				'Alteraciones de calidad cutánea en la zona periocular.',
			),
			'precautions' => array(
				'Si el problema es retención de líquidos (edema malar), el ácido hialurónico está contraindicado.',
				'Si hay bolsas grasas reales severas, la derivación quirúrgica es el camino honesto.',
				'El color oscuro (vascular o pigmentario puro) no se corrige inyectando volumen.',
			),
			'process'     => array(
				'Análisis anatómico de la dinámica del tercio medio y la calidad de piel.',
				'Selección de tecnología (radiofrecuencia, láser o inyectable de soporte).',
				'Diseño del plan de tratamiento y tiempos de recuperación.',
			),
			'faqs'        => array(
				array(
					'q' => '¿Se me quitarán las bolsas con este protocolo?',
					'a' => 'No. Si el diagnóstico revela bolsas de grasa reales, lo indicado es una blefaroplastia. Solo tratamos el hundimiento (falta de volumen).',
				),
			),
			'review_status' => 'approved_for_publication',
		),
	);
}

/** Identifies the current page's protocol catalog entry. */
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

/** Universal 13-point markup renderer for aesthetic and protocol pages. */

/** Dispatches the markup for one approved protocol page. */
function nvx_protocol_pages_markup( string $key, array $data ): string {
	return nvx_render_13_point_matrix( $data );
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
