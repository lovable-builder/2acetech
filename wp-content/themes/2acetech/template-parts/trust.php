<?php
/**
 * Trust strip under the hero.
 *
 * @package 2acetech
 */

defined( 'ABSPATH' ) || exit;

$items = ace_trust_items();
if ( ! $items ) {
	return;
}
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
