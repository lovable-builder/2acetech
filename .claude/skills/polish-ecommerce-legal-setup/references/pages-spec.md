# Page-by-page specification

Every public legal element of a Polish WooCommerce store, in the order a customer meets it. For each:
slug, title, WordPress settings, the section list the theme depends on, legal basis, the mandatory-content
checklist, the facts it depends on, what to delete when a feature is not offered, and where it is linked from.

Conventions

- "Template" = file in `templates/` (Polish text with `{{VARIABLES}}` and `<!--IF:feature-->` blocks).
  "Render" = `scripts/render_legal.py` output. Section numbers below are those of the **fully featured
  2acetech render**. With features switched off the renderer renumbers lists (`<ol start="auto">`), table rows
  (`{{R:..}}`), sub-headings (`{{H:..}}`) and checks cross-references (`lint_legal.py`).
- Legal references carry **[verified date]**. `[A]` = verified by the 2026-11-20 legal audit against ISAP/EUR-Lex
  (`legal/OPEN-ITEMS.md` section 8); `[R]` = re-verified 2026-11-21 (metadata only); `[K]` = from the author's
  knowledge, must be re-verified on first use. Details and refresh duties: `legal-rules-register.md`.
- The seven public pages are Polish consumer documents. Never publish a page that fails `lint_legal.py`.

Standard WordPress settings for ALL legal pages (do not repeat below)

| Setting | Value | Why |
|---|---|---|
| Post type / status | Page; **Draft** until the owner approves publishing | the 2acetech seeding code never publishes by itself |
| Template | Default (`page.php`). Not the homepage template, not Elementor/Canvas | consistent layout; Elementor adds scripts |
| Content | raw HTML pasted in the **Code editor** (or seeded from the file). Do not re-save in the visual editor without re-checking the HTML: the block editor may convert to a Classic block | the theme adds anchors by regex over `<h2>§ N.` (see `theme-implementation.md` 3) |
| Comments / pingbacks | off | no discussion on legal pages |
| Featured image | none | |
| Parent | none | slugs are matched by the theme |
| Permalink | exactly the slug in the table (no `-2` suffix: a stale page with the same slug in the trash blocks it) | footer links and internal links use the slugs |
| SEO | indexable; keep out of the header menu | legal pages are normal pages, but visible only in the footer legal bar |
| Cache | purge the page and the footer after publishing (LiteSpeed/hPanel) | |

---

## 1. Regulamin sklepu

| | |
|---|---|
| Slug / title | `regulamin` / "Regulamin sklepu" (template file `regulamin-sklepu.html`) |
| WP integration | set as WooCommerce **Terms and conditions** page (`woocommerce_terms_page_id`): WooCommerce > Settings > Advanced > Page setup > "Terms and conditions" |
| Linked from | footer legal bar; checkout terms checkbox; order-confirmation e-mail body (full text); privacy policy; every other page that says "Regulamin" |
| Legal basis | art. 8 ust. 1-2 ustawy o świadczeniu usług drogą elektroniczną (Dz.U. 2024 poz. 1513) [A 2026-11-20]; art. 384 § 1 KC (Regulamin binds only if delivered before the contract) [A]; art. 12 ust. 1, 13, 17, 21, 22-24 UPK [A]; art. 385(1)-385(3) KC (unfair terms; grey list art. 385(3)) [A] |
| Facts it depends on | everything in intake sections A-E |

Section list (anchors `#par-N` are created by the theme from `<h2>§ N.`; footer links use `#par-5`, `#par-6`, `#par-8`):

