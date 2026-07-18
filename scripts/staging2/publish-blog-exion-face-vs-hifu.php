<?php
/**
 * Publish / update blog: EXION Face vs HIFU / Thermage (staging2).
 *
 * Usage (on staging2 WP root):
 *   wp eval-file /path/to/publish-blog-exion-face-vs-hifu.php
 *   wp eval-file /path/to/publish-blog-exion-face-vs-hifu.php -- --apply
 *
 * Guards: siteurl/home must be https://staging2.nuvanx.com
 *
 * @package nuvanx-medical
 */

// Note: no declare(strict_types=1) — WP-CLI eval-file prepends code, so strict_types fatals.

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "ERROR: run through wp eval-file.\n" );
	exit( 1 );
}

// Prefer env flag: WP-CLI may treat --apply as an unknown global option.
$apply = ( '1' === getenv( 'NVX_BLOG_APPLY' ) || 'yes' === getenv( 'NVX_BLOG_APPLY' ) );
// Also accept $args if the host WP-CLI version forwards them.
if ( ! $apply && isset( $args ) && is_array( $args ) ) {
	$apply = in_array( '--apply', $args, true ) || in_array( 'apply', $args, true );
}

$expected_url = 'https://staging2.nuvanx.com';
$site_url     = rtrim( (string) get_option( 'siteurl' ), '/' );
$home_url     = rtrim( (string) get_option( 'home' ), '/' );

if ( $site_url !== $expected_url || $home_url !== $expected_url ) {
	fwrite( STDERR, "ERROR: staging2 URL guard failed (expected {$expected_url}).\n" );
	exit( 1 );
}

if ( 'nuvanx-medical' !== wp_get_theme()->get_stylesheet() ) {
	fwrite( STDERR, "ERROR: expected active theme nuvanx-medical.\n" );
	exit( 1 );
}

if ( ! defined( 'NVX_BLOG_EXION_VS_HIFU_SLUG' ) ) {
	define( 'NVX_BLOG_EXION_VS_HIFU_SLUG', 'exion-face-vs-hifu-ultherapy-thermage-regeneracion-endogena' );
}

/**
 * Canonical post HTML (wrapper matches existing journal posts).
 */
