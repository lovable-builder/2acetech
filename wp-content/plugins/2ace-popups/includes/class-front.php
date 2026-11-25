<?php
/**
 * The visitor side: which pop-ups can appear on this page, the script that decides when, and the
 * two small public endpoints (statistics, e-mail sign-up).
 *
 * Page caching: the HTML of a page is the same for everyone, so everything that depends on the
 * visitor (device, new/returning, cart, how often, time of day) is decided in the browser. Only
 * page-level rules are decided here.
 *
 * @package 2ace-popups
 */

defined( 'ABSPATH' ) || exit;

class ACEPU_Front {

	/** @var array[] Pop-ups chosen for this page: id => settings. */
	private static $chosen = array();

	/** @var bool Whether chosen() already ran for this request. */
	private static $done = false;

	/** Forget the choice (tests re-run it for other pages). */
	public static function reset() {
		self::$chosen = array();
		self::$done   = false;
	}

	public static function init() {
		ACEPU_Coupons::init();
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'wp_footer', array( __CLASS__, 'markup' ), 20 );
		add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
	}

	/* ---------------------------------------------------------------------
	 * What page is this?
	 * ------------------------------------------------------------------ */

	public static function page_type() {
		if ( function_exists( 'is_checkout' ) && ( is_checkout() || is_wc_endpoint_url( 'order-received' ) ) ) {
			return 'checkout';
		}
		if ( function_exists( 'is_cart' ) && is_cart() ) {
			return 'cart';
		}
		if ( function_exists( 'is_account_page' ) && is_account_page() ) {
			return 'account';
		}
		if ( function_exists( 'is_product' ) && is_product() ) {
			return 'product';
		}
		if ( function_exists( 'is_shop' ) && is_shop() ) {
			return 'shop';
		}
		if ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() ) {
			return 'product_cat';
		}
		if ( is_front_page() ) {
			return 'home';
		}
		if ( is_search() ) {
			return 'search';
		}
		if ( is_singular( 'post' ) ) {
			return 'post';
		}
		return 'page';
	}

	/** Product category ids that belong to this page (with their parents). */
	private static function page_cat_ids( $type ) {
		$ids = array();
		if ( 'product_cat' === $type && is_tax( 'product_cat' ) ) {
			$t = get_queried_object();
			if ( $t instanceof WP_Term ) {
				$ids = array_merge( array( $t->term_id ), get_ancestors( $t->term_id, 'product_cat', 'taxonomy' ) );
			}
		} elseif ( 'product' === $type ) {
			foreach ( wc_get_product_term_ids( get_queried_object_id(), 'product_cat' ) as $tid ) {
				$ids[] = $tid;
				$ids   = array_merge( $ids, get_ancestors( $tid, 'product_cat', 'taxonomy' ) );
			}
		}
		return $ids;
	}

	/* ---------------------------------------------------------------------
	 * Which pop-ups are possible here
	 * ------------------------------------------------------------------ */

	private static function preview_id() {
		// phpcs:disable WordPress.Security.NonceVerification
		if ( empty( $_GET['acepu_preview'] ) || ! current_user_can( 'manage_options' ) ) {
			return 0;
		}
		$id = absint( $_GET['acepu_preview'] );
		$ok = isset( $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'acepu_preview_' . $id );
		// phpcs:enable
		return $ok ? $id : 0;
	}

	public static function chosen() {
		if ( self::$done ) {
			return self::$chosen;
		}
		self::$done = true;
		if ( is_admin() || is_feed() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return self::$chosen;
		}
		$o       = ACEPU_Store::options();
		$preview = self::preview_id();

		if ( $preview ) {
			$p = get_post( $preview );
			if ( $p && ACEPU_Store::CPT === $p->post_type ) {
				self::$chosen[ $preview ] = ACEPU_Store::get( $preview );
			}
			return self::$chosen;
		}
		if ( $o['pause'] || ( ! $o['admins'] && current_user_can( 'manage_options' ) ) ) {
			return self::$chosen;
		}

		$type = self::page_type();
		if ( in_array( $type, (array) $o['exclude'], true ) ) {
			return self::$chosen;
		}
		$ids   = get_posts( array( 'post_type' => ACEPU_Store::CPT, 'post_status' => 'publish', 'posts_per_page' => 40, 'fields' => 'ids', 'no_found_rows' => true ) );
		$uri   = isset( $_SERVER['REQUEST_URI'] ) ? strtolower( rawurldecode( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$now   = time();
		$cats  = null;
		$logged = is_user_logged_in();

		foreach ( $ids as $id ) {
			$s = ACEPU_Store::get( $id );
			if ( ! in_array( $type, $s['pages'], true ) ) {
				continue;
			}
			if ( 'in' === $s['login'] && ! $logged || 'out' === $s['login'] && $logged ) {
				continue;
			}
			$end   = ACEPU_Store::ts( $s['end'] );
			$start = ACEPU_Store::ts( $s['start'] );
			if ( $end && $end < $now - 300 ) {
				continue;
			}
			if ( $start && $start > $now + 14 * DAY_IN_SECONDS ) {
				continue;
			}
			if ( $s['cats'] ) {
				if ( null === $cats ) {
					$cats = self::page_cat_ids( $type );
				}
				if ( ! array_intersect( $s['cats'], $cats ) ) {
					continue;
				}
			}
			$skip = false;
			foreach ( array_filter( explode( "\n", strtolower( $s['url_out'] ) ) ) as $needle ) {
				if ( false !== strpos( $uri, trim( $needle ) ) ) {
					$skip = true;
				}
			}
			$must = array_filter( array_map( 'trim', explode( "\n", strtolower( $s['url_in'] ) ) ) );
			if ( $must ) {
				$hit = false;
				foreach ( $must as $needle ) {
					if ( false !== strpos( $uri, $needle ) ) {
						$hit = true;
					}
				}
				$skip = $skip || ! $hit;
			}
			if ( $skip ) {
				continue;
			}
			self::$chosen[ $id ] = $s;
		}
		return self::$chosen;
	}

	/* ---------------------------------------------------------------------
	 * Assets and output
	 * ------------------------------------------------------------------ */

	/** Config for one pop-up, for the script. */
	private static function config( $id, $s ) {
		$img = $s['image_id'] ? wp_get_attachment_image_url( $s['image_id'], 'large' ) : '';
		return array(
			'id'    => (int) $id,
			'pr'    => (int) $s['priority'],
			'tr'    => array_values( $s['triggers'] ),
			'delay' => (int) $s['delay'],
			'sc'    => (int) $s['scroll_pct'],
			'idle'  => (int) $s['idle_sec'],
			'pv'    => (int) $s['pv_n'],
			'sel'   => $s['click_sel'],
			'freq'  => $s['freq'],
			'fd'    => (int) $s['freq_days'],
			'max'   => (int) $s['max_views'],
			'conv'  => (int) $s['stop_conv'],
			'dev'   => array_values( $s['devices'] ),
			'vis'   => $s['visitor'],
			'cart'  => $s['cart'],
			'utm'   => strtolower( $s['utm'] ),
			'from'  => ACEPU_Store::ts( $s['start'] ),
			'to'    => ACEPU_Store::ts( $s['end'] ),
			'days'  => array_map( 'intval', $s['days'] ),
			'hf'    => (int) $s['hour_from'],
			'ht'    => (int) $s['hour_to'],
			'img'   => $img ? $img : '',
		);
	}

	public static function assets() {
		$chosen = self::chosen();
		if ( ! $chosen ) {
			return;
		}
		$o       = ACEPU_Store::options();
		$preview = (bool) self::preview_id();
		wp_enqueue_style( 'acepu-front', ACEPU_URL . 'assets/front.css', array(), ACEPU_VERSION );
		wp_enqueue_script( 'acepu-front', ACEPU_URL . 'assets/front.js', array(), ACEPU_VERSION, array( 'strategy' => 'defer', 'in_footer' => true ) );
		$list = array();
		foreach ( $chosen as $id => $s ) {
			$list[] = self::config( $id, $s );
		}
		$tz = wp_timezone();
		$cfg = array(
			'popups'  => $list,
			'rest'    => esc_url_raw( rest_url( 'ace-popups/v1/' ) ),
			'gap'     => (int) $o['gap'],
			'cookie'  => (bool) $o['wait_cookie'],
			'preview' => $preview,
			'stats'   => ! current_user_can( 'manage_options' ) && ! $preview,
			'tzoff'   => (int) $tz->getOffset( new DateTime( 'now', $tz ) ),
			'i18n'    => array(
				'copied' => __( 'Copied!', '2ace-popups' ),
				'copy'   => __( 'Copy', '2ace-popups' ),
				'email'  => __( 'Please enter a valid e-mail address.', '2ace-popups' ),
				'consent' => __( 'Please tick the consent box to get the code.', '2ace-popups' ),
				'error'  => __( 'Something went wrong. Please try again in a moment.', '2ace-popups' ),
				'busy'   => __( 'Sending ...', '2ace-popups' ),
			),
		);
		wp_add_inline_script( 'acepu-front', 'window.acePopups=' . wp_json_encode( $cfg ) . ';', 'before' );
	}

	public static function markup() {
		foreach ( self::chosen() as $id => $s ) {
			echo '<template id="acepu-tpl-' . (int) $id . '">' . ACEPU_Render::html( $id, $s ) . '</template>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput
		}
	}

	/* ---------------------------------------------------------------------
	 * Endpoints
	 * ------------------------------------------------------------------ */

	public static function routes() {
		register_rest_route(
			'ace-popups/v1',
			'/event',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'rest_event' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			'ace-popups/v1',
			'/signup',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'rest_signup' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/** Allow N calls per hour for one visitor. */
	private static function limited( $bucket, $max ) {
		$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$key = 'acepu_rl_' . $bucket . '_' . substr( md5( $ip . wp_salt() ), 0, 16 );
		$n   = (int) get_transient( $key );
		if ( $n >= $max ) {
			return true;
		}
		set_transient( $key, $n + 1, HOUR_IN_SECONDS );
		return false;
	}

	private static function live_popup( $id ) {
		$p = $id ? get_post( $id ) : null;
		return ( $p && ACEPU_Store::CPT === $p->post_type && 'publish' === $p->post_status ) ? $p : null;
	}

	public static function rest_event( WP_REST_Request $r ) {
		$id    = absint( $r->get_param( 'id' ) );
		$event = sanitize_key( (string) $r->get_param( 'event' ) );
		if ( ! self::live_popup( $id ) || ! in_array( $event, ACEPU_Store::events(), true ) || 'signup' === $event ) {
			return new WP_REST_Response( array( 'ok' => false ), 400 );
		}
		if ( ! self::limited( 'ev', 240 ) ) {
			ACEPU_Store::record( $id, $event );
		}
		return new WP_REST_Response( array( 'ok' => true ), 200 );
	}

	public static function rest_signup( WP_REST_Request $r ) {
		$id = absint( $r->get_param( 'popup' ) );
		if ( ! self::live_popup( $id ) ) {
			return new WP_REST_Response( array( 'ok' => false, 'msg' => __( 'This offer is no longer available.', '2ace-popups' ) ), 404 );
		}
		$s = ACEPU_Store::get( $id );
		if ( 'email' !== $s['mode'] ) {
			return new WP_REST_Response( array( 'ok' => false ), 400 );
		}
		if ( '' !== (string) $r->get_param( 'website' ) ) { // Honeypot.
			return new WP_REST_Response( array( 'ok' => false, 'msg' => __( 'Something went wrong. Please try again in a moment.', '2ace-popups' ) ), 400 );
		}
		$email = sanitize_email( (string) $r->get_param( 'email' ) );
		if ( ! is_email( $email ) || strlen( $email ) > 190 ) {
			return new WP_REST_Response( array( 'ok' => false, 'msg' => __( 'Please enter a valid e-mail address.', '2ace-popups' ) ), 422 );
		}
		if ( $s['consent_text'] && '1' !== (string) $r->get_param( 'c1' ) ) {
			return new WP_REST_Response( array( 'ok' => false, 'msg' => __( 'Please tick the consent box to get the code.', '2ace-popups' ) ), 422 );
		}
		if ( self::limited( 'su', 8 ) ) {
			return new WP_REST_Response( array( 'ok' => false, 'msg' => __( 'Too many attempts. Please try again later.', '2ace-popups' ) ), 429 );
		}

		$email = strtolower( $email );
		$code  = ACEPU_Store::lead_coupon( $id, $email );
		$again = '' !== $code;
		if ( ! $again ) {
			$code = ACEPU_Coupons::generate( $id, $s, $email );
			if ( is_wp_error( $code ) ) {
				return new WP_REST_Response( array( 'ok' => false, 'msg' => __( 'Something went wrong. Please try again in a moment.', '2ace-popups' ) ), 500 );
			}
			ACEPU_Store::add_lead( $id, $email, $code, 1, '1' === (string) $r->get_param( 'c2' ) && $s['news_on'], $s['consent_text'] . ( $s['news_on'] ? ' | ' . $s['news_text'] : '' ) );
			ACEPU_Coupons::send_mail( $email, $code, $s );
			ACEPU_Store::record( $id, 'signup' );
		}
		return new WP_REST_Response( array( 'ok' => true, 'code' => $code, 'again' => $again, 'apply' => ACEPU_Render::apply_url( $code, '' ) ), 200 );
	}
}
