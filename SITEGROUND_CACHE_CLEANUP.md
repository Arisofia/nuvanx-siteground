# Procedimiento de Limpieza de Caché SiteGround

## Problema Identificado

SiteGround Optimizer está activo en staging2.nuvanx.com y realiza las siguientes operaciones que pueden causar inconsistencias visuales:

1. **Combina y minifica archivos CSS y JS**
2. **Genera caché de páginas**
3. **Puede inyectar CSS adicional** (como `aspect-ratio: 1/1` en `.nvx-logo`)

## Cuando Limpiar la Caché

Siempre que hagas cambios visuales en el theme, debes limpiar la caché en este orden:

1. **Después de cambios en CSS/JS**
2. **Después de cambios en plantillas PHP**
3. **Después de actualizar plugins o WordPress**
4. **Si ves cambios que no se reflejan en el sitio**

## Procedimiento de Limpieza

### 1. Panel de Administración de WordPress

1. Accede a `https://staging2.nuvanx.com/wp-admin`
2. Navega a **SiteGround > Optimizer**
3. En la sección **Cache**, haz clic en **Purge Cache**
4. En la sección **CSS/JS Optimization**, haz clic en **Purge CSS/JS Cache**

### 2. Opciones de SG Optimizer

Desactiva temporalmente estas opciones para debugging:

**En SG Optimizer > Environment Optimization:**
- ❌ Desactivar "Cache"
- ❌ Desactivar "Minify CSS Files"
- ❌ Desactivar "Minify JavaScript Files"
- ❌ Desactivar "Combine CSS Files"
- ❌ Desactivar "Combine JavaScript Files"

**Luego haz pruebas:**
1. Limpia caché del navegador
2. Recarga la página
3. Verifica si los problemas visuales desaparecen

### 3. Revisión de Plugins

**En Plugins > Plugins instalados:**
- Desactiva temporalmente: SiteGround Optimizer
- Desactiva temporalmente: Complianz (si está activo)
- Limpia caché del navegador
- Verifica si los problemas desaparecen

## Diagnóstico de Problemas Específicos

### Recuadro "Cortado" en Header

**Causa:** SiteGround Optimizer puede inyectar CSS como:
```css
.nvx-logo {
  aspect-ratio: 1 / 1;
  background: var(--nvx-light);
}
```

**Solución:**
1. Desactiva SG Optimizer temporalmente
2. Si el problema desaparece, el problema es la caché de SG
3. Regenera la caché de SG después de tus cambios
4. Verifica que el problema no reaparezca

### Márgenes Inconsistentes

**Causa:** SG Optimizer puede estar sirviendo CSS antiguo con márgenes diferentes.

**Solución:**
1. Purge CSS Cache en SG Optimizer
2. Verifica que los cambios se reflejen
3. Si persiste, desactiva minificación de CSS temporalmente

### Solapamiento en Blogs

**Causa:** Header fijo + caché de CSS antiguo.

**Solución:**
1. Purge CSS Cache en SG Optimizer
2. Purge Page Cache en SG Optimizer
3. Limpia caché del navegador
4. Verifica que el padding-top aplicado en `.nvx-blog-single article` funcione

## Comandos de WP-CLI (si tienes acceso)

```bash
# Limpiar caché de SiteGround
wp sg optimizer purge

# Limpiar caché de CSS/JS
wp sg optimizer purge-css-js

# Desactivar optimización de CSS
wp sg optimizecss off

# Desactivar optimización de JS
wp sg optimizejs off
```

## Prevención

### Para Futuros Desarrollos

1. **Añade versioning a tus assets:**
   - WordPress ya lo hace automáticamente con `filemtime()`
   - Esto fuerza a SG a recargar el archivo cuando cambia

2. **Desarrolla con SG Optimizer desactivado:**
   - Verifica el diseño sin caché
   - Activa SG Optimizer solo al final
   - Limpia caché inmediatamente después

3. **Usa el modo "Auto" de SG Optimizer:**
   - Permite que SG gestione la caché inteligentemente
   - Limpia automáticamente cuando detecta cambios

## Verificación

Para verificar que la caché está limpia:

1. **Abre DevTools del navegador** (F12)
2. **Pestaña Network**
3. **Recarga la página**
4. **Verifica que los archivos CSS/JS tengan timestamps recientes**
5. **Verifica que no haya archivos minificados si desactivaste minificación**

## Contacto

Si los problemas persisten después de limpiar la caché:

1. Revisa el panel de administración de WordPress
2. Verifica qué plugins están activos
3. Revisa la configuración de SG Optimizer
4. Contacta a soporte de SiteGround si es necesario
