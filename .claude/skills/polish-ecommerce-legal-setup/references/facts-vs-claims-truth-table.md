# Facts vs claims: every factual sentence in the published texts

Principle: **a legal text is a set of factual statements about the shop. Publish a statement only if the shop can back it, measured on the live site
or proven by a document.** A false statement is a misleading practice (UPNPR art. 5, 6, 7 [A]) and, in a privacy or cookie policy, an art. 13 RODO breach. 2acetech's first
privacy text said "no analytics", "no transfers outside the EEA" and "Order Attribution is off" while the live shop did the opposite (audit finding C-5).

How to use: before publishing (and after every plugin change) walk this table. For each row: take the measurement, compare, and if the result differs set the feature to
`false`, fix the setting, or use the "if false" text. Record the measurement date in OPEN-ITEMS.

## 1. Measurement recipes (read-only)

| Need | Recipe |
|---|---|
| Requests and cookies **before consent, after "reject", after "accept"** | `node scripts/measure_consent.mjs https://shop.example/ --json out.json` (fresh Chrome profile, no login). Defaults match the 2acetech banner: pass `--reject`/`--accept` selectors for another banner. Re-run in a normal browser with DevTools for confirmation (Network tab, filter by domain; Application > Cookies; "Preserve log") |
| What is a "third-party request" | the script lists hosts; compare with the policies: every host must be explained in the cookie policy section 5 or the privacy recipients |
| Google Consent Mode state | in the output look at `gcs=` (`G100` = analytics and ads denied, `G111` = granted), `npa=1`, `gcd=`; cookieless pings with `gcs=G100` mean tags load while consent is denied (disclose or block) |
| Scripts in `<head>` order | `curl -s $SHOP | grep -n -o "gtag('consent','default'\|gtag/js?id=G-[A-Z0-9]*\|googletagmanager.com/gtm.js"` : the default must come first |
| Duplicate tags | `curl -s $SHOP | grep -o 'G-[A-Z0-9]\{8,12\}\|AW-[0-9]*\|GTM-[A-Z0-9]*' | sort | uniq -c` |
| Fonts | `curl -s $SHOP | grep -o 'fonts\.g[a-z]*\.com' | sort -u` and the network log |
| Payment methods registered | `curl -s $SHOP/wp-json/wc/store/v1/cart | python3 -I -c "import sys,json; print(json.load(sys.stdin).get('payment_methods'))"` (the 2acetech audit read it from a cart that held one item; if the list is empty or missing, read the methods offered on the checkout page in a browser) |
| Shipping countries/rates | Store API cart `shipping_rates` after adding an item with a Polish address (use a test order in test mode) |
| Pages published, status codes | `for s in regulamin polityka-prywatnosci polityka-cookies dostawa-i-platnosc zwroty-i-odstapienie reklamacje dane-sprzedawcy odstap-od-umowy; do printf "%s " $s; curl -s -o /dev/null -w '%{http_code}\n' $SHOP/$s/; done` |
| Footer links | `curl -s $SHOP | grep -o '<p class="footer__legal">.*</p>'` (theme-specific class) |
| Checkout label and checkbox | browser JS in `verification-and-tests.md` 5 |
| E-mail content | a real test order, then read the received e-mail (HTML + plain text) |
| WooCommerce admin values with no browser login | ask the owner for a screenshot (secrets covered); or the REST nonce trick (lessons file, "REST nonce") from a browser session the **owner** has logged in |

## 2. Truth table

`Var/feature` = the renderer name. "If false" = what to do or write.

### Regulamin

