#!/usr/bin/env python3
from pathlib import Path


def read(path: str) -> str:
    return Path(path).read_text(encoding='utf-8')


def write(path: str, text: str) -> None:
    Path(path).write_text(text, encoding='utf-8')


def replace_region(text: str, start: str, end: str, replacement: str) -> str:
    start_index = text.index(start)
    end_index = text.index(end, start_index)
    return text[:start_index] + replacement + text[end_index:]


structured_path = 'wp-content/themes/nuvanx-medical/inc/nvx-structured-data.php'
structured = read(structured_path)
shared_helper = '''/**
 * Link WebPage.mainEntity to an entity @id when the page URL matches.
 *
 * This is the single graph-linking implementation shared by both schema passes.
 *
 * @param array  $graph     Schema graph.
 * @param string $pageUrl   Canonical page URL.
 * @param string $entityId  Entity node @id.
 * @return array Updated schema graph.
 */
function nvxSchemaLinkWebpageMainEntity( array $graph, string $pageUrl, string $entityId ): array {
    if ( '' === $pageUrl || '' === $entityId ) {
        return $graph;
    }

    $target = trailingslashit( $pageUrl );
    foreach ( $graph as $index => $piece ) {
        $types = isset( $piece['@type'] ) ? (array) $piece['@type'] : array();
        $url   = isset( $piece['url'] ) ? trailingslashit( (string) $piece['url'] ) : '';
        if ( in_array( 'WebPage', $types, true ) && $url === $target ) {
            $graph[ $index ]['mainEntity'] = array( '@id' => $entityId );
            break;
        }
    }

    return $graph;
}

'''
structured = replace_region(
    structured,
    '/**\n * Link WebPage.mainEntity to a treatment @id when the page URL matches.',
    '/**\n * Attaches treatment and FAQ nodes to schema graph when applicable.',
    shared_helper,
)
old_call = "            nvx_schema_link_webpage_main_entity( $graph, (string) $treatment['url'], (string) $treatment['@id'] );"
new_call = "            $graph = nvxSchemaLinkWebpageMainEntity( $graph, (string) $treatment['url'], (string) $treatment['@id'] );"
if old_call not in structured:
    raise RuntimeError('structured-data mainEntity call marker missing')
structured = structured.replace(old_call, new_call, 1)
write(structured_path, structured)

