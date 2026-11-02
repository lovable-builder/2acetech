<?php
/**
 * Trust strip under the hero.
 *
 * @package 2acetech
 */

defined( 'ABSPATH' ) || exit;

$items = array(
	array( 'truck', __( 'Fast delivery', '2acetech' ), __( 'Tracked shipping across Poland', '2acetech' ) ),
	array( 'shield', __( 'Secure checkout', '2acetech' ), __( 'Encrypted, trusted payments', '2acetech' ) ),
	array( 'return', __( 'Easy returns', '2acetech' ), __( '14-day right of withdrawal', '2acetech' ) ),
	array( 'badge', __( 'Warranty included', '2acetech' ), __( 'Manufacturer warranty on every product', '2acetech' ) ),
);
?>
<section class="trust" aria-label="<?php esc_attr_e( 'Why shop with us', '2acetech' ); ?>">
	<ul class="container trust__list">
		<?php foreach ( $items as $item ) : ?>
			<li class="trust__item">
				<span class="trust__icon"><?php echo ace_icon( $item[0], 26 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
				<span><strong><?php echo esc_html( $item[1] ); ?></strong><small><?php echo esc_html( $item[2] ); ?></small></span>
			</li>
		<?php endforeach; ?>
	</ul>
</section>
