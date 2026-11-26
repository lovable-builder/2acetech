<?php
// Test: the AI photo editor plugin, with Google mocked. Writes /playground/_photoeditor-result.json
$out = array();
try {
	require_once ABSPATH . 'wp-admin/includes/image.php';
	require_once ABSPATH . 'wp-admin/includes/template.php';
	require_once ABSPATH . 'wp-admin/includes/plugin.php';

	// A service account with a real RSA key, so the sign-in can be verified.
	// Throwaway key made only for this test.
	$private = <<<'PEM'
-----BEGIN PRIVATE KEY-----
MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQDbmg8HAMpwuhjA
X3SXRI9caWCzeLgQQ+DmjvCEDN+l6qnKTLJlZcQOe+n9ecNjJi3LoFpLdphnhpZm
vMcWph+XPoipAKk/AjSnRvf/j1VsL7NGGkQE45CIjHOm/96LlQi5qwGB+SF8xURt
ob5a8dce8SSJgqTB2B7JMKGcooG04hj+Lxx3cdlyuPrqC0WwUWWLOAqTqRQIbdR4
NxElWAOMzBESQjh/6AVTi+Cp232K/I6ISkGcJbvJHNlgQBPkFY4OBXG05rqS1jPe
6lPR0xHaMw2qZPL6KE3T3nyATvVR/fz8XwKFdeKI1OncENn1gfV2xts7uyu8X2gY
3VPHAPuTAgMBAAECggEAPwKe4jcHXz3+gSBCj27UJcN0xDpUshIP84e7GmCpQW2q
R0one/Gtxiwm8RyhhA793+oETpfaVgYnnJX5GBYVhWujgb6XUWV8Hy09Vn5Vl+O9
8FT0BGUZYRzPyHCtdeaO6NtY5ObLhYYCwuRAfs7al13AypYKWTZ+auMB1DRKvFAF
ZFDrZuN1FpoNCcQuKqLY1V6lHR/3H6zKqRTi9upDtpwHKQ7s+2JBVUfg0NVdg4cy
8tB9So78WtOgXBH37KI01VXOXTAFWu9RwS9FeGivKcTn/bdIYSo3DurpMxbtVIqb
+77jHQcNEMgEu9Gxih2wYsycX/67jQ5xkkbWb08hJQKBgQD730VrsIxIhA9/VY4C
A+tRgtoIkady7UbHyIzZbfnuL6MdSTdhog385qbW4mbDnIAvklMuFkW68ByVDSng
zzbAg5BPuQ7rYD4EylGpTKQtlpmAXJvObdoWkqjnBhFtIF1dK5VJM5CIEwOqRU0c
n06/2I13vwZjGrrTilFBM0zQ7wKBgQDfM2W3O4OfS0LOSv4QqIn/ouW3hDbQnb5j
odinT50pPCLx58tuw8cyAFXt08KlkRzpW65kFOy2gyOo/uNPtbqQQgfKhbDTZei/
YSVsaWwvx72or69zyaXZ9TKBbmUIjvbXA7nDgRC7eyHlaCG0JmfjS7mNkph8pGuQ
ak0/vRa3nQKBgQCPL7GedAJ8Ix9QS0ns2V+IujPLVUp1anCnmNqBrSS9hLVdK8dh
KKh9En7cgF7da3XwFWZWN/EyQdht38rj88jzjpeWyuSxaMg5Hu9nqJtZE1l2gK9O
nmKyHHQKygdUG2cYVXOL5tWOlgiqXrVkxSgWpjxeS+mMo6B8LBDfWROdXwKBgEhj
e/60T5jvKMiNK98tOixtoYEmYTzBnEjl6cVBneAMv+yi16ih2E3QLA5JGpzl9nF8
qY+N9cOvvnYFzKVCdDJrJ+m3+ERnjdfb4u5JNqw8ZLd72zR7Vs3cpzexHRVsgIP1
kl7k0g9r/O9RGXU664CdQ521yLzL+pLI1yrv4ZlFAoGADvijE/m6OVFo9GdiXu6L
BMyF1+VwQGFuGXlpLgHpYEwdH9uFP2LFDSGGmolMzh2Mv89RnklmCJhEsSjr5rbi
I1JQ0zSr7rR+xZEYjP3bghG/WUQy2aScO4yR80oBUhMJVLa4wBA+8D2RbkpKFoUw
xjIlVeTyN1cfq30hNYacqj0=
-----END PRIVATE KEY-----
PEM;
	$key = openssl_pkey_get_private( $private );
	$public = openssl_pkey_get_details( $key )['key'];
	$sa = wp_json_encode( array( 'type' => 'service_account', 'project_id' => 'demo-project', 'private_key_id' => 'k1', 'private_key' => $private, 'client_email' => 'editor@demo-project.iam.gserviceaccount.com', 'client_id' => '1' ) );

	// No credentials yet.
	$r = ACE_PE_Google::edit( 'x', 'image/png', 'p' );
	$out['no credentials: clear error'] = is_wp_error( $r ) && 'ace_pe_nocreds' === $r->get_error_code();
	update_option( ACE_PE_Google::OPTION, array( 'sa' => $sa, 'model' => 'gemini-3-pro-image', 'location' => 'global', 'daily_limit' => 40 ), false );
	$out['credentials parsed'] = ACE_PE_Google::credentials()['project_id'];

	// Mock Google.
	$seen = array( 'token' => null, 'gen' => null, 'gen_headers' => null, 'gen_url' => null, 'count_url' => null );
	$png = function ( $r, $g, $b ) { $im = imagecreatetruecolor( 800, 800 ); imagefill( $im, 0, 0, imagecolorallocate( $im, $r, $g, $b ) ); ob_start(); imagepng( $im ); return ob_get_clean(); };
	$mode = 'ok';
	add_filter( 'pre_http_request', function ( $pre, $args, $url ) use ( &$seen, &$mode, $png ) {
		if ( false !== strpos( $url, 'oauth2.googleapis.com/token' ) ) { $seen['token'] = $args['body']; return array( 'response' => array( 'code' => 200 ), 'body' => wp_json_encode( array( 'access_token' => 'tok123', 'expires_in' => 3600 ) ), 'headers' => array() ); }
		if ( false !== strpos( $url, ':countTokens' ) ) { $seen['count_url'] = $url; return array( 'response' => array( 'code' => 200 ), 'body' => wp_json_encode( array( 'totalTokens' => 1 ) ), 'headers' => array() ); }
		if ( false !== strpos( $url, ':generateContent' ) ) {
			$seen['gen'] = json_decode( $args['body'], true ); $seen['gen_headers'] = $args['headers']; $seen['gen_url'] = $url;
			if ( 'blocked' === $mode ) { return array( 'response' => array( 'code' => 200 ), 'body' => wp_json_encode( array( 'promptFeedback' => array( 'blockReason' => 'SAFETY' ) ) ), 'headers' => array() ); }
			if ( 'api_error' === $mode ) { return array( 'response' => array( 'code' => 403 ), 'body' => wp_json_encode( array( 'error' => array( 'message' => 'Permission denied on resource' ) ) ), 'headers' => array() ); }
			return array( 'response' => array( 'code' => 200 ), 'body' => wp_json_encode( array( 'candidates' => array( array( 'content' => array( 'parts' => array( array( 'inlineData' => array( 'mimeType' => 'image/png', 'data' => base64_encode( $png( 20, 160, 90 ) ) ) ) ) ) ) ) ) ), 'headers' => array() );
		}
		return $pre;
	}, 10, 3 );

	// Sign-in and request shape.
	$t = ACE_PE_Google::token();
	$out['token obtained'] = 'tok123' === $t;
	$parts = explode( '.', $seen['token']['assertion'] );
	$claims = json_decode( base64_decode( strtr( $parts[1], '-_', '+/' ) ), true );
	$out['JWT signature verifies with the public key'] = 1 === openssl_verify( $parts[0] . '.' . $parts[1], base64_decode( strtr( $parts[2], '-_', '+/' ) ), $public, OPENSSL_ALGO_SHA256 );
	$out['JWT claims'] = array( $claims['iss'], $claims['scope'], $claims['aud'], $claims['exp'] - $claims['iat'] );
	$out['grant type'] = $seen['token']['grant_type'];
	$out['token cached (no 2nd sign-in)'] = ( function () use ( &$seen ) { $seen['token'] = null; ACE_PE_Google::token(); return null === $seen['token']; } )();
	$test = ACE_PE_Google::test();
	$out['test(): account + project'] = is_array( $test ) ? array( $test['account'], $test['project'] ) : $test->get_error_message();
	$out['test(): countTokens url'] = false !== strpos( $seen['count_url'], 'aiplatform.googleapis.com/v1/projects/demo-project/locations/global/publishers/google/models/gemini-3-pro-image:countTokens' );

	// Product with photos.
	$mkimg = function ( $name, $rgb ) use ( $png ) {
		$up = wp_upload_bits( $name . '.png', null, $png( $rgb[0], $rgb[1], $rgb[2] ) );
		$id = wp_insert_attachment( array( 'post_mime_type' => 'image/png', 'post_title' => $name, 'post_status' => 'inherit' ), $up['file'] );
		wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $up['file'] ) ); return $id;
	};
	$main = $mkimg( 'main', array( 200, 40, 40 ) ); $g1 = $mkimg( 'g1', array( 40, 40, 200 ) ); $g2 = $mkimg( 'g2', array( 200, 200, 40 ) ); $foreign = $mkimg( 'foreign', array( 0, 0, 0 ) );
	$p = new WC_Product_Variable(); $p->set_name( 'Mata' ); $p->set_status( 'publish' ); $p->set_image_id( $main ); $p->set_gallery_image_ids( array( $g1, $g2 ) );
	$att = new WC_Product_Attribute(); $att->set_name( 'Size' ); $att->set_options( array( 'S' ) ); $att->set_visible( true ); $att->set_variation( true ); $p->set_attributes( array( $att ) );
	$pid = $p->save();
	$v = new WC_Product_Variation(); $v->set_parent_id( $pid ); $v->set_attributes( array( 'size' => 'S' ) ); $v->set_regular_price( 10 ); $v->set_image_id( $main ); $v->set_status( 'publish' ); $vid = $v->save();
	$product = wc_get_product( $pid );

	// Edit.
	$new = ACE_PE_Editor::edit_attachment( $main, 'overlay', $pid );
	$out['edit: new attachment'] = is_int( $new ) ? 'id ' . ( $new !== $main ? 'differs from original' : 'SAME' ) : $new->get_error_message();
	$gen = $seen['gen'];
	$out['request: sends the ORIGINAL photo inline'] = base64_decode( $gen['contents'][0]['parts'][0]['inlineData']['data'] ) === file_get_contents( get_attached_file( $main ) ) && 'image/png' === $gen['contents'][0]['parts'][0]['inlineData']['mimeType'];
	$out['request: asks for an image back'] = array( 'IMAGE' ) === $gen['generationConfig']['responseModalities'];
	$out['request: overlay prompt removes overlays, keeps product text'] = false !== strpos( $gen['contents'][0]['parts'][1]['text'], 'Remove every piece of text' ) && false !== strpos( $gen['contents'][0]['parts'][1]['text'], 'Do NOT remove text, logos or markings that are physically printed' );
	$out['request: bearer token + endpoint'] = 'Bearer tok123' === $seen['gen_headers']['Authorization'] && false !== strpos( $seen['gen_url'], '/models/gemini-3-pro-image:generateContent' );
	$file = get_attached_file( $new ); $bytes = file_get_contents( $file ); $info = getimagesize( $file );
	$out['saved: a valid JPEG'] = 'image/jpeg' === $info['mime'] && "\xFF\xD8" === substr( $bytes, 0, 2 );
	$out['saved: marked as AI-edited (IPTC/XMP)'] = false !== strpos( $bytes, 'digitalsourcetype/compositeWithTrainedAlgorithmicMedia' );
	$out['saved: parent is the product, source recorded'] = (int) wp_get_post_parent_id( $new ) === $pid && (int) get_post_meta( $new, ACE_PE_Editor::SOURCE, true ) === $main;
	$out['saved: thumbnails made'] = ! empty( wp_get_attachment_metadata( $new )['sizes'] );
	$out['original untouched'] = (int) $product->get_image_id() === $main && file_exists( get_attached_file( $main ) );
	$out['"all" prompt differs'] = false !== strpos( ACE_PE_Editor::prompt( 'all' ), 'Remove text and numbers everywhere' );

	// Apply / revert.
	$r = ACE_PE_Editor::apply( $pid, $main, $new ); $product = wc_get_product( $pid );
	$out['apply: main photo replaced'] = true === $r && (int) $product->get_image_id() === $new;
	$out['apply: original goes first in the gallery, rest kept'] = array_map( 'intval', $product->get_gallery_image_ids() ) === array( $main, $g1, $g2 );
	$out['apply: variation photo replaced too'] = (int) wc_get_product( $vid )->get_image_id() === $new;
	$out['apply: swap recorded'] = array( $new => $main ) === get_post_meta( $pid, ACE_PE_Editor::SWAPS, true );
	$out['delete_edit refuses a photo that is in use'] = false === ACE_PE_Editor::delete_edit( $new ) && (bool) get_post( $new );
	$r = ACE_PE_Editor::revert( $pid, $new ); $product = wc_get_product( $pid );
	$out['revert: original back (main + variation)'] = true === $r && (int) $product->get_image_id() === $main && (int) wc_get_product( $vid )->get_image_id() === $main;
	$out['revert: gallery back to how it was'] = array_map( 'intval', $product->get_gallery_image_ids() ) === array( $g1, $g2 );
	$out['revert: swap cleared'] = array() === get_post_meta( $pid, ACE_PE_Editor::SWAPS, true );
	$out['discard keeps the edit (nothing deleted)'] = true === ACE_PE_Editor::discard( $new ) && (bool) get_post( $new );
	$out['saved_edits lists it with its original'] = ( ACE_PE_Editor::saved_edits( $pid )[ $new ] ?? 0 ) === $main;
	$out['original photo still exists'] = (bool) get_post( $main );
	$out['delete_edit removes it only on request'] = true === ACE_PE_Editor::delete_edit( $new ) && ! get_post( $new );

	// A gallery photo: replaced in place.
	$n2 = ACE_PE_Editor::edit_attachment( $g1, 'overlay', $pid ); ACE_PE_Editor::apply( $pid, $g1, $n2 ); $product = wc_get_product( $pid );
	$out['gallery photo replaced in its position'] = array_map( 'intval', $product->get_gallery_image_ids() ) === array( $n2, $g2 ) && (int) $product->get_image_id() === $main;

	// Safety checks.
	$out['belongs: foreign photo refused'] = false === ACE_PE_Editor::belongs( $product, $foreign ) && true === ACE_PE_Editor::belongs( $product, $g2 );
	$bad = ACE_PE_Editor::apply( $pid, $foreign, $n2 );
	$out['apply: wrong source refused'] = is_wp_error( $bad );

	// Google problems are reported, not swallowed.
	$mode = 'blocked'; $e = ACE_PE_Editor::edit_attachment( $g2, 'overlay', $pid );
	$out['blocked answer: clear message'] = is_wp_error( $e ) && false !== strpos( $e->get_error_message(), 'SAFETY' );
	$mode = 'api_error'; $e = ACE_PE_Editor::edit_attachment( $g2, 'overlay', $pid );
	$out['API error: Google\'s message shown'] = is_wp_error( $e ) && false !== strpos( $e->get_error_message(), 'Permission denied' );
	$mode = 'ok';

	// Daily limit.
	delete_option( ACE_PE_Editor::USAGE );
	update_option( ACE_PE_Google::OPTION, array_merge( ACE_PE_Google::settings(), array( 'daily_limit' => 1 ) ), false );
	$one = ACE_PE_Editor::edit_attachment( $g2, 'overlay', $pid ); $two = ACE_PE_Editor::edit_attachment( $g2, 'overlay', $pid );
	$out['daily limit: first ok, second refused'] = is_int( $one ) && is_wp_error( $two ) && 'ace_pe_limit' === $two->get_error_code();
	$out['daily limit: counter'] = ACE_PE_Editor::used_today();

	// The page: never prints the private key.
	update_option( ACE_PE_Google::OPTION, array_merge( ACE_PE_Google::settings(), array( 'daily_limit' => 40 ) ), false );
	wp_set_current_user( get_users( array( 'role' => 'administrator', 'number' => 1 ) )[0]->ID );
	$_GET = array( 'product' => $pid ); ob_start(); ACE_PE_Admin::page(); $html = ob_get_clean();
	$out['page: shows connected account'] = false !== strpos( $html, 'editor@demo-project.iam.gserviceaccount.com' );
	$out['page: NEVER prints the private key'] = false === strpos( $html, 'BEGIN PRIVATE KEY' ) && false === strpos( $html, substr( $private, 40, 60 ) );
	$out['page: a checkbox per product photo'] = substr_count( $html, 'class="acepe-pick"' ) === 3;
	$out['page: shows the AI-edited badge for the swapped gallery photo'] = false !== strpos( $html, 'acepe-revert' );
	$out['page: product list in the dropdown'] = false !== strpos( $html, '>Mata<' );
	$row = ACE_PE_Admin::row_action( array(), get_post( $pid ) );
	$out['row action on the products list'] = isset( $row['ace_pe'] );
	$out['option is not autoloaded'] = 'no' === $GLOBALS['wpdb']->get_var( $GLOBALS['wpdb']->prepare( "SELECT autoload FROM {$GLOBALS['wpdb']->options} WHERE option_name = %s", ACE_PE_Google::OPTION ) ) || 'off' === $GLOBALS['wpdb']->get_var( $GLOBALS['wpdb']->prepare( "SELECT autoload FROM {$GLOBALS['wpdb']->options} WHERE option_name = %s", ACE_PE_Google::OPTION ) );
} catch ( Throwable $e ) {
	$out['ERROR'] = $e->getMessage() . ' @ ' . basename( $e->getFile() ) . ':' . $e->getLine();
}
file_put_contents( '/playground/_photoeditor-result.json', wp_json_encode( $out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
