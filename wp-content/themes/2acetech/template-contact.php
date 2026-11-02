<?php
/**
 * Template Name: Contact
 *
 * Contact details plus a message form (handled by inc/contact.php).
 *
 * @package 2acetech
 */

defined( 'ABSPATH' ) || exit;

get_header();

$phone     = get_theme_mod( 'ace_phone', '' );
$email     = get_theme_mod( 'ace_email', 'info@2acetech.pl' );
$address   = get_theme_mod( 'ace_address', '' );
$seller    = get_theme_mod( 'ace_seller', '' );
$seller_id = get_theme_mod( 'ace_seller_id', '' );
$status    = isset( $_GET['sent'] ) ? sanitize_key( wp_unslash( $_GET['sent'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
$privacy   = function_exists( 'get_privacy_policy_url' ) ? get_privacy_policy_url() : '';

$messages = array(
	'ok'      => array( 'status', __( 'Thank you, your message has been sent. We will reply as soon as we can.', '2acetech' ) ),
	'invalid' => array( 'alert', __( 'Please fill in your name, a valid email and a message.', '2acetech' ) ),
	'limit'   => array( 'alert', __( 'Too many messages from this connection. Please try again later.', '2acetech' ) ),
	'fail'    => array( 'alert', __( 'We could not send your message. Please email us directly.', '2acetech' ) ),
	'error'   => array( 'alert', __( 'The form expired. Please reload the page and try again.', '2acetech' ) ),
);
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

		<div class="contact">
			<form class="contact__form" id="contact-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<h2><?php esc_html_e( 'Send us a message', '2acetech' ); ?></h2>

				<?php if ( isset( $messages[ $status ] ) ) : ?>
					<p class="contact__msg<?php echo 'ok' === $status ? '' : ' is-error'; ?>" role="<?php echo esc_attr( $messages[ $status ][0] ); ?>"><?php echo esc_html( $messages[ $status ][1] ); ?></p>
				<?php endif; ?>

				<input type="hidden" name="action" value="ace_contact">
				<?php wp_nonce_field( 'ace_contact', 'ace_contact_nonce' ); ?>
				<div class="hp" aria-hidden="true"><label>Website <input type="text" name="ace_website" tabindex="-1" autocomplete="off"></label></div>

				<div class="field-row">
					<p class="field"><label for="ace-name"><?php esc_html_e( 'Your name', '2acetech' ); ?> *</label><input id="ace-name" name="ace_name" type="text" required autocomplete="name"></p>
					<p class="field"><label for="ace-cemail"><?php esc_html_e( 'Email', '2acetech' ); ?> *</label><input id="ace-cemail" name="ace_email" type="email" required autocomplete="email"></p>
				</div>
				<p class="field"><label for="ace-order"><?php esc_html_e( 'Order number (optional)', '2acetech' ); ?></label><input id="ace-order" name="ace_order" type="text"></p>
				<p class="field"><label for="ace-message"><?php esc_html_e( 'Message', '2acetech' ); ?> *</label><textarea id="ace-message" name="ace_message" rows="6" required></textarea></p>

				<p class="contact__note">
					<?php esc_html_e( 'We use your details only to answer this message.', '2acetech' ); ?>
					<?php if ( $privacy ) : ?>
						<a href="<?php echo esc_url( $privacy ); ?>"><?php esc_html_e( 'Privacy policy', '2acetech' ); ?></a>
					<?php endif; ?>
				</p>
				<button class="btn btn--primary" type="submit"><?php esc_html_e( 'Send message', '2acetech' ); ?><?php echo ace_icon( 'send', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
			</form>

			<aside class="contact__info" aria-label="<?php esc_attr_e( 'Contact details', '2acetech' ); ?>">
				<h2><?php esc_html_e( 'Contact details', '2acetech' ); ?></h2>
				<ul>
					<?php if ( $email ) : ?>
						<li><?php echo ace_icon( 'mail', 22 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><strong><?php esc_html_e( 'Email', '2acetech' ); ?></strong><a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a></span></li>
					<?php endif; ?>
					<?php if ( $phone ) : ?>
						<li><?php echo ace_icon( 'phone', 22 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><strong><?php esc_html_e( 'Phone', '2acetech' ); ?></strong><a href="tel:<?php echo esc_attr( preg_replace( '/[^\d+]/', '', $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a></span></li>
					<?php endif; ?>
					<?php if ( $address ) : ?>
						<li><?php echo ace_icon( 'pin', 22 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><strong><?php esc_html_e( 'Address', '2acetech' ); ?></strong><?php echo esc_html( $address ); ?></span></li>
					<?php endif; ?>
				</ul>
				<?php if ( $seller ) : ?>
					<p class="contact__seller"><strong><?php echo esc_html( $seller ); ?></strong><?php echo $seller_id ? '<br>' . esc_html( $seller_id ) : ''; ?></p>
				<?php endif; ?>
			</aside>
		</div>
	</div>
</main>
<?php
get_footer();
