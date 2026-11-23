<?php
/**
 * Category navigation: a three-level tree for the header menu, subcategory chips
 * on shop and category pages. Parents count the products of their children, so
 * a parent whose products all sit in subcategories still shows up.
 *
 * @package 2acetech
 */

defined( 'ABSPATH' ) || exit;

/**
 * Visible product categories as a tree: top level with their visible children.
 *
 * @param int $limit Max top-level categories.
 * @return array[] Each: term, url, label, count, children[ term, url, label, count, children[ same ] ].
 */
function ace_category_tree( $limit = 12 ) {
	if ( ! ace_has_store() || ace_is_demo() ) {
		return array();
	}
	$base = array(
		'taxonomy'   => 'product_cat',
		'hide_empty' => true,
		'pad_counts' => true,
		'exclude'    => array( (int) get_option( 'default_product_cat' ) ),
		'orderby'    => 'menu_order',
	);
	$tops = get_terms( array_merge( $base, array( 'parent' => 0, 'number' => $limit ) ) );
	if ( is_wp_error( $tops ) ) {
		return array();
	}
	$out = array();
	foreach ( $tops as $t ) {
		$kids     = get_terms( array_merge( $base, array( 'parent' => $t->term_id ) ) );
		$children = array();
		if ( ! is_wp_error( $kids ) ) {
			foreach ( $kids as $k ) {
				// Third level (Sport i turystyka > Camping > Materace i maty).
				$grand = get_terms( array_merge( $base, array( 'parent' => $k->term_id ) ) );
				$g     = array();
				if ( ! is_wp_error( $grand ) ) {
					foreach ( $grand as $gk ) {
						$g[] = array( 'term' => $gk, 'url' => get_term_link( $gk ), 'label' => $gk->name, 'count' => (int) $gk->count );
					}
				}
				$children[] = array( 'term' => $k, 'url' => get_term_link( $k ), 'label' => $k->name, 'count' => (int) $k->count, 'children' => $g );
			}
		}
		$out[] = array( 'term' => $t, 'url' => get_term_link( $t ), 'label' => $t->name, 'count' => (int) $t->count, 'children' => $children );
	}
	return $out;
}

/**
 * Chips above the product grid: the subcategories of the category you are in
 * (or its siblings when you are already inside one), top-level categories on the shop page.
 */
function ace_category_chips() {
	if ( ! function_exists( 'is_shop' ) || ! ( is_shop() || is_product_category() ) || is_search() ) {
		return;
	}
	$base = array(
		'taxonomy'   => 'product_cat',
		'hide_empty' => true,
		'pad_counts' => true,
		'exclude'    => array( (int) get_option( 'default_product_cat' ) ),
		'orderby'    => 'menu_order',
	);
	$current = is_product_category() ? get_queried_object() : null;
	$own = $current ? get_terms( array_merge( $base, array( 'parent' => $current->term_id ) ) ) : array();
	if ( $current && ! is_wp_error( $own ) && $own ) {
		// A category with subcategories of its own: show those (Camping > Materace i maty, Pompki ...).
		$items = $own;
		$all   = array( get_term_link( $current ), sprintf( /* translators: %s: category. */ __( 'All in %s', '2acetech' ), $current->name ), true );
	} elseif ( $current ) {
		$parent = $current->parent ? get_term( $current->parent, 'product_cat' ) : $current;
		$items  = get_terms( array_merge( $base, array( 'parent' => $parent->term_id ) ) );
		$all    = array( get_term_link( $parent ), $parent->term_id === $current->term_id ? sprintf( /* translators: %s: category. */ __( 'All in %s', '2acetech' ), $parent->name ) : sprintf( __( 'All in %s', '2acetech' ), $parent->name ), $parent->term_id === $current->term_id );
	} else {
		$items = get_terms( array_merge( $base, array( 'parent' => 0 ) ) );
		$all   = array( ace_shop_url(), __( 'All products', '2acetech' ), true );
	}
	if ( is_wp_error( $items ) || ! $items ) {
		return;
	}
	echo '<nav class="cat-chips" aria-label="' . esc_attr__( 'Categories', '2acetech' ) . '"><ul>';
	printf( '<li><a class="cat-chip%s" href="%s">%s</a></li>', $all[2] ? ' is-current' : '', esc_url( $all[0] ), esc_html( $all[1] ) );
	foreach ( $items as $t ) {
		printf(
			'<li><a class="cat-chip%s" href="%s">%s <small>%d</small></a></li>',
			$current && $current->term_id === $t->term_id ? ' is-current' : '',
			esc_url( get_term_link( $t ) ),
			esc_html( $t->name ),
			(int) $t->count
		);
	}
	echo '</ul></nav>';
}
add_action( 'woocommerce_before_shop_loop', 'ace_category_chips', 5 );
