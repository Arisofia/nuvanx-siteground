# Componente: índice secuencial

## Uso

Procesos, métodos y pasos que necesitan un orden visible `01`, `02`, `03`.

## Estructura canónica

```html
<article class="nvx-index-item">
  <span class="nvx-index-number" aria-hidden="true">01</span>
  <h3 class="nvx-index-item__title">Diagnóstico antes de tecnología</h3>
  <p class="nvx-index-item__body">…</p>
</article>
```

El runtime también alinea las clases de proceso existentes (`__n` y counters CSS) con este mismo rol, para evitar una migración destructiva del contenido persistido.

## Tokens

- fuente: `--nvx-sans`;
- tamaño: `--nvx-index-number-size`;
- peso: `--nvx-index-number-weight`;
- tracking: `--nvx-index-number-track`;
- color: `--nvx-accent-muted`.

## Accesibilidad

- Usar `aria-hidden="true"` cuando el número sea decorativo o la estructura ya exprese el orden.
- No insertar `1.`, `2.`, `3.` dentro del H3.
- Para instrucciones cuyo orden sea semánticamente obligatorio, utilizar `<ol>`.

## No utilizar

- `.nvx-brand-card__kicker` para numerar.
- Bodoni grande para una secuencia de pasos.
- Estilos inline o escalas privadas por página.
- Ceros iniciales mezclados con números sin formato dentro del mismo patrón.
