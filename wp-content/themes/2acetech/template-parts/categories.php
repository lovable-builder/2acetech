<?php
/**
 * Category tiles.
 *
 * @package 2acetech
 */

defined( 'ABSPATH' ) || exit;

$cats = ace_category_list( 6 );
if ( ! $cats ) {
	return;
}
?>
<section class="section" data-reveal aria-labelledby="cats-title">
	<div class="container">
		<header class="section__head">
			<h2 class="section__title" id="cats-title"><?php esc_html_e( 'Shop by category', '2acetech' ); ?></h2>
			<a class="link-more" href="<?php echo esc_url( ace_shop_url() ); ?>"><?php esc_html_e( 'All products', '2acetech' ); ?><?php echo ace_icon( 'arrow', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
		</header>

		<ul class="tiles">
			<?php foreach ( $cats as $cat ) : ?>
				<li>
					<a class="tile" href="<?php echo esc_url( $cat['url'] ); ?>">
						<span class="tile__media">
							<?php
							if ( $cat['thumb'] ) {
								echo $cat['thumb']; // phpcs:ignore WordPress.Security.EscapeOutput
							} elseif ( $cat['art'] ) {
								echo ace_art( $cat['art'] ); // phpcs:ignore WordPress.Security.EscapeOutput
							} else {
								echo '<span class="tile__initial" aria-hidden="true">' . esc_html( mb_substr( $cat['label'], 0, 1 ) ) . '</span>';
							}
							?>
						</span>
						<span class="tile__name"><?php echo esc_html( $cat['label'] ); ?></span>
						<?php if ( $cat['count'] ) : ?>
							<span class="tile__count">
								<?php
								/* translators: %d: number of products. */
								echo esc_html( sprintf( _n( '%d product', '%d products', $cat['count'], '2acetech' ), $cat['count'] ) );
								?>
							</span>
						<?php endif; ?>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
