<?php
/**
 * Occasion banner: the big banner at the top of the homepage (it works as the
 * hero). Up to three banners are prepared in Customize > Landing page; each has
 * a look (Halloween, Christmas, New Year ... or your own colours), text and
 * buttons, an optional countdown, and on the right a picture or a carousel of
 * products. Each can have "show from" and "show until" dates, so the right one
 * switches itself on and off. A banner with dates beats an always-on one.
 *
 * @package 2acetech
 */

defined( 'ABSPATH' ) || exit;

const ACE_BANNERS = 3;

/* -------------------------------------------------------------------------
 * Looks
 * ---------------------------------------------------------------------- */

/**
 * Ready-made looks. tone = colour of the text on the banner.
 *
 * @return array[] key => label, b1, b2 (background gradient), accent, tone, btn (text colour on the accent button), decor (emojis), motion (fall|float).
 */
function ace_banner_presets() {
	return array(
		'default'     => array( 'label' => __( 'Standard (dark and red)', '2acetech' ), 'b1' => '#0b0b0d', 'b2' => '#2a0a0d', 'accent' => '#f50505', 'tone' => 'light', 'btn' => '#ffffff', 'decor' => array(), 'motion' => 'float' ),
		'halloween'   => array( 'label' => __( 'Halloween', '2acetech' ), 'b1' => '#10051a', 'b2' => '#3a0d4d', 'accent' => '#ff7a00', 'tone' => 'light', 'btn' => '#1a0a00', 'decor' => array( '🎃', '🦇', '👻', '🕸️', '🎃', '🦇', '👻', '🎃' ), 'motion' => 'float' ),
		'christmas'   => array( 'label' => __( 'Christmas', '2acetech' ), 'b1' => '#0a3b2c', 'b2' => '#8a1020', 'accent' => '#ffd24a', 'tone' => 'light', 'btn' => '#3a0a10', 'decor' => array( '❄️', '🎄', '⭐', '❄️', '🎁', '❄️', '⭐', '❄️' ), 'motion' => 'fall' ),
		'newyear'     => array( 'label' => __( 'New Year', '2acetech' ), 'b1' => '#070d2b', 'b2' => '#1d2a63', 'accent' => '#f5c542', 'tone' => 'light', 'btn' => '#0a1230', 'decor' => array( '✨', '🎆', '🥂', '✨', '🎉', '✨', '🎆', '✨' ), 'motion' => 'float' ),
		'blackfriday' => array( 'label' => __( 'Black Friday', '2acetech' ), 'b1' => '#000000', 'b2' => '#1c1c1c', 'accent' => '#ffe600', 'tone' => 'light', 'btn' => '#000000', 'decor' => array(), 'motion' => 'float' ),
		'valentine'   => array( 'label' => __( "Valentine's Day", '2acetech' ), 'b1' => '#3a0716', 'b2' => '#b0155a', 'accent' => '#ff8fb8', 'tone' => 'light', 'btn' => '#3a0716', 'decor' => array( '❤️', '💕', '💗', '🌹', '💕', '❤️', '💗', '💕' ), 'motion' => 'float' ),
		'sale'        => array( 'label' => __( 'Summer sale (light)', '2acetech' ), 'b1' => '#fff3e0', 'b2' => '#ffd9a8', 'accent' => '#e8470c', 'tone' => 'dark', 'btn' => '#ffffff', 'decor' => array( '☀️', '🏷️', '☀️', '🏷️' ), 'motion' => 'float' ),
		'custom'      => array( 'label' => __( 'My own colours', '2acetech' ), 'b1' => '#0b0b0d', 'b2' => '#2a0a0d', 'accent' => '#f50505', 'tone' => 'light', 'btn' => '#ffffff', 'decor' => array(), 'motion' => 'float' ),
	);
}

/**
 * Choices for the look drop-down.
 *
 * @return string[]
 */
