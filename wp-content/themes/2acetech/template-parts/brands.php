<?php
/**
 * Brand strip. Uses WooCommerce Brands (logos) when the store has them,
 * otherwise placeholder names.
 *
 * @package 2acetech
 */

defined( 'ABSPATH' ) || exit;

$brands = ace_brand_list();
if ( ! $brands ) {
	return;
}
?>
<section class="brands" aria-label="<?php esc_attr_e( 'Brands', '2acetech' ); ?>">
	<div class="container">
		<ul class="brands__list">
			<?php foreach ( $brands as $brand ) : ?>
				<li>
					<?php if ( $brand['url'] ) : ?>
						<a class="brand" href="<?php echo esc_url( $brand['url'] ); ?>">
					<?php else : ?>
						<span class="brand">
					<?php endif; ?>
						<?php
						if ( $brand['logo'] ) {
							echo $brand['logo']; // phpcs:ignore WordPress.Security.EscapeOutput
						} else {
							echo esc_html( $brand['label'] );
						}
						?>
					<?php echo $brand['url'] ? '</a>' : '</span>'; ?>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
