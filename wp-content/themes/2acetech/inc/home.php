<?php
/**
 * Homepage content model.
 *
 * Everything on the landing page that is editable in Appearance > Customize >
 * Landing page lives here as defaults plus readers. Templates call these, never
 * get_theme_mod() directly, so defaults stay in one place.
 *
 * @package 2acetech
 */

defined( 'ABSPATH' ) || exit;

/**
 * Hero background tones.
 *
 * @return string[]
 */
function ace_tone_choices() {
	return array(
		'ember' => __( 'Red glow', '2acetech' ),
		'steel' => __( 'Steel blue', '2acetech' ),
		'night' => __( 'Deep crimson', '2acetech' ),
	);
}

/**
 * Built-in illustrations, for scenes and banners without a photo.
 *
 * @return string[]
 */
function ace_art_choices() {
	return array(
		'watch'      => __( 'Smartwatch', '2acetech' ),
		'laptop'     => __( 'Laptop', '2acetech' ),
		'headphones' => __( 'Headphones', '2acetech' ),
		'camera'     => __( 'Camera', '2acetech' ),
		'phone'      => __( 'Smartphone', '2acetech' ),
		'speaker'    => __( 'Speaker', '2acetech' ),
		'tablet'     => __( 'Tablet', '2acetech' ),
		'console'    => __( 'Game console', '2acetech' ),
	);
}

/**
 * Default hero scenes. Index 0..2.
 *
 * @return array[]
 */
