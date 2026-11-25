<?php
/**
 * Data: the pop-up post type, settings of each pop-up, global settings, statistics and leads.
 *
 * @package 2ace-popups
 */

defined( 'ABSPATH' ) || exit;

class ACEPU_Store {

	const CPT     = 'ace_popup';
	const META    = '_acepu';
	const OPTION  = 'acepu_settings';
	const DB_VER  = '1';

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
		add_action( 'init', array( __CLASS__, 'maybe_upgrade' ), 5 );
		add_action( 'before_delete_post', array( __CLASS__, 'forget' ) );
	}

	public static function register() {
		register_post_type(
			self::CPT,
			array(
				'labels'          => array(
					'name'          => __( 'Pop-ups', '2ace-popups' ),
					'singular_name' => __( 'Pop-up', '2ace-popups' ),
					'add_new'       => __( 'Add pop-up', '2ace-popups' ),
					'add_new_item'  => __( 'Add pop-up', '2ace-popups' ),
					'edit_item'     => __( 'Edit pop-up', '2ace-popups' ),
					'all_items'     => __( 'All pop-ups', '2ace-popups' ),
					'search_items'  => __( 'Search pop-ups', '2ace-popups' ),
					'not_found'     => __( 'No pop-ups yet. Start from a template!', '2ace-popups' ),
					'menu_name'     => __( 'Pop-ups', '2ace-popups' ),
				),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => true,
				'menu_position'   => 58,
				'menu_icon'       => 'dashicons-megaphone',
				'supports'        => array( 'title' ),
				'capability_type' => 'post',
				'map_meta_cap'    => true,
				'rewrite'         => false,
			)
		);
	}

	/* ---------------------------------------------------------------------
	 * Tables
	 * ------------------------------------------------------------------ */

	public static function stats_table() {
		global $wpdb;
		return $wpdb->prefix . 'acepu_stats';
	}

	public static function leads_table() {
		global $wpdb;
		return $wpdb->prefix . 'acepu_leads';
	}

	public static function install() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$c = $wpdb->get_charset_collate();
		dbDelta(
			'CREATE TABLE ' . self::stats_table() . " (
				popup_id bigint(20) unsigned NOT NULL,
				day date NOT NULL,
				event varchar(16) NOT NULL,
				n int(10) unsigned NOT NULL DEFAULT 0,
				PRIMARY KEY  (popup_id,day,event)
			) $c;"
		);
		dbDelta(
			'CREATE TABLE ' . self::leads_table() . " (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				popup_id bigint(20) unsigned NOT NULL,
				email varchar(190) NOT NULL,
				coupon varchar(64) NOT NULL DEFAULT '',
				consent_service tinyint(1) NOT NULL DEFAULT 0,
				consent_news tinyint(1) NOT NULL DEFAULT 0,
				consent_text text NOT NULL,
				created datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY email (email),
				KEY popup_id (popup_id)
			) $c;"
		);
		update_option( 'acepu_db', self::DB_VER );
	}

	public static function maybe_upgrade() {
		if ( get_option( 'acepu_db' ) !== self::DB_VER ) {
			self::install();
		}
	}

	/** Remove a pop-up's statistics when it is deleted for good (leads stay: they are records of consent). */
	public static function forget( $post_id ) {
		if ( self::CPT !== get_post_type( $post_id ) ) {
			return;
		}
		global $wpdb;
		$wpdb->delete( self::stats_table(), array( 'popup_id' => (int) $post_id ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}

	/* ---------------------------------------------------------------------
	 * Pop-up settings
	 * ------------------------------------------------------------------ */

	public static function defaults() {
		$d = array();
		foreach ( acepu_schema() as $k => $f ) {
			$d[ $k ] = $f['def'];
		}
		return $d;
	}

	/** Settings of one pop-up, filled with defaults. */
	public static function get( $id ) {
		$raw = get_post_meta( $id, self::META, true );
		return wp_parse_args( is_array( $raw ) ? $raw : array(), self::defaults() );
	}

	/**
	 * Clean user input against the schema.
	 *
	 * @param array $in Raw values (slashes already removed).
	 * @return array
	 */
	public static function sanitize( $in ) {
		$in  = is_array( $in ) ? $in : array();
		$out = array();
		foreach ( acepu_schema() as $k => $f ) {
			$v = isset( $in[ $k ] ) ? $in[ $k ] : null;
			switch ( $f['type'] ) {
				case 'text':
					$out[ $k ] = null === $v ? $f['def'] : sanitize_text_field( (string) $v );
					break;
				case 'textarea':
					$out[ $k ] = null === $v ? $f['def'] : sanitize_textarea_field( (string) $v );
					break;
				case 'lines':
					$lines     = null === $v ? array() : preg_split( '/\r\n|\r|\n/', (string) $v );
					$out[ $k ] = implode( "\n", array_filter( array_map( 'sanitize_text_field', $lines ) ) );
					break;
				case 'url':
					$out[ $k ] = null === $v ? '' : esc_url_raw( trim( (string) $v ) );
					break;
				case 'int':
					$n         = null === $v || '' === $v ? (int) $f['def'] : (int) $v;
					$out[ $k ] = max( isset( $f['min'] ) ? $f['min'] : PHP_INT_MIN, min( isset( $f['max'] ) ? $f['max'] : PHP_INT_MAX, $n ) );
					break;
				case 'bool':
					$out[ $k ] = empty( $v ) ? 0 : 1;
					break;
				case 'color':
					$c         = sanitize_hex_color( (string) $v );
					$out[ $k ] = $c ? $c : $f['def'];
					break;
				case 'select':
					$out[ $k ] = ( null !== $v && isset( $f['opts'][ $v ] ) ) ? (string) $v : $f['def'];
					break;
				case 'multi':
					$vals      = is_array( $v ) ? $v : array();
					$out[ $k ] = array_values( array_filter( array_map( 'strval', $vals ), static function ( $x ) use ( $f ) { return isset( $f['opts'][ $x ] ); } ) );
					break;
				case 'media':
					$out[ $k ] = absint( $v );
					break;
				case 'ids':
					$out[ $k ] = array_values( array_unique( array_filter( array_map( 'absint', is_array( $v ) ? $v : array() ) ) ) );
					break;
				case 'datetime':
					$ts        = $v ? strtotime( (string) $v ) : false;
					$out[ $k ] = $ts ? gmdate( 'Y-m-d\TH:i', $ts ) : '';
					break;
				default:
					$out[ $k ] = $f['def'];
			}
		}
		// "Hide after" must come after "Show from".
		if ( $out['start'] && $out['end'] && strtotime( $out['end'] ) <= strtotime( $out['start'] ) ) {
			$out['end'] = '';
		}
		if ( $out['hour_to'] <= $out['hour_from'] ) {
			$out['hour_from'] = 0;
			$out['hour_to']   = 24;
		}
		return $out;
	}

	public static function save( $id, $clean ) {
		update_post_meta( $id, self::META, $clean );
	}

	/** Timestamp (UTC) of a shop-time "Y-m-dTH:i" string, or 0. */
	public static function ts( $local ) {
		if ( ! $local ) {
			return 0;
		}
		$dt = date_create( $local, wp_timezone() );
		return $dt ? $dt->getTimestamp() : 0;
	}

	/* ---------------------------------------------------------------------
	 * Global settings
	 * ------------------------------------------------------------------ */

	public static function options() {
		return wp_parse_args(
			get_option( self::OPTION, array() ),
			array(
				'pause'        => 0,
				'gap'          => 45,
				'exclude'      => array( 'cart', 'checkout', 'account' ),
				'wait_cookie'  => 1,
				'admins'       => 1,
				'ai_text'      => 'gemini-2.5-flash',
				'ai_text_loc'  => 'global',
				'ai_image'     => 'gemini-3-pro-image',
				'ai_image_loc' => 'global',
				'ai_limit'     => 30,
				'restrict_email' => 1,
			)
		);
	}

	/* ---------------------------------------------------------------------
	 * Statistics
	 * ------------------------------------------------------------------ */

	public static function events() {
		return array( 'view', 'close', 'cta', 'copy', 'signup' );
	}

	public static function record( $popup_id, $event ) {
		if ( ! in_array( $event, self::events(), true ) ) {
			return;
		}
		global $wpdb;
		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				'INSERT INTO ' . self::stats_table() . ' (popup_id, day, event, n) VALUES (%d, %s, %s, 1) ON DUPLICATE KEY UPDATE n = n + 1', // phpcs:ignore WordPress.DB.PreparedSQL
				$popup_id,
				current_time( 'Y-m-d' ),
				$event
			)
		);
	}

	/** Totals per event. */
	public static function totals( $popup_id ) {
		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT event, SUM(n) AS n FROM ' . self::stats_table() . ' WHERE popup_id = %d GROUP BY event', $popup_id ), OBJECT_K ); // phpcs:ignore WordPress.DB
		$out  = array_fill_keys( self::events(), 0 );
		foreach ( (array) $rows as $e => $r ) {
			$out[ $e ] = (int) $r->n;
		}
		return $out;
	}

	/** Views per day for the last N days (oldest first). */
	public static function daily( $popup_id, $days = 14 ) {
		global $wpdb;
		$from = gmdate( 'Y-m-d', current_time( 'timestamp' ) - ( $days - 1 ) * DAY_IN_SECONDS ); // phpcs:ignore WordPress.DateTime
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT day, SUM(n) AS n FROM " . self::stats_table() . " WHERE popup_id = %d AND event = 'view' AND day >= %s GROUP BY day", $popup_id, $from ), OBJECT_K ); // phpcs:ignore WordPress.DB
		$out  = array();
		for ( $i = $days - 1; $i >= 0; $i-- ) {
			$d         = gmdate( 'Y-m-d', current_time( 'timestamp' ) - $i * DAY_IN_SECONDS ); // phpcs:ignore WordPress.DateTime
			$out[ $d ] = isset( $rows[ $d ] ) ? (int) $rows[ $d ]->n : 0;
		}
		return $out;
	}

	public static function reset_stats( $popup_id ) {
		global $wpdb;
		$wpdb->delete( self::stats_table(), array( 'popup_id' => (int) $popup_id ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}

	/* ---------------------------------------------------------------------
	 * Leads
	 * ------------------------------------------------------------------ */

	public static function add_lead( $popup_id, $email, $coupon, $svc, $news, $text ) {
		global $wpdb;
		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			self::leads_table(),
			array(
				'popup_id'        => (int) $popup_id,
				'email'           => $email,
				'coupon'          => $coupon,
				'consent_service' => $svc ? 1 : 0,
				'consent_news'    => $news ? 1 : 0,
				'consent_text'    => $text,
				'created'         => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%d', '%d', '%s', '%s' )
		);
		return (int) $wpdb->insert_id;
	}

	/** An earlier coupon this e-mail got from this pop-up, or ''. */
	public static function lead_coupon( $popup_id, $email ) {
		global $wpdb;
		return (string) $wpdb->get_var( $wpdb->prepare( 'SELECT coupon FROM ' . self::leads_table() . ' WHERE popup_id = %d AND email = %s ORDER BY id DESC LIMIT 1', $popup_id, $email ) ); // phpcs:ignore WordPress.DB
	}
}
