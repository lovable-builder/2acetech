<?php
/**
 * "Shop by category" mega menu, laid out like AliExpress: the main categories in a
 * list on the left, and next to it - for the category you point at - its
 * subcategories as picture tiles, the brands sold in it, and the third-level
 * categories grouped under their parent.
 *
 * The data is cached for a few hours (and cleared whenever a product, category or
 * brand changes), because it walks every category and the brand/category map.
 *
 * @package 2acetech
 */

defined( 'ABSPATH' ) || exit;

/**
 * Picture for a category tile: the image set on the category, otherwise the photo of
 * the newest product inside it (including its subcategories).
 *
 * @param WP_Term $term Category.
 * @return int Attachment id, 0 when there is none.
 */
function ace_mega_term_image( $term ) {
	$id = (int) get_term_meta( $term->term_id, 'thumbnail_id', true );
	if ( $id ) {
		return $id;
	}
	$ids = get_posts(
		array(
			'post_type'        => 'product',
			'post_status'      => 'publish',
			'posts_per_page'   => 1,
			'fields'           => 'ids',
			'no_found_rows'    => true,
			'suppress_filters' => true,
			'meta_query'       => array( array( 'key' => '_thumbnail_id', 'compare' => 'EXISTS' ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
			'tax_query'        => array( array( 'taxonomy' => 'product_cat', 'field' => 'term_id', 'terms' => array( $term->term_id ), 'include_children' => true ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
	return $ids ? (int) get_post_thumbnail_id( $ids[0] ) : 0;
}

/**
 * Category node for the menu: label, link, picture, and its own children.
 *
 * @param array $node One entry of ace_category_tree().
 * @return array
 */
function ace_mega_node( $node ) {
	return array(
		'id'    => (int) $node['term']->term_id,
		'slug'  => $node['term']->slug,
		'label' => $node['label'],
		'url'   => is_wp_error( $node['url'] ) ? '' : $node['url'],
		'img'   => ace_mega_term_image( $node['term'] ),
		'kids'  => array(),
	);
}

/**
 * Everything the mega menu shows.
 *
 * @return array[] Each top-level category: id, slug, label, url, img, kids[] (each with kids[] of its own), brands[].
 */
function ace_mega_data() {
	$cached = get_transient( 'ace_mega_data_v1' );
	if ( is_array( $cached ) ) {
		return $cached;
	}

	$tree = ace_category_tree( 12 );
	if ( ! $tree ) {
		return array();
	}

	// Brands per top-level category.
	$brand_terms = array();
	$map         = array( 'brands' => array() );
	if ( taxonomy_exists( 'product_brand' ) && function_exists( 'ace_brand_category_map' ) ) {
		$found = get_terms( array( 'taxonomy' => 'product_brand', 'hide_empty' => true, 'number' => 80 ) );
		if ( ! is_wp_error( $found ) ) {
			foreach ( $found as $b ) {
				$brand_terms[ $b->term_id ] = $b;
			}
			$map = ace_brand_category_map();
		}
	}

	$out = array();
	foreach ( $tree as $top ) {
		$item = ace_mega_node( $top );
		foreach ( $top['children'] as $kid ) {
			$k = ace_mega_node( $kid );
			foreach ( $kid['children'] as $grand ) {
				$k['kids'][] = ace_mega_node( $grand );
			}
			$item['kids'][] = $k;
		}

		$item['brands'] = array();
		foreach ( $brand_terms as $bid => $b ) {
			if ( empty( $map['brands'][ $bid ][ $top['term']->slug ] ) ) {
				continue;
			}
			$link = get_term_link( $b );
			if ( is_wp_error( $link ) ) {
				continue;
			}
			$item['brands'][] = array(
				'label' => $b->name,
				'url'   => $link,
				'logo'  => function_exists( 'ace_brand_logo' ) ? ace_brand_logo( $b ) : '',
			);
			if ( count( $item['brands'] ) >= 12 ) {
				break;
			}
		}
		$out[] = $item;
	}

	set_transient( 'ace_mega_data_v1', $out, 6 * HOUR_IN_SECONDS );
	return $out;
}

/**
 * Drop the cached menu data (products, categories or brands changed).
 */
function ace_mega_flush() {
	delete_transient( 'ace_mega_data_v1' );
}
add_action( 'save_post_product', 'ace_mega_flush' );
add_action( 'deleted_post', 'ace_mega_flush' );
add_action( 'woocommerce_update_product', 'ace_mega_flush' );
add_action( 'created_term', 'ace_mega_flush' );
add_action( 'edited_term', 'ace_mega_flush' );
add_action( 'delete_term', 'ace_mega_flush' );
add_action( 'set_object_terms', 'ace_mega_flush' );

/**
 * A picture tile (round photo, name below).
 *
 * @param array $n Node with label, url, img.
 * @return string HTML.
 */
function ace_mega_tile( $n ) {
	$img = $n['img'] ? wp_get_attachment_image( $n['img'], 'ace-tile', false, array( 'alt' => '', 'loading' => 'lazy', 'decoding' => 'async' ) ) : '';
	if ( ! $img ) {
		$img = '<span class="mega__initial" aria-hidden="true">' . esc_html( mb_strtoupper( mb_substr( $n['label'], 0, 1 ) ) ) . '</span>';
	}
	return sprintf(
		'<li><a class="mega__tile" href="%s"><span class="mega__pic">%s</span><span class="mega__name">%s</span></a></li>',
		esc_url( $n['url'] ),
		$img, // Attachment markup from WordPress, or escaped initial above.
		esc_html( $n['label'] )
	);
}

/**
 * Print the menu: a list of main categories, each with its panel.
 *
 * @param array[] $data From ace_mega_data().
 */
function ace_mega_render( $data ) {
	echo '<ul class="mega__list">';
	foreach ( $data as $i => $top ) {
		$thumb = $top['img'] ? wp_get_attachment_image( $top['img'], 'thumbnail', false, array( 'alt' => '', 'loading' => 'lazy', 'decoding' => 'async' ) ) : '';
		printf(
			'<li class="mega__item%s" data-mega-item="%d"><a class="mega__link" href="%s">%s<span class="mega__label">%s</span>%s</a>',
			$i ? '' : ' is-active',
			(int) $top['id'],
			esc_url( $top['url'] ),
			$thumb ? '<span class="mega__thumb">' . $thumb . '</span>' : '', // phpcs:ignore WordPress.Security.EscapeOutput
			esc_html( $top['label'] ),
			ace_icon( 'right', 16 ) // phpcs:ignore WordPress.Security.EscapeOutput
		);

		echo '<div class="mega__pane">';
		printf(
			'<div class="mega__head"><strong>%s</strong><a href="%s">%s%s</a></div>',
			esc_html( $top['label'] ),
			esc_url( $top['url'] ),
			esc_html__( 'View all', '2acetech' ),
			ace_icon( 'arrow', 15 ) // phpcs:ignore WordPress.Security.EscapeOutput
		);

		if ( $top['kids'] ) {
			echo '<section class="mega__sec"><h3>' . esc_html__( 'Recommended', '2acetech' ) . '</h3><ul class="mega__tiles">';
			foreach ( $top['kids'] as $kid ) {
				echo ace_mega_tile( $kid ); // phpcs:ignore WordPress.Security.EscapeOutput
			}
			echo '</ul></section>';
		}

		if ( $top['brands'] ) {
			echo '<section class="mega__sec"><h3>' . esc_html__( 'Shop by brand', '2acetech' ) . '</h3><ul class="mega__tiles mega__tiles--brands">';
			foreach ( $top['brands'] as $b ) {
				// With a logo: logo plus name underneath. Without: the name itself is the tile, no repeated caption.
				$face = $b['logo'] ? $b['logo'] : '<span class="mega__brandname">' . esc_html( $b['label'] ) . '</span>';
				printf(
					'<li><a class="mega__tile mega__tile--brand" href="%s" aria-label="%s"><span class="mega__pic mega__pic--brand">%s</span>%s</a></li>',
					esc_url( $b['url'] ),
					esc_attr( $b['label'] ),
					$face, // Logo markup from ace_brand_logo(), or escaped name above.
					$b['logo'] ? '<span class="mega__name">' . esc_html( $b['label'] ) . '</span>' : ''
				);
			}
			echo '</ul></section>';
		}

		// Third level: one group per subcategory that has its own subcategories.
		foreach ( $top['kids'] as $kid ) {
			if ( ! $kid['kids'] ) {
				continue;
			}
			printf( '<section class="mega__sec"><h3><a href="%s">%s</a></h3><ul class="mega__tiles">', esc_url( $kid['url'] ), esc_html( $kid['label'] ) );
			foreach ( $kid['kids'] as $grand ) {
				echo ace_mega_tile( $grand ); // phpcs:ignore WordPress.Security.EscapeOutput
			}
			echo '</ul></section>';
		}

		echo '</div></li>';
	}
	printf(
		'<li class="mega__all"><a class="mega__link" href="%s"><span class="mega__label">%s</span>%s</a></li>',
		esc_url( ace_shop_url() ),
		esc_html__( 'All products', '2acetech' ),
		ace_icon( 'arrow', 16 ) // phpcs:ignore WordPress.Security.EscapeOutput
	);
	echo '</ul>';
}
