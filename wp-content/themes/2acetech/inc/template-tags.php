<?php
/**
 * Template helpers: icons, logo, menus, product and category data.
 *
 * @package 2acetech
 */

defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------
 * Store state
 * ---------------------------------------------------------------------- */

/**
 * Is WooCommerce active?
 *
 * @return bool
 */
function ace_has_store() {
	return class_exists( 'WooCommerce' );
}

/**
 * True while the storefront should show demo content: WooCommerce missing, or
 * no published products yet.
 *
 * @return bool
 */
function ace_is_demo() {
	static $demo = null;
	if ( null === $demo ) {
		$demo = ! ace_has_store() || 0 === (int) wp_count_posts( 'product' )->publish;
	}
	return $demo;
}

/**
 * Shop page URL, or the home page when WooCommerce is not active.
 *
 * @return string
 */
function ace_shop_url() {
	if ( ace_has_store() && function_exists( 'wc_get_page_permalink' ) ) {
		return wc_get_page_permalink( 'shop' );
	}
	return home_url( '/' );
}

/**
 * Product category URL by slug, with a fallback when it does not exist.
 *
 * @param string $slug     Category slug.
 * @param string $fallback URL to use when the category is missing.
 * @return string
 */
function ace_category_url( $slug, $fallback = '' ) {
	if ( ace_has_store() ) {
		$link = get_term_link( $slug, 'product_cat' );
		if ( ! is_wp_error( $link ) ) {
			return $link;
		}
	}
	return $fallback ? $fallback : ace_shop_url();
}

/**
 * My account URL (WooCommerce) or the login URL.
 *
 * @return string
 */
function ace_account_url() {
	if ( ace_has_store() ) {
		return wc_get_page_permalink( 'myaccount' );
	}
	return wp_login_url();
}

/* -------------------------------------------------------------------------
 * Icons
 * ---------------------------------------------------------------------- */

/**
 * Inline SVG icon.
 *
 * @param string $name Icon name.
 * @param int    $size Pixel size.
 * @return string
 */
