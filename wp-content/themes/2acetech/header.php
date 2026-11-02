<?php
/**
 * Site header: top bar, logo, search, account and cart, primary navigation.
 *
 * @package 2acetech
 */

defined( 'ABSPATH' ) || exit;

$phone   = get_theme_mod( 'ace_phone', '' );
$email   = get_theme_mod( 'ace_email', 'info@2acetech.pl' );
$message = get_theme_mod( 'ace_topbar', __( 'Fast, tracked delivery across Poland', '2acetech' ) );
$wish    = apply_filters( 'ace_wishlist_url', '' );
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="theme-color" content="#0b0b0d">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link screen-reader-text" href="#content"><?php esc_html_e( 'Skip to content', '2acetech' ); ?></a>

<div class="topbar">
	<div class="container topbar__inner">
		<p class="topbar__msg"><?php echo esc_html( $message ); ?></p>
		<ul class="topbar__links">
			<?php if ( $phone ) : ?>
				<li><a href="tel:<?php echo esc_attr( preg_replace( '/[^\d+]/', '', $phone ) ); ?>"><?php echo ace_icon( 'phone', 15 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( $phone ); ?></a></li>
			<?php endif; ?>
			<?php if ( $email ) : ?>
				<li><a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo ace_icon( 'mail', 15 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( $email ); ?></a></li>
			<?php endif; ?>
		</ul>
	</div>
</div>

<header class="site-header" data-header>
	<div class="container header__row">
		<button class="icon-btn nav-toggle" type="button" aria-expanded="false" aria-controls="site-nav" data-nav-toggle>
			<span class="screen-reader-text"><?php esc_html_e( 'Menu', '2acetech' ); ?></span>
			<?php echo ace_icon( 'menu', 24 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</button>

		<div class="site-brand"><?php ace_logo(); ?></div>

		<?php get_search_form(); ?>

		<div class="header__actions">
			<?php if ( $wish ) : ?>
				<a class="icon-btn" href="<?php echo esc_url( $wish ); ?>">
					<span class="screen-reader-text"><?php esc_html_e( 'Wishlist', '2acetech' ); ?></span>
					<?php echo ace_icon( 'heart', 22 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</a>
			<?php endif; ?>
			<a class="icon-btn" href="<?php echo esc_url( ace_account_url() ); ?>">
				<span class="screen-reader-text"><?php esc_html_e( 'My account', '2acetech' ); ?></span>
				<?php echo ace_icon( 'user', 22 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</a>
			<?php if ( ace_has_store() ) : ?>
				<a class="icon-btn cart-link" href="<?php echo esc_url( wc_get_cart_url() ); ?>">
					<span class="screen-reader-text"><?php esc_html_e( 'Cart', '2acetech' ); ?></span>
					<?php echo ace_icon( 'cart', 22 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<?php echo ace_cart_count_html(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</a>
			<?php endif; ?>
		</div>
	</div>

	<div class="header__nav" id="site-nav" data-nav>
		<div class="container nav__row">
			<div class="departments" data-departments>
				<button class="departments__btn" type="button" aria-expanded="false" aria-controls="departments-panel" data-departments-toggle>
					<?php echo ace_icon( 'menu', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<span><?php esc_html_e( 'Shop by category', '2acetech' ); ?></span>
					<?php echo ace_icon( 'chevron', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</button>
				<nav class="departments__panel" id="departments-panel" aria-label="<?php esc_attr_e( 'Product categories', '2acetech' ); ?>" hidden>
					<?php
					if ( has_nav_menu( 'departments' ) ) {
						wp_nav_menu( array( 'theme_location' => 'departments', 'container' => false, 'menu_class' => 'menu', 'depth' => 1 ) );
					} else {
						echo '<ul class="menu">';
						foreach ( ace_category_list( 12 ) as $cat ) {
							printf( '<li><a href="%s">%s%s</a></li>', esc_url( $cat['url'] ), esc_html( $cat['label'] ), ace_icon( 'right', 16 ) ); // phpcs:ignore WordPress.Security.EscapeOutput
						}
						printf( '<li class="menu__all"><a href="%s">%s%s</a></li>', esc_url( ace_shop_url() ), esc_html__( 'All products', '2acetech' ), ace_icon( 'arrow', 16 ) ); // phpcs:ignore WordPress.Security.EscapeOutput
						echo '</ul>';
					}
					?>
				</nav>
			</div>

			<nav class="primary-nav" aria-label="<?php esc_attr_e( 'Primary', '2acetech' ); ?>">
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'primary',
						'container'      => false,
						'menu_class'     => 'menu',
						'depth'          => 2,
						'fallback_cb'    => 'ace_primary_fallback',
					)
				);
				?>
			</nav>
		</div>
	</div>
</header>
