<?php
/**
 * Plugin Name: NUVANX Campaign Redirects
 * Description: Maneja redirecciones 301 para slugs legacy y corrige el error {ignore} de Google Ads.
 * Version: 1.0.2
 * Author: NUVANX
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'nvx_campaign_redirects', 1 );

function nvx_campaign_redirects() {
	if ( ! isset( $_SERVER['REQUEST_URI'] ) ) {
		return;
	}

	$request_uri = $_SERVER['REQUEST_URI'];
	$decoded_uri = urldecode( $request_uri );
	$path = parse_url($decoded_uri, PHP_URL_PATH);
	
	// 1. Corregir el error {ignore} de Google Ads
	// Nginx o PHP puede estar eliminando los {} de la URL antes de procesar, así que buscamos "ignore"
	if ( preg_match( '/\/?(?:%7B|{)?ignore(?:%7D|})?$/i', rtrim($path, '/') ) ) {
		$clean_path = preg_replace( '/\/?(?:%7B|{)?ignore(?:%7D|})?$/i', '', rtrim($path, '/') );
		
		$query = parse_url($request_uri, PHP_URL_QUERY);
		$redirect_url = home_url( ltrim($clean_path, '/') );
		// Añadir barra final si corresponde (WordPress style)
		$redirect_url = rtrim($redirect_url, '/') . '/';
		
		if ( ! empty( $query ) ) {
			$redirect_url .= '?' . $query;
		}
		
		wp_redirect( $redirect_url, 301 );
		exit;
	}

	// 2. Redirigir slugs legacy a nuevas URLs
	$legacy_redirects = array(
		'/politica-de-privacidad/' => '/politica-privacidad/',
		'/thermage-flx-radiofrecuencia-monopolar-madrid/' => '/contacto/',
		'/endolift-facial-el-lifting-sin-cirugia-que-revoluciona-la-medicina-estetica/' => '/endolift-facial-papada-mandibula/',
		'/endolaser-corporal-la-revolucion-cientifica-para-eliminar-grasa-y-reafirmar-la-piel/' => '/endolaser-corporal-grasa-localizada/'
	);

	$path_trailing = rtrim($path, '/') . '/';

	if ( array_key_exists( $path_trailing, $legacy_redirects ) ) {
		$query = parse_url($request_uri, PHP_URL_QUERY);
		$redirect_url = home_url( ltrim($legacy_redirects[$path_trailing], '/') );
		if ( ! empty( $query ) ) {
			$redirect_url .= '?' . $query;
		}
		
		wp_redirect( $redirect_url, 301 );
		exit;
	}
}
