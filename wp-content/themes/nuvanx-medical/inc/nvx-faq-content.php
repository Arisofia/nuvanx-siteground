<?php
/**
 * Canonical home FAQ shared by visible HTML and Yoast's schema graph.
 *
 * One catalogue is the sole source for question wording and answers. Existing
 * Yoast FAQPage pieces are updated in place so a combined WebPage + FAQPage
 * node retains its identifier and graph references.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Canonical home FAQ catalogue.
 *
 * @return array<int,array{id:string,q:string,a:string}>
 */
function nvx_home_faq_catalog(): array {
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

/** Build the canonical visible FAQ section. */
function nvx_home_faq_markup(): string {
	$html  = '<section id="nvx-home-faq" class="nvx-brand-section nvx-home-faq-editorial" aria-labelledby="nvx-home-faq-title" data-nvx-faq-source="canonical">';
	$html .= '<div class="nvx-shell nvx-brand-section__inner">';
	$html .= '<p class="nvx-brand-kicker">' . esc_html__( 'Preguntas frecuentes', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-home-faq-title" class="nvx-brand-title">' . esc_html__( 'Información clara antes de decidir', 'nuvanx-medical' ) . '</h2>';
	$html .= '<div class="nvx-faq nvx-brand-faq-accordion">';

	foreach ( nvx_home_faq_catalog() as $faq ) {
		$html .= '<details class="nvx-brand-faq-item" id="faq-' . esc_attr( $faq['id'] ) . '">';
		$html .= '<summary><span>' . esc_html( $faq['q'] ) . '</span></summary>';
		$html .= '<div class="nvx-brand-faq-content"><p>' . esc_html( $faq['a'] ) . '</p></div>';
		$html .= '</details>';
	}

	$html .= '</div></div></section>';
	return $html;
}

/** Determine whether an element is a likely FAQ section. */
function nvx_home_faq_is_candidate( DOMXPath $xpath, DOMElement $element ): bool {
	$classes = ' ' . preg_replace( '/\s+/', ' ', trim( $element->getAttribute( 'class' ) ) ) . ' ';
	if (
		false !== strpos( $classes, ' nvx-home-faq-editorial ' )
		|| false !== strpos( $classes, ' nvx-brand-faq-accordion ' )
	) {
		return true;
	}

	$details = $xpath->query( './/details', $element );
	if ( false === $details || 0 === $details->length ) {
		return false;
	}

	$headings = $xpath->query( './/h2|.//h3', $element );
	if ( false !== $headings ) {
		foreach ( $headings as $heading ) {
			$text = trim( preg_replace( '/\s+/u', ' ', $heading->textContent ) );
			if ( false !== stripos( $text, 'preguntas' ) || false !== stripos( $text, 'frecuentes' ) ) {
				return true;
			}
		}
	}

	return false;
}

/** Return the outermost relevant section for a candidate node. */
function nvx_home_faq_section_ancestor( DOMElement $element ): DOMElement {
	$current = $element;
	while ( $current->parentNode instanceof DOMElement ) {
		if ( 'section' === strtolower( $current->tagName ) ) {
			return $current;
		}
		$current = $current->parentNode;
	}
	return $element;
}

/** Parse one canonical FAQ section into the target document. */
function nvx_home_faq_import_section( DOMDocument $target ): ?DOMElement {
	$fragment_errors = libxml_use_internal_errors( true );
	$source          = new DOMDocument( '1.0', 'UTF-8' );
	$source->loadHTML(
		'<?xml encoding="utf-8" ?>' . nvx_home_faq_markup(),
		LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
	);
	$section = $source->getElementById( 'nvx-home-faq' );
	libxml_clear_errors();
	libxml_use_internal_errors( $fragment_errors );

	if ( ! $section instanceof DOMElement ) {
		return null;
	}
	$imported = $target->importNode( $section, true );
	return $imported instanceof DOMElement ? $imported : null;
}

/** Replace the home FAQ with the canonical visible catalogue. */
function nvx_home_faq_transform( string $content ): string {
	if (
		is_admin()
		|| wp_doing_ajax()
		|| ( defined( 'REST_REQUEST' ) && REST_REQUEST )
		|| ! is_front_page()
		|| false !== strpos( $content, 'data-nvx-faq-source="canonical"' )
		|| ! class_exists( 'DOMDocument' )
	) {
		return $content;
	}

	$previous_errors = libxml_use_internal_errors( true );
	$document        = new DOMDocument( '1.0', 'UTF-8' );
	$loaded          = $document->loadHTML(
		'<?xml encoding="utf-8" ?><div id="nvx-home-faq-root">' . $content . '</div>',
		LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
	);
	if ( ! $loaded ) {
		libxml_clear_errors();
		libxml_use_internal_errors( $previous_errors );
		return $content;
	}

	$root = $document->getElementById( 'nvx-home-faq-root' );
	if ( ! $root ) {
		libxml_clear_errors();
		libxml_use_internal_errors( $previous_errors );
		return $content;
	}

	$xpath      = new DOMXPath( $document );
	$candidates = array();
	$elements   = $xpath->query( './/section|.//div', $root );
	if ( false !== $elements ) {
		foreach ( $elements as $element ) {
			if ( $element instanceof DOMElement && nvx_home_faq_is_candidate( $xpath, $element ) ) {
				$section = nvx_home_faq_section_ancestor( $element );
				$candidates[ spl_object_id( $section ) ] = $section;
			}
		}
	}

	$canonical = nvx_home_faq_import_section( $document );
	if ( ! $canonical ) {
		libxml_clear_errors();
		libxml_use_internal_errors( $previous_errors );
		return $content;
	}

	if ( ! empty( $candidates ) ) {
		$first = array_shift( $candidates );
		if ( $first instanceof DOMElement && $first->parentNode ) {
			$first->parentNode->replaceChild( $canonical, $first );
		}
		foreach ( $candidates as $duplicate ) {
			if ( $duplicate instanceof DOMElement && $duplicate->parentNode ) {
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
	libxml_use_internal_errors( $previous_errors );
	return is_string( $output ) && '' !== trim( $output ) ? $output : $content;
}
add_filter( 'the_content', 'nvx_home_faq_transform', 135 );

/** Schema Question entities from the same visible catalogue. */
function nvx_home_faq_schema_entities(): array {
	$base = home_url( '/#preguntas-frecuentes' );
	$rows = array();
	foreach ( nvx_home_faq_catalog() as $faq ) {
		$rows[] = array(
			'@type'          => 'Question',
			'@id'            => $base . '/' . $faq['id'],
			'name'           => $faq['q'],
			'acceptedAnswer' => array(
				'@type' => 'Answer',
				'text'  => $faq['a'],
			),
		);
	}
	return $rows;
}

/** Test whether a schema type value includes FAQPage. */
function nvx_home_faq_schema_has_type( $types, string $type ): bool {
	$types = is_array( $types ) ? $types : array( $types );
	return in_array( $type, $types, true );
}

/** Replace existing home FAQPage data without deleting a combined WebPage node. */
function nvx_home_faq_schema_graph( $graph ) {
	if ( ! is_front_page() || ! is_array( $graph ) ) {
		return $graph;
	}

	$entities = nvx_home_faq_schema_entities();
	$found    = false;
	$output   = array();

	foreach ( $graph as $piece ) {
		if ( ! is_array( $piece ) || ! isset( $piece['@type'] ) || ! nvx_home_faq_schema_has_type( $piece['@type'], 'FAQPage' ) ) {
			$output[] = $piece;
			continue;
		}

		if ( $found ) {
			// Remove duplicate FAQPage pieces; the first piece retains graph identity.
			continue;
		}

		$piece['mainEntity'] = $entities;
		if ( empty( $piece['@id'] ) ) {
			$piece['@id'] = home_url( '/#faq' );
		}
		if ( empty( $piece['url'] ) ) {
			$piece['url'] = home_url( '/' );
		}
		$output[] = $piece;
		$found    = true;
	}

	if ( ! $found ) {
		$output[] = array(
			'@type'      => 'FAQPage',
			'@id'        => home_url( '/#faq' ),
			'url'        => home_url( '/' ),
			'mainEntity' => $entities,
		);
	}

	return array_values( $output );
}
add_filter( 'wpseo_schema_graph', 'nvx_home_faq_schema_graph', 100, 1 );
