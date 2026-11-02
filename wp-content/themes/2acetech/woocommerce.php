<?php
/**
 * WooCommerce wrapper: shop, categories, single product and product search
 * render through here. Cart, checkout and account are normal pages (page.php).
 *
 * woocommerce_content() does not fire the before/after_main_content hooks,
 * so the theme markup is printed here directly.
 *
 * @package 2acetech
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<main id="content" class="site-main woo-main">
	<div class="container">
		<?php
		if ( function_exists( 'woocommerce_breadcrumb' ) ) {
			woocommerce_breadcrumb();
		}
		woocommerce_content();
		?>
	</div>
</main>
<?php
get_footer();
