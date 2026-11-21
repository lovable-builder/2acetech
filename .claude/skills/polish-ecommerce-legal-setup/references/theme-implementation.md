# Theme implementation patterns (reproducible on another WordPress site)

Source: the real 2acetech theme, `wp-content/themes/2acetech` (repository `lovable-builder/2acetech`, branch
`landing-page-theme`, state of 2026-11-21). Snippets below are quoted from the named files and shortened (marked
`...`). Function prefix `ace_`, text domain `2acetech`, constants `ACE_DIR`, `ACE_URI`.

## 0. How to adapt when the theme is different

| Situation | What to do |
|---|---|
| Own theme you control | copy `inc/legal.php`, `inc/consent.php`, `inc/withdrawal.php`, `template-withdrawal.php`, `assets/js/consent.js`, `assets/css/consent.css`, the `legal/` folder and its `.htaccess`; `require_once` them from `functions.php`; rename the prefix and text domain with a global search/replace; keep slugs and anchors |
| Third-party theme (Astra, Kadence, Storefront, Blocksy) | **do not edit the theme**. Create a child theme or, better, a **must-use plugin** `wp-content/mu-plugins/shop-legal.php` that requires the same files. Replace `ACE_DIR` by `plugin_dir_path( __FILE__ )` and `ACE_URI` by `plugin_dir_url( __FILE__ )`. `get_template_directory()` must not be used in a plugin |
| Footer / header not controlled | print the legal bar and the consent markup through `wp_footer` (priority 5) and a shortcode `[shop_legal_links]` for the theme's footer widget area; exclude legal pages from menus through the menu editor instead of `wp_list_pages` filters |
| Elementor/page-builder shops | legal pages stay plain pages (no builder); the withdrawal page may use any layout as long as the form posts to `admin-post.php?action=ace_withdrawal` |
| No theme support for the block-checkout label | the `placeOrderButtonLabel` filter works with any theme (script), see 4 |

All code is plain PHP/JS with WordPress APIs; no libraries. Test each piece in Playground first (see
`verification-and-tests.md` 9).

Required files (2acetech): `inc/legal.php` (436 lines), `inc/consent.php` (239), `inc/withdrawal.php` (307),
`template-withdrawal.php`, `assets/js/consent.js` (161), `assets/css/consent.css` (126), `assets/js/checkout.js`,
`inc/woocommerce.php` (order label), `footer.php` (legal bar), `legal/*.html` + `legal/.htaccess`.

---

## 1. The legal page registry

`inc/legal.php`:

```php
function ace_legal_defs() {
	return array(
		'terms'   => array( 'slug' => 'regulamin',            'title' => __( 'Store terms and conditions', '2acetech' ), 'file' => 'regulamin-sklepu.html' ),
		'privacy' => array( 'slug' => 'polityka-prywatnosci', 'title' => __( 'Privacy policy', '2acetech' ),            'file' => 'polityka-prywatnosci.html' ),
		'cookies' => array( 'slug' => 'polityka-cookies', ... ),   // 'delivery' => dostawa-i-platnosc, 'returns' => zwroty-i-odstapienie,
		...                                                         // 'complaints' => reklamacje, 'seller' => dane-sprzedawcy
	);
}

function ace_legal_page_id( $key ) {   // whichever way the page was set up
	if ( 'terms' === $key && function_exists( 'wc_terms_and_conditions_page_id' ) && (int) wc_terms_and_conditions_page_id() ) {
		return (int) wc_terms_and_conditions_page_id();
	}
	if ( 'privacy' === $key && (int) get_option( 'wp_page_for_privacy_policy' ) ) {
		return (int) get_option( 'wp_page_for_privacy_policy' );
	}
	$defs = ace_legal_defs();
	$page = isset( $defs[ $key ] ) ? get_page_by_path( $defs[ $key ]['slug'] ) : null;
	return $page ? (int) $page->ID : 0;
}
```

