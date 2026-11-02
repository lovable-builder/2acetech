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
	$p->set_manage_stock( false );
	$p->set_stock_status( 'instock' );
	$id = $p->save();
	wp_set_object_terms( $id, $row[1], 'product_cat' );
	update_post_meta( $id, 'total_sales', $row[5] );
}
