<?php
/**
 * Customizer settings: store details, footer logo, newsletter, deal timer.
 *
 * @package 2acetech
 */

defined( 'ABSPATH' ) || exit;

/**
 * Seller details, as verified in the Ministry of Finance white list and the
 * KRS register (3 November 2026). Shown in the footer and on the Contact page
 * until overridden in Customize > Store details.
 *
 * @return string[]
 */
function ace_seller_defaults() {
	return array(
		'ace_seller'    => '2ACE sp. z o.o.',
		'ace_seller_id' => 'KRS 0001267111 · NIP 1133212948 · REGON 545746743',
		'ace_address'   => 'ul. Ostrobramska 101A lok. 301, 04-041 Warszawa',
	);
}

foreach ( ace_seller_defaults() as $ace_key => $ace_default ) {
	add_filter(
		"theme_mod_{$ace_key}",
		static function ( $value ) use ( $ace_default ) {
			return ( false === $value || '' === trim( (string) $value ) ) ? $ace_default : $value;
		}
	);
}

/**
 * Register Customizer controls.
 *
 * @param WP_Customize_Manager $wp_customize Customizer.
 */
function ace_customize_register( $wp_customize ) {
	$wp_customize->add_section(
		'ace_store',
		array(
			'title'    => __( 'Store details', '2acetech' ),
			'priority' => 30,
		)
	);

	$text_fields = array(
		'ace_topbar'    => array( __( 'Top bar message', '2acetech' ), __( 'Fast, tracked delivery across Poland', '2acetech' ), 'text' ),
		'ace_phone'     => array( __( 'Phone', '2acetech' ), '', 'text' ),
		'ace_email'     => array( __( 'Email', '2acetech' ), 'info@2acetech.pl', 'text' ),
		'ace_address'   => array( __( 'Address', '2acetech' ), ace_seller_defaults()['ace_address'], 'text' ),
		'ace_seller'    => array( __( 'Seller: company name', '2acetech' ), ace_seller_defaults()['ace_seller'], 'text' ),
		'ace_seller_id' => array( __( 'Seller: NIP / tax ID and registry number', '2acetech' ), ace_seller_defaults()['ace_seller_id'], 'text' ),
		'ace_facebook'  => array( __( 'Facebook URL', '2acetech' ), '', 'url' ),
		'ace_instagram' => array( __( 'Instagram URL', '2acetech' ), '', 'url' ),
		'ace_x'         => array( __( 'X (Twitter) URL', '2acetech' ), '', 'url' ),
		'ace_youtube'   => array( __( 'YouTube URL', '2acetech' ), '', 'url' ),
	);

	foreach ( $text_fields as $id => $field ) {
		$wp_customize->add_setting(
			$id,
			array(
				'default'           => $field[1],
				'sanitize_callback' => 'url' === $field[2] ? 'esc_url_raw' : 'sanitize_text_field',
			)
		);
		$wp_customize->add_control(
			$id,
			array(
				'label'   => $field[0],
				'section' => 'ace_store',
				'type'    => $field[2],
			)
		);
	}

	$wp_customize->add_setting( 'ace_footer_logo', array( 'sanitize_callback' => 'absint' ) );
	$wp_customize->add_control(
		new WP_Customize_Media_Control(
			$wp_customize,
			'ace_footer_logo',
			array(
				'label'       => __( 'Footer logo (light version)', '2acetech' ),
				'description' => __( 'Used on the dark footer. Upload a version with white lettering.', '2acetech' ),
				'section'     => 'ace_store',
				'mime_type'   => 'image',
			)
		)
	);

	$wp_customize->add_setting( 'ace_og_image', array( 'sanitize_callback' => 'absint' ) );
	$wp_customize->add_control(
		new WP_Customize_Media_Control(
			$wp_customize,
			'ace_og_image',
			array(
				'label'       => __( 'Link-sharing image', '2acetech' ),
				'description' => __( 'Shown when the site is shared on WhatsApp, Facebook, LinkedIn or X. 1200 x 630 px. Leave empty to use the built-in image. Product pages use the product photo.', '2acetech' ),
				'section'     => 'ace_store',
				'mime_type'   => 'image',
			)
		)
	);

	$wp_customize->add_setting( 'ace_deal_end', array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ) );
	$wp_customize->add_control(
		'ace_deal_end',
		array(
			'label'       => __( 'Deal countdown end', '2acetech' ),
			'description' => __( 'Date and time, e.g. 2026-12-24 23:59. Leave empty to count down to the end of the current week.', '2acetech' ),
			'section'     => 'ace_store',
			'type'        => 'text',
		)
	);

	$wp_customize->add_setting( 'ace_newsletter_shortcode', array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ) );
	$wp_customize->add_control(
		'ace_newsletter_shortcode',
		array(
			'label'       => __( 'Newsletter form shortcode', '2acetech' ),
			'description' => __( 'Paste a shortcode from your mailing tool (Mailchimp for WP, FluentCRM, ...). The newsletter band is shown only when this is set, because a newsletter needs a consent record and an unsubscribe link.', '2acetech' ),
			'section'     => 'ace_store',
			'type'        => 'text',
		)
	);
}
add_action( 'customize_register', 'ace_customize_register' );

/**
 * Customizer: what the product page says around the buy button.
 *
 * @param WP_Customize_Manager $wp_customize Customizer.
 */
function ace_customize_product( $wp_customize ) {
	$wp_customize->add_section(
		'ace_product',
		array(
			'title'       => __( 'Product page', '2acetech' ),
			'description' => __( 'The reassurance lines under the buy button, and the stock note. Only state what you really offer. An empty title hides a line.', '2acetech' ),
			'priority'    => 31,
		)
	);

	ace_cz_field(
		$wp_customize,
		'ace_product',
		'ace_dispatch_note',
		__( 'Dispatch note', '2acetech' ),
		'',
		array( 'description' => __( 'Added after "In stock", for example "ships within 24 h". Empty shows nothing.', '2acetech' ) )
	);

	foreach ( ace_perk_defaults() as $i => $d ) {
		$n = $i + 1;
		/* translators: %d: line number. */
		ace_cz_field( $wp_customize, 'ace_product', "ace_perk{$n}_title", sprintf( __( 'Line %d: title', '2acetech' ), $n ), $d[1] );
		ace_cz_field( $wp_customize, 'ace_product', "ace_perk{$n}_text", sprintf( __( 'Line %d: small text', '2acetech' ), $n ), $d[2] );
	}
}
add_action( 'customize_register', 'ace_customize_product' );
