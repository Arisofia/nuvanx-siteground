<?php
/**
 * WP-CLI script to fix legacy hygiene content in the CMS database.
 *
 * Versioned theme catalogs are audit-only here: a deployment must never rewrite
 * tracked release files after the immutable SHA has been materialized.
 *
 * Usage: wp eval-file tools/wp-cli/hygiene-content-fix.php
 */

if ( ! class_exists( 'WP_CLI' ) ) {
	die( 'This script must be run via WP-CLI.' );
}

WP_CLI::line( 'Starting content hygiene fix...' );

/**
 * Returns the literal replacement map for hygiene content.
 *
 * @return array<string, string> Literal search => replace pairs.
 */
function nvx_hygiene_str_reps() {
	return array(
		'EXILITET'                                             => 'EXILITE™',
		'Exilitet'                                             => 'EXILITE™',
		'Tu mejor versión empieza aquí.'                       => 'Reserva 15–30 min de valoración médica.',
		'Tu mejor versión empieza aquí'                        => 'Reserva 15–30 min de valoración médica',
		'Medicina estética en Goya con enfoque médico premium' => 'Medicina estética láser en Goya–Barrio de Salamanca (CS20073)',
		'enfoque médico premium'                               => 'misma dirección médica que Chamberí',
	);
}

/**
 * Returns the regex replacement map for hygiene content.
 *
 * @return array<string, string> Regex pattern => replacement pairs.
 */
function nvx_hygiene_regex_reps() {
	return array(
		'/\bvaloraci[oó]n\s+m[eé]dica\s+gratuita\b/iu' => 'valoración médica',
		'/\bvaloraci[oó]n\s+gratuita\b/iu'             => 'valoración médica',
		'/\bvaloraci[oó]n\s+gratis\b/iu'               => 'valoración médica',
		'/\bconsulta\s+(?:m[eé]dica\s+)?gratuita\b/iu' => 'consulta médica',
		'/\bconsulta\s+gratis\b/iu'                    => 'consulta médica',
		'/\bpresupuestos?\s+personalizados?\b/iu'      => 'presupuesto individualizado tras la valoración médica',
		'/\bsin\s+compromiso\b/iu'                     => 'sin obligación de continuar con un tratamiento',
		'/\bSolicitar\.(?=\s|<|$)/u'                   => 'Solicitar valoración médica',
	);
}

/**
 * Applies configured literal and regular-expression replacements to a non-empty string.
 *
 * @param mixed $content The value to process.
 * @return mixed The updated string, or the original value for other values and empty strings.
 */
function nvx_apply_hygiene_replacements( $content ) {
	if ( ! is_string( $content ) || '' === $content ) {
		return $content;
	}
	$str_reps   = nvx_hygiene_str_reps();
	$regex_reps = nvx_hygiene_regex_reps();
	$content    = str_replace( array_keys( $str_reps ), array_values( $str_reps ), $content );
	foreach ( $regex_reps as $pattern => $replacement ) {
		$content = preg_replace( $pattern, $replacement, $content ) ?? $content;
	}
	return $content;
}

// Disable KSES so wp_update_post() does not strip legitimate but "disallowed"
// markup (embedded clinic maps, iframes, etc.) from saved content. Without this
// the sanitizer would permanently delete that HTML with no backup.
kses_remove_filters();

