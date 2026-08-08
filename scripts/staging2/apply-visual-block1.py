#!/usr/bin/env python3
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
THEME = ROOT / 'wp-content/themes/nuvanx-medical'

signature = THEME / 'inc/nvx-signature-phase-pages.php'
patterns = THEME / 'assets/css/nvx-patterns-editorial.css'
layout = THEME / 'assets/css/nvx-site-layout.css'

# 1) Restore the two governed Signature hubs that were intentionally implemented in
# f5006462, then accidentally removed from the catalog in de85abe2 while the current
# centralized injector and the contour/post-maternity render branches remained.
s = signature.read_text('utf-8')
if "'contour-architecture' => array(" in s or "'post-maternity'       => array(" in s:
    raise SystemExit('Signature hub entries already present; refusing duplicate patch')
needle = """\t\t'signature-index'      => array(\n\t\t\t'slug'      => 'protocolos-signature',\n\t\t\t'marker'    => 'NUVANX_PROTOCOL_HUB',\n\t\t\t'kind'      => 'index',\n\t\t\t'kicker'    => 'NUVANX · Protocolos Signature · Madrid',\n\t\t\t'h1'        => 'Protocolos Signature: medicina estética de diagnóstico.',\n\t\t\t'lead'      => 'Cada protocolo organiza la decisión clínica alrededor de un objetivo. No son paquetes cerrados ni combinaciones automáticas: la tecnología se elige después de valorar anatomía, tejido y expectativas.',\n\t\t\t'intro'     => 'Los Protocolos Signature conectan diagnóstico, modalidad y seguimiento. El nombre del protocolo ordena la conversación; la indicación, el número de sesiones, la recuperación y el presupuesto se confirman en consulta.',\n\t\t\t'seo_title' => 'Protocolos Signature Madrid | NUVANX',\n\t\t\t'seo_desc'  => 'Protocolos Signature NUVANX en Madrid: rutas clínicas de diagnóstico para contorno, calidad de piel, textura, tono y perfil facial.',\n\t\t),\n"""
if needle not in s:
    raise SystemExit('Signature index catalog block changed; refusing fuzzy patch')
restore = needle + """\t\t'contour-architecture' => array(\n\t\t\t'slug'      => 'remodelacion-corporal-laser-madrid',\n\t\t\t'marker'    => 'NUVANX_PROTOCOL_PAGE:contour-architecture',\n\t\t\t'kind'      => 'contour',\n\t\t\t'kicker'    => $contour,\n\t\t\t'h1'        => 'Remodelación corporal láser diseñada según tu anatomía.',\n\t\t\t'lead'      => $short . ' evalúa grasa localizada, laxitud y continuidad entre zonas antes de indicar una tecnología. El plan se diseña por anatomía, no por una lista de aparatos.',\n\t\t\t'intro'     => 'Abdomen, flancos, brazos, espalda, muslos, rodillas o contorno masculino pueden formar parte del mismo marco de decisión. Cada zona se presupuesta solo si tiene indicación documentada tras la exploración.',\n\t\t\t'seo_title' => 'Remodelación corporal láser Madrid | ' . $short,\n\t\t\t'seo_desc'  => 'Remodelación corporal láser en Madrid con ' . $contour . ': valoración por zonas de grasa, laxitud y continuidad anatómica.',\n\t\t),\n\t\t'post-maternity'       => array(\n\t\t\t'slug'      => 'tratamiento-postparto-abdomen-contorno-corporal-madrid',\n\t\t\t'marker'    => 'NUVANX_PROTOCOL_PAGE:post-maternity',\n\t\t\t'kind'      => 'post-maternity',\n\t\t\t'kicker'    => 'NUVANX Post-Maternity Contour™',\n\t\t\t'h1'        => 'Tratamiento postparto: abdomen y contorno corporal en Madrid.',\n\t\t\t'lead'      => 'Lectura respetuosa de abdomen, flancos y calidad del tejido después del embarazo. Se separa grasa subcutánea, laxitud, diástasis y expectativas realistas antes de proponer cualquier modalidad.',\n\t\t\t'intro'     => 'El postparto no es un protocolo estándar. La valoración considera lactancia, tiempo desde el parto, pared abdominal, cicatrices y disponibilidad de recuperación. Si no hay indicación proporcionada, se explica la alternativa o la espera.',\n\t\t\t'seo_title' => 'Tratamiento postparto abdomen y contorno Madrid | NUVANX',\n\t\t\t'seo_desc'  => 'Valoración postparto de abdomen y contorno corporal en Madrid: grasa, laxitud y pared abdominal con criterio clínico antes de indicar tratamiento.',\n\t\t),\n"""
s = s.replace(needle, restore, 1)
signature.write_text(s, 'utf-8')

# 2) Restore the canonical dark surface for the canonical brand hero. The existing
# hero typography is globally defined as on-dark; transparent background is what
# produced white-on-off-white on raw CMS brand heroes.
p = patterns.read_text('utf-8')
hero_anchor = """/* Home: video hero especial con altura completa */\nbody.home .nvx-home-hero {\n"""
hero_rule = """/* Canonical interior brand hero surface.\n   The copy contract below uses --nvx-light / --nvx-text-on-dark-90. */\n.nvx-brand-hero {\n  background: var(--nvx-ink);\n  color: var(--nvx-light);\n}\n\n"""
if hero_rule in p:
    raise SystemExit('Canonical brand hero rule already present')
