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

	$html  = '<div class="nvx-brand-hero__copy nvx-equipo-hero-copy">';
	$html .= '<p class="nvx-brand-kicker">' . esc_html__( 'NUVANX · Equipo médico', 'nuvanx-medical' ) . '</p>';
	$html .= '<h1 class="nvx-brand-hero__title" id="nvx-equipo-h1">' . esc_html__( 'Equipo médico NUVANX: quién te valora y quién trata', 'nuvanx-medical' ) . '</h1>';
	$html .= '<p class="nvx-brand-hero__lead">' . esc_html__( 'Médicos con práctica hospitalaria y consulta estética en Madrid. Dirección médica, well-aging y valoración clínica antes de cualquier protocolo láser.', 'nuvanx-medical' ) . '</p>';
	$html .= '<p class="nvx-brand-hero__description">' . esc_html(
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

/** Promote data-src to src attribute. */
function nvx_equipo_promote_attr_src( string $attrs ): string {
	if ( ( preg_match( '/\ssrc=["\']data:image\//i', $attrs ) || preg_match( '/\ssrc=["\']["\']/i', $attrs ) )
		&& preg_match( '/\sdata-(?:src|lazy-src|original)=["\']([^"\']+)["\']/i', $attrs, $ds )
	) {
		$real = esc_url( $ds[1] );
		if ( '' !== $real ) {
			return preg_match( '/\ssrc=/i', $attrs )
				? ( preg_replace( '/\ssrc=["\'][^"\']*["\']/i', ' src="' . $real . '"', $attrs, 1 ) ?? $attrs )
				: $attrs . ' src="' . $real . '"';
		}
	}
	return $attrs;
}

/** Promote data-srcset to srcset attribute. */
function nvx_equipo_promote_attr_srcset( string $attrs ): string {
	if ( preg_match( '/\sdata-(?:srcset|lazy-srcset)=["\']([^"\']+)["\']/i', $attrs, $dset ) ) {
		$real_srcset = esc_attr( $dset[1] );
		if ( '' !== $real_srcset ) {
			return preg_match( '/\ssrcset=/i', $attrs )
				? ( preg_replace( '/\ssrcset=["\'][^"\']*["\']/i', ' srcset="' . $real_srcset . '"', $attrs, 1 ) ?? $attrs )
				: $attrs . ' srcset="' . $real_srcset . '"';
		}
	}
	return $attrs;
}

/** Promote data-sizes to sizes attribute. */
function nvx_equipo_promote_attr_sizes( string $attrs ): string {
	if ( preg_match( '/\sdata-(?:sizes|lazy-sizes)=["\']([^"\']+)["\']/i', $attrs, $dsizes ) ) {
		$real_sizes = esc_attr( $dsizes[1] );
		if ( '' !== $real_sizes ) {
			return preg_match( '/\ssizes=/i', $attrs )
				? ( preg_replace( '/\ssizes=["\'][^"\']*["\']/i', ' sizes="' . $real_sizes . '"', $attrs, 1 ) ?? $attrs )
				: $attrs . ' sizes="' . $real_sizes . '"';
		}
	}
	return $attrs;
}

/** Promote lazy attributes (src, srcset, sizes) to their native counterparts. */
function nvx_equipo_promote_lazy_src( string $attrs ): string {
	$attrs = nvx_equipo_promote_attr_src( $attrs );
	$attrs = nvx_equipo_promote_attr_srcset( $attrs );
	$attrs = nvx_equipo_promote_attr_sizes( $attrs );
	return $attrs;
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

	$attrs = nvx_equipo_promote_lazy_src( $m[1] );

	// Drop inline size/style that fights portrait crop; strip body role.
	$attrs = preg_replace( '/\s+style=["\'][^"\']*["\']/i', '', $attrs ) ?? $attrs;
	$attrs = preg_replace( '/\s+(?:width|height)=["\'][^"\']*["\']/i', '', $attrs ) ?? $attrs;
	$attrs = preg_replace( '/\s*nvx-media--body\s*/i', ' ', $attrs ) ?? $attrs;
	// Re-emit loading/decoding once (CMS + cleaners often duplicate).
	$attrs = preg_replace( '/\s+loading=["\'][^"\']*["\']/i', '', $attrs ) ?? $attrs;
	$attrs = preg_replace( '/\s+decoding=["\'][^"\']*["\']/i', '', $attrs ) ?? $attrs;

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

/** Categorize one staff card. */
function nvx_equipo_categorize_staff_card( string $card, string &$rivera_media, string &$ivon_media, string &$fabio_media, array &$other_cards ): void {
	if ( nvx_equipo_block_is_rivera_tejeda( $card ) ) {
		if ( '' === $rivera_media && preg_match( '/<figure\b[\s\S]*?<\/figure>|<img\b[^>]*>/iu', $card, $im ) ) {
			$rivera_media = $im[0];
		}
		return;
	}
	if ( nvx_equipo_block_is_ivon( $card ) ) {
		if ( '' === $ivon_media && preg_match( '/<figure\b[\s\S]*?<\/figure>|<img\b[^>]*>/iu', $card, $im ) ) {
			$ivon_media = $im[0];
		}
		return;
	}
	if ( nvx_equipo_block_is_fabio( $card ) ) {
		if ( '' === $fabio_media && preg_match( '/<figure\b[\s\S]*?<\/figure>|<img\b[^>]*>/iu', $card, $im ) ) {
			$fabio_media = $im[0];
		}
		return;
	}
	if ( nvx_equipo_is_person_staff_card( $card ) ) {
		$other_cards[] = $card;
	}
}

/**
 * Extract staff cards from CMS: director, Dra. Ivon, Dr. Fabio, rest of team.
 *
 * @param string $content CMS content.
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
		nvx_equipo_categorize_staff_card( $card, $rivera_media, $ivon_media, $fabio_media, $other_cards );
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
		$card = preg_replace( '/\bclass=(["\'])/u', 'class=$1nvx-brand-card--team ', $card, 1 ) ?? $card;
	}

	// Portrait frame: single clean img, no noscript/br noise inside figure.
	$card = preg_replace_callback(
		'/(<figure\b[^>]*\bclass=["\'][^"\']*\bnvx-brand-card__media\b)([^"\']*)(["\'][^>]*>)([\s\S]*?)(<\/figure>)/iu',
		static function ( array $m ): string {
			$open = $m[1] . $m[2];
			if ( false === strpos( $open . $m[3], 'nvx-brand-card__media--portrait' ) ) {
				$open .= ' nvx-brand-card__media--portrait';
			}
			$open = preg_replace( '/\s*nvx-content-figure\s*/i', ' ', $open ) ?? $open;
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
			$card = preg_replace( '/<noscript\b[\s\S]*?<\/noscript>/iu', '', $card ) ?? $card;
			$card = preg_replace(
				'/<img\b[^>]*>/iu',
				'<figure class="nvx-brand-card__media nvx-brand-card__media--portrait">' . $img . '</figure>',
				$card,
				1
			) ?? $card;
		}
	}

	$card = preg_replace( '/<br\s*\/?>/iu', '', $card ) ?? $card;

	return is_string( $card ) ? $card : '';
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

	$html  = '<section class="nvx-endolift-section nvx-equipo-staff" aria-labelledby="nvx-equipo-staff-title">';
	$html .= '<div class="nvx-endolift-section__inner">';
	$html .= '<p class="nvx-endolift-kicker">' . esc_html__( 'Equipo clínico', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-equipo-staff-title" class="nvx-endolift-heading">' . esc_html__( 'Resto del equipo médico NUVANX', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p class="nvx-endolift-body nvx-endolift-body--measure">' . esc_html__( 'Profesionales que atienden valoración, seguimiento y protocolos en Chamberí y Goya, junto a la dirección médica y al criterio científico de la clínica.', 'nuvanx-medical' ) . '</p>';
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
 * Renders an authority items section (subspecialties, research, clinical topics).
 */
function nvx_equipo_render_items_section( array $section ): string {
	$section_id    = $section['id'] ?? '';
	$section_class = trim( 'nvx-endolift-section ' . ( $section['class'] ?? '' ) );
	$kicker        = $section['kicker'] ?? '';
	$heading       = $section['heading'] ?? '';
	$lead          = $section['lead'] ?? '';
	$items         = $section['items'] ?? array();

	$html  = '<section class="' . esc_attr( $section_class ) . '" aria-labelledby="' . esc_attr( $section_id ) . '">';
	$html .= '<div class="nvx-container">';
	if ( '' !== $kicker ) {
		$html .= '<p class="nvx-brand-kicker">' . esc_html( $kicker ) . '</p>';
	}
	if ( '' !== $heading ) {
		$html .= '<h2 id="' . esc_attr( $section_id ) . '" class="nvx-heading">' . esc_html( $heading ) . '</h2>';
	}
	if ( '' !== $lead ) {
		$html .= '<p class="nvx-body nvx-endolift-body--measure">' . esc_html( $lead ) . '</p>';
	}

	if ( ! empty( $items ) ) {
		$html .= '<ul class="nvx-endolaser-zone-list">';
		foreach ( $items as $item ) {
			$html .= '<li class="nvx-endolaser-zone">';
			if ( ! empty( $item['title'] ) ) {
				$html .= '<h3 class="nvx-endolaser-zone__title">' . esc_html( $item['title'] ) . '</h3>';
			}
			if ( ! empty( $item['body'] ) ) {
				$html .= '<p class="nvx-body">' . esc_html( $item['body'] ) . '</p>';
			}
			$html .= '</li>';
		}
		$html .= '</ul>';
	}

	$html .= '</div></section>';
	return $html;
}

/**
 * Renders a split formation / docencia section with identity fact panel.
 */
function nvx_equipo_render_split_identity_section( array $config ): string {
	$section_id = $config['id'] ?? '';
	$kicker     = $config['kicker'] ?? '';
	$heading    = $config['heading'] ?? '';
	$paragraphs = $config['paragraphs'] ?? array();
	$items      = $config['items'] ?? array();
	$facts      = $config['facts'] ?? array();

	$html  = '<section class="nvx-brand-section" aria-labelledby="' . esc_attr( $section_id ) . '">';
	$html .= '<div class="nvx-container nvx-equipo-diagnosis__grid">';
	$html .= '<div class="nvx-equipo-diagnosis__copy">';
	if ( '' !== $kicker ) {
		$html .= '<p class="nvx-brand-kicker">' . esc_html( $kicker ) . '</p>';
	}
	if ( '' !== $heading ) {
		$html .= '<h2 id="' . esc_attr( $section_id ) . '" class="nvx-heading">' . esc_html( $heading ) . '</h2>';
	}

	foreach ( $paragraphs as $paragraph ) {
		$html .= '<p class="nvx-body">' . esc_html( $paragraph ) . '</p>';
	}

	if ( ! empty( $items ) ) {
		$html .= '<ul class="nvx-endolaser-zone-list">';
		foreach ( $items as $item ) {
			$html .= '<li class="nvx-endolaser-zone">';
			if ( ! empty( $item['title'] ) ) {
				$html .= '<h3 class="nvx-endolaser-zone__title">' . esc_html( $item['title'] ) . '</h3>';
			}
			if ( ! empty( $item['body'] ) ) {
				$html .= '<p class="nvx-endolift-body">' . esc_html( $item['body'] ) . '</p>';
			}
			$html .= '</li>';
		}
		$html .= '</ul>';
	}

	$html .= '</div>';

	if ( ! empty( $facts ) ) {
		$html .= '<aside class="nvx-endolift-diagnosis__panel" aria-label="' . esc_attr__( 'Identidad profesional', 'nuvanx-medical' ) . '">';
		$html .= '<p class="nvx-endolift-panel-label">' . esc_html__( 'Identidad', 'nuvanx-medical' ) . '</p>';
		$html .= '<ul class="nvx-endolift-panel-list">';
		foreach ( $facts as $fact ) {
			$html .= '<li><strong>' . esc_html( $fact['label'] ) . '</strong> — ' . esc_html( $fact['val'] ) . '</li>';
		}
		$html .= '</ul></aside>';
	}

	$html .= '</div></section>';
	return $html;
}

/**
 * Builds a physician's authority profile markup.
 *
 * @param array $config Physician configuration data.
 * @return string The rendered authority profile HTML.
 */
function nvx_equipo_physician_authority_markup( array $config ): string {
	$wrapper_class = $config['wrapper_class'] ?? 'nvx-equipo-director';
	$wrapper_id    = $config['wrapper_id'] ?? '';

	$html = '<div class="' . esc_attr( $wrapper_class ) . '"';
	if ( '' !== $wrapper_id ) {
		$html .= ' id="' . esc_attr( $wrapper_id ) . '"';
	}
	$html .= '>';

	// Profile layout (Portrait + intro copy)
	$html .= '<section class="nvx-endolift-section nvx-equipo-profile" aria-labelledby="nvx-equipo-profile-title">';
	$html .= '<div class="nvx-endolift-section__inner nvx-equipo-profile-layout">';
	$portrait = nvx_equipo_portrait_figure_markup( $config['media'] ?? '', $config['name'] ?? '' );
	if ( '' !== $portrait ) {
		$html .= $portrait;
	}
	$html .= '<div class="nvx-equipo-profile-layout__copy">';
	if ( ! empty( $config['kicker'] ) ) {
		$html .= '<p class="nvx-endolift-kicker">' . esc_html( $config['kicker'] ) . '</p>';
	}
	if ( ! empty( $config['h2'] ) ) {
		$html .= '<h2 id="nvx-equipo-profile-title" class="nvx-endolift-heading">' . esc_html( $config['h2'] ) . '</h2>';
	}
	if ( ! empty( $config['bio_paragraphs'] ) ) {
		foreach ( $config['bio_paragraphs'] as $para ) {
			$html .= '<p class="nvx-endolift-body">' . $para . '</p>';
		}
	}
	$html .= '</div></div></section>';

	// Middle sections (subspecialties, clinical activities, research, docencia split)
	if ( ! empty( $config['sections'] ) ) {
		foreach ( $config['sections'] as $sec ) {
			if ( ! empty( $sec['type'] ) && 'split_identity' === $sec['type'] ) {
				$html .= nvx_equipo_render_split_identity_section( $sec );
			} else {
				$html .= nvx_equipo_render_items_section( $sec );
			}
		}
	}

	// Quote section
	if ( ! empty( $config['quote'] ) ) {
		$html .= '<section class="nvx-endolift-section nvx-equipo-quote" aria-labelledby="nvx-equipo-quote-title">';
		$html .= '<div class="nvx-endolift-section__inner">';
		$html .= '<h2 id="nvx-equipo-quote-title" class="screen-reader-text">' . esc_html__( 'Visión clínica', 'nuvanx-medical' ) . '</h2>';
		$html .= '<blockquote class="nvx-equipo-blockquote">';
		$html .= '<p>' . esc_html( $config['quote']['text'] ) . '</p>';
		$html .= '<footer>— ' . esc_html( $config['quote']['author'] ) . '</footer>';
		$html .= '</blockquote></div></section>';
	}

	$html .= '</div>';
	return $html;
}

/**
 * Builds the editorial authority profile for Dr. José Javier Rivera Tejeda.
 *
 * @param string $rivera_media Optional portrait media extracted from CMS staff card.
 * @return string The rendered HTML markup for the profile.
 */
function nvx_equipo_director_authority_markup( string $rivera_media = '' ): string {
	$colegiado  = defined( 'NVX_DIRECTOR_COLEGIADO' ) ? NVX_DIRECTOR_COLEGIADO : '282864786';
	$doctoralia = 'https://www.doctoralia.es/jose-javier-rivera-tejeda/medico-estetico/madrid';

	return nvx_equipo_physician_authority_markup(
		array(
			'wrapper_class'  => 'nvx-equipo-director',
			'wrapper_id'     => 'physician-rivera-tejeda',
			'media'          => $rivera_media,
			'name'           => __( 'Dr. José Javier Rivera Tejeda', 'nuvanx-medical' ),
			'kicker'         => __( 'Director médico', 'nuvanx-medical' ),
			'h2'             => __( 'Dr. José Javier Rivera Tejeda: Director Médico e Investigador Clínico', 'nuvanx-medical' ),
			'bio_paragraphs' => array(
				esc_html(
					sprintf(
						/* translators: %s: medical license number */
						__( 'Con número de colegiación ICOMEM %s, el Dr. José Javier Rivera Tejeda ostenta la Dirección Médica de las clínicas NUVANX en Madrid. Médico estético hiper-especializado en la aplicación avanzada de tecnologías láser intervencionistas y medicina regenerativa tisular.', 'nuvanx-medical' ),
						$colegiado
					)
				),
				wp_kses(
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
				),
			),
			'sections'       => array(
				array(
					'id'      => 'nvx-equipo-scope-title',
					'class'   => 'nvx-equipo-scope',
					'kicker'  => __( 'Ámbito clínico', 'nuvanx-medical' ),
					'heading' => __( 'Subespecialización y experiencia', 'nuvanx-medical' ),
					'items'   => array(
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
					),
				),
				array(
					'type'       => 'split_identity',
					'id'         => 'nvx-equipo-form-title',
					'kicker'     => __( 'Formación', 'nuvanx-medical' ),
					'heading'    => __( 'Formación académica y trayectoria', 'nuvanx-medical' ),
					'paragraphs' => array(
						__( 'Máster Universitario en Medicina Estética por la Universidad Complutense de Madrid (UCM). Máster especializado en Tricología y Cirugía Capilar (AMIR).', 'nuvanx-medical' ),
						__( 'Trayectoria como director de cirugía cosmética láser en cadenas hospitalarias de referencia (Clínicas Londres, Clínicas Dr. Esquivel), aplicada hoy al modelo de doble sede NUVANX.', 'nuvanx-medical' ),
					),
					'facts'      => array(
						array(
							'label' => __( 'Colegiado', 'nuvanx-medical' ),
							'val'   => 'ICOMEM ' . $colegiado,
						),
						array(
							'label' => __( 'Cargo', 'nuvanx-medical' ),
							'val'   => __( 'Director médico NUVANX Madrid', 'nuvanx-medical' ),
						),
						array(
							'label' => __( 'Sedes', 'nuvanx-medical' ),
							'val'   => __( 'Chamberí y Goya · Barrio Salamanca', 'nuvanx-medical' ),
						),
						array(
							'label' => __( 'Agenda', 'nuvanx-medical' ),
							'val'   => __( 'Mar/Jue Chamberí · Mié Goya', 'nuvanx-medical' ),
						),
					),
				),
			),
			'quote'          => array(
				'text'   => __( 'Mi visión clínica rechaza la transformación anatómica artificial. La tecnología láser más sofisticada debe emplearse para desencadenar la regeneración celular propia del paciente, logrando una firmeza biológica real, no un aspecto quirúrgico evidente.', 'nuvanx-medical' ),
				'author' => __( 'Dr. J.J. Rivera Tejeda', 'nuvanx-medical' ),
			),
		)
	);
}

/**
 * Builds the editorial authority profile for Dra. Ivon Yamileth Rivera Deras.
 *
 * @param string $ivon_media Optional portrait media extracted from CMS staff card.
 * @return string The rendered HTML markup for the profile.
 */
function nvx_equipo_ivon_authority_markup( string $ivon_media = '' ): string {
	$colegiado = defined( 'NVX_IVON_COLEGIADO' ) ? NVX_IVON_COLEGIADO : '284621525';

	return nvx_equipo_physician_authority_markup(
		array(
			'wrapper_class'  => 'nvx-equipo-ivon',
			'wrapper_id'     => 'physician-rivera-deras',
			'media'          => $ivon_media,
			'name'           => __( 'Dra. Ivon Yamileth Rivera Deras', 'nuvanx-medical' ),
			'kicker'         => __( 'Well-aging y geriatría preventiva', 'nuvanx-medical' ),
			'h2'             => __( 'Dra. Ivon Yamileth Rivera Deras: Referente Científico en Well-Aging y Geriatría Preventiva', 'nuvanx-medical' ),
			'bio_paragraphs' => array(
				esc_html(
					sprintf(
						/* translators: %s: medical license number */
						__( 'Colegiada ICOMEM %s. La Dra. Rivera Deras aporta experiencia en medicina funcional, longevidad y well-aging. Su actividad asistencial e investigadora contribuye a que los protocolos se revisen con criterio clínico y evidencia aplicable.', 'nuvanx-medical' ),
						$colegiado
					)
				),
			),
			'sections'       => array(
				array(
					'id'      => 'nvx-equipo-ivon-public-title',
					'kicker'  => __( 'Asistencia pública', 'nuvanx-medical' ),
					'heading' => __( 'Actividad asistencial hospitalaria', 'nuvanx-medical' ),
					'lead'    => __( 'Médico Especialista (FEA) por concurso selectivo en el Hospital Universitario La Paz, en Unidad de Recuperación Funcional y Hospital de Día Geriátrico. Forma parte del cuadro médico del Hospital Central de la Cruz Roja San José y Santa Adela, centro de referencia en neurorrehabilitación y atención al adulto mayor.', 'nuvanx-medical' ),
				),
				array(
					'type'    => 'split_identity',
					'id'      => 'nvx-equipo-ivon-research-title',
					'kicker'  => __( 'Investigación', 'nuvanx-medical' ),
					'heading' => __( 'Investigación, sociedades y academia', 'nuvanx-medical' ),
					'items'   => array(
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
					),
					'facts'   => array(
						array(
							'label' => __( 'Colegiada', 'nuvanx-medical' ),
							'val'   => 'ICOMEM ' . $colegiado,
						),
						array(
							'label' => __( 'Ámbito', 'nuvanx-medical' ),
							'val'   => __( 'Well-aging · Geriatría preventiva · Longevidad', 'nuvanx-medical' ),
						),
						array(
							'label' => __( 'Asistencia', 'nuvanx-medical' ),
							'val'   => __( 'La Paz · Cruz Roja', 'nuvanx-medical' ),
						),
						array(
							'label' => __( 'Sociedades', 'nuvanx-medical' ),
							'val'   => 'SEMEG · EuGMS',
						),
					),
				),
			),
		)
	);
}

/**
 * Builds the editorial authority profile for Dr. Fabio Augusto Quiñónez Bareiro.
 *
 * @param string $fabio_media Optional portrait media extracted from CMS staff card.
 * @return string The rendered HTML markup for the profile.
 */
function nvx_equipo_fabio_authority_markup( string $fabio_media = '' ): string {
	$colegiado = defined( 'NVX_FABIO_COLEGIADO' ) ? NVX_FABIO_COLEGIADO : '282877543';

	return nvx_equipo_physician_authority_markup(
		array(
			'wrapper_class'  => 'nvx-equipo-fabio',
			'wrapper_id'     => 'physician-quinonez-bareiro',
			'media'          => $fabio_media,
			'name'           => __( 'Dr. Fabio Augusto Quiñónez Bareiro', 'nuvanx-medical' ),
			'kicker'         => __( 'Geriatría, gerontología y paciente complejo', 'nuvanx-medical' ),
			'h2'             => __( 'Dr. Fabio Augusto Quiñónez Bareiro: Especialista en Geriatría, Gerontología y Paciente Complejo', 'nuvanx-medical' ),
			'bio_paragraphs' => array(
				esc_html(
					sprintf(
						/* translators: %s: medical license number */
						__( 'Colegiado ICOMEM %s. El Dr. Quiñónez Bareiro refuerza la unidad de medicina regenerativa y longevidad de NUVANX con experiencia en fisiología del envejecimiento y abordaje clínico del paciente complejo.', 'nuvanx-medical' ),
						$colegiado
					)
				),
			),
			'sections'       => array(
				array(
					'id'      => 'nvx-equipo-fabio-clinical-title',
					'kicker'  => __( 'Asistencia', 'nuvanx-medical' ),
					'heading' => __( 'Experiencia clínica y asistencial', 'nuvanx-medical' ),
					'lead'    => __( 'Facultativo Especialista de Área (FEA) en el Servicio de Geriatría del Hospital Virgen del Valle (Toledo). Trayectoria en SESCAM y Madrid con etapa clave en el Complejo Hospitalario Universitario de Toledo. Experiencia previa en pacientes críticos en Urgencias del Hospital Virgen de la Salud, y labor asistencial en el Hospital de Emergencias Enfermera Isabel Zendal y el Hospital Quirónsalud Tres Culturas.', 'nuvanx-medical' ),
				),
				array(
					'id'      => 'nvx-equipo-fabio-research-title',
					'kicker'  => __( 'Investigación', 'nuvanx-medical' ),
					'heading' => __( 'Investigación, congresos y casos clínicos', 'nuvanx-medical' ),
					'items'   => array(
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
					),
				),
				array(
					'type'       => 'split_identity',
					'id'         => 'nvx-equipo-fabio-teach-title',
					'kicker'     => __( 'Docencia', 'nuvanx-medical' ),
					'heading'    => __( 'Labor docente y formación académica', 'nuvanx-medical' ),
					'paragraphs' => array(
						__( 'Profesor Colaborador en TECH Universidad: dirige el Curso Universitario en Paciente Anciano Crónico Complejo (pluripatología: diabetes, insuficiencia cardíaca y demencia) y diseña contenidos del Experto en Patología Osteoarticular (artrosis, osteoporosis y dolor avanzado).', 'nuvanx-medical' ),
						__( 'Doctor (Ph.D.) por la Universidad Autónoma de Madrid (UAM) con la tesis «Disfunción vascular sub-clínica, declinar cognitivo y fragilidad». Máster en Psicogeriatría (UAB). Licenciado en Medicina por la ELAM.', 'nuvanx-medical' ),
					),
					'facts'      => array(
						array(
							'label' => __( 'Colegiado', 'nuvanx-medical' ),
							'val'   => 'ICOMEM ' . $colegiado,
						),
						array(
							'label' => __( 'Ámbito', 'nuvanx-medical' ),
							'val'   => __( 'Geriatría · Paciente complejo · Longevidad', 'nuvanx-medical' ),
						),
						array(
							'label' => __( 'Doctorado', 'nuvanx-medical' ),
							'val'   => 'UAM',
						),
						array(
							'label' => __( 'Redes', 'nuvanx-medical' ),
							'val'   => 'CIBERFES · SEMEG',
						),
					),
				),
			),
		)
	);
}

/**
 * Rebuild equipo page: dual authority profiles + preserve other CMS clinicians.
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

	$hero_classes = 'nvx-brand-hero nvx-brand-hero--equipo';
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
	$body  = '<div class="nvx-brand-section-wrap">';
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
