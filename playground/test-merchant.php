<?php
// Test: shipping + return policy in the product schema, and the richer Organization. Writes /playground/_merchant-result.json
$out = array();
try {
	$p = new WC_Product_Simple(); $p->set_name( 'Mata do spania' ); $p->set_status( 'publish' ); $p->set_regular_price( '199.99' ); $pid = $p->save();
	$returns = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Zwroty', 'post_name' => 'zwroty-i-odstapienie', 'post_content' => 'x' ) );

	$GLOBALS['product'] = wc_get_product( $pid );
	$sd = new WC_Structured_Data();
	$sd->generate_product_data( $GLOBALS['product'] );
	$data = $sd->get_data(); $prod = null;
	foreach ( $data as $d ) { if ( isset( $d['@type'] ) && 'Product' === $d['@type'] ) { $prod = $d; } }
	$offer = $prod['offers'][0];
	$out['offer type'] = $offer['@type'];
	$out['price + currency still there'] = isset( $offer['price'] ) && isset( $offer['priceCurrency'] );
	$sh = $offer['shippingDetails'];
	$out['shipping: free'] = '0' === $sh['shippingRate']['value'] && 'PLN' === $sh['shippingRate']['currency'];
	$out['shipping: Poland only'] = 'PL' === $sh['shippingDestination']['addressCountry'];
	$out['shipping: dispatch 0-3 days (as on the delivery page)'] = array( $sh['deliveryTime']['handlingTime']['minValue'], $sh['deliveryTime']['handlingTime']['maxValue'] );
	$out['shipping: transit 2-5 days (as on the delivery page)'] = array( $sh['deliveryTime']['transitTime']['minValue'], $sh['deliveryTime']['transitTime']['maxValue'] );
	$rp = $offer['hasMerchantReturnPolicy'];
	$out['returns: 14 days, finite window'] = 14 === $rp['merchantReturnDays'] && false !== strpos( $rp['returnPolicyCategory'], 'MerchantReturnFiniteReturnWindow' );
	$out['returns: customer pays return shipping'] = false !== strpos( $rp['returnFees'], 'ReturnShippingFees' );
	$out['returns: full refund, by mail, PL'] = false !== strpos( $rp['refundType'], 'FullRefund' ) && false !== strpos( $rp['returnMethod'], 'ReturnByMail' ) && 'PL' === $rp['applicableCountry'];
	$out['returns: link to the returns page'] = isset( $rp['merchantReturnLink'] ) && false !== strpos( $rp['merchantReturnLink'], 'zwroty' );
	$out['encodes to valid JSON'] = null !== json_decode( wp_json_encode( $prod ), true );

	// Filters let the numbers change in one place.
	add_filter( 'ace_merchant_shipping', function ( $s ) { $s['handling_max'] = 2; return $s; } );
	$m = ace_offer_policies( array( '@type' => 'Offer' ), $GLOBALS['product'] );
	$out['filter changes dispatch time'] = 2 === $m['shippingDetails']['deliveryTime']['handlingTime']['maxValue'];

	// Organization.
	$org = ace_organization_details( array( '@context' => 'https://schema.org', '@type' => 'Organization', 'name' => 'x', 'url' => 'https://2acetech.pl/' ) );
	$out['org: legal name'] = $org['legalName'] ?? null;
	$out['org: VAT / tax id'] = array( $org['vatID'] ?? null, $org['taxID'] ?? null );
	$out['org: KRS'] = $org['identifier']['value'] ?? null;
	$out['org: address'] = $org['address'] ?? null;
	$out['org: contact point'] = $org['contactPoint'] ?? null;
	$out['org: phone + email'] = array( $org['telephone'] ?? null, $org['email'] ?? null );
} catch ( Throwable $e ) {
	$out['ERROR'] = $e->getMessage() . ' @ ' . basename( $e->getFile() ) . ':' . $e->getLine();
}
file_put_contents( '/playground/_merchant-result.json', wp_json_encode( $out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
