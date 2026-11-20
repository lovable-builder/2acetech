<?php
$out = array();
try {
	$theme = '/wordpress/wp-content/themes/2acetech/';
	foreach ( array( 'inc/woocommerce.php' ) as $f ) { try { token_get_all( file_get_contents( $theme . $f ), TOKEN_PARSE ); $out['syntax'] = 'ok'; } catch ( Throwable $e ) { $out['syntax'] = 'ERROR ' . $e->getMessage(); } }
	// A: variable, one variation, no attributes (the broken import shape).
	$a = new WC_Product_Variable(); $a->set_name( 'No options' ); $a->set_status( 'publish' ); $aid = $a->save();
	$v = new WC_Product_Variation(); $v->set_parent_id( $aid ); $v->set_regular_price( 50 ); $v->set_stock_status( 'instock' ); $vid = $v->save();
	WC_Product_Variable::sync( $aid ); wc_delete_product_transients( $aid );
	// B: variable with a real option.
	$b = new WC_Product_Variable(); $b->set_name( 'With options' ); $b->set_status( 'publish' );
	$at = new WC_Product_Attribute(); $at->set_name( 'Size' ); $at->set_options( array( 's', 'm' ) ); $at->set_visible( true ); $at->set_variation( true ); $b->set_attributes( array( $at ) ); $bid = $b->save();
	foreach ( array( 's', 'm' ) as $s ) { $x = new WC_Product_Variation(); $x->set_parent_id( $bid ); $x->set_attributes( array( 'size' => $s ) ); $x->set_regular_price( 10 ); $x->save(); }
	WC_Product_Variable::sync( $bid ); wc_delete_product_transients( $bid );
	$pa = wc_get_product( $aid ); $pb = wc_get_product( $bid );
	$out['A sole id matches variation'] = ( ace_sole_variation_id( $pa ) === $vid );
	$out['B sole id'] = ace_sole_variation_id( $pb );
	$out['A text'] = $pa->add_to_cart_text(); $out['B text'] = $pb->add_to_cart_text();
	$out['A url'] = $pa->add_to_cart_url(); $out['B url'] = $pb->add_to_cart_url();
	$GLOBALS['product'] = $pa; ob_start(); woocommerce_template_loop_add_to_cart(); $out['A button'] = trim( ob_get_clean() );
	$GLOBALS['product'] = $pb; ob_start(); woocommerce_template_loop_add_to_cart(); $out['B button'] = trim( ob_get_clean() );
	foreach ( array( $aid, $bid ) as $id ) { wp_delete_post( $id, true ); }
} catch ( Throwable $e ) { $out['ERROR'] = $e->getMessage() . ' @ ' . basename( $e->getFile() ) . ':' . $e->getLine(); }
file_put_contents( '/playground/_sole-result.json', wp_json_encode( $out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
