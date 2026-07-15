# Component: Media & Shape

### Description

Imágenes con **rol** (`nvx-media--*`) y **forma** opcional (`nvx-shape--*`). Sin `nth-child`.

### Roles

| Clase | Ratio token | Uso |
|-------|-------------|-----|
| `nvx-media--hero` | 16/9 | Hero |
| `nvx-media--editorial` | 3/2 | Intro narrativa |
| `nvx-media--treatment` | 16/9 | Tratamiento |
| `nvx-media--doctor` | 5/6 | Retrato |
| `nvx-media--clinic` | 19/12 | Clínica |
| `nvx-media--panorama` | 16/9 | Banda sedes |
| `nvx-media--contain` | — | Sin crop |

### Shapes

| Clase | Visual |
|-------|--------|
| `nvx-shape--none` | Recto (default editorial) |
| `nvx-shape--organic-a` | Blob A |
| `nvx-shape--organic-b` | Blob B |
| `nvx-shape--organic-c` | Soft capsule top |
| `nvx-shape--capsule` | Pill |

### Code Example

```html
<img
  class="nvx-media nvx-media--doctor nvx-shape--organic-b"
  src="…"
  alt="Dra. …"
>
```

### Do's and Don'ts

| ✅ Do | ❌ Don't |
|------|---------|
| Rol + shape en el markup | `nth-child` para máscaras |
| `object-fit: cover` en roles | Dimensiones arbitrarias por página |
