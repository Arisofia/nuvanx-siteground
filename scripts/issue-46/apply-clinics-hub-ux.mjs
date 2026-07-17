import fs from 'fs';

const functionsPath = 'wp-content/themes/nuvanx-medical/functions.php';
const componentsPath = 'wp-content/themes/nuvanx-medical/assets/css/nvx-components.css';
const incPath = 'wp-content/themes/nuvanx-medical/inc/nvx-clinics-hub.php';

const php = `<?php
/**
 * Clinics hub navigation and unambiguous external map actions.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
\texit;
}

function nvx_is_clinics_hub(): bool {
\tif ( ! is_page() ) {
\t\treturn false;
\t}

\treturn 'clinicas-de-medicina-estetica-nuvanx' === (string) get_post_field( 'post_name', get_queried_object_id() );
}

function nvx_clinics_map_url( string $clinic ): string {
\t$query = 'goya' === $clinic
\t\t? 'NUVANX Medicina Estética Láser Salamanca Goya Madrid'
\t\t: 'NUVANX Medicina Estética Láser Chamberí Madrid';

\treturn 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( $query );
}

function nvx_clinics_nearest_block( DOMNode $node ): ?DOMElement {
\t$current = $node;
\twhile ( $current instanceof DOMNode && $current->parentNode ) {
\t\tif ( $current instanceof DOMElement && in_array( strtolower( $current->tagName ), array( 'section', 'article' ), true ) ) {
\t\t\treturn $current;
\t\t}
\t\t$current = $current->parentNode;
\t}
\treturn null;
}

function nvx_clinics_set_link_attributes( DOMElement $link, string $clinic ): void {
\t$name = 'goya' === $clinic ? 'NUVANX Salamanca–Goya' : 'NUVANX Chamberí';
\t$link->setAttribute( 'href', nvx_clinics_map_url( $clinic ) );
\t$link->setAttribute( 'target', '_blank' );
\t$link->setAttribute( 'rel', 'noopener noreferrer' );
\t$link->setAttribute( 'aria-label', 'Abrir ' . $name . ' en Google Maps' );
\t$link->nodeValue = 'Abrir en Google Maps';
\n\t$class = trim( $link->getAttribute( 'class' ) . ' nvx-button nvx-button--primary nvx-clinic-map-cta' );
\t$link->setAttribute( 'class', implode( ' ', array_unique( preg_split( '/\\s+/', $class ) ?: array() ) ) );
}

function nvx_clinics_hub_enhance( string $content ): string {
\tif ( is_admin() || ! nvx_is_clinics_hub() || '' === trim( $content ) ) {
\t\treturn $content;
\t}

\t$previous = libxml_use_internal_errors( true );
\t$dom      = new DOMDocument( '1.0', 'UTF-8' );
\t$wrapper  = '<div id="nvx-clinics-document">' . $content . '</div>';
\t$loaded   = $dom->loadHTML( '<?xml encoding="utf-8" ?>' . $wrapper, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
\tlibxml_clear_errors();
\tlibxml_use_internal_errors( $previous );

\tif ( ! $loaded ) {
\t\treturn $content;
\t}

\t$xpath   = new DOMXPath( $dom );
\t$clinics = array(
\t\t'chamberi' => array( 'id' => 'clinica-chamberi', 'label' => 'Chamberí', 'match' => '/chamber[ií]/iu' ),
\t\t'goya'     => array( 'id' => 'clinica-goya', 'label' => 'Salamanca–Goya', 'match' => '/(?:salamanca|goya)/iu' ),
\t);
\t$blocks  = array();

\tforeach ( $xpath->query( '//h2|//h3|//h4' ) ?: array() as $heading ) {
\t\t$text = trim( preg_replace( '/\\s+/u', ' ', $heading->textContent ) ?? $heading->textContent );
\t\tforeach ( $clinics as $key => $config ) {
\t\t\tif ( isset( $blocks[ $key ] ) || ! preg_match( $config['match'], $text ) ) {
\t\t\t\tcontinue;
\t\t\t}
\t\t\t$block = nvx_clinics_nearest_block( $heading );
\t\t\tif ( $block ) {
\t\t\t\t$block->setAttribute( 'id', $config['id'] );
\t\t\t\t$block->setAttribute( 'class', trim( $block->getAttribute( 'class' ) . ' nvx-clinic-location' ) );
\t\t\t\t$blocks[ $key ] = $block;
\t\t\t}
\t\t}
\t}

\tforeach ( $blocks as $key => $block ) {
\t\t$links = $xpath->query( './/a', $block );
\t\t$map_action_seen = false;
\t\tforeach ( $links ?: array() as $link ) {
\t\t\tif ( ! $link instanceof DOMElement ) {
\t\t\t\tcontinue;
\t\t\t}
\t\t\t$text = trim( preg_replace( '/\\s+/u', ' ', $link->textContent ) ?? $link->textContent );
\t\t\t$href = $link->getAttribute( 'href' );
\t\t\t$is_map_action = preg_match( '/(?:cómo llegar|como llegar|google maps|maps\\.app|google\\.[^\\/]+\\/maps)/iu', $text . ' ' . $href );
\t\t\tif ( $is_map_action && ! $map_action_seen ) {
\t\t\t\tnvx_clinics_set_link_attributes( $link, $key );
\t\t\t\t$map_action_seen = true;
\t\t\t} elseif ( $is_map_action ) {
\t\t\t\t$link->parentNode?->removeChild( $link );
\t\t\t}
\t\t}

\t\tif ( ! $map_action_seen ) {
\t\t\t$link = $dom->createElement( 'a', 'Abrir en Google Maps' );
\t\t\tnvx_clinics_set_link_attributes( $link, $key );
\t\t\t$actions = $dom->createElement( 'div' );
\t\t\t$actions->setAttribute( 'class', 'nvx-brand-actions nvx-clinic-location__actions' );
\t\t\t$actions->appendChild( $link );
\t\t\t$block->appendChild( $actions );
\t\t}
\t}

\tif ( isset( $blocks['chamberi'], $blocks['goya'] ) && ! $dom->getElementById( 'nvx-clinics-nav' ) ) {
\t\t$nav = $dom->createElement( 'nav' );
\t\t$nav->setAttribute( 'id', 'nvx-clinics-nav' );
\t\t$nav->setAttribute( 'class', 'nvx-clinics-nav' );
\t\t$nav->setAttribute( 'aria-label', 'Navegación entre las clínicas NUVANX en Madrid' );
\t\t$inner = $dom->createElement( 'div' );
\t\t$inner->setAttribute( 'class', 'nvx-shell nvx-clinics-nav__inner' );
\t\tforeach ( $clinics as $config ) {
\t\t\t$link = $dom->createElement( 'a', $config['label'] );
\t\t\t$link->setAttribute( 'href', '#' . $config['id'] );
\t\t\t$link->setAttribute( 'class', 'nvx-clinics-nav__link' );
\t\t\t$inner->appendChild( $link );
\t\t}
\t\t$nav->appendChild( $inner );
\t\t$blocks['chamberi']->parentNode?->insertBefore( $nav, $blocks['chamberi'] );
\t}

\t$root = $dom->getElementById( 'nvx-clinics-document' );
\tif ( ! $root ) {
\t\treturn $content;
\t}

\t$output = '';
\tforeach ( $root->childNodes as $child ) {
\t\t$output .= $dom->saveHTML( $child );
\t}
\treturn $output ?: $content;
}
add_filter( 'the_content', 'nvx_clinics_hub_enhance', 30 );
`;