| § | Title | Content (mandatory in bold) | Legal basis | Delete when |
|---|---|---|---|---|
| 1 | Postanowienia ogólne i dane Sprzedawcy | **seller identity (firm, seat, KRS, court, NIP, REGON, capital), e-mail, phone, complaints address, return address**; consumer definition incl. art. 7aa UPK (sole trader acting outside professional purpose); definitions (Konsument, Towar, Zamówienie, Umowa sprzedaży, Trwały nośnik, Funkcja odstąpienia, UPK, KC); technical requirements; **Regulamin available free and storable (art. 8 ust. 1 pkt 2 UŚUDE)**; scope of offer (Poland only, art. 4 ust. 1 lit. a Reg. 2018/302); dropshipping disclosure ("Kto jest Sprzedawcą") | art. 5 UŚUDE (seller data) [A]; art. 12 ust. 1 pkt 2-4 UPK [A]; art. 11 UPK (phone cost) [A]; art. 7aa UPK [A]; art. 22(1) KC; Reg. (EU) 2018/302 [A] | "Dostawca" definition and "Kto jest Sprzedawcą" when `dropshipping=false`; "Konto" definition when `accounts=false` |
| 2 | Usługi świadczone drogą elektroniczną | free e-services list (offer display, account, withdrawal function, reviews, contact form, newsletter); account contract and **termination by consumer at any time free; by seller only for listed valid reasons with 14 days notice** (grey list pkt 14, 15); guest checkout; e-service complaints in 14 days; **reviews: who may post, whether and how verified (UPNPR art. 6 ust. 4 pkt 7), no filtering by rating, licence, notice-and-action (DSA art. 11-12, 14, 16, 17)**; newsletter consent (art. 398 PKE) | UŚUDE art. 8 [A]; UPNPR art. 6, 7 pkt 25-26 [A]; DSA (Reg. 2022/2065) art. 11, 12, 14, 16, 17 [A]; PKE art. 398 [A] | per feature: `accounts`, `reviews`, `contact_form`, `newsletter` |
| 3 | Towary, ceny i informacje o ofercie | offer data is part of the contract (art. 22 UPK); **prices in PLN, gross, delivery price; no hidden fees**; Omnibus (30-day lowest price, art. 4 ust. 2-3 ustawy o informowaniu o cenach, Dz.U. 2023 poz. 168 [A]); coupons do not limit rights; no surcharge for a payment method (art. 383(1) KC) [A]; product safety information and manufacturer data on request (GPSR art. 19, 9 [A]); availability (dropshipping) | art. 12 ust. 1 pkt 5, art. 22 UPK; price act art. 3-4 [A]; GPSR [A] | availability paragraph if `dropshipping=false`; VAT sentence changes if `vat_exempt` |
| 4 | Składanie Zamówienia i zawarcie Umowy sprzedaży | steps in the checkout; **button "Zamówienie z obowiązkiem zapłaty" and its effect (art. 17 ust. 2-4 UPK: no contract if the label is wrong)**; express wallet buttons; order = offer, contract concluded when the confirmation e-mail is sent after successful payment (must match the WooCommerce e-mail trigger: Phase 5); closed list of reasons to refuse or cancel (grey list pkt 8, 12, 13); consumer may withdraw the offer before acceptance (art. 31 ust. 2 UPK); **confirmation on a durable medium with the art. 12 information and Regulamin + annexes (art. 21 ust. 1 UPK)**; record of the order | art. 17, 21, 31 UPK [A] | express sentence if `express_checkout=false`; "po zalogowaniu" if `accounts=false` |
| 5 | Płatności, dostawa i zużyty sprzęt | payment methods = only the enabled ones; prepayment (no COD); provider named; failed payment = no order; **invoice on request (art. 106b ust. 3 pkt 1 VAT Act, 3 months)** [A]; **delivery: Poland only, courier, price, dispatch time (binding) and transit time (forecast) in a table** (`<li>` with `{{N:delivery_table}}`); outer limit and consequences (art. 491, 492 KC [A]); **risk passes on delivery (art. 548 § 3 KC)** [A]; multiple parcels; **WEEE: crossed-out bin symbol, free take-back at delivery (art. 37 ust. 2 ZSEE), PSZOK, batteries** | art. 18, 22 UPK [A]; ZSEE Act Dz.U. 2024 poz. 573 art. 37 ust. 2, 39 [A]; KC [A] | whole WEEE part if `weee=false`; delivery cost wording if `free_delivery=false` |
| 6 | Prawo odstąpienia od umowy | 14 days no reason (art. 27 UPK); start of period incl. multi-item/part deliveries (art. 28 pkt 1); **ways to withdraw: electronic function "Odstąp od umowy tutaj" (ust. 6-12), e-mail, letter, model form**; function rules: always available, no login, fields, confirm button, **acknowledgement on a durable medium with date and time (art. 30 ust. 4 UPK)** [A]; effects (art. 31-34): return within 14 days, **refund within 14 days of the statement, same payment method, may wait for goods or proof of dispatch (art. 32)**; return cost (art. 34 ust. 2), **estimated maximum cost for bulky goods (art. 12 ust. 1 pkt 10)**; diminished value (art. 34 ust. 4); exceptions (art. 38); ability to open and test electronics | art. 27-38 UPK [A]; Directive 2023/2673 (art. 11a Dir. 2011/83) [see rules register: Poland not transposed as of 2026-11-20] | return cost variants; `withdrawal_exceptions` variant; third-party return address sentence |
| 7 | Odpowiedzialność Sprzedawcy za niezgodność Towaru z umową | **non-conformity regime art. 43a-43g UPK (not rękojmia; KC 556 ff. do not apply to consumers, art. 43a ust. 1)** [A]; conformity test (43b); **2 years from delivery + presumption (43c ust. 1)**; repair/replacement (43d), price reduction/withdrawal (43e), withholding payment (43f); guarantee is additional (art. 579 § 1 KC) [A]; digital elements updates (43b ust. 3, 43k ust. 3); seller liable although the supplier shipped | art. 43a-43g, 43k UPK [A] | digital-elements and supplier paragraphs per feature |
| 8 | Reklamacje | channels (e-mail, post; phone for information); content hints; **collection of the goods at the seller's cost (art. 43d ust. 5)**; **answer within 14 days, silence = acceptance (art. 7a ust. 1-3 UPK)** [A]; **out-of-court options (art. 12 ust. 1 pkt 21 UPK); ADR statement (art. 32 ADR Act, Dz.U. 2016 poz. 1823)** [A]; courts; **no ODR platform link (platform closed; Reg. 524/2013 repealed from 20 July 2025 by Reg. (EU) 2024/3228)** [A] | art. 7a, 12 ust. 1 pkt 21 UPK; ADR Act art. 31-32 | ADR variant by `adr_participation` |
| 9 | Dane osobowe i pliki cookie | pointer to Polityka prywatności and Polityka cookies; providing data is voluntary but necessary for the contract | art. 13 RODO | never |
| 10 | Zmiany Regulaminu | closed list of valid reasons (grey list pkt 10, 19), 14 days notice, old orders keep the old Regulamin, free exit for account holders | art. 385(3) pkt 10, 19 KC [A] | account sentences if `accounts=false` |
| 11 | Postanowienia końcowe | Polish law with consumer protection of the habitual residence (Rome I art. 6 ust. 2 [A]); no waiver of UPK rights (art. 7 UPK); more favourable text of the info pages wins; severability; effective date | art. 7 UPK [A]; Rome I [A] | never |
| Zał. 1 | Pouczenie o prawie odstąpienia | statutory model (annex 1 UPK) with the shop's data and the electronic-function sentence; return-cost sentence per variant | art. 13 UPK (delivery of the model satisfies art. 12 ust. 1 pkt 9-11) [A] | cost sentence variant |
| Zał. 2 | Wzór formularza odstąpienia | statutory model (annex 2 UPK), addressee = correspondence address | art. 12, annex 2 UPK [A] | never |

