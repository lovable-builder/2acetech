---
name: polish-ecommerce-legal-setup
description: Use when onboarding a new company or online store (WordPress + WooCommerce, selling physical goods to consumers in Poland) that needs its legal documentation, legal pages and legal/technical compliance set up; when creating or changing the Regulamin, polityka prywatności, polityka cookies, dostawa i płatność, zwroty i odstąpienie (incl. the "Odstąp od umowy tutaj" button), reklamacje, dane sprzedawcy, the GPSR product-safety block, the cookie banner / Google Consent Mode, the checkout terms checkbox and "Zamówienie z obowiązkiem zapłaty" button, order e-mail legal content, or supplier/data-processing agreements; when doing a legal audit of an existing Polish WooCommerce/WordPress store; or when setting up dropshipping (domestic supplier, store-to-store, AliExpress/marketplace) from a legal point of view.
---

# Polish e-commerce legal setup (WordPress + WooCommerce, B2C)

## README (read first)

**Audience.** A future Claude Code session acting as the shop's "legal agent", and the shop owner reading what it did.
**Scope.** Polish law and EU law as applied in Poland; online store on WordPress + WooCommerce; seller = Polish company (sp. z o.o.; JDG needs manual edits); customers = consumers (B2C); physical goods; delivery in Poland; prepayment. The skill sets up **all** legal documents, legal pages and the legal/technical settings, and proves each statement against the live site.
**Limits.** Not for shops in other countries (use a lawyer of that country), B2B-only shops, marketplaces, digital goods/services, delivery outside Poland, cash on delivery, regulated goods (food, cosmetics, medical devices, PPE, weapons). **These are drafts based on public sources, not legal advice.** A Polish lawyer (radca prawny / adwokat) must review the texts before the shop takes real orders; an accountant must confirm VAT, sales documents, BDO. The agent never declares a shop "compliant".
**How to refresh the law.** Open `references/legal-rules-register.md`: every rule has a verify date and a source; section 2 is the refresh procedure (Sejm ELI API per Dz.U. position, ISAP consolidated texts, EUR-Lex, RCL for pending acts, UODO/UOKiK/UKE pages, provider pages). Sources to re-check each time: ustawa o prawach konsumenta (UPK); ustawa o przeciwdziałaniu nieuczciwym praktykom rynkowym (UPNPR); ustawa o informowaniu o cenach (Omnibus); GPSR (Reg. 2023/988) and the Polish act of 7.11.2025; DSA (Reg. 2022/2065) and Dz.U. 2026 poz. 1296; European Accessibility Act and Dz.U. 2024 poz. 731; WEEE/battery/packaging acts and Regs 2023/1542, 2025/40; RODO and UODO/EDPB guidance; Prawo komunikacji elektronicznej art. 398-400 (cookies; consent); the EU withdrawal-button rules (Dir. 2023/2673) and the Polish transposition (**not yet in force as of 2026-11-21**).
**Changelog.** 2026-11-21: created from the 2acetech project (2ACE sp. z o.o., 2acetech.pl).

---

## What is in this skill