fs.mkdirSync('wp-content/themes/nuvanx-medical/inc', { recursive: true });
fs.writeFileSync(incPath, php);

let functions = fs.readFileSync(functionsPath, 'utf8');
const requireLine = "require_once get_template_directory() . '/inc/nvx-clinics-hub.php';";
if (!functions.includes(requireLine)) {
  functions = functions.trimEnd() + `\n${requireLine}\n`;
  fs.writeFileSync(functionsPath, functions);
}

let components = fs.readFileSync(componentsPath, 'utf8');
const marker = '/* Clinics hub navigation — issue #46 */';
if (!components.includes(marker)) {
  components = components.trimEnd() + `\n\n${marker}\nhtml{scroll-behavior:smooth}\n@media(prefers-reduced-motion:reduce){html{scroll-behavior:auto}}\n.nvx-clinics-nav{border-block:1px solid var(--nvx-color-line);background:var(--nvx-surface-base)}\n.nvx-clinics-nav__inner{display:flex;flex-wrap:wrap;align-items:center;justify-content:center;gap:var(--nvx-space-4);padding-block:var(--nvx-space-3)}\n.nvx-clinics-nav__link{font-family:var(--nvx-sans);font-size:var(--nvx-type-small);font-weight:600;letter-spacing:var(--nvx-track-button);text-transform:uppercase;color:var(--nvx-ink);text-decoration:none;border-bottom:1px solid transparent;padding-block:var(--nvx-space-1)}\n.nvx-clinics-nav__link:hover,.nvx-clinics-nav__link:focus-visible{border-color:currentColor}\n.nvx-clinics-nav__link:focus-visible,.nvx-clinic-map-cta:focus-visible{outline:2px solid var(--nvx-ink);outline-offset:4px}\n.nvx-clinic-location{scroll-margin-top:calc(var(--nvx-header-height) + var(--nvx-space-4))}\n.nvx-clinic-location__actions{margin-top:var(--nvx-space-4)}\n@media(max-width:680px){.nvx-clinics-nav__inner{justify-content:flex-start;gap:var(--nvx-space-3);overflow-x:auto;scrollbar-width:none}.nvx-clinics-nav__inner::-webkit-scrollbar{display:none}.nvx-clinics-nav__link{white-space:nowrap}.nvx-clinic-location{scroll-margin-top:calc(var(--nvx-header-height-mobile) + var(--nvx-space-3))}}\n`;
  fs.writeFileSync(componentsPath, components);
}

console.log('Issue #46 clinics hub UX patch applied.');
