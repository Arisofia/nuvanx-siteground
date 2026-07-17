<?php
/**
 * Canonical home content v2.
 *
 * Separates the value proposition (why NUVANX) from the clinical workflow
 * (how NUVANX works), makes the valuation CTA concrete, and removes absolute
 * protocol claims that do not yet have a repository-backed clinical claim ID.
 * Existing layout, icon and action markup is preserved.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Canonical content catalogue for the front page.
 *
 * @return array<string,mixed>
 */
function nvx_home_content_v2_catalog(): array {
	return array(
		'values'    => array(
			'kicker' => 'Por qué NUVANX',
			'title'  => 'Medicina estética donde el diagnóstico decide la tecnología',
			'items'  => array(
				array(
					'title' => '1. Diagnóstico antes de tecnología',
					'body'  => 'Cada protocolo comienza con una valoración médica de 15 a 30 minutos: calidad de piel, historial, objetivos y contraindicaciones. Solo se indica un tratamiento cuando existe una razón clínica para hacerlo.',
				),
				array(
					'title' => '2. Equipamiento médico certificado',
					'body'  => 'Trabajamos con plataformas médicas con marcado CE como DEKA Motus AZ+, Láser CO₂ fraccionado y EXION® BTL. La tecnología y sus parámetros se seleccionan según la anatomía y el objetivo de cada paciente.',
				),
				array(
					'title' => '3. Resultados naturales y expectativa realista',
					'body'  => 'El objetivo es mejorar firmeza, textura y definición respetando la expresión y la identidad del rostro. Antes de tratar, explicamos qué puede mejorar, qué límites existen y qué recuperación requiere cada protocolo.',
				),
			),
		),
		'action'    => array(
			'kicker'         => 'Valoración médica gratuita',
			'title'          => '15–30 minutos para saber si existe indicación',
			'body'           => 'Evaluamos tu caso, explicamos las opciones disponibles y documentamos el presupuesto antes de cualquier decisión. Presencial en Chamberí o Salamanca–Goya.',
			'primary_label'  => 'Reservar valoración gratuita',
			'secondary_label'=> 'Consultar con la clínica',
		),
		'method'    => array(
			'kicker' => 'Cómo trabajamos',
			'title'  => 'Un protocolo médico en tres decisiones',
			'lead'   => 'La evaluación, la indicación y el seguimiento forman un único proceso clínico.',
			'items'  => array(
				array(
					'title' => 'Evaluación individual',
					'body'  => 'Revisamos piel, anatomía, historial, objetivos y contraindicaciones antes de proponer un procedimiento.',
				),
				array(
					'title' => 'Indicación y parámetros',
					'body'  => 'Definimos tecnología, energía, profundidad y número de sesiones según el caso, no mediante configuraciones estándar.',
				),
				array(
					'title' => 'Control de evolución',
					'body'  => 'Programamos seguimiento según el tratamiento para valorar respuesta, recuperación y necesidad de ajustes.',
				),
			),
		),
		'protocols' => array(
			array(
				'title' => 'Endolift® Facial: retracción subdérmica y definición mandibular',
				'lead'  => 'Procedimiento médico mínimamente invasivo con microfibra óptica de 200 a 300 micras. La energía láser intersticial actúa en tejido subcutáneo para favorecer lipólisis selectiva y retracción térmica en papada, contorno mandibular y cuello, cuando existe indicación anatómica.',
				'facts' => array(
					'Indicación médica principal'    => 'Flacidez leve a moderada y grasa submentoniana seleccionada.',
					'Recuperación clínica estimada' => 'Inflamación, tirantez o hematomas leves durante 3 a 7 días según el caso.',
				),
			),
			array(
				'title' => 'Endoláser Corporal: lipólisis láser selectiva',
				'lead'  => 'El calor controlado de la fibra láser actúa sobre adiposidad localizada y produce un estímulo térmico de retracción cutánea. La indicación depende de la zona, la calidad de la piel, el volumen de grasa y la expectativa de resultado.',
				'facts' => array(
					'Zonas que pueden valorarse' => 'Abdomen, flancos, cara interna de muslos, rodillas, brazos y otras áreas seleccionadas.',
				),
			),
			array(
				'title' => 'Láser CO₂ Fraccionado: renovación cutánea controlada',
				'lead'  => 'El láser CO₂ crea microcolumnas de ablación fraccionada para tratar cicatrices atróficas de acné, poros, textura irregular y fotodaño. La profundidad y la densidad se ajustan al fototipo, la indicación y el período de recuperación aceptable.',
				'facts' => array(
					'Resultados clínicos' => 'Mejora progresiva de textura y estímulo de remodelación de colágeno.',
					'Recuperación'        => 'Habitualmente de 4 a 7 días, según la profundidad del protocolo.',
				),
			),
		),
	);
}