function ace_banner_style_choices() {
	$out = array();
	foreach ( ace_banner_presets() as $key => $p ) {
		$out[ $key ] = $p['label'];
	}
	return $out;
}

/** Text colour (black or white) that reads best on a given hex background. */
function ace_banner_contrast( $hex ) {
	$hex = ltrim( (string) $hex, '#' );
	if ( 3 === strlen( $hex ) ) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}
	if ( 6 !== strlen( $hex ) || ! ctype_xdigit( $hex ) ) {
		return '#ffffff';
	}
	$r = hexdec( substr( $hex, 0, 2 ) );
	$g = hexdec( substr( $hex, 2, 2 ) );
	$b = hexdec( substr( $hex, 4, 2 ) );
	return ( 0.299 * $r + 0.587 * $g + 0.114 * $b ) > 150 ? '#111111' : '#ffffff';
}

/* -------------------------------------------------------------------------
 * Banner data
 * ---------------------------------------------------------------------- */

/**
 * Starting content for banner 1..3 (translatable; edit in the Customizer).
 *
 * @param int $n Banner number.
 * @return array
 */
function ace_banner_defaults( $n ) {
	$all = array(
		1 => array(
			'on'        => true,
			'style'     => 'default',
			'eyebrow'   => __( 'In our shop', '2acetech' ),
			'title'     => __( 'Discover our range', '2acetech' ),
			'text'      => '',
			'cta'       => __( 'Shop now', '2acetech' ),
			'source'    => 'mix',
			'countdown' => false,
		),
		2 => array(
			'on'        => false,
			'style'     => 'halloween',
			'eyebrow'   => __( 'Halloween', '2acetech' ),
			'title'     => __( 'Spooky deals are here', '2acetech' ),
			'text'      => __( 'Treat yourself before the night is over.', '2acetech' ),
			'cta'       => __( 'Shop the deals', '2acetech' ),
			'source'    => 'sale',
			'countdown' => true,
		),
		3 => array(
			'on'        => false,
			'style'     => 'christmas',
			'eyebrow'   => __( 'Christmas', '2acetech' ),
			'title'     => __( 'Gifts they will love', '2acetech' ),
			'text'      => __( 'Find the perfect present for everyone on your list.', '2acetech' ),
			'cta'       => __( 'Find a gift', '2acetech' ),
			'source'    => 'featured',
			'countdown' => true,
		),
	);
	return $all[ $n ];
}

/**
 * Read one banner from the Customizer.
 *
 * @param int $n Banner number 1..3.
 * @return array
 */
function ace_banner_get( $n ) {
	$d = ace_banner_defaults( $n );
	$m = function ( $key, $default ) use ( $n ) {
		return get_theme_mod( "ace_bn{$n}_{$key}", $default );
	};
	return array(
		'n'         => $n,
		'on'        => (bool) $m( 'on', $d['on'] ),
		'style'     => (string) $m( 'style', $d['style'] ),
		'c1'        => (string) $m( 'c1', '#0b0b0d' ),
		'c2'        => (string) $m( 'c2', '#2a0a0d' ),
		'accent'    => (string) $m( 'accent', '#f50505' ),
		'tone'      => (string) $m( 'tone', 'light' ),
		'decor'     => (bool) $m( 'decor', true ),
		'eyebrow'   => (string) $m( 'eyebrow', $d['eyebrow'] ),
		'title'     => (string) $m( 'title', $d['title'] ),
		'text'      => (string) $m( 'text', $d['text'] ),
		'cta'       => (string) $m( 'cta', $d['cta'] ),
		'url'       => (string) $m( 'url', '' ),
		'cta2'      => (string) $m( 'cta2', '' ),
		'url2'      => (string) $m( 'url2', '' ),
		'media'     => (string) $m( 'media', 'products' ),
		'image'     => (int) $m( 'image', 0 ),
		'source'    => (string) $m( 'source', $d['source'] ),
		'cat'       => (string) $m( 'cat', '' ),
		'ids'       => (string) $m( 'ids', '' ),
		'count'     => (int) $m( 'count', 6 ),
		'countdown' => (bool) $m( 'countdown', $d['countdown'] ),
		'end'       => (string) $m( 'end', '' ),
		'cd_label'  => (string) $m( 'cd_label', __( 'Offer ends in:', '2acetech' ) ),
		'ended'     => (string) $m( 'ended', __( 'This offer has ended.', '2acetech' ) ),
		'from'      => (string) $m( 'from', '' ),
		'until'     => (string) $m( 'until', '' ),
		'size'      => (string) $m( 'size', 'normal' ),
	);
}

