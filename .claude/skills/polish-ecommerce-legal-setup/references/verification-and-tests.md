# Verification and tests: the complete recipe

Run in Phase 5, before the owner is asked to approve publishing, and again after every change of plugins, theme or texts.
Each test has a command or click-path and an expected result. Record the result and the date in OPEN-ITEMS.

Conventions: `SHOP=https://example.pl`; `SKILL=.claude/skills/polish-ecommerce-legal-setup`; `OUT=/tmp/legal-out` (use the session scratchpad, not the repo).
Nothing here needs a login except 5-6 (a test order) and 8 (admin checks); never type or print passwords or keys.

## 1. Rendering, lint and placeholder grep (offline)

```bash
python3 -I $SKILL/scripts/render_legal.py --vars company.json --templates $SKILL/templates --out $OUT
#   -> "rendered 10 file(s)" and "lint: 0 error(s)"; WARN lines are decisions to record (variants authored 2026-11-21, vat_exempt, ...)
python3 -I $SKILL/scripts/lint_legal.py $OUT --vars company.json
PUB=$(for n in regulamin-sklepu polityka-prywatnosci polityka-cookies dostawa-i-platnosc zwroty-i-odstapienie reklamacje dane-sprzedawcy; do echo $OUT/$n.html; done)
grep -n -E '\{\{|\}\}|<!--(IF|ELSE|ENDIF|N:|R:|H:|S:)|<code>\[|WARIANT|POTWIERDŹ|USUŃ|OPEN-ITEMS|TODO|FIXME|XXX' $PUB          # must print nothing
grep -n -E '\[[A-ZĄĆĘŁŃÓŚŹŻ][A-ZĄĆĘŁŃÓŚŹŻ0-9 _/.,:-]{2,}\]' $PUB                                                                # bracketed uppercase notes: must print nothing
```
The owner-side documents (`blok-informacje...`, `wzor-umowy-*`) intentionally keep `<code>[...]</code>` placeholders and a `[USUŃ PRZED UŻYCIEM]` note: they never go on the website.

What `lint_legal.py` checks (all errors fail the run): leftover tokens/markers; placeholders; the marker words; unbalanced/mis-nested tags; artefacts of removed blocks (empty `li/ul/ol/p/tbody`,
`,,`, ` ,`, `;;`, `()`, doubled spaces, heading directly followed by heading); internal links to unknown slugs; anchors `/slug/#anchor` that do not exist; required anchors
`par-2 par-5 par-6 par-8` (Regulamin) and `sec-6` (privacy); "§ N ust. M" references that point to a missing paragraph; NIP checksum, REGON 9/14-digit checksum, KRS 10 digits,
phone `+48` + 9 digits; with `--vars` also the variable values (e-mail, dates `D miesiąca RRRR r.`, postcodes, https URLs, `SHOP_URL` without trailing slash).

Self-test of the template engine (all-off, all-on and 300 random feature combinations, each linted):

```bash
python3 -I $SKILL/scripts/render_legal.py --selftest 300 --vars $SKILL/scripts/company.example.json --templates $SKILL/templates   # "selftest: 302 combination(s), 0 failed"
```
Parity with the published 2ACE masters (regression test of the templates):

```bash
python3 -I $SKILL/scripts/render_legal.py --vars $SKILL/examples/2ace.company.json --templates $SKILL/templates --out $OUT/2ace
for f in legal/*.html; do cmp "$OUT/2ace/$(basename $f)" "$f" || echo "DIFF $f"; done
```
Expected result on 2026-11-21: all ten files identical except `wzor-umowy-powierzenia-danych.html` (one intentional wording change: the generic note "Duzi dostawcy (np. operatorzy płatności, Google, firmy hostingowe)" replaces the 2ACE-specific "(Stripe, Google, Hostinger)").

## 2. Tag balance and structure

Covered by the lint (HTML parser). Additionally, after the texts are in WordPress, compare the **rendered page** with the file: the block editor or `wpautop` can inject `<p>` or `<br />`:

```bash
curl -s $SHOP/regulamin/ | python3 -I -c "import sys,re,html; t=sys.stdin.read(); m=re.search(r'<div class=\"entry-content[^>]*>(.*?)</div>\s*</article>',t,re.S); print(len(m.group(1)) if m else 'content div not found (adapt the regex to the theme)')"
```
Visual check: every `<table>` shows 4 columns, ordered lists number correctly (1., 2., ... with the `start` values), annexes appear.

