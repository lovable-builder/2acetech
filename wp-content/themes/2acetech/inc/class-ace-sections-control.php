<?php
/**
 * Customizer control: tick and drag to show, hide and re-order homepage sections.
 *
 * The setting stores the enabled section keys in order, comma-separated.
 *
 * @package 2acetech
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sortable checklist control.
 */
class ACE_Sections_Control extends WP_Customize_Control {

	/**
	 * Control type.
	 *
	 * @var string
	 */
	public $type = 'ace_sections';

	/**
	 * Load jQuery UI Sortable for the drag handles.
	 */
	public function enqueue() {
		wp_enqueue_script( 'jquery-ui-sortable' );
	}

	/**
	 * Markup: enabled sections first (in saved order), then the hidden ones.
	 */
	public function render_content() {
		$all     = ace_home_sections();
		$enabled = array_filter( array_map( 'trim', explode( ',', (string) $this->value() ) ) );
		$rows    = array();

		foreach ( $enabled as $key ) {
			if ( isset( $all[ $key ] ) ) {
				$rows[ $key ] = true;
			}
		}
		foreach ( $all as $key => $label ) {
			if ( ! isset( $rows[ $key ] ) ) {
				$rows[ $key ] = false;
			}
		}
		?>
		<span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
		<?php if ( $this->description ) : ?>
			<span class="description customize-control-description"><?php echo esc_html( $this->description ); ?></span>
		<?php endif; ?>
		<ul class="ace-sections" data-setting="<?php echo esc_attr( $this->id ); ?>">
			<?php foreach ( $rows as $key => $on ) : ?>
				<li data-key="<?php echo esc_attr( $key ); ?>">
					<span class="ace-handle" aria-hidden="true">&#8942;&#8942;</span>
					<label>
						<input type="checkbox" <?php checked( $on ); ?>>
						<?php echo esc_html( $all[ $key ] ); ?>
					</label>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php
	}
}
