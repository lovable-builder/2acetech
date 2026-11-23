<?php
// Test: brands are detected while cleaning (created if missing), refused when they are guesses / shops /
// "NoEnName", reused regardless of letter case, undone with Undo, and backfilled for existing products.
// Writes /playground/_brands-result.json
$out = array();
try {
	if ( ! taxonomy_exists( 'product_brand' ) ) {
		register_taxonomy( 'product_brand', 'product', array( 'hierarchical' => true, 'public' => true ) );
	}
	$out['brands taxonomy available'] = taxonomy_exists( 'product_brand' );
	update_option( 'ace_pc_settings', array( 'provider' => 'deepseek', 'keys' => array( 'deepseek' => 'x' ), 'model' => 'm', 'auto' => 1, 'category' => 'Sport', 'sku_prefix' => '2AC-' ) );
	wp_insert_term( 'Rimfya', 'product_brand' ); // an existing brand, like on the live shop

	$mk = function ( $title, $sku, $desc ) {
		$p = new WC_Product_Simple(); $p->set_name( $title ); $p->set_sku( $sku ); $p->set_status( 'publish' ); $p->set_regular_price( '50.20' ); $p->set_description( $desc );
		$id = $p->save(); update_post_meta( $id, '_vi_wad_aliexpress_product_id', $sku ); return $id;
	};
	$answer = function ( $brand ) {
		return array(
			'title' => 'Ultralekka mata do spania na camping i turystykę',
			'short_bullets' => array( 'Lekka konstrukcja', 'Szybkie pompowanie', 'Mały pakunek', 'Wygodna na biwaku' ),
			'intro' => 'Lekka mata do spania na biwak. Szybko się pompuje. Po złożeniu zajmuje mało miejsca.',
			'features' => array( 'Lekka konstrukcja', 'Szybkie pompowanie', 'Mały pakunek po złożeniu' ),
			'spec_rows' => array( array( 'Materiał', 'Nylon' ), array( 'Waga', '500 g' ), array( 'Zastosowanie', 'Camping' ) ),
			'category' => array( 'Sport', 'Camping' ), 'tags' => array( 'mata do spania', 'camping', 'turystyka' ),
			'attrs' => array(), 'notes' => array(), 'brand' => $brand,
		);
	};
	$cur = null; $seen_prompt = null;
	add_filter( 'ace_pc_llm_json', function ( $x, $source ) use ( &$cur, &$seen_prompt ) { $seen_prompt = $source; return $cur; }, 10, 2 );
	$brands_of = function ( $id ) { return wp_get_object_terms( $id, 'product_brand', array( 'fields' => 'names' ) ); };
	$brand_count = function () { return count( get_terms( array( 'taxonomy' => 'product_brand', 'hide_empty' => false ) ) ); };

	// A. A real brand in the title: created and assigned; never in the text.
	$a = $mk( 'Naturehike Air Mattress R3.5 Ultralight Sleeping Pad Camping', '1005000000000011', "Brand Name: Naturehike\nMaterial: Nylon\nUltralight sleeping pad for camping." );
	$cur = $answer( 'Naturehike' ); $n0 = $brand_count();
	ACE_PC_Cleaner::clean( $a );
	$out['A: brand assigned'] = $brands_of( $a );
	$out['A: one new brand created'] = $brand_count() - $n0;
	$out['A: stored on the product'] = get_post_meta( $a, '_aliclean_brand', true );
	$out['A: brand kept out of the title'] = false === stripos( wc_get_product( $a )->get_name(), 'naturehike' );
	$out['A: AI was shown the existing brands'] = isset( $seen_prompt['existing_brands'] ) && in_array( 'Rimfya', $seen_prompt['existing_brands'], true );

	// B. Same brand, different letter case: reused, no duplicate.
	$b = $mk( 'naturehike Self Inflating Pad Outdoor', '1005000000000012', 'Brand Name: naturehike. Self inflating pad.' );
	$cur = $answer( 'naturehike' ); $n1 = $brand_count();
	ACE_PC_Cleaner::clean( $b );
	$out['B: reused, not duplicated'] = $brands_of( $b ) === array( 'Naturehike' ) && $brand_count() === $n1;

	// C. A guess that is not in the source is refused.
	$c = $mk( 'Inflatable Sleeping Pad Outdoor', '1005000000000013', 'Brand Name: aiopp. Inflatable pad.' );
	$cur = $answer( 'Coleman' ); $n2 = $brand_count();
	ACE_PC_Cleaner::clean( $c );
	$out['C: invented brand refused'] = array() === $brands_of( $c ) && $brand_count() === $n2;

	// D. "NoEnName" and friends.
	$d = $mk( 'Folding Camp Table Aluminium', '1005000000000014', 'Brand Name: NoEnName_Null. Folding table.' );
	$cur = $answer( 'NoEnName_Null' );
	ACE_PC_Cleaner::clean( $d );
	$out['D: NoEnName refused'] = array() === $brands_of( $d );

	// E. A shop name.
	$e = $mk( 'Camp Chair Ultralight Tuomico Official Store', '1005000000000015', 'Brand Name: Tuomico Official Store. Chair.' );
	$cur = $answer( 'Tuomico Official Store' );
	ACE_PC_Cleaner::clean( $e );
	$out['E: shop name refused'] = array() === $brands_of( $e );

	// F. All-lowercase source brand is tidied (aiopp -> Aiopp) when it is genuinely in the source.
	$f = $mk( 'aiopp Camping Lantern Rechargeable', '1005000000000016', 'Brand Name: aiopp. Lantern.' );
	$cur = $answer( 'aiopp' );
	ACE_PC_Cleaner::clean( $f );
	$out['F: lowercase brand capitalised'] = $brands_of( $f );

	// G. Unit checks of the validator.
	$blob = 'Brand Name: 3M Lobiston Sleeping Bag Mainland China Poland';
	$out['G: 3M accepted'] = '3M' === ACE_PC_Cleaner::valid_brand( '3M', $blob );
	$out['G: null / empty / array refused'] = '' === ACE_PC_Cleaner::valid_brand( null, $blob ) && '' === ACE_PC_Cleaner::valid_brand( '', $blob ) && '' === ACE_PC_Cleaner::valid_brand( array( 'x' ), $blob );
	$out['G: country refused'] = '' === ACE_PC_Cleaner::valid_brand( 'Mainland China', $blob ) && '' === ACE_PC_Cleaner::valid_brand( 'Poland', $blob );
	$out['G: too long refused'] = '' === ACE_PC_Cleaner::valid_brand( str_repeat( 'Lobiston ', 6 ), str_repeat( 'Lobiston ', 6 ) );
	$out['G: url refused'] = '' === ACE_PC_Cleaner::valid_brand( 'www.shop.com', 'www.shop.com' );

	// H. Undo restores the previous (empty) brands.
	ACE_PC_Cleaner::undo( $a );
	$out['H: undo removes the brand'] = array() === $brands_of( $a ) && '' === (string) get_post_meta( $a, '_aliclean_brand', true );

	// I. Backfill for existing products: uses the ORIGINAL title (from the backup), skips branded + own products.
	$g = $mk( 'Lobiston Down Sleeping Bag 800FP Ultralight', '1005000000000017', 'Warm down sleeping bag.' );
	$cur = $answer( null ); // cleaned without a brand
	ACE_PC_Cleaner::clean( $g );
	$out['I: cleaned product has no brand yet'] = array() === $brands_of( $g );
	$own = new WC_Product_Simple(); $own->set_name( 'Rimfya lampka' ); $own->set_sku( 'RMF-LB-9' ); $own->set_status( 'publish' ); $own_id = $own->save();
	update_post_meta( $own_id, '_vi_wad_aliexpress_product_id', 'x' );
	$asked = null;
	add_filter( 'ace_pc_brands_json', function ( $x, $items ) use ( &$asked, $g, $a, $b ) { $asked = array_column( $items, 'title', 'id' ); return array( 'brands' => array( $g => 'Lobiston', $a => 'Naturehike' ) ); }, 10, 2 );
	$got = ACE_PC_Cleaner::recognise_brands( array( $g, $b, $own_id, $a ) );
	$out['I: backfill result'] = $got;
	$out['I: the AI was shown the original title'] = isset( $asked[ $g ] ) && false !== strpos( $asked[ $g ], 'Lobiston Down' );
	$out['I: branded product ($b) not asked again'] = ! isset( $asked[ $b ] );
	$out['I: own product skipped'] = ! isset( $asked[ $own_id ] );
	$out['I: assigned'] = $brands_of( $g );
	$again = ACE_PC_Cleaner::recognise_brands( array( $g ) );
	$out['I: second run finds nothing to do'] = array() === $again;
} catch ( Throwable $e ) {
	$out['ERROR'] = $e->getMessage() . ' @ ' . basename( $e->getFile() ) . ':' . $e->getLine();
}
file_put_contents( '/playground/_brands-result.json', wp_json_encode( $out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