## 3. Cross references and anchors

`lint_legal.py` covers: `§ N ust. M` inside the texts, internal links and anchors. Manual: click every "§" link on the live pages; open `/regulamin/#par-5`, `#par-6`, `#par-8`, `/polityka-prywatnosci/#sec-6`, `#sec-4`; they must scroll to the right heading (the theme creates the ids when the page is displayed:
`curl -s $SHOP/regulamin/ | grep -o 'id="par-[0-9]*"' | sort -u` must list par-1 ... par-11, `id="zalacznik-1"`, `id="zalacznik-2"`).

## 4. Status codes, footer and header links

```bash
for s in regulamin polityka-prywatnosci polityka-cookies dostawa-i-platnosc zwroty-i-odstapienie reklamacje dane-sprzedawcy odstap-od-umowy; do printf "%-26s" $s; curl -s -o /dev/null -w '%{http_code}\n' -L $SHOP/$s/; done   # all 200
curl -s -o /dev/null -w '%{http_code}\n' $SHOP/wp-content/themes/<theme>/legal/regulamin-sklepu.html                                                          # 403 (or 404), never 200
curl -s $SHOP/ | grep -o '<a [^>]*>[^<]*</a>' | grep -i -E 'regulamin|prywatno|cookie|dostaw|zwrot|reklamac|sprzedawc|odstąp|ustawienia'                   # all present in the footer
curl -s $SHOP/ | sed -n '/<header/,/<\/header>/p' | grep -i -E 'regulamin|prywatno|cookie|reklamac'                                                           # must print nothing (legal pages not in the header)
```
While any page is a draft, its link is not printed and the footer uses the Regulamin anchors; after publishing all seven, there must be exactly one link per page. Draft pages must return 404 to anonymous visitors.

## 5. Checkout: label, checkbox, express buttons (browser)

Add one product to the cart (anonymous session, test mode or without paying), open `/checkout/` and run in the DevTools console:

```js
(() => {
  const terms = document.querySelector('.wc-block-checkout__terms');
  const box = terms && terms.querySelector('input[type=checkbox]');
  const btn = document.querySelector('.wc-block-components-checkout-place-order-button');
  return {
    termsBlock: !!terms, termsText: terms && terms.innerText.trim(), checkboxPresent: !!box, checkboxChecked: box && box.checked,
    links: terms && [...terms.querySelectorAll('a')].map(a => a.href),
    buttonLabel: btn && btn.innerText.trim(),
    expressBlock: !!document.querySelector('.wc-block-components-express-payment'),
    phoneField: !!document.querySelector('#shipping-phone, #billing-phone, #contact-phone')
  };
})()
```
Expected: `termsBlock: true`, text "Akceptuję Regulamin i oświadczam, że zapoznałem(-am) się z jego treścią. Informacje o przetwarzaniu Twoich danych: Polityka prywatności.", `checkboxPresent: true`, `checkboxChecked: false`, two links (the published Regulamin and privacy pages), `buttonLabel: "Zamówienie z obowiązkiem zapłaty"`. Try to submit without ticking: the checkout must refuse. If `expressBlock` is true, read what the buttons say and apply the express-checkout rule (`pages-spec.md` 11, `wordpress-woocommerce-setup.md` 6).
Classic checkout: `document.querySelector('#terms')`, `document.querySelector('#place_order').value`.
Also check: total price incl. VAT and delivery in the summary, delivery time text, price format `1 234,56 zł brutto`, the "Odstąp od umowy tutaj" link in the footer, phone optional (or as decided).
Store API: `curl -s $SHOP/wp-json/wc/store/v1/cart` -> `payment_methods` equals the enabled methods, `shipping_rates` only the Polish zone, `needs_shipping` true.

**Browser selection** when several Chrome instances/profiles are connected to the automation tool: list the browsers first and select the one the owner logged in to wp-admin with (by name/device), otherwise all admin calls return 401/404 and you may test the wrong instance. Never switch to a browser to log in on the owner's behalf.

## 6. E-mail test