seo_path = 'wp-content/themes/nuvanx-medical/inc/nvx-seo-production-readiness.php'
seo = read(seo_path)
seo = replace_region(
    seo,
    '/**\n * Links the matching WebPage node to the promoted main entity.',
    '/**\n * Promote Organization.logo ImageObject to a top-level graph node.',
    '',
)
logo_helpers = '''/**
 * Return a materializable Organization logo node, when present.
 *
 * @param array $piece Schema graph node.
 * @return array|null
 */
function nvxSeoSchemaLogoNodeFromOrganization( array $piece ): ?array {
    $types = $piece['@type'] ?? array();
    if ( ! nvx_seo_schema_has_type( $types, 'Organization' ) || nvx_seo_schema_has_type( $types, 'WebSite' ) ) {
        return null;
    }

    $logo = $piece['logo'] ?? null;
    if ( ! is_array( $logo ) ) {
        return null;
    }

    $logoId  = isset( $logo['@id'] ) ? (string) $logo['@id'] : '';
    $hasBody = isset( $logo['url'] ) || isset( $logo['contentUrl'] ) || isset( $logo['width'] ) || isset( $logo['height'] );
    if ( '' === $logoId || ! $hasBody ) {
        return null;
    }

    if ( empty( $logo['@type'] ) ) {
        $logo['@type'] = 'ImageObject';
    }

    return $logo;
}

/**
 * Promote Organization.logo ImageObject to a top-level graph node.
 *
 * @param array $graph Schema graph.
 * @return array
 */
function nvxSeoSchemaMaterializeLogoNode( array $graph ): array {
    foreach ( $graph as $index => $piece ) {
        $logoNode = nvxSeoSchemaLogoNodeFromOrganization( $piece );
        if ( null === $logoNode ) {
            continue;
        }

        $logoId = (string) $logoNode['@id'];
        $graph = nvx_seo_schema_upsert_node( $graph, $logoNode );
        $graph[ $index ]['logo']  = array( '@id' => $logoId );
        $graph[ $index ]['image'] = array( '@id' => $logoId );
    }

    return $graph;
}

'''
seo = replace_region(
    seo,
    '/**\n * Promote Organization.logo ImageObject to a top-level graph node.',
    '/**\n * Link WebPage.mainEntity to the first MedicalProcedure/Service for this URL.',
    logo_helpers,
)
ensure_helper = '''/**
 * Link WebPage.mainEntity to the first MedicalProcedure/Service for this URL.
 *
 * @param array  $graph      Schema graph.
 * @param string $currentUrl Canonical page URL.
 * @return array
 */
function nvxSeoSchemaEnsureWebpageMainEntity( array $graph, string $currentUrl ): array {
    foreach ( $graph as $piece ) {
        $types      = $piece['@type'] ?? array();
        $pieceUrl   = isset( $piece['url'] ) ? trailingslashit( (string) $piece['url'] ) : '';
        $isEntity   = nvx_seo_schema_has_type( $types, 'MedicalProcedure' ) || nvx_seo_schema_has_type( $types, 'Service' );
        $matchesUrl = '' !== $pieceUrl && $pieceUrl === trailingslashit( $currentUrl );
        if ( $isEntity && $matchesUrl && ! empty( $piece['@id'] ) ) {
            return function_exists( 'nvxSchemaLinkWebpageMainEntity' )
                ? nvxSchemaLinkWebpageMainEntity( $graph, $currentUrl, (string) $piece['@id'] )
                : $graph;
        }
    }

    return $graph;
}

'''
seo = replace_region(
    seo,
    '/**\n * Link WebPage.mainEntity to the first MedicalProcedure/Service for this URL.',
    '/**\n * Ensures production readiness by consolidating MedicalOrganization, MedicalProcedure,',
    ensure_helper,
)
seo = seo.replace('nvx_seo_schema_materialize_logo_node( $graph )', 'nvxSeoSchemaMaterializeLogoNode( $graph )')
seo = seo.replace('nvx_seo_schema_ensure_webpage_main_entity( $graph, $current_url )', 'nvxSeoSchemaEnsureWebpageMainEntity( $graph, $current_url )')
old_link = "    $graph = _nvx_seo_schema_link_main_entity( $graph, $current_url, $main_entity_id );"
new_link = "    if ( '' !== $main_entity_id && function_exists( 'nvxSchemaLinkWebpageMainEntity' ) ) {\n        $graph = nvxSchemaLinkWebpageMainEntity( $graph, $current_url, $main_entity_id );\n    }"
if old_link not in seo:
    raise RuntimeError('SEO promoted mainEntity call marker missing')
seo = seo.replace(old_link, new_link, 1)
write(seo_path, seo)

entity_contract_path = 'scripts/theme-hygiene/test-entity-graph-contract.mjs'
contract = read(entity_contract_path)
contract = contract.replace('function nvx_schema_link_webpage_main_entity(', 'function nvxSchemaLinkWebpageMainEntity(')
contract = contract.replace('function nvx_seo_schema_materialize_logo_node(', 'function nvxSeoSchemaMaterializeLogoNode(')
contract = contract.replace('function nvx_seo_schema_ensure_webpage_main_entity(', 'function nvxSeoSchemaEnsureWebpageMainEntity(')
contract = contract.replace(
    '// Avoid regex-based whole-file scans: inspect normalized lines with fixed-string checks.',
    "if (seo.includes('_nvx_seo_schema_link_main_entity')) {\n  failures.push('SEO readiness must use the shared mainEntity helper');\n}\n\n// Avoid regex-based whole-file scans: inspect normalized lines with fixed-string checks.",
    1,
)
write(entity_contract_path, contract)

runtime_contract_path = 'scripts/theme-hygiene/test-runtime-bootstrap-contract.mjs'
runtime = read(runtime_contract_path)
runtime = runtime.replace('nvx_seo_schema_materialize_logo_node', 'nvxSeoSchemaMaterializeLogoNode')
runtime = runtime.replace('nvx_seo_schema_ensure_webpage_main_entity', 'nvxSeoSchemaEnsureWebpageMainEntity')
runtime = runtime.replace('nvx_schema_link_webpage_main_entity', 'nvxSchemaLinkWebpageMainEntity')
write(runtime_contract_path, runtime)

forbidden = (
    'nvx_schema_link_webpage_main_entity',
    'nvx_seo_schema_materialize_logo_node',
    'nvx_seo_schema_ensure_webpage_main_entity',
)
runtime_sources = read(structured_path) + read(seo_path)
leftovers = [name for name in forbidden if name in runtime_sources]
if leftovers:
    raise RuntimeError(f'legacy runtime helpers remain: {leftovers}')
if '_nvx_seo_schema_link_main_entity' in read(seo_path):
    raise RuntimeError('duplicated SEO mainEntity helper remains')
