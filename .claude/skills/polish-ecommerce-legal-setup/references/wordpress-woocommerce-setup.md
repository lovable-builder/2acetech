# WordPress / WooCommerce / plugin / hosting settings (exact values)

Menu paths are given as **English label (Polski label)**. WooCommerce, WordPress and plugins rename items
between versions: if a label is not found, search the settings page, do not guess. Every row has a target value,
the reason, and a way to verify.

**[OK]** = changes a live setting or a plugin: needs the owner's explicit OK in the conversation before it is done
(see "Everything that needs the owner's OK" in `SKILL.md`). Reading a setting never needs OK; **never open, type,
copy or print API keys, passwords, secret keys or bank-account numbers**; if a screen shows one, do not read it
out. Where it is possible to read settings without a browser, use the public Store API or ask the owner for a
screenshot with secrets covered.

Admin-label verification date: labels checked from vendor documentation on 2026-11-21 where cited
(WooCommerce order attribution, Stripe express checkouts, Site Kit consent mode); everything else is from the
2026-11-20 audit of 2acetech and must be confirmed on screen.

Common admin labels, English -> Polish (from the pl_PL translations; confirm on screen, versions differ):

| English | Polski | English | Polski |
|---|---|---|---|
| Settings | Ustawienia | WooCommerce > Settings | WooCommerce > Ustawienia |
| General | Ogólne | Products | Produkty |
| Tax | Podatki | Shipping | Wysyłka |
| Payments | Płatności | Accounts & Privacy | Konta i prywatność |
| Emails | E-maile | Advanced | Zaawansowane |
| Page setup | Konfiguracja stron | Features | Funkcje |
| Reading | Czytanie | Discussion | Dyskusja |
| Privacy | Prywatność | Permalinks | Bezpośrednie odnośniki |
| Pages | Strony | Plugins | Wtyczki |
| Users | Użytkownicy | Tools | Narzędzia |
| Site Health | Kondycja witryny | Orders | Zamówienia |
| Customize | Dostosuj | Appearance | Wygląd |

Read-only checks that need no login:

```bash
SHOP=https://example.pl
curl -s $SHOP/wp-json/wc/store/v1/cart | python3 -I -m json.tool | head -80        # payment methods, shipping, taxes, storePages (terms/privacy ids)
curl -s "$SHOP/wp-json/wc/store/v1/products?per_page=5" | python3 -I -m json.tool | head -60
curl -sI $SHOP | head -20                                                            # headers (HSTS, CSP, cache)
curl -s $SHOP/robots.txt ; curl -s $SHOP/wp-sitemap.xml | head -30
```

---

## 1. WordPress core

| Path | Setting | Target | Why / verify |
|---|---|---|---|
| Settings > General (Ustawienia > Ogólne) | Site Language (Język witryny) | Polski | Polish UI strings; check `<html lang="pl-PL">` |
| | Timezone (Strefa czasowa) | Warsaw (UTC+1/+2) | withdrawal acknowledgement timestamps and "14 days" arithmetic; check withdrawal e-mail time |
| | Site Title / Tagline | real shop name; no marketing claim you cannot prove | footer, banner, e-mail sender; see truth table |
| | Administration E-mail | monitored company mailbox | breach and WooCommerce admin mails |
| | Membership "Anyone can register" (Każdy może się zarejestrować) | OFF | WooCommerce handles customer accounts; open registration invites spam accounts and extra personal data |
| | New User Default Role | Customer (Klient) if WooCommerce is active, else Subscriber | least privilege |
| Settings > Reading (Czytanie) | Your homepage displays | A static page = the theme front page | |
| | Search engine visibility | **unticked at launch**; ticked while the shop is not public | `curl -s $SHOP | grep -i noindex` |
| Settings > Discussion (Dyskusja) | Comment author must fill out name and email | ON | reviews |
| | Show comments cookies opt-in checkbox | **ON** | the "remember me" cookies (comment_author_*) are set only after the visitor ticks: matches the cookie policy block 4.2 |
| | Comment must be manually approved / previously approved | ON (moderate reviews) | the Regulamin promises checks for unlawful content |
| | Avatars: Show Avatars | **OFF** | Gravatar would receive a hash of the reviewer's e-mail (Automattic); the theme also forces it off. Verify: no `gravatar.com` in the network log |
| | Allow link notifications from other blogs (pingbacks) | OFF | |
| Settings > Privacy (Prywatność) | Privacy Policy page | the real `polityka-prywatnosci` page | `wp_page_for_privacy_policy`; the stock draft stub must be replaced |
| Settings > Permalinks (Bezpośrednie odnośniki) | Structure | Post name `/%postname%/` | clean slugs for legal pages; click Save once to flush after adding pages |
| Tools > Export / Erase Personal Data (Narzędzia > Eksport/Usuwanie danych osobowych) | procedure for art. 15/17 RODO requests | document the steps in OPEN-ITEMS; WooCommerce adds customer and order data to these tools | test with a dummy customer; erasure must not delete accounting documents (art. 17 ust. 3 lit. b RODO; keep orders for tax) |
| Users (Użytkownicy) | admin accounts | one named account per person, no user called `admin`, 2FA on every administrator, shop staff = Shop manager | `wp user list` or Users screen; the author archive and `wp-sitemap-users-1.xml` leak usernames: disable the users sitemap |
| Plugins | unused plugins | deactivate and delete | each plugin adds scripts, data and update duty (art. 25, 32 RODO) |
| Site Health (Narzędzia > Kondycja witryny) | PHP >= 8.1, HTTPS, background updates | green | PHP version and `max_input_vars` matter for dropshipping plugins (see 11) |

