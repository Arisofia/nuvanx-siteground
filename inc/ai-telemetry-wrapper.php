<?php
/**
 * NUVANX AI Telemetry & Agent Execution Wrapper
 *
 * Módulo de observabilidad y aislamiento para ejecuciones de IA/LLMs.
 * Implementa logging estructurado JSON, trazabilidad de latencia y consumo de tokens,
 * así como fallbacks graceful de interfaz de usuario.
 *
 * @package NUVANX_SiteGround
 * @version 1.0.0
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit; // Seguridad: Salida directa si se accede fuera del entorno WordPress.
}

/**
 * Clase NVX_AI_Telemetry_Logger
 * Gestiona el almacenamiento seguro y la estructuración de métricas y eventos de IA.
 */
final class NVX_AI_Telemetry_Logger {

    private static ?string $log_directory = null;

    /**
     * Obtiene o crea la ruta física segura para almacenar los logs JSON.
     */
    private static function get_log_dir(): string {
        if (self::$log_directory === null) {
            $upload_dir = wp_upload_dir();
            $dir = trailingslashit($upload_dir['basedir']) . 'nvx-telemetry-logs/';

            if (!file_exists($dir)) {
                wp_mkdir_p($dir);
                // Protección del directorio mediante .htaccess e index.php vacíos
                file_put_contents($dir . '.htaccess', "Deny from all\n");
                file_put_contents($dir . 'index.php', "<?php // Silence is golden.");
            }
            self::$log_directory = $dir;
        }
        return self::$log_directory;
    }

    /**
     * Registra una entrada de log JSON estructurado.
     *
     * @param string $level Nivel del log (INFO, WARNING, ERROR).
     * @param string $event Nombre del evento (ej. 'llm_call_success', 'llm_call_failure').
     * @param array<string, mixed> $context Contexto técnico de la llamada.
     */
    public static function log(string $level, string $event, array $context = []): void {
        $log_entry = [
            'timestamp'   => gmdate('Y-m-d\TH:i:s.v\Z'),
            'environment' => defined('WP_ENV') ? WP_ENV : (defined('WP_DEBUG') && WP_DEBUG ? 'development' : 'production'),
            'level'       => strtoupper($level),
            'event'       => $event,
            'trace_id'    => $context['trace_id'] ?? wp_generate_uuid4(),
            'tenant_id'   => $context['tenant_id'] ?? get_current_blog_id(),
            'user_id'     => $context['user_id'] ?? get_current_user_id(),
            'prompt_id'   => $context['prompt_id'] ?? 'unknown',
            'model'       => $context['model'] ?? 'unknown',
            'latency_ms'  => $context['latency_ms'] ?? 0.0,
            'tokens'      => [
                'prompt'     => $context['tokens']['prompt'] ?? 0,
                'completion' => $context['tokens']['completion'] ?? 0,
                'total'      => $context['tokens']['total'] ?? 0,
            ],
            'status'      => $context['status'] ?? 'UNKNOWN',
            'error'       => $context['error'] ?? null,
            'metadata'    => $context['metadata'] ?? [],
        ];

        $json_payload = wp_json_encode($log_entry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($json_payload !== false) {
            // Escribir en archivo rotativo diario
            $file_path = self::get_log_dir() . 'ai-telemetry-' . gmdate('Y-m-d') . '.json.log';
            error_log($json_payload . "\n", 3, $file_path);

            // Reenviar a error_log de WordPress si es un error o si WP_DEBUG está activo
            if ($level === 'ERROR' || (defined('WP_DEBUG') && WP_DEBUG)) {
                error_log(sprintf('[NUVANX AI TELEMETRY] [%s] %s: %s', $level, $event, $json_payload));
            }
        }
    }
}

/**
 * Clase NVX_AI_Agent_Wrapper
 * Envolvente para llamadas a APIs de LLM/Agentes con captura de excepciones y generación de Fallbacks.
 */
final class NVX_AI_Agent_Wrapper {

