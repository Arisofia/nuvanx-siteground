<?php
/**
 * Canonical home FAQ: one catalogue for visible HTML and Yoast schema.
 *
 * GEO pattern: first sentence answers the question directly (same model as Endolift FAQ).
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @return array<int,array{id:string,q:string,a:string}> */
function nvx_home_faq_v2_catalog(): array {
	$from   = function_exists( 'nvx_endolift_price_from_eur' ) ? (float) nvx_endolift_price_from_eur() : 798.60;
	$papada = function_exists( 'nvx_endolift_price_papada_eur' ) ? (float) nvx_endolift_price_papada_eur() : 1064.80;
	$co2    = function_exists( 'nvx_tariff_catalog' ) ? (float) nvx_tariff_catalog()['laser_co2']['facial']['pvp'] : 330.0;
	$format = static function ( float $amount ): string {
		return function_exists( 'nvx_format_price_eur' )
			? (string) nvx_format_price_eur( $amount )
			: number_format( $amount, 2, ',', '.' );
	};

	return array(
		array(
			'id' => 'valoracion-coste',
			'q'  => '¿La valoración médica en NUVANX tiene coste?',
			'a'  => 'No. La valoración médica inicial es gratuita y sin compromiso. En 15–30 minutos revisamos tu caso, confirmamos si existe indicación y explicamos las opciones antes de cualquier decisión.',
		),
		array(
			'id' => 'precio-endolift',
			'q'  => '¿Cuánto cuesta el Endolift® facial en NUVANX?',
			'a'  => 'El Endolift® facial comienza desde ' . $format( $from ) . ' € (zona ojeras, IVA incluido). Papada o marcación mandibular: ' . $format( $papada ) . ' € por zona. El presupuesto definitivo se cierra tras valoración anatómica presencial.',
		),
		array(
			'id' => 'duracion-endolift',
			'q'  => '¿Cuánto duran los resultados del Endolift®?',
			'a'  => 'Habitualmente entre 18 meses y 3 años, según la calidad de piel, la edad y hábitos (sol, tabaquismo). En consulta se concreta el rango esperable para tu caso — no hay una promesa universal.',
		),
		array(
			'id' => 'sesiones-co2',
			'q'  => '¿Cuántas sesiones necesita el láser CO₂ fraccionado?',
			'a'  => 'Entre 1 y 3 sesiones según indicación (cicatrices, poros, fotodaño) y profundidad del protocolo. Precio orientativo por sesión facial: ' . $format( $co2 ) . ' € (IVA incluido). El plan se define en la valoración.',
		),
		array(
			'id' => 'tecnologia-medica',
			'q'  => '¿Qué tecnología láser usa NUVANX y está certificada?',
			'a'  => 'Sí. Utilizamos equipos con marcado CE médico (entre ellos DEKA Motus AZ+, Endolift® a 1470 nm, láser CO₂ fraccionado y EXION® BTL), autorizados en el marco de centros sanitarios de la Comunidad de Madrid.',
		),
		array(
			'id' => 'exion-morpheus',
			'q'  => '¿Qué es EXION® BTL y en qué se diferencia de Morpheus8®?',
			'a'  => 'EXION® BTL es una plataforma con modalidades Face, Body y Fractional RF. La indicación depende de la anatomía, la zona y el objetivo clínico; no se establece mediante un ranking de marcas.',
		),
		array(
			'id' => 'tratamiento-adecuado',
			'q'  => '¿Cómo sé qué tratamiento necesito?',
			'a'  => 'Solo el médico puede determinarlo tras exploración. En NUVANX el diagnóstico evalúa piel, historial y objetivos antes de indicar Endolift®, CO₂, EXION®, IPL o una combinación. No se decide por teléfono ni por formulario.',
		),
		array(
			'id' => 'recuperacion',
			'q'  => '¿Implican los tratamientos tiempo de recuperación?',
			'a'  => 'Depende del protocolo. Endolift® facial: a menudo 3–7 días de edema o hematomas leves. Láser CO₂: unos 4–7 días de descamación. EXION® e IPL: recuperación habitualmente mínima. Te lo concretamos en la valoración.',
		),
		array(
			'id' => 'diferencia-estetica',
			'q'  => '¿Cuál es la diferencia entre NUVANX y una clínica de estética convencional?',
			'a'  => 'NUVANX son centros sanitarios autorizados (CS20144 y CS20073) con equipo médico colegiado. Los tratamientos requieren indicación médica previa: si no está indicado para tu caso, no se realiza.',
		),
		array(
			'id' => 'clinicas-madrid',
			'q'  => '¿Dónde están las clínicas NUVANX en Madrid?',
			'a'  => 'Dos sedes: Chamberí (C/ Fernández de la Hoz 4, CS20144) y Salamanca–Goya (C/ Fernán González 26, CS20073). Puedes elegir sede al reservar la valoración.',
		),
		array(
			'id' => 'equipo-medico',
			'q'  => '¿Quién forma el equipo médico de NUVANX?',
			'a'  => 'Dirección médica del Dr. José Javier Rivera Tejeda (ICOMEM), con Dra. Ivon Yamileth Rivera Deras (well-aging / geriatría preventiva, FEA Hospital La Paz) y Dr. Fabio Augusto Quiñónez Bareiro (PhD, geriatría y paciente complejo), además del resto del equipo clínico.',
		),
	);
}

