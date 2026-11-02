<?php
/**
 * Landing page.
 *
 * Sections are template parts so they can be reordered or removed here. All
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
	get_template_part( 'template-parts/trust' );
	get_template_part( 'template-parts/categories' );

	get_template_part(
		'template-parts/product-rail',
		null,
		array(
			'title' => __( 'Featured products', '2acetech' ),
			'type'  => 'featured',
			'limit' => 10,
		)
	);

	get_template_part( 'template-parts/promos' );
	get_template_part( 'template-parts/deal' );
	get_template_part( 'template-parts/brands' );
	get_template_part( 'template-parts/category-tabs' );

	get_template_part(
		'template-parts/product-rail',
		null,
		array(
			'title' => __( 'Bestsellers', '2acetech' ),
			'type'  => 'best',
			'limit' => 10,
		)
	);

	get_template_part( 'template-parts/newsletter' );
	?>
</main>
<?php
get_footer();