| Path | What |
|---|---|
| `SKILL.md` | this master runbook |
| `references/intake-questionnaire.md` | every fact to collect, where to verify it, which sentences depend on it (variable names = renderer names) |
| `references/pages-spec.md` | page-by-page spec: slug, WP settings, sections/anchors, legal basis, mandatory content, deletions |
| `references/wordpress-woocommerce-setup.md` | exact WordPress/WooCommerce/plugin/Hostinger settings with verification |
| `references/theme-implementation.md` | code patterns from the 2acetech theme (seeding, footer, checkout, consent, e-mail, withdrawal, .htaccess) + how to adapt |
| `references/dropshipping-models.md` | legal analysis and setup for models A (domestic B2B), B (store-to-store), C (marketplace), D (import) |
| `references/facts-vs-claims-truth-table.md` | every factual sentence -> what it depends on -> how to measure -> what to do if false |
| `references/verification-and-tests.md` | the complete test recipe incl. Playground |
| `references/lessons-learned-2acetech.md` | what went wrong and the corrected assumptions |
| `references/legal-rules-register.md` | rules with verify dates, refresh procedure, open legal-risk list |
| `references/grey-list-check.md` | art. 385(3) KC table seed |
| `references/sketches/gpsr-and-omnibus.php` | reference code for the GPSR fields/publish blocker and Omnibus (not deployed anywhere) |
| `templates/*.html` | 7 public pages + 3 owner-side documents, parameterised (`{{VARIABLES}}`, `<!--IF:feature-->`) |
| `templates/OPEN-ITEMS.skeleton.md` | skeleton of the owner's report |
| `scripts/render_legal.py`, `scripts/lint_legal.py` | renderer (+ lint) and lint; `--list` prints all variables and features |
| `scripts/company.example.json` | clearly fake example company (fill-in sheet and self-test input) |
| `scripts/measure_consent.mjs` | headless-Chrome measurement of requests and cookies before/after consent |
| `examples/2ace.company.json` | the real 2ACE data; reproduces the published masters byte for byte (regression test) |

The real 2acetech work is the worked example: `legal/` (published masters, `AUDIT-2026-11.md`, `OPEN-ITEMS.md`) and `wp-content/themes/2acetech`.

---

## Hard rules (learned the hard way)

1. **Verify, never recall.** Every article number, deadline, form and Dz.U. position that goes into a document is checked against a primary source (isap.sejm.gov.pl / `api.sejm.gov.pl/eli`, eur-lex.europa.eu, uodo.gov.pl, uokik.gov.pl) **today**. Say which version of each act you used and the date. Copying a number from the owner's brief, from an earlier session or from this skill without opening the source is a defect. Mark each rule `[verified date]` / `[K]` as in `legal-rules-register.md`.
2. **Correct the brief.** If the request contains a wrong article, deadline or number format, do not copy it: use the right law and list the correction in OPEN-ITEMS (b) with source and date.
3. **Never invent facts about the business.** Company data only from the owner and public registers (KRS API, Biała lista), cross-checked. Everything the owner must decide (e-mail, phone, return address, carriers, delivery times, payment methods, warranty promises, analytics tools) is a variable that is **UNKNOWN until the owner answers**; the renderer refuses to render with missing variables.
4. **Publish only what the shop can back.** Every factual sentence is tied to a setting or a measurement (`facts-vs-claims-truth-table.md`). No "fast", "guarantee on every product", "tracked", "nothing is sent before consent", "fonts are self-hosted" unless measured true. Measure the live site (headless Chrome: requests and cookies before consent, after reject, after accept) **before** writing the cookie policy.
5. **Match the store as built.** Read the theme and plugin code before describing the store: checkout button wording, which forms collect data, which cookies WooCommerce/theme/plugins set, which third-party scripts load. Do not describe features the store does not have; do not leave features running that the texts say do not exist (the 2acetech theme still had newsletter/contact handlers after the texts said "none").
6. **Grey list.** Check every clause against art. 385(3) KC and the UOKiK black-list practice; no clause may restrict statutory consumer rights, shorten limitation or withdrawal periods, shift risk of loss to the consumer, allow unilateral changes without valid reason, or exclude liability for non-conformity. Record the check as a table in OPEN-ITEMS (c): clause | grey-list point | result.
7. **Plain language.** Short sentences, defined terms, numbered paragraphs, no needless legalese. Documents in Polish; instructions and reports in English.
8. **No placeholders in published pages.** The lint fails on `{{ }}`, `<code>[`, `[BRACKETED UPPERCASE]`, WARIANT, POTWIERDŹ, USUŃ, OPEN-ITEMS, TODO. Owner-side documents keep `<code>[...]</code>` placeholders and never go on the website.
9. **Drafts, not publications.** Pages are created as drafts; publication is a human step after the owner's explicit OK. A page the owner edited is never overwritten.
10. **One source of truth.** Render once from `company.json`; copy the rendered files to the repo master (`legal/`) and to the theme seed folder; they must be identical (`cmp`). Delete superseded drafts.
11. **Re-measure after every change** (plugin update, new tool, new product type). The cookie policy is a snapshot.
12. **Do not claim compliance.** Report what was done, what was verified, what remains unverified, and what the lawyer must confirm.

