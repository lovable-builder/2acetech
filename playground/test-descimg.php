<?php
// Test: supplier (AliExpress) images are removed from the product description on the product page only.
// Writes /playground/_descimg-result.json
$out = array();
try {
	$desc = '<p>Real text.</p>'
		. '<p><img src="https://ae-pic-a1.aliexpress-media.com/kf/S1.jpg_960x960.jpg" alt="x" /></p>'
		. '<a href="https://x.test"><img class="lazy" data-src="//ae01.alicdn.com/kf/S2.jpg" src="data:image/gif;base64,R0lGOD" /></a>'
		. '<p><img src=\'https://ae-pic-a1.aliexpress-media.com/kf/S3.jpg\'></p>'
		. '<p><img src="https://yywaiimagecreation-1253952653.cos.ap-guangzhou.myqcloud.com/results/x.png"><img src="//third-1253952653.cos.ap-guangzhou.myqcloud.com/y.jpg"></p>'
		. '<p>More text.</p><p><img src="' . home_url( '/wp-content/uploads/2026/11/own.webp' ) . '" alt="own"></p>'
		. '<p><img src="/wp-content/uploads/2026/11/relative.webp" alt="rel"></p>';
	$p = new WC_Product_Simple();
	$p->set_name( 'Desc test' );
	$p->set_status( 'publish' );
	$p->set_description( $desc );
	$pid = $p->save();

	// A product page query, inside the loop.
	$q = new WP_Query( array( 'p' => $pid, 'post_type' => 'product' ) );
	$GLOBALS['wp_query'] = $q;
	$GLOBALS['wp_the_query'] = $q;
	$q->the_post();
	$out['is_product()'] = is_product();
	$out['in_the_loop()'] = in_the_loop();

	$html = apply_filters( 'the_content', $desc );
	$out['no aliexpress image left'] = false === stripos( $html, 'aliexpress' ) && false === stripos( $html, 'alicdn' );
	$out['no tencent image left'] = false === stripos( $html, 'myqcloud' );
	$out['own image kept'] = false !== strpos( $html, 'own.webp' );
	$out['relative image kept'] = false !== strpos( $html, 'relative.webp' );
	$out['text kept'] = false !== strpos( $html, 'Real text.' ) && false !== strpos( $html, 'More text.' );
	$out['no empty link / paragraph'] = false === strpos( $html, '<a href="https://x.test"></a>' ) && 1 !== preg_match( '#<p>\s*</p>#', $html );

	wp_reset_postdata();
	$GLOBALS['wp_query'] = new WP_Query( array( 'post_type' => 'post' ) );
	$GLOBALS['wp_the_query'] = $GLOBALS['wp_query'];
	$out['untouched outside a product page'] = ace_strip_supplier_images( $desc ) === $desc;
	$out['output sample'] = $html;
} catch ( Throwable $e ) {
	$out['ERROR'] = $e->getMessage() . ' @ ' . basename( $e->getFile() ) . ':' . $e->getLine();
}
file_put_contents( '/playground/_descimg-result.json', wp_json_encode( $out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) );
