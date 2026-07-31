#!/usr/bin/env python3
from __future__ import annotations

import base64
import json
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
INC = ROOT / "wp-content/themes/nuvanx-medical/inc"
DATA = INC / "data"


def decode_payload(value: str, prefix: str) -> str:
    payload = value[len(prefix) :]
    return base64.b64decode(payload, validate=True).decode("utf-8")


def transform_tokens(value):
    if isinstance(value, dict):
        return {key: transform_tokens(item) for key, item in value.items()}
    if isinstance(value, list):
        return [transform_tokens(item) for item in value]
    if not isinstance(value, str):
        return value

    replacements = (
        ("@nvx-i18n:", "@nvx-t:"),
        ("@nvx-home:", "@nvx-url:"),
        ("@nvx-claim:", "@nvx-claim-key:"),
    )
    for old_prefix, new_prefix in replacements:
        if value.startswith(old_prefix):
            return new_prefix + decode_payload(value, old_prefix)

    if value.startswith("@nvx-aesthetic-url:"):
        payload = decode_payload(value, "@nvx-aesthetic-url:")
        primary, alts = json.loads(payload)
        return {
            "@nvx-aesthetic-url": {
                "primary": primary,
                "alts": alts,
            }
        }

    if value.startswith("@nvx-laser-url:"):
        return {"@nvx-laser-url": decode_payload(value, "@nvx-laser-url:")}

    return value


for path in sorted(DATA.glob("*.json")):
    document = json.loads(path.read_text(encoding="utf-8"))
    document = transform_tokens(document)
    path.write_text(
        json.dumps(document, ensure_ascii=False, indent=4) + "\n",
        encoding="utf-8",
    )

home_faq_path = DATA / "home-faq-v2.json"
home_faq = json.loads(home_faq_path.read_text(encoding="utf-8"))
for entry in home_faq:
    if entry.get("id") == "equipo-medico":
        entry["a"] = (
            "Dirección médica del Dr. José Javier Rivera Tejeda (ICOMEM 282864786), "
            "con Dra. Ivon Yamileth Rivera Deras (well-aging / geriatría preventiva, "
            "FEA Hospital La Paz) y Dr. Fabio Augusto Quiñónez Bareiro (PhD, geriatría "
            "y paciente complejo), además del resto del equipo clínico."
        )
    if entry.get("id") == "recuperacion-real-laser":
        entry["a"] = (
            "Honestamente: en las primeras 24-48 horas puede haber eritema "
            "(enrojecimiento), sensación de calor y pequeñas costras microscópicas en "
            "zonas de mayor densidad de energía. El Luxury Post-Care Protocol incluye "
            "factores de crecimiento EGF, mascarilla biológica post-sesión y crioterapia, "
            "junto con seguimiento médico para revisar la evolución y atender cualquier "
            "incidencia."
        )
home_faq_path.write_text(
    json.dumps(home_faq, ensure_ascii=False, indent=4) + "\n",
    encoding="utf-8",
)

(DATA / "faq-catalog.json").unlink(missing_ok=True)

treatments_path = DATA / "treatments-catalog.json"
treatments = json.loads(treatments_path.read_text(encoding="utf-8"))
renumber = {
    "11 / Biomedicina estética": "10 / Biomedicina estética",
    "12 / Armonización facial": "11 / Armonización facial",
    "13 / Contorno nasal": "12 / Contorno nasal",
}
for section in treatments:
    for item in section.get("items", []):
        item["meta"] = renumber.get(item.get("meta"), item.get("meta"))
treatments_path.write_text(
    json.dumps(treatments, ensure_ascii=False, indent=4) + "\n",
    encoding="utf-8",
)