function ace_icon( $name, $size = 20 ) {
	$paths = array(
		'search'    => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
		'user'      => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-7 8-7s8 3 8 7"/>',
		'cart'      => '<path d="M3 4h2.4l2.2 11.2a1.5 1.5 0 0 0 1.5 1.2h7.8a1.5 1.5 0 0 0 1.5-1.1L20 8H6.2"/><circle cx="9.5" cy="20" r="1.4"/><circle cx="17" cy="20" r="1.4"/>',
		'heart'     => '<path d="M12 20s-7-4.4-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 10c0 5.6-7 10-7 10z"/>',
		'menu'      => '<path d="M4 7h16M4 12h16M4 17h16"/>',
		'close'     => '<path d="m6 6 12 12M18 6 6 18"/>',
		'chevron'   => '<path d="m6 9 6 6 6-6"/>',
		'left'      => '<path d="m15 6-6 6 6 6"/>',
		'right'     => '<path d="m9 6 6 6-6 6"/>',
		'arrow'     => '<path d="M5 12h14M13 6l6 6-6 6"/>',
		'up'        => '<path d="m6 15 6-6 6 6"/>',
		'truck'     => '<path d="M3 6h11v10H3zM14 9h4l3 3v4h-7z"/><circle cx="7.5" cy="17.5" r="1.8"/><circle cx="17.5" cy="17.5" r="1.8"/>',
		'shield'    => '<path d="M12 3 5 6v6c0 4.4 3 7.4 7 9 4-1.6 7-4.6 7-9V6z"/><path d="m9 12 2 2 4-4"/>',
		'return'    => '<path d="M4 12a8 8 0 1 1 3 6.2"/><path d="M4 6v6h6"/>',
		'badge'     => '<circle cx="12" cy="9" r="5.5"/><path d="m8.5 13.5-1.5 7 5-2.7 5 2.7-1.5-7"/>',
		'phone'     => '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z"/>',
		'mail'      => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
		'pin'       => '<path d="M12 21s7-5.6 7-11a7 7 0 0 0-14 0c0 5.4 7 11 7 11z"/><circle cx="12" cy="10" r="2.5"/>',
		'send'      => '<path d="M21 3 3 10.5l7 2.5 2.5 7z"/><path d="m10 13 11-10"/>',
		'facebook'  => '<path d="M14 8h3V4h-3a4 4 0 0 0-4 4v2H7v4h3v7h4v-7h3l1-4h-4V8.5c0-.3.2-.5.5-.5z"/>',
		'instagram' => '<rect x="4" y="4" width="16" height="16" rx="4.5"/><circle cx="12" cy="12" r="3.6"/><circle cx="16.8" cy="7.2" r=".6"/>',
		'x'         => '<path d="M4 4l16 16M20 4 4 20"/>',
		'whatsapp'  => '<path d="M20 11.5A8 8 0 0 1 8.1 18.5L4 20l1.5-4A8 8 0 1 1 20 11.5z"/><path d="M9.2 8.6c.2 2.5 2.7 5 5.3 5.4l1-1.2-1.8-.9-.7.6a4.2 4.2 0 0 1-2-2l.6-.7-.9-1.8z"/>',
		'linkedin'  => '<rect x="4" y="4" width="16" height="16" rx="3"/><path d="M8 11v5M8 8v.01M12 16v-5M12 13c0-1.5 1-2 2-2s2 .7 2 2v3"/>',
		'link'      => '<path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1"/><path d="M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1"/>',
		'youtube'   => '<rect x="3" y="6" width="18" height="12" rx="4"/><path d="m10.5 9.5 4 2.5-4 2.5z"/>',
	);
	if ( ! isset( $paths[ $name ] ) ) {
		return '';
	}
	return sprintf(
		'<svg class="icon icon--%1$s" width="%2$d" height="%2$d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">%3$s</svg>',
		esc_attr( $name ),
		(int) $size,
		$paths[ $name ] // phpcs:ignore WordPress.Security.EscapeOutput -- Static markup above.
	);
}

/**
 * Five-star rating display.
 *
 * @param float $rating 0-5.
 * @return string
 */
function ace_stars( $rating ) {
	$rating = max( 0, min( 5, (float) $rating ) );
	if ( $rating <= 0 ) {
		return '';
	}
	return sprintf(
		'<span class="stars" style="--rating:%1$s" role="img" aria-label="%2$s"></span>',
		esc_attr( $rating ),
		/* translators: %s: rating out of 5. */
		esc_attr( sprintf( __( 'Rated %s out of 5', '2acetech' ), $rating ) )
	);
}

/* -------------------------------------------------------------------------
 * Logo and menus
 * ---------------------------------------------------------------------- */

/**
 * Text wordmark used until a logo is uploaded.
 *
 * @param string $class Extra class on the wrapper.
 * @return string
 */
function ace_wordmark( $class = '' ) {
	return sprintf(
		'<a class="wordmark %1$s" href="%2$s" aria-label="%3$s"><span class="wordmark__a">2ACE</span><span class="wordmark__b">TECH</span></a>',
		esc_attr( $class ),
		esc_url( home_url( '/' ) ),
		esc_attr( get_bloginfo( 'name' ) )
	);
}

/**
 * Header logo: the Customizer logo when set, otherwise the wordmark.
 */
function ace_logo() {
	if ( has_custom_logo() ) {
		the_custom_logo();
		return;
	}
	echo ace_bundled_logo( 'logo.png' ); // phpcs:ignore WordPress.Security.EscapeOutput
}

/**
 * The logo that ships with the theme (assets/img), linked to the home page.
 * Falls back to the text wordmark if the file is missing.
 *
 * @param string $file    File in assets/img.
 * @param string $classes Link classes.
 * @return string
 */
