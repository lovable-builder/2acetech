<?php
/**
 * Contact form handler for the Contact page template.
 *
 * Emails the store (Customize > Store details > Email, else the site admin)
 * with the visitor as Reply-To. Nonce, honeypot and a per-IP rate limit keep
 * spam down; no data is stored in the database.
 *
 * @package 2acetech
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handle the contact form post.
 */
function ace_handle_contact() {
	$back = wp_get_referer() ? wp_get_referer() : home_url( '/' );
	$back = remove_query_arg( 'sent', $back );
	$done = static function ( $status ) use ( $back ) {
		wp_safe_redirect( add_query_arg( 'sent', $status, $back ) . '#contact-form' );
		exit;
	};

	if ( ! isset( $_POST['ace_contact_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ace_contact_nonce'] ) ), 'ace_contact' ) ) {
		$done( 'error' );
	}

	if ( ! empty( $_POST['ace_website'] ) ) { // Honeypot.
		$done( 'ok' );
	}

	$ip_key = 'ace_contact_' . md5( isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'x' );
	$count  = (int) get_transient( $ip_key );
	if ( $count >= 5 ) {
		$done( 'limit' );
	}

	$name    = isset( $_POST['ace_name'] ) ? sanitize_text_field( wp_unslash( $_POST['ace_name'] ) ) : '';
	$email   = isset( $_POST['ace_email'] ) ? sanitize_email( wp_unslash( $_POST['ace_email'] ) ) : '';
	$order   = isset( $_POST['ace_order'] ) ? sanitize_text_field( wp_unslash( $_POST['ace_order'] ) ) : '';
	$message = isset( $_POST['ace_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['ace_message'] ) ) : '';

	if ( '' === $name || ! is_email( $email ) || '' === $message ) {
		$done( 'invalid' );
	}

	set_transient( $ip_key, $count + 1, HOUR_IN_SECONDS );

	$to = sanitize_email( (string) get_theme_mod( 'ace_email', '' ) );
	if ( ! is_email( $to ) ) {
		$to = get_option( 'admin_email' );
	}

	$body = sprintf(
		"%s: %s\n%s: %s\n%s: %s\n\n%s",
		__( 'Name', '2acetech' ),
		$name,
		__( 'Email', '2acetech' ),
		$email,
		__( 'Order number', '2acetech' ),
		'' !== $order ? $order : '-',
		$message
	);

	$sent = wp_mail(
		$to,
		/* translators: 1: site name, 2: sender name. */
		sprintf( __( '[%1$s] Message from %2$s', '2acetech' ), get_bloginfo( 'name' ), $name ),
		$body,
		array( 'Reply-To: ' . $name . ' <' . $email . '>' )
	);

	$done( $sent ? 'ok' : 'fail' );
}
add_action( 'admin_post_nopriv_ace_contact', 'ace_handle_contact' );
add_action( 'admin_post_ace_contact', 'ace_handle_contact' );
