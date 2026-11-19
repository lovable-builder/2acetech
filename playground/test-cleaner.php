<?php
// Test for the 2ACE Product Cleaner plugin: fake import -> clean -> undo. Writes /playground/_cleaner-result.json
$out = array();
try {
	// global attributes, like the importer creates
	foreach ( array( 'Emitting Color', 'Wattage', 'Ships From' ) as $n ) {
		if ( ! wc_attribute_taxonomy_id_by_name( $n ) ) { wc_create_attribute( array( 'name' => $n, 'slug' => sanitize_title( $n ) ) ); }
		register_taxonomy( 'pa_' . sanitize_title( $n ), 'product', array( 'hierarchical' => false ) );
	}
	delete_transient( 'wc_attribute_taxonomies' );
	wp_insert_term( 'Oświetlenie', 'product_cat' );

	$p = new WC_Product_Variable();
	$p->set_name( 'Neon Rope Lights,Flexible Led Rope Lights Control with App/Remote IP65 Outdoor RGB' );
	$p->set_sku( '1005007969253919' );
	$p->set_status( 'publish' );
	$p->set_description( "Luminous Flux: 60-249\nBrand Name: aiopp\nOrigin: Mainland China\nWaterproof: Yes\n<p>The light tape can be used both indoor & outdoors.</p> IP65 Waterproof and Cuttable. Packing list: neon led strip, remote control, EU plug" );
	$attrs = array();
	$spec  = array( 'pa_emitting-color' => array( '1rollx5m', '1rollx10m' ), 'pa_wattage' => array( '0-5W' ), 'pa_ships-from' => array( 'Poland', 'spain' ) );
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
	wp_set_object_terms( $pid, array( '1rollx5m', '1rollx10m' ), 'pa_emitting-color' );
	wp_set_object_terms( $pid, array( '0-5W' ), 'pa_wattage' );
	wp_set_object_terms( $pid, array( 'Poland', 'spain' ), 'pa_ships-from' );
	update_post_meta( $pid, '_vi_wad_aliexpress_product_id', '1005007969253919' );
	$prices = array( array( '1rollx5m', 'Poland', 84.53, 71.15 ), array( '1rollx5m', 'spain', 97.69, 82.22 ), array( '1rollx10m', 'Poland', 119.64, 100.70 ), array( '1rollx10m', 'spain', 127.43, 107.25 ) );
	foreach ( $prices as $pr ) {
		$v = new WC_Product_Variation();
		$v->set_parent_id( $pid );
		$v->set_attributes( array( 'pa_emitting-color' => sanitize_title( $pr[0] ), 'pa_wattage' => '0-5w', 'pa_ships-from' => sanitize_title( $pr[1] ) ) );
		$v->set_regular_price( $pr[2] ); $v->set_sale_price( $pr[3] ); $v->set_status( 'publish' );
		$v->save();
	}
	WC_Product_Variable::sync( $pid );

	// an own product that must be ignored
	$own = new WC_Product_Simple(); $own->set_name( 'Rimfya lampka' ); $own->set_sku( 'RMF-LB-1' ); $own->set_status( 'publish' ); $own->set_regular_price( '100.99' ); $own_id = $own->save();

	$out['waiting_before'] = ACE_PC_Cleaner::waiting();
	$out['own_id'] = $own_id; $out['import_id'] = $pid;

	add_filter( 'ace_pc_llm_json', function () {
		return array(
			'title' => 'Taśma neonowa LED RGB 24 V z pilotem i aplikacją, IP65',
			'short_bullets' => array( 'Kolory RGB, pilot w zestawie', 'Stopień ochrony IP65', 'Można przyciąć na długość', 'Wtyczka EU' ),
			'intro' => 'Elastyczna taśma neonowa LED w kolorach RGB. Można ją wyginać w dowolne kształty. Stopień ochrony IP65 pozwala używać jej na zewnątrz.',
			'features' => array( 'Elastyczna taśma', 'Można ją przyciąć', 'Stopień ochrony IP65' ),
			'spec_rows' => array( array( 'Kolor światła', 'RGB' ), array( 'Stopień ochrony', 'IP65' ), array( 'Wtyczka', 'EU' ) ),
			'category' => array( 'Oświetlenie', 'Taśmy LED' ),
			'tags' => array( 'taśma neonowa LED', 'neon LED RGB', 'taśma LED IP65', 'oświetlenie zewnętrzne' ),
			'attrs' => array(
				'Emitting Color' => array( 'name' => 'Długość', 'variation' => true, 'values' => array( '1rollx5m' => '1 rolka × 5 m', '1rollx10m' => '1 rolka × 10 m' ) ),
				'Wattage' => array( 'drop' => true ),
			),
			'notes' => array( 'test note' ),
		);
	} );

	$r = ACE_PC_Cleaner::clean( $pid );
	$out['result'] = $r;
	$after = wc_get_product( $pid );
	$out['after'] = array(
		'name' => $after->get_name(), 'slug' => $after->get_slug(), 'sku' => $after->get_sku(), 'price' => $after->get_price(),
		'cats' => wp_get_object_terms( $pid, 'product_cat', array( 'fields' => 'names' ) ),
		'tags' => wp_get_object_terms( $pid, 'product_tag', array( 'fields' => 'names' ) ),
		'attrs' => array_map( function ( $a ) { return array( $a->get_name(), $a->get_options(), $a->get_variation(), $a->get_visible() ); }, array_values( $after->get_attributes() ) ),
		'short' => $after->get_short_description(), 'marker' => get_post_meta( $pid, '_aliclean', true ),
		'variations' => array_map( function ( $vid ) { $v = wc_get_product( $vid ); return array( $v->get_status(), $v->get_regular_price(), $v->get_sale_price(), $v->get_attributes() ); }, $after->get_children() ),
	);
	$out['waiting_after'] = ACE_PC_Cleaner::waiting();
	$out['own_untouched'] = wc_get_product( $own_id )->get_name() === 'Rimfya lampka';

	try { ACE_PC_Cleaner::clean( $pid ); $out['second_run'] = 'ran again (BAD)'; } catch ( Exception $e ) { $out['second_run'] = 'refused: ' . $e->getMessage(); }

	ACE_PC_Cleaner::undo( $pid );
	$back = wc_get_product( $pid );
	$out['undo'] = array(
		'name' => $back->get_name(), 'sku' => $back->get_sku(), 'marker' => get_post_meta( $pid, '_aliclean', true ),
		'attrs' => array_map( function ( $a ) { return $a->get_name(); }, array_values( $back->get_attributes() ) ),
		'variations' => array_map( function ( $vid ) { $v = wc_get_product( $vid ); return array( $v->get_status(), $v->get_regular_price(), $v->get_sale_price(), $v->get_attributes() ); }, $back->get_children() ),
		'waiting_again' => ACE_PC_Cleaner::waiting(),
	);
} catch ( Throwable $e ) {
	$out['ERROR'] = get_class( $e ) . ': ' . $e->getMessage() . ' @ ' . basename( $e->getFile() ) . ':' . $e->getLine();
}
try {
	wp_set_current_user( 1 ); require_once ABSPATH . 'wp-admin/includes/template.php'; require_once ABSPATH . 'wp-admin/includes/plugin.php';
	ob_start(); ACE_PC_Settings::page(); $html = ob_get_clean();
	$out['settings_page'] = array( 'bytes' => strlen( $html ), 'has_waiting_heading' => false !== strpos( $html, 'Waiting for cleanup' ), 'has_key_notice' => false !== strpos( $html, 'Anthropic API key' ), 'lists_import' => false !== strpos( $html, 'Neon Rope Lights' ) );
	ACE_PC_Settings::cron(); $out['cron_without_key'] = 'ok (did nothing)';
	$no_key = null;
	try { ACE_PC_Cleaner::clean( $pid ); } catch ( Exception $e ) { $no_key = $e->getMessage(); }
	$out['no_key_message'] = $no_key;
} catch ( Throwable $e ) { $out['settings_ERROR'] = $e->getMessage() . ' @ ' . basename( $e->getFile() ) . ':' . $e->getLine(); }
// Real DeepSeek endpoint with a deliberately wrong key: proves URL, headers and error handling.
try {
	remove_all_filters( 'ace_pc_llm_json' );
	try { ACE_PC_Cleaner::undo( $pid ); } catch ( Exception $e ) {}
	update_option( 'ace_pc_settings', array( 'provider' => 'deepseek', 'keys' => array( 'deepseek' => 'sk-test-wrong-key' ), 'model' => '', 'auto' => 1, 'category' => 'Oświetlenie', 'sku_prefix' => '2AC-' ) );
	$o = ACE_PC_Settings::get();
	$out['deepseek_provider_loaded'] = array( $o['provider'], $o['api_key'] );
	foreach ( array( 'list_models' => function () use ( $o ) { return ACE_PC_Cleaner::list_models( $o ); }, 'clean' => function () use ( $pid ) { return ACE_PC_Cleaner::clean( $pid ); } ) as $k => $fn ) {
		try { $fn(); $out[ 'deepseek_' . $k ] = 'UNEXPECTED success'; } catch ( Exception $e ) { $out[ 'deepseek_' . $k ] = $e->getMessage(); }
	}
	update_option( 'ace_pc_settings', array( 'provider' => 'anthropic', 'keys' => array( 'anthropic' => 'sk-ant-wrong' ), 'model' => '', 'auto' => 1, 'category' => 'Oświetlenie', 'sku_prefix' => '2AC-' ) );
	try { ACE_PC_Cleaner::list_models( ACE_PC_Settings::get() ); } catch ( Exception $e ) { $out['anthropic_list_models'] = $e->getMessage(); }
} catch ( Throwable $e ) { $out['provider_ERROR'] = $e->getMessage() . ' @ ' . basename( $e->getFile() ) . ':' . $e->getLine(); }
file_put_contents( '/playground/_cleaner-result.json', wp_json_encode( $out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