Checklist before publishing the Regulamin: (a) lint 0 errors; (b) every "§ N ust. M" reference exists; (c) the confirmation e-mail trigger matches § 4 ust. 4; (d) the button text equals "Zamówienie z obowiązkiem zapłaty"; (e) payment methods in § 5 ust. 2 equal Store API methods; (f) return address is served; (g) delivery times are supplier-guaranteed; (h) grey-list table in OPEN-ITEMS filled; (i) lawyer review done.

---

## 2. Polityka prywatności

| | |
|---|---|
| Slug / title | `polityka-prywatnosci` / "Polityka prywatności" |
| WP integration | Settings > Privacy > "Change your Privacy Policy page" = this page (`wp_page_for_privacy_policy`); WooCommerce checkout privacy text uses it |
| Anchors | `#sec-N` from `<h2>N.`; **`#sec-6` = cookies section** (footer fallback link while the cookie page is a draft), `#sec-4` = transfers (cookie policy links to it) |
| Legal basis | art. 12, 13 RODO (Reg. 2016/679) [A]; art. 5 ust. 1 lit. a; 6; 7; 15-22; 28; 30; 33 ust. 5; 44-49; 77 [A] |
| Linked from | footer; checkout checkbox text; cookie banner/policy; forms (withdrawal page, contact form, review form) |

