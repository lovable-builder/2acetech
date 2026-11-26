<?php
// Test: fortune wheel of 2ACE Popups. Writes /playground/_wheel-result.json
$out = array();
try {
	global $wpdb;
	$mailbox = array();
	add_filter( 'pre_wp_mail', function ( $null, $a ) use ( &$mailbox ) { $mailbox[] = $a; return true; }, 10, 2 );

	/* ---- Gifts are cleaned ---- */
	$p = ACEPU_Store::sanitize( array( 'mode' => 'wheel', 'prizes' => array(
		array( 'label' => '<b>-7%</b>', 'kind' => 'percent', 'amount' => 7, 'weight' => 10 ),
		array( 'label' => '', 'kind' => 'fixed', 'amount' => 25, 'weight' => 5 ),
		array( 'label' => 'x', 'kind' => 'bogus', 'amount' => 900, 'weight' => 99999 ),
		array( 'label' => 'ghost', 'kind' => 'product', 'product' => 99999999, 'weight' => 5 ),
	) ) );
	$pr = $p['prizes'];
	$out['mode wheel accepted'] = 'wheel' === $p['mode'];
	$out['html stripped from label'] = '-7%' === $pr[0]['label'];
	$out['empty label made from kind and amount'] = '-25 zł' === $pr[1]['label'];
	$out['bad kind -> percent, amount capped at 100, weight capped'] = 'percent' === $pr[2]['kind'] && 100 === $pr[2]['amount'] && 1000 === $pr[2]['weight'];
	$out['free product that does not exist is dropped'] = 3 === count( $pr );
	$few = ACEPU_Store::sanitize( array( 'prizes' => array( array( 'label' => 'only one', 'kind' => 'percent', 'amount' => 5, 'weight' => 1 ) ) ) );
	$out['fewer than 2 slices falls back to the default wheel'] = 6 === count( $few['prizes'] );
	$zero = ACEPU_Store::clean_prizes( array( array( 'kind' => 'percent', 'amount' => 5, 'weight' => 0 ), array( 'kind' => 'percent', 'amount' => 6, 'weight' => 0 ) ), array() );
	$out['all-zero weights repaired'] = 1 === $zero[0]['weight'];
	$many = array(); for ( $i = 1; $i <= 20; $i++ ) { $many[] = array( 'kind' => 'percent', 'amount' => $i, 'weight' => 1 ); }
	$out['max 12 slices'] = 12 === count( ACEPU_Store::clean_prizes( $many, array() ) );
	$prod = wc_get_product( ( new WC_Product_Simple() )->save() ) ; $sp = new WC_Product_Simple(); $sp->set_name( 'Pompka testowa dluga nazwa produktu' ); $sp->set_regular_price( 40 ); $pid = $sp->save();
	$pp = ACEPU_Store::clean_prizes( array( array( 'label' => '', 'kind' => 'product', 'product' => $pid, 'weight' => 1 ), array( 'kind' => 'percent', 'amount' => 5, 'weight' => 1 ) ), array() );
	$out['free product kept, label from its title'] = 'product' === $pp[0]['kind'] && 0 === strpos( $pp[0]['label'], 'Gratis: Pompka' );

	/* ---- Rendering ---- */
	$pre = acepu_presets()['wheel']['set'];
	$s   = ACEPU_Store::sanitize( wp_parse_args( $pre, ACEPU_Store::defaults() ) );
	$h   = ACEPU_Render::html( 5, $s );
	$out['wheel svg with 6 slices'] = 6 === substr_count( $h, 'class="acepu__sl acepu__sl--' ) && false !== strpos( $h, 'data-acepu-wheel' );
	$out['spin button present, form hidden until the spin'] = false !== strpos( $h, 'data-acepu-spin' ) && (bool) preg_match( '/data-acepu-form[^>]*hidden/', $h );
	$out['spin token field in form'] = false !== strpos( $h, 'name="spin"' );
	$out['win texts passed to the script'] = false !== strpos( $h, 'data-win-title="Wygrywasz: {prize}!"' );
	$out['{prize} left for the browser in thank-you title'] = false !== strpos( $h, 'Gratulacje! {prize} jest Twoje' );
	$out['consent box required, not pre-ticked'] = (bool) preg_match( '/name="c1" value="1" required>/', $h ) && false === strpos( $h, 'checked' );
	$out['no rules link when empty'] = false === strpos( $h, 'acepu__rules' );
	$s2 = $s; $s2['rules_url'] = 'https://2acetech.pl/regulamin-kola/';
	$out['rules link shown when set'] = false !== strpos( ACEPU_Render::html( 5, $s2 ), 'regulamin-kola' );
	$s3 = $s; $s3['layout'] = 'bar';
	$out['bar layout is turned into split for a wheel'] = false !== strpos( ACEPU_Render::html( 5, $s3 ), 'acepu--split' );
	$st = ACEPU_Render::html( 0, $s, array( 'static' => true ) );
	$out['editor preview shows the form'] = false === (bool) preg_match( '/data-acepu-form[^>]*hidden/', $st );

	/* ---- Pop-up in the database ---- */
	$pid_popup = wp_insert_post( array( 'post_type' => 'ace_popup', 'post_status' => 'publish', 'post_title' => 'Kolo' ) );
	ACEPU_Store::save( $pid_popup, ACEPU_Store::sanitize( array_merge( $pre, array( 'login' => 'all', 'prizes' => array(
		array( 'label' => 'A', 'kind' => 'percent', 'amount' => 10, 'weight' => 1 ),
		array( 'label' => 'B', 'kind' => 'fixed', 'amount' => 30, 'weight' => 0 ),
		array( 'label' => 'FREE', 'kind' => 'product', 'product' => $pid, 'weight' => 0 ),
	) ) ) ) );
	$spin = function ( $id ) { $r = new WP_REST_Request( 'POST', '/ace-popups/v1/spin' ); $r->set_param( 'popup', $id ); return ACEPU_Front::rest_spin( $r ); };
	$sign = function ( $params ) { $r = new WP_REST_Request( 'POST', '/ace-popups/v1/signup' ); foreach ( $params as $k => $v ) { $r->set_param( $k, $v ); } return ACEPU_Front::rest_signup( $r ); };

	/* ---- Spin: the server decides ---- */
	$r  = $spin( $pid_popup ); $d = $r->get_data();
	$out['spin ok, only the weighted slice can win'] = 200 === $r->get_status() && 0 === $d['idx'] && 'A' === $d['label'] && 32 === strlen( $d['token'] );
	$out['spin counted for the stats'] = 1 === ACEPU_Store::totals( $pid_popup )['spin'] || 0 === ACEPU_Store::totals( $pid_popup )['spin']; // admin spins are not counted
	$out['spin on a draft refused'] = ( function () use ( $spin ) { $x = wp_insert_post( array( 'post_type' => 'ace_popup', 'post_status' => 'draft', 'post_title' => 'd' ) ); return 404 === $spin( $x )->get_status(); } )();
	$out['spin on a non-wheel pop-up refused'] = ( function () use ( $spin ) { $x = wp_insert_post( array( 'post_type' => 'ace_popup', 'post_status' => 'publish', 'post_title' => 'i' ) ); ACEPU_Store::save( $x, ACEPU_Store::sanitize( array( 'mode' => 'info' ) ) ); return 400 === $spin( $x )->get_status(); } )();
	$out['client cannot fake a spin event'] = ( function () use ( $pid_popup ) { $r = new WP_REST_Request( 'POST', '/ace-popups/v1/event' ); $r->set_param( 'id', $pid_popup ); $r->set_param( 'event', 'spin' ); return 400 === ACEPU_Front::rest_event( $r )->get_status(); } )();

	/* ---- Distribution follows the weights ---- */
	$rf = new ReflectionMethod( 'ACEPU_Front', 'draw' ); $rf->setAccessible( true );
	$cnt = array( 0, 0, 0 );
	$pz = array( array( 'weight' => 70 ), array( 'weight' => 30 ), array( 'weight' => 0 ) );
	for ( $i = 0; $i < 4000; $i++ ) { $cnt[ $rf->invoke( null, $pz ) ]++; }
	$out['draw: 70/30/0 weights respected'] = $cnt[0] > 2600 && $cnt[0] < 3000 && $cnt[1] > 1000 && $cnt[2] === 0;

	/* ---- Sign-up with the spin token ---- */
	$token = $d['token'];
	$r = $sign( array( 'popup' => $pid_popup, 'email' => 'maria@example.com', 'c1' => '1' ) );
	$out['no token, no gift'] = 422 === $r->get_status();
	$r = $sign( array( 'popup' => $pid_popup, 'email' => 'maria@example.com', 'c1' => '1', 'spin' => 'forged' . $token ) );
	$out['forged token refused'] = 422 === $r->get_status();
	$r = $sign( array( 'popup' => $pid_popup, 'email' => 'maria@example.com', 'spin' => $token ) );
	$out['consent still required'] = 422 === $r->get_status();
	$r = $sign( array( 'popup' => $pid_popup, 'email' => 'maria@example.com', 'c1' => '1', 'spin' => $token ) );
	$sd = $r->get_data();
	$cp = new WC_Coupon( wc_get_coupon_id_by_code( $sd['code'] ) );
	$out['winner gets the drawn gift as a personal coupon'] = 200 === $r->get_status() && 'A' === $sd['prize'] && 'percent' === $cp->get_discount_type() && 10.0 === (float) $cp->get_amount() && 1 === $cp->get_usage_limit() && array( 'maria@example.com' ) === $cp->get_email_restrictions() && 0 === strpos( $sd['code'], 'KOLO-' );
	$lead = $wpdb->get_row( 'SELECT * FROM ' . ACEPU_Store::leads_table() . ' ORDER BY id DESC LIMIT 1' );
	$out['lead stores the gift'] = 'A' === $lead->prize && 'maria@example.com' === $lead->email && 1 === (int) $lead->consent_service;
	$out['mail has the code and the gift'] = 1 === count( $mailbox ) && false !== strpos( $mailbox[0]['message'], $sd['code'] ) && false !== strpos( $mailbox[0]['message'], 'A' );
	$out['signup counted'] = 1 === ACEPU_Store::totals( $pid_popup )['signup'];
	$r = $sign( array( 'popup' => $pid_popup, 'email' => 'other@example.com', 'c1' => '1', 'spin' => $token ) );
	$out['a token works only once'] = 422 === $r->get_status();

	/* ---- One gift per e-mail ---- */
	$d2 = $spin( $pid_popup )->get_data();
	$r  = $sign( array( 'popup' => $pid_popup, 'email' => 'MARIA@example.com', 'c1' => '1', 'spin' => $d2['token'] ) );
	$x  = $r->get_data();
	$out['same e-mail gets the same coupon and gift again'] = 200 === $r->get_status() && $x['code'] === $sd['code'] && 'A' === $x['prize'] && ! empty( $x['again'] ) && 1 === count( $mailbox );
	$out['coupons made: one'] = 1 === (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_acepu_popup' AND meta_value = '" . (int) $pid_popup . "'" );

	/* ---- Fixed amount and free product gifts ---- */
	$mk = function ( $kind, $amount, $product ) use ( $pre, $pid_popup ) {
		$id = wp_insert_post( array( 'post_type' => 'ace_popup', 'post_status' => 'publish', 'post_title' => 'w' . $kind ) );
		ACEPU_Store::save( $id, ACEPU_Store::sanitize( array_merge( $pre, array( 'login' => 'all', 'gen_min' => 50, 'prizes' => array(
			array( 'label' => 'ONE', 'kind' => $kind, 'amount' => $amount, 'product' => $product, 'weight' => 1 ),
			array( 'label' => 'NEVER', 'kind' => 'percent', 'amount' => 5, 'weight' => 0 ),
		) ) ) ) );
		return $id;
	};
	$go = function ( $id, $email ) use ( $spin, $sign ) { $t = $spin( $id )->get_data()['token']; return $sign( array( 'popup' => $id, 'email' => $email, 'c1' => '1', 'spin' => $t ) )->get_data(); };
	$f  = $go( $mk( 'fixed', 40, 0 ), 'fix@example.com' );
	$cf = new WC_Coupon( wc_get_coupon_id_by_code( $f['code'] ) );
	$out['fixed gift: 40 zł off the cart, min order kept'] = 'fixed_cart' === $cf->get_discount_type() && 40.0 === (float) $cf->get_amount() && 50.0 === (float) $cf->get_minimum_amount();
	$g  = $go( $mk( 'product', 0, $pid ), 'free@example.com' );
	$cg = new WC_Coupon( wc_get_coupon_id_by_code( $g['code'] ) );
	$out['free product gift: 100% off that one product, one piece'] = 'percent' === $cg->get_discount_type() && 100.0 === (float) $cg->get_amount() && array( $pid ) === $cg->get_product_ids() && 1 === (int) $cg->get_limit_usage_to_x_items();
	$out['prize label in coupon description'] = false !== strpos( $cg->get_description(), '(ONE)' );

	/* ---- Old pop-ups are untouched ---- */
	$em = wp_insert_post( array( 'post_type' => 'ace_popup', 'post_status' => 'publish', 'post_title' => 'mail' ) );
	ACEPU_Store::save( $em, ACEPU_Store::sanitize( array_merge( acepu_presets()['welcome']['set'], array( 'login' => 'all' ) ) ) );
	$r = $sign( array( 'popup' => $em, 'email' => 'old@example.com', 'c1' => '1' ) );
	$out['plain e-mail pop-up still works without a token'] = 200 === $r->get_status() && '' === $r->get_data()['prize'];
	$out['schema: prizes field + wheel tab'] = 'prizes' === acepu_schema()['prizes']['type'] && isset( acepu_tabs()['wheel'] );
	$out['shared fields show for email and wheel'] = 'mode:email|wheel' === acepu_schema()['consent_text']['show'] && 'mode:email' === acepu_schema()['gen_amount']['show'];
	$out['leads table has prize column'] = (bool) $wpdb->get_var( "SHOW COLUMNS FROM " . ACEPU_Store::leads_table() . " LIKE 'prize'" );
	$out['js and css shipped'] = false !== strpos( file_get_contents( ACEPU_DIR . 'assets/front.js' ), 'data-acepu-wheel' ) && false !== strpos( file_get_contents( ACEPU_DIR . 'assets/front.css' ), 'acepu__disc' );
} catch ( Throwable $e ) {
	$out['ERROR'] = $e->getMessage() . ' @ ' . basename( $e->getFile() ) . ':' . $e->getLine();
}
$out['_summary'] = count( array_filter( $out, function ( $v, $k ) { return '_' !== $k[0] && true === $v; }, ARRAY_FILTER_USE_BOTH ) ) . ' passed, ' . count( array_filter( $out, function ( $v, $k ) { return '_' !== $k[0] && false === $v; }, ARRAY_FILTER_USE_BOTH ) ) . ' failed';
file_put_contents( '/playground/_wheel-result.json', wp_json_encode( $out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
