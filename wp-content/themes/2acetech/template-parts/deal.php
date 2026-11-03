<?php
/**
 * Deal of the week with countdown, beside a tabbed product grid.
 *
 * The cards are the products ticked "Deal of the week" (inc/deal.php), else
 * the first product on sale. Each countdown runs to that product's sale end
 * date; without one it uses Customize > Store details, or the end of the week.
 *
 * @package 2acetech
 */

defined( 'ABSPATH' ) || exit;

$deals = ace_deal_products( 4 );
if ( ! $deals ) {
	$deals = ace_get_products( 'sale', 1 );
}

$tabs = array(
	'featured' => __( 'Featured', '2acetech' ),
	'latest'   => __( 'Latest', '2acetech' ),
	'sale'     => __( 'On sale', '2acetech' ),
);

$uid = wp_unique_id( 'deal-' );

if ( ! $deals ) {
	return;
}
?>
<section class="section" data-reveal aria-labelledby="<?php echo esc_attr( $uid ); ?>-title-0">
	<div class="container deal">

		<div class="deal__side">
			<div class="deal__cards">
				<?php
				foreach ( array_values( $deals ) as $i => $deal ) :
					if ( is_array( $deal ) ) {
						$d_title = $deal['title'];
						$d_url   = ace_shop_url();
						$d_price = ace_demo_price( $deal['price'], $deal['was'] );
						$d_media = ace_art( $deal['art'] );
						$d_end   = ace_deal_end();
					} else {
						$d_title = $deal->get_name();
						$d_url   = $deal->get_permalink();
						$d_price = $deal->get_price_html();
						$d_media = $deal->get_image( 'woocommerce_single', array( 'loading' => 'lazy' ) );
						$d_end   = ace_deal_end( $deal );
					}
					?>
					<article class="deal__card">
						<p class="deal__label"><span class="badge badge--sale"><?php esc_html_e( 'Deal of the week', '2acetech' ); ?></span></p>
						<a class="deal__media" href="<?php echo esc_url( $d_url ); ?>"><?php echo $d_media; // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
						<h3 class="deal__name" id="<?php echo esc_attr( "$uid-title-$i" ); ?>"><a href="<?php echo esc_url( $d_url ); ?>"><?php echo esc_html( $d_title ); ?></a></h3>
						<p class="deal__price"><?php echo wp_kses_post( $d_price ); ?></p>

						<p class="deal__hurry"><?php esc_html_e( 'Hurry up! Offer ends in:', '2acetech' ); ?></p>
						<div class="countdown" data-countdown="<?php echo esc_attr( $d_end ); ?>" role="timer" aria-live="off">
							<div><b data-cd="d">00</b><span><?php esc_html_e( 'days', '2acetech' ); ?></span></div>
							<div><b data-cd="h">00</b><span><?php esc_html_e( 'hrs', '2acetech' ); ?></span></div>
							<div><b data-cd="m">00</b><span><?php esc_html_e( 'min', '2acetech' ); ?></span></div>
							<div><b data-cd="s">00</b><span><?php esc_html_e( 'sec', '2acetech' ); ?></span></div>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
			<?php if ( count( $deals ) > 1 ) : ?>
				<p class="deal__more"><?php esc_html_e( 'Swipe for more offers', '2acetech' ); ?> &rarr;</p>
			<?php endif; ?>
		</div>

		<div class="deal__tabs" data-tabs>
			<div class="tabs__list" role="tablist" aria-label="<?php esc_attr_e( 'Product groups', '2acetech' ); ?>">
				<?php $n = 0; foreach ( $tabs as $key => $label ) : ?>
					<button class="tabs__tab" type="button" role="tab" id="<?php echo esc_attr( "$uid-tab-$key" ); ?>" aria-controls="<?php echo esc_attr( "$uid-panel-$key" ); ?>" aria-selected="<?php echo 0 === $n ? 'true' : 'false'; ?>" tabindex="<?php echo 0 === $n ? '0' : '-1'; ?>"><?php echo esc_html( $label ); ?></button>
					<?php $n++; endforeach; ?>
			</div>

			<?php $n = 0; foreach ( $tabs as $key => $label ) : ?>
				<div class="tabs__panel" role="tabpanel" id="<?php echo esc_attr( "$uid-panel-$key" ); ?>" aria-labelledby="<?php echo esc_attr( "$uid-tab-$key" ); ?>" <?php echo 0 === $n ? '' : 'hidden'; ?>>
					<div class="grid grid--3">
						<?php foreach ( ace_get_products( $key, 6 ) as $item ) : ?>
							<?php ace_product_card( $item ); ?>
						<?php endforeach; ?>
					</div>
				</div>
				<?php $n++; endforeach; ?>
		</div>

	</div>
</section>
