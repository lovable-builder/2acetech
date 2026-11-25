<?php
// Test: 2ACE Popups plugin. Writes /playground/_popups-result.json
$out = array();
try {
	$out['plugin classes'] = class_exists( 'ACEPU_Store' ) && class_exists( 'ACEPU_Front' ) && class_exists( 'ACEPU_Admin' ) && class_exists( 'ACEPU_AI' );
	$out['post type registered'] = post_type_exists( 'ace_popup' );
	global $wpdb;
	$out['tables exist'] = ACEPU_Store::stats_table() === $wpdb->get_var( "SHOW TABLES LIKE '" . ACEPU_Store::stats_table() . "'" ) && ACEPU_Store::leads_table() === $wpdb->get_var( "SHOW TABLES LIKE '" . ACEPU_Store::leads_table() . "'" );

	/* ---- Sanitising ---- */
	$c = ACEPU_Store::sanitize( array( 'layout' => 'evil', 'bg' => 'red', 'accent' => '#abc', 'radius' => 999, 'width' => 10, 'triggers' => array( 'time', 'hack' ), 'pages' => array( 'home', 'xx' ), 'start' => '2026-12-01T10:00', 'end' => '2026-11-01T10:00', 'headline' => '<b>Hi</b>', 'cta_url' => 'javascript:alert(1)', 'cats' => array( '5', 'x', '5' ), 'hour_from' => 20, 'hour_to' => 5, 'devices' => 'nope' ) );
	$out['bad layout falls back'] = 'split' === $c['layout'];
	$out['bad colour falls back, short hex kept'] = '#ffffff' === $c['bg'] && '#abc' === $c['accent'];
	$out['numbers clamped'] = 40 === $c['radius'] && 320 === $c['width'];
	$out['multi filtered'] = array( 'time' ) === $c['triggers'] && array( 'home' ) === $c['pages'] && array() === $c['devices'];
	$out['end before start dropped'] = '' === $c['end'] && '2026-12-01T10:00' === $c['start'];
	$out['html stripped, js url refused'] = 'Hi' === $c['headline'] && '' === $c['cta_url'];
	$out['ids unique ints'] = array( 5 ) === $c['cats'];
	$out['hours repaired'] = 0 === $c['hour_from'] && 24 === $c['hour_to'];

	/* ---- Presets render ---- */
	$bad = array();
	foreach ( acepu_presets() as $k => $p ) {
		$s = ACEPU_Store::sanitize( wp_parse_args( $p['set'], ACEPU_Store::defaults() ) );
		$h = ACEPU_Render::html( 1, $s );
		if ( false === strpos( $h, 'acepu__title' ) || ( 'email' === $s['mode'] && false === strpos( $h, 'data-acepu-form' ) ) || ( 'code' === $s['mode'] && false === strpos( $h, 'data-acepu-copy' ) ) || false !== strpos( $h, '{discount}' ) || false !== strpos( $h, '{coupon_code}' ) ) {
			$bad[] = $k;
		}
	}
	$out['all presets render cleanly'] = array() === $bad;
	$out['bad presets'] = $bad;
	$out['8 presets'] = 8 === count( acepu_presets() );

	/* ---- Render details ---- */
	$mail = ACEPU_Store::sanitize( wp_parse_args( acepu_presets()['welcome']['set'], ACEPU_Store::defaults() ) );
	$h    = ACEPU_Render::html( 7, $mail );
	$out['consent box is required and not pre-ticked'] = (bool) preg_match( '/name="c1" value="1" required>/', $h ) && false === strpos( $h, 'checked' );
	$out['newsletter box optional'] = (bool) preg_match( '/name="c2" value="1">/', $h );
	$out['honeypot present'] = false !== strpos( $h, 'name="website"' );
	$out['{discount} replaced'] = false !== strpos( $h, '10%' );
	$out['live markup starts hidden'] = (bool) preg_match( '/<div class="acepu [^"]*" id="acepu-7"[^>]*\shidden/', $h );
	$st = ACEPU_Render::html( 7, $mail, array( 'static' => true ) );
	$out['static preview not hidden, has is-open'] = false !== strpos( $st, 'acepu--static' ) && false !== strpos( $st, 'is-open' ) && ! preg_match( '/id="acepu-7"[^>]*\shidden/', $st );
	$out['close button + no-thanks'] = false !== strpos( $h, 'data-acepu-close' ) && false !== strpos( $h, 'acepu__no' );
	$bf = ACEPU_Store::sanitize( wp_parse_args( acepu_presets()['blackfriday']['set'] + array( 'end' => gmdate( 'Y-m-d\TH:i', time() + 3 * DAY_IN_SECONDS ) ), ACEPU_Store::defaults() ) );
	$bh = ACEPU_Render::html( 8, $bf );
	$out['countdown only with a real end date'] = false !== strpos( $bh, 'data-end=' ) && false === strpos( ACEPU_Render::html( 8, array_merge( $bf, array( 'end' => '' ) ) ), 'data-end=' );
	$out['code apply link'] = false !== strpos( $bh, 'acepu_coupon=BLACKFRIDAY' );

	/* ---- Frontend selection ---- */
	update_option( 'show_on_front', 'posts' );
	$mk = function ( $title, $set, $status = 'publish' ) {
		$id = wp_insert_post( array( 'post_type' => 'ace_popup', 'post_status' => $status, 'post_title' => $title ) );
		ACEPU_Store::save( $id, ACEPU_Store::sanitize( wp_parse_args( $set, ACEPU_Store::defaults() ) ) );
		return $id;
	};
	$visit = function ( $args ) {
		ACEPU_Front::reset();
		$q = new WP_Query();
		$GLOBALS['wp_the_query'] = $q; $GLOBALS['wp_query'] = $q;
		$q->query( $args );
		return ACEPU_Front::chosen();
	};
	$pA = $mk( 'A home', array( 'headline' => 'A', 'pages' => array( 'home' ), 'login' => 'all' ) );
	$pB = $mk( 'B product only', array( 'headline' => 'B', 'pages' => array( 'product' ), 'login' => 'all' ) );
	$pC = $mk( 'C ended', array( 'headline' => 'C', 'pages' => array( 'home' ), 'login' => 'all', 'start' => '2020-01-01T10:00', 'end' => '2020-02-01T10:00' ) );
	$pD = $mk( 'D draft', array( 'headline' => 'D', 'pages' => array( 'home' ), 'login' => 'all' ), 'draft' );
	$pE = $mk( 'E logged in only', array( 'headline' => 'E', 'pages' => array( 'home' ), 'login' => 'in' ) );
	$pF = $mk( 'F url', array( 'headline' => 'F', 'pages' => array( 'home' ), 'login' => 'all', 'url_in' => "/nowhere-special/" ) );
	wp_set_current_user( 0 );
	$got = array_keys( $visit( array() ) );
	$out['home: only the matching live pop-up'] = array( $pA ) === $got;
	$out['chosen on home'] = $got;
	$out['not on products / ended / draft / url-restricted'] = ! in_array( $pB, $got, true ) && ! in_array( $pC, $got, true ) && ! in_array( $pD, $got, true ) && ! in_array( $pF, $got, true );
	$out['logged-in only is hidden from guests'] = ! in_array( $pE, $got, true );
	$uid = self_factory_user();
	wp_set_current_user( $uid );
	$out['logged in: E appears'] = in_array( $pE, array_keys( $visit( array() ) ), true );
	wp_set_current_user( 0 );
	$o = ACEPU_Store::options(); $o['pause'] = 1; update_option( ACEPU_Store::OPTION, $o );
	$out['pause switches everything off'] = array() === $visit( array() );
	$o['pause'] = 0; update_option( ACEPU_Store::OPTION, $o );

	// Config for the script.
	$cfg = null;
	ACEPU_Front::reset();
	$visit( array() );
	ob_start();
	ACEPU_Front::assets();
	ob_end_clean();
	$out['assets enqueued when something qualifies'] = wp_script_is( 'acepu-front', 'enqueued' ) && wp_style_is( 'acepu-front', 'enqueued' );
	$inline = wp_scripts()->get_data( 'acepu-front', 'before' );
	$out['config printed'] = is_array( $inline ) && false !== strpos( implode( '', $inline ), '"popups":[{"id":' . $pA );
	ob_start(); ACEPU_Front::markup(); $mk_html = ob_get_clean();
	$out['markup in <template>'] = false !== strpos( $mk_html, '<template id="acepu-tpl-' . $pA . '">' );

	/* ---- Sign-up flow ---- */
	$mailbox = array();
	add_filter( 'pre_wp_mail', function ( $null, $a ) use ( &$mailbox ) { $mailbox[] = $a; return true; }, 10, 2 );
	$em = $mk( 'Email popup', array_merge( acepu_presets()['welcome']['set'], array( 'login' => 'all', 'gen_amount' => 15, 'gen_days' => 7, 'gen_min' => 100, 'gen_nosale' => 1, 'gen_prefix' => 'tst' ) ) );
	$req = function ( $params ) {
		$r = new WP_REST_Request( 'POST', '/ace-popups/v1/signup' );
		foreach ( $params as $k => $v ) { $r->set_param( $k, $v ); }
		return ACEPU_Front::rest_signup( $r );
	};
	$r = $req( array( 'popup' => $em, 'email' => 'nope', 'c1' => '1' ) );
	$out['invalid e-mail refused'] = 422 === $r->get_status();
	$r = $req( array( 'popup' => $em, 'email' => 'anna@example.com' ) );
	$out['no consent, no code'] = 422 === $r->get_status() && 0 === (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . ACEPU_Store::leads_table() );
	$r = $req( array( 'popup' => $em, 'email' => 'anna@example.com', 'c1' => '1', 'website' => 'http://spam' ) );
	$out['honeypot refused'] = 400 === $r->get_status();
	$r = $req( array( 'popup' => $em, 'email' => 'Anna@Example.com', 'c1' => '1', 'c2' => '1' ) );
	$d = $r->get_data();
	$out['sign-up ok with code'] = 200 === $r->get_status() && ! empty( $d['ok'] ) && 0 === strpos( $d['code'], 'TST-' );
	$cid = wc_get_coupon_id_by_code( $d['code'] );
	$cp  = new WC_Coupon( $cid );
	$out['coupon: 15% , one use, 7 days, min 100, no sale items, e-mail locked'] = 'percent' === $cp->get_discount_type() && 15.0 === (float) $cp->get_amount() && 1 === $cp->get_usage_limit() && 1 === $cp->get_usage_limit_per_user() && (float) $cp->get_minimum_amount() === 100.0 && $cp->get_exclude_sale_items() && array( 'anna@example.com' ) === $cp->get_email_restrictions() && $cp->get_individual_use();
	$days = round( ( $cp->get_date_expires()->getTimestamp() - time() ) / DAY_IN_SECONDS );
	$out['coupon expires in about 7 days'] = 7 === (int) $days;
	$lead = $wpdb->get_row( 'SELECT * FROM ' . ACEPU_Store::leads_table() . ' ORDER BY id DESC LIMIT 1' );
	$out['lead stored with both consents + text'] = $lead && 'anna@example.com' === $lead->email && 1 === (int) $lead->consent_service && 1 === (int) $lead->consent_news && '' !== $lead->consent_text && $lead->coupon === $d['code'];
	$out['signup counted'] = 1 === ACEPU_Store::totals( $em )['signup'];
	$out['e-mail sent with the code'] = 1 === count( $mailbox ) && 'anna@example.com' === ( is_array( $mailbox[0]['to'] ) ? $mailbox[0]['to'][0] : $mailbox[0]['to'] ) && false !== strpos( $mailbox[0]['message'], $d['code'] ) && false !== strpos( $mailbox[0]['message'], 'acepu_coupon=' );
	$r2 = $req( array( 'popup' => $em, 'email' => 'anna@example.com', 'c1' => '1' ) );
	$d2 = $r2->get_data();
	$out['same e-mail gets the same code, no new coupon, no second mail'] = $d2['code'] === $d['code'] && ! empty( $d2['again'] ) && 1 === count( $mailbox ) && 1 === (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='shop_coupon' AND post_status='publish'" );
	$r3 = $req( array( 'popup' => $pA, 'email' => 'bob@example.com', 'c1' => '1' ) );
	$out['sign-up on a non-email pop-up refused'] = 400 === $r3->get_status();
	// Rate limit: 8 per hour per visitor (2 successful + others already counted).
	$last = 0;
	for ( $i = 0; $i < 12; $i++ ) { $last = $req( array( 'popup' => $em, 'email' => "u$i@example.com", 'c1' => '1' ) )->get_status(); }
	$out['rate limit kicks in'] = 429 === $last;

	/* ---- Events ---- */
	$ev = function ( $id, $e ) { $r = new WP_REST_Request( 'POST', '/ace-popups/v1/event' ); $r->set_param( 'id', $id ); $r->set_param( 'event', $e ); return ACEPU_Front::rest_event( $r )->get_status(); };
	$out['view event recorded'] = 200 === $ev( $pA, 'view' ) && 200 === $ev( $pA, 'view' ) && 2 === ACEPU_Store::totals( $pA )['view'];
	$out['bad events refused'] = 400 === $ev( $pA, 'signup' ) && 400 === $ev( $pA, 'drop' ) && 400 === $ev( 999999, 'view' ) && 400 === $ev( $pD, 'view' );
	$out['daily series'] = 14 === count( ACEPU_Store::daily( $pA ) ) && 2 === end( ACEPU_Store::daily( $pA ) );

	/* ---- Applying a coupon to the cart ---- */
	WC()->initialize_session(); WC()->initialize_cart();
	$prod = new WC_Product_Simple(); $prod->set_name( 'Test' ); $prod->set_regular_price( '200' ); $prod->set_status( 'publish' ); $prod->save();
	WC()->cart->add_to_cart( $prod->get_id() );
	WC()->session->set( 'acepu_pending', $d['code'] );
	$cp->set_email_restrictions( array() ); $cp->save(); // The test cart has no billing e-mail yet.
	ACEPU_Coupons::apply_pending( WC()->cart );
	$out['pending coupon applied once the cart has a product'] = WC()->cart->has_discount( $d['code'] ) && null === WC()->session->get( 'acepu_pending' );

	/* ---- AI ---- */
	$out['AI off without the Google plugin'] = false === ACEPU_AI::available();
	$p = ACEPU_AI::parse_text( "```json\n{\"headline\":\"Obóz taniej\",\"text\":\"Hej\",\"bg\":\"#101010\",\"fg\":\"zzz\",\"accent\":\"#f50505\",\"image_prompt\":\"A tent\"}\n```" );
	$out['AI answer parsed (fences, bad colour dropped)'] = is_array( $p ) && 'Obóz taniej' === $p['headline'] && '#101010' === $p['bg'] && ! isset( $p['fg'] ) && 'A tent' === $p['image_prompt'];
	$out['AI garbage rejected'] = is_wp_error( ACEPU_AI::parse_text( 'sorry, no' ) );
	$pr = ACEPU_AI::text_prompt( array( 'occasion' => 'Black Friday', 'brief' => 'camping', 'discount' => '15%', 'mode' => 'email', 'tone' => 'friendly' ) );
	$out['AI prompt carries the rules'] = false !== strpos( $pr, 'Polish' ) && false !== strpos( $pr, '15%' ) && false !== strpos( $pr, 'no unprovable superlatives' ) && false !== strpos( $pr, 'No text, no letters' );
	$out['AI call without connection is a clean error'] = 'acepu_ai_off' === ACEPU_AI::suggest( array( 'occasion' => 'x', 'brief' => 'y', 'discount' => '', 'mode' => 'info', 'tone' => 'z' ) )->get_error_code();

	// Connected AI with mocked Google.
	require_once '/wordpress/wp-content/plugins/2ace-photo-editor/includes/class-google.php';
	update_option( 'ace_pe_settings', array( 'sa' => wp_json_encode( array( 'client_email' => 'sa@proj.iam.gserviceaccount.com', 'private_key' => 'dummy-not-used', 'project_id' => 'proj' ) ) ) );
	set_transient( 'ace_pe_token', 'tok', 3600 ); // The signing step is the photo editor's own and is tested there.
	$png = base64_encode( base64_decode( 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==' ) );
	$seen = array();
	add_filter( 'pre_http_request', function ( $pre, $args, $url ) use ( &$seen, $png ) {
		if ( false !== strpos( $url, 'oauth2.googleapis.com' ) ) {
			return array( 'response' => array( 'code' => 200 ), 'body' => wp_json_encode( array( 'access_token' => 'tok', 'expires_in' => 3600 ) ) );
		}
		if ( false !== strpos( $url, 'aiplatform.googleapis.com' ) ) {
			$seen[] = array( 'url' => $url, 'body' => json_decode( $args['body'], true ), 'auth' => $args['headers']['Authorization'] );
			if ( false !== strpos( $url, 'gemini-2.5-flash' ) ) {
				$txt = wp_json_encode( array( 'eyebrow' => 'Black Friday', 'headline' => 'Camping taniej o 15%', 'text' => 'Wpisz kod w koszyku.', 'cta_label' => 'Do sklepu', 'dismiss_label' => 'Nie teraz', 'badge' => '-15%', 'footnote' => 'Kod jednorazowy.', 'bg' => '#0b0b0d', 'fg' => '#ffffff', 'accent' => '#f50505', 'accent_fg' => '#ffffff', 'image_prompt' => 'tent under stars' ) );
				return array( 'response' => array( 'code' => 200 ), 'body' => wp_json_encode( array( 'candidates' => array( array( 'content' => array( 'parts' => array( array( 'text' => $txt ) ) ) ) ) ) ) );
			}
			return array( 'response' => array( 'code' => 200 ), 'body' => wp_json_encode( array( 'candidates' => array( array( 'content' => array( 'parts' => array( array( 'inlineData' => array( 'mimeType' => 'image/png', 'data' => $png ) ) ) ) ) ) ) ) );
		}
		return $pre;
	}, 10, 3 );
	$out['AI connected'] = true === ACEPU_AI::available();
	$sg = ACEPU_AI::suggest( array( 'occasion' => 'Black Friday', 'brief' => 'camping', 'discount' => '15%', 'mode' => 'email', 'tone' => 'friendly' ) );
	$out['AI texts via Google (mock)'] = is_array( $sg ) && 'Camping taniej o 15%' === $sg['headline'] && '#f50505' === $sg['accent'];
	$out['text request: json mode, right model, bearer token'] = ! empty( $seen ) && false !== strpos( $seen[0]['url'], 'gemini-2.5-flash:generateContent' ) && 'application/json' === $seen[0]['body']['generationConfig']['responseMimeType'] && 'Bearer tok' === $seen[0]['auth'];
	$art = ACEPU_AI::artwork( 'a tent under a starry sky', 'portrait' );
	$out['AI artwork saved to the Media Library'] = is_array( $art ) && $art['id'] > 0 && '1' === get_post_meta( $art['id'], '_acepu_ai', true );
	$last = end( $seen );
	$out['image request: image model, no-text instruction, portrait ratio'] = false !== strpos( $last['url'], 'gemini-3-pro-image' ) && false !== strpos( $last['body']['contents'][0]['parts'][0]['text'], 'no letters' ) && '4:5' === $last['body']['generationConfig']['imageConfig']['aspectRatio'];
	$out['daily budget counted'] = ACEPU_AI::left() === 30 - 2;
	$o = ACEPU_Store::options(); $o['ai_limit'] = 2; update_option( ACEPU_Store::OPTION, $o );
	$out['daily limit stops further calls'] = 'acepu_ai_limit' === ACEPU_AI::artwork( 'a tent under a starry sky', 'portrait' )->get_error_code();

	/* ---- Admin screens ---- */
	require_once ABSPATH . 'wp-admin/includes/admin.php';
	set_current_screen( 'edit-ace_popup' );
	$post = get_post( $em );
	ob_start(); ACEPU_Admin::box_designer( $post ); $box = ob_get_clean();
	$out['designer renders every tab and field'] = 6 === substr_count( $box, 'class="acepu-tab"' ) && false !== strpos( $box, 'name="acepu[headline]"' ) && false !== strpos( $box, 'name="acepu[triggers][]"' ) && false !== strpos( $box, 'name="acepu[accent]"' ) && false !== strpos( $box, 'data-show="mode:email"' ) && false !== strpos( $box, 'data-ai-run="text"' );
	$missing = array();
	foreach ( array_keys( acepu_schema() ) as $k ) { if ( false === strpos( $box, 'acepu[' . $k . ']' ) ) { $missing[] = $k; } }
	$out['every schema field is in the form'] = array() === $missing;
	$out['missing fields'] = $missing;
	ob_start(); ACEPU_Admin::box_side( $post ); $side = ob_get_clean();
	$out['stats box shows a preview link and the #acepu-ID'] = false !== strpos( $side, 'acepu_preview=' ) && false !== strpos( $side, '#acepu-' . $em );
	$u = new WP_User( $uid ); $u->set_role( 'administrator' ); wp_set_current_user( 0 ); wp_set_current_user( $uid );
	ob_start(); ACEPU_Admin::page_templates(); $tp = ob_get_clean();
	$out['template gallery: blank + 8 templates'] = 9 === substr_count( $tp, 'class="acepu-card"' );
	$out['status labels'] = 'live' === ACEPU_Admin::status( get_post( $pA ) )[0] && 'off' === ACEPU_Admin::status( get_post( $pD ) )[0] && 'ended' === ACEPU_Admin::status( get_post( $pC ) )[0];
	ob_start(); ACEPU_Admin::column( 'acepu_r', $em ); $col = ob_get_clean();
	$out['list column shows results'] = false !== strpos( $col, 'shown' );
	ob_start(); ACEPU_Admin::page_leads(); $lp = ob_get_clean();
	$out['leads page lists the sign-up'] = false !== strpos( $lp, 'anna@example.com' );
	ob_start(); ACEPU_Admin::page_settings(); $sp = ob_get_clean();
	$out['settings page'] = false !== strpos( $sp, 'name="pause"' ) && false !== strpos( $sp, 'name="ai_limit"' );

	/* ---- Privacy tools ---- */
	$exp = ACEPU_Admin::export_email( 'anna@example.com' );
	$out['privacy export finds the lead'] = 1 === count( $exp['data'] );
	$er = ACEPU_Admin::erase_email( 'anna@example.com' );
	$out['privacy erase removes it'] = true === $er['items_removed'] && 0 === count( ACEPU_Admin::export_email( 'anna@example.com' )['data'] );

	/* ---- Deleting a pop-up clears its numbers ---- */
	wp_delete_post( $pA, true );
	$out['stats removed with the pop-up'] = 0 === ACEPU_Store::totals( $pA )['view'];
} catch ( Throwable $e ) {
	$out['ERROR'] = $e->getMessage() . ' @ ' . basename( $e->getFile() ) . ':' . $e->getLine();
}

function self_factory_user() {
	return wp_insert_user( array( 'user_login' => 'tester' . wp_rand( 100, 999 ), 'user_pass' => wp_generate_password(), 'role' => 'customer', 'user_email' => 'tester' . wp_rand( 100, 999 ) . '@example.com' ) );
}

file_put_contents( '/playground/_popups-result.json', wp_json_encode( $out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