Rule: the **slugs are the contract** between the rendered texts (internal links `/regulamin/#par-5`), the footer,
the consent banner and the lint (`lint_legal.py` knows the seven slugs).

## 2. Seeding legal pages as DRAFT with a seed hash

Goal: texts shipped in `legal/*.html` become pages **as drafts, never published automatically**; a draft that the
owner has not touched may be refreshed when the theme ships a new text; **anything edited or published is never
overwritten**. Mechanism: a post-meta `_ace_legal_seed` holding `md5` of the text that was seeded.

```php
function ace_maybe_create_legal_pages() {
	foreach ( ace_legal_defs() as $key => $def ) {
		$file = ACE_DIR . '/legal/' . $def['file'];
		if ( ! is_readable( $file ) ) { continue; }
		$text = (string) file_get_contents( $file );

		$existing = ace_legal_page_id( $key );
		if ( $existing ) {
			$page = get_post( $existing );
			if ( ! $page ) { continue; }
			// Replace: the stock WordPress privacy stub; an unedited draft of an earlier seed;
			// an unfinished draft that still carries the editor note. Never an edited or published page.
			$stub     = 'privacy' === $key && ace_is_stock_privacy_stub( $existing );
			$hash     = md5( $page->post_content );
			$unedited = 'draft' === $page->post_status
				&& ( $hash === get_post_meta( $existing, '_ace_legal_seed', true ) || in_array( $hash, ace_legal_old_hashes(), true ) || false !== strpos( $page->post_content, '[USUŃ PRZED PUBLIKACJĄ]' ) );
			if ( ( $stub || $unedited ) && $hash !== md5( $text ) ) {
				wp_update_post( wp_slash( array( 'ID' => $existing, 'post_title' => $def['title'], 'post_name' => $def['slug'], 'post_content' => $text ) ) );
				update_post_meta( $existing, '_ace_legal_seed', md5( $text ) );
			}
			continue;
		}
		$id = wp_insert_post( wp_slash( array( 'post_type' => 'page', 'post_status' => 'draft', 'post_title' => $def['title'], 'post_name' => $def['slug'], 'post_content' => $text ) ) );
		if ( $id && ! is_wp_error( $id ) ) {
			update_post_meta( $id, '_ace_legal_seed', md5( $text ) );
			if ( 'terms' === $key )   { update_option( 'woocommerce_terms_page_id', $id ); }
			elseif ( 'privacy' === $key ) { update_option( 'wp_page_for_privacy_policy', $id ); }
		}
	}
}
add_action( 'admin_init', 'ace_maybe_create_legal_pages' );
```

Details that cost time on 2acetech (see lessons): (a) it runs on `admin_init`, so a theme uploaded with "replace current"
(no `after_switch_theme`) still seeds; (b) WordPress creates a **draft "Privacy Policy" stub** with tutorial text
at install: `ace_is_stock_privacy_stub()` recognises it (`privacy-policy-tutorial` marker or empty) and replaces it;
a stub that was not refreshed was a real bug (the old stub kept the wrong text); (c) `ace_legal_old_hashes()` lists the
`md5` of every earlier text the theme ever seeded, so unedited drafts of old versions can be refreshed; (d) the
"editor note" marker (`[USUŃ PRZED PUBLIKACJĄ]`) lets a draft that still carries a template note be replaced; (e) an
admin notice lists drafts and says "do not take real orders before they are published":

```php
add_action( 'admin_notices', 'ace_legal_notice' );   // "Legal pages are waiting: <links>. ... Do not take real orders before then."
```

`ace_legal_old_hashes()`: when you change a text and ship a new version, append `md5( old file )` of every
**previous seeded text** (`md5_file()` of the previous commit) to the array.

**Publishing is a human step** (WP admin > Pages > Publish) after the owner's explicit OK.