try {
	// 1. Process CMS database content only.
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
			wp_update_post( wp_slash( (array) $post ) );
			$db_updated_count++;
		}
	}
	WP_CLI::success( "Updated {$db_updated_count} posts in database." );

	// 2. Fix H1 in legacy legal CMS documents.
	WP_CLI::line( 'Fixing H1 in legal documents...' );
	$legal_posts = get_posts(
		array(
			'post_name__in' => array( 'politica-privacidad', 'aviso-legal' ),
			'post_type'     => 'page',
			'post_status'   => 'any',
			'numberposts'   => -1,
		)
	);
	foreach ( $legal_posts as $post ) {
		if ( false === stripos( $post->post_content, '<h1' ) ) {
			$new_content = preg_replace( '/<h2\b/iu', '<h1', $post->post_content, 1 );
			$new_content = preg_replace( '/<\/h2>/iu', '</h1>', $new_content, 1 );
			if ( $new_content !== $post->post_content ) {
				$post->post_content = $new_content;
				wp_update_post( wp_slash( (array) $post ) );
				WP_CLI::success( "Fixed H1 for {$post->post_name}" );
			}
		}
	}

	// 3. Repair staging-only aesthetic seed records deterministically. Historical
	// staging databases can contain seeded pages from catalogue records that no
	// longer satisfy the canonical catalogue contract. Those orphan seed pages
	// must not remain published: they render only an inert marker plus shell title
	// and therefore poison Block C. Valid seed pages keep their catalogue-driven
	// content; authored pages are never touched. Never apply this repair to production.
	if ( function_exists( 'nvx_environment_is_staging2' ) && nvx_environment_is_staging2() && function_exists( 'nvx_aesthetic_treatment_catalog' ) ) {
		WP_CLI::line( 'Repairing staging aesthetic seed metadata...' );
		$published_pages = get_posts(
			array(
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'ID',
				'order'          => 'ASC',
			)
		);
		$catalog = nvx_aesthetic_treatment_catalog();

		// Retire only machine-seeded pages whose marker no longer resolves to a
		// valid canonical catalogue record. This catches stale staging records such
		// as a treatment draft left published after its source record became invalid,
		// without hiding real authored content or weakening the acceptance gate.
		foreach ( $published_pages as $candidate ) {
			$content = trim( (string) $candidate->post_content );
			if ( false === strpos( $content, 'nvx-aesthetic-treatment-source' ) ) {
				continue;
			}

			$seed_key = get_post_meta( $candidate->ID, '_nvx_aesthetic_treatment_key', true );
			if ( ( ! is_string( $seed_key ) || '' === $seed_key ) && preg_match( '/data-nvx-treatment=["\']([a-z0-9_-]+)["\']/i', $content, $matches ) ) {
				$seed_key = (string) $matches[1];
			}

			if ( is_string( $seed_key ) && '' !== $seed_key && isset( $catalog[ $seed_key ] ) ) {
				continue;
			}

			$result = wp_update_post(
				array(
					'ID'          => $candidate->ID,
					'post_status' => 'draft',
				),
				true
			);
			if ( is_wp_error( $result ) ) {
				WP_CLI::error( "Could not retire orphan aesthetic seed page {$candidate->ID}: {$result->get_error_message()}" );
			}
			WP_CLI::line( "AESTHETIC_SEED_RETIRED id={$candidate->ID} key=" . ( is_string( $seed_key ) ? $seed_key : '' ) . " slug={$candidate->post_name}" );
		}

		foreach ( $catalog as $key => $entry ) {
			$matched = null;
			foreach ( $published_pages as $candidate ) {
				$existing_key = get_post_meta( $candidate->ID, '_nvx_aesthetic_treatment_key', true );
				if ( $existing_key === $key || $candidate->post_name === $entry['slug'] || $candidate->post_title === $entry['h1'] ) {
					$matched = $candidate;
					break;
				}
			}

			if ( ! $matched instanceof WP_Post ) {
				continue;
			}

			$content      = trim( (string) $matched->post_content );
			$is_seed_body = '' === $content || false !== strpos( $content, 'nvx-aesthetic-treatment-source' );
			if ( ! $is_seed_body ) {
				continue;
			}

			$marker = '<div class="nvx-aesthetic-treatment-source" data-nvx-treatment="' . esc_attr( $key ) . '"></div>';
			if ( $content !== $marker ) {
				wp_update_post(
					wp_slash(
						array(
							'ID'           => $matched->ID,
							'post_content' => $marker,
							'post_excerpt' => (string) $entry['description'],
						)
					)
				);
			}
			update_post_meta( $matched->ID, '_nvx_aesthetic_treatment_key', $key );
			update_post_meta( $matched->ID, '_nvx_medical_review_status', 'pending' );
			WP_CLI::line( "AESTHETIC_SEED_READY id={$matched->ID} key={$key} slug={$matched->post_name}" );
		}
	}
} finally {
	// Restore KSES filters removed before DB writes.
	kses_init_filters();
}

// 4. Audit versioned JSON catalogs without modifying them. Any hygiene drift in
// tracked release data must be fixed in GitHub so the deployed tree remains the
// exact content represented by .nvx-deploy-sha.
WP_CLI::line( 'Auditing versioned JSON catalogs (read-only)...' );
$theme_dir  = get_template_directory();
$json_dir   = $theme_dir . '/inc/data';
$json_files = glob( $json_dir . '/*.json' );
if ( false === $json_files ) {
	$json_files = array();
}

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

$catalog_drift = array();
foreach ( $json_files as $file ) {
	$content = file_get_contents( $file );
	if ( false === $content ) {
		$catalog_drift[] = basename( $file ) . ': unreadable';
		continue;
	}

	$data = json_decode( $content, false );
	if ( json_last_error() !== JSON_ERROR_NONE ) {
		$catalog_drift[] = basename( $file ) . ': invalid JSON';
		continue;
	}

	$new_data    = nvx_recursive_hygiene( $data );
	$old_payload = wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	$new_payload = wp_json_encode( $new_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	if ( false === $old_payload || false === $new_payload ) {
		$catalog_drift[] = basename( $file ) . ': JSON encoding failed';
		continue;
	}
	if ( $new_payload !== $old_payload ) {
		$catalog_drift[] = basename( $file ) . ': hygiene text drift';
	}
}

if ( ! empty( $catalog_drift ) ) {
	foreach ( $catalog_drift as $issue ) {
		WP_CLI::warning( $issue );
	}
	WP_CLI::error( 'Versioned catalog hygiene drift detected. Fix the tracked files in GitHub; server-side catalog mutation is forbidden.' );
}

WP_CLI::success( 'Versioned JSON catalogs are clean and unchanged.' );
WP_CLI::success( 'Hygiene content fix completed.' );