/** Test whether a node has a class token. */
function nvx_home_content_v2_has_class( DOMElement $node, string $class_name ): bool {
	$classes = preg_split( '/\s+/', trim( $node->getAttribute( 'class' ) ) ) ?: array();
	return in_array( $class_name, $classes, true );
}

/** Replace all child nodes with a plain text node. */
function nvx_home_content_v2_set_text( DOMElement $node, string $text ): void {
	while ( $node->firstChild ) {
		$node->removeChild( $node->firstChild );
	}
	$node->appendChild( $node->ownerDocument->createTextNode( $text ) );
}

/** Return the first descendant matching a class and optional tag. */
function nvx_home_content_v2_first( DOMXPath $xpath, DOMNode $context, string $class_name, string $tag = '*' ): ?DOMElement {
	$query = './/' . $tag . '[contains(concat(" ", normalize-space(@class), " "), " ' . $class_name . ' ")]';
	$nodes = $xpath->query( $query, $context );
	if ( false === $nodes || 0 === $nodes->length ) {
		return null;
	}
	$node = $nodes->item( 0 );
	return $node instanceof DOMElement ? $node : null;
}

/** Update a repeated card collection by title/body classes. */
function nvx_home_content_v2_update_cards(
	DOMXPath $xpath,
	DOMElement $section,
	string $card_class,
	string $title_class,
	string $body_class,
	array $items
): void {
	$cards = $xpath->query(
		'.//*[contains(concat(" ", normalize-space(@class), " "), " ' . $card_class . ' ")]',
		$section
	);
	if ( false === $cards ) {
		return;
	}

	foreach ( iterator_to_array( $cards ) as $index => $card ) {
		if ( ! $card instanceof DOMElement || empty( $items[ $index ] ) ) {
			continue;
		}
		$title = nvx_home_content_v2_first( $xpath, $card, $title_class );
		$body  = nvx_home_content_v2_first( $xpath, $card, $body_class );
		if ( $title ) {
			nvx_home_content_v2_set_text( $title, (string) $items[ $index ]['title'] );
		}
		if ( $body ) {
			nvx_home_content_v2_set_text( $body, (string) $items[ $index ]['body'] );
		}
	}
}

