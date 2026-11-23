<?php
// Test: the brand filter chips on shop and category pages. Writes /playground/_brandfilter-result.json
$out = array();
try {
	if ( ! taxonomy_exists( 'product_brand' ) ) { register_taxonomy( 'product_brand', 'product', array( 'hierarchical' => true, 'public' => true ) ); }
	$cat = function ( $n, $p = 0 ) { $t = wp_insert_term( $n, 'product_cat', array( 'parent' => $p ) ); return (int) $t['term_id']; };
	$sport = $cat( 'Sport i turystyka' ); $camp = $cat( 'Camping', $sport ); $light = $cat( 'Oświetlenie' );
	$br = function ( $n ) { $t = wp_insert_term( $n, 'product_brand' ); return (int) $t['term_id']; };
	$nh = $br( 'Naturehike' ); $lob = $br( 'Lobiston' ); $rim = $br( 'Rimfya' ); $unused = $br( 'Nieużywana' );
	$mk = function ( $name, $catid, $brand = 0, $hidden = false ) {
		$p = new WC_Product_Simple(); $p->set_name( $name ); $p->set_status( 'publish' ); $p->set_regular_price( '50' ); $p->set_category_ids( array( $catid ) );
		if ( $hidden ) { $p->set_catalog_visibility( 'hidden' ); }
		$id = $p->save(); if ( $brand ) { wp_set_object_terms( $id, array( $brand ), 'product_brand' ); } return $id;
	};
	$mk( 'Mata 1', $camp, $nh ); $mk( 'Mata 2', $camp, $nh ); $mk( 'Śpiwór', $camp, $lob ); $mk( 'Koc bez marki', $camp );
	$mk( 'Lampa 1', $light, $rim ); $mk( 'Lampa 2', $light, $rim ); $mk( 'Ukryta', $camp, $lob, true );

	$here = function ( $kind, $catid = 0, $query = '' ) {
		if ( 'shop' === $kind ) {
			$GLOBALS['wp_query'] = new WP_Query( array( 'post_type' => 'product' ) );
		} else {
			$GLOBALS['wp_query'] = new WP_Query( array( 'post_type' => 'product', 'tax_query' => array( array( 'taxonomy' => 'product_cat', 'field' => 'term_id', 'terms' => $catid ) ) ) );
			$GLOBALS['wp_query']->is_tax = true; $GLOBALS['wp_query']->queried_object = get_term( $catid, 'product_cat' ); $GLOBALS['wp_query']->queried_object_id = $catid;
		}
		$GLOBALS['wp_the_query'] = $GLOBALS['wp_query'];
		$_GET = array(); if ( $query ) { $_GET['product_brand'] = $query; }
	};
	$names = function ( $items ) { return array_map( function ( $i ) { return $i['term']->name . '(' . $i['count'] . ')'; }, $items ); };

	// Shop: all brands with products, biggest first; unused and hidden-product brands don't count.
	$here( 'shop' ); add_filter( 'woocommerce_is_shop', '__return_true' );
	$out['shop: brands + counts'] = $names( ace_brand_filter_items() );

	// Category page: only the brands of that category (including subcategories).
	remove_filter( 'woocommerce_is_shop', '__return_true' );
	$here( 'cat', $sport );
	$out['Sport (has Camping inside): brands'] = $names( ace_brand_filter_items() );
	$here( 'cat', $light );
	$out['Oświetlenie: brands'] = $names( ace_brand_filter_items() );

	// Markup on the category page that has 2 brands.
	$here( 'cat', $camp );
	$GLOBALS['wp_query']->is_product_category = true;
	add_filter( 'woocommerce_is_product_category', '__return_true' );
	ob_start(); ace_brand_filter(); $html = ob_get_clean();
	$out['Camping: chips shown (2 brands)'] = false !== strpos( $html, 'cat-chips--brands' );
	$out['Camping: has All brands + both brands'] = false !== strpos( $html, 'Wszystkie marki' ) || false !== strpos( $html, 'All brands' );
	$out['Camping: Naturehike chip with count'] = 1 === preg_match( '#product_brand=naturehike[^>]*>Naturehike <small>2</small>#', $html );
	$out['Camping: "All brands" is current when nothing selected'] = 1 === preg_match( '#cat-chip is-current"[^>]*>(All brands|Wszystkie marki)#', $html );

	// A selected brand: its chip is current, links keep working, and the list of brands is not narrowed.
	$here( 'cat', $camp, 'lobiston' ); $GLOBALS['wp_query']->is_product_category = true;
	ob_start(); ace_brand_filter(); $html2 = ob_get_clean();
	$out['selected: Lobiston chip current'] = 1 === preg_match( '#cat-chip is-current" href="[^"]*" aria-current="true">Lobiston#', $html2 );
	$out['selected: other brands still offered'] = false !== strpos( $html2, 'Naturehike' );
	$_SERVER['REQUEST_URI'] = '/product-category/camping/page/3/?orderby=price&product_brand=lobiston';
	$out['url: add brand, back to page 1, keep other args'] = ace_brand_filter_url( 'naturehike' );
	$out['url: clear brand'] = ace_brand_filter_url( '' );

	// Nothing to choose: a category with one brand shows nothing; a hidden brand-less list too.
	$only = $cat( 'Tylko jedna' ); $mk( 'X', $only, $nh ); $mk( 'Y', $only );
	$here( 'cat', $only ); $GLOBALS['wp_query']->is_product_category = true;
	ob_start(); ace_brand_filter(); $out['one brand only: no chips'] = '' === trim( ob_get_clean() );
	$here( 'cat', $only, 'naturehike' ); $GLOBALS['wp_query']->is_product_category = true;
	ob_start(); ace_brand_filter(); $out['one brand but selected: chips stay (to clear it)'] = false !== strpos( ob_get_clean(), 'cat-chips--brands' );

	// Not on product pages / other pages.
	$GLOBALS['wp_query'] = new WP_Query( array( 'post_type' => 'post' ) ); $GLOBALS['wp_the_query'] = $GLOBALS['wp_query']; $_GET = array();
	remove_filter( 'woocommerce_is_product_category', '__return_true' );
	ob_start(); ace_brand_filter(); $out['not on other pages'] = '' === trim( ob_get_clean() );
	$out['hooked after category chips'] = 6 === has_action( 'woocommerce_before_shop_loop', 'ace_brand_filter' ) && 5 === has_action( 'woocommerce_before_shop_loop', 'ace_category_chips' );
} catch ( Throwable $e ) {
	$out['ERROR'] = $e->getMessage() . ' @ ' . basename( $e->getFile() ) . ':' . $e->getLine();
}
file_put_contents( '/playground/_brandfilter-result.json', wp_json_encode( $out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
