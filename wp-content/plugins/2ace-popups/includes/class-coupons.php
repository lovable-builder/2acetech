<?php
/**
 * Personal coupons: a one-time WooCommerce coupon for each e-mail that signs up, sent by e-mail.
 * Also applies a coupon to the cart when the visitor taps "Use it now".
 *
 * @package 2ace-popups
 */

defined( 'ABSPATH' ) || exit;

class ACEPU_Coupons {

	public static function init() {
		add_action( 'wp_loaded', array( __CLASS__, 'apply_link' ), 30 );
		add_action( 'woocommerce_cart_loaded_from_session', array( __CLASS__, 'apply_pending' ), 30 );
	}

	/**
	 * Create the coupon for one sign-up.
	 *
	 * @param int    $popup_id Pop-up.
	 * @param array  $s        Its settings.
	 * @param string $email    Visitor's e-mail.
	 * @param array  $prize    A fortune-wheel gift (kind, amount, product) that replaces the pop-up's own discount.
	 * @return string|WP_Error Code.
	 */
	public static function generate( $popup_id, $s, $email, $prize = array() ) {
		if ( ! class_exists( 'WC_Coupon' ) ) {
			return new WP_Error( 'acepu_nowc', __( 'WooCommerce is not active.', '2ace-popups' ) );
		}
		$prefix = strtoupper( preg_replace( '/[^A-Za-z0-9]/', '', $s['gen_prefix'] ) );
		$prefix = '' === $prefix ? 'ACE' : substr( $prefix, 0, 12 );
		$code   = '';
		for ( $i = 0; $i < 6; $i++ ) {
			$try = $prefix . '-' . strtoupper( wp_generate_password( 6, false, false ) );
			if ( ! wc_get_coupon_id_by_code( $try ) ) {
				$code = $try;
				break;
			}
		}
		if ( '' === $code ) {
			return new WP_Error( 'acepu_code', __( 'Could not create a unique code.', '2ace-popups' ) );
		}
		$o = ACEPU_Store::options();
		$c = new WC_Coupon();
		$c->set_code( $code );
		if ( $prize && 'product' === $prize['kind'] ) {
			// A free product: 100% off that one product, one piece.
			$c->set_discount_type( 'percent' );
			$c->set_amount( 100 );
			$c->set_product_ids( array( (int) $prize['product'] ) );
			$c->set_limit_usage_to_x_items( 1 );
		} elseif ( $prize ) {
			$c->set_discount_type( 'percent' === $prize['kind'] ? 'percent' : 'fixed_cart' );
			$c->set_amount( $prize['amount'] );
		} else {
			$c->set_discount_type( 'percent' === $s['gen_type'] ? 'percent' : 'fixed_cart' );
			$c->set_amount( $s['gen_amount'] );
		}
		$c->set_individual_use( true );
		$c->set_usage_limit( 1 );
		$c->set_usage_limit_per_user( 1 );
		$c->set_date_expires( time() + (int) $s['gen_days'] * DAY_IN_SECONDS );
		if ( $s['gen_min'] > 0 ) {
			$c->set_minimum_amount( (string) $s['gen_min'] );
		}
		if ( $s['gen_nosale'] ) {
			$c->set_exclude_sale_items( true );
		}
		if ( ! empty( $o['restrict_email'] ) ) {
			$c->set_email_restrictions( array( $email ) );
		}
		/* translators: %d: pop-up id. */
		$c->set_description( sprintf( __( 'Created automatically by pop-up #%d', '2ace-popups' ), $popup_id ) . ( $prize ? ' (' . $prize['label'] . ')' : '' ) );
		$c->save();
		update_post_meta( $c->get_id(), '_acepu_popup', (int) $popup_id );
		return $code;
	}

