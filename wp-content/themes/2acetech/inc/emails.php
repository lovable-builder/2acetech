<?php
/**
 * Branded WooCommerce e-mails (order confirmations, new account, password reset, ...).
 *
 * WooCommerce's e-mails are plain black-and-white. Without touching the templates this gives them the
 * shop's look: the logo in a white header with a red line, red links and headings, and the seller's
 * details in the footer (name, address, KRS / NIP / REGON, contact), as the law expects on trade
 * e-mails. Anything set in WooCommerce > Settings > Emails (a header image, other colours, an own
 * footer text) wins over these defaults.
 *
 * @package 2acetech
 */

defined( 'ABSPATH' ) || exit;

/** Logo for the e-mail header: the Customizer logo, else the theme's logo.png. */
function ace_email_logo_url() {
	$id = (int) get_theme_mod( 'custom_logo' );
	if ( $id ) {
		$url = wp_get_attachment_image_url( $id, 'medium' );
		if ( $url ) {
			return $url;
		}
	}
	return file_exists( ACE_DIR . '/assets/img/logo.png' ) ? ACE_URI . '/assets/img/logo.png' : '';
}

/** Header image: the logo unless one is set in the WooCommerce e-mail settings. */
function ace_email_header_image( $value ) {
	return $value ? $value : ace_email_logo_url();
}
add_filter( 'option_woocommerce_email_header_image', 'ace_email_header_image' );

/** Links and headings: brand red instead of the WooCommerce default colours. */
function ace_email_base_color( $value ) {
	$value = strtolower( (string) $value );
	return in_array( $value, array( '', '#111111', '#7f54b3', '#96588a', '#720eec' ), true ) ? '#f50505' : $value;
}
add_filter( 'option_woocommerce_email_base_color', 'ace_email_base_color' );

/** Footer: who is writing (only while the footer is still the WooCommerce default). */
function ace_email_footer_text( $value ) {
	$value = trim( (string) $value );
	if ( '' !== $value && '{site_title}<br />{store_address}' !== $value && '{site_title} &mdash; Built with {WooCommerce}' !== $value ) {
		return $value;
	}
	$seller  = trim( (string) get_theme_mod( 'ace_seller', '2ACE sp. z o.o.' ) );
	$address = trim( (string) get_theme_mod( 'ace_address', 'ul. Ostrobramska 101A lok. 301, 04-041 Warszawa' ) );
	$ids     = trim( (string) get_theme_mod( 'ace_seller_id', 'KRS 0001267111 · NIP 1133212948 · REGON 545746743' ) );
	$email   = trim( (string) get_theme_mod( 'ace_email', 'info@2acetech.pl' ) );
	$phone   = trim( (string) get_theme_mod( 'ace_phone', '' ) );

	$lines = array();
	if ( '' !== $seller || '' !== $address ) {
		$lines[] = esc_html( trim( $seller . ( '' !== $seller && '' !== $address ? ', ' : '' ) . $address ) );
	}
	if ( '' !== $ids ) {
		$lines[] = esc_html( $ids );
	}
	$contact = array_filter( array( $email, $phone ) );
	if ( $contact ) {
		$lines[] = esc_html( implode( ' · ', $contact ) );
	}
	$lines[] = esc_html( preg_replace( '#^https?://#', '', home_url() ) );
	return implode( '<br />', $lines );
}
add_filter( 'option_woocommerce_email_footer_text', 'ace_email_footer_text' );

/** Header look: white with a red line (the logo is dark), buttons in brand red. */
function ace_email_styles( $css ) {
	return $css . '
#template_header { background-color: #ffffff !important; color: #0b0b0d !important; border-bottom: 4px solid #f50505 !important; }
#template_header h1, #template_header h1 a { color: #0b0b0d !important; text-shadow: none !important; }
#template_header_image img { margin: 0 0 14px 0 !important; }
#template_footer #credit, #template_footer #credit p { color: #62626c !important; font-size: 12px !important; line-height: 1.6 !important; }
';
}
add_filter( 'woocommerce_email_styles', 'ace_email_styles' );
