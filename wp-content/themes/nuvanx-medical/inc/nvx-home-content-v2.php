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
 * Renders the benefits section using the brand\'s minimally invasive copy and SVG icons.
 */
function nvx_home_benefits_markup(): string {
	return \'
<section class="nvx-brand-section nvx-benefits-section" aria-labelledby="nvx-benefits-title">
	<div class="nvx-container" style="max-width: 1200px; margin: 0 auto; padding: 4rem 1.5rem;">
		<header class="nvx-benefits__header" style="text-align: center; margin-bottom: 3rem;">
			<p class="nvx-brand-kicker" style="font-size: 0.875rem; text-transform: uppercase; letter-spacing: 0.1em; color: var(--nvx-color-primary, #9a8a78); margin-bottom: 1rem; font-weight: 600;">Los beneficios</p>
			<h2 class="nvx-brand-title" id="nvx-benefits-title" style="font-size: 2.5rem; font-weight: 300; margin-bottom: 1.5rem; color: var(--nvx-color-text, #111);">De la tecnología NUVANX®</h2>
			<p class="nvx-brand-lead" style="font-size: 1.125rem; line-height: 1.6; color: var(--nvx-color-text-muted, #444); max-width: 800px; margin: 0 auto;">Nuestra tecnología mínimamente invasiva es la próxima generación en contorno corporal. Diseñada para optimizar tanto la comodidad como la precisión, permite a nuestros cirujanos extraer la grasa célula por célula. Revela los contornos naturales de tu cuerpo mediante una escultura corporal precisa, atacando delicadamente solo los depósitos de grasa que deseas eliminar para siempre, mientras preserva completamente las áreas circundantes.</p>
		</header>
		<div class="nvx-benefits__grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
			<div class="nvx-benefit-item" style="display: flex; align-items: center; gap: 1rem; padding: 1.5rem; background: var(--nvx-color-surface, #f9f9f9); border-radius: 8px;">
				<svg width="32" height="32" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="color: var(--nvx-color-primary, #9a8a78); flex-shrink: 0;"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
				<span class="nvx-benefit-text" style="font-weight: 500; font-size: 1rem; letter-spacing: 0.05em; color: var(--nvx-color-text, #111);">RESULTADOS DEFINITIVOS</span>
			</div>
			<div class="nvx-benefit-item" style="display: flex; align-items: center; gap: 1rem; padding: 1.5rem; background: var(--nvx-color-surface, #f9f9f9); border-radius: 8px;">
				<svg width="32" height="32" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="color: var(--nvx-color-primary, #9a8a78); flex-shrink: 0;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
				<span class="nvx-benefit-text" style="font-weight: 500; font-size: 1rem; letter-spacing: 0.05em; color: var(--nvx-color-text, #111);">24-48 HORAS DE RECUPERACIÓN</span>
			</div>
			<div class="nvx-benefit-item" style="display: flex; align-items: center; gap: 1rem; padding: 1.5rem; background: var(--nvx-color-surface, #f9f9f9); border-radius: 8px;">
				<svg width="32" height="32" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="color: var(--nvx-color-primary, #9a8a78); flex-shrink: 0;"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
				<span class="nvx-benefit-text" style="font-weight: 500; font-size: 1rem; letter-spacing: 0.05em; color: var(--nvx-color-text, #111);">DESPIERTO TODO EL TIEMPO</span>
			</div>
			<div class="nvx-benefit-item" style="display: flex; align-items: center; gap: 1rem; padding: 1.5rem; background: var(--nvx-color-surface, #f9f9f9); border-radius: 8px;">
				<svg width="32" height="32" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="color: var(--nvx-color-primary, #9a8a78); flex-shrink: 0;"><path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
				<span class="nvx-benefit-text" style="font-weight: 500; font-size: 1rem; letter-spacing: 0.05em; color: var(--nvx-color-text, #111);">SIN BISTURÍ NI PUNTOS</span>
			</div>
			<div class="nvx-benefit-item" style="display: flex; align-items: center; gap: 1rem; padding: 1.5rem; background: var(--nvx-color-surface, #f9f9f9); border-radius: 8px;">
				<svg width="32" height="32" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="color: var(--nvx-color-primary, #9a8a78); flex-shrink: 0;"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
				<span class="nvx-benefit-text" style="font-weight: 500; font-size: 1rem; letter-spacing: 0.05em; color: var(--nvx-color-text, #111);">UN SOLO PROCEDIMIENTO</span>
			</div>
			<div class="nvx-benefit-item" style="display: flex; align-items: center; gap: 1rem; padding: 1.5rem; background: var(--nvx-color-surface, #f9f9f9); border-radius: 8px;">
				<svg width="32" height="32" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="color: var(--nvx-color-primary, #9a8a78); flex-shrink: 0;"><path stroke-linecap="round" stroke-linejoin="round" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"></path></svg>
				<span class="nvx-benefit-text" style="font-weight: 500; font-size: 1rem; letter-spacing: 0.05em; color: var(--nvx-color-text, #111);">RESULTADOS NATURALES</span>
			</div>
		</div>
	</div>
</section>
	\';
}

