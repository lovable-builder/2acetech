<?php
/**
 * Site footer.
 *
 * @package 2acetech
 */

defined( 'ABSPATH' ) || exit;

$address = get_theme_mod( 'ace_address', '' );
$phone   = get_theme_mod( 'ace_phone', '' );
$email   = get_theme_mod( 'ace_email', 'info@2acetech.pl' );
$social  = array(
	'facebook'  => array( get_theme_mod( 'ace_facebook', '' ), 'Facebook' ),
	'instagram' => array( get_theme_mod( 'ace_instagram', '' ), 'Instagram' ),
	'x'         => array( get_theme_mod( 'ace_x', '' ), 'X' ),
	'youtube'   => array( get_theme_mod( 'ace_youtube', '' ), 'YouTube' ),
);
?>
<footer class="site-footer">
	<div class="container footer__grid">
		<div class="footer__brand">
			<?php ace_footer_logo(); ?>
			<p class="footer__tagline"><?php echo esc_html( ace_text( 'ace_footer_tagline' ) ); ?></p>

			<ul class="footer__contact">
				<?php if ( $phone ) : ?>
					<li><?php echo ace_icon( 'phone', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><a href="tel:<?php echo esc_attr( preg_replace( '/[^\d+]/', '', $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a></li>
				<?php endif; ?>
				<?php if ( $email ) : ?>
					<li><?php echo ace_icon( 'mail', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a></li>
				<?php endif; ?>
				<?php if ( $address ) : ?>
					<li><?php echo ace_icon( 'pin', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php echo esc_html( $address ); ?></span></li>
				<?php endif; ?>
			</ul>

			<ul class="social">
				<?php foreach ( $social as $key => $item ) : ?>
					<?php if ( $item[0] ) : ?>
						<li><a href="<?php echo esc_url( $item[0] ); ?>" rel="noopener" target="_blank"><span class="screen-reader-text"><?php echo esc_html( $item[1] ); ?></span><?php echo ace_icon( $key, 20 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a></li>
					<?php endif; ?>
				<?php endforeach; ?>
			</ul>
		</div>

		<nav class="footer__col" aria-labelledby="footer-shop">
			<h2 id="footer-shop"><?php esc_html_e( 'Shop', '2acetech' ); ?></h2>
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'footer-shop',
					'container'      => false,
					'menu_class'     => 'menu',
					'depth'          => 1,
					'fallback_cb'    => static function () {
						ace_footer_fallback( 'shop' );
					},
				)
			);
			?>
		</nav>

		<nav class="footer__col" aria-labelledby="footer-help">
			<h2 id="footer-help"><?php esc_html_e( 'Customer care', '2acetech' ); ?></h2>
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'footer-help',
					'container'      => false,
					'menu_class'     => 'menu',
					'depth'          => 1,
					'fallback_cb'    => static function () {
						ace_footer_fallback( 'help' );
					},
				)
			);
			?>
		</nav>

		<?php if ( is_active_sidebar( 'footer-widgets' ) ) : ?>
			<div class="footer__col footer__widgets"><?php dynamic_sidebar( 'footer-widgets' ); ?></div>
		<?php endif; ?>
	</div>

	<div class="footer__bar">
		<div class="container footer__bar-inner">
			<p>&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?>. <?php esc_html_e( 'All rights reserved.', '2acetech' ); ?></p>
			<p class="footer__legal">
				<?php
				$terms_id = function_exists( 'wc_terms_and_conditions_page_id' ) ? (int) wc_terms_and_conditions_page_id() : 0;
				if ( $terms_id ) {
					printf( '<a href="%s">%s</a>', esc_url( get_permalink( $terms_id ) ), esc_html( get_the_title( $terms_id ) ) );
				}
				if ( function_exists( 'the_privacy_policy_link' ) ) {
					the_privacy_policy_link();
				}
				?>
			</p>
		</div>
		<?php
		$seller    = trim( (string) get_theme_mod( 'ace_seller', '' ) );
		$seller_id = trim( (string) get_theme_mod( 'ace_seller_id', '' ) );
		if ( $seller ) :
			$line = array_filter( array( $seller, $address, $seller_id ) );
			?>
			<div class="container"><p class="footer__seller"><?php echo esc_html( implode( ' · ', $line ) ); ?></p></div>
		<?php endif; ?>
	</div>
</footer>

<button class="to-top" type="button" data-to-top aria-label="<?php esc_attr_e( 'Back to top', '2acetech' ); ?>" hidden>
	<?php echo ace_icon( 'up', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
</button>

<?php wp_footer(); ?>
</body>
</html>
