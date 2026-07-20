# Iconografía NUVANX

## Contrato único de presentación

La forma de un icono puede proceder de un helper de contenido existente, pero **color, tamaño y grosor** pertenecen a un único sistema:

- módulo runtime: `inc/nvx-visual-system.php`;
- escala: tokens `--nvx-icon-*`;
- trazo: `--nvx-icon-stroke`;
- color: `currentColor`.

## Escala

| Clase | Token | Uso |
|---|---|---|
| `.nvx-icon--xs` | `--nvx-icon-xs` · 16px | Datos de contacto y utilidades |
| `.nvx-icon--sm` | `--nvx-icon-sm` · 24px | Icono dentro de frame o botón |
| `.nvx-icon--md` | `--nvx-icon-md` · 32px | Métodos y pasos de tratamiento |
| `.nvx-icon--lg` | `--nvx-icon-lg` · 40px | Beneficios destacados |
| Frame | `--nvx-icon-frame` · 48px | Contenedor de icono editorial |

## SVG lineal

```html
<svg class="nvx-icon nvx-icon--md" viewBox="0 0 32 32" fill="none" aria-hidden="true">
  <!-- paths without hardcoded color -->
</svg>
```

Reglas:

1. `viewBox="0 0 32 32"` para nuevas formas.
2. `stroke: currentColor` y `stroke-width: var(--nvx-icon-stroke)`.
3. Sin hex, RGB o nombres de color dentro del SVG.
4. `aria-hidden="true"` para iconos decorativos.
5. El texto visible o `aria-label` aporta el nombre accesible.

Los iconos sólidos de marca, como WhatsApp, pueden usar `fill: currentColor`, pero comparten la misma escala.

## Colores por contexto

- Fondo claro: `--nvx-accent-muted`.
- Fondo oscuro: `--nvx-text-on-dark-72` o `--nvx-light` según contraste.
- Acciones primarias: heredan `currentColor` del botón.
- No se permite el antiguo dorado `#9A8A78`.

## Activos retirados

Los seis SVG de `assets/images/benefits/` fueron eliminados porque fijaban color y grosor. El normalizador reemplaza referencias heredadas por el registro inline canónico antes de enviar el documento público.

## Prohibido

- Crear una escala privada por página.
- Añadir `width` y `height` distintos sin una clase de escala.
- Referenciar símbolos `<use>` no definidos en el documento.
- Copiar un icono para cambiar únicamente su color.
