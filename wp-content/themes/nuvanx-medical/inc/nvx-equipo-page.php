<?php
/**
 * Equipo médico — E-E-A-T page for Dr. José Javier Rivera Tejeda.
 *
 * Wire-frame: Hero → Perfil director → Subespecialización → Formación → Cita clínica → CTA.
 * Does not invent AggregateRating schema; Doctoralia is linked as public profile.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Singular context.
 */
function nvx_equipo_is_singular_context(): bool {
	if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return false;
	}

	return is_singular( 'page' ) || is_page();
}

/**
 * Detect equipo médico page only (path/markers — not every Rivera mention sitewide).
 */
function nvx_content_is_equipo_page( string $content ): bool {
	if ( false !== strpos( $content, 'nvx-equipo-editorial' ) ) {
		return false;
	}

	if ( ! nvx_equipo_is_singular_context() ) {
		return false;
	}

	if ( is_front_page() || is_home() ) {
		return false;
	}

	$path = function_exists( 'nvx_schema_current_path' )
		? nvx_schema_current_path( (int) get_queried_object_id() )
		: '';

	if ( is_string( $path ) && function_exists( 'nvx_schema_path_matches' ) && nvx_schema_path_matches( $path, '/equipo-medico/' ) ) {
		return true;
	}

	return (bool) preg_match(
		'/aria-label=["\']Equipo médico NUVANX["\']|id=["\']nvx-equipo-h1["\']|class=["\'][^"\']*nvx-equipo-hero/iu',
		$content
	);
}

/**
 * Hero.
 */
function nvx_equipo_hero_copy_markup(): string {
	$colegiado = defined( 'NVX_DIRECTOR_COLEGIADO' ) ? NVX_DIRECTOR_COLEGIADO : '282864786';

	$html  = '<div class="nvx-brand-hero__copy nvx-equipo-hero-copy">';
	$html .= '<p class="nvx-brand-kicker">' . esc_html__( 'NUVANX · Equipo médico', 'nuvanx-medical' ) . '</p>';
	$html .= '<h1 class="nvx-brand-hero__title" id="nvx-equipo-h1">' . esc_html__( 'Equipo Médico: Autoridad Clínica Liderada por el Dr. José Javier Rivera Tejeda', 'nuvanx-medical' ) . '</h1>';
	$html .= '<p class="nvx-brand-hero__lead">' . esc_html__( 'Dirección médica de las clínicas NUVANX en Madrid — láser intervencionista, regeneración tisular y criterio de indicación antes que el catálogo de aparatología.', 'nuvanx-medical' ) . '</p>';
	$html .= '<p class="nvx-brand-hero__description">' . esc_html(
		sprintf(
			/* translators: %s: medical license number */
			__( 'Colegiado ICOMEM Nº %s. Responsable del diseño de protocolos Endolift®, laserlipólisis, CO₂ fraccionado y medicina estética en ambas sedes.', 'nuvanx-medical' ),
			$colegiado
		)
	) . '</p>';

	if ( function_exists( 'nvx_cta_pair_markup' ) ) {
		$html .= nvx_cta_pair_markup( 'nvx-equipo-hero-ctas nvx-home-hero-ctas' );
	}

	$html .= '<p class="nvx-brand-meta">' . esc_html__( 'Chamberí · Martes y jueves · Goya · Miércoles', 'nuvanx-medical' ) . '</p>';
	$html .= '</div>';

	return $html;
}

/**
 * Action CTAs.
 */
function nvx_equipo_action_ctas_markup(): string {
	$valoracion = function_exists( 'nvx_cta_valoracion_url' )
		? nvx_cta_valoracion_url()
		: home_url( '/madrid/valoracion/' );
	$doctoralia = 'https://www.doctoralia.es/jose-javier-rivera-tejeda/medico-estetico/madrid';

	$html  = '<div class="nvx-cta-pair nvx-endolift-action__ctas">';
	$html .= sprintf(
		'<a class="nvx-brand-btn nvx-brand-btn--primary" href="%1$s">%2$s</a>',
		esc_url( $valoracion ),
		esc_html__( 'Reservar valoración médica', 'nuvanx-medical' )
	);
	$html .= sprintf(
		'<a class="nvx-brand-btn nvx-brand-btn--secondary" href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
		esc_url( $doctoralia ),
		esc_html__( 'Perfil en Doctoralia', 'nuvanx-medical' )
	);
	$html .= '</div>';

	return $html;
}

/**
 * Whether a card/block is the director Rivera Tejeda (not other clinicians).
 */
function nvx_equipo_block_is_rivera( string $html ): bool {
	return (bool) preg_match( '/Rivera\s+Tejeda|Jos[eé]\s+Javier\s+Rivera/iu', $html );
}

