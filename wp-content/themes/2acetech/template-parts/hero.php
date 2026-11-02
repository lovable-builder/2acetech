<?php
/**
 * Scroll-driven hero.
 *
 * A tall track holds a sticky stage. assets/js/hero.js maps scroll progress
 * across the track to the scenes: copy, product art and background cross-fade
 * and move with the scroll position. Without JS the first scene shows as a
 * normal static hero.
 *
 * @package 2acetech
 */

defined( 'ABSPATH' ) || exit;

$scenes = ace_hero_scenes();
$count  = count( $scenes );
if ( ! $count ) {
	return;
}
?>
<section class="hero" data-hero data-scenes="<?php echo (int) $count; ?>" style="--scenes:<?php echo (int) $count; ?>" aria-label="<?php esc_attr_e( 'Featured collections', '2acetech' ); ?>">
	<div class="hero__track">
		<div class="hero__stage">

			<div class="hero__bg" aria-hidden="true">
				<?php foreach ( $scenes as $i => $scene ) : ?>
					<span class="hero__bgl tone-<?php echo esc_attr( $scene['tone'] ); ?><?php echo 0 === $i ? ' is-first' : ''; ?>" data-bg></span>
				<?php endforeach; ?>
				<span class="hero__grid"></span>
				<span class="hero__orb" data-orb></span>
			</div>

			<div class="container hero__inner">
				<div class="hero__copy">
					<?php foreach ( $scenes as $i => $scene ) : ?>
						<?php $tag = 0 === $i ? 'h1' : 'h2'; ?>
						<div class="scene scene--copy<?php echo 0 === $i ? ' is-active' : ''; ?>" data-scene="<?php echo (int) $i; ?>" data-copy>
							<p class="eyebrow"><span class="eyebrow__n"><?php echo esc_html( sprintf( '%02d', $i + 1 ) ); ?></span><?php echo esc_html( $scene['eyebrow'] ); ?></p>
							<<?php echo $tag; // phpcs:ignore WordPress.Security.EscapeOutput ?> class="hero__title"><?php echo ace_split_words( $scene['title'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></<?php echo $tag; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
							<p class="hero__text"><?php echo esc_html( $scene['text'] ); ?></p>
							<a class="btn btn--primary" href="<?php echo esc_url( $scene['url'] ); ?>"><?php echo esc_html( $scene['cta'] ); ?><?php echo ace_icon( 'arrow', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
						</div>
					<?php endforeach; ?>
				</div>

				<div class="hero__visual" aria-hidden="true">
					<?php foreach ( $scenes as $i => $scene ) : ?>
						<div class="scene scene--art<?php echo 0 === $i ? ' is-active' : ''; ?>" data-scene="<?php echo (int) $i; ?>" data-art>
							<span class="glow"></span>
							<div class="art-wrap" data-art-wrap><?php echo ace_art( $scene['art'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
							<?php foreach ( (array) $scene['chips'] as $c => $chip ) : ?>
								<span class="chip chip--<?php echo (int) ( $c + 1 ); ?>" data-chip><?php echo esc_html( $chip ); ?></span>
							<?php endforeach; ?>
						</div>
					<?php endforeach; ?>
				</div>
			</div>

			<div class="container hero__ui">
				<ol class="hero__dots">
					<?php foreach ( $scenes as $i => $scene ) : ?>
						<li>
							<button type="button" class="dot<?php echo 0 === $i ? ' is-active' : ''; ?>" data-dot="<?php echo (int) $i; ?>">
								<span class="dot__n"><?php echo esc_html( sprintf( '%02d', $i + 1 ) ); ?></span>
								<span class="dot__bar"><span class="dot__fill"></span></span>
								<span class="dot__label"><?php echo esc_html( $scene['eyebrow'] ); ?></span>
							</button>
						</li>
					<?php endforeach; ?>
				</ol>
				<p class="hero__cue" data-cue><span class="mouse"></span><?php esc_html_e( 'Scroll', '2acetech' ); ?></p>
			</div>

		</div>
	</div>
</section>
