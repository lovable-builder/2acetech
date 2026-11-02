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

	get_template_part( 'template-parts/hero' );

	// Sections and their order come from Customize > Landing page > Sections.
	foreach ( ace_home_order() as $section ) {
		ace_render_home_section( $section );
	}
	?>
</main>
<?php
get_footer();
