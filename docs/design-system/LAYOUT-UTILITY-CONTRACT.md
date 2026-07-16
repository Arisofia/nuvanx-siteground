# NUVANX layout utility contract

## Decision

The generic layout utilities remain part of the supported theme API:

- `.nvx-reading`
- `.nvx-grid-12`
- `.nvx-span-4`
- `.nvx-span-5`
- `.nvx-span-6`
- `.nvx-span-7`
- `.nvx-span-8`
- `.nvx-span-12`
- `.nvx-stack`
- `.nvx-stack--tight`
- `.nvx-stack--wide`
- `.nvx-cluster`

The non-brand tight section alias also remains supported:

- `.nvx-section--tight`

## Rationale

A repository-only search does not show these layout class names in the currently tracked PHP, JavaScript or HTML templates. That is not sufficient evidence for deletion because WordPress page content and Gutenberg additional-class fields are stored in the database rather than in the theme repository.

Removing these selectors can therefore create silent regressions that visual smoke tests may miss on pages not included in the route matrix. The utilities are small, token-driven and do not create a parallel visual system, so retaining them is lower risk than deleting them without a complete database inventory.

## Spacing behavior

Both tight section variants use the same canonical token on desktop and mobile:

```css
.nvx-brand-section--tight,
.nvx-section--tight {
  padding-block: var(--nvx-pad-section-tight);
}
```

At the mobile breakpoint they both use `var(--nvx-space-8)`.

## Removal requirements

A future removal requires all of the following:

1. repository search across PHP, JavaScript, templates and generated markup;
2. database search across published pages, reusable blocks, navigation items and Gutenberg block attributes;
3. staging route coverage for every affected record;
4. an explicit class migration for stored markup;
5. a release note identifying the removed API.

Until those conditions are met, CSS Gate treats these selectors as required compatibility primitives rather than legacy patches.
