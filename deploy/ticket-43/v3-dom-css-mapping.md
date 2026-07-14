# Ticket 43 — DOM / CSS Mapping (V3 Full-Home)

| DOM (selector principal) | CSS block | Comportamiento |
|--------------------------|-----------|----------------|
| `#nvx-home-main.nvx-editorial-home-v3` | `:root` tokens `--nvx-v3-shell`, `--nvx-v3-ivory`, `--nvx-v3-ink` | Shell 1360px, paleta editorial |
| `.nvx-v3-shell` | `width: var(--nvx-v3-shell)` | Alineación global de secciones |
| `.nvx-home-hero-stage` | padding, fondo `#F4F2EE` | Hero full-width con onda inferior |
| `.nvx-brand-hero__inner` | flex 44/56 hasta 900px | Hero lateral desktop |
| `.nvx-brand-hero__line--main/sub/support` | 3 escalas tipográficas | H1 producción en 3 niveles |
| `.nvx-home-video-frame` | `clip-path` orgánico asimétrico | Máscara vídeo hero-12s-720p |
| `.nvx-home-organic-wave--asymmetric` | SVG path asimétrico | Transición hero → intro |
| `.nvx-v3-intro .nvx-home-editorial-open` | grid 2 columnas | Introducción editorial abierta |
| `.nvx-home-clinicas-panorama__track` | grid 2 cols, borde fino | Franja panorámica clínicas |
| `.nvx-home-tratamientos-row--4` | `repeat(4,1fr)` | Fila editorial tratamientos |
| `.nvx-home-tratamientos-row--3` | `repeat(3,1fr)` | Segunda fila tratamientos |
| `.nvx-home-tratamiento-item` | sin fondo/sombra/radius | Cards → ítems editoriales |
| `.nvx-home-metodo-pilares` | grid 3 cols, divisores | Pilares método abiertos |
| `.nvx-home-direccion-grid` | grid 3 zonas | Retrato + liderazgo + registros |
| `.nvx-home-faq-editorial .nvx-brand-faq-item` | `border-bottom` fino | Acordeón FAQ editorial |
| `.nvx-home-cta-final-band` | fondo `#161511`, `border-radius` amplio | CTA final banda negra |
| `#nvx-header` (body.home) | absolute, transparente | Header sobre hero ivory |
| `@media max-width: 900px` | hero column | Fin hero lateral |
| `@media max-width: 390px` | H1 `min(2.75rem, 44px)` | Límite móvil H1 |