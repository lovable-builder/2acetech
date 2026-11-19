<?php
/**
 * Occasion banner (works as the homepage hero). See inc/banner.php.
 *
 * @package 2acetech
 */

defined( 'ABSPATH' ) || exit;

$b = ace_active_banner();
if ( ! $b ) {
	return;
}
$look = ace_banner_look( $b );

// Right side.
$media    = $b['media'];
$products = array();
if ( 'products' === $media ) {
	$products = ace_banner_products( $b );
	if ( ! $products ) {
		$media = $b['image'] ? 'image' : 'none';
	}
}
if ( 'image' === $media && ! $b['image'] ) {
	$media = 'none';
}

// Countdown.
$end_ts   = $b['countdown'] ? ace_banner_ts( $b['end'] ) : 0;
$running  = $end_ts && $end_ts > time();
$finished = $end_ts && ! $running;

$url  = $b['url'] ? $b['url'] : ace_shop_url();
$uid  = wp_unique_id( 'banner-' );
$cls  = 'banner banner--' . $look['preset'] . ' banner--' . $b['size'] . ' banner--media-' . $media . ' banner--' . $look['tone'];
?>
<section class="<?php echo esc_attr( $cls ); ?>" style="<?php echo esc_attr( $look['vars'] ); ?>" aria-labelledby="<?php echo esc_attr( $uid ); ?>-title">
	<?php if ( $look['decor'] ) : ?>
		<span class="banner__decor banner__decor--<?php echo esc_attr( $look['motion'] ); ?>" aria-hidden="true">
			<?php
			$xs = array( 6, 17, 29, 41, 54, 66, 78, 90 );
			foreach ( $look['decor'] as $i => $emoji ) {
				$x = $xs[ $i % count( $xs ) ];
				printf(
					'<i style="--x:%1$d%%;--y:%2$d%%;--s:%3$.1frem;--t:%4$ds;--d:-%5$ds">%6$s</i>',
					(int) $x,
					12 + ( ( $i * 23 ) % 68 ),
					1.3 + ( $i % 3 ) * 0.5,
					11 + ( $i % 4 ) * 3,
					( $i * 3 ) % 11,
					esc_html( $emoji )
				);
			}
			?>
		</span>
	<?php endif; ?>

	<div class="container banner__inner">
		<div class="banner__copy">
			<?php if ( $b['eyebrow'] ) : ?>
				<p class="banner__eyebrow"><?php echo esc_html( $b['eyebrow'] ); ?></p>
			<?php endif; ?>

			<h1 class="banner__title" id="<?php echo esc_attr( $uid ); ?>-title"><?php echo ace_banner_title_html( $b['title'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></h1>

			<?php if ( $b['text'] ) : ?>
				<p class="banner__text"><?php echo esc_html( $b['text'] ); ?></p>
			<?php endif; ?>

			<?php if ( $running ) : ?>
				<div class="banner__count">
					<?php if ( $b['cd_label'] ) : ?>
						<p class="banner__count-label"><?php echo esc_html( $b['cd_label'] ); ?></p>
					<?php endif; ?>
					<div class="countdown" data-countdown="<?php echo esc_attr( gmdate( 'c', $end_ts ) ); ?>" role="timer" aria-live="off">
						<div><b data-cd="d">00</b><span><?php esc_html_e( 'days', '2acetech' ); ?></span></div>
						<div><b data-cd="h">00</b><span><?php esc_html_e( 'hrs', '2acetech' ); ?></span></div>
						<div><b data-cd="m">00</b><span><?php esc_html_e( 'min', '2acetech' ); ?></span></div>
						<div><b data-cd="s">00</b><span><?php esc_html_e( 'sec', '2acetech' ); ?></span></div>
					</div>
					<?php if ( $b['ended'] ) : ?>
						<p class="banner__ended" data-cd-ended hidden><?php echo esc_html( $b['ended'] ); ?></p>
					<?php endif; ?>
				</div>
			<?php elseif ( $finished && $b['ended'] ) : ?>
				<p class="banner__ended"><?php echo esc_html( $b['ended'] ); ?></p>
			<?php endif; ?>

			<?php if ( $b['cta'] || $b['cta2'] ) : ?>
				<div class="banner__actions">
					<?php if ( $b['cta'] ) : ?>
						<a class="btn banner__btn" href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $b['cta'] ); ?><?php echo ace_icon( 'arrow', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
					<?php endif; ?>
					<?php if ( $b['cta2'] ) : ?>
						<a class="btn banner__btn banner__btn--ghost" href="<?php echo esc_url( $b['url2'] ? $b['url2'] : ace_shop_url() ); ?>"><?php echo esc_html( $b['cta2'] ); ?></a>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>

		<?php if ( 'image' === $media ) : ?>
			<div class="banner__media banner__media--image">
				<?php echo wp_get_attachment_image( $b['image'], 'large', false, array( 'class' => 'banner__img', 'alt' => '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</div>
		<?php elseif ( 'products' === $media ) : ?>
			<div class="banner__media banner__media--products">
				<div class="banner__rail rail" data-rail tabindex="0" role="region" aria-label="<?php esc_attr_e( 'Featured products', '2acetech' ); ?>">
					<?php foreach ( $products as $item ) : ?>
						<div class="rail__item"><?php ace_product_card( $item ); ?></div>
					<?php endforeach; ?>
				</div>
				<div class="banner__nav rail-nav">
					<button class="rail-nav__btn" type="button" data-rail-prev aria-label="<?php esc_attr_e( 'Previous products', '2acetech' ); ?>"><?php echo ace_icon( 'left', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
					<button class="rail-nav__btn" type="button" data-rail-next aria-label="<?php esc_attr_e( 'Next products', '2acetech' ); ?>"><?php echo ace_icon( 'right', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
				</div>
			</div>
		<?php endif; ?>
	</div>
</section>
