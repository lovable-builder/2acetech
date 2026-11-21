<?php
// Test for the cleaner's photo rules: cap at 5, own products untouched, single-photo warning.
// Writes /playground/_photos-result.json
$out = array();
try {
	$att = function () {
		return wp_insert_attachment( array( 'post_title' => 'img', 'post_mime_type' => 'image/webp', 'post_status' => 'inherit' ) );
	};
	$make = function ( $name, $sku, $gallery_n, $with_main = true, $old = false ) use ( $att ) {
		$p = new WC_Product_Simple();
		$p->set_name( $name );
		$p->set_sku( $sku );
		$p->set_status( 'publish' );
		$main = $with_main ? $att() : 0;
		$p->set_image_id( $main );
		$gal = array();
		for ( $i = 0; $i < $gallery_n; $i++ ) {
			$gal[] = $att();
		}
		$p->set_gallery_image_ids( $gal );
		$id = $p->save();
		update_post_meta( $id, '_vi_wad_aliexpress_product_id', $sku );
		if ( $old ) {
			wp_update_post( array( 'ID' => $id, 'post_date' => gmdate( 'Y-m-d H:i:s', time() - 7200 ), 'post_date_gmt' => gmdate( 'Y-m-d H:i:s', time() - 7200 ) ) );
		}
		return array( $id, $main, $gal );
	};

	list( $a, $a_main, $a_gal ) = $make( 'Import with 8 gallery photos', '1005000000000001', 8, true, true );
	list( $b, , )               = $make( 'Import with 3 gallery photos', '1005000000000002', 3, true, true );
	list( $c, , )               = $make( 'Own product with 8 gallery photos', 'RMF-TEST-1', 8, true, true );
	list( $d, , )               = $make( 'Import with a single photo, old', '1005000000000004', 0, true, true );
	list( $e, , )               = $make( 'Import with a single photo, new', '1005000000000005', 0, true, false );
	list( $f, , )               = $make( 'Import with no main photo, 8 gallery', '1005000000000006', 8, false, true );

	$count = function ( $id ) { return ACE_PC_Photos::count( wc_get_product( $id ) ); };

	ACE_PC_Photos::tidy_all();
	$out['A 8+main -> total'] = $count( $a );
	$out['A kept the first 4 in order'] = array_slice( $a_gal, 0, 4 ) === array_map( 'intval', wc_get_product( $a )->get_gallery_image_ids() );
	$out['A main photo unchanged'] = (int) wc_get_product( $a )->get_image_id() === (int) $a_main;
	$out['A removed ids kept'] = count( (array) get_post_meta( $a, ACE_PC_Photos::REMOVED, true ) );
	$out['A removed files still exist'] = (bool) get_post( $a_gal[7] );
	$out['B untouched'] = $count( $b );
	$out['C own product untouched'] = $count( $c );
	$out['F no main, 8 gallery -> total'] = $count( $f );

	ACE_PC_Photos::tidy_all();
	$out['idempotent (A again)'] = $count( $a );

	$low = ACE_PC_Photos::lacking();
	$out['lacking has D (old, 1 photo)'] = isset( $low[ $d ] );
	$out['lacking skips E (new)'] = ! isset( $low[ $e ] );
	$out['lacking skips A, B, C'] = ! isset( $low[ $a ] ) && ! isset( $low[ $b ] ) && ! isset( $low[ $c ] );

	require_once ABSPATH . 'wp-admin/includes/class-wp-screen.php';
	require_once ABSPATH . 'wp-admin/includes/screen.php';
	require_once ABSPATH . 'wp-admin/includes/template.php';
	wp_set_current_user( get_users( array( 'role' => 'administrator', 'number' => 1 ) )[0]->ID );
	set_current_screen( 'edit-product' );
	ob_start();
	ACE_PC_Photos::notice();
	$notice = ob_get_clean();
	$out['notice shown'] = false !== strpos( $notice, 'notice-warning' ) && false !== strpos( $notice, 'woo-alidropship-error-images' );
	$out['notice names D'] = false !== strpos( $notice, 'single photo, old' );

	ob_start();
	ACE_PC_Photos::column_cell( 'ace_photos', $d );
	$cell_low = ob_get_clean();
	ob_start();
	ACE_PC_Photos::column_cell( 'ace_photos', $a );
	$cell_ok = ob_get_clean();
	$out['column low badge'] = false !== strpos( $cell_low, 'ace-pc-photos--low' );
	$out['column ok count'] = false !== strpos( $cell_ok, '>5<' ) && false === strpos( $cell_ok, '--low' );

	$cols = ACE_PC_Photos::column( array( 'cb' => '', 'name' => 'Name', 'price' => 'Price' ) );
	$out['column after name'] = array_keys( $cols ) === array( 'cb', 'name', 'ace_photos', 'price' );
	$out['cron runs without API key'] = ( function () { ACE_PC_Settings::cron(); return true; } )();
} catch ( Throwable $e ) {
	$out['ERROR'] = $e->getMessage() . ' @ ' . basename( $e->getFile() ) . ':' . $e->getLine();
}
file_put_contents( '/playground/_photos-result.json', wp_json_encode( $out, JSON_PRETTY_PRINT ) );
