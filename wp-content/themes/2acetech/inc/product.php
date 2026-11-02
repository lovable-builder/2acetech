<?php
/**
 * Single product page.
 *
 * Re-orders the WooCommerce summary and adds: brand, rating line, sale
 * countdown + stock bar (real data only), trust perks, product info, share
 * links and a sticky mobile buy bar. Everything degrades silently when the
 * data it needs (sale end date, managed stock, brands) is not set.
 *
 * @package 2acetech
 */

defined( 'ABSPATH' ) || exit;

/**
 * Breadcrumb look.
 *
 * @param array $defaults Defaults.
 * @return array
 */
function ace_breadcrumb_defaults( $defaults ) {
	return array_merge(
		$defaults,
		array(
			'delimiter'   => '<span class="sep" aria-hidden="true">&rsaquo;</span>',
			'wrap_before' => '<nav class="woocommerce-breadcrumb" aria-label="' . esc_attr__( 'Breadcrumb', '2acetech' ) . '">',
			'wrap_after'  => '</nav>',
			'home'        => __( 'Home', '2acetech' ),
		)
	);
}
add_filter( 'woocommerce_breadcrumb_defaults', 'ace_breadcrumb_defaults' );

/**
 * Wire the product page, only on single products.
 */
function ace_product_hooks() {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}

	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10 );
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );

	add_action( 'woocommerce_single_product_summary', 'ace_single_brand', 6 );
	add_action( 'woocommerce_single_product_summary', 'ace_single_rating', 7 );
	add_action( 'woocommerce_single_product_summary', 'ace_single_urgency', 25 );
	add_action( 'woocommerce_single_product_summary', 'ace_single_perks', 35 );
	add_action( 'woocommerce_single_product_summary', 'ace_single_info', 40 );
	add_action( 'woocommerce_share', 'ace_single_share' );
	add_action( 'wp_footer', 'ace_buybar' );

	// Simple products show availability in our info list instead.
	add_filter(
		'woocommerce_get_stock_html',
		static function ( $html, $product ) {
			return $product->is_type( 'simple' ) ? '' : $html;
		},
		10,
		2
	);
}
add_action( 'wp', 'ace_product_hooks' );

/**
 * Brand (WooCommerce Brands), as logo or name.
 */
function ace_single_brand() {
	global $product;
	if ( ! taxonomy_exists( 'product_brand' ) ) {
		return;
	}
	$terms = get_the_terms( $product->get_id(), 'product_brand' );
	if ( ! $terms || is_wp_error( $terms ) ) {
		return;
	}
	$term    = $terms[0];
	$logo_id = (int) get_term_meta( $term->term_id, 'thumbnail_id', true );
	printf(
		'<p class="single-brand"><span>%1$s</span> <a href="%2$s">%3$s</a></p>',
		esc_html__( 'Brand:', '2acetech' ),
		esc_url( get_term_link( $term ) ),
		$logo_id ? wp_get_attachment_image( $logo_id, 'medium', false, array( 'alt' => $term->name ) ) : esc_html( $term->name ) // phpcs:ignore WordPress.Security.EscapeOutput
	);
}

/**
 * Rating line, or an invitation to be the first reviewer.
 */
function ace_single_rating() {
	global $product;
	if ( ! wc_review_ratings_enabled() ) {
		return;
	}
	$count = $product->get_review_count();
	echo '<div class="single-rating">';
	if ( $count > 0 ) {
		echo ace_stars( (float) $product->get_average_rating() ); // phpcs:ignore WordPress.Security.EscapeOutput
		printf(
			'<a href="#reviews" class="single-rating__link">%s</a>',
			/* translators: %d: number of reviews. */
			esc_html( sprintf( _n( '%d review', '%d reviews', $count, '2acetech' ), $count ) )
		);
	} else {
		printf( '<a href="#reviews" class="single-rating__link">%s</a>', esc_html__( 'Be the first to review this product', '2acetech' ) );
	}
	echo '</div>';
}

