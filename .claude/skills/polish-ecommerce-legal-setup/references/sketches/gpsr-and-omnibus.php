<?php
/**
 * SKETCH (not deployed on 2acetech; PHP syntax checked in WordPress Playground on 2026-11-21, behaviour untested).
 *
 * 1. GPSR product-safety fields (Reg. (EU) 2023/988 art. 19) with a publish blocker.
 * 2. Omnibus: log every price change and show the lowest price of the 30 days before a reduction
 *    (art. 4 ust. 2-3 ustawy o informowaniu o cenach towarów i usług; CJEU C-330/23).
 *
 * Drop into a child theme / must-use plugin, rename the "ace_" prefix, test in Playground
 * (references/verification-and-tests.md section 9) and have a lawyer confirm the Omnibus series rule.
 * Text domain: replace 'shop-legal' with yours.
 *
 * @package shop-legal
 */

defined( 'ABSPATH' ) || exit;

/* ------------------------------------------------------------------------ GPSR */

/**
 * Fields. 'req' = needed before a product may be public; 'eu_only' = needed only when the
 * manufacturer is outside the EU (checkbox _gpsr_outside_eu).
 *
 * @return array[]
 */
function ace_gpsr_fields() {
	return array(
		'manufacturer_name'    => array( 'label' => __( 'Manufacturer: name', 'shop-legal' ), 'req' => true ),
		'manufacturer_address' => array( 'label' => __( 'Manufacturer: postal address', 'shop-legal' ), 'req' => true ),
		'manufacturer_email'   => array( 'label' => __( 'Manufacturer: e-mail or web contact', 'shop-legal' ), 'req' => true ),
		'eu_rp_name'           => array( 'label' => __( 'EU responsible person: name', 'shop-legal' ), 'eu_only' => true ),
		'eu_rp_address'        => array( 'label' => __( 'EU responsible person: postal address', 'shop-legal' ), 'eu_only' => true ),
		'eu_rp_email'          => array( 'label' => __( 'EU responsible person: e-mail', 'shop-legal' ), 'eu_only' => true ),
		'model'                => array( 'label' => __( 'Type / model', 'shop-legal' ), 'req' => true ),
		'batch'                => array( 'label' => __( 'Batch / serial identifier', 'shop-legal' ), 'req' => true ),
		'ce'                   => array( 'label' => __( 'CE marking present (yes/no)', 'shop-legal' ), 'req' => true ),
		'doc_url'              => array( 'label' => __( 'Declaration of conformity (URL)', 'shop-legal' ) ),
		'warnings'             => array( 'label' => __( 'Warnings and safety information (Polish, as on the packaging)', 'shop-legal' ), 'req' => true ),
		'manual_url'           => array( 'label' => __( 'Instructions in Polish (URL)', 'shop-legal' ), 'req' => true ),
		'min_age'              => array( 'label' => __( 'Toys: minimum age (leave empty if not a toy)', 'shop-legal' ) ),
	);
}

/** Admin: fields in the product data "General" tab. */
function ace_gpsr_admin_fields() {
	echo '<div class="options_group"><p><strong>' . esc_html__( 'Product safety (GPSR) - required before publishing', 'shop-legal' ) . '</strong></p>';
	woocommerce_wp_checkbox(
		array(
			'id'    => '_gpsr_outside_eu',
			'label' => __( 'Manufacturer is outside the EU', 'shop-legal' ),
		)
	);
	foreach ( ace_gpsr_fields() as $key => $field ) {
		woocommerce_wp_text_input(
			array(
				'id'    => '_gpsr_' . $key,
				'label' => $field['label'],
			)
		);
	}
	echo '</div>';
}
add_action( 'woocommerce_product_options_general_product_data', 'ace_gpsr_admin_fields' );

/**
 * Save onto the product object, before WooCommerce saves it, so the publish check below sees the new values.
 *
 * @param WC_Product $product Product.
 */