/**
 * Extract staff cards from CMS content, split director vs rest of team.
 *
 * @return array{rivera_cards:string[],other_cards:string[],rivera_media:string}
 */
function nvx_equipo_extract_staff_cards( string $content ): array {
	$rivera_cards = array();
	$other_cards  = array();
	$rivera_media = '';

	// Prefer full articles; fall back to div.nvx-brand-card.
	$patterns = array(
		'/<article\b[^>]*\bclass=["\'][^"\']*\bnvx-brand-card\b[^"\']*["\'][^>]*>[\s\S]*?<\/article>/iu',
		'/<div\b[^>]*\bclass=["\'][^"\']*\bnvx-brand-card\b[^"\']*["\'][^>]*>[\s\S]*?<\/div>\s*(?=<div\b[^>]*\bnvx-brand-card\b|<section\b|<\/section>|$)/iu',
	);

	$found = array();
	foreach ( $patterns as $pattern ) {
		if ( preg_match_all( $pattern, $content, $m ) && ! empty( $m[0] ) ) {
			$found = $m[0];
			break;
		}
	}

	foreach ( $found as $card ) {
		if ( nvx_equipo_block_is_rivera( $card ) ) {
			$rivera_cards[] = $card;
			if ( '' === $rivera_media && preg_match( '/<figure\b[\s\S]*?<\/figure>|<img\b[^>]*>/iu', $card, $im ) ) {
				$rivera_media = $im[0];
			}
			continue;
		}
		$other_cards[] = $card;
	}

	return array(
		'rivera_cards' => $rivera_cards,
		'other_cards'  => $other_cards,
		'rivera_media' => $rivera_media,
	);
}

/**
 * Markup for the rest of the clinical team (preserved from CMS).
 *
 * @param string[] $other_cards HTML cards.
 */
