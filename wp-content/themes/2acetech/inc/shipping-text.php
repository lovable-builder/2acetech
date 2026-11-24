<?php
/**
 * Never show the supplier's delivery estimate to customers.
 *
 * The dropshipping plugin (ALD) prints the AliExpress shipping option next to the product in the cart,
 * checkout, order page and e-mails, e.g. "[Free] (20-30 days)". That estimate is the supplier's stored
 * data and does not match what happens (dispatch from Poland, delivery as promised on the delivery
 * page). A wrong delivery time shown to a buyer is a misleading statement, so any line that is only
 * such an estimate ("... (20-30 days)") is removed from what customers see. The data itself stays on the
 * order for fulfilment; the admin screens still show it.
 *
 * @package 2acetech
 */

defined( 'ABSPATH' ) || exit;

/** Does this text carry a delivery estimate like "(20-30 days)" or "(2-3 dni)"? */
function ace_is_supplier_delivery_text( $text ) {
	$text = wp_strip_all_tags( (string) $text );
	return (bool) preg_match( '/\(\s*\d+\s*[-–]\s*\d+\s*(days?|dni|dzień|working days)\s*\)/iu', $text );
}

/** Cart, checkout (classic and blocks) and mini cart. */
function ace_hide_delivery_cart_data( $item_data ) {
	foreach ( (array) $item_data as $i => $row ) {
		$value = isset( $row['display'] ) && '' !== $row['display'] ? $row['display'] : ( isset( $row['value'] ) ? $row['value'] : '' );
		if ( is_string( $value ) && ace_is_supplier_delivery_text( $value ) ) {
			unset( $item_data[ $i ] );
		}
	}
	return array_values( $item_data );
}
add_filter( 'woocommerce_get_item_data', 'ace_hide_delivery_cart_data', 99 );

/** Order page, thank-you page and e-mails (not the admin). */
function ace_hide_delivery_order_meta( $formatted_meta ) {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return $formatted_meta;
	}
	foreach ( (array) $formatted_meta as $id => $meta ) {
		if ( ace_is_supplier_delivery_text( isset( $meta->display_value ) ? $meta->display_value : '' ) ) {
			unset( $formatted_meta[ $id ] );
		}
	}
	return $formatted_meta;
}
add_filter( 'woocommerce_order_item_get_formatted_meta_data', 'ace_hide_delivery_order_meta', 99 );
