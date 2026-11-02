/**
 * Checkout: legally compliant order button label.
 *
 * EU consumer law (Directive 2011/83/EU, art. 8(2)) requires the order button
 * to state clearly that the order carries an obligation to pay. WooCommerce's
 * default "Place Order" does not, so the block checkout label is replaced here
 * through WooCommerce's own checkout filters API.
 */
(function () {
	'use strict';

	var label = (window.aceCheckout && window.aceCheckout.placeOrder) || 'Order with obligation to pay';

	if (window.wc && window.wc.blocksCheckout && window.wc.blocksCheckout.registerCheckoutFilters) {
		window.wc.blocksCheckout.registerCheckoutFilters('2acetech', {
			placeOrderButtonLabel: function () {
				return label;
			},
		});
	}
})();
