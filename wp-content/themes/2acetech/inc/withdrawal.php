<?php
/**
 * "Withdraw from contract here": the electronic withdrawal function required by
 * Directive (EU) 2023/2673 (art. 11a of Directive 2011/83/EU).
 *
 * - A page using template-withdrawal.php (created automatically, slug
 *   odstap-od-umowy) holds the form: name, order number, e-mail, optional note.
 * - A link with the same label sits in the footer of every page, next to each
 *   order in My Account, on the order confirmation page and in customer emails.
 * - Submitting records the declaration (Withdrawals in the admin menu), emails
 *   the consumer an acknowledgement with date and time, and emails the store.
 *   If the order matches (number + billing email) an order note is added.
 *
 * Nothing is refunded or changed automatically; the store handles the rest.
 *
 * @package 2acetech
 */

defined( 'ABSPATH' ) || exit;

const ACE_WITHDRAWAL_SLUG     = 'odstap-od-umowy';
const ACE_WITHDRAWAL_TEMPLATE = 'template-withdrawal.php';

/* The page and its link ---------------------------------------------------- */

/**
 * The withdrawal page: the one using our template, else the one with our slug.
 *
 * @return WP_Post|null
 */
function ace_withdrawal_page() {
	$pages = get_posts(
		array(
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'meta_key'       => '_wp_page_template', // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'     => ACE_WITHDRAWAL_TEMPLATE, // phpcs:ignore WordPress.DB.SlowDBQuery
			'no_found_rows'  => true,
		)
	);
	if ( $pages ) {
		return $pages[0];
	}
	$page = get_page_by_path( ACE_WITHDRAWAL_SLUG );
	return ( $page && 'publish' === $page->post_status ) ? $page : null;
}

/**
 * URL of the withdrawal page, optionally with an order number to prefill.
 *
 * @param string $order Order number.
 * @return string Empty string if the page does not exist.
 */
function ace_withdrawal_url( $order = '' ) {
	$page = ace_withdrawal_page();
	if ( ! $page ) {
		return '';
	}
	$url = get_permalink( $page );
	return '' !== (string) $order ? add_query_arg( 'order', rawurlencode( (string) $order ), $url ) : $url;
}

/**
 * Create the page once if the store has none. Runs in the admin so a theme
 * upload "replace current" (which fires no switch hook) still sets it up.
 */
function ace_maybe_create_withdrawal_page() {
	if ( get_option( 'ace_withdrawal_page_checked' ) || ace_withdrawal_page() ) {
		return;
	}
	update_option( 'ace_withdrawal_page_checked', 1 );

	if ( get_page_by_path( ACE_WITHDRAWAL_SLUG ) ) {
		return; // Someone made a page with this slug; leave it alone.
	}

	wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => __( 'Withdraw from contract here', '2acetech' ),
			'post_name'    => ACE_WITHDRAWAL_SLUG,
			'post_content' => '',
			'meta_input'   => array( '_wp_page_template' => ACE_WITHDRAWAL_TEMPLATE ),
		)
	);
}
add_action( 'admin_init', 'ace_maybe_create_withdrawal_page' );
add_action( 'after_switch_theme', 'ace_maybe_create_withdrawal_page' );

/**
 * The link, as HTML.
 *
 * @param string $order Order number to prefill.
 * @param string $class CSS class.
 * @return string
 */
function ace_withdrawal_link( $order = '', $class = 'withdraw-link' ) {
	$url = ace_withdrawal_url( $order );
	if ( '' === $url ) {
		return '';
	}
	return sprintf( '<a class="%1$s" href="%2$s">%3$s</a>', esc_attr( $class ), esc_url( $url ), esc_html__( 'Withdraw from contract here', '2acetech' ) );
}