    /**
     * Ejecuta una función invocable de IA con seguimiento de telemetría y aislamiento de fallos.
     *
     * @param string   $prompt_id Identificador único del prompt o agente.
     * @param callable $callable  Función que realiza la solicitud a la API del modelo.
     * @param array    $payload   Parámetros de entrada para el modelo.
     * @param array    $options   Opciones de configuración (modelo, mensaje fallback, timeouts).
     * @return array   Respuesta estandarizada de ejecución.
     */
    public static function execute(string $prompt_id, callable $callable, array $payload = [], array $options = []): array {
        $trace_id   = wp_generate_uuid4();
        $start_time = microtime(true);
        $model      = $options['model'] ?? 'gpt-4o';

        $default_fallback_msg = __('El servicio de inteligencia artificial no está disponible en este momento. Por favor, reinténtalo o solicita asistencia directa con el equipo.', 'nvx');
        $fallback_message     = $options['fallback_message'] ?? $default_fallback_msg;

        try {
            // Ejecutar la integración con el Agente/LLM
            $result = call_user_func($callable, $payload, $options);
            $latency_ms = round((microtime(true) - $start_time) * 1000, 2);

            // Validar que el resultado tenga la estructura mínima esperada
            if (!is_array($result) || empty($result['success'])) {
                throw new \RuntimeException($result['error_message'] ?? __('Respuesta vacía o inválida del proveedor de IA.', 'nvx'));
            }

            // Extraer uso de tokens
            $tokens = [
                'prompt'     => $result['usage']['prompt_tokens'] ?? 0,
                'completion' => $result['usage']['completion_tokens'] ?? 0,
                'total'      => $result['usage']['total_tokens'] ?? 0,
            ];

            // Telemetría: Éxito
            NVX_AI_Telemetry_Logger::log('INFO', 'llm_call_success', [
                'trace_id'   => $trace_id,
                'prompt_id'  => $prompt_id,
                'model'      => $model,
                'latency_ms' => $latency_ms,
                'tokens'     => $tokens,
                'status'     => 'SUCCESS',
                'metadata'   => $result['metadata'] ?? [],
            ]);

            return [
                'success'    => true,
                'status'     => 'success',
                'data'       => $result['data'] ?? $result,
                'trace_id'   => $trace_id,
                'latency_ms' => $latency_ms,
                'usage'      => $tokens,
            ];

        } catch (\Throwable $exception) {
            $latency_ms = round((microtime(true) - $start_time) * 1000, 2);

            // Telemetría: Error / Fallback Activado
            NVX_AI_Telemetry_Logger::log('ERROR', 'llm_call_failure', [
                'trace_id'   => $trace_id,
                'prompt_id'  => $prompt_id,
                'model'      => $model,
                'latency_ms' => $latency_ms,
                'status'     => 'FALLBACK_TRIGGERED',
                'error'      => [
                    'class'   => get_class($exception),
                    'message' => $exception->getMessage(),
                    'code'    => $exception->getCode(),
                    'file'    => basename($exception->getFile()),
                    'line'    => $exception->getLine(),
                ],
            ]);

            // Estructura de respuesta de degradación elegante (Fallback)
            return [
                'success'     => false,
                'status'      => 'fallback',
                'trace_id'    => $trace_id,
                'latency_ms'  => $latency_ms,
                'error'       => $exception->getMessage(),
                'data'        => null,
                'fallback_ui' => [
                    'is_fallback' => true,
                    'title'       => __('Asistencia Temporalmente No Disponible', 'nvx'),
                    'message'     => $fallback_message,
                    'action_text' => __('Reintentar', 'nvx'),
                ],
            ];
        }
    }
}

/**
 * Helper global para ejecutar llamados a Agentes de IA con telemetría automática.
 *
 * @param string   $prompt_id Identificador del prompt.
 * @param callable $callable  Función de solicitud al modelo.
 * @param array    $payload   Parámetros del prompt.
 * @param array    $options   Configuraciones extra.
 * @return array
 */
function nvx_execute_ai_agent(string $prompt_id, callable $callable, array $payload = [], array $options = []): array {
    return NVX_AI_Agent_Wrapper::execute($prompt_id, $callable, $payload, $options);
}

/**
 * Helper global para renderizar el componente HTML de Fallback UI.
 *
 * @param array $fallback_data Array retornado por nvx_execute_ai_agent() en caso de fallo.
 * @param bool  $echo          Define si se imprime directamente o se retorna como string HTML.
 * @return string
 */
function nvx_render_ai_fallback_ui(array $fallback_data, bool $echo = true): string {
    $ui = $fallback_data['fallback_ui'] ?? [
        'title'       => __('Servicio no disponible', 'nvx'),
        'message'     => __('Inténtalo de nuevo más tarde.', 'nvx'),
        'action_text' => __('Reintentar', 'nvx'),
    ];

    $title       = esc_html($ui['title']);
    $message     = esc_html($ui['message']);
    $action_text = esc_html($ui['action_text']);
    $trace_id    = esc_attr($fallback_data['trace_id'] ?? '');

    $html = sprintf(
        '<div class="nvx-ai-fallback-card border border-rose-200 bg-rose-50/50 rounded-xl p-6 text-center max-w-lg mx-auto shadow-sm my-4" data-trace-id="%s">
            <div class="w-10 h-10 bg-rose-100 rounded-full flex items-center justify-center mx-auto mb-3 text-rose-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <h4 class="text-base font-semibold text-slate-900 mb-1">%s</h4>
            <p class="text-sm text-slate-600 mb-4">%s</p>
            <button type="button" onclick="window.location.reload()" class="inline-flex items-center px-4 py-2 text-xs font-medium text-white bg-slate-900 hover:bg-slate-800 rounded-lg transition-colors">
                %s
            </button>
            %s
        </div>',
        $trace_id,
        $title,
        $message,
        $action_text,
        $trace_id ? sprintf('<p class="text-[10px] text-slate-400 mt-3">%s: %s</p>', esc_html__('ID de Rastreo', 'nvx'), $trace_id) : ''
    );

    if ($echo) {
        echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    return $html;
}
