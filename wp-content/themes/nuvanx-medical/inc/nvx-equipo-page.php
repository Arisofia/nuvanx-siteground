<?php
/**
 * Equipo médico — E-E-A-T: Rivera Tejeda + Rivera Deras + Quiñónez Bareiro + rest of staff.
 *
 * Wire-frame: Hero → Director → Dra. Ivon → Dr. Fabio → Resto CMS → CTA.
 * Schema Physicians via Yoast graph only (no standalone ld+json). No AggregateRating hardcode.
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
	$colegiado_dir   = defined( 'NVX_DIRECTOR_COLEGIADO' ) ? NVX_DIRECTOR_COLEGIADO : '282864786';
	$colegiado_ivon  = defined( 'NVX_IVON_COLEGIADO' ) ? NVX_IVON_COLEGIADO : '284621525';
	$colegiado_fabio = defined( 'NVX_FABIO_COLEGIADO' ) ? NVX_FABIO_COLEGIADO : '282877543';

	$html  = '<div class="nvx-editorial-hero__copy nvx-equipo-hero-copy">';
	$html .= '<p class="nvx-eyebrow">' . esc_html__( 'NUVANX · Equipo médico', 'nuvanx-medical' ) . '</p>';
	$html .= '<h1 class="nvx-heading" id="nvx-equipo-h1">' . esc_html__( 'Equipo médico NUVANX: quién te valora y quién trata', 'nuvanx-medical' ) . '</h1>';
	$html .= '<p class="nvx-lead">' . esc_html__( 'En muchas clínicas, quien te atiende al principio no es quien luego te trata — te ve un comercial, y el médico solo aparece para aplicar lo que ya se vendió. Aquí no. La persona que te explora es la misma que te trata y la que te sigue viendo después. Nadie cambia a mitad de tu plan.', 'nuvanx-medical' ) . '</p>';
	$html .= '<p class="nvx-lead">' . esc_html(
		sprintf(
			/* translators: 1: director license, 2: Dra. Ivon license, 3: Dr. Fabio license */
			__( 'Dr. José Javier Rivera Tejeda (ICOMEM %1$s), director médico; Dra. Ivon Yamileth Rivera Deras (ICOMEM %2$s), well-aging y geriatría preventiva; y Dr. Fabio Augusto Quiñónez Bareiro (ICOMEM %3$s), geriatría y paciente complejo — junto al resto del equipo clínico NUVANX.', 'nuvanx-medical' ),
			$colegiado_dir,
			$colegiado_ivon,
			$colegiado_fabio
		)
	) . '</p>';

	if ( function_exists( 'nvx_cta_pair_markup' ) ) {
		$html .= nvx_cta_pair_markup( 'nvx-equipo-hero-ctas nvx-home-hero-ctas' );
	}

	$html .= '<p class="nvx-brand-meta">' . esc_html__( 'Chamberí · Goya · Medicina basada en evidencia', 'nuvanx-medical' ) . '</p>';
	$html .= '</div>';

	return $html;
}

/**
 * Whether media HTML is a logo / non-portrait asset (never use as staff/hero photo).
 */
function nvx_equipo_media_is_logo( string $html ): bool {
	return (bool) preg_match(
		'/logo-nuvanx|nuvanx-web\.webp|\/logo[-_]|nvx-logo|site-logo|custom-logo/iu',
		$html
	);
}

/**
 * Normalize a portrait snippet to a single clean <img> (doctor crop).
 *
 * @param string $media Figure or img HTML from CMS.
 * @return string Safe img markup or empty.
 */
