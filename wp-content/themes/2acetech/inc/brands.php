<?php
/**
 * Brand logos: one standard look for every logo, whatever file is uploaded.
 *
 * Logos arrive in every shape: wide, tall, square, with big empty margins, on a
 * white background. This file makes them consistent:
 *
 * 1. Normalising. The uploaded logo is trimmed of its empty margin (transparent
 *    or near-white) and saved as a tidy PNG, once, in uploads/ace-brands. The
 *    original in the Media Library is never touched. If the server has no GD
 *    library the original is used.
 * 2. One slot. CSS shows every logo inside the same fixed-size slot, fitted
 *    with object-fit: contain, so a wide logo and a square one take the same
 *    space. White backgrounds disappear (multiply blend).
 * 3. Two knobs. Customize > Brand logos sets the slot height, colour or grey,
 *    and the strip spacing. Products > Brands > edit has "Logo size" for the
 *    odd logo that still looks too big or small.
 *
 * @package 2acetech
 */

defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------
 * Settings
 * ---------------------------------------------------------------------- */

/**
 * Customizer values with limits applied.
 *
 * @return array height (px), grey (bool), fill (percent of the slot a logo may use).
 */
function ace_brand_settings() {
	$height = (int) get_theme_mod( 'ace_brand_height', 64 );
	$fill   = (int) get_theme_mod( 'ace_brand_fill', 70 );
	return array(
		'height' => max( 40, min( 120, $height ) ),
		'grey'   => 'colour' !== get_theme_mod( 'ace_brand_look', 'grey' ),
		'fill'   => max( 40, min( 100, $fill ) ),
	);
}

/**
 * The "Logo size" chosen for one brand, as a percentage (60 to 160, default 100).
 *
 * @param int $term_id Brand term id.
 * @return int
 */
function ace_brand_scale( $term_id ) {
	$scale = (int) get_term_meta( $term_id, 'ace_logo_scale', true );
	return $scale ? max( 60, min( 160, $scale ) ) : 100;
}

/* -------------------------------------------------------------------------
 * Normalising the file
 * ---------------------------------------------------------------------- */

/**
 * Is this pixel background? Transparent, or close to white.
 *
 * @param int $rgba Colour from imagecolorat() on a true-colour image.
 * @return bool
 */
function ace_brand_is_blank( $rgba ) {
	$alpha = ( $rgba >> 24 ) & 0x7F; // 0 opaque .. 127 transparent.
	if ( $alpha > 110 ) {
		return true;
	}
	$r = ( $rgba >> 16 ) & 0xFF;
	$g = ( $rgba >> 8 ) & 0xFF;
	$b = $rgba & 0xFF;
	return $r > 242 && $g > 242 && $b > 242;
}

/**
 * Trim the empty margin of an image and return a new, padded GD image, or null.
 *
 * @param string $file Path to the source image.
 * @return GdImage|resource|null
 */