function ace_bundled_logo( $file, $classes = 'custom-logo-link' ) {
	$path = ACE_DIR . '/assets/img/' . $file;
	if ( ! file_exists( $path ) ) {
		return ace_wordmark( 'logo-light.png' === $file ? 'wordmark--light' : '' );
	}
	$size = getimagesize( $path );
	return sprintf(
		'<a class="%1$s" href="%2$s" rel="home"><img class="custom-logo" src="%3$s" width="%4$d" height="%5$d" alt="%6$s" decoding="async"></a>',
		esc_attr( $classes ),
		esc_url( home_url( '/' ) ),
		esc_url( ACE_URI . '/assets/img/' . $file ),
		$size ? (int) $size[0] : 760,
		$size ? (int) $size[1] : 101,
		esc_attr( get_bloginfo( 'name' ) )
	);
}

/**
 * Footer logo: a dedicated light logo for the dark footer, else the wordmark.
 */
function ace_footer_logo() {
	$id = (int) get_theme_mod( 'ace_footer_logo' );
	if ( $id ) {
		printf(
			'<a class="footer-logo" href="%1$s">%2$s</a>',
			esc_url( home_url( '/' ) ),
			wp_get_attachment_image( $id, 'full', false, array( 'alt' => get_bloginfo( 'name' ) ) ) // phpcs:ignore WordPress.Security.EscapeOutput
		);
		return;
	}
	echo ace_bundled_logo( 'logo-light.png', 'footer-logo' ); // phpcs:ignore WordPress.Security.EscapeOutput
}

/**
 * Primary navigation fallback until a menu is assigned.
 */
function ace_primary_fallback() {
	echo '<ul class="menu">';
	printf( '<li><a href="%s">%s</a></li>', esc_url( home_url( '/' ) ), esc_html__( 'Home', '2acetech' ) );
	if ( ace_has_store() ) {
		printf( '<li><a href="%s">%s</a></li>', esc_url( ace_shop_url() ), esc_html__( 'Shop', '2acetech' ) );
	}
	$posts_page = (int) get_option( 'page_for_posts' );
	if ( $posts_page ) {
		printf( '<li><a href="%s">%s</a></li>', esc_url( get_permalink( $posts_page ) ), esc_html__( 'Blog', '2acetech' ) );
	}
	wp_list_pages(
		array(
			'title_li' => '',
			'depth'    => 1,
			'exclude'  => implode( ',', array_filter( array( $posts_page, (int) get_option( 'page_on_front' ), ace_has_store() ? (int) wc_get_page_id( 'shop' ) : 0, ace_has_store() ? (int) wc_get_page_id( 'cart' ) : 0, ace_has_store() ? (int) wc_get_page_id( 'checkout' ) : 0, ace_has_store() ? (int) wc_get_page_id( 'myaccount' ) : 0 ) ) ),
		)
	);
	echo '</ul>';
}

/**
 * Footer links fallback.
 *
 * @param string $which 'shop' or 'help'.
 */
function ace_footer_fallback( $which ) {
	$links = array();
	if ( 'shop' === $which ) {
		foreach ( ace_category_list( 6 ) as $cat ) {
			$links[ $cat['label'] ] = $cat['url'];
		}
	} else {
		$links[ __( 'My account', '2acetech' ) ] = ace_account_url();
		if ( ace_has_store() ) {
			$links[ __( 'Cart', '2acetech' ) ] = wc_get_cart_url();
		}
		foreach ( get_pages( array( 'number' => 4, 'sort_column' => 'menu_order,post_title', 'exclude' => implode( ',', array_filter( array( (int) get_option( 'page_on_front' ), ace_has_store() ? (int) wc_get_page_id( 'shop' ) : 0, ace_has_store() ? (int) wc_get_page_id( 'cart' ) : 0, ace_has_store() ? (int) wc_get_page_id( 'checkout' ) : 0, ace_has_store() ? (int) wc_get_page_id( 'myaccount' ) : 0 ) ) ) ) ) as $page ) {
			$links[ get_the_title( $page ) ] = get_permalink( $page );
		}
	}
	echo '<ul class="menu">';
	foreach ( $links as $label => $url ) {
		printf( '<li><a href="%s">%s</a></li>', esc_url( $url ), esc_html( $label ) );
	}
	echo '</ul>';
}

