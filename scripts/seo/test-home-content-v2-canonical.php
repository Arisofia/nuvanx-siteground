<?php
/**
 * Canonical contract for home values, action, method and protocols.
 */

declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );

function add_filter( ...$args ): bool {
	return true;
}
function is_admin(): bool {
	return false;
}
function wp_doing_ajax(): bool {
	return false;
}
function is_front_page(): bool {
	return true;
}

require dirname( __DIR__, 2 ) . '/wp-content/themes/nuvanx-medical/inc/nvx-home-content-v2.php';

$input = <<<'HTML'
<div class="nvx-brand-page">
<section class="nvx-brand-section nvx-values-section">
  <div><p class="nvx-brand-kicker">La base de nuestro criterio clínico</p><h2 class="nvx-brand-title">Texto anterior</h2>
  <div class="nvx-values">
    <article class="nvx-value"><h3 class="nvx-value__title">Viejo 1</h3><p class="nvx-value__body">Viejo body 1</p></article>
    <article class="nvx-value"><h3 class="nvx-value__title">Viejo 2</h3><p class="nvx-value__body">Viejo body 2</p></article>
    <article class="nvx-value"><h3 class="nvx-value__title">Viejo 3</h3><p class="nvx-value__body">Viejo body 3</p></article>
  </div></div>
</section>
<div id="nvx-post-values-action-banner"><section class="nvx-home-action-banner">
  <p class="nvx-home-action-banner__kicker">Valoración</p><h2 class="nvx-home-action-banner__title">Recupera la armonía</h2><p class="nvx-home-action-banner__text">Texto anterior</p>
  <div><a href="/valoracion/">Viejo CTA</a><a href="https://wa.me/">WhatsApp</a></div>
</section></div>
<section class="nvx-brand-section nvx-method-section">
  <p class="nvx-brand-kicker">Método</p><h2 class="nvx-brand-title">El criterio médico</h2><p class="nvx-method-lead">Viejo lead</p>
  <div class="nvx-method-columns">
    <article class="nvx-method-col"><h3 class="nvx-method-col__title">Viejo 1</h3><p class="nvx-method-col__body">Body</p></article>
    <article class="nvx-method-col"><h3 class="nvx-method-col__title">Viejo 2</h3><p class="nvx-method-col__body">Body</p></article>
    <article class="nvx-method-col"><h3 class="nvx-method-col__title">Viejo 3</h3><p class="nvx-method-col__body">Body</p></article>
  </div>
</section>
<section class="nvx-home-protocols">
  <article class="nvx-home-protocol"><h3 class="nvx-home-protocol__title">Viejo Endolift</h3><p class="nvx-home-protocol__lead">Considerado el estándar de oro actual.</p><dl><div class="nvx-home-protocol__fact"><dt>Indicación</dt><dd>Vieja</dd></div><div class="nvx-home-protocol__fact"><dt>Recuperación</dt><dd>Vieja</dd></div></dl></article>
  <article class="nvx-home-protocol"><h3 class="nvx-home-protocol__title">Viejo Endoláser</h3><p class="nvx-home-protocol__lead">El abordaje definitivo supera ampliamente el frío.</p><dl><div class="nvx-home-protocol__fact"><dt>Zonas</dt><dd>Vieja</dd></div></dl></article>
  <article class="nvx-home-protocol"><h3 class="nvx-home-protocol__title">Viejo CO2</h3><p class="nvx-home-protocol__lead">Máxima expresión.</p><dl><div class="nvx-home-protocol__fact"><dt>Resultados</dt><dd>Mejora radical y síntesis masiva.</dd></div><div class="nvx-home-protocol__fact"><dt>Recuperación</dt><dd>Vieja</dd></div></dl></article>
</section>
</div>
HTML;

$output = nvx_home_content_v2_transform( $input );

$required = array(
	'Por qué NUVANX no es una clínica de estética',
	'Medicina estética donde el diagnóstico decide la tecnología',
	'1. Diagnóstico médico, no catálogo',
	'2. Tecnología médica certificada CE',
	'3. Equipo médico hospitalario en activo',
	'15–30 minutos para saber si existe indicación',
	'Reservar valoración gratuita',
	'WhatsApp con la clínica',
	'Un protocolo médico en tres decisiones',
	'Evaluación individual',
	'Indicación y parámetros',
	'Control de evolución',
	'Microfibra láser bajo la piel para tensar tejido',
	'Protocolo láser ambulatorio para focos de grasa',
	'Resurfacing fraccionado para cicatrices de acné',
	'Desde 798,60 €',
	'Desde 330 € sesión facial / 450 € corporal',
);

foreach ( $required as $text ) {
	if ( false === strpos( $output, $text ) ) {
		fwrite( STDERR, "Required canonical text missing: {$text}\n" );
		exit( 1 );
	}
}

$forbidden = array(
	'estándar de oro',
	'abordaje definitivo',
	'supera ampliamente',
	'mejora radical',
	'síntesis masiva',
	'Recupera la armonía',
);
foreach ( $forbidden as $text ) {
	if ( false !== stripos( $output, $text ) ) {
		fwrite( STDERR, "Unsupported or superseded text remains: {$text}\n" );
		exit( 1 );
	}
}

if ( 1 !== substr_count( $output, 'data-nvx-home-content="v2"' ) ) {
	fwrite( STDERR, "Canonical v2 marker must appear exactly once.\n" );
	exit( 1 );
}

$second_pass = nvx_home_content_v2_transform( $output );
if ( $output !== $second_pass ) {
	fwrite( STDERR, "Canonical home transformation is not idempotent.\n" );
	exit( 1 );
}

fwrite( STDOUT, "Canonical home content v2 tests passed.\n" );
