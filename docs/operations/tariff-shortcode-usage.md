# Tariff Shortcode Usage Guide

## Overview

The `[nvx_tariff]` shortcode renders prices directly from the canonical tariff catalog SSOT (`tariff-catalog.json`). This prevents hardcoded prices in WordPress posts and ensures all pricing remains synchronized with the single source of truth.

## Syntax

```php
[nvx_tariff key="group.subkey"]
```

## Examples

### Laser CO₂ Prices
```php
[nvx_tariff key="laser_co2.facial"]  // Renders: 330 €
[nvx_tariff key="laser_co2.corporal"] // Renders: 450 €
```

### EXION Prices
```php
[nvx_tariff key="exion.exion_face_sesion"]    // Renders: 395 €
[nvx_tariff key="exion.exion_body_sesion"]    // Renders: 445 €
[nvx_tariff key="exion.exion_fractional_cara"] // Renders: 595 €
```

### Endolift Prices
```php
[nvx_tariff key="endolift.papada"]              // Renders: 1.064,80 €
[nvx_tariff key="endolift.abdomen"]            // Renders: 1.694,00 €
[nvx_tariff key="endolift.rodillas"]           // Renders: 1.197,90 €
```

### Aesthetic Treatment Prices
```php
[nvx_tariff key="labios_ha.perfilado_leve"]    // Renders: 498 €
[nvx_tariff key="ojeras_ha.surco_lagrimal"]    // Renders: 448 €
[nvx_tariff key="bioestimuladores.polynucleotides_cara"] // Renders: 348 €
```

## Available Groups

- `endolift` - Endolift facial and body zones
- `endolift_combo` - Combined Endolift zones
- `laser_co2` - Fractional CO₂ laser sessions
- `labios_ha` - Hyaluronic acid lip treatments
- `ojeras_ha` - Tear trough treatments
- `rinomodelacion_ha` - Non-surgical rhinoplasty
- `bioestimuladores` - Biostimulator treatments
- `neuromoduladores` - Neuromodulator treatments
- `exion` - EXION BTL treatments
- `btl_exilite` - BTL EXILITE IPL treatments

## Usage in Content

**Correct:**
```html
<p>El precio de una sesión de Láser CO₂ facial es de [nvx_tariff key="laser_co2.facial"].</p>
```

**Incorrect (hardcoded):**
```html
<p>El precio de una sesión de Láser CO₂ facial es de 330 €.</p>
```

## Benefits

1. **Single Source of Truth**: All prices come from `tariff-catalog.json`
2. **Automatic Updates**: When catalog prices change, all shortcode instances update automatically
3. **No Manual Entry**: Content authors never type prices manually
4. **Consistent Formatting**: All prices use the same euro formatting (e.g., "330 €", "1.064,80 €")
5. **Error Prevention**: Invalid keys return empty string with debug logging

## Error Handling

- Invalid key format returns empty string
- Missing group/subkey returns empty string
- Errors are logged to WordPress debug log when `WP_DEBUG` is enabled
- No visible errors on frontend (graceful degradation)

## Implementation Details

The shortcode is registered in `inc/nvx-tariff-shortcode.php` and loaded in `functions.php`. It uses the existing `nvx_catalog_json_load()` and `nvx_catalog_tariff_display_price()` functions from the catalog system.
