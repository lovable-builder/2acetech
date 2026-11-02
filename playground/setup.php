<?php
/**
 * Local preview setup for the 2acetech theme (WordPress Playground only).
 * Not part of the theme and never deployed.
 */

update_option( 'blogname', '2acetech' );
update_option( 'blogdescription', 'Premium electronics' );
set_theme_mod( 'ace_seller', 'Sample Sp. z o.o.' );
set_theme_mod( 'ace_seller_id', 'NIP 000-000-00-00' );
set_theme_mod( 'ace_address', 'ul. Przykladowa 1, 00-001 Warszawa' );
set_theme_mod( 'ace_phone', '+48 000 000 000' );
update_option( 'timezone_string', 'Europe/Warsaw' );
update_option( 'permalink_structure', '/%postname%/' );

// WooCommerce: Polish store, skip the onboarding wizard, make the shop public.
update_option( 'woocommerce_currency', 'PLN' );
update_option( 'woocommerce_default_country', 'PL' );
update_option( 'woocommerce_currency_pos', 'right_space' );
update_option( 'woocommerce_price_thousand_sep', ' ' );
update_option( 'woocommerce_price_decimal_sep', ',' );
update_option( 'woocommerce_coming_soon', 'no' );
update_option( 'woocommerce_store_pages_only', 'no' );
update_option( 'woocommerce_onboarding_profile', array( 'skipped' => true ) );
update_option( 'woocommerce_task_list_hidden', 'yes' );

/**
 * Create a published page once, by title.
 */
function ace_dev_page( $title, $content = '' ) {
	$existing = get_page_by_title( $title, OBJECT, 'page' ); // phpcs:ignore WordPress.WP.DeprecatedFunctions
	if ( $existing ) {
		return $existing->ID;
	}
	return wp_insert_post(
		array(
			'post_title'   => $title,
			'post_content' => $content,
			'post_status'  => 'publish',
			'post_type'    => 'page',
		)
	);
}

$home    = ace_dev_page( 'Home' );
$blog    = ace_dev_page( 'Blog' );
$about   = ace_dev_page( 'About us', '<p>2acetech sells premium electronics, delivered fast across Poland.</p>' );
$contact = ace_dev_page( 'Contact', '<p>Write to us at info@2acetech.pl.</p>' );

update_post_meta( $contact, '_wp_page_template', 'template-contact.php' );
update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $home );
update_option( 'page_for_posts', $blog );

// Primary menu.
$menu_name = 'Main menu';
if ( ! wp_get_nav_menu_object( $menu_name ) ) {
	$menu_id = wp_create_nav_menu( $menu_name );
	$items   = array(
		'Home'    => $home,
		'Shop'    => function_exists( 'wc_get_page_id' ) ? wc_get_page_id( 'shop' ) : 0,
		'Blog'    => $blog,
		'About'   => $about,
		'Contact' => $contact,
	);
	foreach ( $items as $label => $page_id ) {
		if ( ! $page_id || $page_id < 1 ) {
			continue;
		}
		wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-title'     => $label,
				'menu-item-object'    => 'page',
				'menu-item-object-id' => $page_id,
				'menu-item-type'      => 'post_type',
				'menu-item-status'    => 'publish',
			)
		);
	}
	$locations            = get_theme_mod( 'nav_menu_locations', array() );
	$locations['primary'] = $menu_id;
	set_theme_mod( 'nav_menu_locations', $locations );
}

// Preview-only commerce setup: payment methods and a Poland shipping zone, so
// cart and checkout have something to show.
update_option( 'woocommerce_cod_settings', array( 'enabled' => 'yes', 'title' => 'Pay on delivery', 'description' => 'Pay with cash or card when the courier arrives.' ) );
update_option( 'woocommerce_bacs_settings', array( 'enabled' => 'yes', 'title' => 'Bank transfer', 'description' => 'Pay by bank transfer. We ship once the payment arrives.' ) );
if ( class_exists( 'WC_Shipping_Zone' ) && empty( WC_Shipping_Zones::get_zones() ) ) {
	$zone = new WC_Shipping_Zone();
	$zone->set_zone_name( 'Poland' );
	$zone->add_location( 'PL', 'country' );
	$zone->save();
	$flat = $zone->add_shipping_method( 'flat_rate' );
	update_option( 'woocommerce_flat_rate_' . $flat . '_settings', array( 'title' => 'Courier delivery', 'tax_status' => 'none', 'cost' => '15' ) );
	$free = $zone->add_shipping_method( 'free_shipping' );
	update_option( 'woocommerce_free_shipping_' . $free . '_settings', array( 'title' => 'Free delivery over 500 zł', 'requires' => 'min_amount', 'min_amount' => '500' ) );
}

// Remove WordPress's starter content.
foreach ( array( 'Sample Page' ) as $title ) {
	$starter = get_page_by_title( $title, OBJECT, 'page' ); // phpcs:ignore WordPress.WP.DeprecatedFunctions
	if ( $starter ) {
		wp_delete_post( $starter->ID, true );
	}
}
$hello = get_page_by_title( 'Hello world!', OBJECT, 'post' ); // phpcs:ignore WordPress.WP.DeprecatedFunctions
if ( $hello ) {
	wp_delete_post( $hello->ID, true );
}

flush_rewrite_rules();
