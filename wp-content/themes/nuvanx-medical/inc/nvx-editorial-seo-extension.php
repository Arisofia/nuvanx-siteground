<?php
/**
 * SEO metadata for governed editorial routes.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides SEO metadata for governed editorial routes outside the principal SEO catalog.
 *
 * @return array<string, array{title: string, description: string}> Metadata keyed by route path.
 */
function nvx_editorial_seo_catalog(): array {
	return array(
		'/soluciones-medicas/' => nvx_meta_item( 'Soluciones médicas para rostro y cuerpo | NUVANX Madrid', 'Soluciones de medicina estética por anatomía y diagnóstico para rostro, piel, contorno corporal y cambios posgestacionales en NUVANX Madrid.' ),
		'/remodelacion-corporal-laser-madrid/' => nvx_meta_item( 'Remodelación corporal láser en Madrid | NUVANX Contour Architecture', 'NUVANX Contour Architecture™: remodelación corporal láser por unidades anatómicas para grasa localizada, laxitud y continuidad tras valoración médica.' ),
		'/tratamiento-postparto-abdomen-contorno-corporal-madrid/' => nvx_meta_item( 'Tratamiento postparto abdomen Madrid | NUVANX', 'Valoración médica del abdomen posgestacional para diferenciar grasa localizada, laxitud, cicatriz y diástasis antes de indicar tratamiento o derivación.' ),
		'/papada-definicion-mandibular-madrid/' => nvx_meta_item( 'Papada y definición mandibular Madrid | NUVANX', 'Valoración médica de papada, cuello y mandíbula en Madrid para diferenciar grasa, laxitud y soporte antes de indicar Endolift® u otra opción.' ),
		'/calidad-piel-firmeza-luminosidad-madrid/' => nvx_meta_item( 'Calidad y firmeza de la piel Madrid | NUVANX', 'Tratamiento médico para calidad, firmeza y luminosidad de la piel en Madrid con tecnología seleccionada tras diagnóstico, fototipo y valoración.' ),
		'/cicatrices-acne-poros-textura-madrid/' => nvx_meta_item( 'Cicatrices de acné, poros y textura Madrid | NUVANX', 'Tratamiento de cicatrices de acné, poros y textura en Madrid con CO₂ o Fractional RF según morfología, fototipo y valoración médica.' ),
		'/manchas-rojeces-fotorejuvenecimiento-ipl-madrid/' => nvx_meta_item( 'Manchas, rojeces y fotodaño Madrid | NUVANX', 'Tratamiento de manchas, rojeces y fotodaño en Madrid con IPL seleccionada según diagnóstico, fototipo y valoración médica.' ),
		'/grasa-localizada-abdomen-flancos-madrid/' => nvx_meta_item( 'Grasa localizada abdomen y flancos Madrid | NUVANX', 'Valoración de grasa localizada, laxitud y pared abdominal en abdomen y flancos en Madrid dentro de NUVANX Contour Architecture™.' ),
		'/flacidez-grasa-localizada-brazos-madrid/' => nvx_meta_item( 'Flacidez y grasa localizada brazos Madrid | NUVANX', 'Tratamiento de flacidez y grasa localizada en brazos en Madrid con valoración de brazo, axila y torso antes de seleccionar tecnología.' ),
		'/grasa-espalda-zona-sujetador-madrid/' => nvx_meta_item( 'Grasa espalda y zona del sujetador Madrid | NUVANX', 'Valoración de grasa y laxitud en espalda y zona del sujetador en Madrid, considerando continuidad con brazos y flancos.' ),
		'/flacidez-muslos-internos-subgluteo-madrid/' => nvx_meta_item( 'Flacidez muslos internos y subglúteo Madrid | NUVANX', 'Valoración de flacidez, grasa y continuidad en muslos internos y región subglútea en Madrid dentro de Contour Architecture™.' ),
		'/tratamiento-rodillas-grasa-flacidez-madrid/' => nvx_meta_item( 'Grasa localizada y flacidez rodillas Madrid | NUVANX', 'Valoración de grasa localizada y flacidez en rodillas en Madrid, diferenciando tejido estético de causas articulares, vasculares o edema.' ),
		'/contorno-corporal-masculino-madrid/' => nvx_meta_item( 'Contorno corporal masculino Madrid | NUVANX', 'Contorno corporal masculino en Madrid para abdomen, cintura, espalda o perfil, con diagnóstico y tecnología seleccionada tras valoración.' ),
	);
}

/**
 * Resolves editorial SEO metadata for the current request path.
 *
 * @return array|null The matching SEO metadata, or null for 404 and unmatched requests.
 */
function nvx_editorial_seo_current(): ?array {
	if ( is_404() ) {
		return null;
	}
	$path    = function_exists( 'nvx_seo_current_path' ) ? nvx_seo_current_path() : '/' . trim( (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH ), '/' ) . '/';
	$catalog = nvx_editorial_seo_catalog();
	return $catalog[ $path ] ?? null;
}