function nvx_equipo_clean_portrait_img( string $media ): string {
	if ( '' === trim( $media ) || nvx_equipo_media_is_logo( $media ) ) {
		return '';
	}

	// Prefer real <img> over noscript twin / decorative placeholders.
	if ( ! preg_match( '/<img\b([^>]*)>/iu', $media, $m ) ) {
		return '';
	}

	$attrs = $m[1];

	// Lazyload placeholder: promote data-src / data-lazy-src to real src.
	if ( preg_match( '/\ssrc=["\']data:image\//i', $attrs ) || preg_match( '/\ssrc=["\']["\']/i', $attrs ) ) {
		if ( preg_match( '/\sdata-(?:src|lazy-src|original)=["\']([^"\']+)["\']/i', $attrs, $ds ) ) {
			$real = esc_url( $ds[1] );
			if ( '' !== $real ) {
				if ( preg_match( '/\ssrc=/i', $attrs ) ) {
					$attrs = nvx_content_preg_replace_keep( '/\ssrc=["\'][^"\']*["\']/i', ' src="' . $real . '"', $attrs, 1 );
				} else {
					$attrs .= ' src="' . $real . '"';
				}
			}
		}
	}

	// Drop inline size/style that fights portrait crop; strip body role.
	$attrs = nvx_content_preg_replace_keep( '/\s+style=["\'][^"\']*["\']/i', '', $attrs );
	$attrs = nvx_content_preg_replace_keep( '/\s+(?:width|height)=["\'][^"\']*["\']/i', '', $attrs ) ?? $attrs;
	$attrs = nvx_content_preg_replace_keep( '/\s*nvx-media--body\s*/i', ' ', $attrs );
	// Re-emit loading/decoding once (CMS + cleaners often duplicate).
	$attrs = nvx_content_preg_replace_keep( '/\s+loading=["\'][^"\']*["\']/i', '', $attrs );
	$attrs = nvx_content_preg_replace_keep( '/\s+decoding=["\'][^"\']*["\']/i', '', $attrs );
	// Drop leftover placeholder-only srcset noise when src is real file.
	if ( preg_match( '/\ssrc=["\']https?:\/\//i', $attrs ) ) {
		// Keep srcset/sizes when present for responsive; strip only data-src twins later.
	}

	if ( function_exists( 'nvx_html_attrs_add_class' ) ) {
		$attrs = nvx_html_attrs_add_class( $attrs, 'nvx-media' );
		$attrs = nvx_html_attrs_add_class( $attrs, 'nvx-media--doctor' );
	} elseif ( ! preg_match( '/\bclass=/i', $attrs ) ) {
		$attrs .= ' class="nvx-media nvx-media--doctor"';
	}

	return '<img' . $attrs . ' loading="lazy" decoding="async">';
}

/**
 * Whether a CMS card is a real clinician (photo + person name), not sedes/reseñas/listas.
 */
function nvx_equipo_is_person_staff_card( string $card ): bool {
	if ( ! preg_match( '/<img\b/i', $card ) ) {
		return false;
	}
	if ( nvx_equipo_media_is_logo( $card ) ) {
		return false;
	}

	// Prefer cards with a named title (person).
	if ( preg_match( '/nvx-brand-card__title[^>]*>([\s\S]*?)<\//iu', $card, $tm ) ) {
		$title = trim( wp_strip_all_tags( $tm[1] ) );
		if ( '' === $title ) {
			return false;
		}
		// Titles that are places, proof widgets, or section headers — not people.
		if ( preg_match(
			'/^(Chamber[ií]|Goya\b|Especialidades|NUVANX Medicina|NUVANX en Doctoralia|Reseñas)/iu',
			$title
		) ) {
			return false;
		}
		return true;
	}

	// No title: drop review/list chrome; keep only cards with portrait media.
	if ( preg_match( '/NUVANX en Doctoralia|Reseñas públicas|Especialidades y tecnolog/iu', $card ) ) {
		return false;
	}

	return (bool) preg_match( '/nvx-brand-card__media/i', $card );
}

/**
 * Portrait frame markup for authority profiles.
 */
function nvx_equipo_portrait_figure_markup( string $media, string $label ): string {
	$img = nvx_equipo_clean_portrait_img( $media );
	if ( '' === $img ) {
		return '';
	}

	return '<figure class="nvx-equipo-portrait" aria-label="' . esc_attr( $label ) . '">' . $img . '</figure>';
}

/**
 * Whether a card/block is the director Rivera Tejeda.
 */
function nvx_equipo_block_is_rivera_tejeda( string $html ): bool {
	return (bool) preg_match( '/Rivera\s+Tejeda|Jos[eé]\s+Javier\s+Rivera/iu', $html );
}

/**
 * Whether a card/block is Dra. Ivon Yamileth Rivera Deras.
 */
function nvx_equipo_block_is_ivon( string $html ): bool {
	return (bool) preg_match( '/Ivon|Yamileth|Rivera\s+Deras/iu', $html );
}

/**
 * Whether a card/block is Dr. Fabio Augusto Quiñónez Bareiro.
 */
function nvx_equipo_block_is_fabio( string $html ): bool {
	return (bool) preg_match( '/Fabio|Qui[nñ][oó]nez|Bareiro/iu', $html );
}

/**
 * Extract staff cards from CMS: director, Dra. Ivon, Dr. Fabio, rest of team.
 *
 * @return array{rivera_media:string,ivon_media:string,fabio_media:string,other_cards:string[]}
 */
