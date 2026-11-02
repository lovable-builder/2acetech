<?php
/**
 * Promo banners, edited in Customize > Landing page > Promo banners.
 *
 * @package 2acetech
 */

defined( 'ABSPATH' ) || exit;

$promos = ace_promos();
if ( ! $promos ) {
	return;
}
?>
<section class="section section--tight" data-reveal>
	<div class="container promos">
		<?php foreach ( $promos as $promo ) : ?>
			<a class="promo promo--<?php echo esc_attr( 'dark' === $promo['tone'] ? 'dark' : 'light' ); ?>" href="<?php echo esc_url( $promo['url'] ); ?>">
				<span class="promo__copy">
					<span class="promo__kicker"><?php echo esc_html( $promo['kicker'] ); ?></span>
					<span class="promo__title"><?php echo esc_html( $promo['title'] ); ?></span>
					<span class="promo__cta"><?php echo esc_html( $promo['cta'] ); ?><?php echo ace_icon( 'arrow', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
				</span>
				<span class="promo__art"><?php echo ace_visual( $promo['image'], $promo['art'], 'medium_large' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
			</a>
		<?php endforeach; ?>
	</div>
</section>
