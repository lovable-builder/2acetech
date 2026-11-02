<?php
/**
 * Demo content shown while the store has no WooCommerce products yet.
 *
 * Every demo card links to the shop and is clearly flagged to admins, so none
 * of it can be mistaken for real stock. It disappears automatically once real
 * products exist.
 *
 * @package 2acetech
 */

defined( 'ABSPATH' ) || exit;

/**
 * Demo products.
 *
 * @return array[]
 */
function ace_demo_products() {
	return array(
		array( 'title' => 'Pulse Watch S2', 'cat' => 'wearables', 'art' => 'watch', 'price' => 899, 'was' => 1099, 'badges' => array( 'new', 'sale' ), 'rating' => 4.5 ),
		array( 'title' => 'Aero Book 14 Ultralight', 'cat' => 'laptops', 'art' => 'laptop', 'price' => 4299, 'was' => 4799, 'badges' => array( 'new', 'sale' ), 'rating' => 4 ),
		array( 'title' => 'Studio ANC Headphones', 'cat' => 'audio', 'art' => 'headphones', 'price' => 649, 'was' => 0, 'badges' => array( 'new' ), 'rating' => 5 ),
		array( 'title' => 'ActionCam 4K Waterproof', 'cat' => 'cameras', 'art' => 'camera', 'price' => 1299, 'was' => 1499, 'badges' => array( 'sale' ), 'rating' => 4 ),
		array( 'title' => 'Nova 5G Smartphone', 'cat' => 'phones', 'art' => 'phone', 'price' => 2799, 'was' => 0, 'badges' => array( 'new' ), 'rating' => 4.5 ),
		array( 'title' => 'Orbit Speaker 360', 'cat' => 'audio', 'art' => 'speaker', 'price' => 549, 'was' => 699, 'badges' => array( 'sale' ), 'rating' => 4 ),
		array( 'title' => 'Slate Tablet 11" 128GB', 'cat' => 'phones', 'art' => 'tablet', 'price' => 1899, 'was' => 0, 'badges' => array( 'new' ), 'rating' => 4 ),
		array( 'title' => 'Vector Console Bundle', 'cat' => 'gaming', 'art' => 'console', 'price' => 2199, 'was' => 2399, 'badges' => array( 'sale' ), 'rating' => 5 ),
		array( 'title' => 'Aero Book 16 Pro', 'cat' => 'laptops', 'art' => 'laptop', 'price' => 6499, 'was' => 0, 'badges' => array( 'new' ), 'rating' => 5 ),
		array( 'title' => 'Pulse Watch Lite', 'cat' => 'wearables', 'art' => 'watch', 'price' => 499, 'was' => 599, 'badges' => array( 'sale' ), 'rating' => 3.5 ),
		array( 'title' => 'Nova Mini Smartphone', 'cat' => 'phones', 'art' => 'phone', 'price' => 1699, 'was' => 0, 'badges' => array(), 'rating' => 4 ),
		array( 'title' => 'Studio Buds', 'cat' => 'audio', 'art' => 'headphones', 'price' => 299, 'was' => 349, 'badges' => array( 'sale' ), 'rating' => 4 ),
		array( 'title' => 'Aero Book 15 Student', 'cat' => 'laptops', 'art' => 'laptop', 'price' => 2899, 'was' => 3299, 'badges' => array( 'sale' ), 'rating' => 4 ),
		array( 'title' => 'Slate Laptop Air 13', 'cat' => 'laptops', 'art' => 'laptop', 'price' => 3599, 'was' => 3999, 'badges' => array( 'sale' ), 'rating' => 4.5 ),
		array( 'title' => 'Nova Max 5G Smartphone', 'cat' => 'phones', 'art' => 'phone', 'price' => 3499, 'was' => 3799, 'badges' => array( 'sale' ), 'rating' => 5 ),
		array( 'title' => 'Slate Tablet Mini 8"', 'cat' => 'phones', 'art' => 'tablet', 'price' => 1199, 'was' => 1399, 'badges' => array( 'sale' ), 'rating' => 4 ),
		array( 'title' => 'Orbit Speaker Mini', 'cat' => 'audio', 'art' => 'speaker', 'price' => 299, 'was' => 369, 'badges' => array( 'sale' ), 'rating' => 4 ),
		array( 'title' => 'Studio Over-Ear Pro', 'cat' => 'audio', 'art' => 'headphones', 'price' => 899, 'was' => 1049, 'badges' => array( 'sale' ), 'rating' => 4.5 ),
		array( 'title' => 'Frame Mirrorless Kit', 'cat' => 'cameras', 'art' => 'camera', 'price' => 4599, 'was' => 4999, 'badges' => array( 'sale' ), 'rating' => 5 ),
		array( 'title' => 'ActionCam 4K Lite', 'cat' => 'cameras', 'art' => 'camera', 'price' => 799, 'was' => 949, 'badges' => array( 'sale' ), 'rating' => 4 ),
		array( 'title' => 'Frame Compact Zoom', 'cat' => 'cameras', 'art' => 'camera', 'price' => 1899, 'was' => 2099, 'badges' => array( 'sale' ), 'rating' => 4 ),
		array( 'title' => 'Pulse Band 3', 'cat' => 'wearables', 'art' => 'watch', 'price' => 249, 'was' => 299, 'badges' => array( 'sale' ), 'rating' => 4 ),
		array( 'title' => 'Pulse Watch Pro Titanium', 'cat' => 'wearables', 'art' => 'watch', 'price' => 1799, 'was' => 1999, 'badges' => array( 'sale' ), 'rating' => 4.5 ),
		array( 'title' => 'Vector Controller Pro', 'cat' => 'gaming', 'art' => 'console', 'price' => 349, 'was' => 399, 'badges' => array( 'sale' ), 'rating' => 5 ),
		array( 'title' => 'Vector Handheld', 'cat' => 'gaming', 'art' => 'console', 'price' => 1999, 'was' => 2199, 'badges' => array( 'sale' ), 'rating' => 4 ),
		array( 'title' => 'Vector Console Digital', 'cat' => 'gaming', 'art' => 'console', 'price' => 1699, 'was' => 1849, 'badges' => array( 'sale' ), 'rating' => 4 ),
	);
}

