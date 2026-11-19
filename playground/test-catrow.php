<?php
$out = array();
try {
	$names = array( 'Oświetlenie', 'Zabawki i gry', 'Sport i turystyka', 'Dom i kuchnia', 'Ogród', 'Biuro i artykuły papiernicze', 'Dla dzieci i niemowląt', 'Zwierzęta', 'Elektronika', 'Rzeczy dziwne' );
	require_once ABSPATH . 'wp-admin/includes/file.php'; require_once ABSPATH . 'wp-admin/includes/media.php'; require_once ABSPATH . 'wp-admin/includes/image.php';
	$tmp = wp_tempnam( 'x.png' ); copy( '/playground/brand-samples/nexo-square-white.png', $tmp );
	$att = media_handle_sideload( array( 'name' => 'x.png', 'tmp_name' => $tmp ), 0 );
	foreach ( $names as $i => $n ) {
		$t = wp_insert_term( $n, 'product_cat' );
		$p = new WC_Product_Simple(); $p->set_name( 'Produkt ' . $i ); $p->set_status( 'publish' ); $p->set_regular_price( '10.99' ); $id = $p->save();
		wp_set_object_terms( $id, array( (int) $t['term_id'] ), 'product_cat' );
		if ( ! is_wp_error( $att ) ) { set_post_thumbnail( $id, $att ); }
	}
	foreach ( array( 'icons', 'photos' ) as $mode ) {
		set_theme_mod( 'ace_cat_style', $mode );
		foreach ( array_filter( array( 'ace_cat_img_' ) ) as $x ) {}
		global $wpdb; $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient%ace_cat_img_%'" );
		$out[ $mode ] = array_map( function ( $c ) { return array( $c['label'], 'art=' . $c['art'], 'photo=' . ( $c['thumb'] ? 'yes' : 'no' ), $c['count'] ); }, ace_category_list( 12 ) );
	}
} catch ( Throwable $e ) { $out['ERROR'] = $e->getMessage() . ' @ ' . basename( $e->getFile() ) . ':' . $e->getLine(); }
file_put_contents( '/playground/_catrow-result.json', wp_json_encode( $out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
