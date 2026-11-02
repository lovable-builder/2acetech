<?php
/**
 * Two promo banners. Edit the copy here or filter 'ace_promos'.
 *
 * @package 2acetech
 */

defined( 'ABSPATH' ) || exit;

$shop   = ace_shop_url();
$promos = apply_filters(
	'ace_promos',
	array(
		array(
			'kicker' => __( 'Tablets & phones', '2acetech' ),
			'title'  => __( 'Shop and save big on the hottest tablets', '2acetech' ),
			'cta'    => __( 'Shop now', '2acetech' ),
			'url'    => ace_category_url( 'phones', $shop ),
			'art'    => 'tablet',
			'tone'   => 'light',
		),
		array(
			'kicker' => __( 'Gaming', '2acetech' ),
			'title'  => __( 'Level up your setup with new consoles', '2acetech' ),
			'cta'    => __( 'Explore gaming', '2acetech' ),
			'url'    => ace_category_url( 'gaming', $shop ),
			'art'    => 'console',
			'tone'   => 'dark',
		),
	)
);
?>
<section class="section section--tight" data-reveal>
	<div class="container promos">
		<?php foreach ( $promos as $promo ) : ?>
			<a class="promo promo--<?php echo esc_attr( $promo['tone'] ); ?>" href="<?php echo esc_url( $promo['url'] ); ?>">
				<span class="promo__copy">
					<span class="promo__kicker"><?php echo esc_html( $promo['kicker'] ); ?></span>
					<span class="promo__title"><?php echo esc_html( $promo['title'] ); ?></span>
					<span class="promo__cta"><?php echo esc_html( $promo['cta'] ); ?><?php echo ace_icon( 'arrow', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
				</span>
				<span class="promo__art"><?php echo ace_art( $promo['art'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
			</a>
		<?php endforeach; ?>
	</div>
</section>
