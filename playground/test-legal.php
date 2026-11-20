<?php
$out = array();
try {
	wp_set_current_user( 1 );
	// Simulate an old unedited draft from an earlier theme version.
	$old = file_get_contents( '/wordpress/wp-content/themes/2acetech/legal/regulamin-sklepu.html' );
	ace_maybe_create_legal_pages();
	$out['pages'] = array();
	foreach ( ace_legal_defs() as $k => $d ) { $id = ace_legal_page_id( $k ); $out['pages'][ $k ] = $id ? get_post_status( $id ) : 'MISSING'; }
	$out['terms option'] = (int) get_option( 'woocommerce_terms_page_id' ) === ace_legal_page_id( 'terms' );
	$out['privacy option'] = (int) get_option( 'wp_page_for_privacy_policy' ) === ace_legal_page_id( 'privacy' );
	$out['wp_page_for_privacy still privacy page (not clobbered by other pages)'] = ace_legal_page_id( 'privacy' ) === (int) get_option( 'wp_page_for_privacy_policy' );

	// Old-hash replacement: put an old template in the terms draft, re-run.
	$tid = ace_legal_page_id( 'terms' );
	wp_update_post( array( 'ID' => $tid, 'post_content' => 'owner edited text' ) );
	ace_maybe_create_legal_pages();
	$out['edited draft kept'] = get_post( $tid )->post_content === 'owner edited text';
	wp_update_post( array( 'ID' => $tid, 'post_content' => $old ) );
	update_post_meta( $tid, '_ace_legal_seed', md5( $old ) );
	ace_maybe_create_legal_pages();
	$out['unedited draft same as seed untouched'] = get_post( $tid )->post_content === $old;

	// Footer links before publishing: only cookie button.
	$out['footer links (all draft)'] = array_map( 'wp_strip_all_tags', ace_footer_legal_links() );
	// Publish terms only.
	wp_update_post( array( 'ID' => $tid, 'post_status' => 'publish' ) );
	$out['footer links (terms published)'] = array_map( 'wp_strip_all_tags', ace_footer_legal_links() );
	$out['footer raw (terms)'] = ace_footer_legal_links();
	// Publish all.
	foreach ( array_keys( ace_legal_defs() ) as $k ) { wp_update_post( array( 'ID' => ace_legal_page_id( $k ), 'post_status' => 'publish' ) ); }
	$out['footer links (all published)'] = array_map( 'wp_strip_all_tags', ace_footer_legal_links() );
	$out['cookie policy url'] = ace_cookie_policy_url();

	// Checkout terms block.
	$b = ace_checkout_terms_block( array( 'blockName' => 'woocommerce/checkout-terms-block', 'attrs' => array() ) );
	$out['terms block attrs'] = $b['attrs'];
	$other = ace_checkout_terms_block( array( 'blockName' => 'core/paragraph', 'attrs' => array() ) );
	$out['other block untouched'] = $other['attrs'] === array();
	wp_update_post( array( 'ID' => $tid, 'post_status' => 'draft' ) );
	$b2 = ace_checkout_terms_block( array( 'blockName' => 'woocommerce/checkout-terms-block', 'attrs' => array() ) );
	$out['terms draft -> no checkbox'] = $b2['attrs'] === array();

	// Consent default script.
	ob_start(); ace_consent_google_default(); $out['gtag default (admin => none)'] = trim( ob_get_clean() );
	// Brand strip and rails on an empty real store.
	$out['brands in non-demo'] = count( ace_brand_list() );
	$out['is_demo'] = ace_is_demo();
	$out['trust'] = ace_trust_items();
	$out['perks'] = ace_perks();
	$out['tagline'] = ace_text( 'ace_footer_tagline' );
} catch ( Throwable $e ) { $out['ERROR'] = $e->getMessage() . ' @ ' . basename( $e->getFile() ) . ':' . $e->getLine(); }
file_put_contents( '/playground/_legal-result.json', wp_json_encode( $out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
