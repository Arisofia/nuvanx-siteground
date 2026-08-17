# Third-Party Script Filter Consolidation Plan

## Current State Analysis

### MU-Plugin: `nvx-third-party-scripts-manager.php`
**Priority:** `init` (very early in WordPress lifecycle)

**Capabilities:**
1. `remove_server_side_scripts()` - Removes hooks from HubSpot and Facebook plugins
2. `filter_rogue_script_tags()` - Filters script tags by src (priority 999)
3. `enqueue_client_side_loader()` - Loads client-side dynamic script loader

**Rogue Sources Blocked:**
- `connect.facebook.net`
- `js.hs-scripts.com`
- `hs-analytics.net`

**Script Loader Tag Filter:** Priority 999 (last)

### Theme: `nvx-integrations.php`
**Priority:** `wp_enqueue_scripts` (priority 100) + `script_loader_tag` (priority 10)

**Capabilities:**
1. `wp_enqueue_scripts` (priority 100) - Dequeue/deregister specific scripts
2. `script_loader_tag` (priority 10) - Filter script tags by handle/src
3. `template_redirect` (priority 999999) - Full-document buffer ob_start

**Scripts Blocked:**
- `siteground-facebook-signal`
- `facebook-for-wordpress-pixel`
- `googlesitekit-sign-in-with-google`
- `nvx-hubspot-forms-embed`
- `leadin-script-loader-js`

**Script Loader Tag Filter:** Priority 10 (early)

## Duplicated Functionality

| Function | MU-Plugin | Theme | Priority Conflict |
|----------|-----------|-------|-------------------|
| Facebook blocking | ✅ (src-based) | ✅ (handle-based) | MU=999, Theme=10 |
| HubSpot blocking | ✅ (hook removal) | ✅ (dequeue) | MU=init, Theme=100 |
| Google Sign-In | ❌ | ✅ (src-based) | Theme only |
| Script tag filtering | ✅ (priority 999) | ✅ (priority 10) | **CONFLICT** |

## Recommended Consolidation Strategy

### Phase 1: Keep MU-Plugin as Primary Defense
**Rationale:** MU-plugins load before themes, providing earliest possible blocking.

**Actions:**
1. Maintain `nvx-third-party-scripts-manager.php` as primary script filter
2. Keep `ROGUE_SCRIPT_SOURCES` list
3. Keep `remove_server_side_scripts()` for hook removal
4. Keep `filter_rogue_script_tags()` at priority 999 (final defense)

### Phase 2: Reduce Theme to Complementary Role
**Rationale:** Theme should only handle scripts MU-plugin cannot reach.

**Actions:**
1. Remove duplicate Facebook blocking from theme (MU covers it)
2. Remove duplicate HubSpot blocking from theme (MU covers it)
3. Keep Google Sign-In blocking in theme (MU doesn't cover it)
4. Keep SiteGround Facebook Signal blocking in theme (MU doesn't cover it)
5. Keep GTM delay execution in theme buffer (unique capability)

### Phase 3: Priority Coordination
**Rationale:** Ensure predictable execution order.

**Actions:**
1. MU-plugin `script_loader_tag`: priority 999 (remains)
2. Theme `script_loader_tag`: change from priority 10 to priority 20 (allow MU to filter first)
3. Theme `wp_enqueue_scripts`: priority 100 (remains)
4. Theme `template_redirect`: priority 999999 (remains - buffer processing)

### Phase 4: Update Documentation
**Rationale:** Make ownership explicit.

**Actions:**
1. Document MU-plugin as "primary third-party script filter"
2. Document theme as "complementary script management"
3. Add comment explaining priority coordination
4. Update nvx-integrations.php comment to reflect dual-layer architecture

## Implementation Steps

### Step 1: Update MU-Plugin
```php
// Add Google Sign-In to ROGUE_SCRIPT_SOURCES
private const ROGUE_SCRIPT_SOURCES = array(
    'connect.facebook.net',
    'js.hs-scripts.com',
    'hs-analytics.net',
    'accounts.google.com/gsi', // Add this
);
```

### Step 2: Simplify Theme nvx-integrations.php
```php
// Remove duplicate Facebook blocking
// Remove duplicate HubSpot blocking
// Keep Google Sign-In (MU doesn't cover it yet)
// Keep SiteGround-specific scripts
// Keep GTM delay execution (unique to theme buffer)
```

### Step 3: Adjust Theme Priority
```php
// Change from priority 10 to priority 20
add_filter( 'script_loader_tag', static function ( $tag, $handle, $src = '' ): string {
    // Allow MU-plugin priority 999 to filter first
    // This provides fallback for anything MU misses
}, 20, 3 );
```

### Step 4: Update Documentation
```php
/*
 * Third-party script filtering architecture:
 *
 * PRIMARY: MU-plugin nvx-third-party-scripts-manager.php
 * - Loads at init (earliest possible)
 * - Removes hooks from Facebook/HubSpot plugins
 * - Filters script tags by src (priority 999)
 *
 * COMPLEMENTARY: Theme nvx-integrations.php
 * - Dequeue/deregister specific handles (priority 100)
 * - Filter script tags by handle (priority 20)
 * - Full-document buffer processing (priority 999999)
 * - GTM delay execution
 *
 * This dual-layer approach ensures:
 * 1. Earliest possible blocking (MU-plugin)
 * 2. Theme-specific control (theme filters)
 * 3. Runtime buffer processing (theme ob_start)
 */
```

## Validation Requirements

1. Test that Facebook scripts are still blocked
2. Test that HubSpot scripts are still blocked
3. Test that Google Sign-In is still blocked
4. Test that GTM delay execution still works
5. Test that SiteGround scripts are handled correctly
6. Verify no script loader conflicts
7. Verify priority order with debug logging

## Backward Compatibility

This consolidation should not break existing functionality:
- All currently blocked scripts remain blocked
- GTM delay execution preserved
- Client-side loader preserved
- Priority changes are minor (10 → 20)

## Rollback Plan

If issues arise:
1. Revert theme priority change (20 → 10)
2. Re-add duplicate blocking to theme
3. Keep MU-plugin changes (they're improvements)
