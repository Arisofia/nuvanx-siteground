<?php
/** Contract test for canonical home FAQ HTML/schema parity. */

declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );

function add_filter( ...$args ): bool { return true; }
function is_admin(): bool { return false; }
function wp_doing_ajax(): bool { return false; }
function is_front_page(): bool { return true; }
function esc_html__( string $text ): string { return htmlspecialchars( $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' ); }
function esc_html( string $text ): string { return htmlspecialchars( $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' ); }
function esc_attr( string $text ): string { return htmlspecialchars( $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' ); }
function home_url( string $path = '/' ): string { return 'https://nuvanx.com' . $path; }
function nvx_endolift_price_from_eur(): float { return 798.60; }
function nvx_endolift_price_papada_eur(): float { return 1064.80; }
function nvx_format_price_eur( float $amount ): string { return number_format( $amount, 2, ',', '.' ); }

require dirname( __DIR__, 2 ) . '/wp-content/themes/nuvanx-medical/inc/nvx-faq-content-v2.php';

$input = <<<'HTML'
<div class="nvx-brand-page">
  <section class="nvx-unrelated"><h2>Contenido clínico</h2><p>No debe desaparecer.</p></section>
  <section class="nvx-home-faq-editorial"><h2>Preguntas frecuentes anteriores</h2><div class="nvx-faq"><details><summary><span>Pregunta vieja</span></summary><div><p>Respuesta vieja</p></div></details></div></section>
  <section class="nvx-brand-section"><h2>Preguntas frecuentes duplicadas</h2><div class="nvx-brand-faq-accordion"><details><summary>Duplicada</summary><p>Duplicada</p></details></div></section>
</div>
HTML;

$output  = nvx_home_faq_v2_transform( $input );
$catalog = nvx_home_faq_v2_catalog();

if ( 1 !== substr_count( $output, 'data-nvx-faq-source="canonical"' ) ) {
	fwrite( STDERR, "Visible FAQ must have one canonical marker.\n" );
	exit( 1 );
}
if ( count( $catalog ) !== preg_match_all( '/<details\b/iu', $output ) ) {
	fwrite( STDERR, "Visible FAQ item count does not match catalogue.\n" );
	exit( 1 );
}
if ( false !== strpos( $output, 'Pregunta vieja' ) || false !== strpos( $output, 'Preguntas frecuentes duplicadas' ) ) {
	fwrite( STDERR, "Legacy or duplicate FAQ content remains visible.\n" );
	exit( 1 );
}
if ( false === strpos( $output, 'Contenido clínico' ) || false === strpos( $output, 'No debe desaparecer.' ) ) {
	fwrite( STDERR, "A non-FAQ page section was removed.\n" );
	exit( 1 );
}
foreach ( $catalog as $faq ) {
	if ( false === strpos( html_entity_decode( $output, ENT_QUOTES | ENT_HTML5, 'UTF-8' ), $faq['q'] ) || false === strpos( html_entity_decode( $output, ENT_QUOTES | ENT_HTML5, 'UTF-8' ), $faq['a'] ) ) {
		fwrite( STDERR, "Visible FAQ differs from catalogue: {$faq['id']}.\n" );
		exit( 1 );
	}
}
if ( $output !== nvx_home_faq_v2_transform( $output ) ) {
	fwrite( STDERR, "Visible FAQ transformation is not idempotent.\n" );
	exit( 1 );
}

$graph = array(
	array(
		'@type'      => array( 'WebPage', 'FAQPage' ),
		'@id'        => 'https://nuvanx.com/#webpage',
		'url'        => 'https://nuvanx.com/',
		'name'       => 'Home',
		'mainEntity' => array( array( '@type' => 'Question', 'name' => 'Vieja' ) ),
	),
	array(
		'@type'      => 'FAQPage',
		'@id'        => 'https://nuvanx.com/#duplicate-faq',
		'mainEntity' => array(),
	),
	array( '@type' => 'Organization', '@id' => 'https://nuvanx.com/#organization' ),
);

$result    = nvx_home_faq_v2_schema_graph( $graph );
$faq_nodes = array_values(
	array_filter(
		$result,
		static fn( $piece ): bool => is_array( $piece ) && isset( $piece['@type'] ) && nvx_home_faq_v2_has_type( $piece['@type'], 'FAQPage' )
	)
);

if ( 1 !== count( $faq_nodes ) ) {
	fwrite( STDERR, "Schema graph must contain exactly one FAQPage piece.\n" );
	exit( 1 );
}
$faq_node = $faq_nodes[0];
if ( 'https://nuvanx.com/#webpage' !== $faq_node['@id'] || ! nvx_home_faq_v2_has_type( $faq_node['@type'], 'WebPage' ) ) {
	fwrite( STDERR, "Combined WebPage identity was not preserved.\n" );
	exit( 1 );
}
if ( count( $catalog ) !== count( $faq_node['mainEntity'] ) ) {
	fwrite( STDERR, "Schema FAQ item count does not match visible catalogue.\n" );
	exit( 1 );
}
foreach ( $catalog as $index => $faq ) {
	$schema = $faq_node['mainEntity'][ $index ];
	if ( $faq['q'] !== $schema['name'] || $faq['a'] !== $schema['acceptedAnswer']['text'] ) {
		fwrite( STDERR, "Schema FAQ differs from visible catalogue: {$faq['id']}.\n" );
		exit( 1 );
	}
}

fwrite( STDOUT, "Canonical home FAQ parity tests passed.\n" );
