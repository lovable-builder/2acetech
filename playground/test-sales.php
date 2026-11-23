<?php
// Test: the importer's fake "regular" price is removed; the price customers pay does not change.
// Writes /playground/_sales-result2.json
$out = array();
try {
	update_option( 'ace_pc_settings', array( 'provider' => 'deepseek', 'keys' => array( 'deepseek' => 'x' ), 'model' => 'm', 'auto' => 1, 'category' => 'Sport', 'sku_prefix' => '2AC-' ) );
	$answer = function () {
		return array(
			'title' => 'Ultralekka mata do spania na camping i turystykę',
			'short_bullets' => array( 'Lekka konstrukcja', 'Szybkie pompowanie', 'Mały pakunek', 'Wygodna na biwaku' ),
			'intro' => 'Lekka mata do spania na biwak. Szybko się pompuje. Po złożeniu zajmuje mało miejsca.',
			'features' => array( 'Lekka konstrukcja', 'Szybkie pompowanie', 'Mały pakunek po złożeniu' ),
			'spec_rows' => array( array( 'Materiał', 'Nylon' ), array( 'Waga', '500 g' ), array( 'Zastosowanie', 'Camping' ) ),
			'category' => array( 'Sport', 'Camping' ), 'tags' => array( 'mata do spania', 'camping', 'turystyka' ), 'attrs' => array(), 'notes' => array(), 'brand' => null,
		);
	};
	add_filter( 'ace_pc_llm_json', function () use ( $answer ) { return $answer(); } );

	$simple = function ( $sku, $reg, $sale, $own = false ) {
		$p = new WC_Product_Simple(); $p->set_name( 'Product ' . $sku ); $p->set_sku( $sku ); $p->set_status( 'publish' ); $p->set_description( 'Some English text.' );
		$p->set_regular_price( $reg ); if ( '' !== $sale ) { $p->set_sale_price( $sale ); }
		$id = $p->save(); update_post_meta( $id, '_vi_wad_aliexpress_product_id', $sku ); return $id;
	};

	// A. Simple product, 21% fake sale (561.99 -> 444.99 after .99 rounding).
	$a = $simple( '1005000000000021', '561.40', '444.20' );
	ACE_PC_Cleaner::clean( $a ); $pa = wc_get_product( $a );
	$out['A: price paid is unchanged (444.99)'] = $pa->get_price();
	$out['A: regular price = what is paid'] = $pa->get_regular_price();
	$out['A: no sale price'] = '' === $pa->get_sale_price() && ! $pa->is_on_sale();
	$bk = get_post_meta( $a, '_aliclean_sale_backup', true );
	$out['A: old pair kept (clean backup, no stray entry)'] = $bk;
	$out['A: product id'] = $a; $out['A: all meta keys'] = array_values( array_filter( array_keys( get_post_meta( $a ) ), function ( $k ) { return 0 === strpos( $k, '_aliclean' ); } ) );

	// B. Undo restores the original pair.
	ACE_PC_Cleaner::undo( $a ); $pa = wc_get_product( $a );
	$out['B: undo restores regular + sale'] = array( $pa->get_regular_price(), $pa->get_sale_price() );

	// C. Variable product: every variation.
	$v = new WC_Product_Variable(); $v->set_name( 'Var' ); $v->set_sku( '1005000000000022' ); $v->set_status( 'publish' ); $v->set_description( 'English text.' );
	$att = new WC_Product_Attribute(); $att->set_name( 'Size' ); $att->set_options( array( 'S', 'M' ) ); $att->set_visible( true ); $att->set_variation( true ); $v->set_attributes( array( $att ) );
	$vid = $v->save(); update_post_meta( $vid, '_vi_wad_aliexpress_product_id', 'x' );
	$vars = array();
	foreach ( array( array( 'S', '300.00', '237.00' ), array( 'M', '400.00', '316.00' ) ) as $row ) {
		$x = new WC_Product_Variation(); $x->set_parent_id( $vid ); $x->set_attributes( array( 'size' => $row[0] ) ); $x->set_regular_price( $row[1] ); $x->set_sale_price( $row[2] ); $x->set_status( 'publish' ); $vars[] = $x->save();
	}
	WC_Product_Variable::sync( $vid );
	$n = ACE_PC_Cleaner::drop_importer_sale( $vid );
	$out['C: both variations changed'] = $n;
	$out['C: variations: regular = old sale, no sale'] = array_map( function ( $id ) { $x = wc_get_product( $id ); return $x->get_regular_price() . '/' . ( '' === $x->get_sale_price() ? 'none' : $x->get_sale_price() ); }, $vars );
	$out['C: product no longer on sale'] = ! wc_get_product( $vid )->is_on_sale();
	$out['C: second run does nothing'] = 0 === ACE_PC_Cleaner::drop_importer_sale( $vid );

	// D. A scheduled (real) sale is kept.
	$d = $simple( '1005000000000023', '100.00', '80.00' );
	$pd = wc_get_product( $d ); $pd->set_date_on_sale_from( gmdate( 'Y-m-d', time() - 86400 ) ); $pd->set_date_on_sale_to( gmdate( 'Y-m-d', time() + 86400 * 5 ) ); $pd->save();
	$out['D: scheduled sale kept'] = 0 === ACE_PC_Cleaner::drop_importer_sale( $d ) && '80.00' === wc_get_product( $d )->get_sale_price();

	// E. Own products are never touched.
	$own = new WC_Product_Simple(); $own->set_name( 'Rimfya' ); $own->set_sku( 'RMF-LB-1' ); $own->set_status( 'publish' ); $own->set_regular_price( '100' ); $own->set_sale_price( '79' ); $oid = $own->save();
	update_post_meta( $oid, '_vi_wad_aliexpress_product_id', 'x' );
	$out['E: own product untouched'] = 0 === ACE_PC_Cleaner::drop_importer_sale( $oid ) && '79' === wc_get_product( $oid )->get_sale_price();

	// F. A product with no sale is untouched; batch helper reports only changed ones.
	$f = $simple( '1005000000000024', '50.00', '' );
	$g = $simple( '1005000000000025', '90.00', '70.00' );
	$batch = ACE_PC_Cleaner::drop_importer_sales( array( $f, $g, $d, $oid ) );
	$out['F: batch result'] = array_keys( $batch ) === array( $g );
	$out['F: no-sale product unchanged'] = '50.00' === wc_get_product( $f )->get_regular_price();

	// G. The shop's front-end: nothing shows as "on sale" for the cleaned/dropped ones.
	$out['G: on-sale ids among a,c,g'] = array_values( array_filter( array( $a, $vid, $g ), function ( $id ) { return wc_get_product( $id )->is_on_sale(); } ) );
} catch ( Throwable $e ) {
	$out['ERROR'] = $e->getMessage() . ' @ ' . basename( $e->getFile() ) . ':' . $e->getLine();
}
file_put_contents( '/playground/_sales-result2.json', wp_json_encode( $out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