## 3. Section anchors (`#par-N`, `#sec-N`, `#zalacznik-N`)

The Regulamin and privacy policy keep **plain `<h2>` headings**; the theme turns them into anchors when the content
is displayed (so the stored text stays pasteable and independent of the theme):

```php
function ace_legal_anchors( $content ) {
	if ( ! is_page() || ! in_the_loop() ) { return $content; }
	$id = get_the_ID();
	if ( ace_legal_page_id( 'terms' ) === $id ) {
		$content = preg_replace( '/<h2>(§ (\d+)\.)/u', '<h2 id="par-$2">$1', $content );
		$content = preg_replace( '/<h2>(Załącznik nr (\d+)\.)/u', '<h2 id="zalacznik-$2">$1', $content );
	} elseif ( ace_legal_page_id( 'privacy' ) === $id ) {
		$content = preg_replace( '/<h2>((\d+)\.)/u', '<h2 id="sec-$2">$1', $content );
	}
	return $content;
}
add_filter( 'the_content', 'ace_legal_anchors' );
```

Contract with the templates: headings must be exactly `<h2>§ N. Title` (Regulamin), `<h2>N. Title` (privacy policy),
`<h2>Załącznik nr N. ...`. Required anchors: `par-2, par-5, par-6, par-8, sec-6` (+ `par-3`, `par-7`, `sec-4` which other
pages link to). `lint_legal.py` computes the same anchors and fails when one is missing. If you adopt explicit
`id=` attributes in the HTML instead, keep the same ids.

## 4. Checkout: order button label and the required Regulamin checkbox

Label (art. 17 ust. 3 UPK: "zamówienie z obowiązkiem zapłaty"), `inc/woocommerce.php` + `assets/js/checkout.js`:

```php
function ace_place_order_label() { return __( 'Order with obligation to pay', '2acetech' ); }   // pl_PL.po -> "Zamówienie z obowiązkiem zapłaty"
add_filter( 'woocommerce_order_button_text', 'ace_place_order_label' );                          // classic checkout

function ace_checkout_script() {                                                                  // block checkout
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) { return; }
	wp_enqueue_script( 'ace-checkout', ACE_URI . '/assets/js/checkout.js', array( 'wc-blocks-checkout' ), ace_ver( 'assets/js/checkout.js' ), true );
	wp_add_inline_script( 'ace-checkout', 'window.aceCheckout = ' . wp_json_encode( array( 'placeOrder' => ace_place_order_label() ) ) . ';', 'before' );
}
add_action( 'wp_enqueue_scripts', 'ace_checkout_script', 40 );
```
```js
// assets/js/checkout.js
if (window.wc && window.wc.blocksCheckout && window.wc.blocksCheckout.registerCheckoutFilters) {
	window.wc.blocksCheckout.registerCheckoutFilters('2acetech', { placeOrderButtonLabel: function () { return label; } });
}
```
Note: the repo `README.md` still says the label is "Zamawiam z obowiązkiem zapłaty" while the `.po` file says
"Zamówienie z obowiązkiem zapłaty" (the live site was verified with the latter): always read the `.po` / live DOM, not the README.

Required checkbox in the **block** checkout via `render_block_data` (set the "Terms" inner block attributes
`checkbox` = true and `text`), only while the Regulamin is **published** (a box that links to a draft would be worse than none):