function nvx_blog_exion_vs_hifu_html(): string {
	$valoracion = esc_url( home_url( '/madrid/valoracion/' ) );
	$exion      = esc_url( home_url( '/exion-btl/' ) );
	$equipo     = esc_url( home_url( '/equipo-medico/#physician-rivera-tejeda' ) );
	$wa         = 'https://wa.me/34669319836';
	$doctoralia = 'https://www.doctoralia.es/jose-javier-rivera-tejeda/medico-estetico/madrid';

	return <<<HTML
<div class="nvx-blog-article nvx-brand-readable">
<!-- Byline: theme hero meta — do not hardcode Autor/Fecha/Lectura in body. -->

<h2>Introducción: la trampa termodinámica del damage controlado</h2>
<p>Durante dos décadas, gran parte de la medicina estética facial ha operado bajo un principio biomecánico hoy cuestionado: <strong>provocar daño térmico intenso</strong> en la dermis para forzar cicatrización y contracción de colágeno. HIFU (<em>High Intensity Focused Ultrasound</em>), con plataformas comerciales como Ultherapy®, y radiofrecuencia volumétrica tradicional tipo Thermage®, se construyeron en gran medida sobre esa premisa.</p>
<p><strong>El problema clínico:</strong> el modelo de “quemadura controlada” puede ofrecer un resultado inicial aparente, pero a medio plazo (12–24 meses) se han descrito complicaciones silenciosas en series y práctica clínica: pérdida de volumen adiposo facial, fibrosis desorganizada y un envejecimiento paradójico en el que la piel puede verse más “esqueletizada”.</p>
<p><a class="nvx-brand-inline-link" href="{$exion}">EXION® Face</a> (BTL), implementada en NUVANX Madrid, plantea un paradigma distinto: <strong>sinergia de radiofrecuencia monopolar y ultrasonido terapéutico (TUS)</strong> orientada a reactivar la regeneración endógena del paciente, no a necrosar el tejido para que “cicatrice tenso”.</p>

<h2>¿Por qué HIFU y RF tradicional fallan a largo plazo en algunos pacientes?</h2>
<h3>Mecanismo de fallo documentado</h3>
<p><strong>HIFU (p. ej. Ultherapy®)</strong> concentra energía en puntos focales con temperaturas pico frecuentemente descritas en el rango de ~65–70&nbsp;°C durante fracciones de segundo. A esa intensidad, las proteínas tisulares pueden desnaturalizarse de forma irreversible. El fibroblasto sometido a estrés térmico extremo no siempre regenera colágeno bien organizado: puede entrar en apoptosis o producir matriz cicatricial rígida y desorganizada.</p>
<p><strong>RF volumétrica tradicional (p. ej. Thermage®)</strong> genera calor en dermis media/profunda, con picos frecuentemente citados en torno a 60–65&nbsp;°C, mientras se protege la epidermis con enfriamiento. El calor residual no siempre es tan selectivo como el discurso comercial sugiere.</p>
<p><strong>Consecuencias biológicas que explican el descontento a 12–24 meses:</strong></p>
<ol>
<li><strong>Pérdida de volumen adiposo:</strong> los compartimentos de grasa facial rodean la dermis profunda. El calor intenso puede inducir apoptosis en adipocitos periféricos y contribuir a un aspecto de “hundimiento” que después se intenta compensar con rellenos.</li>
<li><strong>Fibrosis desorganizada:</strong> en lugar de colágeno tipo I/III bien orientado, puede predominar colágeno cicatricial con entrecruzamiento rígido y textura artificial.</li>
<li><strong>Envejecimiento paradójico:</strong> menos grasa + colágeno de mala calidad = peor calidad de contorno a medio plazo, aunque la “tirantez” de las primeras semanas pareciera un éxito.</li>
</ol>

<h2>EXION Face: biomecánica de la regeneración endógena</h2>
<h3>1. Radiofrecuencia monopolar a microtemperaturas controladas (~40–42&nbsp;°C)</h3>
<p>A diferencia de protocolos de RF de alto pico térmico, EXION Face trabaja en un rango de <strong>hipertermia controlada y reversible</strong>. En ese intervalo fisiológico elevado (no de necrosis), se describe la activación de vías de estrés adaptativo:</p>
<ul>
<li><strong>HSPs (heat shock proteins)</strong> — respuesta protectora y de reparación celular.</li>
<li><strong>Señalización TGF-β</strong> — regulador clave de la síntesis de matriz.</li>
<li><strong>Mayor demanda metabólica del fibroblasto</strong> — contexto favorable a síntesis proteica si el estímulo es dosificado y repetible.</li>
</ul>
<h3>2. Ultrasonido terapéutico (TUS)</h3>
<p>El ultrasonido aporta <strong>estrés mecánico no invasivo</strong> (frecuencias habituales en el orden de 1–2&nbsp;MHz según modo). Las microvibraciones estimulan fibroblastos a través de mecanotransducción (p. ej. vías mediadas por integrinas), favoreciendo síntesis de matriz extracelular sin el patrón de cavitación destructiva de energías de ablación.</p>

<h2>Datos de eficacia: lo que documenta el fabricante y cómo lo leemos en consulta</h2>
<p>Los marcadores más citados en documentación clínica de EXION Face (BTL; materiales de referencia 2024–2025) incluyen, en modelos y series evaluadas:</p>
<table class="nvx-blog-table">
<thead>
<tr><th>Parámetro</th><th>Orden de magnitud comunicado</th><th>Ventana habitual</th><th>Lectura clínica</th></tr>
</thead>
<tbody>
<tr><td>Ácido hialurónico endógeno</td><td>hasta +224%</td><td>~4 semanas</td><td>Síntesis de novo por fibroblastos estimulados (no “inyección” de AH)</td></tr>
<tr><td>Colágeno tipo I</td><td>hasta +47%</td><td>8–12 semanas</td><td>Respuesta de remodelado; no es un lifting quirúrgico</td></tr>
<tr><td>Elastina</td><td>hasta +50%</td><td>8–12 semanas</td><td>Mejora de calidad y elasticidad percibida</td></tr>
<tr><td>Hidratación dérmica</td><td>hasta +38%</td><td>~2 semanas</td><td>Retención de agua en matriz</td></tr>
</tbody>
</table>
<p><em>Nota:</em> estos porcentajes no sustituyen el diagnóstico individual. En NUVANX se contextualizan tras valoración médica: fototipo, reserva grasa, historial de HIFU/RF previo y expectativas realistas.</p>

<h3>Comparativa de tolerancia (marco clínico orientativo)</h3>
<table class="nvx-blog-table">
<thead>
<tr><th>Parámetro</th><th>EXION Face</th><th>HIFU (p. ej. Ultherapy®)</th><th>RF tradicional (p. ej. Thermage®)</th></tr>
</thead>
<tbody>
<tr><td>Temperatura pico habitual</td><td>~40–42&nbsp;°C</td><td>~65–70&nbsp;°C</td><td>~60–65&nbsp;°C</td></tr>
<tr><td>Dolor percibido (0–10)</td><td>0–2 en la mayoría de sesiones</td><td>a menudo 6–8 sin anestesia</td><td>frecuente 5–7</td></tr>
<tr><td>Riesgo relativo de atrofia adiposa</td><td>bajo en series del fabricante</td><td>mayor en literatura y práctica cuando hay sobretratamiento</td><td>mayor si el calor volumétrico es agresivo</td></tr>
<tr><td>Downtime típico</td><td>mínimo / nulo</td><td>horas de eritema/sensibilidad</td><td>horas a 1–2 días de eritema</td></tr>
</tbody>
</table>
<p>Las tasas absolutas de complicaciones varían según operador, protocolo y selección de paciente; en consulta no se prometen porcentajes universales, sino <strong>criterio de seguridad</strong>.</p>

<h2>La física del volumen facial: por qué importa no “quemar” grasa</h2>
<p>El envejecimiento facial no es solo “menos colágeno”. Es una ecuación de tres variables:</p>
<ul>
<li><strong>ΔV adiposo</strong> — pérdida de volumen de grasa facial</li>
<li><strong>ΔC colágeno</strong> — pérdida y desorganización de colágeno dérmico</li>
<li><strong>ΔH hidratación</strong> — menos agua en la matriz extracelular</li>
</ul>
<p>HIFU y RF de alto pico pueden mejorar temporalmente la “tirantez” (ΔC) a costa de agravar ΔV si se lesiona grasa. EXION Face busca <strong>mejorar matriz (AH, colágeno, elastina) preservando el volumen</strong> cuando el protocolo y la anatomía lo permiten.</p>

<h2>Caso clínico ilustrativo (consulta NUVANX)</h2>
<p><strong>Perfil:</strong> mujer, 52 años, descolgamiento leve, piel apagada. Antecedente de HIFU años atrás: buen resultado inicial y, a ~18 meses, percepción de mayor “hundimiento” malar con dependencia de rellenos.</p>
<p><strong>Plan:</strong> 3 sesiones de EXION Face (intervalo ~4 semanas) y, según respuesta, EMFUSION® como adyuvante de barrera/hidratación tras el bloque principal.</p>
<p><strong>Objetivo:</strong> redensificar y rehidratar sin quemar compartimentos grasos, reduciendo la necesidad de compensar con volumen inyectable si el tejido responde.</p>
<p><em>Los casos ilustran el razonamiento clínico; no garantizan el mismo resultado en todos los pacientes.</em></p>

<h2>¿Calentar o regenerar?</h2>
<p><strong>Thermage® / HIFU “calientan” con picos altos</strong> para forzar contracción y cicatrización. <strong>EXION Face estimula</strong> con hipertermia controlada + mecanotransducción para síntesis de novo de matriz. En NUVANX no sustituimos el juicio médico por un catálogo de aparatos: si el caso requiere lifting quirúrgico o inductores, se indica con honestidad.</p>

<h2>FAQs clínicas</h2>
<h3>¿EXION Face “genera +224% de ácido hialurónico”?</h3>
<p>El <strong>+224%</strong> es una magnitud comunicada en documentación del fabricante a partir de estudios de matriz (biopsia/análisis de referencia). En consulta lo presentamos como <strong>potencial de estimulación endógena</strong>, no como promesa personalizada. El AH se produce por fibroblastos del paciente; no es un vial inyectado.</p>
<h3>¿Cuántas sesiones necesito?</h3>
<ul>
<li><strong>40–50 años, calidad media:</strong> habitualmente 3 sesiones (≈4 semanas entre ellas)</li>
<li><strong>50–60, envejecimiento moderado:</strong> 4–5 sesiones según respuesta</li>
<li><strong>60+ o fotodaño severo:</strong> plan combinado (EXION ± otras modalidades) tras diagnóstico</li>
</ul>
<p>Mantenimiento orientativo: 1 sesión cada 12–18 meses si el objetivo se mantiene.</p>
<h3>¿Por qué HIFU “funciona” al principio?</h3>
<p>Porque desnaturaliza y contrae colágeno existente de forma inmediata. El resultado de las primeras semanas puede impresionar; el problema aparece cuando se suma pérdida de volumen y calidad cicatricial a medio plazo.</p>
<h3>¿Es seguro en fototipos altos?</h3>
<p>Al trabajar con picos térmicos más fisiológicos, el riesgo de hiperpigmentación postinflamatoria suele ser inferior al de protocolos de daño térmico intenso; aun así, la indicación la marca el médico (fototipo, medicación, historial de PIH).</p>

<h2>Protocolo orientativo en NUVANX Madrid</h2>
<ol>
<li><strong>Sesión 1:</strong> EXION Face (cara ± cuello según indicación)</li>
<li><strong>Sesión 2 (~4 semanas):</strong> EXION Face ± adyuvantes de barrera si procede</li>
<li><strong>Sesión 3 (~8 semanas):</strong> EXION Face de consolidación</li>
<li><strong>Mantenimiento:</strong> individualizado (a menudo 12–18 meses)</li>
</ol>
<p><strong>Duración orientativa por sesión:</strong> ~30 minutos. <strong>Inversión:</strong> presupuesto cerrado tras valoración presencial (sin catálogo de “precio milagro” online).</p>

<h2>Valoración médica en NUVANX</h2>
<p>Si buscas rejuvenecimiento sin cirugía y sin apostar por un modelo de daño térmico agresivo, agenda una valoración presencial. Revisamos historial (incluido HIFU/RF previos), anatomía y expectativas.</p>
<p class="nvx-blog-cta-row"><a class="nvx-brand-btn nvx-brand-btn--primary" href="{$valoracion}">Reservar valoración médica</a>
<a class="nvx-brand-btn nvx-brand-btn--secondary" href="{$wa}" target="_blank" rel="noopener noreferrer">WhatsApp</a>
<a class="nvx-brand-inline-link" href="{$exion}">Ficha EXION® BTL</a>
<a class="nvx-brand-inline-link" href="{$doctoralia}" target="_blank" rel="noopener noreferrer">Doctoralia · Dr. Rivera</a></p>
<ul>
<li><strong>Chamberí:</strong> C/ Fernández de la Hoz, 4 · CS20144 · 669 319 836</li>
<li><strong>Goya · Barrio Salamanca:</strong> C/ Fernán González, 26 · CS20073 · 647 505 107</li>
<li><strong>Email:</strong> info@nuvanx.com</li>
</ul>

<h2>Referencias y fuentes de contexto</h2>
<ol>
<li>Documentación clínica y materiales de referencia BTL Aesthetics sobre EXION Face (RF monopolar + ultrasonido terapéutico; marcadores de matriz extracelular).</li>
<li>Literatura sobre mecanismos térmicos de HIFU y RF volumétrica y complicaciones de remodelado (atrofia/fibrosis) en series y revisiones de dermatología estética.</li>
<li>Trabajos de mecanotransducción y síntesis de matriz mediada por ultrasonido / señalización de fibroblastos (integrinas, TGF-β, HSPs) en biología cutánea.</li>
</ol>
<p><em>NUVANX Madrid: la tecnología al servicio del diagnóstico médico, no al revés.</em> Publicado el 15 de julio de 2026.</p>
</div>
HTML;
}

