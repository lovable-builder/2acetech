<?php
/**
 * Editing a product photo and putting the result in the product.
 *
 * Nothing is overwritten: an edit becomes a NEW attachment (the original stays in the media library),
 * and it only replaces the original in a product after the shop owner approves it. The swap is recorded
 * on the product so one click puts the original back.
 *
 * @package 2ace-photo-editor
 */

defined( 'ABSPATH' ) || exit;

class ACE_PE_Editor {

	/** On the new attachment: id of the photo it was made from. */
	const SOURCE = '_ace_pe_source';
	/** On the product: [ new attachment id => original attachment id ] for photos that are swapped in. */
	const SWAPS = '_ace_pe_swaps';
	/** Request counter for the daily limit. */
	const USAGE = 'ace_pe_usage';

	/** Modes: what to remove. */
	public static function modes() {
		return array(
			'overlay' => __( 'Text added on top of the photo (recommended): slogans, banners, badges, size labels, watermarks, logos. Keeps what is printed on the product itself.', '2ace-photo-editor' ),
			'all'     => __( 'All text and numbers, also on the product or its packaging', '2ace-photo-editor' ),
		);
	}

	/** The instruction sent with the photo. */
	public static function prompt( $mode ) {
		$keep = 'overlay' === $mode
			? 'Do NOT remove text, logos or markings that are physically printed on the product itself or on its packaging.'
			: 'Remove text and numbers everywhere, including those printed on the product or its packaging.';
		$text = 'Edit this e-commerce product photo. Remove every piece of text, number, logo, badge, watermark, caption, arrow and graphic overlay that was added on top of the photo '
			. '(headlines, slogans, banners, price or shipping labels, size and dimension annotations, brand marks). ' . $keep . ' '
			. 'Rebuild the areas behind the removed elements so they look natural and continuous with their surroundings. '
			. 'Do not change the product in any way: same shape, colours, proportions, parts, accessories and details. Do not add, remove, move or invent any object. '
			. 'Keep the same framing, aspect ratio, camera angle, lighting and background style. Output only the edited image.';
		return apply_filters( 'ace_pe_prompt', $text, $mode );
	}

	/* ---- daily limit ---- */

	public static function used_today() {
		$u = get_option( self::USAGE, array() );
		return ( is_array( $u ) && isset( $u['date'], $u['n'] ) && gmdate( 'Y-m-d' ) === $u['date'] ) ? (int) $u['n'] : 0;
	}

	public static function left_today() {
		$s = ACE_PE_Google::settings();
		return max( 0, (int) $s['daily_limit'] - self::used_today() );
	}

	private static function count_use() {
		update_option( self::USAGE, array( 'date' => gmdate( 'Y-m-d' ), 'n' => self::used_today() + 1 ), false );
	}

	/* ---- image helpers ---- */

