<?php
/**
 * Landing page.
 *
 * Sections are template parts, shown and ordered in Customize > Landing page. All
 * product data comes from WooCommerce when the store has products, and from
 * demo content otherwise (see inc/demo-data.php).
 *
 * @package 2acetech
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<main id="content" class="site-main front">
	<?php
	ace_demo_notice();

	// The top of the page: the category carousel (one slide per main category), unless an occasion
	// banner with dates is running (Customize > Landing page > Banner 1-3); else the always-on banner,
	// else the scroll hero if it has been switched on again, else nothing.
	if ( ace_category_hero_active() ) {
		get_template_part( 'template-parts/category-hero' );
	} elseif ( ace_active_banner() ) {
		get_template_part( 'template-parts/banner' );
	} elseif ( get_theme_mod( 'ace_hero_show', false ) ) {
		get_template_part( 'template-parts/hero' );
	}

	// Sections and their order come from Customize > Landing page > Sections.
	foreach ( ace_home_order() as $section ) {
		ace_render_home_section( $section );
	}
	?>
</main>
<?php
get_footer();
