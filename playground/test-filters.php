<?php
// Test: shop filter side panel. Writes /playground/_filters-result.json
$out = array();
try {
	$sport = wp_insert_term( 'Sport i turystyka', 'product_cat' )['term_id'];
	$camp  = wp_insert_term( 'Camping', 'product_cat', array( 'parent' => $sport ) )['term_id'];
	$light = wp_insert_term( 'Oświetlenie', 'product_cat' )['term_id'];
	$mk = function ( $name, $cat, $brand, $price, $attrs, $stock = true ) {
		$p = new WC_Product_Simple();
		$p->set_name( $name ); $p->set_regular_price( (string) $price ); $p->set_status( 'publish' );
		$p->set_stock_status( $stock ? 'instock' : 'outofstock' );
		$list = array();
		foreach ( $attrs as $n => $vals ) {
			$a = new WC_Product_Attribute(); $a->set_id( 0 ); $a->set_name( $n ); $a->set_options( $vals ); $a->set_visible( true ); $a->set_variation( false );
			$list[] = $a;
		}
		$p->set_attributes( $list );
		$id = $p->save();
		wp_set_object_terms( $id, array( (int) $cat ), 'product_cat' );
		if ( $brand ) { wp_set_object_terms( $id, $brand, 'product_brand' ); }
		return $id;
	};
	$p1 = $mk( 'P1', $camp, 'Naturehike', 100, array( 'Kolor' => array( 'Czarny', 'Zielony' ), 'Rozmiar' => array( 'S', 'M' ) ) );
	$p2 = $mk( 'P2', $camp, 'Widesea', 300, array( 'Kolor' => array( 'Zielony' ) ) );
	$p3 = $mk( 'P3', $light, 'Jelena', 700, array( 'Kolor' => array( 'Czarny' ), 'Rozmiar' => array( 'M', 'L' ) ) );
	$p4 = $mk( 'P4', $light, 'Jelena', 1500, array( 'Kolor' => array( 'Biały' ) ), false );
	$p5 = $mk( 'P5', $light, '', 50, array( 'barcode' => array( '123', '456' ), 'Wariant' => array( 'a', 'b' ) ) );

	delete_transient( 'ace_filter_index_v1' );
	$idx = ace_filter_index();
	$out['index has 5 rows'] = 5 === count( $idx );
	$out['barcode/Wariant options skipped'] = empty( $idx[ $p5 ]['attrs'] );
	$out['P1 options'] = array_keys( $idx[ $p1 ]['attrs'] );
	$out['index cached'] = is_array( get_transient( 'ace_filter_index_v1' ) );
	ace_filter_flush();
	$out['flush works'] = false === get_transient( 'ace_filter_index_v1' );

	$srt = function ( $a ) { sort( $a ); return $a; };
	$match = function ( $get ) use ( $idx ) {
		$_GET = $get;
		$f = ace_filter_request(); $ids = array();
		foreach ( $idx as $id => $row ) { if ( ace_filter_match( $row, $f ) ) { $ids[] = $id; } }
		$_GET = array();
		sort( $ids );
		return $ids;
	};
	$out['brand=jelena -> P3,P4'] = $srt( array( $p3, $p4 ) ) === $match( array( 'fbrand' => array( 'jelena' ) ) );
	$out['kolor=czarny -> P1,P3'] = $srt( array( $p1, $p3 ) ) === $match( array( 'fattr' => array( 'kolor' => array( 'Czarny' ) ) ) );
	$out['jelena + czarny -> P3'] = array( $p3 ) === $match( array( 'fbrand' => array( 'jelena' ), 'fattr' => array( 'kolor' => array( 'czarny' ) ) ) );
	$out['two brands (OR)'] = $srt( array( $p1, $p2 ) ) === $match( array( 'fbrand' => array( 'naturehike', 'widesea' ) ) );
	$out['price 200-800 -> P2,P3'] = $srt( array( $p2, $p3 ) ) === $match( array( 'min_price' => '200', 'max_price' => '800' ) );
	$out['in stock excludes P4'] = ! in_array( $p4, $match( array( 'instock' => '1' ) ), true );
	$out['two colours (OR)'] = $srt( array( $p1, $p3, $p4 ) ) === $match( array( 'fattr' => array( 'kolor' => array( 'czarny', 'biały' ) ) ) );
	$out['nothing matches'] = array() === $match( array( 'fbrand' => array( 'jelena' ), 'min_price' => '1', 'max_price' => '60' ) );
	$out['garbage input safe'] = is_array( $match( array( 'fbrand' => '<script>', 'min_price' => 'abc', 'fattr' => 'x' ) ) );

	// A real main query on the shop archive.
	$run = function ( $get ) {
		$_GET = $get;
		$q = new WP_Query();
		$GLOBALS['wp_the_query'] = $q; $GLOBALS['wp_query'] = $q;
		$q->query( array( 'post_type' => 'product', 'post_status' => 'publish', 'posts_per_page' => 50 ) );
		$titles = wp_list_pluck( $q->posts, 'post_title' ); sort( $titles );
		return array( $titles, $q );
	};
	list( $all ) = $run( array() );
	$out['no filter: all 5 products'] = 5 === count( $all );
	list( $t ) = $run( array( 'fbrand' => array( 'jelena' ) ) );
	$out['main query narrowed by brand'] = array( 'P3', 'P4' ) === $t;
	list( $t ) = $run( array( 'fattr' => array( 'rozmiar' => array( 'M' ) ), 'max_price' => '500' ) );
	$out['main query: size M and up to 500 -> P1'] = array( 'P1' ) === $t;
	list( $t ) = $run( array( 'fbrand' => array( 'nope' ) ) );
	$out['no match -> empty list'] = array() === $t;

	// Facets and panel.
	$_GET = array( 'fbrand' => array( 'jelena' ) );
	$f = ace_filter_request();
	$fa = ace_filter_facets( $idx, $f );
	$out['brand counts ignore own brand filter'] = isset( $fa['brands']['naturehike'] ) && 1 === $fa['brands']['naturehike']['count'] && 2 === $fa['brands']['jelena']['count'];
	$out['colour counts follow brand filter'] = 1 === $fa['attrs']['kolor']['values']['czarny']['count'] && 0 === $fa['attrs']['kolor']['values']['zielony']['count'];
	$out['colour group first'] = 'kolor' === array_key_first( $fa['attrs'] );
	$out['price limits'] = 50.0 === $fa['min'] && 1500.0 === $fa['max'];
	$_GET = array();

	list( , $q ) = $run( array( 'fbrand' => array( 'jelena' ), 'fattr' => array( 'kolor' => array( 'czarny' ) ), 'min_price' => '100' ) );
	ob_start();
	ace_filter_layout_open(); ace_filter_toolbar_open(); ace_filter_toolbar_close();
	$html = ob_get_clean();
	$out['layout + panel printed'] = false !== strpos( $html, 'shop-layout' ) && false !== strpos( $html, 'data-filters-form' );
	$out['brand checkboxes'] = false !== strpos( $html, 'name="fbrand[]" value="jelena" checked' );
	$out['colour checkbox checked + swatch'] = false !== strpos( $html, 'name="fattr[kolor][]" value="Czarny" checked' ) && false !== strpos( $html, 'sf-dot' );
	$out['size group present'] = false !== strpos( $html, 'name="fattr[rozmiar][]"' );
	$out['barcode group absent'] = false === strpos( $html, 'fattr[barcode]' );
	$out['price boxes + quick chips'] = false !== strpos( $html, 'name="min_price" value="100"' ) && false !== strpos( $html, 'sf-quick' );
	$out['stock + sale checkboxes'] = false !== strpos( $html, 'name="instock"' ) && false !== strpos( $html, 'name="onsale"' );
	$out['category tree'] = false !== strpos( $html, 'sf-cats' ) && false !== strpos( $html, 'Sport i turystyka' );
	$out['active filter chips + clear'] = 3 <= substr_count( $html, 'class="sf-tag"' ) && false !== strpos( $html, 'sf-clear' );
	$out['filters button with count'] = false !== strpos( $html, 'data-filters-open' ) && false !== strpos( $html, '<b>3</b>' );

	// Chip links round-trip.
	$_GET = array( 'fbrand' => array( 'jelena' ), 'fattr' => array( 'kolor' => array( 'czarny', 'zielony' ) ), 'min_price' => '100', 'instock' => '1' );
	$f = ace_filter_request();
	$url = ace_filter_url( $f );
	parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $back );
	$_GET = $back;
	$f2 = ace_filter_request();
	$out['url round-trip keeps every filter'] = $f === $f2;
	$out['url under shop base'] = 0 === strpos( $url, ace_shop_url() );
	$_GET = array();
} catch ( Throwable $e ) {
	$out['ERROR'] = $e->getMessage() . ' @ ' . basename( $e->getFile() ) . ':' . $e->getLine();
}
file_put_contents( '/playground/_filters-result.json', wp_json_encode( $out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
