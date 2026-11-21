<?php
/**
 * Photos: keeps imported galleries at 5 photos at most, and shows which
 * imports have only one photo (the importer's image queue has not run).
 *
 * The importer saves the main photo at once and downloads the rest in the
 * background, so photos can arrive after a product was cleaned. That is why
 * this runs on every automatic tick, not only when a product is cleaned.
 *
 * @package 2ace-product-cleaner
 */

defined( 'ABSPATH' ) || exit;

class ACE_PC_Photos {

	/** Main photo + gallery, at most. */
	const MAX = 5;

	/** Gallery photos taken off a product, kept so nothing is lost. */
	const REMOVED = '_aliclean_gallery_removed';

	/** Imports younger than this are not reported (their photos may still be coming). */
	const GRACE = 900;

	/** ALD page that lists and restarts the stuck image queue. */
	const QUEUE_PAGE = 'woo-alidropship-error-images';

	public static function init() {
		add_filter( 'manage_edit-product_columns', array( __CLASS__, 'column' ), 20 );
		add_action( 'manage_product_posts_custom_column', array( __CLASS__, 'column_cell' ), 10, 2 );
		add_action( 'admin_notices', array( __CLASS__, 'notice' ) );
		add_action( 'admin_head', array( __CLASS__, 'css' ) );
	}

	/** Own products (RMF-*, lig*) are never touched, as everywhere in this plugin. */
	private static function eligible( $product ) {
		return $product instanceof WC_Product
			&& ACE_PC_Cleaner::is_import( $product )
			&& ! ACE_PC_Cleaner::is_protected( $product );
	}

	/** Number of photos on a product (main photo + gallery). */
	public static function count( $product ) {
		return ( $product->get_image_id() ? 1 : 0 ) + count( $product->get_gallery_image_ids() );
	}

	/**
	 * Cap one product at MAX photos: the main photo and the first gallery photos
	 * stay, in their order. The rest only leave the gallery (the files stay in the
	 * media library, and their ids are kept in a meta field).
	 *
	 * @return int How many photos were taken off.
	 */
	public static function tidy( $id ) {
		$product = wc_get_product( $id );
		if ( ! self::eligible( $product ) ) {
			return 0;
		}
		$room    = self::MAX - ( $product->get_image_id() ? 1 : 0 );
		$gallery = array_values( array_map( 'intval', $product->get_gallery_image_ids() ) );
		if ( count( $gallery ) <= $room ) {
			return 0;
		}
		$extra = array_slice( $gallery, $room );
		$old   = (array) get_post_meta( $id, self::REMOVED, true );
		update_post_meta( $id, self::REMOVED, array_values( array_unique( array_merge( array_filter( $old ), $extra ) ) ) );
		$product->set_gallery_image_ids( array_slice( $gallery, 0, $room ) );
		$product->save();
		wc_delete_product_transients( $id );
		return count( $extra );
	}

	/** Every import. Runs on each automatic tick, whether or not cleaning is switched on. */
	public static function tidy_all() {
		$ids = get_posts(
			array(
				'post_type'      => 'product',
				'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);
		foreach ( $ids as $id ) {
			$n = self::tidy( $id );
			if ( $n ) {
				ACE_PC_Settings::log( sprintf( '#%d photos: %d extra taken off the gallery (max %d).', $id, $n, self::MAX ) );
			}
		}
	}

	/** Imports (older than the grace period) that have a single photo or none. */
	public static function lacking() {
		$ids = get_posts(
			array(
				'post_type'      => 'product',
				'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'date_query'     => array( array( 'column' => 'post_date_gmt', 'before' => gmdate( 'Y-m-d H:i:s', time() - self::GRACE ) ) ),
			)
		);
		$out = array();
		foreach ( $ids as $id ) {
			$p = wc_get_product( $id );
			if ( self::eligible( $p ) && self::count( $p ) <= 1 ) {
				$out[ $id ] = $p->get_name();
			}
		}
		return $out;
	}

	private static function queue_url() {
		return admin_url( 'admin.php?page=' . self::QUEUE_PAGE );
	}

	/* ---- Products list ---- */

	public static function column( $cols ) {
		$out = array();
		foreach ( $cols as $key => $label ) {
			$out[ $key ] = $label;
			if ( 'name' === $key ) {
				$out['ace_photos'] = __( 'Photos', '2ace-product-cleaner' );
			}
		}
		return $out;
	}

	public static function column_cell( $col, $post_id ) {
		if ( 'ace_photos' !== $col ) {
			return;
		}
		$p = wc_get_product( $post_id );
		if ( ! $p ) {
			return;
		}
		$n = self::count( $p );
		if ( self::eligible( $p ) && $n <= 1 ) {
			printf(
				'<a class="ace-pc-photos ace-pc-photos--low" href="%s" title="%s">%d</a>',
				esc_url( self::queue_url() ),
				esc_attr__( 'Only one photo. The importer may not have downloaded the gallery yet: open its image queue and press Run.', '2ace-product-cleaner' ),
				(int) $n
			);
			return;
		}
		printf( '<span class="ace-pc-photos">%d</span>', (int) $n );
	}

	public static function css() {
		$s = get_current_screen();
		if ( ! $s || 'edit-product' !== $s->id ) {
			return;
		}
		echo '<style>.column-ace_photos{width:70px}.ace-pc-photos{display:inline-block;min-width:24px;padding:2px 8px;border-radius:999px;background:#f0f0f1;text-align:center;font-weight:600}.ace-pc-photos--low{background:#fcf0f1;color:#b32d2e;text-decoration:none}</style>';
	}

	/* ---- Warning ---- */

	public static function notice() {
		$s = get_current_screen();
		if ( ! $s || 'edit-product' !== $s->id || ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		$low = self::lacking();
		if ( ! $low ) {
			return;
		}
		$names = array_map( 'esc_html', array_slice( array_map( function ( $n ) {
			return wp_html_excerpt( $n, 40, '…' );
		}, array_values( $low ) ), 0, 5 ) );
		printf(
			'<div class="notice notice-warning"><p><strong>%s</strong> %s <a href="%s">%s</a></p><p>%s</p></div>',
			esc_html( sprintf(
				/* translators: %d: number of products */
				_n( '%d imported product has only one photo.', '%d imported products have only one photo.', count( $low ), '2ace-product-cleaner' ),
				count( $low )
			) ),
			esc_html__( 'The importer saves the main photo first and downloads the rest in the background. If that has stalled, open the image queue and press Run:', '2ace-product-cleaner' ),
			esc_url( self::queue_url() ),
			esc_html__( 'Failed images / image queue', '2ace-product-cleaner' ),
			implode( ' · ', $names ) // Already escaped above.
		);
	}
}
