<?php
// Test: the supplier's delivery estimate is hidden from customers. Writes /playground/_shiptext-result.json
$out = array();
try {
	$out['detects "[Free] (20-30 days)"'] = ace_is_supplier_delivery_text( '[Free] (20-30 days)' );
	$out['detects "(2-3 dni)"'] = ace_is_supplier_delivery_text( 'Kurier (2-3 dni)' );
	$out['normal text kept'] = ! ace_is_supplier_delivery_text( 'Kolor: Czarny' ) && ! ace_is_supplier_delivery_text( 'Rozmiar 20-30 cm' );
	$rows = ace_hide_delivery_cart_data( array(
		array( 'key' => 'Shipping', 'value' => '[Free] (20-30 days)', 'display' => '[Free] (20-30 days)' ),
		array( 'key' => 'Color', 'value' => 'Black', 'display' => 'Black' ),
	) );
	$out['cart: only the estimate row removed'] = 1 === count( $rows ) && 'Color' === $rows[0]['key'];

	$resp = new WP_REST_Response( array( 'items' => array( array( 'name' => 'Lampa', 'item_data' => array( array( 'key' => 'Shipping', 'value' => '[Free] (20-30 days)' ), array( 'key' => 'Color', 'value' => 'Black' ) ) ) ) ) );
	$req  = new WP_REST_Request( 'GET', '/wc/store/v1/cart' );
	$r2   = ace_hide_delivery_store_api( $resp, rest_get_server(), $req );
	$rows = $r2->get_data()['items'][0]['item_data'];
	$out['store api: estimate removed, colour kept'] = 1 === count( $rows ) && 'Color' === $rows[0]['key'];
	$resp3 = new WP_REST_Response( array( 'items' => array( array( 'item_data' => array( array( 'key' => 'Shipping', 'value' => '[Free] (20-30 days)' ) ) ) ) ) );
	$r3    = ace_hide_delivery_store_api( $resp3, rest_get_server(), new WP_REST_Request( 'GET', '/wp/v2/posts' ) );
	$out['other routes untouched'] = 1 === count( $r3->get_data()['items'][0]['item_data'] );

	$p = new WC_Product_Simple(); $p->set_name( 'Lampa' ); $p->set_regular_price( '50' ); $p->set_status( 'publish' ); $pid = $p->save();
	$o = wc_create_order(); $o->add_product( wc_get_product( $pid ), 1 ); $o->calculate_totals(); $o->save();
	$item = array_values( $o->get_items() )[0];
	$item->add_meta_data( 'Shipping', '[Free] (20-30 days)' ); $item->add_meta_data( 'Color', 'Black' ); $item->save();
	$item = array_values( wc_get_order( $o->get_id() )->get_items() )[0];
	$shown = wp_list_pluck( $item->get_formatted_meta_data(), 'key' );
	$out['order meta shown to customers'] = array_values( $shown );
	$out['order: estimate hidden, colour kept'] = in_array( 'Color', $shown, true ) && ! in_array( 'Shipping', $shown, true );
	$out['order: raw data still stored (fulfilment)'] = '[Free] (20-30 days)' === $item->get_meta( 'Shipping' );
} catch ( Throwable $e ) {
	$out['ERROR'] = $e->getMessage() . ' @ ' . basename( $e->getFile() ) . ':' . $e->getLine();
}
file_put_contents( '/playground/_shiptext-result.json', wp_json_encode( $out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
