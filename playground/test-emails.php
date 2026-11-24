<?php
// Test: branded WooCommerce e-mails + the logged-out My Account layout CSS. Writes /playground/_emails-result.json
$out = array();
try {
	$uid = wp_create_user( 'klient', 'x-' . wp_generate_password(), 'klient@example.com' );
	$e   = WC()->mailer()->get_emails()['WC_Email_Customer_New_Account'];
	$e->object = get_userdata( $uid ); $e->user_login = 'klient'; $e->user_email = 'klient@example.com'; $e->recipient = 'klient@example.com';
	$e->password_generated = true; $e->set_password_url = 'https://2acetech.pl/my-account/lost-password/?key=abc&id=1';
	$html = $e->get_content();
	$out['option: header image = logo'] = false !== strpos( get_option( 'woocommerce_email_header_image' ), 'logo' );
	$out['logo in the e-mail'] = false !== strpos( $html, 'logo.png' ) || false !== stripos( $html, '<img' );
	$css = apply_filters( 'woocommerce_email_styles', '' );
	$out['style rules: white header with red line'] = false !== strpos( $css, '#template_header' ) && false !== strpos( $css, '#f50505' );
	$out['footer: company + address'] = false !== strpos( $html, '2ACE sp. z o.o.' ) && false !== strpos( $html, 'Ostrobramska 101A' );
	$out['footer: KRS / NIP'] = false !== strpos( $html, 'KRS 0001267111' ) && false !== strpos( $html, 'NIP 1133212948' );
	$out['password link in the e-mail'] = false !== strpos( $html, 'lost-password' );
	$out['option base colour'] = get_option( 'woocommerce_email_base_color' );
	// Owner's own settings win.
	update_option( 'woocommerce_email_base_color', '#123456' ); update_option( 'woocommerce_email_footer_text', 'Własna stopka' ); update_option( 'woocommerce_email_header_image', 'https://x.test/a.png' );
	$out['own colour kept'] = '#123456' === get_option( 'woocommerce_email_base_color' );
	$out['own footer kept'] = 'Własna stopka' === get_option( 'woocommerce_email_footer_text' );
	$out['own header image kept'] = 'https://x.test/a.png' === get_option( 'woocommerce_email_header_image' );
	$out['account layout css rule'] = false !== strpos( file_get_contents( get_theme_file_path( 'assets/css/flow.css' ) ), 'body.woocommerce-account:not(.logged-in) .woocommerce { display: block; }' );
} catch ( Throwable $e ) {
	$out['ERROR'] = $e->getMessage() . ' @ ' . basename( $e->getFile() ) . ':' . $e->getLine();
}
file_put_contents( '/playground/_emails-result.json', wp_json_encode( $out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