loader = r'''<?php
/**
 * Shared loader for large structured catalogs stored outside PHP source.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Log a catalog integrity error when WordPress debugging is enabled. */
function nvx_catalog_log_error( string $message ): void {
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( 'NUVANX catalog: ' . $message );
	}
}

/**
 * Load and cache a JSON catalog from inc/data.
 *
 * @return array<mixed>
 */
function nvx_catalog_json_load( string $filename ): array {
	static $catalogs = array();

	$safe_name = basename( $filename );
	if ( array_key_exists( $safe_name, $catalogs ) ) {
		return $catalogs[ $safe_name ];
	}

	$path    = __DIR__ . '/data/' . $safe_name;
	$decoded = array();

	if ( ! is_readable( $path ) ) {
		nvx_catalog_log_error( sprintf( 'JSON catalog is not readable: %s', $safe_name ) );
	} else {
		$raw = file_get_contents( $path );
		if ( false === $raw ) {
			nvx_catalog_log_error( sprintf( 'JSON catalog could not be read: %s', $safe_name ) );
		} else {
			$candidate = json_decode( $raw, true );
			if ( is_array( $candidate ) ) {
				$decoded = $candidate;
			} else {
				nvx_catalog_log_error(
					sprintf( 'Invalid JSON catalog %s: %s', $safe_name, json_last_error_msg() )
				);
			}
		}
	}

	$catalogs[ $safe_name ] = $decoded;
	return $decoded;
}

/** Decode a legacy Base64 token without passing invalid data to WordPress APIs. */
function nvx_catalog_decode_token_payload( string $payload, string $token_type ): ?string {
	$decoded = base64_decode( $payload, true );
	if ( false === $decoded ) {
		nvx_catalog_log_error( sprintf( 'Invalid %s token payload.', $token_type ) );
		return null;
	}

	return $decoded;
}

/**
 * Transform catalog values while preserving keys and nesting.
 *
 * @param mixed                   $value Catalog value.
 * @param callable                $transform String transformer.
 * @param array<string, callable> $object_resolvers Structured token resolvers.
 * @return mixed
 */
function nvx_catalog_transform_values(
	$value,
	callable $transform,
	array $object_resolvers = array()
) {
	if ( is_array( $value ) ) {
		if ( 1 === count( $value ) ) {
			$key = array_key_first( $value );
			if ( is_string( $key ) && isset( $object_resolvers[ $key ] ) ) {
				return $object_resolvers[ $key ]( $value[ $key ] );
			}
		}

		foreach ( $value as $key => $item ) {
			$value[ $key ] = nvx_catalog_transform_values( $item, $transform, $object_resolvers );
		}
		return $value;
	}

	return is_string( $value ) ? $transform( $value ) : $value;
}

/**
 * Resolve WordPress-aware values captured in JSON catalogs.
 *
 * Base prefixes are reserved and cannot be replaced by custom resolvers.
 * Legacy Base64 tokens remain supported for backwards compatibility only.
 *
 * @param array<mixed>            $catalog Catalog data.
 * @param callable|null           $claim_resolver Optional BTL claim resolver.
 * @param array<string, callable> $custom_resolvers Optional string-prefix resolvers.
 * @param array<string, callable> $object_resolvers Optional structured-token resolvers.
 * @return array<mixed>
 */
function nvx_catalog_resolve_tokens(
	array $catalog,
	?callable $claim_resolver = null,
	array $custom_resolvers = array(),
	array $object_resolvers = array()
): array {
	return nvx_catalog_transform_values(
		$catalog,
		static function ( string $value ) use ( $claim_resolver, $custom_resolvers ) {
			$prefixes = array(
				'@nvx-t:' => static function ( string $payload ) {
					return '' === $payload ? '' : __( $payload, 'nuvanx-medical' );
				},
				'@nvx-url:' => static function ( string $payload ) {
					return home_url( $payload );
				},
				'@nvx-i18n:' => static function ( string $payload ) {
					$decoded = nvx_catalog_decode_token_payload( $payload, 'translation' );
					return null === $decoded || '' === $decoded ? '' : __( $decoded, 'nuvanx-medical' );
				},
				'@nvx-home:' => static function ( string $payload ) {
					$decoded = nvx_catalog_decode_token_payload( $payload, 'home URL' );
					return null === $decoded ? '' : home_url( $decoded );
				},
			);

			$resolvers = $prefixes + $custom_resolvers;
			foreach ( $resolvers as $prefix => $resolver ) {
				if ( 0 === strpos( $value, $prefix ) ) {
					return $resolver( substr( $value, strlen( $prefix ) ) );
				}
			}

			if ( null !== $claim_resolver && 0 === strpos( $value, '@nvx-claim-key:' ) ) {
				return $claim_resolver( substr( $value, 15 ) );
			}

			if ( null !== $claim_resolver && 0 === strpos( $value, '@nvx-claim:' ) ) {
				$claim_key = nvx_catalog_decode_token_payload( substr( $value, 11 ), 'claim' );
				return null === $claim_key || '' === $claim_key ? '' : $claim_resolver( $claim_key );
			}

			return $value;
		},
		$object_resolvers
	);
}

/**
 * Load, resolve and cache a catalog for the current request.
 *
 * Use an explicit cache key when a file can be resolved with different resolver sets.
 *
 * @param array<string, callable> $custom_resolvers String-prefix resolvers.
 * @param array<string, callable> $object_resolvers Structured-token resolvers.
 * @return array<mixed>
 */
function nvx_catalog_json_resolved(
	string $filename,
	?callable $claim_resolver = null,
	array $custom_resolvers = array(),
	array $object_resolvers = array(),
	string $cache_key = ''
): array {
	static $resolved = array();

	$key = '' === $cache_key ? basename( $filename ) : $cache_key;
	if ( ! array_key_exists( $key, $resolved ) ) {
		$resolved[ $key ] = nvx_catalog_resolve_tokens(
			nvx_catalog_json_load( $filename ),
			$claim_resolver,
			$custom_resolvers,
			$object_resolvers
		);
	}

	return $resolved[ $key ];
}

/**
 * Retain only catalog records that contain every required key.
 *
 * @param array<mixed>  $catalog Catalog records.
 * @param array<int,string> $required_keys Required keys.
 * @return array<mixed>
 */
function nvx_catalog_filter_records(
	array $catalog,
	array $required_keys,
	string $catalog_name
): array {
	$valid = array();
	foreach ( $catalog as $key => $entry ) {
		if ( ! is_array( $entry ) || array() !== array_diff( $required_keys, array_keys( $entry ) ) ) {
			nvx_catalog_log_error(
				sprintf( 'Incomplete record %s in %s.', (string) $key, $catalog_name )
			);
			continue;
		}
		$valid[ $key ] = $entry;
	}

	return $valid;
}
'''
(INC / "nvx-catalog-json.php").write_text(loader, encoding="utf-8")