/**
 * Canonical content catalogue for the front page.
 *
 * @return array<string,mixed>
 */
function nvx_home_content_v2_catalog(): array {
	return array(
		'values'    => array(
			'kicker' => '¿Por qué NUVANX no es una clínica de estética convencional?',
			'title'  => 'Criterio clínico donde el diagnóstico decide la tecnología.',
			'items'  => array(
				array(
					'title' => 'Prescripción, no venta:',
					'body'  => 'Antes de actuar, evaluamos tu piel, historial clínico y objetivos (15–30 min). Si un tratamiento no es médicamente viable, no se realiza. Las sesiones se prescriben, no se comercializan.',
				),
				array(
					'title' => 'Tecnología médica certificada:',
					'body'  => 'Contamos con plataformas como DEKA Motus AZ+, Endolift® (1470 nm), láser CO₂ fraccionado y EXION® BTL. Cada parámetro se calibra según tu anatomía, evitando protocolos estandarizados.',
				),
				array(
					'title' => 'Equipo hospitalario activo:',
					'body'  => 'Médicos especialistas vinculados al Hospital La Paz e investigadores PhD del CIBERFES. Esta formación en medicina del envejecimiento respalda cada una de nuestras decisiones.',
				),
			),
		),
		'action'    => array(
			'kicker'          => 'Tu primera valoración clínica',
			'title'           => '15–30 minutos para determinar la viabilidad de tu caso.',
			'body'            => 'Evaluamos tu tejido, resolvemos dudas y entregamos un presupuesto cerrado. Encuéntranos en nuestras clínicas autorizadas de Madrid: Chamberí (CS20144): Un espacio discreto a pocos minutos de la Plaza de Olavide. Salamanca–Goya (CS20073): Ubicación accesible entre las zonas de Diego de León y Goya.',
			'primary_label'   => 'Reservar valoración gratuita',
			'secondary_label' => 'Contactar por WhatsApp',
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
				'title' => 'Endolift® facial: papada, mandíbula y cuello',
				'lead'  => 'Microfibra láser bajo la piel para tensar tejido y, cuando hay indicación, reducir grasa local. No sustituye un lifting quirúrgico en todos los casos.',
				'facts' => array(
					'Inversión orientativa' => 'Desde 798,60 € (zona ojeras, IVA incl.). Papada/mandíbula en tabla de precios.',
					'Recuperación estimada' => 'Inflamación o hematomas leves habitualmente 3 a 7 días según el caso.',
				),
			),
			array(
				'title' => 'Endoláser corporal: grasa localizada y contorno',
				'lead'  => 'Protocolo láser ambulatorio para focos de grasa con flacidez leve–moderada. No es tratamiento de obesidad ni liposucción.',
				'facts' => array(
					'Zonas habituales' => 'Abdomen, flancos, muslos, rodillas, brazos y otras áreas seleccionadas.',
					'Inversión'        => 'Presupuesto por zonas tras valoración médica.',
				),
			),
			array(
				'title' => 'Láser CO₂ fraccionado: textura y cicatrices',
				'lead'  => 'Resurfacing fraccionado para cicatrices de acné, poros y fotodaño, con downtime realista según profundidad.',
				'facts' => array(
					'Inversión orientativa' => 'Desde 330 € sesión facial / 450 € corporal (IVA incl.).',
					'Recuperación'          => 'Habitualmente 4 a 7 días de eritema y descamación según protocolo.',
				),
			),
		),
		'tratamientos' => array(
			'title'  => 'Procedimientos médicos disponibles',
			'items'  => array(
				array(
					'title' => 'Endolift® Facial',
					'body'  => 'Tensado del óvalo, mandíbula y papada con microfibra láser subdérmica. Ideal para flacidez leve-moderada y grasa submentoniana.',
				),
				array(
					'title' => 'Endoláser Corporal',
					'body'  => 'Lipólisis y reafirmación profunda en zonas resistentes al ejercicio para un resultado armónico de la silueta.',
				),
				array(
					'title' => 'Láser CO₂ Fraccionado',
					'body'  => 'Renovación profunda de textura, poros, cicatrices y manchas. Parámetros adaptados a tu fototipo y tiempo de recuperación.',
				),
				array(
					'title' => 'Armonización Facial',
					'body'  => 'Técnicas combinadas (rellenos, neuromoduladores y bioestimuladores) bajo un enfoque conservador que respeta tu identidad.',
				),
				array(
					'title' => 'EXION® BTL',
					'body'  => 'Plataforma avanzada (Fractional RF, Face y Body) para optimizar la densidad dérmica y la producción natural de ácido hialurónico.',
				),
				array(
					'title' => 'BTL EXILITE IPL',
					'body'  => 'Luz pulsada intensa para fotorrejuvenecimiento. Trata de forma eficaz rojeces, capilares y manchas.',
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

