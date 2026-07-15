# Component: Header

### Description

Chrome sticky editorial. Markup: `#nvx-header` > `.nvx-header__inner` (logo + nav + CTA + hamburger).

### Tokens / layout

- width inner: `--nvx-shell` + gutter-inner  
- fondo: `--nvx-header-bg` (pearl glass)  
- acento underline nav: platinum  
- CTA: pill ink (chrome 1.9.3+)

### States

| State | Behavior |
|-------|----------|
| Sticky | `top: 0`, z-index alto |
| Mobile ≤1024 | nav/CTA ocultos, panel hamburger |
| Current item | underline gradient platinum |

### Accessibility

- Nav con lista de enlaces  
- Hamburger con label accesible  
- Focus visible en enlaces y CTA  

### Ownership

**Solo** `nvx-header.css`. No redefinir en page CSS.
