<?php
/**
 * Shop filters: a side panel on every page that lists products (shop, categories, brands, tags,
 * search), like AliExpress. Categories, brands, price range, colour and every other product
 * option (size, plug, power ...), availability and sale.
 *
 * Why our own filtering: the cleaning plugin writes product options as per-product attributes
 * ("Kolor", "Rozmiar", "Wtyczka" ...), not as WooCommerce's shared attribute taxonomies, so
 * WooCommerce's layered-navigation filters cannot see them. Everything the filters need is read
 * once into a small index (cached, cleared when a product, category or brand changes); the
 * product list then narrows to the matching ids.
 *
 * Address parameters (work without JavaScript, can be shared and bookmarked):
 *   fbrand[]=slug   fattr[<option>][]=value   min_price / max_price   instock=1   onsale=1
 *
 * @package 2acetech
 */

defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------
 * Where the filters appear
 * ---------------------------------------------------------------------- */

/**
 * Is this a page that lists products?
 *
 * @return bool
 */
function ace_filters_active() {
	if ( ! function_exists( 'is_shop' ) || ! ace_has_store() || ace_is_demo() ) {
		return false;
	}
	return is_shop() || is_product_taxonomy() || ( is_search() && 'product' === get_query_var( 'post_type' ) );
}

/* -------------------------------------------------------------------------
 * The product index
 * ---------------------------------------------------------------------- */

/** Option names that are not for shoppers (internal data from the supplier). */
function ace_filter_skip_attr( $name ) {
	return (bool) preg_match( '/^\s*(barcode|kod|ean|sku|b2b|ships|wysy|brand|marka|wariant|variant)/iu', $name );
}

/**
 * One row per visible product: price, stock, sale, brands, categories, options.
 *
 * @return array[]
 */
