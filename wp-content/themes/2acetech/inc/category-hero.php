<?php
/**
 * Category carousel hero.
 *
 * The top of the homepage is a carousel with one slide per main category: a
 * catchy phrase, the subcategories, a button, and photos of real products from
 * that category. The slides follow the shop: a new category appears by itself
 * once it has products, and an empty one disappears.
 *
 * The phrase is, in this order: the category's own "Banner phrase" (Products >
 * Categories > edit; the cleaning plugin writes one for the categories it
 * creates), a built-in phrase for common categories, or "Discover: <name>".
 *
 * A banner with "show from / until" dates (Black Friday, Christmas ...) still
 * wins over the carousel while it runs. See front-page.php.
 *
 * @package 2acetech
 */

defined( 'ABSPATH' ) || exit;

/** Term meta that holds a category's banner phrase. */
const ACE_CAT_TAGLINE = 'ace_cat_tagline';

/** Most slides the carousel shows. */
const ACE_CAT_HERO_MAX = 6;

/**
 * Built-in phrases for common categories, matched on the category's name or slug.
 *
 * @return string[] Needle => phrase.
 */
function ace_cat_tagline_defaults() {
	return array(
		'camping'    => __( 'Sleep under the stars, in comfort.', '2acetech' ),
		'kemping'    => __( 'Sleep under the stars, in comfort.', '2acetech' ),
		'oswietl'    => __( 'Light that sets the mood.', '2acetech' ),
		'lighting'   => __( 'Light that sets the mood.', '2acetech' ),
		'zabaw'      => __( 'Play starts here.', '2acetech' ),
		'toy'        => __( 'Play starts here.', '2acetech' ),
		'sport'      => __( 'Get out and get moving.', '2acetech' ),
		'turyst'     => __( 'Get out and get moving.', '2acetech' ),
		'ogrod'      => __( 'Make the garden your favourite room.', '2acetech' ),
		'garden'     => __( 'Make the garden your favourite room.', '2acetech' ),
		'kuchn'      => __( 'Everyday life, made easier.', '2acetech' ),
		'dom '       => __( 'Everyday life, made easier.', '2acetech' ),
		'elektron'   => __( 'Smart gadgets for everyday life.', '2acetech' ),
		'komputer'   => __( 'Gear that keeps up with you.', '2acetech' ),
		'dziec'      => __( 'Everything for the little ones.', '2acetech' ),
		'niemowl'    => __( 'Everything for the little ones.', '2acetech' ),
		'zwierz'     => __( 'Treats for your four-legged friend.', '2acetech' ),
		'biur'       => __( 'A tidy desk, a clear head.', '2acetech' ),
	);
}

/**
 * The phrase for a category.
 *
 * @param WP_Term $term Category.
 * @return string
 */
function ace_category_tagline( $term ) {
	$own = trim( (string) get_term_meta( $term->term_id, ACE_CAT_TAGLINE, true ) );
	if ( '' !== $own ) {
		return $own;
	}
	$hay = ' ' . ( function_exists( 'mb_strtolower' ) ? mb_strtolower( $term->name . ' ' . $term->slug ) : strtolower( $term->name . ' ' . $term->slug ) ) . ' ';
	foreach ( ace_cat_tagline_defaults() as $needle => $phrase ) {
		if ( false !== strpos( $hay, $needle ) ) {
			return $phrase;
		}
	}
	/* translators: %s: category name. */
	return sprintf( __( 'Discover: %s', '2acetech' ), $term->name );
}

/**
 * The slides: main categories that have products, biggest first.
 *
 * @param int $limit Most slides.
 * @return array[] Each: term, name, url, tagline, count, subs (name+url), products (WC_Product[]).
 */
function ace_category_hero_slides( $limit = ACE_CAT_HERO_MAX ) {
	if ( ace_is_demo() || ! function_exists( 'wc_get_product' ) ) {
		return array();
	}
	$cats = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'parent'     => 0,
			'hide_empty' => true,
			'pad_counts' => true,
			'exclude'    => array( (int) get_option( 'default_product_cat' ) ),
		)
	);
	if ( is_wp_error( $cats ) || ! $cats ) {
		return array();
	}
	usort(
		$cats,
		static function ( $a, $b ) {
			return $b->count <=> $a->count;
		}
	);

	$slides = array();
	foreach ( $cats as $cat ) {
		$products = ace_category_showcase_products( $cat->slug, 3 );
		if ( ! $products ) {
			continue; // Nothing that can be bought: no slide.
		}
		$subs = array();
		$kids = get_terms( array( 'taxonomy' => 'product_cat', 'parent' => $cat->term_id, 'hide_empty' => true, 'orderby' => 'count', 'order' => 'DESC', 'number' => 4 ) );
		// A main category with a single subcategory (Sport i turystyka > Camping): show what is inside that one.
		if ( ! is_wp_error( $kids ) && 1 === count( $kids ) ) {
			$inner = get_terms( array( 'taxonomy' => 'product_cat', 'parent' => $kids[0]->term_id, 'hide_empty' => true, 'orderby' => 'count', 'order' => 'DESC', 'number' => 4 ) );
			if ( ! is_wp_error( $inner ) && $inner ) {
				$kids = $inner;
			}
		}
		if ( ! is_wp_error( $kids ) ) {
			foreach ( $kids as $kid ) {
				$link = get_term_link( $kid );
				if ( ! is_wp_error( $link ) ) {
					$subs[] = array( 'name' => $kid->name, 'url' => $link );
				}
			}
		}
		$url = get_term_link( $cat );
		$slides[] = array(
			'term'     => $cat,
			'name'     => $cat->name,
			'url'      => is_wp_error( $url ) ? ace_shop_url() : $url,
			'tagline'  => ace_category_tagline( $cat ),
			'count'    => (int) $cat->count,
			'subs'     => $subs,
			'products' => $products,
		);
		if ( count( $slides ) >= max( 1, (int) $limit ) ) {
			break;
		}
	}
	return apply_filters( 'ace_category_hero_slides', $slides );
}