## 2. WooCommerce > Settings > General (Ogólne)

| Setting | Target | Why |
|---|---|---|
| Store address | registered seat of the company (street, city, PL, postcode) | WooCommerce uses it for taxes and e-mails; must equal the Regulamin |
| Selling location(s) (Lokalizacja sprzedaży) | **Sell to all countries** | Reg. (EU) 2018/302 art. 4(1)(a): a consumer from another EU country must be able to order to a Polish address; do not geo-block (audit M-13) |
| Shipping location(s) | **Ship to specific countries only: Poland** | the Regulamin delivers only in Poland |
| Default customer location | "No location by default" or "Shop base address" | avoid third-party geolocation calls |
| Enable taxes (Włącz podatki) | ON (unless `vat_exempt`) | gross prices with VAT shown; see 4 |
| Enable coupons | per owner. If coupons exist, they must not hide the price: Regulamin § 3 ust. 4 | |
| Currency (Waluta) | **Polish złoty (PLN) zł** | |
| Currency position | **Right with space** | `1 234,56 zł` |
| Thousand separator | a space | `1 234,56 zł` |
| Decimal separator | `,` | |
| Number of decimals | 2 | |

Verify: view a product page and the cart: "1 234,56 zł brutto" (live 2acetech showed "zł227.99" before this was fixed).

## 3. WooCommerce > Settings > Products (Produkty)

| Setting | Target | Why |
|---|---|---|
| Shop page | the Polish "Sklep" page | |
| Add to cart behaviour | stay on page (AJAX), no forced redirect | no resubmission prompts; theme handles notices |
| Weight/dimension units | kg / cm | return cost, bulky goods |
| Reviews: Enable product reviews | **only if `reviews=true`**; else OFF (removes DSA hosting duties and the reviews text) | `reviews` feature |
| Reviews: Show "verified owner" label on customer reviews | ON | UPNPR art. 7 pkt 25-26: do not claim reviews come from buyers without checks [A]; the label is set by WooCommerce when the account/e-mail matches an order |
| Reviews: Reviews can only be left by "verified owners" | ON (safest) | if OFF, the Regulamin must say non-buyer reviews are not verified (it does when `review_verified_label=true`) |
| Star ratings | enabled; required | |
| Inventory: Manage stock | on if stock is synced from the supplier; Hold stock 60 minutes | availability claims |

Photo-review plugins add a coupon banner ("Review now to get coupon!"): see 9.

## 4. WooCommerce > Settings > Tax (Podatki)