Sections: 1 Administrator (identity, contact, DPO yes/no); 2 table "cel / dane / podstawa / okres" (one row per purpose, rows below), "Czy musisz podać dane?"; 3 Recipients; 4 Transfers outside EEA; 5 Rights + how to exercise (1 month, +2 months, art. 12 ust. 3); 6 Cookies (summary, links to Polityka cookies); 7 Security; 8 Changes.

Rows of the purposes table (`{{R:name}}` numbering; legal basis and retention in the template):

| Row | Purpose | Basis | Retention | Delete when |
|---|---|---|---|---|
| order | contract performance (order, payment, delivery, returns) | art. 6 ust. 1 lit. b | until performance, then rows tax/claims | never |
| tax | accounting and tax | art. 6 ust. 1 lit. c; **art. 74 ust. 2-3 ustawy o rachunkowości (5 years from the start of the year after the financial year)** and art. 112 VAT Act + **art. 70 § 1 Ordynacji podatkowej (5 years from the end of the year in which the tax was due)** [A 2026-11-20] | as stated | never |
| claims | establish/defend claims | lit. f; art. 118 KC (3 or 6 years) | until limitation | never |
| account | account | lit. b | until account deleted | `accounts=false` |
| complaints | complaints (14-day answer, art. 7a UPK) | lit. b, c; art. 74 ust. 2 pkt 6 ustawy o rachunkowości | 1 year after settlement, claims longer | never |
| withdrawal | withdrawal function, acknowledgement (art. 30 ust. 4 UPK) | lit. b, c, f | as claims | never |
| correspondence | e-mail/letter/phone/contact form | lit. f / b | `CORRESPONDENCE_RETENTION_MONTHS` after last contact | never |
| abuse | 1-hour IP hash for form rate limiting | lit. f | 1 hour | `withdrawal_ip_hash=false` |
| logs | server logs | lit. f | host policy | never |
| payments | card/wallet payments, fraud prevention; provider as separate controller; art. 22 RODO automated rejection note | lit. b + provider's own | order data + provider policy | never |
| safety | GPSR product-safety reports | lit. c (GPSR), f | until resolved + claims | never |
| reviews | publication of reviews, verified label | lit. f (or a for "remember me") | until deleted | `reviews=false` |
| newsletter | newsletter | lit. a + art. 398 PKE | until withdrawal + proof | `newsletter=false` |
| cart | abandoned-cart reminders | lit. a + art. 398 PKE | `ABANDONED_CART_RETENTION` | `abandoned_cart=false` |
| ga4 | analytics | lit. a + art. 399 PKE | GA retention 2 or 14 months (per account setting [A]); cookies up to 400 days | `ga4=false` |
| google_ads | advertising/measurement | lit. a + art. 399 PKE | cookies 90 days (`_gcl_au`), ~13 months (IDE) | `google_ads=false` |
| gcm | cookieless Google pings and remote font before choice | lit. f | not stored by the shop | `google_consent_mode_pings=false` and `google_fonts_remote=false` |
| order_attr | WooCommerce Order Attribution `sbjs_*` | lit. f | session; stored with the order | `order_attribution=false` |
| business | business management | lit. f | as order data | never |

Mandatory content checklist (art. 13 RODO): controller identity and contact; DPO contact if any; purposes **and** legal basis per purpose; legitimate interests named; recipients or categories; transfers and safeguards (and where to get a copy); **retention period or criteria**; rights (access, rectification, erasure, restriction, portability, objection, withdrawal of consent) and the **right to complain to the Prezes UODO**; whether providing data is mandatory and the consequence; automated decision-making/profiling (art. 22) statement. Also record: record of processing (art. 30), processor register (art. 28), breach register (art. 33 ust. 5) - owner tasks in OPEN-ITEMS.

---

## 3. Polityka cookies