/**
 * Demo categories: slug => label, art.
 *
 * @return array[]
 */
function ace_demo_categories() {
	return array(
		'laptops'   => array( 'label' => __( 'Laptops & computers', '2acetech' ), 'art' => 'laptop' ),
		'phones'    => array( 'label' => __( 'Phones & tablets', '2acetech' ), 'art' => 'phone' ),
		'audio'     => array( 'label' => __( 'Audio', '2acetech' ), 'art' => 'headphones' ),
		'cameras'   => array( 'label' => __( 'Cameras', '2acetech' ), 'art' => 'camera' ),
		'wearables' => array( 'label' => __( 'Wearables', '2acetech' ), 'art' => 'watch' ),
		'gaming'    => array( 'label' => __( 'Gaming', '2acetech' ), 'art' => 'console' ),
	);
}

/**
 * Brand names shown in the strip until the store has real brands.
 *
 * @return string[]
 */
function ace_demo_brands() {
	return array( 'Vector', 'Aero', 'Pulse', 'Nova', 'Orbit', 'Slate', 'Studio', 'Kinetic' );
}

/**
 * Hero scenes. Filterable: add_filter( 'ace_hero_scenes', fn ( $s ) => $s ).
 *
 * @return array[]
 */
function ace_hero_scenes() {
	$shop = ace_shop_url();

	$scenes = array(
		array(
			'eyebrow' => __( 'Wearables', '2acetech' ),
			'title'   => __( 'Wear the future on your wrist.', '2acetech' ),
			'text'    => __( 'Smartwatches that track, pay and keep up with every day.', '2acetech' ),
			'cta'     => __( 'Shop wearables', '2acetech' ),
			'url'     => ace_category_url( 'wearables', $shop ),
			'art'     => 'watch',
			'chips'   => array( __( 'AMOLED display', '2acetech' ), __( '7-day battery', '2acetech' ), __( 'GPS + NFC', '2acetech' ) ),
			'tone'    => 'ember',
		),
		array(
			'eyebrow' => __( 'Laptops', '2acetech' ),
			'title'   => __( 'Power that travels light.', '2acetech' ),
			'text'    => __( 'Ultralight laptops built for work, study and everything after.', '2acetech' ),
			'cta'     => __( 'Shop laptops', '2acetech' ),
			'url'     => ace_category_url( 'laptops', $shop ),
			'art'     => 'laptop',
			'chips'   => array( __( '14" OLED', '2acetech' ), __( '16 GB RAM', '2acetech' ), __( '1 kg', '2acetech' ) ),
			'tone'    => 'steel',
		),
		array(
			'eyebrow' => __( 'Audio', '2acetech' ),
			'title'   => __( 'Hear every last detail.', '2acetech' ),
			'text'    => __( 'Noise-cancelling headphones and speakers with room-filling sound.', '2acetech' ),
			'cta'     => __( 'Shop audio', '2acetech' ),
			'url'     => ace_category_url( 'audio', $shop ),
			'art'     => 'headphones',
			'chips'   => array( __( 'Active noise cancelling', '2acetech' ), __( '40 h playback', '2acetech' ), __( 'Bluetooth 5.3', '2acetech' ) ),
			'tone'    => 'night',
		),
	);

	return apply_filters( 'ace_hero_scenes', $scenes );
}
