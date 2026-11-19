<?php
/**
 * Category row: round photo tiles with the name underneath (like Amazon's
 * "shop by category" strip). The photo is the category's own picture or, if it
 * has none, a product photo from it.
 *
 * @package 2acetech
 */

defined( 'ABSPATH' ) || exit;

$cats = ace_category_list( 8 );
if ( ! $cats ) {
	return;
}
?>
<section class="section section--tight" data-reveal aria-labelledby="cats-title">
	<div class="container">
		<header class="section__head">
			<h2 class="section__title" id="cats-title"><?php echo esc_html( ace_text( 'ace_title_categories' ) ); ?></h2>
			<a class="link-more" href="<?php echo esc_url( ace_shop_url() ); ?>"><?php esc_html_e( 'All products', '2acetech' ); ?><?php echo ace_icon( 'arrow', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
		</header>

		<ul class="catrow">
			<?php foreach ( $cats as $cat ) : ?>
				<li>
					<a class="catrow__item" href="<?php echo esc_url( $cat['url'] ); ?>">
						<span class="catrow__img">
							<?php
							if ( $cat['thumb'] ) {
								echo $cat['thumb']; // phpcs:ignore WordPress.Security.EscapeOutput
							} elseif ( $cat['art'] ) {
								echo ace_art( $cat['art'] ); // phpcs:ignore WordPress.Security.EscapeOutput
							} else {
								echo '<span class="catrow__initial" aria-hidden="true">' . esc_html( mb_substr( $cat['label'], 0, 1 ) ) . '</span>';
							}
							?>
						</span>
						<span class="catrow__name"><?php echo esc_html( $cat['label'] ); ?></span>
						<?php if ( $cat['count'] ) : ?>
							<span class="catrow__count">
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
