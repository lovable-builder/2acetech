<?php
/**
 * Newsletter band. Uses a mailing-list shortcode if one is set, otherwise the
 * built-in form (see inc/newsletter.php).
 *
 * @package 2acetech
 */

defined( 'ABSPATH' ) || exit;

$shortcode = trim( (string) get_theme_mod( 'ace_newsletter_shortcode', '' ) );
$status    = isset( $_GET['subscribed'] ) ? sanitize_key( wp_unslash( $_GET['subscribed'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
?>
<section class="newsletter" id="newsletter" aria-labelledby="nl-title">
	<div class="container newsletter__inner">
		<div class="newsletter__copy">
			<span class="newsletter__icon"><?php echo ace_icon( 'send', 30 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
			<div>
				<h2 id="nl-title"><?php esc_html_e( 'Get the drop before everyone else', '2acetech' ); ?></h2>
				<p><?php esc_html_e( 'New arrivals and deals, straight to your inbox.', '2acetech' ); ?></p>
			</div>
		</div>

		<div class="newsletter__form">
			<?php if ( $shortcode ) : ?>
				<?php echo do_shortcode( wp_kses_post( $shortcode ) ); ?>
			<?php else : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="ace_newsletter">
					<?php wp_nonce_field( 'ace_newsletter', 'ace_nl_nonce' ); ?>
					<div class="hp" aria-hidden="true"><label>Website <input type="text" name="ace_website" tabindex="-1" autocomplete="off"></label></div>
					<label class="screen-reader-text" for="ace-nl-email"><?php esc_html_e( 'Email address', '2acetech' ); ?></label>
					<input id="ace-nl-email" type="email" name="ace_email" required placeholder="<?php esc_attr_e( 'Your email address', '2acetech' ); ?>" autocomplete="email">
					<button class="btn btn--dark" type="submit"><?php esc_html_e( 'Subscribe', '2acetech' ); ?></button>
				</form>
				<?php if ( 'ok' === $status ) : ?>
					<p class="newsletter__msg" role="status"><?php esc_html_e( 'Thanks, you are on the list.', '2acetech' ); ?></p>
				<?php elseif ( $status ) : ?>
					<p class="newsletter__msg is-error" role="alert"><?php esc_html_e( 'Please enter a valid email address and try again.', '2acetech' ); ?></p>
				<?php endif; ?>
			<?php endif; ?>
		</div>
	</div>
</section>
