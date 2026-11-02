<?php
/**
 * Local preview setup for the 2acetech theme (WordPress Playground only).
 * Not part of the theme and never deployed.
 */

update_option( 'blogname', '2acetech' );
update_option( 'blogdescription', 'Premium electronics' );
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
