<?php
/**
 * Richer product content.
 *
 * - "Extra details" panel on the product edit screen: what's in the box, FAQ.
 * - Those become tabs next to Description, Specification (the WooCommerce
 *   attributes table) and Reviews.
 * - A short list of key specs under the short description, from the
 *   attributes marked "Visible on the product page".
 *
 * @package 2acetech
 */

defined( 'ABSPATH' ) || exit;

/* Product edit screen ------------------------------------------------------- */

/**
 * Add the "Extra details" tab to the Product data box.
 *
 * @param array $tabs Tabs.
 * @return array
 */
function ace_details_tab( $tabs ) {
	$tabs['ace_details'] = array(
		'label'    => __( 'Extra details', '2acetech' ),
		'target'   => 'ace_details_data',
		'class'    => array(),
		'priority' => 80,
	);
	return $tabs;
}
add_filter( 'woocommerce_product_data_tabs', 'ace_details_tab' );

/**
 * The "Extra details" fields.
 */
function ace_details_panel() {
	echo '<div id="ace_details_data" class="panel woocommerce_options_panel"><div class="options_group">';

	woocommerce_wp_textarea_input(
		array(
			'id'          => '_ace_box',
			'label'       => __( 'What\'s in the box', '2acetech' ),
			'description' => __( 'One item per line, for example "4 x aluminium LED strips". Shows as the "What\'s in the box" tab. Leave empty to hide the tab.', '2acetech' ),
			'desc_tip'    => false,
			'class'       => '',
			'style'       => 'width: 70%; min-height: 140px;',
		)
	);

	woocommerce_wp_textarea_input(
		array(
			'id'          => '_ace_faq',
			'label'       => __( 'Questions and answers', '2acetech' ),
			'description' => __( 'A question on one line, its answer on the next line(s), then a blank line before the next question. Plain text. Shows as the "FAQ" tab. Leave empty to hide the tab.', '2acetech' ),
			'desc_tip'    => false,
			'class'       => '',
			'style'       => 'width: 70%; min-height: 220px;',
		)
	);

	echo '</div></div>';
}
add_action( 'woocommerce_product_data_panels', 'ace_details_panel' );

/**
 * Save the fields. WooCommerce has already checked the nonce and rights.
 *
 * @param WC_Product $product Product being saved.
 */
function ace_details_save( $product ) {
	foreach ( array( '_ace_box', '_ace_faq' ) as $key ) {
		$value = isset( $_POST[ $key ] ) ? sanitize_textarea_field( wp_unslash( $_POST[ $key ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$product->update_meta_data( $key, $value );
	}
}
add_action( 'woocommerce_admin_process_product_object', 'ace_details_save' );

/* Readers ------------------------------------------------------------------ */

/**
 * Items for the "What's in the box" tab.
 *
 * @param WC_Product $product Product.
 * @return string[]
 */
function ace_box_items( $product ) {
	$lines = array_map( 'trim', preg_split( '/\R/', (string) $product->get_meta( '_ace_box' ) ) );
	return array_values( array_filter( $lines, 'strlen' ) );
}

/**
 * Question and answer pairs for the FAQ tab.
 *
 * @param WC_Product $product Product.
 * @return array[] Each: question, answer.
 */
function ace_faq_items( $product ) {
	$out  = array();
	$text = trim( (string) $product->get_meta( '_ace_faq' ) );
	if ( '' === $text ) {
		return $out;
	}

	foreach ( preg_split( '/\R\s*\R/', $text ) as $block ) {
		$lines = array_values( array_filter( array_map( 'trim', preg_split( '/\R/', $block ) ), 'strlen' ) );
		if ( count( $lines ) >= 2 ) {
			$question = array_shift( $lines );
			$out[]    = array( $question, implode( "\n", $lines ) );
		}
	}
	return $out;
}

/* Tabs --------------------------------------------------------------------- */

/**
 * Rename and add product tabs.
 *
 * @param array $tabs Tabs.
 * @return array
 */
function ace_product_tabs( $tabs ) {
	global $product;

	if ( isset( $tabs['additional_information'] ) ) {
		$tabs['additional_information']['title'] = __( 'Specification', '2acetech' );
	}

	if ( $product instanceof WC_Product ) {
		if ( ace_box_items( $product ) ) {
			$tabs['ace_box'] = array(
				'title'    => __( 'What\'s in the box', '2acetech' ),
				'priority' => 22,
				'callback' => 'ace_box_tab',
			);
		}
		if ( ace_faq_items( $product ) ) {
			$tabs['ace_faq'] = array(
				'title'    => __( 'FAQ', '2acetech' ),
				'priority' => 25,
				'callback' => 'ace_faq_tab',
			);
		}
	}

	return $tabs;
}
add_filter( 'woocommerce_product_tabs', 'ace_product_tabs', 20 );

/**
 * "What's in the box" tab content.
 */
function ace_box_tab() {
	global $product;
	echo '<h2>' . esc_html__( 'What\'s in the box', '2acetech' ) . '</h2><ol class="inbox">';
	foreach ( ace_box_items( $product ) as $item ) {
		echo '<li>' . esc_html( $item ) . '</li>';
	}
	echo '</ol>';
}

/**
 * FAQ tab content: one collapsible question per pair.
 */
function ace_faq_tab() {
	global $product;
	echo '<h2>' . esc_html__( 'Frequently asked questions', '2acetech' ) . '</h2><div class="faq">';
	foreach ( ace_faq_items( $product ) as $pair ) {
		printf(
			'<details><summary>%1$s</summary>%2$s</details>',
			esc_html( $pair[0] ),
			wp_kses_post( wpautop( esc_html( $pair[1] ) ) )
		);
	}
	echo '</div>';
}

/* Key specs ---------------------------------------------------------------- */

/**
 * Up to four attributes (visible on the product page, not used for variations)
 * as a short list under the short description.
 */
function ace_single_specs() {
	global $product;
	$rows = array();

	foreach ( $product->get_attributes() as $attribute ) {
		if ( ! $attribute->get_visible() || $attribute->get_variation() ) {
			continue;
		}
		$value = wp_strip_all_tags( $product->get_attribute( $attribute->get_name() ) );
		if ( '' === $value ) {
			continue;
		}
		$rows[] = array( wc_attribute_label( $attribute->get_name(), $product ), $value );
		if ( count( $rows ) >= 4 ) {
			break;
		}
	}

	if ( ! $rows ) {
		return;
	}

	echo '<ul class="specs">';
	foreach ( $rows as $row ) {
		printf( '<li><span>%1$s</span><b>%2$s</b></li>', esc_html( $row[0] ), esc_html( $row[1] ) );
	}
	echo '</ul>';
}
add_action( 'woocommerce_single_product_summary', 'ace_single_specs', 22 );
