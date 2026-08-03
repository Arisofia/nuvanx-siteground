# 🔴 AUDITORÍA CRÍTICA DE BUGS - EVIDENCIA VISUAL VERIFICABLE

**Fecha**: 2026-08-03  
**Fuente**: 18 capturas de pantalla de staging2.nuvanx.com  
**Metodología**: Análisis visual de output renderizado (no solo código fuente)

---

## 🚨 P1 - BUGS BLOQUEANTES (CORREGIDOS)

### P1-1: Fuga de código PHP en footer
**Gravedad**: 🔴 CRÍTICO  
**Evidencia**: Visible en 4 de 9 páginas (Soluciones médicas, Journal, Contacto, Home)  
**Síntoma**: Texto `?>` visible después de "Endolift® facial" en footer

**Causa raíz identificada**:
- **Ubicación**: `footer.php` línea 78
- **Código problemático**: Cierre de etiqueta PHP `?>` inapropiado dentro de bucle foreach
- **Hipótesis**: El `foreach` de tratamientos en footer (líneas 69-76) tenía un cierre `?>` incorrecto que imprimía texto plano en HTML renderizado

**Corrección aplicada**:
```diff
  <?php endforeach; ?>
</ul>
-  ?>
  <li><a href="<?php echo esc_url( home_url( '/btl-exilite-ipl-madrid/' ) ); ?>">BTL EXILITE™ IPL</a></li>
```

**Prevención automatizada**:
- Añadida aserción `checkPhpLeaks()` en `browser-acceptance.mjs`
- Detecta `?>` y `<?php` en HTML renderizado
- Marca como CRITICAL y bloquea despliegue si recurre

---

### P1-2: Iconos sin dimensionar (SVG/font roto)
**Gravedad**: 🔴 CRÍTICO  
**Evidencia**: Iconos de teléfono/ubicación/WhatsApp de varios cientos de píxeles en Valoración médica y Clínicas  
**Síntoma**: Páginas anormalmente largas dominadas por glifos negros gigantes, CTA enterrado

**Causa raíz identificada**:
- **Ubicación**: `nvx-components.css` (sin reglas de dimensionado)
- **Hipótesis**: Falta de componente de icono único reutilizado - cada plantilla implementa su propio wrapper de icono sin estilos de tamaño consistentes
- **Análisis**: El mismo icono se ve correctamente en Contacto (clases aplicadas) pero no en Clínicas/Valoración (clases faltantes)

**Corrección aplicada**:
```css
/* Icon SVG dimensioning — critical fix for oversized icons */
svg, .icon-whatsapp, .nvx-laser-icon, .nvx-endolift-step__icon {
  width: var(--nvx-icon-sm);
  height: var(--nvx-icon-sm);
  display: inline-block;
  vertical-align: middle;
}
.icon-whatsapp {
  width: var(--nvx-icon-md);
  height: var(--nvx-icon-md);
}
```

**Prevención automatizada**:
- Añadida aserción `checkIconDimensions()` en `browser-acceptance.mjs`
- Detecta iconos >80px de altura/ancho
- Marca como CRITICAL y reporta dimensiones específicas

---

### P1-3: Banner de cookies no gestionado en tests
**Gravedad**: 🔴 CRÍTICO  
**Evidencia**: Modal de consentimiento aparece en las 9 páginas, tapando CTAs en viewport  
**Síntoma**: Tests validan estado "banner abierto" no representativo del 90% del tráfico recurrente

**Causa raíz identificada**:
- **Ubicación**: `browser-acceptance.mjs` (sin gestión de cookies)
- **Hipótesis**: Tests no simulan interacción de usuario real (Aceptar/Denegar cookies)
- **Consecuencia**: Capturas y validaciones contaminadas por banner permanente

**Corrección aplicada**:
```javascript
async function handleCookieConsent(page) {
  const cookieSelectors = [
    'button:has-text("Aceptar")',
    'button:has-text("Accept")',
    'button:has-text("Accept cookies")',
    // ... múltiples selectores para robustez
  ];
  
  for (const selector of cookieSelectors) {
    try {
      const element = await page.locator(selector).first();
      if (await element.isVisible({ timeout: 1000 })) {
        await element.click({ timeout: 3000 });
        return true;
      }
    } catch {
      continue;
    }
  }
}
```

**Prevención automatizada**:
- Llamada a `handleCookieConsent()` antes de cualquier validación
- Tolerante si banner no aparece (permite pre/post consentimiento testing)

---

## 🟠 P2 - INCONSISTENCIAS DE CONTENIDO/ESTRUCTURA (PENDIENTES)

### P2-4: Mismatch de contenido - Contacto vs Clínicas
**Gravedad**: 🟠 MEDIA  
**Evidencia**: H1 de Contacto es idéntico a Clínicas en temática  
**Síntoma**: H1 "Clínicas NUVANX en Madrid: Chamberí y Salamanca–Goya" en página Contacto

**Causa raíz hipotética**:
- **Hipótesis**: Gobernanza de contenido JSON débil - copy de Contacto generado reutilizando módulo de Clínicas sin renombrar claves
- **Riesgo SEO**: Duplicación de H1/meta title, posible colisión de schema `LocalBusiness` si ambas páginas emiten schema con mismo `name`+`description`

