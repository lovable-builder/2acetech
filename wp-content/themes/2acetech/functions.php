<?php
/**
 * 2acetech theme functions.
 *
 * @package 2acetech
 */

defined( 'ABSPATH' ) || exit;

define( 'ACE_VERSION', wp_get_theme()->get( 'Version' ) );
define( 'ACE_DIR', get_template_directory() );
define( 'ACE_URI', get_template_directory_uri() );

/**
 * Cache-busting version for an asset: its modification time, so browsers fetch
 * new CSS/JS as soon as a theme update replaces the file.
 *
 * @param string $rel Path relative to the theme, e.g. assets/css/main.css.
 * @return string
 */
function ace_ver( $rel ) {
	$file = ACE_DIR . '/' . $rel;
	return file_exists( $file ) ? (string) filemtime( $file ) : ACE_VERSION;
}

require_once ACE_DIR . '/inc/art.php';
require_once ACE_DIR . '/inc/demo-data.php';
require_once ACE_DIR . '/inc/template-tags.php';
require_once ACE_DIR . '/inc/home.php';
require_once ACE_DIR . '/inc/customizer.php';
require_once ACE_DIR . '/inc/customizer-home.php';
require_once ACE_DIR . '/inc/newsletter.php';
require_once ACE_DIR . '/inc/seo.php';
require_once ACE_DIR . '/inc/contact.php';
require_once ACE_DIR . '/inc/withdrawal.php';
require_once ACE_DIR . '/inc/legal.php';
require_once ACE_DIR . '/inc/woocommerce.php';
require_once ACE_DIR . '/inc/product.php';
require_once ACE_DIR . '/inc/product-details.php';
require_once ACE_DIR . '/inc/deal.php';

/**
 * Theme setup: supports and menus.
 */
function ace_setup() {
	load_theme_textdomain( '2acetech', ACE_DIR . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'editor-styles' );
	add_theme_support(
		'html5',
		array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' )
	);
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 80,
			'width'       => 320,
			'flex-height' => true,
			'flex-width'  => true,
		)
	);

	add_image_size( 'ace-tile', 480, 480, true );

	register_nav_menus(
		array(
			'primary'     => __( 'Primary menu', '2acetech' ),
			'departments' => __( 'Departments (category dropdown)', '2acetech' ),
			'footer-shop' => __( 'Footer: Shop', '2acetech' ),
			'footer-help' => __( 'Footer: Customer care', '2acetech' ),
		)
	);
}
add_action( 'after_setup_theme', 'ace_setup' );

/**
 * Widget areas. Plugins and page builders commonly expect at least one.
 */
function ace_widgets() {
	register_sidebar(
		array(
			'name'          => __( 'Sidebar', '2acetech' ),
			'id'            => 'sidebar-1',
			'before_widget' => '<section id="%1$s" class="widget %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h2 class="widget__title">',
			'after_title'   => '</h2>',
		)
	);
	register_sidebar(
		array(
			'name'          => __( 'Footer widgets', '2acetech' ),
			'id'            => 'footer-widgets',
			'before_widget' => '<section id="%1$s" class="widget %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h2 class="widget__title">',
			'after_title'   => '</h2>',
		)
	);
}
add_action( 'widgets_init', 'ace_widgets' );

/**
 * Front-end assets.
 */
function ace_assets() {
	$css = ACE_URI . '/assets/css/';
	$js  = ACE_URI . '/assets/js/';

	wp_enqueue_style( 'ace-main', $css . 'main.css', array(), ace_ver( 'assets/css/main.css' ) );
	wp_enqueue_style( 'ace-hero', $css . 'hero.css', array( 'ace-main' ), ace_ver( 'assets/css/hero.css' ) );
	wp_enqueue_style( 'ace-woo', $css . 'woocommerce.css', array( 'ace-main' ), ace_ver( 'assets/css/woocommerce.css' ) );

	wp_enqueue_script( 'ace-main', $js . 'main.js', array(), ace_ver( 'assets/js/main.js' ), array( 'strategy' => 'defer', 'in_footer' => true ) );
	if ( is_front_page() ) {
		wp_enqueue_script( 'ace-hero', $js . 'hero.js', array(), ace_ver( 'assets/js/hero.js' ), array( 'strategy' => 'defer', 'in_footer' => true ) );
	}

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'ace_assets' );

/**
 * Preload the variable font so text does not flash.
 */
function ace_preload_font() {
	printf(
		'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
		esc_url( ACE_URI . '/assets/fonts/manrope-latin-wght-normal.woff2' )
	);
}
add_action( 'wp_head', 'ace_preload_font', 1 );

/**
 * Flag JS availability early so reveal animations never hide content without JS.
 */
function ace_js_flag() {
	echo "<script>document.documentElement.classList.add('js');</script>\n";
}
add_action( 'wp_head', 'ace_js_flag', 0 );

/**
 * Cap content width for the block editor / embeds.
 */
function ace_content_width() {
	$GLOBALS['content_width'] = 1240;
}
add_action( 'after_setup_theme', 'ace_content_width', 0 );
