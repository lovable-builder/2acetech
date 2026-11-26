<?php
/**
 * Admin: Products > AI photo editor (pick a product, pick photos, review before/after, use or discard)
 * and the Google Cloud connection settings.
 *
 * @package 2ace-photo-editor
 */

defined( 'ABSPATH' ) || exit;

class ACE_PE_Admin {

	const PAGE = 'ace-photo-editor';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_post_ace_pe_save', array( __CLASS__, 'save_settings' ) );
		add_action( 'wp_ajax_ace_pe_edit', array( __CLASS__, 'ajax_edit' ) );
		add_action( 'wp_ajax_ace_pe_apply', array( __CLASS__, 'ajax_apply' ) );
		add_action( 'wp_ajax_ace_pe_discard', array( __CLASS__, 'ajax_discard' ) );
		add_action( 'wp_ajax_ace_pe_delete', array( __CLASS__, 'ajax_delete' ) );
		add_action( 'wp_ajax_ace_pe_revert', array( __CLASS__, 'ajax_revert' ) );
		add_action( 'wp_ajax_ace_pe_test', array( __CLASS__, 'ajax_test' ) );
		add_filter( 'post_row_actions', array( __CLASS__, 'row_action' ), 10, 2 );
	}

	public static function menu() {
		add_submenu_page( 'edit.php?post_type=product', __( 'AI photo editor', '2ace-photo-editor' ), __( 'AI photo editor', '2ace-photo-editor' ), 'manage_woocommerce', self::PAGE, array( __CLASS__, 'page' ) );
	}

	public static function row_action( $actions, $post ) {
		if ( 'product' === $post->post_type && current_user_can( 'manage_woocommerce' ) ) {
			$url = admin_url( 'edit.php?post_type=product&page=' . self::PAGE . '&product=' . (int) $post->ID );
			$actions['ace_pe'] = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'AI photo editor', '2ace-photo-editor' ) . '</a>';
		}
		return $actions;
	}

	/* ---- settings ---- */

	public static function save_settings() {
		check_admin_referer( 'ace_pe_save' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Not allowed' );
		}
		$o   = ACE_PE_Google::settings();
		$msg = 'saved';
		if ( ! empty( $_POST['ace_pe_remove'] ) ) {
			$o['sa'] = '';
			delete_transient( 'ace_pe_token' );
			$msg = 'removed';
		} else {
			$raw = isset( $_POST['ace_pe_sa'] ) ? trim( wp_unslash( $_POST['ace_pe_sa'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- JSON, validated below.
			if ( '' !== $raw ) {
				$j = json_decode( $raw, true );
				if ( is_array( $j ) && 'service_account' === ( $j['type'] ?? '' ) && ! empty( $j['client_email'] ) && ! empty( $j['private_key'] ) && ! empty( $j['project_id'] ) ) {
					$o['sa'] = wp_json_encode( array_intersect_key( $j, array_flip( array( 'type', 'project_id', 'private_key_id', 'private_key', 'client_email', 'client_id' ) ) ) );
					delete_transient( 'ace_pe_token' );
				} else {
					$msg = 'badjson';
				}
			}
		}
		$o['model']       = preg_replace( '/[^a-zA-Z0-9._-]/', '', isset( $_POST['ace_pe_model'] ) ? wp_unslash( $_POST['ace_pe_model'] ) : $o['model'] ) ?: 'gemini-3-pro-image'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$o['location']    = preg_replace( '/[^a-z0-9-]/', '', strtolower( isset( $_POST['ace_pe_location'] ) ? wp_unslash( $_POST['ace_pe_location'] ) : $o['location'] ) ) ?: 'global'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$o['daily_limit'] = max( 1, min( 500, absint( $_POST['ace_pe_limit'] ?? $o['daily_limit'] ) ) );
		update_option( ACE_PE_Google::OPTION, $o, false ); // Not autoloaded: it holds the private key.
		wp_safe_redirect( admin_url( 'edit.php?post_type=product&page=' . self::PAGE . '&msg=' . $msg ) );
		exit;
	}

	/* ---- ajax ---- */

	private static function guard() {
		check_ajax_referer( 'ace_pe' );
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( 'Not allowed' );
		}
		if ( function_exists( 'set_time_limit' ) ) {
			@set_time_limit( 180 ); // phpcs:ignore
		}
	}

	private static function fail( $e ) {
		wp_send_json_error( is_wp_error( $e ) ? $e->get_error_message() : (string) $e );
	}

	public static function ajax_edit() {
		self::guard();
		$pid  = absint( $_POST['product'] ?? 0 );
		$att  = absint( $_POST['att'] ?? 0 );
		$mode = sanitize_key( $_POST['mode'] ?? 'overlay' );
		$p    = wc_get_product( $pid );
		if ( ! $p || ! ACE_PE_Editor::belongs( $p, $att ) ) {
			self::fail( __( 'This photo does not belong to the product.', '2ace-photo-editor' ) );
		}
		$new = ACE_PE_Editor::edit_attachment( $att, $mode, $pid );
		if ( is_wp_error( $new ) ) {
			self::fail( $new );
		}
		wp_send_json_success(
			array(
				'new'    => $new,
				'before' => wp_get_attachment_image_url( $att, 'large' ),
				'after'  => wp_get_attachment_image_url( $new, 'large' ),
				'left'   => ACE_PE_Editor::left_today(),
			)
		);
	}

	public static function ajax_apply() {
		self::guard();
		$r = ACE_PE_Editor::apply( absint( $_POST['product'] ?? 0 ), absint( $_POST['old'] ?? 0 ), absint( $_POST['new'] ?? 0 ) );
		is_wp_error( $r ) ? self::fail( $r ) : wp_send_json_success();
	}

	public static function ajax_discard() {
		self::guard();
		wp_send_json_success( array( 'kept' => ACE_PE_Editor::discard( absint( $_POST['new'] ?? 0 ) ) ) );
	}

	public static function ajax_delete() {
		self::guard();
		$ok = ACE_PE_Editor::delete_edit( absint( $_POST['new'] ?? 0 ) );
		$ok ? wp_send_json_success() : wp_send_json_error( __( 'This photo is used in a product, so it was not deleted.', '2ace-photo-editor' ) );
	}

	public static function ajax_revert() {
		self::guard();
		$r = ACE_PE_Editor::revert( absint( $_POST['product'] ?? 0 ), absint( $_POST['new'] ?? 0 ) );
		is_wp_error( $r ) ? self::fail( $r ) : wp_send_json_success();
	}

	public static function ajax_test() {
		self::guard();
		$r = ACE_PE_Google::test();
		is_wp_error( $r ) ? self::fail( $r ) : wp_send_json_success( $r );
	}

	/* ---- page ---- */

	public static function page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		$s       = ACE_PE_Google::settings();
		$creds   = ACE_PE_Google::credentials();
		$pid     = absint( $_GET['product'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification
		$product = $pid ? wc_get_product( $pid ) : null;
		$msg     = sanitize_key( $_GET['msg'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification
		$notes   = array(
			'saved'   => array( 'success', __( 'Settings saved.', '2ace-photo-editor' ) ),
			'removed' => array( 'success', __( 'The Google connection was removed.', '2ace-photo-editor' ) ),
			'badjson' => array( 'error', __( 'That is not a valid service account JSON (it needs type, project_id, client_email and private_key). Nothing was changed.', '2ace-photo-editor' ) ),
		);
		$products = get_posts( array( 'post_type' => 'product', 'post_status' => array( 'publish', 'draft', 'private' ), 'posts_per_page' => 400, 'orderby' => 'title', 'order' => 'ASC', 'no_found_rows' => true ) );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'AI photo editor', '2ace-photo-editor' ); ?></h1>
			<?php if ( isset( $notes[ $msg ] ) ) : ?>
				<div class="notice notice-<?php echo esc_attr( $notes[ $msg ][0] ); ?> is-dismissible"><p><?php echo esc_html( $notes[ $msg ][1] ); ?></p></div>
			<?php endif; ?>
			<p><?php esc_html_e( 'Removes text from the product photos you choose. Nothing is overwritten: the edit is a new photo, you see before and after, and only the ones you approve go into the product. You can put the original back with one click.', '2ace-photo-editor' ); ?></p>

			<h2><?php esc_html_e( '1. Choose a product', '2ace-photo-editor' ); ?></h2>
			<form method="get">
				<input type="hidden" name="post_type" value="product"><input type="hidden" name="page" value="<?php echo esc_attr( self::PAGE ); ?>">
				<select name="product" onchange="this.form.submit()" style="min-width:420px;max-width:100%">
					<option value=""><?php esc_html_e( '— select a product —', '2ace-photo-editor' ); ?></option>
					<?php foreach ( $products as $p ) : ?>
						<option value="<?php echo (int) $p->ID; ?>" <?php selected( $pid, $p->ID ); ?>><?php echo esc_html( wp_html_excerpt( $p->post_title, 70, '…' ) ); ?></option>
					<?php endforeach; ?>
				</select>
				<noscript><button class="button"><?php esc_html_e( 'Open', '2ace-photo-editor' ); ?></button></noscript>
			</form>

			<?php if ( $product ) : ?>
				<?php
				$images = ACE_PE_Editor::product_images( $product );
				$swaps  = get_post_meta( $pid, ACE_PE_Editor::SWAPS, true );
				$swaps  = is_array( $swaps ) ? $swaps : array();
				?>
				<h2><?php esc_html_e( '2. Choose the photos to clean', '2ace-photo-editor' ); ?></h2>
				<?php if ( ! $creds ) : ?>
					<div class="notice notice-warning inline"><p><?php esc_html_e( 'Connect Google Cloud first (section 3 below).', '2ace-photo-editor' ); ?></p></div>
				<?php endif; ?>
				<div id="acepe-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:14px;max-width:960px">
					<?php foreach ( $images as $id => $kind ) : ?>
						<label style="border:1px solid #c3c4c7;border-radius:6px;padding:8px;background:#fff;display:block">
							<?php echo wp_get_attachment_image( $id, 'medium', false, array( 'style' => 'width:100%;height:auto;display:block' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							<span style="display:flex;gap:6px;align-items:center;margin-top:6px">
								<input type="checkbox" class="acepe-pick" value="<?php echo (int) $id; ?>">
								<span><?php echo 'main' === $kind ? esc_html__( 'Main photo', '2ace-photo-editor' ) : esc_html__( 'Gallery', '2ace-photo-editor' ); ?></span>
							</span>
							<?php if ( isset( $swaps[ $id ] ) ) : ?>
								<span style="display:block;margin-top:6px;font-size:12px">✔ <?php esc_html_e( 'AI-edited', '2ace-photo-editor' ); ?> · <a href="#" class="acepe-revert" data-new="<?php echo (int) $id; ?>"><?php esc_html_e( 'put the original back', '2ace-photo-editor' ); ?></a></span>
							<?php endif; ?>
						</label>
					<?php endforeach; ?>
				</div>
				<p style="margin-top:14px">
					<?php foreach ( ACE_PE_Editor::modes() as $key => $label ) : ?>
						<label style="display:block;margin:3px 0"><input type="radio" name="acepe-mode" value="<?php echo esc_attr( $key ); ?>" <?php checked( 'overlay', $key ); ?>> <?php echo esc_html( $label ); ?></label>
					<?php endforeach; ?>
				</p>
				<p>
					<button class="button button-primary" id="acepe-run" <?php disabled( ! $creds ); ?>><?php esc_html_e( 'Remove text from the selected photos', '2ace-photo-editor' ); ?></button>
					<span id="acepe-status" style="margin-left:10px"></span><br>
					<span class="description"><?php echo esc_html( sprintf( /* translators: %d: edits left today. */ __( 'Each photo is one paid request to Google. Left today: %d.', '2ace-photo-editor' ), ACE_PE_Editor::left_today() ) ); ?></span>
				</p>
				<div id="acepe-results" style="display:grid;gap:16px;max-width:960px"></div>
				<?php $saved = ACE_PE_Editor::saved_edits( $pid ); ?>
				<?php if ( $saved ) : ?>
					<h2><?php esc_html_e( 'Saved edits (not used in the product)', '2ace-photo-editor' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Nothing here is deleted automatically. Use one, or delete it when you are sure.', '2ace-photo-editor' ); ?></p>
					<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:14px;max-width:960px">
						<?php foreach ( $saved as $nid => $oid ) : ?>
							<div style="border:1px solid #c3c4c7;border-radius:6px;padding:8px;background:#fff">
								<?php echo wp_get_attachment_image( $nid, 'medium', false, array( 'style' => 'width:100%;height:auto;display:block' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
								<p style="margin:8px 0 0">
									<?php if ( ACE_PE_Editor::belongs( $product, $oid ) ) : ?>
										<button type="button" class="button button-primary acepe-saved-use" data-old="<?php echo (int) $oid; ?>" data-new="<?php echo (int) $nid; ?>"><?php esc_html_e( 'Use', '2ace-photo-editor' ); ?></button>
									<?php endif; ?>
									<button type="button" class="button acepe-saved-del" data-new="<?php echo (int) $nid; ?>"><?php esc_html_e( 'Delete permanently', '2ace-photo-editor' ); ?></button>
								</p>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			<?php endif; ?>

			<h2><?php echo $product ? '3' : '2'; ?>. <?php esc_html_e( 'Google Cloud connection', '2ace-photo-editor' ); ?></h2>
			<p>
				<?php if ( $creds ) : ?>
					<strong style="color:#00a32a">● <?php esc_html_e( 'Connected', '2ace-photo-editor' ); ?></strong> — <?php echo esc_html( $creds['client_email'] ); ?> (<?php echo esc_html( $creds['project_id'] ); ?>)
					<button class="button" id="acepe-test"><?php esc_html_e( 'Test connection', '2ace-photo-editor' ); ?></button> <span id="acepe-test-out"></span>
				<?php else : ?>
					<strong style="color:#b32d2e">● <?php esc_html_e( 'Not connected', '2ace-photo-editor' ); ?></strong>
				<?php endif; ?>
			</p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="max-width:760px">
				<?php wp_nonce_field( 'ace_pe_save' ); ?>
				<input type="hidden" name="action" value="ace_pe_save">
				<table class="form-table" role="presentation">
					<tr><th><label for="ace_pe_sa"><?php esc_html_e( 'Service account JSON', '2ace-photo-editor' ); ?></label></th>
						<td><textarea id="ace_pe_sa" name="ace_pe_sa" rows="6" class="large-text code" placeholder="<?php echo $creds ? esc_attr__( '(saved — paste a new one only to replace it)', '2ace-photo-editor' ) : '{ "type": "service_account", … }'; ?>" autocomplete="off" spellcheck="false"></textarea>
							<p class="description"><?php esc_html_e( 'Paste the whole contents of the key file. It is stored in the database, never shown again, and used only to sign in to Google. The account needs the "Vertex AI User" role.', '2ace-photo-editor' ); ?></p></td></tr>
					<tr><th><label for="ace_pe_model"><?php esc_html_e( 'Model', '2ace-photo-editor' ); ?></label></th><td><input id="ace_pe_model" name="ace_pe_model" type="text" class="regular-text" value="<?php echo esc_attr( $s['model'] ); ?>"></td></tr>
					<tr><th><label for="ace_pe_location"><?php esc_html_e( 'Location', '2ace-photo-editor' ); ?></label></th><td><input id="ace_pe_location" name="ace_pe_location" type="text" class="regular-text" value="<?php echo esc_attr( $s['location'] ); ?>"></td></tr>
					<tr><th><label for="ace_pe_limit"><?php esc_html_e( 'Edits per day (limit)', '2ace-photo-editor' ); ?></label></th><td><input id="ace_pe_limit" name="ace_pe_limit" type="number" min="1" max="500" value="<?php echo (int) $s['daily_limit']; ?>"><p class="description"><?php esc_html_e( 'Stops costs from running away.', '2ace-photo-editor' ); ?></p></td></tr>
				</table>
				<p>
					<?php submit_button( __( 'Save', '2ace-photo-editor' ), 'primary', 'submit', false ); ?>
					<?php if ( $creds ) : ?>
						<label style="margin-left:14px"><input type="checkbox" name="ace_pe_remove" value="1"> <?php esc_html_e( 'Remove the saved Google key', '2ace-photo-editor' ); ?></label>
					<?php endif; ?>
				</p>
			</form>
		</div>
		<?php self::script( $pid ); ?>
		<?php
	}

	private static function script( $pid ) {
		$t = array(
			'working' => __( 'Editing photo %1$d of %2$d … (about 20–40 seconds each)', '2ace-photo-editor' ),
			'done'    => __( 'Done. Check the results below.', '2ace-photo-editor' ),
			'pick'    => __( 'Select at least one photo.', '2ace-photo-editor' ),
			'before'  => __( 'Before', '2ace-photo-editor' ),
			'after'   => __( 'After', '2ace-photo-editor' ),
			'use'     => __( 'Use the edited photo', '2ace-photo-editor' ),
			'discard' => __( 'Not now (keep it)', '2ace-photo-editor' ),
			'delete'  => __( 'Delete permanently', '2ace-photo-editor' ),
			'sure'    => __( 'Delete this edited photo for good?', '2ace-photo-editor' ),
			'used'    => __( '✔ In use. Reload the page to see it in the list.', '2ace-photo-editor' ),
			'gone'    => __( 'Kept in the media library and in “Saved edits” below. Nothing was deleted.', '2ace-photo-editor' ),
			'revert'  => __( 'Original is back. Reloading…', '2ace-photo-editor' ),
		);
		?>
		<script>
		(function(){
			var T=<?php echo wp_json_encode( $t ); ?>, NONCE='<?php echo esc_js( wp_create_nonce( 'ace_pe' ) ); ?>', PID=<?php echo (int) $pid; ?>;
			function post(data){ var fd=new FormData(); fd.append('_wpnonce',NONCE); for(var k in data) fd.append(k,data[k]); return fetch(ajaxurl,{method:'POST',body:fd,credentials:'same-origin'}).then(function(r){return r.json();}); }
			function el(t,a,h){ var e=document.createElement(t); for(var k in a) e.setAttribute(k,a[k]); if(h) e.innerHTML=h; return e; }
			var tb=document.getElementById('acepe-test');
			if(tb) tb.addEventListener('click',function(e){ e.preventDefault(); var o=document.getElementById('acepe-test-out'); o.textContent=' …'; post({action:'ace_pe_test'}).then(function(r){ o.textContent=r.success?' ✔ OK ('+r.data.project+')':' ✖ '+r.data; }).catch(function(x){ o.textContent=' ✖ '+x; }); });
			document.querySelectorAll('.acepe-revert').forEach(function(a){ a.addEventListener('click',function(e){ e.preventDefault(); post({action:'ace_pe_revert',product:PID,'new':a.dataset.new}).then(function(r){ if(r.success){ a.parentNode.textContent=T.revert; location.reload(); } else alert(r.data); }); }); });
			document.querySelectorAll('.acepe-saved-use').forEach(function(b){ b.addEventListener('click',function(){ b.disabled=true; post({action:'ace_pe_apply',product:PID,old:b.dataset.old,'new':b.dataset.new}).then(function(r){ if(r.success) location.reload(); else { alert(r.data); b.disabled=false; } }); }); });
			document.querySelectorAll('.acepe-saved-del').forEach(function(b){ b.addEventListener('click',function(){ if(!confirm(T.sure)) return; post({action:'ace_pe_delete','new':b.dataset.new}).then(function(r){ if(r.success) b.closest('div').remove(); else alert(r.data); }); }); });
			var run=document.getElementById('acepe-run'); if(!run) return;
			run.addEventListener('click',async function(){
				var picks=[].slice.call(document.querySelectorAll('.acepe-pick:checked')).map(function(c){return c.value;});
				var status=document.getElementById('acepe-status'), box=document.getElementById('acepe-results');
				if(!picks.length){ status.textContent=T.pick; return; }
				var mode=(document.querySelector('input[name=acepe-mode]:checked')||{}).value||'overlay';
				run.disabled=true;
				for(var i=0;i<picks.length;i++){
					status.textContent=T.working.replace('%1$d',i+1).replace('%2$d',picks.length);
					var card=el('div',{style:'border:1px solid #c3c4c7;border-radius:6px;padding:12px;background:#fff'});
					try{
						var r=await post({action:'ace_pe_edit',product:PID,att:picks[i],mode:mode});
						if(!r.success){ card.innerHTML='<p style="color:#b32d2e">✖ '+r.data+'</p>'; box.appendChild(card); if(/limit/i.test(r.data)) break; continue; }
						var d=r.data;
						card.innerHTML='<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px"><div><strong>'+T.before+'</strong><br><img src="'+d.before+'" style="width:100%;height:auto"></div><div><strong>'+T.after+'</strong><br><img src="'+d.after+'" style="width:100%;height:auto"></div></div>';
						var bar=el('p'); var use=el('button',{class:'button button-primary',type:'button'},T.use), dis=el('button',{class:'button',type:'button',style:'margin-left:8px'},T.discard); var msg=el('span',{style:'margin-left:10px'});
						bar.appendChild(use); bar.appendChild(dis); bar.appendChild(msg); card.appendChild(bar); box.appendChild(card);
						(function(d,old,use,dis,msg,card){
							use.addEventListener('click',function(){ use.disabled=dis.disabled=true; post({action:'ace_pe_apply',product:PID,old:old,'new':d.new}).then(function(r){ msg.textContent=r.success?T.used:'✖ '+r.data; if(!r.success) use.disabled=dis.disabled=false; }); });
							dis.addEventListener('click',function(){ use.disabled=dis.disabled=true; post({action:'ace_pe_discard','new':d.new}).then(function(){ msg.textContent=T.gone; }); });
						})(d,picks[i],use,dis,msg,card);
					}catch(e){ card.innerHTML='<p style="color:#b32d2e">✖ '+e+'</p>'; box.appendChild(card); }
				}
				status.textContent=T.done; run.disabled=false;
			});
		})();
		</script>
		<?php
	}
}
