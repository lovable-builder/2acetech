# Launch checklist: 2acetech.pl

Work top to bottom. Items marked **(legal)** should be reviewed by a Polish
lawyer or accountant; this list is a practical guide, not legal advice.

## 1. Business details (Appearance > Customize > Store details)

- [ ] Seller company name and NIP / registry number (shown in the footer and on
      the Contact page; required for an online shop in the EU)
- [ ] Address, phone, email, social links
- [ ] Footer logo (white version) if you replace the default logo
- [ ] WordPress Settings > General: real Site Title and Tagline (used in search
      results and link previews)

## 2. Legal pages (legal)

Create four pages, then connect them:

- [ ] **Regulamin** (terms and conditions): WooCommerce > Settings > Advanced >
      Page setup > Terms and conditions. The checkout then shows the accept box.
- [ ] **Polityka prywatności** (privacy policy): Settings > Privacy > choose page.
- [ ] **Zwroty i odstąpienie od umowy** (returns, 14-day right of withdrawal,
      with the model withdrawal form), linked from the footer menu.
- [ ] **Dostawa i płatności** (delivery and payments).
- [ ] Footer menu: Appearance > Menus > assign to "Footer: Customer care".

The theme already: labels the order button "Order with obligation to pay" (EU
law), shows terms and privacy links in the footer, and shows no fake urgency.

## 3. Cookies and tracking (legal)

The theme sets only essential cookies (cart, session). **Before** adding Google
Analytics, Meta Pixel, ads, live chat or embedded video, install a consent
plugin (Complianz, CookieYes or Cookiebot) and block those tools until consent.

## 4. Prices and VAT (legal)

- [ ] WooCommerce > Settings > General: tick **Enable taxes**.
- [ ] Tax tab: prices entered **including** tax; display prices **including**
      tax; add a "incl. VAT" price suffix.
- [ ] Standard rate table: Poland, 23% (check each product category).
- [ ] Sale prices: Polish/EU "Omnibus" rules require showing the lowest price of
      the previous 30 days next to a price reduction. The theme shows the
      regular price struck through exactly as you enter it; follow the rule when
      you set sale prices.

## 5. Payments and delivery

- [ ] Payment provider: for Poland pick one that offers BLIK, Przelewy24 and
      cards (for example the Stripe or Przelewy24/PayU plugins). Test in test
      mode first. Never put keys in the theme.
- [ ] WooCommerce > Settings > Shipping: zones and rates; add InPost parcel
      lockers via a plugin if you want them.
- [ ] Product weights and dimensions, if rates depend on them.

## 6. Email

- [ ] WooCommerce > Settings > Emails: sender name and address (use an address
      on your own domain).
- [ ] Set up SMTP with a mailbox from Hostinger (plugin such as FluentSMTP or WP
      Mail SMTP) and add SPF and DKIM records. Without this, order emails and
      Contact form messages can land in spam or not arrive.
- [ ] Send yourself a test order email and a Contact form message.

## 7. Technical

- [ ] Hostinger: free SSL active, force HTTPS.
- [ ] Settings > Permalinks: "Post name".
- [ ] Backups on (Hostinger backups or a plugin) and a restore tested once.
- [ ] After each theme update, purge any cache plugin.
- [ ] Google Search Console: add the site and submit `/wp-sitemap.xml`.
- [ ] Appearance > Customize > Landing page: replace all placeholder copy and
      hero tags with true statements.

## 8. Test purchase (every payment and delivery method)

- [ ] Add to cart from the homepage, a category page and a product page.
- [ ] Variable product: pick options, price updates, correct variation in cart.
- [ ] Checkout as guest and as a new customer; coupon; each payment method.
- [ ] Order received page, emails to you and the customer, order in admin.
- [ ] A refund and a cancelled order.
- [ ] Phone (iPhone and Android): header, hero, product page, sticky buy bar,
      checkout.

## 9. Go live

- [ ] WooCommerce > Settings > Site visibility: set to **Live**.
- [ ] Add real products (photos, descriptions, SKUs, stock); demo products
      disappear on their own once a product is published.
- [ ] Share the link on WhatsApp and Facebook to check the preview image.
- [ ] Remove any test orders and test customers.
