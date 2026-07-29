## Immutable validation sequence

1. Deploy the operational head to staging2.
2. Run theme hygiene and all repository contracts.
3. Crawl every public WordPress route at desktop and mobile widths.
4. Run rendered acceptance, visual QA, smoke checks, marker validation and cache verification.
5. Inspect artifacts and correct only reproducible failures.
6. Revert the temporary style nonce and repeat the complete validation against a tree equal to master.
7. Close this operational branch without merge.