$slug  = NVX_BLOG_EXION_VS_HIFU_SLUG;
$title = 'EXION Face vs HIFU (Ultherapy) y RF tradicional (Thermage): el fin de la quemadura controlada';
$html  = nvx_blog_exion_vs_hifu_html();

$existing = get_page_by_path( $slug, OBJECT, 'post' );
$post_id  = ( $existing instanceof WP_Post ) ? (int) $existing->ID : 0;

$cat_id = 0;
$term   = get_term_by( 'slug', 'medicina-estetica-laser', 'category' );
if ( $term instanceof WP_Term ) {
	$cat_id = (int) $term->term_id;
} else {
	$created = wp_insert_term( 'Medicina estética láser', 'category', array( 'slug' => 'medicina-estetica-laser' ) );
	if ( ! is_wp_error( $created ) && ! empty( $created['term_id'] ) ) {
		$cat_id = (int) $created['term_id'];
	}
}

$excerpt = 'Comparativa clínica EXION Face frente a HIFU (Ultherapy) y RF tradicional (Thermage): por qué el daño térmico agresivo envejece a medio plazo y cómo la regeneración endógena cambia el protocolo en NUVANX Madrid.';

$postarr = array(
	'post_title'   => $title,
	'post_name'    => $slug,
	'post_content' => $html,
	'post_excerpt' => $excerpt,
	'post_status'  => 'publish',
	'post_type'    => 'post',
	'post_date'    => '2026-07-15 10:00:00',
	'post_date_gmt'=> get_gmt_from_date( '2026-07-15 10:00:00' ),
);