/**
 * "2026-12-24T18:00" (site time zone) to a Unix timestamp, or 0.
 *
 * @param string $value Value from a datetime-local field.
 * @return int
 */
function ace_banner_ts( $value ) {
	if ( ! $value ) {
		return 0;
	}
	$dt = date_create_immutable_from_format( 'Y-m-d\TH:i', $value, wp_timezone() );
	return $dt ? $dt->getTimestamp() : 0;
}

/** Is this banner inside its show-from / show-until window right now? */
function ace_banner_in_window( $b ) {
	$now  = time();
	$from = ace_banner_ts( $b['from'] );
	$to   = ace_banner_ts( $b['until'] );
	return ( ! $from || $from <= $now ) && ( ! $to || $to > $now );
}

/**
 * The banner to show now, or null. Among the enabled banners that are inside
 * their window, one with dates beats an always-on one; ties go to the lowest number.
 *
 * @param bool $fresh Ignore the remembered result (tests).
 * @return array|null
 */
function ace_active_banner( $fresh = false ) {
	static $cache = false;
	if ( ! $fresh && false !== $cache ) {
		return $cache;
	}
	$best  = null;
	$score = -1;
	for ( $n = 1; $n <= ACE_BANNERS; $n++ ) {
		$b = ace_banner_get( $n );
		if ( ! $b['on'] || ! ace_banner_in_window( $b ) ) {
			continue;
		}
		$s = ( $b['from'] || $b['until'] ) ? 1 : 0;
		if ( $s > $score ) {
			$best  = $b;
			$score = $s;
		}
	}
	/** Filter: ace_active_banner. */
	$cache = apply_filters( 'ace_active_banner', $best );
	return $cache;
}

/**
 * Inline CSS variables and the decor list for a banner.
 *
 * @param array $b Banner.
 * @return array vars (string), decor (string[]), motion (string), preset (key).
 */
function ace_banner_look( $b ) {
	$presets = ace_banner_presets();
	$key     = isset( $presets[ $b['style'] ] ) ? $b['style'] : 'default';
	$p       = $presets[ $key ];
	if ( 'custom' === $key ) {
		$c1     = sanitize_hex_color( $b['c1'] ) ? $b['c1'] : $p['b1'];
		$c2     = sanitize_hex_color( $b['c2'] ) ? $b['c2'] : $p['b2'];
		$accent = sanitize_hex_color( $b['accent'] ) ? $b['accent'] : $p['accent'];
		$tone   = 'dark' === $b['tone'] ? 'dark' : 'light';
		$btn    = ace_banner_contrast( $accent );
		$decor  = array();
	} else {
		$c1     = $p['b1'];
		$c2     = $p['b2'];
		$accent = $p['accent'];
		$tone   = $p['tone'];
		$btn    = $p['btn'];
		$decor  = $b['decor'] ? $p['decor'] : array();
	}
	$text = 'dark' === $tone ? '#14141a' : '#ffffff';
	return array(
		'vars'   => sprintf( '--b1:%s;--b2:%s;--b-accent:%s;--b-text:%s;--b-btn:%s;', $c1, $c2, $accent, $text, $btn ),
		'decor'  => $decor,
		'motion' => $p['motion'],
		'tone'   => $tone,
		'preset' => $key,
	);
}