function ace_brand_trim( $file ) {
	$data = @file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	if ( false === $data ) {
		return null;
	}
	$src = @imagecreatefromstring( $data );
	if ( ! $src ) {
		return null;
	}

	// Work on a copy of at most 900 px so large uploads stay quick.
	$w = imagesx( $src );
	$h = imagesy( $src );
	$k = min( 1, 900 / max( $w, $h ) );
	$ww = max( 1, (int) round( $w * $k ) );
	$hh = max( 1, (int) round( $h * $k ) );

	$img = imagecreatetruecolor( $ww, $hh );
	imagealphablending( $img, false );
	imagesavealpha( $img, true );
	imagefill( $img, 0, 0, imagecolorallocatealpha( $img, 255, 255, 255, 127 ) );
	imagecopyresampled( $img, $src, 0, 0, 0, 0, $ww, $hh, $w, $h );
	imagedestroy( $src );

	$minx = $ww;
	$miny = $hh;
	$maxx = -1;
	$maxy = -1;
	for ( $y = 0; $y < $hh; $y++ ) {
		for ( $x = 0; $x < $ww; $x++ ) {
			if ( ! ace_brand_is_blank( imagecolorat( $img, $x, $y ) ) ) {
				if ( $x < $minx ) {
					$minx = $x;
				}
				if ( $x > $maxx ) {
					$maxx = $x;
				}
				if ( $y < $miny ) {
					$miny = $y;
				}
				if ( $y > $maxy ) {
					$maxy = $y;
				}
			}
		}
	}

	// Nothing but background: keep the whole image.
	if ( $maxx < 0 ) {
		return $img;
	}

	$cw  = $maxx - $minx + 1;
	$ch  = $maxy - $miny + 1;
	$pad = (int) round( max( $cw, $ch ) * 0.02 ); // A hair of air so edges are not clipped.
	$out = imagecreatetruecolor( $cw + 2 * $pad, $ch + 2 * $pad );
	imagealphablending( $out, false );
	imagesavealpha( $out, true );
	imagefill( $out, 0, 0, imagecolorallocatealpha( $out, 255, 255, 255, 127 ) );
	imagecopy( $out, $img, $pad, $pad, $minx, $miny, $cw, $ch );
	imagedestroy( $img );

	// Cap the size: 640 px wide or 320 px tall is plenty for any slot.
	$cw2 = imagesx( $out );
	$ch2 = imagesy( $out );
	$s   = min( 1, 640 / $cw2, 320 / $ch2 );
	if ( $s < 1 ) {
		$nw  = max( 1, (int) round( $cw2 * $s ) );
		$nh  = max( 1, (int) round( $ch2 * $s ) );
		$res = imagecreatetruecolor( $nw, $nh );
		imagealphablending( $res, false );
		imagesavealpha( $res, true );
		imagefill( $res, 0, 0, imagecolorallocatealpha( $res, 255, 255, 255, 127 ) );
		imagecopyresampled( $res, $out, 0, 0, 0, 0, $nw, $nh, $cw2, $ch2 );
		imagedestroy( $out );
		$out = $res;
	}

	return $out;
}

/**
 * The tidy version of an uploaded logo: url, width, height. Built once and
 * remembered; rebuilt when the attachment or its file changes.
 *
 * @param int $attachment_id Media Library id.
 * @return array|null Null means "use the original".
 */
function ace_brand_normalized( $attachment_id ) {
	$attachment_id = (int) $attachment_id;
	if ( ! $attachment_id || ! function_exists( 'imagecreatefromstring' ) || ! function_exists( 'imagepng' ) ) {
		return null;
	}

	$file = get_attached_file( $attachment_id );
	if ( ! $file || ! file_exists( $file ) ) {
		return null;
	}
	// SVG and animated GIF are left alone: they scale cleanly or cannot be trimmed.
	$mime = get_post_mime_type( $attachment_id );
	if ( ! in_array( $mime, array( 'image/png', 'image/jpeg', 'image/webp' ), true ) ) {
		return null;
	}

	$key    = $attachment_id . '-' . filemtime( $file ) . '-1';
	$cached = get_post_meta( $attachment_id, '_ace_brand_logo', true );
	if ( is_array( $cached ) && isset( $cached['key'], $cached['file'], $cached['w'], $cached['h'] ) && $cached['key'] === $key ) {
		$uploads = wp_get_upload_dir();
		if ( file_exists( trailingslashit( $uploads['basedir'] ) . $cached['file'] ) ) {
			return array(
				'url' => trailingslashit( $uploads['baseurl'] ) . $cached['file'],
				'w'   => (int) $cached['w'],
				'h'   => (int) $cached['h'],
			);
		}
	}
	if ( is_array( $cached ) && ! empty( $cached['failed'] ) && isset( $cached['key'] ) && $cached['key'] === $key ) {
		return null; // Tried before and could not: do not retry on every page view.
	}

	$img = ace_brand_trim( $file );
	if ( ! $img ) {
		update_post_meta( $attachment_id, '_ace_brand_logo', array( 'key' => $key, 'failed' => 1 ) );
		return null;
	}

	$uploads = wp_get_upload_dir();
	$dir     = trailingslashit( $uploads['basedir'] ) . 'ace-brands';
	if ( ! wp_mkdir_p( $dir ) ) {
		imagedestroy( $img );
		return null;
	}
	$name = 'brand-' . $attachment_id . '-' . substr( md5( $key ), 0, 8 ) . '.png';
	$path = $dir . '/' . $name;
	imagealphablending( $img, false );
	imagesavealpha( $img, true );
	$ok = imagepng( $img, $path, 9 );
	$w  = imagesx( $img );
	$h  = imagesy( $img );
	imagedestroy( $img );
	if ( ! $ok ) {
		update_post_meta( $attachment_id, '_ace_brand_logo', array( 'key' => $key, 'failed' => 1 ) );
		return null;
	}

	// Remove the older tidy copy of this logo.
	if ( is_array( $cached ) && ! empty( $cached['file'] ) ) {
		$old = trailingslashit( $uploads['basedir'] ) . $cached['file'];
		if ( file_exists( $old ) && $old !== $path ) {
			wp_delete_file( $old );
		}
	}

	update_post_meta(
		$attachment_id,
		'_ace_brand_logo',
		array(
			'key'  => $key,
			'file' => 'ace-brands/' . $name,
			'w'    => $w,
			'h'    => $h,
		)
	);

	return array(
		'url' => trailingslashit( $uploads['baseurl'] ) . 'ace-brands/' . $name,
		'w'   => $w,
		'h'   => $h,
	);
}