```php
function ace_checkout_terms_block( $block ) {
	if ( empty( $block['blockName'] ) || 'woocommerce/checkout-terms-block' !== $block['blockName'] ) { return $block; }
	$terms = ace_legal_page_id( 'terms' );
	if ( ! $terms || 'publish' !== get_post_status( $terms ) ) { return $block; }
	$privacy = ace_legal_page_id( 'privacy' );
	$link = static function ( $id, $label ) { return sprintf( '<a href="%s" target="_blank" rel="noopener">%s</a>', esc_url( get_permalink( $id ) ), esc_html( $label ) ); };
	if ( $privacy && 'publish' === get_post_status( $privacy ) ) {
		$text = sprintf( __( 'I accept the %1$s and confirm that I have read it. How we process your data: %2$s.', '2acetech' ),
			$link( $terms, __( 'Terms and conditions', '2acetech' ) ), $link( $privacy, __( 'Privacy policy', '2acetech' ) ) );
	} else { $text = sprintf( __( 'I accept the %s and confirm that I have read it.', '2acetech' ), $link( $terms, __( 'Terms and conditions', '2acetech' ) ) ); }
	if ( ! isset( $block['attrs'] ) || ! is_array( $block['attrs'] ) ) { $block['attrs'] = array(); }
	$block['attrs']['checkbox'] = true;
	$block['attrs']['text']     = $text;
	return $block;
}
add_filter( 'render_block_data', 'ace_checkout_terms_block' );
```
Polish text (`pl_PL.po`): "Akceptuję %1$s i oświadczam, że zapoznałem(-am) się z jego treścią. Informacje o przetwarzaniu Twoich danych: %2$s."
where %1$s = link "Regulamin", %2$s = link "Polityka prywatności".
Classic checkout: WooCommerce shows the terms checkbox when **Settings > Advanced > Page setup > Terms and conditions**
is set. Browser test: `verification-and-tests.md` 5.

Also in `inc/legal.php`: `add_filter( 'pre_option_show_avatars', '__return_zero' );` (no Gravatar, so the privacy policy can say no
reviewer hash is sent to Automattic).

## 5. Cookie consent: banner, panel, Google Consent Mode v2

`inc/consent.php` (PHP) + `assets/js/consent.js` + `assets/css/consent.css`.

