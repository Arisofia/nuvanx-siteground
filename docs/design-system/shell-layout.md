# Component: Shell & Section layout

### Description

Contrato de contenedor y ritmo vertical para **todas** las páginas.

### Classes

| Clase | Rol |
|-------|-----|
| `.nvx-shell`, `.nvx-site-shell`, `.nvx-page__shell`, … | width = `--nvx-shell`, padding-inline = gutter-inner |
| `.nvx-section`, `.nvx-brand-section` | padding-block = `--nvx-section-y` |
| `.nvx-brand-section--tight` | section-y-tight |
| `.nvx-brand-grid--2` / `--3` | grids con gap-wide |
| `.nvx-brand-section__inner` | shell interno |

### Tokens used

- `--nvx-shell`, `--nvx-gutter`, `--nvx-gutter-inner`
- `--nvx-section-y`, `--nvx-gap-wide`

### Archivo

`nvx-site-layout.css` (+ tokens)

### Do's and Don'ts

| ✅ Do | ❌ Don't |
|------|---------|
| Usar shell token | `max-width: 1360px` hardcode por página |
| Section-y token | padding 48px mágico |