	/** Convert whatever Google returned to a JPEG (white behind any transparency). */
	public static function to_jpeg( $bytes ) {
		if ( ! function_exists( 'imagecreatefromstring' ) ) {
			return $bytes;
		}
		$im = @imagecreatefromstring( $bytes ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		if ( ! $im ) {
			return $bytes;
		}
		$w   = imagesx( $im );
		$h   = imagesy( $im );
		$out = imagecreatetruecolor( $w, $h );
		imagefill( $out, 0, 0, imagecolorallocate( $out, 255, 255, 255 ) );
		imagecopy( $out, $im, 0, 0, 0, 0, $w, $h );
		ob_start();
		imagejpeg( $out, null, 92 );
		return (string) ob_get_clean();
	}

	/**
	 * Mark a JPEG as AI-edited with the IPTC "digital source type" in an XMP block. Google Merchant Center
	 * asks for this on images made or changed with AI.
	 */
	public static function inject_xmp( $jpeg ) {
		if ( "\xFF\xD8" !== substr( $jpeg, 0, 2 ) ) {
			return $jpeg;
		}
		$packet = '<?xpacket begin="' . "\xEF\xBB\xBF" . '" id="W5M0MpCehiHzreSzNTczkc9d"?>'
			. '<x:xmpmeta xmlns:x="adobe:ns:meta/"><rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">'
			. '<rdf:Description rdf:about="" xmlns:Iptc4xmpExt="http://iptc.org/std/Iptc4xmpExt/2008-02-29/" '
			. 'Iptc4xmpExt:DigitalSourceType="http://cv.iptc.org/newscodes/digitalsourcetype/compositeWithTrainedAlgorithmicMedia"/>'
			. '</rdf:RDF></x:xmpmeta><?xpacket end="w"?>';
		$body = "http://ns.adobe.com/xap/1.0/\0" . $packet;
		$seg  = "\xFF\xE1" . pack( 'n', strlen( $body ) + 2 ) . $body;
		$pos  = 2;
		if ( "\xFF\xE0" === substr( $jpeg, 2, 2 ) ) { // Keep the JFIF header first.
			$len = unpack( 'n', substr( $jpeg, 4, 2 ) );
			$pos = 4 + $len[1];
		}
		return substr( $jpeg, 0, $pos ) . $seg . substr( $jpeg, $pos );
	}

	/* ---- editing ---- */

	/**
	 * Edit one photo and store the result as a new attachment.
	 *
	 * @param int    $att_id Original attachment.
	 * @param string $mode   overlay|all.
	 * @param int    $parent Product the new photo belongs to.
	 * @return int|WP_Error New attachment id.
	 */
	public static function edit_attachment( $att_id, $mode, $parent = 0 ) {
		$mode = isset( self::modes()[ $mode ] ) ? $mode : 'overlay';
		$file = get_attached_file( $att_id );
		if ( ! $file || ! file_exists( $file ) || ! wp_attachment_is_image( $att_id ) ) {
			return new WP_Error( 'ace_pe_file', __( 'That photo could not be found.', '2ace-photo-editor' ) );
		}
		if ( self::left_today() < 1 ) {
			return new WP_Error( 'ace_pe_limit', __( 'The daily limit of edits is used up. Raise it in the settings or try again tomorrow.', '2ace-photo-editor' ) );
		}
		$bytes = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		$mime  = get_post_mime_type( $att_id );
		$res   = apply_filters( 'ace_pe_edit_image', null, $bytes, $mime, self::prompt( $mode ) ); // Tests supply the answer here.
		if ( ! is_array( $res ) && ! is_wp_error( $res ) ) {
			$res = ACE_PE_Google::edit( $bytes, $mime, self::prompt( $mode ) );
		}
		self::count_use();
		if ( is_wp_error( $res ) ) {
			return $res;
		}

		$jpeg = self::inject_xmp( self::to_jpeg( $res['bytes'] ) );
		$name = sanitize_file_name( pathinfo( $file, PATHINFO_FILENAME ) . '-bez-tekstu-' . strtolower( wp_generate_password( 4, false ) ) . '.jpg' );
		$up   = wp_upload_bits( $name, null, $jpeg );
		if ( ! empty( $up['error'] ) ) {
			return new WP_Error( 'ace_pe_save', $up['error'] );
		}
		$new = wp_insert_attachment(
			array(
				'post_mime_type' => 'image/jpeg',
				'post_title'     => get_the_title( $att_id ) . ' (' . __( 'text removed', '2ace-photo-editor' ) . ')',
				'post_status'    => 'inherit',
			),
			$up['file'],
			(int) $parent
		);
		if ( is_wp_error( $new ) || ! $new ) {
			return new WP_Error( 'ace_pe_save', __( 'The edited photo could not be saved.', '2ace-photo-editor' ) );
		}
		require_once ABSPATH . 'wp-admin/includes/image.php';
		wp_update_attachment_metadata( $new, wp_generate_attachment_metadata( $new, $up['file'] ) );
		update_post_meta( $new, '_wp_attachment_image_alt', (string) get_post_meta( $att_id, '_wp_attachment_image_alt', true ) );
		update_post_meta( $new, self::SOURCE, (int) $att_id );
		update_post_meta( $new, '_ace_pe_mode', $mode );
		return (int) $new;
	}

	/** Photos of a product: [ attachment id => label ], main photo first. */
	public static function product_images( $product ) {
		$out = array();
		if ( $product->get_image_id() ) {
			$out[ (int) $product->get_image_id() ] = 'main';
		}
		foreach ( $product->get_gallery_image_ids() as $i => $id ) {
			$out[ (int) $id ] = 'gallery';
		}
		return $out;
	}

	/** Is this attachment one of the product's photos (including variation photos)? */
	public static function belongs( $product, $att_id ) {
		if ( isset( self::product_images( $product )[ (int) $att_id ] ) ) {
			return true;
		}
		foreach ( $product->get_children() as $vid ) {
			$v = wc_get_product( $vid );
			if ( $v && (int) $v->get_image_id() === (int) $att_id ) {
				return true;
			}
		}
		return false;
	}

	/** Replace $old with $new wherever the product shows it (main photo, gallery position, variations). */
	private static function swap( $product, $from, $to ) {
		if ( (int) $product->get_image_id() === (int) $from ) {
			$product->set_image_id( $to );
		}
		$product->set_gallery_image_ids( array_map( function ( $id ) use ( $from, $to ) { return (int) $id === (int) $from ? (int) $to : (int) $id; }, $product->get_gallery_image_ids() ) );
		$product->save();
		foreach ( $product->get_children() as $vid ) {
			$v = wc_get_product( $vid );
			if ( $v && (int) $v->get_image_id() === (int) $from ) {
				$v->set_image_id( $to );
				$v->save();
			}
		}
		wc_delete_product_transients( $product->get_id() );
	}

	/** Use the edited photo in the product. */
	public static function apply( $product_id, $old, $new ) {
		$product = wc_get_product( $product_id );
		if ( ! $product || (int) get_post_meta( $new, self::SOURCE, true ) !== (int) $old || ! self::belongs( $product, $old ) ) {
			return new WP_Error( 'ace_pe_apply', __( 'This photo does not belong to the product.', '2ace-photo-editor' ) );
		}
		self::swap( $product, $old, $new );
		$swaps = get_post_meta( $product_id, self::SWAPS, true );
		$swaps = is_array( $swaps ) ? $swaps : array();
		$swaps[ (int) $new ] = (int) $old;
		update_post_meta( $product_id, self::SWAPS, $swaps );
		return true;
	}

	/** Put the original back. The edited photo stays in the media library. */
	public static function revert( $product_id, $new ) {
		$product = wc_get_product( $product_id );
		$swaps   = get_post_meta( $product_id, self::SWAPS, true );
		if ( ! $product || ! is_array( $swaps ) || empty( $swaps[ (int) $new ] ) ) {
			return new WP_Error( 'ace_pe_revert', __( 'Nothing to put back for this photo.', '2ace-photo-editor' ) );
		}
		self::swap( $product, $new, (int) $swaps[ (int) $new ] );
		unset( $swaps[ (int) $new ] );
		update_post_meta( $product_id, self::SWAPS, $swaps );
		return true;
	}

	/** Throw away an edit that was not used. */
	public static function discard( $new ) {
		if ( ! get_post_meta( $new, self::SOURCE, true ) ) {
			return false;
		}
		global $wpdb;
		// Never delete a photo some product is using.
		$used = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key IN ('_thumbnail_id') AND meta_value = %d", $new ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$gal  = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_product_image_gallery' AND FIND_IN_SET(%d, meta_value)", $new ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		if ( (int) $used || (int) $gal ) {
			return false;
		}
		return (bool) wp_delete_attachment( $new, true );
	}
}
