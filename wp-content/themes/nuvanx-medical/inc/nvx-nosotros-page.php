<?php
/**
 * Sobre Nosotros — authority, platforms, NAP, cuadro médico corto, principios.
 *
 * Path: /nosotros/ only. Does not rewrite home, equipo (full bios) or treatment pages.
 * Technology copy is positioning-level; detail pages keep full clinical encyclopedia.
 * No AggregateRating hardcode. No videoconsulta CTA.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Singular page context.
 */
function nvx_nosotros_is_singular_context(): bool {
	if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return false;
	}

	return is_singular( 'page' ) || is_page();
}

/**
 * Detect Sobre Nosotros page only.
 */
function nvx_content_is_nosotros_page( string $content ): bool {
	if ( false !== strpos( $content, 'nvx-nosotros-editorial' ) ) {
		return false;
	}

	if ( ! nvx_nosotros_is_singular_context() || is_front_page() || is_home() ) {
		return false;
	}

	$path = function_exists( 'nvx_schema_current_path' )
		? nvx_schema_current_path( (int) get_queried_object_id() )
		: '';

	if ( is_string( $path ) && function_exists( 'nvx_schema_path_matches' ) ) {
		if ( nvx_schema_path_matches( $path, '/nosotros/' ) || nvx_schema_path_matches( $path, '/sobre-nosotros/' ) ) {
			return true;
		}
	}

	$slug = (string) get_post_field( 'post_name', get_queried_object_id() );
	if ( in_array( $slug, array( 'nosotros', 'sobre-nosotros', 'about' ), true ) ) {
		return true;
	}

	return (bool) preg_match(
		'/class=["\'][^"\']*\bnvx-brand-page--nosotros\b|id=["\']nvx-nosotros-h1["\']|aria-label=["\']Sobre Nosotros NUVANX["\']/iu',
		$content
	);
}

/**
 * Public URL helper.
 */
function nvx_nosotros_url( string $path ): string {
	$path = trim( $path, '/' );
	if ( function_exists( 'nvx_laser_page_url' ) ) {
		return nvx_laser_page_url( $path );
	}
	return home_url( '/' . $path . '/' );
}

/**
 * Hero copy.
 */
function nvx_nosotros_hero_copy_markup(): string {
	$html  = '<div class="nvx-brand-hero__copy nvx-nosotros-hero-copy">';
	$html .= '<p class="nvx-brand-kicker">' . esc_html__( 'NUVANX · Madrid', 'nuvanx-medical' ) . '</p>';
	$html .= '<h1 class="nvx-brand-hero__title" id="nvx-nosotros-h1">' . esc_html__( 'Sobre Nosotros: Autoridad Médica, Criterio Clínico y Transparencia', 'nuvanx-medical' ) . '</h1>';
	$html .= '<p class="nvx-brand-hero__lead">' . esc_html__( 'Medicina estética láser basada en evidencia, ingeniería tisular y well-aging — sin protocolos estandarizados ni inercia comercial.', 'nuvanx-medical' ) . '</p>';

	if ( function_exists( 'nvx_cta_pair_markup' ) ) {
		$html .= nvx_cta_pair_markup( 'nvx-nosotros-hero-ctas nvx-home-hero-ctas' );
	}

	$html .= '<p class="nvx-brand-meta">' . esc_html__( 'Chamberí · Goya · Registros sanitarios CS20144 y CS20073', 'nuvanx-medical' ) . '</p>';
	$html .= '</div>';

	return $html;
}

/**
 * Positioning intro.
 */
