<?php
/**
 * Header search. Searches products with an optional category filter when
 * WooCommerce is active, otherwise searches the site.
 *
 * @package 2acetech
 */

defined( 'ABSPATH' ) || exit;

$uid = wp_unique_id( 'search-' );
?>
<form role="search" method="get" class="search" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="screen-reader-text" for="<?php echo esc_attr( $uid ); ?>"><?php esc_html_e( 'Search', '2acetech' ); ?></label>
	<?php if ( ace_has_store() ) : ?>
		<input type="hidden" name="post_type" value="product">
		<?php
		wp_dropdown_categories(
			array(
				'taxonomy'          => 'product_cat',
				'name'              => 'product_cat',
				'value_field'       => 'slug',
				'show_option_all'   => __( 'All categories', '2acetech' ),
				'option_none_value' => '',
				'hide_empty'        => true,
				'hierarchical'      => false,
				'class'             => 'search__cat',
				'selected'          => isset( $_GET['product_cat'] ) ? sanitize_text_field( wp_unslash( $_GET['product_cat'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification
				'exclude'           => array( (int) get_option( 'default_product_cat' ) ),
				'id'                => $uid . '-cat',
			)
		);
		?>
	<?php endif; ?>
	<input id="<?php echo esc_attr( $uid ); ?>" class="search__input" type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php echo esc_attr( ace_has_store() ? __( 'Search products…', '2acetech' ) : __( 'Search…', '2acetech' ) ); ?>">
	<button class="search__btn" type="submit">
		<span class="screen-reader-text"><?php esc_html_e( 'Search', '2acetech' ); ?></span>
		<?php echo ace_icon( 'search', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</button>
</form>
