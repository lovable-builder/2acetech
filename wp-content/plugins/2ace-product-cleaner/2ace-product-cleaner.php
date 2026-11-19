<?php
/**
 * Plugin Name:       2ACE Product Cleaner
 * Description:       Rewrites imported AliExpress products in Polish, automatically: title, bullets, description, tags, attributes, SKU, category and .99 prices. Own products are never touched. Undo per product.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Requires Plugins:  woocommerce
 * Author:            2ACE sp. z o.o.
 * License:           GPL-2.0-or-later
 * Text Domain:       2ace-product-cleaner
 *
 * @package 2ace-product-cleaner
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'plugins_loaded',
	function () {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}
		require_once __DIR__ . '/includes/class-settings.php';
		require_once __DIR__ . '/includes/class-cleaner.php';
		ACE_PC_Settings::init();
	}
);

register_deactivation_hook(
	__FILE__,
	function () {
		wp_clear_scheduled_hook( 'ace_pc_cron' );
	}
);