| # | Sentence (short) | Depends on | Measure | If false |
|---|---|---|---|---|
| R1 | Company identity (name, KRS, NIP, REGON, capital, court, representation) | registers | KRS API + white list (`intake-questionnaire.md` A) | fix the variable; never publish unverified data |
| R2 | E-mail, phone | mailbox/phone exist and are served | send a mail; call; `dig MX/TXT` | remove the phone only with a lawyer; art. 12 ust. 1 pkt 3 UPK |
| R3 | "Sklep jest przeznaczony dla Konsumentów" | B2C only | owner; no company-invoice flow | separate B2B Regulamin |
| R4 | Regulamin "dostępny nieodpłatnie ... w sposób, który umożliwia jego pobranie, odtworzenie i utrwalenie" | page is public, text is HTML (printable); e-mail carries the text | open page; test order e-mail | make the page printable; fix e-mail |
| R5 | Offer only to Poland, order possible from any country with a Polish address | `Ship to Poland only`, `Sell to all countries` | Store API; try a foreign billing country in a test order | WooCommerce General settings; do not geo-block |
| R6 | Sprzedawcą jest sklep; wysyłka z magazynów Dostawców w Polsce | `dropshipping`, `WAREHOUSE_COUNTRY_LOC`, every variation's ship-from | supplier confirmation; tracking origin of a test order; plugin ship-from filter | set `dropshipping=false` or remove non-PL variations |
| R7 | "Zamówienie można złożyć jako gość" | `guest_checkout` | WooCommerce > Accounts & Privacy; checkout page logged out | change setting or text |
| R8 | Reviews: who may post; verified label; no filtering by rating; no ordered/fake reviews; moderation before/after | `reviews`, `review_verified_label`; plugin settings; actual moderation habit | WooCommerce > Products; post a test review | `reviews=false`; or fix label logic; do not claim moderation you do not do |
| R9 | DSA contact point = e-mail, communication in Polish | `reviews` | the mailbox reads Polish | `reviews=false` |
| R10 | Prices gross incl. VAT | Tax settings; `vat_exempt` | product page price + "brutto" suffix; cart tax line | set `vat_exempt` text or fix tax settings |
| R11 | Delivery is free (0 zł) | `free_delivery`, shipping method cost 0 | Store API `shipping_rates` | `free_delivery=false` + `DELIVERY_PRICE_TEXT` |
| R12 | Omnibus: shows lowest price of 30 days next to reductions | price history implemented | find a product on sale; page source | remove sale prices (default) |
| R13 | No payment surcharge | no fee plugin | checkout totals for each method | remove fee or the sentence (art. 383(1) KC forbids exceeding the seller's cost) |
| R14 | Manufacturer data and Polish instructions available on request | GPSR documents per product | open 3 random products; ask supplier | no documents = no sale |
| R15 | Button reads "Zamówienie z obowiązkiem zapłaty"; checkbox; summary shows goods and total | theme code, Terms page published | browser JS (`verification-and-tests.md` 5) | fix theme code; publish Terms page |
| R16 | Express wallet order also carries the obligation | `express_checkout`, labelling decision | product/cart/checkout DOM | disable express, or label and keep the sentence |
| R17 | Confirmation e-mail sent after successful payment; contract concluded then; carries Regulamin | WooCommerce e-mail triggers and theme hook | test order; unpaid test | change trigger or § 4 ust. 4 |
| R18 | Payments: only listed methods; prepayment; provider named; no COD | Payments settings | Store API `payment_methods` | disable extra methods or add them to all texts |
| R19 | Failed payment = no order | gateway behaviour | failed test card | adjust text |
| R20 | Invoice on request in 3 months | accountant process | test request | process or text |
| R21 | Delivery table: label, price, dispatch (binding), transit (forecast); total up to N days | supplier written guarantee | supplier contract; 5 test orders measured | lengthen the binding time to what the supplier guarantees |
| R22 | 30-day outer delivery limit | own promise | n/a | remove or lengthen |
| R23 | WEEE: take-back on request, e-mail before or on delivery day, organised | arrangement with courier/supplier/organisation | dry run: ask for a take-back | `weee=false` text off is NOT allowed if you sell equipment; arrange it or ask a lawyer |
| R24 | Withdrawal function: footer, My account > Orders, thank-you page, customer e-mails | theme code; page published | browse each place; submit a test | fix theme; publish page |
| R25 | Acknowledgement e-mail with date and time "bez zbędnej zwłoki" | SMTP/DMARC | submit; read the mail within minutes | fix mail delivery |
| R26 | Return address accepts returns | `RETURN_ADDRESS` is served | owner; letter test | change address or get the operator's consent |
| R27 | Return cost: consumer pays / seller pays | owner decision | n/a | the other variant |
| R28 | No goods excluded from withdrawal | catalogue | scan categories (custom-made, sealed hygiene, sealed media) | `withdrawal_exceptions=true` and mark products |
| R29 | Complaints: 14-day answer; collection at seller's cost | process, courier account | calendar rule; one test complaint | change process; the law applies anyway (art. 7a UPK) |
| R30 | No own guarantee | no guarantee card of the seller; site does not say "gwarancja" | `grep -ri "gwarancj" theme and product texts` | `own_guarantee=true` and text; or remove the claim |
| R31 | ADR: no commitment | owner | n/a | `adr_participation=true` |
| R32 | Digital elements: updates | products with apps | catalogue | `digital_goods` |
| R33 | Account deletion only by e-mail | no self-service delete | My account | adjust § 2 ust. 2 |
| R34 | Newsletter / contact form / abandoned cart blocks | the features really exist **and** the consent mechanics exist | subscribe test; consent log; unsubscribe link | switch off feature and **remove the form and handler from the theme** (2acetech theme still had accepting handlers after the texts said "no newsletter") |

### Polityka prywatności

| # | Sentence | Depends on | Measure | If false |
|---|---|---|---|---|
| P1 | Controller identity; no DPO | register; owner | n/a | `dpo_appointed` |
| P2 | Every processing purpose in the table exists, and none is missing | all plugins and forms | list: orders, account, reviews, forms, newsletter, abandoned cart, analytics, ads, attribution, logs, withdrawals | add rows via `EXTRA_PRIVACY_ROWS_HTML`; delete rows for tools not installed |
| P3 | Retention periods | system settings **actually applied** | WooCommerce > Accounts & Privacy retention; mailbox deletion routine; GA4 retention; backups <= 30 days | change settings or text; a promised deletion that never runs is a false statement |
| P4 | IP address is not stored with the withdrawal declaration; 1-hour hash | theme code | read `ace_handle_withdrawal()`; DB check of withdrawal posts | `withdrawal_ip_hash` / rewrite row |
| P5 | Recipients: suppliers, couriers, payment provider, host, accountant, advisers, Google, public bodies | contracts and tools | plugin list; DNS (mail provider); network log | add/remove bullets; `EXTRA_RECIPIENTS_HTML` |
| P6 | Payment provider's role and entity | provider agreement | Stripe dashboard Settings > Business > Legal entity; provider DPA | fix `STRIPE_*`/`PAYMENT_PROVIDER_*` |
| P7 | Hosting provider entity, country, transfers | invoice, DPA, provider privacy policy | read provider pages (date them) | fix `HOSTING_*` |
| P8 | Google LLC is certified under the EU-US Data Privacy Framework | Google's page | policies.google.com/privacy/frameworks and dataprivacyframework.gov search (**[verified 2026-11-20 on Google's page]**) | if the certification lapses: SCC statement |
| P9 | Stripe, LLC uses DPF and SCC | Stripe's DPA | stripe.com/legal/dpa | update |
| P10 | No transfers outside the EEA (only when all transfer flags are false) | all providers | each provider's DPA | any "true" flag switches the section on |
| P11 | Google tools load pings before the choice | measured | `measure_consent.mjs` phase A | stricter model or keep the disclosure |
| P12 | Order Attribution cookies from the first visit | measured | phase A cookie list | turn Order Attribution off, set `order_attribution=false` |
| P13 | Fonts fetched from Google before the choice | measured | network log | self-host; `google_fonts_remote=false` |
| P14 | Reviews: IP stored by WordPress, e-mail not public, photos | plugin settings | comment table | adjust row |
| P15 | Abandoned-cart tool and its consent | plugin | settings; checkout consent box | off or build consent |

