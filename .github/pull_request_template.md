## Checklist — cambios en functions.php / requires

- [ ] Si este PR elimina un `require_once`, se ha ejecutado
      `grep -rn "function_exists( '<símbolo>'" --include="*.php" .`
      para cada función definida en el archivo eliminado, y el resultado
      (sin consumidores) se pega abajo.
- [ ] Si este PR añade un `require_once`, se ha verificado en staging2
      que la ruta afectada renderiza el módulo esperado (no el fallback genérico).

## Descripción del PR

*(Añade aquí el contexto, capturas o resultados del grep si aplican)*
