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
 * Renders the benefits section using evidence-aware, patient-first copy and original SVG icons.
 */
function nvx_home_benefits_markup(): string {
	return '
<section class="nvx-brand-section nvx-benefits-section" aria-labelledby="nvx-benefits-title">
	<div class="nvx-container">
		<header class="nvx-benefits__header">
			<p class="nvx-brand-kicker">Criterio médico NUVANX</p>
			<h2 class="nvx-brand-title" id="nvx-benefits-title">Medicina estética donde el diagnóstico precede a la tecnología.</h2>
			<p class="nvx-brand-lead">Cada protocolo comienza con una valoración individual. El equipo médico evalúa anatomía, historial, objetivos y contraindicaciones antes de considerar cualquier técnica. Sin diagnóstico previo no se activa ningún protocolo.</p>
		</header>
		<div class="nvx-benefits__grid">
			<div class="nvx-benefit-item">
				<img src="' . esc_url( get_stylesheet_directory_uri() ) . '/assets/images/benefits/resultados-definitivos.svg" alt="" class="nvx-benefit-icon">
				<span class="nvx-benefit-text">OBJETIVOS REALISTAS</span>
			</div>
			<div class="nvx-benefit-item">
				<img src="' . esc_url( get_stylesheet_directory_uri() ) . '/assets/images/benefits/recuperacion-rapida.svg" alt="" class="nvx-benefit-icon">
				<span class="nvx-benefit-text">RECUPERACIÓN PLANIFICADA</span>
			</div>
			<div class="nvx-benefit-item">
				<img src="' . esc_url( get_stylesheet_directory_uri() ) . '/assets/images/benefits/paciente-despierto.svg" alt="" class="nvx-benefit-icon">
				<span class="nvx-benefit-text">ACOMPAÑAMIENTO MÉDICO</span>
			</div>
			<div class="nvx-benefit-item">
				<img src="' . esc_url( get_stylesheet_directory_uri() ) . '/assets/images/benefits/sin-bisturi.svg" alt="" class="nvx-benefit-icon">
				<span class="nvx-benefit-text">TÉCNICA SEGÚN INDICACIÓN</span>
			</div>
			<div class="nvx-benefit-item">
				<img src="' . esc_url( get_stylesheet_directory_uri() ) . '/assets/images/benefits/solo-una-vez.svg" alt="" class="nvx-benefit-icon">
				<span class="nvx-benefit-text">PLAN PERSONALIZADO</span>
			</div>
			<div class="nvx-benefit-item">
				<img src="' . esc_url( get_stylesheet_directory_uri() ) . '/assets/images/benefits/efecto-natural.svg" alt="" class="nvx-benefit-icon">
				<span class="nvx-benefit-text">RESULTADO ARMÓNICO</span>
			</div>
		</div>
	</div>
</section>
	';
}

/**
 * Canonical content catalogue for the front page.
 *
 * @return array<string,mixed>
 */
