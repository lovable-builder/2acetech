<?php
/**
 * Favicon, app icons and link-sharing tags (Open Graph, X/Twitter card).
 *
 * - Icons: a site icon set in Customize > Site Identity wins; otherwise the
 *   icons bundled in assets/img are used.
 * - Share tags: skipped when an SEO plugin (Yoast, Rank Math, AIOSEO, SEOPress)
 *   is active, because they print their own and duplicates confuse scrapers.
 *
 * @package 2acetech
 */

defined( 'ABSPATH' ) || exit;

/**
 * Is a plugin already handling Open Graph / meta description?
 *
 * @return bool
 */
function ace_seo_plugin_active() {
	return defined( 'WPSEO_VERSION' )
		|| class_exists( 'RankMath' )
		|| defined( 'AIOSEO_VERSION' )
		|| defined( 'SEOPRESS_VERSION' )
		|| apply_filters( 'ace_seo_plugin_active', false );
}

/**
 * Favicon and app icons, unless the Customizer site icon is set.
 */
function ace_icons() {
	if ( has_site_icon() ) {
		return; // WordPress prints its own icon tags.
	}
	$img = ACE_URI . '/assets/img/';
	printf( '<link rel="icon" type="image/png" sizes="32x32" href="%s">' . "\n", esc_url( $img . 'favicon-32.png' ) );
	printf( '<link rel="icon" type="image/png" sizes="48x48" href="%s">' . "\n", esc_url( $img . 'favicon-48.png' ) );
	printf( '<link rel="icon" type="image/png" sizes="192x192" href="%s">' . "\n", esc_url( $img . 'icon-192.png' ) );
	printf( '<link rel="apple-touch-icon" href="%s">' . "\n", esc_url( $img . 'apple-touch-icon.png' ) );
}
add_action( 'wp_head', 'ace_icons', 2 );

/**
 * Share image for the current request: product or post image, else the
 * Customizer share image, else the bundled one.
 *
 * @return array { url, width, height, bundled }
 */
function ace_share_image() {
	if ( is_singular() && has_post_thumbnail() ) {
		$src = wp_get_attachment_image_src( get_post_thumbnail_id(), 'large' );
		if ( $src ) {
			return array( 'url' => $src[0], 'width' => (int) $src[1], 'height' => (int) $src[2] );
		}
	}

	$custom = (int) get_theme_mod( 'ace_og_image' );
	if ( $custom ) {
		$src = wp_get_attachment_image_src( $custom, 'full' );
		if ( $src ) {
			return array( 'url' => $src[0], 'width' => (int) $src[1], 'height' => (int) $src[2] );
		}
	}

	return array( 'url' => ACE_URI . '/assets/img/og-default.png', 'width' => 1200, 'height' => 630 );
}

/**
 * Description for the current request.
 *
 * @return string
 */
function ace_meta_description() {
	$desc = '';
	if ( is_singular() ) {
		$post = get_queried_object();
		if ( $post instanceof WP_Post ) {
			$desc = has_excerpt( $post ) ? $post->post_excerpt : $post->post_content;
		}
	} elseif ( is_category() || is_tag() || is_tax() ) {
		$desc = term_description();
	}
	$desc = trim( wp_strip_all_tags( strip_shortcodes( (string) $desc ) ) );
	$desc = preg_replace( '/\s+/', ' ', $desc );

	if ( '' === $desc ) {
		$desc = get_bloginfo( 'description' );
		// A fresh WordPress install ships with this placeholder tagline.
		if ( false !== stripos( $desc, 'just another wordpress site' ) ) {
			$desc = '';
		}
	}
	if ( '' === $desc ) {
		$desc = __( 'Premium electronics, delivered fast across Poland.', '2acetech' );
	}
	return wp_html_excerpt( $desc, 160, '…' );
}

/**
 * Canonical URL for the current request.
 *
 * @return string
 */
function ace_canonical_url() {
	if ( is_front_page() ) {
		return home_url( '/' );
	}
	if ( is_singular() ) {
		return (string) get_permalink();
	}
	global $wp;
	return home_url( $wp->request ? '/' . $wp->request . '/' : '/' );
}

/**
 * Print description, Open Graph and Twitter card tags.
 */
function ace_share_meta() {
	if ( is_admin() || is_feed() || is_robots() || ace_seo_plugin_active() ) {
		return;
	}

	$title = wp_get_document_title();
	$desc  = ace_meta_description();
	$url   = ace_canonical_url();
	$image = ace_share_image();
	$type  = 'website';
	if ( is_singular( 'product' ) ) {
		$type = 'product';
	} elseif ( is_singular( 'post' ) ) {
		$type = 'article';
	}

	$tags = array(
		'og:type'        => $type,
		'og:site_name'   => get_bloginfo( 'name' ),
		'og:title'       => $title,
		'og:description' => $desc,
		'og:url'         => $url,
		'og:locale'      => get_locale(),
		'og:image'       => $image['url'],
	);
	if ( $image['width'] && $image['height'] ) {
		$tags['og:image:width']  = $image['width'];
		$tags['og:image:height'] = $image['height'];
	}
	$tags['og:image:alt'] = $title;

	printf( '<meta name="description" content="%s">' . "\n", esc_attr( $desc ) );
	printf( '<link rel="canonical" href="%s">' . "\n", esc_url( $url ) );
	foreach ( $tags as $property => $content ) {
		printf( '<meta property="%s" content="%s">' . "\n", esc_attr( $property ), esc_attr( $content ) );
	}
	echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
	printf( '<meta name="twitter:title" content="%s">' . "\n", esc_attr( $title ) );
	printf( '<meta name="twitter:description" content="%s">' . "\n", esc_attr( $desc ) );
	printf( '<meta name="twitter:image" content="%s">' . "\n", esc_url( $image['url'] ) );

	if ( is_front_page() ) {
		$org = array(
			'@context' => 'https://schema.org',
			'@type'    => 'Organization',
			'name'     => get_bloginfo( 'name' ),
			'url'      => home_url( '/' ),
			'logo'     => ACE_URI . '/assets/img/icon-192.png',
		);
		$same = array_values( array_filter( array( get_theme_mod( 'ace_facebook' ), get_theme_mod( 'ace_instagram' ), get_theme_mod( 'ace_x' ), get_theme_mod( 'ace_youtube' ) ) ) );
		if ( $same ) {
			$org['sameAs'] = $same;
		}
		echo '<script type="application/ld+json">' . wp_json_encode( $org, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "</script>\n";
	}
}
add_action( 'wp_head', 'ace_share_meta', 5 );

/**
 * WordPress prints its own canonical link; drop it so ours is the only one.
 */
function ace_drop_default_canonical() {
	if ( ! ace_seo_plugin_active() ) {
		remove_action( 'wp_head', 'rel_canonical' );
	}
}
add_action( 'wp', 'ace_drop_default_canonical' );