| Setting | Target | Why |
|---|---|---|
| Prices entered with tax | **Yes, I will enter prices inclusive of tax** | gross prices (art. 3 ust. 2, 4 ust. 1 ustawy o informowaniu o cenach [A]) |
| Calculate tax based on | Customer shipping address | destination PL; for Poland-only delivery equals the shop base |
| Shipping tax class | Based on cart items | |
| Rounding | default (not rounded at subtotal) | |
| Display prices in the shop / during cart and checkout | **Including tax** | |
| **Price display suffix** | `brutto` (or `w tym VAT`) | consumer sees that the price includes VAT |
| Display tax totals | As a single total | "w tym VAT" line |
| Standard rates (Stawki) | Poland, rate per category (23% standard; 8%/5% reduced; 0%) set **with the accountant**; WooCommerce additional classes "Reduced rate", "Zero rate" | **[K]** rates; accountant confirms per product |

`vat_exempt=true`: leave taxes disabled and change price/invoice sentences with the accountant (the renderer warns).

## 5. WooCommerce > Settings > Shipping (Wysyłka)

| Setting | Target | Why |
|---|---|---|
| Shipping zones | one zone "Polska" = region Poland; **no "Locations not covered by your other zones" methods** | the Regulamin delivers only in Poland; verify Store API `shipping_rates` |
| Method | Flat rate (Stała stawka) titled exactly like `DELIVERY_METHOD_LABEL_REG`; cost `0` or the price; tax status taxable | Regulamin § 5 ust. 7 table must equal checkout |
| Shipping classes | for bulky goods: cost per class; every bulky class needs the **estimated maximum return cost** in the Regulamin (`bulky_goods`) | art. 12 ust. 1 pkt 10 UPK |
| Shipping options > Hide shipping costs until an address is entered | OFF | total price visible before the order |
| Delivery time on product and checkout | text "Wysyłka do N dni roboczych, dostawa kurierem M-K dni roboczych" (Customizer field `ace_dispatch_note` in the theme) | must equal Regulamin § 5 ust. 7-8; supplier must guarantee it |

## 6. WooCommerce > Settings > Payments (Płatności)

| Item | Target | Why / verify |
|---|---|---|
| Methods enabled | **exactly the methods named in the documents** | Store API `payment_methods` must equal `PAYMENT_METHODS_LIST`: on 2acetech the audit found `stripe_klarna`, `stripe_link`, `woocommerce_payments` registered though the texts named card/Apple Pay/Google Pay |
| Direct bank transfer, Check payments, Cash on delivery | OFF unless offered | the template says prepayment only, no COD |
| Stripe (official WooCommerce Stripe extension): Payments > Stripe > Settings | enable cards; express checkout toggle; disable Link, Klarna, Afterpay etc. unless offered | per WooCommerce docs (checked 2026-11-21): express checkout options are on WooCommerce > Settings > Payments > Stripe; **Apple Pay and Google Pay share one toggle** and cannot be switched separately; button size is shared |
| Express checkout placement | Product / Cart / Checkout: choose; **label or disable** | art. 17 ust. 2-4 UPK: if the order button/label is not "zamówienie z obowiązkiem zapłaty" (or equivalent) **no contract is concluded**. Options: (a) disable express checkout until a lawyer approves wording; (b) keep, use a "buy" type button if your version has the setting (the current official extension may not: checked 2026-11-21, not found), and show a visible text next to the buttons: "Klikając przycisk płatności składasz zamówienie z obowiązkiem zapłaty". The Regulamin § 4 ust. 3 says the express order also carries the obligation; keep that sentence only if true |
| Link by Stripe | OFF unless intended | Stripe may offer saving payment data (privacy § 3 mentions it generally) |
| WooPayments | deactivate unless it is the chosen provider | a second payment provider doubles processors |
| Test / live mode | **live only after the owner approves**; test with Stripe test cards first; confirm which mode is active (read the label, never the keys) | on 2acetech the checkout was live with a live key while no legal page existed |
| Statement descriptor | set in the **Stripe dashboard** (Settings > Business > Public details / Statement descriptor), not WooCommerce; shortened descriptor used for card/express | recognisable name prevents chargebacks |
| BLIK / Przelewy24 | Stripe supports BLIK and Przelewy24 for PLN **[K, verify in Stripe dashboard Settings > Payment methods]**; enable in the Stripe dashboard and in the plugin; asynchronous: order stays on-hold until confirmation: **the confirmation e-mail trigger (Regulamin § 4 ust. 4) must still be "payment succeeded"**; update `PAYMENT_METHODS_*`, privacy, cookie policy; test each method | |
| Stripe webhooks | configured and green (WooCommerce > Settings > Payments > Stripe > "Webhook") | otherwise paid orders stay pending and the contract timing is wrong |