/** Order confirmation page. */
function ace_withdrawal_thankyou( $order_id ) {
	$order = wc_get_order( $order_id );
	$link  = $order ? ace_withdrawal_link( $order->get_order_number(), 'withdraw-link withdraw-link--box' ) : '';
	if ( $link ) {
		echo '<p class="withdraw-note">' . $link . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
}
add_action( 'woocommerce_thankyou', 'ace_withdrawal_thankyou', 30 );

/**
 * My Account > Orders: a button on every order.
 *
 * @param array    $actions Actions.
 * @param WC_Order $order   Order.
 * @return array
 */
function ace_withdrawal_order_action( $actions, $order ) {
	$url = ace_withdrawal_url( $order->get_order_number() );
	if ( $url ) {
		$actions['withdraw'] = array(
			'url'  => $url,
			'name' => __( 'Withdraw from contract here', '2acetech' ),
		);
	}
	return $actions;
}
add_filter( 'woocommerce_my_account_my_orders_actions', 'ace_withdrawal_order_action', 20, 2 );

/**
 * Customer emails: the link under the order table.
 *
 * @param WC_Order $order         Order.
 * @param bool     $sent_to_admin Whether the email goes to the store.
 * @param bool     $plain_text    Plain-text email.
 */
function ace_withdrawal_email_link( $order, $sent_to_admin, $plain_text ) {
	if ( $sent_to_admin ) {
		return;
	}
	$url = ace_withdrawal_url( $order->get_order_number() );
	if ( '' === $url ) {
		return;
	}
	if ( $plain_text ) {
		echo "\n" . esc_html__( 'Withdraw from contract here', '2acetech' ) . ': ' . esc_url_raw( $url ) . "\n";
	} else {
		echo '<p><a href="' . esc_url( $url ) . '">' . esc_html__( 'Withdraw from contract here', '2acetech' ) . '</a></p>';
	}
}
add_action( 'woocommerce_email_after_order_table', 'ace_withdrawal_email_link', 20, 3 );

/* Stored declarations ------------------------------------------------------ */

/**
 * "Withdrawals" in the admin menu: one entry per declaration, visible to shop
 * managers only.
 */
function ace_register_withdrawal_type() {
	register_post_type(
		'ace_withdrawal',
		array(
			'labels'          => array(
				'name'          => __( 'Withdrawals', '2acetech' ),
				'singular_name' => __( 'Withdrawal', '2acetech' ),
				'edit_item'     => __( 'Withdrawal', '2acetech' ),
				'not_found'     => __( 'No withdrawals yet.', '2acetech' ),
			),
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => true,
			'menu_position'   => 56,
			'menu_icon'       => 'dashicons-undo',
			'supports'        => array( 'title', 'editor' ),
			'capability_type' => 'post',
			'map_meta_cap'    => true,
			'capabilities'    => array( 'create_posts' => 'do_not_allow' ), // Only the website form creates them.
		)
	);
}
add_action( 'init', 'ace_register_withdrawal_type' );

/* Form handler ------------------------------------------------------------- */

/**
 * Handle the withdrawal form.
 */
function ace_handle_withdrawal() {
	$page = ace_withdrawal_url();
	$back = $page ? $page : home_url( '/' );
	$done = static function ( $status, $args = array() ) use ( $back ) {
		$args['wd'] = $status;
		wp_safe_redirect( add_query_arg( $args, $back ) . '#withdrawal-form' );
		exit;
	};

	if ( ! isset( $_POST['ace_wd_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ace_wd_nonce'] ) ), 'ace_withdrawal' ) ) {
		$done( 'error' );
	}

	if ( ! empty( $_POST['ace_website'] ) ) { // Honeypot.
		$done( 'error' );
	}

	$ip_key = 'ace_wd_' . md5( isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'x' );
	$count  = (int) get_transient( $ip_key );
	if ( $count >= 10 ) {
		$done( 'limit' );
	}

	$name  = isset( $_POST['ace_wd_name'] ) ? sanitize_text_field( wp_unslash( $_POST['ace_wd_name'] ) ) : '';
	$order = isset( $_POST['ace_wd_order'] ) ? sanitize_text_field( wp_unslash( $_POST['ace_wd_order'] ) ) : '';
	$email = isset( $_POST['ace_wd_email'] ) ? sanitize_email( wp_unslash( $_POST['ace_wd_email'] ) ) : '';
	$note  = isset( $_POST['ace_wd_note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['ace_wd_note'] ) ) : '';

	if ( '' === $name || '' === $order || ! is_email( $email ) ) {
		$done( 'invalid' );
	}

	set_transient( $ip_key, $count + 1, HOUR_IN_SECONDS );

	$now   = time();
	$when  = wp_date( 'Y-m-d H:i:s T', $now );
	$items = '' !== $note ? $note : __( 'The whole order', '2acetech' );

	$declaration = sprintf(
		"%s: %s\n%s: %s\n%s: %s\n%s: %s\n%s: %s",
		__( 'Name', '2acetech' ),
		$name,
		__( 'Order number', '2acetech' ),
		$order,
		__( 'Email', '2acetech' ),
		$email,
		__( 'Goods covered', '2acetech' ),
		$items,
		__( 'Received', '2acetech' ),
		$when
	);

	// Match the order, quietly: the consumer is never told whether it matched.
	$matched = null;
	if ( function_exists( 'wc_get_order' ) && ctype_digit( ltrim( $order, '#' ) ) ) {
		$candidate = wc_get_order( absint( ltrim( $order, '#' ) ) );
		if ( $candidate && 0 === strcasecmp( (string) $candidate->get_billing_email(), $email ) ) {
			$matched = $candidate;
		}
	}

	$post_id = wp_insert_post(
		array(
			'post_type'    => 'ace_withdrawal',
			'post_status'  => 'publish',
			/* translators: 1: order number, 2: name. */
			'post_title'   => sprintf( __( 'Withdrawal: order %1$s, %2$s', '2acetech' ), $order, $name ),
			'post_content' => $declaration . "\n\n" . ( $matched ? __( 'Matched to the order (number and email agree).', '2acetech' ) : __( 'Not matched to an order automatically. Check the details.', '2acetech' ) ),
			'meta_input'   => array(
				'_ace_wd_email' => $email,
				'_ace_wd_time'  => $now,
			),
		)
	);

	if ( $matched ) {
		/* translators: %s: date and time. */
		$matched->add_order_note( sprintf( __( 'The customer declared withdrawal from the contract using the website function on %s.', '2acetech' ), $when ) );
	}

	// Acknowledgement to the consumer, on a durable medium (email), now.
	$shop    = get_bloginfo( 'name' );
	$ack_msg = sprintf(
		"%s\n\n%s\n\n%s\n\n%s",
		/* translators: %s: date and time. */
		sprintf( __( 'We confirm that we received your declaration of withdrawal from the contract on %s.', '2acetech' ), $when ),
		$declaration,
		__( 'We will reply to this address with the next steps for returning the goods and the refund.', '2acetech' ),
		$shop
	);
	$sent = wp_mail(
		$email,
		/* translators: %s: shop name. */
		sprintf( __( '[%s] Confirmation of your withdrawal from the contract', '2acetech' ), $shop ),
		$ack_msg
	);

	// Copy to the store.
	$to = sanitize_email( (string) get_theme_mod( 'ace_email', '' ) );
	if ( ! is_email( $to ) ) {
		$to = get_option( 'admin_email' );
	}
	wp_mail(
		$to,
		/* translators: %s: order number. */
		sprintf( __( '[%1$s] Withdrawal from contract: order %2$s', '2acetech' ), $shop, $order ),
		$declaration . "\n\n" . ( $sent ? __( 'Acknowledgement sent to the consumer.', '2acetech' ) : __( 'WARNING: the acknowledgement email to the consumer could NOT be sent. Contact them now.', '2acetech' ) ) . "\n\n" . ( $post_id ? admin_url( 'post.php?post=' . (int) $post_id . '&action=edit' ) : '' ),
		array( 'Reply-To: ' . $name . ' <' . $email . '>' )
	);

	$done( $sent ? 'ok' : 'saved', array( 't' => $now ) );
}
add_action( 'admin_post_nopriv_ace_withdrawal', 'ace_handle_withdrawal' );
add_action( 'admin_post_ace_withdrawal', 'ace_handle_withdrawal' );
