<?php
// Test: the brand strip with category tabs. Writes /playground/_brandstrip-result.json
$out = array();
try {
	$sport = wp_insert_term( 'Sport i turystyka', 'product_cat' )['term_id'];
	$camp  = wp_insert_term( 'Camping', 'product_cat', array( 'parent' => $sport ) )['term_id'];
	$light = wp_insert_term( 'Oświetlenie', 'product_cat' )['term_id'];
	$mk = function ( $name, $cat, $brand ) {
		$p = new WC_Product_Simple(); $p->set_name( $name ); $p->set_regular_price( '50' ); $p->set_status( 'publish' ); $id = $p->save();
		wp_set_object_terms( $id, array( (int) $cat ), 'product_cat' );
		if ( $brand ) { wp_set_object_terms( $id, $brand, 'product_brand' ); }
		return $id;
	};
	$mk( 'Mata', $camp, 'Naturehike' ); $mk( 'Pompka', $camp, 'Widesea' ); $mk( 'Lampa', $light, 'Jelena' ); $mk( 'Lampa 2', $light, 'Naturehike' ); $mk( 'Bez marki', $camp, '' );

	$list = ace_brand_list(); $by = array();
	foreach ( $list as $b ) { $by[ $b['label'] ] = $b['cats']; }
	$out['brand => categories'] = $by;
	$out['Naturehike sells in both main categories (subcategory rolled up to Sport i turystyka)'] = 2 === count( $by['Naturehike'] ) && in_array( 'sport-i-turystyka', $by['Naturehike'], true );
	$out['Jelena only in lighting'] = array( 'oswietlenie' ) === array_values( $by['Jelena'] );

	ob_start(); get_template_part( 'template-parts/brands' ); $html = ob_get_clean();
	$out['tabs: All + 2 categories'] = 3 === substr_count( $html, 'data-brands-tab=' );
	$out['3 brand items with category data'] = 3 === substr_count( $html, 'data-cats=' );
	$out['marquee hooks present'] = false !== strpos( $html, 'data-brands-marquee' ) && false !== strpos( $html, 'data-brands-list' );
	$out['js file exists'] = file_exists( get_theme_file_path( 'assets/js/brands.js' ) );

	// One category only -> no tabs.
	wp_delete_term( $light, 'product_cat' );
	ob_start(); get_template_part( 'template-parts/brands' ); $html2 = ob_get_clean();
	$out['single category: no tabs'] = 0 === substr_count( $html2, 'data-brands-tab=' );
} catch ( Throwable $e ) {
	$out['ERROR'] = $e->getMessage() . ' @ ' . basename( $e->getFile() ) . ':' . $e->getLine();
}
file_put_contents( '/playground/_brandstrip-result.json', wp_json_encode( $out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
