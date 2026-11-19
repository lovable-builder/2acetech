<?php
/**
 * Preview only: ?bn={"style":"halloween","countdown":1,"end":"2026-12-24T18:00"} overrides the settings of
 * banner 1, so every look can be viewed without opening the Customizer.
 */
if ( isset( $_GET['bn'] ) ) { // phpcs:ignore
	$o = json_decode( wp_unslash( $_GET['bn'] ), true ); // phpcs:ignore
	if ( is_array( $o ) ) {
		foreach ( $o as $k => $v ) {
			add_filter( 'theme_mod_ace_bn1_' . $k, function () use ( $v ) { return $v; } );
		}
	}
}