/**
 * Urgency block: countdown (needs a sale end date) and stock bar (needs managed
 * stock and an active sale). Nothing is invented; with neither it prints
 * nothing, and a low-stock note appears only when stock really is low.
 */
function ace_single_urgency() {
	global $product;

	$on_sale = $product->is_on_sale();
	$ends    = $on_sale ? $product->get_date_on_sale_to() : null;
	$timer   = $ends && $ends->getTimestamp() > time();

	$qty   = $product->get_manage_stock() ? $product->get_stock_quantity() : null;
	$bar   = $on_sale && null !== $qty && $qty > 0;
	$low   = null !== $qty && $qty > 0 && $qty <= 5;

	if ( ! $timer && ! $bar && ! $low ) {
		return;
	}

	echo '<div class="urgency">';

	if ( $timer || $bar ) {
		printf( '<p class="urgency__title">%s</p>', esc_html__( 'Hurry up! Special offer.', '2acetech' ) );
	}

	if ( $bar ) {
		$sold  = (int) $product->get_total_sales();
		$total = $sold + (int) $qty;
		$pct   = $total > 0 ? max( 4, min( 100, round( $sold / $total * 100 ) ) ) : 4;
		?>
		<div class="stockbar">
			<div class="stockbar__labels">
				<span><?php echo esc_html( sprintf( /* translators: %d: units available. */ __( 'Available: %d', '2acetech' ), $qty ) ); ?></span>
				<span><?php echo esc_html( sprintf( /* translators: %d: units sold. */ __( 'Already sold: %d', '2acetech' ), $sold ) ); ?></span>
			</div>
			<div class="stockbar__track" role="img" aria-label="<?php echo esc_attr( sprintf( /* translators: %d: percent sold. */ __( '%d%% sold', '2acetech' ), $pct ) ); ?>"><span class="stockbar__fill" style="--pct:<?php echo (int) $pct; ?>%"></span></div>
		</div>
		<?php
	} elseif ( $low ) {
		printf( '<p class="urgency__low">%s</p>', esc_html( sprintf( /* translators: %d: units left. */ _n( 'Only %d left in stock', 'Only %d left in stock', $qty, '2acetech' ), $qty ) ) );
	}

	if ( $timer ) {
		printf(
			'<div class="countdown" data-countdown="%s" role="timer" aria-live="off"><div><b data-cd="d">00</b><span>%s</span></div><div><b data-cd="h">00</b><span>%s</span></div><div><b data-cd="m">00</b><span>%s</span></div><div><b data-cd="s">00</b><span>%s</span></div></div>',
			esc_attr( $ends->date( 'c' ) ),
			esc_html__( 'days', '2acetech' ),
			esc_html__( 'hrs', '2acetech' ),
			esc_html__( 'min', '2acetech' ),
			esc_html__( 'sec', '2acetech' )
		);
	}

	echo '</div>';
}

/**
 * Delivery, payment and returns reassurance under the buy button.
 */
function ace_single_perks() {
	$perks = array(
		array( 'truck', __( 'Fast, tracked delivery across Poland', '2acetech' ) ),
		array( 'shield', __( 'Secure, encrypted checkout', '2acetech' ) ),
		array( 'return', __( '14-day right of withdrawal', '2acetech' ) ),
	);
	echo '<ul class="perks">';
	foreach ( $perks as $perk ) {
		printf( '<li>%s<span>%s</span></li>', ace_icon( $perk[0], 20 ), esc_html( $perk[1] ) ); // phpcs:ignore WordPress.Security.EscapeOutput
	}
	echo '</ul>';
}

/**
 * Availability, SKU and categories.
 */