## Everything that needs the owner's explicit OK (ask in the conversation; a standing instruction does not replace it)

| Action | Why |
|---|---|
| **Publishing** any legal page (status Draft -> Publish), deleting a page, setting the Terms/Privacy page, changing menus or footer | customer-facing legal effect |
| **Changing any live setting**: WooCommerce (payments, taxes, shipping, accounts, emails, Advanced features), WordPress (Settings), Stripe or other provider settings, Google/Site Kit settings, DNS/mail records, hosting settings (HSTS, headers, backups) | affects customers and money |
| **Plugin changes**: install, activate, deactivate, update, delete, change a plugin's settings | changes what loads, what cookies exist, what data leaves |
| **Switching payment mode** (test <-> live), enabling/disabling a payment method, express checkout | contract formation (art. 17 UPK) |
| **Placing any order**, even a test order; sending e-mail to real addresses; submitting the withdrawal form on the live site | real data and real consequences |
| **Deploying code** to the live theme (a standing owner instruction such as "commit, push and deploy without asking" covers **code deployment only**; it does not cover settings, plugins or publishing) | |
| **Pausing orders** / taking the shop offline / setting WooCommerce Site visibility | revenue |
| **Deleting data** (orders, users, comments, mailboxes), enabling retention auto-delete | irreversible |
| **Contacting third parties** (suppliers, UOKiK, UODO, the accountant, Stripe support) on the owner's behalf | |
| **Committing or pushing** to git when the owner did not ask (this skill's authoring did not commit) | |

Reading public pages (one anonymous visit, no cart actions beyond what the owner approved), reading registers, rendering and linting offline never need OK.

## Standing prohibitions

1. **Never type, paste or submit a password**, API key, token, recovery code or payment-card number on any site; never ask the owner to paste one into the chat. If a login is needed, the owner logs in in their own browser; the agent works from that already-authenticated session only if the owner says so, read-only unless OK is given.
2. **Never print, log, commit or put into a report any secret**: passwords, Stripe/API keys, webhook secrets, SMTP credentials, nonces, session cookies, database credentials, bank-account numbers from the white list. If a tool output contains one, do not repeat it; say "a secret was present and was not copied".
3. Never write secrets into `company.json`, OPEN-ITEMS, templates, theme code or `.htaccess`.
4. Never read or exfiltrate customer personal data beyond what the task needs; never use real customers' data in tests; never submit forms with other people's data.
5. Never disable security headers, 2FA, backups or logging to "make a test pass".
6. Never take real payments or run card tests in live mode.
7. Never state that a document "complies with the law"; never remove the "not legal advice / lawyer review" statements.
8. Never touch the live site when the task says not to; never change theme/plugin code when the task is documentation only.

---

## Phase 0: Intake (owner and repo)

Goal: a complete, dated `company.json` and a list of UNKNOWN facts. Work in the repository's `legal/` folder (create it; do not commit before the owner approves).

1. Read this file, then `references/intake-questionnaire.md`, `references/legal-rules-register.md` (check verify dates; refresh anything older than 90 days in Phase 1) and `references/lessons-learned-2acetech.md`.
2. Confirm scope with the owner in one message: Polish seller, B2C, WordPress + WooCommerce, physical goods, delivery in Poland, prepayment, language Polish. Stop and ask if anything else (EU delivery, B2B, COD, regulated goods, digital goods).
3. Copy the fill-in sheet: `cp .claude/skills/polish-ecommerce-legal-setup/scripts/company.example.json legal/company.json`. Replace every fake value only with facts **given by the owner or read from a register**; leave unknown optional values empty; features default to false.
4. Ask the questions in batches following the questionnaire sections A-G (company; model and suppliers; payments; data processing; returns/complaints/guarantees/goods; technical; decisions). For every answer note the source ("owner, 2026-11-21"). Ask for documents, not promises: KRS extract (PDF), supplier warehouse confirmation, Stripe entity screenshot (no keys), accountant's answers.
5. Read the store as built (repo and live): theme `functions.php` and `inc/`; templates for checkout, cart, account, forms; the active plugin list (owner screenshot or read-only admin session); `package.json`/README. Write down: checkout button wording, forms (contact, newsletter, review, withdrawal), account features, cookies the theme sets, third-party scripts in the theme, plugins that load scripts (Site Kit, Google for WooCommerce, Stripe, Hostinger Reach, abandoned-cart, reviews, tracking, popups).
6. Output of Phase 0: `legal/company.json` (draft), a list of UNKNOWN items (they become OPEN-ITEMS (a)), the product categories and special-risk goods (toys, electrical, batteries, cosmetics, food, children's articles), the supplier list with legal entities and the model A/B/C/D classification (`references/dropshipping-models.md`).

## Phase 1: Fact verification

Goal: every fact in `company.json` is cross-checked and every statement-to-be is measured.

1. **Registers** (read-only, public):
   ```bash
   KRS=<10 digits>; NIP=<10 digits>; D=$(date +%F)
   curl -s "https://api-krs.ms.gov.pl/api/krs/OdpisAktualny/$KRS?rejestr=P&format=json" | python3 -I -m json.tool | less     # name, seat, capital, representation, REGON
   curl -s "https://wl-api.mf.gov.pl/api/search/nip/$NIP?date=$D" | python3 -I -m json.tool                                # name, NIP, REGON, address, statusVat (do not copy bank accounts)
   ```
   If the KRS API is down ("Przerwa techniczna"), use the PDF extract from ekrs.ms.gov.pl. Compare name, NIP, REGON (9 vs 14 digits), address, capital, **registry court** (the API may not give it: PDF) and representation with the owner's answers; correct and log differences. Check the supplier(s) the same way (and look for shared address/phone/board with the shop: related party).
2. **Law refresh** (`legal-rules-register.md` section 2): ELI record of the UPK (`api.sejm.gov.pl/eli/acts/DU/2014/827`: latest consolidated text, amending acts); set `UPK_TEXT_REF`; check pending acts (withdrawal function, repair, PLD); check provider facts (Stripe/Google/Hostinger entity names, DPF status). Update the register's verify dates.
3. **Live-site measurement** (read-only, no login, no order):
   ```bash
   node .claude/skills/polish-ecommerce-legal-setup/scripts/measure_consent.mjs https://SHOP/ --json legal/measure-home.json
   node .../measure_consent.mjs https://SHOP/product/<slug>/ --json legal/measure-product.json
   curl -s https://SHOP/wp-json/wc/store/v1/cart | python3 -I -m json.tool          # payment methods, shipping zones, tax lines, storePages (terms/privacy ids)
   for s in regulamin polityka-prywatnosci polityka-cookies dostawa-i-platnosc zwroty-i-odstapienie reklamacje dane-sprzedawcy odstap-od-umowy; do printf "%-26s" $s; curl -s -o /dev/null -w '%{http_code}\n' https://SHOP/$s/; done
   dig +short TXT _dmarc.DOMAIN; dig +short TXT DOMAIN; dig +short MX DOMAIN
   ```
   Record: third-party hosts, cookies per phase (A first visit / B after reject / C after accept), Google `gcs=` flags, fonts, scripts injected by plugins, Stripe cookies on product/cart/checkout, payment mode label (live/test; never read keys), registered payment methods vs enabled methods, checkout label/checkbox (browser JS in `verification-and-tests.md` 5), whether any legal page exists, status of Terms/Privacy settings, HTTPS/HSTS, DNS (SPF/DKIM/DMARC, one DMARC record).
4. **Truth table walk** (`facts-vs-claims-truth-table.md`): for each row decide true/false; set features accordingly (`order_attribution`, `google_consent_mode_pings`, `google_fonts_remote`, `hostinger_reach_script`, `cookie_hcdn`, `cookie_cf_bm`, `no_other_trackers`, ...). Anything that is false and cheap to fix becomes a Phase 3 task; anything false and not fixable is disclosed in the text.
5. **Facts the owner must supply later** stay UNKNOWN and block the dependent features (e.g. no phone: ask; no supplier documents: no product).
6. Output: completed `company.json`, `legal/measurements-YYYY-MM-DD.md` (what was measured, when, how), a first draft of OPEN-ITEMS (a), (b), (f), (g), (h).

## Phase 2: Render the documents

1. List what exists: `python3 -I .claude/skills/polish-ecommerce-legal-setup/scripts/render_legal.py --list` (all variables, all features).
2. Render to a scratch folder first:
   ```bash
   SK=.claude/skills/polish-ecommerce-legal-setup
   python3 -I $SK/scripts/render_legal.py --vars legal/company.json --templates $SK/templates --out /tmp/legal-out [--features reviews=false,newsletter=false]
   ```
   It fails with the list of **missing variables** (only those used by the selected features), then runs the lint. Fix inputs, not templates. Read every `WARN` line: variants authored 2026-11-21 (paid delivery, seller pays returns, own warehouse, ADR participation, newsletter, contact form, abandoned cart, VAT-exempt) are **not** part of the published 2ACE texts and need lawyer review.
3. Read the rendered Polish text once, fully, for sense (grammar of inserted values; "we Wrocławiu"; numerals; the delivery-time sentences). Edit variables, not the output; if a variable cannot express the needed change, edit the **template** (and add a regression check), never only the output.
4. Add what the templates cannot know: other tools' cookie rows and recipients through the `EXTRA_*_HTML` variables (4-cell `<tr>`/`<li>`), `other_consent_tools=true`.
5. Copy the seven public files to `legal/` (repo master) and to the theme seed folder (`wp-content/themes/<theme>/legal/`); delete superseded drafts; check `cmp`. Render the three owner-side documents (`blok-informacje-o-bezpieczenstwie-produktu`, `wzor-umowy-dropshipping`, `wzor-umowy-powierzenia-danych`) into `legal/` for the owner and the lawyer; they keep `<code>[...]</code>` placeholders by design and **must not** be copied into the theme.
6. Write `legal/OPEN-ITEMS.md` from `templates/OPEN-ITEMS.skeleton.md`: (a) placeholders and facts to fill, (b) corrections to the brief with sources, (c) grey-list table (seed `references/grey-list-check.md`), (d) decisions, (e) lawyer list (seed `legal-rules-register.md` section 3), (f) company data check, (g) measurements, (h) sources and versions.
7. The renderer must also pass `--selftest` unchanged if templates were edited (`verification-and-tests.md` 1).

## Phase 3: WordPress / WooCommerce / theme / plugin setup

Do the **code** first (in git, tested offline), then ask for OK for each live change.

1. **Compare the store with the required behaviours** (`pages-spec.md` 8-13 and `theme-implementation.md`): legal-page seeding as drafts; footer legal bar with fallbacks; legal pages excluded from menus and page lists; `woocommerce_terms_page_id` / `wp_page_for_privacy_policy`; label "Zamówienie z obowiązkiem zapłaty" (classic + block); required Regulamin checkbox in the block checkout; Regulamin in the body of order-confirmation e-mails (processing/on-hold, HTML + plain); Consent Mode default printed first in `<head>`, consent update, cookie banner with equal buttons, reopen link, consent cookie; withdrawal page + links (footer, account, thank-you, e-mails) + acknowledgement e-mail; `legal/.htaccess` deny; GPSR fields + publish blocker; Omnibus handling or no sale prices; removal of unsupported claims; Polish gettext. Use the existing 2acetech files when the theme is the 2acetech theme; otherwise adapt (child theme or mu-plugin, `theme-implementation.md` 0).
2. **Remove or switch off what the texts say does not exist** (newsletter/contact handlers, remote fonts, Order Attribution, host scripts, abandoned-cart, unused plugins) - each is a live/plugin change: ask for OK.
3. **Test the code offline** in a throw-away theme copy with WordPress Playground (`verification-and-tests.md` 9): PHP syntax, seeding, anchors, footer links, checkout terms attributes, consent default. Never test by editing the live site.
4. **Settings** (`wordpress-woocommerce-setup.md`): present the exact list of changes to the owner (menu path, current value, target value, reason) and apply only the approved ones, in this order: (1) pause orders / Site visibility if the shop is live and Critical items exist; (2) WordPress core; (3) WooCommerce General/Products/Tax/Shipping; (4) Payments (methods, express checkout, test mode); (5) Accounts & Privacy; (6) Emails; (7) Advanced (Terms page, Order Attribution off, usage tracking off); (8) plugins; (9) Google; (10) hosting/DNS. After each group re-run the relevant check.
5. **Re-measure** after the changes (Phase 1 step 3) and re-render if the measured facts changed (feature flags), then update the copies.
6. Commit only when the owner asked for it (the owner's standing instruction for 2acetech is to commit and push, and to deploy code; it does not extend to settings/plugins/publishing).

## Phase 4: Publish

Prerequisite: the owner has read OPEN-ITEMS and said "publish" for the pages.

1. The seeded drafts appear in WP admin (Pages). Confirm each draft's content equals the rendered file (`curl` of the preview is not public for drafts: compare in the editor or through the REST read with the owner's session).
2. Set Terms and Privacy pages (WooCommerce > Settings > Advanced > Page setup; Settings > Privacy) if the seeding code did not.
3. Publish in this order: Regulamin, Polityka prywatności, Polityka cookies, Dostawa i płatność, Zwroty i odstąpienie, Reklamacje, Dane sprzedawcy; make sure the withdrawal page is published. Purge caches.
4. Run the status/footer/anchor checks (`verification-and-tests.md` 3-4) and the checkout check (5).
5. If customers already ordered under an earlier text: the new version applies from the notice date (Regulamin § 10: 14 days' notice; accounts get an e-mail); keep the old version archived.
6. Output: a dated "published" entry in OPEN-ITEMS (g).

## Phase 5: Verification and tests

Run everything in `references/verification-and-tests.md` (lint, placeholder grep, selftest, parity, cross references, anchors, status codes, footer/header, checkout browser JS, e-mail test, consent test, cache, sitemap/noindex, accessibility, mobile, Playground). Record results with dates in OPEN-ITEMS (g). **Gate**: do not recommend live orders until the pre-publication gate list (section 11 of that file) is satisfied; otherwise say plainly what blocks it.

## Phase 6: Owner-side tasks and lawyer review

Hand the owner (OPEN-ITEMS (a) and (d)) a short list, in order of risk:
1. Fill facts: phone, return-address consent, supplier warehouse/time confirmations, BDO numbers, Stripe entity screenshot, accountant's answers (VAT, sales documents, cash-register exemption, BDO).
2. Documents from suppliers per product (GPSR block): manufacturer, EU responsible person, identifier, CE/declaration, Polish manual and warnings, toy age, EPREL for light sources. No documents = no sale.
3. Sign or obtain: supplier agreement (`wzor-umowy-dropshipping`) or written purchase conditions; art. 28 agreements (`wzor-umowy-powierzenia-danych`) where a recipient is a processor; keep copies of Stripe/Google/Hostinger DPAs.
4. Records: record of processing activities (art. 30 RODO), processor register, breach register (art. 33 ust. 5), complaint register, withdrawal log, product-safety incident log; name the process owners and the response times (complaint 14 days; refund 14 days; safety report; data-subject request one month).
5. Operations: monitor the shop mailbox and the "Withdrawals" list; DMARC/SPF/DKIM; backups; 2FA; remove unused plugins; arrange the WEEE take-back; decide express checkout, reviews, newsletter, analytics.
6. **Lawyer review.** Send the Polish radca prawny/adwokat: the seven pages, the owner-side documents, OPEN-ITEMS (c) and (e), the measurement notes and the legal-rules register section 3. Do not publish for live orders before this review unless the owner decides in writing to accept the risk; the agent records that decision but never makes it.

## Phase 7: Maintenance calendar

| When | Task |
|---|---|
| after every plugin/theme/WooCommerce update | re-run `measure_consent.mjs` (home, product, checkout) and the checkout JS check; update cookie tables/flags and re-render |
| monthly | RCL/Sejm check for the withdrawal-function act, repair act, PLD; UOKiK/UODO news; supplier dispatch/transit statistics vs the promise; unanswered complaints; mailbox deletion routine (correspondence retention) |
| quarterly | full `legal-rules-register.md` refresh (ELI record of UPK, new consolidated texts, provider facts); re-render with a new `VERSION_DATE` only if something changed; 14 days' notice per Regulamin § 10 |
| yearly | privacy retention clean-up (accounts, carts, correspondence; accounting documents per art. 74); supplier documents re-collected; DPA copies; review of the records (art. 30); Stripe/Hostinger/Google entity check; breach-response drill |
| **2026-12-09** | Product Liability Directive (EU) 2024/2853 applies to products placed on the market after this date (Polish transposition status to be checked) |
| **2027-01-01** | KSeF: all VAT taxpayers (micro with sales up to PLN 10 000 per month join); KSeF number on transfers between taxpayers |
| **2027-02-18** | amendment Dz.U. 2026 poz. 176 of the WEEE Act (per the 2026-11-20 audit; check content) |
| **2027-09-27** | Dz.U. 2026 poz. 1353 (UPK and UPNPR: green claims, statutory-guarantee notice, durability/repair information, black-list changes) enters into force |
| **2027-12-11** | RED cybersecurity delegated act repeal (per audit) |
| **2030-08-01** | Toy Safety Regulation (EU) 2025/2509 replaces Directive 2009/48/EC |
| whenever Poland publishes the act implementing Dir. 2023/2673 | adapt the withdrawal function and Regulamin § 6 ust. 6-12 |

---

## Quick reference: commands

```bash
SK=.claude/skills/polish-ecommerce-legal-setup
python3 -I $SK/scripts/render_legal.py --list
python3 -I $SK/scripts/render_legal.py --vars legal/company.json --templates $SK/templates --out /tmp/legal-out
python3 -I $SK/scripts/lint_legal.py /tmp/legal-out --vars legal/company.json
python3 -I $SK/scripts/render_legal.py --selftest 300 --vars $SK/scripts/company.example.json --templates $SK/templates
python3 -I $SK/scripts/render_legal.py --vars $SK/examples/2ace.company.json --templates $SK/templates --out /tmp/2ace && for f in legal/*.html; do cmp /tmp/2ace/$(basename $f) $f; done
node $SK/scripts/measure_consent.mjs https://SHOP/ --json /tmp/measure.json
```

## Template language (for editing templates)

`{{NAME}}` variable (HTML-escaped; names ending `_HTML` are raw) · `<!--IF:a&b|!c-->..<!--ELSE-->..<!--ENDIF-->` nestable · `<li><!--N:name-->` + `{{N:name}}` list-item number · `<ol start="auto">` continues numbering · `<tr><td><!--R:name-->` + `{{R:name}}` table-row number · `<h3><!--H:name-->` + `{{H:name}}` "section.sub" · `<h2><!--S:name-->` + `{{S:name}}` section number · `{{X:doc:K:name}}` cross-document number. Keep `<h2>§ N.`, `<h2>N.` and `<h2>Załącznik nr N.` exactly: the theme builds `#par-N`, `#sec-N`, `#zalacznik-N` from them. Markers sit on the same line as the content they wrap so that a rendered file has no blank-line artefacts. After editing a template run the selftest and the 2ACE parity check.

## Final report to the owner (English, short)

1. What was written (file list). 2. The three most important corrections or risks. 3. What the owner must fill in. 4. What was verified and what could not be verified. 5. Plain statement: **these are drafts based on public sources, not legal advice; a Polish lawyer should review them before the shop goes live.**
