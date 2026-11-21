# Intake questionnaire: every fact to collect before rendering

Use this file in Phase 0 (collect) and Phase 1 (verify). Every row is one fact. Nothing may be
guessed. If the owner does not know, the fact stays **UNKNOWN** and the feature or sentence that
depends on it is not published (see `facts-vs-claims-truth-table.md`).

How to read the tables:

- **Variable / feature** = the exact name used in `scripts/company.example.json`, `scripts/render_legal.py`
  and the templates. `python3 -I scripts/render_legal.py --list` prints the live registry.
- **Verify where** = the primary source or the measurement that proves it. "Owner" alone is never enough
  for a fact that has a public register.
- **Used in** = the documents whose sentences depend on it (R = Regulamin, P = Polityka prywatności,
  C = Polityka cookies, D = Dostawa i płatność, Z = Zwroty i odstąpienie, K = Reklamacje, S = Dane sprzedawcy,
  G = blok bezpieczeństwa produktu, U = wzór umowy dropshipping, A = wzór umowy powierzenia).
- Law references are marked **[verify date]**: the date the rule was last checked against a primary source.
  Re-check everything older than 90 days (see `legal-rules-register.md`).

Rule zero: collect the answers into `company.json` in the skill working copy (never into the repo root,
never into a commit before the owner approves). Passwords, API keys and bank-account numbers are never
requested or stored (see the standing prohibitions in `SKILL.md`).

---

## A. The company (all from public registers, cross-checked)