if hero_anchor not in p:
    raise SystemExit('Hero anchor changed; refusing fuzzy patch')
p = p.replace(hero_anchor, hero_rule + hero_anchor, 1)
patterns.write_text(p, 'utf-8')

# 3) Add two missing layout contracts demonstrated by full-page screenshots:
#    a) authority strategy page must not constrain the whole document to 68ch;
#    b) legal Gutenberg documents already carry semantic classes but had no theme CSS.
l = layout.read_text('utf-8')
marker = '/* === NUVANX VISUAL FORENSIC BLOCK 1 === */'
if marker in l:
    raise SystemExit('Block 1 layout patch already present')
block = r'''

/* === NUVANX VISUAL FORENSIC BLOCK 1 === */
/* Authority strategy page: full document shell, measured copy inside it.
   Investment/review strategy pages already opt into .nvx-shell and are excluded. */
.nvx-strategy-page.nvx-brand-readable:not(.nvx-shell) {
  width: 100%;
  max-width: none;
  margin: 0;
}

.nvx-strategy-page:not(.nvx-shell) > .nvx-brand-hero {
  width: 100vw;
  max-width: 100vw;
  margin-inline: calc(50% - 50vw);
  background: var(--nvx-ink);
}

.nvx-strategy-page:not(.nvx-shell) > .nvx-brand-section {
  width: var(--nvx-shell);
  max-width: 100%;
  margin-inline: auto;
  padding-block: var(--nvx-pad-section);
  padding-inline: var(--nvx-gutter-inner);
  box-sizing: border-box;
}

.nvx-strategy-page:not(.nvx-shell) > .nvx-brand-section > :where(h2, h3, p, ul, ol) {
  max-width: var(--nvx-measure);
}

/* Legal documents: the CMS already supplies nvx-blog-article + nvx-legal-* classes.
   Give them an explicit editorial shell instead of leaving the 68ch wrapper pinned
   to the left edge of a 1240px desktop canvas. */
.nvx-page__content > .nvx-blog-article.nvx-brand-readable {
  width: min(980px, calc(100vw - var(--nvx-gutter)));
  max-width: min(980px, calc(100vw - var(--nvx-gutter)));
  margin-inline: auto;
  padding-block: clamp(48px, 6vw, 88px) var(--nvx-pad-section);
  box-sizing: border-box;
}

.nvx-page__content > .nvx-blog-article.nvx-brand-readable > .nvx-legal-page {
  width: 100%;
  max-width: none;
  margin: 0;
  padding: 0;
  background: transparent;
}

.nvx-blog-article.nvx-brand-readable > :where(.nvx-legal-page, h1, p) {
  margin-inline: auto;
}

.nvx-legal-page > h1,
.nvx-blog-article.nvx-brand-readable > h1 {
  max-width: 18ch;
  margin-top: 0;
  margin-bottom: var(--nvx-space-6);
  font-family: var(--nvx-serif);
  font-size: clamp(40px, 5vw, 64px);
  font-weight: 400;
  line-height: 1.06;
  color: var(--nvx-ink);
  text-wrap: balance;
}

.nvx-legal-page > .nvx-legal-card {
  width: 100%;
  max-width: 860px;
  margin: var(--nvx-space-6) auto 0;
  padding: clamp(28px, 4vw, 52px);
  border: 1px solid var(--nvx-color-line);
  border-radius: var(--nvx-radius-card);
  background: var(--nvx-light);
  box-sizing: border-box;
}

.nvx-legal-card :where(p, li) {
  font-size: 16px;
  line-height: 1.75;
  color: var(--nvx-text-body);
}

.nvx-legal-card :where(ul, ol) {
  padding-left: 1.35em;
}

.nvx-legal-card h2 {
  max-width: 24ch;
  margin-top: var(--nvx-space-7);
  margin-bottom: var(--nvx-space-3);
  font-family: var(--nvx-serif);
  font-size: clamp(26px, 3vw, 36px);
  font-weight: 400;
  line-height: 1.16;
  color: var(--nvx-ink);
  text-wrap: balance;
}

.nvx-legal-card h2:first-child {
  margin-top: 0;
}

.nvx-page__content > .nvx-blog-article.nvx-brand-readable + p {
  width: min(980px, calc(100vw - var(--nvx-gutter)));
  max-width: min(980px, calc(100vw - var(--nvx-gutter)));
  margin: calc(-1 * var(--nvx-space-6)) auto var(--nvx-pad-section);
  color: var(--nvx-text-muted);
}

@media (max-width: 600px) {
  .nvx-page__content > .nvx-blog-article.nvx-brand-readable,
  .nvx-page__content > .nvx-blog-article.nvx-brand-readable + p {
    width: calc(100vw - 32px);
    max-width: calc(100vw - 32px);
  }

  .nvx-page__content > .nvx-blog-article.nvx-brand-readable {
    padding-block: var(--nvx-space-7) var(--nvx-pad-section-tight);
  }

  .nvx-legal-page > h1,
  .nvx-blog-article.nvx-brand-readable > h1 {
    font-size: clamp(34px, 11vw, 46px);
  }

  .nvx-legal-page > .nvx-legal-card {
    padding: 22px 20px;
    border-radius: 16px;
  }
}
'''
layout.write_text(l.rstrip() + block + '\n', 'utf-8')

print('VISUAL_BLOCK1_PATCH=PASS')
