<?php
$out = array();
try {
	$f = function ( $t ) { return gmdate( 'Y-m-d\TH:i', $t ); };
	$active = function () { $b = ace_active_banner( true ); return $b ? $b['n'] : null; };
	$reset = function () { foreach ( array( 1, 2, 3 ) as $n ) { foreach ( array( 'on', 'from', 'until', 'end' ) as $k ) { remove_theme_mod( "ace_bn{$n}_{$k}" ); } } };
	$now = time();

	$reset();
	$out['default'] = $active();                                   // banner 1 is on by default -> 1
	set_theme_mod( 'ace_bn2_on', true );
	$out['2 on, no dates'] = $active();                            // tie -> lowest number -> 1
	set_theme_mod( 'ace_bn2_from', $f( $now - 3600 ) ); set_theme_mod( 'ace_bn2_until', $f( $now + 3600 ) );
	$out['2 dated, inside window'] = $active();                    // dated beats always-on -> 2
	set_theme_mod( 'ace_bn2_from', $f( $now + 3600 ) ); set_theme_mod( 'ace_bn2_until', $f( $now + 7200 ) );
	$out['2 dated, in the future'] = $active();                    // -> 1
	set_theme_mod( 'ace_bn2_from', $f( $now - 7200 ) ); set_theme_mod( 'ace_bn2_until', $f( $now - 3600 ) );
	$out['2 dated, already over'] = $active();                     // -> 1
	set_theme_mod( 'ace_bn1_on', false ); set_theme_mod( 'ace_bn2_on', false );
	$out['all off'] = $active();                                   // null
	$reset();

	// Time zone: "18:00" in Warsaw is not "18:00" UTC.
	update_option( 'timezone_string', 'Europe/Warsaw' );
	$out['ts warsaw 2026-12-24T18:00'] = gmdate( 'H:i', ace_banner_ts( '2026-12-24T18:00' ) ) . ' UTC';
	$out['ts garbage'] = ace_banner_ts( 'nonsense' );

	// Customizer registration.
	require_once ABSPATH . WPINC . '/class-wp-customize-manager.php';
	global $wp_customize;
	$wp_customize = new WP_Customize_Manager();
	do_action( 'customize_register', $wp_customize );
	$out['sections'] = array_values( array_filter( array_keys( $wp_customize->sections() ), function ( $k ) { return 0 === strpos( $k, 'ace_banner_' ); } ) );
	$out['banner1 controls'] = count( array_filter( array_keys( $wp_customize->controls() ), function ( $k ) { return 0 === strpos( $k, 'ace_bn1_' ); } ) );
	$out['sanitize datetime'] = array( ace_sanitize_banner_datetime( '2026-12-24T18:00' ), ace_sanitize_banner_datetime( '<script>' ) );
	$out['sanitize count'] = array( ace_sanitize_banner_count( 1 ), ace_sanitize_banner_count( 99 ), ace_sanitize_banner_count( 6 ) );
	$out['sanitize style'] = array( ace_sanitize_banner_style( 'halloween' ), ace_sanitize_banner_style( 'evil' ) );

	// Products by IDs / SKUs.
	$p = new WC_Product_Simple(); $p->set_name( 'Test A' ); $p->set_sku( 'BN-A' ); $p->set_status( 'publish' ); $p->set_regular_price( '10' ); $ida = $p->save();
	$p2 = new WC_Product_Simple(); $p2->set_name( 'Test B' ); $p2->set_status( 'publish' ); $p2->set_regular_price( '12' ); $idb = $p2->save();
	$b = ace_banner_get( 1 ); $b['source'] = 'ids'; $b['ids'] = "BN-A, $idb, 999999, nonsense";
	$out['ids picked (in order)'] = array_map( function ( $x ) { return $x->get_name(); }, ace_banner_products( $b ) );
	$b['source'] = 'category'; $b['cat'] = 'does-not-exist';
	$out['unknown category falls back to newest'] = count( ace_banner_products( $b ) ) > 0;

	// Render the template with banner 1 on, with a past countdown.
	$reset();
	set_theme_mod( 'ace_bn1_countdown', true ); set_theme_mod( 'ace_bn1_end', $f( $now - 600 ) ); set_theme_mod( 'ace_bn1_title', 'Hello world banner' );
	ace_active_banner( true ); ob_start(); include get_template_directory() . '/template-parts/banner.php'; $html = ob_get_clean();
	$out['render'] = array( 'bytes' => strlen( $html ), 'h1' => (bool) preg_match( '#<h1[^>]*>Hello world <em>banner</em></h1>#', $html ), 'ended text shown, no timer' => ( false !== strpos( $html, 'banner__ended' ) && false === strpos( $html, 'data-countdown' ) ), 'cards' => substr_count( $html, 'class="pcard' ) );
	set_theme_mod( 'ace_bn1_end', $f( $now + 86400 * 3 ) );
	ace_active_banner( true ); ob_start(); include get_template_directory() . '/template-parts/banner.php'; $html = ob_get_clean();
	$out['render running'] = array( 'timer' => false !== strpos( $html, 'data-countdown' ), 'hidden ended note' => false !== strpos( $html, 'data-cd-ended hidden' ) );
	$reset(); remove_theme_mod( 'ace_bn1_countdown' ); remove_theme_mod( 'ace_bn1_title' );
} catch ( Throwable $e ) { $out['ERROR'] = $e->getMessage() . ' @ ' . basename( $e->getFile() ) . ':' . $e->getLine(); }
file_put_contents( '/playground/_banner-result.json', wp_json_encode( $out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
