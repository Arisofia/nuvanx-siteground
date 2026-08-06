# Protocolo de Auditoría Forense de Producción — NUVANX SiteGround

> **Uso:** Pegar como addendum al prompt de cualquier agente auditor, antes de auditar
> este repo o el sitio nuvanx.com para producción. No asume stack, framework, CMS ni
> historial previo — todo eso se completa al inicio de cada auditoría, nunca se da por sabido.

---

## Principio Rector

El objetivo del agente **NO** es confirmar que el sistema está listo.  
El objetivo es **intentar demostrar que NO lo está.**

- **Presunción de fallo:** cada componente, página o módulo se asume defectuoso hasta que se demuestre lo contrario con evidencia verificable.
- **No aceptar** "se ve bien", "parece consistente" o "debería funcionar" sin comparación explícita, campo a campo, contra una referencia.
- **Solo cuando no se encuentre evidencia en contra** se puede marcar algo como Verificado.
- **No inferir** el alcance, el stack o el historial del proyecto: preguntarlo o descubrirlo activamente antes de auditar. Nunca asumir que "probablemente es como el ejemplo anterior".

---

## Estados Obligatorios (prohibido lenguaje ambiguo)

Toda afirmación del informe debe llevar una de estas etiquetas, **sin excepción**:

| Etiqueta | Significado |
|---|---|
| ✅ Verificado | Evidencia positiva directa, reproducible |
| ⚠️ Parcialmente verificado | Evidencia parcial o con supuestos menores declarados |
| ❌ Contradicho | Evidencia contraria encontrada |
| ❓ No verificable | Faltan datos o accesos para concluir |

**Prohibido:** "parece correcto", "todo indica que", "probablemente", "debería funcionar", "listo", "en general está bien".

---

## §0 — Descubrimiento de Alcance (paso obligatorio, antes de auditar nada)

No asumir el alcance. Determinarlo activamente:

- Inventario completo de páginas/módulos/entidades a auditar (no solo las conocidas — recorrer el repo/estructura completa).
- Stack real detectado (no el declarado): framework, CMS, lenguaje, sistema de build.
- ¿Existe una auditoría previa? Buscarla activamente en el repo/docs antes de asumir que esta es la primera.
- ¿Existe una referencia de diseño/especificación formal (design system, guía de estilo, mockup)? Si no existe, la referencia por defecto es la página/módulo más completo o representativo — **declárarlo explícitamente**.

**Tabla resultante obligatoria:**

| Página/módulo | Archivo/ruta | Auditada | Referencia de comparación usada |
|---|---|---|---|

---

## §1 — Auditoría de Consistencia Inter-Elementos

Para cada página/módulo distinto de la referencia elegida en §0, comparar campo a campo:

```
Elemento: [nombre]
  ↓ Tipografía → fuente/peso/tamaño real vs referencia → ✅/❌
  ↓ Espaciados → sistema de espaciado usado vs referencia → ✅/❌
  ↓ Color → variables/tokens usados vs valores hardcoded → ✅/❌
  ↓ Componentes → misma versión de componente que la referencia? variante distinta? → ✅/❌
  ↓ Estructura compartida (header/footer/nav/layout) → markup y estilos idénticos? → ✅/❌
  ↓ Estados/efectos (hover, loading, transiciones) → aplicados o ausentes → ✅/❌
```

> No aceptar "usa el mismo componente/include" como evidencia de consistencia — verificar
> que el CSS/estilos resueltos y las variables aplicadas son las mismas, no solo que el archivo se referencia.

**Tabla resumen obligatoria:**

| Elemento | Tipografía | Espaciados | Color | Componentes | Estructura compartida | Veredicto |
|---|---|---|---|---|---|---|

---

## §2 — Auditoría de Dependencias

Cada import/require/include/referencia entre archivos debe rastrearse de punta a punta, no darse por hecho.

```
punto de entrada → dependencia 1 → dependencia 2 → archivo final renderizado
```

- Si el rastro no se completa: **DEPENDENCIA NO AUDITADA** — no se asume que funciona.
- Si dos elementos deberían compartir una dependencia y no lo hacen, o la comparten con versiones distintas: es un **hallazgo**, no un detalle menor.

---

## §3 — Auditoría de Hooks/Integraciones (si el stack los tiene)

Si el sistema tiene hooks, eventos, middlewares o interceptores (WordPress hooks, event listeners, middleware chains, etc.), mapear:

- Orden/prioridad de ejecución real.
- Prioridades duplicadas o en conflicto.
- Diferencias de registro entre la referencia y el resto de elementos.

