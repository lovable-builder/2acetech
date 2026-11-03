<?php
/**
 * Template Name: Withdrawal
 *
 * "Withdraw from contract here": the electronic withdrawal function. The
 * handler and the rules are in inc/withdrawal.php. The page is created
 * automatically; the footer links to it from every page.
 *
 * @package 2acetech
 */

defined( 'ABSPATH' ) || exit;

get_header();

$status  = isset( $_GET['wd'] ) ? sanitize_key( wp_unslash( $_GET['wd'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
$stamp   = isset( $_GET['t'] ) ? absint( $_GET['t'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
$order   = isset( $_GET['order'] ) ? sanitize_text_field( wp_unslash( $_GET['order'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
$user    = wp_get_current_user();
$name    = $user->exists() ? trim( $user->first_name . ' ' . $user->last_name ) : '';
$email   = $user->exists() ? $user->user_email : '';
$contact = sanitize_email( (string) get_theme_mod( 'ace_email', '' ) );
$when    = $stamp ? wp_date( 'Y-m-d H:i:s T', $stamp ) : '';

$messages = array(
	/* translators: %s: date and time. */
	'ok'      => array( 'status', sprintf( __( 'Your withdrawal was received on %s. We have sent a confirmation to your email address.', '2acetech' ), $when ) ),
	/* translators: 1: date and time, 2: contact email. */
	'saved'   => array( 'alert', sprintf( __( 'Your withdrawal was recorded on %1$s, but we could not send the confirmation email. Please keep this page (for example as a screenshot) and write to %2$s.', '2acetech' ), $when, $contact ? $contact : get_option( 'admin_email' ) ) ),
	'invalid' => array( 'alert', __( 'Please fill in your name, the order number and a valid email address.', '2acetech' ) ),
	'limit'   => array( 'alert', __( 'Too many attempts from this connection. Please try again later or email us.', '2acetech' ) ),
	'error'   => array( 'alert', __( 'The form expired. Please reload the page and try again.', '2acetech' ) ),
);
$show_form = ! in_array( $status, array( 'ok', 'saved' ), true );
?>
<main id="content" class="site-main">
	<div class="container">
		<?php
		while ( have_posts() ) {
			the_post();
			?>
			<header class="page-head"><h1 class="page-title"><?php the_title(); ?></h1></header>
			<?php if ( get_the_content() ) : ?>
				<div class="entry-content contact__intro"><?php the_content(); ?></div>
			<?php endif; ?>
		<?php } ?>

		<div class="contact contact--single">
			<form class="contact__form" id="withdrawal-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php if ( isset( $messages[ $status ] ) ) : ?>
					<p class="contact__msg<?php echo 'ok' === $status ? '' : ' is-error'; ?>" role="<?php echo esc_attr( $messages[ $status ][0] ); ?>"><?php echo esc_html( $messages[ $status ][1] ); ?></p>
				<?php endif; ?>

				<?php if ( $show_form ) : ?>
					<p class="contact__note"><?php esc_html_e( 'You have 14 days from receiving the goods to withdraw from the contract, without giving a reason. Fill in the details below and confirm.', '2acetech' ); ?></p>

					<input type="hidden" name="action" value="ace_withdrawal">
					<?php wp_nonce_field( 'ace_withdrawal', 'ace_wd_nonce' ); ?>
					<div class="hp" aria-hidden="true"><label>Website <input type="text" name="ace_website" tabindex="-1" autocomplete="off"></label></div>

					<p class="field"><label for="wd-name"><?php esc_html_e( 'First and last name', '2acetech' ); ?> *</label><input id="wd-name" name="ace_wd_name" type="text" required autocomplete="name" value="<?php echo esc_attr( $name ); ?>"></p>
					<div class="field-row">
						<p class="field"><label for="wd-order"><?php esc_html_e( 'Order number', '2acetech' ); ?> *</label><input id="wd-order" name="ace_wd_order" type="text" required value="<?php echo esc_attr( $order ); ?>"></p>
						<p class="field"><label for="wd-email"><?php esc_html_e( 'Email for the confirmation', '2acetech' ); ?> *</label><input id="wd-email" name="ace_wd_email" type="email" required autocomplete="email" value="<?php echo esc_attr( $email ); ?>"></p>
					</div>
					<p class="field"><label for="wd-note"><?php esc_html_e( 'Which goods? (leave empty for the whole order)', '2acetech' ); ?></label><textarea id="wd-note" name="ace_wd_note" rows="3"></textarea></p>

					<p class="contact__note">
						<?php esc_html_e( 'After you confirm, we send you an acknowledgement by email with the date and time we received your declaration.', '2acetech' ); ?>
						<?php
						$privacy = function_exists( 'get_privacy_policy_url' ) ? get_privacy_policy_url() : '';
						if ( $privacy ) :
							?>
							<a href="<?php echo esc_url( $privacy ); ?>"><?php esc_html_e( 'Privacy policy', '2acetech' ); ?></a>
						<?php endif; ?>
					</p>
					<button class="btn btn--primary" type="submit"><?php esc_html_e( 'Confirm withdrawal here', '2acetech' ); ?><?php echo ace_icon( 'send', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
				<?php endif; ?>
			</form>
		</div>
	</div>
</main>
<?php
get_footer();
