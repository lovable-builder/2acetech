<?php
/**
 * Google Customer Reviews: the opt-in survey on the order confirmation page.
 *
 * Google asks the buyer (a tick box in its own pop-up) whether it may send a short survey after
 * delivery. The answers become real, verified seller ratings in Merchant Center and on Google.
 *
 * - Needs the Customer Reviews programme switched on in Merchant Center (Growth > Manage programs).
 * - Printed only after the visitor allowed Marketing cookies (the same consent as Google Ads).
 * - The Merchant Center ID can be changed in Customizer > Shop details (ace_gcr_merchant_id); empty = off.
 * - The e-mail, order number, country and expected delivery date are sent to Google only when the buyer ticks the box.
 *
 * @package 2acetech
 */

defined( 'ABSPATH' ) || exit;

/** Merchant Center ID (the number after "a=" in the Merchant Center address). */
function ace_gcr_merchant_id() {
	return preg_replace( '/\D/', '', (string) get_theme_mod( 'ace_gcr_merchant_id', '5900437748' ) );
}

/**
 * Expected delivery date: dispatch + transit working days, as promised on the delivery page.
 *
 * @return string Y-m-d.
 */
function ace_gcr_delivery_date() {
	$ship = function_exists( 'ace_merchant_shipping' ) ? ace_merchant_shipping() : array( 'handling_max' => 3, 'transit_max' => 5 );
	$days = (int) $ship['handling_max'] + (int) $ship['transit_max'];
	$t    = time();
	while ( $days > 0 ) {
		$t += DAY_IN_SECONDS;
		if ( (int) gmdate( 'N', $t ) < 6 ) {
			--$days;
		}
	}
	return gmdate( 'Y-m-d', $t );
}

/** Print the opt-in on the order confirmation page. */
function ace_gcr_optin( $order_id ) {
	$merchant = ace_gcr_merchant_id();
	if ( '' === $merchant || ! function_exists( 'ace_consent_allowed' ) || ! ace_consent_allowed( 'marketing' ) ) {
		return;
	}
	$order = wc_get_order( $order_id );
	if ( ! $order || ! is_email( $order->get_billing_email() ) ) {
		return;
	}
	$data = array(
		'merchant_id'             => (int) $merchant,
		'order_id'                => (string) $order->get_order_number(),
		'email'                   => $order->get_billing_email(),
		'delivery_country'        => 'PL',
		'estimated_delivery_date' => ace_gcr_delivery_date(),
	);
	?>
	<script src="https://apis.google.com/js/platform.js?onload=aceRenderOptIn" async defer></script>
	<script>
		window.aceRenderOptIn = function () {
			window.gapi.load('surveyoptin', function () {
				window.gapi.surveyoptin.render(<?php echo wp_json_encode( $data ); ?>);
			});
		};
	</script>
	<?php
}
add_action( 'woocommerce_thankyou', 'ace_gcr_optin', 40 );
