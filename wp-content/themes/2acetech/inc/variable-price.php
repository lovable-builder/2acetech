<?php
/**
 * Variable products: show ONE price ("od 615,99 zł", the lowest) instead of a range, and tell Google the
 * same single price.
 *
 * Why: the dropshipping import makes many products with several options (size, colour) at different prices.
 * WooCommerce prints "615,99 zł – 768,99 zł" and tells Google "AggregateOffer, from 615,99 to 768,99".
 * Google Merchant Center compares the price on the page with the price in the feed, and a range never
 * equals one feed price, which is a classic cause of "price mismatch" warnings.
 *
 * What the customer sees:
 *  - Lists, home page, search, recommendations: "od 615,99 zł" (the lowest price of the options).
 *  - Product page: the same "od ..." line until an option is chosen; WooCommerce then replaces it with the
 *    price of the chosen option, as it always did.
 *  - Google (structured data): one Offer at the lowest price, which equals the "od" price on the page.
 *
 * Products whose options all cost the same keep their normal single price.
 *
 * @package 2acetech
 */

defined( 'ABSPATH' ) || exit;

/**
 * Lowest and highest price (with tax as displayed) of a variable product, or null if it has one price.
 *
 * @param WC_Product $product Product.
 * @return array|null array( min, max ) of regular and sale prices.
 */
function ace_variable_price_bounds( $product ) {
	if ( ! $product || ! $product->is_type( 'variable' ) ) {
		return null;
	}
	$min = (float) $product->get_variation_price( 'min', true );
	$max = (float) $product->get_variation_price( 'max', true );
	if ( $min <= 0 || $min === $max ) {
		return null;
	}
	return array( $min, $max );
}

/**
 * "od 615,99 zł" instead of "615,99 zł – 768,99 zł".
 *
 * @param string     $html    Default price HTML.
 * @param WC_Product $product Product.
 * @return string
 */
function ace_variable_price_html( $html, $product ) {
	$b = ace_variable_price_bounds( $product );
	if ( ! $b ) {
		return $html;
	}
	$min_reg = (float) $product->get_variation_regular_price( 'min', true );
	$from    = '<span class="ace-from">' . esc_html__( 'from', '2acetech' ) . '</span> ';

	if ( $product->is_on_sale() && $min_reg > $b[0] ) {
		$out = $from . wc_format_sale_price( wc_price( $min_reg ), wc_price( $b[0] ) );
	} else {
		$out = $from . wc_price( $b[0] );
	}
	return $out . $product->get_price_suffix();
}
add_filter( 'woocommerce_variable_price_html', 'ace_variable_price_html', 20, 2 );
add_filter( 'woocommerce_variable_sale_price_html', 'ace_variable_price_html', 20, 2 );

/**
 * The option named in the address (?attribute_x=y), if it matches one purchasable option.
 *
 * @param WC_Product $product Variable product.
 * @return WC_Product_Variation|null
 */
function ace_requested_variation( $product ) {
	$attrs = array();
	foreach ( $_GET as $key => $value ) { // phpcs:ignore WordPress.Security.NonceVerification
		if ( 0 === strpos( (string) $key, 'attribute_' ) && is_scalar( $value ) ) {
			$attrs[ sanitize_title( $key ) ] = wc_clean( wp_unslash( (string) $value ) );
		}
	}
	if ( ! $attrs ) {
		return null;
	}
	$id = ( new WC_Product_Data_Store_CPT() )->find_matching_product_variation( $product, $attrs );
	$v  = $id ? wc_get_product( $id ) : null;
	return ( $v && $v->is_purchasable() ) ? $v : null;
}

/**
 * Structured data: a single Offer at the lowest price instead of an AggregateOffer range.
 * Runs before ace_offer_policies() (priority 20) adds shipping and returns to the offer.
 *
 * @param array      $markup  Offer markup.
 * @param WC_Product $product Product.
 * @return array
 */
function ace_single_offer_markup( $markup, $product ) {
	$b = ace_variable_price_bounds( $product );
	if ( ! $b || empty( $markup['@type'] ) || 'AggregateOffer' !== $markup['@type'] ) {
		return $markup;
	}
	// The Google feed lists every option as its own item and links to it with the option in the address
	// (?attribute_rozmiar=L ...). For such a link the page must tell Google that option's own price.
	$price = $b[0];
	$match = ace_requested_variation( $product );
	if ( $match ) {
		$price = (float) wc_get_price_to_display( $match );
	}
	$offer = array(
		'@type'         => 'Offer',
		'price'         => wc_format_decimal( $price, wc_get_price_decimals() ),
		'priceCurrency' => isset( $markup['priceCurrency'] ) ? $markup['priceCurrency'] : get_woocommerce_currency(),
	);
	foreach ( array( 'priceValidUntil', 'availability', 'url', 'seller', 'itemCondition' ) as $key ) {
		if ( isset( $markup[ $key ] ) ) {
			$offer[ $key ] = $markup[ $key ];
		}
	}
	return $offer;
}
add_filter( 'woocommerce_structured_data_product_offer', 'ace_single_offer_markup', 10, 2 );
