<?php
/**
 * Deal of the week: which products appear in the homepage countdown card.
 *
 * Each product has a "Deal of the week" tick box (Product data > General).
 * Ticked products that are on sale and in stock show in the card, soonest
 * ending first. With none ticked, the first product on sale is used.
 *
 * @package 2acetech
 */

defined( 'ABSPATH' ) || exit;

/**
 * Tick box on the product edit screen, under the sale price.
 */
function ace_deal_field() {
	woocommerce_wp_checkbox(
		array(
			'id'          => '_ace_deal',
			'label'       => __( 'Deal of the week', '2acetech' ),
			'description' => __( 'Show this product in the countdown card on the homepage. It needs a sale price; the countdown runs to the sale end date.', '2acetech' ),
		)
	);
}
add_action( 'woocommerce_product_options_pricing', 'ace_deal_field' );

/**
 * Save the tick box. WooCommerce has already checked the nonce and rights.
 *
 * @param WC_Product $product Product being saved.
 */
function ace_deal_save( $product ) {
	$product->update_meta_data( '_ace_deal', isset( $_POST['_ace_deal'] ) ? 'yes' : 'no' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
}
add_action( 'woocommerce_admin_process_product_object', 'ace_deal_save' );

/**
 * Products ticked as a deal that can be shown right now.
 *
 * @param int $limit Maximum number of products.
 * @return WC_Product[] Soonest-ending first; products with no end date last.
 */
function ace_deal_products( $limit = 4 ) {
	if ( ! ace_has_store() ) {
		return array();
	}

	$ids = get_posts(
		array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => 20,
			'fields'         => 'ids',
			'meta_key'       => '_ace_deal', // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'     => 'yes', // phpcs:ignore WordPress.DB.SlowDBQuery
			'no_found_rows'  => true,
		)
	);

	$items = array();
	foreach ( $ids as $id ) {
		$product = wc_get_product( $id );
		if ( $product && $product->is_visible() && $product->is_in_stock() && $product->is_on_sale() ) {
			$items[] = $product;
		}
	}

	$ends = static function ( $product ) {
		$date = $product->get_date_on_sale_to();
		return $date ? $date->getTimestamp() : PHP_INT_MAX;
	};
	usort(
		$items,
		static function ( $a, $b ) use ( $ends ) {
			return $ends( $a ) <=> $ends( $b );
		}
	);

	return array_slice( $items, 0, $limit );
}
