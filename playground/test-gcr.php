<?php
// Test: Google Customer Reviews opt-in on the order confirmation page. Writes /playground/_gcr-result.json
$out = array();
try {
	$p = new WC_Product_Simple(); $p->set_name( 'Mata' ); $p->set_regular_price( '100' ); $p->set_status( 'publish' ); $pid = $p->save();
	$o = wc_create_order(); $o->add_product( wc_get_product( $pid ), 1 ); $o->set_billing_email( 'kupujacy@example.com' ); $o->calculate_totals(); $o->save();
	$show = function () use ( $o ) { ob_start(); ace_gcr_optin( $o->get_id() ); return ob_get_clean(); };

	unset( $_COOKIE['ace_consent'] );
	$out['no consent: nothing printed'] = '' === trim( $show() );

	$_COOKIE['ace_consent'] = ACE_CONSENT_VERSION . '.0.1'; // Marketing allowed.
	$html = $show();
	$out['marketing consent: script printed'] = false !== strpos( $html, 'apis.google.com/js/platform.js' );
	preg_match( '/render\((\{.*?\})\)/s', $html, $m ); $d = json_decode( $m[1] ?? '{}', true );
	$out['data'] = $d;
	$out['merchant id = account id'] = 5900437748 === ( $d['merchant_id'] ?? 0 );
	$out['country PL, order number, e-mail'] = 'PL' === $d['delivery_country'] && (string) $o->get_order_number() === $d['order_id'] && 'kupujacy@example.com' === $d['email'];
	$days = ( strtotime( $d['estimated_delivery_date'] ) - strtotime( gmdate( 'Y-m-d' ) ) ) / DAY_IN_SECONDS;
	$out['delivery date 8 working days ahead (11-12 calendar days at most)'] = array( $d['estimated_delivery_date'], $days, $days >= 8 && $days <= 12 );

	$_COOKIE['ace_consent'] = ACE_CONSENT_VERSION . '.1.0'; // Only analytics.
	$out['analytics only: nothing printed'] = '' === trim( $show() );

	$_COOKIE['ace_consent'] = ACE_CONSENT_VERSION . '.0.1';
	set_theme_mod( 'ace_gcr_merchant_id', '' );
	$out['empty merchant id: off'] = '' === trim( $show() );
	remove_theme_mod( 'ace_gcr_merchant_id' );

	$o->set_billing_email( '' ); $o->save();
	$out['no e-mail: nothing printed'] = '' === trim( $show() );
	$out['hooked on the thank-you page'] = false !== has_action( 'woocommerce_thankyou', 'ace_gcr_optin' );
} catch ( Throwable $e ) {
	$out['ERROR'] = $e->getMessage() . ' @ ' . basename( $e->getFile() ) . ':' . $e->getLine();
}
file_put_contents( '/playground/_gcr-result.json', wp_json_encode( $out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
