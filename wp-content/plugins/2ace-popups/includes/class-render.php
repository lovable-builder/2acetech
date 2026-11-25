<?php
/**
 * Builds the pop-up markup from a pop-up's settings. The same markup is used on the live site and in the
 * editor's live preview.
 *
 * @package 2ace-popups
 */

defined( 'ABSPATH' ) || exit;

class ACEPU_Render {

	/** Short label of the discount: "10%" or "20 zł". */
	public static function discount( $s ) {
		return 'percent' === $s['gen_type'] ? $s['gen_amount'] . '%' : $s['gen_amount'] . ' zł';
	}

	/** Replace {discount}, {coupon_code}, {end_date}, {site}. */
	public static function tokens( $text, $s ) {
		$end = ACEPU_Store::ts( $s['end'] );
		return strtr(
			(string) $text,
			array(
				'{discount}'    => 'email' === $s['mode'] ? self::discount( $s ) : '',
				'{coupon_code}' => 'code' === $s['mode'] ? $s['code'] : '',
				'{end_date}'    => $end ? wp_date( 'j.m.Y, H:i', $end ) : '',
				'{site}'        => get_bloginfo( 'name' ),
			)
		);
	}

	/** "#rrggbb" + percent -> rgba(). */
	private static function rgba( $hex, $pct ) {
		$hex = ltrim( $hex, '#' );
		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}
		return sprintf( 'rgba(%d,%d,%d,%s)', hexdec( substr( $hex, 0, 2 ) ), hexdec( substr( $hex, 2, 2 ) ), hexdec( substr( $hex, 4, 2 ) ), round( $pct / 100, 2 ) );
	}

	/** Address that applies a coupon to the cart and then goes on to $to. */
	public static function apply_url( $code, $to ) {
		$args = array( 'acepu_coupon' => rawurlencode( $code ) );
		if ( $to ) {
			$args['to'] = rawurlencode( $to );
		}
		return add_query_arg( $args, home_url( '/' ) );
	}

	/**
	 * @param int   $id   Pop-up id.
	 * @param array $s    Settings (full, with defaults).
	 * @param array $args static: show in place (editor preview).
	 * @return string
	 */
	public static function html( $id, $s, $args = array() ) {
		$static = ! empty( $args['static'] );
		$layout = $s['layout'];
		$pos    = in_array( $layout, array( 'bar', 'slide' ), true ) && 'left' === $s['image_pos'] ? 'left' : $s['image_pos'];
		$mode   = $s['mode'];
		$to     = $s['cta_url'] ? $s['cta_url'] : ( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' ) );
		$img    = $s['image_id'] ? wp_get_attachment_image( $s['image_id'], 'large', false, array( 'class' => 'acepu__img', 'alt' => '', 'loading' => 'lazy', 'decoding' => 'async' ) ) : '';
		$end    = ACEPU_Store::ts( $s['end'] );
		$style  = sprintf(
			'--bg:%s;--fg:%s;--ac:%s;--acfg:%s;--ov:%s;--r:%dpx;--w:%dpx;',
			$s['bg'],
			$s['fg'],
			$s['accent'],
			$s['accent_fg'],
			self::rgba( $s['overlay'], $s['overlay_op'] ),
			$s['radius'],
			$s['width']
		);
		$classes = array( 'acepu', 'acepu--' . $layout, 'acepu--img-' . $pos, 'acepu--anim-' . $s['anim'] );
		if ( $s['blur'] ) {
			$classes[] = 'acepu--blur';
		}
		if ( ! $img && 'background' !== $pos ) {
			$classes[] = 'acepu--noimg';
		}
		if ( ! empty( $args['phone'] ) ) {
			$classes[] = 'acepu--phone';
		}
		if ( $static ) {
			$classes[] = 'acepu--static';
			$classes[] = 'is-open';
		}
		// In the editor the preview sits inside WordPress's own <form>, and browsers drop a nested one.
		$ftag     = $static ? 'div' : 'form';
		$code_url = 'code' === $mode && $s['code'] ? self::apply_url( $s['code'], $to ) : '';

		ob_start();
		?>
		<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" id="acepu-<?php echo (int) $id; ?>" role="dialog" aria-modal="<?php echo in_array( $layout, array( 'bar', 'slide' ), true ) ? 'false' : 'true'; ?>" aria-labelledby="acepu-<?php echo (int) $id; ?>-t" style="<?php echo esc_attr( $style ); ?>"<?php echo $static ? '' : ' hidden'; ?> data-click-close="<?php echo (int) $s['click_close']; ?>">
			<div class="acepu__overlay" data-acepu-close></div>
			<div class="acepu__box">
				<button type="button" class="acepu__x<?php echo $s['close_text'] ? ' acepu__x--text' : ''; ?>" data-acepu-close aria-label="<?php esc_attr_e( 'Close', '2ace-popups' ); ?>">
					<?php if ( $s['close_text'] ) : ?><span><?php esc_html_e( 'Close', '2ace-popups' ); ?></span><?php endif; ?>
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg>
				</button>

				<?php if ( 'background' === $pos && $img ) : ?>
					<div class="acepu__bg" aria-hidden="true"><?php echo $img; // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
				<?php elseif ( 'bar' !== $layout ) : ?>
					<div class="acepu__media<?php echo $img ? '' : ' acepu__media--ph'; ?>" aria-hidden="true">
						<?php echo $img; // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<?php if ( $s['badge'] ) : ?><span class="acepu__badge"><?php echo esc_html( $s['badge'] ); ?></span><?php endif; ?>
					</div>
				<?php endif; ?>

				<div class="acepu__body">
					<?php if ( $s['eyebrow'] ) : ?><p class="acepu__eyebrow"><?php echo esc_html( self::tokens( $s['eyebrow'], $s ) ); ?></p><?php endif; ?>
					<h2 class="acepu__title" id="acepu-<?php echo (int) $id; ?>-t"><?php echo esc_html( self::tokens( $s['headline'], $s ) ); ?></h2>
					<?php if ( $s['text'] ) : ?><p class="acepu__text"><?php echo esc_html( self::tokens( $s['text'], $s ) ); ?></p><?php endif; ?>

					<?php if ( $s['countdown'] && $end ) : ?>
						<div class="acepu__timer" data-end="<?php echo (int) $end; ?>" aria-live="off">
							<span class="acepu__timer-label"><?php esc_html_e( 'Offer ends in', '2ace-popups' ); ?></span>
							<span class="acepu__timer-val"><b data-t="d">00</b><i><?php esc_html_e( 'd', '2ace-popups' ); ?></i><b data-t="h">00</b><i><?php esc_html_e( 'h', '2ace-popups' ); ?></i><b data-t="m">00</b><i><?php esc_html_e( 'min', '2ace-popups' ); ?></i><b data-t="s">00</b><i><?php esc_html_e( 's', '2ace-popups' ); ?></i></span>
						</div>
					<?php endif; ?>

					<?php if ( 'code' === $mode && $s['code'] ) : ?>
						<div class="acepu__code">
							<code><?php echo esc_html( $s['code'] ); ?></code>
							<button type="button" data-acepu-copy="<?php echo esc_attr( $s['code'] ); ?>"><?php esc_html_e( 'Copy', '2ace-popups' ); ?></button>
						</div>
					<?php endif; ?>

					<?php if ( 'email' === $mode ) : ?>
						<<?php echo $ftag; // phpcs:ignore WordPress.Security.EscapeOutput ?> class="acepu__form" data-acepu-form<?php echo $static ? '' : ' novalidate'; ?>>
							<input type="hidden" name="popup" value="<?php echo (int) $id; ?>">
							<label class="screen-reader-text acepu__sr" for="acepu-<?php echo (int) $id; ?>-e"><?php esc_html_e( 'Your e-mail address', '2ace-popups' ); ?></label>
							<div class="acepu__row">
								<input type="email" id="acepu-<?php echo (int) $id; ?>-e" name="email" autocomplete="email" required placeholder="<?php echo esc_attr( $s['email_ph'] ? $s['email_ph'] : __( 'Your e-mail address', '2ace-popups' ) ); ?>">
								<button type="submit" class="acepu__btn"><?php echo esc_html( $s['cta_label'] ? $s['cta_label'] : __( 'Get my code', '2ace-popups' ) ); ?></button>
							</div>
							<?php if ( $s['consent_text'] ) : ?>
								<label class="acepu__check"><input type="checkbox" name="c1" value="1" required><span><?php echo esc_html( $s['consent_text'] ); ?>
									<?php
									$pp = function_exists( 'get_privacy_policy_url' ) ? get_privacy_policy_url() : '';
									if ( $pp ) :
										?>
										<a href="<?php echo esc_url( $pp ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Privacy policy', '2ace-popups' ); ?></a>
									<?php endif; ?></span></label>
							<?php endif; ?>
							<?php if ( $s['news_on'] && $s['news_text'] ) : ?>
								<label class="acepu__check"><input type="checkbox" name="c2" value="1"><span><?php echo esc_html( $s['news_text'] ); ?></span></label>
							<?php endif; ?>
							<input type="text" name="website" value="" tabindex="-1" autocomplete="off" class="acepu__hp" aria-hidden="true">
							<p class="acepu__err" role="alert" hidden></p>
						</<?php echo $ftag; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
						<div class="acepu__done" hidden aria-live="polite">
							<h3><?php echo esc_html( self::tokens( $s['done_title'] ? $s['done_title'] : __( 'Your code is ready!', '2ace-popups' ), $s ) ); ?></h3>
							<?php if ( $s['done_text'] ) : ?><p><?php echo esc_html( self::tokens( $s['done_text'], $s ) ); ?></p><?php endif; ?>
							<div class="acepu__code"><code data-acepu-code></code><button type="button" data-acepu-copy=""><?php esc_html_e( 'Copy', '2ace-popups' ); ?></button></div>
							<a class="acepu__btn" href="<?php echo esc_url( $to ); ?>" data-acepu-apply data-to="<?php echo esc_attr( $to ); ?>" data-cta><?php esc_html_e( 'Use it now', '2ace-popups' ); ?></a>
						</div>
					<?php elseif ( 'code' === $mode && $code_url && $s['cta_label'] ) : ?>
						<a class="acepu__btn" href="<?php echo esc_url( $code_url ); ?>" data-cta><?php echo esc_html( $s['cta_label'] ); ?></a>
					<?php elseif ( 'info' === $mode && $s['cta_label'] ) : ?>
						<a class="acepu__btn" href="<?php echo esc_url( $to ); ?>" data-cta><?php echo esc_html( $s['cta_label'] ); ?></a>
					<?php endif; ?>

					<?php if ( $s['dismiss_label'] ) : ?>
						<button type="button" class="acepu__no" data-acepu-close><?php echo esc_html( $s['dismiss_label'] ); ?></button>
					<?php endif; ?>
					<?php if ( $s['footnote'] ) : ?><p class="acepu__foot"><?php echo esc_html( self::tokens( $s['footnote'], $s ) ); ?></p><?php endif; ?>
				</div>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}