	/** E-mail the code, in the shop's e-mail look. */
	public static function send_mail( $email, $code, $s, $prize_label = '' ) {
		$subject = $s['mail_subject'] ? $s['mail_subject'] : sprintf( /* translators: %s: shop. */ __( 'Your discount code from %s', '2ace-popups' ), get_bloginfo( 'name' ) );
		$intro   = $s['mail_text'] ? $s['mail_text'] : ( $prize_label ? __( 'Congratulations on your win! Here is your gift: {prize}. Use the one-time code below.', '2ace-popups' ) : __( 'Thank you for signing up! Here is your one-time discount code.', '2ace-popups' ) );
		$o       = ACEPU_Store::options();
		$valid   = sprintf( /* translators: %d: days. */ _n( 'The code is valid for %d day.', 'The code is valid for %d days.', $s['gen_days'], '2ace-popups' ), $s['gen_days'] );
		if ( $s['gen_min'] > 0 ) {
			$valid .= ' ' . sprintf( /* translators: %s: amount. */ __( 'Minimum order: %s zł.', '2ace-popups' ), number_format_i18n( $s['gen_min'] ) );
		}
		if ( ! empty( $o['restrict_email'] ) ) {
			$valid .= ' ' . __( 'Use the same e-mail address when you place the order.', '2ace-popups' );
		}
		$body  = wpautop( esc_html( ACEPU_Render::tokens( $intro, $s, $prize_label ) ) );
		$body .= '<p style="margin:24px 0;text-align:center"><span style="display:inline-block;padding:14px 26px;border:2px dashed #f50505;border-radius:10px;font:700 24px/1.2 monospace;letter-spacing:2px;color:#0b0b0d">' . esc_html( $code ) . '</span></p>';
		$body .= '<p style="text-align:center"><a href="' . esc_url( ACEPU_Render::apply_url( $code, '' ) ) . '" style="display:inline-block;padding:12px 24px;background:#f50505;color:#ffffff;border-radius:999px;text-decoration:none;font-weight:700">' . esc_html__( 'Use it now', '2ace-popups' ) . '</a></p>';
		$body .= '<p style="color:#62626c;font-size:13px">' . esc_html( $valid ) . '</p>';

		if ( function_exists( 'WC' ) && WC()->mailer() ) {
			$mailer = WC()->mailer();
			return $mailer->send( $email, $subject, $mailer->wrap_message( $subject, $body ) );
		}
		return wp_mail( $email, $subject, wp_strip_all_tags( $body ), array( 'Content-Type: text/html; charset=UTF-8' ) );
	}

	/* ---------------------------------------------------------------------
	 * Applying a coupon from a link: /?acepu_coupon=CODE&to=URL
	 * ------------------------------------------------------------------ */

	public static function apply_link() {
		// phpcs:disable WordPress.Security.NonceVerification
		if ( empty( $_GET['acepu_coupon'] ) || ! function_exists( 'WC' ) || ! WC()->session ) {
			return;
		}
		$code = wc_format_coupon_code( wp_unslash( $_GET['acepu_coupon'] ) );
		$to   = isset( $_GET['to'] ) ? esc_url_raw( wp_unslash( $_GET['to'] ) ) : '';
		// phpcs:enable
		if ( '' === $code || ! wc_get_coupon_id_by_code( $code ) ) {
			wp_safe_redirect( $to ? $to : wc_get_cart_url() );
			exit;
		}
		WC()->session->set_customer_session_cookie( true );
		WC()->session->set( 'acepu_pending', $code ); // Applied as soon as the cart has products.
		if ( WC()->cart && ! WC()->cart->is_empty() ) {
			self::apply_pending( WC()->cart );
		}
		wp_safe_redirect( $to ? $to : wc_get_cart_url() );
		exit;
	}

	/** Apply the waiting coupon once the cart has something in it. */
	public static function apply_pending( $cart ) {
		if ( ! function_exists( 'WC' ) || ! WC()->session || ! $cart || $cart->is_empty() ) {
			return;
		}
		$code = WC()->session->get( 'acepu_pending' );
		if ( ! $code ) {
			return;
		}
		remove_action( 'woocommerce_cart_loaded_from_session', array( __CLASS__, 'apply_pending' ), 30 );
		WC()->session->set( 'acepu_pending', null );
		if ( ! $cart->has_discount( $code ) ) {
			$before = wc_get_notices();
			$cart->apply_coupon( $code );
			wc_set_notices( $before ); // A code that does not fit must not shout at the visitor.
		}
		add_action( 'woocommerce_cart_loaded_from_session', array( __CLASS__, 'apply_pending' ), 30 );
	}
}
