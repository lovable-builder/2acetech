<?php
// Test: products that used to get stuck in the cleaner. Writes /playground/_stuck-result.json
$out = array();
try {
	update_option( 'ace_pc_settings', array( 'provider' => 'deepseek', 'keys' => array( 'deepseek' => 'x' ), 'model' => 'm', 'auto' => 1, 'category' => 'Sport', 'sku_prefix' => '2AC-' ) );
	$answer = function ( $attr = null ) {
		return array(
			'title' => 'Ultralekka mata do spania na camping i turystykę',
			'short_bullets' => array( 'Lekka konstrukcja', 'Szybkie pompowanie', 'Mały pakunek', 'Wygodna na biwaku' ),
			'intro' => 'Lekka mata do spania na biwak. Szybko się pompuje. Po złożeniu zajmuje mało miejsca.',
			'features' => array( 'Lekka konstrukcja', 'Szybkie pompowanie', 'Mały pakunek po złożeniu' ),
			'spec_rows' => array( array( 'Materiał', 'Nylon' ), array( 'Waga', '500 g' ), array( 'Zastosowanie', 'Camping' ) ),
			'category' => array( 'Sport', 'Camping' ), 'tags' => array( 'mata do spania', 'camping', 'turystyka' ),
			'attrs' => $attr ? $attr : array(), 'notes' => array(), 'brand' => null,
		);
	};

	// A. Several Poland variations with the same options (the TARKA case).
	$v = new WC_Product_Variable(); $v->set_name( 'TARKA Inflatable Camping Mats' ); $v->set_sku( '1005000000000031' ); $v->set_status( 'publish' ); $v->set_description( 'Inflatable camping mat for hiking.' );
	$a1 = new WC_Product_Attribute(); $a1->set_name( 'Color' ); $a1->set_options( array( 'Army Green' ) ); $a1->set_visible( true ); $a1->set_variation( true );
	$a2 = new WC_Product_Attribute(); $a2->set_name( 'Ships From' ); $a2->set_options( array( 'Poland', 'france' ) ); $a2->set_visible( true ); $a2->set_variation( true );
	$v->set_attributes( array( $a1, $a2 ) );
	$vid = $v->save(); update_post_meta( $vid, '_vi_wad_aliexpress_product_id', 'x' );
	$vars = array();
	foreach ( array( array( 'Poland', '2848.99' ), array( 'Poland', '2834.99' ), array( 'Poland', '2900.00' ), array( 'france', '2790.99' ) ) as $row ) {
		$x = new WC_Product_Variation(); $x->set_parent_id( $vid ); $x->set_attributes( array( 'color' => 'Army Green', 'ships-from' => $row[0] ) ); $x->set_regular_price( $row[1] ); $x->set_status( 'publish' ); $vars[] = array( $x->save(), $row );
	}
	WC_Product_Variable::sync( $vid );
	$calls = 0;
	add_filter( 'ace_pc_llm_json', function () use ( $answer, &$calls ) {
		$calls++;
		return $answer( array( 'Color' => array( 'name' => 'Kolor', 'variation' => true, 'values' => array( 'Army Green' => 'Zielony wojskowy' ) ) ) );
	} );
	$res = ACE_PC_Cleaner::clean( $vid );
	$out['A: cleaned (no more "Duplicate options" failure)'] = true;
	$live = array_filter( $vars, function ( $r ) { return 'publish' === get_post_status( $r[0] ); } );
	$out['A: exactly one variation left on sale'] = count( $live );
	$out['A: it is the cheapest Poland one'] = array_values( $live )[0][1] === array( 'Poland', '2834.99' );
	$out['A: the others are disabled, not deleted'] = array_map( function ( $r ) { return get_post_status( $r[0] ); }, $vars );
	$out['A: product stays published'] = 'publish' === get_post_status( $vid );
	$out['A: note explains it'] = false !== strpos( implode( ' ', (array) get_post_meta( $vid, '_aliclean_notes', true ) ), 'zdublowanych' );
	$out['A: price shown = the cheapest'] = wc_get_product( $vid )->get_price();

	// B. The AI's first answer is not JSON: one more try, then it works.
	remove_all_filters( 'ace_pc_llm_json' );
	$b = new WC_Product_Simple(); $b->set_name( 'Strobe light' ); $b->set_sku( '1005000000000032' ); $b->set_status( 'publish' ); $b->set_regular_price( '100' ); $b->set_description( 'Strobe light.' ); $bid = $b->save();
	update_post_meta( $bid, '_vi_wad_aliexpress_product_id', 'x' );
	$n = 0; $extra = null;
	add_filter( 'ace_pc_llm_json', function ( $x, $source, $e ) use ( $answer, &$n, &$extra ) { $n++; if ( 1 === $n ) { throw new Exception( 'The AI returned no JSON. [finish: length]' ); } $extra = $e; return $answer(); }, 10, 3 );
	ACE_PC_Cleaner::clean( $bid );
	$out['B: second try succeeded'] = 2 === $n && '' !== (string) get_post_meta( $bid, '_aliclean', true );
	$out['B: second try asked for a shorter answer'] = false !== strpos( (string) $extra, 'skróć' );

	// B2. Two bad answers in a row still fail (and say why).
	remove_all_filters( 'ace_pc_llm_json' );
	$b2 = new WC_Product_Simple(); $b2->set_name( 'Strobe two' ); $b2->set_sku( '1005000000000033' ); $b2->set_status( 'publish' ); $b2->set_regular_price( '100' ); $b2->set_description( 'x' ); $b2id = $b2->save();
	update_post_meta( $b2id, '_vi_wad_aliexpress_product_id', 'x' );
	add_filter( 'ace_pc_llm_json', function () { throw new Exception( 'The AI returned no JSON. [finish: length]' ); } );
	try { ACE_PC_Cleaner::clean( $b2id ); $out['B2: gives up after the retry'] = false; } catch ( Exception $e ) { $out['B2: gives up after the retry'] = false !== strpos( $e->getMessage(), 'no JSON' ); }

	// C. The error shows what the provider really answered.
	remove_all_filters( 'ace_pc_llm_json' );
	$reply = null;
	add_filter( 'pre_http_request', function ( $pre, $args, $url ) use ( &$reply ) { if ( false !== strpos( $url, 'deepseek.com' ) ) { return array( 'response' => array( 'code' => 200 ), 'body' => wp_json_encode( $reply ), 'headers' => array() ); } return $pre; }, 10, 3 );
	$rm = new ReflectionMethod( 'ACE_PC_Cleaner', 'complete' ); $rm->setAccessible( true );
	$reply = array( 'choices' => array( array( 'message' => array( 'content' => '' ), 'finish_reason' => 'length' ) ) );
	try { $rm->invoke( null, 'p' ); $out['C: empty answer'] = 'no error'; } catch ( Exception $e ) { $out['C: empty answer'] = $e->getMessage(); }
	$reply = array( 'choices' => array( array( 'message' => array( 'content' => 'Sorry, I cannot help with that request.' ), 'finish_reason' => 'content_filter' ) ) );
	try { $rm->invoke( null, 'p' ); $out['C: refusal'] = 'no error'; } catch ( Exception $e ) { $out['C: refusal'] = $e->getMessage(); }
	$reply = array( 'choices' => array( array( 'message' => array( 'content' => '{"title": "ok"}' ), 'finish_reason' => 'stop' ) ) );
	$out['C: good answer still parsed'] = $rm->invoke( null, 'p' ) === array( 'title' => 'ok' );
} catch ( Throwable $e ) {
	$out['ERROR'] = $e->getMessage() . ' @ ' . basename( $e->getFile() ) . ':' . $e->getLine();
}
file_put_contents( '/playground/_stuck-result.json', wp_json_encode( $out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
