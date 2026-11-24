<?php
/**
 * My Account, logged out: one form at a time.
 *
 * Login is shown first with "Nie masz jeszcze konta? Zarejestruj się" under it; the link swaps in the
 * register form, which has "Masz już konto? Zaloguj się" to go back. Works with the link #register
 * (e.g. from an e-mail or a button). Without JavaScript both forms stay visible next to each other.
 * Only active while "Allow customers to create an account on the My account page" is on.
 *
 * @package 2acetech
 */

defined( 'ABSPATH' ) || exit;

/** Is this the logged-out account page with registration switched on? */
function ace_account_switch_active() {
	return function_exists( 'is_account_page' ) && is_account_page() && ! is_user_logged_in()
		&& 'yes' === get_option( 'woocommerce_enable_myaccount_registration' );
}

/** Marks the page early, so there is no flash of both forms. */
function ace_account_switch_head() {
	if ( ! ace_account_switch_active() ) {
		return;
	}
	echo "<script>document.documentElement.classList.add('acct-toggle');if(location.hash==='#register'){document.documentElement.classList.add('acct-register');}</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput
}
add_action( 'wp_head', 'ace_account_switch_head', 1 );

function ace_account_switch_assets() {
	if ( ace_account_switch_active() ) {
		wp_enqueue_script( 'ace-account', ACE_URI . '/assets/js/account.js', array(), ace_ver( 'assets/js/account.js' ), array( 'strategy' => 'defer', 'in_footer' => true ) );
	}
}
add_action( 'wp_enqueue_scripts', 'ace_account_switch_assets', 30 );

/** Under the login form. */
function ace_account_switch_to_register() {
	if ( 'yes' !== get_option( 'woocommerce_enable_myaccount_registration' ) ) {
		return;
	}
	printf(
		'<p class="acct-switch">%1$s <a href="#register" data-acct-switch="register">%2$s</a></p>',
		esc_html__( "Don't have an account yet?", '2acetech' ),
		esc_html__( 'Register', '2acetech' )
	);
}
add_action( 'woocommerce_login_form_end', 'ace_account_switch_to_register' );

/** Under the register form. */
function ace_account_switch_to_login() {
	printf(
		'<p class="acct-switch">%1$s <a href="#login" data-acct-switch="login">%2$s</a></p>',
		esc_html__( 'Already have an account?', '2acetech' ),
		esc_html__( 'Log in', '2acetech' )
	);
}
add_action( 'woocommerce_register_form_end', 'ace_account_switch_to_login' );