function nvx_nosotros_positioning_markup(): string {
	$html  = '<section class="nvx-endolift-section nvx-nosotros-positioning" aria-labelledby="nvx-nosotros-pos-title">';
	$html .= '<div class="nvx-endolift-section__inner">';
	$html .= '<p class="nvx-endolift-kicker">' . esc_html__( 'Posicionamiento', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-nosotros-pos-title" class="nvx-endolift-heading">' . esc_html__( 'Criterio clínico antes que catálogo', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p class="nvx-endolift-body nvx-endolift-body--measure">' . esc_html__( 'En NUVANX Medicina Estética Láser (Madrid) rechazamos la comercialización masiva y los protocolos estandarizados de la estética convencional. Operamos bajo el rigor de la medicina basada en la evidencia, la ingeniería tisular y el well-aging (envejecimiento saludable).', 'nuvanx-medical' ) . '</p>';
	$html .= '<p class="nvx-endolift-body nvx-endolift-body--measure">' . esc_html__( 'No aplicamos tratamientos por inercia: diagnosticamos cada anatomía de forma individual y precisa. Solo entonces prescribimos las soluciones tecnológicas más indicadas, sustentando cada decisión en mecanismos de acción celular comprobables.', 'nuvanx-medical' ) . '</p>';
	$html .= '</div></section>';

	return $html;
}

/**
 * Platform positioning cards (not full treatment encyclopedia).
 *
 * @return array<int, array{title:string,body:string,url:?string}>
 */
function nvx_nosotros_platforms_data(): array {
	return array(
		array(
			'title' => __( 'Endolift® — láser intersticial 1470 nm', 'nuvanx-medical' ),
			'body'  => __( 'Tecnología mínimamente invasiva que, a 1470 nm, actúa de forma selectiva sobre agua y grasa subcutánea. Mediante microfibra óptica estéril de 200–300 micras se induce lipólisis selectiva y se estimula neocolagénesis con retracción estructural, sin incisiones de lifting clásico.', 'nuvanx-medical' ),
			'url'   => nvx_nosotros_url( 'endolift-facial-papada-mandibula' ),
		),
		array(
			'title' => __( 'EXION® — radiofrecuencia fraccionada y ultrasonido', 'nuvanx-medical' ),
			'body'  => __( 'Plataforma que combina radiofrecuencia monopolar y ultrasonido focalizado (TUS), con control de profundidad orientado a dermis profunda. Estudios preclínicos de referencia describen un incremento de la producción endógena de ácido hialurónico del orden del 224% en el modelo evaluado; la indicación y el número de sesiones se definen siempre tras valoración médica.', 'nuvanx-medical' ),
			'url'   => nvx_nosotros_url( 'exion-btl' ),
		),
		array(
			'title' => __( 'EMFUSION® — restauración de barrera DYNAMiQ™', 'nuvanx-medical' ),
			'body'  => __( 'Sistema de infusión cutánea con tecnología DYNAMiQ™ que convierte energía eléctrica en ondas mecánicas y crea microcanales temporales para favorecer la penetración de activos (p. ej. ceramidas y ectoína). Datos de referencia del fabricante describen una reducción relevante de la pérdida de agua transepidérmica y mejor absorción de nutrientes; se indica solo cuando el tejido lo justifica.', 'nuvanx-medical' ),
			'url'   => null,
		),
		array(
			'title' => __( 'Láser CO₂ fraccionado, laserlipólisis y modelado subdérmico', 'nuvanx-medical' ),
			'body'  => __( 'Sistemas de alta precisión térmica para resurfacing y corrección de cicatrices atróficas (CO₂ fraccionado) y para modelado subdérmico / laserlipólisis corporal en casos seleccionados. Se activan únicamente cuando el diagnóstico anticipa una respuesta clínica real y predecible.', 'nuvanx-medical' ),
			'url'   => nvx_nosotros_url( 'laser-co2-fraccionado-madrid-textura-cicatrices-poro' ),
		),
	);
}

/**
 * Platforms section markup.
 */
function nvx_nosotros_platforms_markup(): string {
	$html  = '<section class="nvx-endolift-section nvx-nosotros-platforms" aria-labelledby="nvx-nosotros-tech-title">';
	$html .= '<div class="nvx-endolift-section__inner">';
	$html .= '<p class="nvx-endolift-kicker">' . esc_html__( 'Plataformas clínicas', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-nosotros-tech-title" class="nvx-endolift-heading">' . esc_html__( 'Tecnología con evidencia, nunca por tendencia', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p class="nvx-endolift-body nvx-endolift-body--measure">' . esc_html__( 'Incorporamos dispositivos con marcado CE y documentación técnica disponible. Se indican solo cuando la valoración clínica identifica un objetivo, una alternativa y un seguimiento apropiados.', 'nuvanx-medical' ) . '</p>';
	$html .= '<ul class="nvx-endolaser-zone-list nvx-nosotros-platform-list">';

	foreach ( nvx_nosotros_platforms_data() as $item ) {
		$html .= '<li class="nvx-endolaser-zone">';
		$html .= '<h3 class="nvx-endolaser-zone__title">' . esc_html( $item['title'] ) . '</h3>';
		$html .= '<p class="nvx-endolift-body">' . esc_html( $item['body'] ) . '</p>';
		if ( ! empty( $item['url'] ) ) {
			$html .= '<p class="nvx-nosotros-platform-link"><a class="nvx-brand-inline-link" href="' . esc_url( $item['url'] ) . '">' . esc_html__( 'Ver protocolo', 'nuvanx-medical' ) . '</a></p>';
		}
		$html .= '</li>';
	}

	// Secondary link to endoláser corporal (laserlipolysis body detail).
	$endolaser = nvx_nosotros_url( 'endolaser-corporal-grasa-localizada' );
	$html     .= '<li class="nvx-endolaser-zone nvx-nosotros-platform-related">';
	$html     .= '<h3 class="nvx-endolaser-zone__title">' . esc_html__( 'Endoláser corporal', 'nuvanx-medical' ) . '</h3>';
	$html     .= '<p class="nvx-endolift-body">' . esc_html__( 'Protocolo de laserlipólisis corporal para adiposidad localizada con retracción térmica asociada, documentado en página propia.', 'nuvanx-medical' ) . '</p>';
	$html     .= '<p class="nvx-nosotros-platform-link"><a class="nvx-brand-inline-link" href="' . esc_url( $endolaser ) . '">' . esc_html__( 'Ver Endoláser corporal', 'nuvanx-medical' ) . '</a></p>';
	$html     .= '</li>';

	$html .= '</ul></div></section>';

	return $html;
}

/**
 * Clinics NAP (reuse contact data when available).
 */
function nvx_nosotros_clinics_markup(): string {
	$clinics = function_exists( 'nvx_contact_clinics_nap' )
		? nvx_contact_clinics_nap()
		: array(
			array(
				'name'    => 'NUVANX Chamberí',
				'reg'     => 'CS20144',
				'address' => 'Calle de Fernández de la Hoz, 4, 28010 Madrid',
				'phone'   => '669 319 836',
			),
			array(
				'name'    => 'NUVANX Goya · Barrio Salamanca',
				'reg'     => 'CS20073',
				'address' => 'Calle de Fernán González, 26, 28009 Madrid',
				'phone'   => '647 505 107',
			),
		);

	$html  = '<section class="nvx-endolift-section nvx-nosotros-clinics" aria-labelledby="nvx-nosotros-clinics-title">';
	$html .= '<div class="nvx-endolift-section__inner">';
	$html .= '<p class="nvx-endolift-kicker">' . esc_html__( 'Sedes', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-nosotros-clinics-title" class="nvx-endolift-heading">' . esc_html__( 'Instalaciones autorizadas en Madrid', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p class="nvx-endolift-body nvx-endolift-body--measure">' . esc_html__( 'Nuestras instalaciones cumplen la normativa sanitaria de la Comunidad de Madrid en dos sedes de excelencia:', 'nuvanx-medical' ) . '</p>';
	$html .= '<div class="nvx-contact-clinics">';

	foreach ( $clinics as $clinic ) {
		$html .= '<article class="nvx-contact-clinic">';
		$html .= '<h3 class="nvx-contact-clinic__name">' . esc_html( $clinic['name'] ) . '</h3>';
		$html .= '<p class="nvx-contact-clinic__reg"><strong>' . esc_html__( 'Registro sanitario', 'nuvanx-medical' ) . '</strong> — ' . esc_html( $clinic['reg'] ) . '</p>';
		$html .= '<p class="nvx-contact-clinic__addr">' . esc_html( $clinic['address'] ) . '</p>';
		if ( ! empty( $clinic['phone'] ) && ! empty( $clinic['phone_href'] ) ) {
			$html .= '<p class="nvx-contact-clinic__phone"><a class="nvx-brand-inline-link" href="tel:' . esc_attr( $clinic['phone_href'] ) . '">' . esc_html( $clinic['phone'] ) . '</a></p>';
		} elseif ( ! empty( $clinic['phone'] ) ) {
			$html .= '<p class="nvx-contact-clinic__phone">' . esc_html( $clinic['phone'] ) . '</p>';
		}
		if ( ! empty( $clinic['days'] ) ) {
			$html .= '<p class="nvx-contact-clinic__days">' . esc_html( $clinic['days'] ) . '</p>';
		}
		$html .= '</article>';
	}

	$html .= '</div>';
	$clinicas = home_url( '/clinicas-de-medicina-estetica-nuvanx/' );
	$html    .= '<p class="nvx-endolift-body"><a class="nvx-brand-inline-link" href="' . esc_url( $clinicas ) . '">' . esc_html__( 'Ver clínicas NUVANX', 'nuvanx-medical' ) . '</a></p>';
	$html    .= '</div></section>';

	return $html;
}

/**
 * Short medical board — colegiados visibles; full bios live on /equipo-medico/.
 */
function nvx_nosotros_team_markup(): string {
	$dir   = defined( 'NVX_DIRECTOR_COLEGIADO' ) ? NVX_DIRECTOR_COLEGIADO : '282864786';
	$ivon  = defined( 'NVX_IVON_COLEGIADO' ) ? NVX_IVON_COLEGIADO : '284621525';
	$fabio = defined( 'NVX_FABIO_COLEGIADO' ) ? NVX_FABIO_COLEGIADO : '282877543';
	$equipo = home_url( '/equipo-medico/' );
	$doctoralia = 'https://www.doctoralia.es/jose-javier-rivera-tejeda/medico-estetico/madrid';

	$members = array(
		array(
			'name'  => __( 'Dr. José Javier Rivera Tejeda', 'nuvanx-medical' ),
			'role'  => __( 'Director médico', 'nuvanx-medical' ),
			'col'   => $dir,
			'body'  => __( 'Especialista en medicina estética avanzada, láser intervencionista (Endolift®) y tricología. Perfil público con reseñas verificadas en Doctoralia.', 'nuvanx-medical' ),
			'anchor'=> $equipo . '#physician-rivera-tejeda',
			'extra' => $doctoralia,
		),
		array(
			'name'  => __( 'Dra. Ivon Yamileth Rivera Deras', 'nuvanx-medical' ),
			'role'  => __( 'Well-aging y geriatría preventiva', 'nuvanx-medical' ),
			'col'   => $ivon,
			'body'  => __( 'Referente en longevidad y well-aging. FEA en Hospital Universitario La Paz; investigación y sociedades científicas (SEMEG / EuGMS).', 'nuvanx-medical' ),
			'anchor'=> $equipo . '#physician-rivera-deras',
			'extra' => '',
		),
		array(
			'name'  => __( 'Dr. Fabio Augusto Quiñónez Bareiro', 'nuvanx-medical' ),
			'role'  => __( 'Geriatría y paciente complejo', 'nuvanx-medical' ),
			'col'   => $fabio,
			'body'  => __( 'Especialista en geriatría, gerontología y paciente complejo. Trayectoria hospitalaria (SERMAS / SESCAM), CIBERFES y docencia universitaria.', 'nuvanx-medical' ),
			'anchor'=> $equipo . '#physician-quinonez-bareiro',
			'extra' => '',
		),
	);

	$html  = '<section class="nvx-endolift-section nvx-nosotros-team" aria-labelledby="nvx-nosotros-team-title">';
	$html .= '<div class="nvx-endolift-section__inner">';
	$html .= '<p class="nvx-endolift-kicker">' . esc_html__( 'Cuadro médico', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-nosotros-team-title" class="nvx-endolift-heading">' . esc_html__( 'Excelencia hospitalaria e investigadora', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p class="nvx-endolift-body nvx-endolift-body--measure">' . esc_html__( 'El mayor aval de NUVANX no es solo la tecnología, sino la trayectoria académica, investigadora y clínica del equipo. Resumen de autoridad; biografías completas en Equipo médico.', 'nuvanx-medical' ) . '</p>';
	$html .= '<div class="nvx-nosotros-team-grid">';

	foreach ( $members as $m ) {
		$html .= '<article class="nvx-nosotros-team-card">';
		$html .= '<p class="nvx-endolift-kicker">' . esc_html( $m['role'] ) . '</p>';
		$html .= '<h3 class="nvx-endolaser-zone__title">' . esc_html( $m['name'] ) . '</h3>';
		$html .= '<p class="nvx-endolift-body"><strong>' . esc_html__( 'ICOMEM', 'nuvanx-medical' ) . '</strong> ' . esc_html( $m['col'] ) . '</p>';
		$html .= '<p class="nvx-endolift-body">' . esc_html( $m['body'] ) . '</p>';
		$html .= '<p class="nvx-nosotros-platform-link"><a class="nvx-brand-inline-link" href="' . esc_url( $m['anchor'] ) . '">' . esc_html__( 'Ver biografía completa', 'nuvanx-medical' ) . '</a></p>';
		if ( '' !== $m['extra'] ) {
			$html .= '<p class="nvx-nosotros-platform-link"><a class="nvx-brand-inline-link" href="' . esc_url( $m['extra'] ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Perfil en Doctoralia', 'nuvanx-medical' ) . '</a></p>';
		}
		$html .= '</article>';
	}

	$html .= '</div>';
	$html .= '<p class="nvx-endolift-body"><a class="nvx-brand-inline-link" href="' . esc_url( $equipo ) . '">' . esc_html__( 'Conocer al equipo médico completo', 'nuvanx-medical' ) . '</a></p>';
	$html .= '</div></section>';

	return $html;
}

/**
 * Non-negotiable medical principles.
 */
function nvx_nosotros_principles_markup(): string {
	$items = array(
		array(
			'title' => __( 'Diagnóstico tisular estricto', 'nuvanx-medical' ),
			'body'  => __( 'Ningún láser se enciende sin indicación médica. Evaluamos calidad dérmica, grado de ptosis y anatomía facial o corporal. Si el caso requiere cirugía, lo comunicamos con honestidad y derivamos al especialista correspondiente.', 'nuvanx-medical' ),
		),
		array(
			'title' => __( 'Transparencia transaccional', 'nuvanx-medical' ),
			'body'  => __( 'Rangos de inversión claros y presupuestos médicos cerrados tras la valoración, sin costes ocultos. La claridad sustituye al hermetismo tradicional del sector.', 'nuvanx-medical' ),
		),
		array(
			'title' => __( 'Tecnología con evidencia', 'nuvanx-medical' ),
			'body'  => __( 'Solo incorporamos dispositivos con certificación CE y respaldo en estudios clínicos publicados. Nunca por tendencia de mercado.', 'nuvanx-medical' ),
		),
		array(
			'title' => __( 'Seguimiento médico reglado', 'nuvanx-medical' ),
			'body'  => __( 'Monitorizamos la evolución y la respuesta celular a corto, medio y largo plazo. La responsabilidad clínica no termina al salir de la consulta.', 'nuvanx-medical' ),
		),
	);

	$html  = '<section class="nvx-endolift-section nvx-nosotros-principles" aria-labelledby="nvx-nosotros-principles-title">';
	$html .= '<div class="nvx-endolift-section__inner">';
	$html .= '<p class="nvx-endolift-kicker">' . esc_html__( 'Principios', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-nosotros-principles-title" class="nvx-endolift-heading">' . esc_html__( 'Principios médicos innegociables', 'nuvanx-medical' ) . '</h2>';
	$html .= '<ul class="nvx-endolaser-zone-list">';
	foreach ( $items as $item ) {
		$html .= '<li class="nvx-endolaser-zone">';
		$html .= '<h3 class="nvx-endolaser-zone__title">' . esc_html( $item['title'] ) . '</h3>';
		$html .= '<p class="nvx-endolift-body">' . esc_html( $item['body'] ) . '</p>';
		$html .= '</li>';
	}
	$html .= '</ul></div></section>';

	return $html;
}

/**
 * Full editorial body.
 * Closing valoración CTA: site-wide nvx-cta-banner in footer.php.
 */
function nvx_nosotros_editorial_body_markup(): string {
	$html  = '<div class="nvx-nosotros-editorial nvx-endolift-editorial">';
	$html .= nvx_nosotros_positioning_markup();
	$html .= nvx_nosotros_platforms_markup();
	$html .= nvx_nosotros_clinics_markup();
	$html .= nvx_nosotros_team_markup();
	$html .= nvx_nosotros_principles_markup();
	$html .= '</div>';

	return $html;
}

/**
 * Rebuild nosotros page content once.
 */
function nvx_content_restructure_nosotros_page( string $content ): string {
	if ( ! nvx_content_is_nosotros_page( $content ) ) {
		return $content;
	}

	$media = '';
	if ( preg_match( '/<figure class="nvx-brand-hero__media"[\s\S]*?<\/figure>/iu', $content, $m ) ) {
		$media = $m[0];
	} elseif ( preg_match( '/<div class="nvx-brand-hero__media"[\s\S]*?<\/div>/iu', $content, $m ) ) {
		$media = $m[0];
	}
	// Drop logo-as-hero if helper exists.
	if ( '' !== $media && function_exists( 'nvx_equipo_media_is_logo' ) && nvx_equipo_media_is_logo( $media ) ) {
		$media = '';
	}

	$hero_classes = 'nvx-brand-hero nvx-brand-hero--laser nvx-endolift-hero nvx-nosotros-hero';
	if ( '' === $media ) {
		$hero_classes .= ' nvx-nosotros-hero--copy-only';
	}

	$hero  = '<section class="' . esc_attr( $hero_classes ) . '" aria-labelledby="nvx-nosotros-h1" aria-label="' . esc_attr__( 'Sobre Nosotros NUVANX', 'nuvanx-medical' ) . '">';
	$hero .= '<div class="nvx-brand-hero__inner">';
	$hero .= nvx_nosotros_hero_copy_markup();
	$hero .= $media;
	$hero .= '</div></section>';

	$body = nvx_nosotros_editorial_body_markup();

	if ( preg_match( '/(<div class="nvx-brand-page[^"]*"[^>]*>)/iu', $content, $wrap ) ) {
		$open = $wrap[1];
		if ( false === strpos( $open, 'nvx-brand-page--nosotros' ) ) {
			$open = preg_replace( '/\bclass=(["\'])/u', 'class=$1nvx-brand-page--nosotros ', $open, 1 ) ?? $open;
		}
		return $open . $hero . $body . '</div>';
	}

	return '<div class="nvx-brand-page nvx-brand-page--nosotros">' . $hero . $body . '</div>';
}
add_filter( 'the_content', 'nvx_content_restructure_nosotros_page', 19 );

/**
 * Document title for nosotros.
 *
 * @param string $title Title.
 * @return string
 */
function nvx_filter_nosotros_document_title( $title ) {
	if ( ! function_exists( 'nvx_schema_path_matches' ) || ! function_exists( 'nvx_schema_current_path' ) ) {
		return $title;
	}
	$path = nvx_schema_current_path( (int) get_queried_object_id() );
	if ( ! nvx_schema_path_matches( $path, '/nosotros/' ) && ! nvx_schema_path_matches( $path, '/sobre-nosotros/' ) ) {
		return $title;
	}
	return 'Sobre Nosotros | Autoridad médica y transparencia · NUVANX Madrid';
}
add_filter( 'wpseo_title', 'nvx_filter_nosotros_document_title', 21 );

/**
 * Meta description for nosotros.
 *
 * @param string $desc Description.
 * @return string
 */
function nvx_filter_nosotros_metadesc( $desc ) {
	if ( ! function_exists( 'nvx_schema_path_matches' ) || ! function_exists( 'nvx_schema_current_path' ) ) {
		return $desc;
	}
	$path = nvx_schema_current_path( (int) get_queried_object_id() );
	if ( ! nvx_schema_path_matches( $path, '/nosotros/' ) && ! nvx_schema_path_matches( $path, '/sobre-nosotros/' ) ) {
		return $desc;
	}
	return 'NUVANX Madrid: medicina estética láser con evidencia, well-aging e ingeniería tisular. Sedes Chamberí (CS20144) y Goya (CS20073). Cuadro médico colegiado y principios de transparencia.';
}
add_filter( 'wpseo_metadesc', 'nvx_filter_nosotros_metadesc', 21 );
