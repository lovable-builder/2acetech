<?php
/**
 * Category carousel hero. See inc/category-hero.php.
 *
 * Plain markup first: without JavaScript it is a row of slides that can be
 * swiped or scrolled; assets/js/category-hero.js adds arrows, dots and autoplay.
 *
 * @package 2acetech
 */

defined( 'ABSPATH' ) || exit;

$slides = ace_category_hero_slides();
$total  = count( $slides );
if ( ! $total ) {
	return;
}
$tones = array_keys( ace_tone_choices() );
?>
<section class="chero" data-chero aria-roledescription="carousel" aria-label="<?php esc_attr_e( 'Product categories', '2acetech' ); ?>">
	<div class="chero__track" data-chero-track tabindex="0">
		<?php foreach ( $slides as $i => $slide ) : ?>
			<?php
			$tone = $tones[ $i % count( $tones ) ];
			$tag  = 0 === $i ? 'h1' : 'h2';
			?>
			<article class="chero__slide tone-<?php echo esc_attr( $tone ); ?><?php echo 0 === $i ? ' is-active' : ''; ?>" data-chero-slide data-label="<?php echo esc_attr( $slide['name'] ); ?>" role="group" aria-roledescription="slide" aria-label="<?php echo esc_attr( sprintf( /* translators: 1: slide number, 2: number of slides. */ __( 'Slide %1$d of %2$d', '2acetech' ), $i + 1, $total ) ); ?>">
				<div class="container chero__inner">
					<div class="chero__copy">
						<p class="chero__eyebrow">
							<span class="chero__n"><?php echo esc_html( sprintf( '%02d', $i + 1 ) ); ?></span>
							<?php echo esc_html( $slide['name'] ); ?>
							<span class="chero__count"><?php echo esc_html( sprintf( /* translators: %d: number of products. */ _n( '%d product', '%d products', $slide['count'], '2acetech' ), $slide['count'] ) ); ?></span>
						</p>
						<<?php echo $tag; // phpcs:ignore WordPress.Security.EscapeOutput ?> class="chero__title"><?php echo ace_banner_title_html( $slide['tagline'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></<?php echo $tag; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
						<?php if ( $slide['subs'] ) : ?>
							<ul class="chero__subs" aria-label="<?php esc_attr_e( 'Subcategories', '2acetech' ); ?>">
								<?php foreach ( $slide['subs'] as $sub ) : ?>
									<li><a href="<?php echo esc_url( $sub['url'] ); ?>"><?php echo esc_html( $sub['name'] ); ?></a></li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
						<a class="btn chero__btn" href="<?php echo esc_url( $slide['url'] ); ?>"><?php echo esc_html( sprintf( /* translators: %s: category name. */ __( 'Shop: %s', '2acetech' ), $slide['name'] ) ); ?><?php echo ace_icon( 'arrow', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
					</div>

					<div class="chero__visual">
						<?php foreach ( $slide['products'] as $n => $product ) : ?>
							<?php
							$img_id = $product->get_image_id();
							if ( ! $img_id ) {
								continue;
							}
							?>
							<a class="chero__photo chero__photo--<?php echo (int) ( $n + 1 ); ?>" href="<?php echo esc_url( get_permalink( $product->get_id() ) ); ?>" aria-label="<?php echo esc_attr( $product->get_name() ); ?>" tabindex="-1">
								<?php
								echo wp_get_attachment_image( // phpcs:ignore WordPress.Security.EscapeOutput
									$img_id,
									0 === $n ? 'large' : 'medium_large',
									false,
									array(
										'alt'      => '',
										'loading'  => ( 0 === $i && 0 === $n ) ? 'eager' : 'lazy',
										'decoding' => 'async',
									)
								);
								?>
							</a>
						<?php endforeach; ?>
					</div>
				</div>
			</article>
		<?php endforeach; ?>
	</div>

	<?php if ( $total > 1 ) : ?>
		<div class="container chero__ui">
			<div class="chero__arrows">
				<button type="button" class="chero__arrow" data-chero-prev aria-label="<?php esc_attr_e( 'Previous category', '2acetech' ); ?>"><?php echo ace_icon( 'left', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
				<button type="button" class="chero__arrow" data-chero-next aria-label="<?php esc_attr_e( 'Next category', '2acetech' ); ?>"><?php echo ace_icon( 'right', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
			</div>
			<ol class="chero__dots">
				<?php foreach ( $slides as $i => $slide ) : ?>
					<li><button type="button" class="chero__dot<?php echo 0 === $i ? ' is-active' : ''; ?>" data-chero-dot="<?php echo (int) $i; ?>" aria-label="<?php echo esc_attr( $slide['name'] ); ?>"><span class="chero__dot-label"><?php echo esc_html( $slide['name'] ); ?></span><span class="chero__dot-bar"><span class="chero__dot-fill"></span></span></button></li>
				<?php endforeach; ?>
			</ol>
		</div>
	<?php endif; ?>
</section>