function nvx_home_faq_v2_markup(): string {
	$html  = '<section id="nvx-home-faq" class="nvx-brand-section nvx-home-faq-editorial" aria-labelledby="nvx-home-faq-title" data-nvx-faq-source="canonical" data-nvx-home-content="faq-v2">';
	$html .= '<div class="nvx-shell nvx-brand-section__inner">';
	$html .= '<p class="nvx-brand-kicker">' . esc_html__( 'Preguntas frecuentes', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-home-faq-title" class="nvx-brand-title">' . esc_html__( 'Información clara antes de decidir', 'nuvanx-medical' ) . '</h2>';
	$html .= '<div class="nvx-faq nvx-brand-faq-accordion">';
	foreach ( nvx_home_faq_v2_catalog() as $faq ) {
		$html .= '<details class="nvx-brand-faq-item" id="faq-' . esc_attr( $faq['id'] ) . '">';
		$html .= '<summary><span>' . esc_html( $faq['q'] ) . '</span></summary>';
		$html .= '<div class="nvx-brand-faq-content"><p>' . esc_html( $faq['a'] ) . '</p></div></details>';
	}
	return $html . '</div></div></section>';
}

function nvx_home_faq_v2_nearest_section( DOMElement $node ): DOMElement {
	$current = $node;
	do {
		if ( 'section' === strtolower( $current->tagName ) ) {
			return $current;
		}
		$current = $current->parentNode;
	} while ( $current instanceof DOMElement );
	return $node;
}

/** @return array<int,DOMElement> */
function nvx_home_faq_v2_candidates( DOMXPath $xpath, DOMElement $root ): array {
	$found = array();
	$nodes = $xpath->query(
		'.//*[contains(concat(" ",normalize-space(@class)," ")," nvx-home-faq-editorial ") '
		. 'or contains(concat(" ",normalize-space(@class)," ")," nvx-brand-faq-accordion ") '
		. 'or contains(concat(" ",normalize-space(@class)," ")," nvx-faq ")]',
		$root
	);
	if ( false !== $nodes ) {
		foreach ( $nodes as $node ) {
			if ( $node instanceof DOMElement ) {
				$section = nvx_home_faq_v2_nearest_section( $node );
				$found[ spl_object_id( $section ) ] = $section;
			}
		}
	}
	return array_values( $found );
}

function nvx_home_faq_v2_import( DOMDocument $target ): ?DOMElement {
	$source = new DOMDocument( '1.0', 'UTF-8' );
	$source->loadHTML( '<?xml encoding="utf-8" ?>' . nvx_home_faq_v2_markup(), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
	$imported = $target->importNode( $source->documentElement, true );
	return $imported instanceof DOMElement ? $imported : null;
}

function nvx_home_faq_v2_transform( string $content ): string {
	if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ! is_front_page() || false !== strpos( $content, 'data-nvx-faq-source="canonical"' ) || ! class_exists( 'DOMDocument' ) ) {
		// Allow refresh when already marked: rebuild markup.
		if ( is_front_page() && false !== strpos( $content, 'data-nvx-faq-source="canonical"' ) ) {
			$refreshed = preg_replace(
				'/<section\b[^>]*\bid=["\']nvx-home-faq["\'][^>]*>[\s\S]*?<\/section>/iu',
				nvx_home_faq_v2_markup(),
				$content,
				1
			);
			return is_string( $refreshed ) ? $refreshed : $content;
		}
		return $content;
	}

	$previous = libxml_use_internal_errors( true );
	$document = new DOMDocument( '1.0', 'UTF-8' );
	$wrapped  = '<div id="nvx-home-faq-v2-root">' . $content . '</div>';
	if ( ! $document->loadHTML( '<?xml encoding="utf-8" ?>' . $wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD ) ) {
		libxml_clear_errors();
		libxml_use_internal_errors( $previous );
		return $content;
	}

	$root = $document->getElementById( 'nvx-home-faq-v2-root' );
	if ( ! $root ) {
		libxml_clear_errors();
		libxml_use_internal_errors( $previous );
		return $content;
	}

	$xpath      = new DOMXPath( $document );
	$candidates = nvx_home_faq_v2_candidates( $xpath, $root );
	$import     = nvx_home_faq_v2_import( $document );
	if ( ! $import ) {
		libxml_clear_errors();
		libxml_use_internal_errors( $previous );
		return $content;
	}

	if ( ! empty( $candidates ) ) {
		$first = true;
		foreach ( $candidates as $section ) {
			if ( $first && $section->parentNode ) {
				$section->parentNode->replaceChild( $import, $section );
				$first = false;
			} elseif ( $section->parentNode ) {
				$section->parentNode->removeChild( $section );
			}
		}
	} else {
		$root->appendChild( $import );
	}

	$output = '';
	foreach ( $root->childNodes as $child ) {
		$output .= $document->saveHTML( $child );
	}
	libxml_clear_errors();
	libxml_use_internal_errors( $previous );
	return is_string( $output ) && '' !== trim( $output ) ? $output : $content;
}
add_filter( 'the_content', 'nvx_home_faq_v2_transform', 140 );

/** Return whether a Schema.org @type value contains the requested type. */
function nvx_home_faq_v2_has_type( $types, string $type ): bool {
	return in_array( $type, is_array( $types ) ? $types : array( $types ), true );
}

/** Build Question nodes from the same catalogue used for visible HTML. */
function nvx_home_faq_v2_schema_entities(): array {
	$entities = array();
	foreach ( nvx_home_faq_v2_catalog() as $faq ) {
		if ( empty( $faq['q'] ) || empty( $faq['a'] ) ) {
			continue;
		}
		$entities[] = array(
			'@type'          => 'Question',
			'name'           => $faq['q'],
			'acceptedAnswer' => array(
				'@type' => 'Answer',
				'text'  => $faq['a'],
			),
		);
	}
	return $entities;
}

/**
 * Consolidate the homepage FAQ into one Yoast graph node.
 *
 * Preference order: an existing WebPage+FAQPage, an existing FAQPage, an
 * existing WebPage, or a new FAQPage. Every other FAQPage node is removed.
 */
function nvx_home_faq_v2_schema_graph( array $graph, $context = null ): array {
	if ( ! is_front_page() ) {
		return $graph;
	}

	$preferred = null;
	$fallback_faq = null;
	$fallback_webpage = null;
	foreach ( $graph as $index => $piece ) {
		if ( ! is_array( $piece ) || ! isset( $piece['@type'] ) ) {
			continue;
		}
		$is_faq = nvx_home_faq_v2_has_type( $piece['@type'], 'FAQPage' );
		$is_web = nvx_home_faq_v2_has_type( $piece['@type'], 'WebPage' );
		if ( $is_faq && $is_web ) {
			$preferred = $index;
			break;
		}
		if ( $is_faq && null === $fallback_faq ) {
			$fallback_faq = $index;
		}
		if ( $is_web && null === $fallback_webpage ) {
			$fallback_webpage = $index;
		}
	}

	$preferred = null !== $preferred ? $preferred : ( null !== $fallback_faq ? $fallback_faq : $fallback_webpage );
	if ( null === $preferred ) {
		$graph[] = array(
			'@type' => array( 'WebPage', 'FAQPage' ),
			'@id'   => home_url( '/#webpage' ),
			'url'   => home_url( '/' ),
		);
		$preferred = array_key_last( $graph );
	}

	$types = isset( $graph[ $preferred ]['@type'] ) && is_array( $graph[ $preferred ]['@type'] )
		? $graph[ $preferred ]['@type']
		: array( $graph[ $preferred ]['@type'] ?? 'WebPage' );
	if ( ! in_array( 'FAQPage', $types, true ) ) {
		$types[] = 'FAQPage';
	}
	$graph[ $preferred ]['@type']      = array_values( array_unique( array_filter( $types ) ) );
	$graph[ $preferred ]['mainEntity'] = nvx_home_faq_v2_schema_entities();
	$graph[ $preferred ]['url']        = $graph[ $preferred ]['url'] ?? home_url( '/' );
	$graph[ $preferred ]['@id']        = $graph[ $preferred ]['@id'] ?? home_url( '/#webpage' );

	foreach ( array_keys( $graph ) as $index ) {
		if ( $index === $preferred || ! isset( $graph[ $index ]['@type'] ) ) {
			continue;
		}
		if ( nvx_home_faq_v2_has_type( $graph[ $index ]['@type'], 'FAQPage' ) ) {
			unset( $graph[ $index ] );
		}
	}

	return array_values( $graph );
}
add_filter( 'wpseo_schema_graph', 'nvx_home_faq_v2_schema_graph', 99, 2 );
