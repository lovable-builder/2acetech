<?php
/**
 * Legal pages: terms (regulamin) and privacy policy.
 *
 * The texts ship with the theme as HTML in /legal. The first time an
 * administrator opens wp-admin, each missing page is created as a DRAFT (never
 * published automatically, because the texts contain [bracketed] business
 * details to fill in and must be reviewed first) and assigned as the
 * WooCommerce terms page and the WordPress privacy policy page.
 *
 * The footer links to a page only once it is published.
 *
 * @package 2acetech
 */

defined( 'ABSPATH' ) || exit;

/**
 * The legal pages: key => slug, title, source file in /legal.
 *
 * @return array[]
 */
function ace_legal_defs() {
	return array(
		'terms'   => array(
			'slug'  => 'regulamin',
			'title' => __( 'Store terms and conditions', '2acetech' ),
			'file'  => 'regulamin-sklepu.html',
		),
		'privacy' => array(
			'slug'  => 'polityka-prywatnosci',
			'title' => __( 'Privacy policy', '2acetech' ),
			'file'  => 'polityka-prywatnosci.html',
		),
		'cookies'   => array(
			'slug'  => 'polityka-cookies',
			'title' => __( 'Cookie policy', '2acetech' ),
			'file'  => 'polityka-cookies.html',
		),
		'delivery'  => array(
			'slug'  => 'dostawa-i-platnosc',
			'title' => __( 'Delivery and payment', '2acetech' ),
			'file'  => 'dostawa-i-platnosc.html',
		),
		'returns'   => array(
			'slug'  => 'zwroty-i-odstapienie',
			'title' => __( 'Returns and withdrawal', '2acetech' ),
			'file'  => 'zwroty-i-odstapienie.html',
		),
		'complaints' => array(
			'slug'  => 'reklamacje',
			'title' => __( 'Complaints', '2acetech' ),
			'file'  => 'reklamacje.html',
		),
		'seller'    => array(
			'slug'  => 'dane-sprzedawcy',
			'title' => __( 'Seller details', '2acetech' ),
			'file'  => 'dane-sprzedawcy.html',
		),
	);
}

/**
 * MD5 of every text the theme seeded in earlier versions. A draft that still
 * holds one of them (or the text this version seeded) has not been edited by
 * the owner, so it is safe to replace with the current text. Anything else is
 * the owner's work and is never touched.
 *
 * @return string[]
 */
function ace_legal_old_hashes() {
	return array(
		'12bb5c5987ac12aebf2113567d42e0f3',
		'f1edfe87352c8e981252da1515e54364',
		'ed5eeadb99557f33baba01d6f9a7551d',
		'1d0c1b0d17944b25027bc6ff3c6384a6',
		'b5357bb4ba4138f4f5f1d5a5ffcd4114',
		'1f644f81c63900c63b3172e60cb21a09',
		'78e8200bd8fe69a21b920318f9942f30',
	);
}

/**
 * ID of a legal page, whichever way it was set up: the WooCommerce terms
 * setting, the WordPress privacy setting, else a page with our slug.
 *
 * @param string $key terms|privacy.
 * @return int 0 if none.
 */
function ace_legal_page_id( $key ) {
	if ( 'terms' === $key && function_exists( 'wc_terms_and_conditions_page_id' ) && (int) wc_terms_and_conditions_page_id() ) {
		return (int) wc_terms_and_conditions_page_id();
	}
	if ( 'privacy' === $key && (int) get_option( 'wp_page_for_privacy_policy' ) ) {
		return (int) get_option( 'wp_page_for_privacy_policy' );
	}
	$defs = ace_legal_defs();
	$page = isset( $defs[ $key ] ) ? get_page_by_path( $defs[ $key ]['slug'] ) : null;
	return $page ? (int) $page->ID : 0;
}

/**
 * Is this the draft privacy page WordPress creates on install, still holding
 * its tutorial text (or empty)?
 *
 * @param int $id Page ID.
 * @return bool
 */
function ace_is_stock_privacy_stub( $id ) {
	$page = get_post( $id );
	return $page && 'draft' === $page->post_status && ( '' === trim( $page->post_content ) || false !== strpos( $page->post_content, 'privacy-policy-tutorial' ) );
}

/**
 * Create the missing pages as drafts.
 */