function ace_filter_index() {
	$cached = get_transient( 'ace_filter_index_v1' );
	if ( is_array( $cached ) ) {
		return $cached;
	}
	$ids = get_posts(
		array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => 1500,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'tax_query'      => array( array( 'taxonomy' => 'product_visibility', 'field' => 'name', 'terms' => array( 'exclude-from-catalog' ), 'operator' => 'NOT IN' ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
	$rows = array();
	foreach ( $ids as $id ) {
		$p = wc_get_product( $id );
		if ( ! $p ) {
			continue;
		}
		if ( $p->is_type( 'variable' ) ) {
			$min = (float) $p->get_variation_price( 'min', false );
			$max = (float) $p->get_variation_price( 'max', false );
		} else {
			$min = (float) $p->get_price();
			$max = $min;
		}

		$cats = array();
		foreach ( wc_get_product_term_ids( $id, 'product_cat' ) as $tid ) {
			$cats[ $tid ] = $tid;
			foreach ( get_ancestors( $tid, 'product_cat', 'taxonomy' ) as $a ) {
				$cats[ $a ] = $a;
			}
		}

		$brands = array();
		$bt     = taxonomy_exists( 'product_brand' ) ? get_the_terms( $id, 'product_brand' ) : array();
		if ( $bt && ! is_wp_error( $bt ) ) {
			foreach ( $bt as $t ) {
				$brands[ $t->slug ] = $t->name;
			}
		}

		$attrs = array(); // option slug => array( 'label' => name, 'values' => array( key => label ) ).
		foreach ( $p->get_attributes() as $attr ) {
			if ( ! $attr->get_visible() && ! $attr->get_variation() ) {
				continue;
			}
			$label = $attr->is_taxonomy() ? wc_attribute_label( $attr->get_name() ) : $attr->get_name();
			if ( '' === trim( $label ) || ace_filter_skip_attr( $label ) ) {
				continue;
			}
			$vals = $attr->is_taxonomy() ? wc_get_product_terms( $id, $attr->get_name(), array( 'fields' => 'names' ) ) : $attr->get_options();
			$slug = sanitize_title( $label );
			foreach ( (array) $vals as $v ) {
				$v = trim( (string) $v );
				if ( '' === $v ) {
					continue;
				}
				$attrs[ $slug ]['label']                       = $label;
				$attrs[ $slug ]['values'][ mb_strtolower( $v ) ] = $v;
			}
		}

		$rows[ $id ] = array(
			'min'    => $min,
			'max'    => $max,
			'stock'  => $p->is_in_stock(),
			'sale'   => $p->is_on_sale(),
			'cats'   => array_values( $cats ),
			'brands' => $brands,
			'attrs'  => $attrs,
		);
	}
	set_transient( 'ace_filter_index_v1', $rows, 6 * HOUR_IN_SECONDS );
	return $rows;
}

function ace_filter_flush() {
	delete_transient( 'ace_filter_index_v1' );
}
add_action( 'save_post_product', 'ace_filter_flush' );
add_action( 'deleted_post', 'ace_filter_flush' );
add_action( 'woocommerce_update_product', 'ace_filter_flush' );
add_action( 'woocommerce_variation_set_stock_status', 'ace_filter_flush' );
add_action( 'woocommerce_product_set_stock_status', 'ace_filter_flush' );
add_action( 'created_term', 'ace_filter_flush' );
add_action( 'edited_term', 'ace_filter_flush' );
add_action( 'delete_term', 'ace_filter_flush' );
add_action( 'set_object_terms', 'ace_filter_flush' );

/* -------------------------------------------------------------------------
 * What the visitor chose
 * ---------------------------------------------------------------------- */

/**
 * Read the filters from the address.
 *
 * @return array brand: slugs; attr: option slug => lower-case values; min/max: float|null; stock, sale: bool.
 */
function ace_filter_request() {
	// phpcs:disable WordPress.Security.NonceVerification
	$f = array( 'brand' => array(), 'attr' => array(), 'min' => null, 'max' => null, 'stock' => false, 'sale' => false );

	if ( isset( $_GET['fbrand'] ) ) {
		$raw = wp_unslash( $_GET['fbrand'] );
		$raw = is_array( $raw ) ? $raw : explode( ',', (string) $raw );
		foreach ( $raw as $s ) {
			$s = sanitize_title( (string) $s );
			if ( '' !== $s ) {
				$f['brand'][ $s ] = $s;
			}
		}
	}
	if ( isset( $_GET['fattr'] ) && is_array( $_GET['fattr'] ) ) {
		foreach ( wp_unslash( $_GET['fattr'] ) as $slug => $vals ) {
			$slug = sanitize_title( (string) $slug );
			foreach ( (array) $vals as $v ) {
				$v = mb_strtolower( trim( sanitize_text_field( (string) $v ) ) );
				if ( $slug && '' !== $v ) {
					$f['attr'][ $slug ][ $v ] = $v;
				}
			}
		}
	}
	if ( isset( $_GET['min_price'] ) && '' !== $_GET['min_price'] && is_numeric( wp_unslash( $_GET['min_price'] ) ) ) {
		$f['min'] = max( 0, (float) wp_unslash( $_GET['min_price'] ) );
	}
	if ( isset( $_GET['max_price'] ) && '' !== $_GET['max_price'] && is_numeric( wp_unslash( $_GET['max_price'] ) ) ) {
		$f['max'] = max( 0, (float) wp_unslash( $_GET['max_price'] ) );
	}
	$f['stock'] = ! empty( $_GET['instock'] );
	$f['sale']  = ! empty( $_GET['onsale'] );
	// phpcs:enable
	return $f;
}

/** Is any filter on? */
function ace_filter_any( $f ) {
	return $f['brand'] || $f['attr'] || null !== $f['min'] || null !== $f['max'] || $f['stock'] || $f['sale'];
}

/**
 * Does a product row pass the filters? One group can be left out (to count its own options).
 *
 * @param array       $row  Index row.
 * @param array       $f    Filters.
 * @param string|null $skip 'brand', 'price', 'stock', 'sale' or 'attr:<slug>'.
 * @return bool
 */
function ace_filter_match( $row, $f, $skip = null ) {
	if ( 'brand' !== $skip && $f['brand'] && ! array_intersect( $f['brand'], array_keys( $row['brands'] ) ) ) {
		return false;
	}
	if ( 'price' !== $skip ) {
		if ( null !== $f['min'] && $row['max'] < $f['min'] ) {
			return false;
		}
		if ( null !== $f['max'] && $row['min'] > $f['max'] ) {
			return false;
		}
	}
	if ( 'stock' !== $skip && $f['stock'] && ! $row['stock'] ) {
		return false;
	}
	if ( 'sale' !== $skip && $f['sale'] && ! $row['sale'] ) {
		return false;
	}
	foreach ( $f['attr'] as $slug => $wanted ) {
		if ( 'attr:' . $slug === $skip ) {
			continue;
		}
		$have = isset( $row['attrs'][ $slug ]['values'] ) ? array_keys( $row['attrs'][ $slug ]['values'] ) : array();
		if ( ! array_intersect( $wanted, $have ) ) {
			return false;
		}
	}
	return true;
}

/**
 * Narrow the product list to the products that pass the filters.
 *
 * @param WP_Query $q Query.
 */
function ace_filter_pre_get_posts( $q ) {
	if ( is_admin() || ! $q->is_main_query() ) {
		return;
	}
	$is_list = $q->is_post_type_archive( 'product' ) || $q->is_tax( array( 'product_cat', 'product_tag', 'product_brand' ) ) || ( $q->is_search() && 'product' === $q->get( 'post_type' ) );
	if ( ! $is_list ) {
		return;
	}
	$f = ace_filter_request();
	if ( ! ace_filter_any( $f ) ) {
		return;
	}
	$ok = array();
	foreach ( ace_filter_index() as $id => $row ) {
		if ( ace_filter_match( $row, $f ) ) {
			$ok[] = $id;
		}
	}
	$have = array_filter( array_map( 'absint', (array) $q->get( 'post__in' ) ) );
	if ( $have ) {
		$ok = array_values( array_intersect( $ok, $have ) );
	}
	$q->set( 'post__in', $ok ? $ok : array( 0 ) );
}
add_action( 'pre_get_posts', 'ace_filter_pre_get_posts', 20 );

/* -------------------------------------------------------------------------
 * The products the current page lists (before the visitor's filters)
 * ---------------------------------------------------------------------- */

/**
 * Index rows of the page's own scope: the category (and its subcategories), the brand, the tag,
 * the search, or the whole shop.
 *
 * @return array[] id => row.
 */
function ace_filter_scope() {
	$rows = ace_filter_index();
	$obj  = get_queried_object();
	if ( is_search() ) {
		$ids = get_posts(
			array(
				'post_type'      => 'product',
				's'              => get_search_query( false ),
				'posts_per_page' => 1500,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);
		return array_intersect_key( $rows, array_flip( $ids ) );
	}
	if ( $obj instanceof WP_Term ) {
		if ( 'product_cat' === $obj->taxonomy ) {
			return array_filter(
				$rows,
				static function ( $r ) use ( $obj ) {
					return in_array( $obj->term_id, $r['cats'], true );
				}
			);
		}
		if ( 'product_brand' === $obj->taxonomy ) {
			return array_filter(
				$rows,
				static function ( $r ) use ( $obj ) {
					return isset( $r['brands'][ $obj->slug ] );
				}
			);
		}
		if ( 'product_tag' === $obj->taxonomy ) {
			$ids = get_objects_in_term( $obj->term_id, 'product_tag' );
			return array_intersect_key( $rows, array_flip( is_wp_error( $ids ) ? array() : $ids ) );
		}
	}
	return $rows;
}

/* -------------------------------------------------------------------------
 * Addresses
 * ---------------------------------------------------------------------- */

/** The list page without any filter (the form posts here). */
function ace_filter_base_url() {
	$obj = get_queried_object();
	if ( is_search() ) {
		return home_url( '/' );
	}
	if ( $obj instanceof WP_Term ) {
		$link = get_term_link( $obj );
		return is_wp_error( $link ) ? ace_shop_url() : $link;
	}
	return ace_shop_url();
}

/** Parameters that must survive a filter change (sorting, search words). */
function ace_filter_keep_params() {
	$keep = array();
	// phpcs:disable WordPress.Security.NonceVerification
	foreach ( array( 's', 'post_type', 'orderby' ) as $k ) {
		if ( isset( $_GET[ $k ] ) && is_string( $_GET[ $k ] ) && '' !== $_GET[ $k ] ) {
			$keep[ $k ] = sanitize_text_field( wp_unslash( $_GET[ $k ] ) );
		}
	}
	// phpcs:enable
	return $keep;
}

/** Filters as address parameters. */
function ace_filter_params( $f ) {
	$p = ace_filter_keep_params();
	if ( $f['brand'] ) {
		$p['fbrand'] = array_values( $f['brand'] );
	}
	foreach ( $f['attr'] as $slug => $vals ) {
		$p['fattr'][ $slug ] = array_values( $vals );
	}
	if ( null !== $f['min'] ) {
		$p['min_price'] = $f['min'];
	}
	if ( null !== $f['max'] ) {
		$p['max_price'] = $f['max'];
	}
	if ( $f['stock'] ) {
		$p['instock'] = 1;
	}
	if ( $f['sale'] ) {
		$p['onsale'] = 1;
	}
	return $p;
}

function ace_filter_url( $f ) {
	return add_query_arg( rawurlencode_deep( ace_filter_params( $f ) ), ace_filter_base_url() );
}

/* -------------------------------------------------------------------------
 * Colours
 * ---------------------------------------------------------------------- */

/**
 * CSS colour for a colour name in an option value ("Czarny", "Biały + czerwony" ...), or ''.
 *
 * @param string $label Value.
 * @return string
 */
function ace_filter_color_css( $label ) {
	$l   = mb_strtolower( $label );
	$map = array(
		'wielokolor' => 'conic-gradient(#f50505,#ffd400,#2ecc40,#0074d9,#b10dc9,#f50505)',
		'rgb'        => 'conic-gradient(#f50505,#2ecc40,#0074d9,#f50505)',
		'czarn'      => '#111111',
		'biał'       => '#ffffff',
		'czerwon'    => '#e02020',
		'bordow'     => '#7a1230',
		'niebiesk'   => '#1e6fe0',
		'granat'     => '#14284b',
		'zielon'     => '#2a9d4b',
		'oliw'       => '#6b7a2f',
		'khaki'      => '#8a8450',
		'żółt'       => '#f5c800',
		'pomarańcz'  => '#f58a07',
		'różow'      => '#f37fb0',
		'fiolet'     => '#7b3fbf',
		'purpur'     => '#8e24aa',
		'szar'       => '#8a8a93',
		'srebrn'     => '#c4c7cc',
		'złot'       => '#d4a82a',
		'brąz'       => '#7a4b2a',
		'beż'        => '#d9c3a0',
		'turkus'     => '#1fb5b0',
		'kremow'     => '#f3ead2',
		'przezroczy' => 'repeating-conic-gradient(#e7e7ea 0 25%,#fff 0 50%) 50%/10px 10px',
	);
	foreach ( $map as $needle => $css ) {
		if ( false !== mb_strpos( $l, $needle ) ) {
			return $css;
		}
	}
	return '';
}

/* -------------------------------------------------------------------------
 * The panel
 * ---------------------------------------------------------------------- */

/**
 * Facets for the panel: option lists with counts, price limits.
 *
 * @param array $scope Rows of the page scope.
 * @param array $f     Filters.
 * @return array
 */
function ace_filter_facets( $scope, $f ) {
	$out = array( 'brands' => array(), 'attrs' => array(), 'min' => null, 'max' => null, 'stock_cnt' => 0, 'sale_cnt' => 0, 'total' => 0 );

	foreach ( $scope as $row ) {
		// Price limits come from everything in scope, so the range stays stable while filtering.
		$out['min'] = null === $out['min'] ? $row['min'] : min( $out['min'], $row['min'] );
		$out['max'] = null === $out['max'] ? $row['max'] : max( $out['max'], $row['max'] );

		if ( ace_filter_match( $row, $f ) ) {
			$out['total']++;
		}
		if ( ace_filter_match( $row, $f, 'brand' ) ) {
			foreach ( $row['brands'] as $slug => $name ) {
				$out['brands'][ $slug ]['label'] = $name;
				$out['brands'][ $slug ]['count'] = ( isset( $out['brands'][ $slug ]['count'] ) ? $out['brands'][ $slug ]['count'] : 0 ) + 1;
			}
		}
		if ( ace_filter_match( $row, $f, 'stock' ) && $row['stock'] ) {
			$out['stock_cnt']++;
		}
		if ( ace_filter_match( $row, $f, 'sale' ) && $row['sale'] ) {
			$out['sale_cnt']++;
		}
		foreach ( $row['attrs'] as $slug => $a ) {
			$out['attrs'][ $slug ]['label'] = $a['label'];
			$out['attrs'][ $slug ]['n']     = ( isset( $out['attrs'][ $slug ]['n'] ) ? $out['attrs'][ $slug ]['n'] : 0 ) + 1; // Products that have this option.
			if ( ! ace_filter_match( $row, $f, 'attr:' . $slug ) ) {
				// Still register the option names so chosen ones do not vanish.
				foreach ( $a['values'] as $key => $label ) {
					$out['attrs'][ $slug ]['values'][ $key ]['label'] = $label;
					$out['attrs'][ $slug ]['values'][ $key ]['count'] = isset( $out['attrs'][ $slug ]['values'][ $key ]['count'] ) ? $out['attrs'][ $slug ]['values'][ $key ]['count'] : 0;
				}
				continue;
			}
			foreach ( $a['values'] as $key => $label ) {
				$out['attrs'][ $slug ]['values'][ $key ]['label'] = $label;
				$out['attrs'][ $slug ]['values'][ $key ]['count'] = ( isset( $out['attrs'][ $slug ]['values'][ $key ]['count'] ) ? $out['attrs'][ $slug ]['values'][ $key ]['count'] : 0 ) + 1;
			}
		}
	}

	// Brands with no products left under the other filters stay out, unless chosen.
	foreach ( $f['brand'] as $s ) {
		if ( ! isset( $out['brands'][ $s ] ) ) {
			$out['brands'][ $s ] = array( 'label' => $s, 'count' => 0 );
		}
	}
	uasort(
		$out['brands'],
		static function ( $a, $b ) {
			return $b['count'] <=> $a['count'] ?: strcasecmp( $a['label'], $b['label'] );
		}
	);

	// Option groups: only those a shopper can choose from (two or more values) or has already chosen.
	foreach ( $out['attrs'] as $slug => &$g ) {
		$g['values'] = isset( $g['values'] ) ? $g['values'] : array();
		uasort(
			$g['values'],
			static function ( $a, $b ) {
				return $b['count'] <=> $a['count'] ?: strnatcasecmp( $a['label'], $b['label'] );
			}
		);
		$g['is_color'] = (bool) preg_match( '/kolor|color/iu', $g['label'] );
	}
	unset( $g );
	$out['attrs'] = array_filter(
		$out['attrs'],
		static function ( $g, $slug ) use ( $f ) {
			return count( $g['values'] ) >= 2 || isset( $f['attr'][ $slug ] );
		},
		ARRAY_FILTER_USE_BOTH
	);
	// Plain colour first, then the options most products have; at most seven groups.
	$plain = static function ( $g ) {
		return (bool) preg_match( '/^\s*(kolor|color)\s*$/iu', $g['label'] );
	};
	uasort(
		$out['attrs'],
		static function ( $a, $b ) use ( $plain ) {
			return (int) $plain( $b ) <=> (int) $plain( $a ) ?: $b['n'] <=> $a['n'] ?: count( $b['values'] ) <=> count( $a['values'] );
		}
	);
	$out['attrs'] = array_slice( $out['attrs'], 0, 7, true );
	return $out;
}

/**
 * Category list with the branch you are in opened.
 *
 * @param array $nodes ace_category_tree() nodes.
 * @param int   $cur   Current category id (0 = none).
 * @param int   $depth Depth.
 */
function ace_filter_render_cats( $nodes, $cur, $depth = 0 ) {
	if ( ! $nodes ) {
		return;
	}
	echo '<ul class="sf-cats' . ( $depth ? ' sf-cats--sub' : '' ) . '">';
	foreach ( $nodes as $n ) {
		$id   = (int) $n['term']->term_id;
		$open = $cur && ( $cur === $id || term_is_ancestor_of( $id, $cur, 'product_cat' ) );
		printf(
			'<li class="%s"><a href="%s"%s><span>%s</span><small>%d</small></a>',
			esc_attr( trim( ( $cur === $id ? 'is-current ' : '' ) . ( $open ? 'is-open' : '' ) ) ),
			esc_url( $n['url'] ),
			$cur === $id ? ' aria-current="true"' : '',
			esc_html( $n['label'] ),
			(int) $n['count']
		);
		if ( $open && ! empty( $n['children'] ) ) {
			ace_filter_render_cats( $n['children'], $cur, $depth + 1 );
		}
		echo '</li>';
	}
	echo '</ul>';
}

/** Collapsible group. */
function ace_filter_group_open( $title, $open = true, $extra = '' ) {
	printf( '<details class="sf-group%s"%s><summary>%s</summary><div class="sf-body">', esc_attr( $extra ), $open ? ' open' : '', esc_html( $title ) );
}
function ace_filter_group_close() {
	echo '</div></details>';
}

/**
 * Print the side panel.
 */
function ace_filter_panel() {
	$f      = ace_filter_request();
	$scope  = ace_filter_scope();
	$facets = ace_filter_facets( $scope, $f );
	$obj    = get_queried_object();
	$cur    = ( $obj instanceof WP_Term && 'product_cat' === $obj->taxonomy ) ? (int) $obj->term_id : 0;
	$brand_page = $obj instanceof WP_Term && 'product_brand' === $obj->taxonomy;
	$tree   = ace_category_tree( 12 );

	echo '<aside class="shop-filters" id="shop-filters" aria-label="' . esc_attr__( 'Filters', '2acetech' ) . '" data-filters>';
	echo '<div class="sf-head"><strong>' . esc_html__( 'Filters', '2acetech' ) . '</strong>';
	echo '<button type="button" class="sf-close" data-filters-close aria-label="' . esc_attr__( 'Close filters', '2acetech' ) . '">' . ace_icon( 'close', 20 ) . '</button></div>'; // phpcs:ignore WordPress.Security.EscapeOutput

	echo '<form class="sf-form" method="get" action="' . esc_url( ace_filter_base_url() ) . '" data-filters-form>';
	foreach ( ace_filter_keep_params() as $k => $v ) {
		printf( '<input type="hidden" name="%s" value="%s">', esc_attr( $k ), esc_attr( $v ) );
	}

	// Categories (plain links).
	if ( $tree ) {
		ace_filter_group_open( __( 'Categories', '2acetech' ) );
		ace_filter_render_cats( $tree, $cur );
		ace_filter_group_close();
	}

	// Brands.
	if ( $facets['brands'] && ! $brand_page ) {
		ace_filter_group_open( __( 'Brand', '2acetech' ), true, ' sf-group--list' );
		echo '<ul class="sf-opts" data-more>';
		foreach ( $facets['brands'] as $slug => $b ) {
			$on = isset( $f['brand'][ $slug ] );
			if ( 0 === $b['count'] && ! $on ) {
				continue;
			}
			printf(
				'<li><label class="sf-opt"><input type="checkbox" name="fbrand[]" value="%s"%s><span class="sf-opt__box"></span><span class="sf-opt__label">%s</span><small>%d</small></label></li>',
				esc_attr( $slug ),
				checked( $on, true, false ),
				esc_html( $b['label'] ),
				(int) $b['count']
			);
		}
		echo '</ul>';
		ace_filter_group_close();
	}

	// Price.
	if ( null !== $facets['min'] ) {
		$lo = (int) floor( $facets['min'] );
		$hi = (int) ceil( $facets['max'] );
		ace_filter_group_open( __( 'Price', '2acetech' ), true, ' sf-group--price' );
		echo '<div class="sf-price">';
		printf(
			'<label><span class="screen-reader-text">%1$s</span><input type="number" inputmode="numeric" min="0" step="1" name="min_price" value="%2$s" placeholder="%3$s"></label><span class="sf-price__dash" aria-hidden="true">–</span><label><span class="screen-reader-text">%4$s</span><input type="number" inputmode="numeric" min="0" step="1" name="max_price" value="%5$s" placeholder="%6$s"></label><span class="sf-price__cur">zł</span>',
			esc_html__( 'Price from', '2acetech' ),
			null !== $f['min'] ? esc_attr( (string) $f['min'] ) : '',
			esc_attr( (string) $lo ),
			esc_html__( 'Price to', '2acetech' ),
			null !== $f['max'] ? esc_attr( (string) $f['max'] ) : '',
			esc_attr( (string) $hi )
		);
		echo '</div>';
		// Quick ranges across the real price span.
		if ( $hi > $lo ) {
			$steps = array_values( array_filter( array( 100, 250, 500, 1000, 2000 ), static function ( $s ) use ( $lo, $hi ) { return $s > $lo && $s < $hi; } ) );
			if ( $steps ) {
				echo '<div class="sf-quick">';
				$prev = null;
				foreach ( $steps as $to ) {
					$g        = $f;
					$g['min'] = null === $prev ? null : (float) $prev;
					$g['max'] = (float) $to;
					$label    = null === $prev
						? sprintf( /* translators: %s: amount. */ __( 'up to %s zł', '2acetech' ), number_format_i18n( $to ) )
						: sprintf( '%s – %s zł', number_format_i18n( $prev ), number_format_i18n( $to ) );
					printf( '<a class="sf-chip%s" href="%s">%s</a>', ( $f['min'] === $g['min'] && $f['max'] === $g['max'] ) ? ' is-on' : '', esc_url( ace_filter_url( $g ) ), esc_html( $label ) );
					$prev = $to;
				}
				$g        = $f;
				$g['min'] = (float) $prev;
				$g['max'] = null;
				printf( '<a class="sf-chip%s" href="%s">%s</a>', ( $f['min'] === $g['min'] && null === $f['max'] ) ? ' is-on' : '', esc_url( ace_filter_url( $g ) ), esc_html( sprintf( /* translators: %s: amount. */ __( 'over %s zł', '2acetech' ), number_format_i18n( $prev ) ) ) );
				echo '</div>';
			}
		}
		ace_filter_group_close();
	}

	// Colour and the other options.
	foreach ( $facets['attrs'] as $slug => $g ) {
		ace_filter_group_open( $g['label'], true, $g['is_color'] ? ' sf-group--color' : ' sf-group--list' );
		echo '<ul class="sf-opts' . ( $g['is_color'] ? ' sf-opts--color' : '' ) . '" data-more>';
		foreach ( $g['values'] as $key => $v ) {
			$on = isset( $f['attr'][ $slug ][ $key ] );
			if ( 0 === $v['count'] && ! $on ) {
				continue;
			}
			$swatch = '';
			if ( $g['is_color'] ) {
				$css    = ace_filter_color_css( $v['label'] );
				$swatch = $css ? '<span class="sf-dot" style="background:' . esc_attr( $css ) . '"></span>' : '';
			}
			printf(
				'<li><label class="sf-opt"><input type="checkbox" name="fattr[%1$s][]" value="%2$s"%3$s><span class="sf-opt__box"></span>%4$s<span class="sf-opt__label">%5$s</span><small>%6$d</small></label></li>',
				esc_attr( $slug ),
				esc_attr( $v['label'] ),
				checked( $on, true, false ),
				$swatch, // Escaped above.
				esc_html( $v['label'] ),
				(int) $v['count']
			);
		}
		echo '</ul>';
		ace_filter_group_close();
	}

	// Availability and sale.
	ace_filter_group_open( __( 'Availability', '2acetech' ), true, ' sf-group--list' );
	echo '<ul class="sf-opts">';
	printf(
		'<li><label class="sf-opt"><input type="checkbox" name="instock" value="1"%s><span class="sf-opt__box"></span><span class="sf-opt__label">%s</span><small>%d</small></label></li>',
		checked( $f['stock'], true, false ),
		esc_html__( 'In stock only', '2acetech' ),
		(int) $facets['stock_cnt']
	);
	printf(
		'<li><label class="sf-opt"><input type="checkbox" name="onsale" value="1"%s><span class="sf-opt__box"></span><span class="sf-opt__label">%s</span><small>%d</small></label></li>',
		checked( $f['sale'], true, false ),
		esc_html__( 'On sale', '2acetech' ),
		(int) $facets['sale_cnt']
	);
	echo '</ul>';
	ace_filter_group_close();

	echo '<div class="sf-actions">';
	printf( '<button type="submit" class="btn btn--primary sf-apply">%s</button>', esc_html__( 'Show results', '2acetech' ) );
	if ( ace_filter_any( $f ) ) {
		printf( '<a class="sf-reset" href="%s">%s</a>', esc_url( add_query_arg( rawurlencode_deep( ace_filter_keep_params() ), ace_filter_base_url() ) ), esc_html__( 'Clear all filters', '2acetech' ) );
	}
	echo '</div>';
	echo '</form></aside>';
	echo '<div class="sf-backdrop" data-filters-close></div>';
}

/**
 * Chips for the filters that are on, each removes itself.
 */
function ace_filter_chips() {
	$f = ace_filter_request();
	if ( ! ace_filter_any( $f ) ) {
		return;
	}
	$items = array();
	$obj   = array();
	foreach ( ace_filter_scope() as $row ) {
		foreach ( $row['brands'] as $slug => $name ) {
			$obj['brand'][ $slug ] = $name;
		}
		foreach ( $row['attrs'] as $slug => $a ) {
			$obj['attr'][ $slug ]['label'] = $a['label'];
			foreach ( $a['values'] as $key => $label ) {
				$obj['attr'][ $slug ]['values'][ $key ] = $label;
			}
		}
	}
	foreach ( $f['brand'] as $slug ) {
		$g = $f;
		unset( $g['brand'][ $slug ] );
		$items[] = array( isset( $obj['brand'][ $slug ] ) ? $obj['brand'][ $slug ] : $slug, ace_filter_url( $g ) );
	}
	foreach ( $f['attr'] as $slug => $vals ) {
		foreach ( $vals as $key ) {
			$g = $f;
			unset( $g['attr'][ $slug ][ $key ] );
			if ( empty( $g['attr'][ $slug ] ) ) {
				unset( $g['attr'][ $slug ] );
			}
			$label   = isset( $obj['attr'][ $slug ]['values'][ $key ] ) ? $obj['attr'][ $slug ]['values'][ $key ] : $key;
			$group   = isset( $obj['attr'][ $slug ]['label'] ) ? $obj['attr'][ $slug ]['label'] : $slug;
			$items[] = array( $group . ': ' . $label, ace_filter_url( $g ) );
		}
	}
	if ( null !== $f['min'] || null !== $f['max'] ) {
		$g       = $f;
		$g['min'] = $g['max'] = null;
		if ( null !== $f['min'] && null !== $f['max'] ) {
			$txt = sprintf( '%s – %s zł', number_format_i18n( $f['min'] ), number_format_i18n( $f['max'] ) );
		} elseif ( null !== $f['min'] ) {
			$txt = sprintf( /* translators: %s: amount. */ __( 'from %s zł', '2acetech' ), number_format_i18n( $f['min'] ) );
		} else {
			$txt = sprintf( /* translators: %s: amount. */ __( 'up to %s zł', '2acetech' ), number_format_i18n( $f['max'] ) );
		}
		$items[] = array( $txt, ace_filter_url( $g ) );
	}
	if ( $f['stock'] ) {
		$g = $f;
		$g['stock'] = false;
		$items[] = array( __( 'In stock only', '2acetech' ), ace_filter_url( $g ) );
	}
	if ( $f['sale'] ) {
		$g = $f;
		$g['sale'] = false;
		$items[] = array( __( 'On sale', '2acetech' ), ace_filter_url( $g ) );
	}

	echo '<div class="sf-active" aria-label="' . esc_attr__( 'Active filters', '2acetech' ) . '">';
	foreach ( $items as $it ) {
		printf( '<a class="sf-tag" href="%s" title="%s">%s%s</a>', esc_url( $it[1] ), esc_attr__( 'Remove filter', '2acetech' ), esc_html( $it[0] ), ace_icon( 'close', 14 ) ); // phpcs:ignore WordPress.Security.EscapeOutput
	}
	printf( '<a class="sf-clear" href="%s">%s</a>', esc_url( add_query_arg( rawurlencode_deep( ace_filter_keep_params() ), ace_filter_base_url() ) ), esc_html__( 'Clear all filters', '2acetech' ) );
	echo '</div>';
}

/* -------------------------------------------------------------------------
 * Page layout: panel on the left, products on the right
 * ---------------------------------------------------------------------- */

/** Open the two-column layout (panel + main column). */
function ace_filter_layout_open() {
	static $done = false;
	if ( $done || ! ace_filters_active() ) {
		return;
	}
	$done = true;
	echo '<div class="shop-layout" data-shop-layout>';
	ace_filter_panel();
	echo '<div class="shop-main">';
}
add_action( 'woocommerce_before_shop_loop', 'ace_filter_layout_open', 1 );
add_action( 'woocommerce_no_products_found', 'ace_filter_layout_open', 1 );

function ace_filter_layout_close() {
	if ( ! ace_filters_active() ) {
		return;
	}
	echo '</div></div>'; // .shop-main, .shop-layout
}
add_action( 'woocommerce_after_shop_loop', 'ace_filter_layout_close', 100 );
add_action( 'woocommerce_no_products_found', 'ace_filter_layout_close', 100 );

/** Toolbar: "Filters" button (phones), active filter chips, result count, sorting. */
function ace_filter_toolbar_open() {
	if ( ! ace_filters_active() ) {
		return;
	}
	$f = ace_filter_request();
	$n = count( $f['brand'] ) + count( $f['attr'] ) + ( ( null !== $f['min'] || null !== $f['max'] ) ? 1 : 0 ) + (int) $f['stock'] + (int) $f['sale'];
	echo '<div class="shop-toolbar">';
	printf(
		'<button type="button" class="sf-open" data-filters-open aria-controls="shop-filters">%s<span>%s</span>%s</button>',
		ace_icon( 'menu', 18 ), // phpcs:ignore WordPress.Security.EscapeOutput
		esc_html__( 'Filters', '2acetech' ),
		$n ? '<b>' . (int) $n . '</b>' : ''
	);
}
add_action( 'woocommerce_before_shop_loop', 'ace_filter_toolbar_open', 19 );

function ace_filter_toolbar_close() {
	if ( ! ace_filters_active() ) {
		return;
	}
	echo '</div>';
	ace_filter_chips();
}
add_action( 'woocommerce_before_shop_loop', 'ace_filter_toolbar_close', 31 );

/** Three products per row beside the panel. */
function ace_filter_columns( $cols ) {
	return ace_filters_active() ? 3 : $cols;
}
add_filter( 'loop_shop_columns', 'ace_filter_columns', 20 );

/** Panel script. */
function ace_filter_assets() {
	if ( ace_filters_active() ) {
		wp_enqueue_script( 'ace-filters', ACE_URI . '/assets/js/shop-filters.js', array(), ace_ver( 'assets/js/shop-filters.js' ), array( 'strategy' => 'defer', 'in_footer' => true ) );
		wp_localize_script(
			'ace-filters',
			'aceFilters',
			array(
				'more' => __( 'Show more', '2acetech' ),
				'less' => __( 'Show less', '2acetech' ),
			)
		);
	}
}
add_action( 'wp_enqueue_scripts', 'ace_filter_assets' );