1. Preview: WooCommerce > Settings > Emails > Customer processing order > Preview (labels vary) and Customer on-hold order. The Regulamin text must appear after the order table, then the "Odstąp od umowy tutaj" link.
2. Real test: a test-mode order (owner OK for the test keys) or an order created in WooCommerce > Orders > Add order (status Processing, billing e-mail = a mailbox you control) > "Order actions: Resend order notification". Read the HTML and plain-text parts; open in Gmail and one other client.
3. Content: Regulamin incl. Załącznik nr 1 and 2, seller identity, withdrawal link, no unreplaced placeholders.
4. Deliverability: "Show original" in Gmail: SPF, DKIM, DMARC = PASS; not in spam. `dig +short TXT _dmarc.$DOMAIN` returns exactly one record.
5. Failed/unpaid order: no "order accepted" e-mail and no Regulamin text (Regulamin § 4 ust. 4).
6. Withdrawal function: submit once with a real e-mail; acknowledgement within minutes with date and time; the shop copy arrives; "Withdrawals" list shows the entry.

## 7. Consent test (cookies and requests)

```bash
node $SKILL/scripts/measure_consent.mjs $SHOP/ --json $OUT/consent-home.json
node $SKILL/scripts/measure_consent.mjs $SHOP/product/<slug>/ --json $OUT/consent-product.json     # Stripe/Google scripts load on product, cart, checkout
node $SKILL/scripts/measure_consent.mjs $SHOP/checkout/    --json $OUT/consent-checkout.json       # add an item in a normal browser first; the script has no cart
```
Expected for the **stricter** model: phase A: no Google/analytics request, no cookie except necessary ones; phase B (after reject): same; phase C (after accept): analytics cookies appear.
Expected for the **Consent Mode "denied"** model (2acetech): phase A/B: Google requests may appear but with `gcs=G100` and **no** `_ga*`/`_gcl_*`/`IDE` cookie; phase C: `_ga`, `_ga_<id>`, `_gcl_au`, `IDE`. Anything else = fix or disclose.
Compare each cookie and host with the cookie policy tables; add what is missing (feature flags or `EXTRA_*_HTML`) and re-render. Re-run in a normal desktop Chrome (DevTools > Network, filter `google`/`gstatic`/`stripe`/`hostinger`; Application > Cookies) and on a phone.

Banner behaviour to confirm by hand: three buttons the same size; "Zarządzaj plikami cookie" opens the panel; panel toggles default off; "Zapisz moje wybory" stores; footer "Ustawienia cookies" reopens; withdrawing a granted category reloads the page; Escape closes the panel; Tab stays inside the panel; returning visitor sees no banner and Google receives `consent update` (`dataLayer` contains it).

## 8. Cache, sitemap, robots, noindex, headers

```bash
# after publishing: purge LiteSpeed/hPanel cache, then
curl -s $SHOP/regulamin/ | grep -c 'Postanowienia ogólne'                       # 1 (new text, not a cached stub)
curl -s $SHOP/wp-sitemap.xml | head; curl -s $SHOP/wp-sitemap-posts-page-1.xml | grep -o '<loc>[^<]*</loc>' | grep -E 'regulamin|prywatnosci|cookies|dostawa|zwroty|reklamacje|dane-sprzedawcy'
curl -s -o /dev/null -w '%{http_code}\n' $SHOP/wp-sitemap-users-1.xml           # 404 after disabling the users sitemap
curl -s $SHOP/robots.txt
for p in cart checkout my-account; do printf "%s " $p; curl -s $SHOP/$p/ | grep -o -i '<meta name=.robots.[^>]*>' | head -1; done   # noindex on all three (slugs may be Polish)
curl -sI $SHOP | grep -i -E 'strict-transport|x-content-type|referrer-policy|x-frame|content-security'
```
Draft legal pages must not be in the sitemap. The site must not be set to "discourage search engines" at launch (Settings > Reading), and must be while it is not public.

## 9. WordPress Playground CLI recipe (syntax and seeding tests, no live site)

