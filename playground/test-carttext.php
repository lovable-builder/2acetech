<?php
$out = array();
try {
	// Simulate a plugin that forces English labels (late priority, like the real ones).
	add_filter( 'woocommerce_product_add_to_cart_text', function () { return 'Add To Cart'; }, 9999, 2 );
	$var = new WC_Product_Variable(); $var->set_name( 'Var' ); $var->save();
	$sim = new WC_Product_Simple(); $sim->set_name( 'Sim' ); $sim->set_regular_price( 10 ); $sim->save();
	$out['variable text'] = $var->add_to_cart_text();
	$out['simple text']   = $sim->add_to_cart_text();
	// A plugin that rewrites the finished HTML instead.
	remove_all_filters( 'woocommerce_product_add_to_cart_text' );
	add_filter( 'woocommerce_product_add_to_cart_text', 'ace_polish_cart_text', PHP_INT_MAX, 2 );
	add_filter( 'woocommerce_loop_add_to_cart_link', function ( $h ) { return str_replace( '>Dodaj do koszyka<', '>Add To Cart<', str_replace( '>Wybierz opcje<', '>Add To Cart<', $h ) ); }, 9999, 2 );
	global $product; $product = $sim; ob_start(); woocommerce_template_loop_add_to_cart(); $out['html simple'] = wp_strip_all_tags( ob_get_clean() );
	$product = $var; ob_start(); woocommerce_template_loop_add_to_cart(); $out['html variable'] = wp_strip_all_tags( ob_get_clean() );
	$out['already polish untouched'] = ace_polish_cart_text( 'Kup teraz', $sim );
	$var->delete( true ); $sim->delete( true );
} catch ( Throwable $e ) { $out['ERROR'] = $e->getMessage() . ' @ ' . basename( $e->getFile() ) . ':' . $e->getLine(); }
file_put_contents( '/playground/_carttext-result.json', wp_json_encode( $out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