/**
 * Forget the tidy copy when its attachment is deleted.
 *
 * @param int $post_id Attachment id.
 */
function ace_brand_forget( $post_id ) {
	$cached = get_post_meta( $post_id, '_ace_brand_logo', true );
	if ( is_array( $cached ) && ! empty( $cached['file'] ) ) {
		$uploads = wp_get_upload_dir();
		wp_delete_file( trailingslashit( $uploads['basedir'] ) . $cached['file'] );
	}
}
add_action( 'delete_attachment', 'ace_brand_forget' );

/* -------------------------------------------------------------------------
 * Markup
 * ---------------------------------------------------------------------- */

/**
 * The logo of a brand term, ready to print inside a standard slot.
 *
 * @param WP_Term $term Brand term.
 * @return string HTML, or '' when the brand has no logo.
 */
function ace_brand_logo( $term ) {
	$logo_id = (int) get_term_meta( $term->term_id, 'thumbnail_id', true );
	if ( ! $logo_id ) {
		return '';
	}

	$norm = ace_brand_normalized( $logo_id );
	if ( $norm ) {
		$img = sprintf(
			'<img src="%1$s" width="%2$d" height="%3$d" alt="%4$s" loading="lazy" decoding="async">',
			esc_url( $norm['url'] ),
			(int) $norm['w'],
			(int) $norm['h'],
			esc_attr( $term->name )
		);
	} else {
		$img = wp_get_attachment_image( $logo_id, 'medium', false, array( 'alt' => $term->name, 'loading' => 'lazy' ) );
	}
	if ( ! $img ) {
		return '';
	}

	$scale = ace_brand_scale( $term->term_id );
	$style = 100 !== $scale ? sprintf( ' style="--logo-scale:%s"', esc_attr( $scale / 100 ) ) : '';
	return '<span class="brand-logo"' . $style . '>' . $img . '</span>'; // Attributes escaped above.
}

/**
 * Inline CSS variables for the settings, printed on the brand strip.
 *
 * @return string
 */
function ace_brand_style_attr() {
	$s = ace_brand_settings();
	return sprintf( '--brand-h:%dpx;--brand-fill:%s;', $s['height'], $s['fill'] / 100 );
}

/**
 * CSS class for the strip's look.
 *
 * @return string
 */
function ace_brand_strip_class() {
	$s = ace_brand_settings();
	return $s['grey'] ? 'brands--grey' : 'brands--colour';
}

/* -------------------------------------------------------------------------
 * Customizer
 * ---------------------------------------------------------------------- */

/**
 * Customize > Brand logos.
 *
 * @param WP_Customize_Manager $wp_customize Customizer.
 */