## 7. WooCommerce > Settings > Accounts & Privacy (Konta i prywatność)

| Setting | Target | Why |
|---|---|---|
| Allow customers to place orders without an account | per `guest_checkout` | the Regulamin § 2 ust. 5 states it |
| Allow customers to log into an existing account during checkout | ON if accounts | |
| Allow customers to create an account on "My account" / during checkout | ON only if `accounts=true` | |
| Account creation: automatically generate username / "Send password setup link" | ON (link) | no password in e-mail |
| Privacy policy page | the Polityka prywatności page | checkout and registration text |
| Registration privacy policy / Checkout privacy policy text | Polish: "Twoje dane osobowe będą wykorzystywane do realizacji zamówienia, obsługi Twojego konta i w innych celach opisanych w naszej [privacy_policy]." | keeps the notice at the point of collection (art. 13 RODO) |
| Personal data retention | Inactive accounts: 24 months (or the owner's value, must equal what the policy says); pending orders 2 days; failed 10 days; cancelled 10 days; **completed/refunded orders: do not auto-delete before the accounting and tax periods end** (art. 74 ust. 2-3 ustawy o rachunkowości, art. 112 VAT Act; privacy row "tax") | what the policy promises must be what the system does |
| Account erasure requests: remove personal data from orders | **unticked** unless the accountant confirms the orders are not accounting evidence | art. 17 ust. 3 lit. b RODO (legal obligation) |
| Marketing checkbox at checkout | only if `newsletter` or `abandoned_cart`; unticked by default; separate from the Regulamin checkbox | art. 398 PKE consent |

## 7a. Checkout page (block editor)

Pages > Checkout > Edit (Strony > Zamówienie > Edytuj). The classic shortcode checkout also works; the theme
code covers both (`theme-implementation.md` 4).

| Block / setting | Target | Why |
|---|---|---|
| Contact information / Address > Phone number | Optional (or Required if the carrier needs it; then edit the phone sentences in the templates) | `phone_optional` |
| Address > Company field | Hidden (consumer shop) | B2C only |
| Terms and conditions block > "Require checkbox" | **ON** (the theme sets it and the text with links when the Regulamin is published) | art. 8 ust. 1 UŚUDE; proof the Regulamin was made available |
| Order summary block | shows goods, total incl. VAT, shipping | art. 17 ust. 1 UPK |
| Place order button | label "Zamówienie z obowiązkiem zapłaty" (theme filter) | art. 17 ust. 3 UPK |
| Express checkout block (Apple Pay/Google Pay) | label or disable (see 6) | art. 17 ust. 4 UPK |
| Additional notes ("Add a note to your order") | optional; goes to the supplier in dropshipping: privacy row "order" covers it | |
| Pre-ticked boxes | none | |

Verify in a browser (`verification-and-tests.md` 5).

## 8. WooCommerce > Settings > Emails (E-maile)

| Setting | Target | Why |
|---|---|---|
| "From" name / address | company or brand; address on the shop domain (e.g. kontakt@domain), with SPF/DKIM/DMARC | deliverability of withdrawal acknowledgements (art. 30 ust. 4 UPK) |
| Header image / base colour | brand | |
| Footer text | `{COMPANY_NAME_FULL}, {SEAT_ADDRESS}, KRS {KRS}, NIP {NIP}` (literal values) | seller identity on a durable medium |
| Customer "Processing order" and "On-hold order" | ENABLED; Polish subject/heading/additional content; **Regulamin text in the body** via theme code (`theme-implementation.md` 6) | art. 21 UPK durable medium; Regulamin § 4 ust. 7 |
| Customer "Completed", "Refunded", "Failed order" | Polish; "Failed order" must not carry the Regulamin or say the order was accepted | consistent with § 4 ust. 4 |
| Admin recipients | monitored mailbox | |
| Template | HTML + plain text | both must carry the same legal text |

Verify with a real test order and a refund (Phase 5), in two mail clients and in the spam folder.

## 9. Reviews, coupons, abandoned carts, marketing plugins

| Plugin / feature | Decision rule | Settings |
|---|---|---|
| Photo-review plugin with "Review now to get coupon!" | Either remove the coupon feature or: disclose it, give it regardless of rating, translate the banner (UPNPR art. 6 ust. 3 pkt 2; art. 7 pkt 25-26 [A]); then add the Regulamin reward sentence | photo uploads: limit size/count; list photos in privacy row "reviews"; verified owners only |
| Abandoned-cart plugin (e.g. Woo Abandoned Cart Recovery) | default: **deactivate**. If kept: its GDPR switch ON; consent checkbox at checkout; reminders only to consenting users; retention set; `abandoned_cart=true` | art. 398, 399 PKE; art. 6, 7, 13 RODO |
| Hostinger Reach / marketing plugin | default: **deactivate** unless forms are used; if used, load only on the page with the form and list it in the cookie policy | the script `cdn-reach.hostinger.com/js/embed.js` loaded on every page of 2acetech with no form |
| Newsletter plugin | only with consent text, double opt-in, consent log, unsubscribe link | `newsletter=true` |
| Pop-ups / "someone purchased" notifications | never fake social proof (UPNPR art. 5, 7 [A]); deactivate | |

## 10. WooCommerce > Settings > Advanced (Zaawansowane)

| Tab / setting | Target | Why |
|---|---|---|
| Page setup > Cart / Checkout / My account | Polish pages | |
| Page setup > **Terms and conditions** | the Regulamin page | enables the checkout checkbox text in the theme |
| Features > **Order Attribution** (Atrybucja zamówień) | **OFF** | sets `sbjs_first`, `sbjs_current`, `sbjs_first_add`, `sbjs_current_add`, `sbjs_udata`, `sbjs_migrations` (session) and `sbjs_session` (30 min) from the first visit; WooCommerce docs say it works with WP Consent API; option `woocommerce_feature_order_attribution_enabled` = `no` (checked 2026-11-21). If kept ON: cookie policy block 4.3 and privacy row "order_attr" stay |
| Features > High-performance order storage | ON (compatibility mode off after migration) | |
| WooCommerce.com > **Enable tracking / usage data** | **OFF**; marketplace suggestions OFF | no telemetry to Automattic |
| REST API / Webhooks | no keys that are not used; revoke old keys | |

## 11. Dropshipping plugins

**AliDropship (free version)** - legal-relevant settings:

| Setting | Value | Why |
|---|---|---|
| Import "publish status" | **Draft** | nothing goes live before GPSR data, price and text review |
| "Ignore ship from" / ship-from filter | do not ignore: a variation whose "ship from" is not Poland breaks the "Polish warehouse" promise; set the default to Poland only; delete other variations | Regulamin § 5 and delivery page claim Polish warehouses |
| Default phone country | PL (+48) | courier data |
| Fulfilment statuses / "Ali Order" | Pro-only: the free version leaves ordering to the owner | process, privacy rows (data to the platform) |
| PHP `max_input_vars` | >= 3000 (variations with many options) | otherwise saving a product silently drops variations |
| Price rules | no "regular price" markers that were never charged (Omnibus) | `reference-price` rules in `facts-vs-claims-truth-table.md` |

**Shelfbridge (store-to-store supplier connection)**: what it does on 2acetech (read-only audit 2026-11-20): products come from the supplier's WooCommerce store; the shop price = supplier wholesale + a markup, hourly sync; when an order is "processing" the plugin sends **items, customer shipping address, phone and note** to the supplier's site, which creates an order billed to the shop; the supplier invoices the shop; tracking is polled back. Consequences: the supplier is a **recipient (processor or separate controller)** in the privacy policy (`name_suppliers_in_privacy`); a supplier agreement (`wzor-umowy-dropshipping`) and an art. 28 agreement (`wzor-umowy-powierzenia-danych`); related-party documentation if the supplier is a connected company (see `dropshipping-models.md` B). Settings to check: what customer fields are sent (name, address, phone only; no e-mail unless required), sync interval, "publish new products as draft", stock sync, tracking e-mail text (must not promise delivery dates that the Regulamin does not).

Product-text plugins that use an AI service (e.g. a Product Cleaner): send product text only, never customer data; a human must approve every text before publishing (public assurances bind: art. 43b ust. 2 pkt 2 UPK [A]).

## 12. Google (Site Kit, Google for WooCommerce, GA4, Ads)

| Item | Target | Why / verify |
|---|---|---|
| Decision | **off until the consent flow is verified**; then GA4 only, Ads remarketing only if needed | cookies and pings per cookie policy |
| Consent Mode default | **`ad_storage`, `ad_user_data`, `ad_personalization`, `analytics_storage` = denied, printed as the very first script in `<head>` (wp_head priority 0)** by the theme; Site Kit: Site Kit > Settings > Admin Settings > Consent Mode: enable (checked 2026-11-21: adds default preferences; EEA/CH/UK excluded by default; works with WP Consent API) | verify with headless Chrome: order of scripts and `gcs=G100` pings |
| Duplicate GA4 snippets | exactly one measurement tag: search page source for the measurement ID; on 2acetech a second snippet (not in the theme repo) duplicated Site Kit's | `curl -s $SHOP | grep -o 'G-[A-Z0-9]\{8,12\}' | sort | uniq -c` |
| Google for WooCommerce | conversion tracking only after consent; remarketing/enhanced conversions OFF | it prints its own consent default after Site Kit unless ordered |
| GA4 > Admin > Data collection and modification > Data retention | event data **14 months** (maximum for standard) or 2 months | privacy row says "2 or 14 months, per account setting" [A] |
| GA4 Google signals / ads personalization | OFF | marketing needs separate consent |
| Ads | conversion/remarketing tags only after marketing consent | |
| Fonts | self-hosted (the theme self-hosts); find any `fonts.googleapis.com` request (DevTools > Network > Initiator; deactivate plugins one by one) | Google Fonts remote loading discloses the IP to Google (audit M-3) |

## 13. Hostinger (hosting, mail, DNS)

| Item | Target | Verify |
|---|---|---|
| Force HTTPS | ON | `curl -sI http://domain` -> 301 to https |
| **HSTS** | `Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"` in `.htaccess` after HTTPS works everywhere; no `preload` | `curl -sI https://domain | grep -i strict` |
| Other headers | `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`, `X-Frame-Options: SAMEORIGIN`. **Do not add a restrictive CSP or Permissions-Policy without testing Stripe/Apple Pay** | test an express checkout button after every header change |
| Mail DNS | SPF includes every sender (host mail; Brevo only if used); **one DMARC record only** (two records = invalid: found on 2acetech); DKIM enabled in hPanel (Emails > DKIM); start DMARC with `p=none` + `rua` mailbox, tighten later | `dig +short TXT _dmarc.domain` returns one record; send a test to a Gmail address and read "Show original" (SPF/DKIM/DMARC pass) |
| Hostinger Reach / Tools / AI plugins | deactivate unused | network log |
| Caching | LiteSpeed Cache: never cache cart/checkout/account; purge after legal page publication | |
| Backups | daily (plan) **plus** an off-site copy; retention <= 30 days (backups hold personal data; privacy "retention" must be true) | restore test once |
| Server logs | host retention not under the owner's control: privacy row "logs" says so | |
| `.htaccess` | **deny web access to the legal source folder** inside the theme (`wp-content/themes/<theme>/legal/`), see `theme-implementation.md` 8 | `curl -I $SHOP/wp-content/themes/<theme>/legal/regulamin-sklepu.html` -> 403 |
| Hostinger WordPress "Staging" | never use customer data on staging | |

## 14. Order tracking, search and indexing

| Item | Target | Verify |
|---|---|---|
| Orders-tracking plugin page ("Orders Tracking") | Polish strings or unpublish; promise tracking in the texts only if suppliers send tracking numbers for every shipment (the 2acetech texts do not promise it) | open the page |
| robots.txt | `Disallow: /wp-admin/`, `Allow: /wp-admin/admin-ajax.php`, sitemap line | `curl $SHOP/robots.txt` |
| noindex on Cart, Checkout, My account, withdrawal confirmation with `?wd=` | WooCommerce sets noindex for cart/checkout/account; check the withdrawal page query-string states | `curl -s $SHOP/checkout/ | grep -i robots` |
| Sitemap | `wp-sitemap.xml` lists legal pages; users sitemap disabled | `curl -s $SHOP/wp-sitemap-users-1.xml` -> 404 after the fix |

## 15. After any change

Purge caches; re-run Phase 5 tests that the change can affect (cookies/requests; checkout; e-mail); update the
cookie policy variables and re-render if a plugin was added or removed; record the change in OPEN-ITEMS.