| | |
|---|---|
| Slug / title | `polityka-cookies` / "Polityka cookies" |
| Linked from | cookie banner and settings panel ("Polityka cookies" link), footer legal bar, privacy policy |
| Legal basis | **art. 399 ust. 1 (consent after information), ust. 3 pkt 2 (strictly necessary exemption), ust. 2 (browser settings), art. 400 (consent standard as RODO) of the Prawo komunikacji elektronicznej (Dz.U. 2024 poz. 1221, in force 10 Nov 2024)** [R 2026-11-21 status; articles A 2026-11-20]; art. 7 RODO |
| Sections | 1 What cookies are; 2 Who and on what basis; 3 Your choice (banner with three equal buttons "Odrzuć wszystkie" / "Zarządzaj plikami cookie" / "Akceptuję", default-off categories, what happens before the choice, how to change/withdraw, consent cookie name and lifetime); 4 Cookie tables (4.1 necessary, 4.2 functions you switch on, 4.3 Order Attribution, 4.4 analytics, 4.5 marketing); 5 Other third-party connections; 6 Browser management; 7 Changes; 8 Contact |

Rules: the document describes only what a headless-browser test **measured** (Phase 1, `facts-vs-claims-truth-table.md`). Every cookie row = name, who sets it, purpose, lifetime. The strictly-necessary classification of Stripe security cookies is a lawyer point (OPEN-ITEMS). If Google tags load before the choice and send cookieless pings, section 3 and privacy row "gcm" must say so (they do); the stricter option is not to load tags before consent.

---

## 4. Dostawa i płatność

Slug `dostawa-i-platnosc`; footer label "Dostawa i płatność" or the Regulamin anchor `#par-5` while the page is a draft. Sections 1 Where (Poland only) - 2 Cost and time (table, dispatch binding / transit forecast, 30-day outer limit, art. 491 KC) - 3 Where we ship from (dropshipping disclosure) - 4 Receiving the parcel (art. 548 § 3 KC) - 5 Payment (methods, provider, no surcharge, prepayment) - 6 Invoice (art. 106b ust. 3 pkt 1 VAT Act) - 7 WEEE/batteries (free take-back art. 37 ust. 2 ZSEE; PSZOK) - 8 Help. Section numbers 7/8 auto-renumber (`{{S:..}}`). Must equal Regulamin § 5. Legal basis: art. 18 UPK (delivery restrictions and payment methods stated no later than at the start of ordering) [A]. Delete 7 when `weee=false`.

## 5. Zwroty i odstąpienie

Slug `zwroty-i-odstapienie`; footer label "Zwroty" or `#par-6`. Sections 1 14 days (art. 27, 28, 30 ust. 2) - 2 how to withdraw (button, e-mail, letter, form; sub-paragraph with where the button is) - 3 what happens next (acknowledgement, return within 14 days, refund within 14 days, same method, may withhold) - 4 costs (delivery refund, return cost variant, bulky goods, diminished value) - 5 exceptions (art. 38) - 6 return vs complaint (never present a statutory right as a perk: UPNPR art. 7 pkt 10 black list [A]) - 7 supplier-shipped goods - 8 help; then **statutory notice (Załącznik 1) and model form (Załącznik 2) repeated** so the page alone satisfies art. 12/13 UPK. Must equal Regulamin § 6.

## 6. Reklamacje

Slug `reklamacje`; footer label "Reklamacje" or `#par-8`. Sections 1 when (non-conformity, art. 43a-43g; plain-language "rękojmia" explained) - 2 what you may demand - 3 how to complain - 4 what happens next (14 days; collection at seller cost) - 5 digital elements - 6 guarantee - 7 **safety problem (stop using, write; the number is cross-referenced from "Dostawa" and "Dane sprzedawcy" via `{{X:reklamacje:S:safety}}`)** - 8 out-of-court options; then the voluntary complaint form. Must equal Regulamin §§ 7-8.

## 7. Dane sprzedawcy (Kontakt)

