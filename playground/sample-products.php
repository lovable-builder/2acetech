<?php
/**
 * Sample WooCommerce products so the real-store path can be previewed
 * (npm run start:products). No images: the store placeholder shows.
 */

if ( ! class_exists( 'WC_Product_Simple' ) ) {
	return;
}

$categories = array( 'laptops' => 'Laptops & computers', 'phones' => 'Phones & tablets', 'audio' => 'Audio', 'cameras' => 'Cameras', 'wearables' => 'Wearables', 'gaming' => 'Gaming' );
foreach ( $categories as $slug => $name ) {
	if ( ! term_exists( $slug, 'product_cat' ) ) {
		wp_insert_term( $name, 'product_cat', array( 'slug' => $slug ) );
	}
}

$products = array(
	array( 'Sample Watch S2', 'wearables', 899, 1099, true, 120 ),
	array( 'Sample Book 14', 'laptops', 4299, 4799, true, 90 ),
	array( 'Sample ANC Headphones', 'audio', 649, 0, true, 150 ),
	array( 'Sample ActionCam 4K', 'cameras', 1299, 1499, false, 60 ),
	array( 'Sample 5G Phone', 'phones', 2799, 0, true, 200 ),
	array( 'Sample Speaker 360', 'audio', 549, 699, false, 80 ),
	array( 'Sample Tablet 11"', 'phones', 1899, 0, false, 70 ),
	array( 'Sample Console Bundle', 'gaming', 2199, 2399, true, 110 ),
);

foreach ( $products as $i => $row ) {
	$sku = 'SAMPLE-' . ( $i + 1 );
	if ( wc_get_product_id_by_sku( $sku ) ) {
		continue;
	}
	$p = new WC_Product_Simple();
	$p->set_name( $row[0] );
	$p->set_sku( $sku );
	$p->set_status( 'publish' );
	$p->set_regular_price( $row[3] ? (string) $row[3] : (string) $row[2] );
	if ( $row[3] ) {
		$p->set_sale_price( (string) $row[2] );
	}
	$p->set_featured( $row[4] );
	$p->set_short_description( 'Sample product for previewing the theme. Replace with your own description.' );
	$p->set_description( '<p>A longer description goes here. Use headings, lists and images to describe the product in detail.</p><ul><li>Feature one</li><li>Feature two</li><li>Feature three</li></ul>' );
	$p->set_manage_stock( true );
	$p->set_stock_quantity( $i < 3 ? 12 : 3 );
	$p->set_stock_status( 'instock' );
	// First two sale products get a sale end date, so their page shows the countdown.
	if ( $row[3] && $i < 2 ) {
		$p->set_date_on_sale_to( gmdate( 'Y-m-d', strtotime( '+9 days' ) ) );
		$p->update_meta_data( '_ace_deal', 'yes' ); // Shown in the homepage deal card.
	}
	$id = $p->save();
	wp_set_object_terms( $id, $row[1], 'product_cat' );
	update_post_meta( $id, 'total_sales', $row[5] );
}

// A variable product with colour and size, to preview swatches.
if ( ! wc_get_product_id_by_sku( 'SAMPLE-VAR' ) ) {
	$v = new WC_Product_Variable();
	$v->set_name( 'Sample Phone Case' );
	$v->set_sku( 'SAMPLE-VAR' );
	$v->set_status( 'publish' );

	$color = new WC_Product_Attribute();
	$color->set_name( 'Color' );
	$color->set_options( array( 'Black', 'White', 'Blue' ) );
	$color->set_visible( true );
	$color->set_variation( true );

	$size = new WC_Product_Attribute();
	$size->set_name( 'Size' );
	$size->set_options( array( 'S', 'M', 'L' ) );
	$size->set_visible( true );
	$size->set_variation( true );

	$v->set_attributes( array( $color, $size ) );
	$vid = $v->save();
	wp_set_object_terms( $vid, 'phones', 'product_cat' );

	foreach ( array( 'Black', 'White', 'Blue' ) as $ci => $c ) {
		foreach ( array( 'S', 'M', 'L' ) as $si => $sz ) {
			if ( 'Blue' === $c && 'L' === $sz ) {
				continue; // One missing combination, to see it disable.
			}
			$var = new WC_Product_Variation();
			$var->set_parent_id( $vid );
			$var->set_attributes( array( 'color' => $c, 'size' => $sz ) );
			$var->set_regular_price( (string) ( 79 + $si * 10 ) );
			$var->set_stock_status( 'instock' );
			$var->save();
		}
	}
	WC_Product_Variable::sync( $vid );
	update_post_meta( $vid, 'total_sales', 40 );
}

// One finished order, so the order-received page can be previewed. Its URL is
// written to playground/_order-url.txt (gitignored).
if ( function_exists( 'wc_create_order' ) && ! get_option( 'ace_preview_order' ) ) {
	$product = wc_get_product( wc_get_product_id_by_sku( 'SAMPLE-2' ) );
	if ( $product ) {
		$order = wc_create_order();
		$order->add_product( $product, 1 );
		$order->set_address( array( 'first_name' => 'Anna', 'last_name' => 'Kowalska', 'email' => 'anna@example.com', 'phone' => '500 100 200', 'address_1' => 'ul. Marszalkowska 10', 'city' => 'Warszawa', 'postcode' => '00-001', 'country' => 'PL' ), 'billing' );
		$order->set_address( array( 'first_name' => 'Anna', 'last_name' => 'Kowalska', 'address_1' => 'ul. Marszalkowska 10', 'city' => 'Warszawa', 'postcode' => '00-001', 'country' => 'PL' ), 'shipping' );
		$order->set_payment_method( 'cod' );
		$order->set_payment_method_title( 'Pay on delivery' );
		$order->calculate_totals();
		$order->update_status( 'processing' );
		update_option( 'ace_preview_order', $order->get_id() );
		@file_put_contents( '/playground/_order-url.txt', $order->get_checkout_order_received_url() );
	}
}