/**
 * The products shown in the banner carousel.
 *
 * @param array $b Banner.
 * @return array WC_Product objects (or demo items).
 */
function ace_banner_products( $b ) {
	$limit = max( 3, min( 10, (int) $b['count'] ) );
	$out   = array();

	if ( 'ids' === $b['source'] && function_exists( 'wc_get_product' ) ) {
		foreach ( preg_split( '/[\s,;]+/', (string) $b['ids'], -1, PREG_SPLIT_NO_EMPTY ) as $token ) {
			$id = ctype_digit( $token ) ? (int) $token : (int) wc_get_product_id_by_sku( $token );
			$p  = $id ? wc_get_product( $id ) : null;
			if ( $p && 'publish' === $p->get_status() ) {
				$out[] = $p;
			}
			if ( count( $out ) >= $limit ) {
				break;
			}
		}
	} elseif ( 'category' === $b['source'] ) {
		$out = ace_get_products( 'latest', $limit, sanitize_title( $b['cat'] ) );
	} else {
		$type = in_array( $b['source'], array( 'mix', 'latest', 'featured', 'sale', 'best' ), true ) ? $b['source'] : 'mix';
		$out  = ace_get_products( $type, $limit );
	}

	// A wrong category slug or product list must not leave the banner with an empty side.
	if ( ! $out ) {
		$out = ace_get_products( 'mix', $limit );
	}
	return $out;
}

/** Wrap the last word of a headline in <em> (shown in the accent colour). */
function ace_banner_title_html( $title ) {
	$title = trim( $title );
	$pos   = strrpos( $title, ' ' );
	if ( false === $pos ) {
		return '<em>' . esc_html( $title ) . '</em>';
	}
	return esc_html( substr( $title, 0, $pos ) ) . ' <em>' . esc_html( substr( $title, $pos + 1 ) ) . '</em>';
}

/* -------------------------------------------------------------------------
 * Customizer
 * ---------------------------------------------------------------------- */

function ace_sanitize_banner_style( $v ) {
	return isset( ace_banner_presets()[ $v ] ) ? $v : 'default';
}
function ace_sanitize_banner_tone( $v ) {
	return 'dark' === $v ? 'dark' : 'light';
}
function ace_sanitize_banner_media( $v ) {
	return in_array( $v, array( 'none', 'image', 'products' ), true ) ? $v : 'products';
}
function ace_sanitize_banner_source( $v ) {
	return in_array( $v, array( 'mix', 'latest', 'featured', 'sale', 'best', 'category', 'ids' ), true ) ? $v : 'mix';
}
function ace_sanitize_banner_size( $v ) {
	return in_array( $v, array( 'compact', 'normal', 'tall' ), true ) ? $v : 'normal';
}
function ace_sanitize_banner_count( $v ) {
	return max( 3, min( 10, (int) $v ) );
}
function ace_sanitize_banner_datetime( $v ) {
	return preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', (string) $v ) ? $v : '';
}

/**
 * Customize > Landing page > Occasion banner 1..3.
 *
 * @param WP_Customize_Manager $wp Customizer.
 */