function ace_hero_defaults() {
	$shop = ace_shop_url();

	return array(
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
}

/**
 * Hero scenes as configured in the Customizer. A scene can be switched off.
 * Filter: ace_hero_scenes.
 *
 * @return array[]
 */
function ace_hero_scenes() {
	$scenes = array();

	foreach ( ace_hero_defaults() as $i => $d ) {
		$n = $i + 1;
		if ( ! get_theme_mod( "ace_hero{$n}_on", true ) ) {
			continue;
		}

		$chips = array();
		foreach ( array( 1, 2, 3 ) as $c ) {
			$chip = trim( (string) get_theme_mod( "ace_hero{$n}_chip{$c}", $d['chips'][ $c - 1 ] ) );
			if ( '' !== $chip ) {
				$chips[] = $chip;
			}
		}

		$scenes[] = array(
			'eyebrow' => get_theme_mod( "ace_hero{$n}_eyebrow", $d['eyebrow'] ),
			'title'   => get_theme_mod( "ace_hero{$n}_title", $d['title'] ),
			'text'    => get_theme_mod( "ace_hero{$n}_text", $d['text'] ),
			'cta'     => get_theme_mod( "ace_hero{$n}_cta", $d['cta'] ),
			'url'     => get_theme_mod( "ace_hero{$n}_url", $d['url'] ),
			'image'   => (int) get_theme_mod( "ace_hero{$n}_image", 0 ),
			'art'     => get_theme_mod( "ace_hero{$n}_art", $d['art'] ),
			'chips'   => $chips,
			'tone'    => get_theme_mod( "ace_hero{$n}_tone", $d['tone'] ),
		);
	}

	return apply_filters( 'ace_hero_scenes', $scenes );
}

/**
 * Product photo that ships with the theme for a built-in picture name
 * (assets/img/hero-{name}.webp), as an <img>. Empty string if there is none.
 *
 * @param string $art Picture name: watch, laptop, headphones.
 * @return string
 */
function ace_bundled_photo( $art ) {
	$file = 'hero-' . sanitize_file_name( $art ) . '.webp';
	$path = ACE_DIR . '/assets/img/' . $file;
	if ( ! file_exists( $path ) ) {
		return '';
	}
	$size = getimagesize( $path );
	return sprintf(
		'<img class="art art--photo" src="%1$s" width="%2$d" height="%3$d" alt="" decoding="async">',
		esc_url( ACE_URI . '/assets/img/' . $file ),
		$size ? (int) $size[0] : 0,
		$size ? (int) $size[1] : 0
	);
}

/**
 * Visual for a hero scene or promo: the uploaded photo, else the built-in
 * photo for that picture name, else the line illustration.
 *
 * @param int    $image_id Attachment ID or 0.
 * @param string $art      Built-in picture name.
 * @param string $size     Image size for uploaded photos.
 * @return string
 */
function ace_visual( $image_id, $art, $size = 'large' ) {
	if ( $image_id ) {
		$img = wp_get_attachment_image( $image_id, $size, false, array( 'class' => 'art art--photo', 'alt' => '' ) );
		if ( $img ) {
			return $img;
		}
	}
	$photo = ace_bundled_photo( $art );
	return '' !== $photo ? $photo : ace_art( $art );
}

/**
 * Default promo banners.
 *
 * @return array[]
 */
function ace_promo_defaults() {
	$shop = ace_shop_url();

	return array(
		array(
			'kicker' => __( 'Tablets & phones', '2acetech' ),
			'title'  => __( 'Shop and save big on the hottest tablets', '2acetech' ),
			'cta'    => __( 'Shop now', '2acetech' ),
			'url'    => ace_category_url( 'phones', $shop ),
			'art'    => 'tablet',
			'tone'   => 'light',
		),
		array(
			'kicker' => __( 'Gaming', '2acetech' ),
			'title'  => __( 'Level up your setup with new consoles', '2acetech' ),
			'cta'    => __( 'Explore gaming', '2acetech' ),
			'url'    => ace_category_url( 'gaming', $shop ),
			'art'    => 'console',
			'tone'   => 'dark',
		),
	);
}

/**
 * Promo banners as configured. A banner with no title is hidden.
 * Filter: ace_promos.
 *
 * @return array[]
 */
function ace_promos() {
	$promos = array();
	foreach ( ace_promo_defaults() as $i => $d ) {
		$n = $i + 1;
		if ( ! get_theme_mod( "ace_promo{$n}_on", true ) ) {
			continue;
		}
		$promos[] = array(
			'kicker' => get_theme_mod( "ace_promo{$n}_kicker", $d['kicker'] ),
			'title'  => get_theme_mod( "ace_promo{$n}_title", $d['title'] ),
			'cta'    => get_theme_mod( "ace_promo{$n}_cta", $d['cta'] ),
			'url'    => get_theme_mod( "ace_promo{$n}_url", $d['url'] ),
			'image'  => (int) get_theme_mod( "ace_promo{$n}_image", 0 ),
			'art'    => get_theme_mod( "ace_promo{$n}_art", $d['art'] ),
			'tone'   => get_theme_mod( "ace_promo{$n}_tone", $d['tone'] ),
		);
	}
	return apply_filters( 'ace_promos', $promos );
}

/**
 * Default trust strip items: icon, title, text.
 *
 * @return array[]
 */
function ace_trust_defaults() {
	return array(
		array( 'truck', __( 'Fast delivery', '2acetech' ), __( 'Tracked shipping across Poland', '2acetech' ) ),
		array( 'shield', __( 'Secure checkout', '2acetech' ), __( 'Encrypted, trusted payments', '2acetech' ) ),
		array( 'return', __( 'Easy returns', '2acetech' ), __( '14-day right of withdrawal', '2acetech' ) ),
		array( 'badge', __( 'Warranty included', '2acetech' ), __( 'Manufacturer warranty on every product', '2acetech' ) ),
	);
}

/**
 * Trust strip items as configured. An item with no title is hidden.
 *
 * @return array[] Each: icon, title, text.
 */
function ace_trust_items() {
	$items = array();
	foreach ( ace_trust_defaults() as $i => $d ) {
		$n     = $i + 1;
		$title = trim( (string) get_theme_mod( "ace_trust{$n}_title", $d[1] ) );
		if ( '' === $title ) {
			continue;
		}
		$items[] = array( $d[0], $title, get_theme_mod( "ace_trust{$n}_text", $d[2] ) );
	}
	return $items;
}

/**
 * Landing page sections that can be shown, hidden and re-ordered.
 * The hero is always first.
 *
 * @return string[] key => label
 */
function ace_home_sections() {
	return array(
		'trust'       => __( 'Trust strip', '2acetech' ),
		'categories'  => __( 'Category tiles', '2acetech' ),
		'featured'    => __( 'Featured products', '2acetech' ),
		'promos'      => __( 'Promo banners', '2acetech' ),
		'deal'        => __( 'Deal of the week and product tabs', '2acetech' ),
		'brands'      => __( 'Brand strip', '2acetech' ),
		'tabs'        => __( 'Best deals by category', '2acetech' ),
		'bestsellers' => __( 'Bestsellers', '2acetech' ),
		'newsletter'  => __( 'Newsletter', '2acetech' ),
	);
}

/**
 * Default order, as a comma-separated list.
 *
 * @return string
 */
function ace_home_default_order() {
	return implode( ',', array_keys( ace_home_sections() ) );
}

/**
 * Enabled sections, in display order.
 *
 * @return string[]
 */
function ace_home_order() {
	$saved = (string) get_theme_mod( 'ace_home_order', ace_home_default_order() );
	$keys  = array_filter( array_map( 'trim', explode( ',', $saved ) ) );
	$keys  = array_intersect( $keys, array_keys( ace_home_sections() ) );
	return array_values( array_unique( $keys ) );
}

/**
 * Text defaults for section headings and the newsletter band.
 *
 * @return string[]
 */
function ace_text_defaults() {
	return array(
		'ace_title_categories'  => __( 'Shop by category', '2acetech' ),
		'ace_title_featured'    => __( 'Featured products', '2acetech' ),
		'ace_title_deals'       => __( 'Best deals', '2acetech' ),
		'ace_title_best'        => __( 'Bestsellers', '2acetech' ),
		'ace_newsletter_title'  => __( 'Get the drop before everyone else', '2acetech' ),
		'ace_newsletter_text'   => __( 'New arrivals and deals, straight to your inbox.', '2acetech' ),
		'ace_footer_tagline'    => __( 'Premium electronics, delivered fast across Poland.', '2acetech' ),
	);
}

/**
 * Read one of the heading/text settings.
 *
 * @param string $key Setting id from ace_text_defaults().
 * @return string
 */
function ace_text( $key ) {
	$defaults = ace_text_defaults();
	return (string) get_theme_mod( $key, isset( $defaults[ $key ] ) ? $defaults[ $key ] : '' );
}

/**
 * Render one landing page section by key.
 *
 * @param string $key Key from ace_home_sections().
 */
function ace_render_home_section( $key ) {
	switch ( $key ) {
		case 'trust':
			get_template_part( 'template-parts/trust' );
			break;
		case 'categories':
			get_template_part( 'template-parts/categories' );
			break;
		case 'featured':
			get_template_part( 'template-parts/product-rail', null, array( 'title' => ace_text( 'ace_title_featured' ), 'type' => 'featured', 'limit' => 10 ) );
			break;
		case 'promos':
			get_template_part( 'template-parts/promos' );
			break;
		case 'deal':
			get_template_part( 'template-parts/deal' );
			break;
		case 'brands':
			get_template_part( 'template-parts/brands' );
			break;
		case 'tabs':
			get_template_part( 'template-parts/category-tabs' );
			break;
		case 'bestsellers':
			get_template_part( 'template-parts/product-rail', null, array( 'title' => ace_text( 'ace_title_best' ), 'type' => 'best', 'limit' => 10 ) );
			break;
		case 'newsletter':
			get_template_part( 'template-parts/newsletter' );
			break;
	}
}