function ace_gpsr_save( $product ) {
	// WooCommerce has verified the product-edit nonce before this action runs.
	// phpcs:disable WordPress.Security.NonceVerification
	foreach ( array_keys( ace_gpsr_fields() ) as $key ) {
		if ( isset( $_POST[ '_gpsr_' . $key ] ) ) {
			$product->update_meta_data( '_gpsr_' . $key, sanitize_text_field( wp_unslash( $_POST[ '_gpsr_' . $key ] ) ) );
		}
	}
	if ( isset( $_POST['_gpsr_manufacturer_name'] ) ) { // The product form was submitted.
		$product->update_meta_data( '_gpsr_outside_eu', isset( $_POST['_gpsr_outside_eu'] ) ? 'yes' : 'no' );
	}
	// phpcs:enable WordPress.Security.NonceVerification
}
add_action( 'woocommerce_admin_process_product_object', 'ace_gpsr_save' );

/**
 * Required fields that are empty.
 *
 * @param int $product_id Product ID (the parent for a variation).
 * @return string[] Labels.
 */
function ace_gpsr_missing( $product_id ) {
	$outside = 'yes' === get_post_meta( $product_id, '_gpsr_outside_eu', true );
	$missing = array();
	foreach ( ace_gpsr_fields() as $key => $field ) {
		$needed = ! empty( $field['req'] ) || ( $outside && ! empty( $field['eu_only'] ) );
		if ( $needed && '' === trim( (string) get_post_meta( $product_id, '_gpsr_' . $key, true ) ) ) {
			$missing[] = $field['label'];
		}
	}
	return $missing;
}

/**
 * Publish blocker: a product that is published without the data goes back to draft.
 * Runs for the admin form and for plugin imports (they call $product->save()).
 *
 * @param WC_Product $product Product.
 */
function ace_gpsr_block_publish( $product ) {
	static $busy = false;
	if ( $busy || 'publish' !== $product->get_status() ) {
		return;
	}
	$missing = ace_gpsr_missing( $product->get_id() );
	if ( ! $missing ) {
		return;
	}
	$busy = true;
	wp_update_post( array( 'ID' => $product->get_id(), 'post_status' => 'draft' ) );
	$busy = false;
	set_transient( 'ace_gpsr_notice_' . get_current_user_id(), $missing, 120 );
}
add_action( 'woocommerce_after_product_object_save', 'ace_gpsr_block_publish', 99 );

/** Admin notice after a blocked publish. */
function ace_gpsr_notice() {
	$missing = get_transient( 'ace_gpsr_notice_' . get_current_user_id() );
	if ( ! $missing ) {
		return;
	}
	delete_transient( 'ace_gpsr_notice_' . get_current_user_id() );
	printf(
		'<div class="notice notice-error"><p><strong>%1$s</strong> %2$s</p></div>',
		esc_html__( 'Not published: product-safety data is missing.', 'shop-legal' ),
		esc_html( implode( '; ', $missing ) )
	);
}
add_action( 'admin_notices', 'ace_gpsr_notice' );

/** Front end: the block, visible without a click, under the short description. */
function ace_gpsr_block() {
	global $product;
	$id    = $product->get_id();
	$rows  = array();
	$get   = static function ( $key ) use ( $id ) {
		return trim( (string) get_post_meta( $id, '_gpsr_' . $key, true ) );
	};
	$rows[ __( 'Producent', 'shop-legal' ) ] = implode( ', ', array_filter( array( $get( 'manufacturer_name' ), $get( 'manufacturer_address' ), $get( 'manufacturer_email' ) ) ) );
	if ( 'yes' === get_post_meta( $id, '_gpsr_outside_eu', true ) ) {
		$rows[ __( 'Podmiot odpowiedzialny w UE', 'shop-legal' ) ] = implode( ', ', array_filter( array( $get( 'eu_rp_name' ), $get( 'eu_rp_address' ), $get( 'eu_rp_email' ) ) ) );
	}
	$rows[ __( 'Model / identyfikator', 'shop-legal' ) ] = trim( $get( 'model' ) . ' / ' . $get( 'batch' ), ' /' );
	$rows[ __( 'Oznakowanie CE', 'shop-legal' ) ]        = $get( 'ce' );
	$rows[ __( 'Ostrzeżenia', 'shop-legal' ) ]           = $get( 'warnings' );
	if ( $get( 'min_age' ) ) {
		$rows[ __( 'Wiek dziecka (zabawka)', 'shop-legal' ) ] = $get( 'min_age' );
	}
	echo '<section class="gpsr" aria-labelledby="gpsr-title"><h2 id="gpsr-title">' . esc_html__( 'Informacje o bezpieczeństwie i producencie', 'shop-legal' ) . '</h2><dl>';
	foreach ( $rows as $label => $value ) {
		if ( '' !== $value ) {
			printf( '<dt>%1$s</dt><dd>%2$s</dd>', esc_html( $label ), esc_html( $value ) );
		}
	}
	if ( $get( 'doc_url' ) ) {
		printf( '<dt>%1$s</dt><dd><a href="%2$s" rel="noopener">%3$s</a></dd>', esc_html__( 'Deklaracja zgodności', 'shop-legal' ), esc_url( $get( 'doc_url' ) ), esc_html__( 'Pobierz', 'shop-legal' ) );
	}
	if ( $get( 'manual_url' ) ) {
		printf( '<dt>%1$s</dt><dd><a href="%2$s" rel="noopener">%3$s</a></dd>', esc_html__( 'Instrukcja po polsku', 'shop-legal' ), esc_url( $get( 'manual_url' ) ), esc_html__( 'Pobierz', 'shop-legal' ) );
	}
	echo '</dl></section>';
}
add_action( 'woocommerce_single_product_summary', 'ace_gpsr_block', 25 );