function ace_maybe_create_legal_pages() {
	foreach ( ace_legal_defs() as $key => $def ) {
		$file = ACE_DIR . '/legal/' . $def['file'];
		if ( ! is_readable( $file ) ) {
			continue;
		}
		$text = (string) file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

		$existing = ace_legal_page_id( $key );
		if ( $existing ) {
			$page = get_post( $existing );
			if ( ! $page ) {
				continue;
			}
			// WordPress ships an untouched draft "Privacy Policy" with tutorial
			// text. Replace that, and an unedited draft of an older theme text
			// with the current one. Never touch a page the owner has edited or
			// published.
			$stub      = 'privacy' === $key && ace_is_stock_privacy_stub( $existing );
			$hash      = md5( $page->post_content );
			$unedited  = 'draft' === $page->post_status
				&& ( $hash === get_post_meta( $existing, '_ace_legal_seed', true ) || in_array( $hash, ace_legal_old_hashes(), true ) );
			if ( ( $stub || $unedited ) && $hash !== md5( $text ) ) {
				wp_update_post(
					wp_slash(
						array(
							'ID'           => $existing,
							'post_title'   => $def['title'],
							'post_name'    => $def['slug'],
							'post_content' => $text,
						)
					)
				);
				update_post_meta( $existing, '_ace_legal_seed', md5( $text ) );
			}
			continue;
		}

		$id = wp_insert_post(
			wp_slash(
				array(
					'post_type'    => 'page',
					'post_status'  => 'draft',
					'post_title'   => $def['title'],
					'post_name'    => $def['slug'],
					'post_content' => $text,
				)
			)
		);

		if ( $id && ! is_wp_error( $id ) ) {
			update_post_meta( $id, '_ace_legal_seed', md5( $text ) );
			if ( 'terms' === $key ) {
				update_option( 'woocommerce_terms_page_id', $id );
			} elseif ( 'privacy' === $key ) {
				update_option( 'wp_page_for_privacy_policy', $id );
			}
		}
	}
}
add_action( 'admin_init', 'ace_maybe_create_legal_pages' );

/**
 * Admin reminder while a legal page is still a draft.
 */
function ace_legal_notice() {
	if ( ! current_user_can( 'edit_pages' ) ) {
		return;
	}

	$drafts = array();
	foreach ( array_keys( ace_legal_defs() ) as $key ) {
		$id = ace_legal_page_id( $key );
		if ( $id && 'publish' !== get_post_status( $id ) ) {
			$drafts[] = sprintf( '<a href="%s">%s</a>', esc_url( get_edit_post_link( $id ) ), esc_html( get_the_title( $id ) ) );
		}
	}
	if ( ! $drafts ) {
		return;
	}

	printf(
		'<div class="notice notice-warning"><p><strong>%1$s</strong> %2$s %3$s</p></div>',
		esc_html__( 'Legal pages are waiting:', '2acetech' ),
		wp_kses_post( implode( ', ', $drafts ) ),
		esc_html__( 'Fill in every [bracketed] detail, have a lawyer review the text, then publish. They appear in the footer once published. Do not take real orders before then.', '2acetech' )
	);
}
add_action( 'admin_notices', 'ace_legal_notice' );

/**
 * Footer links: the Regulamin and its delivery, returns and complaints
 * sections, the privacy policy and its cookie section, and the withdrawal
 * function. Only pages that are published. Section links are anchors into the
 * two texts (see ace_legal_anchors), so there is one source of truth.
 *
 * @return string[] Anchor elements.
 */