/* -------------------------------------------------------------------------
 * Categories and brands
 * ---------------------------------------------------------------------- */

/**
 * Pick an illustration for a category name, or '' when nothing fits.
 *
 * @param string $name Category name or slug.
 * @return string
 */
function ace_guess_art( $name ) {
	$map = array(
		'watch'      => 'watch',
		'wear'       => 'watch',
		'laptop'     => 'laptop',
		'computer'   => 'laptop',
		'notebook'   => 'laptop',
		'phone'      => 'phone',
		'tablet'     => 'tablet',
		'audio'      => 'headphones',
		'headphone'  => 'headphones',
		'speaker'    => 'speaker',
		'camera'     => 'camera',
		'gaming'     => 'console',
		'console'    => 'console',
	);
	$name = strtolower( $name );
	foreach ( $map as $needle => $art ) {
		if ( false !== strpos( $name, $needle ) ) {
			return $art;
		}
	}
	return '';
}

/**
 * Normalised top-level categories for tiles, menus and footer links.
 *
 * @param int $limit Max categories.
 * @return array[] Each: slug, label, url, count, thumb (HTML or ''), art.
 */
function ace_category_list( $limit = 8 ) {
	$out = array();

	if ( ace_has_store() && ! ace_is_demo() ) {
		$terms = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'parent'     => 0,
				'hide_empty' => true,
				'exclude'    => array( (int) get_option( 'default_product_cat' ) ),
				'number'     => $limit,
				'orderby'    => 'menu_order',
			)
		);
		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$thumb_id = (int) get_term_meta( $term->term_id, 'thumbnail_id', true );
				$out[]    = array(
					'slug'  => $term->slug,
					'label' => $term->name,
					'url'   => get_term_link( $term ),
					'count' => (int) $term->count,
					'thumb' => $thumb_id ? wp_get_attachment_image( $thumb_id, 'ace-tile', false, array( 'loading' => 'lazy' ) ) : '',
					'art'   => ace_guess_art( $term->slug . ' ' . $term->name ),
				);
			}
		}
	}

	if ( ! $out ) {
		$shop = ace_shop_url();
		foreach ( ace_demo_categories() as $slug => $cat ) {
			$out[] = array(
				'slug'  => $slug,
				'label' => $cat['label'],
				'url'   => $shop,
				'count' => 0,
				'thumb' => '',
				'art'   => $cat['art'],
			);
		}
	}

	return array_slice( $out, 0, $limit );
}

/**
 * Brand list for the brand strip. Uses WooCommerce Brands when available.
 *
 * @return array[] Each: label, url, logo (HTML or '').
 */
function ace_brand_list() {
	$out = array();

	if ( ace_has_store() && ! ace_is_demo() && taxonomy_exists( 'product_brand' ) ) {
		$terms = get_terms( array( 'taxonomy' => 'product_brand', 'hide_empty' => true, 'number' => 12 ) );
		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$logo_id = (int) get_term_meta( $term->term_id, 'thumbnail_id', true );
				$out[]   = array(
					'label' => $term->name,
					'url'   => get_term_link( $term ),
					'logo'  => $logo_id ? wp_get_attachment_image( $logo_id, 'medium', false, array( 'loading' => 'lazy' ) ) : '',
				);
			}
		}
	}

	if ( ! $out ) {
		foreach ( ace_demo_brands() as $name ) {
			$out[] = array( 'label' => $name, 'url' => '', 'logo' => '' );
		}
	}

	return $out;
}

/* -------------------------------------------------------------------------
 * Products
 * ---------------------------------------------------------------------- */

/**
 * Fetch products for a section.
 *
 * Returns WC_Product objects when the store has products, otherwise demo
 * arrays (rendered the same way by ace_product_card()).
 *
 * @param string $type  featured|latest|sale|best.
 * @param int    $limit Count.
 * @param string $cat   Optional product category slug (store) or demo slug.
 * @return array
 */