| Variable | Exact meaning | Verify where | Used in |
|---|---|---|---|
| `COMPANY_NAME_FULL` | Name ("firma") exactly as in KRS, in capitals, including the form: `... SPÓŁKA Z OGRANICZONĄ ODPOWIEDZIALNOŚCIĄ` | KRS API `api-krs.ms.gov.pl/api/krs/OdpisAktualny/{krs}?rejestr=P&format=json` (field `dane.dzial1.danePodmiotu.nazwa`), cross-check Biała lista `wl-api.mf.gov.pl/api/search/nip/{nip}?date=YYYY-MM-DD` (`name`) | R P C S K Z (addressee of the model form) U A |
| `COMPANY_NAME_SHORT` | The short form used in running text ("2ACE sp. z o.o."). Must be a recognisable abbreviation of the KRS name. Never invent a brand that is not the legal name | KRS; owner confirms | R D Z K C U |
| form of business | `sp. z o.o.` (template default), `S.A.`, `sp.k.`, `JDG`. The templates assume a company with a KRS and a seat. For a sole proprietorship (JDG) the seller block, representation and registry lines differ: stop and ask a lawyer, or adapt `dane-sprzedawcy` by hand (CEIDG number instead of KRS, no share capital, no registry court) | CEIDG `dane.biznes.gov.pl` | S R |
| `KRS` | 10-digit number, leading zeros kept | KRS API; checksum-less, so confirm by opening the extract | R P C S |
| `NIP` | 10 digits, the lint verifies the checksum | Biała lista (`statusVat`, `nip`), KRS extract | R P C S U |
| `REGON` | 9 or 14 digits (KRS shows the 14-digit form; white list shows 9). Use the 9-digit form unless the owner prefers otherwise. NEVER put the REGON in the KRS field: the 2acetech audit had to state explicitly that 545746743 is the REGON, not a KRS number | Biała lista `regon`, KRS `identyfikatory.regon` | R P C S |
| `SHARE_CAPITAL` | As in KRS, e.g. `5 000 zł` (space as thousands separator) | KRS `dzial1.kapital.wysokoscKapitaluZakladowego` | R S |
| `REGISTRY_COURT` | Court and division exactly as in the current extract (`Sąd Rejonowy dla ... , ... Wydział Gospodarczy Krajowego Rejestru Sądowego`). If the API does not give it, take it from the PDF extract (odpis aktualny) from `ekrs.ms.gov.pl`. 2acetech inferred it from the case number once; that is a **risk**: confirm on the PDF | KRS PDF extract | R S |
| `REPRESENTATION_TEXT` | Who may bind the company, as in KRS (`reprezentacja`): "każdy z członków zarządu samodzielnie" / "dwóch członków zarządu łącznie" / "prezes zarządu łącznie z prokurentem" | KRS `dzial2.reprezentacja` | S |
| `SEAT_ADDRESS` | Registered seat as one line: `ul. X nr, lok. Y, NN-NNN Miasto` | KRS `siedzibaIAdres`; the white list writes `101A/301` (slash) - normalise to "lok." | R P C D S |
| `SEAT_CITY_LOC` | The seat town in the **locative** ("w Warszawie", "w Krakowie", "w Łodzi", "we Wrocławiu"): give only the word after "w". For "we Wrocławiu" the template says "w": edit by hand | owner / grammar | R P |
| `CORRESPONDENCE_ADDRESS` | Address for **withdrawal letters** and the model form addressee. Default = seat. Must be an address that is really served | owner | R Z S |
| `COMPLAINTS_ADDRESS` | Address for written complaints. Default = seat. Feature `complaints_same_as_seat` adds "(taki sam jak adres siedziby)" | owner | R K S |
| `RETURN_ADDRESS` | Where consumers physically send returned goods (art. 34 ust. 1 UPK allows an authorised person). **Who operates it?** If a third party (supplier, 3PL, relative): written consent that it accepts returns for the seller and notifies the seller; set `return_address_third_party=true` and `RETURN_ADDRESS_OPERATOR` | owner, written consent | R Z S U |
| `SHOP_EMAIL` | One monitored mailbox (also used as DSA contact point, data-protection contact, complaints, withdrawal, WEEE take-back requests). Check MX/SPF/DKIM/DMARC (Phase 6) | owner; DNS | all |
| `SHOP_PHONE` | `+48 NNN NNN NNN`. The 2026-11-20 audit reads art. 12 ust. 1 pkt 3 UPK as requiring the trader's e-mail address **and** phone number, and art. 11 UPK as limiting the call cost to an ordinary call **[A 2026-11-20]**: confirm the exact wording on ISAP before quoting it. Someone must really answer or the owner decides to publish none (then R § 1 ust. 2, Z, S need a manual edit and a lawyer) | owner | R Z K S D |
| `SHOP_URL` | `https://domain` without trailing slash; the final canonical host (www or not) | `curl -sI -L` | R Z S |
| `WITHDRAWAL_PATH` | Path of the withdrawal-button page, default `/odstap-od-umowy/` (the theme creates this slug) | WP admin | R Z S |
| micro-enterprise? | Not a template variable. Decides the European Accessibility Act exemption (act of 26 April 2024, Dz.U. 2024 poz. 731, art. 4 pkt 1 [audit 2026-11-20]): fewer than 10 persons and turnover or balance sheet <= EUR 2 m, linked companies counted | owner + accountant | OPEN-ITEMS |
| VAT status | `vat_exempt` (feature). Default false = VAT payer and prices are gross. **VAT-exempt (art. 113 VAT Act)** changes the price and invoice sentences: accountant + lawyer must review. `statusVat` on the white list: `Czynny` / `Zwolniony` / `Niezarejestrowany` | Biała lista | R D |
| BDO number | The shop's own BDO registration (the owner's, not the supplier's), if the company is an "introducer" of equipment, batteries, packaging. Not printed in the public pages; recorded in OPEN-ITEMS | `bdo.mos.gov.pl` public register search | OPEN-ITEMS, G |
| EU responsible person | Only if the shop itself **imports** or sells under its own name (see `dropshipping-models.md` model D). For a distributor of goods made by someone else: the manufacturer data on the product page (GPSR art. 19) | supplier documents | G |
| `VERSION_DATE` | Date of this version, `D miesiąca RRRR r.` (e.g. `21 listopada 2026 r.`) | today | all |
| `EFFECTIVE_DATE` | Date the Regulamin applies from (not earlier than publication; changes to a Regulamin need 14 days notice in the template, § 10 ust. 2) | owner | R |
| `UPK_TEXT_REF` | Current consolidated text reference of the consumer rights act. On 2026-11-21: `Dz.U. 2026 poz. 1244` (obwieszczenie Marszałka Sejmu of 4 Sept 2026, promulgated 23 Sept 2026) **[re-verified 2026-11-21 via api.sejm.gov.pl/eli/acts/DU/2026/1244]** | ISAP / ELI API | R |