### Polityka cookies

| # | Sentence | Measure | If false |
|---|---|---|---|
| C1 | Banner has three equal buttons "Odrzuć wszystkie / Zarządzaj plikami cookie / Akceptuję"; reject as easy as accept | screenshot + DOM size/weight; `document.querySelectorAll('.cookie__btn')` computed sizes | fix CSS; never ship a larger "accept" |
| C2 | Analytics and marketing off by default | phase A cookies (none of `_ga*`, `_gcl_*`, `IDE`, `_fbp`) | gate scripts; fix |
| C3 | Before the choice Google tags load but cookie-less | phase A: Google requests with `gcs=G100`, no Google cookie | adjust text or block the tags |
| C4 | After "reject" Google consents stay denied | phase B: no `_ga*`; `gcs=G100` | fix consent update (the 2acetech C-2 bug: `_ga` cookies after reject) |
| C5 | After "accept" `_ga`, `_ga_<id>`, `_gcl_au`, `IDE` | phase C | update tables |
| C6 | Consent cookie name and lifetime | cookie list | variables |
| C7 | Necessary cookies list (WooCommerce cart/session, WP login, Stripe `__stripe_mid` 1 year, `__stripe_sid` 30 min, `m`, `__cf_bm` ~30 min, `hcdn`) | phase A/C cookie lists on home, product, cart, checkout, login | rows in/out; flags `cookie_*`; cookies created only on cart/checkout/login must be measured **on those pages** |
| C8 | Order attribution cookie names and lifetimes (session; `sbjs_session` 30 min) | phase A | WooCommerce docs checked 2026-11-21 |
| C9 | Third-party connections: Google fonts, host script, Stripe | network log on product page and checkout | flags |
| C10 | "Sklep nie ładuje narzędzi reklamowych ani pikseli innych firm" | network log on all page types incl. checkout | `no_other_trackers=false` and list the tool |
| C11 | Share buttons are plain links | page source | `share_links=false` |
| C12 | Cookie lifetimes of Google (400 days `_ga`, 90 days `_gcl_au`, ~13 months IDE) | measured `days` | update |

