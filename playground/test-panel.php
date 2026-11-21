<?php
// Test: the checkout side panel markup is on normal pages, not on the checkout, and the frame flag is printed.
// Writes /playground/_panel-result.json
$out = array();
try {
	$home = home_url( '/' );
	$render = function ( $url ) {
		$GLOBALS['wp_query'] = new WP_Query( array( 'page_id' => url_to_postid( $url ) ) );
		ob_start(); ace_panel_markup(); return ob_get_clean();
	};
	// A normal page (not checkout).
	$about = wp_insert_post( array( 'post_type' => 'page', 'post_title' => 'Plain', 'post_status' => 'publish', 'post_content' => 'x' ) );
	$GLOBALS['wp_query'] = new WP_Query( array( 'page_id' => $about, 'post_type' => 'page' ) );
	$GLOBALS['wp_the_query'] = $GLOBALS['wp_query'];
	$out['available on a normal page'] = ace_panel_available();
	ob_start(); ace_panel_markup(); $html = ob_get_clean();
	$out['markup has dialog'] = false !== strpos( $html, 'role="dialog"' ) && false !== strpos( $html, 'data-cpanel-frame' );
	$out['frame is named ace-panel'] = false !== strpos( $html, 'name="ace-panel"' );
	$out['frame allows payment'] = false !== strpos( $html, 'allow="payment *' );
	$out['no frame src until opened'] = false === strpos( $html, ' src=' );
	$out['close button labelled'] = 1 === preg_match( '/aria-label="[^"]+"/', $html );

	// The checkout page itself.
	$co = (int) get_option( 'woocommerce_checkout_page_id' );
	if ( ! $co ) { $co = wp_insert_post( array( 'post_type' => 'page', 'post_title' => 'Checkout', 'post_status' => 'publish', 'post_content' => '[woocommerce_checkout]' ) ); update_option( 'woocommerce_checkout_page_id', $co ); }
	$GLOBALS['wp_query'] = new WP_Query( array( 'page_id' => $co, 'post_type' => 'page' ) );
	$GLOBALS['wp_the_query'] = $GLOBALS['wp_query'];
	add_filter( 'woocommerce_is_checkout', '__return_true' ); // This request IS the checkout.
	$out['is_checkout() true here'] = is_checkout();
	$out['not available on checkout'] = ! ace_panel_available();
	ob_start(); ace_panel_markup(); $out['no markup on checkout'] = '' === trim( ob_get_clean() );

	ob_start(); ace_panel_flag(); $flag = ob_get_clean();
	$out['flag keyed on frame name'] = false !== strpos( $flag, "window.name==='ace-panel'" ) && false !== strpos( $flag, 'ace-in-panel' );

	// Assets registered with a localised checkout URL.
	do_action( 'wp_enqueue_scripts' );
	$out['css enqueued'] = wp_style_is( 'ace-panel', 'enqueued' );
	$out['js enqueued'] = wp_script_is( 'ace-panel', 'enqueued' );
	$data = wp_scripts()->get_data( 'ace-panel', 'data' );
	$out['js gets checkout url'] = is_string( $data ) && false !== strpos( $data, 'aceCheckoutPanel' ) && false !== strpos( $data, 'url' );
} catch ( Throwable $e ) {
	$out['ERROR'] = $e->getMessage() . ' @ ' . basename( $e->getFile() ) . ':' . $e->getLine();
}
file_put_contents( '/playground/_panel-result.json', wp_json_encode( $out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) );