function ace_customize_brands( $wp_customize ) {
	$wp_customize->add_section(
		'ace_brands',
		array(
			'title'       => __( 'Brand logos', '2acetech' ),
			'description' => __( 'Every brand logo is trimmed and fitted into the same slot, so they all look alike. Brands are added under Products > Brands. If one logo still looks too big or small, set its "Logo size" when you edit that brand.', '2acetech' ),
			'priority'    => 32,
		)
	);

	$wp_customize->add_setting( 'ace_brand_look', array( 'default' => 'grey', 'sanitize_callback' => 'ace_sanitize_brand_look' ) );
	$wp_customize->add_control(
		'ace_brand_look',
		array(
			'label'   => __( 'Logo colour', '2acetech' ),
			'section' => 'ace_brands',
			'type'    => 'radio',
			'choices' => array(
				'grey'   => __( 'Grey, colour on hover (calm, uniform)', '2acetech' ),
				'colour' => __( 'Full colour', '2acetech' ),
			),
		)
	);

	$wp_customize->add_setting( 'ace_brand_height', array( 'default' => 64, 'sanitize_callback' => 'absint' ) );
	$wp_customize->add_control(
		'ace_brand_height',
		array(
			'label'       => __( 'Slot height (px)', '2acetech' ),
			'description' => __( 'How tall each logo slot is. Slots are always the same size.', '2acetech' ),
			'section'     => 'ace_brands',
			'type'        => 'range',
			'input_attrs' => array( 'min' => 40, 'max' => 120, 'step' => 4 ),
		)
	);

	$wp_customize->add_setting( 'ace_brand_fill', array( 'default' => 70, 'sanitize_callback' => 'absint' ) );
	$wp_customize->add_control(
		'ace_brand_fill',
		array(
			'label'       => __( 'Logo size inside the slot (%)', '2acetech' ),
			'description' => __( 'Lower leaves more space around each logo.', '2acetech' ),
			'section'     => 'ace_brands',
			'type'        => 'range',
			'input_attrs' => array( 'min' => 40, 'max' => 100, 'step' => 5 ),
		)
	);
}
add_action( 'customize_register', 'ace_customize_brands' );

/**
 * Accept only the two known looks.
 *
 * @param string $value Submitted value.
 * @return string
 */
function ace_sanitize_brand_look( $value ) {
	return 'colour' === $value ? 'colour' : 'grey';
}

/* -------------------------------------------------------------------------
 * Products > Brands: "Logo size" field
 * ---------------------------------------------------------------------- */

/**
 * The field on the add-brand form.
 */
function ace_brand_field_add() {
	?>
	<div class="form-field">
		<label for="ace_logo_scale"><?php esc_html_e( 'Logo size (%)', '2acetech' ); ?></label>
		<input type="number" name="ace_logo_scale" id="ace_logo_scale" value="100" min="60" max="160" step="5" style="width:100px">
		<p><?php esc_html_e( '100 is standard. Raise it for a logo that looks small (a thin or light one), lower it for one that looks too big.', '2acetech' ); ?></p>
	</div>
	<?php
}

/**
 * The field on the edit-brand form.
 *
 * @param WP_Term $term Brand being edited.
 */
function ace_brand_field_edit( $term ) {
	$scale = ace_brand_scale( $term->term_id );
	?>
	<tr class="form-field">
		<th scope="row"><label for="ace_logo_scale"><?php esc_html_e( 'Logo size (%)', '2acetech' ); ?></label></th>
		<td>
			<input type="number" name="ace_logo_scale" id="ace_logo_scale" value="<?php echo esc_attr( $scale ); ?>" min="60" max="160" step="5" style="width:100px">
			<p class="description"><?php esc_html_e( '100 is standard. Raise it for a logo that looks small (a thin or light one), lower it for one that looks too big.', '2acetech' ); ?></p>
		</td>
	</tr>
	<?php
}

/**
 * Save the field.
 *
 * @param int $term_id Brand term id.
 */
function ace_brand_field_save( $term_id ) {
	// WordPress has already checked the nonce for the term form.
	if ( ! isset( $_POST['ace_logo_scale'] ) || ! current_user_can( 'manage_product_terms' ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		return;
	}
	$scale = (int) $_POST['ace_logo_scale']; // phpcs:ignore WordPress.Security.NonceVerification
	$scale = $scale ? max( 60, min( 160, $scale ) ) : 100;
	if ( 100 === $scale ) {
		delete_term_meta( $term_id, 'ace_logo_scale' );
	} else {
		update_term_meta( $term_id, 'ace_logo_scale', $scale );
	}
}

/**
 * Add the field once the brands taxonomy exists.
 */
function ace_brand_field_hooks() {
	if ( ! taxonomy_exists( 'product_brand' ) ) {
		return;
	}
	add_action( 'product_brand_add_form_fields', 'ace_brand_field_add' );
	add_action( 'product_brand_edit_form_fields', 'ace_brand_field_edit' );
	add_action( 'created_product_brand', 'ace_brand_field_save' );
	add_action( 'edited_product_brand', 'ace_brand_field_save' );
}
add_action( 'admin_init', 'ace_brand_field_hooks' );
