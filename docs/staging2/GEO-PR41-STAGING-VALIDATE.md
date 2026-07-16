# PR #41 — GEO competitive authority (staging validate, no merge yet)

**PR:** https://github.com/Arisofia/nuvanx-siteground/pull/41  
**Branch:** `feat/geo-seo-competitive-authority`  
**Do not merge** until clinical/commercial confirm **Endolift desde 1.460 €** and CI is green.

## Deploy staging2 (branch ref, not master)

```text
Actions → Deploy theme to staging2 + flush cache
  ref: feat/geo-seo-competitive-authority
```

## Automated checks (GitHub)

- [ ] CSS Gate
- [ ] PHP Lint
- [ ] Security / repository-hygiene

## Schema / GEO smoke (after deploy)

| URL | Expect |
|-----|--------|
| `/` | 1× Yoast graph: MedicalOrganization, both MedicalClinic, Physician, OfferCatalog (Endolift price 1460) |
| `/endolift-facial-papada-mandibula/` | MedicalProcedure + Offer 1460 EUR + FAQPage + performer/reviewedBy |
| Any page body | 0× schema.org `ld+json` in content (strip active) |

Manual:

```bash
# Example after staging is live — replace host
curl -sS 'https://staging2.nuvanx.com/endolift-facial-papada-mandibula/' \
  | grep -o 'application/ld+json' | wc -l
# Expect: 1 (Yoast head) not 2+
```

## Visible content smoke

- [ ] Hero meta includes “Desde 1.460 €”
- [ ] Section `#inversion-endolift` with price + includes list
- [ ] FAQ first question is cost; answers match schema catalog
- [ ] Byline “Revisado por el Dr. José Javier Rivera Tejeda”
- [ ] No videoconsulta CTA

## Commercial lock before merge

| Item | Owner | Status |
|------|-------|--------|
| Confirm 1.460 € still matches Doctoralia / internal tariff | Clínica | ☐ |
| Publish same “desde” on other treatments when known | Clínica + theme constant or CMS | ☐ later |
| Casos vacíos → purge / noindex (E-E-A-T hole) | WP editorial | ☐ separate |
| Landings anatómicas endoláser | Content + new pages | ☐ later |

## After merge (future)

1. Deploy theme to staging2 from `master`
2. Spot-check Rich Results Test on production host when ready
3. Monitor AI Overview / Perplexity for “precio Endolift Madrid” over 1–2 weeks
