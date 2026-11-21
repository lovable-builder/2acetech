<?php
// Test: a variation without its own gallery gets the product's other photos. Writes /playground/_vargal-result.json
$out = array();
try {
	require_once ABSPATH . 'wp-admin/includes/image.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	$mk = function ( $i ) {
		$im = imagecreatetruecolor( 700, 700 );
		imagefill( $im, 0, 0, imagecolorallocate( $im, 20 * $i, 60, 200 - 20 * $i ) );
		ob_start(); imagepng( $im ); $png = ob_get_clean();
		$up = wp_upload_bits( "p$i.png", null, $png );
		$id = wp_insert_attachment( array( 'post_mime_type' => 'image/png', 'post_title' => "p$i", 'post_status' => 'inherit' ), $up['file'] );
		wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $up['file'] ) );
		return $id;
	};
	$imgs = array();
	for ( $i = 1; $i <= 5; $i++ ) { $imgs[] = $mk( $i ); }

	$p = new WC_Product_Variable();
	$p->set_name( 'Gallery test' ); $p->set_status( 'publish' );
	$p->set_image_id( $imgs[0] );
	$p->set_gallery_image_ids( array_slice( $imgs, 1 ) );
	$pid = $p->save();
	$v = new WC_Product_Variation(); $v->set_parent_id( $pid ); $v->set_regular_price( 10 ); $v->set_image_id( $imgs[0] ); $v->set_status( 'publish' ); $vid = $v->save();
	$parent = wc_get_product( $pid ); $var = wc_get_product( $vid );

	// 1. variation image = main photo, no gallery of its own (what imports look like)
	$d = ace_variation_keeps_gallery( array( 'image_id' => $imgs[0], 'vargal' => array() ), $parent, $var );
	$out['1: other 4 photos added'] = count( $d['vargal'] );
	$out['1: each has what vargal needs'] = ! array_diff( array( 'src', 'srcset', 'full_src', 'full_src_w', 'full_src_h', 'gallery_thumbnail_src', 'alt' ), array_keys( $d['vargal'][0] ) );
	$out['1: variation image not repeated'] = ! in_array( $imgs[0], array_map( function ( $x ) use ( $imgs ) { foreach ( $imgs as $id ) { if ( wp_get_attachment_url( $id ) === $x['full_src'] ) { return $id; } } return 0; }, $d['vargal'] ), true );

	// 2. variation image differs from main: main + 3 gallery photos come after it
	$d2 = ace_variation_keeps_gallery( array( 'image_id' => $imgs[2], 'vargal' => array() ), $parent, $var );
	$out['2: own image differs -> 4 others'] = count( $d2['vargal'] );

	// 3. a variation with its own gallery is left alone
	$own = array( array( 'full_src' => 'x' ) );
	$d3 = ace_variation_keeps_gallery( array( 'image_id' => $imgs[0], 'vargal' => $own ), $parent, $var );
	$out['3: own gallery untouched'] = $d3['vargal'] === $own;

	// 4. no vargal key (plugin inactive): untouched
	$d4 = ace_variation_keeps_gallery( array( 'image_id' => $imgs[0] ), $parent, $var );
	$out['4: no key, untouched'] = ! isset( $d4['vargal'] );

	// 5. through the real WooCommerce filter
	$av = $parent->get_available_variation( $var );
	$out['5: key present via woocommerce'] = array_key_exists( 'image_id', $av );
	$av['vargal'] = array();
	$d5 = apply_filters( 'woocommerce_available_variation', $av, $parent, $var );
	$out['5: filter registered and fills'] = count( $d5['vargal'] );
} catch ( Throwable $e ) {
	$out['ERROR'] = $e->getMessage() . ' @ ' . basename( $e->getFile() ) . ':' . $e->getLine();
}
file_put_contents( '/playground/_vargal-result.json', wp_json_encode( $out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) );
