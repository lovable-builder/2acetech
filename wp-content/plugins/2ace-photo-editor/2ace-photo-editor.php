<?php
/**
 * Plugin Name:       2ACE AI Photo Editor
 * Description:       Removes text from the product photos you choose, with Google Cloud (Vertex AI). You see before and after, only approved photos go into the product, and the original can be put back with one click. Edited photos are marked as AI-edited in their metadata.
 * Version:           1.1.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Requires Plugins:  woocommerce
 * Author:            2ACE sp. z o.o.
 * License:           GPL-2.0-or-later
 * Text Domain:       2ace-photo-editor
 *
 * @package 2ace-photo-editor
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'plugins_loaded',
	function () {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}
		require_once __DIR__ . '/includes/class-google.php';
		require_once __DIR__ . '/includes/class-editor.php';
		require_once __DIR__ . '/includes/class-admin.php';
		ACE_PE_Admin::init();
	}
);