/* ---------------------------------------------------------------------- Omnibus */

/**
 * Log the active price whenever it changes: list of [timestamp, price] in meta _ace_price_log.
 *
 * @param WC_Product $product Product or variation.
 */
function ace_price_log( $product ) {
	$price = (string) $product->get_price();
	if ( '' === $price ) {
		return;
	}
	$log  = $product->get_meta( '_ace_price_log', true );
	$log  = is_array( $log ) ? $log : array();
	$last = end( $log );
	if ( $last && (string) $last[1] === $price ) {
		return;
	}
	$log[] = array( time(), $price );
	// Keep 400 days of history plus the entry in force at the start of that window.
	$cut = time() - 400 * DAY_IN_SECONDS;
	while ( count( $log ) > 2 && $log[1][0] < $cut ) {
		array_shift( $log );
	}
	update_post_meta( $product->get_id(), '_ace_price_log', $log ); // Not $product->save(): that would recurse.
}
add_action( 'woocommerce_after_product_object_save', 'ace_price_log', 20 );

/**
 * Lowest price in force during the 30 days before the latest reduction, or null when the
 * current price is not a reduction. (A series of reductions needs a lawyer: art. 4 ust. 4 ff.)
 *
 * @param WC_Product $product Product or variation.
 * @return string|null
 */
function ace_lowest_price_before_reduction( $product ) {
	$log = $product->get_meta( '_ace_price_log', true );
	if ( ! is_array( $log ) || count( $log ) < 2 ) {
		return null;
	}
	$now  = end( $log );
	$prev = $log[ count( $log ) - 2 ];
	if ( (float) $now[1] >= (float) $prev[1] ) {
		return null;
	}
	$from = $now[0] - 30 * DAY_IN_SECONDS;
	$min  = (float) $prev[1]; // The price in force just before the reduction.
	foreach ( $log as $entry ) {
		if ( $entry[0] >= $from && $entry[0] < $now[0] ) {
			$min = min( $min, (float) $entry[1] );
		}
	}
	return (string) $min;
}

/**
 * Show the reference price next to the reduced price.
 *
 * @param string     $html    Price HTML.
 * @param WC_Product $product Product.
 * @return string
 */
function ace_price_html_omnibus( $html, $product ) {
	if ( is_admin() || ! $product->is_on_sale() ) {
		return $html;
	}
	$lowest = ace_lowest_price_before_reduction( $product );
	if ( null === $lowest ) {
		return $html;
	}
	return $html . sprintf(
		'<small class="omnibus">%s</small>',
		/* translators: %s: price. */
		sprintf( esc_html__( 'Najniższa cena z 30 dni przed obniżką: %s', 'shop-legal' ), wp_kses_post( wc_price( $lowest ) ) )
	);
}
add_filter( 'woocommerce_get_price_html', 'ace_price_html_omnibus', 20, 2 );
