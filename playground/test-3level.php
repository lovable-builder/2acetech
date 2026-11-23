<?php
// Test: Sport i turystyka > Camping > Materace i maty / Pompki / Krzesła i stoły shows up everywhere.
// Writes /playground/_3level-result.json
$out = array();
try {
	$cat = function ( $name, $parent = 0 ) { $t = wp_insert_term( $name, 'product_cat', array( 'parent' => $parent ) ); return (int) $t['term_id']; };
	$sport = $cat( 'Sport i turystyka' ); $camping = $cat( 'Camping', $sport );
	$mat = $cat( 'Materace i maty', $camping ); $pump = $cat( 'Pompki', $camping ); $chair = $cat( 'Krzesła i stoły', $camping );
	$light = $cat( 'Oświetlenie' ); $lamps = $cat( 'Lampki nocne', $light ); $strips = $cat( 'Taśmy LED', $light );
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$mk = function ( $name, $catid ) {
		$p = new WC_Product_Simple(); $p->set_name( $name ); $p->set_status( 'publish' ); $p->set_regular_price( '50' ); $p->set_category_ids( array( $catid ) );
		$im = imagecreatetruecolor( 600, 600 ); imagefill( $im, 0, 0, imagecolorallocate( $im, 40, 90, 200 ) ); ob_start(); imagepng( $im ); $png = ob_get_clean();
		$up = wp_upload_bits( sanitize_title( $name ) . '.png', null, $png );
		$aid = wp_insert_attachment( array( 'post_mime_type' => 'image/png', 'post_title' => $name, 'post_status' => 'inherit' ), $up['file'] );
		wp_update_attachment_metadata( $aid, wp_generate_attachment_metadata( $aid, $up['file'] ) );
		$p->set_image_id( $aid );
		return $p->save();
	};
	for ( $i = 1; $i <= 3; $i++ ) { $mk( "Materac $i", $mat ); }
	$mk( 'Pompka 1', $pump ); $mk( 'Stolik 1', $chair );
	$mk( 'Koc', $camping ); // directly in Camping
	$mk( 'Lampka 1', $lamps ); $mk( 'Taśma 1', $strips );

	// Icon: tourism gets the tent, sport alone keeps the scooter.
	$out['icon: Sport i turystyka'] = ace_guess_art( 'Sport i turystyka sport-i-turystyka' );
	$out['icon: Sport (alone)'] = ace_guess_art( 'Sport sport' );
	$out['icon: Camping'] = ace_guess_art( 'Camping camping' );

	// Menu tree: 3 levels.
	$tree = ace_category_tree( 12 ); $by = array(); foreach ( $tree as $t ) { $by[ $t['label'] ] = $t; }
	$out['nav: top levels'] = array_keys( $by );
	$out['nav: Sport > Camping'] = array_map( function ( $c ) { return $c['label']; }, $by['Sport i turystyka']['children'] );
	$out['nav: Camping > its three subs'] = array_map( function ( $c ) { return $c['label'] . '(' . $c['count'] . ')'; }, $by['Sport i turystyka']['children'][0]['children'] );
	$out['nav: parents count everything below (Sport)'] = $by['Sport i turystyka']['count'];
	$out['nav: two-level category still fine (Oświetlenie)'] = array_map( function ( $c ) { return count( $c['children'] ); }, $by['Oświetlenie']['children'] ) === array( 0, 0 );

	// Hero chips: a main category with ONE subcategory shows what is inside it.
	$slides = ace_category_hero_slides(); $s = array(); foreach ( $slides as $x ) { $s[ $x['name'] ] = $x; }
	$out['hero: Sport chips show Camping\'s subs'] = array_map( function ( $c ) { return $c['name']; }, $s['Sport i turystyka']['subs'] );
	$out['hero: Oświetlenie keeps direct subs'] = array_map( function ( $c ) { return $c['name']; }, $s['Oświetlenie']['subs'] );
	$out['hero: slide uses tent-worthy phrase'] = $s['Sport i turystyka']['tagline'];

	// Header markup: third level rendered in both menus.
	ob_start(); get_header(); $head = ob_get_clean();
	$out['header: sub2 items rendered'] = substr_count( $head, 'menu__sub2' );
	$out['header: Pompki linked'] = false !== strpos( $head, 'Pompki' ) && false !== strpos( $head, 'materace-i-maty' );

	// Category page chips: Camping shows its own subs; a leaf shows its siblings; Sport shows Camping.
	$chips = function ( $term_id ) {
		$GLOBALS['wp_query'] = new WP_Query( array( 'product_cat' => get_term( $term_id )->slug, 'post_type' => 'product' ) );
		$GLOBALS['wp_the_query'] = $GLOBALS['wp_query'];
		ob_start(); ace_category_chips(); $h = ob_get_clean();
		preg_match_all( '#<a class="cat-chip[^"]*" href="[^"]*">([^<]+)#', $h, $m );
		return array_map( 'trim', $m[1] );
	};
	$out['chips: on Camping'] = $chips( $camping );
	$out['chips: on a leaf (Pompki) = siblings'] = $chips( $pump );
	$out['chips: on Sport'] = $chips( $sport );
} catch ( Throwable $e ) {
	$out['ERROR'] = $e->getMessage() . ' @ ' . basename( $e->getFile() ) . ':' . $e->getLine();
}
file_put_contents( '/playground/_3level-result.json', wp_json_encode( $out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
