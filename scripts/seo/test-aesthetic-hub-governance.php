<?php
/** Contract test for aesthetic hub route and clinical wording governance. */

declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );
$GLOBALS['nvx_test_published_routes'] = array();

function add_filter( ...$args ): bool { return true; }
function home_url( string $path = '' ): string { return 'https://nuvanx.com' . $path; }
function esc_url( string $value ): string { return $value; }
function esc_html__( string $value, string $domain = '' ): string { return $value; }
function nvx_cta_valoracion_url(): string { return 'https://nuvanx.com/madrid/valoracion/'; }
function nvx_aesthetic_lookup_published_url( string $slug ): ?string {
	return $GLOBALS['nvx_test_published_routes'][ $slug ] ?? null;
}

require dirname( __DIR__, 2 ) . '/wp-content/themes/nuvanx-medical/inc/nvx-aesthetic-hub-governance.php';

$fixture = '<div class="nvx-aesthetic-editorial">'
	. '<p>Restauramos el soporte estructural, la turgencia y la armonía del rostro mediante procedimientos médicos inyectables y regenerativos de alta precisión. Sin alterar tu identidad y guiados exclusivamente por el diagnóstico personalizado de nuestro equipo médico.</p>'
	. '<p>Protocolos inductores de colágeno mediante la infiltración de ácido poliláctico (Sculptra®) o hidroxiapatita de calcio (Radiesse®). Estos principios activos desencadenan una respuesta celular en la dermis profunda que estimula a los fibroblastos a producir nuevas fibras elásticas, tensando el tejido sin añadir volumen artificial al rostro.</p>'
	. '<p>Desde 290 €</p>'
	. '<a class="nvx-aes-card__link" href="https://nuvanx.com/labios-acido-hialuronico-madrid/">Ver protocolo</a>'
	. '</div>';

$governed = nvx_aesthetic_hub_governance_filter( $fixture );

$required = array(
	'Valoramos soporte, calidad cutánea, expresión y proporciones',
	'El ácido poli-L-láctico y la hidroxiapatita cálcica son materiales distintos del ácido hialurónico.',
	'Según valoración médica',
	'href="https://nuvanx.com/madrid/valoracion/"',
	'data-gtag="click-reserve"',
	'Solicitar valoración',
);
foreach ( $required as $fragment ) {
	if ( false === strpos( $governed, $fragment ) ) {
		fwrite( STDERR, "Missing governed output: {$fragment}\n" );
		exit( 1 );
	}
}

$forbidden = array(
	'Desde 290 €',
	'tensando el tejido sin añadir volumen artificial',
	'href="https://nuvanx.com/labios-acido-hialuronico-madrid/">Ver protocolo',
);
foreach ( $forbidden as $fragment ) {
	if ( false !== strpos( $governed, $fragment ) ) {
		fwrite( STDERR, "Forbidden hub output remains: {$fragment}\n" );
		exit( 1 );
	}
}

$GLOBALS['nvx_test_published_routes']['labios-acido-hialuronico-madrid'] = 'https://nuvanx.com/labios-acido-hialuronico-madrid/';
$published = nvx_aesthetic_hub_governance_filter( $fixture );
if ( false === strpos( $published, 'href="https://nuvanx.com/labios-acido-hialuronico-madrid/">Ver protocolo</a>' ) ) {
	fwrite( STDERR, "Published treatment route was incorrectly replaced.\n" );
	exit( 1 );
}

$unrelated = '<p>Unrelated content.</p>';
if ( nvx_aesthetic_hub_governance_filter( $unrelated ) !== $unrelated ) {
	fwrite( STDERR, "Governance changed unrelated content.\n" );
	exit( 1 );
}

fwrite( STDOUT, "Aesthetic hub governance contracts passed.\n" );
