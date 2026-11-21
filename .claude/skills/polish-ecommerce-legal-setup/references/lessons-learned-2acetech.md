# Lessons learned on 2acetech.pl (candid)

What went wrong, what was found wrong, and what was corrected, on the first store set up with this method (2ACE sp. z o.o., audit and texts of 20 November 2026;
skill written 2026-11-21). Use it as a checklist of **mistakes not to repeat**. Sources: `legal/AUDIT-2026-11.md`, `legal/OPEN-ITEMS.md`, git history of branch `landing-page-theme`.

## 1. What was found wrong on the live shop (before the legal work)

| # | Finding | Severity | What fixed it / status |
|---|---|---|---|
| 1 | **Checkout open with a LIVE Stripe key and 4 published products, while no legal page existed** (404 for `/regulamin/`, `/polityka-prywatnosci/`, `/kontakt/`); WooCommerce Terms and Privacy settings empty (`storePages.terms.id = 0`) | Critical | seven pages written; terms/privacy set by the seeding code; **always check the payment mode and pause orders first** |
| 2 | **False privacy text**: "no analytics, no transfers outside the EEA, Order Attribution off, no cookies except necessary", while Google, Stripe, Hostinger, Order Attribution, an abandoned-cart plugin and dropshipping suppliers were live | Critical | rewritten from measurement; truth table |
| 3 | **No Regulamin** and the checkout sentence "Kontynuując zamówienie wyrażasz zgodę na nasz Warunki i zasady oraz Polityka prywatności" was ungrammatical, unlinked, with no checkbox | Critical/High | required checkbox with links; Terms page set |
| 4 | **Tracking before consent and after "Odrzuć wszystkie"**: Site Kit printed `gtag('config', ...)` before any consent state; Google for WooCommerce printed its default after it; the banner never called `gtag('consent','update')`; a duplicate GA4 snippet existed that was **not in the repository**. Observed: `_ga` cookies after reject | Critical | consent default printed at `wp_head` priority 0; `update` from the banner; duplicate to be removed (owner action); Consent-Mode pings still disclosed |
| 5 | **Product safety (GPSR) data missing** on every product page; electrical goods, toys and a hoverboard in the catalogue; no evidence of supplier documents; texts rewritten by an AI plugin | Critical | **still open** (AUDIT D-10); block template + reference sketch; "no documents, no sale" |
| 6 | **Express buttons (Apple Pay, Google Pay, Link) without the "zamówienie z obowiązkiem zapłaty" label** on product and checkout pages (art. 17 ust. 4 UPK: no contract) | High | open: label or disable; lawyer |
| 7 | **Unsupported claims**: "Gwarancja producenta na każdy produkt", "Łatwe zwroty / 14 dni" (statutory right as a perk, black list), "Szybka dostawa", "z numerem śledzenia", "Elektronika premium", invented brand strip, "Oferta tygodnia" on non-discounted products, crossed-out prices on 24 of 28 catalogue products | High | removed or gated; bestsellers only from real sales (commit e5a2459) |
| 8 | **Legal pages leaked into the header page list and the footer "help" column** (automatic `wp_list_pages`/`get_pages`) | Low | `ace_legal_page_ids()` excluded (commit c907e25) |
| 9 | **The legal source folder was publicly readable**: `/wp-content/themes/2acetech/legal/*.html` served the Polish source texts (including editor notes) | Medium | `legal/.htaccess` deny (commit c8547bb) |
| 10 | **A stale privacy stub page**: WordPress creates a draft "Privacy Policy" with tutorial text; the first seeding code created pages only when none existed, so the stub (and an old text) survived | Medium | `ace_is_stock_privacy_stub()` + seed hash + old-hash list; unfinished drafts that still carry the editor note are refreshed (commit 9437ffa) |
| 11 | **Placeholder-laden drafts** (`[ADRES E-MAIL SKLEPU]`, `[USUŃ PRZED PUBLIKACJĄ]`) risked being published by mistake | Medium | seeded as drafts only, admin notice; final texts without placeholders (commit bfcfad3); **lint now fails on any placeholder** |
| 12 | Order e-mails did not carry the Regulamin (art. 21 UPK durable medium) | High | `ace_email_regulamin()` (commit bfcfad3) |
| 13 | Hostinger Reach script on every page, Order Attribution cookies from the first load, Google Fonts (DM Sans) fetched remotely although the theme self-hosts Manrope, Stripe/hCaptcha/Cloudflare cookies | Medium | disclosed in the cookie policy; owner to switch off |
| 14 | Newsletter/contact handlers remained in the theme after the texts said "no newsletter, no contact form" (endpoints accepted posts) | Medium | open: remove the code |
| 15 | English strings: page titles "Cart/Checkout/My account", "Orders Tracking", review banner "Review now to get coupon!", price format `zł227.99`, WooCommerce taxes disabled while prices are gross | Low/Medium | partly fixed; price format and taxes: owner/accountant |
| 16 | Two DMARC TXT records (invalid), SPF for host mail only, a Brevo record | Low | owner (DNS) |
| 17 | Phone number missing everywhere (art. 12 ust. 1 pkt 3 UPK) | High | owner supplied it later (commit 9e18d77) |
| 18 | Return address unknown ("warehouse for now"), supplier shares address and phone with the shop | Open | consent of the address operator; related-party note |
| 19 | `README.md` said the label was "Zamawiam z obowiązkiem zapłaty"; the `.po` and the live DOM say "Zamówienie z obowiązkiem zapłaty" | Low | read the DOM, not the README |
| 20 | Number mix-up risk: the audit had to state explicitly that **545746743 is the REGON, never a KRS number** (KRS is 0001267111) | Low | KRS API + white list cross-check; lint checks formats |

