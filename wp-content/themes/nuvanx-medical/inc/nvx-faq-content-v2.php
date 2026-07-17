<?php
/**
 * Canonical home FAQ: one catalogue for visible HTML and Yoast schema.
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
	$format = static function ( float $amount ): string {
		return function_exists( 'nvx_format_price_eur' )
			? (string) nvx_format_price_eur( $amount )
			: number_format( $amount, 2, ',', '.' );
	};

	return array(
		array(
			'id' => 'valoracion-coste',
			'q'  => '¿La valoración médica en NUVANX tiene coste?',
			'a'  => 'No. La valoración médica inicial es gratuita e incluye evaluación del caso, confirmación de si existe indicación y explicación de las opciones disponibles antes de decidir un tratamiento.',
		),
		array(
			'id' => 'tratamiento-adecuado',
			'q'  => '¿Cómo sé qué tratamiento necesito?',
			'a'  => 'La indicación se define tras una valoración médica. Se revisan la piel, la anatomía, el historial, los objetivos y las contraindicaciones antes de seleccionar tecnología, parámetros o número de sesiones.',
		),
		array(
			'id' => 'tecnologia-medica',
			'q'  => '¿Qué tecnología médica utiliza NUVANX?',
			'a'  => 'NUVANX trabaja con plataformas médicas con marcado CE como DEKA Motus AZ+, Láser CO₂ fraccionado y EXION® BTL. La plataforma se elige según la indicación clínica, no por una configuración estándar.',
		),
		array(
			'id' => 'recuperacion',
			'q'  => '¿Cuánto tiempo de recuperación tienen los tratamientos?',
			'a'  => 'Depende del procedimiento y de su intensidad. El Endolift® puede producir inflamación, tirantez o hematomas leves durante 3 a 7 días; el Láser CO₂ suele requerir entre 4 y 7 días de recuperación visible. En la valoración se explica el período esperado para cada caso.',
		),
		array(
			'id' => 'clinicas-madrid',
			'q'  => '¿Dónde están las clínicas NUVANX en Madrid?',
			'a'  => 'NUVANX dispone de dos centros sanitarios autorizados: Chamberí, en Calle de Fernández de la Hoz 4, registro CS20144, y Salamanca–Goya, en Calle de Fernán González 26, registro CS20073.',
		),
		array(
			'id' => 'precio-endolift',
			'q'  => '¿Cuánto cuesta el Endolift® facial en NUVANX?',
			'a'  => 'Las tarifas faciales parten desde ' . $format( $from ) . ' € para ojeras. El PVP de papada o marcación mandibular es ' . $format( $papada ) . ' € por zona. El presupuesto definitivo se documenta tras valorar la anatomía y las zonas indicadas.',
		),
		array(
			'id' => 'exion-morpheus',
			'q'  => '¿EXION® Fractional RF es una alternativa a Morpheus8®?',
			'a'  => 'Puede ser una alternativa dentro de la radiofrecuencia fraccionada con microagujas, según el caso. La elección depende del objetivo, la calidad de la piel, los parámetros necesarios y el período de recuperación aceptable; no de un ranking comercial entre marcas.',
		),
	);
}

function nvx_home_faq_v2_markup(): string {
	$html  = '<section id="nvx-home-faq" class="nvx-brand-section nvx-home-faq-editorial" aria-labelledby="nvx-home-faq-title" data-nvx-faq-source="canonical">';
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

	// Fallback only inspects semantic sections, never generic page wrappers.
	if ( empty( $found ) ) {
		$sections = $xpath->query( './/section[.//details]', $root );
		if ( false !== $sections ) {
			foreach ( $sections as $section ) {
				if ( ! $section instanceof DOMElement ) {
					continue;
				}
				$heading = $xpath->query( './/h2|.//h3', $section );
				foreach ( false !== $heading ? $heading : array() as $item ) {
					$text = trim( preg_replace( '/\s+/u', ' ', $item->textContent ) );
					if ( false !== stripos( $text, 'preguntas' ) || false !== stripos( $text, 'frecuentes' ) ) {
						$found[ spl_object_id( $section ) ] = $section;
						break;
					}
				}
			}
		}
	}
	return array_values( $found );
}

function nvx_home_faq_v2_import( DOMDocument $target ): ?DOMElement {
	$errors = libxml_use_internal_errors( true );
	$source = new DOMDocument( '1.0', 'UTF-8' );
	$source->loadHTML( '<?xml encoding="utf-8" ?>' . nvx_home_faq_v2_markup(), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
	$node = $source->getElementById( 'nvx-home-faq' );
	libxml_clear_errors();
	libxml_use_internal_errors( $errors );
	if ( ! $node instanceof DOMElement ) {
		return null;
	}
	$copy = $target->importNode( $node, true );
	return $copy instanceof DOMElement ? $copy : null;
}

function nvx_home_faq_v2_transform( string $content ): string {
	if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ! is_front_page() || false !== strpos( $content, 'data-nvx-faq-source="canonical"' ) || ! class_exists( 'DOMDocument' ) ) {
		return $content;
	}

	$errors   = libxml_use_internal_errors( true );
	$document = new DOMDocument( '1.0', 'UTF-8' );
	$loaded   = $document->loadHTML( '<?xml encoding="utf-8" ?><div id="nvx-home-faq-root">' . $content . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
	$root     = $document->getElementById( 'nvx-home-faq-root' );
	if ( ! $loaded || ! $root ) {
		libxml_clear_errors();
		libxml_use_internal_errors( $errors );
		return $content;
	}

	$xpath      = new DOMXPath( $document );
	$candidates = nvx_home_faq_v2_candidates( $xpath, $root );
	$canonical  = nvx_home_faq_v2_import( $document );
	if ( ! $canonical ) {
		libxml_clear_errors();
		libxml_use_internal_errors( $errors );
		return $content;
	}

	if ( ! empty( $candidates ) ) {
		$first = array_shift( $candidates );
		if ( $first->parentNode ) {
			$first->parentNode->replaceChild( $canonical, $first );
		}
		foreach ( $candidates as $duplicate ) {
			if ( $duplicate->parentNode ) {
				$duplicate->parentNode->removeChild( $duplicate );
			}
		}
	} else {
		$root->appendChild( $canonical );
	}

	$output = '';
	foreach ( $root->childNodes as $child ) {
		$output .= $document->saveHTML( $child );
	}
	libxml_clear_errors();
	libxml_use_internal_errors( $errors );
	return '' !== trim( $output ) ? $output : $content;
}
add_filter( 'the_content', 'nvx_home_faq_v2_transform', 135 );

function nvx_home_faq_v2_schema_entities(): array {
	$entities = array();
	foreach ( nvx_home_faq_v2_catalog() as $faq ) {
		$entities[] = array(
			'@type'          => 'Question',
			'@id'            => home_url( '/#faq-' . $faq['id'] ),
			'name'           => $faq['q'],
			'acceptedAnswer' => array( '@type' => 'Answer', 'text' => $faq['a'] ),
		);
	}
	return $entities;
}

function nvx_home_faq_v2_has_type( $types, string $type ): bool {
	return in_array( $type, is_array( $types ) ? $types : array( $types ), true );
}

function nvx_home_faq_v2_schema_graph( $graph ) {
	if ( ! is_front_page() || ! is_array( $graph ) ) {
		return $graph;
	}
	$entities = nvx_home_faq_v2_schema_entities();
	$found    = false;
	$output   = array();
	foreach ( $graph as $piece ) {
		$is_faq = is_array( $piece ) && isset( $piece['@type'] ) && nvx_home_faq_v2_has_type( $piece['@type'], 'FAQPage' );
		if ( ! $is_faq ) {
			$output[] = $piece;
			continue;
		}
		if ( $found ) {
			continue;
		}
		$piece['mainEntity'] = $entities;
		$piece['@id']        = empty( $piece['@id'] ) ? home_url( '/#faq' ) : $piece['@id'];
		$piece['url']        = empty( $piece['url'] ) ? home_url( '/' ) : $piece['url'];
		$output[]            = $piece;
		$found               = true;
	}
	if ( ! $found ) {
		$output[] = array( '@type' => 'FAQPage', '@id' => home_url( '/#faq' ), 'url' => home_url( '/' ), 'mainEntity' => $entities );
	}
	return array_values( $output );
}
add_filter( 'wpseo_schema_graph', 'nvx_home_faq_v2_schema_graph', 100, 1 );