function nvx_equipo_extract_staff_cards( string $content ): array {
	$other_cards  = array();
	$rivera_media = '';
	$ivon_media   = '';
	$fabio_media  = '';

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
		if ( nvx_equipo_block_is_rivera_tejeda( $card ) ) {
			if ( '' === $rivera_media && preg_match( '/<figure\b[\s\S]*?<\/figure>|<img\b[^>]*>/iu', $card, $im ) ) {
				$rivera_media = $im[0];
			}
			// Long-form authority replaces short card for director.
			continue;
		}
		if ( nvx_equipo_block_is_ivon( $card ) ) {
			if ( '' === $ivon_media && preg_match( '/<figure\b[\s\S]*?<\/figure>|<img\b[^>]*>/iu', $card, $im ) ) {
				$ivon_media = $im[0];
			}
			// Long-form authority replaces short card for Dra. Ivon.
			continue;
		}
		if ( nvx_equipo_block_is_fabio( $card ) ) {
			if ( '' === $fabio_media && preg_match( '/<figure\b[\s\S]*?<\/figure>|<img\b[^>]*>/iu', $card, $im ) ) {
				$fabio_media = $im[0];
			}
			// Long-form authority replaces short card for Dr. Fabio.
			continue;
		}
		// Only real clinician cards — drop sedes, reseñas, listas, chrome vacío.
		if ( nvx_equipo_is_person_staff_card( $card ) ) {
			$other_cards[] = $card;
		}
	}

	return array(
		'rivera_media' => $rivera_media,
		'ivon_media'   => $ivon_media,
		'fabio_media'  => $fabio_media,
		'other_cards'  => $other_cards,
	);
}

/**
 * Normalize a CMS staff card: team class + portrait media crop.
 */
function nvx_equipo_normalize_staff_card( string $card ): string {
	if ( preg_match( '/\bclass=(["\'])/u', $card ) && false === strpos( $card, 'nvx-brand-card--team' ) ) {
		$card = nvx_content_preg_replace_keep( '/\bclass=(["\'])/u', 'class=$1nvx-brand-card--team ', $card, 1 ) ?? $card;
	}

	// Portrait frame: single clean img, no noscript/br noise inside figure.
	$card = nvx_content_preg_replace_keep(
		'/(<figure\b[^>]*\bclass=["\'][^"\']*\bnvx-brand-card__media\b)([^"\']*)(["\'][^>]*>)([\s\S]*?)(<\/figure>)/iu',
		static function ( array $m ): string {
			$open = $m[1] . $m[2];
			if ( false === strpos( $open . $m[3], 'nvx-brand-card__media--portrait' ) ) {
				$open .= ' nvx-brand-card__media--portrait';
			}
			$open = nvx_content_preg_replace_keep( '/\s*nvx-content-figure\s*/i', ' ', $open );
			$img  = nvx_equipo_clean_portrait_img( $m[4] );
			if ( '' === $img ) {
				return $open . $m[3] . $m[5];
			}
			return $open . $m[3] . $img . $m[5];
		},
		$card
	) ?? $card;

	// Bare img without figure.
	if ( false === strpos( $card, 'nvx-brand-card__media' ) && preg_match( '/<img\b[^>]*>/iu', $card, $im ) ) {
		$img = nvx_equipo_clean_portrait_img( $im[0] );
		if ( '' !== $img ) {
			$card = nvx_content_preg_replace_keep( '/<noscript\b[\s\S]*?<\/noscript>/iu', '', $card );
			$card = nvx_content_preg_replace_keep(
				'/<img\b[^>]*>/iu',
				'<figure class="nvx-brand-card__media nvx-brand-card__media--portrait">' . $img . '</figure>',
				$card,
				1
			);
		}
	}

	$card = nvx_content_preg_replace_keep( '/<br\s*\/?>/iu', '', $card );

	return is_string( $card ) ? $card : $card; /* fixed */
}

/**
 * Markup for remaining clinical team (CMS cards, not the two authority profiles).
 *
 * @param string[] $other_cards HTML cards.
 */
