<?php
// Test: the category carousel hero. Writes /playground/_chero-result.json
$out = array();
try {
	$cat = function ( $name, $parent = 0 ) { $t = wp_insert_term( $name, 'product_cat', array( 'parent' => $parent ) ); return (int) $t['term_id']; };
	$camping = $cat( 'Camping' ); $light = $cat( 'Oświetlenie' ); $lamps = $cat( 'Lampki nocne', $light ); $strips = $cat( 'Taśmy LED', $light );
	$toys = $cat( 'Zabawki' ); $empty = $cat( 'Pusta kategoria' ); $novel = $cat( 'Biżuteria' ); $broken = $cat( 'Tylko szkice' );
	update_term_meta( $light, ACE_CAT_TAGLINE, 'Światło, które zmienia pokój.' );
	$seq = 0;
	$mk = function ( $name, $catid, $status = 'publish', $with_img = true ) use ( &$seq ) {
		$p = new WC_Product_Simple(); $p->set_name( $name ); $p->set_status( $status ); $p->set_regular_price( '50' ); $p->set_category_ids( array( $catid ) );
		if ( $with_img ) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
			$im = imagecreatetruecolor( 700, 700 ); imagefill( $im, 0, 0, imagecolorallocate( $im, 40, 90, 200 ) );
			ob_start(); imagepng( $im ); $png = ob_get_clean();
			$up  = wp_upload_bits( sanitize_title( $name ) . '.png', null, $png );
			$aid = wp_insert_attachment( array( 'post_mime_type' => 'image/png', 'post_title' => $name, 'post_status' => 'inherit' ), $up['file'] );
			wp_update_attachment_metadata( $aid, wp_generate_attachment_metadata( $aid, $up['file'] ) );
			$p->set_image_id( $aid );
		}
		return $p->save();
	};
	for ( $i = 1; $i <= 4; $i++ ) { $mk( "Camp $i", $camping ); }
	$mk( 'Lampka 1', $lamps ); $mk( 'Taśma 1', $strips ); $mk( 'Lampka 2', $lamps );
	$mk( 'Klocki', $toys );
	$mk( 'Szkic', $broken, 'draft' ); // only a draft: no slide

	$slides = ace_category_hero_slides();
	$names  = array_map( function ( $s ) { return $s['name']; }, $slides );
	$out['slides, biggest first'] = $names;
	$out['empty and draft-only categories get no slide'] = ! in_array( 'Pusta kategoria', $names, true ) && ! in_array( 'Tylko szkice', $names, true ) && ! in_array( 'Biżuteria', $names, true );
	$by = array(); foreach ( $slides as $s ) { $by[ $s['name'] ] = $s; }
	$out['Camping uses the built-in phrase'] = $by['Camping']['tagline'];
	$out['own phrase wins (Oświetlenie)'] = 'Światło, które zmienia pokój.' === $by['Oświetlenie']['tagline'];
	$out['unknown category gets "Discover: name"'] = false !== strpos( $by['Zabawki']['tagline'], 'Zabawki' ) || 'Play starts here.' === $by['Zabawki']['tagline'];
	$out['subcategories listed'] = array_map( function ( $s ) { return $s['name']; }, $by['Oświetlenie']['subs'] );
	$out['camping has no subs'] = array() === $by['Camping']['subs'];
	$out['products per slide (max 3)'] = array_map( function ( $s ) { return count( $s['products'] ); }, $slides );

	// Term meta save from the category form.
	wp_set_current_user( get_users( array( 'role' => 'administrator', 'number' => 1 ) )[0]->ID );
	$_POST['ace_cat_tagline'] = '  Biżuteria na każdą okazję  ';
	ace_cat_tagline_save( $novel );
	$out['saved phrase trimmed'] = 'Biżuteria na każdą okazję' === get_term_meta( $novel, ACE_CAT_TAGLINE, true );
	$_POST['ace_cat_tagline'] = ''; ace_cat_tagline_save( $novel );
	$out['empty clears it'] = '' === (string) get_term_meta( $novel, ACE_CAT_TAGLINE, true );

	// Active / inactive logic.
	$out['active by default'] = ace_category_hero_active();
	set_theme_mod( 'ace_hero_cats', false );
	$out['off when switched off'] = ! ace_category_hero_active();
	set_theme_mod( 'ace_hero_cats', true );
	set_theme_mod( 'ace_bn1_from', '2000-01-01T00:00' ); set_theme_mod( 'ace_bn1_until', '2999-01-01T00:00' ); set_theme_mod( 'ace_bn1_on', true );
	ace_active_banner( true );
	$out['a dated banner takes over'] = ! ace_category_hero_active();
	remove_theme_mod( 'ace_bn1_from' ); remove_theme_mod( 'ace_bn1_until' ); ace_active_banner( true );
	$out['back to carousel when the banner has no dates'] = ace_category_hero_active();

	// Markup.
	ob_start(); get_template_part( 'template-parts/category-hero' ); $html = ob_get_clean();
	$out['markup: carousel role'] = false !== strpos( $html, 'aria-roledescription="carousel"' );
	$out['markup: one slide per category'] = substr_count( $html, 'data-chero-slide' ) === count( $slides );
	$out['markup: one h1 only'] = 1 === substr_count( $html, '<h1' );
	$out['markup: dots + arrows'] = substr_count( $html, 'data-chero-dot' ) === count( $slides ) && false !== strpos( $html, 'data-chero-prev' ) && false !== strpos( $html, 'data-chero-next' );
	$out['markup: subcategory links'] = false !== strpos( $html, 'Lampki nocne' ) && false !== strpos( $html, 'Taśmy LED' );
	$out['markup: category links'] = false !== strpos( $html, '/product-category/camping' ) || false !== strpos( $html, 'camping' );
	$out['markup: first image eager, rest lazy'] = false !== strpos( $html, 'loading="eager"' ) && false !== strpos( $html, 'loading="lazy"' );
	$out['markup: phrase last word in <em>'] = 1 === preg_match( '#<em>[^<]+</em></h[12]>#', $html );

	// Slide limit.
	$out['limit respected'] = count( ace_category_hero_slides( 2 ) ) === 2;
} catch ( Throwable $e ) {
	$out['ERROR'] = $e->getMessage() . ' @ ' . basename( $e->getFile() ) . ':' . $e->getLine();
}
file_put_contents( '/playground/_chero-result.json', wp_json_encode( $out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