function nvx_equipo_other_staff_section_markup( array $other_cards ): string {
	if ( empty( $other_cards ) ) {
		return '';
	}

	$html  = '<section class="nvx-endolift-section nvx-equipo-staff" aria-labelledby="nvx-equipo-staff-title">';
	$html .= '<div class="nvx-endolift-section__inner">';
	$html .= '<p class="nvx-endolift-kicker">' . esc_html__( 'Equipo clínico', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-equipo-staff-title" class="nvx-endolift-heading">' . esc_html__( 'Resto del equipo médico NUVANX', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p class="nvx-endolift-body nvx-endolift-body--measure">' . esc_html__( 'Profesionales que atienden valoración, seguimiento y protocolos en Chamberí y Goya bajo la dirección médica de la clínica.', 'nuvanx-medical' ) . '</p>';
	$html .= '<div class="nvx-brand-grid nvx-equipo-staff-grid">';
	foreach ( $other_cards as $card ) {
		// Ensure portrait role on team cards when media present.
		if ( false === strpos( $card, 'nvx-brand-card--team' ) && preg_match( '/\bclass=(["\'])/u', $card ) ) {
			$card = preg_replace( '/\bclass=(["\'])/u', 'class=$1nvx-brand-card--team ', $card, 1 ) ?? $card;
		}
		$html .= $card;
	}
	$html .= '</div></div></section>';

	return $html;
}

/**
 * Director authority block only (not a full-page wipe of the team).
 *
 * @param string $rivera_media Optional portrait HTML for the director.
 */
function nvx_equipo_director_authority_markup( string $rivera_media = '' ): string {
	$colegiado  = defined( 'NVX_DIRECTOR_COLEGIADO' ) ? NVX_DIRECTOR_COLEGIADO : '282864786';
	$doctoralia = 'https://www.doctoralia.es/jose-javier-rivera-tejeda/medico-estetico/madrid';

	$html  = '<div class="nvx-equipo-director" id="physician-rivera-tejeda">';

	// A. Profile (+ optional portrait from his CMS card only).
	$html .= '<section class="nvx-endolift-section nvx-equipo-profile" aria-labelledby="nvx-equipo-profile-title">';
	$html .= '<div class="nvx-endolift-section__inner">';
	if ( '' !== $rivera_media ) {
		$html .= '<div class="nvx-equipo-director-media nvx-brand-card__media nvx-brand-card__media--portrait">' . $rivera_media . '</div>';
	}
	$html .= '<p class="nvx-endolift-kicker">' . esc_html__( 'Director médico', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-equipo-profile-title" class="nvx-endolift-heading">' . esc_html__( 'Dr. José Javier Rivera Tejeda: Dirección Médica e Investigación Clínica Aplicada', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p class="nvx-endolift-body nvx-endolift-body--measure">' . esc_html(
		sprintf(
			/* translators: %s: medical license number */
			__( 'Con número de colegiación ICOMEM %s, el Dr. José Javier Rivera Tejeda ostenta la Dirección Médica de las clínicas NUVANX en Madrid. Médico estético especializado en tecnologías láser intervencionistas y medicina regenerativa tisular.', 'nuvanx-medical' ),
			$colegiado
		)
	) . '</p>';
	$html .= '<p class="nvx-endolift-body nvx-endolift-body--measure">' . wp_kses(
		sprintf(
			/* translators: %s: Doctoralia URL */
			__( 'Su perfil público en <a class="nvx-brand-inline-link" href="%s" target="_blank" rel="noopener noreferrer">Doctoralia</a> concentra reseñas certificadas de pacientes (consultables en el directorio). Es el responsable del diseño de los protocolos de tratamiento en NUVANX: la aparatología se subordina al diagnóstico, no al revés.', 'nuvanx-medical' ),
			esc_url( $doctoralia )
		),
		array(
			'a' => array(
				'class'  => true,
				'href'   => true,
				'target' => true,
				'rel'    => true,
			),
		)
	) . '</p>';
	$html .= '</div></section>';

	// B. Subespecialización.
	$html .= '<section class="nvx-endolift-section nvx-equipo-scope" aria-labelledby="nvx-equipo-scope-title">';
	$html .= '<div class="nvx-endolift-section__inner">';
	$html .= '<p class="nvx-endolift-kicker">' . esc_html__( 'Ámbito clínico', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-equipo-scope-title" class="nvx-endolift-heading">' . esc_html__( 'Subespecialización y experiencia', 'nuvanx-medical' ) . '</h2>';
	$html .= '<ul class="nvx-endolaser-zone-list">';
	$scopes = array(
		array(
			'title' => __( 'Láser intersticial avanzado', 'nuvanx-medical' ),
			'body'  => __( 'Endolift® y laserlipólisis para modificación estructural de grasa submentoniana y corporal en casos seleccionados.', 'nuvanx-medical' ),
		),
		array(
			'title' => __( 'Dermatología láser ablativa', 'nuvanx-medical' ),
			'body'  => __( 'Láser CO₂ fraccionado orientado a secuelas de acné, textura y fotodaño, con planificación de downtime.', 'nuvanx-medical' ),
		),
		array(
			'title' => __( 'Arquitectura y geometría facial', 'nuvanx-medical' ),
			'body'  => __( 'Restauración volumétrica con inductores de colágeno (p. ej. Radiesse®, Ellansé®) y neuromoduladores cuando el diagnóstico lo indica — tras tensar, no al revés.', 'nuvanx-medical' ),
		),
		array(
			'title' => __( 'Tricología médica', 'nuvanx-medical' ),
			'body'  => __( 'Abordaje médico del cabello y cuero cabelludo dentro del alcance de la consulta especializada.', 'nuvanx-medical' ),
		),
	);
	foreach ( $scopes as $scope ) {
		$html .= '<li class="nvx-endolaser-zone">';
		$html .= '<h3 class="nvx-endolaser-zone__title">' . esc_html( $scope['title'] ) . '</h3>';
		$html .= '<p class="nvx-endolift-body">' . esc_html( $scope['body'] ) . '</p>';
		$html .= '</li>';
	}
	$html .= '</ul></div></section>';

	// C. Formación.
	$html .= '<section class="nvx-endolift-section nvx-equipo-formation" aria-labelledby="nvx-equipo-form-title">';
	$html .= '<div class="nvx-endolift-section__inner nvx-endolift-diagnosis__grid">';
	$html .= '<div class="nvx-endolift-diagnosis__copy">';
	$html .= '<p class="nvx-endolift-kicker">' . esc_html__( 'Formación', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-equipo-form-title" class="nvx-endolift-heading">' . esc_html__( 'Formación académica y trayectoria', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p class="nvx-endolift-body">' . esc_html__( 'Máster Universitario en Medicina Estética por la Universidad Complutense de Madrid (UCM). Máster especializado en Tricología y Cirugía Capilar (AMIR).', 'nuvanx-medical' ) . '</p>';
	$html .= '<p class="nvx-endolift-body">' . esc_html__( 'Trayectoria como director de cirugía cosmética láser en cadenas hospitalarias de referencia (Clínicas Londres, Clínicas Dr. Esquivel), aplicada hoy al modelo de doble sede NUVANX.', 'nuvanx-medical' ) . '</p>';
	$html .= '</div>';
	$html .= '<aside class="nvx-endolift-diagnosis__panel" aria-label="' . esc_attr__( 'Identidad profesional', 'nuvanx-medical' ) . '">';
	$html .= '<p class="nvx-endolift-panel-label">' . esc_html__( 'Identidad', 'nuvanx-medical' ) . '</p>';
	$html .= '<ul class="nvx-endolift-panel-list">';
	$html .= '<li><strong>' . esc_html__( 'Colegiado', 'nuvanx-medical' ) . '</strong> — ICOMEM ' . esc_html( $colegiado ) . '</li>';
	$html .= '<li><strong>' . esc_html__( 'Cargo', 'nuvanx-medical' ) . '</strong> — ' . esc_html__( 'Director médico NUVANX Madrid', 'nuvanx-medical' ) . '</li>';
	$html .= '<li><strong>' . esc_html__( 'Sedes', 'nuvanx-medical' ) . '</strong> — ' . esc_html__( 'Chamberí y Goya · Barrio Salamanca', 'nuvanx-medical' ) . '</li>';
	$html .= '<li><strong>' . esc_html__( 'Agenda', 'nuvanx-medical' ) . '</strong> — ' . esc_html__( 'Mar/Jue Chamberí · Mié Goya', 'nuvanx-medical' ) . '</li>';
	$html .= '</ul></aside></div></section>';

	// D. Quote.
	$html .= '<section class="nvx-endolift-section nvx-equipo-quote" aria-labelledby="nvx-equipo-quote-title">';
	$html .= '<div class="nvx-endolift-section__inner">';
	$html .= '<h2 id="nvx-equipo-quote-title" class="screen-reader-text">' . esc_html__( 'Visión clínica', 'nuvanx-medical' ) . '</h2>';
	$html .= '<blockquote class="nvx-equipo-blockquote">';
	$html .= '<p>' . esc_html__( 'Mi visión clínica rechaza la transformación anatómica artificial. La tecnología láser más sofisticada debe emplearse para desencadenar la regeneración celular propia del paciente, logrando una firmeza biológica real, no un aspecto quirúrgico evidente.', 'nuvanx-medical' ) . '</p>';
	$html .= '<footer>— ' . esc_html__( 'Dr. J.J. Rivera Tejeda', 'nuvanx-medical' ) . '</footer>';
	$html .= '</blockquote></div></section>';

	$html .= '</div>';

	return $html;
}

/**
 * Closing CTA for equipo page.
 */
function nvx_equipo_closing_cta_markup(): string {
	$html  = '<section class="nvx-endolift-action" aria-label="' . esc_attr__( 'Reservar valoración con el equipo médico', 'nuvanx-medical' ) . '">';
	$html .= '<div class="nvx-endolift-action__inner">';
	$html .= '<div>';
	$html .= '<p class="nvx-endolift-action__kicker">' . esc_html__( 'Valoración médica', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 class="nvx-endolift-action__title">' . esc_html__( 'Consulta con criterio médico, no con catálogo', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p class="nvx-endolift-action__text">' . esc_html__( 'Agenda una valoración presencial en Chamberí o Goya. Indicación, límites y plan documentado antes de cualquier procedimiento.', 'nuvanx-medical' ) . '</p>';
	$html .= '</div>';
	$html .= nvx_equipo_action_ctas_markup();
	$html .= '</div></section>';

	return $html;
}

/**
 * Rebuild equipo page: director authority + preserve other clinicians from CMS.
 */
function nvx_content_restructure_equipo_page( string $content ): string {
	if ( ! nvx_content_is_equipo_page( $content ) ) {
		return $content;
	}

	$staff = nvx_equipo_extract_staff_cards( $content );

	// Hero media: only real page hero — never steal another doctor's portrait.
	$media = '';
	if ( preg_match( '/<figure class="nvx-brand-hero__media"[\s\S]*?<\/figure>/iu', $content, $m ) ) {
		$media = $m[0];
	} elseif ( preg_match( '/<div class="nvx-brand-hero__media"[\s\S]*?<\/div>/iu', $content, $m ) ) {
		$media = $m[0];
	}

	$hero  = '<section class="nvx-brand-hero nvx-brand-hero--laser nvx-endolift-hero nvx-equipo-hero" aria-labelledby="nvx-equipo-h1" aria-label="' . esc_attr__( 'Equipo médico NUVANX', 'nuvanx-medical' ) . '">';
	$hero .= '<div class="nvx-brand-hero__inner">';
	$hero .= nvx_equipo_hero_copy_markup();
	$hero .= $media;
	$hero .= '</div></section>';

	// Order: director authority → resto del equipo (CMS) → CTA.
	$body  = '<div class="nvx-equipo-editorial nvx-endolift-editorial">';
	$body .= nvx_equipo_director_authority_markup( $staff['rivera_media'] );
	$body .= nvx_equipo_other_staff_section_markup( $staff['other_cards'] );
	$body .= nvx_equipo_closing_cta_markup();
	$body .= '</div>';

	if ( preg_match( '/(<div class="nvx-brand-page[^"]*"[^>]*>)/iu', $content, $wrap ) ) {
		return $wrap[1] . $hero . $body . '</div>';
	}

	return $hero . $body;
}
add_filter( 'the_content', 'nvx_content_restructure_equipo_page', 19 );