page_helpers = r'''<?php
/**
 * Shared helpers for canonical page rebuild modules.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Extract a balanced legacy div media slot without truncating nested markup. */
function nvx_page_extract_brand_hero_div( string $content ): string {
	if ( ! preg_match( '/<div class="nvx-brand-hero__media"[^>]*>/iu', $content, $opening, PREG_OFFSET_CAPTURE ) ) {
		return '';
	}

	$start = (int) $opening[0][1];
	$tail  = substr( $content, $start );
	if ( ! preg_match_all( '/<\/?div\b[^>]*>/iu', $tail, $tags, PREG_OFFSET_CAPTURE ) ) {
		return '';
	}

	$depth = 0;
	foreach ( $tags[0] as $tag ) {
		$is_closing = 0 === strpos( $tag[0], '</' );
		$depth     += $is_closing ? -1 : 1;
		if ( 0 === $depth ) {
			$length = (int) $tag[1] + strlen( $tag[0] );
			return substr( $tail, 0, $length );
		}
	}

	return '';
}

/** Preserve the existing canonical hero media slot when rebuilding a page. */
function nvx_page_extract_brand_hero_media( string $content ): string {
	if ( preg_match( '/<figure class="nvx-brand-hero__media"[\s\S]*?<\/figure>/iu', $content, $matches ) ) {
		return $matches[0];
	}

	return nvx_page_extract_brand_hero_div( $content );
}

/** Preserve an existing brand-page opening wrapper or apply a defined fallback. */
function nvx_page_render_brand_wrapper(
	string $content,
	string $inner_markup,
	string $fallback_class = ''
): string {
	if ( preg_match( '/(<div class="nvx-brand-page[^"]*"[^>]*>)/iu', $content, $matches ) ) {
		return $matches[1] . $inner_markup . '</div>';
	}

	if ( '' !== $fallback_class ) {
		return '<div class="' . esc_attr( $fallback_class ) . '">' . $inner_markup . '</div>';
	}

	return $inner_markup;
}
'''
(INC / "nvx-page-render-helpers.php").write_text(page_helpers, encoding="utf-8")