/** Apply canonical front-page section content after the legacy presentation layer. */
function nvx_home_content_v2_transform( string $content ): string {
	if (
		is_admin()
		|| wp_doing_ajax()
		|| ( defined( 'REST_REQUEST' ) && REST_REQUEST )
		|| ! is_front_page()
		|| false !== strpos( $content, 'data-nvx-home-content="v2"' )
		|| ! class_exists( 'DOMDocument' )
	) {
		return $content;
	}

	$previous_errors = libxml_use_internal_errors( true );
	$document        = new DOMDocument( '1.0', 'UTF-8' );
	$wrapped         = '<div id="nvx-home-content-v2-root">' . $content . '</div>';
	$loaded          = $document->loadHTML(
		'<?xml encoding="utf-8" ?>' . $wrapped,
		LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
	);
	if ( ! $loaded ) {
		libxml_clear_errors();
		libxml_use_internal_errors( $previous_errors );
		return $content;
	}

	$root = $document->getElementById( 'nvx-home-content-v2-root' );
	if ( ! $root ) {
		libxml_clear_errors();
		libxml_use_internal_errors( $previous_errors );
		return $content;
	}

	$xpath   = new DOMXPath( $document );
	$catalog = nvx_home_content_v2_catalog();

	$values = nvx_home_content_v2_first( $xpath, $root, 'nvx-values-section', 'section' );
	if ( $values ) {
		$values->setAttribute( 'data-nvx-home-content', 'v2' );
		$kicker = nvx_home_content_v2_first( $xpath, $values, 'nvx-brand-kicker', 'p' );
		$title  = nvx_home_content_v2_first( $xpath, $values, 'nvx-brand-title', 'h2' );
		if ( $kicker ) {
			nvx_home_content_v2_set_text( $kicker, $catalog['values']['kicker'] );
		}
		if ( $title ) {
			nvx_home_content_v2_set_text( $title, $catalog['values']['title'] );
		}
		nvx_home_content_v2_update_cards(
			$xpath,
			$values,
			'nvx-value',
			'nvx-value__title',
			'nvx-value__body',
			$catalog['values']['items']
		);
	}

	$action = nvx_home_content_v2_first( $xpath, $root, 'nvx-home-action-banner', 'section' );
	if ( $action ) {
		$kicker = nvx_home_content_v2_first( $xpath, $action, 'nvx-home-action-banner__kicker', 'p' );
		$title  = nvx_home_content_v2_first( $xpath, $action, 'nvx-home-action-banner__title', 'h2' );
		$body   = nvx_home_content_v2_first( $xpath, $action, 'nvx-home-action-banner__text', 'p' );
		if ( $kicker ) {
			nvx_home_content_v2_set_text( $kicker, $catalog['action']['kicker'] );
		}
		if ( $title ) {
			nvx_home_content_v2_set_text( $title, $catalog['action']['title'] );
		}
		if ( $body ) {
			nvx_home_content_v2_set_text( $body, $catalog['action']['body'] );
		}
		$links = $xpath->query( './/a', $action );
		if ( false !== $links ) {
			foreach ( iterator_to_array( $links ) as $index => $link ) {
				if ( ! $link instanceof DOMElement ) {
					continue;
				}
				if ( 0 === $index ) {
					nvx_home_content_v2_set_text( $link, $catalog['action']['primary_label'] );
				} elseif ( 1 === $index ) {
					nvx_home_content_v2_set_text( $link, $catalog['action']['secondary_label'] );
				}
			}
		}
	}

	$method = nvx_home_content_v2_first( $xpath, $root, 'nvx-method-section', 'section' );
	if ( $method ) {
		$kicker = nvx_home_content_v2_first( $xpath, $method, 'nvx-brand-kicker', 'p' );
		$title  = nvx_home_content_v2_first( $xpath, $method, 'nvx-brand-title', 'h2' );
		$lead   = nvx_home_content_v2_first( $xpath, $method, 'nvx-method-lead', 'p' );
		if ( $kicker ) {
			nvx_home_content_v2_set_text( $kicker, $catalog['method']['kicker'] );
		}
		if ( $title ) {
			nvx_home_content_v2_set_text( $title, $catalog['method']['title'] );
		}
		if ( $lead ) {
			nvx_home_content_v2_set_text( $lead, $catalog['method']['lead'] );
		}
		nvx_home_content_v2_update_cards(
			$xpath,
			$method,
			'nvx-method-col',
			'nvx-method-col__title',
			'nvx-method-col__body',
			$catalog['method']['items']
		);
	}

	$protocols = nvx_home_content_v2_first( $xpath, $root, 'nvx-home-protocols', 'section' );
	if ( $protocols ) {
		$cards = $xpath->query(
			'.//*[contains(concat(" ", normalize-space(@class), " "), " nvx-home-protocol ")]',
			$protocols
		);
		if ( false !== $cards ) {
			foreach ( iterator_to_array( $cards ) as $index => $card ) {
				if ( ! $card instanceof DOMElement || empty( $catalog['protocols'][ $index ] ) ) {
					continue;
				}
				$item  = $catalog['protocols'][ $index ];
				$title = nvx_home_content_v2_first( $xpath, $card, 'nvx-home-protocol__title' );
				$lead  = nvx_home_content_v2_first( $xpath, $card, 'nvx-home-protocol__lead', 'p' );
				if ( $title ) {
					nvx_home_content_v2_set_text( $title, $item['title'] );
				}
				if ( $lead ) {
					nvx_home_content_v2_set_text( $lead, $item['lead'] );
				}
				$facts = $xpath->query(
					'.//*[contains(concat(" ", normalize-space(@class), " "), " nvx-home-protocol__fact ")]',
					$card
				);
				if ( false !== $facts ) {
					$fact_data = array_values( $item['facts'] );
					$fact_keys = array_keys( $item['facts'] );
					foreach ( iterator_to_array( $facts ) as $fact_index => $fact ) {
						if ( ! $fact instanceof DOMElement || ! isset( $fact_keys[ $fact_index ] ) ) {
							continue;
						}
						$dt = $xpath->query( './/dt', $fact );
						$dd = $xpath->query( './/dd', $fact );
						if ( false !== $dt && $dt->item( 0 ) instanceof DOMElement ) {
							nvx_home_content_v2_set_text( $dt->item( 0 ), $fact_keys[ $fact_index ] );
						}
						if ( false !== $dd && $dd->item( 0 ) instanceof DOMElement ) {
							nvx_home_content_v2_set_text( $dd->item( 0 ), $fact_data[ $fact_index ] );
						}
					}
				}
			}
		}
	}

	$output = '';
	foreach ( $root->childNodes as $child ) {
		$output .= $document->saveHTML( $child );
	}

	libxml_clear_errors();
	libxml_use_internal_errors( $previous_errors );

	return is_string( $output ) && '' !== trim( $output ) ? $output : $content;
}
add_filter( 'the_content', 'nvx_home_content_v2_transform', 130 );
