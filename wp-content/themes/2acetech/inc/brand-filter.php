<?php
/**
 * Brand filter: chips above the product grid on the shop and category pages.
 *
 * Shows the brands (WooCommerce Brands, taxonomy product_brand) that have products on the page you are
 * on, with how many. Clicking one adds ?product_brand=<slug> to the address (WordPress filters the
 * list by itself); "All brands" removes it. Chips only appear when there is something to choose:
 * two or more brands in the list, or a brand is already selected. The cleaning plugin creates the
 * brands while it cleans products.
 *
 * @package 2acetech
 */

defined( 'ABSPATH' ) || exit;

/**
 * Brands of the products in the current list, with counts.
 *
 * On a category page: the products of that category (and its subcategories). On the shop page: every
 * published product. A selected brand does not narrow the list of brands (so you can switch brand).
 *
 * @return array[] Each: term (WP_Term), count (int); biggest first.
 */
function ace_brand_filter_items() {
	if ( ! taxonomy_exists( 'product_brand' ) ) {
		return array();
	}
	$args = array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'posts_per_page' => 500,
		'fields'         => 'ids',
		'no_found_rows'  => true,
		'tax_query'      => array(), // phpcs:ignore WordPress.DB.SlowDBQuery
	);
	if ( function_exists( 'is_product_category' ) && is_product_category() ) {
		$cat = get_queried_object();
		if ( $cat instanceof WP_Term ) {
			$args['tax_query'][] = array( 'taxonomy' => 'product_cat', 'field' => 'term_id', 'terms' => $cat->term_id, 'include_children' => true );
		}
	}
	// Hidden products are not listed in the shop either.
	$args['tax_query'][] = array( 'taxonomy' => 'product_visibility', 'field' => 'name', 'terms' => array( 'exclude-from-catalog' ), 'operator' => 'NOT IN' );

	$ids = get_posts( $args );
	if ( ! $ids ) {
		return array();
	}
	$rows = wp_get_object_terms( $ids, 'product_brand', array( 'fields' => 'all_with_object_id' ) );
	if ( is_wp_error( $rows ) || ! $rows ) {
		return array();
	}
	$items = array();
	foreach ( $rows as $t ) {
		if ( ! isset( $items[ $t->term_id ] ) ) {
			$items[ $t->term_id ] = array( 'term' => $t, 'count' => 0 );
		}
		$items[ $t->term_id ]['count']++;
	}
	usort(
		$items,
		static function ( $a, $b ) {
			return $b['count'] <=> $a['count'] ?: strcasecmp( $a['term']->name, $b['term']->name );
		}
	);
	return $items;
}

/** The brand slug selected in the address, or ''. */
function ace_brand_filter_current() {
	return isset( $_GET['product_brand'] ) ? sanitize_title( wp_unslash( $_GET['product_brand'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
}

/**
 * Address of the current list with another brand selected ('' = no brand).
 *
 * @param string $slug Brand slug.
 * @return string
 */
function ace_brand_filter_url( $slug ) {
	$base = remove_query_arg( array( 'product_brand', 'paged' ) );
	$base = preg_replace( '#/page/\d+/?#', '/', $base ); // Back to page 1.
	return '' === $slug ? $base : add_query_arg( 'product_brand', rawurlencode( $slug ), $base );
}

function ace_brand_filter() {
	if ( ! function_exists( 'is_shop' ) || ! ( is_shop() || is_product_category() ) || is_search() ) {
		return;
	}
	$items   = ace_brand_filter_items();
	$current = ace_brand_filter_current();
	if ( count( $items ) < 2 && ! $current ) {
		return; // Nothing to choose.
	}
	if ( ! $items ) {
		return;
	}
	echo '<nav class="cat-chips cat-chips--brands" aria-label="' . esc_attr__( 'Brands', '2acetech' ) . '"><span class="cat-chips__label">' . esc_html__( 'Brand', '2acetech' ) . '</span><ul>';
	printf(
		'<li><a class="cat-chip%s" href="%s">%s</a></li>',
		'' === $current ? ' is-current' : '',
		esc_url( ace_brand_filter_url( '' ) ),
		esc_html__( 'All brands', '2acetech' )
	);
	foreach ( $items as $it ) {
		printf(
			'<li><a class="cat-chip%s" href="%s"%s>%s <small>%d</small></a></li>',
			$current === $it['term']->slug ? ' is-current' : '',
			esc_url( ace_brand_filter_url( $it['term']->slug ) ),
			$current === $it['term']->slug ? ' aria-current="true"' : '',
			esc_html( $it['term']->name ),
			(int) $it['count']
		);
	}
	echo '</ul></nav>';
}
// Replaced by the filter side panel (inc/shop-filters.php); kept for reference.
// add_action( 'woocommerce_before_shop_loop', 'ace_brand_filter', 6 );