Slug `dane-sprzedawcy` (title "Dane sprzedawcy"; may also serve as the Contact page; if a separate contact page with a form exists, the form needs the controller notice and the privacy link). Content: identity block (art. 5 UŚUDE: name, seat, registry, NIP, REGON, capital, representation), contact (e-mail, phone with tariff note, correspondence, complaints and return addresses), language, where the rules are (links with anchors), product safety pointer, **DSA contact point (art. 11-12 Reg. 2022/2065) only if reviews/UGC are on**, complaints and disputes, UODO. Never publish opening hours that are not kept.

## 8. The withdrawal button page ("Odstąp od umowy tutaj")

| | |
|---|---|
| Slug / title | `odstap-od-umowy` / "Odstąp od umowy tutaj" |
| Template | the theme's withdrawal template (page content may stay empty); WP "Page attributes > Template" = Withdrawal. Page must be **published and reachable without login** |
| Form | name, order number, e-mail for the acknowledgement, optional goods; hidden honeypot; nonce; 1-hour IP-hash rate limit (10 per hour) |
| Behaviour | two steps: the page explains; the **confirm button "Potwierdź odstąpienie od umowy tutaj"** sends; the screen shows date/time; **acknowledgement e-mail to the consumer without undue delay** with the content and the date and time received; copy to the shop; matched order gets an order note; nothing is refunded automatically |
| Links | footer on every page; My account > Orders (button per order, order number prefilled); order confirmation page; customer e-mails with the order table; Regulamin § 6 ust. 6; Zwroty page |
| Legal basis | Directive (EU) 2023/2673 inserting art. 11a into Directive 2011/83/EU (applies from 19 June 2026 in the EU); **Poland had not transposed it as of the 2026-11-20 audit and press research of 2026-11-21 (drafts UC82 paused, UC169 in consultation; not confirmed in Dz.U.)**; art. 30 ust. 3-4 UPK already require a prompt acknowledgement on a durable medium when the trader offers electronic withdrawal [A]. **Keep the function**: it is cheap and matches the directive. Re-check the status in every refresh (rules register R-04) |

Tests: submit with a real address; the acknowledgement must arrive (SPF/DKIM/DMARC); check spam; check the admin list "Withdrawals". Do not add a CAPTCHA that blocks consumers.

## 9. Product safety (GPSR) block on every product page

Owner-side template: `templates/blok-informacje-o-bezpieczenstwie-produktu.html` (editor note + field table + examples). **Public output** per product, visible before purchase (art. 19 Reg. (EU) 2023/988 [A], applies since 13 Dec 2024; Polish act of 7 Nov 2025 Dz.U. 2025 poz. 1826 in force 3 Jan 2026 [R 2026-11-21]: art. 4 Polish language; **art. 76 fine up to PLN 500 000** for breach of art. 19 [A]):

| Field | Content | Basis |
|---|---|---|
| Producent | name, postal address, e-mail or web contact | GPSR art. 19(1) lit. a |
| Podmiot odpowiedzialny w UE | if the manufacturer is outside the EU: name, address, e-mail | art. 19(1) lit. b; art. 16 |
| Identyfikacja | photo, type/model, batch/serial | art. 19(1) lit. c |
| Ostrzeżenia i informacje o bezpieczeństwie | exact warnings in Polish | art. 19(1) lit. d; ustawa o języku polskim art. 7a [A] |
| Znak CE i deklaracja zgodności | where required; link or "on request" | sector directives |
| Toys | age grading and warnings visible before purchase (Directive 2009/48/EC art. 11(2), annex V part B) | [A] |
| Energy label / product fiche | for light sources in scope of Del. Reg. 2019/2015 art. 4 lit. b-c; check EPREL | [A] |
| App/assistants (if any) | compatibility, requirements | art. 12 ust. 1 pkt 19-20 UPK [A] |

Design for the theme (not built on 2acetech as of 2026-11-21; see `theme-implementation.md` 9): custom fields on the product, shown under the short description, **publish blocker** when required fields are empty. Rule: "no documents, no sale".

## 10. Order-confirmation e-mail: legal content

