# 🔍 Investigación FacebookSignal Persistente

## Problema
FacebookSignal sigue apareciendo en HTML de staging2 a pesar de las medidas de desactivación implementadas en `nvx-integrations.php`.

## Medidas Implementadas Actuales

### 1. Desactivación de Plugins (nvx-integrations.php líneas 163-202)
```php
function nvx_theme_disable_public_facebook_pixel( $plugins ) {
    // Desactiva plugins de Facebook en front-end público
    // Solo mantiene activo en wp-admin/CLI
}
add_filter( 'option_active_plugins', 'nvx_theme_disable_public_facebook_pixel', 1 );
add_filter( 'site_option_active_sitewide_plugins', 'nvx_theme_disable_public_facebook_pixel', 1 );
```

### 2. Dequeue de Scripts (nvx-integrations.php líneas 223-226)
```php
wp_dequeue_script( 'siteground-facebook-signal' );
wp_deregister_script( 'siteground-facebook-signal' );
wp_dequeue_script( 'facebook-for-wordpress-pixel' );
wp_deregister_script( 'facebook-for-wordpress-pixel' );
```

### 3. Script Loader Tag Hard-Block (nvx-integrations.php líneas 240-246)
```php
if (
    str_contains( $handle, 'facebook-signal' )
    || str_contains( $handle, 'facebook-for-wordpress' )
    || str_contains( $tag, 'facebook-signal' )
    || str_contains( $tag, 'FacebookSignal' )
) {
    return '';
}
```

## Por Qué Persiste FacebookSignal

### Causa Raíz Identificada
**SiteGround Optimizer** opera a nivel de optimización de servidor/buffer, no via WordPress wp_enqueue_scripts.

**Evidencia**:
- `header.php` línea 3: "SiteGround Optimizer + Complianz own the front-end buffer stack"
- `nvx-document-governance.php` línea 5: "Full-document rewrite buffers were retired: SiteGround Optimizer + Complianz + core already own the front-end buffer stack"

**Explicación**:
- SiteGround Optimizer inyecta FacebookSignal directamente en el HTML optimizado
- El dequeue de wp_enqueue_scripts no afecta scripts inyectados a nivel de buffer
- La desactivación de plugins puede no afectar optimizaciones ya cacheadas

## Soluciones Propuestas

### Opción 1: Desactivar SiteGround Optimizer en Staging2
**Ventajas**:
- Elimina el problema en la raíz
- Más limpio y predecible

**Desventajas**:
- Perdemos optimizaciones de SiteGround en staging2
- Requiere acceso administrativo a staging2

**Implementación**:
- Desactivar plugin "SiteGround Optimizer" en WordPress admin de staging2
- O desactivar via configuración de SiteGround

### Opción 2: Filtro de Salida HTML para Eliminar FacebookSignal
**Ventajas**:
- No requiere acceso administrativo
- Funciona independientemente de cómo se inyecte FacebookSignal

**Desventajas**:
- Solución parche, no ataca la raíz
- Añade overhead de procesamiento de HTML

**Implementación**:
```php
add_filter(
    'template_redirect',
    function () {
        if ( is_admin() ) {
            return;
        }
        
        ob_start( function( $buffer ) {
            // Eliminar FacebookSignal del HTML final
            $buffer = preg_replace( '/<script[^>]*facebook[^>]*>.*?<\/script>/is', '', $buffer );
            $buffer = preg_replace( '/<noscript[^>]*>.*?<\/noscript>/is', '', $buffer );
            return $buffer;
        } );
    },
    999999
);
```

### Opción 3: Configuración de SiteGround Optimizer
**Ventajas**:
- Mantiene optimizaciones de SiteGround
- Solución nativa del plugin

**Desventajas**:
- Requiere acceso a configuración de SiteGround
- Puede no tener opción para desactivar FacebookSignal específicamente

**Implementación**:
- Configurar SiteGround Optimizer para excluir Facebook Pixel
- O desactivar específicamente la optimización de scripts de Facebook

## Recomendación

**Prioridad 1**: Opción 1 (Desactivar SiteGround Optimizer en Staging2)
- Más limpio y predecible
- Elimina el problema en la raíz
- Staging2 no necesita las mismas optimizaciones que producción

**Prioridad 2**: Opción 2 (Filtro de Salida HTML)
- Si no se puede desactivar SiteGround Optimizer
- Solución parche que funciona independientemente de la fuente

**Prioridad 3**: Opción 3 (Configuración SiteGround)
- Si se quiere mantener SiteGround Optimizer
- Requiere investigación de opciones específicas del plugin

## Próximos Pasos

1. Verificar si SiteGround Optimizer está activo en staging2
2. Si está activo, proceder con Opción 1 (desactivación)
3. Si no se puede desactivar, implementar Opción 2 (filtro de salida)
4. Revalidar tests de aceptación después de la corrección