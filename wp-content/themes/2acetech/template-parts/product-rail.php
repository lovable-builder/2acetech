<?php
/**
 * Horizontal product rail with arrow controls (native scroll-snap).
 *
 * Args: title, type (featured|latest|sale|best), limit.
 *
 * @package 2acetech
 */

defined( 'ABSPATH' ) || exit;

$title = isset( $args['title'] ) ? $args['title'] : '';
$type  = isset( $args['type'] ) ? $args['type'] : 'latest';
$limit = isset( $args['limit'] ) ? (int) $args['limit'] : 10;

$items = ace_get_products( $type, $limit );
if ( ! $items ) {
	return;
}
$uid = wp_unique_id( 'rail-' );
?>
<section class="section" data-reveal aria-labelledby="<?php echo esc_attr( $uid ); ?>">
	<div class="container">
		<header class="section__head">
			<h2 class="section__title" id="<?php echo esc_attr( $uid ); ?>"><?php echo esc_html( $title ); ?></h2>
			<a class="link-more" href="<?php echo esc_url( ace_shop_url() ); ?>"><?php esc_html_e( 'View all', '2acetech' ); ?><?php echo ace_icon( 'arrow', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
			<div class="rail-nav">
				<button class="rail-nav__btn" type="button" data-rail-prev aria-label="<?php esc_attr_e( 'Previous products', '2acetech' ); ?>"><?php echo ace_icon( 'left', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
				<button class="rail-nav__btn" type="button" data-rail-next aria-label="<?php esc_attr_e( 'Next products', '2acetech' ); ?>"><?php echo ace_icon( 'right', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
			</div>
		</header>

		<div class="rail" data-rail tabindex="0" role="region" aria-label="<?php echo esc_attr( $title ); ?>">
			<?php foreach ( $items as $item ) : ?>
				<div class="rail__item"><?php ace_product_card( $item ); ?></div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
