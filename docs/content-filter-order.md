# the_content Filter Execution Order

El tema registra más de 40 filtros sobre `the_content` en `inc/`. Muchos comparten la misma prioridad (`priority: 99`, `19-21`), por lo que su orden de ejecución depende implícitamente del orden en que los archivos son incluidos mediante `require_once` en `functions.php` y `nvx-integrations.php`.

Esta deuda técnica produce colisiones silenciadas o "textos erráticos", ya que el último filtro en ejecutarse sobrescribe las reglas de negocio anteriores, y una falla en `preg_replace` (vía `nvx_content_preg_replace_keep`) no emite error.

## Orden de Inclusión (Priority 99)

Actualmente, las funciones enganchadas a `the_content` con prioridad 99 se ejecutan en este orden estricto:

1. **`nvx_apply_production_business_rules`** (`inc/nvx-page-hygiene.php`)
   - Enganchado vía `inc/nvx-integrations.php`
2. **`nvx_apply_p0_business_rules`** (`inc/nvx-p0-publication-guard.php`)
   - Enganchado vía `inc/nvx-integrations.php`
3. **`nvx_btl_clinical_governance`** (`inc/nvx-btl-clinical-governance.php`)
   - Enganchado vía `inc/nvx-integrations.php`
4. **`nvx_content_strip_page_closing_ctas_late`** (`inc/nvx-content-presentation.php`)
   - Enganchado directamente desde `functions.php` (después de cargar `nvx-integrations.php`)

> **Nota:** La función `nvx_content_strip_page_closing_ctas_late` **gana siempre** sobre las demás en `priority 99` al estar al final de la carga.

## Recomendaciones
- No alterar el orden de los `require_once` sin probar exhaustivamente las páginas afectadas.
- A medio plazo: consolidar `nvx_apply_production_business_rules`, `nvx_apply_p0_business_rules` y `nvx_btl_clinical_governance` en un único pipeline con pasos explícitos.
