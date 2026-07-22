<?php
/**
 * Published Phase 2 roadmap directory for the Soluciones hub.
 *
 * Cards are rendered only for pages that exist and are published, preventing
 * future or draft routes from becoming public 404 links.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Return the Phase 2 directory definitions. */
function nvx_roadmap_phase2_directory(): array {
	return array(
		array( 'title' => 'Abdomen y flancos', 'slug' => 'grasa-localizada-abdomen-flancos-madrid', 'body' => 'Grasa subcutánea, laxitud, pared abdominal y continuidad con cintura y espalda baja.' ),
		array( 'title' => 'Brazos y axila', 'slug' => 'flacidez-grasa-localizada-brazos-madrid', 'body' => 'Grasa localizada, laxitud y relación del brazo con axila anterior y torso.' ),
		array( 'title' => 'Espalda y zona del sujetador', 'slug' => 'grasa-espalda-zona-sujetador-madrid', 'body' => 'Pliegues, laxitud, ajuste de la prenda y continuidad con flancos y brazos.' ),
		array( 'title' => 'Muslos y región subglútea', 'slug' => 'flacidez-muslos-internos-subgluteo-madrid', 'body' => 'Grasa localizada, laxitud, celulitis estructural y proporción del tren inferior.' ),
		array( 'title' => 'Región de las rodillas', 'slug' => 'tratamiento-rodillas-grasa-flacidez-madrid', 'body' => 'Valoración focal para diferenciar grasa, laxitud, edema y continuidad con el muslo.' ),
		array( 'title' => 'Contorno corporal masculino', 'slug' => 'contorno-corporal-masculino-madrid', 'body' => 'Planificación de abdomen, cintura, pecho, espalda o mandíbula según anatomía individual.' ),
	);
}

/** Append the published Phase 2 directory to the Soluciones hub. */
function nvx_roadmap_append_phase2_directory( string $content ): string {
	if ( is_admin() || ! is_main_query() || ! in_the_loop() || ! is_page() ) {
		return $content;
	}
	if ( 'soluciones-medicas' !== (string) get_post_field( 'post_name', get_queried_object_id() ) ) {
		return $content;
	}

	$cards = '';
	foreach ( nvx_roadmap_phase2_directory() as $entry ) {
		$page = get_page_by_path( $entry['slug'], OBJECT, 'page' );
		if ( ! $page instanceof WP_Post || 'publish' !== get_post_status( $page ) ) {
			continue;
		}
		$url = get_permalink( $page );
		if ( ! is_string( $url ) || '' === $url ) {
			continue;
		}
		$cards .= '<article class="nvx-catalog-card"><div class="nvx-catalog-card__main"><h3 class="nvx-catalog-card__title">' . esc_html( $entry['title'] ) . '</h3><p class="nvx-catalog-card__body">' . esc_html( $entry['body'] ) . '</p></div><a class="nvx-catalog-card__cta" href="' . esc_url( $url ) . '">' . esc_html__( 'Explorar solución', 'nuvanx-medical' ) . ' <span aria-hidden="true">→</span></a></article>';
	}
	if ( '' === $cards ) {
		return $content;
	}

	$section  = '<section class="nvx-brand-section" aria-labelledby="nvx-phase2-directory-title">';
	$section .= '<h2 id="nvx-phase2-directory-title">' . esc_html__( 'Explorar el contorno corporal por unidad anatómica', 'nuvanx-medical' ) . '</h2>';
	$section .= '<p>' . esc_html__( 'Estas páginas explican qué se valora en cada unidad. La tecnología y la combinación de zonas se deciden después de la exploración médica.', 'nuvanx-medical' ) . '</p><div class="nvx-catalog-grid">' . $cards . '</div></section>';

	$position = strrpos( $content, '</article>' );
	return false === $position ? $content . $section : substr_replace( $content, $section . '</article>', $position, strlen( '</article>' ) );
}
add_filter( 'the_content', 'nvx_roadmap_append_phase2_directory', 35 );
