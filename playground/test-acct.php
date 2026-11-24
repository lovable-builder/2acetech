<?php
// Test: login/register switch on My Account. Writes /playground/_acct-result.json
$out = array();
try {
	update_option( 'woocommerce_enable_myaccount_registration', 'yes' );
	ob_start(); do_action( 'woocommerce_login_form_end' ); $login = ob_get_clean();
	ob_start(); do_action( 'woocommerce_register_form_end' ); $reg = ob_get_clean();
	$out['login form: link to register'] = false !== strpos( $login, 'data-acct-switch="register"' ) && false !== strpos( $login, 'Register' ) || false !== strpos( $login, 'Zarejestruj' );
	$out['register form: link to login'] = false !== strpos( $reg, 'data-acct-switch="login"' ) && false !== strpos( $reg, 'Log in' ) || false !== strpos( $reg, 'Zaloguj' );
	update_option( 'woocommerce_enable_myaccount_registration', 'no' );
	ob_start(); do_action( 'woocommerce_login_form_end' ); $login2 = ob_get_clean();
	$out['registration off: no link to a form that does not exist'] = '' === trim( $login2 );
	$out['not active outside the account page'] = false === ace_account_switch_active();
	$po = file_get_contents( get_theme_file_path( 'languages/pl_PL.po' ) );
	$out['Polish texts present in pl_PL.po'] = false !== strpos( $po, 'Nie masz jeszcze konta?' ) && false !== strpos( $po, 'Masz już konto?' );
	$out['js + css exist'] = file_exists( get_theme_file_path( 'assets/js/account.js' ) ) && false !== strpos( file_get_contents( get_theme_file_path( 'assets/css/flow.css' ) ), 'html.acct-toggle.acct-register' );
	ob_start(); ace_account_switch_head(); $out['no head script when inactive'] = '' === ob_get_clean();
} catch ( Throwable $e ) {
	$out['ERROR'] = $e->getMessage() . ' @ ' . basename( $e->getFile() ) . ':' . $e->getLine();
}
file_put_contents( '/playground/_acct-result.json', wp_json_encode( $out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
