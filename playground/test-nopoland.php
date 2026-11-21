<?php
// Test: a product with no variation shipping from Poland is still cleaned (Polish text), its variations
// stay disabled, it goes to Draft, and Undo restores everything. Writes /playground/_nopoland-result.json
$out = array();
try {
	foreach ( array( 'Size', 'Ships From' ) as $n ) {
		if ( ! wc_attribute_taxonomy_id_by_name( $n ) ) { wc_create_attribute( array( 'name' => $n, 'slug' => sanitize_title( $n ) ) ); }
		register_taxonomy( 'pa_' . sanitize_title( $n ), 'product', array( 'hierarchical' => false ) );
	}
	delete_transient( 'wc_attribute_taxonomies' );
	wp_insert_term( 'Sport i turystyka', 'product_cat' );
	update_option( 'ace_pc_settings', array( 'provider' => 'deepseek', 'keys' => array( 'deepseek' => 'x' ), 'model' => 'm', 'auto' => 1, 'category' => 'Sport i turystyka', 'sku_prefix' => '2AC-' ) );

	$p = new WC_Product_Variable();
	$p->set_name( 'Camping Double Inflatable High Raised Air Bed Mattress, Airbed, PVC Foldable Bed, Brand New' );
	$p->set_sku( '1005013640801820' );
	$p->set_status( 'publish' );
	$p->set_description( '<p>Inflatable air bed with pump and pillows.</p><p><img src="https://img.test/x.jpg"></p>' );
	$attrs = array();
	$spec  = array( 'pa_size' => array( 'queen' ), 'pa_ships-from' => array( 'Spain', 'Germany' ) );
	$i = 0;
	foreach ( $spec as $tax => $terms ) {
		$a = new WC_Product_Attribute();
		$a->set_id( wc_attribute_taxonomy_id_by_name( $tax ) );
		$a->set_name( $tax );
		$a->set_options( array_map( function ( $t ) use ( $tax ) { $r = wp_insert_term( $t, $tax ); return is_wp_error( $r ) ? get_term_by( 'name', $t, $tax )->term_id : $r['term_id']; }, $terms ) );
		$a->set_position( $i++ ); $a->set_visible( false ); $a->set_variation( true );
		$attrs[] = $a;
	}
	$p->set_attributes( $attrs );
	$pid = $p->save();
	wp_set_object_terms( $pid, array( 'queen' ), 'pa_size' );
	wp_set_object_terms( $pid, array( 'Spain', 'Germany' ), 'pa_ships-from' );
	update_post_meta( $pid, '_vi_wad_aliexpress_product_id', '1005013640801820' );
	foreach ( array( 'spain', 'germany' ) as $from ) {
		$v = new WC_Product_Variation();
		$v->set_parent_id( $pid );
		$v->set_attributes( array( 'pa_size' => 'queen', 'pa_ships-from' => $from ) );
		$v->set_regular_price( 250.40 ); $v->set_status( 'publish' );
		$v->save();
	}
	WC_Product_Variable::sync( $pid );
	$vids = wc_get_product( $pid )->get_children();

	add_filter( 'ace_pc_llm_json', function () {
		return array(
			'title' => 'Materac dmuchany dwuosobowy z pompką i poduszkami na camping',
			'short_bullets' => array( 'Wysoki materac dwuosobowy', 'Pompka i poduszki w zestawie', 'Składany, łatwy w transporcie', 'Wytrzymały materiał PVC' ),
			'intro' => 'Wysoki materac dmuchany dla dwóch osób. W zestawie pompka i dwie poduszki. Po złożeniu zajmuje niewiele miejsca.',
			'features' => array( 'Wysoki, wygodny materac', 'Pompka w zestawie', 'Dwie poduszki' ),
			'spec_rows' => array( array( 'Materiał', 'PVC' ), array( 'Rozmiar', 'Queen' ), array( 'Zestaw', 'Pompka i poduszki' ) ),
			'category' => array( 'Sport i turystyka' ),
			'tags' => array( 'materac dmuchany', 'materac na camping', 'materac dwuosobowy', 'pompka elektryczna' ),
			'attrs' => array( 'Size' => array( 'name' => 'Rozmiar', 'variation' => true, 'values' => array( 'queen' => 'Queen' ) ) ),
			'notes' => array(),
		);
	} );

	$r = ACE_PC_Cleaner::clean( $pid );
	$after = wc_get_product( $pid );
	$out['cleaned without error'] = true;
	$out['title is Polish'] = 'Materac dmuchany dwuosobowy z pompką i poduszkami na camping' === $after->get_name();
	$out['status'] = get_post_status( $pid );
	$out['marked cleaned'] = '' !== (string) get_post_meta( $pid, '_aliclean', true );
	$out['no_poland flag'] = '1' === (string) get_post_meta( $pid, '_aliclean_no_poland', true );
	$out['variation statuses'] = array_map( function ( $vid ) { return get_post_status( $vid ); }, $vids );
	$out['note stored'] = false !== strpos( implode( ' ', (array) get_post_meta( $pid, '_aliclean_notes', true ) ), 'wersji roboczej' );
	$out['not in waiting list'] = ! in_array( $pid, ACE_PC_Cleaner::waiting(), true );

	ACE_PC_Cleaner::undo( $pid );
	$back = wc_get_product( $pid );
	$out['undo: status back'] = get_post_status( $pid );
	$out['undo: title back'] = 0 === strpos( $back->get_name(), 'Camping Double' );
	$out['undo: variations back'] = array_map( function ( $vid ) { return get_post_status( $vid ); }, $vids );
	$out['undo: flag removed'] = '' === (string) get_post_meta( $pid, '_aliclean_no_poland', true );
} catch ( Throwable $e ) {
	$out['ERROR'] = get_class( $e ) . ': ' . $e->getMessage() . ' @ ' . basename( $e->getFile() ) . ':' . $e->getLine();
}
file_put_contents( '/playground/_nopoland-result.json', wp_json_encode( $out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
