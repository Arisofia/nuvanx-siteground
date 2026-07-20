#!/usr/bin/env python3
"""Apply the approved QA visual-blocker plan, then delete this file in CI."""

from pathlib import Path
import re

ROOT = Path(__file__).resolve().parents[2]


def read(relative: str) -> str:
    return (ROOT / relative).read_text(encoding="utf-8")


def write(relative: str, content: str) -> None:
    (ROOT / relative).write_text(content, encoding="utf-8")


# 1. Restore the canonical editorial shell on Investment and protected review pages.
strategy_path = "wp-content/themes/nuvanx-medical/inc/nvx-strategy-pages.php"
strategy = read(strategy_path)
review_old = '<article class="nvx-brand-readable nvx-strategy-page nvx-strategy-page--review">'
review_new = '<article class="nvx-brand-readable nvx-strategy-page nvx-strategy-page--review nvx-shell">'
if review_old in strategy:
    strategy = strategy.replace(review_old, review_new, 1)
elif review_new not in strategy:
    raise SystemExit("Review article marker not found")

investment_start = strategy.index("function nvx_strategy_investment_markup(): string")
investment_end = strategy.index("function nvx_strategy_page_markup", investment_start)
investment = strategy[investment_start:investment_end]
investment_old = '<article class="nvx-brand-readable nvx-strategy-page">'
investment_new = '<article class="nvx-brand-readable nvx-strategy-page nvx-shell">'
if investment_old in investment:
    investment = investment.replace(investment_old, investment_new, 1)
elif investment_new not in investment:
    raise SystemExit("Investment article marker not found")
strategy = strategy[:investment_start] + investment + strategy[investment_end:]
write(strategy_path, strategy)

# 2. Confirm Contact owns the grid wrapper and no iframe presentation attributes.
contact_path = "wp-content/themes/nuvanx-medical/templates/template-contact.php"
contact = read(contact_path)
contact = contact.replace(' style="border:0;"', "").replace(" style='border:0;'", "")
if '<div class="nvx-clinics-grid">' not in contact:
    raise SystemExit("Contact clinic grid wrapper not found")
write(contact_path, contact)

# 3. Let nvx-shell control the strategy root; keep hero/section presentation.
closure_path = "wp-content/themes/nuvanx-medical/inc/nvx-external-visual-closure.php"
closure = read(closure_path)
strategy_root = re.compile(
    r"\n/\* Strategy pages own one full-width editorial hierarchy\. \*/\n"
    r"\.nvx-page__content > \.nvx-strategy-page \{.*?\n\}\n",
    re.S,
)
closure, strategy_count = strategy_root.subn(
    "\n/* Strategy pages retain the canonical shell restored in PHP markup. */\n",
    closure,
    count=1,
)
if strategy_count != 1:
    raise SystemExit(f"Expected one strategy root override, removed {strategy_count}")

# 4. Move Contact card CSS out of late inline CSS and into canonical components.css.
contact_closure = re.compile(
    r"\n/\* Contact cards use the same shell, card and control contracts as treatment pages\. \*/.*?"
    r"\n/\* Native details controls",
    re.S,
)
closure, contact_count = contact_closure.subn("\n/* Native details controls", closure, count=1)
if contact_count != 1:
    raise SystemExit(f"Expected one Contact closure block, removed {contact_count}")
write(closure_path, closure)