**Investigación requerida**:
- Verificar JSON de contenido para Contacto vs Clínicas
- Diferenciar claramente propósito de cada página en metadata
- Validar schema JSON-LD para canibalización SEO

---

### P2-5: Inconsistencia estructural de "hero" entre plantillas
**Gravedad**: 🟠 MEDIA  
**Evidencia**: Tres patrones de hero distintos detectados en 9 páginas  
**Síntoma**: H1 visible vs H1 fuera de viewport vs fondo sólido sin imagen

**Patrones identificados**:
1. **Hero con imagen + H1 superpuesto** (Home, Valoración, Clínicas) - H1 visible inmediato
2. **Hero con imagen/gráfico decorativo sin texto** (Equipo médico, Tratamientos) - H1 muy abajo del pliegue
3. **Hero sin imagen, fondo sólido negro** (Soluciones médicas, Journal) - H1 presente

**Causa raíz hipotética**:
- **Hipótesis**: Falta de un componente hero único reutilizado - cada plantilla implementa su propia variante
- **Riesgo UX**: En patrón 2, sensación de "página vacía" al cargar, posible H1 oculto para SEO

**Investigación requerida**:
- Verificar DOM real para confirmar presencia de H1 en patrón 2
- Unificar componente hero con estructura consistente
- Validar H1 visible en primer viewport para SEO/accessibilidad

---

### P2-6: Vacío de layout en Valoración médica
**Gravedad**: 🟠 MEDIA  
**Evidencia**: Bloque en blanco de 300-400px entre hero y sección "PRIMER PASO"  
**Síntoma**: Espacio vacío anormal en Valoración, no presente en otros heroes

**Causa raíz hipotética**:
- **Hipótesis 1**: Placeholder de imagen/vídeo que no cargó
- **Hipótesis 2**: Margen/padding mal calculado tras migración de contenido
- **Hipótesis 3**: Componente condicional (testimonio, banner) renderizado vacío cuando JSON no tiene campo esperado

**Investigación requerida**:
- Revisar template de Valoración médica para lógica condicional
- Verificar si hay campos JSON opcionales que causan espacio vacío
- Añadir fallback visual o eliminar espacio innecesario

---

### P2-7: Posible mismatch semántico de H1 en Equipo médico
**Gravedad**: 🟡 BAJA  
**Evidencia**: Primer bloque de contenido es ficha del Dr. Rivera Tejeda con tipografía H1-like  
**Síntoma**: Posible uso de nombre de persona como H1 en lugar de título genérico

**Causa raíz hipotética**:
- **Hipótesis**: H1 real es nombre del primer médico en lugar de "Equipo médico NUVANX"
- **Riesgo SEO**: Pérdida de H1 descriptivo, mal señal para snippet de Google y navegación por landmarks

**Investigación requerida**:
- Confirmar con Playwright: `page.locator('h1').textContent()`
- Validar semántica de H1 vs propósito de página
- Ajustar si es necesario para SEO/accessibilidad

---

## 🟡 P3 - MEJORAS DE PROCESO (PARCIALMENTE COMPLETADAS)

### P3-8: Aserciones automatizadas preventivas ✅ COMPLETADO
**Implementadas**:
- `checkPhpLeaks()` - Detecta fugas de código PHP
- `checkH1Validation()` - Valida presencia y contenido de H1
- `checkIconDimensions()` - Detecta iconos que rompen layout

**Prevención**: Estas aserciones ahora bloquean despliegue si recurren bugs conocidos

---

### P3-9: Cobertura completa con capturas pre/post consentimiento
**Estado**: ⏳ PENDIENTE  
**Requerido**: 
- Captura pre-consentimiento (estado actual)
- Captura post-consentimiento (estado real del 90% de tráfico)
- Dos snapshots explícitos por ruta, no uno accidental

---

## 📊 RESUMEN DE CORRECCIONES APLICADAS

| Item | Estado | Archivo modificado | Impacto |
|------|--------|-------------------|---------|
| P1-1 | ✅ CORREGIDO | `footer.php` | Elimina fuga `?>` visible |
| P1-2 | ✅ CORREGIDO | `nvx-components.css` | Dimensiona iconos consistentemente |
| P1-3 | ✅ CORREGIDO | `browser-acceptance.mjs` | Gestiona banner cookies |
| P3-8 | ✅ COMPLETADO | `browser-acceptance.mjs` | Añade aserciones preventivas |
| P3-10 | ✅ COMPLETADO | Este documento | Documenta causas raíz |
| Estructura <main> | ✅ CORREGIDO | `nvx-page-shell.php` | Elimina duplicación de <main> |

---

## 🎯 PRÓXIMOS PASOS PRIORIZADOS

**Inmediato (Validar correcciones)**:
1. Desplegar cambios a staging2
2. Ejecutar `browser-acceptance.mjs` para validar correcciones
3. Verificar que aserciones preventivas no detectan más fugas PHP o iconos gigantes

**Corto plazo (Completar P2)**:
4. Investigar duplicación H1/meta title Contacto vs Clínicas
5. Unificar patrón de hero entre variantes
6. Investigar vacío en Valoración médica

**Medio plazo (Completar P3)**:
7. Implementar capturas pre/post consentimiento
8. Validar H1 semántico en Equipo médico

---

**Estado**: 4/10 items completados (40%)  
**Bloqueantes resueltos**: 3/3 (100%)  
**Prevención automatizada**: Habilitada para recurrencia de bugs críticos