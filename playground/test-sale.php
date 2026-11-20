<?php
$out = array();
try {
	$theme = '/wordpress/wp-content/themes/2acetech/';
	foreach ( array( 'inc/template-tags.php', 'inc/woocommerce.php', 'inc/product.php', 'template-parts/deal.php', 'inc/deal.php' ) as $f ) {
		try { token_get_all( file_get_contents( $theme . $f ), TOKEN_PARSE ); $out['syntax'][ $f ] = 'ok'; } catch ( Throwable $e ) { $out['syntax'][ $f ] = 'ERROR ' . $e->getMessage() . ' line ' . $e->getLine(); }
	}
	// 8 variable products (each with 6 on-sale variations) + 2 simple on sale: the raw on-sale list is mostly variation IDs.
	$made = array();
	for ( $i = 0; $i < 8; $i++ ) {
		$p = new WC_Product_Variable(); $p->set_name( "Var $i" ); $p->set_status( 'publish' );
		$attr = new WC_Product_Attribute(); $attr->set_name( 'Size' ); $attr->set_options( array( 'a', 'b', 'c', 'd', 'e', 'f' ) ); $attr->set_visible( true ); $attr->set_variation( true );
		$p->set_attributes( array( $attr ) ); $pid = $p->save(); $made[] = $pid;
		foreach ( array( 'a', 'b', 'c', 'd', 'e', 'f' ) as $s ) {
			$v = new WC_Product_Variation(); $v->set_parent_id( $pid ); $v->set_attributes( array( 'size' => $s ) ); $v->set_regular_price( 100 ); $v->set_sale_price( 80 ); $v->save();
		}
		WC_Product_Variable::sync( $pid );
	}
	for ( $i = 0; $i < 2; $i++ ) { $s = new WC_Product_Simple(); $s->set_name( "Sim $i" ); $s->set_status( 'publish' ); $s->set_regular_price( 50 ); $s->set_sale_price( 40 ); $made[] = $s->save(); }
	delete_transient( 'wc_products_onsale' );
	$raw = wc_get_product_ids_on_sale();
	$out['raw on-sale ids'] = count( $raw );
	$got = ace_query_products( 'sale', 4 );
	$out['sale query (limit 4)'] = array( count( $got ), array_map( function ( $p ) { return $p->get_name(); }, $got ) );
	$out['sale query (limit 20)'] = count( ace_query_products( 'sale', 20 ) );
	$out['deal end, no date'] = ace_deal_end( $got ? $got[0] : null );
	foreach ( $made as $id ) { wp_delete_post( $id, true ); }
} catch ( Throwable $e ) { $out['ERROR'] = $e->getMessage() . ' @ ' . basename( $e->getFile() ) . ':' . $e->getLine(); }
file_put_contents( '/playground/_sale-result.json', wp_json_encode( $out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
