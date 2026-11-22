<?php
// Test: the home "mix" shows products from every category, not just the newest uploads.
// Writes /playground/_mix-result.json
$out = array();
try {
	$cat = function ( $name, $parent = 0 ) { $t = wp_insert_term( $name, 'product_cat', array( 'parent' => $parent ) ); return (int) $t['term_id']; };
	$camping = $cat( 'Camping' ); $light = $cat( 'Oświetlenie' ); $toys = $cat( 'Zabawki' ); $kids = $cat( 'Klocki', $toys );
	$seq = 0;
	$mk = function ( $name, $catid, $opts = array() ) use ( &$seq ) {
		$p = new WC_Product_Simple();
		$p->set_name( $name ); $p->set_status( 'publish' ); $p->set_regular_price( '50' );
		$p->set_category_ids( array( $catid ) );
		if ( ! empty( $opts['featured'] ) ) { $p->set_featured( true ); }
		if ( ! empty( $opts['sale'] ) ) { $p->set_sale_price( '40' ); }
		if ( ! empty( $opts['out'] ) ) { $p->set_stock_status( 'outofstock' ); }
		$id = $p->save();
		wp_update_post( array( 'ID' => $id, 'post_date' => gmdate( 'Y-m-d H:i:s', time() - 1000 + 10 * ( ++$seq ) ) ) ); // later = newer
		return $id;
	};
	// Camping was uploaded LAST and is the biggest: 6 products. Lighting 2, toys 1 (in a subcategory).
	$l1 = $mk( 'Lamp A', $light ); $l2 = $mk( 'Lamp B', $light, array( 'featured' => true ) );
	$t1 = $mk( 'Blocks', $kids );
	$c = array(); for ( $i = 1; $i <= 6; $i++ ) { $c[$i] = $mk( "Camp $i", $camping, 3 === $i ? array( 'sale' => true ) : array() ); }
	$out_of_stock = $mk( 'Gone mat', $camping, array( 'out' => true ) );

	$newest = array_map( function ( $p ) { return $p->get_id(); }, ace_query_products( 'latest', 6 ) );
	$out['"latest" would show (the old behaviour)'] = array_map( function ( $id ) { return get_the_title( $id ); }, $newest );

	$mix = ace_mix_products( 6 );
	$names = array_map( function ( $p ) { return $p->get_name(); }, $mix );
	$out['mix shows'] = $names;
	$cats_in = array();
	foreach ( $mix as $p ) { $cats_in[] = wp_get_post_terms( $p->get_id(), 'product_cat', array( 'fields' => 'ids' ) )[0]; }
	$top = function ( $id ) { $t = get_term( $id, 'product_cat' ); while ( $t->parent ) { $t = get_term( $t->parent, 'product_cat' ); } return $t->name; };
	$out['main categories in the mix'] = array_values( array_unique( array_map( $top, $cats_in ) ) );
	$out['all 3 main categories present'] = 3 === count( $out['main categories in the mix'] );
	$out['first round is one per category'] = 3 === count( array_unique( array_map( $top, array_slice( $cats_in, 0, 3 ) ) ) );
	$out['no duplicates'] = count( $names ) === count( array_unique( $names ) );
	$out['out of stock never shown'] = ! in_array( 'Gone mat', $names, true );
	$out['starred product leads its category'] = array_search( 'Lamp B', $names, true ) < array_search( 'Lamp A', $names, true );
	$out['sale product leads camping'] = array_search( 'Camp 3', $names, true ) < array_search( 'Camp 6', $names, true );
	$out['biggest category first'] = 'Camping' === $top( $cats_in[0] );
	$out['6 asked, 6 shown'] = 6 === count( $mix );

	$two = ace_mix_products( 2 );
	$out['limit 2 -> 2 different categories'] = 2 === count( $two ) && $top( wp_get_post_terms( $two[0]->get_id(), 'product_cat', array( 'fields' => 'ids' ) )[0] ) !== $top( wp_get_post_terms( $two[1]->get_id(), 'product_cat', array( 'fields' => 'ids' ) )[0] );
	$out['ace_get_products("mix") routes here'] = count( ace_get_products( 'mix', 6 ) ) === 6;

	// Featured rail with nothing starred in a category-less call falls back to the mix, not the newest.
	foreach ( array( $l2 ) as $id ) { wc_get_product( $id )->set_featured( false ); $pp = wc_get_product( $id ); $pp->set_featured( false ); $pp->save(); }
	$fb = array_map( function ( $p ) { return $p->get_name(); }, ace_get_products( 'featured', 6 ) );
	$out['featured fallback = mix (spread)'] = count( array_unique( array_map( function ( $n ) { return substr( $n, 0, 4 ); }, $fb ) ) ) > 1;

	// Banner defaults and sanitising.
	$d = ace_banner_defaults( 1 );
	$out['banner 1 default source'] = $d['source'];
	$out['sanitize accepts mix'] = 'mix' === ace_sanitize_banner_source( 'mix' );
	$b = ace_banner_get( 1 );
	$out['banner products come from the mix'] = array_map( function ( $p ) { return $p->get_name(); }, ace_banner_products( $b ) ) === $names || count( ace_banner_products( $b ) ) === 6;
} catch ( Throwable $e ) {
	$out['ERROR'] = $e->getMessage() . ' @ ' . basename( $e->getFile() ) . ':' . $e->getLine();
}
file_put_contents( '/playground/_mix-result.json', wp_json_encode( $out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
