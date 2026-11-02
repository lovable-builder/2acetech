<?php
/**
 * Customizer: Landing page panel.
 *
 * Hero scenes, promo banners, trust strip, section order and headings. The
 * values are read back through inc/home.php.
 *
 * @package 2acetech
 */

defined( 'ABSPATH' ) || exit;

/* Sanitizers -------------------------------------------------------------- */

/**
 * @param mixed $value Raw.
 * @return bool
 */
function ace_sanitize_checkbox( $value ) {
	return (bool) $value;
}

/**
 * @param string $value Raw.
 * @return string
 */
function ace_sanitize_tone( $value ) {
	return isset( ace_tone_choices()[ $value ] ) ? $value : 'ember';
}

/**
 * @param string $value Raw.
 * @return string
 */
function ace_sanitize_art( $value ) {
	return isset( ace_art_choices()[ $value ] ) ? $value : 'watch';
}

/**
 * @param string $value Raw.
 * @return string
 */
function ace_sanitize_promo_tone( $value ) {
	return in_array( $value, array( 'light', 'dark' ), true ) ? $value : 'light';
}

/**
 * Keep only known section keys, in the order given.
 *
 * @param string $value Raw comma-separated list.
 * @return string
 */
function ace_sanitize_order( $value ) {
	$keys = array_filter( array_map( 'sanitize_key', explode( ',', (string) $value ) ) );
	$keys = array_intersect( $keys, array_keys( ace_home_sections() ) );
	return implode( ',', array_unique( $keys ) );
}

/* Helpers ----------------------------------------------------------------- */

/**
 * Add a setting and its control in one go.
 *
 * @param WP_Customize_Manager $wp      Customizer.
 * @param string               $section Section id.
 * @param string               $id      Setting id.
 * @param string               $label   Label.
 * @param mixed                $default Default value (same one the front end falls back to).
 * @param array                $args    type, choices, description, sanitize.
 */
function ace_cz_field( $wp, $section, $id, $label, $default, $args = array() ) {
	$type = isset( $args['type'] ) ? $args['type'] : 'text';

	if ( isset( $args['sanitize'] ) ) {
		$sanitize = $args['sanitize'];
	} elseif ( 'url' === $type ) {
		$sanitize = 'esc_url_raw';
	} elseif ( 'textarea' === $type ) {
		$sanitize = 'sanitize_textarea_field';
	} elseif ( 'checkbox' === $type ) {
		$sanitize = 'ace_sanitize_checkbox';
	} else {
		$sanitize = 'sanitize_text_field';
	}

	$wp->add_setting( $id, array( 'default' => $default, 'sanitize_callback' => $sanitize ) );

	$control = array( 'label' => $label, 'section' => $section, 'type' => $type );
	foreach ( array( 'choices', 'description' ) as $extra ) {
		if ( isset( $args[ $extra ] ) ) {
			$control[ $extra ] = $args[ $extra ];
		}
	}
	$wp->add_control( $id, $control );
}

/**
 * Add an image picker.
 *
 * @param WP_Customize_Manager $wp          Customizer.
 * @param string               $section     Section id.
 * @param string               $id          Setting id.
 * @param string               $label       Label.
 * @param string               $description Help text.
 */
function ace_cz_image( $wp, $section, $id, $label, $description = '' ) {
	$wp->add_setting( $id, array( 'default' => 0, 'sanitize_callback' => 'absint' ) );
	$wp->add_control(
		new WP_Customize_Media_Control(
			$wp,
			$id,
			array(
				'label'       => $label,
				'description' => $description,
				'section'     => $section,
				'mime_type'   => 'image',
			)
		)
	);
}

/* Panel ------------------------------------------------------------------- */

/**
 * Register the Landing page panel.
 *
 * @param WP_Customize_Manager $wp Customizer.
 */