def replace_exact(path: Path, old: str, new: str) -> None:
    source = path.read_text(encoding="utf-8")
    count = source.count(old)
    if count != 1:
        raise RuntimeError(f"Expected one replacement in {path}, found {count}")
    path.write_text(source.replace(old, new), encoding="utf-8")

replace_exact(
    INC / "nvx-aesthetic-treatment-pages.php",
    """function nvx_aesthetic_treatment_catalog(): array {\n\trequire_once __DIR__ . '/nvx-catalog-json.php';\n\n\treturn nvx_catalog_resolve_tokens(\n\t\tnvx_catalog_json_load( 'aesthetic-treatment-pages.json' ),\n\t\tnull\n\t);\n}\n""",
    """function nvx_aesthetic_treatment_catalog(): array {\n\tstatic $catalog = null;\n\n\tif ( null === $catalog ) {\n\t\trequire_once __DIR__ . '/nvx-catalog-json.php';\n\t\t$catalog = nvx_catalog_filter_records(\n\t\t\tnvx_catalog_json_resolved( 'aesthetic-treatment-pages.json' ),\n\t\t\tarray( 'slug', 'h1', 'description', 'faqs', 'schema' ),\n\t\t\t'aesthetic-treatment-pages.json'\n\t\t);\n\t}\n\n\treturn $catalog;\n}\n""",
)

replace_exact(
    INC / "nvx-btl-detail-pages.php",
    """function nvx_btl_detail_registry(): array {\n\trequire_once __DIR__ . '/nvx-catalog-json.php';\n\n\treturn nvx_catalog_resolve_tokens(\n\t\tnvx_catalog_json_load( 'btl-detail-pages.json' ),\n\t\tstatic function ( string $key ) { return nvx_btl_claim( $key ); }\n\t);\n}\n""",
    """function nvx_btl_detail_registry(): array {\n\trequire_once __DIR__ . '/nvx-catalog-json.php';\n\n\treturn nvx_catalog_json_resolved(\n\t\t'btl-detail-pages.json',\n\t\tstatic function ( string $key ) { return nvx_btl_claim( $key ); },\n\t\tarray(),\n\t\tarray(),\n\t\t'btl-detail-pages'\n\t);\n}\n""",
)

