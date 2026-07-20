<?php
/**
 * Template Name: Sede Local
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * La estructura DOM global y los componentes maestros (Header/Footer)
 * son gobernados exclusivamente por nvx-page-shell.php.
 */

// 1. Iniciar la captura del contenido específico de la página/sede
ob_start();
?>

<!-- INICIO: Lógica táctica, Loops y extracción de datos -->
<?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
    
    <article id="post-<?php the_ID(); ?>" <?php post_class('nvx-dynamic-content'); ?>>
        <?php 
        // Aquí permanece intacta tu lógica actual: llamadas a la base de datos, 
        // inyección de coordenadas, esquemas de tratamientos, galerías, etc.
        the_content(); 
        ?>
    </article>

<?php endwhile; endif; ?>
<!-- FIN: Lógica táctica -->

<?php
// 2. Almacenar el contenido renderizado
$nvx_tactical_content = ob_get_clean();

// 3. Transmitir el contenido al Shell maestro para su ensamblaje
set_query_var( 'nvx_shell_content', $nvx_tactical_content );
get_template_part( 'template-parts/content/nvx-page-shell' );