function ace_customize_banners( $wp ) {
	for ( $n = 1; $n <= ACE_BANNERS; $n++ ) {
		$d   = ace_banner_defaults( $n );
		$sec = "ace_banner_{$n}";
		$wp->add_section(
			$sec,
			array(
				/* translators: %d: banner number. */
				'title'       => sprintf( __( 'Banner %d (top of the homepage)', '2acetech' ), $n ),
				'description' => 1 === $n
					? __( 'The big banner at the top of the homepage. Prepare up to three (for example the normal one, Halloween and Christmas). Give a banner "Show from" and "Show until" dates and it switches itself on and off; a banner with dates beats an always-on one.', '2acetech' )
					: '',
				'panel'       => 'ace_home',
				'priority'    => 0,
			)
		);

		ace_cz_field( $wp, $sec, "ace_bn{$n}_on", __( 'Show this banner', '2acetech' ), $d['on'], array( 'type' => 'checkbox' ) );
		ace_cz_field( $wp, $sec, "ace_bn{$n}_style", __( 'Look', '2acetech' ), $d['style'], array( 'type' => 'select', 'choices' => ace_banner_style_choices(), 'sanitize' => 'ace_sanitize_banner_style' ) );
		ace_cz_field( $wp, $sec, "ace_bn{$n}_decor", __( 'Festive decorations (floating emojis)', '2acetech' ), true, array( 'type' => 'checkbox', 'description' => __( 'Pumpkins, snowflakes, sparkles ... depending on the look. Not used with "My own colours".', '2acetech' ) ) );

		// Own colours.
		foreach ( array( 'c1' => array( __( 'My colours: background start', '2acetech' ), '#0b0b0d' ), 'c2' => array( __( 'My colours: background end', '2acetech' ), '#2a0a0d' ), 'accent' => array( __( 'My colours: accent (button, last word)', '2acetech' ), '#f50505' ) ) as $k => $info ) {
			$wp->add_setting( "ace_bn{$n}_{$k}", array( 'default' => $info[1], 'sanitize_callback' => 'sanitize_hex_color' ) );
			$wp->add_control( new WP_Customize_Color_Control( $wp, "ace_bn{$n}_{$k}", array( 'label' => $info[0], 'section' => $sec ) ) );
		}
		ace_cz_field( $wp, $sec, "ace_bn{$n}_tone", __( 'My colours: text', '2acetech' ), 'light', array( 'type' => 'select', 'choices' => array( 'light' => __( 'White text (dark background)', '2acetech' ), 'dark' => __( 'Dark text (light background)', '2acetech' ) ), 'sanitize' => 'ace_sanitize_banner_tone' ) );

		// Words.
		ace_cz_field( $wp, $sec, "ace_bn{$n}_eyebrow", __( 'Small label', '2acetech' ), $d['eyebrow'] );
		ace_cz_field( $wp, $sec, "ace_bn{$n}_title", __( 'Headline', '2acetech' ), $d['title'], array( 'description' => __( 'The last word is shown in the accent colour.', '2acetech' ) ) );
		ace_cz_field( $wp, $sec, "ace_bn{$n}_text", __( 'Text', '2acetech' ), $d['text'], array( 'type' => 'textarea' ) );
		ace_cz_field( $wp, $sec, "ace_bn{$n}_cta", __( 'Button text', '2acetech' ), $d['cta'], array( 'description' => __( 'Leave empty for no button.', '2acetech' ) ) );
		ace_cz_field( $wp, $sec, "ace_bn{$n}_url", __( 'Button link', '2acetech' ), '', array( 'type' => 'url', 'description' => __( 'Empty = the shop page.', '2acetech' ) ) );
		ace_cz_field( $wp, $sec, "ace_bn{$n}_cta2", __( 'Second button text (optional)', '2acetech' ), '' );
		ace_cz_field( $wp, $sec, "ace_bn{$n}_url2", __( 'Second button link', '2acetech' ), '', array( 'type' => 'url' ) );

		// Right side.
		ace_cz_field( $wp, $sec, "ace_bn{$n}_media", __( 'Right side shows', '2acetech' ), 'products', array( 'type' => 'select', 'choices' => array( 'products' => __( 'A carousel of products', '2acetech' ), 'image' => __( 'A picture', '2acetech' ), 'none' => __( 'Nothing (centred text)', '2acetech' ) ), 'sanitize' => 'ace_sanitize_banner_media' ) );
		ace_cz_image( $wp, $sec, "ace_bn{$n}_image", __( 'Picture', '2acetech' ), __( 'Used when the right side shows "A picture". A transparent PNG works best.', '2acetech' ) );
		ace_cz_field(
			$wp,
			$sec,
			"ace_bn{$n}_source",
			__( 'Products to show', '2acetech' ),
			$d['source'],
			array(
				'type'     => 'select',
				'choices'  => array(
					'mix'      => __( 'A mix from every category (recommended)', '2acetech' ),
					'latest'   => __( 'Newest products', '2acetech' ),
					'featured' => __( 'Featured products (starred in WooCommerce)', '2acetech' ),
					'sale'     => __( 'Products on sale', '2acetech' ),
					'best'     => __( 'Bestsellers', '2acetech' ),
					'category' => __( 'One category (type its slug below)', '2acetech' ),
					'ids'      => __( 'Products I pick (IDs or SKUs below)', '2acetech' ),
				),
				'sanitize' => 'ace_sanitize_banner_source',
			)
		);
		ace_cz_field( $wp, $sec, "ace_bn{$n}_cat", __( 'Category slug', '2acetech' ), '', array( 'description' => __( 'For example: swiateczne-dekoracje. You find the slug under Products > Categories.', '2acetech' ) ) );
		ace_cz_field( $wp, $sec, "ace_bn{$n}_ids", __( 'Product IDs or SKUs', '2acetech' ), '', array( 'description' => __( 'Separated by commas, in the order you want them, for example: 592, 2AC-0003, 675.', '2acetech' ) ) );
		ace_cz_field( $wp, $sec, "ace_bn{$n}_count", __( 'How many products (3 to 10)', '2acetech' ), 6, array( 'type' => 'number', 'sanitize' => 'ace_sanitize_banner_count' ) );

		// Countdown.
		ace_cz_field( $wp, $sec, "ace_bn{$n}_countdown", __( 'Show a countdown', '2acetech' ), $d['countdown'], array( 'type' => 'checkbox' ) );
		ace_cz_field( $wp, $sec, "ace_bn{$n}_end", __( 'Countdown ends on', '2acetech' ), '', array( 'type' => 'datetime-local', 'sanitize' => 'ace_sanitize_banner_datetime', 'description' => __( 'Date and time in your site time zone. No countdown is shown while this is empty.', '2acetech' ) ) );
		ace_cz_field( $wp, $sec, "ace_bn{$n}_cd_label", __( 'Text above the countdown', '2acetech' ), __( 'Offer ends in:', '2acetech' ) );
		ace_cz_field( $wp, $sec, "ace_bn{$n}_ended", __( 'Text when the countdown is over', '2acetech' ), __( 'This offer has ended.', '2acetech' ), array( 'description' => __( 'Shown instead of the countdown. To remove the whole banner at that moment, also set "Show until".', '2acetech' ) ) );

		// Schedule + size.
		ace_cz_field( $wp, $sec, "ace_bn{$n}_from", __( 'Show from (optional)', '2acetech' ), '', array( 'type' => 'datetime-local', 'sanitize' => 'ace_sanitize_banner_datetime', 'description' => __( 'Empty = from now on.', '2acetech' ) ) );
		ace_cz_field( $wp, $sec, "ace_bn{$n}_until", __( 'Show until (optional)', '2acetech' ), '', array( 'type' => 'datetime-local', 'sanitize' => 'ace_sanitize_banner_datetime', 'description' => __( 'Empty = no end. After this moment the banner disappears by itself.', '2acetech' ) ) );
		ace_cz_field( $wp, $sec, "ace_bn{$n}_size", __( 'Height', '2acetech' ), 'normal', array( 'type' => 'select', 'choices' => array( 'compact' => __( 'Compact', '2acetech' ), 'normal' => __( 'Normal', '2acetech' ), 'tall' => __( 'Tall', '2acetech' ) ), 'sanitize' => 'ace_sanitize_banner_size' ) );
	}
}
add_action( 'customize_register', 'ace_customize_banners', 20 );
