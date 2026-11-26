<?php
/**
 * Plugin Name:       2ACE Popups
 * Description:       Design your own marketing pop-ups for sales and occasions, with coupons, e-mail sign-up that creates a one-time coupon, many triggers (time, scroll, exit intent, idle, add to cart ...), targeting, schedule, statistics, and AI help for the texts and artwork. No paid plugin needed.
 * Version:           1.1.1
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            2ACE sp. z o.o.
 * License:           GPL-2.0-or-later
 * Text Domain:       2ace-popups
 *
 * @package 2ace-popups
 */

defined( 'ABSPATH' ) || exit;

define( 'ACEPU_VERSION', '1.1.1' );
define( 'ACEPU_FILE', __FILE__ );
define( 'ACEPU_DIR', plugin_dir_path( __FILE__ ) );
define( 'ACEPU_URL', plugin_dir_url( __FILE__ ) );

require_once ACEPU_DIR . 'includes/schema.php';
require_once ACEPU_DIR . 'includes/class-store.php';
require_once ACEPU_DIR . 'includes/presets.php';
require_once ACEPU_DIR . 'includes/class-render.php';
require_once ACEPU_DIR . 'includes/class-coupons.php';
require_once ACEPU_DIR . 'includes/class-ai.php';
require_once ACEPU_DIR . 'includes/class-front.php';
require_once ACEPU_DIR . 'includes/class-admin.php';

register_activation_hook( __FILE__, array( 'ACEPU_Store', 'install' ) );

add_action(
	'plugins_loaded',
	function () {
		ACEPU_Store::init();
		ACEPU_Front::init();
		if ( is_admin() ) {
			ACEPU_Admin::init();
		}
	}
);