The e-mail sent when the order is accepted (WooCommerce "Processing order" and, for bank transfers, "On-hold order") must contain on a durable medium (art. 15, 21 ust. 1 UPK; art. 384 KC): the **Regulamin text in the body** (with Załączniki 1-2, so the statutory withdrawal notice and model form arrive), the order table (goods, total price incl. VAT, delivery cost), the "Odstąp od umowy tutaj" link, seller identity (footer text). WooCommerce > Settings > Emails: sender name = company/brand, sender address on the shop domain. Only customer e-mails for accepted orders; **not** for failed/unpaid orders, otherwise Regulamin § 4 ust. 4 ("contract is concluded when this e-mail is sent") is false. Plain-text variant must carry the same text. Code: `theme-implementation.md` 6.

## 11. Checkout legal elements

1. Order button label: **"Zamówienie z obowiązkiem zapłaty"** (art. 17 ust. 3 UPK) on the block checkout (`placeOrderButtonLabel` filter) and the classic checkout (`woocommerce_order_button_text`).
2. **Required checkbox** "Zapoznałem(-am) się z Regulaminem ... i akceptuję" with links to the Regulamin and the Polityka prywatności; appears only when the Regulamin is published and set as the Terms page.
3. Order summary directly before the button: goods and main features, **total price including VAT and delivery** (art. 17 ust. 1 UPK), shipping method and time (dispatch/transit), price format "1 234,56 zł" with "brutto" suffix.
4. Express wallet buttons (Apple Pay/Google Pay/Link): label or disable (rules register R-05).
5. A note near the button: the withdrawal function exists and where (Directive art. 6(1)(h) as amended; cheap to include).
6. Phone optional; no pre-ticked marketing consents; no extra paid options pre-selected (additional payments need express consent, default options are forbidden: art. 22 Directive 2011/83/EU; find and cite the Polish UPK counterpart on ISAP before quoting it in a document: **[K, unverified]**).
7. Marketing consent checkboxes (newsletter) are separate, unticked, and not required for the order.

## 12. Footer

Seller line (name · address · KRS/NIP/REGON); copyright with the **company name**, not just the domain; **legal bar** with links in this order: Regulamin, Polityka prywatności, Polityka cookies, Dostawa i płatność, Zwroty, Reklamacje, Dane sprzedawcy, **Odstąp od umowy tutaj**, **Ustawienia cookies** (button that reopens the consent panel). While a page is a draft: Regulamin anchors (`#par-5`, `#par-6`, `#par-8`) and privacy `#sec-6` as fallbacks; nothing links to an unpublished page. Legal pages must **not** appear in the header menu or the "Pomoc" column (excluded by ID).

## 13. Cookie banner and settings panel

Three **equal-weight** buttons of the same size (reject is as easy as accept; EDPB/UODO practice and art. 400 PKE/RODO art. 7 [A]); text: what cookies, who is the controller, link to the cookie policy, how to change the choice; categories: Necessary (locked), Analytics, Marketing (default off); consent stored in a first-party cookie (name/lifetime in the cookie policy); a way to reopen (footer button); accessible (role="region"/dialog, focus trap, Escape closes, keyboard operable); Google Consent Mode v2 default "denied" printed first in `<head>`; update on choice; on withdrawal the page reloads. Code: `theme-implementation.md` 5.

## 14. WooCommerce stub pages to hide or translate

| Page | Action |
|---|---|
| Shop, Cart, Checkout, My account | rename to Polish ("Sklep", "Koszyk", "Zamówienie"/"Kasa", "Moje konto"); slugs `sklep`, `koszyk`, `kasa`, `moje-konto` are optional (WooCommerce > Settings > Advanced > Page setup keeps the assignments) |
| "Refund and Returns Policy" (`refund_returns`, WooCommerce creates a draft stub) | delete or keep as a draft; never publish the English stub; excluded from page lists by ID |
| "Orders Tracking" (tracking plugin page) | Polish title/strings or unpublish if no tracking exists; do not promise tracking in the texts |
| "Sample Page", "Privacy Policy" stock stub | delete / replace by the real policy (the seeding code replaces the stock stub) |
| Elementor/Hello/other plugin demo pages | delete |
| Hostinger/AliDropship/Shelfbridge helper pages | review; noindex or delete |
| `wp-sitemap-users-1.xml` | disable the users sitemap |
| Cart, Checkout, My account | `noindex` (WooCommerce does by default; verify with `curl -s URL | grep -i robots`) |
