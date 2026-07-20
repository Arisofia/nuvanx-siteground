# Numeraciones NUVANX

## Dos roles, no más

### 1. Secuencia editorial

Para procesos, métodos, pasos o índices:

```html
<span class="nvx-index-number" aria-hidden="true">01</span>
<h3>Diagnóstico antes de tecnología</h3>
```

Contrato:

- formato `01`, `02`, `03`;
- Manrope mediante `--nvx-sans`;
- tamaño, peso y tracking mediante `--nvx-index-number-*`;
- color `--nvx-accent-muted` en fondo claro;
- separado del título;
- `aria-hidden="true"` cuando el orden ya está expresado por la estructura o el texto.

Clases legacy de procesos (`__n` y counters) comparten el mismo rol mediante el cierre canónico de `nvx-visual-system.php`.

### 2. Métrica o cantidad

Cifras como estadísticas, precios o indicadores pueden utilizar **Playfair Display** y una escala visual mayor. No deben reutilizar `.nvx-index-number`, porque no representan orden.

## Listas editoriales

Los `<ol>` sin clase dentro de superficies de lectura conservan numeración decimal nativa. El reset global no debe ocultar el significado de una lista ordenada.

## Prohibido

- Escribir `1. Título` dentro del H3 para simular un índice.
- Utilizar una clase kicker para un número.
- Mezclar Playfair Display grande y Manrope pequeña para el mismo tipo de secuencia.
- Crear una numeración específica por tratamiento.
- Reintroducir Bodoni Moda o Cormorant Garamond para métricas.