> Si el stack no usa este patrón, declarar explícitamente **"No aplica — stack sin mecanismo de hooks"**.

---

## §4 — Auditoría de Metadatos/SEO por Elemento (sitios públicos)

Para cada página pública verificar:
`title` · `meta description` · `canonical` · `robots` · `schema/structured data` · `OG` · `Twitter cards` · `JSON-LD`

Marcar duplicados entre páginas y campos presentes en la referencia pero ausentes en el resto.

**Evidencia mínima:** output literal de `curl -sI <url>` y el fragmento `<head>` renderizado.

---

## §5 — Contradicción contra Auditorías Previas

Si §0 encontró una auditoría previa: para cada hallazgo reportado antes, verificar su estado actual **releyendo el archivo**, no asumiendo que sigue igual ni que se corrigió.

| Hallazgo previo | Estado actual verificado | Evidencia |
|---|---|---|

> No reportar como "nuevo" un hallazgo ya conocido sin marcarlo como **regresión** si reapareció.

---

## §6 — Bloqueantes de Contexto

Antes de emitir veredicto, preguntar o descubrir activamente si existen bloqueantes conocidos fuera del código:

- Credenciales pendientes de rotar
- Flags de no-indexación activos (`x-robots-tag: noindex`, `NVX_ENV` no definido)
- Features a medio desplegar
- Incidentes de seguridad abiertos

> Si existe alguno sin resolver, el veredicto es **NO GO** independientemente del resto del audit.

**Bloqueante activo documentado (2026-08-06):**  
`x-robots-tag: noindex, nofollow` activo en `https://nuvanx.com` — causa probable: `NVX_ENV` no definido como `'production'` en `wp-config.php` del host SiteGround. Estado: ❓ No verificable (requiere acceso al `wp-config.php` de producción).

---

## §7 — Evidencia Mínima por Hallazgo

Cada hallazgo debe incluir **obligatoriamente**:

| Campo | Descripción |
|---|---|
| Archivo | Ruta completa en el repo |
| Línea | Número de línea exacto |
| Código/fragmento | Extracto literal del código afectado |
| Causa | Explicación técnica causal |
| Impacto | Consecuencia observable |
| Cómo reproducir | Pasos exactos o comando literal |
| Cómo verificar la corrección | Qué cambio y qué evidencia confirmaría la corrección |
| Severidad | Bloqueante / Alto / Medio / Bajo |

> Si falta alguno de estos campos: **no se reporta el hallazgo todavía**, se marca ❓ No verificable
> y se indica qué falta para completarlo.

---

## §8 — Veredicto Final

```
¿Hay evidencia suficiente para declarar Production Ready? SI / NO
```

**Prohibido** declarar "Production Ready", "GO" o "Sin incidencias" salvo que se cumplan **TODAS**:

1. 100% del inventario de §0 auditado (o el % real declarado, nunca redondeado hacia arriba).
2. Todos los elementos homologados contra la referencia en los ejes de §1, o discrepancias documentadas como decisión intencional.
3. Dependencias e integraciones rastreadas de punta a punta, sin **DEPENDENCIA NO AUDITADA** pendientes.
4. Bloqueantes de contexto (§6) resueltos o inexistentes, **confirmado activamente, no asumido**.
5. Ningún hallazgo Bloqueante o Alto abierto.
6. Todo hallazgo de auditorías previas reconciliado (corregido, regresión, o sigue abierto — verificado, no asumido).

Si falta cualquiera de las condiciones, el veredicto debe ser:
- **NO GO** — con lista exacta de bloqueantes
- **GO CON RESERVAS** — listando reservas exactas
- **AUDITORÍA INCOMPLETA** — indicando cobertura real y qué falta

> Nunca GO por ausencia de evidencia en contra — solo por **evidencia positiva demostrada**.

---

## Notas de Stack — NUVANX SiteGround

- **CMS:** WordPress (Yoast SEO v28.2 activo en producción)
- **Tema:** `nuvanx-medical` (custom)
- **Entorno de producción:** `https://nuvanx.com` (SiteGround)
- **Entorno de staging:** `https://staging2.nuvanx.com`
- **Control de entorno:** Constante `NVX_ENV` en `wp-config.php` — valor requerido en producción: `'production'`
- **Rutas canónicas:** definidas en `wp-content/themes/nuvanx-medical/inc/data/routes.json`
- **Script de auditoría:** `audit/audit_comprehensive.py` (configurado vía `AUDIT_OUT_DIR`, `AUDIT_VERIFY_SSL`, `AUDIT_TRUST_ENV`)