**Default denied, printed first in `<head>`** (priority 0, before any plugin prints `gtag`; always "denied" on the server, never read from the
cookie, so a page cache cannot serve one visitor's choice to another):

```php
function ace_consent_google_default() {
	if ( ! ace_consent_enabled() ) { return; }
	echo "<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('consent','default',{ad_storage:'denied',ad_user_data:'denied',ad_personalization:'denied',analytics_storage:'denied',wait_for_update:500});</script>\n";
}
add_action( 'wp_head', 'ace_consent_google_default', 0 );
```
`ace_consent_enabled()` = `! is_admin() && ! is_customize_preview() && ! is_feed() && ! wp_doing_ajax() && ! is_embed()`.

**Choice storage**: one first-party cookie `ace_consent` = `version.analytics.marketing` (e.g. `1.0.0`), 180 days, `Path=/; SameSite=Lax; Secure`:

```js
function write(state) {
	const value = [cfg.version, state.analytics ? 1 : 0, state.marketing ? 1 : 0].join('.');
	const secure = location.protocol === 'https:' ? '; Secure' : '';
	document.cookie = cfg.cookie + '=' + value + '; Max-Age=' + cfg.days * 86400 + '; Path=/; SameSite=Lax' + secure;
}
```
Bumping `ACE_CONSENT_VERSION` re-asks everybody (use when a new tool or purpose is added). `wp_localize_script` converts numbers to strings: the JS converts back.

**Update to Google** on load (returning visitors) and on every choice:

```js
function announce() {
	const detail = current || { analytics: false, marketing: false };
	if (typeof window.gtag === 'function') {
		const a = detail.analytics ? 'granted' : 'denied';
		const m = detail.marketing ? 'granted' : 'denied';
		window.gtag('consent', 'update', { analytics_storage: a, ad_storage: m, ad_user_data: m, ad_personalization: m });
	}
	document.dispatchEvent(new CustomEvent('ace:consent', { detail }));
}
```
**Scripts that wait for consent**: `<script type="text/plain" data-ace-consent="analytics" src="...">` runs only after that category is granted
(`activate()` clones the element without `type`/`data-ace-consent`); PHP check `ace_consent_allowed( 'analytics' )`; JS `window.aceConsent.has('marketing')`.
**Withdrawal reloads the page** when a previously granted category is removed ("a script that already ran cannot be taken back").

**Markup** (`ace_consent_markup()` on `wp_footer` priority 5): banner `role="region" aria-labelledby`, hidden until JS finds no valid choice; panel
`role="dialog" aria-modal="true"`, focus trap, Escape closes. Three buttons in both places (reject / manage-or-save / accept) with the **same size and
weight**; labels (Polish): "Odrzuć wszystkie", "Zarządzaj plikami cookie" (panel: "Zapisz moje wybory"), "Akceptuję" (panel: "Akceptuj wszystkie").
Footer **reopen** control (`inc/legal.php` footer links): `<button type="button" class="cookie-link" data-cookie-open>Ustawienia cookies</button>`; also
`a[href="#cookie-settings"]` opens the panel.

CSS essentials (`assets/css/consent.css`): equal weight and size:

```css
.cookie__actions { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; }
.cookie__btn { min-height: 48px; padding: 12px 16px; border: 2px solid transparent; border-radius: 12px; font-weight: 800; }
@media (max-width: 640px) { .cookie__actions { grid-template-columns: 1fr; } .cookie__btn { min-height: 44px; } }
.cookie .cookie__logo { height: clamp(26px, 5vw, 40px); }   /* ".cookie .cookie__logo": WooCommerce's ".woocommerce img {height:auto}" beat a single class */
```
Accessibility: visible focus (`:focus-visible { outline: 3px solid }`), contrast, keyboard operable, no scroll lock trap beyond the modal.

What the banner does **not** do (and the texts must say so): it does not stop Google's scripts from loading; Consent Mode "denied" prevents
Google cookies but cookieless pings still leave the browser (disclosed in cookie policy 3 and privacy row "gcm"). For the stricter model
print the Google tags as `type="text/plain" data-ace-consent="analytics"` and delete the disclosure.

## 6. Regulamin in the body of order-confirmation e-mails

`inc/legal.php`; hook `woocommerce_email_after_order_table` (priority 30), only the customer e-mails sent when the order is received, only while
the Regulamin page is published, HTML and plain text:

```php
function ace_email_regulamin( $order, $sent_to_admin, $plain_text, $email = null ) {
	if ( $sent_to_admin || ! is_object( $email ) || ! in_array( $email->id, array( 'customer_processing_order', 'customer_on_hold_order' ), true ) ) { return; }
	$id = ace_legal_page_id( 'terms' ); $page = $id ? get_post( $id ) : null;
	if ( ! $page || 'publish' !== $page->post_status ) { return; }
	$title = 'Regulamin sklepu';
	if ( $plain_text ) {
		echo "\n\n==========\n" . esc_html( $title ) . "\n==========\n\n";
		echo esc_html( html_entity_decode( wp_strip_all_tags( preg_replace( '#</(p|li|h[1-6]|tr)>#i', "\n", $page->post_content ) ) ) ) . "\n";
		return;
	}
	echo '<h2 style="margin:32px 0 12px">' . esc_html( $title ) . '</h2>';
	echo '<div style="font-size:12px;line-height:1.5">' . wp_kses_post( wpautop( $page->post_content ) ) . '</div>';
}
add_action( 'woocommerce_email_after_order_table', 'ace_email_regulamin', 30, 4 );
```
The Regulamin contains both annexes, so the statutory withdrawal notice and the model form arrive on a durable medium (art. 21 ust. 1 UPK).
The withdrawal link is added at priority 20 by `ace_withdrawal_email_link()`. **Test**: a real test order and read the e-mail (HTML and
plain text) in two clients; the "On-hold" e-mail is for unpaid methods: the Regulamin § 4 ust. 4 says the contract is concluded when the confirmation is sent after
successful payment, so check which e-mail is sent in which state.

## 7. The withdrawal function ("Odstąp od umowy tutaj")

`inc/withdrawal.php` + `template-withdrawal.php`. Page slug `odstap-od-umowy`, template `template-withdrawal.php`, created once on `admin_init`
(published, because the function must exist from day one: the theme will not recreate it when the page was deleted: option `ace_withdrawal_page_checked`).

Label everywhere: **"Odstąp od umowy tutaj"**; confirm button **"Potwierdź odstąpienie od umowy tutaj"** (`pl_PL.po`: "Withdraw from contract here" /
"Confirm withdrawal here").

Where the link appears:

```php
function ace_withdrawal_link( $order = '', $class = 'withdraw-link' ) { ... '<a class="withdraw-link" href="..."> Odstąp od umowy tutaj</a>' }
add_action( 'woocommerce_thankyou', 'ace_withdrawal_thankyou', 30 );                                   // order confirmation page
add_filter( 'woocommerce_my_account_my_orders_actions', 'ace_withdrawal_order_action', 20, 2 );       // My account > Orders button per order
add_action( 'woocommerce_email_after_order_table', 'ace_withdrawal_email_link', 20, 3 );              // customer e-mails with the order table
// footer: ace_footer_legal_links() appends ace_withdrawal_link( '', 'withdraw-link' )
```

Handler (`admin_post_nopriv_ace_withdrawal` and `admin_post_ace_withdrawal`): nonce check, honeypot field `ace_website`, **rate limit 10 per hour per IP
via a transient keyed by `md5( IP )`** (disclosed in privacy row "abuse"; the IP itself is not stored), validation (name, order number, valid e-mail),
stores a private `ace_withdrawal` post (admin menu "Withdrawals", no create UI), matches the order quietly (order number + billing e-mail; the consumer is never told
whether it matched; order note added if it matched), sends:

1. **acknowledgement to the consumer** with the declaration and `wp_date( 'Y-m-d H:i:s T' )` (art. 30 ust. 4 UPK: durable medium, without undue delay);
2. a copy to the shop (Customizer e-mail or admin e-mail) with a loud WARNING line if the acknowledgement could not be sent.

Result states shown to the consumer: `ok` (date/time shown), `saved` (recorded but mail failed: "keep a screenshot and write to ..."), `invalid`, `limit`, `error`.
Nothing is refunded or changed automatically. Missing in 2acetech (candid): no return instructions in the acknowledgement; nobody is assigned to the
"Withdrawals" list; the 10/hour limit can block a legitimate user behind a shared IP. Add: return address and next steps to the acknowledgement e-mail,
a named process owner, a higher limit.

## 8. Footer legal bar, anchors fallback, page lists, and the private legal folder

**Footer** (`ace_footer_legal_links()` in `inc/legal.php`, echoed in `footer.php`): links appear only for **published** pages; while the dedicated page is a draft the
link falls back to the Regulamin anchor (`#par-5` delivery, `#par-6` returns, `#par-8` complaints) and privacy `#sec-6` for cookies:

```php
$rows = array(
	array( 'terms', '', '' ), array( 'privacy', '', '' ), array( 'cookies', '', '#sec-6' ),
	array( 'delivery', __( 'Delivery and payment', '2acetech' ), '#par-5' ), array( 'returns', __( 'Returns', '2acetech' ), '#par-6' ),
	array( 'complaints', __( 'Complaints', '2acetech' ), '#par-8' ), array( 'seller', '', '' ),
);
...   // then: the withdrawal link, then the cookie-settings button:
$links[] = sprintf( '<button type="button" class="cookie-link" data-cookie-open>%s</button>', esc_html__( 'Cookie settings', '2acetech' ) );
```
Footer copyright shows the **company** name (Customizer `ace_seller`), then the seller line "name · address · KRS/NIP/REGON" from `ace_seller_id`.

**Excluding legal pages from automatic lists** (they live in the legal bar only), `inc/legal.php`:

```php
function ace_legal_page_ids() {            // seven legal pages + withdrawal page + WooCommerce's refund_returns stub
	$ids = array();
	foreach ( array_keys( ace_legal_defs() ) as $key ) { $ids[] = ace_legal_page_id( $key ); }
	foreach ( array( 'odstap-od-umowy', 'refund_returns' ) as $slug ) { $page = get_page_by_path( $slug ); $ids[] = $page ? (int) $page->ID : 0; }
	return array_values( array_unique( array_filter( $ids ) ) );
}
```
used as `'exclude' => implode( ',', array_merge( ace_legal_page_ids(), ... ) )` in `wp_list_pages()` (header page list) and `get_pages()` (footer "help"
column fallback) in `inc/template-tags.php` (commit c907e25: "Legal pages only in the footer legal bar"). Without it the seven legal pages leaked into the
header menu on the live site.

**Private legal folder**: the theme ships the Polish source texts in `wp-content/themes/<theme>/legal/`. They are read by PHP only; **web access must be
denied** (commit c8547bb). `legal/.htaccess`:

```apache
# Source texts for the legal pages; read by PHP only, never served directly.
<IfModule mod_authz_core.c>
	Require all denied
</IfModule>
<IfModule !mod_authz_core.c>
	Order allow,deny
	Deny from all
</IfModule>
```
Test: `curl -s -o /dev/null -w '%{http_code}' $SHOP/wp-content/themes/<theme>/legal/regulamin-sklepu.html` must print `403`. On hosts without Apache semantics
(nginx) the `.htaccess` is ignored: block the path in the server config or move the texts outside the web root / into the database. Owner-side documents
(supplier agreement, DPA, GPSR block template) **never** go into the theme folder.

## 9. Product safety (GPSR) fields and the publish blocker (design; not built on 2acetech as of 2026-11-21: AUDIT D-10 open)

Reference implementation: `references/sketches/gpsr-and-omnibus.php` (PHP syntax checked in Playground on 2026-11-21; behaviour untested).

| Design point | Decision |
|---|---|
| Fields (product meta `_gpsr_*`) | manufacturer name/address/e-mail, "manufacturer outside EU" checkbox + EU responsible person name/address/e-mail, model, batch, CE yes/no, declaration URL, warnings (Polish), manual URL (Polish), minimum age (toys), optional app/assistants |
| Where | General tab of the product data panel (`woocommerce_product_options_general_product_data`); saved onto the product object in `woocommerce_admin_process_product_object` so the check sees the new values |
| Front end | `woocommerce_single_product_summary` priority 25, under the short description: visible without a click or tab (art. 19: "clearly and visibly") |
| **Publish blocker** | `woocommerce_after_product_object_save` (runs for the admin form **and** plugin imports such as AliDropship/Shelfbridge that call `$product->save()`): a product that is `publish` and lacks required data is set back to `draft` with an admin notice. Variable products: data sits on the parent |
| Importers | set import status to Draft; run a "missing data" report before publishing |
| Not covered | energy label/EPREL fiche for light sources, toy warnings text, batteries: separate fields or the description; check per category |

## 10. Omnibus: 30-day lowest price (design; not built on 2acetech)

Rule [A 2026-11-20]: next to every announced reduction show the **lowest price in the 30 days before the reduction** (art. 4 ust. 2-3 ustawy o informowaniu o cenach
towarów i usług, Dz.U. 2023 poz. 168; offers shorter than 30 days: lowest price since the start of the offer); the consumer may demand the most favourable price
(art. 5); fines up to PLN 20 000 (art. 6); CJEU 26 Sept 2024, C-330/23 (Aldi Süd): a percentage reduction is counted from that lowest price.

Simplest compliant policy for a new shop: **no sale prices, no struck-through regular price** (2acetech: `ace_deal_end()` shows a countdown only with a real end date; the home-page
deal sections are off until real discounts exist). If promotions are planned: log every price change (sketch `ace_price_log()` stores `[timestamp, price]` in meta) and print
the reference price with the reduced price (`woocommerce_get_price_html`, sketch `ace_price_html_omnibus()`), per variation. Dropshipping imports often carry a "regular price"
that the shop **never charged**: never show it as the "before" price (UPNPR art. 5 ust. 3 pkt 5 [A]). Lawyer: series of reductions, rounding to .99, scheduled sales.

## 11. Removing unsupported marketing claims (patterns)

The 2acetech audit removed or gated:

| Claim found | Replaced by / gated how |
|---|---|
| "Gwarancja producenta na każdy produkt" (trust strip) | item removed (an empty title hides a trust item: `ace_trust_items()` skips `'' === $title`); the Regulamin says there is no own guarantee |
| "Łatwe zwroty / 14 dni" as a perk (statutory right as a feature: black list art. 7 pkt 10 UPNPR) | removed |
| "Szybka dostawa", "z numerem śledzenia" | factual text: "Wysyłka do 3 dni roboczych, dostawa 2-5 dni roboczych"; tracking promise dropped (commit e5a2459) |
| "Marki" strip with invented brand names | theme falls back to placeholder names when no brand logos exist: section switched off until real brands exist |
| "Oferta tygodnia" badges on products with no sale price; "Bestsellery" on unsold products | bestsellers query restricted to `total_sales > 0`; deal sections off |
| Countdown that resets weekly | only with a real end date |
| Footer tagline "Elektronika premium" for lamps and toys | factual tagline |

Bestsellers (`inc/template-tags.php`):

```php
'meta_key'   => 'total_sales',
'meta_query' => array( array( 'key' => 'total_sales', 'value' => 0, 'type' => 'NUMERIC', 'compare' => '>' ) ),   // only products that have really been sold
'orderby'    => 'meta_value_num', 'order' => 'DESC',
```
Generalisation: every sentence the theme prints about the business must come from a Customizer field with a factual default or be **empty by default**; add a code
review item "no new marketing claim without a source" (`facts-vs-claims-truth-table.md`).

## 12. Polish gettext workflow

All UI strings are English `__()` strings with a Polish `.po` (230+ strings on 2acetech):

```sh
cd wp-content/themes/<theme>
find . -name '*.php' | xargs xgettext --language=PHP --from-code=UTF-8 \
  --keyword=__:1 --keyword=_e:1 --keyword=esc_html__:1 --keyword=esc_html_e:1 \
  --keyword=esc_attr__:1 --keyword=esc_attr_e:1 --keyword=_n:1,2 --no-location \
  -o languages/<theme>.pot
msgmerge -U languages/pl_PL.po languages/<theme>.pot     # then translate new entries (msgstr)
msgfmt -o languages/pl_PL.mo languages/pl_PL.po
```
Set Settings > General > Site Language = Polski. Rules: (1) legal strings must be exactly: "Zamówienie z obowiązkiem zapłaty", "Odstąp od umowy tutaj", "Potwierdź odstąpienie od umowy
tutaj", "Odrzuć wszystkie", "Zarządzaj plikami cookie", "Akceptuję", "Ustawienia cookies" (the cookie policy quotes them); (2) after editing the `.po`, recompile and **purge caches**; (3) plugin
strings (Photo Reviews, Stripe errors, tracking page) need Loco Translate or per-plugin `.po`; (4) fuzzy entries are ignored by `msgfmt` unless
fixed: `grep -n fuzzy languages/pl_PL.po`; (5) verify on the live site, not only in the file: read the label from the DOM.

`ace_auto_page_titles()` maps the English page titles WordPress created at install ("Cart", "Checkout", "Withdraw from contract here", "Privacy policy", ...) to translations
as long as the owner has not renamed the page (`the_title` filter).