faq_catalog = r'''<?php
/**
 * NUVANX · FAQ Catalog — Single source of truth
 *
 * The homepage FAQ JSON is the canonical source for visible FAQ and schema.
 * This adapter selects the stable entries used by the compact global block.
 *
 * @package nuvanx-medical
 * @version 3.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Returns the global FAQ selection as ['q' => string, 'a' => string].
 *
 * @return array<int, array{q: string, a: string}>
 */
function nvx_get_faq_catalog(): array {
	static $catalog = null;

	if ( null !== $catalog ) {
		return $catalog;
	}

	require_once __DIR__ . '/nvx-catalog-json.php';
	$source = nvx_catalog_json_resolved( 'home-faq-v2.json' );
	$ids    = array(
		'valoracion-medica',
		'precio-endolift',
		'duracion-endolift',
		'sesiones-co2',
		'tecnologia-medica',
		'exion-btl',
		'tratamiento-adecuado',
		'recuperacion',
		'diferencia-estetica',
		'clinicas-madrid',
		'equipo-medico',
	);
	$by_id = array();
	foreach ( $source as $entry ) {
		if ( is_array( $entry ) && isset( $entry['id'], $entry['q'], $entry['a'] ) ) {
			$by_id[ $entry['id'] ] = $entry;
		}
	}

	$catalog = array();
	foreach ( $ids as $id ) {
		if ( isset( $by_id[ $id ] ) ) {
			$catalog[] = array(
				'q' => $by_id[ $id ]['q'],
				'a' => $by_id[ $id ]['a'],
			);
		}
	}

	return $catalog;
}

/** Renders the FAQ section using the canonical FAQ catalog. */
function nvx_render_faq_block(): void {
	$faqs = nvx_get_faq_catalog();
	if ( empty( $faqs ) ) {
		return;
	}
	echo '<section class="nvx-faq" aria-labelledby="nvx-faq-heading">';
	echo '<h2 id="nvx-faq-heading">' . esc_html__( 'Preguntas frecuentes', 'nuvanx-medical' ) . '</h2>';
	echo '<p class="nvx-faq__intro">' . esc_html__( 'Información clara antes de decidir', 'nuvanx-medical' ) . '</p>';
	echo '<dl class="nvx-faq__list">';
	foreach ( $faqs as $item ) {
		echo '<div class="nvx-faq__item">';
		echo '<dt class="nvx-faq__question">' . esc_html( $item['q'] ) . '</dt>';
		echo '<dd class="nvx-faq__answer">' . esc_html( $item['a'] ) . '</dd>';
		echo '</div>';
	}
	echo '</dl>';
	echo '</section>';
}

/**
 * Builds the FAQPage JSON-LD schema for the site's FAQ catalog.
 *
 * @return array<string, mixed>
 */
function nvx_get_faqpage_schema(): array {
	$faqs        = nvx_get_faq_catalog();
	$main_entity = array();
	foreach ( $faqs as $item ) {
		$main_entity[] = array(
			'@type'          => 'Question',
			'name'           => $item['q'],
			'acceptedAnswer' => array(
				'@type' => 'Answer',
				'text'  => $item['a'],
			),
		);
	}
	return array(
		'@type'      => 'FAQPage',
		'@id'        => home_url( '/#faqpage' ),
		'mainEntity' => $main_entity,
	);
}

/** Inject FAQPage node into Yoast SEO graph on the front page. */
function nvx_inject_faqpage_schema_graph( array $data ): array {
	if ( is_front_page() ) {
		$data[] = nvx_get_faqpage_schema();
	}
	return $data;
}
add_filter( 'wpseo_schema_graph', 'nvx_inject_faqpage_schema_graph' );
'''
(INC / "nvx-faq-catalog.php").write_text(faq_catalog, encoding="utf-8")

replace_exact(
    INC / "nvx-faq-content-v2.php",
    """function nvx_home_faq_v2_catalog(): array {\n\trequire_once __DIR__ . '/nvx-catalog-json.php';\n\n\treturn nvx_catalog_resolve_tokens(\n\t\tnvx_catalog_json_load( 'home-faq-v2.json' )\n\t);\n}\n""",
    """function nvx_home_faq_v2_catalog(): array {\n\trequire_once __DIR__ . '/nvx-catalog-json.php';\n\n\treturn nvx_catalog_json_resolved( 'home-faq-v2.json' );\n}\n""",
)

replace_exact(
    INC / "nvx-seo-metadata.php",
    """function nvx_seo_metadata_catalog(): array {\n\trequire_once __DIR__ . '/nvx-catalog-json.php';\n\n\treturn nvx_catalog_resolve_tokens(\n\t\tnvx_catalog_json_load( 'seo-metadata.json' )\n\t);\n}\n""",
    """function nvx_seo_metadata_catalog(): array {\n\trequire_once __DIR__ . '/nvx-catalog-json.php';\n\n\treturn nvx_catalog_json_resolved( 'seo-metadata.json' );\n}\n""",
)
replace_exact(
    INC / "nvx-seo-metadata.php",
    """function nvx_seo_blog_post_metadata_catalog(): array {\n\trequire_once __DIR__ . '/nvx-catalog-json.php';\n\n\treturn nvx_catalog_resolve_tokens(\n\t\tnvx_catalog_json_load( 'seo-blog-post-metadata.json' )\n\t);\n}\n""",
    """function nvx_seo_blog_post_metadata_catalog(): array {\n\trequire_once __DIR__ . '/nvx-catalog-json.php';\n\n\treturn nvx_catalog_json_resolved( 'seo-blog-post-metadata.json' );\n}\n""",
)
replace_exact(
    INC / "nvx-treatments-catalog.php",
    """function nvx_treatments_catalog_data(): array {\n\trequire_once __DIR__ . '/nvx-catalog-json.php';\n\n\treturn nvx_catalog_resolve_tokens(\n\t\tnvx_catalog_json_load( 'treatments-catalog.json' ),\n\t\tnull\n\t);\n}\n""",
    """function nvx_treatments_catalog_data(): array {\n\trequire_once __DIR__ . '/nvx-catalog-json.php';\n\n\treturn nvx_catalog_json_resolved( 'treatments-catalog.json' );\n}\n""",
)