/**
 * Is the category carousel the hero right now? On unless switched off in the
 * Customizer, and not while a banner with dates is running.
 *
 * @return bool
 */
function ace_category_hero_active() {
	if ( ! get_theme_mod( 'ace_hero_cats', true ) ) {
		return false;
	}
	$banner = ace_active_banner();
	if ( $banner && ( $banner['from'] || $banner['until'] ) ) {
		return false; // An occasion banner is running.
	}
	return (bool) ace_category_hero_slides();
}

/* -------------------------------------------------------------------------
 * "Banner phrase" field on the category screens
 * ---------------------------------------------------------------------- */

function ace_cat_tagline_add_field() {
	?>
	<div class="form-field">
		<label for="ace_cat_tagline"><?php esc_html_e( 'Banner phrase', '2acetech' ); ?></label>
		<input type="text" name="ace_cat_tagline" id="ace_cat_tagline" value="" maxlength="90">
		<p><?php esc_html_e( 'A short catchy line shown on this category’s slide in the homepage carousel. Empty = a built-in phrase.', '2acetech' ); ?></p>
	</div>
	<?php
}
add_action( 'product_cat_add_form_fields', 'ace_cat_tagline_add_field' );

function ace_cat_tagline_edit_field( $term ) {
	$value = (string) get_term_meta( $term->term_id, ACE_CAT_TAGLINE, true );
	?>
	<tr class="form-field">
		<th scope="row"><label for="ace_cat_tagline"><?php esc_html_e( 'Banner phrase', '2acetech' ); ?></label></th>
		<td>
			<input type="text" name="ace_cat_tagline" id="ace_cat_tagline" value="<?php echo esc_attr( $value ); ?>" maxlength="90">
			<p class="description"><?php esc_html_e( 'A short catchy line shown on this category’s slide in the homepage carousel. Empty = a built-in phrase.', '2acetech' ); ?></p>
		</td>
	</tr>
	<?php
}
add_action( 'product_cat_edit_form_fields', 'ace_cat_tagline_edit_field' );

function ace_cat_tagline_save( $term_id ) {
	if ( ! isset( $_POST['ace_cat_tagline'] ) || ! current_user_can( 'manage_product_terms' ) ) { // phpcs:ignore WordPress.Security.NonceVerification -- WordPress verifies the term form.
		return;
	}
	$value = sanitize_text_field( wp_unslash( $_POST['ace_cat_tagline'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
	if ( '' === $value ) {
		delete_term_meta( $term_id, ACE_CAT_TAGLINE );
	} else {
		update_term_meta( $term_id, ACE_CAT_TAGLINE, mb_substr( $value, 0, 90 ) );
	}
}
add_action( 'created_product_cat', 'ace_cat_tagline_save' );
add_action( 'edited_product_cat', 'ace_cat_tagline_save' );

/* -------------------------------------------------------------------------
 * Customizer + assets
 * ---------------------------------------------------------------------- */

/**
 * Switch in Customize > Landing page.
 *
 * @param WP_Customize_Manager $wp Customizer.
 */
function ace_customize_category_hero( $wp ) {
	if ( ! $wp->get_section( 'ace_hero_switch' ) ) {
		return;
	}
	ace_cz_field(
		$wp,
		'ace_hero_switch',
		'ace_hero_cats',
		__( 'Show the category carousel at the top (recommended)', '2acetech' ),
		true,
		array(
			'type'        => 'checkbox',
			'description' => __( 'One slide per main category with a catchy phrase and real products. A banner with "show from / until" dates still takes over while it runs.', '2acetech' ),
		)
	);
}
add_action( 'customize_register', 'ace_customize_category_hero', 30 );

function ace_category_hero_assets() {
	if ( ! is_front_page() || ! ace_category_hero_active() ) {
		return;
	}
	wp_enqueue_style( 'ace-chero', ACE_URI . '/assets/css/category-hero.css', array( 'ace-main' ), ace_ver( 'assets/css/category-hero.css' ) );
	wp_enqueue_script( 'ace-chero', ACE_URI . '/assets/js/category-hero.js', array(), ace_ver( 'assets/js/category-hero.js' ), array( 'strategy' => 'defer', 'in_footer' => true ) );
}
add_action( 'wp_enqueue_scripts', 'ace_category_hero_assets', 31 );
