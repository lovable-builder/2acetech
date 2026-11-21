<?php
/**
 * Settings page (WooCommerce > Product Cleaner), the "clean now" button,
 * product-list row actions, and the automatic schedule.
 *
 * @package 2ace-product-cleaner
 */

defined( 'ABSPATH' ) || exit;

class ACE_PC_Settings {

	const OPTION = 'ace_pc_settings';
	const LOG    = 'ace_pc_log';
	const CRON   = 'ace_pc_cron';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
		add_action( 'wp_ajax_ace_pc_clean', array( __CLASS__, 'ajax_clean' ) );
		add_action( 'wp_ajax_ace_pc_test', array( __CLASS__, 'ajax_test' ) );
		add_action( 'wp_ajax_ace_pc_recat', array( __CLASS__, 'ajax_recat' ) );
		add_action( 'admin_post_ace_pc_undo', array( __CLASS__, 'handle_undo' ) );
		add_filter( 'post_row_actions', array( __CLASS__, 'row_actions' ), 10, 2 );
		add_filter( 'cron_schedules', array( __CLASS__, 'schedules' ) );
		add_action( self::CRON, array( __CLASS__, 'cron' ) );
		add_action( 'init', array( __CLASS__, 'ensure_schedule' ) );
	}

	/** Settings with defaults. */
	public static function get() {
		$o = wp_parse_args(
			get_option( self::OPTION, array() ),
			array(
				'provider'   => 'deepseek',
				'keys'       => array(),
				'model'      => '', // Empty = automatic.
				'auto'       => 1,
				'category'   => 'Oświetlenie',
				'sku_prefix' => '2AC-',
			)
		);
		$o['api_key'] = isset( $o['keys'][ $o['provider'] ] ) ? $o['keys'][ $o['provider'] ] : '';
		return $o;
	}

	public static function log( $line ) {
		$log = get_option( self::LOG, array() );
		array_unshift( $log, gmdate( 'Y-m-d H:i' ) . '  ' . $line );
		update_option( self::LOG, array_slice( $log, 0, 60 ), false );
	}

	/* ---- schedule ---- */

	public static function schedules( $s ) {
		$s['ace_pc_10min'] = array( 'interval' => 600, 'display' => __( 'Every 10 minutes', '2ace-product-cleaner' ) );
		return $s;
	}

	public static function ensure_schedule() {
		if ( ! wp_next_scheduled( self::CRON ) ) {
			wp_schedule_event( time() + 120, 'ace_pc_10min', self::CRON );
		}
	}

	/** Runs by itself: cleans up to 2 finished imports per run. */
	public static function cron() {
		// Photos first, and always: cap galleries at 5, even when cleaning is switched off or has no API key.
		ACE_PC_Photos::tidy_all();

		$o = self::get();
		if ( ! $o['auto'] || '' === $o['api_key'] || get_transient( 'ace_pc_lock' ) ) {
			return;
		}
		set_transient( 'ace_pc_lock', 1, 9 * MINUTE_IN_SECONDS );
		if ( function_exists( 'set_time_limit' ) ) {
			@set_time_limit( 280 ); // phpcs:ignore
		}
		// Only products untouched for 5 minutes, so a running import is not interrupted.
		foreach ( ACE_PC_Cleaner::waiting( 2, 300, true ) as $id ) {
			try {
				self::run_one( $id );
			} catch ( Exception $e ) {
				// Already logged and counted; carry on with the next product.
			}
		}
		delete_transient( 'ace_pc_lock' );
	}

	private static function run_one( $id ) {
		try {
			$r = ACE_PC_Cleaner::clean( $id );
			self::log( sprintf( '#%d cleaned: %s (%s)', $id, $r['title'], $r['sku'] ) );
			return $r;
		} catch ( Exception $e ) {
			$n = (int) get_post_meta( $id, ACE_PC_Cleaner::FAILS, true ) + 1;
			update_post_meta( $id, ACE_PC_Cleaner::FAILS, $n );
			update_post_meta( $id, ACE_PC_Cleaner::FAIL_AT, time() );
			update_post_meta( $id, ACE_PC_Cleaner::FAIL_MSG, mb_substr( $e->getMessage(), 0, 300 ) );
			self::log( sprintf( '#%d skipped (try %d): %s', $id, $n, $e->getMessage() ) );
			throw $e;
		}
	}

	/* ---- menu + settings ---- */

	public static function menu() {
		add_submenu_page( 'woocommerce', __( 'Product Cleaner', '2ace-product-cleaner' ), __( 'Product Cleaner', '2ace-product-cleaner' ), 'manage_woocommerce', 'ace-pc', array( __CLASS__, 'page' ) );
	}

	public static function register() {
		register_setting( 'ace_pc', self::OPTION, array( 'sanitize_callback' => array( __CLASS__, 'sanitize' ) ) );
	}

	public static function sanitize( $in ) {
		$old      = self::get();
		$provider = isset( $in['provider'] ) && 'anthropic' === $in['provider'] ? 'anthropic' : 'deepseek';
		$keys     = is_array( $old['keys'] ) ? $old['keys'] : array();
		$key      = isset( $in['api_key'] ) ? trim( sanitize_text_field( $in['api_key'] ) ) : '';
		if ( '' !== $key ) {
			$keys[ $provider ] = $key; // An empty box keeps the saved key.
		}
		delete_transient( 'ace_pc_model' );
		return array(
			'provider'   => $provider,
			'keys'       => $keys,
			'model'      => isset( $in['model'] ) ? sanitize_text_field( $in['model'] ) : '',
			'auto'       => empty( $in['auto'] ) ? 0 : 1,
			'category'   => isset( $in['category'] ) ? sanitize_text_field( $in['category'] ) : $old['category'],
			'sku_prefix' => isset( $in['sku_prefix'] ) ? sanitize_text_field( $in['sku_prefix'] ) : $old['sku_prefix'],
		);
	}

	public static function page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		$o       = self::get();
		$waiting = ACE_PC_Cleaner::waiting();
		$log     = get_option( self::LOG, array() );
		$nonce   = wp_create_nonce( 'ace_pc' );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Product Cleaner', '2ace-product-cleaner' ); ?></h1>
			<p><?php esc_html_e( 'Rewrites imported AliExpress products in Polish: title, bullets, description, tags, attribute names, SKU, category and .99 prices. Your own products are never touched.', '2ace-product-cleaner' ); ?></p>

			<?php if ( '' === $o['api_key'] ) : ?>
				<div class="notice notice-warning inline"><p><strong><?php esc_html_e( 'Step 1: paste your API key below and save.', '2ace-product-cleaner' ); ?></strong> <?php echo 'anthropic' === $o['provider'] ? esc_html__( 'Get one at console.anthropic.com.', '2ace-product-cleaner' ) : esc_html__( 'Get one at platform.deepseek.com (API keys). Cleaning a product costs a fraction of a cent to a few cents.', '2ace-product-cleaner' ); ?></p></div>
			<?php endif; ?>

			<h2><?php esc_html_e( 'Waiting for cleanup', '2ace-product-cleaner' ); ?> (<?php echo count( $waiting ); ?>)</h2>
			<?php if ( $waiting ) : ?>
				<ul style="list-style:disc;margin-left:20px">
					<?php foreach ( $waiting as $id ) : ?>
						<li id="acepc-<?php echo (int) $id; ?>"><?php echo esc_html( get_the_title( $id ) ); ?> <small>(#<?php echo (int) $id; ?>)</small> <span class="acepc-state"><?php
						$f = (int) get_post_meta( $id, ACE_PC_Cleaner::FAILS, true );
						if ( $f ) {
							/* translators: 1: number of tries, 2: error. */
							echo esc_html( sprintf( ' ⚠ failed %1$d× – %2$s', $f, get_post_meta( $id, ACE_PC_Cleaner::FAIL_MSG, true ) ) );
							if ( $f >= ACE_PC_Cleaner::MAX_AUTO_TRIES ) {
								echo esc_html( ' ' . __( '(automatic mode gave up; "Clean them now" tries again)', '2ace-product-cleaner' ) );
							}
						}
					?></span></li>
					<?php endforeach; ?>
				</ul>
				<p><button class="button button-primary" id="acepc-run" <?php disabled( '' === $o['api_key'] ); ?>><?php esc_html_e( 'Clean them now', '2ace-product-cleaner' ); ?></button>
				<span class="description"><?php esc_html_e( 'About 30 seconds per product. Keep this page open.', '2ace-product-cleaner' ); ?></span></p>
			<?php else : ?>
				<p><?php esc_html_e( 'Nothing waiting. New imports are picked up automatically.', '2ace-product-cleaner' ); ?></p>
			<?php endif; ?>

			<h2><?php esc_html_e( 'Categories', '2ace-product-cleaner' ); ?></h2>
			<p><?php esc_html_e( 'New imports get their category from the AI automatically: it uses an existing category or creates a new Polish one (at most 12 main categories, 2 levels). Use the button below to re-check the category of every product that is already in the shop (your own RMF-* and lig* products are skipped).', '2ace-product-cleaner' ); ?></p>
			<p><button class="button" id="acepc-recat" <?php disabled( '' === $o['api_key'] ); ?>><?php esc_html_e( 'Re-check categories of all products', '2ace-product-cleaner' ); ?></button> <span id="acepc-recat-out"></span></p>
			<ul id="acepc-recat-list" style="list-style:disc;margin-left:20px"></ul>

			<form method="post" action="options.php">
				<?php settings_fields( 'ace_pc' ); ?>
				<table class="form-table" role="presentation">
					<tr><th><label for="pv"><?php esc_html_e( 'AI provider', '2ace-product-cleaner' ); ?></label></th>
						<td><select id="pv" name="<?php echo esc_attr( self::OPTION ); ?>[provider]">
							<option value="deepseek" <?php selected( $o['provider'], 'deepseek' ); ?>>DeepSeek (cheap)</option>
							<option value="anthropic" <?php selected( $o['provider'], 'anthropic' ); ?>>Anthropic Claude</option>
						</select>
						<p class="description"><?php esc_html_e( 'Only product texts from the import are sent. No customer data.', '2ace-product-cleaner' ); ?></p></td></tr>
					<tr><th><label for="ak"><?php esc_html_e( 'API key', '2ace-product-cleaner' ); ?></label></th>
						<td><input type="password" id="ak" name="<?php echo esc_attr( self::OPTION ); ?>[api_key]" class="regular-text" autocomplete="off" placeholder="<?php echo $o['api_key'] ? esc_attr( '••••••••' . substr( $o['api_key'], -4 ) ) : 'sk-…'; ?>">
						<p class="description"><?php esc_html_e( 'Stored in your database. Leave empty to keep the saved key.', '2ace-product-cleaner' ); ?></p></td></tr>
					<tr><th><?php esc_html_e( 'Clean new imports automatically', '2ace-product-cleaner' ); ?></th>
						<td><label><input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[auto]" value="1" <?php checked( $o['auto'] ); ?>> <?php esc_html_e( 'Every 10 minutes, products that have not changed for 5 minutes are cleaned. Status (draft or published) is never changed.', '2ace-product-cleaner' ); ?></label></td></tr>
					<tr><th><label for="md"><?php esc_html_e( 'Model', '2ace-product-cleaner' ); ?></label></th>
						<td><input type="text" id="md" name="<?php echo esc_attr( self::OPTION ); ?>[model]" value="<?php echo esc_attr( $o['model'] ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'automatic', '2ace-product-cleaner' ); ?>">
						<p class="description"><?php esc_html_e( 'Leave empty: the plugin picks a suitable model by itself.', '2ace-product-cleaner' ); ?></p></td></tr>
					<tr><th><label for="ct"><?php esc_html_e( 'Fallback category', '2ace-product-cleaner' ); ?></label></th>
						<td><input type="text" id="ct" name="<?php echo esc_attr( self::OPTION ); ?>[category]" value="<?php echo esc_attr( $o['category'] ); ?>" class="regular-text">
						<p class="description"><?php esc_html_e( 'Only a fallback; the AI normally chooses the category.', '2ace-product-cleaner' ); ?></p></td></tr>
					<tr><th><label for="sp"><?php esc_html_e( 'SKU prefix', '2ace-product-cleaner' ); ?></label></th>
						<td><input type="text" id="sp" name="<?php echo esc_attr( self::OPTION ); ?>[sku_prefix]" value="<?php echo esc_attr( $o['sku_prefix'] ); ?>" class="small-text"></td></tr>
				</table>
				<?php submit_button(); ?>
			</form>
			<p><button class="button" id="acepc-test" <?php disabled( '' === $o['api_key'] ); ?>><?php esc_html_e( 'Test connection', '2ace-product-cleaner' ); ?></button> <span id="acepc-test-out"></span>
			<span class="description"><?php esc_html_e( 'Uses the saved settings.', '2ace-product-cleaner' ); ?></span></p>

			<h2><?php esc_html_e( 'Recent activity', '2ace-product-cleaner' ); ?></h2>
			<pre style="background:#fff;border:1px solid #ccd0d4;padding:12px;max-height:260px;overflow:auto;white-space:pre-wrap"><?php echo $log ? esc_html( implode( "\n", $log ) ) : esc_html__( 'Nothing yet.', '2ace-product-cleaner' ); ?></pre>
			<p class="description"><?php esc_html_e( 'To undo a product: Products list > hover the product > "Undo Polish cleanup".', '2ace-product-cleaner' ); ?></p>
		</div>
		<script>
		(function(){
			var tb=document.getElementById('acepc-test');
			if(tb) tb.addEventListener('click',async function(){
				var out=document.getElementById('acepc-test-out'); out.textContent=' testing…';
				var fd=new FormData(); fd.append('action','ace_pc_test'); fd.append('_wpnonce','<?php echo esc_js( $nonce ); ?>');
				try{ var r=await (await fetch(ajaxurl,{method:'POST',body:fd,credentials:'same-origin'})).json();
					out.textContent = r.success ? ' ✔ '+r.data : ' ✖ '+r.data; }catch(e){ out.textContent=' ✖ '+e; }
			});
			var rb=document.getElementById('acepc-recat');
			if(rb) rb.addEventListener('click',async function(){
				rb.disabled=true; var out=document.getElementById('acepc-recat-out'), list=document.getElementById('acepc-recat-list'); var offset=0, n=0;
				try{
					while(true){
						out.textContent=' working… '+offset;
						var fd=new FormData(); fd.append('action','ace_pc_recat'); fd.append('offset',offset); fd.append('_wpnonce','<?php echo esc_js( $nonce ); ?>');
						var r=await (await fetch(ajaxurl,{method:'POST',body:fd,credentials:'same-origin'})).json();
						if(!r.success){ out.textContent=' ✖ '+r.data; break; }
						Object.keys(r.data.changed).forEach(function(id){ n++; var li=document.createElement('li'); li.textContent='#'+id+': '+r.data.changed[id]; list.appendChild(li); });
						offset=r.data.next;
						if(r.data.done){ out.textContent=' ✔ done, '+n+' product(s) moved'; break; }
					}
				}catch(e){ out.textContent=' ✖ '+e; }
				rb.disabled=false;
			});
			var btn=document.getElementById('acepc-run'); if(!btn) return;
			var ids=<?php echo wp_json_encode( array_map( 'intval', $waiting ) ); ?>;
			btn.addEventListener('click',async function(){
				btn.disabled=true;
				for(var i=0;i<ids.length;i++){
					var li=document.getElementById('acepc-'+ids[i]), st=li.querySelector('.acepc-state');
					st.textContent=' … working';
					try{
						var fd=new FormData(); fd.append('action','ace_pc_clean'); fd.append('id',ids[i]); fd.append('_wpnonce','<?php echo esc_js( $nonce ); ?>');
						var r=await (await fetch(ajaxurl,{method:'POST',body:fd,credentials:'same-origin'})).json();
						st.textContent = r.success ? ' ✔ '+r.data.title : ' ✖ '+(r.data||'error');
					}catch(e){ st.textContent=' ✖ '+e; }
				}
				btn.textContent='Done. Reload the page.';
			});
		})();
		</script>
		<?php
	}

	public static function ajax_clean() {
		check_ajax_referer( 'ace_pc' );
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( 'Not allowed' );
		}
		if ( function_exists( 'set_time_limit' ) ) {
			@set_time_limit( 280 ); // phpcs:ignore
		}
		try {
			wp_send_json_success( self::run_one( absint( $_POST['id'] ?? 0 ) ) );
		} catch ( Exception $e ) {
			wp_send_json_error( $e->getMessage() );
		}
	}

	public static function ajax_test() {
		check_ajax_referer( 'ace_pc' );
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( 'Not allowed' );
		}
		$o = self::get();
		try {
			$ids = ACE_PC_Cleaner::list_models( $o );
			wp_send_json_success( sprintf( 'Connected. Model used: %s (%d available)', ACE_PC_Cleaner::model( $o ), count( $ids ) ) );
		} catch ( Exception $e ) {
			wp_send_json_error( $e->getMessage() );
		}
	}

	public static function ajax_recat() {
		check_ajax_referer( 'ace_pc' );
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( 'Not allowed' );
		}
		if ( function_exists( 'set_time_limit' ) ) {
			@set_time_limit( 280 ); // phpcs:ignore
		}
		$all = get_posts( array( 'post_type' => 'product', 'post_status' => array( 'publish', 'draft', 'pending', 'private' ), 'posts_per_page' => -1, 'orderby' => 'ID', 'order' => 'ASC', 'fields' => 'ids', 'no_found_rows' => true ) );
		$off = absint( $_POST['offset'] ?? 0 );
		$batch = array_slice( $all, $off, 15 );
		try {
			$changed = ACE_PC_Cleaner::recategorize( $batch );
		} catch ( Exception $e ) {
			self::log( 'Category re-check failed: ' . $e->getMessage() );
			wp_send_json_error( $e->getMessage() );
		}
		foreach ( $changed as $id => $txt ) {
			self::log( sprintf( '#%d category: %s', $id, $txt ) );
		}
		wp_send_json_success( array( 'changed' => (object) $changed, 'next' => $off + 15, 'done' => $off + 15 >= count( $all ) ) );
	}

	/* ---- product list: Undo ---- */

	public static function row_actions( $actions, $post ) {
		if ( 'product' === $post->post_type && current_user_can( 'manage_woocommerce' ) && ACE_PC_Cleaner::is_cleaned( $post->ID ) && is_array( get_post_meta( $post->ID, ACE_PC_Cleaner::BACKUP, true ) ) ) {
			$url = wp_nonce_url( admin_url( 'admin-post.php?action=ace_pc_undo&id=' . $post->ID ), 'ace_pc_undo_' . $post->ID );
			$actions['ace_pc_undo'] = '<a href="' . esc_url( $url ) . '" onclick="return confirm(\'' . esc_js( __( 'Put this product back as it was before the cleanup?', '2ace-product-cleaner' ) ) . '\')">' . esc_html__( 'Undo Polish cleanup', '2ace-product-cleaner' ) . '</a>';
		}
		return $actions;
	}

	public static function handle_undo() {
		$id = absint( $_GET['id'] ?? 0 );
		check_admin_referer( 'ace_pc_undo_' . $id );
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( 'Not allowed' );
		}
		try {
			ACE_PC_Cleaner::undo( $id );
			self::log( "#$id restored to the original" );
		} catch ( Exception $e ) {
			wp_die( esc_html( $e->getMessage() ) );
		}
		wp_safe_redirect( admin_url( 'edit.php?post_type=product' ) );
		exit;
	}
}
