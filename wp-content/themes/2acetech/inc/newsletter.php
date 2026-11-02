<?php
/**
 * Built-in newsletter sign-up.
 *
 * Emails the site admin with each address. Swap for a mailing-list plugin by
 * setting a shortcode under Customize > Store details.
 *
 * @package 2acetech
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handle the form post.
 */
function ace_handle_newsletter() {
	$back = wp_get_referer() ? wp_get_referer() : home_url( '/' );
	$back = remove_query_arg( 'subscribed', $back );

	if ( ! isset( $_POST['ace_nl_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ace_nl_nonce'] ) ), 'ace_newsletter' ) ) {
		wp_safe_redirect( add_query_arg( 'subscribed', 'error', $back ) . '#newsletter' );
		exit;
	}

	// Honeypot: real visitors never fill this in.
	if ( ! empty( $_POST['ace_website'] ) ) {
		wp_safe_redirect( add_query_arg( 'subscribed', 'ok', $back ) . '#newsletter' );
		exit;
	}

	$email = isset( $_POST['ace_email'] ) ? sanitize_email( wp_unslash( $_POST['ace_email'] ) ) : '';
	if ( ! is_email( $email ) ) {
		wp_safe_redirect( add_query_arg( 'subscribed', 'invalid', $back ) . '#newsletter' );
		exit;
	}

	wp_mail(
		get_option( 'admin_email' ),
		/* translators: %s: site name. */
		sprintf( __( '[%s] New newsletter sign-up', '2acetech' ), get_bloginfo( 'name' ) ),
		/* translators: %s: email address. */
		sprintf( __( 'New newsletter sign-up: %s', '2acetech' ), $email )
	);

	wp_safe_redirect( add_query_arg( 'subscribed', 'ok', $back ) . '#newsletter' );
	exit;
}
add_action( 'admin_post_nopriv_ace_newsletter', 'ace_handle_newsletter' );
add_action( 'admin_post_ace_newsletter', 'ace_handle_newsletter' );
