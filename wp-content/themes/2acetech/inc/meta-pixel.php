<?php
/**
 * Meta (Facebook) Pixel.
 *
 * The pixel is a marketing tool, so it waits for the visitor's choice in the cookie banner (inc/consent.php):
 * every script below is printed as type="text/plain" data-ace-consent="marketing" and consent.js starts it
 * only after the visitor accepts marketing cookies. Nothing is loaded from Facebook before that.
 * (For the same reason there is no <noscript> image: it would load without consent.)
 *
 * Events: PageView (all pages), ViewContent (product page), AddToCart (ajax buttons and the product form),
 * InitiateCheckout (checkout page), Purchase (order-received page, once per order).
 *
 * The pixel ID can be changed with the ACE_META_PIXEL_ID constant in wp-config.php or the
 * 'ace_meta_pixel_id' filter; an empty ID switches the pixel off.
 *
 * @package 2acetech
 */

defined( 'ABSPATH' ) || exit;

/**
 * The pixel ID.
 *
 * @return string
 */
function ace_meta_pixel_id() {
	$id = defined( 'ACE_META_PIXEL_ID' ) ? ACE_META_PIXEL_ID : '987315574452864';
	return preg_replace( '/\D/', '', (string) apply_filters( 'ace_meta_pixel_id', $id ) );
}

/**
 * Print a script that waits for marketing consent.
 *
 * @param string $js JavaScript (trusted, built here).
 */
function ace_meta_pixel_script( $js ) {
	echo '<script type="text/plain" data-ace-consent="marketing">' . $js . "</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput
}

/**
 * Base code: the loader and the PageView.
 */
function ace_meta_pixel_base() {
	$id = ace_meta_pixel_id();
	if ( '' === $id || is_admin() || is_customize_preview() || is_feed() ) {
		return;
	}
	$js = "!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init','" . $id . "');fbq('track','PageView');";
	ace_meta_pixel_script( $js );
}
add_action( 'wp_head', 'ace_meta_pixel_base', 3 );

/**
 * Build "fbq('track', name, data);".
 *
 * @param string $event Event name.
 * @param array  $data  Event data.
 * @return string
 */
function ace_meta_pixel_call( $event, $data ) {
	return 'fbq("track",' . wp_json_encode( $event ) . ',' . wp_json_encode( $data ) . ');';
}

/**
 * Event scripts.
 */
function ace_meta_pixel_events() {
	if ( '' === ace_meta_pixel_id() || ! function_exists( 'is_product' ) ) {
		return;
	}
	$cur = get_woocommerce_currency();

	if ( is_product() ) {
		$p = wc_get_product( get_queried_object_id() );
		if ( $p ) {
			ace_meta_pixel_script(
				ace_meta_pixel_call(
					'ViewContent',
					array(
						'content_type' => 'product',
						'content_ids'  => array( (string) $p->get_id() ),
						'content_name' => $p->get_name(),
						'value'        => (float) wc_get_price_to_display( $p ),
						'currency'     => $cur,
					)
				)
			);
		}
	}

	if ( is_checkout() && ! is_order_received_page() && WC()->cart && ! WC()->cart->is_empty() ) {
		$ids = array();
		foreach ( WC()->cart->get_cart() as $item ) {
			$ids[] = (string) ( $item['variation_id'] ? $item['variation_id'] : $item['product_id'] );
		}
		ace_meta_pixel_script(
			ace_meta_pixel_call(
				'InitiateCheckout',
				array(
					'content_type' => 'product',
					'content_ids'  => $ids,
					'num_items'    => WC()->cart->get_cart_contents_count(),
					'value'        => (float) WC()->cart->get_total( 'edit' ),
					'currency'     => $cur,
				)
			)
		);
	}

	// Add to cart from a product form (the page reloads): remembered in the session and sent on the next page.
	if ( WC()->session ) {
		$pending = WC()->session->get( 'ace_fb_atc' );
		if ( $pending ) {
			WC()->session->set( 'ace_fb_atc', null );
			ace_meta_pixel_script( ace_meta_pixel_call( 'AddToCart', $pending ) );
		}
	}

	// Ajax "add to cart" buttons (lists, related products).
	ace_meta_pixel_script(
		'jQuery(function($){$(document.body).on("added_to_cart",function(e,f,h,b){if(!window.fbq||!b)return;var id=b.data("product_id");if(!id)return;fbq("track","AddToCart",{content_type:"product",content_ids:[String(id)],value:parseFloat(b.data("ace_price")||0),currency:' . wp_json_encode( $cur ) . '});});});'
	);
}
add_action( 'wp_footer', 'ace_meta_pixel_events', 5 );

/**
 * Remember an add-to-cart that reloads the page (the product form).
 *
 * @param string $key        Cart key.
 * @param int    $product_id Product.
 * @param int    $qty        Quantity.
 * @param int    $variation  Variation.
 */
function ace_meta_pixel_remember_add( $key, $product_id, $qty, $variation ) {
	if ( wp_doing_ajax() || ! WC()->session ) {
		return;
	}
	$id = $variation ? $variation : $product_id;
	$p  = wc_get_product( $id );
	WC()->session->set(
		'ace_fb_atc',
		array(
			'content_type' => 'product',
			'content_ids'  => array( (string) $id ),
			'value'        => $p ? (float) wc_get_price_to_display( $p ) * max( 1, (int) $qty ) : 0,
			'currency'     => get_woocommerce_currency(),
		)
	);
}
add_action( 'woocommerce_add_to_cart', 'ace_meta_pixel_remember_add', 10, 4 );

/**
 * Purchase, once per order, on the thank-you page.
 *
 * @param int $order_id Order.
 */
function ace_meta_pixel_purchase( $order_id ) {
	$order = wc_get_order( $order_id );
	if ( '' === ace_meta_pixel_id() || ! $order || $order->get_meta( '_ace_fb_purchase_sent' ) ) {
		return;
	}
	$ids = array();
	foreach ( $order->get_items() as $item ) {
		$ids[] = (string) ( $item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id() );
	}
	ace_meta_pixel_script(
		ace_meta_pixel_call(
			'Purchase',
			array(
				'content_type' => 'product',
				'content_ids'  => $ids,
				'num_items'    => $order->get_item_count(),
				'value'        => (float) $order->get_total(),
				'currency'     => $order->get_currency(),
			)
		)
	);
	// Only mark it as sent when the visitor gave consent (otherwise the event never fired).
	if ( ace_consent_allowed( 'marketing' ) ) {
		$order->update_meta_data( '_ace_fb_purchase_sent', 1 );
		$order->save();
	}
}
add_action( 'woocommerce_thankyou', 'ace_meta_pixel_purchase', 20 );

/**
 * Ajax buttons carry the price so the AddToCart event has a value.
 *
 * @param array      $args    Link args.
 * @param WC_Product $product Product.
 * @return array
 */
function ace_meta_pixel_button_price( $args, $product ) {
	$args['attributes']['data-ace_price'] = (float) wc_get_price_to_display( $product );
	return $args;
}
add_filter( 'woocommerce_loop_add_to_cart_args', 'ace_meta_pixel_button_price', 10, 2 );