function ace_single_info() {
	global $product;
	echo '<dl class="info">';

	if ( $product->is_type( 'simple' ) ) {
		if ( $product->is_on_backorder() ) {
			$state = array( 'is-back', __( 'On backorder', '2acetech' ) );
		} elseif ( $product->is_in_stock() ) {
			$state = array( 'is-in', __( 'In stock', '2acetech' ) );
		} else {
			$state = array( 'is-out', __( 'Out of stock', '2acetech' ) );
		}
		printf( '<div><dt>%s</dt><dd class="info__stock %s">%s</dd></div>', esc_html__( 'Availability', '2acetech' ), esc_attr( $state[0] ), esc_html( $state[1] ) );
	}

	if ( wc_product_sku_enabled() && $product->get_sku() ) {
		printf( '<div><dt>%s</dt><dd>%s</dd></div>', esc_html__( 'SKU', '2acetech' ), esc_html( $product->get_sku() ) );
	}

	$cats = wc_get_product_category_list( $product->get_id(), ', ' );
	if ( $cats ) {
		printf( '<div><dt>%s</dt><dd>%s</dd></div>', esc_html__( 'Category', '2acetech' ), wp_kses_post( $cats ) );
	}
	echo '</dl>';
}

/**
 * Share links (no third-party scripts), plus copy-link.
 */
function ace_single_share() {
	$url   = rawurlencode( get_permalink() );
	$title = rawurlencode( get_the_title() );

	$links = array(
		'facebook' => array( 'Facebook', 'https://www.facebook.com/sharer/sharer.php?u=' . $url ),
		'x'        => array( 'X', 'https://twitter.com/intent/tweet?url=' . $url . '&text=' . $title ),
		'whatsapp' => array( 'WhatsApp', 'https://wa.me/?text=' . $title . '%20' . $url ),
		'linkedin' => array( 'LinkedIn', 'https://www.linkedin.com/sharing/share-offsite/?url=' . $url ),
	);

	echo '<div class="share"><span class="share__label">' . esc_html__( 'Share', '2acetech' ) . '</span><ul>';
	foreach ( $links as $key => $link ) {
		printf(
			'<li><a href="%1$s" target="_blank" rel="noopener noreferrer"><span class="screen-reader-text">%2$s</span>%3$s</a></li>',
			esc_url( $link[1] ),
			esc_html( $link[0] ),
			ace_icon( $key, 18 ) // phpcs:ignore WordPress.Security.EscapeOutput
		);
	}
	printf(
		'<li><button type="button" data-copy-link="%1$s" data-copied="%2$s"><span class="screen-reader-text">%3$s</span>%4$s</button></li>',
		esc_url( get_permalink() ),
		esc_attr__( 'Link copied', '2acetech' ),
		esc_html__( 'Copy link', '2acetech' ),
		ace_icon( 'link', 18 ) // phpcs:ignore WordPress.Security.EscapeOutput
	);
	echo '</ul></div>';
}

/**
 * Sticky buy bar for phones: appears once the real buy button scrolls away.
 */
function ace_buybar() {
	global $product;
	if ( ! $product instanceof WC_Product || ! $product->is_purchasable() ) {
		return;
	}
	?>
	<div class="buybar" data-buybar aria-hidden="true">
		<div class="buybar__info">
			<span class="buybar__title"><?php echo esc_html( $product->get_name() ); ?></span>
			<span class="buybar__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></span>
		</div>
		<button type="button" class="btn btn--primary" data-buybar-btn tabindex="-1"><?php echo esc_html( $product->single_add_to_cart_text() ); ?></button>
	</div>
	<?php
}

/**
 * Product page assets. product.js also powers the cart quantity stepper.
 */
function ace_product_assets() {
	if ( ! function_exists( 'is_product' ) ) {
		return;
	}
	if ( is_product() ) {
		wp_enqueue_style( 'ace-product', ACE_URI . '/assets/css/product.css', array( 'ace-woo' ), ACE_VERSION );
	}
	if ( is_product() || is_cart() ) {
		wp_enqueue_script( 'ace-product', ACE_URI . '/assets/js/product.js', array(), ACE_VERSION, array( 'strategy' => 'defer', 'in_footer' => true ) );
	}
}
add_action( 'wp_enqueue_scripts', 'ace_product_assets', 30 );
