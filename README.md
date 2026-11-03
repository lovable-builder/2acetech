# 2acetech

WordPress + WooCommerce storefront for 2acetech.pl. The theme lives in
`wp-content/themes/2acetech`.

## Preview locally

Needs Node 20.18+. Nothing else (no PHP, MySQL or Docker): it runs real
WordPress in WebAssembly via WordPress Playground.

```sh
npm install
npm start                # landing page with demo content, http://127.0.0.1:9400
npm run start:products   # same, plus 8 sample WooCommerce products
npm run start:pl         # same, with the site language set to Polish
```

`playground/` and the `blueprint*.json` files only configure the preview
site. They are not part of the theme.

## The theme

- **Landing page**: `front-page.php`, one template part per section in
  `template-parts/`. Reorder or remove sections there.
- **Scroll hero**: `template-parts/hero.php`, `assets/css/hero.css`,
  `assets/js/hero.js`. Scenes are edited in Customize > Landing page (see
  below). It honours `prefers-reduced-motion` and degrades to a static hero
  without JS.
- **Demo content**: until WooCommerce has a published product, every product
  section shows demo products (`inc/demo-data.php`). Admins see a notice. The
  moment a real product exists, the same sections show real data: featured,
  latest, on sale, bestsellers, categories and brands (WooCommerce Brands).
- **Logo**: the theme ships with transparent logos in `wp-content/themes/2acetech/assets/img`
  (`logo.png` for the header, `logo-light.png` for the dark footer), made from
  `brand/2ace-tech-logo-original.png`. Appearance > Customize > Site Identity
  overrides the header logo; Store details > Footer logo overrides the footer one.
- **Store details** (Customize): top bar text, phone, email, address, social
  links, deal countdown end, newsletter shortcode.
- **Deal of the week**: tick "Deal of the week" on a product (Product data >
  General, under the sale price). Ticked products that are on sale and in stock
  fill the homepage countdown card, soonest-ending first (up to 4, swipeable).
  Each countdown runs to that product's sale end date; without one it falls
  back to Store details > deal countdown end. Nothing ticked: first sale product.
- **Product page content** (per product, in the product editor):
  - Product data > **Attributes**: add custom attributes (Colour: Black,
    Material: Aluminium...) with "Visible on the product page" ticked. The first
    four show as key specs under the short description; all of them fill the
    **Specification** tab.
  - Product data > **Extra details**: "What's in the box" (one item per line)
    and "Questions and answers" (question line, answer line, blank line). Each
    becomes its own tab; leave empty to hide it.
  - The long **Description** is styled (headings, lists, tables, `<details>`).
  - Customize > **Product page**: the delivery/returns/warranty lines under the
    buy button (up to four, empty title hides one) and a dispatch note shown
    after "In stock".
- **Withdraw from contract here** (Directive 2023/2673): the theme creates a page
  `/odstap-od-umowy/` with a form (name, order number, email, optional goods) and
  links to it from the footer of every page, My Account > Orders, the order
  confirmation page and customer emails. Submitting emails the consumer an
  acknowledgement with date and time, copies the store, adds an order note when
  number and billing email match, and keeps every declaration under
  **Withdrawals** in the admin menu. Needs working email (SMTP).
- **Menus**: Appearance > Menus. Locations: Primary, Departments, Footer Shop,
  Footer Customer care. Sensible fallbacks show until assigned.
- **Colours and type**: CSS variables at the top of `assets/css/main.css`.
  Manrope is self-hosted (no Google Fonts requests).

## Icons and link sharing

`assets/img` holds the favicon (32/48), app icons (180/192), a 512 px site icon
and `og-default.png` (1200 x 630 share image). The head tags are in
`inc/seo.php`: favicon, canonical, description, Open Graph and X cards, and
Organization data on the home page. A site icon or share image set in the
Customizer overrides the bundled ones. If an SEO plugin (Yoast, Rank Math,
AIOSEO, SEOPress) is active, the theme stops printing share tags so they never
double up.

## Product page

`inc/product.php`, `assets/css/product.css`, `assets/js/product.js`.

- **Countdown**: shows when a product is on sale *and* the sale has an end date
  (Edit product > General > Sale price > Schedule).
- **Stock bar** ("Available / Already sold"): shows on sale products with
  Manage stock turned on (Inventory tab). "Only N left" appears at 5 or fewer.
  All numbers are real WooCommerce data; nothing is invented.
- **Swatches**: variation dropdowns become buttons. An attribute named
  Color/Colour/Kolor whose values are CSS colour names (Black, White, Blue)
  shows round colour dots; any other value shows a text button.
- **Brand**: uses WooCommerce Brands (Products > Brands), logo or name.
- Quantity stepper (also on the cart page), share links, copy-link, and a
  sticky "Add to cart" bar on phones.

## Cart, checkout and account

`assets/css/flow.css` skins WooCommerce's block cart and checkout (and the
classic shortcode versions), the order confirmation page and My Account.
`page.php` adds a three-step progress indicator (Cart, Details & payment,
Confirmation), a "Continue shopping" link and a reassurance row. In the
preview, `playground/setup.php` enables bank transfer and pay-on-delivery,
a Poland shipping zone and one finished order so every page has data.
Asset versions follow each file's modification time, so browsers pick up
theme updates immediately.

## Editing the landing page (no code)

Appearance > Customize > **Landing page**:

- **Hero scene 1-3**: show/hide, label, headline (last word is red), text,
  button and link, a product photo (transparent PNG) or a
  built-in picture, background glow, and up to three floating tags. The theme
  ships product photos for the watch, laptop and headphones scenes
  (`assets/img/hero-*.webp`); uploading your own photo in a scene replaces them.
- **Promo banners**: two banners, each with copy, link, photo or illustration,
  light or dark.
- **Trust strip**: four items; an empty title hides one.
- **Sections**: tick to show, drag to re-order every section below the hero.
- **Headings and texts**: section headings, newsletter copy, footer tagline.

Code lives in `inc/home.php` (defaults and readers) and
`inc/customizer-home.php` (the panel). Developers can still override output
with the `ace_hero_scenes` and `ace_promos` filters.

## Before launch

See [docs/launch-checklist.md](docs/launch-checklist.md): business details,
legal pages, VAT, payments, email, backups, test purchases and going live.
The theme includes a Contact page template (Page attributes > Template >
Contact), seller details in the footer, and the EU-compliant order button.

## Polish translation

`wp-content/themes/2acetech/languages/` holds `2acetech.pot` (template),
`pl_PL.po` (Polish, 230 strings) and the compiled `pl_PL.mo`. Set the site
language under Settings > General > Site Language > Polski and the theme,
WooCommerce and WordPress all switch to Polish together. The checkout button
reads "Zamawiam z obowiązkiem zapłaty".

After changing any theme text, regenerate and recompile:

```sh
cd wp-content/themes/2acetech
find . -name '*.php' | xargs xgettext --language=PHP --from-code=UTF-8 \
  --keyword=__:1 --keyword=_e:1 --keyword=esc_html__:1 --keyword=esc_html_e:1 \
  --keyword=esc_attr__:1 --keyword=esc_attr_e:1 --keyword=_n:1,2 --no-location \
  -o languages/2acetech.pot
msgmerge -U languages/pl_PL.po languages/2acetech.pot   # then translate new entries
msgfmt -o languages/pl_PL.mo languages/pl_PL.po
```