if ( $cat_id > 0 ) {
	$postarr['post_category'] = array( $cat_id );
}

echo wp_json_encode(
	array(
		'mode'     => $apply ? 'apply' : 'audit',
		'slug'     => $slug,
		'existing' => $post_id,
		'title'    => $title,
		'bytes'    => strlen( $html ),
		'category' => $cat_id,
	),
	JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
) . "\n";

if ( ! $apply ) {
	echo "DRY_RUN_OK\n";
	exit( 0 );
}

if ( $post_id > 0 ) {
	$postarr['ID'] = $post_id;
	$result        = wp_update_post( wp_slash( $postarr ), true );
} else {
	$result = wp_insert_post( wp_slash( $postarr ), true );
}

if ( is_wp_error( $result ) ) {
	fwrite( STDERR, 'ERROR: ' . $result->get_error_message() . "\n" );
	exit( 1 );
}

$post_id = (int) $result;

// Yoast SEO meta (best-effort).
update_post_meta( $post_id, '_yoast_wpseo_title', 'EXION Face vs HIFU y Thermage | Regeneración endógena | NUVANX Madrid' );
update_post_meta( $post_id, '_yoast_wpseo_metadesc', 'EXION Face frente a Ultherapy (HIFU) y Thermage: menos daño térmico, más regeneración endógena. Criterio médico en NUVANX Chamberí y Goya.' );
update_post_meta( $post_id, '_yoast_wpseo_focuskw', 'EXION Face Madrid' );

$permalink = get_permalink( $post_id );
echo "APPLIED post_id={$post_id}\n";
echo 'permalink=' . ( is_string( $permalink ) ? $permalink : '' ) . "\n";
echo "STAGING2_BLOG_EXION_VS_HIFU_OK\n";
exit( 0 );