function ace_footer_legal_links() {
	$links = array();

	/**
	 * Is the page published? Returns its URL or ''.
	 *
	 * @param string $key Key from ace_legal_defs().
	 * @return string
	 */
	$url = static function ( $key ) {
		$id = ace_legal_page_id( $key );
		return ( $id && 'publish' === get_post_status( $id ) ) ? get_permalink( $id ) : '';
	};

	$terms = $url( 'terms' );
	$rows  = array(
		array( 'terms', '', '' ),
		array( 'privacy', '', '' ),
		array( 'cookies', '', '#sec-6' ),
		array( 'delivery', __( 'Delivery and payment', '2acetech' ), '#par-5' ),
		array( 'returns', __( 'Returns', '2acetech' ), '#par-6' ),
		array( 'complaints', __( 'Complaints', '2acetech' ), '#par-8' ),
		array( 'seller', '', '' ),
	);
	foreach ( $rows as $row ) {
		list( $key, $label, $anchor ) = $row;
		$id   = ace_legal_page_id( $key );
		$href = $url( $key );
		if ( '' !== $href ) {
			$title = '' !== $label ? $label : get_the_title( $id );
			$links[] = sprintf( '<a href="%s">%s</a>', esc_url( $href ), esc_html( $title ) );
		} elseif ( 'cookies' === $key && $url( 'privacy' ) ) {
			// No cookie page yet: the privacy policy has a cookie section.
			$links[] = sprintf( '<a href="%s">%s</a>', esc_url( $url( 'privacy' ) . '#sec-6' ), esc_html__( 'Cookie policy', '2acetech' ) );
		} elseif ( '' !== $terms && in_array( $key, array( 'delivery', 'returns', 'complaints' ), true ) ) {
			// No own page yet: the section of the Regulamin.
			$links[] = sprintf( '<a href="%s">%s</a>', esc_url( $terms . $anchor ), esc_html( $label ) );
		}
	}

	$withdraw = ace_withdrawal_link( '', 'withdraw-link' );
	if ( '' !== $withdraw ) {
		$links[] = $withdraw;
	}

	// Reopens the cookie settings (inc/consent.php).
	$links[] = sprintf( '<button type="button" class="cookie-link" data-cookie-open>%s</button>', esc_html__( 'Cookie settings', '2acetech' ) );

	return $links;
}

/**
 * Give the section headings of the two legal texts anchors, whatever is stored
 * in the page: "§ 5." becomes #par-5, annexes #zalacznik-1, privacy "6." #sec-6.
 *
 * @param string $content Page content.
 * @return string
 */
function ace_legal_anchors( $content ) {
	if ( ! is_page() || ! in_the_loop() ) {
		return $content;
	}
	$id = get_the_ID();
	if ( ace_legal_page_id( 'terms' ) === $id ) {
		$content = preg_replace( '/<h2>(§ (\d+)\.)/u', '<h2 id="par-$2">$1', $content );
		$content = preg_replace( '/<h2>(Załącznik nr (\d+)\.)/u', '<h2 id="zalacznik-$2">$1', $content );
	} elseif ( ace_legal_page_id( 'privacy' ) === $id ) {
		$content = preg_replace( '/<h2>((\d+)\.)/u', '<h2 id="sec-$2">$1', $content );
	}
	return $content;
}
add_filter( 'the_content', 'ace_legal_anchors' );

/**
 * Checkout (block): a required tick box for the Regulamin.
 *
 * The consumer must be able to read the terms before ordering and the shop must
 * be able to prove they were made available (art. 8 e-services act, art. 384 KC).
 * The "Terms and conditions" block of the checkout has a "require checkbox"
 * option; this sets it, and the text with links, as long as the Regulamin is
 * published (a tick box for a page that does not exist would be worse than none).
 *
 * @param array $block Parsed block.
 * @return array
 */
function ace_checkout_terms_block( $block ) {
	if ( empty( $block['blockName'] ) || 'woocommerce/checkout-terms-block' !== $block['blockName'] ) {
		return $block;
	}
	$terms = ace_legal_page_id( 'terms' );
	if ( ! $terms || 'publish' !== get_post_status( $terms ) ) {
		return $block;
	}
	$privacy = ace_legal_page_id( 'privacy' );
	$link    = static function ( $id, $label ) {
		return sprintf( '<a href="%s" target="_blank" rel="noopener">%s</a>', esc_url( get_permalink( $id ) ), esc_html( $label ) );
	};

	if ( $privacy && 'publish' === get_post_status( $privacy ) ) {
		$text = sprintf(
			/* translators: 1: link "Terms and conditions", 2: link "Privacy policy". */
			__( 'I accept the %1$s and confirm that I have read it. How we process your data: %2$s.', '2acetech' ),
			$link( $terms, __( 'Terms and conditions', '2acetech' ) ),
			$link( $privacy, __( 'Privacy policy', '2acetech' ) )
		);
	} else {
		$text = sprintf(
			/* translators: %s: link "Terms and conditions". */
			__( 'I accept the %s and confirm that I have read it.', '2acetech' ),
			$link( $terms, __( 'Terms and conditions', '2acetech' ) )
		);
	}

	if ( ! isset( $block['attrs'] ) || ! is_array( $block['attrs'] ) ) {
		$block['attrs'] = array();
	}
	$block['attrs']['checkbox'] = true;
	$block['attrs']['text']     = $text;
	return $block;
}
add_filter( 'render_block_data', 'ace_checkout_terms_block' );