## 2. Process lessons

1. **Measure before you write.** The texts of 20 Nov were rewritten after a headless-Chrome run (hosts, cookies, `gcs=` flags). Texts written from assumptions were wrong in five places.
2. **State only what the shop does.** Refused statements (S4 in OPEN-ITEMS): "nothing is sent to Google before consent", "fonts are self-hosted", "no cookies before consent", a tracking-number promise, a BLIK/Przelewy24/Klarna mention, "the seller does not reward reviewers" (a coupon banner existed), the hosting server country, the 23% VAT rate, a WEEE quantity cap, a collection-point map URL, "order data never leaves the EEA".
3. **The master copy and the theme copy of the texts must stay identical** (`legal/` and `wp-content/themes/2acetech/legal/`: check with `cmp`). Better: render once and copy.
4. **Seeding is not publishing.** Pages are drafts until the owner says yes; a draft that was edited is never overwritten.
5. **Never trust "the plugin is off"**: check the page source and network log.
6. **A text is a snapshot of plugin state**: re-measure after updates.
7. **Old drafts must be deleted** (`regulamin-sklepu.md`, `polityka-prywatnosci.md` of 3-4 Nov stayed in the folder and could be pasted by mistake).
8. **Playground test scripts write result files into the repository folder** (`playground/_*-result.json`, untracked): add them to `.gitignore` or write to a scratch directory.
9. **Copying text between agents**: wrong article numbers were repeated until checked (see section 3).
10. **REST nonce trick for admin reads** (when the owner's browser is already logged in; no password is ever typed): in that browser `GET /wp-admin/admin-ajax.php?action=rest-nonce` returns a nonce; send it as header `X-WP-Nonce` to `/wp-json/wp/v2/...` and `/wp-json/wc/v3/...` read endpoints. Use only for **read-only** checks (pages, settings, plugin list), tell the owner, never print cookies or nonces in the report.
11. **Browser selection**: several Chrome instances were connected to the automation tool. Only one had the WP admin login. List the browsers, pick the one the owner identified, and verify with a harmless request that it is logged in (e.g. the `rest-nonce` call returns a nonce). Testing the wrong instance silently tests an anonymous session.
12. **Live-site rule**: reading public pages with one anonymous visit is acceptable for the audit; changing settings, placing an order, publishing a page or switching a plugin always needs the owner's explicit OK in the conversation.

## 3. Corrections to earlier assumptions (with sources, checked 2026-11-20 unless noted)

| Earlier assumption | Correct | Source |
|---|---|---|
| Complaints for consumer sales are "rękojmia" (KC 556 ff.) | **Non-conformity regime art. 43a-43g UPK** since 1 Jan 2023; KC 556 ff. do not apply to consumers (art. 43a ust. 1); "rękojmia" survives only as a plain-language label and in B2B contracts (supplier agreement) | UPK Dz.U. 2026 poz. 1244 |
| Order button rule is "art. 8 ust. 2" ("Zamawiam i płacę") | **Art. 17 ust. 2-3 UPK**; ust. 4: no contract if not met. The statutory phrase "zamówienie z obowiązkiem zapłaty" is safest | UPK |
| Invoice for consumers: "art. 106b ust. 1 pkt 1 VAT" | **Art. 106b ust. 3 pkt 1** (request within 3 months from the end of the month of delivery or payment) | ISAP Dz.U. 2026 poz. 1263 |
| Payment provider "Stripe Inc. (US)" | EEA contracting entity **Stripe Payments Europe, Limited** (Ireland); US importer **Stripe, LLC** (DPF + SCC); spelling "Limited" | stripe.com/privacy, /legal/dpa, /legal/ssa/pl |
| Complaint deadline "art. 7a ust. 2" | 14 days is **art. 7a ust. 1**; ust. 2 = deemed acceptance when unanswered; ust. 3 = form of the answer | UPK |
| Accounting documents "5 years from end of tax year" | **art. 74 ust. 2-3 ustawy o rachunkowości**: 5 years from the **start** of the year after the financial year; tax limitation (art. 70 § 1 Ordynacja podatkowa) 5 years from the **end** of the year in which the tax was due | Dz.U. 2026 poz. 522; 622 |
| 30-day delivery rule "art. 22 UPK" | Art. 22 UPK says information is part of the contract; **the UPK has no 30-day delivery rule** (Dir. 2011/83 art. 18 not copied); KC 455, 491, 492 apply; the 30 days in the texts are the seller's own promise | UPK; KC Dz.U. 2026 poz. 795 |
| WEEE take-back "up to the quantity purchased" | art. 37 ust. 2: "tego samego rodzaju i pełnił te same funkcje" - no explicit cap | ISAP Dz.U. 2024 poz. 573 |
| EU ODR platform closed "20 July 2025" | complaints ended 20 Mar 2025; Reg. 524/2013 repealed from 20 Jul 2025 | Reg. (EU) 2024/3228 |
| "EU withdrawal function from 19 June 2026" applies in Poland | Directive applies; **Poland has not transposed it** (2026-11-20 audit; 2026-11-21 ELI list of UPK amendments shows no implementing act) | ELI; press |
| Digital Content/Goods Directive "2019/771" for digital content | 2019/771 = **Sale of Goods Directive** (UPK ch. 5a); Digital Content = 2019/770 (ch. 5b) | UPK |
| Accessibility applies to the shop | e-commerce is covered but **micro-enterprises are excluded** (art. 4 pkt 1) | Dz.U. 2024 poz. 731 |
| "GA4 default retention 2 months" | Google lists 2 or 14 months; no default stated | support.google.com/analytics/answer/7667196 |
| "Hostinger Ltd." | DPA executed by Hostinger International Ltd. (Cyprus) or Hostinger UK Limited / Hostinger Global S.a r.l.; server locations not named | hostinger.com/pl/legal/dpa |
| Dz.U. 2026 poz. 1353 enters into force in 2026 | **27 Sept 2027** (ELI, checked 2026-11-21) | api.sejm.gov.pl/eli/acts/DU/2026/1353 |
| "Seller may use `<ol>` numbers hard-coded in cross references" | numbering changes when optional blocks are switched off: use `{{N:..}}`, `{{R:..}}`, `{{H:..}}`, `{{S:..}}` tokens (the renderer computes them) and let the lint check "§ N ust. M" | this skill |
