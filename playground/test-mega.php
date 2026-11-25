<?php
// Test: AliExpress-style mega menu. Writes /playground/_mega-result.json
$out = array();
try {
	$sport = wp_insert_term( 'Sport i turystyka', 'product_cat' )['term_id'];
	$camp  = wp_insert_term( 'Camping', 'product_cat', array( 'parent' => $sport ) )['term_id'];
	$mat   = wp_insert_term( 'Materace i maty', 'product_cat', array( 'parent' => $camp ) )['term_id'];
	$light = wp_insert_term( 'Oświetlenie', 'product_cat' )['term_id'];
	$mk = function ( $name, $cat, $brand ) {
		$p = new WC_Product_Simple(); $p->set_name( $name ); $p->set_regular_price( '50' ); $p->set_status( 'publish' ); $id = $p->save();
		wp_set_object_terms( $id, array( (int) $cat ), 'product_cat' );
		if ( $brand ) { wp_set_object_terms( $id, $brand, 'product_brand' ); }
		return $id;
	};
	$mk( 'Mata', $mat, 'Naturehike' ); $mk( 'Lampa', $light, 'Jelena' ); $mk( 'Lampa 2', $light, 'Naturehike' );

	delete_transient( 'ace_mega_data_v1' );
	$data = ace_mega_data();
	$out['top-level count'] = count( $data );
	$by = array();
	foreach ( $data as $t ) { $by[ $t['label'] ] = array( 'kids' => wp_list_pluck( $t['kids'], 'label' ), 'brands' => wp_list_pluck( $t['brands'], 'label' ) ); }
	$out['tree'] = $by;
	$out['Sport has Naturehike only'] = array( 'Naturehike' ) === $by['Sport i turystyka']['brands'];
	$out['Lighting has both brands'] = 2 === count( $by['Oświetlenie']['brands'] );
	$out['cached'] = is_array( get_transient( 'ace_mega_data_v1' ) );
	$mk( 'Nowa', $light, 'Jelena' );
	$out['cache cleared on product save'] = false === get_transient( 'ace_mega_data_v1' );

	switch_to_locale( 'pl_PL' ); load_theme_textdomain( '2acetech', get_theme_file_path( 'languages' ) );
	ob_start(); ace_mega_render( ace_mega_data() ); $html = ob_get_clean();
	$out['2 category items'] = 2 === substr_count( $html, 'data-mega-item=' );
	$out['first item active'] = 1 === substr_count( $html, 'mega__item is-active' );
	$po = file_get_contents( get_theme_file_path( 'languages/pl_PL.po' ) );
	$out['Recommended heading (English source)'] = false !== strpos( $html, 'Recommended' ) || false !== strpos( $html, 'Polecane' );
	$out['brand heading (English source)'] = false !== strpos( $html, 'Shop by brand' ) || false !== strpos( $html, 'Kupuj według marki' );
	$out['Polish strings in pl_PL.po'] = false !== strpos( $po, 'msgstr "Polecane"' ) && false !== strpos( $po, 'msgstr "Kupuj według marki"' );
	$out['third level group heading'] = false !== strpos( $html, '>Camping</a></h3>' );
	$out['locale'] = get_locale();
	$out['third-level tile'] = false !== strpos( $html, 'Materace i maty' );
	$out['all products link'] = false !== strpos( $html, 'mega__all' );

	ob_start(); get_header(); $h = ob_get_clean();
	$out['header has mega wrapper + panel'] = false !== strpos( $h, 'data-mega' ) && false !== strpos( $h, 'class="mega"' );
	$out['2 catbar triggers'] = 2 === substr_count( $h, 'data-mega-open=' );
	$out['no old flyouts'] = false === strpos( $h, 'catbar__sub' ) && false === strpos( $h, 'departments__panel' );
} catch ( Throwable $e ) {
	$out['ERROR'] = $e->getMessage() . ' @ ' . basename( $e->getFile() ) . ':' . $e->getLine();
}
file_put_contents( '/playground/_mega-result.json', wp_json_encode( $out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
