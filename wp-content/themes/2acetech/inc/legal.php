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

		$existing = ace_legal_page_id( $key );
		if ( $existing ) {
			// WordPress ships an untouched draft "Privacy Policy" with tutorial
			// text. Replace that, and never touch a page the owner has edited.
			if ( 'privacy' === $key && ace_is_stock_privacy_stub( $existing ) ) {
				wp_update_post(
					wp_slash(
						array(
							'ID'           => $existing,
							'post_title'   => $def['title'],
							'post_name'    => $def['slug'],
							'post_content' => (string) file_get_contents( $file ), // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
						)
					)
				);
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
					'post_content' => (string) file_get_contents( $file ), // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
				)
			)
		);

		if ( $id && ! is_wp_error( $id ) ) {
			if ( 'terms' === $key ) {
				update_option( 'woocommerce_terms_page_id', $id );
			} else {
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
	$map   = array(
		'terms'   => array(
			array( '', '' ),
			array( '#par-5', __( 'Delivery and payment', '2acetech' ) ),
			array( '#par-6', __( 'Returns', '2acetech' ) ),
			array( '#par-8', __( 'Complaints', '2acetech' ) ),
		),
		'privacy' => array(
			array( '', '' ),
			array( '#sec-6', __( 'Cookie policy', '2acetech' ) ),
		),
	);

	foreach ( $map as $key => $rows ) {
		$id = ace_legal_page_id( $key );
		if ( ! $id || 'publish' !== get_post_status( $id ) ) {
			continue;
		}
		foreach ( $rows as $row ) {
			$links[] = sprintf(
				'<a href="%s">%s</a>',
				esc_url( get_permalink( $id ) . $row[0] ),
				esc_html( '' === $row[1] ? get_the_title( $id ) : $row[1] )
			);
		}
	}

	$withdraw = ace_withdrawal_link( '', 'withdraw-link' );
	if ( '' !== $withdraw ) {
		$links[] = $withdraw;
	}

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
 * Review avatars would send a hash of the reviewer's email to Gravatar
 * (Automattic). The privacy policy assumes that does not happen.
 */
add_filter( 'pre_option_show_avatars', '__return_zero' );
