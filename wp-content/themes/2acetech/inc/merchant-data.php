<?php
/**
 * Business and policy data for Google (Merchant Center, Shopping, search).
 *
 * Google checks that a shop is open about who it is and what it promises. These blocks give it the
 * same facts the shop already states on its pages, in the machine-readable form it reads:
 *
 *  - Organization (home page): legal name, address, phone, e-mail, tax and register numbers, contact point.
 *  - Product offers: shipping (free, Poland only, dispatch time, transit time) and the return policy
 *    (14 days, customer pays the return shipping, full refund, link to the returns page).
 *
 * The numbers are the ones in the published "Dostawa i płatności" and "Zwroty" pages. If those pages
 * change, change them here too (or use the filters ace_merchant_shipping / ace_merchant_returns).
 *
 * @package 2acetech
 */

defined( 'ABSPATH' ) || exit;

/** Shipping facts, as on the delivery page. */
function ace_merchant_shipping() {
	return apply_filters(
		'ace_merchant_shipping',
		array(
			'country'      => 'PL',
			'currency'     => 'PLN',
			'rate'         => 0,    // Free delivery.
			'handling_min' => 0,    // Working days to prepare and dispatch ...
			'handling_max' => 3,    // ... "do 3 dni roboczych".
			'transit_min'  => 2,    // Working days in transport ...
			'transit_max'  => 5,    // ... "2-5 dni roboczych".
		)
	);
}

/** Return facts, as on the returns page. */
function ace_merchant_returns() {
	return apply_filters(
		'ace_merchant_returns',
		array(
			'country' => 'PL',
			'days'    => 14,
		)
	);
}

/**
 * Add shipping details and the return policy to every product offer.
 *
 * @param array      $markup  The offer (Offer or AggregateOffer).
 * @param WC_Product $product Product.
 * @return array
 */
function ace_offer_policies( $markup, $product ) {
	$ship = ace_merchant_shipping();
	$ret  = ace_merchant_returns();

	$markup['shippingDetails'] = array(
		'@type'               => 'OfferShippingDetails',
		'shippingRate'        => array( '@type' => 'MonetaryAmount', 'value' => (string) $ship['rate'], 'currency' => $ship['currency'] ),
		'shippingDestination' => array( '@type' => 'DefinedRegion', 'addressCountry' => $ship['country'] ),
		'deliveryTime'        => array(
			'@type'        => 'ShippingDeliveryTime',
			'handlingTime' => array( '@type' => 'QuantitativeValue', 'minValue' => (int) $ship['handling_min'], 'maxValue' => (int) $ship['handling_max'], 'unitCode' => 'DAY' ),
			'transitTime'  => array( '@type' => 'QuantitativeValue', 'minValue' => (int) $ship['transit_min'], 'maxValue' => (int) $ship['transit_max'], 'unitCode' => 'DAY' ),
		),
	);

	$policy = array(
		'@type'                => 'MerchantReturnPolicy',
		'applicableCountry'    => $ret['country'],
		'returnPolicyCategory' => 'https://schema.org/MerchantReturnFiniteReturnWindow',
		'merchantReturnDays'   => (int) $ret['days'],
		'returnMethod'         => 'https://schema.org/ReturnByMail',
		'returnFees'           => 'https://schema.org/ReturnShippingFees', // The customer pays the return shipping.
		'refundType'           => 'https://schema.org/FullRefund',
	);
	$page = function_exists( 'ace_legal_page_id' ) ? ace_legal_page_id( 'returns' ) : 0;
	if ( $page && 'publish' === get_post_status( $page ) ) {
		$policy['merchantReturnLink'] = get_permalink( $page );
	}
	$markup['hasMerchantReturnPolicy'] = $policy;

	return $markup;
}
add_filter( 'woocommerce_structured_data_product_offer', 'ace_offer_policies', 20, 2 );

/**
 * Richer Organization data on the home page: who the seller is and how to reach it.
 *
 * @param array $org Organization markup from ace_share_meta().
 * @return array
 */
function ace_organization_details( $org ) {
	$seller  = trim( (string) get_theme_mod( 'ace_seller', '' ) );
	$ids     = (string) get_theme_mod( 'ace_seller_id', '' );
	$address = trim( (string) get_theme_mod( 'ace_address', '' ) );
	$phone   = trim( (string) get_theme_mod( 'ace_phone', '' ) );
	$email   = trim( (string) get_theme_mod( 'ace_email', 'info@2acetech.pl' ) );

	if ( '' !== $seller ) {
		$org['legalName'] = $seller;
	}
	if ( preg_match( '/NIP\s*(\d{10})/', $ids, $m ) ) {
		$org['vatID'] = 'PL' . $m[1];
		$org['taxID'] = $m[1];
	}
	if ( preg_match( '/KRS\s*(\d{10})/', $ids, $m ) ) {
		$org['identifier'] = array( '@type' => 'PropertyValue', 'propertyID' => 'KRS', 'value' => $m[1] );
	}
	// "ul. Ostrobramska 101A lok. 301, 04-041 Warszawa"
	if ( preg_match( '/^(.*?),\s*(\d{2}-\d{3})\s+(.+)$/u', $address, $m ) ) {
		$org['address'] = array(
			'@type'           => 'PostalAddress',
			'streetAddress'   => trim( $m[1] ),
			'postalCode'      => $m[2],
			'addressLocality' => trim( $m[3] ),
			'addressCountry'  => 'PL',
		);
	}
	if ( '' !== $email ) {
		$org['email'] = $email;
	}
	if ( '' !== $phone ) {
		$org['telephone'] = $phone;
	}
	if ( '' !== $phone || '' !== $email ) {
		$point = array( '@type' => 'ContactPoint', 'contactType' => 'customer service', 'areaServed' => 'PL', 'availableLanguage' => 'pl' );
		if ( '' !== $phone ) {
			$point['telephone'] = $phone;
		}
		if ( '' !== $email ) {
			$point['email'] = $email;
		}
		$org['contactPoint'] = $point;
	}
	return $org;
}
add_filter( 'ace_organization_schema', 'ace_organization_details' );