components_path = "wp-content/themes/nuvanx-medical/assets/css/nvx-components.css"
components = read(components_path).rstrip() + "\n"
marker = "/* CONTACT CLINIC CARDS — canonical editorial grid */"
contact_css = """

/* CONTACT CLINIC CARDS — canonical editorial grid */
.nvx-page--contact .nvx-section--contact-hero {
  background: var(--nvx-surface-base);
}

.nvx-page--contact .nvx-clinics-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: var(--nvx-gap-base);
  margin-top: var(--nvx-space-6);
}

.nvx-page--contact .nvx-clinic-card {
  display: flex;
  flex-direction: column;
  gap: var(--nvx-space-3);
  min-width: 0;
  padding: var(--nvx-pad-card);
  border: var(--nvx-border-hairline) solid var(--nvx-color-line);
  border-radius: var(--nvx-radius-card);
  background: var(--nvx-light);
  box-shadow: var(--nvx-shadow-min);
}

.nvx-page--contact .nvx-clinic-card__header {
  display: grid;
  gap: var(--nvx-space-1);
}

.nvx-page--contact .nvx-clinic-card__data {
  display: grid;
  gap: var(--nvx-space-2);
  margin: 0;
  padding: 0;
  list-style: none;
}

.nvx-page--contact .nvx-clinic-card__data li {
  display: block;
  margin: 0;
}

.nvx-page--contact .nvx-clinic-card__data .nvx-icon {
  width: var(--nvx-icon-xs);
  height: var(--nvx-icon-xs);
  margin-right: var(--nvx-space-1);
  color: var(--nvx-accent-muted);
  vertical-align: text-bottom;
}

.nvx-page--contact .nvx-clinic-card__map {
  overflow: hidden;
  border-radius: var(--nvx-radius-image);
  background: var(--nvx-surface-base);
}

.nvx-page--contact .nvx-clinic-card__map iframe {
  display: block;
  width: 100%;
  border: 0;
}

.nvx-page--contact .nvx-clinic-card > .nvx-btn {
  align-self: flex-start;
  margin-top: auto;
}

@media (max-width: 720px) {
  .nvx-page--contact .nvx-clinics-grid {
    grid-template-columns: 1fr;
  }
}
""".strip("\n")
if marker not in components:
    components += contact_css + "\n"
write(components_path, components)

# 5. Keep consent evidence separate, with the requested filename ordering.
qa_path = "scripts/staging2/rendered-qa.mjs"
qa = read(qa_path)
old_name = "`${slug}-${viewport}-consent.png`"
new_name = "`${slug}-consent-${viewport}.png`"
if old_name in qa:
    qa = qa.replace(old_name, new_name, 1)
elif new_name not in qa:
    raise SystemExit("Consent screenshot filename marker not found")
write(qa_path, qa)

qa_contract_path = "scripts/staging2/test-rendered-qa-contract.mjs"
qa_contract = read(qa_contract_path)
old_contract = r"/`\$\{slug\}-\$\{viewport\}-consent\.png`/"
new_contract = r"/`\$\{slug\}-consent-\$\{viewport\}\.png`/"
if old_contract in qa_contract:
    qa_contract = qa_contract.replace(old_contract, new_contract, 1)
elif new_contract not in qa_contract:
    raise SystemExit("Consent screenshot contract marker not found")
write(qa_contract_path, qa_contract)

# 6. Pin the architecture in deterministic tests.
write(
    "scripts/design-system/test-strategy-contact-visual-closure.mjs",
    r'''#!/usr/bin/env node
import fs from 'node:fs';
import path from 'node:path';
import process from 'node:process';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const read = (relative) => fs.readFileSync(path.join(root, relative), 'utf8');
const failures = [];
const requireText = (source, text, message) => { if (!source.includes(text)) failures.push(message); };
const forbidText = (source, text, message) => { if (source.includes(text)) failures.push(message); };

const strategy = read('wp-content/themes/nuvanx-medical/inc/nvx-strategy-pages.php');
const components = read('wp-content/themes/nuvanx-medical/assets/css/nvx-components.css');
const closure = read('wp-content/themes/nuvanx-medical/inc/nvx-external-visual-closure.php');

requireText(strategy, 'nvx-strategy-page--review nvx-shell', 'review article must use the canonical shell');
const investmentStart = strategy.indexOf('function nvx_strategy_investment_markup(): string');
const investmentEnd = strategy.indexOf('function nvx_strategy_page_markup', investmentStart);
requireText(strategy.slice(investmentStart, investmentEnd), 'nvx-strategy-page nvx-shell', 'investment article must use the canonical shell');

forbidText(closure, '.nvx-page__content > .nvx-strategy-page', 'late CSS must not override the restored strategy shell');
requireText(closure, '.nvx-strategy-page > .nvx-brand-hero', 'strategy hero presentation must remain available');
requireText(closure, '.nvx-strategy-page > .nvx-brand-section', 'strategy section presentation must remain available');

requireText(components, '/* CONTACT CLINIC CARDS — canonical editorial grid */', 'clinic cards must live in components.css');
requireText(components, '.nvx-page--contact .nvx-clinics-grid', 'clinic grid selector is missing');
requireText(components, 'grid-template-columns: repeat(2, minmax(0, 1fr));', 'desktop clinic grid must use two columns');
requireText(components, '.nvx-page--contact .nvx-clinic-card', 'clinic card component is missing');
requireText(components, '.nvx-page--contact .nvx-clinic-card__map iframe', 'clinic map component is missing');
requireText(components, 'grid-template-columns: 1fr;', 'mobile clinic grid must collapse to one column');
forbidText(closure, 'Contact cards use the same shell', 'contact cards must not remain in late inline CSS');
forbidText(components, '!important', 'clinic cards must not use !important');

if (failures.length) {
  console.error('Strategy/contact visual contract failed:');
  for (const failure of failures) console.error(`- ${failure}`);
  process.exit(1);
}

console.log('PASS: strategy shell and canonical contact-card CSS');
''',
)