The repository has `@wp-playground/cli` (v3.1.62) and a `playground/` folder with blueprints and `test-*.php` scripts (`blueprint.legal-test.json` + `test-legal.php` test the seeding, footer links, checkout terms block and consent default; `blueprint.syntax-test.json` + `test-syntax.php` check every PHP file with `token_get_all( ..., TOKEN_PARSE )`). **A mounted host directory is writable from the virtual file system: test in a throw-away copy of the theme, never mount the repo theme read-write for tests that write files.** (The repo's own tests write results to `playground/_*-result.json`; those untracked files can be deleted.)

Recipe used for this skill (2026-11-21):

```bash
SB=/path/to/scratchpad/pg; mkdir -p $SB/out
cp -R wp-content/themes/<theme> $SB/theme                         # throw-away copy
rm -f $SB/theme/legal/*.html && cp $OUT/{regulamin-sklepu,polityka-prywatnosci,polityka-cookies,dostawa-i-platnosc,zwroty-i-odstapienie,reklamacje,dane-sprzedawcy}.html $SB/theme/legal/
cat > $SB/blueprint.json <<'EOF'
{ "$schema": "https://playground.wordpress.net/blueprint-schema.json",
  "preferredVersions": { "php": "8.3", "wp": "latest" },
  "features": { "networking": true }, "login": true, "plugins": ["woocommerce"],
  "steps": [ { "step": "activateTheme", "themeFolderName": "<theme>" },
             { "step": "runPHP", "code": "<?php require '/wordpress/wp-load.php'; require '/playground/test-seed.php';" } ] }
EOF
# test-seed.php (in $SB): token_get_all syntax check of every theme PHP file; ace_maybe_create_legal_pages(); page statuses; terms/privacy options;
#   the Regulamin passed through apply_filters('the_content') to read the generated id="par-N"; ace_footer_legal_links(); writes /playground/out/seed-result.json
npx wp-playground-cli run-blueprint --blueprint=$SB/blueprint.json \
     --mount-before-install=$SB/theme:/wordpress/wp-content/themes/<theme> --mount=$SB:/playground
cat $SB/out/seed-result.json
# PHP-only syntax check of a file (no WordPress install, a few seconds):
echo '<?php token_get_all(file_get_contents("/playground/sketch.php"), TOKEN_PARSE); echo "OK\n";' > $SB/lint.php
npx wp-playground-cli php --mount=$SB:/playground -- /playground/lint.php
```
Observed on 2026-11-21 with the rendered fake-company texts: 44 PHP files, 0 syntax errors; 7 pages created as drafts with the right slugs, `woocommerce_terms_page_id` and `wp_page_for_privacy_policy` set, no placeholders in any page, anchors `par-1` ... `par-11`, `zalacznik-1`, `zalacznik-2` generated.
Interactive preview: `npm start` (blueprint.json) or `npm run start:pl` (Polish site language) serves WordPress at http://127.0.0.1:9400. Playground has no real mail or Stripe: test e-mail templates by calling the hook functions (`ob_start(); ace_email_regulamin( $order, false, false, $email ); ob_get_clean()`) and compare the text.

## 10. Accessibility and mobile

- Banner and panel: keyboard only (Tab, Shift+Tab, Enter, Space, Escape); focus visible; focus returns to the opener; `role="region"`/`role="dialog"`, `aria-labelledby`; contrast >= 4.5:1 (axe DevTools or Lighthouse); text resizable to 200% without loss; the banner never covers the whole checkout on a 360x640 phone (`max-height: 92svh`, internal scroll).
- Mobile (DevTools device toolbar 390x844 and 360x640, and one real phone): banner buttons stack and are 44 px high; legal tables scroll horizontally or reflow; the footer legal bar wraps; the checkout checkbox is tappable; the withdrawal form is usable.
- Legal pages: headings in order (one h1 from the theme, h2 sections), table headers, link text meaningful ("Polityka prywatności", not "tutaj").
- The European Accessibility Act exempts micro-enterprises (Dz.U. 2024 poz. 731 art. 4 pkt 1 [A]); good practice anyway.

## 11. Pre-publication gate (all must be true)

- [ ] `lint_legal.py`: 0 errors; placeholder grep: nothing; the OPEN-ITEMS grey-list table exists.
- [ ] Facts: intake complete, registers cross-checked, measurements done and dated.
- [ ] Terms and Privacy pages set in WooCommerce/WordPress; checkout checkbox and label verified (5).
- [ ] E-mail test passed (6); withdrawal acknowledgement works.
- [ ] Consent test matches the cookie policy (7).
- [ ] Payment methods = texts; test-mode order done; **live mode** only with the owner's OK.
- [ ] GPSR block present for every published product; no product without documents.
- [ ] Supplier agreement(s) signed or written confirmation of dispatch/transit, returns, documents, BDO.
- [ ] WEEE take-back arranged (if the text promises it).
- [ ] Owner reviewed OPEN-ITEMS and gave explicit OK to publish; lawyer review done or explicitly waived by the owner **in writing** (this is the owner's decision, never the agent's).