/**
 * Review avatars would send a hash of the reviewer's email to Gravatar
 * (Automattic). The privacy policy assumes that does not happen.
 */
add_filter( 'pre_option_show_avatars', '__return_zero' );

/* Titles of the pages the theme creates ------------------------------------ */

/**
 * The pages the theme creates are saved with an English title when WordPress
 * creates them (admin language). Translate them when shown, as long as the
 * owner has not renamed them.
 *
 * @return array[] slug => array( English title, translated title ).
 */
function ace_auto_page_titles() {
	return array(
		ACE_WITHDRAWAL_SLUG    => array( 'Withdraw from contract here', __( 'Withdraw from contract here', '2acetech' ) ),
		'regulamin'            => array( 'Store terms and conditions', __( 'Store terms and conditions', '2acetech' ) ),
		'polityka-prywatnosci' => array( 'Privacy policy', __( 'Privacy policy', '2acetech' ) ),
		'polityka-cookies'     => array( 'Cookie policy', __( 'Cookie policy', '2acetech' ) ),
		'dostawa-i-platnosc'   => array( 'Delivery and payment', __( 'Delivery and payment', '2acetech' ) ),
		'zwroty-i-odstapienie' => array( 'Returns and withdrawal', __( 'Returns and withdrawal', '2acetech' ) ),
		'reklamacje'           => array( 'Complaints', __( 'Complaints', '2acetech' ) ),
		'dane-sprzedawcy'      => array( 'Seller details', __( 'Seller details', '2acetech' ) ),
	);
}

/**
 * @param string      $title   Title.
 * @param int|WP_Post $post_id Post.
 * @return string
 */
function ace_translate_auto_title( $title, $post_id ) {
	$post = get_post( $post_id );
	if ( ! $post || 'page' !== $post->post_type ) {
		return $title;
	}
	$map = ace_auto_page_titles();
	if ( isset( $map[ $post->post_name ] ) && $title === $map[ $post->post_name ][0] ) {
		return $map[ $post->post_name ][1];
	}
	return $title;
}
add_filter( 'the_title', 'ace_translate_auto_title', 10, 2 );
add_filter( 'single_post_title', 'ace_translate_auto_title', 10, 2 );

/**
 * Order confirmation e-mails: the full Regulamin in the message body.
 *
 * The seller must confirm the contract and the pre-contract information on a
 * durable medium (art. 15 and 21 consumer rights act). An e-mail that contains
 * the text of the Regulamin, with the withdrawal information and the model
 * form it holds, does that without a PDF attachment. Only for the e-mails sent
 * when the order is received, and only while the Regulamin page is published.
 *
 * @param WC_Order $order         Order.
 * @param bool     $sent_to_admin Whether the e-mail goes to the shop owner.
 * @param bool     $plain_text    Plain text e-mail.
 * @param WC_Email $email         E-mail object.
 */
function ace_email_regulamin( $order, $sent_to_admin, $plain_text, $email = null ) {
	if ( $sent_to_admin || ! is_object( $email ) || ! in_array( $email->id, array( 'customer_processing_order', 'customer_on_hold_order' ), true ) ) {
		return;
	}
	$id   = ace_legal_page_id( 'terms' );
	$page = $id ? get_post( $id ) : null;
	if ( ! $page || 'publish' !== $page->post_status ) {
		return;
	}
	$title = 'Regulamin sklepu';
	if ( $plain_text ) {
		echo "\n\n==========\n" . esc_html( $title ) . "\n==========\n\n";
		echo esc_html( html_entity_decode( wp_strip_all_tags( preg_replace( '#</(p|li|h[1-6]|tr)>#i', "\n", $page->post_content ) ) ) ) . "\n";
		return;
	}
	echo '<h2 style="margin:32px 0 12px">' . esc_html( $title ) . '</h2>';
	echo '<div style="font-size:12px;line-height:1.5">' . wp_kses_post( wpautop( $page->post_content ) ) . '</div>';
}
add_action( 'woocommerce_email_after_order_table', 'ace_email_regulamin', 30, 4 );