function ace_customize_home( $wp ) {
	require_once ACE_DIR . '/inc/class-ace-sections-control.php';

	$wp->add_panel(
		'ace_home',
		array(
			'title'       => __( 'Landing page', '2acetech' ),
			'description' => __( 'Edit the homepage: hero scenes, banners, trust strip, headings and section order. Products, categories, brands and the deal of the week come from WooCommerce.', '2acetech' ),
			'priority'    => 25,
		)
	);

	/* Hero scenes */
	foreach ( ace_hero_defaults() as $i => $d ) {
		$n   = $i + 1;
		$sec = "ace_hero_{$n}";

		$wp->add_section(
			$sec,
			array(
				/* translators: %d: scene number. */
				'title' => sprintf( __( 'Hero scene %d', '2acetech' ), $n ),
				'panel' => 'ace_home',
			)
		);

		ace_cz_field( $wp, $sec, "ace_hero{$n}_on", __( 'Show this scene', '2acetech' ), true, array( 'type' => 'checkbox' ) );
		ace_cz_field( $wp, $sec, "ace_hero{$n}_eyebrow", __( 'Small label', '2acetech' ), $d['eyebrow'] );
		ace_cz_field( $wp, $sec, "ace_hero{$n}_title", __( 'Headline', '2acetech' ), $d['title'], array( 'description' => __( 'The last word is shown in red.', '2acetech' ) ) );
		ace_cz_field( $wp, $sec, "ace_hero{$n}_text", __( 'Text', '2acetech' ), $d['text'], array( 'type' => 'textarea' ) );
		ace_cz_field( $wp, $sec, "ace_hero{$n}_cta", __( 'Button text', '2acetech' ), $d['cta'], array( 'description' => __( 'Leave empty for no button.', '2acetech' ) ) );
		ace_cz_field( $wp, $sec, "ace_hero{$n}_url", __( 'Button link', '2acetech' ), $d['url'], array( 'type' => 'url' ) );
		ace_cz_image( $wp, $sec, "ace_hero{$n}_image", __( 'Product photo', '2acetech' ), __( 'Best: a transparent PNG, at least 1000 px wide. Empty uses the illustration below.', '2acetech' ) );
		ace_cz_field( $wp, $sec, "ace_hero{$n}_art", __( 'Illustration (no photo)', '2acetech' ), $d['art'], array( 'type' => 'select', 'choices' => ace_art_choices(), 'sanitize' => 'ace_sanitize_art' ) );
		ace_cz_field( $wp, $sec, "ace_hero{$n}_tone", __( 'Background glow', '2acetech' ), $d['tone'], array( 'type' => 'select', 'choices' => ace_tone_choices(), 'sanitize' => 'ace_sanitize_tone' ) );
		foreach ( array( 1, 2, 3 ) as $c ) {
			ace_cz_field(
				$wp,
				$sec,
				"ace_hero{$n}_chip{$c}",
				/* translators: %d: chip number. */
				sprintf( __( 'Floating tag %d', '2acetech' ), $c ),
				$d['chips'][ $c - 1 ],
				array( 'description' => 1 === $c ? __( 'Short facts around the product. Empty hides it. Only state facts that are true.', '2acetech' ) : '' )
			);
		}
	}

	/* Promo banners */
	$wp->add_section( 'ace_promos', array( 'title' => __( 'Promo banners', '2acetech' ), 'panel' => 'ace_home' ) );
	foreach ( ace_promo_defaults() as $i => $d ) {
		$n = $i + 1;
		/* translators: %d: banner number. */
		ace_cz_field( $wp, 'ace_promos', "ace_promo{$n}_on", sprintf( __( 'Banner %d: show', '2acetech' ), $n ), true, array( 'type' => 'checkbox' ) );
		ace_cz_field( $wp, 'ace_promos', "ace_promo{$n}_kicker", sprintf( __( 'Banner %d: small label', '2acetech' ), $n ), $d['kicker'] );
		ace_cz_field( $wp, 'ace_promos', "ace_promo{$n}_title", sprintf( __( 'Banner %d: headline', '2acetech' ), $n ), $d['title'] );
		ace_cz_field( $wp, 'ace_promos', "ace_promo{$n}_cta", sprintf( __( 'Banner %d: button text', '2acetech' ), $n ), $d['cta'] );
		ace_cz_field( $wp, 'ace_promos', "ace_promo{$n}_url", sprintf( __( 'Banner %d: link', '2acetech' ), $n ), $d['url'], array( 'type' => 'url' ) );
		ace_cz_image( $wp, 'ace_promos', "ace_promo{$n}_image", sprintf( __( 'Banner %d: product photo', '2acetech' ), $n ), __( 'Transparent PNG works best. Empty uses the illustration.', '2acetech' ) );
		ace_cz_field( $wp, 'ace_promos', "ace_promo{$n}_art", sprintf( __( 'Banner %d: illustration (no photo)', '2acetech' ), $n ), $d['art'], array( 'type' => 'select', 'choices' => ace_art_choices(), 'sanitize' => 'ace_sanitize_art' ) );
		ace_cz_field( $wp, 'ace_promos', "ace_promo{$n}_tone", sprintf( __( 'Banner %d: style', '2acetech' ), $n ), $d['tone'], array( 'type' => 'select', 'choices' => array( 'light' => __( 'Light', '2acetech' ), 'dark' => __( 'Dark', '2acetech' ) ), 'sanitize' => 'ace_sanitize_promo_tone' ) );
	}

	/* Trust strip */
	$wp->add_section(
		'ace_trust',
		array(
			'title'       => __( 'Trust strip', '2acetech' ),
			'description' => __( 'Four short promises under the hero. Empty title hides an item. Only promise what you will deliver.', '2acetech' ),
			'panel'       => 'ace_home',
		)
	);
	foreach ( ace_trust_defaults() as $i => $d ) {
		$n = $i + 1;
		/* translators: %d: item number. */
		ace_cz_field( $wp, 'ace_trust', "ace_trust{$n}_title", sprintf( __( 'Item %d title', '2acetech' ), $n ), $d[1] );
		ace_cz_field( $wp, 'ace_trust', "ace_trust{$n}_text", sprintf( __( 'Item %d text', '2acetech' ), $n ), $d[2] );
	}

	/* Section order */
	$wp->add_section(
		'ace_layout',
		array(
			'title'       => __( 'Sections', '2acetech' ),
			'description' => __( 'The hero is always first. Tick a section to show it, drag to re-order.', '2acetech' ),
			'panel'       => 'ace_home',
		)
	);
	$wp->add_setting( 'ace_home_order', array( 'default' => ace_home_default_order(), 'sanitize_callback' => 'ace_sanitize_order' ) );
	$wp->add_control(
		new ACE_Sections_Control(
			$wp,
			'ace_home_order',
			array(
				'label'   => __( 'Homepage sections', '2acetech' ),
				'section' => 'ace_layout',
			)
		)
	);

	/* Headings and texts */
	$wp->add_section( 'ace_texts', array( 'title' => __( 'Headings and texts', '2acetech' ), 'panel' => 'ace_home' ) );
	$labels = array(
		'ace_title_categories' => __( 'Category tiles heading', '2acetech' ),
		'ace_title_featured'   => __( 'Featured products heading', '2acetech' ),
		'ace_title_deals'      => __( 'Best deals heading', '2acetech' ),
		'ace_title_best'       => __( 'Bestsellers heading', '2acetech' ),
		'ace_newsletter_title' => __( 'Newsletter heading', '2acetech' ),
		'ace_newsletter_text'  => __( 'Newsletter text', '2acetech' ),
		'ace_footer_tagline'   => __( 'Footer tagline', '2acetech' ),
	);
	foreach ( ace_text_defaults() as $id => $default ) {
		ace_cz_field( $wp, 'ace_texts', $id, $labels[ $id ], $default );
	}
}
add_action( 'customize_register', 'ace_customize_home' );

/**
 * Customizer controls: styles and the script for sections + preview jump.
 */
function ace_customizer_controls_assets() {
	wp_enqueue_style( 'ace-customizer', ACE_URI . '/assets/css/customizer.css', array(), ace_ver( 'assets/css/customizer.css' ) );
	wp_enqueue_script( 'ace-customizer', ACE_URI . '/assets/js/customizer.js', array( 'customize-controls', 'jquery', 'jquery-ui-sortable' ), ace_ver( 'assets/js/customizer.js' ), true );
}
add_action( 'customize_controls_enqueue_scripts', 'ace_customizer_controls_assets' );
