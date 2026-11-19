# 2ACE Product Cleaner

Rewrites imported AliExpress products in Polish, inside WordPress. WooCommerce > **Product Cleaner**.

Per product: Polish title, bullets, description (intro, features, specification table), 3-5 tags, attributes
renamed and translated (by their real contents when the importer mislabelled them), "Ships from"/"Origin"
removed (only Poland variations stay on sale, the others are disabled, not deleted), SKU `2AC-0001...`,
default category, prices rounded **up** to .99. Products named `RMF-*` / `lig*` and anything already cleaned
are skipped. Status (draft/published) is never changed.

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
