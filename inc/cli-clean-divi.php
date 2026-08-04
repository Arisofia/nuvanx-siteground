<?php
/**
 * NUVANX Divi Legacy Cleaner - WP-CLI Command
 *
 * Transforma shortcodes de Divi a HTML semántico y purga metadatos residuales.
 *
 * Uso:
 *   wp nvx clean-divi --dry-run
 *   wp nvx clean-divi --post-type=page,post
 *   wp eval-file inc/cli-clean-divi.php
 *
 * @package NUVANX_SiteGround
 * @version 1.0.0
 */

declare(strict_types=1);

if (!defined('WP_CLI') || !WP_CLI) {
    return;
}

final class NVX_Divi_Cleaner_Command {

    /**
     * Limpia la base de datos de shortcodes de Divi y los convierte a HTML semántico.
     *
     * ## OPTIONS
     *
     * [--post-type=<post-types>]
     * : Tipos de post separados por coma. Por defecto: 'page,post'.
     *
     * [--dry-run]
     * : Ejecuta una simulación sin modificar la base de datos.
     *
     * [--batch-size=<number>]
     * : Número de posts a procesar por lote. Por defecto: 50.
     *
     * ## EXAMPLES
     *
     *     wp nvx clean-divi --dry-run
     *     wp nvx clean-divi --post-type=page --batch-size=20
     */
    public function __invoke(array $args, array $assoc_args): void {
        $dry_run    = isset($assoc_args['dry-run']);
        $post_types = explode(',', $assoc_args['post-type'] ?? 'page,post');
        $batch_size = (int) ($assoc_args['batch-size'] ?? 50);

        WP_CLI::line(WP_CLI::colorize('%YIniciando análisis de shortcodes Divi...%n'));
        if ($dry_run) {
            WP_CLI::log(WP_CLI::colorize('%C[MODO DRY-RUN ACTIVADO] No se realizarán cambios en la Base de Datos.%n'));
        }

        $query_args = [
            'post_type'      => array_map('trim', $post_types),
            'post_status'    => ['publish', 'draft', 'private'],
            'posts_per_page' => $batch_size,
            'paged'          => 1,
            's'              => 'et_pb_', // Solo posts que contengan la firma de Divi
        ];

        $query       = new WP_Query($query_args);
        $total_posts = $query->found_posts;

        if ($total_posts === 0) {
            WP_CLI::success('No se encontraron posts con shortcodes de Divi.');
            return;
        }

        WP_CLI::log(sprintf('Se encontraron %d entradas con contenido de Divi.', $total_posts));
        $progress = WP_CLI\Utils\make_progress_bar('Procesando posts', $total_posts);

        $processed_count = 0;
        $pages_total     = (int) $query->max_num_pages;

        for ($paged = 1; $paged <= $pages_total; $paged++) {
            $query_args['paged'] = $paged;
            $posts               = get_posts($query_args);

            foreach ($posts as $post) {
                $original_content = $post->post_content;
                $cleaned_content  = $this->convert_divi_to_html($original_content);

                if ($original_content !== $cleaned_content) {
                    $processed_count++;

                    if (!$dry_run) {
                        // 1. Guardar revisión previa por seguridad
                        wp_save_post_revision($post->ID);

                        // 2. Actualizar contenido limpio
                        wp_update_post([
                            'ID'           => $post->ID,
                            'post_content' => $cleaned_content,
                        ]);

                        // 3. Eliminar meta residuales de Divi
                        $this->purge_divi_metadata($post->ID);
                    }

                    WP_CLI::debug(sprintf('Post ID %d ("%s") procesado.', $post->ID, $post->post_title), 'nvx-divi');
                }

                $progress->tick();
            }
        }

        $progress->finish();

        if ($dry_run) {
            WP_CLI::success(sprintf('[DRY-RUN] Se habrían transformado %d de %d posts.', $processed_count, $total_posts));
        } else {
            // Limpieza general de caché
            if (function_exists('sg_cachepress_purge_cache')) {
                sg_cachepress_purge_cache();
            }
            WP_CLI::success(sprintf('Operación completada: %d posts convertidos y metadatos borrados.', $processed_count));
        }
    }

    /**
     * Mapea y convierte la sintaxis de shortcodes Divi a estructura HTML5 limpia.
     */
    private function convert_divi_to_html(string $content): string {
        if (empty($content) || strpos($content, 'et_pb_') === false) {
            return $content;
        }

        // 1. Convertir Botones: [et_pb_button button_url="..." button_text="..." ...]
        $content = preg_replace_callback('/\[et_pb_button\s+[^\]]*button_url="([^"]+)"[^\]]*button_text="([^"]+)"[^\]]*\]/i', function($m) {
            return sprintf(
                '<a href="%s" class="inline-flex items-center justify-center px-6 py-3 text-sm font-semibold text-white bg-slate-900 hover:bg-slate-800 rounded-lg transition-colors my-4">%s</a>',
                esc_url($m[1]),
                esc_html($m[2])
            );
        }, $content);

        // 2. Convertir Imágenes: [et_pb_image src="..." alt="..." ...]
        $content = preg_replace_callback('/\[et_pb_image\s+[^\]]*src="([^"]+)"(?:\s+alt="([^"]*)")?[^\]]*\]/i', function($m) {
            $src = esc_url($m[1]);
            $alt = !empty($m[2]) ? esc_attr($m[2]) : '';
            return sprintf('<figure class="my-6"><img src="%s" alt="%s" class="w-full h-auto rounded-xl shadow-sm loading="lazy" /></figure>', $src, $alt);
        }, $content);

        // 3. Mapeo de Envolventes de Estructura (Sección, Fila, Columna, Bloque Texto)
        $replacements = [
            // Opening Tags
            '/\[et_pb_section[^\]]*\]/i' => '<section class="nvx-section py-10 md:py-16">',
            '/\[et_pb_row[^\]]*\]/i'     => '<div class="nvx-container mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 md:grid-cols-12 gap-8">',
            '/\[et_pb_column[^\]]*\]/i'  => '<div class="col-span-12">',
            '/\[et_pb_text[^\]]*\]/i'    => '<div class="nvx-prose prose prose-slate max-w-none">',
            
            // Closing Tags
            '/\[\/et_pb_section\]/i'     => '</section>',
            '/\[\/et_pb_row\]/i'         => '</div>',
            '/\[\/et_pb_column\]/i'      => '</div>',
            '/\[\/et_pb_text\]/i'        => '</div>',
        ];

        $content = preg_replace(array_keys($replacements), array_values($replacements), $content);

        // 4. Limpieza final: Eliminar cualquier otro shortcode et_pb_* no mapeado explícitamente
        $content = preg_replace('/\[\/?et_pb_[^\]]*\]/i', '', $content);

        // 5. Normalizar saltos de línea e higienizar párrafos
        $content = preg_replace("/\n\s*\n/", "\n\n", $content);

        return trim($content);
    }

    /**
     * Elimina los meta keys de base de datos creados por el motor de Divi.
     */
    private function purge_divi_metadata(int $post_id): void {
        $meta_keys = [
            '_et_builder_version',
            '_et_pb_use_builder',
            '_et_pb_old_content',
            '_et_pb_page_layout',
            '_et_pb_side_nav',
            '_et_pb_post_hide_nav',
            '_et_pb_show_title',
            '_et_pb_truncate_post',
            '_et_pb_built_for_post_type',
        ];

        foreach ($meta_keys as $key) {
            delete_post_meta($post_id, $key);
        }
    }
}

// Registrar el comando en WP-CLI
WP_CLI::add_command('nvx clean-divi', 'NVX_Divi_Cleaner_Command');