**Cross-check script (Phase 1)** - run, read, compare to the owner's answers, and record differences in OPEN-ITEMS:

```bash
KRS=0001267111; NIP=1133212948; D=$(date +%F)
curl -s "https://api-krs.ms.gov.pl/api/krs/OdpisAktualny/$KRS?rejestr=P&format=json" | python3 -I -m json.tool | head -120
curl -s "https://wl-api.mf.gov.pl/api/search/nip/$NIP?date=$D" | python3 -I -m json.tool
```
If the KRS API is down ("Przerwa techniczna" happened on 2026-11-20), get the PDF extract from ekrs.ms.gov.pl and
read capital, court and representation from it. Never print or store bank-account numbers from the white list.

---

## B. The business model and suppliers

| Variable / feature | Exact meaning | Verify where | Used in |
|---|---|---|---|
| `dropshipping` (feature) | Goods are dispatched from a supplier's warehouse, not from the seller's own stock. Turns on the "Dostawca" definition, "Kto jest Sprzedawcą", availability paragraph, supplier recipient bullet, return-handling section | owner, supplier contract | R P Z K D |
| supplier(s) and legal entities | For EACH supplier: legal name, KRS/NIP, seat, warehouse address, contact for safety reports. For store-to-store models record the relationship (see model B). **Related-party check**: same address, same phone, same owners, same board members as the store? Then document it (transfer pricing, arm's length) and tell the accountant | KRS/Biała lista, supplier website footer, owner | OPEN-ITEMS, U, A |
| `name_suppliers_in_privacy` (feature) + `SUPPLIER_NAME`, `SUPPLIER_ADDRESS`, `SUPPLIER_ID_TEXT`, `SUPPLIER_DATA_ROLE_TEXT` | Art. 13 ust. 1 lit. e RODO requires "recipients or **categories** of recipients"; naming is optional but advisable when there is one fixed supplier. The role text must match the real contract: separate controller (supplier uses data for its own duties: invoicing carrier, returns) or processor under an art. 28 agreement (`wzor-umowy-powierzenia-danych`) | privacy lawyer decision | P |
| `WAREHOUSE_COUNTRY_LOC` | Country of the dispatch warehouse in the locative, default `Polsce`. **If not Poland: stop.** Delivery times, customs, GPSR responsible person, transfer rules and the Regulamin sentences all change; model C/D rules apply | supplier written confirmation, test order | R P D Z |
| supplier warehouse countries | List per product/variation. A single variation that ships from China breaks the "Polish warehouse" claim for that product: block or disable it (AliDropship `ignore-ship-from` problem, see `dropshipping-models.md`) | supplier feed, test order tracking | R D |
| `DISPATCH_DAYS_MAX` | **Binding** time from contract to dispatch, in working days (a number). Must be guaranteed by the supplier in writing: it becomes part of the contract (art. 22 UPK; the 30-day rule of art. 18 Directive 2011/83 is NOT copied into the Polish UPK, so the outer limit in the template is the seller's own promise, KC art. 455, 491, 492) **[audit 2026-11-20]** | supplier contract | R D U |
| `TRANSIT_DAYS` | Expected carrier time, e.g. `2–5` (en dash). A forecast, not binding | carrier statistics, test orders | R D U |
| `TOTAL_DAYS_MAX` | Usual total ("zwykle do N Dni roboczych"): dispatch max + transit max | arithmetic | R D |
| `DELIVERY_OUTER_LIMIT_DAYS` | Outer limit if no date is given: default `30` | owner | R D |
| couriers | Not named in the templates (they say "kurier"). Name them only if the shop shows carrier choice at checkout. Couriers chosen by suppliers are separate controllers in P | owner | P |
| `free_delivery` (feature) + `DELIVERY_PRICE_TEXT` | Free delivery for every order (`0 zł (bezpłatnie)`) or a price (`14,99 zł`). Threshold-based free delivery ("od 199 zł") is NOT supported by the templates: add a table row and a lawyer check | WooCommerce > Settings > Shipping | R D Z |
| `DELIVERY_METHOD_LABEL_REG` / `_PAGE` | Label in the delivery tables. Must equal the method name at checkout | WooCommerce shipping method title | R D |
| delivery area | Templates support **Poland only** (R § 1 ust. 7, § 5 ust. 5, D 1). Sale to other EU countries needs a different Regulamin (VAT OSS, return addresses, ADR, language, geo-blocking duties) and a lawyer. Geo-blocking rules: Reg. (EU) 2018/302 art. 3-5 apply even to Poland-only delivery (a foreign consumer must be able to order to a Polish address) **[audit 2026-11-20]** | WooCommerce > Settings > General > Selling/Shipping location(s) | R D |
| `phone_optional` (feature) | The phone field at checkout is optional. Set in the block editor on the Checkout page: Checkout block > "Contact information"/"Address" settings > Phone number "Optional" / "Required" / "Hidden" (see `wordpress-woocommerce-setup.md` section 7a). If it is required or hidden, the Polish sentences about the phone must change | checkout page | R P |
| `guest_checkout` / `accounts` (features) | Guest checkout allowed? Customer accounts offered? WooCommerce > Settings > Accounts & Privacy | WP admin | R P C Z |

## C. Payments (only what is enabled and tested)

| Variable / feature | Exact meaning | Verify where | Used in |
|---|---|---|---|
| `PAYMENT_METHODS_LIST` | Enabled methods in one phrase: `karta płatnicza, Apple Pay i Google Pay` | WooCommerce > Settings > Payments **and** Store API: `curl -s $SHOP/wp-json/wc/store/v1/cart` (`payment_methods`) | R D Z P |
| `PAYMENT_METHODS_LIST_PAGE`, `_INSTR`, `_PARENS` | Same list in other grammatical forms (default = `PAYMENT_METHODS_LIST`). `INSTR` for "płatności kartą, ...". `PARENS` for the refund line | grammar | D P Z |
| `payment_cards` (feature) | Cards accepted: turns on "Sprzedawca nie przechowuje pełnych numerów kart" | Payments | R D P |
| `PAYMENT_PROVIDER_NAME` | Brand: Stripe, PayU, Przelewy24, Tpay, Autopay | Payments | R D |
| `payment_stripe` (feature) + `STRIPE_CONTRACT_ENTITY`, `STRIPE_SERVICES_ENTITY`, `STRIPE_US_ENTITY`, `STRIPE_COUNTRY` | Stripe's EEA contracting entity for the merchant's country (2026-11-20 audit: **Stripe Payments Europe, Limited** (Ireland), payment services by **Stripe Technology Europe, Limited**, US importer **Stripe, LLC**; official spelling "Limited"). Check the merchant's own Stripe dashboard: Settings > Business > Legal entity, and the Stripe Services Agreement for Poland (stripe.com/legal/ssa/pl) and stripe.com/legal/dpa **[verify date 2026-11-20; re-check on use]** | Stripe dashboard (owner screenshot, no keys) | P |
| non-Stripe provider: `PAYMENT_PROVIDER_ENTITY`, `_COUNTRY`, `_PRIVACY_URL`, `_ROLE_TEXT`, `payment_provider_transfers` + `PAYMENT_PROVIDER_TRANSFER_TEXT` | Legal entity from the provider's merchant agreement, its privacy policy URL, one sentence on controller/processor role and any transfer outside the EEA | provider agreement and privacy policy | P |
| `express_checkout` (feature) + `EXPRESS_WALLETS_TEXT` | Apple Pay / Google Pay / Link buttons that place an order from the product, cart or checkout page without the standard order button. Art. 17 ust. 2-4 UPK: the button/label must say "zamówienie z obowiązkiem zapłaty" or equivalent, else **no contract is concluded** (ust. 4) **[audit 2026-11-20]**. Decision: label them or disable them (`wordpress-woocommerce-setup.md` section 6) | live DOM of product, cart, checkout | R |
| BLIK / Przelewy24 / BNPL (Klarna) | Offered only if enabled and tested. Stripe-registered methods that are not offered still show up in the Store API: compare `payment_methods` with what the documents say | Store API, checkout | R D P |
| statement descriptor | Text on the card statement. Set in the Stripe dashboard (not WooCommerce) to a recognisable name; wrong descriptors cause chargebacks | Stripe dashboard | owner |
| sales document | What the buyer gets: receipt from a cash register, e-receipt, invoice on request. **Accountant decides**. Cash-register exemption for mail-order sales: Rozporządzenie MF of 17.12.2024 (Dz.U. 2024 poz. 1902), annex item 41, needs payment through bank/post/SKOK with buyer identification; card payments via a payment institution are a grey area [audit 2026-11-20] | accountant, written | R D |
| invoice on request | Art. 106b ust. 3 pkt 1 VAT Act, request within 3 months from the end of the month of delivery or payment (NOT ust. 1 pkt 1) [audit 2026-11-20] | ISAP | R D |

## D. Data processing: who gets what (privacy policy rows and cookie policy tables)

| Variable / feature | Exact meaning | Verify where | Used in |
|---|---|---|---|
| `HOSTING_PROVIDER_NAME` / `_COUNTRY` / `HOSTING_GROUP_NAME` / `HOSTING_PRIVACY_URL` | The entity in the hosting contract (Hostinger: Hostinger International Ltd., Cyprus; the DPA may instead be executed by Hostinger UK Limited or Hostinger Global S.a r.l.), group brand, privacy URL | invoice and DPA in the hosting panel | P |
| `hosting_transfers_outside_eea` + `HOSTING_TRANSFER_TEXT` | Whether the host may process data outside the EEA and under what safeguard (SCC, adequacy). Copy the provider's own wording; do not state the server country unless the plan says so | provider privacy policy, DPA | P |
| mail provider | Same entity as the host for host mailboxes; a separate provider (Brevo, Google Workspace, SMTP plugin) must be added via `EXTRA_RECIPIENTS_HTML` and, if outside the EEA, `EXTRA_TRANSFERS_HTML` | DNS (MX, SPF includes), mail plugin | P |
| accountant | A recipient category ("zewnętrzne biuro rachunkowe"); name only if the privacy lawyer wants it. A processor agreement is needed if the accountant processes on instruction (usually a separate controller/processor: lawyer) | owner | P A |
| `ga4`, `google_ads` (features) + `GOOGLE_EU_ENTITY`, `GOOGLE_US_ENTITY`, `GA4_ID_SUFFIX` | Google tools that are really loaded (Phase 1 browser measurement). `GOOGLE_EU_ENTITY` = contracting entity in the Google account terms (Google Ireland Limited for EEA). GA4 cookie `_ga_<suffix>`: the suffix is the measurement ID without `G-` | live cookies | P C |
| `google_consent_mode_pings` | Measured: with consent default "denied" do Google tags still load and send cookieless pings (`gcs=G100`, `npa=1`) before the choice? If yes the documents must say so. Stricter alternative: do not load tags before consent (then set false and delete the pings text) | headless Chrome network log | P C |
| `google_fonts_remote` + `REMOTE_FONT_NAME` | Measured: any request to `fonts.googleapis.com`/`fonts.gstatic.com`? Prefer to self-host and set false | network log | P C |
| `order_attribution` | WooCommerce Order Attribution cookies `sbjs_*` (feature on in WooCommerce > Settings > Advanced > Features). Prefer OFF | cookies before consent | P C |
| `hostinger_reach_script` + `HOSTING_CDN_SCRIPT_HOST` | Host-injected scripts (e.g. `cdn-reach.hostinger.com/js/embed.js`) loaded on every page | network log | C |
| `cookie_hcdn`, `cookie_cf_bm` | Cookies measured on the shop's domain (`hcdn`) or from third-party anti-bot scripts (`__cf_bm`, 30 minutes) | cookie list | C |
| `no_other_trackers`, `share_links` | Statement that no other ad pixels/trackers load; that share buttons are plain links. Only true after the measurement | network log, page source | C |
| `other_consent_tools` + `EXTRA_*_HTML` | Meta Pixel, TikTok, Hotjar, Clarity, Klaviyo, live chat etc.: rows are added by hand through the `EXTRA_*` variables (4-cell `<tr>` rows) | network log | P C |
| `CONSENT_COOKIE_NAME`, `CONSENT_COOKIE_DAYS` | Name and lifetime of the consent cookie of the theme/plugin (2acetech: `ace_consent`, 180 days) | cookies | C |
| `withdrawal_ip_hash` | The withdrawal form stores an IP hash for 1 hour for rate limiting (true with the skill's theme code). If the form stores or logs the IP differently, rewrite privacy row "Ochrona przed nadużyciami" | code | P |
| `reviews` + `review_verified_label`, `review_remember_cookie` | Reviews on/off; "verified owner" label (WooCommerce > Settings > Products); WordPress comment cookie opt-in checkbox. If reviews are on, DSA hosting duties and UPNPR art. 6 ust. 4 pkt 7 (say whether/how reviews are verified) apply **[audit 2026-11-20]** | WooCommerce > Settings > Products | R P C S |
| `newsletter` + `newsletter_double_optin`, `NEWSLETTER_PROVIDER_NAME`, `newsletter_provider_outside_eea`, `NEWSLETTER_PROVIDER_TRANSFER_TEXT` | Newsletter exists? Tool? Double opt-in? Consent log (date, text version, source)? Unsubscribe link in every mail? Art. 398 PKE: prior consent for electronic commercial information. **The 2acetech theme form does none of these**; do not switch the feature on without building them | plugin settings | R P |
| `contact_form` | A form on the site that sends messages. Needs a controller note under the form (art. 13 RODO) | site | R P |
| `abandoned_cart` + `ABANDONED_CART_TOOL`, `ABANDONED_CART_RETENTION` | Abandoned-cart reminders (Woo Abandoned Cart Recovery, Klaviyo, Brevo...). Needs consent before the mail (art. 398 PKE) and a GDPR switch in the plugin | plugin | P |
| `CORRESPONDENCE_RETENTION_MONTHS` | How long e-mail correspondence is kept after the last contact (template default 12). Delete mailboxes accordingly | owner | P |
| `dpo_appointed` + `DPO_CONTACT` | Normally false: a DPO is not required for ordinary online retail (art. 37 ust. 1 RODO) [audit 2026-11-20] | owner | P |
| `supplier_outside_eea` + `SUPPLIER_TRANSFER_TEXT` | Any supplier or marketplace that receives customer data outside the EEA (AliExpress platform, Chinese warehouse) needs a transfer mechanism (art. 44-49 RODO; art. 49 ust. 1 lit. b only for occasional transfers) | supplier terms | P |

## E. Returns, complaints, guarantees, goods

| Variable / feature | Exact meaning | Verify where | Used in |
|---|---|---|---|
| `return_cost_customer` | The consumer pays the direct cost of returning (art. 34 ust. 2 UPK, statutory default **if the seller informed before the contract**); false = seller pays (`RETURN_COST_SELLER_MECHANISM`: return label or refund of documented cost) | owner decision | R Z |
| `bulky_goods` + `BULKY_GOODS_TEXT`, `BULKY_RETURN_COST_ESTIMATE` | Goods that cannot be returned by normal post: the estimated maximum return cost must be given before the order (art. 12 ust. 1 pkt 10 UPK), else the consumer does not bear it | owner, courier price list | R Z |
| `CATALOG_CATEGORIES_TEXT` | Categories in one phrase; used where the text says "at present the shop has no goods that cannot be returned by post". Update when the catalogue changes | catalogue | R Z |
| `withdrawal_exceptions` + `WITHDRAWAL_EXCLUDED_GOODS_TEXT` | Goods excluded from withdrawal (art. 38 UPK: custom-made, sealed hygiene goods, sealed recordings, perishable, ...). Must be marked on the product page | catalogue | R Z |
| `own_guarantee` + `OWN_GUARANTEE_TEXT` | The seller's own guarantee. Content required by art. 577(1) para. 2 KC; handed over with the goods (art. 577(3) KC); advertising statements bind (art. 43g UPK) **[audit 2026-11-20]**. Never claim "gwarancja na każdy produkt" because of a manufacturer card | owner | R K |
| manufacturer guarantees | List products that really ship with a Polish-language guarantee card. Otherwise the text says "if attached" | supplier | R K |
| `adr_participation` + `ADR_BODY_NAME`, `_ADDRESS`, `_URL` | Default false (no commitment to ADR; statement per art. 32 ADR Act after an unresolved complaint). If true, name the body (list: polubowne.uokik.gov.pl) | owner | R K |
| `weee` + `WEEE_EXAMPLES` | The shop sells electrical/electronic equipment or batteries: turns on free take-back text (art. 37 ust. 2 ZSEE Act, Dz.U. 2024 poz. 573 [audit 2026-11-20]; amended by Dz.U. 2026 poz. 176 from 18 Feb 2027: re-check) and "wprowadzający" duties. **The text promises a service: arrange it first** (courier take-back or a collection organisation) | supplier + courier agreement | R D |
| `digital_goods` + `DIGITAL_GOODS_EXAMPLE` | Goods with digital elements (app-controlled, firmware): update duty art. 43b ust. 3, 43k ust. 3 UPK | catalogue | R K |
| special-risk goods | **Toys** (Dir. 2009/48/EC: CE, warnings visible before purchase, age grading; repealed by Reg. (EU) 2025/2509 from 1 Aug 2030), **electrical/lighting** (LVD 2014/35/EU, EMC 2014/30/EU, RED 2014/53/EU for Wi-Fi/Bluetooth, RoHS 2011/65/EU, ecodesign/energy label Reg. 2019/2020 and Del. Reg. 2019/2015), **batteries / lithium (hoverboards, scooters)** (Reg. (EU) 2023/1542; transport rules), **cosmetics** (Reg. (EC) 1223/2009: responsible person, CPNP notification, PIF), **food and supplements** (separate regime: do not sell without an adviser), **children's articles** (GPSR + sector rules), **PPE, medical devices** (out of scope: do not sell). Each category listed here needs a documents folder before the product is published (GPSR block, Phase 3) | supplier documents | G U |
| `vat_exempt` | see A | | R D |

## F. Technical and operational facts

| Fact | Verify where |
|---|---|
| WordPress, WooCommerce, theme, PHP versions; block vs classic checkout | wp-admin > Tools > Site Health > Info; `curl -s $SHOP/wp-json/` |
| Hosting plan, PHP `max_input_vars` (AliDropship needs it high), `memory_limit`, `.htaccess` supported? (LiteSpeed/Apache) | hPanel > PHP configuration; Site Health |
| Active plugins and what each loads (the cookie policy must match) | wp-admin > Plugins; network log |
| Stripe (or other) mode: **live vs test keys**. 2acetech had a LIVE Stripe key before any legal page existed | WooCommerce > Settings > Payments (do not open keys; read the mode label only) |
| Languages: shop language Polish; documents Polish; English UI strings still visible? | site front end |
| Who processes withdrawals, complaints, safety reports, data-subject requests, and how fast (14 days for complaints; 14 days for refunds) | owner (process owner named in OPEN-ITEMS) |
| Languages of customers: Polish consumers only | owner |

## G. Questions that produce decisions (record in OPEN-ITEMS section d)

1. Sell only to consumers (B2C)? (Templates: yes. B2B needs its own Regulamin.)
2. Who pays returns? 3. Newsletter yes/no. 4. Google Analytics/Ads yes/no (default recommendation: off until consent flow verified). 5. Reviews yes/no.
6. ADR commitment yes/no (default no). 7. Own guarantee yes/no (default no).
8. Delivery promise (dispatch/transit) and whether the supplier signs it.
9. Express buttons: label or disable. 10. Take-back of old equipment: how.
11. Pause orders until Critical items are fixed (set WooCommerce > Settings > Site visibility or switch off the payment method).
12. VAT/sales document handling (accountant).

## H. Verification checklist for the facts above

- [ ] KRS API and white list agree on name, NIP, REGON, address; extract state date recorded.
- [ ] Registry court and representation confirmed on the PDF extract.
- [ ] E-mail mailbox exists and receives mail; SPF/DKIM/DMARC valid (one DMARC record only).
- [ ] Phone answers.
- [ ] Return address: written consent if third party.
- [ ] Every supplier: warehouse country, dispatch/transit times, tracking, documents (GPSR, CE, instructions in Polish), BDO number, returns acceptance in writing.
- [ ] Live-site measurement done (requests and cookies before consent, after reject, after accept).
- [ ] Store API payment methods equal the methods named in the texts.
- [ ] Accountant's written answer on VAT, sales documents, BDO.
