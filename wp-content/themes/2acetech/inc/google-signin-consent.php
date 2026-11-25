<?php
/**
 * Site Kit "Sign in with Google": load only after the visitor accepts Marketing cookies.
 *
 * Site Kit prints Google's sign-in script (accounts.google.com/gsi/client) and its own sign-in script
 * in the footer of every page for logged-out visitors, which connects every visitor to Google (and shows
 * the One Tap pop-up) before they have chosen anything in the cookie banner. These two scripts are
 * re-marked as type="text/plain" data-ace-consent="marketing" so assets/js/consent.js starts them only
 * after consent (right away when the visitor accepts, on every later page for returning visitors).
 * The page itself is identical for everybody, so a page cache cannot mix up choices.
 *
 * Until then the Google button on My Account does not appear; with consent it works as before.
 *
 * @package 2acetech
 */

defined( 'ABSPATH' ) || exit;

/** Does the footer contain scripts that must wait? Called on the finished footer output. */
function ace_siwg_defer_scripts( $html ) {
	// Google's own library.
	$html = preg_replace(
		'#<script(\s+)src=(["\'])(https://accounts\.google\.com/gsi/client)\2(\s*)>#i',
		'<script$1type="text/plain" data-ace-consent="marketing" src=$2$3$2$4>',
		$html
	);
	// Site Kit's sign-in script (carries its settings in data-siwg-config).
	$html = preg_replace(
		'#<script(?![^>]*data-ace-consent)(\s[^>]*?\bdata-siwg-config=)#i',
		'<script type="text/plain" data-ace-consent="marketing"$1',
		$html
	);
	return $html;
}

/** Start catching the footer output. */
function ace_siwg_buffer_start() {
	if ( function_exists( 'ace_consent_enabled' ) && ace_consent_enabled() ) {
		ob_start( 'ace_siwg_defer_scripts' );
		$GLOBALS['ace_siwg_buffer_level'] = ob_get_level();
	}
}
add_action( 'wp_footer', 'ace_siwg_buffer_start', 0 );

/** Stop catching (only the buffer started above). */
function ace_siwg_buffer_end() {
	if ( ! empty( $GLOBALS['ace_siwg_buffer_level'] ) && ob_get_level() === $GLOBALS['ace_siwg_buffer_level'] ) {
		ob_end_flush();
	}
	unset( $GLOBALS['ace_siwg_buffer_level'] );
}
add_action( 'wp_footer', 'ace_siwg_buffer_end', PHP_INT_MAX );
