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
```

`playground/` and the `blueprint*.json` files only configure the preview
site. They are not part of the theme.

## The theme

- **Landing page**: `front-page.php`, one template part per section in
  `template-parts/`. Reorder or remove sections there.
- **Scroll hero**: `template-parts/hero.php`, `assets/css/hero.css`,
  `assets/js/hero.js`. Scenes (copy, art, CTA) come from `ace_hero_scenes()` in
  `inc/demo-data.php` and can be replaced with the `ace_hero_scenes` filter.
  It honours `prefers-reduced-motion` and degrades to a static hero without JS.
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
