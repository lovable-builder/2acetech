<?php
// Category automation: create/reuse, cap, and fixing products that sit in the wrong category.
$out = array();
register_shutdown_function( function () { $e = error_get_last(); if ( $e && in_array( $e['type'], array( E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR ), true ) ) { file_put_contents( '/playground/_categories-fatal.json', wp_json_encode( $e ) ); } } );
try {
	$names = function ( $id ) { return wp_get_object_terms( $id, 'product_cat', array( 'fields' => 'names' ) ); };
	$tree  = function () { return ACE_PC_Cleaner::tree_lines(); };

	$light = wp_insert_term( 'Oświetlenie', 'product_cat' )['term_id'];
	$mk = function ( $name, $sku = '' ) use ( $light ) {
		$p = new WC_Product_Simple(); $p->set_name( $name ); $p->set_status( 'publish' ); $p->set_regular_price( '20.99' ); if ( $sku ) { $p->set_sku( $sku ); }
		$id = $p->save(); wp_set_object_terms( $id, array( (int) $light ), 'product_cat' ); return $id;
	};
	$strip = $mk( 'Taśma LED RGB 5 m z pilotem' );
	$blocks = $mk( 'Klocki konstrukcyjne samochód wyścigowy 1347 elementów' );
	$cards = $mk( 'Gra karciana dla 2-6 graczy na wieczór rodzinny' );
	$own = $mk( 'Rimfya lampka', 'RMF-LB-9' );
	$out['before'] = array( 'strip' => $names( $strip ), 'blocks' => $names( $blocks ), 'cards' => $names( $cards ) );

	add_filter( 'ace_pc_recat_json', function ( $x, $items ) use ( $strip, $blocks, $cards ) {
		return array( 'categories' => array(
			$strip  => array( 'oświetlenie', 'Taśmy LED' ),               // existing parent (different case) + new child
			$blocks => array( 'Zabawki i gry', 'Klocki' ),                // new parent + new child
			$cards  => array( 'Zabawki i gry', 'Gry planszowe i karciane' ), // existing parent + new child
		) );
	}, 10, 2 );
	$out['changed'] = ACE_PC_Cleaner::recategorize( array( $strip, $blocks, $cards, $own ) );
	$out['after'] = array( 'strip' => $names( $strip ), 'blocks' => $names( $blocks ), 'cards' => $names( $cards ), 'own_untouched' => $names( $own ) );
	$out['tree'] = $tree();

	// Running it again must not create duplicates or move anything.
	$out['second_pass_changed'] = ACE_PC_Cleaner::recategorize( array( $strip, $blocks, $cards ) );
	$out['tree_after_second'] = count( $tree() ) === count( $out['tree'] );

	// Cap: fill up to 12 top-level categories, the 13th request falls back to "Inne produkty".
	$i = 0;
	while ( count( get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false, 'parent' => 0, 'fields' => 'ids', 'exclude' => array( (int) get_option( 'default_product_cat' ) ) ) ) ) < 12 ) { wp_insert_term( 'Dział testowy ' . ( ++$i ), 'product_cat' ); }
	$ids = ACE_PC_Cleaner::resolve_category( array( 'Zupełnie nowy dział' ) );
	$out['cap_fallback'] = get_term( $ids[0], 'product_cat' )->name;
	$ids2 = ACE_PC_Cleaner::resolve_category( array( 'Zupełnie nowy dział' ) );
	$out['cap_fallback_reused'] = $ids === $ids2;
	$out['bad_path'] = null;
	try { ACE_PC_Cleaner::resolve_category( array() ); } catch ( Exception $e ) { $out['bad_path'] = $e->getMessage(); }
} catch ( Throwable $e ) {
	$out['ERROR'] = $e->getMessage() . ' @ ' . basename( $e->getFile() ) . ':' . $e->getLine();
}
file_put_contents( '/playground/_categories-result.json', wp_json_encode( $out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
