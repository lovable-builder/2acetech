# 2ACE Product Cleaner

Rewrites imported AliExpress products in Polish, inside WordPress. WooCommerce > **Product Cleaner**.

Per product: Polish title, bullets, description (intro, features, specification table), 3-5 tags, attributes
renamed and translated (by their real contents when the importer mislabelled them), "Ships from"/"Origin"
removed (only Poland variations stay on sale, the others are disabled, not deleted), SKU `2AC-0001...`,
default category, prices rounded **up** to .99. Products named `RMF-*` / `lig*` and anything already cleaned
are skipped. Status (draft/published) is never changed.

- **Stuck products** (v1.7): (1) when several live variations have the SAME options once "Ships from" is removed (different
  warehouses/prices of one product) the cleaner keeps the cheapest and disables the others (not deleted) instead of failing with
  "Duplicate options remain"; (2) when the AI's answer is not JSON (cut off or refused) it asks once more for a shorter answer, and
  the error now shows what the AI really answered (`finish`, `length`, first 160 characters).
- **No fake sales** (v1.6): the importer's price rules (Settings > Price in the importer, "Multiply with" x.x / y.y) add a made-up
  higher "regular" price to every product, so everything shows the same permanent ~21% "sale". Google Merchant Center and Polish
  law (Omnibus) treat that as misleading. Cleaning now makes the price customers actually pay the regular price and clears the sale
  price, so the amount charged does not change; products or variations with a scheduled sale (from/to dates) are left alone, and so
  are own products. The old pair is kept in `_aliclean_sale_backup`; Undo restores the original prices. Settings page > Prices >
  "Remove the importer's fake sale prices from all products" does it once for products already in the shop.
- **Brands** (v1.5): while cleaning, the AI reads the brand from the source ("Brand Name" or the start of the title); the
  plugin creates it under Products > Brands if it is missing (any letter case matches the existing one) and assigns it, so
  the shop can have a brand filter. Only real brands written literally in the source are used: guesses, "NoEnName", shop or
  seller names (Store, Official, Ltd ...), countries and over-long values are refused. The brand stays OUT of the title and
  description (the existing rule). Undo restores the previous brand. Settings page > Brands > "Detect brands of all products"
  backfills the products already in the shop from their original title and description. Own products (RMF-*, lig*) are skipped.
- **Banner phrase** (v1.3): when the cleaner creates a new *main* category it also writes a short catchy Polish phrase for it
  (term meta `ace_cat_tagline`, editable under Products > Categories > Banner phrase). The homepage carousel shows it on that
  category's slide. Phrases with superlatives or unprovable promises ("najlepsze", "gwarancja", "numer 1", "!") are refused;
  with no API key nothing is written and the theme uses a built-in phrase. Subcategories get no phrase.
- **Categories**: for every import the AI picks the category from what the product *is*: an existing one, or a
  new Polish one (names in plural, up to 3 levels, e.g. Sport i turystyka > Camping > Materace i maty, at most 12 main categories; past that, "Inne produkty").
  Duplicates are avoided (case-insensitive match). "Re-check categories of all products" on the settings page fixes
  products that already sit in the wrong place. Own products (RMF-*/lig*) are skipped.
- **Nothing ships from Poland** (v1.2): such a product is no longer skipped. The text is still cleaned into Polish
  (title, description, attributes), every variation stays disabled (private, not deleted), and the product goes to
  **Draft**, so nothing is sold with a false "shipped from Poland" promise. A note says so, and `_aliclean_no_poland`
  is set. Undo restores the old status, the variations and the text. Publish it again only after choosing a
  Polish-warehouse variation with the supplier.
- **Photos** (v1.1): every 10 minutes every imported product is kept at **5 photos at most** (main photo + gallery;
  the extra gallery photos only leave the gallery, the files stay in the media library and their ids are kept in
  `_aliclean_gallery_removed`). Products > list has a **Photos** column; imports older than 15 minutes with a single
  photo get a red badge and a warning above the list, linking to the importer's image queue (WooCommerce > ALD >
  Failed images: press **Run** when it says the background process is not running). The cleaner cannot start that
  queue itself. Own products (RMF-*/lig*) are skipped.
- **Automatic**: every 10 minutes (WP-Cron) cleans up to 2 imports that have not changed for 5 minutes.
- **Manual**: "Clean them now" on the settings page.
- **Undo**: Products list > hover > "Undo Polish cleanup" (restores title, text, SKU, attributes incl. shared
  ones, variations, prices, category, tags).
- The Polish copy is written by an AI through its API: **DeepSeek** (default, cheap; key from platform.deepseek.com)
  or Anthropic Claude. Pick the provider and paste the key in the settings; "Test connection" checks it. The model is
  chosen automatically from the account's model list unless you type one. Only product texts are sent, no customer data. Every answer is
  checked (length, bullets, forbidden words, every attribute value translated) before anything is written;
  a failing product is skipped and logged.

Test: `npx wp-playground-cli run-blueprint --blueprint=./playground/blueprint.cleaner-test.json --mount-before-install=./wp-content/plugins/2ace-product-cleaner:/wordpress/wp-content/plugins/2ace-product-cleaner --mount=./playground:/playground` writes `playground/_cleaner-result.json`.