function ace_get_products( $type = 'latest', $limit = 10, $cat = '' ) {
	if ( ace_is_demo() ) {
		return ace_demo_slice( $type, $limit, $cat );
	}

	$products = ace_query_products( $type, $limit, $cat );
	if ( ! $products && 'latest' !== $type ) {
		$products = ace_query_products( 'latest', $limit, $cat ); // Never leave a rail empty.
	}
	return $products;
}

/**
 * Query real WooCommerce products.
 *
 * @param string $type  Section type.
 * @param int    $limit Count.
 * @param string $cat   Category slug.
 * @return WC_Product[]
 */
function ace_query_products( $type, $limit, $cat = '' ) {
	$args = array(
		'status'     => 'publish',
		'limit'      => $limit,
		'visibility' => 'catalog',
		'orderby'    => 'date',
		'order'      => 'DESC',
	);
	if ( $cat ) {
		$args['category'] = array( $cat );
	}

	switch ( $type ) {
		case 'featured':
			$args['featured'] = true;
			break;
		case 'sale':
			$ids = wc_get_product_ids_on_sale();
			if ( ! $ids ) {
				return array();
			}
			$args['include'] = array_slice( $ids, 0, 60 );
			break;
		case 'best':
			$query = new WP_Query(
				array(
					'post_type'           => 'product',
					'post_status'         => 'publish',
					'posts_per_page'      => $limit,
					'meta_key'            => 'total_sales', // phpcs:ignore WordPress.DB.SlowDBQuery
					'orderby'             => 'meta_value_num',
					'order'               => 'DESC',
					'no_found_rows'       => true,
					'fields'              => 'ids',
					'product_cat'         => $cat,
					'ignore_sticky_posts' => true,
				)
			);
			return array_filter( array_map( 'wc_get_product', $query->posts ) );
	}

	return array_filter( wc_get_products( $args ) );
}

/**
 * Demo products for a section type.
 *
 * @param string $type  Section type.
 * @param int    $limit Count.
 * @param string $cat   Demo category slug.
 * @return array[]
 */
function ace_demo_slice( $type, $limit, $cat = '' ) {
	$items = ace_demo_products();
	if ( $cat ) {
		$items = array_values( array_filter( $items, static function ( $i ) use ( $cat ) { return $i['cat'] === $cat; } ) );
	}
	if ( 'sale' === $type ) {
		$items = array_values( array_filter( $items, static function ( $i ) { return $i['was'] > 0; } ) );
	} elseif ( 'latest' === $type ) {
		$items = array_reverse( $items );
	} elseif ( 'best' === $type ) {
		usort( $items, static function ( $a, $b ) { return $b['rating'] <=> $a['rating']; } );
	}
	return array_slice( $items, 0, $limit );
}

/**
 * Render a product card from a WC_Product or a demo array.
 *
 * @param WC_Product|array $item Product.
 */
