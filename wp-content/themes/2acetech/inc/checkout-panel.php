<?php
/**
 * Checkout as a side panel.
 *
 * Every link to the checkout (the "Checkout" button in the added-to-cart card,
 * "Proceed to checkout" on the cart page, ...) slides the real checkout in from
 * the right, over the page the customer is on, instead of loading a new page.
 * The panel shows the normal checkout page in a frame, so payments, the order
 * button wording and the legal checkbox are exactly the same as on /checkout/.
 *
 * Inside the frame the page drops its header, footer and floating widgets (see
 * checkout-panel.css). The frame is named "ace-panel"; the page uses that name,
 * not "am I in a frame", so the Customizer preview and other embeds are not affected.
 *
 * @package 2acetech
 */

defined( 'ABSPATH' ) || exit;

/**
 * Is the panel available on this request? Not on the checkout itself, and not in the admin.
 *
 * @return bool
 */
function ace_panel_available() {
	return ! is_admin()
		&& function_exists( 'wc_get_checkout_url' )
		&& function_exists( 'is_checkout' )
		&& ! is_checkout();
}

/**
 * Mark pages shown inside the panel before the first paint, so the header never flashes.
 */
function ace_panel_flag() {
	echo "<script>try{if(window.name==='ace-panel'&&window.self!==window.top){document.documentElement.classList.add('ace-in-panel');}}catch(e){}</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput
}
add_action( 'wp_head', 'ace_panel_flag', 1 );

/**
 * Panel styles and script. Also needed inside the frame (the checkout page).
 */
function ace_panel_assets() {
	if ( is_admin() || ! function_exists( 'wc_get_checkout_url' ) ) {
		return;
	}
	wp_enqueue_style( 'ace-panel', ACE_URI . '/assets/css/checkout-panel.css', array( 'ace-main' ), ace_ver( 'assets/css/checkout-panel.css' ) );
	wp_enqueue_script( 'ace-panel', ACE_URI . '/assets/js/checkout-panel.js', array(), ace_ver( 'assets/js/checkout-panel.js' ), array( 'strategy' => 'defer', 'in_footer' => true ) );
	wp_localize_script(
		'ace-panel',
		'aceCheckoutPanel',
		array(
			'url' => wc_get_checkout_url(),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'ace_panel_assets', 30 );

/**
 * The panel itself. Hidden until a checkout link is clicked; the frame loads then.
 */
function ace_panel_markup() {
	if ( ! ace_panel_available() ) {
		return;
	}
	?>
	<div class="cpanel" data-cpanel hidden>
		<div class="cpanel__backdrop" data-cpanel-close></div>
		<aside class="cpanel__sheet" role="dialog" aria-modal="true" aria-labelledby="cpanel-title" tabindex="-1">
			<header class="cpanel__head">
				<h2 class="cpanel__title" id="cpanel-title"><?php echo esc_html__( 'Checkout', 'woocommerce' ); // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch ?></h2>
				<button type="button" class="cpanel__close" data-cpanel-close aria-label="<?php echo esc_attr__( 'Close' ); // phpcs:ignore WordPress.WP.I18n.MissingArgDomain ?>">
					<?php echo ace_icon( 'close', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</button>
			</header>
			<div class="cpanel__body">
				<div class="cpanel__loading" data-cpanel-loading role="status"><span class="cpanel__spinner" aria-hidden="true"></span><span class="screen-reader-text"><?php echo esc_html__( 'Loading&hellip;' ); // phpcs:ignore WordPress.WP.I18n.MissingArgDomain ?></span></div>
				<iframe class="cpanel__frame" name="ace-panel" title="<?php echo esc_attr__( 'Checkout', 'woocommerce' ); // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch ?>" allow="payment *; clipboard-write" data-cpanel-frame></iframe>
			</div>
		</aside>
	</div>
	<?php
}
add_action( 'wp_footer', 'ace_panel_markup', 20 );