write(
    "scripts/staging2/test-runtime-qa-closure-contract.mjs",
    r'''#!/usr/bin/env node
import fs from 'node:fs';
import path from 'node:path';
import process from 'node:process';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const read = (relative) => fs.readFileSync(path.join(root, relative), 'utf8');
const exists = (relative) => fs.existsSync(path.join(root, relative));
const failures = [];
const requireText = (source, text, message) => { if (!source.includes(text)) failures.push(message); };
const forbidText = (source, text, message) => { if (source.includes(text)) failures.push(message); };

const shell = read('wp-content/themes/nuvanx-medical/template-parts/content/nvx-page-shell.php');
const strategy = read('wp-content/themes/nuvanx-medical/inc/nvx-strategy-pages.php');
const components = read('wp-content/themes/nuvanx-medical/assets/css/nvx-components.css');
const closure = read('wp-content/themes/nuvanx-medical/inc/nvx-external-visual-closure.php');
const canonical = read('wp-content/themes/nuvanx-medical/inc/nvx-staging2-canonical-closure.php');
const contact = read('wp-content/themes/nuvanx-medical/templates/template-contact.php');
const doctor = read('wp-content/themes/nuvanx-medical/inc/nvx-dr-rivera-page.php');

requireText(shell, "function_exists( 'nvx_strategy_current_page_key' )", 'page shell must recognize strategy routes');
requireText(shell, 'null !== nvx_strategy_current_page_key()', 'strategy route recognition must require a resolved key');
requireText(strategy, 'nvx-strategy-page--review nvx-shell', 'review strategy article must use nvx-shell');
const investmentStart = strategy.indexOf('function nvx_strategy_investment_markup(): string');
const investmentEnd = strategy.indexOf('function nvx_strategy_page_markup', investmentStart);
requireText(strategy.slice(investmentStart, investmentEnd), 'nvx-strategy-page nvx-shell', 'investment strategy article must use nvx-shell');
forbidText(closure, '.nvx-page__content > .nvx-strategy-page', 'late CSS must not override the strategy shell');

requireText(closure, "require_once __DIR__ . '/nvx-staging2-canonical-closure.php';", 'external closure must load the canonical module');
requireText(canonical, 'https://nuvanx.com', 'approved Staging2 routes must target the public canonical host');
requireText(canonical, 'nvx_staging2_canonical_is_approved_strategy', 'strategy canonical eligibility must require explicit approval');
requireText(canonical, "add_filter( 'wpseo_canonical'", 'Yoast canonical output must be filtered');

requireText(contact, '<div class="nvx-clinics-grid">', 'contact template must wrap clinics in the grid');
forbidText(contact, 'style="border:0;"', 'contact iframe still has inline border style');
requireText(components, '.nvx-page--contact .nvx-clinics-grid', 'clinic grid must live in components.css');
requireText(components, '.nvx-page--contact .nvx-clinic-card__map iframe', 'clinic maps must be styled in components.css');
requireText(components, 'border: 0;', 'clinic map border reset must live in components.css');
forbidText(closure, 'Contact cards use the same shell', 'contact styles must not remain in the external closure');

forbidText(doctor, '<p class="nvx-brand-kicker" style=', 'doctor authority kicker still uses inline spacing');
requireText(doctor, 'nvx-brand-kicker nvx-dr-rivera-kicker', 'doctor authority kicker class is missing');

if (!exists('googlee8160480bf01506f.html')) failures.push('Google Search Console verification file must be retained');
if (exists('scratch/replace_pixels.mjs')) failures.push('one-time pixel replacement script must stay deleted');

if (failures.length) {
  console.error('Runtime QA closure contract failed:');
  for (const failure of failures) console.error(`- ${failure}`);
  process.exit(1);
}

console.log('PASS: runtime QA closure and visual blocker plan');
''',
)

print("Applied QA visual-blocker plan")
