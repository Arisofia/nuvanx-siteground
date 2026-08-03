<?php
/**
 * WP-CLI script to permanently fix legacy hygiene content in DB and JSON catalogs.
 *
 * Usage: wp eval-file tools/wp-cli/hygiene-content-fix.php
 */

if ( ! class_exists( 'WP_CLI' ) ) {
	die( 'This script must be run via WP-CLI.' );
}

WP_CLI::line( 'Starting content hygiene fix...' );

$str_reps = array(
	'EXILITET'                                             => 'EXILITE™',
	'Exilitet'                                             => 'EXILITE™',
	'Tu mejor versión empieza aquí.'                       => 'Reserva 15–30 min de valoración médica.',
	'Tu mejor versión empieza aquí'                        => 'Reserva 15–30 min de valoración médica',
	'enfoque médico premium'                               => 'misma dirección médica que Chamberí',
	'Medicina estética en Goya con enfoque médico premium' => 'Medicina estética láser en Goya–Barrio de Salamanca (CS20073)',
);

$regex_reps = array(
	'/\bvaloraci[oó]n\s+m[eé]dica\s+gratuita\b/iu' => 'valoración médica',
	'/\bvaloraci[oó]n\s+gratuita\b/iu'             => 'valoración médica',
	'/\bvaloraci[oó]n\s+gratis\b/iu'               => 'valoración médica',
	'/\bconsulta\s+(?:m[eé]dica\s+)?gratuita\b/iu' => 'consulta médica',
	'/\bconsulta\s+gratis\b/iu'                    => 'consulta médica',
	'/\bpresupuestos?\s+personalizados?\b/iu'      => 'presupuesto individualizado tras la valoración médica',
	'/\bsin\s+compromiso\b/iu'                     => 'sin obligación de continuar con un tratamiento',
	'/\bSolicitar\.(?=\s|<|$)/u'                   => 'Solicitar valoración médica',
);

/**
 * Applies configured literal and regular-expression replacements to a non-empty string.
 *
 * @param mixed $content The value to process.
 * @return mixed The updated string, or the original value for other values and empty strings.
 */
function nvx_apply_hygiene_replacements( $content ) {
	global $str_reps, $regex_reps;
	if ( ! is_string( $content ) || '' === $content ) {
		return $content;
	}
	$content = str_replace( array_keys( $str_reps ), array_values( $str_reps ), $content );
	foreach ( $regex_reps as $pattern => $replacement ) {
		$content = preg_replace( $pattern, $replacement, $content ) ?? $content;
	}
	return $content;
}

// 1. Process Database (wp_posts)
WP_CLI::line( 'Processing wp_posts...' );
global $wpdb;
$post_ids = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type IN ('post', 'page')" );
$db_updated_count = 0;

foreach ( $post_ids as $id ) {
	$post = get_post( $id );
	if ( ! $post ) {
		continue;
	}

	$needs_update = false;
	
	$new_content = nvx_apply_hygiene_replacements( $post->post_content );
	if ( $new_content !== $post->post_content ) {
		$post->post_content = $new_content;
		$needs_update = true;
	}

	$new_title = nvx_apply_hygiene_replacements( $post->post_title );
	if ( $new_title !== $post->post_title ) {
		$post->post_title = $new_title;
		$needs_update = true;
	}

	$new_excerpt = nvx_apply_hygiene_replacements( $post->post_excerpt );
	if ( $new_excerpt !== $post->post_excerpt ) {
		$post->post_excerpt = $new_excerpt;
		$needs_update = true;
	}

	if ( $needs_update ) {
		wp_update_post( $post );
		$db_updated_count++;
	}
}
WP_CLI::success( "Updated {$db_updated_count} posts in database." );

// 2. Fix H1 in Legal Documents
WP_CLI::line( 'Fixing H1 in legal documents...' );
$legal_posts = get_posts( array(
	'post_name__in' => array( 'politica-privacidad', 'aviso-legal' ),
	'post_type'     => 'page',
	'post_status'   => 'any',
	'numberposts'   => -1,
) );
foreach ( $legal_posts as $post ) {
	if ( false === stripos( $post->post_content, '<h1' ) ) {
		$new_content = preg_replace( '/<h2\b/iu', '<h1', $post->post_content, 1 );
		$new_content = preg_replace( '/<\/h2>/iu', '</h1>', $new_content, 1 );
		if ( $new_content !== $post->post_content ) {
			$post->post_content = $new_content;
			wp_update_post( $post );
			WP_CLI::success( "Fixed H1 for {$post->post_name}" );
		}
	}
}

// 3. Process JSON Catalogs
WP_CLI::line( 'Processing JSON catalogs...' );
$theme_dir = get_template_directory();
$json_dir  = $theme_dir . '/inc/data';
$json_files = glob( $json_dir . '/*.json' );
$json_updated_count = 0;

/**
 * Applies hygiene content replacements recursively to strings in nested data.
 *
 * @param mixed $data The value to process.
 * @return mixed The processed data with non-string values preserved.
 */
function nvx_recursive_hygiene( $data ) {
	if ( is_string( $data ) ) {
		return nvx_apply_hygiene_replacements( $data );
	}
	if ( is_array( $data ) ) {
		$new_array = array();
		foreach ( $data as $key => $value ) {
			$new_array[ $key ] = nvx_recursive_hygiene( $value );
		}
		return $new_array;
	}
	if ( is_object( $data ) ) {
		$new_obj = new stdClass();
		foreach ( get_object_vars( $data ) as $key => $value ) {
			$new_obj->$key = nvx_recursive_hygiene( $value );
		}
		return $new_obj;
	}
	return $data;
}

foreach ( $json_files as $file ) {
	$content = file_get_contents( $file );
	$data = json_decode( $content, true );
	if ( json_last_error() !== JSON_ERROR_NONE ) {
		WP_CLI::warning( "Invalid JSON in {$file}" );
		continue;
	}

	$new_data = nvx_recursive_hygiene( $data );
	$new_content = wp_json_encode( $new_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	
	if ( $new_content !== $content ) {
		file_put_contents( $file, $new_content );
		$json_updated_count++;
	}
}
WP_CLI::success( "Updated {$json_updated_count} JSON files." );

WP_CLI::success( 'Hygiene content fix completed.' );