replace_exact(
    INC / "nvx-aesthetic-medicine-page.php",
    """\t$catalog = nvx_catalog_resolve_tokens(\n\t\tnvx_catalog_json_load( 'aesthetic-medicine-page.json' ),\n\t\tnull,\n\t\tarray(\n\t\t\t'@nvx-aesthetic-url:' => static function ( string $payload ) {\n\t\t\t\t$arguments = json_decode( (string) base64_decode( $payload, true ), true );\n\t\t\t\t$primary   = is_array( $arguments ) && isset( $arguments[0] ) ? (string) $arguments[0] : '';\n\t\t\t\t$alts      = is_array( $arguments ) && isset( $arguments[1] ) && is_array( $arguments[1] )\n\t\t\t\t\t? $arguments[1]\n\t\t\t\t\t: array();\n\t\t\t\treturn nvx_aesthetic_resolve_treatment_url( $primary, $alts );\n\t\t\t},\n\t\t)\n\t);\n""",
    """\t$catalog = nvx_catalog_json_resolved(\n\t\t'aesthetic-medicine-page.json',\n\t\tnull,\n\t\tarray(),\n\t\tarray(\n\t\t\t'@nvx-aesthetic-url' => static function ( $arguments ) {\n\t\t\t\t$primary = is_array( $arguments ) && isset( $arguments['primary'] )\n\t\t\t\t\t? (string) $arguments['primary']\n\t\t\t\t\t: '';\n\t\t\t\t$alts = is_array( $arguments ) && isset( $arguments['alts'] ) && is_array( $arguments['alts'] )\n\t\t\t\t\t? $arguments['alts']\n\t\t\t\t\t: array();\n\t\t\t\treturn nvx_aesthetic_resolve_treatment_url( $primary, $alts );\n\t\t\t},\n\t\t),\n\t\t'aesthetic-medicine-page'\n\t);\n""",
)

replace_exact(
    INC / "nvx-laser-medicine-page.php",
    """\t$catalog = nvx_catalog_resolve_tokens(\n\t\tnvx_catalog_json_load( 'laser-medicine-page.json' ),\n\t\tnull,\n\t\tarray(\n\t\t\t'@nvx-laser-url:' => static function ( string $payload ) {\n\t\t\t\treturn nvx_laser_page_url( (string) base64_decode( $payload, true ) );\n\t\t\t},\n\t\t)\n\t);\n""",
    """\t$catalog = nvx_catalog_json_resolved(\n\t\t'laser-medicine-page.json',\n\t\tnull,\n\t\tarray(),\n\t\tarray(\n\t\t\t'@nvx-laser-url' => static function ( $path ) {\n\t\t\t\treturn nvx_laser_page_url( is_string( $path ) ? $path : '' );\n\t\t\t},\n\t\t),\n\t\t'laser-medicine-page'\n\t);\n""",
)

schema_path = INC / "nvx-treatment-hub-schema.php"
schema_source = schema_path.read_text(encoding="utf-8")
schema_source = schema_source.replace(
    "\t$definitions = nvx_catalog_json_load( 'treatment-hub-schema.json' );",
    "\t// Raw JSON intentionally preserves the previous non-translated schema labels.\n"
    "\t$definitions = nvx_catalog_json_load( 'treatment-hub-schema.json' );",
    1,
)
schema_path.write_text(schema_source, encoding="utf-8")

print("Applied PR 309 review fixes.")
