<?php
/**
 * NUVANX Hostile Conversion Engine
 *
 * Implements the aggressive Pain -> Competitor Teardown -> Dr. Rivera Tejeda -> NUVANX Protocol architecture.
 * Hooks into the_content to forcefully structure treatment and landing pages.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generates the hostile competitor teardown block based on page context.
 *
 * @param string $path URL path.
 * @return string HTML markup.
 */
function nvx_hostile_teardown_markup( $path ): string {
	$pain_title = 'El Diagnóstico Erróneo del Mercado';
	$pain_text  = 'La mayoría de clínicas en Madrid ofrecen soluciones genéricas. Bonos de sesiones sin diagnóstico que resultan en pérdida de tiempo, dinero y una profunda frustración corporal.';

	if ( strpos( $path, 'remodelacion-corporal' ) !== false || strpos( $path, 'grasa' ) !== false || strpos( $path, 'abdomen' ) !== false || strpos( $path, 'flacidez' ) !== false || strpos( $path, 'endolaser' ) !== false ) {
		$pain_title = 'La Ilusión de la Criolipólisis Barata y el Riesgo de la Lipo';
		$pain_text  = 'La liposucción implica quirófano, riesgos y tiempos de baja. Las máquinas de frío genéricas de clínicas masificadas dejan flacidez severa. En NUVANX destruimos la grasa y retraemos la piel en la misma sesión, redibujando la anatomía sin cirugía.';
	} elseif ( strpos( $path, 'papada' ) !== false || strpos( $path, 'endolift' ) !== false ) {
		$pain_title = 'El Peligro de los Rostros Clonados e Inflados';
		$pain_text  = 'Inyectar más ácido hialurónico para "tensar" o esconder la papada solo ensancha el rostro y crea resultados artificiales. La verdadera tensión facial requiere tratar la arquitectura profunda de la piel, no rellenarla.';
	} elseif ( strpos( $path, 'cicatrices' ) !== false || strpos( $path, 'manchas' ) !== false || strpos( $path, 'co2' ) !== false || strpos( $path, 'exilite' ) !== false ) {
		$pain_title = 'Cremas Caras y Peelings de Belleza Inútiles';
		$pain_text  = 'Ningún cosmético ni tratamiento de cabina estética va a borrar cicatrices ni daño solar severo. La renovación real de la piel exige energía láser médica calibrada con precisión clínica.';
	}

	ob_start();
	?>
	<section class="nvx-editorial-section nvx-hostile-teardown">
		<div class="nvx-editorial-section__container">
			<h2 class="nvx-editorial-title"><?php echo esc_html( $pain_title ); ?></h2>
			<p class="nvx-editorial-text"><?php echo esc_html( $pain_text ); ?></p>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

/**
 * Generates the Dr. Rivera Tejeda Authority Block.
 *
 * @return string HTML markup.
 */
function nvx_hostile_authority_markup(): string {
	ob_start();
	?>
	<section class="nvx-editorial-section nvx-hostile-authority">
		<div class="nvx-editorial-section__container">
			<h2 class="nvx-editorial-title">Autoridad Diagnóstica: Dr. Rivera Tejeda</h2>
			<p class="nvx-editorial-text">"No aplicamos un láser sin entender la biometría del paciente. No inyectamos sin un objetivo anatómico. En NUVANX Madrid, el criterio clínico es innegociable."</p>
			<a href="<?php echo esc_url( home_url( '/valoracion/' ) ); ?>" class="nvx-button nvx-button--primary">Solicitar Diagnóstico Médico Real</a>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

/**
 * Injects the hostile architecture into treatment/editorial pages.
 *
 * @param string $content Existing content.
 * @return string Modified content.
 */
function nvx_hostile_conversion_inject( $content ) {
	if ( ! is_main_query() || ! is_singular( 'page' ) ) {
		return $content;
	}

	$path = function_exists( 'nvx_seo_current_path' ) ? nvx_seo_current_path() : $_SERVER['REQUEST_URI'] ?? '';
	
	// Only apply to specific treatment / editorial routes, avoiding legal pages or contact pages.
	$target_routes = array( 'endolift', 'endolaser', 'co2', 'remodelacion', 'postparto', 'papada', 'piel', 'cicatrices', 'manchas', 'grasa', 'flacidez', 'rodillas', 'contorno' );
	
	$is_target = false;
	foreach ( $target_routes as $route ) {
		if ( strpos( $path, $route ) !== false ) {
			$is_target = true;
			break;
		}
	}

	if ( ! $is_target ) {
		return $content;
	}

	// Prevent double injection
	if ( strpos( $content, 'nvx-hostile-teardown' ) !== false ) {
		return $content;
	}

	// Insert after the first H1 or Hero section (very rudimentarily looking for first closing section or header)
	$injection_point = strpos( $content, '</header>' );
	if ( false === $injection_point ) {
		$injection_point = strpos( $content, '</section>' );
	}
	
	$teardown  = nvx_hostile_teardown_markup( $path );
	$authority = nvx_hostile_authority_markup();
	$hostile_block = $teardown . $authority;

	if ( false !== $injection_point ) {
		$injection_point += 10; // length of </section> or </header>
		return substr_replace( $content, $hostile_block, $injection_point, 0 );
	}

	// Fallback to prepending
	return $hostile_block . $content;
}
# Hostile conversion engine desactivado; la arquitectura actual usa presentación neutral y protocolos Signature.
# add_filter( 'the_content', 'nvx_hostile_conversion_inject', 15 ); // Runs before presentation layer