function nvx_home_content_v2_catalog(): array {
	return array(
		'values'    => array(
			'kicker' => 'Criterio médico NUVANX',
			'title'  => 'Madrid. Dos sedes. Un único criterio médico.',
			'items'  => array(
				array(
					'title' => 'Diagnóstico antes de tecnología:',
					'body'  => 'Antes de proponer un tratamiento, revisamos anatomía, antecedentes, objetivos y contraindicaciones. Sin diagnóstico previo no se activa ningún protocolo.',
				),
				array(
					'title' => 'Tecnología según indicación:',
					'body'  => 'La elección de plataforma —DEKA, EXION® BTL, CO₂ fraccionado— se decide tras la exploración. No se parte de un catálogo de servicios ni de una promesa de resultado.',
				),
				array(
					'title' => 'Información y seguimiento:',
					'body'  => 'Antes de tratar, explicamos qué puede mejorar, qué no, y por qué. El presupuesto se documenta por escrito antes de cualquier decisión.',
				),
			),
		),
		'action'    => array(
			'kicker'          => 'Valoración médica individualizada',
			'title'           => 'Una consulta para determinar si existe indicación en tu caso.',
			'body'            => 'Evaluamos tu anatomía, resolvemos dudas y documentamos las alternativas y el presupuesto tras la exploración. Atención presencial en Chamberí (Almagro, CS20144) y Salamanca–Goya (Barrio de Salamanca, CS20073).',
			'primary_label'   => 'Iniciar mi valoración médica',
			'secondary_label' => 'Contactar por WhatsApp',
		),
		'method'    => array(
			'kicker' => 'Cómo trabajamos',
			'title'  => 'Un protocolo médico en tres decisiones',
			'lead'   => 'La evaluación, la indicación y el seguimiento forman un único proceso clínico.',
			'items'  => array(
				array(
					'title' => 'Diagnóstico preciso',
					'body'  => '"Si no hay indicación clínica, no hay tratamiento." El Dr. Rivera evalúa cada caso antes de recomendar cualquier procedimiento. No existe protocolo estándar: existe tu protocolo.',
				),
				array(
					'title' => 'Trazabilidad total',
					'body'  => '"Sabes exactamente qué se te aplica, en qué cantidad y quién lo hace." Inyectables Allergan® y Merz® con código de lote en tu historial. Técnica firmada en el presupuesto antes del procedimiento.',
				),
				array(
					'title' => 'Continuidad clínica',
					'body'  => '"El Dr. Rivera que hace tu diagnóstico es el mismo que ejecuta el procedimiento y el mismo que hace tu seguimiento." No hay rotación de médicos. No hay delegación silenciosa.',
				),
			),
		),
		'protocols' => array(
			array(
				'title' => 'Endolift® facial: tensado con microfibra láser',
				'lead'  => 'Tratamiento láser intersticial para zonas seleccionadas cuando existe indicación. La indicación la decide el diagnóstico, no la zona que incomoda.',
				'facts' => array(
					'Presupuesto'   => 'Se documenta por escrito tras la valoración médica.',
					'Recuperación' => 'Se explica según la zona, el protocolo y la respuesta individual.',
				),
			),
			array(
				'title' => 'Endoláser corporal: grasa localizada y contorno',
				'lead'  => 'Protocolo láser para zonas seleccionadas tras valoración médica. No es tratamiento de obesidad ni liposucción.',
				'facts' => array(
					'Zonas habituales' => 'Abdomen, flancos, muslos, rodillas, brazos y otras áreas seleccionadas.',
					'Inversión'        => 'Presupuesto por zonas tras valoración médica.',
				),
			),
			array(
				'title' => 'Láser CO₂ fraccionado: textura y cicatrices',
				'lead'  => 'Resurfacing fraccionado cuya indicación y cuidados se definen según fototipo, zona y profundidad del protocolo.',
				'facts' => array(
					'Presupuesto'   => 'Se documenta por escrito tras la valoración médica.',
					'Recuperación' => 'Se explica según profundidad, zona y protocolo indicado.',
				),
			),
		),
		'tratamientos' => array(
			'title'  => 'Procedimientos con indicación médica',
			'items'  => array(
				array(
					'title' => 'Endolift® Facial',
					'body'  => 'Tratamiento láser subdérmico valorado para zonas faciales seleccionadas. La indicación depende de la anatomía, los objetivos y las alternativas disponibles.',
				),
				array(
					'title' => 'Endoláser Corporal',
					'body'  => 'Tratamiento láser corporal que se considera tras explorar la zona, la calidad de piel y las expectativas realistas.',
				),
				array(
					'title' => 'Láser CO₂ Fraccionado',
					'body'  => 'Protocolo de láser fraccionado cuya indicación, parámetros y cuidados se individualizan según fototipo, zona y objetivo clínico.',
				),
				array(
					'title' => 'Armonización Facial',
					'body'  => 'Técnicas combinadas (rellenos, neuromoduladores y bioestimuladores) bajo un enfoque conservador que respeta tu identidad.',
				),
				array(
					'title' => 'EXION® BTL',
					'body'  => 'Plataforma cuya disponibilidad, aplicador e indicación se confirman después de la valoración médica.',
				),
				array(
					'title' => 'BTL EXILITE IPL',
					'body'  => 'La disponibilidad y la indicación se revisan según diagnóstico, fototipo y protocolo clínico vigente.',
				),
			),
		),
	);
}

/** Test whether a node has a class token. */

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
	$tratamientos = nvx_home_content_v2_first( $xpath, $root, 'nvx-home-tratamientos', 'section' );
	if ( $tratamientos ) {
		$title = nvx_home_content_v2_first( $xpath, $tratamientos, 'nvx-brand-title', 'h2' );
		$lead  = nvx_home_content_v2_first( $xpath, $tratamientos, 'nvx-brand-lead', 'p' );
		if ( $title ) {
			nvx_home_content_v2_set_text( $title, $catalog['tratamientos']['title'] );
		}
		if ( $lead ) {
			$lead->parentNode->removeChild( $lead );
		}
		nvx_home_content_v2_update_cards(
			$xpath,
			$tratamientos,
			'nvx-brand-card',
			'nvx-brand-card__title',
			'nvx-brand-card__body',
			$catalog['tratamientos']['items']
		);
	}
	
	// Remove the standalone clinics block since it was merged into the action banner.
	$direccion = nvx_home_content_v2_first( $xpath, $root, 'nvx-home-direccion', 'section' );
	if ( $direccion && $direccion->parentNode ) {
		$direccion->parentNode->removeChild( $direccion );
	}

	$video = $xpath->query( '//video[@id="nvx-home-hero-video"]' );
	if ( false !== $video && $video->item( 0 ) instanceof DOMElement ) {
		$vnode = $video->item( 0 );
		$vnode->setAttribute( 'preload', 'metadata' );
		$vnode->removeAttribute( 'fetchpriority' );
		$vnode->setAttribute( 'autoplay', '' );
		$vnode->setAttribute( 'muted', '' );
		$vnode->setAttribute( 'loop', '' );
		$vnode->setAttribute( 'playsinline', '' );
		if ( ! $vnode->hasAttribute( 'poster' ) || '' === trim( $vnode->getAttribute( 'poster' ) ) ) {
			$vnode->setAttribute( 'poster', 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7' );
		}
	}

	$output = '';
	foreach ( $root->childNodes as $child ) {
		$output .= $document->saveHTML( $child );
	}

	$output .= nvx_home_benefits_markup();

	libxml_clear_errors();
	libxml_use_internal_errors( $previous_errors );

	return is_string( $output ) && '' !== trim( $output ) ? $output : $content;
}
add_filter( 'the_content', 'nvx_home_content_v2_transform', 130 );

