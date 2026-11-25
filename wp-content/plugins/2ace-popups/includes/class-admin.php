<?php
/**
 * WordPress admin: the designer, the template gallery, statistics, leads and settings.
 *
 * @package 2ace-popups
 */

defined( 'ABSPATH' ) || exit;

class ACEPU_Admin {

	const CAP = 'manage_options';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'load-post-new.php', array( __CLASS__, 'new_goes_to_templates' ) );
		add_action( 'add_meta_boxes_' . ACEPU_Store::CPT, array( __CLASS__, 'boxes' ) );
		add_action( 'save_post_' . ACEPU_Store::CPT, array( __CLASS__, 'save' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );

		add_filter( 'manage_' . ACEPU_Store::CPT . '_posts_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_' . ACEPU_Store::CPT . '_posts_custom_column', array( __CLASS__, 'column' ), 10, 2 );
		add_filter( 'post_row_actions', array( __CLASS__, 'row_actions' ), 10, 2 );

		add_action( 'admin_post_acepu_new', array( __CLASS__, 'create_from_preset' ) );
		add_action( 'admin_post_acepu_duplicate', array( __CLASS__, 'duplicate' ) );
		add_action( 'admin_post_acepu_reset', array( __CLASS__, 'reset_stats' ) );
		add_action( 'admin_post_acepu_settings', array( __CLASS__, 'save_settings' ) );
		add_action( 'admin_post_acepu_csv', array( __CLASS__, 'csv' ) );
		add_action( 'admin_post_acepu_lead_delete', array( __CLASS__, 'lead_delete' ) );

		add_action( 'wp_ajax_acepu_preview', array( __CLASS__, 'ajax_preview' ) );
		add_action( 'wp_ajax_acepu_ai_text', array( __CLASS__, 'ajax_ai_text' ) );
		add_action( 'wp_ajax_acepu_ai_image', array( __CLASS__, 'ajax_ai_image' ) );

		add_filter( 'wp_privacy_personal_data_exporters', array( __CLASS__, 'register_exporter' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( __CLASS__, 'register_eraser' ) );
	}

	/* ---------------------------------------------------------------------
	 * Menu and pages
	 * ------------------------------------------------------------------ */

	public static function menu() {
		$parent = 'edit.php?post_type=' . ACEPU_Store::CPT;
		add_submenu_page( $parent, __( 'Templates', '2ace-popups' ), __( 'Templates', '2ace-popups' ), self::CAP, 'acepu-templates', array( __CLASS__, 'page_templates' ) );
		add_submenu_page( $parent, __( 'Leads', '2ace-popups' ), __( 'Leads', '2ace-popups' ), self::CAP, 'acepu-leads', array( __CLASS__, 'page_leads' ) );
		add_submenu_page( $parent, __( 'Settings', '2ace-popups' ), __( 'Settings', '2ace-popups' ), self::CAP, 'acepu-settings', array( __CLASS__, 'page_settings' ) );
	}

	/** "Add pop-up" opens the template gallery (it has a blank option). */
	public static function new_goes_to_templates() {
		// phpcs:ignore WordPress.Security.NonceVerification
		if ( isset( $_GET['post_type'] ) && ACEPU_Store::CPT === $_GET['post_type'] ) {
			wp_safe_redirect( admin_url( 'edit.php?post_type=' . ACEPU_Store::CPT . '&page=acepu-templates' ) );
			exit;
		}
	}

	private static function is_our_screen() {
		$s = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		return $s && ACEPU_Store::CPT === $s->post_type;
	}

	public static function assets( $hook ) {
		if ( ! self::is_our_screen() ) {
			return;
		}
		wp_enqueue_style( 'acepu-admin', ACEPU_URL . 'assets/admin.css', array(), ACEPU_VERSION );
		if ( in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			wp_enqueue_style( 'acepu-front', ACEPU_URL . 'assets/front.css', array(), ACEPU_VERSION );
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_media();
			wp_enqueue_script( 'acepu-admin', ACEPU_URL . 'assets/admin.js', array( 'jquery', 'wp-color-picker' ), ACEPU_VERSION, true );
			wp_localize_script(
				'acepu-admin',
				'aceAdmin',
				array(
					'ajax'  => admin_url( 'admin-ajax.php' ),
					'nonce' => wp_create_nonce( 'acepu_admin' ),
					'ai'    => ACEPU_AI::available(),
					'i18n'  => array(
						'pick'    => __( 'Choose picture', '2ace-popups' ),
						'use'     => __( 'Use this picture', '2ace-popups' ),
						'working' => __( 'Working ...', '2ace-popups' ),
						'filled'  => __( 'Texts and colours filled in. Check them, then save.', '2ace-popups' ),
						'made'    => __( 'Artwork created. Click it to use it as the picture.', '2ace-popups' ),
						'copied'  => __( 'Copied', '2ace-popups' ),
					),
				)
			);
		}
	}

	/* ---------------------------------------------------------------------
	 * Template gallery
	 * ------------------------------------------------------------------ */

	public static function page_templates() {
		if ( ! current_user_can( self::CAP ) ) {
			return;
		}
		wp_enqueue_style( 'acepu-front', ACEPU_URL . 'assets/front.css', array(), ACEPU_VERSION );
		echo '<div class="wrap acepu-wrap"><h1>' . esc_html__( 'Start a pop-up from a template', '2ace-popups' ) . '</h1>';
		echo '<p class="description">' . esc_html__( 'Pick the closest one. Everything can be changed afterwards: texts, colours, picture, triggers and who sees it. A new pop-up starts switched off (draft) until you publish it.', '2ace-popups' ) . '</p><div class="acepu-cards">';

		$cards = array_merge( array( '' => array( 'name' => __( 'Blank', '2ace-popups' ), 'desc' => __( 'Start with an empty design.', '2ace-popups' ), 'title' => __( 'New pop-up', '2ace-popups' ), 'set' => array( 'headline' => __( 'Your headline', '2ace-popups' ), 'text' => __( 'Your text goes here.', '2ace-popups' ), 'cta_label' => __( 'Button', '2ace-popups' ) ) ) ), acepu_presets() );
		foreach ( $cards as $key => $p ) {
			$s    = wp_parse_args( $p['set'], ACEPU_Store::defaults() );
			$url  = wp_nonce_url( admin_url( 'admin-post.php?action=acepu_new&preset=' . rawurlencode( (string) $key ) ), 'acepu_new' );
			$html = ACEPU_Render::html( 0, $s, array( 'static' => true ) );
			echo '<div class="acepu-card"><div class="acepu-card__stage"><div class="acepu-card__inner">' . $html . '</div></div>'; // phpcs:ignore WordPress.Security.EscapeOutput
			echo '<div class="acepu-card__meta"><h2>' . esc_html( $p['name'] ) . '</h2><p>' . esc_html( $p['desc'] ) . '</p>';
			echo '<a class="button button-primary" href="' . esc_url( $url ) . '">' . esc_html__( 'Use this template', '2ace-popups' ) . '</a></div></div>';
		}
		echo '</div></div>';
		// Scale the cards to fit.
		echo '<script>document.querySelectorAll(".acepu-card__stage").forEach(function(s){var i=s.firstElementChild;var f=function(){i.style.setProperty("--s",(s.clientWidth/1000).toFixed(4));s.style.height=Math.round(i.offsetHeight*s.clientWidth/1000)+"px"};f();window.addEventListener("resize",f)});</script>';
	}

	public static function create_from_preset() {
		if ( ! current_user_can( self::CAP ) || ! check_admin_referer( 'acepu_new' ) ) {
			wp_die( esc_html__( 'Not allowed.', '2ace-popups' ) );
		}
		$key     = isset( $_GET['preset'] ) ? sanitize_key( wp_unslash( $_GET['preset'] ) ) : '';
		$presets = acepu_presets();
		$set     = isset( $presets[ $key ] ) ? $presets[ $key ]['set'] : array( 'headline' => __( 'Your headline', '2ace-popups' ), 'text' => __( 'Your text goes here.', '2ace-popups' ), 'cta_label' => __( 'Button', '2ace-popups' ) );
		$title   = isset( $presets[ $key ] ) ? $presets[ $key ]['title'] : __( 'New pop-up', '2ace-popups' );
		$id      = wp_insert_post( array( 'post_type' => ACEPU_Store::CPT, 'post_status' => 'draft', 'post_title' => $title ) );
		if ( ! $id || is_wp_error( $id ) ) {
			wp_die( esc_html__( 'Could not create the pop-up.', '2ace-popups' ) );
		}
		ACEPU_Store::save( $id, ACEPU_Store::sanitize( wp_parse_args( $set, ACEPU_Store::defaults() ) ) );
		wp_safe_redirect( admin_url( 'post.php?post=' . $id . '&action=edit' ) );
		exit;
	}

	/* ---------------------------------------------------------------------
	 * The designer
	 * ------------------------------------------------------------------ */

	public static function boxes() {
		add_meta_box( 'acepu_designer', __( 'Pop-up designer', '2ace-popups' ), array( __CLASS__, 'box_designer' ), ACEPU_Store::CPT, 'normal', 'high' );
		add_meta_box( 'acepu_side', __( 'Results and how to open', '2ace-popups' ), array( __CLASS__, 'box_side' ), ACEPU_Store::CPT, 'side', 'default' );
	}

	/** One field of the form. */
	private static function field( $key, $f, $val ) {
		$name = 'acepu[' . $key . ']';
		$id   = 'acepu_' . $key;
		$show = isset( $f['show'] ) ? ' data-show="' . esc_attr( $f['show'] ) . '"' : '';
		echo '<div class="acepu-field acepu-field--' . esc_attr( $f['type'] ) . '"' . $show . '>'; // phpcs:ignore WordPress.Security.EscapeOutput
		$label = '<label class="acepu-label" for="' . esc_attr( $id ) . '">' . esc_html( $f['label'] ) . '</label>';
		switch ( $f['type'] ) {
			case 'bool':
				echo '<label class="acepu-check"><input type="checkbox" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="1"' . checked( ! empty( $val ), true, false ) . '> ' . esc_html( $f['label'] ) . '</label>'; // phpcs:ignore WordPress.Security.EscapeOutput
				break;
			case 'text':
			case 'url':
				echo $label . '<input type="' . ( 'url' === $f['type'] ? 'url' : 'text' ) . '" class="widefat" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $val ) . '">'; // phpcs:ignore WordPress.Security.EscapeOutput
				break;
			case 'textarea':
			case 'lines':
				echo $label . '<textarea class="widefat" rows="3" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '">' . esc_textarea( $val ) . '</textarea>'; // phpcs:ignore WordPress.Security.EscapeOutput
				break;
			case 'int':
				echo $label . '<input type="number" class="small-text" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $val ) . '" min="' . esc_attr( isset( $f['min'] ) ? $f['min'] : '' ) . '" max="' . esc_attr( isset( $f['max'] ) ? $f['max'] : '' ) . '">'; // phpcs:ignore WordPress.Security.EscapeOutput
				break;
			case 'datetime':
				echo $label . '<input type="datetime-local" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $val ) . '">'; // phpcs:ignore WordPress.Security.EscapeOutput
				break;
			case 'color':
				echo $label . '<input type="text" class="acepu-color" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $val ) . '" data-default-color="' . esc_attr( $f['def'] ) . '">'; // phpcs:ignore WordPress.Security.EscapeOutput
				break;
			case 'select':
				echo $label . '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '">'; // phpcs:ignore WordPress.Security.EscapeOutput
				foreach ( $f['opts'] as $v => $l ) {
					echo '<option value="' . esc_attr( $v ) . '"' . selected( (string) $val, (string) $v, false ) . '>' . esc_html( $l ) . '</option>'; // phpcs:ignore WordPress.Security.EscapeOutput
				}
				echo '</select>';
				break;
			case 'multi':
				echo '<span class="acepu-label">' . esc_html( $f['label'] ) . '</span><div class="acepu-checks">';
				foreach ( $f['opts'] as $v => $l ) {
					echo '<label><input type="checkbox" name="' . esc_attr( $name ) . '[]" value="' . esc_attr( $v ) . '"' . checked( in_array( (string) $v, array_map( 'strval', (array) $val ), true ), true, false ) . '> ' . esc_html( $l ) . '</label>'; // phpcs:ignore WordPress.Security.EscapeOutput
				}
				echo '</div>';
				break;
			case 'ids':
				echo '<span class="acepu-label">' . esc_html( $f['label'] ) . '</span><div class="acepu-checks acepu-checks--scroll">';
				$terms = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) );
				if ( ! is_wp_error( $terms ) ) {
					foreach ( $terms as $t ) {
						echo '<label><input type="checkbox" name="' . esc_attr( $name ) . '[]" value="' . (int) $t->term_id . '"' . checked( in_array( (int) $t->term_id, array_map( 'intval', (array) $val ), true ), true, false ) . '> ' . esc_html( ( $t->parent ? '— ' : '' ) . $t->name ) . '</label>'; // phpcs:ignore WordPress.Security.EscapeOutput
					}
				}
				echo '</div>';
				break;
			case 'media':
				$thumb = $val ? wp_get_attachment_image( (int) $val, 'thumbnail' ) : '';
				echo '<span class="acepu-label">' . esc_html( $f['label'] ) . '</span><div class="acepu-media" data-media><div class="acepu-media__thumb">' . $thumb . '</div><input type="hidden" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . (int) $val . '"><button type="button" class="button" data-media-pick>' . esc_html__( 'Choose', '2ace-popups' ) . '</button> <button type="button" class="button-link" data-media-clear>' . esc_html__( 'Remove', '2ace-popups' ) . '</button></div>'; // phpcs:ignore WordPress.Security.EscapeOutput
				break;
		}
		if ( ! empty( $f['help'] ) ) {
			echo '<p class="description">' . esc_html( $f['help'] ) . '</p>';
		}
		echo '</div>';
	}

	public static function box_designer( $post ) {
		wp_nonce_field( 'acepu_save', 'acepu_nonce' );
		$s      = ACEPU_Store::get( $post->ID );
		$schema = acepu_schema();
		echo '<div id="acepu-designer" class="acepu-admin" data-post="' . (int) $post->ID . '"><div class="acepu-admin__main"><nav class="acepu-tabs" role="tablist">';
		foreach ( acepu_tabs() as $key => $label ) {
			echo '<button type="button" class="acepu-tab" role="tab" data-tab="' . esc_attr( $key ) . '">' . esc_html( $label ) . '</button>';
		}
		echo '</nav>';
		foreach ( acepu_tabs() as $tab => $label ) {
			echo '<div class="acepu-panel" role="tabpanel" data-panel="' . esc_attr( $tab ) . '">';
			if ( 'ai' === $tab ) {
				self::ai_panel();
			}
			foreach ( $schema as $key => $f ) {
				if ( $f['tab'] === $tab ) {
					self::field( $key, $f, $s[ $key ] );
				}
			}
			echo '</div>';
		}
		echo '</div><div class="acepu-admin__side"><div class="acepu-devices"><button type="button" class="button is-on" data-dev="1000">' . esc_html__( 'Computer', '2ace-popups' ) . '</button> <button type="button" class="button" data-dev="420">' . esc_html__( 'Phone', '2ace-popups' ) . '</button></div>';
		echo '<div class="acepu-stage" data-stage><div class="acepu-stage__inner" data-stage-inner></div></div><p class="description">' . esc_html__( 'Live preview. The real pop-up appears at the moment you chose under "When to show".', '2ace-popups' ) . '</p></div></div>';
	}

	private static function ai_panel() {
		$on = ACEPU_AI::available();
		echo '<div class="acepu-ai">';
		if ( ! $on ) {
			echo '<div class="notice notice-warning inline"><p>' . wp_kses_post( sprintf( /* translators: %s: link. */ __( 'Google AI is not connected yet. Connect it once in %s (paste the service account), then come back.', '2ace-popups' ), '<a href="' . esc_url( ACEPU_AI::settings_url() ) . '">' . esc_html__( 'AI photo editor settings', '2ace-popups' ) . '</a>' ) ) . '</p></div>';
		} else {
			/* translators: %d: number of requests. */
			echo '<p class="description">' . esc_html( sprintf( __( 'AI requests left today: %d', '2ace-popups' ), ACEPU_AI::left() ) ) . '</p>';
		}
		?>
		<div class="acepu-ai__box">
			<h3><?php esc_html_e( '1. Describe the idea, get texts and colours', '2ace-popups' ); ?></h3>
			<p><label><?php esc_html_e( 'Occasion', '2ace-popups' ); ?><br>
				<select data-ai="occasion">
					<?php foreach ( array( 'Black Friday', 'Cyber Monday', 'Christmas gifts', 'Summer holidays', 'Camping season start', 'Back to school', 'Welcome for new customers', 'Cart reminder', 'Shop anniversary', 'Other (describe below)' ) as $o ) : ?>
						<option><?php echo esc_html( $o ); ?></option>
					<?php endforeach; ?>
				</select></label></p>
			<p><label><?php esc_html_e( 'What should the pop-up say or do? (any language)', '2ace-popups' ); ?><br>
				<textarea rows="3" class="widefat" data-ai="brief" placeholder="<?php esc_attr_e( 'e.g. 15% off camping gear for the weekend, friendly and adventurous', '2ace-popups' ); ?>"></textarea></label></p>
			<p><label><?php esc_html_e( 'Tone', '2ace-popups' ); ?>
				<select data-ai="tone"><option>friendly</option><option>exciting</option><option>elegant and calm</option><option>playful</option><option>urgent but honest</option></select></label>
			<button type="button" class="button button-primary" data-ai-run="text"<?php disabled( ! $on ); ?>><?php esc_html_e( 'Suggest texts and colours', '2ace-popups' ); ?></button></p>
			<p class="description"><?php esc_html_e( 'It fills the fields in the other tabs (the discount is taken from the Coupon & form tab). Read what it wrote: you are responsible for every claim.', '2ace-popups' ); ?></p>
		</div>
		<div class="acepu-ai__box">
			<h3><?php esc_html_e( '2. Paint artwork', '2ace-popups' ); ?></h3>
			<p><label><?php esc_html_e( 'Describe the picture (English works best)', '2ace-popups' ); ?><br>
				<textarea rows="3" class="widefat" data-ai="prompt" placeholder="<?php esc_attr_e( 'e.g. a tent glowing under a starry sky, warm lantern light, red and black tones', '2ace-popups' ); ?>"></textarea></label></p>
			<p><label><?php esc_html_e( 'Shape', '2ace-popups' ); ?>
				<select data-ai="aspect"><option value="portrait"><?php esc_html_e( 'Portrait (picture on the side)', '2ace-popups' ); ?></option><option value="landscape"><?php esc_html_e( 'Wide (top / behind text / bar)', '2ace-popups' ); ?></option><option value="square"><?php esc_html_e( 'Square', '2ace-popups' ); ?></option></select></label>
			<button type="button" class="button button-primary" data-ai-run="image"<?php disabled( ! $on ); ?>><?php esc_html_e( 'Create artwork', '2ace-popups' ); ?></button></p>
			<div class="acepu-ai__results" data-ai-results></div>
			<p class="description"><?php esc_html_e( 'Artwork is decoration only. It never contains text or logos, and it is not a photo of your products: keep real product photos for products. Generated pictures are marked as AI-generated in the Media Library.', '2ace-popups' ); ?></p>
		</div>
		<p class="acepu-ai__status" data-ai-status role="status"></p>
		</div>
		<?php
	}

	public static function box_side( $post ) {
		$t = ACEPU_Store::totals( $post->ID );
		$s = ACEPU_Store::get( $post->ID );
		$conv = 'email' === $s['mode'] ? $t['signup'] : ( $t['cta'] + $t['copy'] );
		$rate = $t['view'] ? round( $conv / $t['view'] * 100, 1 ) : 0;
		echo '<div class="acepu-stats"><div><b>' . (int) $t['view'] . '</b><span>' . esc_html__( 'Shown', '2ace-popups' ) . '</span></div><div><b>' . (int) $t['close'] . '</b><span>' . esc_html__( 'Closed', '2ace-popups' ) . '</span></div><div><b>' . (int) $conv . '</b><span>' . esc_html( 'email' === $s['mode'] ? __( 'Sign-ups', '2ace-popups' ) : __( 'Took the offer', '2ace-popups' ) ) . '</span></div><div><b>' . esc_html( $rate ) . '%</b><span>' . esc_html__( 'Rate', '2ace-popups' ) . '</span></div></div>';
		$days = ACEPU_Store::daily( $post->ID, 14 );
		$max  = max( 1, max( $days ) );
		echo '<div class="acepu-spark" title="' . esc_attr__( 'Shown per day, last 14 days', '2ace-popups' ) . '">';
		foreach ( $days as $d => $n ) {
			echo '<i style="height:' . (int) round( $n / $max * 100 ) . '%" title="' . esc_attr( $d . ': ' . $n ) . '"></i>';
		}
		echo '</div>';
		if ( 'auto-draft' !== $post->post_status ) {
			$prev = add_query_arg( array( 'acepu_preview' => $post->ID, '_wpnonce' => wp_create_nonce( 'acepu_preview_' . $post->ID ) ), home_url( '/' ) );
			echo '<p><a class="button" target="_blank" rel="noopener" href="' . esc_url( $prev ) . '">' . esc_html__( 'Preview on the shop', '2ace-popups' ) . '</a></p>';
			echo '<p class="description">' . esc_html__( 'Open it from any button or link:', '2ace-popups' ) . '<br><code>#acepu-' . (int) $post->ID . '</code></p>';
			$reset = wp_nonce_url( admin_url( 'admin-post.php?action=acepu_reset&id=' . $post->ID ), 'acepu_reset_' . $post->ID );
			echo '<p><a class="button-link button-link-delete" href="' . esc_url( $reset ) . '" onclick="return confirm(\'' . esc_js( __( 'Reset the numbers of this pop-up?', '2ace-popups' ) ) . '\')">' . esc_html__( 'Reset statistics', '2ace-popups' ) . '</a></p>';
		}
		echo '<p class="description">' . esc_html__( 'A pop-up is live when its status is Published. Use Draft to switch it off.', '2ace-popups' ) . '</p>';
	}

	public static function save( $post_id, $post ) {
		if ( ! isset( $_POST['acepu_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['acepu_nonce'] ) ), 'acepu_save' ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( self::CAP ) ) {
			return;
		}
		$raw = isset( $_POST['acepu'] ) ? wp_unslash( $_POST['acepu'] ) : array(); // phpcs:ignore WordPress.Security
		ACEPU_Store::save( $post_id, ACEPU_Store::sanitize( $raw ) );
	}

	/* ---------------------------------------------------------------------
	 * Admin AJAX
	 * ------------------------------------------------------------------ */

	private static function ajax_guard() {
		check_ajax_referer( 'acepu_admin', 'nonce' );
		if ( ! current_user_can( self::CAP ) ) {
			wp_send_json_error( array( 'msg' => __( 'Not allowed.', '2ace-popups' ) ), 403 );
		}
	}

	public static function ajax_preview() {
		self::ajax_guard();
		$raw = isset( $_POST['acepu'] ) ? wp_unslash( $_POST['acepu'] ) : array(); // phpcs:ignore WordPress.Security
		$s   = ACEPU_Store::sanitize( $raw );
		wp_send_json_success( array( 'html' => ACEPU_Render::html( 0, $s, array( 'static' => true ) ) ) );
	}

	public static function ajax_ai_text() {
		self::ajax_guard();
		$raw  = isset( $_POST['acepu'] ) ? wp_unslash( $_POST['acepu'] ) : array(); // phpcs:ignore WordPress.Security
		$s    = ACEPU_Store::sanitize( $raw );
		$disc = ( 'email' === $s['mode'] ) ? ACEPU_Render::discount( $s ) : ( 'code' === $s['mode'] && $s['code'] ? 'the existing coupon code ' . $s['code'] . ' (do not state a percentage unless the brief gives one)' : '' );
		$in   = array(
			'occasion' => isset( $_POST['occasion'] ) ? sanitize_text_field( wp_unslash( $_POST['occasion'] ) ) : '',
			'brief'    => isset( $_POST['brief'] ) ? sanitize_textarea_field( wp_unslash( $_POST['brief'] ) ) : '',
			'tone'     => isset( $_POST['tone'] ) ? sanitize_text_field( wp_unslash( $_POST['tone'] ) ) : 'friendly',
			'discount' => $disc,
			'mode'     => $s['mode'],
		);
		$r = ACEPU_AI::suggest( $in );
		if ( is_wp_error( $r ) ) {
			wp_send_json_error( array( 'msg' => $r->get_error_message() ) );
		}
		wp_send_json_success( array( 'fields' => $r, 'left' => ACEPU_AI::left() ) );
	}

	public static function ajax_ai_image() {
		self::ajax_guard();
		$prompt = isset( $_POST['prompt'] ) ? sanitize_textarea_field( wp_unslash( $_POST['prompt'] ) ) : '';
		$aspect = isset( $_POST['aspect'] ) ? sanitize_key( wp_unslash( $_POST['aspect'] ) ) : 'portrait';
		if ( strlen( $prompt ) < 8 ) {
			wp_send_json_error( array( 'msg' => __( 'Describe the picture first (a sentence or two).', '2ace-popups' ) ) );
		}
		$r = ACEPU_AI::artwork( $prompt, $aspect );
		if ( is_wp_error( $r ) ) {
			wp_send_json_error( array( 'msg' => $r->get_error_message() ) );
		}
		wp_send_json_success( array( 'id' => $r['id'], 'url' => $r['url'], 'left' => ACEPU_AI::left() ) );
	}

	/* ---------------------------------------------------------------------
	 * List table
	 * ------------------------------------------------------------------ */

	public static function status( $post ) {
		if ( 'publish' !== $post->post_status ) {
			return array( 'off', __( 'Off (draft)', '2ace-popups' ) );
		}
		$s     = ACEPU_Store::get( $post->ID );
		$now   = time();
		$start = ACEPU_Store::ts( $s['start'] );
		$end   = ACEPU_Store::ts( $s['end'] );
		if ( $start && $start > $now ) {
			return array( 'sched', sprintf( /* translators: %s: date. */ __( 'Scheduled for %s', '2ace-popups' ), wp_date( 'j.m.Y H:i', $start ) ) );
		}
		if ( $end && $end < $now ) {
			return array( 'ended', __( 'Ended', '2ace-popups' ) );
		}
		return array( 'live', __( 'Live', '2ace-popups' ) );
	}

	public static function columns( $cols ) {
		return array(
			'cb'      => $cols['cb'],
			'title'   => __( 'Name', '2ace-popups' ),
			'acepu_s' => __( 'Status', '2ace-popups' ),
			'acepu_w' => __( 'Shows', '2ace-popups' ),
			'acepu_r' => __( 'Results', '2ace-popups' ),
			'date'    => __( 'Date', '2ace-popups' ),
		);
	}

	public static function column( $col, $id ) {
		if ( 'acepu_s' === $col ) {
			$st = self::status( get_post( $id ) );
			echo '<span class="acepu-pill acepu-pill--' . esc_attr( $st[0] ) . '">' . esc_html( $st[1] ) . '</span>';
		}
		if ( 'acepu_w' === $col ) {
			$s      = ACEPU_Store::get( $id );
			$labels = acepu_schema()['triggers']['opts'];
			$names  = array_map( static function ( $t ) use ( $labels ) { return isset( $labels[ $t ] ) ? $labels[ $t ] : $t; }, $s['triggers'] );
			echo esc_html( implode( ' · ', $names ) );
		}
		if ( 'acepu_r' === $col ) {
			$t    = ACEPU_Store::totals( $id );
			$s    = ACEPU_Store::get( $id );
			$conv = 'email' === $s['mode'] ? $t['signup'] : ( $t['cta'] + $t['copy'] );
			/* translators: 1: views, 2: conversions, 3: percentage. */
			echo esc_html( sprintf( __( '%1$d shown · %2$d acted · %3$s%%', '2ace-popups' ), $t['view'], $conv, $t['view'] ? round( $conv / $t['view'] * 100, 1 ) : 0 ) );
		}
	}

	public static function row_actions( $actions, $post ) {
		if ( ACEPU_Store::CPT !== $post->post_type ) {
			return $actions;
		}
		unset( $actions['inline hide-if-no-js'] );
		$dup = wp_nonce_url( admin_url( 'admin-post.php?action=acepu_duplicate&id=' . $post->ID ), 'acepu_dup_' . $post->ID );
		$actions['acepu_dup'] = '<a href="' . esc_url( $dup ) . '">' . esc_html__( 'Duplicate', '2ace-popups' ) . '</a>';
		return $actions;
	}

	public static function duplicate() {
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		if ( ! current_user_can( self::CAP ) || ! check_admin_referer( 'acepu_dup_' . $id ) ) {
			wp_die( esc_html__( 'Not allowed.', '2ace-popups' ) );
		}
		$p = get_post( $id );
		if ( ! $p || ACEPU_Store::CPT !== $p->post_type ) {
			wp_die( esc_html__( 'Not found.', '2ace-popups' ) );
		}
		$new = wp_insert_post( array( 'post_type' => ACEPU_Store::CPT, 'post_status' => 'draft', 'post_title' => sprintf( /* translators: %s: title. */ __( '%s (copy)', '2ace-popups' ), $p->post_title ) ) );
		ACEPU_Store::save( $new, ACEPU_Store::get( $id ) );
		wp_safe_redirect( admin_url( 'post.php?post=' . $new . '&action=edit' ) );
		exit;
	}

	public static function reset_stats() {
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		if ( ! current_user_can( self::CAP ) || ! check_admin_referer( 'acepu_reset_' . $id ) ) {
			wp_die( esc_html__( 'Not allowed.', '2ace-popups' ) );
		}
		ACEPU_Store::reset_stats( $id );
		wp_safe_redirect( admin_url( 'post.php?post=' . $id . '&action=edit' ) );
		exit;
	}

	/* ---------------------------------------------------------------------
	 * Settings
	 * ------------------------------------------------------------------ */

	public static function page_settings() {
		if ( ! current_user_can( self::CAP ) ) {
			return;
		}
		$o = ACEPU_Store::options();
		?>
		<div class="wrap acepu-wrap">
			<h1><?php esc_html_e( 'Pop-up settings', '2ace-popups' ); ?></h1>
			<?php if ( isset( $_GET['saved'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Saved.', '2ace-popups' ); ?></p></div>
			<?php endif; ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="acepu_settings">
				<?php wp_nonce_field( 'acepu_settings' ); ?>
				<table class="form-table" role="presentation">
					<tr><th><?php esc_html_e( 'Pause all pop-ups', '2ace-popups' ); ?></th><td><label><input type="checkbox" name="pause" value="1" <?php checked( $o['pause'] ); ?>> <?php esc_html_e( 'Nothing is shown to visitors (handy for emergencies)', '2ace-popups' ); ?></label></td></tr>
					<tr><th><?php esc_html_e( 'Pause between pop-ups', '2ace-popups' ); ?></th><td><input type="number" class="small-text" name="gap" value="<?php echo esc_attr( $o['gap'] ); ?>" min="0" max="3600"> <?php esc_html_e( 'seconds. Only one pop-up is ever open at a time; this is the quiet time before the next one.', '2ace-popups' ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Never show on', '2ace-popups' ); ?></th><td>
						<?php foreach ( acepu_page_types() as $k => $l ) : ?>
							<label style="display:inline-block;margin:0 16px 6px 0"><input type="checkbox" name="exclude[]" value="<?php echo esc_attr( $k ); ?>" <?php checked( in_array( $k, (array) $o['exclude'], true ) ); ?>> <?php echo esc_html( $l ); ?></label>
						<?php endforeach; ?>
						<p class="description"><?php esc_html_e( 'Cart, checkout and account are best left alone: a pop-up there costs sales.', '2ace-popups' ); ?></p></td></tr>
					<tr><th><?php esc_html_e( 'Cookie banner', '2ace-popups' ); ?></th><td><label><input type="checkbox" name="wait_cookie" value="1" <?php checked( $o['wait_cookie'] ); ?>> <?php esc_html_e( 'Wait until the visitor has answered the cookie banner', '2ace-popups' ); ?></label></td></tr>
					<tr><th><?php esc_html_e( 'You, as administrator', '2ace-popups' ); ?></th><td><label><input type="checkbox" name="admins" value="1" <?php checked( $o['admins'] ); ?>> <?php esc_html_e( 'See the pop-ups while logged in (your visits are not counted in the statistics)', '2ace-popups' ); ?></label></td></tr>
					<tr><th><?php esc_html_e( 'Personal coupons', '2ace-popups' ); ?></th><td><label><input type="checkbox" name="restrict_email" value="1" <?php checked( $o['restrict_email'] ); ?>> <?php esc_html_e( 'Only valid for the e-mail address that asked for it (stops code sharing)', '2ace-popups' ); ?></label></td></tr>
				</table>
				<h2><?php esc_html_e( 'AI', '2ace-popups' ); ?></h2>
				<p class="description"><?php echo wp_kses_post( sprintf( /* translators: %s: link. */ __( 'The Google connection is shared with %s.', '2ace-popups' ), '<a href="' . esc_url( ACEPU_AI::settings_url() ) . '">' . esc_html__( 'AI photo editor', '2ace-popups' ) . '</a>' ) ); ?> <?php echo ACEPU_AI::available() ? esc_html__( 'Status: connected.', '2ace-popups' ) : esc_html__( 'Status: not connected.', '2ace-popups' ); ?></p>
				<table class="form-table" role="presentation">
					<tr><th><?php esc_html_e( 'Text model', '2ace-popups' ); ?></th><td><input type="text" class="regular-text" name="ai_text" value="<?php echo esc_attr( $o['ai_text'] ); ?>"> <input type="text" class="small-text" name="ai_text_loc" value="<?php echo esc_attr( $o['ai_text_loc'] ); ?>"> <span class="description"><?php esc_html_e( 'model, location', '2ace-popups' ); ?></span></td></tr>
					<tr><th><?php esc_html_e( 'Image model', '2ace-popups' ); ?></th><td><input type="text" class="regular-text" name="ai_image" value="<?php echo esc_attr( $o['ai_image'] ); ?>"> <input type="text" class="small-text" name="ai_image_loc" value="<?php echo esc_attr( $o['ai_image_loc'] ); ?>"> <span class="description"><?php esc_html_e( 'model, location', '2ace-popups' ); ?></span></td></tr>
					<tr><th><?php esc_html_e( 'Daily limit', '2ace-popups' ); ?></th><td><input type="number" class="small-text" name="ai_limit" value="<?php echo esc_attr( $o['ai_limit'] ); ?>" min="0" max="500"> <?php esc_html_e( 'AI requests per day (texts and pictures together)', '2ace-popups' ); ?></td></tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	public static function save_settings() {
		if ( ! current_user_can( self::CAP ) || ! check_admin_referer( 'acepu_settings' ) ) {
			wp_die( esc_html__( 'Not allowed.', '2ace-popups' ) );
		}
		$ex = isset( $_POST['exclude'] ) && is_array( $_POST['exclude'] ) ? array_map( 'sanitize_key', wp_unslash( $_POST['exclude'] ) ) : array();
		update_option(
			ACEPU_Store::OPTION,
			array(
				'pause'          => empty( $_POST['pause'] ) ? 0 : 1,
				'gap'            => isset( $_POST['gap'] ) ? max( 0, min( 3600, (int) $_POST['gap'] ) ) : 45,
				'exclude'        => array_values( array_intersect( $ex, array_keys( acepu_page_types() ) ) ),
				'wait_cookie'    => empty( $_POST['wait_cookie'] ) ? 0 : 1,
				'admins'         => empty( $_POST['admins'] ) ? 0 : 1,
				'restrict_email' => empty( $_POST['restrict_email'] ) ? 0 : 1,
				'ai_text'        => isset( $_POST['ai_text'] ) ? sanitize_text_field( wp_unslash( $_POST['ai_text'] ) ) : 'gemini-2.5-flash',
				'ai_text_loc'    => isset( $_POST['ai_text_loc'] ) ? sanitize_text_field( wp_unslash( $_POST['ai_text_loc'] ) ) : 'global',
				'ai_image'       => isset( $_POST['ai_image'] ) ? sanitize_text_field( wp_unslash( $_POST['ai_image'] ) ) : 'gemini-3-pro-image',
				'ai_image_loc'   => isset( $_POST['ai_image_loc'] ) ? sanitize_text_field( wp_unslash( $_POST['ai_image_loc'] ) ) : 'global',
				'ai_limit'       => isset( $_POST['ai_limit'] ) ? max( 0, min( 500, (int) $_POST['ai_limit'] ) ) : 30,
			),
			false
		);
		wp_safe_redirect( admin_url( 'edit.php?post_type=' . ACEPU_Store::CPT . '&page=acepu-settings&saved=1' ) );
		exit;
	}

	/* ---------------------------------------------------------------------
	 * Leads
	 * ------------------------------------------------------------------ */

	public static function page_leads() {
		if ( ! current_user_can( self::CAP ) ) {
			return;
		}
		global $wpdb;
		$t     = ACEPU_Store::leads_table();
		$per   = 50;
		$paged = max( 1, isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1 ); // phpcs:ignore WordPress.Security.NonceVerification
		$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $t" ); // phpcs:ignore WordPress.DB
		$rows  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $t ORDER BY id DESC LIMIT %d OFFSET %d", $per, ( $paged - 1 ) * $per ) ); // phpcs:ignore WordPress.DB
		$csv   = wp_nonce_url( admin_url( 'admin-post.php?action=acepu_csv' ), 'acepu_csv' );
		?>
		<div class="wrap acepu-wrap">
			<h1><?php esc_html_e( 'Leads', '2ace-popups' ); ?> <a class="page-title-action" href="<?php echo esc_url( $csv ); ?>"><?php esc_html_e( 'Export CSV', '2ace-popups' ); ?></a></h1>
			<p class="description"><?php esc_html_e( 'E-mail addresses collected by pop-ups, with the consent each person gave. Only contact people who ticked the newsletter box for marketing e-mails. Everything here also appears in WordPress personal data export and erase tools.', '2ace-popups' ); ?></p>
			<table class="widefat striped">
				<thead><tr><th><?php esc_html_e( 'Date', '2ace-popups' ); ?></th><th><?php esc_html_e( 'E-mail', '2ace-popups' ); ?></th><th><?php esc_html_e( 'Pop-up', '2ace-popups' ); ?></th><th><?php esc_html_e( 'Coupon', '2ace-popups' ); ?></th><th><?php esc_html_e( 'Coupon consent', '2ace-popups' ); ?></th><th><?php esc_html_e( 'Newsletter consent', '2ace-popups' ); ?></th><th></th></tr></thead>
				<tbody>
				<?php if ( ! $rows ) : ?>
					<tr><td colspan="7"><?php esc_html_e( 'No sign-ups yet.', '2ace-popups' ); ?></td></tr>
				<?php endif; ?>
				<?php foreach ( $rows as $r ) : ?>
					<tr>
						<td><?php echo esc_html( mysql2date( 'j.m.Y H:i', $r->created ) ); ?></td>
						<td><?php echo esc_html( $r->email ); ?></td>
						<td><?php echo esc_html( get_the_title( $r->popup_id ) ); ?></td>
						<td><code><?php echo esc_html( $r->coupon ); ?></code></td>
						<td><?php echo $r->consent_service ? '✔' : '—'; ?></td>
						<td><?php echo $r->consent_news ? '✔' : '—'; ?></td>
						<td><a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=acepu_lead_delete&id=' . (int) $r->id ), 'acepu_lead_' . (int) $r->id ) ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Delete this entry?', '2ace-popups' ) ); ?>')"><?php esc_html_e( 'Delete', '2ace-popups' ); ?></a></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<?php
			echo wp_kses_post(
				paginate_links(
					array(
						'base'    => add_query_arg( 'paged', '%#%' ),
						'format'  => '',
						'current' => $paged,
						'total'   => max( 1, (int) ceil( $total / $per ) ),
					)
				)
			);
			?>
		</div>
		<?php
	}

	public static function csv() {
		if ( ! current_user_can( self::CAP ) || ! check_admin_referer( 'acepu_csv' ) ) {
			wp_die( esc_html__( 'Not allowed.', '2ace-popups' ) );
		}
		global $wpdb;
		$rows = $wpdb->get_results( 'SELECT * FROM ' . ACEPU_Store::leads_table() . ' ORDER BY id DESC' ); // phpcs:ignore WordPress.DB
		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=popup-leads-' . gmdate( 'Y-m-d' ) . '.csv' );
		$out = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		fwrite( $out, "\xEF\xBB\xBF" ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		fputcsv( $out, array( 'date', 'email', 'popup', 'coupon', 'coupon_consent', 'newsletter_consent', 'consent_text' ) );
		foreach ( $rows as $r ) {
			// A leading = + - @ would run as a formula in a spreadsheet.
			$mail = preg_match( '/^[=+\-@]/', $r->email ) ? "'" . $r->email : $r->email;
			fputcsv( $out, array( $r->created, $mail, get_the_title( $r->popup_id ), $r->coupon, $r->consent_service, $r->consent_news, $r->consent_text ) );
		}
		fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		exit;
	}

	public static function lead_delete() {
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		if ( ! current_user_can( self::CAP ) || ! check_admin_referer( 'acepu_lead_' . $id ) ) {
			wp_die( esc_html__( 'Not allowed.', '2ace-popups' ) );
		}
		global $wpdb;
		$wpdb->delete( ACEPU_Store::leads_table(), array( 'id' => $id ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		wp_safe_redirect( admin_url( 'edit.php?post_type=' . ACEPU_Store::CPT . '&page=acepu-leads' ) );
		exit;
	}

	/* ---------------------------------------------------------------------
	 * WordPress privacy tools
	 * ------------------------------------------------------------------ */

	public static function register_exporter( $exporters ) {
		$exporters['2ace-popups'] = array( 'exporter_friendly_name' => __( 'Pop-up sign-ups', '2ace-popups' ), 'callback' => array( __CLASS__, 'export_email' ) );
		return $exporters;
	}

	public static function register_eraser( $erasers ) {
		$erasers['2ace-popups'] = array( 'eraser_friendly_name' => __( 'Pop-up sign-ups', '2ace-popups' ), 'callback' => array( __CLASS__, 'erase_email' ) );
		return $erasers;
	}

	public static function export_email( $email ) {
		global $wpdb;
		$rows  = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . ACEPU_Store::leads_table() . ' WHERE email = %s', strtolower( $email ) ) ); // phpcs:ignore WordPress.DB
		$items = array();
		foreach ( $rows as $r ) {
			$items[] = array(
				'group_id'    => 'acepu',
				'group_label' => __( 'Pop-up sign-ups', '2ace-popups' ),
				'item_id'     => 'acepu-' . $r->id,
				'data'        => array(
					array( 'name' => __( 'Date', '2ace-popups' ), 'value' => $r->created ),
					array( 'name' => __( 'E-mail', '2ace-popups' ), 'value' => $r->email ),
					array( 'name' => __( 'Coupon', '2ace-popups' ), 'value' => $r->coupon ),
					array( 'name' => __( 'Newsletter consent', '2ace-popups' ), 'value' => $r->consent_news ? __( 'Yes', '2ace-popups' ) : __( 'No', '2ace-popups' ) ),
				),
			);
		}
		return array( 'data' => $items, 'done' => true );
	}

	public static function erase_email( $email ) {
		global $wpdb;
		$n = $wpdb->delete( ACEPU_Store::leads_table(), array( 'email' => strtolower( $email ) ), array( '%s' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return array( 'items_removed' => (bool) $n, 'items_retained' => false, 'messages' => array(), 'done' => true );
	}
}