function ace_product_card( $item ) {
	$is_demo = is_array( $item );

	if ( $is_demo ) {
		$title   = $item['title'];
		$url     = ace_shop_url();
		$badges  = $item['badges'];
		$rating  = $item['rating'];
		$price   = ace_demo_price( $item['price'], $item['was'] );
		$media   = ace_art( $item['art'] );
		$sku_cls = 'is-demo';
	} else {
		$title   = $item->get_name();
		$url     = $item->get_permalink();
		$badges  = array();
		$created = $item->get_date_created();
		if ( $created && $created->getTimestamp() > strtotime( '-30 days' ) ) {
			$badges[] = 'new';
		}
		if ( $item->is_on_sale() ) {
			$badges[] = 'sale';
		}
		if ( ! $item->is_in_stock() ) {
			$badges = array( 'out' );
		}
		$rating  = (float) $item->get_average_rating();
		$price   = $item->get_price_html();
		$media   = $item->get_image( 'woocommerce_thumbnail', array( 'loading' => 'lazy' ) );
		$sku_cls = 'product-' . $item->get_id();
	}

	$labels = array(
		'new'  => __( 'New', '2acetech' ),
		'sale' => __( 'Sale', '2acetech' ),
		'out'  => __( 'Sold out', '2acetech' ),
	);
	?>
	<article class="pcard <?php echo esc_attr( $sku_cls ); ?>">
		<?php if ( $badges ) : ?>
			<div class="pcard__badges">
				<?php foreach ( $badges as $badge ) : ?>
					<span class="badge badge--<?php echo esc_attr( $badge ); ?>"><?php echo esc_html( $labels[ $badge ] ); ?></span>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<a class="pcard__media" href="<?php echo esc_url( $url ); ?>" tabindex="-1" aria-hidden="true">
			<?php echo $media; // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</a>

		<div class="pcard__body">
			<h3 class="pcard__title"><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $title ); ?></a></h3>
			<?php echo ace_stars( $rating ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<div class="pcard__foot">
				<span class="pcard__price"><?php echo wp_kses_post( $price ); ?></span>
				<span class="pcard__cta">
					<?php
					if ( $is_demo ) {
						printf(
							'<a class="button" href="%1$s" aria-label="%2$s"></a>',
							esc_url( $url ),
							/* translators: %s: product name. */
							esc_attr( sprintf( __( 'View %s', '2acetech' ), $title ) )
						);
					} else {
						$GLOBALS['product'] = $item; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
						woocommerce_template_loop_add_to_cart();
					}
					?>
				</span>
			</div>
		</div>
	</article>
	<?php
}

/**
 * Format a demo price with the store currency.
 *
 * @param float $price Current price.
 * @param float $was   Regular price, or 0.
 * @return string
 */
function ace_demo_price( $price, $was = 0 ) {
	$fmt = static function ( $n ) {
		return function_exists( 'wc_price' ) ? wc_price( $n, array( 'decimals' => 0 ) ) : esc_html( number_format_i18n( $n, 0 ) ) . '&nbsp;zł';
	};
	if ( $was > 0 ) {
		return '<del>' . $fmt( $was ) . '</del> <ins>' . $fmt( $price ) . '</ins>';
	}
	return $fmt( $price );
}

/**
 * Admin-only notice explaining that demo content is showing.
 */
function ace_demo_notice() {
	if ( ! ace_is_demo() || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$msg = ace_has_store()
		? __( 'Showing demo products. Add your first WooCommerce product and this content is replaced automatically.', '2acetech' )
		: __( 'Showing demo products. Install and activate WooCommerce to sell real products.', '2acetech' );
	printf( '<div class="demo-notice" role="note"><strong>%s</strong> %s</div>', esc_html__( 'Admin preview:', '2acetech' ), esc_html( $msg ) );
}

/**
 * Seconds-accurate deal end timestamp (ISO 8601): the product's own sale end
 * date when it has one, else the Customizer date, else the end of the week.
 *
 * @param WC_Product|null $product Optional deal product.
 * @return string
 */
function ace_deal_end( $product = null ) {
	if ( $product instanceof WC_Product ) {
		$sale_end = $product->get_date_on_sale_to();
		if ( $sale_end && $sale_end->getTimestamp() > time() ) {
			return gmdate( 'c', $sale_end->getTimestamp() );
		}
	}

	$custom = trim( (string) get_theme_mod( 'ace_deal_end', '' ) );
	$ts     = $custom ? strtotime( $custom, current_time( 'timestamp' ) ) : false; // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp
	if ( ! $ts || $ts < time() ) {
		$ts = strtotime( 'next sunday 23:59:59', current_time( 'timestamp' ) ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp
	}
	return gmdate( 'c', $ts - (int) ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) );
}

/**
 * Wrap each word of a heading so the hero can reveal them one by one.
 *
 * @param string $text Heading text.
 * @return string
 */
function ace_split_words( $text ) {
	$out = '';
	foreach ( preg_split( '/\s+/', trim( $text ) ) as $i => $word ) {
		$out .= sprintf( '<span class="w"><span style="--i:%d">%s</span></span> ', (int) $i, esc_html( $word ) );
	}
	return trim( $out );
}