function nvx_equipo_other_staff_section_markup( array $other_cards ): string {
	if ( empty( $other_cards ) ) {
		return '';
	}

	$html  = '<section class="nvx-editorial-section nvx-equipo-staff" aria-labelledby="nvx-equipo-staff-title">';
	$html .= '<div class="nvx-editorial-section__inner">';
	$html .= '<p class="nvx-editorial-kicker">' . esc_html__( 'Equipo clínico', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-equipo-staff-title" class="nvx-editorial-heading">' . esc_html__( 'Resto del equipo médico NUVANX', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p class="nvx-editorial-body nvx-editorial-body--measure">' . esc_html__( 'Profesionales que atienden valoración, seguimiento y protocolos en Chamberí y Goya, junto a la dirección médica y al criterio científico de la clínica.', 'nuvanx-medical' ) . '</p>';
	$html .= '<div class="nvx-equipo-staff-grid">';
	foreach ( $other_cards as $card ) {
		$card = nvx_equipo_normalize_staff_card( $card );
		if ( '' !== $card ) {
			$html .= $card;
		}
	}
	$html .= '</div></div></section>';

	return $html;
}

/**
 * Builds the medical director's profile, clinical scope, training, and clinical vision sections.
 *
 * @param string $rivera_media Optional portrait HTML for the director.
 * @return string The rendered director authority markup.
 */
function nvx_equipo_director_authority_markup( string $rivera_media = '' ): string {
	$colegiado  = defined( 'NVX_DIRECTOR_COLEGIADO' ) ? NVX_DIRECTOR_COLEGIADO : '282864786';
	$doctoralia = 'https://www.doctoralia.es/jose-javier-rivera-tejeda/medico-estetico/madrid';

	$html  = '<div class="nvx-equipo-director" id="physician-rivera-tejeda">';

	// A. Profile: portrait + copy in structured grid.
	$html .= '<section class="nvx-editorial-section nvx-equipo-profile" aria-labelledby="nvx-equipo-profile-title">';
	$html .= '<div class="nvx-editorial-section__inner nvx-equipo-profile-layout">';
	$portrait = nvx_equipo_portrait_figure_markup( $rivera_media, __( 'Dr. José Javier Rivera Tejeda', 'nuvanx-medical' ) );
	if ( '' !== $portrait ) {
		$html .= $portrait;
	}
	$html .= '<div class="nvx-equipo-profile-layout__copy">';
	$html .= '<p class="nvx-editorial-kicker">' . esc_html__( 'Director médico', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-equipo-profile-title" class="nvx-editorial-heading">' . esc_html__( 'Dr. José Javier Rivera Tejeda: Director Médico e Investigador Clínico', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p class="nvx-editorial-body">' . esc_html(
		sprintf(
			/* translators: %s: medical license number */
			__( 'Con número de colegiación ICOMEM %s, el Dr. José Javier Rivera Tejeda ostenta la Dirección Médica de las clínicas NUVANX en Madrid. Médico estético hiper-especializado en la aplicación avanzada de tecnologías láser intervencionistas y medicina regenerativa tisular.', 'nuvanx-medical' ),
			$colegiado
		)
	) . '</p>';
	$html .= '<p class="nvx-editorial-body">' . wp_kses(
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
	$html .= '</div></div></section>';

	// B. Subespecialización.
	$html .= '<section class="nvx-editorial-section nvx-equipo-scope" aria-labelledby="nvx-equipo-scope-title">';
	$html .= '<div class="nvx-editorial-section__inner">';
	$html .= '<p class="nvx-editorial-kicker">' . esc_html__( 'Ámbito clínico', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-equipo-scope-title" class="nvx-editorial-heading">' . esc_html__( 'Subespecialización y experiencia', 'nuvanx-medical' ) . '</h2>';
	$html .= '<ul class="nvx-editorial-grid-list">';
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
		$html .= '<li class="nvx-editorial-grid-item">';
		$html .= '<h3 class="nvx-editorial-grid-item__title">' . esc_html( $scope['title'] ) . '</h3>';
		$html .= '<p class="nvx-editorial-body">' . esc_html( $scope['body'] ) . '</p>';
		$html .= '</li>';
	}
	$html .= '</ul></div></section>';

	// C. Formación.
	$html .= '<section class="nvx-editorial-section nvx-equipo-formation" aria-labelledby="nvx-equipo-form-title">';
	$html .= '<div class="nvx-editorial-section__inner nvx-editorial-split">';
	$html .= '<div class="nvx-editorial-split__copy">';
	$html .= '<p class="nvx-editorial-kicker">' . esc_html__( 'Formación', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-equipo-form-title" class="nvx-editorial-heading">' . esc_html__( 'Formación académica y trayectoria', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p class="nvx-editorial-body">' . esc_html__( 'Máster Universitario en Medicina Estética por la Universidad Complutense de Madrid (UCM). Máster especializado en Tricología y Cirugía Capilar (AMIR).', 'nuvanx-medical' ) . '</p>';
	$html .= '<p class="nvx-editorial-body">' . esc_html__( 'Trayectoria como director de cirugía cosmética láser en cadenas hospitalarias de referencia (Clínicas Londres, Clínicas Dr. Esquivel), aplicada hoy al modelo de doble sede NUVANX.', 'nuvanx-medical' ) . '</p>';
	$html .= '</div>';
	$html .= '<aside class="nvx-editorial-panel" aria-label="' . esc_attr__( 'Identidad profesional', 'nuvanx-medical' ) . '">';
	$html .= '<p class="nvx-editorial-panel__label">' . esc_html__( 'Identidad', 'nuvanx-medical' ) . '</p>';
	$html .= '<ul class="nvx-editorial-fact-list">';
	$html .= '<li><strong>' . esc_html__( 'Colegiado', 'nuvanx-medical' ) . '</strong> — ICOMEM ' . esc_html( $colegiado ) . '</li>';
	$html .= '<li><strong>' . esc_html__( 'Cargo', 'nuvanx-medical' ) . '</strong> — ' . esc_html__( 'Director médico NUVANX Madrid', 'nuvanx-medical' ) . '</li>';
	$html .= '<li><strong>' . esc_html__( 'Sedes', 'nuvanx-medical' ) . '</strong> — ' . esc_html__( 'Chamberí y Goya · Barrio Salamanca', 'nuvanx-medical' ) . '</li>';
	$html .= '<li><strong>' . esc_html__( 'Agenda', 'nuvanx-medical' ) . '</strong> — ' . esc_html__( 'Mar/Jue Chamberí · Mié Goya', 'nuvanx-medical' ) . '</li>';
	$html .= '</ul></aside></div></section>';

	// D. Quote.
	$html .= '<section class="nvx-editorial-section nvx-equipo-quote" aria-labelledby="nvx-equipo-quote-title">';
	$html .= '<div class="nvx-editorial-section__inner">';
	$html .= '<h2 id="nvx-equipo-quote-title" class="screen-reader-text">' . esc_html__( 'Visión clínica', 'nuvanx-medical' ) . '</h2>';
	$html .= '<blockquote class="nvx-equipo-blockquote">';
	$html .= '<p>' . esc_html__( 'Mi visión clínica rechaza la transformación anatómica artificial. La tecnología láser más sofisticada debe emplearse para desencadenar la regeneración celular propia del paciente, logrando una firmeza biológica real, no un aspecto quirúrgico evidente.', 'nuvanx-medical' ) . '</p>';
	$html .= '<footer>— ' . esc_html__( 'Dr. J.J. Rivera Tejeda', 'nuvanx-medical' ) . '</footer>';
	$html .= '</blockquote></div></section>';

	$html .= '</div>';

	return $html;
}

/**
 * Builds the authority profile markup for Dra. Ivon Yamileth Rivera Deras.
 *
 * @param string $ivon_media Optional portrait media from the CMS card.
 * @return string The rendered authority profile HTML.
 */
function nvx_equipo_ivon_authority_markup( string $ivon_media = '' ): string {
	$colegiado = defined( 'NVX_IVON_COLEGIADO' ) ? NVX_IVON_COLEGIADO : '284621525';

	$html  = '<div class="nvx-equipo-ivon" id="physician-rivera-deras">';
	$html .= '<section class="nvx-editorial-section nvx-equipo-profile" aria-labelledby="nvx-equipo-ivon-title">';
	$html .= '<div class="nvx-editorial-section__inner nvx-equipo-profile-layout">';
	$portrait = nvx_equipo_portrait_figure_markup( $ivon_media, __( 'Dra. Ivon Yamileth Rivera Deras', 'nuvanx-medical' ) );
	if ( '' !== $portrait ) {
		$html .= $portrait;
	}
	$html .= '<div class="nvx-equipo-profile-layout__copy">';
	$html .= '<p class="nvx-editorial-kicker">' . esc_html__( 'Well-aging y geriatría preventiva', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-equipo-ivon-title" class="nvx-editorial-heading">' . esc_html__( 'Dra. Ivon Yamileth Rivera Deras: Referente Científico en Well-Aging y Geriatría Preventiva', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p class="nvx-editorial-body">' . esc_html(
		sprintf(
			/* translators: %s: medical license number */
			__( 'Colegiada ICOMEM %s. La Dra. Rivera Deras aporta experiencia en medicina funcional, longevidad y well-aging. Su actividad asistencial e investigadora contribuye a que los protocolos se revisen con criterio clínico y evidencia aplicable.', 'nuvanx-medical' ),
			$colegiado
		)
	) . '</p>';
	$html .= '</div></div></section>';

	$html .= '<section class="nvx-editorial-section" aria-labelledby="nvx-equipo-ivon-public-title">';
	$html .= '<div class="nvx-editorial-section__inner">';
	$html .= '<p class="nvx-editorial-kicker">' . esc_html__( 'Asistencia pública', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-equipo-ivon-public-title" class="nvx-editorial-heading">' . esc_html__( 'Actividad asistencial hospitalaria', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p class="nvx-editorial-body nvx-editorial-body--measure">' . esc_html__( 'Médico Especialista (FEA) por concurso selectivo en el Hospital Universitario La Paz, en Unidad de Recuperación Funcional y Hospital de Día Geriátrico. Forma parte del cuadro médico del Hospital Central de la Cruz Roja San José y Santa Adela, centro de referencia en neurorrehabilitación y atención al adulto mayor.', 'nuvanx-medical' ) . '</p>';
	$html .= '</div></section>';

	$html .= '<section class="nvx-editorial-section" aria-labelledby="nvx-equipo-ivon-research-title">';
	$html .= '<div class="nvx-editorial-section__inner nvx-editorial-split">';
	$html .= '<div class="nvx-editorial-split__copy">';
	$html .= '<p class="nvx-editorial-kicker">' . esc_html__( 'Investigación', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-equipo-ivon-research-title" class="nvx-editorial-heading">' . esc_html__( 'Investigación, sociedades y academia', 'nuvanx-medical' ) . '</h2>';
	$html .= '<ul class="nvx-editorial-grid-list">';
	$items = array(
		array(
			'title' => __( 'Real-World Evidence', 'nuvanx-medical' ),
			'body'  => __( 'Investigadora clínica externa y consultora médica para OXON Epidemiology.', 'nuvanx-medical' ),
		),
		array(
			'title' => __( 'SEMEG y EuGMS', 'nuvanx-medical' ),
			'body'  => __( 'Coordinadora científica de las Jornadas de Deterioro Cognitivo de la Sociedad Española de Medicina Geriátrica (SEMEG) y colaboración activa con la European Geriatric Medicine Society (EuGMS).', 'nuvanx-medical' ),
		),
		array(
			'title' => __( 'Universidad Europea de Madrid', 'nuvanx-medical' ),
			'body'  => __( 'Profesora e investigadora en la UEM, vinculada al Hospital Vithas Madrid Arturo Soria. Formación continuada de facultativos, enfermería y TCAE en hospitales del SERMAS.', 'nuvanx-medical' ),
		),
		array(
			'title' => __( 'Obra escrita y publicaciones', 'nuvanx-medical' ),
			'body'  => __( 'Coautora de obras bioéticas y clínicas como «El tormento de la inmortalidad sin juventud» y del «Manual de manejo de personas mayores que sufren caídas» (SEMEG), además de trabajos sobre cribado cognitivo temprano.', 'nuvanx-medical' ),
		),
	);
	foreach ( $items as $item ) {
		$html .= '<li class="nvx-editorial-grid-item">';
		$html .= '<h3 class="nvx-editorial-grid-item__title">' . esc_html( $item['title'] ) . '</h3>';
		$html .= '<p class="nvx-editorial-body">' . esc_html( $item['body'] ) . '</p>';
		$html .= '</li>';
	}
	$html .= '</ul></div>';
	$html .= '<aside class="nvx-editorial-panel" aria-label="' . esc_attr__( 'Identidad profesional', 'nuvanx-medical' ) . '">';
	$html .= '<p class="nvx-editorial-panel__label">' . esc_html__( 'Identidad', 'nuvanx-medical' ) . '</p>';
	$html .= '<ul class="nvx-editorial-fact-list">';
	$html .= '<li><strong>' . esc_html__( 'Colegiada', 'nuvanx-medical' ) . '</strong> — ICOMEM ' . esc_html( $colegiado ) . '</li>';
	$html .= '<li><strong>' . esc_html__( 'Ámbito', 'nuvanx-medical' ) . '</strong> — ' . esc_html__( 'Well-aging · Geriatría preventiva · Longevidad', 'nuvanx-medical' ) . '</li>';
	$html .= '<li><strong>' . esc_html__( 'Asistencia', 'nuvanx-medical' ) . '</strong> — ' . esc_html__( 'La Paz · Cruz Roja', 'nuvanx-medical' ) . '</li>';
	$html .= '<li><strong>' . esc_html__( 'Sociedades', 'nuvanx-medical' ) . '</strong> — SEMEG · EuGMS</li>';
	$html .= '</ul></aside></div></section>';

	$html .= '</div>';

	return $html;
}

/**
 * Builds the editorial authority profile for Dr. Fabio Augusto Quiñónez Bareiro.
 *
 * @param string $fabio_media Optional portrait media extracted from a CMS staff card.
 * @return string The rendered HTML markup for the profile.
 */
function nvx_equipo_fabio_authority_markup( string $fabio_media = '' ): string {
	$colegiado = defined( 'NVX_FABIO_COLEGIADO' ) ? NVX_FABIO_COLEGIADO : '282877543';

	$html  = '<div class="nvx-equipo-fabio" id="physician-quinonez-bareiro">';
	$html .= '<section class="nvx-editorial-section nvx-equipo-profile" aria-labelledby="nvx-equipo-fabio-title">';
	$html .= '<div class="nvx-editorial-section__inner nvx-equipo-profile-layout">';
	$portrait = nvx_equipo_portrait_figure_markup( $fabio_media, __( 'Dr. Fabio Augusto Quiñónez Bareiro', 'nuvanx-medical' ) );
	if ( '' !== $portrait ) {
		$html .= $portrait;
	}
	$html .= '<div class="nvx-equipo-profile-layout__copy">';
	$html .= '<p class="nvx-editorial-kicker">' . esc_html__( 'Geriatría, gerontología y paciente complejo', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-equipo-fabio-title" class="nvx-editorial-heading">' . esc_html__( 'Dr. Fabio Augusto Quiñónez Bareiro: Especialista en Geriatría, Gerontología y Paciente Complejo', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p class="nvx-editorial-body">' . esc_html(
		sprintf(
			/* translators: %s: medical license number */
			__( 'Colegiado ICOMEM %s. El Dr. Quiñónez Bareiro refuerza la unidad de medicina regenerativa y longevidad de NUVANX con experiencia en fisiología del envejecimiento y abordaje clínico del paciente complejo.', 'nuvanx-medical' ),
			$colegiado
		)
	) . '</p>';
	$html .= '</div></div></section>';

	$html .= '<section class="nvx-editorial-section" aria-labelledby="nvx-equipo-fabio-clinical-title">';
	$html .= '<div class="nvx-editorial-section__inner">';
	$html .= '<p class="nvx-editorial-kicker">' . esc_html__( 'Asistencia', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-equipo-fabio-clinical-title" class="nvx-editorial-heading">' . esc_html__( 'Experiencia clínica y asistencial', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p class="nvx-editorial-body nvx-editorial-body--measure">' . esc_html__( 'Facultativo Especialista de Área (FEA) en el Servicio de Geriatría del Hospital Virgen del Valle (Toledo). Trayectoria en SESCAM y Madrid con etapa clave en el Complejo Hospitalario Universitario de Toledo. Experiencia previa en pacientes críticos en Urgencias del Hospital Virgen de la Salud, y labor asistencial en el Hospital de Emergencias Enfermera Isabel Zendal y el Hospital Quirónsalud Tres Culturas.', 'nuvanx-medical' ) . '</p>';
	$html .= '</div></section>';

	$html .= '<section class="nvx-editorial-section" aria-labelledby="nvx-equipo-fabio-research-title">';
	$html .= '<div class="nvx-editorial-section__inner">';
	$html .= '<p class="nvx-editorial-kicker">' . esc_html__( 'Investigación', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-equipo-fabio-research-title" class="nvx-editorial-heading">' . esc_html__( 'Investigación, congresos y casos clínicos', 'nuvanx-medical' ) . '</h2>';
	$html .= '<ul class="nvx-editorial-grid-list">';
	$items = array(
		array(
			'title' => __( 'CIBERFES y SEMEG', 'nuvanx-medical' ),
			'body'  => __( 'Investigador activo asociado al CIBER de Fragilidad y Envejecimiento Saludable (CIBERFES) y colaborador de la Sociedad Española de Medicina Geriátrica (SEMEG).', 'nuvanx-medical' ),
		),
		array(
			'title' => __( 'Estudio Toledo · Envejecimiento saludable', 'nuvanx-medical' ),
			'body'  => __( 'Trabajos que proponen el uso de la velocidad de onda de pulso (cf-PWV) para la detección temprana del deterioro cognitivo en el marco del Estudio Toledo para el Envejecimiento Saludable.', 'nuvanx-medical' ),
		),
		array(
			'title' => __( 'Casos y diagnóstico diferencial', 'nuvanx-medical' ),
			'body'  => __( 'Coautoría en «¿Será una infección del tracto urinario?» (diagnósticos diferenciales entre delírium e infección en el anciano) e investigaciones sobre riesgo cardiovascular mal controlado, síncopes y fracturas de cadera.', 'nuvanx-medical' ),
		),
	);
	foreach ( $items as $item ) {
		$html .= '<li class="nvx-editorial-grid-item">';
		$html .= '<h3 class="nvx-editorial-grid-item__title">' . esc_html( $item['title'] ) . '</h3>';
		$html .= '<p class="nvx-editorial-body">' . esc_html( $item['body'] ) . '</p>';
		$html .= '</li>';
	}
	$html .= '</ul></div></section>';

	$html .= '<section class="nvx-editorial-section" aria-labelledby="nvx-equipo-fabio-teach-title">';
	$html .= '<div class="nvx-editorial-section__inner nvx-editorial-split">';
	$html .= '<div class="nvx-editorial-split__copy">';
	$html .= '<p class="nvx-editorial-kicker">' . esc_html__( 'Docencia', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-equipo-fabio-teach-title" class="nvx-editorial-heading">' . esc_html__( 'Labor docente y formación académica', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p class="nvx-editorial-body">' . esc_html__( 'Profesor Colaborador en TECH Universidad: dirige el Curso Universitario en Paciente Anciano Crónico Complejo (pluripatología: diabetes, insuficiencia cardíaca y demencia) y diseña contenidos del Experto en Patología Osteoarticular (artrosis, osteoporosis y dolor avanzado).', 'nuvanx-medical' ) . '</p>';
	$html .= '<p class="nvx-editorial-body">' . esc_html__( 'Doctor (Ph.D.) por la Universidad Autónoma de Madrid (UAM) con la tesis «Disfunción vascular sub-clínica, declinar cognitivo y fragilidad». Máster en Psicogeriatría (UAB). Licenciado en Medicina por la ELAM.', 'nuvanx-medical' ) . '</p>';
	$html .= '</div>';
	$html .= '<aside class="nvx-editorial-panel" aria-label="' . esc_attr__( 'Identidad profesional', 'nuvanx-medical' ) . '">';
	$html .= '<p class="nvx-editorial-panel__label">' . esc_html__( 'Identidad', 'nuvanx-medical' ) . '</p>';
	$html .= '<ul class="nvx-editorial-fact-list">';
	$html .= '<li><strong>' . esc_html__( 'Colegiado', 'nuvanx-medical' ) . '</strong> — ICOMEM ' . esc_html( $colegiado ) . '</li>';
	$html .= '<li><strong>' . esc_html__( 'Ámbito', 'nuvanx-medical' ) . '</strong> — ' . esc_html__( 'Geriatría · Paciente complejo · Longevidad', 'nuvanx-medical' ) . '</li>';
	$html .= '<li><strong>' . esc_html__( 'Doctorado', 'nuvanx-medical' ) . '</strong> — UAM</li>';
	$html .= '<li><strong>' . esc_html__( 'Redes', 'nuvanx-medical' ) . '</strong> — CIBERFES · SEMEG</li>';
	$html .= '</ul></aside></div></section>';

	$html .= '</div>';

	return $html;
}

/**
 * Rebuilds the Equipo médico page with editorial hero content, clinician authority profiles, and preserved CMS staff cards.
 *
 * @param string $content The original page content.
 * @return string The rebuilt Equipo médico page content, or the original content when the page is not eligible for restructuring.
 */
function nvx_content_restructure_equipo_page( string $content ): string {
	if ( ! nvx_content_is_equipo_page( $content ) ) {
		return $content;
	}

	$staff = nvx_equipo_extract_staff_cards( $content );

	// Hero media: only real page hero — never logo, never a stolen staff portrait.
	$media = '';
	if ( preg_match( '/<figure class="nvx-brand-hero__media"[\s\S]*?<\/figure>/iu', $content, $m ) ) {
		$media = $m[0];
	} elseif ( preg_match( '/<div class="nvx-brand-hero__media"[\s\S]*?<\/div>/iu', $content, $m ) ) {
		$media = $m[0];
	}
	if ( '' !== $media && nvx_equipo_media_is_logo( $media ) ) {
		$media = '';
	}

	$hero_classes = 'nvx-brand-hero nvx-brand-hero--laser nvx-editorial-hero nvx-equipo-hero';
	if ( '' === $media ) {
		$hero_classes .= ' nvx-equipo-hero--copy-only';
	}

	$hero  = '<section class="' . esc_attr( $hero_classes ) . '" aria-labelledby="nvx-equipo-h1" aria-label="' . esc_attr__( 'Equipo médico NUVANX', 'nuvanx-medical' ) . '">';
	$hero .= '<div class="nvx-brand-hero__inner">';
	$hero .= nvx_equipo_hero_copy_markup();
	$hero .= $media;
	$hero .= '</div></section>';

	// Director → Dra. Ivon → Dr. Fabio → resto del equipo (CMS).
	// Closing valoración CTA: site-wide nvx-cta-banner in footer.php.
	$body  = '<div class="nvx-equipo-editorial nvx-editorial-page">';
	$body .= nvx_equipo_director_authority_markup( $staff['rivera_media'] );
	$body .= nvx_equipo_ivon_authority_markup( $staff['ivon_media'] );
	$body .= nvx_equipo_fabio_authority_markup( $staff['fabio_media'] ?? '' );
	$body .= nvx_equipo_other_staff_section_markup( $staff['other_cards'] );
	$body .= '</div>';

	if ( preg_match( '/(<div class="nvx-brand-page[^"]*"[^>]*>)/iu', $content, $wrap ) ) {
		return $wrap[1] . $hero . $body . '</div>';
	}

	return $hero . $body;
}
add_filter( 'the_content', 'nvx_content_restructure_equipo_page', 19 );
