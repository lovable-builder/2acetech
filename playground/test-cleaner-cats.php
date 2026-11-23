<?php
// Test: while cleaning, the category AND subcategory are created if missing; a phrase is written for
// new main categories only. Writes /playground/_cleaner-cats-result.json
$out = array();
try {
	update_option( 'ace_pc_settings', array( 'provider' => 'deepseek', 'keys' => array( 'deepseek' => 'x' ), 'model' => 'm', 'auto' => 1, 'category' => 'Oświetlenie', 'sku_prefix' => '2AC-' ) );
	$taglines = array(
		'Namioty i biwak' => array( 'tagline' => 'Biwak w twoim stylu' ),
		'Zły przykład' => array( 'tagline' => 'Najlepsze produkty w Polsce!' ),
	);
	add_filter( 'ace_pc_tagline_json', function ( $x, $name ) use ( $taglines ) { return isset( $taglines[ $name ] ) ? $taglines[ $name ] : null; }, 10, 2 );

	$count = function () { return count( get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) ) ); };
	$before = $count();

	// 1. Neither parent nor child exist: both are created, the product gets the leaf.
	$ids = ACE_PC_Cleaner::resolve_category( array( 'Namioty i biwak', 'Namioty rodzinne' ) );
	$leaf = get_term( $ids[0], 'product_cat' ); $top = get_term( $leaf->parent, 'product_cat' );
	$out['1: leaf created'] = $leaf->name;
	$out['1: parent created'] = $top->name;
	$out['1: leaf sits under the parent'] = $leaf->parent === $top->term_id;
	$out['1: 2 new categories'] = $count() - $before;
	$out['1: phrase written for the new main category'] = get_term_meta( $top->term_id, 'ace_cat_tagline', true );
	$out['1: no phrase for the subcategory'] = '' === (string) get_term_meta( $leaf->term_id, 'ace_cat_tagline', true );

	// 2. Parent exists, child missing: only the child is created, the parent's phrase is left alone.
	update_term_meta( $top->term_id, 'ace_cat_tagline', 'Moje własne hasło' );
	$ids2 = ACE_PC_Cleaner::resolve_category( array( 'namioty i biwak', 'Śpiwory' ) ); // different letter case on purpose
	$leaf2 = get_term( $ids2[0], 'product_cat' );
	$out['2: child created under the existing parent'] = $leaf2->name . ' < ' . get_term( $leaf2->parent, 'product_cat' )->name;
	$out['2: no duplicate parent'] = 1 === count( get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false, 'name' => 'Namioty i biwak' ) ) );
	$out['2: own phrase kept'] = 'Moje własne hasło' === get_term_meta( $top->term_id, 'ace_cat_tagline', true );

	// 3. Both exist: nothing new.
	$n = $count(); $ids3 = ACE_PC_Cleaner::resolve_category( array( 'Namioty i biwak', 'Śpiwory' ) );
	$out['3: nothing created'] = $count() === $n && $ids3 === $ids2;

	// 4. A phrase with a superlative / exclamation is refused (no unprovable claims).
	$bad = ACE_PC_Cleaner::resolve_category( array( 'Zły przykład' ) );
	$out['4: unprovable phrase refused'] = '' === (string) get_term_meta( $bad[0], 'ace_cat_tagline', true );

	// 5. No API key and no phrase available: the category is still created.
	remove_all_filters( 'ace_pc_tagline_json' );
	update_option( 'ace_pc_settings', array( 'provider' => 'deepseek', 'keys' => array(), 'model' => '', 'auto' => 1, 'category' => 'x', 'sku_prefix' => '2AC-' ) );
	$nokey = ACE_PC_Cleaner::resolve_category( array( 'Biżuteria', 'Naszyjniki' ) );
	$out['5: no API key: categories still created'] = 'Naszyjniki' === get_term( $nokey[0], 'product_cat' )->name;
	$out['5: and no phrase stored'] = '' === (string) get_term_meta( get_term( $nokey[0], 'product_cat' )->parent, 'ace_cat_tagline', true );

	// 5b. Three levels: main > sub > narrower sub, all created; a 4th level is cut off.
	$three = ACE_PC_Cleaner::resolve_category( array( 'Sport i turystyka', 'Camping', 'Materace i maty', 'Za głęboko' ) );
	$l3 = get_term( $three[0], 'product_cat' ); $l2 = get_term( $l3->parent, 'product_cat' ); $l1 = get_term( $l2->parent, 'product_cat' );
	$out['5b: three levels created'] = $l1->name . ' > ' . $l2->name . ' > ' . $l3->name;
	$out['5b: 4th level ignored'] = false === get_term_by( 'name', 'Za głęboko', 'product_cat' );
	$again = ACE_PC_Cleaner::resolve_category( array( 'Sport i turystyka', 'Camping', 'Pompki' ) );
	$out['5b: second leaf under the same Camping'] = get_term( get_term( $again[0], 'product_cat' )->parent, 'product_cat' )->term_id === $l2->term_id;
	$lines = ACE_PC_Cleaner::tree_lines();
	$out['5b: AI sees the third level'] = in_array( 'Sport i turystyka > Camping > Materace i maty', $lines, true );

	// 6. Category cap: past 12 main categories the catch-all is used instead of a new one.
	$tops = function () { return count( get_terms( array( 'taxonomy' => 'product_cat', 'parent' => 0, 'hide_empty' => false, 'fields' => 'ids', 'exclude' => array( (int) get_option( 'default_product_cat' ) ) ) ) ); };
	for ( $i = 1; $i <= 20 && $tops() < ACE_PC_Cleaner::MAX_TOP; $i++ ) { wp_insert_term( "Fill $i", 'product_cat' ); }
	$out['6: main categories now'] = $tops();
	$cap = ACE_PC_Cleaner::resolve_category( array( 'Zupełnie nowa kategoria' ) );
	$out['6: at the cap, "Inne produkty" is used'] = 'Inne produkty' === get_term( $cap[0], 'product_cat' )->name;
} catch ( Throwable $e ) {
	$out['ERROR'] = $e->getMessage() . ' @ ' . basename( $e->getFile() ) . ':' . $e->getLine();
}
file_put_contents( '/playground/_cleaner-cats-result.json', wp_json_encode( $out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