### Dostawa, Zwroty, Reklamacje, Dane sprzedawcy

| # | Sentence | Measure | If false |
|---|---|---|---|
| D1 | Poland only | WooCommerce shipping zone | extend text only with a lawyer |
| D2 | Free delivery | `free_delivery` | variant |
| D3 | Dispatch N working days (binding), transit M-K | supplier contract; 5 measured orders | longer, honest numbers |
| D4 | Carrier does not appear; sender label may be another entity | test parcel | adjust § 1 ust. 8 |
| D5 | No tracking promise | n/a | add only if every shipment has one |
| D6 | Payment methods, provider | Store API | R18 |
| D7 | Take-back of old equipment is possible on request | R23 | R23 |
| Z1 | Return address, 14 days, refund in 14 days from the statement, same payment method | process | change process (law applies anyway) |
| Z2 | "Dostawa do Ciebie była bezpłatna" | `free_delivery` | variant |
| Z3 | Statutory notice and form match the UPK annexes | compare with ISAP annex 1 and 2 | re-copy from ISAP (re-verify yearly) |
| K1 | Non-conformity regime art. 43a-43g, 2 years, presumption | law | refresh via rules register |
| S1 | DSA contact point | `reviews` | remove with reviews |
| S2 | Phone call "opłata według taryfy Twojego operatora" | the phone is a normal-rate number (no 0-700, no premium) | art. 11 UPK |

### Theme and site claims outside the seven pages

| # | Claim | Rule | If you cannot back it |
|---|---|---|---|
| T1 | "Gwarancja producenta na każdy produkt" | UPNPR art. 5; art. 43g UPK | delete (done on 2acetech) |
| T2 | "Łatwe zwroty / 14 dni" as a perk | UPNPR art. 7 pkt 10 (black list) [A] | delete or reword neutrally |
| T3 | "Szybka dostawa", "z numerem śledzenia", "ekspresowa" | UPNPR art. 5 | factual times only |
| T4 | Brand strip, "Oferta tygodnia", "Bestsellery", "Najlepsze okazje" with no basis | UPNPR art. 5, 7 [A] | gate on real data (`total_sales > 0`, real sale price, real end date) |
| T5 | Countdown timers, "ostatnie sztuki", "ktoś właśnie kupił" | UPNPR art. 7 pkt 7 (false limited offer) [A] | real stock and end date only; no fake social proof |
| T6 | Struck-through "regular price" | price act art. 4; UPNPR art. 5 ust. 3 pkt 5 [A] | real earlier price + 30-day lowest price, or remove |
| T7 | "Elektronika premium", "najlepsze ceny", "numer 1" | UPNPR art. 5 (puffery vs verifiable claim) | remove superlatives you cannot prove |
| T8 | Review stars/counts on home page from imported data | UPNPR art. 7 pkt 25-26 | show only real shop reviews |
| T9 | "Bezpieczne płatności" / "szyfrowane" | HTTPS and PCI provider | keep wording factual ("płatności obsługuje Stripe") |
| T10 | Copyright line with the domain only | art. 5 UŚUDE (identify the seller) | company name |
| T11 | Product descriptions rewritten by an AI service: compatibility, power, battery life, standards | art. 43b ust. 2 pkt 2 UPK (public assurances) | human review and datasheet comparison before publishing |

## 3. Lessons that shaped the table (see also `lessons-learned-2acetech.md`)

1. **Measure first, then write.** The first cookie/privacy texts were written from assumptions; the headless-Chrome run found Google pings before consent, `_ga` cookies after "reject", Order Attribution cookies, remote Google Fonts, a Hostinger Reach script, and Stripe security cookies.
2. **Consent Mode pings are still network requests.** With "denied" defaults Google tags send cookieless pings carrying the IP, URL and user agent: disclose them or block the tags until consent.
3. **Self-host fonts** or disclose; the theme self-hosted Manrope but a plugin/snippet fetched DM Sans from Google.
4. **Order Attribution** (`sbjs_*`) is on by default in recent WooCommerce versions: turn it off or list it.
5. **Hostinger Reach** (and similar host plugins) inject an embed script on every page.
6. **Stripe-registered methods are not "offered" methods**: the Store API listed Klarna, Link and WooPayments although the texts said "karta, Apple Pay, Google Pay".
7. **Re-measure after every plugin update.** The cookie policy is a snapshot.
