<?php
/**
 * WooCommerce integration.
 *
 * @package 2acetech
 */

defined( 'ABSPATH' ) || exit;

/**
 * Declare WooCommerce support and the product gallery features.
 */
function ace_woocommerce_setup() {
	add_theme_support(
		'woocommerce',
		array(
			'thumbnail_image_width' => 600,
			'single_image_width'    => 900,
			'product_grid'          => array(
				'default_rows'    => 3,
				'min_rows'        => 1,
				'default_columns' => 4,
				'min_columns'     => 2,
				'max_columns'     => 5,
			),
		)
	);
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );
}
add_action( 'after_setup_theme', 'ace_woocommerce_setup' );

/**
 * Cart count and total in the header, refreshed by WooCommerce cart fragments.
 *
 * @param array $fragments Fragments.
 * @return array
 */
function ace_cart_fragments( $fragments ) {
	$fragments['span.cart-count'] = ace_cart_count_html();
	return $fragments;
}
add_filter( 'woocommerce_add_to_cart_fragments', 'ace_cart_fragments' );

/**
 * Cart count badge markup.
 *
 * @return string
 */
function ace_cart_count_html() {
	$count = ace_has_store() && WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
	return sprintf(
		'<span class="cart-count" data-count="%1$d"><span class="screen-reader-text">%2$s </span>%1$d</span>',
		$count,
		esc_html__( 'Items in cart:', '2acetech' )
	);
}

/**
 * The theme prints its own wrapper in woocommerce.php, and the shop has no
 * sidebar, so drop WooCommerce's default wrapper and sidebar.
 */
remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );
remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );

/**
 * Four products per row on shop pages.
 *
 * @return int
 */
function ace_loop_columns() {
	return 4;
}
add_filter( 'loop_shop_columns', 'ace_loop_columns' );

/**
 * Related products: four across.
 *
 * @param array $args Args.
 * @return array
 */
function ace_related_args( $args ) {
	$args['posts_per_page'] = 4;
	$args['columns']        = 4;
	return $args;
}
add_filter( 'woocommerce_output_related_products_args', 'ace_related_args' );

/**
 * Keep WooCommerce's cart fragments script available on the front page, where
 * the header cart count lives, even if the cart is empty.
 */
function ace_keep_cart_fragments() {
	if ( ace_has_store() && wp_script_is( 'wc-cart-fragments', 'registered' ) ) {
		wp_enqueue_script( 'wc-cart-fragments' );
	}
}
add_action( 'wp_enqueue_scripts', 'ace_keep_cart_fragments', 20 );
