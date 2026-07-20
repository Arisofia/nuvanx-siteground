---
name: nuvanx-theme-factory
description: Instrucciones y directivas para generar artefactos de marketing, presentaciones comerciales, PDFs y diseños de stands basados en el Design System "Metal Pulido" de NUVANX.
---

# NUVANX Theme Factory

Esta skill rige la creación de artefactos comerciales (decks, PDFs, stands) para NUVANX, garantizando que el sistema monotemático de producción web (Quiet Luxury) no se contamine, pero permitiendo variaciones controladas para el área comercial.

## 1. Tipografía
- **Playfair Display**: Obligatorio para títulos (H1-H3), portadas de presentaciones, nombres de clínicas y mensajes editoriales destacados.
- **Manrope**: Obligatorio para cuerpo de texto, UI funcional, botones, enumeraciones y datos técnicos.

## 2. Paleta Base "Metal Pulido"
Toda pieza de marketing debe asentar sus cimientos en la paleta oficial:
- **Light / Surface**: `#fcfbf8` (Principal), `#f8f7f4` (Soft).
- **Ink / Charcoal**: `#1a1a1a` (Texto), `#2b2926` (Títulos pesados).

## 3. Variantes de Acento (Uso Exclusivo en Marketing)
El repositorio de producción web no contiene estas variantes para mantener el minimalismo, pero en marketing (Stands, Presentaciones) se permiten las siguientes variantes controladas de acento para jerarquizar información comercial:
- **Acento Zafiro (Sapphire)**: `#1a365d` (Para credibilidad médica, data clínica, gráficos de eficacia).
- **Acento Oro Suave (Soft Gold)**: `#9a8a78` (Para resaltar invitaciones a la acción, "Call to actions" de alto nivel y membresías).

## 4. Principios de Handoff
Al generar un artefacto (como un PDF de showcase o un spec), siempre:
1. Extrae los tokens fuente de `wp-content/themes/nuvanx-medical/assets/css/nvx-tokens.css`.
2. Respeta el espaciado (escala de 8px).
3. Mantén la limpieza visual, alineada al concepto de "quiet luxury" (sin estridencias, colores vibrantes ni elementos ruidosos).
